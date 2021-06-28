<?php
# ini config 데이터를 불러옵니다.
$config = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . "/.." . "/config.ini", true);

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
$search = urldecode($_GET['search']);

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
$super = urldecode($_GET['super']);

# 원 영단어를 확인합니다.
if (empty($_GET['origin'])) {
    print("<검색어가 비어있습니다.>");
    return;
}
$origin = urldecode($_GET['origin']);

// api key
$key = $config['api']['key'];
$engineID = $config['api']['engine'];

# 단어의 국적에 따라, api key 를 다르게 설정합니다.
switch (urldecode($_GET['nation'])) {
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
$loop = 50;
$imageCount = 0;
$query = sprintf('SELECT * FROM _terms WHERE term="%s";', $search);
if ($resultElement = mysqli_fetch_assoc(mysqli_query($dbConnect, $query))) {
    $imageCount = $resultElement["image"];
}
$c = 0;
$lowFlag = true;

$query = sprintf('create table `%s_image_meta` (index_ int primary key, title text, snippet text)', $search);
mysqli_query($dbConnect, $query);

for ($i = 0; $i < 50; $i++) {
    $query = sprintf('insert into `%s_image_meta`(index_) values (%d)', $search, $i);
    mysqli_query($dbConnect, $query);
}

//구글 api 호출하여 검색어에 대한 이미지 주소를 json 파일로 받음
for ($as = 0; $as < $loop / 10; $as++) {
    /*
    if ($as == 0) {
        $url = "https://www.googleapis.com/customsearch/v1?key=" . $key . "&cx=" . $engineID . "&searchType=image&q=" . $search . "&num=10";
    } else {
        $url = "https://www.googleapis.com/customsearch/v1?key=" . $key . "&cx=" . $engineID . "&searchType=image&q=" . $search . "&num=10&start=" . $as * 10;
    }
    */
    $url = "https://www.googleapis.com/customsearch/v1?key=" . $key . "&cx=" . $engineID . "&searchType=image&q=" . urlencode($search) . "&num=10&start=" . $as * 10;
    try {
        $response = file_get_contents($url);
        if (strpos($response, 'usageLimits') !== false) {
            $query = sprintf('delete from _terms where term = "%s" and origin = "%s"', $search, $origin);
            mysqli_query($dbConnect, $query);
            $flash = false;

            $query = sprintf('select * from _origin where super = "%s" and term = "%s"', $super, $origin);
            $result = mysqli_query($dbConnect, $query);
            if ($resultElement = mysqli_fetch_assoc($result)) {
                function value($queryResult, $wordNation, $searchWord, $dbConnect, $originWord)
                {
                    $splash = false;
                    $x = explode(',', $queryResult[$wordNation]);
                    $value = "";
                    foreach ($x as $char) {
                        if ($char == $searchWord) {
                            $splash = true;
                        } else {
                            $value = $value . $char . ",";
                        }
                    }
                    if (empty($value)) {
                        $value = "null";
                    } else {
                        $value = substr($value, 0, -1);
                    }
                    if ($splash) {
                        $query = sprintf('UPDATE _origin SET %s = "%s" WHERE %s = "%s" AND term= "%s";',
                            $wordNation, $value, $wordNation, $queryResult[$wordNation], $originWord);
                        mysqli_query($dbConnect, $query);
                    }
                    return $splash;
                }

                if (value($resultElement, "english", $search, $dbConnect, $origin) ||
                    value($resultElement, "korean", $search, $dbConnect, $origin) ||
                    value($resultElement, "chinese", $search, $dbConnect, $origin) ||
                    value($resultElement, "japanese", $search, $dbConnect, $origin)) {
                    break;
                }
            }
            mysqli_query($dbConnect, sprintf("DROP TABLE `%s`", $search));
            mysqli_query($dbConnect, sprintf("DROP TABLE `%s_split`", $search));
            mysqli_query($dbConnect, sprintf("DROP TABLE `%s_image_meta`", $search));
            for ($i = 0; $i < 50; $i++) {
                mysqli_query($dbConnect, sprintf("DROP TABLE `%s_comment%d`", $search, $i));
            }
            print ("사용량이 초과되었습니다");
            return;
        }
        # curl_close($ch);
    } catch (Exception $e) {
        print ("
[Exception] 예외가 발생했습니다.1
<내용> : " . $e->getMessage() . "
<코드> : " . $e->getCode() . "
");
        return;
    }
    try {
        if (!is_dir("photo/" . urlencode($search))) {
            mkdir("photo/" . urlencode($search));
        } else {
            $fi = new FilesystemIterator("photo/" . urlencode($search), FilesystemIterator::SKIP_DOTS);
            if ($lowFlag && iterator_count($fi) >= 50) {
                $lowFlag = false;
            }
        }
    } catch (Exception $e) {
        print ("
[Exception] 예외가 발생했습니다.2
<내용> : " . $e->getMessage() . "
<코드> : " . $e->getCode() . "
");
        return;
    }
    //json으로 변환
    $data = json_decode($response, true);
    $data = $data['items'];
    $createTable = sprintf("CREATE TABLE `%s`(index_ INT primary key, male0 INT, female0 INT, male1 INT, female1 INT, male2 INT, female2 INT, male3 INT, female3 INT, male4 INT, female4 INT, male5 INT, female5 INT, male6 INT, female6 INT);", $search);
    $createSplitTable = sprintf("create table `%s_split`(index_ int primary key, male0_0 int, male0_1 int, male0_2 int, male0_3 int, female0_0 int, female0_1 int, female0_2 int, female0_3 int, male1_0 int, male1_1 int, male1_2 int, male1_3 int, female1_0 int, female1_1 int, female1_2 int, female1_3 int, male2_0 int, male2_1 int, male2_2 int, male2_3 int, female2_0 int, female2_1 int, female2_2 int, female2_3 int, male3_0 int, male3_1 int, male3_2 int, male3_3 int, female3_0 int, female3_1 int, female3_2 int, female3_3 int, male4_0 int, male4_1 int, male4_2 int, male4_3 int, female4_0 int, female4_1 int, female4_2 int, female4_3 int, male5_0 int, male5_1 int, male5_2 int, male5_3 int, female5_0 int, female5_1 int, female5_2 int, female5_3 int, male6_0 int, male6_1 int, male6_2 int, male6_3 int, female6_0 int, female6_1 int, female6_2 int, female6_3 int);", $search);
    mysqli_query($dbConnect, $createTable);
    mysqli_query($dbConnect, $createSplitTable);

    for ($i = 0; $i < 50; $i++) {
        $createCommentTable = sprintf("create table `%s_comment%d` (index_ int primary key", $search, $i);
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

    //link 부분이 url인데 그 링크의 이미지를 다운로드
    for ($i = 0; $i < count($data); $i++) {
        $c = $i + $as * 10;
        if ($c < $imageCount)
            continue;

        if ($lowFlag) {
            try {
                //이미지 주소
                $url = $data[$i]['link'];
                //저장 경로
                $location = "photo/" . urlencode($search) . "/";
                //이미지 저장
                $fp = fopen($location . $c . ".jpg", "wb");
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)");
                curl_setopt($ch, CURLOPT_FILE, $fp);
                curl_exec($ch);
                fclose($fp);
                curl_close($ch);

                // 이미지에 따라 title 과 snippet 저장
                // base64가 URL safe하지 않아, GET을 통한 전송시 urlencode 함수를 사용해야 합니다.
                $query = sprintf('update `%s_image_meta` set title = "%s", snippet = "%s" where index_ = %d', $search, base64_encode($data[$i]['title']), base64_encode($data[$i]['snippet']), $c);
                mysqli_query($dbConnect, $query);
            } catch (Exception $e) {
                error_log("image save error");
            }
        }
        $addToTableValue = sprintf("INSERT INTO `%s` VALUES (%d, %d, %d, %d, %d, %d, %d, %d, %d, %d, %d, %d, %d, %d, %d);", $search, $c, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
        mysqli_query($dbConnect, $addToTableValue);
        $addToSplitTable = sprintf("INSERT INTO `%s_split` VALUES (%d, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);", $search, $c);
        mysqli_query($dbConnect, $addToSplitTable);
    }
}
mysqli_query($dbConnect, sprintf('UPDATE _terms SET image = %d WHERE term = "%s"', $c, $search));
print ('저장되었습니다.');

