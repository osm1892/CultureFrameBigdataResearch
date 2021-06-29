<?php
$config = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . "/.." . "/config.ini", true);
$myID = $config['server']['admin_id'];
$myPw = $config['server']['hashed_pw'];

// myPw를 해시한 결과값과 쿠키 내부값을 비교합니다.
// 쿠키와 해시값이 일치한다면, admin계정으로 로그인을 한 상태라는 의미이므로, OK를 출력합니다.
$val = hash('sha256', $myPw, false);
if (isset($_COOKIE['keys']) && $val == $_COOKIE['keys']) {
    print("OK");
    return;
}

// url로 넘겨진 id와 pw 데이터를 받아옵니다.
$id = rawurldecode($_GET['id']);
$pw = rawurldecode($_GET['pw']);

if (isset($_GET['id']) && isset($_GET['pw']) && $id == $myID && $myPw == $pw) {
    print(hash('sha256', $myPw, false));
} else {
    print("denied");
}
?>
