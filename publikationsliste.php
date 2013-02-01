<?php

/**
 * PHP-Bibliothek zum Erstellen von Publikationslisten
 * 
 * Publikationslisten werden in zwei Schritten erstellt: Zunaechst wird ueber
 * eine Abfrage an den Katalog (an PSI) eine Liste von PPNs zusammengestellt.
 * Anschliessend werden die dazugehoerigen Metadaten ueber die unAPI-Schnittstelle
 * des GBV hinzugeholt. Anwendungsbeispiele finden Sie im Unterordner
 * "/beispiele". Beachten Sie auch die Dokumentation unter
 * http://www.gbv.de/wikis/cls/Publikationslisten. Dieses Skript soll als Vorlage
 * fuer eigene Anwendungen dienen und wartet darauf, eingesetzt und erweitert zu
 * werden. 
 * 
 * Die Bibliothek ist prozedural programmiert. Nach Einbindung stehen die Funktionen
 * - get_ppns_from_psi() fuer die Suche in einem PSI-System, 
 * - get_records_via_unapi() fuer die Abfrage der Metadaten
 * fuer die externe Programmierung zur Verfuegung. Alle weiteren Einstellungen 
 * erfolgen ueber das Konfigurationsarray $publistconf. Deren Standardeinstellungen 
 * werden bei Einbindung der Bibliothek gesetzt, sofern diese nicht vorher extern vom 
 * Programmierer definiert wurden. Alle Programmmeldungen und Fehler koennen in einer 
 * externen Datei mitgeloggt werden.
 *
 * Caching: Damit der Katalog nicht bei jeder Anzeige der Publikationsliste neu abgefragt 
 * werden muss, kann die gesamte Ausgabe - inklusive der extern hinzugefuegten Programmierung
 * - gecacht werden. Da die gesamte Ausgabe gecacht wird, vertraegt sich dieses Verfahren 
 * nicht mit automatisch generierten Suchanfragen. Das Caching ist gedacht um den Katalog 
 * von wiederkehrenden Anfrgen zu entlasten, die mit hoher Wahrscheinlichkeit zum gleichen 
 * Ergebnis fuehren. Wenn Sie Ihre Anfrage dynamisch veraendern und dennoch cachen moechten, 
 * muessen Sie Ihre Ausgabe in verschiedene Cache-Dateien speichern. Dies erreichen Sie, 
 * indem Sie dem Skript einen dynamisch generierten Dateinamen als Cache-Datei uebergeben. 
 * Damit der Cache-Mechanismus funktioniert, darf das eigene Skript keine der Output Control
 * Funktionen (flush(), ob_*()) verwenden. Zu Beachten ist auch, dass im Debugging-Modus die
 * Logging-Meldungen ebenfalls gecacht werden, was unter Umstaenden zu Verwirrung fuehren 
 * kann ;-)  Nach Ablauf des Caching-Intervals wird der Cache bei erneutem Aufruf des Skriptes 
 * automatisch neu befuellt.
 * 
 * Die Konfigurationsparameter im Ueberblick:
 * - $publistconf[unapiformat] : Metadatenformat, das von der unAPI-Schnittstelle zurueckgeliefert werden soll. Name muss den unter http://unapi.gbv.de verfuegbaren Formaten entsprechen. Angabe erforderlich, falls bei Aufruf der Funktion get_records_via_unapi() kein Format uebergeben wird.
 * - $publistconf[recordlimit] : Anzahl maximal zu liefernder Treffer bei PSI-Abfrage. Voreingestellt ist 100.
 * - $publistconf[debug] : Schaltet Debugging-Modus ein ( entpsricht 1) oder aus (entspricht 0). Voreingestellt ist 0.
 * - $publistconf[logfile] : Veranlasst Logging in diese Datei. Bleibt leer, falls kein Logging erwuenscht.
 * - $publistconf[loglevel] : Legt fest wie detailliert geloggt werden soll. Vgl. die Loglevel der Funktion log_msg. Standard ist 1.
 * - $publistconf[cachefile] : Veranlasst Caching des Ergebnisseite in diese Datei. Bleibt leer, falls kein Caching erwuenscht.
 * - $publistconf[interval] : Legt fest, wann der Cache geleert werden soll. Angabe in Sekunden. Voreinstellung ist 60*60*24 = taegliche Aktualisierung.
 * - $publistconf[purge] : Erzwingt Ignorieren des Caches - auch wenn Cachefile angegeben und Interval noch nicht abgelaufen. Variable kann extern gesetzt oder per GET oder POST uebergeben werden. Voreingestellt wird die Variable $_REQUEST['purge'] ausgelesen.
 *
 * Verwendung dieser PHP-Bibliothek
 * 1. Schritt: Konfigurationsparameter setzen, z.B. in der Form "$publistcon[interval] = 60;"
 * 2. Schritt: PHP-Bibliothek einbinden ueber den Befehl "require 'Publikationsliste.php';"
 * 3. Schritt: PPNs ermitteln, z.B. 20 Titel von Guenter Grass aus dem GVK in der Form "$ppns = get_ppns_from_psi("http://gso.gbv.de/DB=2.1/", "1004", "grass, günter", 20);"
 * 4. Schritt: Datensaetze holen, z.B. durch "$records = get_records_via_unapi("http://unapi.gbv.de/", $ppns, "isbd", "gvk:ppn:");"
 * 5. Schritt: Titel ausgeben, z.B. durch eine Schleife "foreach ($records as $record) { print htmlspecialchars($record); }"
 * Viel Erfolg!
 * 
 * @author Jakob Voss <jakob.voss@gbv.de>
 * @author Christian Knoop <christian.knoop@gmx.net>
 * @link http://ws.gbv.de/publikationsliste
 * @link http://www.gbv.de/wikis/cls/Publikationslisten
 * @link http://unapi.gbv.de/about
 * @package publikationsliste
 * @date 2008-09-02
 */

