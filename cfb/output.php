<?php
$config = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . "/.." . "/config.ini", true);

$dbConnect = new mysqli($config['database']['access_host'], $config['database']['user_id'], $config['database']['user_pw'], $config['database']['name'], $config['database']['port']);
if ($dbConnect->errno) {
    die("mysql error");
}

if (empty($_GET['term'])) {
    $_GET['term'] = "";
}
$_GET['term'] = rawurldecode($_GET['term']);

function isChinese($string) {
    return preg_match("/\p{Han}+/u", $string);
}

function isJapanese($string) {
    return preg_match('/[\x{4E00}-\x{9FBF}\x{3040}-\x{309F}\x{30A0}-\x{30FF}]/u', $string);
}

function isKorean($string) {
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

$qr = "SELECT origin,nationality FROM _terms WHERE term='{$_GET['term']}'";
$GLOBALS['exist'] = false;

if (empty($_GET['index'])) {
    $_GET['index'] = 0;
}

$indexx = $_GET['index'];

$origins = array();
$myUrl = sprintf("output.php?nationality=%s&term=%s&origin=%s&index=", $_GET['nationality'], rawurlencode($_GET['term']), $_GET['origin']);
$cntt = 0;

$aaa = mysqli_query($dbConnect, $qr);
while ($rst = mysqli_fetch_assoc($aaa)) {
    $origins[] = $rst["origin"];
    if ($cntt == $indexx) {
        $GLOBALS["origin"] = $rst["origin"];
        $GLOBALS['exist'] = true;
        $GLOBALS['nationality'] = $rst["nationality"];
        //break;
    }
    $cntt++;
}

if ($GLOBALS['exist']) {
    $qr = "SELECT * FROM _origin WHERE term='{$GLOBALS["origin"]}'";
    while ($rst = mysqli_fetch_assoc(mysqli_query($dbConnect, $qr))) {
        $GLOBALS['english'] = $rst["english"];
        $GLOBALS['korean'] = $rst["korean"];
        $GLOBALS['chinese'] = $rst["chinese"];
        $GLOBALS['japanese'] = $rst["japanese"];
        break;
    }
}

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>문화프레임빅데이터연구소</title>
    <link
        rel="stylesheet"
        href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="/resource/output.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script
        src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script
        src="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>
    <script id="prescript" type="text/javascript">
        var localData = {
            english: "<?php echo $GLOBALS['english'];?>".split(","),
            korean: "<?php echo $GLOBALS['korean'];?>".split(","),
            chinese: "<?php echo $GLOBALS['chinese'];?>".split(","),
            japanese: "<?php echo $GLOBALS['japanese'];?>".split(",")
        };
        console.log(localData);
        $(window).on('load', function () {
            var agent = navigator
                .userAgent
                .toLowerCase();

            // 인터넷익스플로러 배제 포함시키는게 사실상 새로 짜는것과 같음
            if ((navigator.appName === 'Netscape' && navigator.userAgent.search('Trident') !== -1) || (agent.indexOf("msie") !== -1)) {
                $('#modalcontext').empty();
                $('#cancel').hide();
                $('#submit').hide();
                $('#modaltext').text("Information");
                $('#modalcontext').append(
                    "<p style='width:100%; font-size:16px;' align='center'><b>Sorry,<br>Support for" +
                    " this browser has ended.</b></p>"
                );
                $('#modalcontext').append(
                    "<p style='width:100%; font-size:13px;' align='center'>This page is optimized f" +
                    "or Chrome browser.</p>"
                );
                $('#modalcontext').append(
                    "<p align='center'><a style='width:100%;' target='_blank' href='https://www.goo" +
                    "gle.com/intl/en/chrome/'>Install Google Chrome Browser</a></p>"
                );

                $('#myModal2').modal({backdrop: 'static', keyboard: false, show: true});

            }

            if ("<?php if ($GLOBALS['exist']) echo 'true'; ?>" !== "true") {
                alert('존재하지 않는 단어입니다.');
                window
                    .history
                    .back();
                return;
            }
            $('head').append(
                `<script type="text/javascript" src="/resource/output.js?${Math.floor(new Date().getTime() / 1000)}"/>`
            );
            let v = "<?php echo $GLOBALS['nationality'];?>";
            let tr = "<?php echo $_GET['term'];?>";
            $("#nationality")
                .val(v)
                .prop("selected", true);
            $('#gender')
                .val("male")
                .prop("selected", true);

            setSelect(v, tr);

            $('#nationality').change(() => {
                setSelect($("#nationality option:selected").val(), null);
                setUp($("#terms option:selected").val(), $("#origin option:selected")[0].text);
            });
            $('#terms').change(() => {
                setUp($("#terms option:selected").val(), $("#origin option:selected")[0].text);
            });
            $('#gender').change(() => {
                setRanking()
            });
            $('#age').change(() => {
                setUp($("#terms option:selected").val(), $("#origin option:selected")[0].text);
            });

            setUp($("#terms option:selected").val(), $("#origin option:selected")[0].text);
            $('#prescript').remove();
        });
    </script>
</head>

<body>
<header>
    <style>
        .bg-gray {
            background-color: LightGray;
        }
    </style>
    <div class="navbar navbar-light box-shadow" style="background-color: #b4b4b4;">
        <div
            class="container d-flex justify-content-between"
            style="margin-top:-10px;margin-bottom:-10px">
            <a
                href="/index.html"
                class="navbar-brand d-flex align-items-center no-drag">
                <img height="70px" src="/resource/main.gif">
            </a>
            <div class="row">
                <div class="col-md-6">
                    <form id="voteSubmit" action="/vote.php" method="get">
                        <input style="display:none;" type="text" calss="btn" name="holder" value="!">
                        <input
                            id="voteInput"
                            style="text-align:left;background-color:white;width:auto;display:inline-block;"
                            type="text"
                            class="form-control"
                            name="word"
                            placeholder="Enter a word to vote">
                        <button type="submit" class="btn btn-info" style="margin-bottom:5px">Search</button>
                    </form>
                </div>
                <div class="col-md-6">
                    <form id="outputSubmit" action="/output.php" method="get">
                        <input style="display:none;" type="text" name="nationality" value="korea">
                        <input
                            id="outputInput"
                            style="text-align:left;background-color:white;width:auto;display:inline-block"
                            type="text"
                            class="form-control"
                            name="term"
                            placeholder="Enter a word for result">
                        <button type="submit" class="btn btn-info" style="margin-bottom:5px">Search</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

<main role="main">
    <section
        class="jumbotron text-center"
        style="background-color:white;margin:0px;padding:10px;padding-top:30px">
        <div class="container">
            <div class="card bg-light mb-3">
                <div class="card-body" style="padding:0px">
                    <div class="card-header">
                        <p style="font-size:x-large">Vote Result Of "<?php print($_GET["term"]); ?>"</p>
                        <select
                            class="form-control"
                            id="origin"
                            style="width:130px;display:inline-block;"
                            onchange="location = this.value;">
                            <?php
                            foreach ($origins as $key => $value) {
                                $tmp = "";
                                if (strcmp($value, $GLOBALS["origin"]) == 0) {
                                    $tmp = "selected";
                                }
                                echo sprintf('<option value="%s%d" %s>%s</option>', $myUrl, $key, $tmp, $value);
                            }
                            ?>
                        </select>
                    </div>
                    <div class="row" style="padding:15px">
                        <div class="col-md-4" style="padding: 30px;">
                            <h2 class="no-drag" for="nationality">Nationality</h2>
                            <select
                                class="form-control"
                                id="nationality"
                                style="padding-right:30px;padding-left:30px;margin-top:20px">
                                <option value="english">english</option>
                                <option value="korean">한국</option>
                                <option value="chinese">中國</option>
                                <option value="japanese">日本</option>
                            </select>
                        </div>
                        <div class="col-md-4" style="padding: 30px;">
                            <h2 class="no-drag" for="nationality">Word</h2>
                            <select
                                class="form-control"
                                id="terms"
                                style="padding-right:30px;padding-left:30px;margin-top:20px"></select>
                        </div>
                        <div class="col-md-4" style="padding: 30px;">
                            <h2 class="no-drag" for="gender">Gender</h2>
                            <select
                                class="form-control"
                                id="gender"
                                style="padding-right:30px;padding-left:30px;margin-top:20px">
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>

                        <div id='age-div' class="col-md-4" style="display: none;padding: 30px;">
                            <h2 class=" no-drag" for="gender">Age</h2>
                            <select
                                class="form-control"
                                id="age"
                                style="padding-right:30px;padding-left:30px;margin-top:20px">
                                <option value="0">0-19</option>
                                <option value="1">20-29</option>
                                <option value="2">30-39</option>
                                <option value="3">40-49</option>
                                <option value="4">50-59</option>
                                <option value="5">60~</option>
                                <option value="7" selected>TOTAL</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <section
        class="jumbotron text-center"
        style="background-color:white;margin:0px;padding:10px">
        <div class="container">
            <div class="center-block">
                <div class="card bg-light mb-3">
                    <div class="card-body" style="padding:0px">
                        <div class="album " style="padding:0px">
                            <table class="table" style="width:100%; margin:0px;">
                                <thead id="thead-table">
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col" style="text-align:center">TOTAL</th>
                                    <th scope="col" style="text-align:center">0-19</th>
                                    <th scope="col" style="text-align:center">20-29</th>
                                    <th scope="col" style="text-align:center">30-39</th>
                                    <th scope="col" style="text-align:center">40-49</th>
                                    <th scope="col" style="text-align:center">50-59</th>
                                    <th scope="col" style="text-align:center">60~</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                for ($g = 1; $g <= 10; $g++) {
                                    print('<tr><th scope="row">' . $g . '</th>');
                                    for ($k = -1; $k < 6; $k++)// 성별_랭킹_연령
                                    {
                                        $red = "";
                                        $gray = "";
                                        if ($k == -1) {
                                            $red = "bg-danger ";
                                            $gray = "bg-gray";
                                        }
                                        // print('<td style="width:100%;padding:3px">
                                        print(sprintf('<td class="%s" id="td_%d_%d">
                                                    <img class="card-img-top imgRed" id="male_%d_%d" style="width:100%%;padding:3px">
                                                    <p style="margin-bottom:0px;font-size:small;%s" id="mtx_%d_%d"></p>
                                                    <div id="gcmale_%d_%d" class="progress"><div id="cmale_%d_%d" title="tooltip" class="progress-bar progress-bar-striped progress-bar-animated bg-primary %s" role="progressbar" style="width: 0%%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div></div>
                                                    </td>', $gray, $g, $k, $g, $k, "", $g, $k, $g, $k, $g, $k, $red));
                                    }
                                    print('</tr>');
                                }
                                ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

</main>

<footer class="text-muted">
    <div class="container">
        <p class="no-drag" style="margin-top:16px">
            © 문화프레임빅데이터연구소
        </p>
    </div>
</footer>
<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-body">
                <img id="modal_image" style="width:100%;"/>
            </div>
        </div>
    </div>
</div>
</body>

</html>
