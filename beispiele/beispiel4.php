<?php
/**
 * Anfrage an Lokalsystem-PSI, Metadaten im ISBD-Format und Darstellung in HTML
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

### PPNs suchen und Kataloglinks holen
$psibase = "http://opac.sub.uni-goettingen.de/DB=1.20/";
$ppns = get_ppns_from_psi($psibase, "1016", "gti gretil?", ""); # Diese Abfrage liefert alle Titel der Datenbank, chronologisch sortiert, neueste Einträge oben. Vgl. http://www.sub.uni-goettingen.de/ebene_1/fiindolo/gr_elib.htm
$psilink = create_psi_link($psibase, "1016", "gti gretil?");

### Metadaten holen. In diesem Fall wird Format direkt angegeben
$records = get_records_via_unapi("http://unapi.gbv.de/", $ppns, "isbd", "gvk:ppn:");

# Funktion die, in dieser Verwendung (siehe unten) den ISBD-String nach 100 Zeichen abschneidet
function utf8_substr($str,$start) {

	# Zeilenumbrueche etc. des ISBD-Formates in Leerzeichen umwandeln
	$str = preg_replace("/\\s+/", " ", $str);
	# UTF-8 String wird in ein numerisches Array umwandeln mit einem Zeichen pro Element
	preg_match_all("/./u", $str, $ar);

	if(func_num_args() >= 3) {
		$end = func_get_arg(2);
		return join("",array_slice($ar[0],$start,$end));
	}
	else {
		return join("",array_slice($ar[0],$start));
	}
}

### Ausgabe
?>

<html>
<head>
	<title>VZG - Beispiel Publikationslisten</title>
	<link rel="stylesheet" href="http://ws.gbv.de/daia//daia.css" type="text/css" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body>
<h1>GRETIL - Göttingen Register of Electronic Texts in Indian Languages</h1>

<p>Der <a href="<?php echo $psibase; ?>">GRETIL-Katalog</a> enthält derzeit <a href="<?php echo $psilink; ?>"><?php echo count($records); ?> Titel</a>:<br />&nbsp;</p>

<?php
$num = 1;
foreach ($records as $ppn => $record) {
    $link = "[<a href='${psibase}PPNSET?PPN=$ppn'>$num</a>]";
    if (strlen($record) > 100) $record = utf8_substr($record,0,100) . "...";
    print "$link " . htmlspecialchars($record);
    print "<hr/>";
    $num++;
}

# Da diese Seite gecacht werden kann, ist es sinnvoll, das Erstellungsdatum anzugeben
print "<p>Zuletzt aktualisiert: " . date("d.m.Y, H:i:s") . "</p>";

?>

</body>
</html>
