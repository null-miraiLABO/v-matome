<?php
//セッションスタート
session_name('sesname');
session_start();
session_regenerate_id(true);

//DB
//設定部
$user ="db_mizukinet";//サーバーの設定による
$password = "4CBEpHSn";//サーバーの設定による
$dbname = "db_mizukinet_1";//サーバーの設定による
$dbtable = "otoiawase";//ここは自分で指定するところ

//データベース初期化部
$dsn  = "mysql:host=localhost;charset=utf8;dbname=".$dbname;
$db = new PDO($dsn,$user,$password);

//mysql文を使ってデータを得るための関数
function queryrun($query)
{
	global $db;
	$result = $db->query($query)->fetchAll();
	return $result;
}

//こちらはprepare版、sqlインジェクションを防ぐ意味ではこっちのほうがいい。
function queryrunpre($query,$param)
{
	global $db;
	$pre = $db->prepare($query);
	if($pre->execute($param))
		return $pre->fetchAll();
	else
		return false;
}

//新しいデータを作る関数
function newdata($name,$mail,$msg)
{
	global $db,$dbtable;
	$insert_query = "INSERT INTO ".$dbtable." (name,mail,msg) ".
					"VALUES(".$db->quote($name).",".$db->quote($mail).",".$db->quote($msg).")";

	queryrunpre($insert_query,null);
}

//問い合わせ送り先
$ownermail='tmc20247006@gmail.com';
//問い合わせ件名
$mailsub='お問い合わせ';
//メール用dat
$MAIL_DAT='mailvalue.dat';

// 各ファイルのパス
$HTML_FORM_DAT='form.dat';
$HTML_CHECK_DAT='check.dat';
$HTML_FIN_DAT='fin.dat';

$LOGNAME='log/enq.log';
$LOGTEMP='log.dat';

if($_SERVER["REQUEST_METHOD"]=='POST'){

	if(isset($_POST['chk'])){
		$_SESSION=$_POST;
	}

	//押されたボタンによって次のページが何かをきめる
	$param='';
	if(isset($_POST['chk']))
		$param='?chk=1';
	else if(isset($_POST['fin']))
		$param='?fin=1';
	header("Location: " . $_SERVER['PHP_SELF'].$param);//$_SERVER['PHP_SELF']すなわち自分自身に接続しなおし
	exit();//ここでプログラム終了
}

//エラーチェック
$Err='';
//確認画面、終了画面を表示しようとしているときはエラーチェックします
if(isset($_GET['chk']) || isset($_GET['fin']))
{
	//SeChk()を使っていちいちissetをかかずエラーをチェックしてます
	//エラーチェックは必ずセッションに入っているデータからやりましょ。
	if(SeChk('name')=='')
		$Err.='<div class="err">※名前を入力してください</div>';

	if(SeChk('mail')=='')
		$Err.='<div class="err">※メールアドレスを入力してください</div>';
	else if(!preg_match('/^([a-zA-Z0-9])+([a-zA-Z0-9\.+_-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/',SeChk('mail')) )
		$Err.='<div class="err">※メールアドレスを確認してください</div>';
	else
	{
		//メール重複をチェックする部分です
		//ほとんどチャットの表示部と一緒なことにきづいたかしら？

		//データをいれる変数初期化
		$chkolddata=array();

		//いままでのデータがあれば読み込み
		if(file_exists($LOGNAME))
			$chkolddata=file($LOGNAME);

		//一行ずつループして内容チェック
		foreach($chkolddata as $key=>$eachline)
		{
			//最後の改行コードとかいらないもの削除
			$eachline=trim($eachline);
			//一行を<>で分解して入れ込む
			$eachdata=explode('<>',$eachline);

			//２番目にメールが入ってるので[1]と今回のメールを比較して
			if($eachdata[1]==SeChk('mail'))
			{
				$Err.='<div class="err">※解答済みのメールアドレスです</div>';
				break;
				//おんなじだったらエラー仕込んでループを抜けます
			}
		}
	}

	if(SeChk('mes')=='')
		$Err.='<div class="err">※お問い合わせ内容を入力してください</div>';

	if($Err!='')
		unset($_GET);
}

//まずはキーと中身をいれる変数初期化
$SearchKey=array();
$SearchValue=array();

foreach($_SESSION as $key=>$value){
	$SearchKey[]='{{'.$key.'}}';
	$SearchValue[]=htmlspecialchars($value);
}

//このプログラムではエラー処理を表示側でやっているので
//セッションにエラーがありません
//なのでそのぶんだけ追加
$SearchKey[]='{{Err}}';
$SearchValue[]=$Err;

//まずはdatの名前をいれる変数を用意して
$loadname="";
if(isset($_GET['chk'])){//確認画面だったら
	$loadname=$HTML_CHECK_DAT;//確認画面のdat名を控えます
}else if(isset($_GET['fin'])){
	$loadname=$HTML_FIN_DAT;

	//メール送信
	$usemail=str_replace($SearchKey,$SearchValue,file_get_contents($MAIL_DAT));

	//ip追加
	$usemail=str_replace('{{ip}}',$_SERVER['REMOTE_ADDR'],$usemail);
	$usemail=preg_replace("/{{.*?}}/","",$usemail);

	mb_internal_encoding("UTF-8") ;
	mb_send_mail($ownermail,$mailsub,htmlspecialchars_decode($usemail));

	newdata($_SESSION["name"],$_SESSION["mail"],$_SESSION["mes"]);

	//何度も言うけど、本当はファイルロック処理をしないと
	//データが吹っ飛ぶ可能性があります。

	//書き込んだらセッション初期化しちゃいます
	//もういらないので。
	$_SESSION=array();
}else{
	$loadname=$HTML_FORM_DAT;
}

$usehtml=file_get_contents($loadname);

//ざっくり置き換え
$usehtml=str_replace($SearchKey,$SearchValue,$usehtml);
$usehtml=preg_replace("/{{.*?}}/","",$usehtml);

echo $usehtml;

//issetがメンチなので関数つくったった。
function SeChk($sessionName)
{
	return isset($_SESSION[$sessionName]) ? $_SESSION[$sessionName] : '';
}
?>