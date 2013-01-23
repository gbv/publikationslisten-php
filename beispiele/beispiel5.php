<?php
/** 
 * Anfrage an Lokalsystem-PSI, Metadaten im ISBD-Format und Darstellung in HTML mit DAIA-Verfuegbarkeitspruefung
 * 
 * Verwendet die experimentelle Document Availability Information API (DAIA). Fuer die Kommunikation mit DAIA wird der SeeAlso-Linkservice verwendet.
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
$psibase = "http://opac.sub.uni-goettingen.de/DB=1/";
$person = "von Foerster, Heinz";
$ppns = get_ppns_from_psi($psibase, "1004", $person);
$psilink = create_psi_link($psibase, "1004", $person);

### Metadaten holen. In diesem Fall wird Format direkt angegeben
$records = get_records_via_unapi("http://unapi.gbv.de/", $ppns, "isbd", "gvk:ppn:");

### Ausgabe
?>

<html>
<head>
	<title>VZG - Beispiel Publikationslisten</title>
	<link rel="stylesheet" href="http://ws.gbv.de/daia//daia.css" type="text/css" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<script type="text/javascript" src="http://ws.gbv.de/seealso/client/seealso.js"></script>
	<script type="text/javascript" src="http://ws.gbv.de/daia//daia2seealso.js"></script>
	<script type="text/javascript">
		var isil = "DE-7";
		var mySeeAlso = new SeeAlsoCollection();
		mySeeAlso.services =  {
		    'ppnsubavailable' : new DAIAService("http://ws.gbv.de/daia?format=json&isil=" + isil)
		};
		mySeeAlso.replaceTagsOnLoad();
	</script>
</head>
<body>

<h1>Suche nach der Person Heinz von Foerster im Katalog der SUB Göttingen</h1>

<p><i>Diese Publikationsliste wird automatisch aus <a href="<?php echo $psilink; ?>">einer Suchanfrage</a> erstellt. Die aktuelle Verfügbarkeit wird über die experimentelle DAIA-Schnittstelle zusätzlich eingebunden.</i></p>
<hr/>

<?php

foreach ($records as $ppn => $record) {
    $link = "[<a href='${psibase}PPNSET?PPN=$ppn'>SUB</a>]";

    $str = $record;
    if (strlen($str) > 100) $str = substr($str,0,100) . "...";

    print "$link " . htmlspecialchars($str);
    print "<span class='ppnsubavailable' title='$ppn' ></span>";
    print "<hr/>";
}


# Da diese Seite gecacht werden kann, ist es sinnvoll, das Erstellungsdatum anzugeben
print "<p>Zuletzt aktualisiert: " . date("d.m.Y, H:i:s") . "</p>";

?>

</body>
</html>
