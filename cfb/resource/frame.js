var dataCount = 0;
var superTable = {};

function loadData(data) {
    $('#spinner').remove();

    if (data.signal !== 1) {
        hideValue(data.signal);
        return;
    }

    $('#main_div').attr(
        "src",
        `/frameNetData/frame/${data.term}.xml`
    );

    dataCount = data.data.length;

    data
        .data
        .forEach((e, b, _) => {
            let gu = `   
            <tr id="instance_${b}">
        <th scope="row"> 
            <label id="origin_${b}" style="width:100%;padding:3px;text-align:center;"> 
            ${e.term}
            </label>
        </th>
        <td>
            <input id="english_${b}" style="width:100%;padding:3px" value="${e.english === "null" ? "" : e.english}">
        </td>
        <td>
            <input id="korean_${b}" style="width:100%;padding:3px" value="${e.korean === "null" ? "" : e.korean}">
        </td>
        <td>
            <input id="chinese_${b}" style="width:100%;padding:3px" value="${e.chinese === "null" ? "" : e.chinese}">
        </td>
        <td>
            <input id="japanese_${b}" style="width:100%;padding:3px" value="${e.japanese === "null" ? "" : e.japanese}">
        </td>
    </tr>`;
            $('#tbody').append(gu);

        });
    $('#main_div').show();
    $('#sub_div').show();
    $('#crts').show();
    $('#submitParent').show();
    $('#submit').attr("disabled", false);
}

function appendList() {
    for (let ct = 0; ct < SUPERSET.length; ct++) {
        $('#smartboy').append(`
        <a href="/frame.php?word=${encodeURIComponent(SUPERSET[ct])}">${SUPERSET[ct]}</a><br>
        `);
    }

    setHeight();
}

function setHeight() {
    $('#smartboy').height($('#xray').height());
}

function hideValue(signal) {
    $('#sub_div').remove()
    $('#crts').remove()
    $('#super').append(
        signal === -1
            ? `
            <label>
            검색어가 비어있거나 영문이 아닙니다.
            </label>
            `
            : `
            <label>
            존재하지 않는 검색어 입니다.
            </label>
            `
    );
}

// 콤마로 구분된 모든 단어에 대해 trim을 적용한 후, 다시 합쳐서 반환합니다.
function trimAll(str) {
    let splitted = str.split(",");
    for (let i = 0; i < splitted.length; i++) {
        splitted[i] = splitted[i].trim();
    }
    return splitted.join(",");
}

var exinos = [];

function submit() {
    $('#submit').attr("disabled", true);

    if (confirm('해당 작업은 시간이 매우 오래 걸릴 수 있습니다. 제출하시겠습니까?') === false) {
        $('#submit').attr("disabled", false);
        return;
    }

    superTable = {};
    exinos = [];
    $('input').attr("disabled", true);

    let dataList = [];
    let terms = [];

    for (let i = 0; i < dataCount; i++) {
        let origin = $(`#origin_${i}`)
            .text()
            .trim();

        let english = $(`#english_${i}`).val().replaceAll('，', ',');
        let korean = $(`#korean_${i}`).val().replaceAll('，', ',');
        let chinese = $(`#chinese_${i}`).val().replaceAll('，', ',');
        let japanese = $(`#japanese_${i}`).val().replaceAll('，', ',');
        let myData = {
            origin: origin
        };

        myData.english = isEmptyOrSpaces(english) ? "null" : trimAll(english);
        myData.korean = isEmptyOrSpaces(korean) ? "null" : trimAll(korean);
        myData.chinese = isEmptyOrSpaces(chinese) ? "null" : trimAll(chinese);
        myData.japanese = isEmptyOrSpaces(japanese) ? "null" : trimAll(japanese);

        dataList.push(myData)

        myData.english.split(',').forEach(e => {
            if (e !== "null") {
                superTable[e] = 'english';
                exinos.push([origin, e]);
                terms.push(e);
            }
        })
        myData.korean.split(',').forEach(e => {
            if (e !== "null") {
                superTable[e] = 'korean';
                exinos.push([origin, e]);
                terms.push(e);
            }
        })
        myData.chinese.split(',').forEach(e => {
            if (e !== "null") {
                superTable[e] = 'chinese';
                exinos.push([origin, e]);
                terms.push(e);
            }
        })
        myData.japanese.split(',').forEach(e => {
            if (e !== "null") {
                superTable[e] = 'japanese';
                exinos.push([origin, e]);
                terms.push(e);
            }
        })
    }

    if (dataList.length === 0) {
        alert('추가된 용어가 없습니다.');
        window.location.href = "index.html";
    } else {
        let json = {
            data: dataList,
            super: term
        };
        let postData = JSON.stringify(json);
        console.log(json);

        fetch('/data/imageManagement.php', {
            method: 'POST',
            headers: {
                'Accept': 'application/text',
                'Content-Type': 'application/text'
            },
            credentials: "same-origin",
            body: postData
        })
            .then((res) => res.json())
            .then((data_) => {
                console.log(data_)
                if (data_.status === "denied") {
                    alert('잘못된 접근입니다.');
                    window.location.href = "index.html";
                } else {
                    saveImages(data_.suc);
                }
            });
    }
}

