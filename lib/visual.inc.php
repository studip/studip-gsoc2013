<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO


require_once 'config.inc.php';
require_once 'lib/classes/cssClassSwitcher.inc.php';
include_once 'vendor/idna_convert/idna_convert.class.php';
include_once 'lib/classes/searchtypes/SQLSearch.class.php';
include_once 'lib/classes/searchtypes/StandardSearch.class.php';
include_once 'lib/classes/searchtypes/PermissionSearch.class.php';
require_once 'lib/classes/LinkButton.class.php';
require_once 'lib/classes/Button.class.php';
require_once 'lib/classes/ResetButton.class.php';
require_once 'lib/wiki.inc.php';

/**
 * get_ampel_state is a helper function for get_ampel_write and get_ampel_read.
 * It checks if the new parameters lead to a "lower" trafficlight. If so, the new
 * level and the new text are set and returned.
 *
 * @param unknown_type $cur_ampel_state
 * @param unknown_type $new_level
 * @param unknown_type $new_text
 * @return
 */
function get_ampel_state ($cur_ampel_state, $new_level, $new_text)
{
    if ($cur_ampel_state["access"] < $new_level) {
        $cur_ampel_state["access"] = $new_level;
        $cur_ampel_state["text"] = $new_text;
    }
    return $cur_ampel_state;
}

/**
 * get_ampel_write, waehlt die geeignete Grafik in der Ampel Ansicht
 * (fuer Berechtigungen) aus. Benoetigt den Status in der Veranstaltung
 * und auf der Anmeldeliste und den read_level der Veranstaltung
 *
 * @param unknown_type $mein_status
 * @param unknown_type $admission_status
 * @param unknown_type $write_level
 * @param unknown_type $print
 * @param unknown_type $start
 * @param unknown_type $ende
 * @param unknown_type $temporaly
 */
function get_ampel_write ($mein_status, $admission_status, $write_level, $print="TRUE", $start = -1, $ende = -1, $temporaly = 0)
{
    global $perm;

    $ampel_state["access"] = 0;     // the current "lowest" access-level. If already yellow, it can't be green again, etc.
    $ampel_state["text"] = "";          // the text for the reason, why the "ampel" has the current color
    /*
     * 0 : green
     * 1 : yellow
     * 2 : red
     */

    if ($mein_status == "dozent" || $mein_status == "tutor" || $mein_status == "autor") { // in den Fällen darf ich auf jeden Fall schreiben
        $ampel_state = get_ampel_state($ampel_state,0,"");
        //echo $ampel_state["access"]."<br>";
        //echo $ampel_state["text"]."<br>";
    } else {
        if ($temporaly != 0) {
            $ampel_state = get_ampel_state($ampel_state,1,_("(Vorl. Eintragung)"));
        }

        if (($start != -1) && ($start > time())) {
            $ampel_state = get_ampel_state($ampel_state,1,_("(Starttermin)"));
        }

        if (($ende != -1) && ($ende < time())) {
            $ampel_state = get_ampel_state($ampel_state,2,_("(Beendet)"));
        }

        switch($write_level) {
            case 0 : //Schreiben darf jeder
                $ampel_state = get_ampel_state($ampel_state,0,"");
            break;
            case 1 : //Schreiben duerfen nur registrierte Stud.IP Teilnehmer
                if ($perm->have_perm("autor"))
                    $ampel_state = get_ampel_state($ampel_state,0,"");
                else
                    $ampel_state = get_ampel_state($ampel_state,2,_("(Registrierungsmail beachten!)"));
            break;
            case 2 : //Schreiben nur mit Passwort
                if ($perm->have_perm("autor"))
                    $ampel_state = get_ampel_state($ampel_state,1,_("(mit Passwort)"));
                else
                    $ampel_state = get_ampel_state($ampel_state,2,_("(Registrierungsmail beachten!)"));
            break;
            case 3 : //Schreiben nur nach Anmeldeverfaren
                if ($perm->have_perm("autor"))
                    if ($admission_status)
                        $ampel_state = get_ampel_state($ampel_state,1,_("(Anmelde-/Warteliste)"));
                    else
                        $ampel_state = get_ampel_state($ampel_state,1, _("(Anmeldeverfahren)"));
                else
                    $ampel_state = get_ampel_state($ampel_state,2, _("(Registrierungsmail beachten!)"));
            break;
        }
    }

    switch ($ampel_state["access"]) {
        case 0 :
            $color = 'icons/16/green/accept.png';
            break;
        case 1 :
            $color = 'icons/16/black/exclaim.png';
            break;
        case 2 :
            $color = 'icons/16/red/decline.png';
            break;
    }

    $ampel_status = "<img src=\"". Assets::image_path($color) . "\"> ". $ampel_state["text"];

    if ($print == TRUE) {
        echo $ampel_status;
    }
    return $ampel_status;
}

