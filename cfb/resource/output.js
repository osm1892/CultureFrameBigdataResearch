let TERM = "";
let ORIGIN = "";
let male = [];
let female = [];
let sm = [];
let m_c = [];
let f_c = [];
let m_c_f = 0;

function setUp(term, origin) {
    if (term.trim() === "") {
        alert("잘못된 접근입니다.")
        return;
    }

    TERM = term;
    ORIGIN = origin;

    $('#hcls').text(TERM);

    fetch(`/database/ranking.php?term=${encodeURIComponent(term)}&origin=${encodeURIComponent(origin)}`)
        .then(
            res => {
                if (res.ok) {
                    return res.json()
                } else {
                    return false;
                }
            }
        )
        .then(data => {
            if (data === false) {
                throw Error('');
            }
            console.log(data);
            doit(data);
        })
        .catch(error => {
            console.error(error);
            alert('존재하지 않는 단어입니다.');
            //window.location.href = "index.html";
        });
}

function setSelect(nationality, trm) {
    let spliter;
    let trms = trm;
    switch (nationality) {
        case "chinese":
            spliter = localData.chinese;
            break;
        case "japanese":
            spliter = localData.japanese;
            break;
        case "korean":
            spliter = localData.korean;
            break;
        default:
            spliter = localData.english;
            break;
    }

    if (trm == null) {
        trms = spliter[0];
    }

    if (spliter.includes(trms)) {
        $('#terms').empty();
        for (let count = 0; count < spliter.length; count++) {
            let option = $("<option>" + spliter[count] + "</option>");
            $('#terms').append(option);
        }
        $('#terms')
            .val(trms)
            .prop("selected", true);
    }
}

function doit(data) {
    male = [
        [],
        [],
        [],
        [],
        [],
        [],
        []
    ];
    sm = [];
    m_c = [0, 0, 0, 0, 0, 0, 0];
    female = [
        [],
        [],
        [],
        [],
        [],
        [],
        []
    ];
    f_c = [0, 0, 0, 0, 0, 0, 0];

    m_c_f = 0;

    let topWhat = 10;
    let arr = data.data;

    let sortMethod = (a, b) => {
        return a.dt > b.dt ?
            -1 :
            a.dt < b.dt ?
                1 :
                0;
    };

    for (let c = 0; c < 6; c++) {
        let m_t = 0;
        let f_t = 0;
        male[c] = arr.map((e, _) => {
            m_t += e.male[c];
            return {
                index: e.index,
                dt: e.male[c]
            };
        }).sort(sortMethod).slice(0, topWhat);

        female[c] = arr.map((e, _) => {
            f_t += e.female[c];
            return {
                index: e.index,
                dt: e.female[c]
            }
        }).sort(sortMethod).slice(0, topWhat);

        m_c[c] = m_t;
        f_c[c] = f_t;
    }

    arr.forEach(e => {
        let mx = 0;

        for (let v = 0; v <= 6; v++) {
            mx += e.male[v] + e.female[v];
            m_c_f += e.male[v] + e.female[v];
        }

        sm.push({index: e.index, dt: mx});
    });

    sm = sm.sort(sortMethod).slice(0, topWhat);

    console.log(male);
    console.log(female);
    console.log(sm);
    setRanking();
}

// 랭킹을 매기는 메서드 m은 male, f는 female을 뜻함 각 연령대 (0 ~ 6) 까지 표를 많이 받은 순서대로 입력되어있음.
// index는 사진의 인덱스, dt는 표의 개수
function setRanking() {
    var gender = document
        .getElementById('gender')
        .value

    var section = document
        .getElementById('age')
        .value

    var mobile = false;
    if (isMobile()) {
        mobile = true;
        $(`#thead-table`).hide();
        showViews(`#age`, `#age-div`);
    } else {
        $(`#thead-table`).show();
        hideViews(`#age`, `#age-div`);
    }

    let v = gender === 'male' ? male : female;
    let v_c = gender === 'male' ? m_c : f_c;

    for (let z = -1; z < 6; z++) {
        let ia = z !== -1;
        let sum_m = ia ? v_c[z] : m_c_f;
        for (let c = 1; c <= 10; c++) {
            let src = "";
            let ln;
            let tds = `#td_${c}_${z}`;
            let mtx = `#mtx_${c}_${z}`;
            let ale = `#male_${c}_${z}`;
            let cale = `#cmale_${c}_${z}`;
            let gcale = `#gcmale_${c}_${z}`;
            let VZ = ia ? v[z] : sm;

            if (VZ[c - 1].dt !== 0) {
                // 브라우저에서 URL을 자체적으로 디코딩 하기 때문에, 인코딩을 두번 해주어야 올바른 파일 경로를 참조합니다.
                src = `data/photo/${encodeURIComponent(encodeURIComponent(TERM))}/${VZ[c - 1].index}.jpg`;
                $(ale).attr("src", src);
                $(ale).attr("title", `${VZ[c - 1].dt} / ${sum_m}`)
                ln = ((VZ[c - 1].dt / sum_m) * 100).toFixed(2);
                $(cale).attr("style", `width:${ln}%;`);
                $(cale).attr("aria-valuenow", ln);
                $(mtx).text(`${ln}%`);
                showViews(cale, mtx, gcale);
            } else {
                hideViews(cale, mtx, gcale);
                $(ale).attr("title", "");
                $(ale).attr("src", '');
            }

            if (mobile) {
                hideViews(gcale, cale);
                if (section === z || (section === 7 && z === -1)) {
                    showViews(tds);
                    $(tds).attr("style", 'width:100%;padding:3px');
                    $(tds).attr("class", '');
                } else {
                    hideViews(tds, mtx);
                    $(tds).attr("style", 'width:0%;padding:0px');
                    $(tds).attr("class", '');
                }
            } else {
                $(tds).attr("style", '');
                $(ale).click(() => {
                    $('#modal_image').attr("src", src)
                    $("#myModal").modal()
                });
            }
        }
    }
}

function showViews(...views) {
    views.forEach(e => {
        $(e).show();
    })
}

function hideViews(...views) {
    views.forEach(e => {
        $(e).hide();
    })
}

function timeout(ms, promise) {
    return new Promise(function (resolve, reject) {
        setTimeout(function () {
            reject(new Error("timeout"))
        }, ms)
        promise.then(resolve, reject)
    })
}

function isMobile() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
        navigator.userAgent
    );
}