# Standardkonfigurationseinstellungen
if (!isset($publistconf))
    $publistconf = array();

if (!array_key_exists('recordlimit',$publistconf))
    $publistconf["recordlimit"] = 100;

if (!array_key_exists('debug',$publistconf))
    $publistconf["debug"] = 0;

if (!array_key_exists('loglevel',$publistconf))
    $publistconf["loglevel"] = 1;

if (!array_key_exists('purge',$publistconf))
    $publistconf["purge"] = $_REQUEST['purge'];

if (!array_key_exists('interval',$publistconf))
    $publistconf["interval"] = 24*60*60; # daily


/**
 * Schreibt Nachrichten in die Logdatei oder auf den Bildschirm
 *
 * Schreibt Nachrichten in eine Logging Datei sofern diese definiert wurde und der Loglevel ueber dem in $publistconf['loglevel'] definierten Wert liegt. Falls $publistconf['debug'] werden zusaetzlich alle Nachrichten als HTML auf den Bildschirm ausgeben. Negative Loglevel geben Fehler an, positive Loglevel einfache Nachrichten. Je hoeher der Absolutwert des Loglevel, desto weniger wichtig ist die Nachricht. Hier einige Beispiele:
 * - log_msg("Ganz schwerer Fehler", -1);
 * - log_msg("Nicht so schwerwiegender Fehler", -3);
 * - log_msg("Ganz wichtiger Schritt", 1);
 * - log_msg("Unwichtiger Hinweis", 3);
 *
 * @param string Nachrichtentext
 * @param int Loglevel
 */
function log_msg($message, $level) {
	
    global $publistconf;

    $str  = "[" . date("Y-m-d\Th:i:s", mktime()) . "] ";
    $str .= $level < 0 ? "ERROR($level) " : "INFO($level) ";
    $str .= $message;

    # Im Debugging-Modus alle Nachrichten als HTML ausgeben
    if ($publistconf["debug"]) print '<span style="color:red">' . htmlspecialchars($str) . '<br /></span>';

    $maxlevel = defined($publistconf["loglevel"]) ? $publistconf["loglevel"] : 99;
	
	# Es wird nicht geloggt, falls keine Logdatei angegeben oder Level der Nachricht von zu geringer Prioritaet
    if (!$publistconf["logfile"] || abs($level) > $maxlevel) return;

    fwrite($publistconf["logfile"], $str . "\n");
}

