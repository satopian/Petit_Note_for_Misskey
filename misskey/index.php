<?php
//Petit Note (c)さとぴあ @satopian 2021-2023
//1スレッド1ログファイル形式のスレッド式画像掲示板
$petit_ver='for_misskey';
$petit_lot='lot.20240110';
$lang = ($http_langs = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '')
  ? explode( ',', $http_langs )[0] : '';
$en= (stripos($lang,'ja')!==0);

if (version_compare(PHP_VERSION, '5.6.0', '<')) {
	die($en? "Error. PHP version 5.6.0 or higher is required for this program to work. <br>\n(Current PHP version:".PHP_VERSION.")":
		"エラー。本プログラムの動作には PHPバージョン 5.6.0 以上が必要です。<br>\n(現在のPHPバージョン：".PHP_VERSION.")"
	);
}
if(!is_file(__DIR__.'/functions.php')){
	return die(__DIR__.'/functions.php'.($en ? ' does not exist.':'がありません。'));
}
require_once(__DIR__.'/functions.php');
if(!isset($functions_ver)||$functions_ver<20231219){
	return die($en?'Please update functions.php to the latest version.':'functions.phpを最新版に更新してください。');
}
check_file(__DIR__.'/misskey_note.inc.php');
require_once(__DIR__.'/misskey_note.inc.php');
if(!isset($misskey_note_ver)||$misskey_note_ver<20231216){
	return die($en?'Please update misskey_note.inc.php to the latest version.':'misskey_note.inc.phpを最新版に更新してください。');
}
check_file(__DIR__.'/save.inc.php');
require_once(__DIR__.'/save.inc.php');
if(!isset($save_inc_ver)||$save_inc_ver<20231219){
	return die($en?'Please update save.inc.php to the latest version.':'save.inc.phpを最新版に更新してください。');
}
// jQueryバージョン
const JQUERY='jquery-3.7.0.min.js';
check_file(__DIR__.'/lib/'.JQUERY);
// luminous
check_file(__DIR__.'/lib/luminous/luminous.min.js');
check_file(__DIR__.'/lib/luminous/luminous-basic.min.css');

check_file(__DIR__.'/config.php');

require_once(__DIR__.'/config.php');

//テンプレート
$skindir='template/'.$skindir;

if(!isset($admin_pass)||!$admin_pass){
	return error($en?'The administrator password has not been set.':'管理者パスワードが設定されていません。');
}
$max_com= isset($max_com) ? $max_com : 1000;
$deny_all_posts= isset($deny_all_posts) ? $deny_all_posts : (isset($denny_all_posts) ? $denny_all_posts : false);
$badhost=isset($badhost) ? $badhost :[]; 
$aikotoba_required_to_view=isset($aikotoba_required_to_view) ? $aikotoba_required_to_view : false;
$keep_aikotoba_login_status=isset($keep_aikotoba_login_status) ? $keep_aikotoba_login_status : false;
$use_paintbbs_neo=isset($use_paintbbs_neo) ? $use_paintbbs_neo : true;
$use_chickenpaint=isset($use_chickenpaint) ? $use_chickenpaint : true;
$use_klecs=isset($use_klecs) ? $use_klecs : true;
$use_tegaki=isset($use_tegaki) ? $use_tegaki : true;
$display_link_back_to_home = isset($display_link_back_to_home) ? $display_link_back_to_home : true;
$pmin_w = isset($pmin_w) ? $pmin_w : 300;//幅
$pmin_h = isset($pmin_h) ? $pmin_h : 300;//高さ
$pdef_w = isset($pdef_w) ? $pdef_w : 300;//幅
$pdef_h = isset($pdef_h) ? $pdef_h : 300;//高さ
$step_of_canvas_size = isset($step_of_canvas_size) ? $step_of_canvas_size : 50;
$mode = (string)filter_input(INPUT_POST,'mode');
$mode = $mode ? $mode :(string)filter_input(INPUT_GET,'mode');
$userip = get_uip();
//user-codeの発行
$usercode = t((string)filter_input(INPUT_COOKIE, 'usercode'));//user-codeを取得
session_sta();
$session_usercode = isset($_SESSION['usercode']) ? t((string)$_SESSION['usercode']) : "";
$usercode = $usercode ? $usercode : $session_usercode;
if(!$usercode){//user-codeがなければ発行
	$usercode = substr(crypt(md5($userip.uniqid()),'id'),-12);
	//念の為にエスケープ文字があればアルファベットに変換
	$usercode = strtr($usercode,"!\"#$%&'()+,/:;<=>?@[\\]^`/{|}~\t","ABCDEFGHIJKLMNOabcdefghijklmno");
}
setcookie("usercode", $usercode, time()+(86400*365),"","",false,true);//1年間
$_SESSION['usercode']=$usercode;

