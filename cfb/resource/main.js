function checkAdmin() {
    let idV = $('#ID');
    let pwV = $('#PW');

    if (pwV.val() === "" || idV.val() === "") {
        return;
    }

    $('#submit').attr('disabled', true);
    $('#cancel').attr('disabled', true);

    idV.hide();
    pwV.hide();

    $('#spin').show();

    let pwe = btoa(SHA256(pwV.val()));

    timeout(300000, fetch(`/admin/management.php?id=${encodeURIComponent(idV.val())}&pw=${encodeURIComponent(pwe)}`))
        .then(
            res => res.text()
        )
        .then(txt => {
            if (txt.includes('<')) {
                throw new Error("오류가 발생했습니다.");
            }
            if (txt.includes('denied')) {
                throw new Error("계정이 올바르지 않습니다.");
            } else {
                document.cookie = `keys=${txt}`;
                window.location.href = "/frame.php?word=Abandonment";
            }
        })
        .catch(error => {
            alert(error.message);
            location.reload();
        });
}

function timeout(ms, promise) {
    return new Promise(function (resolve, reject) {
        setTimeout(function () {
            reject(new Error("timeout"))
        }, ms)
        promise.then(resolve, reject)
    })
}

function goTo() {
    window.location.href = "/frame.php?word=Abandonment";
}

function voteSubmit(_) {
    if (isEmptyOrSpaces($("#voteInput").val())) {
        return false;
    }
}

function outputSubmit(_) {
    if (isEmptyOrSpaces($("#outputInput").val())) {
        return false;
    }
}

function isEmptyOrSpaces(str) {
    return str === null || str.match(/^ *$/) !== null;
}

function inflateRanking() {
    timeout(300000, fetch(`/database/votingCounter.php`))
        .then(
            res => res.json()
        )
        .then(data => {
            console.log(data);

            let dts = data
                .arr
                .map((element, _) => {
                    let sum = ((element.value / data.sum) * 100).toFixed(2);
                    let size = sum < 8
                        ? 8
                        : sum;
                    return {
                        term: element.term,
                        value: element.value,
                        percent: `<div class="progress"><div title="tooltip" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: ${size}%" aria-valuenow="${size}" aria-valuemin="0" aria-valuemax="100">${sum}%</div></div>`
                    };
                });

            let dts2 = data
                .arr2
                .map((element, _) => {
                    return {term: element.term, value: element.value, date: element.date};
                });

            inflation(dts, dts2);
        })
        .catch(error => {
            console.error(error)
        });
}

function inflation(data, data2) {
    data.forEach((a, b, _) => {
        if (a.value != 0) {
            $('#inflation').append(
                `
        <tr class="no-drag">
            <th>${b + 1}</th>
            <td><a  href="/vote.php?word=${encodeURIComponent(a.term)}" class="term">${a.term}</a></td>
            <td>${a.value}</td>
            <td>${a.percent}</td>
        </tr>
        `
            )
        }
    })
    data2.forEach((a, b, _) => {
        if (a.date !== "0000-00-00 00:00:00" && a.value !== 0) {
            $('#inflation2').append(
                `
        <tr class="no-drag">
            <th>${b + 1}</th>
            <td><a  href="/output.php?nationality=korea&term=${encodeURIComponent(a.term)}" class="term">${a.term}</a></td>
            <td>${a.value}</td>
            <td>${a.date}</td>
        </tr>
        `
            )
        }
    })

    $('#spinner2-1').hide();
    $('#spinner2-2').hide();
    $('#spinner2-1').attr('style', 'margin:0px');
    $('#spinner2-2').attr('style', 'margin:0px');
    $('#spin2-1').hide();
    $('#spin2-2').hide();
    $('#tb12').show();
    $('#tb13').show();
}