/**
 * Liefert einen Suchlink auf ein PSI-System
 *
 * Dieser Link fuehrt zum gleichen Ergebnis wie die mit der Funktion get_ppns_from_psi ermittelten Treffer.
 *
 * @param string Basis-URL, z.B. "http://gso.gbv.de/DB=2.1/"
 * @param int Suchschluessel. Wo soll gesucht werden, z.B. in "1004" fuer Suche nach Personenname
 * @param string Suchanfrage, z.B. "von Foerster, Heinz"
 * @param string Sortierung, z.B. "LST_a" fuer die Sortierung nach Autor im GVK
 */
function create_psi_link($psibase, $ikt, $search, $sort="") {
    
	$link = $psibase . "CMD?ACT=SRCHA&IKT=" . urlencode($ikt) . "&TRM=" . urlencode($search);
	if ($sort) $link .= '&SRT=' . urlencode($sort);
	
	return $link;
}


/**
 * Schickt Suche an PSI-System und liefert Array mit PPNs zurueck
 * 
 * Schickt Suchanfrage an ein PSI-System. Benutzt dabei "XML-Schnittstelle" und
 * liefert eine Liste von PPNs in Form eines Array zurueck. Die Treffermenge kann
 * begrenzt werden. Durch einen Parameter, der an das liefernde PSI-System
 * uebergeben wird, kann das Ergebnis auch sortiert werden. Die
 * Sortiermoeglichkeiten haengen allerdings vom Liefersystem ab. PSI-Systeme
 * erlauben i.d.R. eine Sortierung nach 
 *
 * - YOP = Erscheinungsjahr
 * - RLV = Relevanz
 * - LST_a = Autor und 
 * - LST_t = Titel. 
 * Voreingestellt wird kein Sortierparameter uebergeben, das Skript liefert dann die Treffer in der Standardsortierung des gewaehlten Kataloges zurueck.
 *
 * Da die Abfrage ueber die so genannte XML-Schnittstelle erfolgt ( die im Gegensatz z.B. zu SRU nicht standardisiert ist)
 * muss sich die Funktion durch die Kurztitellisten hangeln, die jeweils nur 10 Datensaetze auf einmal uebermitteln.
 * Mit einer sauber standardisierten und implementierten Schnittstelle koennte man mehr machen ... ;)
 *
 * @param string Basis-URL, z.B. "http://gso.gbv.de/DB=2.1/"
 * @param int Suchschluessel. Wo soll gesucht werden, z.B. in "1004" fuer Suche nach Personenname
 * @param string Suchanfrage, z.B. "von Foerster, Heinz"
 * @param int Maximale Trefferanzahl (maximal 1000, Standard ist 100)
  * @param string Sortierung, z.B. "LST_a" fuer die Sortierung nach Autor im GVK
 */