$x_frame_options_deny = isset($x_frame_options_deny) ? $x_frame_options_deny : true;
if($x_frame_options_deny){
	header('X-Frame-Options: DENY');
}
//初期化
init();
deltemp();//テンポラリ自動削除
switch($mode){
	case 'paint':
		return paint();
	case 'paintcom':
		return paintcom();
	case 'adminin':
		return admin_in();
	case 'adminpost':
		return adminpost();
	case 'view_nsfw':
		return view_nsfw();
	case 'logout_admin':
		return logout_admin();
	case 'logout':
		return logout();
	case 'create_misskey_note_sessiondata':
		return misskey_note::create_misskey_note_sessiondata();
	case 'create_misskey_authrequesturl':
		return misskey_note::create_misskey_authrequesturl();
	case 'misskey_success':
		return misskey_note::misskey_success();
	case 'saveimage':
		return saveimage();
		case '':
		return defaultview();
	default:
		return defaultview();
}

//お絵かき画面
function paint(){

	global $boardname,$skindir,$pmax_w,$pmax_h,$pmin_w,$pmin_h,$en;
	global $usercode,$petit_lot;

	check_same_origin();

	$app = (string)filter_input(INPUT_POST,'app');
	$picw = (int)filter_input(INPUT_POST,'picw',FILTER_VALIDATE_INT);
	$pich = (int)filter_input(INPUT_POST,'pich',FILTER_VALIDATE_INT);
	$resto = t((string)filter_input(INPUT_POST, 'resto',FILTER_VALIDATE_INT));
	if(strlen($resto)>1000){
		return error($en?'Unknown error':'問題が発生しました。');
	}
	if(!$usercode){
		error($en? 'User code does not exist.' :'ユーザーコードがありません。');
	}
	$picw = ($picw < $pmin_w) ? $pmin_w : $picw;//最低の幅チェック
	$pich = ($pich < $pmin_h) ? $pmin_h : $pich;//最低の高さチェック
	$picw = ($picw > $pmax_w) ? $pmax_w : $picw;//最大の幅チェック
	$pich = ($pich > $pmax_h) ? $pmax_h : $pich;//最大の高さチェック

	setcookie("appc", $app , time()+(60*60*24*30),"","",false,true);//アプレット選択
	setcookie("picwc", $picw , time()+(60*60*24*30),"","",false,true);//幅
	setcookie("pichc", $pich , time()+(60*60*24*30),"","",false,true);//高さ

	$mode = (string)filter_input(INPUT_POST, 'mode');

	$imgfile='';
	$pchfile='';
	$img_chi='';
	$img_klecks='';
	$anime=true;
	$rep=false;
	$paintmode='paintcom';

	session_sta();

	$adminpost=adminpost_valid();

	//pchファイルアップロードペイント
	if($adminpost){

		$pchfilename = isset($_FILES['pchup']['name']) ? basename($_FILES['pchup']['name']) : '';
		
		$pchtmp=isset($_FILES['pchup']['tmp_name']) ? $_FILES['pchup']['tmp_name'] : '';

		if(isset($_FILES['pchup']['error']) && in_array($_FILES['pchup']['error'],[1,2])){//容量オーバー
			return error($en? 'The file size is too big.':'ファイルサイズが大きすぎます。');
		} 

		if ($pchtmp && $_FILES['pchup']['error'] === UPLOAD_ERR_OK){
	
			$time = (string)(time().substr(microtime(),2,6));
			$pchext=pathinfo($pchfilename, PATHINFO_EXTENSION);
			$pchext=strtolower($pchext);//すべて小文字に
			//拡張子チェック
			if (!in_array($pchext, ['pch','chi','psd'])) {
				return error($en?'This file does not supported by the ability to load uploaded files onto the canvas.Supported formats are pch and chi.':'アップロードペイントで使用できるファイルはpch、chi、psdです。');
			}
			$pchup = TEMP_DIR.'pchup-'.$time.'-tmp.'.$pchext;//アップロードされるファイル名

			$move_uploaded = move_uploaded_file($pchtmp, $pchup);
			if(!$move_uploaded){//アップロードは成功した?
				safe_unlink($pchtmp);
				return error($en?'This operation has failed.':'失敗しました。');
			
			}
			$basename_pchup=basename($pchup);
			$pchup=TEMP_DIR.$basename_pchup;//ファイルを開くディレクトリを固定
			$mime_type = mime_content_type($pchup);
			if(($pchext==="pch") && ($mime_type === "application/octet-stream") && is_neo($pchup)){
					$app='neo';
						if($get_pch_size = get_pch_size($pchup)){
							list($picw,$pich)=$get_pch_size;//pchの幅と高さを取得
						}
					$pchfile = $pchup;
				} elseif(($pchext==="chi") && ($mime_type === "application/octet-stream")){
					$app='chi';
					$img_chi = $pchup;
				} elseif(($pchext==="psd") && ($mime_type === "image/vnd.adobe.photoshop")){
					$app='klecks';
				$img_klecks = $pchup;
				} elseif(in_array($pchext, ['gif','jpg','jpeg','png','webp']) && in_array($mime_type, ['image/gif', 'image/jpeg', 'image/png','image/webp'])){
					$file_name=pathinfo($pchup,PATHINFO_FILENAME);
					$max_px=isset($max_px) ? $max_px : 1024;
					thumb(TEMP_DIR,$basename_pchup,$time,$max_px,$max_px,['toolarge'=>1]);
					list($picw,$pich) = getimagesize($pchup);
					$imgfile = $pchup;
					$anime = false;
				}else{
					safe_unlink($pchup);
					return error($en? 'This file is an unsupported format.':'対応していないファイル形式です。');
			}
		}
	}
	$repcode='';
	$hide_animation=false;
	if($mode==="contpaint"){

		$imgfile = basename((string)filter_input(INPUT_POST,'imgfile'));
		$ctype = (string)filter_input(INPUT_POST, 'ctype');
		$type = (string)filter_input(INPUT_POST, 'type');
		$no = (string)filter_input(INPUT_POST, 'no',FILTER_VALIDATE_INT);
		$time = basename((string)filter_input(INPUT_POST, 'time'));
		$cont_paint_same_thread=(bool)filter_input(INPUT_POST, 'cont_paint_same_thread',FILTER_VALIDATE_BOOLEAN);

		if(is_file(LOG_DIR."{$no}.log")){
			if($type!=='rep'){
				$resto = $cont_paint_same_thread ? $no : '';
			}
		}
		if(!is_file(IMG_DIR.$imgfile)){
			return error($en? 'The article does not exist.':'記事がありません。');
		}
		list($picw,$pich)=getimagesize(IMG_DIR.$imgfile);//キャンバスサイズ

		$_pch_ext = check_pch_ext(IMG_DIR.$time,['upload'=>true]);

		if($ctype=='pch'&& $_pch_ext){//動画から続き
			$pchfile = IMG_DIR.$time.$_pch_ext;
		}

		if($ctype=='img'){//画像から続き
			$animeform = false;
			$anime= false;
			$imgfile = IMG_DIR.$imgfile;
			if($_pch_ext==='.chi'){
				$img_chi =IMG_DIR.$time.'.chi';
			}
			if($_pch_ext==='.psd'){
				$img_klecks =IMG_DIR.$time.'.psd';
			}
		}
		$hide_animation = (bool)filter_input(INPUT_POST,'hide_animation',FILTER_VALIDATE_BOOLEAN);
		$hide_animation = $hide_animation ? 'true' : 'false';
		if($type==='rep'){//画像差し換え
			$rep=true;
			$pwd = t((string)filter_input(INPUT_POST, 'pwd'));
			$pwd=$pwd ? $pwd : t((string)filter_input(INPUT_COOKIE,'pwdc'));//未入力ならCookieのパスワード
			if(strlen($pwd) > 100) return error($en? 'Password is too long.':'パスワードが長すぎます。');
			if($pwd){
				$pwd=basename($pwd);
				$pwd=openssl_encrypt ($pwd,CRYPT_METHOD, CRYPT_PASS, true, CRYPT_IV);//暗号化
				$pwd=bin2hex($pwd);//16進数に
			}
			$userip = get_uip();
			$paintmode='picrep';
			$id=$time;	//テンプレートでも使用。
			$repcode = substr(crypt(md5($no.$id.$userip.$pwd.uniqid()),'id'),-12);
			//念の為にエスケープ文字があればアルファベットに変換
			$repcode = strtr($repcode,"!\"#$%&'()+,/:;<=>?@[\\]^`/{|}~\t","ABCDEFGHIJKLMNOabcdefghijklmno");
		}
	}

	check_AsyncRequest();//Asyncリクエストの時は処理を中断

	$parameter_day = date("Ymd");//JavaScriptのキャッシュ制御

	$admin_pass= null;

	switch($app){
		case 'chi'://ChickenPaint
		
			$tool='chi';
			// HTML出力
			$templete='paint_chi.html';
			return include __DIR__.'/'.$skindir.$templete;

		case 'tegaki':

			$tool ='tegaki';
			$templete='paint_tegaki.html';
			return include __DIR__.'/'.$skindir.$templete;

		case 'klecks':

			$tool ='klecks';
			$templete='paint_klecks.html';
			return include __DIR__.'/'.$skindir.$templete;

		case 'neo'://PaintBBS NEO

			$tool='neo';
			$appw = $picw + 150;//NEOの幅
			$apph = $pich + 172;//NEOの高さ
			$appw = ($appw < 450) ? 450 : $appw;//最低幅
			$apph = ($apph < 560) ? 560 : $apph;//最低高
			//動的パレット
			$palettetxt = $en? 'palette_en.txt' : 'palette.txt';
			check_file(__DIR__.'/'.$palettetxt);  
			$lines =file($palettetxt);
			$pal=[];
			$arr_dynp=[];
			$arr_pal=[];
			$initial_palette = 'Palettes[0] = "#000000\n#FFFFFF\n#B47575\n#888888\n#FA9696\n#C096C0\n#FFB6FF\n#8080FF\n#25C7C9\n#E7E58D\n#E7962D\n#99CB7B\n#FCECE2\n#F9DDCF";';
			foreach ( $lines as $i => $line ) {
				$line=str_replace(["\r","\n","\t"],"",$line);
				$line=$line;
				list($pid,$pname,$pal[0],$pal[2],$pal[4],$pal[6],$pal[8],$pal[10],$pal[1],$pal[3],$pal[5],$pal[7],$pal[9],$pal[11],$pal[12],$pal[13]) = explode(",", $line);
				$arr_dynp[]=h($pname);
				$p_cnt=$i+1;
				ksort($pal);
				$arr_pal[$i] = 'Palettes['.h($p_cnt).'] = "#'.h(implode('\n#',$pal)).'";';
			}
			$palettes=$initial_palette.implode('',$arr_pal);
			$palsize = count($arr_dynp) + 1;

			$admin_pass= null;
			// HTML出力
			$templete='paint_neo.html';
			return include __DIR__.'/'.$skindir.$templete;

		default:
			return error($en?'This operation has failed.':'失敗しました。');
	}

}
// お絵かきコメント 
function paintcom(){
	global $use_aikotoba,$boardname,$home,$skindir,$en,$mark_sensitive_image;
	global $usercode,$petit_lot; 

	aikotoba_required_to_view(true);
	$token=get_csrf_token();
	$userip = get_uip();
	//テンポラリ画像リスト作成
	$uresto = '';
	$handle = opendir(TEMP_DIR);
	$tmps = [];
	$hide_animation=false;
	while ($file = readdir($handle)) {
		if(!is_dir($file) && pathinfo($file, PATHINFO_EXTENSION)==='dat') {
			$file=basename($file);
			$fp = fopen(TEMP_DIR.$file, "r");
			$userdata = fread($fp, 1024);
			fclose($fp);
			list($uip,$uhost,$uagent,$imgext,$ucode,,$starttime,$postedtime,$uresto,$tool,$u_hide_animation) = explode("\t", rtrim($userdata)."\t\t\t");
			$hide_animation=($u_hide_animation==='true');
			$imgext=basename($imgext);
			$file_name = pathinfo($file, PATHINFO_FILENAME);
			$uresto = $uresto ? 'res' :''; 
			if(is_file(TEMP_DIR.$file_name.$imgext)){ //画像があればリストに追加
				$pchext = check_pch_ext(TEMP_DIR . $file_name);
				$pchext = !$hide_animation ? $pchext : ''; 
				if(($ucode && ($ucode === $usercode))||($uip && ($uip === $userip))){
					$tmps[$file_name] = [$file_name.$imgext,$uresto,$pchext];
				}
			}
		}
	}
	closedir($handle);

	if(!empty($tmps)){
		$pictmp = 2;
		ksort($tmps);
		foreach($tmps as $tmp){
			list($tmpfile,$resto,$pchext)=$tmp;
			$tmpfile=basename($tmpfile);
			list($w,$h)=getimagesize(TEMP_DIR.$tmpfile);
			$tmp_img=[
				'w'=>$w,
				'h'=>$h,
				'src' => TEMP_DIR.$tmpfile,
				'srcname' => $tmpfile,
				'slect_src_val' => $tmpfile.','.$resto.','.$pchext,
				'date' => date("Y/m/d H:i", filemtime(TEMP_DIR.$tmpfile)),
			];
			$out['tmp'][] = $tmp_img;
		}
	}
	$aikotoba = $use_aikotoba ? aikotoba_valid() : true;

	$namec = (string)filter_input(INPUT_COOKIE,'namec');
	$pwdc = (string)filter_input(INPUT_COOKIE,'pwdc');
	$urlc = (string)filter_input(INPUT_COOKIE,'urlc');

	// HTML出力
	$templete='paint_com.html';
	return include __DIR__.'/'.$skindir.$templete;
}

