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

if (empty($_GET['origin'])) {
    die("no origin");
}
$origin = urldecode($_GET['origin']);

if (empty($_GET['nationality'])) {
    $query = "select nationality from _terms where term = '{$term}' and origin = '{$origin}'";
    $_GET['nationality'] = mysqli_fetch_assoc(mysqli_query($dbConnect, $query))['nationality'];
}

$wordClass = preg_split('/[.]/', $origin);
$wordClass = end($wordClass);

$nationality = rawurldecode($_GET['nationality']);

$result = array("term" => $term, "nationality" => $nationality, "data" => []);

$qr1 = "SELECT * FROM `{$wordClass}㉠{$term}`";
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