function get_ppns_from_psi($psibase, $ikt, $search, $limit="", $sort="") {
    global $publistconf;

    $ppns = array();
	
	if (!$limit || $limit <= 0) $limit = $publistconf['recordlimit'];
	if ( $limit > 1000 ) { $limit = 1000; }

    log_msg("get_ppns(\"$psibase\",\"$ikt\",\"$search\")",2);

    $url = $psibase
         . "CHARSET=UTF-8/PRS=PP%7F/XML=1.0/CMD?ACT=SRCHA&IKT="
         . urlencode($ikt) . "&TRM=" . urlencode($search);
	if ($sort) $url .= '&SRT=' . urlencode($sort);
	
	$nr = 0; # Laufende Nummer in der Ergebnisliste
	
	# Daten paeckchenweise von XML-Schnittstelle holen ...
    do {
        log_msg("Fetching $url",3);

        $lines = file($url);
        if (!$lines) {
            log_msg("Failed to fetch $url", -1);
            return $ppns;
        }

        $new = 0;
        $sessionvar = array();

        foreach($lines as $line) {
			if ($nr < $limit) { # Datensatz lesen bis Limit erreicht
	            if (preg_match('/\s*<SHORTTITLE[^>]+nr="(\d+)"[^>]+PPN="([0-9xX]+)"/', $line, $match)) {
	                if ($match[1] > $nr) $nr = $match[1]; # Laufende Nummer neu laden
	                $ppns[$match[2]] = $match[2];
	                $new++;
	            }
				else if (preg_match('/<SET[^>]+hits="(\d+)"/', $line, $match)) {
	                $hits = $match[1]; # Maximale Anzahl von Treffern
	            } 
				else if (preg_match('/<SESSIONVAR[^>]+name="([^<"]+)">([^<]+)/', $line, $match)) {
	                $sessionvar[ $match[1] ] = $match[2];
	            }
			}
        }
        if ($new) {
            log_msg("Got $new PPNs up to $nr of $hits",3);
        } else {
            log_msg("Empty result set (no PPNs)", -2);
        }

        $url = $psibase . "CHARSET=UTF-8/PRS=PP%7F/XML=1.0/NXT?FRST=" . ($nr+1);
        foreach ($sessionvar as $key => $value) {
            $url .= "&" . urlencode($key) . "=" . urlencode($value);
        }
	# ... bis Ende der Liste oder Limit erreicht
    } while ($nr < $hits && $nr < $limit);

    log_msg("Got " . count($ppns) . " PPNs in total",2);

    return $ppns;
}



/**
 * Holt Metadaten ueber unAPI-Schnittstelle
 * 
 * Liefert Metadaten im geforderten Format, arbeitet ein Array mit Identifiern ab. Sofern kein Metadatenformat bei Aufruf genannt wird, wird Einstellung aus $publistconf[unapiformat] uebernommen. Liefert ein Array mit Paaren id => Datensatz zurueck. Das Prefix beim Datensatz-Identifier ist nach unAPI-Spezifikation grundsaetzlich optional, bei Verwendung der GBV-unAPI-Schnittstelle aber Pflicht, da hier ueber eine Schnittstelle mehrere Datenquellen erreichbar sind.
 *
 * @param string Basis-URL des unAPI-Servers, z.B. "http://unapi.gbv.de/"
 * @param array Array mit IDs, z.B. "56677741X"
 * @param string Gewuenschtes Metadtenformat. Standard ist $publistconf['unapiformat']
 * @param string ID-Prefix zur Kennzeichnung der Datenquelle, z.B. "gvk:ppn:"
 */
function get_records_via_unapi($server, $ids, $format="", $prefix="") {
    global $publistconf;
    if (!$format) $format = $publistconf['unapiformat'];

    if (!($server && $format && is_array($ids))) {
        log_msg("get_records_via_unapi called with wrong arguments ($server, $format, $ids)",-2);
        return array();
    }

    log_msg("get_records_via_unapi(\"$server\"," . count($ids) ." ids,\"$format\",\"$prefix\")",3);
    $records = array();

    foreach ($ids as $id) {
        $unapiid = $id;
        if ($prefix) $unapiid = $prefix . $id;
        $url = $server . "?id=" . urlencode($unapiid) . "&format=" . urlencode($format);
        log_msg("get via unAPI: $unapiid in $format",3);

        if ( $record = @file_get_contents($url) ) {
            $records[$id] = $record;
        } else {
            log_msg("Got no data via $url" . ($php_errormsg ? $php_errormsg : ""), -2);
        }
    }

    return $records;
}


/**
 * Cache lesen, falls aktueller Cache vorhanden
 * 
 * Die Funktion zum Fuellen des Caches wird vom Server automatisch bei Skript-Abschluss aufgerufen (Fkt. register_shutdown_function), sofern eine Cache-Datei genannt wird. Daher bietet es sich an, jede Listendarstellung mit Zeitstempel zu versehen.
 *
 * @param string Datei, in die gecacht werden soll
 */
