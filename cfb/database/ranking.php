<?php
$config = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . "/.." . "/config.ini", true);

$dbConnect = new mysqli(
    $config['database']['access_host'],
    $config['database']['user_id'],
    $config['database']['user_pw'],
    $config['database']['name'],
    $config['database']['port']
);

if ($dbConnect->connect_errno) {
    die("mysql error");
}

if (empty($_GET['term'])) {
    die("no word");
}
$term = $_GET['term'];

function isChinese($string)
{
    return preg_match("/\p{Han}+/u", $string);
}

function isJapanese($string)
{
    return preg_match('/[\x{4E00}-\x{9FBF}\x{3040}-\x{309F}\x{30A0}-\x{30FF}]/u', $string);
}

function isKorean($string)
{
    return preg_match('/[\x{3130}-\x{318F}\x{AC00}-\x{D7AF}]/u', $string);
}

if (empty($_GET['nationality'])) {
    if (isChinese($_GET['term'])) {
        $_GET['nationality'] = "chinese";
    } else if (isJapanese($_GET['term'])) {
        $_GET['nationality'] = "japanese";
    } else if (isKorean($_GET['term'])) {
        $_GET['nationality'] = "korean";
    } else {
        $_GET['nationality'] = "english";
    }
}

$_GET['nationality'] = urldecode($_GET['nationality']);
$nationality = $_GET['nationality'];

$result = array("term" => $term, "nationality" => $nationality, "data" => []);

$qr1 = sprintf("SELECT * FROM `%s`", $term);
$counting = mysqli_query($dbConnect, $qr1);

$i = 0;
while ($rst = mysqli_fetch_assoc($counting)) {
    $index = $rst['index_'];
    $result["data"][$i] = array("index" => $index, "male" => [], "female" => []);

    for ($age = 0; $age <= 6; $age++) {
        array_push($result["data"][$i]["male"], (int)$rst["male" . $age]);
        array_push($result["data"][$i]["female"], (int)$rst["female" . $age]);
    }
    $i++;
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);
?>
