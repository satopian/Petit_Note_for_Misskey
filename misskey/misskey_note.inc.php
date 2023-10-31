<?php
//Petit Note 2021-2023 (c)satopian MIT LICENCE
//https://paintbbs.sakura.ne.jp/
//APIを使ってお絵かき掲示板からMisskeyにノート
$misskey_note_ver=20230809;
class misskey_note{

	//Misskeyに投稿するSESSIONデータを作成
	public static function create_misskey_note_sessiondata(){
		global $en,$usercode,$root_url,$skindir,$boardname,$petit_lot;
	
			$userip =t(get_uip());

			$pictmp = (int)filter_input(INPUT_POST, 'pictmp',FILTER_VALIDATE_INT);
			$com = t((string)filter_input(INPUT_POST,'com'));
			$hide_thumbnail = (bool)filter_input(INPUT_POST,'hide_thumbnail',FILTER_VALIDATE_BOOLEAN);
			$hide_content = (bool)filter_input(INPUT_POST,'hide_content',FILTER_VALIDATE_BOOLEAN);
			$show_painttime = (bool)filter_input(INPUT_POST,'show_painttime',FILTER_VALIDATE_BOOLEAN);
			$show_tag = (bool)filter_input(INPUT_POST,'show_tag',FILTER_VALIDATE_BOOLEAN);
			$cw = t((string)filter_input(INPUT_POST,'cw'));
			$cw = t((string)filter_input(INPUT_POST,'cw'));

			$cw = $hide_content ? $cw : null;

			$pictmp2=false;
			if($pictmp===2){//ユーザーデータを調べる
				list($picfile,) = explode(",",(string)filter_input(INPUT_POST, 'picfile'));
				$picfile_name=basename($picfile);
				$tempfile = TEMP_DIR.$picfile;
				$picfile=basename($picfile);
				$picfile=pathinfo($picfile, PATHINFO_FILENAME );//拡張子除去
				//選択された絵が投稿者の絵か再チェック
				if (!$picfile || !is_file(TEMP_DIR.$picfile.".dat") || !is_file($tempfile)||!get_image_type($tempfile)) {
					return error($en? 'Posting failed.':'投稿に失敗しました。');
				}
				//ユーザーデータから情報を取り出す
				$fp = fopen(TEMP_DIR.$picfile.".dat", "r");
				$userdata = fread($fp, 1024);
				fclose($fp);
				list($uip,$uhost,,,$ucode,,$starttime,$postedtime,$uresto,$tool,$u_hide_animation) = explode("\t", rtrim($userdata)."\t\t\t");
				if((!$ucode || ($ucode != $usercode)) && (!$uip || ($uip != $userip))){return error($en? 'Posting failed.':'投稿に失敗しました。');}
				$tool= in_array($tool,['neo','chi','klecks','tegaki']) ? $tool : '???';
				//描画時間を$userdataをもとに計算
				if($starttime && is_numeric($starttime) && $postedtime && is_numeric($postedtime)){
					$painttime=(int)$postedtime-(int)$starttime;
				}
				

				$pictmp2=true;//お絵かきでエラーがなかった時にtrue;
				
			}
			if(!$pictmp2){
				error($en ? 'This operation has failed.':'失敗しました。');
			}

			$tool=switch_tool($tool);
			
			$painttime=calcPtime($painttime);
			$painttime = $en ? $painttime['en'] : $painttime['ja'];
			$painttime = $show_painttime ? $painttime :'';
			session_sta();

			//SESSIONに投稿内容を格納
			$_SESSION['sns_api_val']=[$com,$picfile_name,$tool,$painttime,$hide_thumbnail,$cw,$show_tag];

			$misskey_servers=isset($misskey_servers)?$misskey_servers:
			[
			
				["misskey.io","https://misskey.io"],
				["misskey.design","https://misskey.design"],
				["nijimiss.moe","https://nijimiss.moe"],
				["misskey.art","https://misskey.art"],
				["oekakiskey.com","https://oekakiskey.com"],
				["misskey.gamelore.fun","https://misskey.gamelore.fun"],
				["novelskey.tarbin.net","https://novelskey.tarbin.net"],
				["tyazzkey.work","https://tyazzkey.work"],
				["sushi.ski","https://sushi.ski"],
				["misskey.delmulin.com","https://misskey.delmulin.com"],
			
			];
			$misskey_servers[]=[($en?"Direct input":"直接入力"),"direct"];//直接入力の箇所はそのまま。

			$misskey_server_radio_cookie=(string)filter_input(INPUT_COOKIE,"misskey_server_radio_cookie");
			$misskey_server_direct_input_cookie=(string)filter_input(INPUT_COOKIE,"misskey_server_direct_input_cookie");

			// HTML出力
			$templete='post2misskey.html';
			return include __DIR__.'/'.$skindir.$templete;
		}
	public static function create_misskey_authrequesturl(){
		global $root_url,$en,$petit_lot;

		$misskey_server_radio=(string)filter_input(INPUT_POST,"misskey_server_radio",FILTER_VALIDATE_URL);
		$misskey_server_radio_for_cookie=(string)filter_input(INPUT_POST,"misskey_server_radio");//directを判定するためurlでバリデーションしていない
		$misskey_server_radio_for_cookie=($misskey_server_radio_for_cookie === 'direct') ? 'direct' : $misskey_server_radio;
		$misskey_server_direct_input=(string)filter_input(INPUT_POST,"misskey_server_direct_input",FILTER_VALIDATE_URL);
		setcookie("misskey_server_radio_cookie",$misskey_server_radio_for_cookie, time()+(86400*30),"","",false,true);
		setcookie("misskey_server_direct_input_cookie",$misskey_server_direct_input, time()+(86400*30),"","",false,true);
		$share_url='';

		if(!$misskey_server_radio&&!$misskey_server_direct_input){
			error($en ? "Please select an misskey server.":"Misskeyサーバを選択してください。");
		}

		if(!$misskey_server_radio && $misskey_server_direct_input){
			$misskey_server_radio = $misskey_server_direct_input;
		}

		session_sta();
		// セッションIDとユニークIDを結合
		$sns_api_session_id = session_id() . uniqid();
		// SHA256ハッシュ化
		$sns_api_session_id=hash('sha256', $sns_api_session_id);

		$_SESSION['sns_api_session_id']=$sns_api_session_id;
		$_SESSION['misskey_server_radio']=$misskey_server_radio;

		$encoded_root_url = urlencode($root_url);

		if(isset($_SESSION['accessToken'])){

			// ダミーの投稿を試みる（textフィールドを空にする）
			$postUrl = "{$misskey_server_radio}/api/notes/create";
			$postData = array(
				'i' => $_SESSION['accessToken'],
				'text' => '', // textフィールドを空にする
			);

			$postCurl = curl_init();
			curl_setopt($postCurl, CURLOPT_URL, $postUrl);
			curl_setopt($postCurl, CURLOPT_POST, true);
			curl_setopt($postCurl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
			curl_setopt($postCurl, CURLOPT_POSTFIELDS, json_encode($postData));
			curl_setopt($postCurl, CURLOPT_RETURNTRANSFER, true);
			$postResponse = curl_exec($postCurl);
			$postStatusCode = curl_getinfo($postCurl, CURLINFO_HTTP_CODE); // HTTPステータスコードを取得
			curl_close($postCurl);

			// HTTPステータスコードが403番台でない場合は、ダミーの投稿が失敗したと判断
			if ($postStatusCode === 403) {
				$Location = "{$misskey_server_radio}/miauth/{$sns_api_session_id}?name=Petit%20Note&callback={$encoded_root_url}connect_misskey_api.php&permission=write:notes,write:drive";
			} else {
				$Location = "{$root_url}connect_misskey_api.php?noauth=on&s_id={$sns_api_session_id}";
			}

		}else{
			$Location = "{$misskey_server_radio}/miauth/{$sns_api_session_id}?name=Petit%20Note&callback={$encoded_root_url}connect_misskey_api.php&permission=write:notes,write:drive";

		}
		return header('Location:'.$Location);

	}

	// Misskeyへの投稿が成功した事を知らせる画面
	public static function misskey_success(){
		global $en,$skindir,$boardname;
		$no = (string)filter_input(INPUT_GET, 'no',FILTER_VALIDATE_INT);
		session_sta();
		$misskey_server_url = isset($_SESSION['misskey_server_radio']) ? $_SESSION['misskey_server_radio'] : "";
		if(!$misskey_server_url || !filter_var($misskey_server_url,FILTER_VALIDATE_URL)){
			return header('Location: ./');
		}
		$templete='success.html';
		return include $skindir.$templete;
	}
}

