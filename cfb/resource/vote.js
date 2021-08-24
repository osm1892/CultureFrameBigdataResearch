// 현재 투표되는 단어의 정보입니다.
let data = {
    term: "",
    age: "",
    gender: "",
    values: [],
    splitVoteds: [[0, 0, 0, 0], [0, 0, 0, 0], [0, 0, 0, 0]],
    comment: ["", "", ""],
    origin: "",
};

// 현재 선택된 이미지 번호 목록입니다.
let selectedImages = [];

// selectedImages 중에서 현재 표시되는 잘린 이미지의 번호입니다.
let currentImgOrder = 0;
// comment 중에서 현재 표시되는 위치입니다.
let commentPos = 0;

// 
let splitCardImgStyle = "";

// 쿠키에 데이터를 추가합니다.
function addCookie(key, value) {
    if (document.cookie !== "") {
        document.cookie += ";";
    }
    document.cookie += `${key}=${value};`;
}

// 쿠키에서 값을 읽습니다.
function getCookie(key) {
    let cookie = document.cookie.split(";");

    for (let i = 0; i < cookie.length; i++) {
        let cur = cookie[i].split("=");
        if (key === cur[0]) {
            return cur[1];
        }
    }
    return undefined;
}

// 쿠키에서 데이터를 제거합니다.
function delCookie(key) {
    let cookie = document.cookie.split(";");

    let value = getCookie(key);
    cookie = cookie.filter(cur => cur !== `${key}=${value}`);

    document.cookie = cookie.join(";");
}

// 쿠키의 데이터를 설정합니다.
function setCookie(key, value) {
    if (getCookie(key) === undefined) {
        // 해당 값이 존재하지 않는다면, 값을 추가합니다.
        addCookie(key, value);
    } else {
        // 해당 값이 존재한다면, 기존 값을 제거하고, 새로 추가합니다.
        delCookie(key);
        addCookie(key, value);
    }
}

// body가 로딩되었을 시 실행되는 함수입니다.
function bodyOnLoadListener() {
    // 모바일 사이즈 변환에 따른 창 크기 대응
    modalAdjust();
    window.addEventListener('resize', function () {
        modalAdjust();
    });
}

// 화면 크기를 인식하여, modal에서 나타나는 컨텐츠의 상태를 조정하는 함수입니다.
function modalAdjust() {
    if (window.innerWidth < 768) {
        splitCardImgStyle = "width: 100px; height: auto; object-fit: contain;";
        for (let i = 0; i < 4; i++) {
            const col = document.getElementById(`splitCol${i}`);
            col.className = "col-xs-6";
            if (i < 2) {
                col.style = "margin: 1% 1% 1% 15%;";
            } else {
                col.style = "margin: 1% 1% 1% 1%;";
            }
        }
        document.getElementById("commentArea").cols = "25";
    } else {
        splitCardImgStyle = "";
        for (let i = 0; i < 4; i++) {
            const col = document.getElementById(`splitCol${i}`);
            col.className = "col-md-6";
            if (i < 2) {
                col.style = "margin: 1% -10px 1% 2%;";
            } else {
                col.style = "margin: 1% 0 1% -10px;";
            }
        }
        document.getElementById("commentArea").cols = "40";
    }

    for (let i = 0; i < 4; i += 1) {
        if (data.splitVoteds[currentImgOrder][i] === 1) {
            document.getElementById(`splitImgCard${i}`).style = splitCardImgStyle + "background-color:black;";
            document.getElementById(`splitImg${i}`).className = "card-img-top backgroundDark no-drag";
        } else {
            document.getElementById(`splitImgCard${i}`).style = splitCardImgStyle + "";
            document.getElementById(`splitImg${i}`).className = "card-img-top no-drag";
        }
    }
}

// image 를 선택했을 때 호출되는 함수입니다.
function imageClickListener(i, ii = true) {
    let box = document.getElementById(`check${i}`);
    let item = document.getElementById('selectedCnt');
    let item2 = document.getElementById('selectedCnt2');
    let image = document.getElementById(`image${i}`);
    let div = document.getElementById(`div${i}`);
    let submit = document.getElementById('submit');
    let submit2 = document.getElementById('submit2');
    let times = getCount();

    if (ii) {
        box.checked = !box.checked;
    }

    if (times >= 3 && box.checked) {
        box.checked = false;
        return;
    }

    if (box.checked) {
        data.values.push(i);
        data.values.sort();
        times++;
    } else {
        data.values = data.values.filter(x => x !== i);
    }

    item.innerText = 1 * item.innerText + 2 * (box.checked - 0.5);
    item2.innerText = item.innerText;

    if (box.checked) {
        image.className = "card-img-top backgroundDark no-drag";
        div.style = "position: relative;background-color:black;";
        selectedImages.push(i);
        selectedImages.sort();
    } else {
        image.className = "card-img-top no-drag";
        div.style = "position: relative;";
        selectedImages = selectedImages.filter(x => x !== i);
    }

    if (times !== 3) {
        submit.setAttribute('onclick', 'alert("Please choose three.")');
        submit.setAttribute('data-target', null);
        submit2.setAttribute('onclick', 'alert("Please choose three.")');
        submit2.setAttribute('data-target', null);
    } else {
        submit.setAttribute('onclick', null);
        submit.setAttribute('data-target', "#splitSelectModal");
        submit2.setAttribute('onclick', null);
        submit2.setAttribute('data-target', "#splitSelectModal");

        currentImgOrder = 0;
        cutImageUp(selectedImages[currentImgOrder]);
    }
}

