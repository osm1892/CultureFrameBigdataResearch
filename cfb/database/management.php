<?php
$config = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . "/.." . "/config.ini", true);
$data = json_decode(file_get_contents('php://input'));

// error_log + var_dump
function var_error_log($object = null)
{
    ob_start();                    // start buffer capture
    var_dump($object);             // dump the values
    $contents = ob_get_contents(); // put the buffer into a variable
    ob_end_clean();                // end capture
    error_log($contents);          // log contents of the result of var_dump( $object )
}

if (is_null($data)) {
    die("no data");
}

$term = $data->term;
$age = $data->age;
$gender = $data->gender;
$comments = $data->comment;

$dbConnect = new mysqli(
    $config['database']['access_host'],
    $config['database']['user_id'],
    $config['database']['user_pw'],
    $config['database']['name'],
    $config['database']['port']
);
if ($dbConnect->connect_errno) die("mysql error");

$query = sprintf('SELECT _count FROM _terms WHERE term="%s";', $term);
$result = mysqli_query($dbConnect, $query);
$counting = 0;

if ($result === false) {
    error_log("error occurred while loading count");
    return;
}

$fetch_data = mysqli_fetch_assoc($result);
$counting = $fetch_data["_count"];

$tDate = date("Y-m-d H:i:s", time());
$query = sprintf('UPDATE _terms SET _count =%d, _date ="%s" WHERE term = "%s";',
    $counting + 1, $tDate, $term);

if (mysqli_query($dbConnect, $query) === false) {
    error_log("error occurred while updating vote count and time");
    return;
}

for ($i = 0; $i < 3; $i++) {
    $query = sprintf("SELECT %s%s FROM %s WHERE index_=%d;", $gender, $age, $term, $data->values[$i]);
    $result = mysqli_query($dbConnect, $query);

    if ($result === false) {
        error_log("error occurred while loading image voting count data");
        return;
    }

    $fetch_data = mysqli_fetch_assoc($result);
    $vote = $fetch_data[$gender . $age];

    $query = sprintf('UPDATE %s SET %s%s = %d WHERE index_ = %d;',
        $term, $gender, $age, $vote + 1, $data->values[$i]);

    if (mysqli_query($dbConnect, $query) === false) {
        error_log("error occurred while updating image voting result");
        return;
    }

    // load comment count data
    $query = sprintf('select count(*) from %s_comment%d', $term, $data->values[$i]);
    $result = mysqli_query($dbConnect, $query);
    $commentCount = intval(mysqli_fetch_array($result)[0]);

    if ($result === false) {
        error_log("error occurred while loading comment count");
        return;
    }

    // add new comment column
    $query = sprintf('insert into %s_comment%d (index_) values (%d)',
        $term, $data->values[$i], $commentCount);
    $result = mysqli_query($dbConnect, $query);

    if ($result === false) {
        error_log("error occurred while inserting comment column");
        return;
    }

    for ($j = 0; $j < 4; $j++) {
        // if current split image is not selected, continue
        if ($data->splitVoteds[$i][$j] == 0) {
            continue;
        }

        // get split image voting count
        $query = sprintf('select %s%s_%d from %s_split where index_ = %d',
            $gender, $age, $j, $term, $data->values[$i]);
        $result = mysqli_query($dbConnect, $query);

        if ($result === false) {
            error_log("error occurred while voting count about cropped image");
            return;
        }

        // voting count about split image
        $splitVotes = intval(mysqli_fetch_array($result)[0]);

        // add base64 encoded comment data to DB
        $query = sprintf('update %s_comment%d set %s%d_%d = "%s" where index_ = %d',
            $term, $data->values[$i], $gender, $age, $j,
            base64_encode($comments[$i]), $commentCount);
        $result = mysqli_query($dbConnect, $query);
        if ($result === false) {
            error_log("comment update failed");
        }

        // update split image voting count
        $query = sprintf("UPDATE %s_split SET %s%d_%d = %d where index_ = %d;",
            $term, $gender, (int)$age, $j, $splitVotes + 1, $data->values[$i]);

        if (mysqli_query($dbConnect, $query) === false) {
            error_log("error occurred while updating comment data");
            return;
        }
    }
}

echo "/output.php?term=" . $term;
