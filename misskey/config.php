<?php
/*設定項目*/

/*-----絶対に変更が必要な項目-----*/

//管理者パスワード 必ず変更してください。
$admin_pass = "kanripass";

//第2パスワード 必ず変更してください。
//管理者投稿や管理者削除の時に管理者である事を再確認する為に使うパスワード。
//内部で処理するため覚えておく必要はありません。
//管理パスと同じパスワードは使えません。
$second_pass = "yNhUFapFrTY4ETi";

//この掲示板の名前
$boardname = "Misskey専用お絵かき掲示板";

//ホームページ(掲示板からの戻り先)
$home = "https://example.com/"; //相対パス、絶対パス、URLどれでもOK 

//MisskeyのAPIを使うために必要
// 設置場所のurl `/`まで。
$root_url = "http://example.com/misskey/";

/*テンプレート切り替え*/
//テンプレートのディレクトリ`/`まで 初期値 "basic/"
$skindir="basic/";

/*掲示板の説明文*/

// テンプレートに直接記入しても構いませんが、ここで入力する事もできます。
// 説明文が1行なら ["説明そのいち"]
// 説明文が3行なら ["説明そのいち","説明そのに","説明そのさん"]
// 文字をダブルクオートで囲って、カンマで区切ります。
// 説明文が不要なら []で。

// $descriptions = ["iPadやスマートフォンでも描けるお絵かき掲示板です。"];	
$descriptions = ["｢PaintBBS NEO｣｢Tegaki｣｢litaChix｣｢Klecks｣で絵を描いてMisskeyに投稿できます。","楽しくお絵かき。"];	

/*使用目的別設定*/

// ホームページへ戻るリンクを上段のメニューに表示する
// ホームページへのリンクが必要ない場合は 表示しない:false
// 表示する:true 表示しない:false

$display_link_back_to_home = true;
// $display_link_back_to_home = false;

// PaintBBS NEOを使う
// 使う:true 使わない:false

$use_paintbbs_neo= true;
// $use_paintbbs_neo= false;

// Tegakiを使う
// 使う:true 使わない:false

$use_tegaki= true;
// $use_tegaki= false;

// litaChixを使う
// 使う:true 使わない:false

$use_chickenpaint= true;
// $use_chickenpaint= false;

// Klecksを使う
// 使う:true 使わない:false

$use_klecs= true;
// $use_klecs= false;

//日記モードを使用する
//する: true でスレッド立ては管理者のみになります。
// する: true しない: false

// 閲覧注意を設定する
//する: trueに設定すると閲覧注意の設定ができるようになります。閲覧注意画像にぼかしが入ります。
// する: true しない: false

$mark_sensitive_image = true;
// $mark_sensitive_image = false;

//編集しても投稿日時を変更しないようにする 
//日記などで日付が変わると困る人のための設定
//する: trueに設定すると編集しても投稿日時が変わりません。 通常は しない: false 。
// する: true しない: false

//管理者ページに最新のリリースのバージョンとリンクを表示する
// する: true しない: false


/*画像関連*/

//投稿できる画像の容量上限 単位kb

//お絵かきできる幅と高さの最大サイズ

$pmax_w = 1000;//幅
$pmax_h = 800;//高さ


/*セキュリティ*/

// 管理者パスワードを5回連続して間違えた時は拒絶する
// する: true しない: false
// trueにするとセキュリティは高まりますが、ログインページがロックされた時の解除に手間がかかります。

// $check_password_input_error_count = true;
$check_password_input_error_count = false;

//ftp等でアクセスして、
// `template/errorlog/error.log`
// を削除すると、再度ログインできるようになります。
// このファイルには、間違った管理者パスワードを入力したクライアントのIPアドレスが保存されています。
// また上記ファイルを手動で削除しなくても、ロック発生から3日経過すると自動的に解除されます。

//お絵かきアプリで投稿する時の必要最低限の描画時間
//(単位:秒)。この設定が不要な時は : 0 
// 指定した秒数に達しない場合は、描画に必要な秒数を知らせるアラートが開きます。

$security_timer = 0;
// $security_timer = 60;

/*詳細設定*/

//タイムゾーン 日本時間で良ければ初期値 "asia/tokyo"

date_default_timezone_set("asia/tokyo");

//iframe内での表示を 拒否する:true 許可する:false
//セキュリティリスクを回避するため "拒否する:true" を強く推奨。

$x_frame_options_deny=true;
// $x_frame_options_deny=false;

//Misskeyへの投稿時に一覧で表示するMisskeyサーバ
$misskey_servers=
[

	["misskey.io","https://misskey.io"],
	["xissmie.xfolio.jp","https://xissmie.xfolio.jp"],
	["misskey.design","https://misskey.design"],
	["nijimiss.moe","https://nijimiss.moe"],
	["misskey.art","https://misskey.art"],
	["oekakiskey.com","https://oekakiskey.com"],
	["misskey.gamelore.fun","https://misskey.gamelore.fun"],
	["novelskey.tarbin.net","https://novelskey.tarbin.net"],
	["tyazzkey.work","https://tyazzkey.work"],
	["sushi.ski","https://sushi.ski"],
	["misskey.delmulin.com","https://misskey.delmulin.com"],
	["side.misskey.productions","https://side.misskey.productions"],
	["mk.shrimpia.network","https://mk.shrimpia.network"],

];

//通常は変更しません
//ペイント画面の$pwdの暗号化

define("CRYPT_PASS","wpt5ULbYvrF3L5R");//暗号鍵初期値
define("CRYPT_METHOD","aes-128-cbc");
define("CRYPT_IV","T3pkYxNyjN7Wz3pu");//半角英数16文字

/*変更不可*/

//変更しないでください
//テンポラリ
define("TEMP_DIR","temp/");
//ログ
define("LOG_DIR","log/");
//画像
define("IMG_DIR","src/");
//画像
define("THUMB_DIR","thumbnail/");

