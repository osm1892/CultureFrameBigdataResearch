<?php
$config = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . "/.." . "/config.ini", true);

$dbConnect = new mysqli($config['database']['access_host'], $config['database']['user_id'],
    $config['database']['user_pw'], $config['database']['name'], $config['database']['port']);
if ($dbConnect->connect_errno) {
    die("mysql error");
}

if (empty($_GET['word'])) {
    $_GET['word'] = "";
}
$_GET['word'] = urldecode($_GET['word']);

$GLOBALS['exist'] = false;
$qr = "SELECT term FROM _terms WHERE term=\"" . $_GET['word'] . "\";";

if ($rst = mysqli_fetch_assoc(mysqli_query($dbConnect, $qr))) {
    $GLOBALS['exist'] = true;
}

?>

<!doctype html>
<html lang="en">

<head>
    <title>문화프레임빅데이터연구소</title>
    <meta charset="utf-8">
    <meta
            name="viewport"
            content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link
            rel="stylesheet"
            href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="/resource/vote.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script type="text/javascript" src="/resource/security.js"></script>
    <script
            src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script
            src="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>
    <script id="prescript" type="text/javascript">
        let twinkle = true;
        $(window).on('load', function () {
            $('head').append(
                `<script type="text/javascript" src="/resource/vote.js?${Math.floor(new Date().getTime() / 1000)}"/>`
            );
            if (hasCheckValue()) {
                window
                    .location
                    .reload()
                return;
            }
            data.term = "<?php echo $_GET['word'];?>"
            let spin = $('#spin');
            if (spin != null) {
                spin.hide();
            }
            setInterval(() => {
                $('.str').text(twinkle ? '☆' : '★');
                twinkle = !twinkle;
            }, 500);
            $('#prescript').remove();
        });

        function hide(id) {
            $(`#${id}`).hide();
        }
    </script>
</head>

