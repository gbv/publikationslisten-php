<?php
/**
 * Anfrage an PERLIS, Metadaten im ISBD-Format und Darstellung in HTML
 *
 * Darstellung von Listen aus Perlis. Derzeit ist PERLIS so konfiguriert, dass jeder Benutzer alle Sammlungen ("collections") einsehen kann. Ueber Schreibrechte verfuegt dagegen nur der jeweilige Besitzer. Im Skript wird ein Beispielnutzer verwendet, der in diesem Fall selbst keine Schreibrechte fuer die ausgewaehlte Sammlung besitzt.
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

### Konfiguration: Logdatei und Loglevel
$publistconf["logfile"] = "../publist.log";
$publistconf["loglevel"] = 2;

### Konfiguration: Cache-Datei und Caching-Interval in Sekunden
$start = strrpos($_SERVER['PHP_SELF'],'/') + 1;
$length = strrpos($_SERVER['PHP_SELF'],'.') - $start;
$name = substr ( $_SERVER['PHP_SELF'] , $start , $length);
$publistconf["cachefile"] = $name.".cache";
$publistconf["interval"] = 60;

### Konfiguration: Ausgangsformat, in dem Datensätze über unAPI geholt werden sollen. Eine Liste von Formaten gibt es unter der Basis-URL des unAPI-Servers: http://unapi.gbv.de/
$publistconf["unapiformat"] = "isbd";

### PHP-Bibliothek einbinden
require '../publikationsliste.php';

### PPNs bestimmen, hier aus PERLIS
$username  = "pdemo80"; # Beispielnutzer
$password = "zehy"; # Beispielpasswort
$dbsid    = "2.101";
$cid = "50"; # Identifier der Collection
$ppns = get_ppns_from_collectionws($username,$password,$dbsid,$cid);

### Metadaten holen.
$records = get_records_via_unapi("http://unapi.gbv.de/", $ppns, "", "perlis:ppn:");

### Ausgabe
?>

<html>
<head>
	<title>VZG - Beispiel Publikationslisten</title>
	<link rel="stylesheet" href="http://ws.gbv.de/daia//daia.css" type="text/css" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body>
<h1>Darstellung der Perlis-Liste Nr. <?php echo $cid; ?></h1>

<p>Diese Liste wird automatisch erstellt, aus der <a href="http://perlis.gbv.de:8080/DB=2.101/">PERLIS-Datenbank</a>. Die <a href="http://perlis.gbv.de:8080/DB=2.101/?CCID=<?php echo $cid; ?>">Liste Nr. <?php echo $cid; ?></a> enthält derzeit <a href ="http://perlis.gbv.de:8080/DB=2.101/CLK?IKT=8182&TRM=<?php echo $cid; ?>"><?php echo count($records); ?> Titel</a>:</p>

<?php

print "<ul >";
foreach ($records as $ppn => $record) {
	$url = "http://perlis.gbv.de:8080/DB=2.101/PPNSET?PPN=".$ppn;
	print "<li>[<a href='$url'>PERLIS</a>] ";
	print htmlspecialchars($record);
	print "</li>";
}
print "</ul>";

# Da diese Seite gecacht werden kann, ist es sinnvoll, das Erstellungsdatum anzugeben
print "<p>Zuletzt aktualisiert: " . date("d.m.Y, H:i:s") . "</p>";

?>

</body>
</html>