function saveImages(terms) {
    $("#myModal").modal({keyboard: false, backdrop: "static"});

    recursive(0, terms, [])
}

function recursive(integer, terms, data) {
    if (integer >= terms.length) {
        setProgress(100);
        setTimeout(() => {
            if (data.length === 0) {
                alert('완료되었습니다.')
            } else {
                let errorWordList = [];
                data.forEach((a, b, _) => {
                    errorWordList.push(a);
                })
                alert(`완료되었습니다.\n하지만 다음과 같은 단어에 대해 오류가 발생했을 수 있습니다.\n[${errorWordList.toString()}]`);
            }

            location.reload();
        }, 1000);

    } else {
        let percentage = (integer / terms.length) * 100;
        setProgress(percentage.toFixed(2))
        console.log(terms[integer]);

        let origin = "";

        exinos.forEach(e => {
            if (e[1] === terms[integer]) {
                origin = e[0];
            }
        });
        let interval = setInterval(() => {
            addProgress((100 / terms.length) / 50);
        }, 1000);
        let tm = terms[integer];
        console.log(`${tm} : ${superTable[tm]}`);
        timeout(
            300000,
            fetch(`/data/getimage.php?search=${encodeURIComponent(tm)}&super=${encodeURIComponent(term)}&origin=${encodeURIComponent(origin)}&nation=${encodeURIComponent(superTable[tm])}`, {credentials: "same-origin"})
        )
            .then(res => res.text())
            .then(txt => {
                if (interval) clearInterval(interval);
                console.log(txt);
                if (txt.includes('<')) {
                    throw new Error(txt);
                }
                if (txt.includes('사용량')) {
                    data.push(terms[integer]);
                }
                recursive(integer + 1, terms, data);
            })
            .catch(error => {
                data.push(terms[integer]);
                recursive(integer + 1, terms, data);
            });
    }
}

function setProgress(percentage) {
    $('#gres').attr('style', `width: ${percentage}%`);
    $('#gres').attr('aria-valuenow', percentage);
    $('#gres').text(`${percentage}%`);
}

function addProgress(percentage) {
    let now = $('#gres').attr('aria-valuenow') * 1;
    let af = (now + percentage).toFixed(2);
    if (af < 100) {
        $('#gres').attr('style', `width: ${af}%`);
        $('#gres').attr('aria-valuenow', af);
        $('#gres').text(`${af}%`);
    }
}

function timeout(ms, promise) {
    return new Promise(function (resolve, reject) {
        setTimeout(function () {
            reject(new Error("timeout"))
        }, ms)
        promise.then(resolve, reject)
    })
}

function isEmptyOrSpaces(str) {
    return str === null || str.match(/^ *$/) !== null;
}
