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
    $ctx = $rst["_count"];
    $sum += $ctx;
}

$qr1 = "SELECT * FROM _terms ORDER BY _count DESC;";
$counting = mysqli_query($dbConnect, $qr1);
$qr2 = "SELECT * FROM _terms ORDER BY _date DESC;";
$counting2 = mysqli_query($dbConnect, $qr2);

$ct = 0;
$arr = array();

$result = array("sum" => $sum, "arr" => []);

while ($rst = mysqli_fetch_assoc($counting)) {
    $ctx = $rst["_count"];
    $tm = $rst["term"];

    if (in_array($tm, $arr)) {
        continue;
    }

    $result["arr"][$ct] = array("term" => $tm, "value" => $ctx);
    $arr[$ct] = $tm;

    $ct++;
    if ($ct > 9) break;
}

$result["arr"][$ct] = array("term" => "dummy", "value" => 0);
$result["arr2"] = [];

$ct = 0;
$arr = array();

while ($rst = mysqli_fetch_assoc($counting2)) {
    $ctx = $rst["_date"];
    $rtx = $rst["_count"];
    $tm = $rst["term"];

    if (in_array($tm, $arr)) {
        continue;
    }

    $result["arr2"][$ct] = array("term" => $tm, "date" => $ctx, "value" => $rtx);
    $arr[] = $tm;

    $ct++;
    if ($ct > 9) break;
}

$result["arr2"][$ct] = array("term" => "dummy", "date" => "0000-00-00 00:00:00", "value" => 0);


print(json_encode($result, JSON_UNESCAPED_UNICODE));

?>