// 이미지 번호를 받아, 잘린 이미지를 출력합니다.
function cutImageUp(imageNum) {
    $('#split_alert').hide();
    // 알 수 없는 꺽쇄가 나타나는 현상이 발생하여, splitImgCard를 사용 전 초기화해줍니다.
    for (let i = 0; i < 4; i++) {
        document.getElementById(`splitImgCard${i}`).innerHTML = `<img class="card-img-top no-drag" id="splitImg${i}" onclick="splitImageClickListener(${i})">`;
    }

    // 투표용 이미지 목록으로부터 이미지 원본을 받아옵니다.
    let image = document.getElementById("image" + imageNum);
    // 이미지 조각 데이터를 저장하기 위한 배열입니다.
    let imagePieces = [];

    // x, y를 순회하면서, 이미지를 잘라, 새로운 이미지 객체로 만듭니다.
    for(let x = 0; x < 2; ++x) {
        for(let y = 0; y < 2; ++y) {
            let canvas = document.createElement('canvas');
            let context = canvas.getContext('2d');
            let width = image.naturalWidth / 2;
            let height = image.naturalHeight / 2;
            canvas.width = image.naturalWidth / 2;
            canvas.height = image.naturalHeight / 2;

            context.drawImage(image, x * width, y * height, width, height, 0, 0, canvas.width, canvas.height);
            imagePieces.push(canvas.toDataURL());
        }
    }

    // imagePieces는 잘린 이미지의 데이터를 담고 있는 base64 URL 목록입니다.

    // 네 개의 잘린 이미지를 표시합니다.
    for (let pos = 0; pos < 4; pos++) {
        let imagePiece = document.getElementById('splitImg' + pos);
        imagePiece.src = imagePieces[pos];
    }
    // modal 화면을 조정합니다.
    modalAdjust();
}

// split modal 에 이동 버튼을 눌렀을 경우 호출되는 함수입니다.
function splitMoveClick(move) {
    currentImgOrder = (currentImgOrder + 3 + move) % 3;

    document.getElementById("splitSelectLabel").innerText = document.getElementById("splitSelectLabel").innerText.replace(/[0-9]\/3/, currentImgOrder + 1 + "/" + 3);
    cutImageUp(selectedImages[currentImgOrder]);
}

// split modal 을 닫을 때, 데이터를 초기화하기 위한 함수입니다.
function splitClose() {
    data.splitVoteds = [[0, 0, 0, 0], [0, 0, 0, 0], [0, 0, 0, 0]];

    modalAdjust();

    $("#splitSelectModal").modal('hide');
}

// split modal 에서 save 를 눌렀을 경우 호출되는 함수입니다.
function splitSave() {
    let finished = data.splitVoteds.filter(function(value) {
        return value.toString() === [0, 0, 0, 0].toString();
    }).length === 0;

    if (!finished) {
        $('#split_alert').show();
        return;
    }

    modalAdjust();

    $("#commentModal").modal("show");
}

// split image 를 클릭했을 경우 호출되는 함수입니다.
function splitImageClickListener(pos) {
    if (data.splitVoteds[currentImgOrder][pos] === 1) {
        data.splitVoteds[currentImgOrder][pos] = 0;
    } else {
        data.splitVoteds[currentImgOrder][pos] = 1;
    }

    modalAdjust();
}

function commentMove(move) {
    data.comment[commentPos] = document.getElementById("commentArea").value;
    commentPos = (commentPos + 3 + move) % 3;
    document.getElementById("commentModalLabel").innerText = document.getElementById("commentModalLabel").innerText.replace(/[0-9]\/3/, commentPos + 1 + "/" + 3);
    document.getElementById("commentArea").value = data.comment[commentPos];
}

// comment 를 남기고 save 를 누르면 호출되는 함수입니다.
function commentSave() {
    data.comment[commentPos] = document.getElementById("commentArea").value;
    document.getElementById("commentArea").value = "";
    $("#splitSelectModal").modal("hide");
    $("#commentModal").modal("hide");
    $("#myModal").modal("show");
}

// 데이터를 리셋하는 함수입니다.
function resetData() {
    data.term = "";
    data.age = 0;
    data.gender = "";
    data.values = [];
    data.splitVoteds = [[0, 0, 0, 0], [0, 0, 0, 0], [0, 0, 0, 0]];
    data.comment = "";
    data.origin = "";
}

// data 에서 몇개가 선택되었는지를 반환하는 함수입니다.
function getCount() {
    return data.values.length;
}

// 데이터를 management 파일로 전송하여, DB 처리를 하기 위한 함수입니다.
function sendData() {
    data.age = document
        .getElementById('age')
        .value;
    data.gender = document
        .getElementById('male')
        .checked ?
        "male" :
        "female";

    let postData = JSON.stringify(data);

    fetch('/database/management.php', {
        method: 'POST',
        headers: {
            'Accept': 'application/text',
            'Content-Type': 'application/text'
        },
        body: postData
    })
        .then((res) => res.text())
        .then((data_) => {
            console.log(data_);
            window.location = data_;
        });
    resetData();
}

function GenerateHMAC(key, payload) {
    const timestamp = new Date().getTime();
    const message = btoa(payload + timestamp);
    const hash = CryptoJS.HmacSHA256(message, key);
    return CryptoJS
        .enc
        .Base64
        .stringify(hash);
}

function timeout(ms, promise) {
    return new Promise(function (resolve, reject) {
        setTimeout(function () {
            reject(new Error("timeout"))
        }, ms)
        promise.then(resolve, reject)
    })
}

function hasCheckValue() {
    let cv = false;

    for (let c = 0; c < 50; c++) {
        let box = document.getElementById(`check${c}`);
        if (box != null && box.checked) {
            cv = true;
            break;
        }
    }
    return cv;
}
