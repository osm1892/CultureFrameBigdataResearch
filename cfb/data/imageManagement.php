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

$val = hash('sha256', $config['server']['hashed_pw'], false);
if (empty($_COOKIE['keys']) || $val != $_COOKIE['keys']) {
    print('{"status":"denied"}');
    return;
}

$ser = json_decode(file_get_contents('php://input'));
$data = $ser->data;
$super = $ser->super;
$jsonResult = array("status" => "allowed", "suc" => []);

foreach ($data as $my) {
    $origin = $my->origin;
    $english = $my->english;
    $korean = $my->korean;
    $chinese = $my->chinese;
    $japanese = $my->japanese;
    $english_ex = explode(',', $english);
    $korean_ex = explode(',', $korean);
    $chinese_ex = explode(',', $chinese);
    $japanese_ex = explode(',', $japanese);
    $flag = false;

    $query = sprintf('select * from _origin where term = "%s"', $origin);

    while ($rst = mysqli_fetch_assoc(mysqli_query($dbConnect, $query))) {
        if ($rst["term"] == $origin) {
            $e_ = explode(',', $rst["english"]);
            $k_ = explode(',', $rst["korean"]);
            $c_ = explode(',', $rst["chinese"]);
            $j_ = explode(',', $rst["japanese"]);
            mysqli_query($dbConnect,
                sprintf('UPDATE _origin SET english="%s", korean="%s", chinese="%s", japanese="%s" WHERE term ="%s";',
                    $english, $korean, $chinese, $japanese, $origin));

            foreach ($english_ex as $en) {
                if (!in_array($en, $e_)) {
                    $query = sprintf('INSERT INTO _terms VALUES("%s", 0, "english", "%s", "0000-00-00 00:00:00", 0, "%s")',
                        $en, $origin, $super);
                    mysqli_query($dbConnect, $query);
                    array_push($jsonResult["suc"], $en);
                }
            }
            foreach ($e_ as $ev) {
                if (!in_array($ev, $english_ex)) {
                    $query = "DELETE FROM _terms WHERE term=\"" . $ev . "\";";

                    mysqli_query($dbConnect, $query);

                    $query = "DROP TABLE " . $ev;
                    mysqli_query($dbConnect, $query);
                    $query = "DROP TABLE " . $ev . "_split";
                    mysqli_query($dbConnect, $query);
                    $query = "DROP TABLE " . $ev . "_image_meta";
                    mysqli_query($dbConnect, $query);
                    for ($i = 0; $i < 50; $i++) {
                        $query = "drop table " . $ev . "_comment" . $i;
                        mysqli_query($dbConnect, $query);
                    }
                }
            }
            /************/
            foreach ($korean_ex as $ko) {
                if (!in_array($ko, $k_)) {
                    $query = sprintf('INSERT INTO _terms VALUES("%s", 0, "korean", "%s", "0000-00-00 00:00:00", 0, "%s")',
                        $ko, $origin, $super);
                    mysqli_query($dbConnect, $query);
                    array_push($jsonResult["suc"], $ko);
                }
            }
            foreach ($k_ as $kv) {
                if (!in_array($kv, $korean_ex)) {
                    $query = "DELETE FROM _terms WHERE term=\"" . $kv . "\";";

                    mysqli_query($dbConnect, $query);

                    $query = "DROP TABLE " . $kv;
                    mysqli_query($dbConnect, $query);
                    $query = "DROP TABLE " . $kv . "_split";
                    mysqli_query($dbConnect, $query);
                    $query = "DROP TABLE " . $kv . "_image_meta";
                    mysqli_query($dbConnect, $query);
                    for ($i = 0; $i < 50; $i++) {
                        $query = "drop table " . $kv . "_comment" . $i;
                        mysqli_query($dbConnect, $query);
                    }
                }
            }
            /************/
            foreach ($chinese_ex as $ch) {
                if (!in_array($ch, $c_)) {
                    $query = sprintf('INSERT INTO _terms VALUES("%s", 0, "chinese", "%s", "0000-00-00 00:00:00", 0, "%s")',
                        $ch, $origin, $super);
                    mysqli_query($dbConnect, $query);
                    array_push($jsonResult["suc"], $ch);
                }
            }
            foreach ($c_ as $cv) {
                if (!in_array($cv, $chinese_ex)) {
                    $query = "DELETE FROM _terms WHERE term=\"" . $cv . "\";";
                    mysqli_query($dbConnect, $query);
                    $query = "DROP TABLE " . $cv . ";";
                    mysqli_query($dbConnect, $query);
                    $query = "DROP TABLE " . $cv . "_split";
                    mysqli_query($dbConnect, $query);
                    $query = "DROP TABLE " . $cv . "_image_meta";
                    mysqli_query($dbConnect, $query);
                    for ($i = 0; $i < 50; $i++) {
                        $query = "drop table " . $cv . "_comment" . $i;
                        mysqli_query($dbConnect, $query);
                    }
                }
            }
            /************/
            foreach ($japanese_ex as $ja) {
                if (!in_array($ja, $j_)) {
                    $query = sprintf('INSERT INTO _terms VALUES("%s", 0, "japanese", "%s", "0000-00-00 00:00:00", 0, "%s")',
                        $ja, $origin, $super);
                    mysqli_query($dbConnect, $query);
                    array_push($jsonResult["suc"], $ja);
                }
            }
            foreach ($j_ as $jv) {
                if (!in_array($jv, $japanese_ex)) {
                    $query = sprintf('DELETE FROM _terms WHERE term="%s"', $jv);
                    mysqli_query($dbConnect, $query);
                    $query = "DROP TABLE " . $jv;
                    mysqli_query($dbConnect, $query);
                    $query = "DROP TABLE " . $jv . "_split";
                    mysqli_query($dbConnect, $query);
                    $query = "DROP TABLE " . $jv . "_image_meta";
                    mysqli_query($dbConnect, $query);
                    for ($i = 0; $i < 50; $i++) {
                        $query = "drop table " . $jv . "_comment" . $i;
                        mysqli_query($dbConnect, $query);
                    }
                }
            }
            $flag = true;
            break;
        }
    }
    if (!$flag) {
        $qr_origin = sprintf('INSERT INTO _origin (term, english, korean, chinese, japanese, _count, super) VALUES ("%s","%s","%s","%s","%s", 0, "%s")',
            $origin, $english, $korean, $chinese, $japanese, $super);
        mysqli_query($dbConnect, $qr_origin);

        foreach ($english_ex as $en) {
            if ($en == "null") break;
            $query = sprintf('INSERT INTO _terms VALUES("%s", 0, "english", "%s", "0000-00-00 00:00:00", 0, "%s")',
                $en, $origin, $super);
            mysqli_query($dbConnect, $query);
            array_push($jsonResult["suc"], $en);
        }
        foreach ($korean_ex as $ko) {
            if ($ko == "null") break;
            $query = sprintf('INSERT INTO _terms VALUES("%s", 0, "korean", "%s", "0000-00-00 00:00:00", 0, "%s")',
                $ko, $origin, $super);
            mysqli_query($dbConnect, $query);
            array_push($jsonResult["suc"], $ko);
        }
        foreach ($chinese_ex as $ch) {
            if ($ch == "null") break;
            $query = sprintf('INSERT INTO _terms VALUES("%s", 0, "chinese", "%s", "0000-00-00 00:00:00", 0, "%s")',
                $ch, $origin, $super);
            mysqli_query($dbConnect, $query);
            array_push($jsonResult["rst"], $ch);
        }
        foreach ($japanese_ex as $ja) {
            if ($ja == "null") break;
            $query = sprintf('INSERT INTO _terms VALUES("%s", 0, "japanese", "%s", "0000-00-00 00:00:00", 0, "%s")',
                $ja, $origin, $super);
            mysqli_query($dbConnect, $query);
            array_push($jsonResult["rst"], $ja);
        }

    }
}

print(json_encode($jsonResult, JSON_UNESCAPED_UNICODE));

$qch5 = "DELETE FROM _terms WHERE term=\"null\";";
mysqli_query($dbConnect, $qch5);

$qch6 = 'DELETE FROM _origin WHERE english="null" AND korean="null" AND chinese="null" AND japanese="null";';
mysqli_query($dbConnect, $qch6);
?>
