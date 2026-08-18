<?php
//Petit Note (c)さとぴあ @satopian 2021-2026 MIT License
//https://paintbbs.sakura.ne.jp/
//APIを使ってお絵かき掲示板からMisskeyにノート

$misskey_note_ver = 20260817;

$misskey_servers = $misskey_servers ??
	[

		["misskey.io", "https://misskey.io"],
		["xissmie.xfolio.jp", "https://xissmie.xfolio.jp"],
		["misskey.design", "https://misskey.design"],
		["nijimiss.moe", "https://nijimiss.moe"],
		["misskey.art", "https://misskey.art"],
		["oekakiskey.com", "https://oekakiskey.com"],
		["misskey.gamelore.fun", "https://misskey.gamelore.fun"],
		["novelskey.tarbin.net", "https://novelskey.tarbin.net"],
		["tyazzkey.work", "https://tyazzkey.work"],
		["sushi.ski", "https://sushi.ski"],
		["misskey.delmulin.com", "https://misskey.delmulin.com"],
		["side.misskey.productions", "https://side.misskey.productions"],
		["mk.shrimpia.network", "https://mk.shrimpia.network"],
		["misskey.id", "https://misskey.id"],

	];

class misskey_note
{

	//Misskeyに投稿するSESSIONデータを作成
	public static function create_misskey_note_sessiondata(): void
	{
		global $en, $usercode, $root_url, $skindir, $petit_lot, $misskey_servers, $boardname;

		check_csrf_token();

		$userip = t(get_uip());

		$pictmp = (int)filter_input_data('POST', 'pictmp', FILTER_VALIDATE_INT);
		$com = t((string)filter_input_data('POST', 'com'));
		$hide_thumbnail = (bool)filter_input_data('POST', 'hide_thumbnail', FILTER_VALIDATE_BOOLEAN);
		$hide_content = (bool)filter_input_data('POST', 'hide_content', FILTER_VALIDATE_BOOLEAN);
		$show_painttime = (bool)filter_input_data('POST', 'show_painttime', FILTER_VALIDATE_BOOLEAN);
		$show_tag = (bool)filter_input_data('POST', 'show_tag', FILTER_VALIDATE_BOOLEAN);
		$cw = t((string)filter_input_data('POST', 'cw'));

		if ($hide_content && !$cw) {
			error($en ? "Content warning field is empty." : "注釈がありません。");
		}
		check_AsyncRequest(); //Asyncリクエストの時は処理を中断

		$cw = $hide_content ? $cw : null;

		$pictmp2 = false;
		$tool = '';
		$painttime = '';
		$picfile_name = '';
		if ($pictmp === 2) { //ユーザーデータを調べる
			[$picfile,] = explode(",", (string)filter_input_data('POST', 'picfile'));
			$picfile_name = basename($picfile);
			$tempfile = TEMP_DIR . $picfile;
			$picfile = basename($picfile);
			$picfile = pathinfo($picfile, PATHINFO_FILENAME); //拡張子除去
			//選択された絵が投稿者の絵か再チェック
			if (!$picfile || !is_file(TEMP_DIR . $picfile . ".dat") || !is_file($tempfile) || !get_image_type($tempfile)) {
				error($en ? 'Posting failed.' : '投稿に失敗しました。');
			}
			//ユーザーデータから情報を取り出す
			$fp = fopen(TEMP_DIR . $picfile . ".dat", "r");
			$userdata = fread($fp, 1024);
			fclose($fp);
			[$uip, $uhost,,, $ucode,, $starttime, $postedtime, $uresto, $tool, $u_hide_animation] = explode("\t", rtrim($userdata) . "\t\t\t");
			if ((!$ucode || ($ucode != $usercode)) && (!$uip || ($uip != $userip))) {
				error($en ? 'Posting failed.' : '投稿に失敗しました。');
			}
			$tool = is_paint_tool_name($tool);
			//描画時間を$userdataをもとに計算
			if ($starttime && is_numeric($starttime) && $postedtime && is_numeric($postedtime)) {
				$painttime = (int)$postedtime - (int)$starttime;
			}


			$pictmp2 = true; //お絵かきでエラーがなかった時にtrue;

		}
		if (!$pictmp2) {
			error($en ? 'This operation has failed.' : '失敗しました。');
		}

		$tool = switch_tool($tool);

		$painttime = calcPtime($painttime);
		$painttime = $en ? $painttime['en'] : $painttime['ja'];
		$painttime = $show_painttime ? $painttime : '';
		session_sta();

		//SESSIONに投稿内容を格納
		$_SESSION['sns_api_val'] = [$com, $picfile_name, $tool, $painttime, $hide_thumbnail, $cw, $show_tag];

		$misskey_server_radio_cookie = (string)filter_input_data('COOKIE', "misskey_server_radio_cookie");
		$misskey_server_direct_input_cookie = ""; //テンプレート互換

		$admin_pass = null;
		// HTML出力
		$templete = 'post2misskey.html';
		include __DIR__ . '/' . $skindir . $templete;
		exit();
	}
	public static function create_misskey_authrequesturl(): void
	{
		global $root_url, $en, $petit_lot;
		check_same_origin();

		$misskey_server_radio = (string)filter_input_data('POST', "misskey_server_radio", FILTER_VALIDATE_URL);
		setcookie("misskey_server_radio_cookie", $misskey_server_radio, time() + (86400 * 30), "", "", false, true);

		if (!$misskey_server_radio) {
			error($en ? "Please select an misskey server." : "Misskeyサーバを選択してください。");
		}
		$arrowd = self::is_arrowd_url($misskey_server_radio);
		if (!$arrowd) {
			error($en ? "This is not a valid server URL." : "サーバのURLが無効です。", false);
		}

		session_sta();
		// セッションIDとユニークIDを結合
		$sns_api_session_id = session_id() . random_bytes(16);

		// SHA256ハッシュ化
		$sns_api_session_id = hash('sha256', $sns_api_session_id);

		$_SESSION['sns_api_session_id'] = $sns_api_session_id;

		$encoded_root_url = urlencode($root_url);

		//別のサーバを選択した時はトークンをクリア
		if (
			!isset($_SESSION['misskey_server_radio']) ||
			$_SESSION['misskey_server_radio'] !== $misskey_server_radio
		) {
			unset($_SESSION['accessToken']); //トークンをクリア
		}
		//投稿完了画面に表示するサーバのURl
		$_SESSION['misskey_server_radio'] = $misskey_server_radio;

		//アプリを認証するためのURL
		$Location = "{$misskey_server_radio}/miauth/{$sns_api_session_id}?name=Petit%20Note&callback={$encoded_root_url}connect_misskey_api.php&permission=write:notes,write:drive";

		if (isset($_SESSION['accessToken'])) { //SESSIONのトークンが有効か確認

			// ダミーの投稿を試みる（textフィールドを空にする）
			$postUrl = "{$misskey_server_radio}/api/notes/create";
			$postData = array(
				'i' => $_SESSION['accessToken'],
				'text' => '', // 投稿を成功させないようにするためtextフィールドを空にする
			);

			$postCurl = curl_init();
			curl_setopt($postCurl, CURLOPT_URL, $postUrl);
			curl_setopt($postCurl, CURLOPT_POST, true);
			curl_setopt($postCurl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
			curl_setopt($postCurl, CURLOPT_POSTFIELDS, json_encode($postData));
			curl_setopt($postCurl, CURLOPT_RETURNTRANSFER, true);
			$postResponse = curl_exec($postCurl);
			$postStatusCode = curl_getinfo($postCurl, CURLINFO_HTTP_CODE); // HTTPステータスコードを取得

			if (PHP_VERSION_ID < 80000) { //PHP8.0未満の時は
				curl_close($postCurl);
			}

			// HTTPステータスコードが403の時は、トークン不一致と判断しアプリを認証
			if ($postStatusCode === 403) {
				unset($_SESSION['accessToken']); //トークンをクリア
			} else {
				//アプリの認証をスキップするURL
				$Location = "{$root_url}connect_misskey_api.php?skip_auth_check=on&s_id={$sns_api_session_id}";
			}
		}

		redirect($Location);
	}
	// Misskeyへの投稿が成功した事を知らせる画面
	public static function misskey_success(): void
	{
		global $en, $skindir, $boardname, $petit_lot;
		$no = (string)filter_input_data('GET', 'no', FILTER_VALIDATE_INT);
		session_sta();
		$misskey_server_url = $_SESSION['misskey_server_radio'] ?? "";
		$arrowd = self::is_arrowd_url($misskey_server_url);

		if (!$arrowd) {
			redirect('./');
		}
		$templete = 'success.html';
		include $skindir . $templete;
		exit();
	}
	/** 
	 * リストに登録ずみのmisskeyサーバならtrueを返す
	 * $misskey_serversが未定義の時にもtrueが返る
	 * 	@param $baseUrl
	 *  @return bool
	 */
	public static function is_arrowd_url(string $baseUrl): bool
	{
		global $misskey_servers;

		if (!filter_var($baseUrl, FILTER_VALIDATE_URL)) {
			return false;
		}
		if (parse_url($baseUrl, PHP_URL_SCHEME) !== 'https') {
			return false;
		}
		$host = parse_url($baseUrl, PHP_URL_HOST);
		$ip = $host ? gethostbyname($host) : false;
		if (!$ip || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
			return false;
		}
		if (!isset($misskey_servers) || !is_array($misskey_servers)) {
			return false;
		}
		$arrowdServerURLs = [];
		foreach ($misskey_servers as $misskey_server) {
			[, $arrowdServerURL] = $misskey_server;
			$arrowdServerURLs[] = $arrowdServerURL;
		}
		if (in_array($baseUrl, $arrowdServerURLs, true)) {
			return true;
		}
		return false;
	}
}