function saveimage(){
	
	$tool=filter_input(INPUT_GET,"tool");

	$image_save = new image_save;

	header('Content-type: text/plain');

	switch($tool){
		case "neo":
			$image_save->save_neo();
			break;
		case "chi":
			$image_save->save_chickenpaint();
			break;
		case "klecks":
			$image_save->save_klecks();
			break;
		case "tegaki":
			$image_save->save_klecks();
			break;
	}
}


function defaultview(){

		global $use_aikotoba,$home,$skindir,$descriptions,$root_url;
		global $en,$mark_sensitive_image,$petit_lot,$boardname,$pmax_w,$pmax_h; 
		global $use_paintbbs_neo,$use_chickenpaint,$use_klecs,$use_tegaki,$display_link_back_to_home;
	
		aikotoba_required_to_view();
	
		//管理者判定処理
		$admindel=admindel_valid();
	
		// 禁止ホスト
		$is_badhost=is_badhost();
		$aikotoba = $use_aikotoba ? aikotoba_valid() : true;
		$userdel=isset($_SESSION['userdel'])&&($_SESSION['userdel']==='userdel_mode');
		$adminpost=adminpost_valid();
	
		//token
		$token=get_csrf_token();
	
		$arr_apps=app_to_use();
		$count_arr_apps=count($arr_apps);
		$use_paint=!empty($count_arr_apps);
		$select_app=($count_arr_apps>1);
		$app_to_use=($count_arr_apps===1) ? $arr_apps[0] : ''; 
	
		$appc=h((string)filter_input(INPUT_COOKIE,'appc'));
		$picwc=h((string)filter_input(INPUT_COOKIE,'picwc'));
		$pichc=h((string)filter_input(INPUT_COOKIE,'pichc'));
	
		$petit_ver="";

		// HTML出力
		$templete='main.html';
		return include __DIR__.'/'.$skindir.$templete;
}