/**
 * get_ampel_read, waehlt die geeignete Grafik in der Ampel Ansicht
 * (fuer Berechtigungen) aus. Benoetigt den Status in der Veranstaltung
 * und auf der Anmeldeliste und den read_level der Veranstaltung
 *
 * @param unknown_type $mein_status
 * @param unknown_type $admission_status
 * @param unknown_type $read_level
 * @param unknown_type $print
 * @param unknown_type $start
 * @param unknown_type $ende
 * @param unknown_type $temporaly
 */
function get_ampel_read ($mein_status, $admission_status, $read_level, $print="TRUE", $start = -1, $ende = -1, $temporaly = 0)
{
    global $perm;

    $ampel_state["access"] = 0;     // the current "lowest" access-level. If already yellow, it can't be green again, etc.
    $ampel_state["text"] = "";          // the text for the reason, why the "ampel" has the current color
    /*
     * 0 : green
     * 1 : yellow
     * 2 : red
     */

    if ($mein_status) { // wenn ich im Seminar schon drin bin, darf ich auf jeden Fall lesen
        $ampel_state = get_ampel_state($ampel_state,0,"");
    } else {
            if ($temporaly != 0) {
                $ampel_state = get_ampel_state($ampel_state,1,_("(Vorl. Eintragung)"));
            }

            if (($start != -1) && ($start > time())) {
                $ampel_state = get_ampel_state($ampel_state,1,_("(Starttermin)"));
            }

            if (($ende != -1) && ($ende < time())) {
                $ampel_state = get_ampel_state($ampel_state,2,_("(Beendet)"));
            }

        switch($read_level){
            case 0 :    //Lesen darf jeder
                $ampel_state = get_ampel_state($ampel_state,0,"");
            break;
            case 1 :    //Lesen duerfen registrierte nur Stud.IP Teilnehmer
                if ($perm->have_perm("autor"))
                    $ampel_state = get_ampel_state($ampel_state,0,"");
                else
                    $ampel_state = get_ampel_state($ampel_state,2,_("(Registrierungsmail beachten!)"));
            break;
            case 2 :    //Lesen nur mit Passwort
                if ($perm->have_perm("autor"))
                    $ampel_state = get_ampel_state($ampel_state,1,_("(mit Passwort)"));
                else
                    $ampel_state = get_ampel_state($ampel_state,2,_("(Registrierungsmail beachten!)"));
            break;
            case 3 :    //Lesen nur nach Anmeldeverfaren
                if ($perm->have_perm("autor"))
                    if ($admission_status)
                        $ampel_state = get_ampel_state($ampel_state,1,_("(Anmelde-/Warteliste)"));
                    else
                        $ampel_state = get_ampel_state($ampel_state,1,_("(Anmeldeverfahren)"));
                else
                    $ampel_state = get_ampel_state($ampel_state,2,_("(Registrierungsmail beachten!)"));
            break;
        }
    }

    switch ($ampel_state["access"]) {
        case 0 :
            $color = 'icons/16/green/accept.png';
            break;
        case 1 :
            $color = 'icons/16/black/exclaim.png';
            break;
        case 2 :
            $color = 'icons/16/red/decline.png';
            break;
    }

    $ampel_status = "<img src=\"". Assets::image_path($color) . "\"> ". $ampel_state["text"];

    if ($print == TRUE) {
        echo $ampel_status;
    }
    return $ampel_status;
}

function htmlReady ($what, $trim = TRUE, $br = FALSE, $double_encode = FALSE) {
    if ($trim) {
        $what = trim(htmlspecialchars($what, ENT_QUOTES, 'cp1252', $double_encode));
    } else {
        $what = htmlspecialchars($what,ENT_QUOTES, 'cp1252', $double_encode);
    }

    if ($br) { // fix newlines
        $what = nl2br($what, false);
    }

    return $what;
}

