<?php
// GET으로 입력된 단어에 따라, 해당 super term과 관련된 frameNet xml 파일을 파싱하여,
// xml 파일에 들어있는 연관 original 영단어 목록을 가져옵니다.
// 해당 영단어에 따라, DB에서 영한중일 단어 목록을 가져와 json 형식으로 출력합니다.

// config 파일을 불러옵니다.
$config = parse_ini_file(sprintf("%s/../config.ini", $_SERVER['DOCUMENT_ROOT']), true);

// DB에 연결합니다.
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

// 만약 term 데이터가 없다면, signal -1을 반환합니다.
if (empty($_GET['term'])) {
    print('{"signal":-1, "data":[]}');
    return;
}

include_once 'simple_html_dom.php';

// term 데이터를 받습니다.
$term = rawurldecode($_GET['term']);

// 해당 super term에 대한 frameNet xml 파일을 불러옵니다.
$response = file_get_contents("frame/" . $term . ".xml");

// 만약 파일이 존재하지 않는다면, signal 0을 반환합니다.
if ($response == false || strpos($response, "not found") !== False) {
    print('{"signal":0, "data":[]}');
    return;
}

// xml을 파싱합니다.
$xslt = new xsltProcessor;
$dom = new DOMDocument();
$dom->load('frame.xsl');
$xslt->importStyleSheet($dom);

$dom->loadXML($response);
$data = $xslt->transformToXML($dom);
$html = new simple_html_dom();
$html->load($data);

// 세번째 테이블 칸에는 해당 super term에 관련된 영단어 목록이 있습니다.
$table = $html->find('table')[2];

// 단어 목록을 json 형식으로 패킹하여 출력합니다.
$json = array("term" => $term, "signal" => 1, "data" => []);

// xml상의 연관 단어들을 파싱하면서, 해당 단어에 대응되는 영한중일 단어 목록을 DB에서 뽑아온 후
// json에 담아 출력합니다.
$isFirst = true;
foreach ($table->find('tr') as $dolor) {
    // 첫번째는 의미없는 부분이고, 2번째 요소부터 단어가 나옵니다.
    if ($isFirst == true) {
        $isFirst = false;
        continue;
    }
    // $txt는 xml에 존재하는 original 단어입니다.
    $txt = $dolor->find('td')[0]->innertext;

    // original 단어와 관련하여, 이전에 입력된 한중일 단어 목록을 가져옵니다.
    $query_origin = sprintf('SELECT * FROM _origin WHERE term="%s";', $txt);

    $english = "null";
    $korean = "null";
    $chinese = "null";
    $japanese = "null";

    while ($result = mysqli_fetch_assoc(mysqli_query($dbConnect, $query_origin))) {
        $english = $result["english"];
        $korean = $result["korean"];
        $chinese = $result["chinese"];
        $japanese = $result["japanese"];
        break;
    }
    array_push($json["data"], ["term" => $txt, "english" => $english, "korean" => $korean, "chinese" => $chinese, "japanese" => $japanese]);
}

print(json_encode($json, JSON_UNESCAPED_UNICODE));

?>
