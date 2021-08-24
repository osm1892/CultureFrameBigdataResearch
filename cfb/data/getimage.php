<?php

function removeWordFromOrigin($originRow, $wordNation, $searchWord, $dbConnect, $originWord): bool
{
    if (!$originRow) {
        return false;
    }
    $removed = false;
    $x = explode(',', $originRow[$wordNation]);
    $value = "";
    foreach ($x as $char) {
        if ($char == $searchWord) {
            $removed = true;
        } else {
            $value = $value . $char . ",";
        }
    }
    if (empty($value)) {
        $value = "null";
    } else {
        $value = substr($value, 0, -1);
    }
    if ($removed) {
        $query = sprintf('UPDATE _origin SET %s = "%s" WHERE %s = "%s" AND term= "%s";',
            $wordNation, $value, $wordNation, $originRow[$wordNation], $originWord);
        mysqli_query($dbConnect, $query);
    }
    return $removed;
}

# ini config 데이터를 불러옵니다.
$config = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . "/.." . "/config.ini", true);
ini_set('user_agent', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:89.0) Gecko/20100101 Firefox/89.0');

# DB에 연결합니다.
$dbConnect = new mysqli(
    $config['database']['access_host'],
    $config['database']['user_id'],
    $config['database']['user_pw'],
    $config['database']['name'],
    $config['database']['port']
);
if ($dbConnect->connect_errno) {
    die("<mysql error>");
}

# 인증 데이터를 확인합니다.
$val = hash('sha256', $config['server']['hashed_pw']);
if (empty($_COOKIE['keys']) || $val != $_COOKIE['keys']) {
    print("<잘못된 접근입니다.>");
    return;
}

# 검색 데이터를 확인합니다.
if (empty($_GET['search'])) {
    print("<검색어가 비어있습니다.>");
    return;
}
$search = rawurldecode($_GET['search']);

# 검색 단어창이 null이라면 리턴합니다.
if (strcasecmp($search, 'null') == 0) {
    print("저장되었습니다.");
    return;
}

# super term을 확인합니다.
if (empty($_GET['super'])) {
    print("<검색어가 비어있습니다.>");
    return;
}
$super = rawurldecode($_GET['super']);

# 원 영단어를 확인합니다.
if (empty($_GET['origin'])) {
    print("<검색어가 비어있습니다.>");
    return;
}
$origin = rawurldecode($_GET['origin']);

// api key
$key = $config['api']['key'];
$engineID = $config['api']['engine'];

# 단어의 국적에 따라, api key 를 다르게 설정합니다.
switch (rawurldecode($_GET['nation'])) {
    case 'chinese':
        $engineID = $config['api']['engine_cn'];
        break;
    case 'japanese':
        $engineID = $config['api']['engine_jp'];
        break;
    default:
        $engineID = $config['api']['engine_ko'];
        break;
}

# 이미지 다운로드의 기본 설정은 50개 입니다.
$imageMax = 50;
$count = 0;

# 동음이의어가 이미 존재하는지 확인합니다.
$pre_exist = false;
if (mysqli_query($dbConnect, sprintf('select * from _terms where term = "%s"', $search))->num_rows > 1) {
    $pre_exist = true;
}

# 동음이의어가 존재하지 않는다면, 이미지를 새로 받을 준비를 합니다.
if ($pre_exist == false) {
    $query = sprintf('create table `%s_image_meta` (index_ int primary key, title text, snippet text)', $search);
    mysqli_query($dbConnect, $query);

    for ($i = 0; $i < $imageMax; $i++) {
        $query = sprintf('insert into `%s_image_meta`(index_) values (%d)', $search, $i);
        mysqli_query($dbConnect, $query);
    }
}

# 품사를 추출합니다.
$query = sprintf('select * from _origin where term = "%s" and super = "%s"', $origin, $super);
$result = mysqli_fetch_assoc(mysqli_query($dbConnect, $query));
$wordClass = "";
if ($result) {
    $split = preg_split('/[.]/', $result['term']);
    $wordClass = end($split);
}

$google_results = [];

for ($i = 0; $i < $imageMax / 10; $i++) {
    $url = sprintf("https://www.googleapis.com/customsearch/v1?key=%s&cx=%s&searchType=image&q=%s&num=10&start=%d",
        $key, $engineID, rawurlencode($search), $i * 10);
    try {
        $ctx = stream_context_create(['http' => ['timeout' => 3]]);
        $response = file_get_contents($url, false, $ctx);
        if (strpos($response, 'usageLimits') !== false) {
            break;
        }
        array_push($google_results, $response);
    } catch (Exception $e) {
        print (sprintf("\n[Exception] 예외가 발생했습니다.\n<내용> : %s\n<코드> : %s\n",
            $e->getMessage(), $e->getCode()));
    }
}

for ($i = 0; $i < $imageMax; $i++) {
    $createCommentTable = sprintf("create table `%s㉠%s_comment%d` (index_ int primary key", $wordClass, $search, $i);
    for ($age = 0; $age < 7; $age++) {
        foreach (['male', 'female'] as $gender) {
            for ($split = 0; $split < 4; $split++) {
                $createCommentTable = $createCommentTable . sprintf(', %s%d_%d longtext', $gender, $age, $split);
            }
        }
    }
    $createCommentTable = $createCommentTable . ");";
    mysqli_query($dbConnect, $createCommentTable);
}

