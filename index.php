<?php
//var_dump($_POST);
require_once 'Encode.php';

// 定数でパスワードを決める
const PASSWORD = '12345';

// セッション開始
session_start();

if( !empty($_POST["btn_login"]) ) {
	if( !empty($_POST['admin_password']) && $_POST['admin_password'] === PASSWORD ) {
		$_SESSION['admin_login'] = true;
		$login_message = 'ログインしました。';
	} else {
		$login_message = 'ログインに失敗しました。';
	}
}

// 最初に変数を定義しておかないとエラーになる
$err_msg1 = "";
$err_msg2 = "";
$err_msg3 = "";
$message = "";
// $POST["name"]がセットされていれば、$name = $POST["name"]
// $POST["name"]がセットされていなければ、$name = ""
$name = ( isset($_POST["name"]) === true )?$_POST["name"]:"";
// $POST["comment"]がセットされていれば、$comment = trim($POST["comment"])
// trim関数で、$_POST["comment"]の先頭と末尾の空白を除去する
// $POST["comment"]がセットされていなければ、$comment = ""
$comment = ( isset($_POST["comment"]) === true ) ? trim($_POST["comment"]):"";
// $POST["gender"]の値に応じて結果を変化させる
switch($_POST["gender"]) {
	case 'male':
		$gender = '男性';
		break;
	case 'female':
		$gender = '女性';
		break;
	default:
		// 空白にして、エラーメッセージが表示されるようにする
		$gender = '';
		break;
}

// 投稿があるときのみ処理を行う
if (isset($_POST["send"]) === true) {
	// エラーチェック
	if($name === "") $err_msg1 = '名前を入力してください。';
	if($comment === "") $err_msg2 = 'コメントを入力してください。';
	if($gender === "") $err_msg3 = '性別を選択してください。';
	
	// エラーがなければデータを書き込む
	if($err_msg1 === "" && $err_msg2 === "" && $err_msg3 === "") {
		$fp = fopen("data.txt", "a"); // 書き込み専用で"data.txt"を開く
		fwrite($fp, $name."\t".$comment."\t".$gender."\n"); // ファイルに書き込み
		$_SESSION['success_message'] = '書き込みに成功しました。';
	}
	// 書き込み後、自動リダイレクトを行う(二重投稿を防ぐため)
	header('Location: ./');
}

$fp = fopen("data.txt", "r"); // 読み込み専用で"data.txt"を開く

// 1ページ当たりの投稿表示数
const MAX = 10;

$dataArr = array();
// ファイルポインタを読み込み、1行分読み込んで変数$resに入れる
// テキストファイルの行数分、処理を繰り返す
while( $res = fgets($fp)) {
	$tmp = explode("\t", $res); // 変数$resを分割して配列$tmpに格納
	// 配列$tmpの中身を連想配列$arrに移す
	$arr = array(
		"name" => $tmp[0],
		"comment" => $tmp[1],
		"gender" => $tmp[2]
	);
	// 多次元配列$dataArrに連想配列$arrを格納
	$dataArr[] = $arr;
}

$books_num = count($dataArr);

// トータルページ数※ceilは小数点を切り上げる関数
$max_page = ceil($books_num / MAX); 
//var_dump($max_page);

// $_GET['page_id'] はURLに渡された現在のページ数
if(!isset($_GET['page_id'])){
	$now = 1; // 設定されてない場合は1ページ目にする
}else{
	$now = $_GET['page_id'];
}

// 配列の何番目から取得すればよいか
$start_no = ($now - 1) * MAX;

// array_sliceは、配列の何番目($start_no)から何番目(MAX)まで切り取る関数
$disp_data = array_slice($dataArr, $start_no, MAX, true);

?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
<link href="css/style.css" rel="stylesheet" />
<title>掲示板</title>
</head>

<body>
	<?=$login_message ?><br>
	<!-- 「送信」ボタンが押されていないかつ、送信成功メッセージがあるとき、 -->
	<!-- セッションに入っているメッセージを出力 -->
	<?php if( empty($_POST['send']) && !empty($_SESSION['success_message'] )): ?>
		<p class="success_message"><?=$_SESSION['success_message'] ?></p>
		<!-- 成功メッセージ表示後、メッセージをセッションから削除 -->
		<?php unset($_SESSION['success_message']); ?>
	<?php endif; ?>
	<?php if( !empty($_SESSION['admin_login']) && $_SESSION['admin_login'] === true ): ?>
	<!-- ログイン後のみ表示 -->
	<form method="post" action="">
		名前：<input type="text" name="name" value="<?=e($name) ?>" ><?=e($err_msg1) ?>
		<br>
		<!-- 追加内容 -->
		性別：<input type="radio" name="gender" value="male" checked> 男性
<input type="radio" name="gender" value="female"> 女性
		<?=e($err_msg3) ?>
		<br>
		コメント：<textarea name="comment" rows="4" cols="40"><?=e($comment) ?></textarea><?=e($err_msg2) ?>
		<br>
		<input type="submit" name="send" value="投稿">
	</form>
	<dl>
		<!-- 多次元配列$disp_dataの中身をforeachで取り出す -->
		<!-- $disp_dataはあらかじめ現在のページ数をもとに切りとられた配列 -->
		<?php foreach($disp_data as $data): ?>
			<article>
				<div class="info">
					<h2><?=e($data["name"]) ?></h2>
					<p>(<?=e($data["gender"]) ?>)</p>
				</div>
				<p><?=e($data["comment"]) ?></p>
			</article>
		<?php endforeach ?>
	</dl>
	
	<!-- ここにページリンクが入る -->
	<?php for($i = 1; $i <= $max_page; $i++): ?>
		<?php if($i === $now): ?>
			<!-- 現在表示中のページにはリンクを貼らない -->
			<?='<p class="pagenation" >'. $now.'</p>' ?>
		<?php else: ?>
			<?='<a class="pagenation" href=\'http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).'?page_id='. $i. '\')>'. $i. '</a>'. '　' ?>
		<?php endif ?>
	<?php endfor ?>
	
	<?php else: ?>
	<!-- ここにログインフォームが入る -->
	<form method="post">
	    <div>
 	       <label for="admin_password">ログインパスワード</label>
 	       <input id="admin_password" type="password" name="admin_password" value="">
 	   </div>
 	   <input type="submit" name="btn_login" value="ログイン">
	</form>
	<?php endif; ?>
</body>
</html>