function jsReady ($what = "", $target = "overlib") {
    switch ($target) {

    case "script-single" :
        return addcslashes($what, "\\'\n\r");
    break;

    case "script-double" :
        return addcslashes($what, "\\\"\n\r");
    break;

    case "inline-single" :
        return htmlReady(addcslashes($what, "\\'\n\r"));
    break;

    case "inline-double" :
        return htmlReady(addcslashes($what, "\\\"\n\r"));
    break;

    case "contact" :
        $what = htmlReady($what, true, false, true);
        $what = str_replace("\n","<br>",$what);
        $what = str_replace("\r","",$what);
        return $what;
    break;

    case "alert" :
        $what = addslashes(htmlReady($what));
        $what = str_replace("\r","",$what);
        $what = str_replace("\n","\\n",$what); // alert boxen stellen keine html tags dar
        return $what;
    break;

    case 'forum' :
        $what = str_replace("\r",'',formatReady($what));
        $what = '<p width="100%"class="printcontent">' . $what . '</p>';
        return addslashes(htmlReady($what));
        break;

    case "overlib" :
    default :
        $what = addslashes(htmlReady(htmlReady($what)));
        $what = str_replace("\n","<br>",$what);
        $what = str_replace("\r","",$what);
        return $what;
        break;
    }
}

/**
 * Funktion um Quotings zu encoden
 *
 * @param string $description der Text der gequotet werden soll, wird zurueckgegeben
 * @param string $author Name des urspruenglichen Autors
 * @return string
 */
function quotes_encode($description,$author)
{
    if (preg_match("/%%\[editiert von/",$description)) { // wurde schon mal editiert
        $postmp = strpos($description,"%%[editiert von");
        $description = substr_replace($description," ",$postmp);
    }
    $description = "[quote=".$author."]\n".$description."\n[/quote]";
    return $description;
}

/**
* universal and very usable functions to get all the special stud.ip formattings
*
*
* @access       public
* @param        string $what        what to format
* @param        boolean $trim       should the output trimmed?
* @param        boolean $extern TRUE if called from external pages ('externe Seiten')
* @param    boolean $wiki       if TRUE format for wiki
* @param    string  $show_comments  Comment mode (none, all, icon), used for Wiki comments
* @return       string
*/
function formatReady ($what, $trim = TRUE, $extern = FALSE, $wiki = FALSE, $show_comments="icon") {
    $markup = new StudipFormat();
    $what = preg_replace("/\r\n?/", "\n", $what);
    $what = htmlReady($what, $trim);

    $what = $markup->format($what);
    $what = symbol(smile($what, false));
    return str_replace("\n", '<br>', $what);
}

/**
 * simplified version of formatReady that handles only link formatting
 *
 * @param    string $what   what to format
 * @param    bool $nl2br    convert newlines to <br>
 */
function formatLinks($what, $nl2br = true)
{
    $link_markup_rule = StudipFormat::getStudipMarkup("links");
    $markup = new TextFormat();
    $markup->addMarkup(
        "links",
        $link_markup_rule['start'],
        $link_markup_rule['end'],
        $link_markup_rule['callback']
    );
    return $markup->format(htmlReady($what, true, $nl2br));
}

/**
* the special version of formatReady for Wiki-Webs
*
*
* @access       public
* @param        string $what        what to format
* @param        string $trim        should the output trimmed?
* @param        boolean $extern TRUE if called from external pages ('externe Seiten')
* @return       string
*/
function wikiReady ($what, $trim = TRUE) {
    $markup = new WikiFormat();
    $what = preg_replace("/\r\n?/", "\n", $what);
    $what = htmlReady($what, $trim);

    $what = $markup->format($what);
    $what = symbol(smile($what, false));
    return str_replace("\n", '<br>', $what);
}


/**
 * Transform the argument using the replace-before-save rules defined
 * by StudipTransformFormat.
 *
 * @param string $what the original string
 *
 * @return the result of applying the replace-before-save
 * transformations to the argument of the function
 */
function transformBeforeSave($what)
{
    $markup = new StudipTransformFormat();
    return $markup->format($what);
}

////////////////////////////////////////////////////////////////////////////////

/**
* decodes html entities to normal characters
*
* @access   public
* @param    string
* @return   string
*/
function decodeHTML ($string) {
    return html_entity_decode($string, ENT_QUOTES, 'cp1252');
}