$createTable = sprintf("CREATE TABLE `%s㉠%s`(index_ INT primary key, male0 INT, female0 INT, male1 INT, female1 INT, male2 INT, female2 INT, male3 INT, female3 INT, male4 INT, female4 INT, male5 INT, female5 INT, male6 INT, female6 INT);", $wordClass, $search);
$createSplitTable = sprintf("create table `%s㉠%s_split`(index_ int primary key, male0_0 int, male0_1 int, male0_2 int, male0_3 int, female0_0 int, female0_1 int, female0_2 int, female0_3 int, male1_0 int, male1_1 int, male1_2 int, male1_3 int, female1_0 int, female1_1 int, female1_2 int, female1_3 int, male2_0 int, male2_1 int, male2_2 int, male2_3 int, female2_0 int, female2_1 int, female2_2 int, female2_3 int, male3_0 int, male3_1 int, male3_2 int, male3_3 int, female3_0 int, female3_1 int, female3_2 int, female3_3 int, male4_0 int, male4_1 int, male4_2 int, male4_3 int, female4_0 int, female4_1 int, female4_2 int, female4_3 int, male5_0 int, male5_1 int, male5_2 int, male5_3 int, female5_0 int, female5_1 int, female5_2 int, female5_3 int, male6_0 int, male6_1 int, male6_2 int, male6_3 int, female6_0 int, female6_1 int, female6_2 int, female6_3 int);", $wordClass, $search);
mysqli_query($dbConnect, $createTable);
mysqli_query($dbConnect, $createSplitTable);

for ($i = 0; $i < $imageMax; $i++) {
    $addToTableValue = sprintf("INSERT INTO `%s㉠%s` VALUES (%d, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);", $wordClass, $search, $i);
    mysqli_query($dbConnect, $addToTableValue);
    $addToSplitTable = sprintf("INSERT INTO `%s㉠%s_split` VALUES (%d, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);", $wordClass, $search, $i);
    mysqli_query($dbConnect, $addToSplitTable);
}

# API 사용량 초과로 검색을 수행하지 못했을 경우, 데이터베이스의 단어 관련 정보를 삭제합니다.
if (count($google_results) < $imageMax / 10) {
    $query = sprintf('delete from _terms where term = "%s" and origin = "%s"', $search, $origin);
    mysqli_query($dbConnect, $query);

    $query = sprintf('select * from _origin where super = "%s" and term = "%s"', $super, $origin);
    $result = mysqli_query($dbConnect, $query);
    $resultElement = mysqli_fetch_assoc($result);
    removeWordFromOrigin($resultElement, "english", $search, $dbConnect, $origin);
    removeWordFromOrigin($resultElement, "korean", $search, $dbConnect, $origin);
    removeWordFromOrigin($resultElement, "chinese", $search, $dbConnect, $origin);
    removeWordFromOrigin($resultElement, "japanese", $search, $dbConnect, $origin);

    mysqli_query($dbConnect, sprintf("DROP TABLE `%s㉠%s`", $wordClass, $search));
    mysqli_query($dbConnect, sprintf("DROP TABLE `%s㉠%s_split`", $wordClass, $search));
    if ($pre_exist == false) {
        mysqli_query($dbConnect, sprintf("DROP TABLE `%s_image_meta`", $search));
    }
    for ($i = 0; $i < $imageMax; $i++) {
        mysqli_query($dbConnect, sprintf("DROP TABLE `%s㉠%s_comment%d`", $wordClass, $search, $i));
    }
    print ("사용량이 초과되었습니다");
    return;
}

try {
    if (!is_dir("photo/" . rawurlencode($search))) {
        mkdir("photo/" . rawurlencode($search));
    } else {
        exec(sprintf("rm -rf %s", rawurlencode($search)));
    }
} catch (Exception $e) {
    print (sprintf("\n[Exception] 예외가 발생했습니다.\n<내용> : %s\n<코드> : %s\n",
        $e->getMessage(), $e->getCode()));
    return;
}

for ($i = 0; $i < $imageMax / 10; $i++) {
    $data = json_decode($google_results[$i], true)['items'];

    for ($j = 0; $j < count($data); $j++) {
        $count = $j + $i * 10;
        try {
            $url = $data[$j]['link'];
            $location = sprintf("photo/%s/", rawurlencode($search));
            $fp = fopen($location . $count . ".jpg", "wb");
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:89.0) Gecko/20100101 Firefox/89.0");
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
            curl_exec($ch);
            fclose($fp);
            curl_close($ch);

            $query = sprintf('update `%s_image_meta` set title = "%s", snippet = "%s" where index_ = %d', $search, base64_encode($data[$i]['title']), base64_encode($data[$i]['snippet']), $count);
            mysqli_query($dbConnect, $query);
        } catch (Exception $e) {
            print (sprintf("\n[Exception] 예외가 발생했습니다.\n<내용> : %s\n<코드> : %s\n",
                $e->getMessage(), $e->getCode()));
            return;
        }
    }
}

mysqli_query($dbConnect, sprintf('UPDATE _terms SET image = %d WHERE term = "%s"', $count, $search));
print ('저장되었습니다.');

