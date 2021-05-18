<?php
$config = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . "/.." . "/config.ini", true);
$myPw = $config['server']['hashed_pw'];

$val = hash('sha256', $myPw, false);
if (!isset($_COOKIE['keys']) && $val != $_COOKIE['keys']) {
    print("권한이 없습니다.");
    return;
}

if (empty($_GET['word'])) {
    $_GET['word'] = "";
}

$_GET['word'] = ucfirst($_GET['word']);
//$_GET['word']=ucfirst(preg_replace("/[^a-z _]/i", "", $_GET['word']));
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
    <link rel="stylesheet" href="/resource/frame.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script
            src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script
            src="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>
    <script src="/resource/frame2.js"></script>
    <script id="prescript" type="text/javascript">
        let term = "<?php echo $_GET['word']?>";
        $('head').append(
            `<script type="text/javascript" src="/resource/frame.js?${Math.floor(new Date().getTime() / 1000)}"/>`
        );
        $(window).on('load', function () {
            appendList();

            fetch(`/frameNetData/parse.php?term=${term}`)
                .then(res => {
                    if (res.ok) {
                        return res.json()
                    } else {
                        return false;
                    }
                })
                .then(data => {
                    if (data === false) {
                        alert("error");
                        return;
                    } else {
                        console.log(data);
                        doit(data);
                    }
                    $('#prescript').remove();
                });
        });
    </script>
</head>

<body>
<header>
    <div class="navbar navbar-dark bg-dark box-shadow">
        <div class="container d-flex justify-content-between">
            <a
                    href="/index.html"
                    class="navbar-brand d-flex align-items-center no-drag">
                <img width="50px" src="/resource/main.png">
                <strong style="margin-left:10px">문화프레임빅데이터연구소</strong>
            </a>
            <div style="height:100%;">
                <form id="frameSubmit" action="/frame.php" method="get">
                    <input
                            id="frameInput"
                            style="text-align:left;background-color:white;width:200px;display:inline-block;"
                            type="text"
                            class="form-control"
                            name="word"
                            placeholder="FrameIndex">
                    <button type="submit" class="btn btn-info my-2">Search</button>
                </form>
            </div>
        </div>
    </div>
</header>

<section
        class="jumbotron text-center"
        style="background-color:white;margin:0px;padding-top:20px">
    <div class="row justify-content-md-center">
        <div class="container">

            <div class="row">
                <div class="col-md-10">
                    <div id="xray" class="card bg-light mb-10">
                        <div class="card-body" style="padding:0px">
                            <div class="album " style="padding:0px">
                                <div id="spinner" class="d-flex justify-content-center" style="padding:50px">
                                    <div id="spin" class="spinner-border text-secondary" role="status">
                                        <span class="sr-only"></span>
                                    </div>
                                </div>
                                <div
                                        id="sub_div"
                                        class="embed-responsive embed-responsive-16by9"
                                        style="display:none;">
                                    <iframe
                                            onload="setHeight();"
                                            class="embed-responsive-item"
                                            id="main_div"
                                            style="display:none;padding:20px"
                                            allowfullscreen="allowfullscreen"></iframe>
                                </div>
                                <br>
                            </div>
                        </div>
                    </div>
                </div>


                <div
                        class="col col-lg-2"
                        style="overflow:scroll; width:auto; height:auto;"
                        id="smartboy"></div>
            </div>

            <br>
            <div class="card bg-light mb-3">
                <div class="card-body" style="padding:0px">
                    <div class="album " style="padding:0px">
                        <table id="crts" class="table" style="width:100%; margin:0px; display:none;">
                            <thead>
                            <tr>
                                <th scope="col" style="text-align:center">Origin</th>
                                <th scope="col" style="text-align:center">English</th>
                                <th scope="col" style="text-align:center">Korean</th>
                                <th scope="col" style="text-align:center">Chinese</th>
                                <th scope="col" style="text-align:center">Japanese</th>
                            </tr>
                            </thead>
                            <tbody id="tbody"></tbody>
                        </table>
                        <div id="submitParent" style="width:auto; margin:20px; display:none;">
                            <button
                                    type="button"
                                    id="submit"
                                    onclick="submit();"
                                    class="btn btn-info btn-lg"
                                    style="width:100%;"
                                    disabled="true">Submit
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

</section>

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
        <div class="modal-content" style="width:350px">
            <div class="modal-header" style="padding-right:30px;padding-left:30px;">
                <h4 class="modal-title no-drag">Loading..</h4>
            </div>
            <div class="modal-body">
                <div class="progress">
                    <div
                            id="gres"
                            title="tooltip"
                            class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                            role="progressbar"
                            style="width: 0%"
                            aria-valuenow="0"
                            aria-valuemin="0"
                            aria-valuemax="100">
                        0 %
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="width:auto;margin-right:10px">
                절대로 사이트를 벗어나지 마세요.
            </div>
        </div>
    </div>
</div>
</body>

</html>
