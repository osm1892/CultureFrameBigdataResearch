<?php

include_once 'simple_html_dom.php';

if (empty($_GET['name'])) {
    echo "not found";
    return;
}

$name = $_GET['name'];

$response = file_get_contents("frame/" . $name . ".xml");

if ($response == false || strpos($response, "not found") !== False) {
    echo "not found";
    return;
}

// xml을 파싱합니다.
$xslt = new xsltProcessor;
$dom = new DOMDocument();
$dom->load('frame/frame.xsl');
$xslt->importStyleSheet($dom);

$dom->loadXML($response);
$data = $xslt->transformToXML($dom);
$html = new simple_html_dom();
$html->load($data);

$h3s = array_slice($html->find('h3'), 1);
foreach ($h3s as $remove) {
    $remove->outertext = '';
}

foreach ($html->find('h4') as $remove) {
    $remove->outertext = '';
}

$tables = array_slice($html->find('table'), 1);
foreach ($tables as $remove) {
    $remove->outertext = '';
}

foreach ($html->find('p') as $remove) {
    $remove->outertext = '';
}

$html->find('hr')[0]->outertext = '';

print($html);