/**
* formats a ~~~~ wiki signature with username and timestamp
* @param string
* @param unix timestamp
*/
function preg_call_format_signature($username, $timestamp) {
    $fullname = get_fullname_from_uname($username);
    $date = strftime('%x, %X', $timestamp);
    return '<span style="font-size: 75%">-- <a href="'.URLHelper::getLink('dispatch.php/profile', array('username' => $username)).'">'.htmlReady($fullname).'</a> '.htmlReady($date).'</span>';
}


/**
* removes all characters used by quick-format-syntax
*
* @access   public
* @param    string
* @return   string
*/
function kill_format ($text) {
    $text = preg_replace("'\n?\r\n?'", "\n", $text);
    // wir wandeln [code] einfach in [pre][nop] um und sind ein Problem los ... :-)
    $text = preg_replace_callback ( "|(\[/?code\])|isU", create_function('$a', 'return ($a[0] == "[code]")? "[pre][nop]":"[/nop][/pre]";'), $text);

    $pattern = array(
                    "'(^|\n)\!{1,4}(.+)$'m",      // Ueberschriften
                    "'(\n|\A)(-|=)+ (.+)$'m",     // Aufzaehlungslisten
                    "'(^|\s)%(?!%)(\S+%)+'e",     // SL-kursiv
                    "'(^|\s)\*(?!\*)(\S+\*)+'e",  // SL-fett
                    "'(^|\s)_(?!_)(\S+_)+'e",     // SL-unterstrichen
                    "'(^|\s)#(?!#)(\S+#)+'e",     // SL-diktengleich
                    "'(^|\s)\+(?!\+)(\S+\+)+'e",  // SL-groesser
                    "'(^|\s)-(?!-)(\S+-)+'e",     // SL-kleiner
                    "'(^|\s)>(?!>)(\S+>)+'e",     // SL-hochgestellt
                    "'(^|\s)<(?!<)(\S+<)+'e",     // SL-tiefgestellt
                    "'%%(\S|\S.*?\S)%%'s",        // ML-kursiv
                    "'\*\*(\S|\S.*?\S)\*\*'s",    // ML-fett
                    "'__(\S|\S.*?\S)__'s",        // ML-unterstrichen
                    "'##(\S|\S.*?\S)##'s",        // ML-diktengleich
                    "'\+\+(((\+\+)*)(\S|\S.*?\S)?\\2)\+\+'s",  // ML-groesser
                    "'--(((--)*)(\S|\S.*?\S)?\\2)--'s",        // ML-kleiner
                    "'>>(\S|\S.*?\S)>>'is",  // ML-hochgestellt
                    "'<<(\S|\S.*?\S)<<'is",  // ML-tiefgestellt
                    "'{-(.+?)-}'is" ,        // durchgestrichen
                    "'\n\n  (((\n\n)  )*(.+?))(\Z|\n\n(?! ))'s",  // Absatz eingerueckt
                    "'(?<=\n|^)--+(\d?)(\n|$|(?=<))'m", // Trennlinie
                    "'\[pre\](.+?)\[/pre\]'is" ,        // praeformatierter Text
                    "'\[nop\].+\[/nop\]'isU",
                    //"'\[.+?\](((http\://|https\://|ftp\://)?([^/\s]+)(.[^/\s]+){2,})|([-a-z0-9_]+(\.[_a-z0-9-]+)*@([a-z0-9-]+(\.[a-z0-9-]+)+)))'i",
                    "'\[(.+?)\](((http\://|https\://|ftp\://)?([^/\s]+)(\.[^/\s]+){2,}(/[^\s]*)?)|([-a-z0-9_]+(\.[_a-z0-9-]+)*@([a-z0-9-]+(\.[a-z0-9-]+)+)))'i",
            //      "'\[quote=.+?quote\]'is",    // quoting
                    "'(\s):[^\s]+?:(\s)'s"              // smileys

                    );
    $replace = array(
                    "\\1\\2", "\\1\\3",
                    "'\\1'.substr(str_replace('%', ' ', '\\2'), 0, -1)",
                    "'\\1'.substr(str_replace('*', ' ', '\\2'), 0, -1)",
                    "'\\1'.substr(str_replace('_', ' ', '\\2'), 0, -1)",
                    "'\\1'.substr(str_replace('#', ' ', '\\2'), 0, -1)",
                    "'\\1'.substr(str_replace('+', ' ', '\\2'), 0, -1)",
                    "'\\1'.substr(str_replace('-', ' ', '\\2'), 0, -1)",
                    "'\\1'.substr(str_replace('>', ' ', '\\2'), 0, -1)",
                    "'\\1'.substr(str_replace('<', ' ', '\\2'), 0, -1)",
                    "\\1", "\\1", "\\1", "\\1", "\\1", "\\1",
                    "\\1", "\\1", "\\1", "\n\\1\n", "", "\\1",'[nop] [/nop]',
                    //"\\2",
                    '$1 ($2)',
                     //"",
                      '$1$2');

    if (preg_match_all("'\[nop\](.+)\[/nop\]'isU", $text, $matches)) {
        $text = preg_replace($pattern, $replace, $text);
        $text = explode("[nop] [/nop]", $text);
        $i = 0;
        $all = '';
        foreach ($text as $w)
            $all .= $w . $matches[1][$i++];

        return $all;
    }

    return preg_replace($pattern, $replace, $text);
}