<body>
<header>
    <div class="navbar navbar-dark bg-dark box-shadow">
        <div
                class="container d-flex justify-content-between"
                style="margin-top:5px;margin-bottom:5px">
            <a
                    href="/index.html"
                    class="navbar-brand d-flex align-items-center no-drag">
                <img width="50px" src="/resource/main.png" alt="main image">
                <strong style="margin-left:10px">문화프레임빅데이터연구소</strong>
            </a>
            <div class="row">
                <div class="col-md-6">
                    <form id="voteSubmit" action="/vote.php" method="get">
                        <input style="display:none;" type="text" class="btn" name="holder" value="!">
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
            style="background-color:#ffffff;margin:0;padding:10px;padding-top:30px">
        <div class="container">
            <div class="center-block">
                <div class="card bg-light mb-3">
                    <div class="card-body" style="padding:30px">
                        <h1 class="jumbotron-heading no-drag">
                            <?php
                            if (!empty($_GET['word'])) {
                                $word = $_GET['word'];
                                if (is_dir('data/photo/' . urlencode($word)) && $GLOBALS['exist']) {
                                    print('
<p style="display: inline-block;font-size:xx-large;"><strong class="str">★</strong> &nbsp' . $_GET['word'] . '&nbsp <strong class="str">★</strong></p>
<p style="margin-top:10px;color:red; font-size:x-large;">해당 단어의 의미를 가장 잘 표현하는 이미지 3개를 선택해주세요!</p>
<p style="color:green; font-size:x-large;">Choose 3 images which best express the meaning of the word!</p>
<p style="color:blue; font-size:x-large;">該当する単語の意味を一番よく表しているイメージを3つお選びください!</p>
<p style="color:purple; font-size:x-large;">请选择最能表达相应单词含义的3个图片!</p>
<div style="padding-top:20px;float:center;display: inline-block;">
    <strong style="float:left;font-size:xx-large;" id="selectedCnt">0</strong>
    <h1 style="float:left;font-size:xx-large;" > / 3 items Selected.</h1>
</div>
<div style="width:100%;height:auto;">
    <button style="float:right;" type="button" id="submit" class="btn btn-info btn-lg" data-toggle="modal" data-backdrop="static" data-keyboard="false" onclick="alert(\'Please choose three.\')" >Submit</button>
</div>
                                                ');
                                }
                            }
                            ?>
                        </h1>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section
            class="jumbotron text-center"
            style="background-color:white;margin:0;padding:10px">
        <div class="container">
            <div class="center-block">
                <div class="card bg-light mb-3">
                    <div class="card-body" style="padding:0">
                        <div class="album " style="padding-top:15px;padding-bottom:15px">
                            <div class="container">
                                <div class="row">
                                    <?php
                                    $mCount = 50;
                                    if (!empty($_GET['word'])) {
                                        $word = $_GET['word'];
                                        if (is_dir('data/photo/' . urlencode($word)) && $GLOBALS['exist']) {
                                            for ($i = 0; $i < $mCount; $i++) {
                                                print('
<div id="load' . $i . '" class="col-md-3">
    <div id="div' . $i . '" class="card mb-3 box-shadow" style="position: relative;">
        <input type="checkbox" id="check' . $i . '" style="position: absolute;zoom: 2;" onclick = "imageClickListener(' . $i . ', false)">
        <img class="card-img-top no-drag" id="image' . $i . '" onclick = "imageClickListener(' . $i . ')" onerror="hide(\'load' . $i . '\')" src="data/photo/' . $word . '/' . $i . '.jpg" style="">
    </div>
</div>
                                                        ');
                                            }
                                        } else {
                                            print('
<div>
<p>지원하지 않는 단어입니다.</p>
</div>
<script type="application/javascript">
    alert("지원하지 않는 단어입니다.");
    window.history.back();
</script>
                                                    ');
                                        }
                                    } else {
                                        if (empty($_GET['holder'])) {
                                            print('<div><p>어서오세요</p></div>');//처음
                                        } else {
                                            print('<div><p>검색어를 입력해주세요.</p></div>');//검색어
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
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
                    <div class="card-body" style="padding:30px">
                        <h1 class="jumbotron-heading no-drag">
                            <?php
                            if (!empty($_GET['word'])) {
                                $word = $_GET['word'];
                                if (is_dir('data/photo/' . urlencode($word)) && $GLOBALS['exist']) {
                                    print('
<strong id="selectedCnt2">0</strong> 
/ 3 items Selected.</h1>
<br><button type="button" id="submit2" class="btn btn-info btn-lg" data-toggle="modal" data-backdrop="static" data-keyboard="false" onclick="alert(\'세 개를 선택해주세요.\')" >Submit</button>
                                        ');
                                }
                            }
                            ?>
                        </h1>
                    </div>
                </div>
            </div>
        </div>
    </section>

</main>
<footer class="text-muted">
    <div class="container">
        <p class="no-drag" style="margin-top:16px">ⓒ 문화프레임빅데이터연구소</p>
    </div>
</footer>

<p hidden id="splitSelectNum">1</p>

<!-- Modal -->
<div class="modal fade" id="splitSelectModal" tabindex="-1" role="dialog" aria-labelledby="splitSelectLabel"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="splitSelectLabel">가장 <?php echo $word ?>같은 부분을 선택해주세요. 1/3</h5>
            </div>
            <div class="modal-body">
                <div>
                    <div class="row">
                        <div class="col-md-6" style="margin: 1% -10px 1% 2%;">
                            <div class="card-img" id="splitImgCard0">
                                <img class="card-img-top no-drag" id="splitImg0">
                            </div>
                        </div>
                        <div class="col-md-6" style="margin: 1% 0 1% -10px;">
                            <div class="card-img" id="splitImgCard2">>
                                <img class="card-img-top no-drag" id="splitImg2">
                            </div>
                        </div>
                        <div class="col-md-6" style="margin: 1% -10px 1% 2%;">
                            <div class="card-img" id="splitImgCard1">>
                                <img class="card-img-top no-drag" id="splitImg1">
                            </div>
                        </div>
                        <div class="col-md-6" style="margin: 1% 0 1% -10px;">
                            <div class="card-img" id="splitImgCard3">>
                                <img class="card-img-top no-drag" id="splitImg3">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6"></div>
                        <div class="col-md-6"></div>
                    </div>
                    <div class="alert alert-warning" role="alert" id="split_alert">
                        3가지 사진에 대해서 모두 체크해주세요!
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="splitClose();">Close</button>
                <button type="button" class="btn btn-secondary" onclick="splitMoveClick(-1);">Prev</button>
                <button type="button" class="btn btn-secondary" onclick="splitMoveClick(1);">Next</button>
                <button type="button" class="btn btn-primary" id="splitSaveButton" onclick="splitSave();">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="commentModal" tabindex="-1" aria-labelledby="commentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="commentModalLabel">이미지를 선택하신 이유를 적어주세요. 1/3</h5>
            </div>
            <div class="modal-body">
                <textarea id="commentArea" cols="40" rows="5"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"
                        onclick="document.getElementById('commentArea').value = ''; $('#commentModal').modal('hide');">
                    Close
                </button>
                <button type="button" class="btn btn-secondary" onclick="commentMove(-1);">Prev</button>
                <button type="button" class="btn btn-secondary" onclick="commentMove(1);">Next</button>
                <button type="button" class="btn btn-primary" onclick="commentSave();">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content" style="width:350px">
            <?php
            if (!empty($_GET['word']) && is_dir('data/photo/' . urlencode($_GET['word'])) && $GLOBALS['exist']) {
                print('
    <div class="modal-header" style="padding-right:30px;padding-left:30px;">
        <h4 class="modal-title no-drag">Thank you!</h4>
</div>
<div class="modal-body">
    <div class="form-group" style="padding-right:15px;padding-left:15px;">
        <label class=" no-drag" for="age">Age:</label>
        <select class="form-control" id="age">
            <option value="0">0 ~ 19</option>
            <option value="1" selected="selected">20 ~ 29</option>
            <option value="2">30 ~ 39</option>
            <option value="3">40 ~ 49</option>
            <option value="4">50 ~ 59</option>
            <option value="5">60 ~ </option>
        </select>
    </div>
    <div
        id="gender-table"
        class="row"
        style="padding-right:15px;padding-left:15px;padding-top:5px;padding-bottom:5px;">
        <div class="col">
            <div class="">
                <div class="row">
                    <input
                        id="male"
                        type="radio"
                        name="gender"
                        data-id="1"
                        checked="checked"
                        autocomplete="off"
                        style="margin-left:20px;margin-top:4px">
                    <p
                        onclick="document.getElementById(\'male\').checked = true"
                        class="no-drag"
                        style="margin-left:10px">Male</p>
                    <input
                        id="female"
                        type="radio"
                        name="gender"
                        data-id="2"
                        autocomplete="off"
                        style="margin-left:20px;margin-top:4px">
                    <p
                        onclick="document.getElementById(\'female\').checked = true"
                        class="no-drag"
                        style="margin-left:10px">Female</p>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer" style="padding:10px 0 0 0;height:55px">
        <button
            style="margin:5px 10px 5px 5px"
            type="btn btn-sm no-drag"
            class="btn btn-default no-drag"
            data-dismiss="modal">Close</button>
        <button
            type="button"
            onclick="sendData()"
            style="margin:5px 10px 5px 5px"
            class="btn btn-success btn-sm no-drag"
            data-dismiss="modal">Submit</button>
    </div>
</div>
                        ');
            } else {
                if (!empty($_GET['word'])) {
                    print('
<div class="modal-header" style="padding-right:30px;padding-left:30px;">
    <h4 class="modal-title no-drag">Downloading Image</h4>
</div>
<div class="modal-body">
    <div class="d-flex justify-content-center">
        <div id="spin" class="spinner-border text-secondary" role="status">
            <span class="sr-only"></span>
        </div>
    </div>
</div>
                            ');
                }
            }
            ?>
        </div>
    </div>
</div>
</body>
</html>