function cache_and_flush_buffer( $cachefile ) {
    if ($cachefile) {
        log_msg("Saving output in cachefile: $cachefile",2);

        # TODO: if this fails, there is no real error handling
        $fp = @fopen( $cachefile, "w" );
        if ($fp) {
            fputs($fp, ob_get_contents());
            fclose($fp);
        } else {
            log_msg("Could not open cachefile: $cachefile", -1);
        }
    }

    log_msg("Sending output buffer",3);

    # TODO: ggf. header schicken (ob_get_length() etc.)
    ob_flush();
}

/**
 * 
 */

# Überprüfe vorhandensein der Logdatei
if ($publistconf["logfile"]) {
    $filename = $publistconf["logfile"];
    $publistconf["logfile"] = @fopen( $filename, "a" );
    if (!$publistconf["logfile"]) {
        $publistconf["debug"] = 1; # wird immer als HTML ausgegeben
        log_msg("Failed to open logfile: $filename",-1);
        exit;
    }
}


# Falls Caching eingeschaltet ist, versuche Cache auszugeben, sonst Skript durchlaufen lassen
if ($publistconf["cachefile"]) {

    $filename = $publistconf["cachefile"];
	$purge = $publistconf["purge"];
	$interval = $publistconf["interval"];
	
	# Egal ob purge gesetzt oder nicht: Datei muss fuer realpath() existieren
	$fp = @fopen($filename, "r");
	if ( !$fp ) {
		log_msg("Failed to open cachefile: $filename",-1);
		if ( !($fp = @fopen($filename, "w")) ) { 
			log_msg("Failed to create cachefile: $filename",-1); 
		}
		else { 
			log_msg("Wrote new cachefile: $filename", 2); 
			fclose($fp);
		}
	}
	else { 
		fclose($fp);
		if (!$purge) {
			if ( filesize($filename) > 0 ) {
				
				# Testen ob Cache aktuelle genug
				$mtime = filemtime($filename);
				$now = time();
				if ( ($now - $mtime) > $interval ) {
					log_msg("Cache expired",3);
					$purge = 1;
				} else {
					log_msg("Cache (" . date("c",$mtime). ") is new enough ($interval)",3);
				}
				
				# Cache ausgeben und Programm beenden
				if (!$purge) {
					# header("Content-Type: text/html");
					# TODO: weitere header (lastmod, wann cache expired etc.)
					header("Content-Length: " . filesize($filename));
					readfile($filename);
					log_msg("Send cachefile $filename",2);
					exit;
				}
			}
		}
	}

    # zlib compression and output buffering does not work together that well
    # TODO: this must be sent before the first logging message on debug=1
    # ini_set('zlib.output_compression', 0);

    # Buffering starten, zum späteren Cachen der Ausgabe
    ob_implicit_flush(false);
    ob_start();

    register_shutdown_function("cache_and_flush_buffer", realpath($filename) );
}


/**
 * Geplante Verbesserungen:
 *
 * - Script-Kennung und weitere Parameter mitloggen
 * - Support für SRU (SRU-Client mit yaz)
 * - Support für Z39.50 (?)
 * - Caching von verschiedenen Content-Types (header senden je nach Dateiendung, mit Hash Endung => mime-type)
 * - Accesslog (wie seealso)
 * - JabRef-Exportformate
 * - URL-Parameter auch als Kommandozeilen-Parameter ermöglichen (argv parsen und key=value-Paare nehmen)
 * - RSS als Ausgabeformat (?) => welches Skript erzeugt bisher RSS?
 * - ...
 *
 * Testen mit headers_sent() wg. logging ?
 * Systemvoraussetzungen testen:
# Benötigt mindestens PHP X.Y.Z (TODO: welche Version?)
	if (!version_compare(PHP_VERSION, '5.2.0', '>=')) {
		$publistconf["debug"] = 1;
		log_msg("Mindestens PHP 5.2.0 benötigt - die auf diesem Server läuft aber " . PHP_VERSION, -1);
		exit;
	}
*/

?>
