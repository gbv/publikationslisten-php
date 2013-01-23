<?php
/** 
 * Anfrage an GVK-PSI, Metadaten im MODS-Format und Transformation mittels XSL nach HTML
 * 
 * Beispiel fuer die Verwendung der PHP-Bibliothek "publikationsliste". Siehe auch die Dokumentation unter http://ws.gbv.de/publikationsliste.
 *
 * @author Jakob Voss <jakob.voss@gbv.de>
 * @author Christian Knoop <christian.knoop@gmx.net>
 * @link http://www.gbv.de/wikis/cls/Publikationslisten
 * @package publikationsliste
 * @date 2008-09-02
 **/

### Konfiguration: Logdatei und Loglevel
$publistconf["logfile"] = "../publist.log";
$publistconf["loglevel"] = 2;

### Konfiguration: Cache-Datei
$start = strrpos($_SERVER['PHP_SELF'],'/') + 1;
$length = strrpos($_SERVER['PHP_SELF'],'.') - $start;
$name = substr ( $_SERVER['PHP_SELF'] , $start , $length);
$publistconf["cachefile"] = $name.".cache";

### PHP-Bibliothek einbinden
require '../publikationsliste.php';

### PPNs suchen und Kataloglinks holen. Treffermenge wird auf 10 Titel begrenzt
$ppns = get_ppns_from_psi("http://gso.gbv.de/DB=2.1/", "1004", "von Foerster, Heinz",10);
$psilink = create_psi_link("http://gso.gbv.de/DB=2.1/", "1004", "von Foerster, Heinz");

### Metadaten holen. In diesem Fall wird Format direkt angegeben
$records = get_records_via_unapi("http://unapi.gbv.de/", $ppns, "mods", "gvk:ppn:");

### Ausgabe
?>

<html>
<head>
	<title>VZG - Beispiel Publikationslisten</title>
	<link rel="stylesheet" href="http://ws.gbv.de/daia//daia.css" type="text/css" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body>
<h1>Suche nach der Person Heinz von Foerster im Katalog der SUB Göttingen</h1>

<p>Diese Publikationsliste wird automatisch aus <a href="<?php echo $psilink; ?>">einer Suchanfrage</a> an den <a href="http://gso.gbv.de/DB=2.1/">GVK</a> erstellt.</p>

<p>Treffermenge auf 10 Titel begrenzt</p>

<?php

# Folgende Umsetzung gibt Fehler mit XAMPP 1.6.7: Verfahren bricht mit Fehler ab, falls sowohl DOMDocument als auch DOMXML installiert sind. Behebung: DOMXML-Extension in der php.ini auskommentieren!

# XSL-Dokumente als XML einlesen
$xsldoc = new DOMDocument();
$xsldoc->load("mods2html.xsl");

# XSL-Porzessor aufrufen und XSL-Datei uebergeben
$xslproc = new XSLTProcessor();
$xslproc->importStyleSheet($xsldoc);

# MODS-XML-Dokument vorbereiten
$xmldoc = new DOMDocument();

# Alle MODS-Metadaten in eine Collection schreiben
$collection = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n\n<modsCollection xmlns=\"http://www.loc.gov/mods/v3\">";
foreach ($records as $ppn => $record) {
	# XML-Starttag entfernen. Dieses Pattern-Matching beachtet nicht alle Moeglichkeiten die die XML-Definition hergibt, sollte aber in den meisten Faellen klappen
	$record = preg_replace("/^<\?xml[[:print:]]+?\?>/u", "", $record);
	$collection .= $record;	
}
$collection .= "\n</modsCollection>";

# MODS-Collection-String als XML einlesen, Transformierung anwenden und ausgeben
$xmldoc->loadXML($collection);
$result = $xslproc->transformToXML($xmldoc);
print $result;

# Da diese Seite gecacht werden kann, ist es sinnvoll, das Erstellungsdatum anzugeben
print "<p>Zuletzt aktualisiert: " . date("d.m.Y, H:i:s") . "</p>";

?>

</body>
</html>
