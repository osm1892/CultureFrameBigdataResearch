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

$qr0 = "SELECT _count FROM _terms;";

$counts = mysqli_query($dbConnect, $qr0);

$sum = 0;
while ($rst = mysqli_fetch_assoc($counts)) {
    $voteCount = $rst["_count"];
    $sum += $voteCount;
}

$qr1 = "SELECT * FROM _terms ORDER BY _count DESC;";
$counting = mysqli_query($dbConnect, $qr1);
$qr2 = "SELECT * FROM _terms ORDER BY _date DESC;";
$counting2 = mysqli_query($dbConnect, $qr2);

$ct = 0;
$arr = array();

$result = array("sum" => $sum, "arr" => []);

while ($rst = mysqli_fetch_assoc($counting)) {
    $voteCount = $rst["_count"];
    $term = $rst["term"];
    $origin = $rst["origin"];

    if (in_array($term, $arr)) {
        continue;
    }

    $result["arr"][$ct] = array("term" => $term, "value" => $voteCount, "origin" => $origin);
    $arr[$ct] = $term;

    $ct++;
    if ($ct > 9) break;
}

$result["arr"][$ct] = array("term" => "dummy", "value" => 0, "origin" => "dummy");
$result["arr2"] = [];

$ct = 0;
$arr = array();

while ($rst = mysqli_fetch_assoc($counting2)) {
    $voteCount = $rst["_date"];
    $rtx = $rst["_count"];
    $term = $rst["term"];
    $origin = $rst["origin"];

    if (in_array($term, $arr)) {
        continue;
    }

    $result["arr2"][$ct] = array("term" => $term, "date" => $voteCount, "value" => $rtx, "origin" => $origin);
    $arr[] = $term;

    $ct++;
    if ($ct > 9) break;
}

$result["arr2"][$ct] = array("term" => "dummy", "date" => "0000-00-00 00:00:00", "value" => 0, "origin" => "dummy");


print(json_encode($result, JSON_UNESCAPED_UNICODE));
