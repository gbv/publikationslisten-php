<?php
/** 
 * Anfrage an GVK-PSI, Sortierung nach Autor durch GVK-PSI, Metadaten im ISBD-Format und Darstellung in HTML
 * 
 * Beispiel fuer die Verwendung der PHP-Bibliothek "publikationsliste". Siehe auch die Dokumentation unter http://ws.gbv.de/publikationsliste.
 *
 * @author Jakob Voss <jakob.voss@gbv.de>
 * @author Christian Knoop <christian.knoop@gmx.net>
 * @link http://www.gbv.de/wikis/cls/Publikationslisten
 * @package publikationsliste
 * @date 2008-09-02
 **/

### Konfiguration: Debugging-Modus ausschalten (Standard: 0, d.h. aus). Probieren Sie den Debugging-Modus aus, indem Sie den Wert auf 1 setzen!
$publistconf["debug"] = 0;

### Konfiguration: Ausgangsformat, in dem Datensätze über unAPI geholt werden sollen. Eine Liste von Formaten gibt es unter der Basis-URL des unAPI-Servers: http://unapi.gbv.de/
$publistconf["unapiformat"] = "isbd";

### Konfiguration: Logdatei und Loglevel
$publistconf["logfile"] = "../publist.log";
$publistconf["loglevel"] = 2;

### Konfiguration: Cache-Datei und Interval in Sekunden
$start = strrpos($_SERVER['PHP_SELF'],'/') + 1;
$length = strrpos($_SERVER['PHP_SELF'],'.') - $start;
$name = substr ( $_SERVER['PHP_SELF'] , $start , $length);
$publistconf["cachefile"] = $name.".cache";
$publistconf["interval"] = 60;

### PHP-Bibliothek einbinden
require '../publikationsliste.php';

### PPNs suchen und Kataloglinks holen. Treffermenge wurde auf 20 Titel begrenzt. Treffer werden nach Autor sortiert.
$psibase = "http://gso.gbv.de/DB=2.1/";
$person = "Grass, Günter";
$ppns = get_ppns_from_psi($psibase, "1004", $person, 20, "LST_a");
$psilink = create_psi_link($psibase, "1004", $person, "LST_a");

### Metadaten holen. In diesem Fall wird Format nicht direkt angegeben, weil schon oben gesetzt
$records = get_records_via_unapi("http://unapi.gbv.de/", $ppns, "", "gvk:ppn:");

### Ausgabe
?>

<html>
<head>
	<title>VZG - Beispiel Publikationslisten</title>
	<link rel="stylesheet" href="http://ws.gbv.de/daia//daia.css" type="text/css" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body>
<h1>Suche nach der Person Günter Grass im GVK</h1>

<p>Diese Titelliste wird automatisch aus <a href="<?php echo $psilink; ?>">einer Suchanfrage</a> an den <a href="http://gso.gbv.de/DB=2.1/">GVK</a> erstellt. Die Treffermenge wurde auf 20 Titel begrenzt und durch das PSI-System nach Autor sortiert.<br />&nbsp;</p>

<?php

foreach ($records as $ppn => $record) {
    $link = "http://gso.gbv.de/DB=2.1/PPNSET?PPN=".$ppn;
    print "[<a href='$link'>GBV</a>] ";
    print htmlspecialchars($record);
    print "<hr/>";
}

# Da diese Seite gecacht werden kann, ist es sinnvoll, das Erstellungsdatum anzugeben
print "<p>Zuletzt aktualisiert: " . date("d.m.Y, H:i:s") . "</p>";

?>

</body>
</html>
