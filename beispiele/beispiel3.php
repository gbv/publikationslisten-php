<?php

require '../publikationsliste.php';

$records = get_records_via_sru("http://sru.gbv.de/opac-de-b1594","pica.gnd=112908071","mods");

if (count($records)) {
    $xpath = new DOMXpath($records[0]->ownerDocument);
    $xpath->registerNamespace("m","http://www.loc.gov/mods/v3");
}

foreach ($records as $mods) {
    echo "---------\n";
    $title = $xpath->evaluate("normalize-space(m:titleInfo/m:title)",$mods);
    $year  = $xpath->evaluate("normalize-space(m:originInfo/m:dateIssued)",$mods);
    echo "($year): $title\n";
    echo $mods->ownerDocument->saveXML($mods) . "\n";
}

?>
