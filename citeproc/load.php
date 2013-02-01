<?php

$data = new stdClass();

function response_code($code) {
    $data = array("error" => $code);
    header(':', true, $code);
}

if (isset($_GET['style'])) {

    ////////////////////////////////////////////
    // GET CSL style from CSL style repository

    $style = $_GET['style'];
    if (!preg_match('/^[a-zA-Z0-9-]+$/', $style)) {
        response_code(400);
    } else if ($xml = @file_get_contents("./styles/$style.csl")) {
        $xml = preg_replace('/^<\?xml.+\n/i','',$xml);
        $data = array( 'style' => $xml );
    } else {
        response_code(404);
    }

} else if(isset($_GET['locale'])) {

    /////////////////////////////////////////////////////////
    // GET citeproc-js locales (from citeproc-js repository)

    $locale = $_GET['locale'];
    if (!preg_match('/^[a-z][a-z](-[A-Z][A-Z])?$/', $locale)) {
        response_code(400);
    } else if ($xml = @file_get_contents("./locales/locales-$locale.xml")) {
        $xml = preg_replace('/^<\?xml.+\n/i','',$xml);
        $data = array( 'locales' => array( $locale => $xml ) );
    } else {
        response_code(404);
    }

} else if(isset($_GET['abbrev'])) {

    // TODO

} else if(isset($_GET['cql'])) {
    $cql   = $_GET['cql'];
    $dbkey = isset($_GET['dbkey']) ? $_GET['dbkey'] : 'gvk';

    // TODO: get via SRU and map MODS to JSON
    // see http://bibliographie-trac.ub.rub.de/wiki/CiteProc-JS
    // and https://github.com/zotero/translators/blob/master/MODS.js 

    $data = json_decode(file_get_contents("samplecites.json"));

} else {

    // TODO: support listing of available styles and locales

}

////////////////////////////
// Serialize JSON or JSONP

if (isset($_GET['callback'])) {
    $callback = $_GET['callback'];
    if (preg_match('/^[a-zA-Z0-9_]+$/', $callback)) {
        header('Content-Type: text/javascript; charset=utf-8');
        header('access-control-allow-origin: *');
        echo $callback . '(' . json_encode($data) . ')';
    } else {
        response_code(400);
    }
} else {
    header('Content-type: application/json; charset=utf-8');
    header('access-control-allow-origin: *');
    echo json_encode($data);
}

?>