function isURL($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

function isLinkIntern($url) {
    $pum = @parse_url($url);
    if (($pum['scheme'] === 'http' || $pum['scheme'] === 'https')
            && ($pum['host'] == $_SERVER['HTTP_HOST'] || $pum['host'].':'.$pum['port'] == $_SERVER['HTTP_HOST'])
            && strpos($pum['path'], $GLOBALS['CANONICAL_RELATIVE_PATH_STUDIP']) === 0) {
        return true;
    }
    return false;
}

/**
* convert links with 'umlauten' to punycode
*
* @access   public
* @param    string  link to convert
* @param    boolean  for mailadr = true and for other link = false
* @return   string  link in punycode
*/
function idna_link($link, $mail = false){
    if (!$GLOBALS['CONVERT_IDNA_URL']) return $link;
    $pu = @parse_url($link);
    if (preg_match('/&\w+;/i',$pu['host'])) { //umlaute?  (html-coded)
        $IDN = new idna_convert();
        $out = false;
        if ($mail){
            if (preg_match('#^([^@]*)@(.*)$#i',$link, $matches)) {
                $out = $IDN->encode(utf8_encode(decodeHTML($matches[2], ENT_NOQUOTES))); // false by error
                $out = ($out)? $matches[1].'@'.$out : $link;
            }
        }elseif (preg_match('#^([^/]*)//([^/?]*)(((/|\?).*$)|$)#i',$link, $matches)) {
            $out = $IDN->encode(utf8_encode(decodeHTML($matches[2], ENT_NOQUOTES))); // false by error
            $out = ($out)? $matches[1].'//'.$out.$matches[3] : $link;
        }
        return ($out)? $out:$link;
    }
    return $link;
}


/**
 * Create smileys
 *
 * This functions converts the smiley codes notation (:name:) as well as the
 * available short notation.
 *
 * @access public
 * @param  string $text The text to convert
 * @return string Converted text
 */
function smile($text = '') {
    $markup = new SmileyFormat();
    return $markup->format($text);
}


/**
* create symbols from the shorts
*
* This functions converts the short, locatet in the config.inc
* into the assigned pictures. It uses a different directory
* as the smile-function, becauso symbols should not be shown in
* the smiley and so, no link is given onto the picture. A tooltip which
* shows the symbol code is given, too.
*
* @access   public
* @param        string  the text to convert
* @return       string  convertet text
*/
function symbol ($text = "")
{
    global $SYMBOL_SHORT;

    if(empty($text))
        return $text;

    $patterns = array();
    $replaces = array();
    //symbols in short notation
    reset($SYMBOL_SHORT);
    while (list($key, $value) = each($SYMBOL_SHORT)) {
        $patterns[] = "'" . preg_quote($key) . "'m";
        $replaces[] = $value;
    }

    return preg_replace($patterns, $replaces, $text);
}

//Beschneidungsfunktion fuer alle printhead Ausgaben
function mila ($titel, $size = 60) {
    global $auth;

    if ($auth->auth["jscript"] AND $size == 60) {
        //hier wird die maximale Laenge berechnet, nach der Abgeschnitten wird (JS dynamisch)
        if (strlen ($titel) >$auth->auth["xres"] / 13)
            $titel=substr($titel, 0, $auth->auth["xres"] / 13)."... ";
    }
    else {
        if (strlen ($titel) >$size)
            $titel=substr($titel, 0, $size)."... ";
    }
    return $titel;
}

/**
 * Ausgabe der Aufklapp-Kopfzeile
 *
 * @param $breite
 * @param $left
 * @param $link
 * @param $open
 * @param $new
 * @param $icon
 * @param $titel
 * @param $zusatz
 * @param $timestmp
 * @param $printout
 * @param $index
 * @param $indikator
 * @param $css_class
 */
function printhead($breite, $left, $link, $open, $new, $icon, $titel, $zusatz,
                   $timestmp = 0, $printout = TRUE, $index = "", $indikator = "age",
                   $css_class = NULL)
{
    global $user;

    // Verzweigung was der Pfeil anzeigen soll
    if ($indikator == "viewcount") {
        if ($index == "0") {
            $timecolor = "#BBBBBB";
        } else {
            $tmp = $index;
            if ($tmp > 68)
                $tmp = 68;
            $tmp = 68-$tmp;
            $green = dechex(255 - $tmp);
            $other = dechex(119 + ($tmp/1.5));
            $timecolor= "#" . $other . $green . $other;
        }
    } elseif ($indikator == "rating") {
        if ($index == "?") {
            $timecolor = "#BBBBBB";
        } else {
            $tmp = (ABS(1-$index))*10*3;
            $green = dechex(255 - $tmp);
            $other = dechex(0);
            $red = dechex(255);
            $timecolor= "#" . $red . $green . $other;
        }
    } elseif ($indikator == "score") {
        if ($index == "0") {
            $timecolor = "#BBBBBB";
        } else {
            if ($index > 68)
                $tmp = 68;
            else
                $tmp = $index;
            $tmpb = 68-$tmp;
            $blue = dechex(255 - $tmpb);
            $other = dechex(119 + ($tmpb/1.5));
            $timecolor= "#" . $other . $other . $blue;
        }
    } else {
        if ($timestmp == 0)
            $timecolor = "#BBBBBB";
        else {
            if ($new == TRUE)
                $timecolor = "#FF0000";
            else {
                $timediff = (int) log((time() - $timestmp) / 86400 + 1) * 15;
                if ($timediff >= 68)
                    $timediff = 68;
                $red = dechex(255 - $timediff);
                $other = dechex(119 + $timediff);
                $timecolor= "#" . $red . $other . $other;
            }
        }
    }

    //TODO: überarbeiten -> valides html und/oder template draus machen...
    $class = "printhead";
    $class2 = "printhead2";
    $class3 = "printhead3";

    if ($css_class) {
        $class = $class2 = $class3 = $css_class;
    }

    if ($open == "close") {
        $print = "<td bgcolor=\"".$timecolor."\" class=\"".$class2."\" nowrap=\"nowrap\" width=\"1%\"";
        $print .= "align=\"left\" valign=\"top\">";
    }
    else {
        $print = "<td bgcolor=\"".$timecolor."\" class=\"".$class3."\" nowrap=\"nowrap\" width=\"1%\"";
        $print .= " align=\"left\" valign=\"top\">";
    }

    if ($link)
        $print .= "<a href=\"".$link."\">";

    $print .= "<img src=\"";
    if ($open == "open")
        $titel = "<b>" . $titel . "</b>";

    if ($link) {
        $addon = '';
        if ($index) $addon =  " ($indikator: $index)";
        if ($open == "close") {
            $print .= Assets::image_path('forumgrau2.png') . "\"" . tooltip(_("Objekt aufklappen") . $addon);
        }
        else {
            $print .= Assets::image_path('forumgraurunt2.png') . "\"" . tooltip(_("Objekt zuklappen") . $addon);
        }
    }
    else {
        if ($open == "close") {
            $print .= Assets::image_path('forumgrau2.png') . "\"";
        }
        else {
            $print .= Assets::image_path('forumgraurunt2.png') . "\"";
        }
    }

    $print .= " > ";
    if ($link) {
        $print .= "</a> ";
    }
    $print .= "</td><td class=\"".$class."\" nowrap=\"nowrap\" width=\"1%\" valign=\"bottom\"> $icon &nbsp; </td>";
    $print .= "<td class=\"".$class."\" align=\"left\" width=\"20%\" nowrap=\"nowrap\" valign=\"bottom\"> ";
    $print .= $titel."</td><td align=\"right\" nowrap=\"nowrap\" class=\"".$class."\" width=\"99%\" valign=\"bottom\">";
    $print .= $zusatz."</td>";


    if ($printout)
        echo $print;
    else
        return $print;
}

//Ausgabe des Contents einer aufgeklappten Kopfzeile
function printcontent ($breite, $write = FALSE, $inhalt, $edit, $printout = TRUE, $addon="") {

    $print = "<td class=\"printcontent\" width=\"22\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
    $print .= "</td><td class=\"printcontent\" width=\"$breite\" valign=\"bottom\"><br>";
    $print .= $inhalt;

    if ($edit) {
        $print .= "<br><br><div align=\"center\">$edit</div><img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\" height=\"6\">";
        if ($addon!="")
            if (substr($addon,0,5)=="open:") // es wird der öffnen-Pfeil mit Link ausgegeben
                $print .= "</td><td valign=\"middle\" class=\"table_row_even\" nowrap><a href=\"".substr($addon,5)."\"><img src=\"".Assets::image_path('icons/16/blue/arr_1left.png')."\" align=\"middle\"".tooltip(_("Bewertungsbereich öffnen"))."></a>&nbsp;";
            else {              // es wird erweiterter Inhalt ausgegeben
                $print .= "</td><td class=\"content_body_panel\" nowrap>";
                $print .= "<font size=\"-2\" color=\"#444444\">$addon";
    }       }
    else
        $print .= "<br>";

    $print .= "</td>";

    if ($printout)
        echo $print;
    else
        return $print;
}

/**
 * print_infobox, baut einen Info-Kasten aus folgenden Elementen zusammen:
 * Bild (separat uebergeben), Ueberschriften, Icons, Inhalt (in Array).
 * Der Aufruf des Bildes ist optional.
 *
 * @example
    $infobox = array    (
    array  ("kategorie"  => "Information:",
            "eintrag" => array  (
                            array    (  "icon" => "icons/16/black/search.png",
                                    "text"  => "Um weitere Veranstaltungen bitte Blabla"
                                    ),
                            array    (  "icon" => "icons/16/black/info.png",
                                    "text"  => "um Verwaltung  Veranstaltungen bitte Blabla"
                                    )
            )
        ),
    array  ("kategorie" => "Aktionen:",
               "eintrag" => array   (
                            array ( "icon" => "icons/16/black/info.png",
                                    "text"  => "es sind noch 19 Veranstaltungen vorhanden."
                                    )
            )
        )
    );
 *
 * @param   array() $content
 * @param   string $picture
 * @param   bool $dont_display_immediatly
 */
function print_infobox($content, $picture = '', $dont_display_immediatly = false)
{
    // get template
    $template = $GLOBALS['template_factory']->open('infobox/infobox_generic_content');

    // fill attributes
    $template->set_attribute('picture', $picture);
    $template->set_attribute('content', $content);

    // render template
    if ($dont_display_immediatly) {
        return $template->render();
    } else {
        echo $template->render();
    }
}


/**
 * Returns a given text as html tooltip
 *
 * title and alt attribute is default, with_popup means a JS alert box
 * activated on click
 *
 * @param        string  $text
 * @param        boolean $with_alt    return text with alt attribute
 * @param        boolean $with_popup  return text with JS alert box on click
 * @return       string
 */
function tooltip ($text, $with_alt = TRUE, $with_popup = FALSE) {
    $result = '';
    foreach (tooltip2($text, $with_alt, $with_popup) as $key => $value) {
        $result .= sprintf(' %s="%s"', $key, $value);
    }
    return $result;
}

/**
 * Returns a given text as an array of html attributes used as tooltip
 *
 * title and alt attribute is default, with_popup means a JS alert box
 * activated on click
 *
 * @param        string  $text
 * @param        boolean $with_alt    return text with alt attribute
 * @param        boolean $with_popup  return text with JS alert box on click
 * @return       string
 */
function tooltip2($text, $with_alt = TRUE, $with_popup = FALSE) {

    $ret = array();

    if ($with_popup) {
        $ret['onClick'] = "alert('".JSReady($text, "alert")."');";
    }

    $text = preg_replace("/(\n\r|\r\n|\n|\r)/", " ", $text);
    $text = htmlReady($text);

    if ($with_alt) {
        $ret['alt'] = $text;
    }
    $ret['title'] = $text;

    return $ret;
}

/**
 * returns a html-snippet with an icon and a tooltip on it
 *
 * @param type $text
 */
function tooltipIcon($text, $important = false)
{
    // prepare text
    $text = preg_replace("/(\n\r|\r\n|\n|\r)/", " ", $text);
    $text = htmlReady($text);

    // render tooltip
    $template = $GLOBALS['template_factory']->open('shared/tooltip');
    return $template->render(compact('text', 'important'));
}

/**
* detects internal links in a given string and convert used domain to the domain
* actually used (only necessary if more than one domain exists)
*
* @param    string  text to convert
* @return   string  text with convertes internal links
*/
function TransformInternalLinks($str){
    static $domain_data = null;
    if (is_array($GLOBALS['STUDIP_DOMAINS']) && count($GLOBALS['STUDIP_DOMAINS']) > 1) {
        if (is_null($domain_data)){
            $domain_data['domains'] = '';
            foreach ($GLOBALS['STUDIP_DOMAINS'] as $studip_domain) $domain_data['domains'] .= '|' . preg_quote($studip_domain);
            $domain_data['domains'] = preg_replace("'(\|.+?)((/.*?)|\|)'", "\\1[^/]*?\\2", $domain_data['domains']);
            $domain_data['domains'] = substr($domain_data['domains'], 1);
            $domain_data['user_domain'] = preg_replace("'^({$domain_data['domains']})(.*)$'i", "\\1", $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            $domain_data['user_domain_scheme'] = 'http' . (($_SERVER['HTTPS'] || $_SERVER['SERVER_PORT'] == 443) ? 's' : '') . '://';
        }
        return preg_replace("'https?\://({$domain_data['domains']})((/[^<\s]*[^\.\s<])*)'i", "{$domain_data['user_domain_scheme']}{$domain_data['user_domain']}\\2", $str);
    } else {
        return $str;
    }
}

/**
* creates a modal dialog ensuring that the user is really aware about the action to perform
*
* @param   string $question          question of the modal dialog
* @param   string $approveParams     an array of params for a link to be used on approval
* @param   string $disapproveParams  an array of params for a link to be used on disapproval
* @param   string $baseUrl           if set, this url is used, PHP_SELF otherwise
*
* @return  string $dialog            text which contains the dialog
*/

function createQuestion($question, $approveParams, $disapproveParams = array(), $baseUrl = '?') {
    $template = $GLOBALS['template_factory']->open('shared/question');

    $template->set_attribute('approvalLink', URLHelper::getURL($baseUrl, $approveParams ));
    $template->set_attribute('disapprovalLink', URLHelper::getURL($baseUrl, $disapproveParams ));
    $template->set_attribute('question', $question);

    return $template->render();
}

/**
 * Displays the provided exception in a more readable fashion.
 *
 * @param Exception $exception The exception to be displayed
 * @param bool $as_html Indicates whether the exception shall be displayed as
 *                      plain text or html (optional, defaults to plain text)
 * @param bool $deep    Indicates whether any previous exception should be
 *                      included in the output (optional, defaults to false)
 * @return String The exception display either as plain text or html
 */
function display_exception(Exception $exception, $as_html = false, $deep = false) {
    $result  = '';
    $result .= sprintf("%s: %s\n", _('Typ'), get_class($exception));
    $result .= sprintf("%s: %s\n", _('Nachricht'), $exception->getMessage());
    $result .= sprintf("%s: %d\n", _('Code'), $exception->getCode());

    $trace = sprintf("  #$ %s(%u)\n", $exception->getFile(), $exception->getLine())
           . '  '  . str_replace("\n", "\n  ", $exception->getTraceAsString());
    $trace = str_replace($GLOBALS['STUDIP_BASE_PATH'] . '/', '', $trace);
    $result .= sprintf("%s:\n%s\n", _('Stack trace'), $trace);

    if ($deep && $exception->getPrevious()) {
        $result .= "\n";
        $result .= _('Vorherige Exception:') . "\n";
        $result .= display_exception($exception->getPrevious(), false, $deep);
    }

    return $as_html ? nl2br(htmlReady($result)) : $result;
}
