<?php
/** 
 * Anfrage an GVK-PSI, Metadaten im RIS-Format, anschließende Sortierung nach Autor, Jahr und Titel und Darstellung in HTML
 * 
 * Beispiel fuer die Verwendung der PHP-Bibliothek "publikationsliste". Siehe auch die Dokumentation unter http://ws.gbv.de/publikationsliste.
 *
 * @author Jakob Voss <jakob.voss@gbv.de>
 * @author Christian Knoop <christian.knoop@gmx.net>
 * @link http://www.gbv.de/wikis/cls/Publikationslisten
 * @package publikationsliste
 * @date 2008-09-02
 **/

### Konfiguration: Ausgangsformat, in dem Datensätze über unAPI geholt werden sollen. Eine Liste von Formaten gibt es unter der Basis-URL des unAPI-Servers: http://unapi.gbv.de/
$publistconf["unapiformat"] = "ris";

### PHP-Bibliothek einbinden
require '../publikationsliste.php';

### PPNs suchen und Kataloglinks holen. Treffermenge wurde auf 20 Titel begrenzt.
$psibase = "http://gso.gbv.de/DB=2.1/";
$person = "du gay, paul";
$ppns = get_ppns_from_psi($psibase, "1004", $person, 20);
$psilink = create_psi_link($psibase, "1004", $person);

### Metadaten holen. In diesem Fall wird Format nicht direkt angegeben, weil schon oben gesetzt
$records = get_records_via_unapi("http://unapi.gbv.de/", $ppns, "", "gvk:ppn:");

?>

<html>
<head>
	<title>VZG - Beispiel Publikationslisten</title>
	<link rel="stylesheet" href="http://ws.gbv.de/daia//daia.css" type="text/css" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body>
<h1>Suche nach der Person Paul Du Gay im GVK</h1>

<p>Diese Titelliste wird automatisch aus <a href="<?php echo $psilink; ?>">einer Suchanfrage</a> an den <a href="http://gso.gbv.de/DB=2.1/">GVK</a> erstellt. Die Treffermenge wurde anschließend nach Autor, Jahr und Titel sortiert.<br />&nbsp;</p>

<?php

# Zerlegen des RIS-Strings in ein Array
$risArray = array();
foreach ($records AS $ppn => $record) {
	$lines = explode ("\n",$record);
	$counter = 0;
	foreach ($lines AS $line) {
		if ( preg_match("/(..)\s+-\s+(.*)/", $line, $match) ) { # preg_match("/(..)[[:space:]]+-[[:space:]]+(.*)/", $line) ) {
			$key = $match[1] . '_' . sprintf( '%03s', $counter);
			$risArray[$ppn][$key] = $match[2];
			$counter++;
		}
	}
}

# Manche Informationen können in mehreren Feldern stehen, zudem soll über mehrere Felder (Autor, Jahr, Titel) sortiert werden. Für die Sortierung werden die betreffenden Felder zusammengeklebt.
# In dieser Form erwartet das Skript, dass z.B. die Angaben zum Verfasser von der unAPI-Schnittstelle in der "richtigen" Reihenfolge geliefert werden, also A1 vor A2 vor A3. Will man Fehler hier ausschließen, kann man die Einträge in $risarray zuvor mit uksort() gemäß den eigenen Wünschen sortieren
$authorkeys = array ( 'A1', 'AU', 'A2', 'ED', 'A3' );
$yearkeys = array ( 'PY', 'Y1', 'Y2' );
$titlekeys = array ( 'T1', 'TI', 'CT', 'BT', 'T2' );
foreach ($risArray AS $ppn => $record) {
	foreach ($record AS $key => $value) {
		$key = $key[0].$key[1];
		if ( in_array($key,$authorkeys) ) { 
			# Mehrere Autoren für die Ausgabe mit einem Semikolon aneinanderfügen
			if ( isset($risArray[$ppn][sortauthor]) ){ $risArray[$ppn][sortauthor] .= '; '.$value; }
			else { $risArray[$ppn][sortauthor] = $value; }
		}
		elseif ( in_array($key,$yearkeys) ) { $risArray[$ppn][sortyear] .= $value; }
		elseif ( in_array($key,$titlekeys) ) { $risArray[$ppn][sorttitle] .= $value; }
	}
	$risArray[$ppn][sortstring] = $risArray[$ppn][sortauthor].$risArray[$ppn][sortyear].$risArray[$ppn][sorttitle];
}

# Sortierung definieren: Alphabetisch nach Autor, chronologisch absteigend (!) nach Jahr, alphabetisch nach Titel. Feinere Sortierung wird nur jeweils dann angewendet, wenn vorangehende keinen Unterschied liefert.
function sortArray ($a, $b) { 
	$return = strcmp($a[sortauthor],$b[sortauthor]);
	if ($return == 0) {
		$return = strcmp($a[sortyear],$b[sortyear]);
		$return = -$return;
		if ($return == 0) {
			$return = strcmp($a[sorttitle],$b[sorttitle]);
			return $return;
		}
		else { return $return; }
	} 
	else { return $return; }
}

# Sortierung auf Array anwenden
uasort($risArray, 'sortArray');

# Treffer ausgeben
foreach ($risArray AS $ppn => $record) {
    $link = "http://gso.gbv.de/DB=2.1/PPNSET?PPN=".$ppn;
    print "[<a href='$link'>GBV</a>] ";
    print $record[sortauthor] . ' ('. $record[sortyear] . ') : ' . $record[sorttitle];
    print "<hr/>";
}

?>

<br />&nbsp;
</body>
</html>
