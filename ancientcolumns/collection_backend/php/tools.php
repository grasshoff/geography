<?php
use Seboettg\CiteProc\CiteProc;

/**
 * Common helper functions.
 */


/**
 * Sanitize path parameter: Strip all but [A-Za-z0-9], slashes and "-" symbols from a string $v
 * @param string $v value to be sanitized
 * @return mixed sanitized value
 */
function sanitizeParamPath($v) {
    return preg_replace('/[^\w\/.-]/', '', $v);
}


/**
 * Abbreviate a persons name, e.g. John Wayne -> J. Wayne or Jan-Josef Liefers -> J.J. Liefers
 * @param string $n full person name
 * @return string abbreviated person name
 */
function abbreviatePersonName($n) {
    // split name by whitespace or hyphen
    $nParts = preg_split("/[\s-]+/", $n);
    $numNameParts = count($nParts);

    if ($numNameParts <= 1) {
        return $n;
    }

    // create abbreviation for forename(s)
    $abbrevs = '';
    for ($i = 0; $i < $numNameParts - 1; $i++) {
        $forename = trim($nParts[$i]);
        if (strlen($forename) <= 0) {
            continue;
        }
        $abbrevs .= substr($forename, 0, 1) . '.';
    }

    // return forename abbreviation(s) + last name
    return $abbrevs . ' ' . $nParts[$numNameParts - 1];
}


/**
 * Format multiple literature references as HTML string concatenated by '; '
 * @see formatLitRef()
 * @param array $refs literature reference data array
 * @param stdClass $meta contains attribute "lit_ref_style" (optional)
 * @return string multiple literature references as HTML string concatenated by '; '
 */
function formatLitRefs($refs, $meta) {
    $output = array();
    
    $formatLitRef = "formatLitRef";
    $style = null;
    if (isset($meta) && isset($meta->lit_refs_style)){
    	$formatLitRef = "formatLitRefWithCiteProc";
    	$style = $meta->lit_refs_style;
    }
    
    foreach ($refs as $i => $ref) {
        array_push($output, $formatLitRef($ref, $style));
    }

    // sort by value
    sort($output);

    // create list items
    $outputHTML = array();
    foreach ($output as $ref) {
        array_push($outputHTML, sprintf('<li>%s</li>', $ref));
    }

    // return as HTML list
    return sprintf('<ul class="lit_ref_list">%s</ul>', implode('', $outputHTML));
}


/**
 * Format a list of names for a literature reference
 *
 * @param array $names list of named BibJSON objects
 * @param string $glue glue between the names
 * @param string $append string to append at the end
 * @return string formatted list of names
 */
function formatLitRefNames(array $names, $glue=' and ', $append=': ') {
    $namesList = array();
    foreach ($names as $n) {
        if (isset($n->name) && strlen($n->name) > 0) {
            array_push($namesList, $n->name);
        }
    }

    return implode($glue, $namesList) . $append;
}


/**
 * Format literature reference BibJSON data as HTML string
 * @param array $ref literature reference data
 * @return string literature reference as HTML string
 */
function formatLitRef($ref) {
    // 1. part: handle authors or editors
    $editorAdded = false;
    if (isset($ref->author) && count($ref->author) > 0) {
        $authors = formatLitRefNames($ref->author);
    } else if (isset($ref->editor) && count($ref->editor) > 0) {
        $authors = formatLitRefNames($ref->editor);
        $editorAdded = true;
    } else {
        $authors = '';
    }

    // 2. part: handle title + additonal information like editors, volume
    $title = $ref->title;
    $volAdded = false;
    if (isset($ref->journal)) {
        if (!$editorAdded && isset($ref->editor) && count($ref->editor) > 0) {
            $editor = formatLitRefNames($ref->editor);
        } else {
            $editor = '';
        }

        $title .= ', In: ' . $editor . $ref->journal->name;
        if (isset($ref->journal->volume) && strlen($ref->journal->volume) > 0) {
            $volAdded = true;
            $title .= ', vol. ' . $ref->journal->volume;
        }

        if (isset($ref->journal->pages) && strlen($ref->journal->pages) > 0) {
            $ref->pages = $ref->journal->pages;
        }
    }

    if (substr($title, -1) != ',') {
        $title .= ',';
    }

    // 3. part: handle publisher + publisher place
    $addrAdded = false;
    if (isset($ref->publisher) && isset($ref->publisher->name)) {
        $title .= ' ' . $ref->publisher->name;
        if (isset($ref->publisher->address) && strlen($ref->publisher->address) > 0) {
            $title .= ', '. $ref->publisher->address;
            $addrAdded = true;
        }
    }

    if (substr($title, -1) != ',') {
        $title .= ',';
    }

    if (!$addrAdded && isset($ref->address) && strlen($ref->address) > 0) {
        $title .= ' '. $ref->address;
    }

    if (substr($title, -1) != ',') {
        $title .= ',';
    }

    $additionalCit = '';
    $glue = '';

    // 4. part: handle volume (if not added yet for journals)
    if (!$volAdded && isset($ref->volume) && strlen($ref->volume) > 0) {
        $additionalCit .=  'vol. ' . $ref->volume;
        $glue = ', ';
    }

    // 5. part: handle year
    if (isset($ref->year) && strlen($ref->year) > 0) {
        $additionalCit .=  $glue . $ref->year;
        $glue = ', ';
    }

    // 6. part: handle chapter
    if (isset($ref->chapter) && strlen($ref->chapter) > 0) {
        $additionalCit .=  $glue . $ref->chapter;
        $glue = ', ';
    }

    // 7. part: handle pages
    if (isset($ref->pages) && strlen($ref->pages) > 0) {
        $additionalCit .=  $glue . $ref->pages;
    }

    // create full citation
    $fullCit = implode(' ', array($authors, $title, $additionalCit));

    if (substr($fullCit, -1) != '.') {
        $fullCit .= '.';
    }

    //return sprintf('<span class="lit-entry">%s</span><br />', $fullCit);
    return $fullCit;
}

/*
 * CiteProc instance. Only created on demand.
 */
$citeproc = null;
$citeprocStyle = null;
/*
 * Format LitRef with help of CiteProc library
 */
function formatLitRefWithCiteProc($ref, $style) {
	global $citeproc;
    global $citeprocStyle;
	
	if (!isset($style)){
		$style = "din-1505-2-alphanumeric.csl";
	}

	if (!isset($citeproc) || ($style != $citeprocStyle)){
	    $citeProc = new CiteProc($style);
	    
		$cslStyle = file_get_contents("./php/lib/CiteProc/style/".$style);
		
		$citeproc = new CiteProc($cslStyle);
		$citeprocStyle = $style;
		/*
		 * TODO: this was inserted because of ICG project, which requires certain type of LitRef, fix this.
		 */
		echo '<style>.lit_ref_list li:before { content: ""; }</style>';
		
	}
	
	$citationDisplay = $citeproc->render([$ref],"bibliography");
	
	return $citationDisplay;
}

/**
 * Format a link list defined by $listData
 *
 * @param array $listData       array of list items. each item is a 2-elem.-array with link and title
 * @param string $listFmt       format string for the whole list
 * @param string $listItemFmt   format string for an item
 * @param string $linkFmt       format string for a link inside an item
 * @return string formatted list (by default HTML list)
 */
function formatLinkList(array $listData,
                        $listFmt='<ul class="link_list">%s</ul>',
                        $listItemFmt='<li>%s</li>',
                        $linkFmt='<a href="%s" target="_blank">&rarr; %s</a>')
{
    $listHTMLArr = array();

    // create list data from individual items
    foreach ($listData as $link) {
        $linkStr = sprintf($linkFmt, htmlentities($link[0]), htmlentities($link[1]));
        $itemStr = sprintf($listItemFmt, $linkStr);

        array_push($listHTMLArr, $itemStr);
    }

    $listStr = implode($listHTMLArr);

    return sprintf($listFmt, $listStr);
}


/**
 * Return a full path to a 'tiny' thumbnail
 * @param string $path thumbnail folder path
 * @param string $file original (full scaled) file name
 * @return string full path to a 'tiny' thumbnail
 */
function thumbTiny($path, $file) {
    return thumb($path, $file, 'tiny');
}

/**
 * Return a full path to a 'small' thumbnail
 * @param string $path thumbnail folder path
 * @param string $file original (full scaled) file name
 * @return string full path to a 'small' thumbnail
 */
function thumbSmall($path, $file) {
    return thumb($path, $file, 'small');
}

/**
 * Return a full path to a thumbnail of a certain thumbnail size type $thumbSize.
 * @param string $path thumbnail folder path
 * @param string $file original (full scaled) file name
 * @param string $thumbSize thumbnail size type ('small' or 'tiny')
 * @return string full path to a thumbnail of a certain thumbnail size type $thumbSize
 */
function thumb($path, $file, $thumbSize) {
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    $extLowerCase = strtolower($ext);
    //TODO: BSDP has to be fixed and this has to be removed

    if ((strrpos($path, "BSDP" ) > 0) || (strrpos($path, "BDPP" ) > 0) || (strrpos($path, "MAPD" ) > 0)){
       	$ext = $extLowerCase;
    }

    $base = pathinfo($file, PATHINFO_FILENAME);

    // set default thumb extension if it was not yet defined
    if (!defined('THUMBS_DEFAULT_FILE_EXT')) {
        define('THUMBS_DEFAULT_FILE_EXT', 'jpg');
    }

    // if this is a thumb for a non-image resource (.zip, .obj, etc.), use the default thumb extension
    if (!in_array($extLowerCase, array('jpg', 'jpeg', 'png', 'gif'))) {
        $ext = THUMBS_DEFAULT_FILE_EXT;
    }

    return $path . '/' . $base . '_' . $thumbSize . '.' . $ext;
}

/**
 * Truncate a text by max. number of words.
 *
 * @param string $input text
 * @param int $numwords max. number of words
 * @param string $padding additional string appended to truncated text
 * @return string truncated text
 */
function truncateWords($input, $numwords, $padding="") {
    $output = strtok($input, " \n");
    while(--$numwords > 0) $output .= " " . strtok(" \n");
    if($output != $input) $output .= $padding;
    return $output;
}


/**
 * Merge two simple objects $a and $b recursively. Values in $b will override the ones in $a. Values in $b and not in
 * $a will be added to the output object.
 * Values will be copied, not simply referenced.
 * @param object $a object A
 * @param object $b object B (will override A's values wherever the keys match)
 * @return stdClass merged output object
 */
function mergeObjects($a, $b) {
    assert(is_object($a));
    assert(is_object($b));

    $o = new stdClass();

    // add all values from $a to $o and where existing, override by $b's values
    foreach (get_object_vars($a) as $k => $aV) {
        if (isset($b->$k)) {
            if (is_object($aV)) {
                $v = mergeObjects($a->$k, $b->$k);
            } else {
                $v = $b->$k;
            }
        } else {
            if (is_object($aV)) {
                $v = clone $aV;
            } else {
                $v = $aV;
            }
        }

        $o->$k = $v;
    }

    // if there're values in $b that aren't in $a, add them here
    foreach (get_object_vars($b) as $k => $bV) {
        if (!isset($a->$k)) {
            if (is_object($bV)) {
                $v = clone $bV;
            } else {
                $v = $bV;
            }

            $o->$k = $v;
        }
    }

    return $o;
}


/**
 * Delete all keys (in-place) in array $keys in object or array $x.
 *
 * @param $x array or object to delete keys in
 * @param $keys array with keys to delete
 */
function delKeys($x, $keys) {
    assert(is_object($x) || is_array($x));
    $isObj = is_object($x);

    foreach ($keys as $k) {
        if ($isObj) {
            unset($x->$k);
        } else {
            unset($x[$k]);
        }
    }
}


/**
 * Create intersection of all arrays in $arrays using array_intersection() function.
 *
 * @param $arrays array input arrays
 * @return array intersection of all input arrays
 */
function multiArrayIntersection($arrays) {
    assert(is_array($arrays) && count($arrays) > 1);

    $intersect = array_intersect($arrays[0], $arrays[1]);
    $rest = array_slice($arrays, 2);
    foreach ($rest as $a) {
        $intersect = array_intersect($intersect, $a);
    }

    return array_values($intersect);
}

/**
 * Create join of all arrays in $arrays using array_intersection() function.
 *
 * @param $arrays array input arrays
 * @return array intersection of all input arrays
 */
function multiArrayJoin($arrays) {
	$flatten = function(array $array) {
		$return = array();
		array_walk_recursive($array, function($a) use (&$return) { $return[] = $a; });
		return $return;
	};
	
	return array_unique($flatten($arrays));
}

/**
 * vnsprintf is equal to vsprintf except for associative, signed or floating keys.
 * http://de2.php.net/manual/en/function.vsprintf.php#83883
 */
function vnsprintf( $format, array $data)
{
    preg_match_all( '/ (?<!%) % ( (?: [[:alpha:]_-][[:alnum:]_-]* | ([-+])? [0-9]+ (?(2) (?:\.[0-9]+)? | \.[0-9]+ ) ) ) \$ [-+]? \'? .? -? [0-9]* (\.[0-9]+)? \w/x', $format, $match, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
    $offset = 0;
    $keys = array_keys($data);
    foreach ( $match as &$value )
    {
        if ( ( $key = array_search( $value[1][0], $keys) ) !== FALSE || ( is_numeric( $value[1][0]) && ( $key = array_search( (int)$value[1][0], $keys) ) !== FALSE ) ) {
            $len = strlen( $value[1][0]);
            $format = substr_replace( $format, 1 + $key, $offset + $value[1][1], $len);
            $offset -= $len - strlen( $key);
        }
    }
    return vsprintf( $format, $data);
}

/**
 * Create a formatted string from a twig template and one or more attributes of a research object.
 *
 * @param stdClass $template twig template
 * @param array $objData data in associated array
 * @return null|string rendered HTML or null if something went wrong
 * @throws Exception
 */
function stringFromTemplateAndData($template, $objDataArray) {
    $loader = new Twig_Loader_Array(array(
        'template' => $template,
    ));
    $twig = new Twig_Environment($loader);
    
    return $twig->render('template', $objDataArray);
}

/**
 * Create a formatted string from one or more attributes of a research object.
 * This function is called in a collection's template with the research object data as first argument and uses the
 * formatting definition provided for individual information sections as second argument. These sections are defined in
 * the "display_definitions" document.
 *
 * @param stdClass $objData full research object data
 * @param stdClass $def display definition for a section
 * @return null|string rendered HTML or null if something went wrong
 * @throws Exception
 */
function stringFromObjectData($objData, $def) {
    $escape = !isset($def->html) || (isset($def->html) && !$def->html); // use htmlentities() at last?

    $ret = null;
    
    if (!isset($def->template)){
        $valsForFmt = array();
        
        if (isset($def->value)) {   // fetch and format a single (possibly composite) value
            $v = getAttr($objData, $def->value, true);
    
            if (isset($v) && $v){
                if (is_array($v)) { //iterate over array values
                    $escape = false;
                    foreach ($v as $i => $element){
                        //for non-associative (single-entry) values
                        if (!is_object($element)){
                            $element = [$element];
                        } else {
                            $element = (array)$element;
                        }
                        $vFormatted = @vnsprintf($def->format_item, $element);
                        array_push($valsForFmt, $vFormatted);
                    }
                    $a=1;
                } else if (isset($def->lit_refs) && $def->lit_refs) {  // format a list of literature references
    				$ret = formatLitRefs($v, $def);
    	            $escape = false;
    	        } else if (isset($def->list_of_links) && $def->list_of_links) { // format a list of links
    	            $ret = formatLinkList($v);
    	            $escape = false;
    	        } else {    // format a single value
    	            $ret = $v;
    	        }
            }
        } elseif (isset($def->values) && (isset($def->format) || isset($def->concat_by))) { // fetch and format multiple values defined in an array
            foreach ($def->values as $attr) {   // go through all the values that should be fetched from the object
                if (is_array($attr)) {  // key, title as array items
                    list($k, $title) = $attr;
                } else {    // single key as item
                    $k = $attr;
                    $title = null;
                }
    
                // get the value
                $v = getAttr($objData, $k, true);
                if ($v) {
                    if (isset($def->format_item)) { // format individual item
                        if ($title) {
                            $vFmtElems = array(htmlentities($title), htmlentities($v));
                        } else {
                            $vFmtElems = array($v);
                        }
    
                        $vFormatted = @vnsprintf($def->format_item, $vFmtElems);
                    } else {    // dont format individual item
                        $vFormatted = $v;
                    }
    
                    array_push($valsForFmt, $vFormatted);
                }
            }
        }
    
        if (sizeof($valsForFmt)>0){
            if (isset($def->format)) {  // format all values
                if (isset($def->format_item)) {
                    $ret = sprintf($def->format, implode($valsForFmt));
                } else {
                    $ret = @vnsprintf($def->format, $valsForFmt);
                }
            } elseif (isset($def->concat_by)) { // concatenate all values
                $ret = implode($def->concat_by, $valsForFmt);
            }
        }
    } else {
        // use twig template
        //convert stdClass to associated array
        $objDataArray = json_decode(json_encode($objData), true);
        $ret = stringFromTemplateAndData($def->template, $objDataArray);
    }

    // escape
    if (!is_null($ret)) {
        $s = $escape ? htmlentities($ret) : $ret;
    } else {
        $s = '';
        // throw new Exception("Either 'value' or ('values' and ('format' or 'concat_by')) must be specified.");
    }

    if (isset($def->surround_by)) { // surround by tag
        $tag = htmlentities($def->surround_by);
        return '<' . $tag . '>' . $s . '</' . $tag . '>';
    } else {
        return $s;
    }
}


/**
 * Get an attribute $attr from $obj which can be either an assoc. array or an PHP object.
 * @param mixed $obj variable which can be either an assoc. array or an PHP object
 * @param string $attr attribute name
 * @param bool $recursive traverse recursively through $obj when we have an $attr with 'obj->subobj->...' path
 * @return mixed attribute value
 * @throws Exception
 */
function getAttr($obj, $attr, $recursive = false) {
    if ($recursive) {
        $attrPath = explode('->', $attr);
        $attr = $attrPath[0];
        if (count($attrPath) > 1) {
            $attrNextLevel = implode('->', array_slice($attrPath, 1));
        } else {
            $attrNextLevel = null;
        }
    } else {
        $attrNextLevel = null;
    }

    if (is_array($obj)) {
        if (!isset($obj[$attr])) {
            return null;
        }

        if ($recursive && $attrNextLevel && (is_array($obj[$attr]) || is_object($obj[$attr]))) {
            return getAttr($obj[$attr], $attrNextLevel, true);
        } else {
            return $obj[$attr];
        }
    } else if (is_object($obj)) {
        if (!isset($obj->$attr)) {
            return null;
        }

        if ($recursive && $attrNextLevel && (is_array($obj->$attr) || is_object($obj->$attr))) {
            return getAttr($obj->$attr, $attrNextLevel, true);
        } else {
            return $obj->$attr;
        }
    } else {
        throw new Exception('Must be either array or object: ' . $obj);
    }
}


/**
 * Check if an attribute $attr exists in $obj which can be either an assoc. array or an PHP object.
 * @param mixed $obj variable which can be either an assoc. array or an PHP object
 * @param string $attr attribute name
 * @return mixed attribute value
 * @throws Exception
 */
function hasAttr($obj, $attr) {
    if (is_null($obj)) return false;

    if (is_array($obj)) {
        return isset($obj[$attr]);
    } else if (is_object($obj)) {
        return isset($obj->$attr);
    } else {
        throw new Exception('Must be either array or object: ' . $obj);
    }
}

/**
 * If $n is numeric, return $n as integer, else return it unchanged.
 * @param mixed $n input value
 * @return mixed $n either as integer or unchanged
 */
function intFromNumber($n) {
    return is_numeric($n) ? (int)$n : $n;
}


/**
 * Encode an URL parameter value (often used for CouchDB queries).
 * If $p is not numeric, wrap quotes around the parameter
 * @param mixed $p parameter value
 * @return string URL encoded parameter value
 * @throws Exception
 */
function encodeURLParamValue($p) {
    if (is_numeric($p)) {
        $q = urlencode($p);
    } else if (is_string($p)) {
        $q = urlencode('"' . $p . '"');
    } else if (is_object($p) && !(array)$p) {
    	$q = "{}";
    } else {
        throw new Exception('URL parameter must be either numeric or a string or an empty object');
    }

    return $q;
}


/**
 * Check if something exists at URL $url, i.e. if we can connect to that URL.
 * @param string $url URL to check
 * @return mixed true on success or false on failure
 */
function urlExists($url) {
    $handle   = curl_init($url);

    curl_setopt($handle, CURLOPT_HEADER, false);
    curl_setopt($handle, CURLOPT_FAILONERROR, true);
    curl_setopt($handle, CURLOPT_NOBODY, true);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, false);
    $connectable = curl_exec($handle);
    curl_close($handle);
    return $connectable;
}


/**
 * Check if string $haystack starts with $needle.
 * @param string $haystack
 * @param string $needle
 * @return bool
 */
function strStartsWith($haystack, $needle) {
    // search backwards starting from haystack length characters from the end
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
}


/**
 * Check if string $haystack ends with $needle.
 * @param string $haystack
 * @param string $needle
 * @return bool
 */
function strEndsWith($haystack, $needle) {
    // search forward starting from end minus needle length characters
    return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
}


/**
 * Create slug for string $s
 * @param string $s string to sluggify
 * @return string slugged string
 */
function sluggify($s) {
    $s = strtolower($s);
    $charTable = array(
        'ä' => 'a',
        'ö' => 'o',
        'ü' => 'u',
        'ß' => 'ss',
        'ı' => 'i',
        'ɩ' => 'i',
        'ç' => 'c',
        'ğ' => 'g',
        'ş' => 's',
        'ș' => 's',
    );
    $s = strtr($s, $charTable);
    $s = preg_replace('/[^A-Za-z0-9]/', '_', $s);
    $s = preg_replace('/_+/', '_', $s);

    return $s;
}


/**
 * Clean a parameter value (delete evil characters)
 * @param string $s value to clean
 * @return string cleaned value
 */
function cleanParam($s) {
    return preg_replace('/[^a-z0-9_ %-:\.\/]/i', '', $s);
}


class ETRepoHTMLPurifier {
    private static $htmlPurifierConfig = false;
    private static $purifier = false;

    static function cleanHTML($dirty_html){
        if (self::$htmlPurifierConfig === FALSE){
            require_once('lib/HTMLPurifier/HTMLPurifier.includes.php');
            ETRepoHTMLPurifier::initialize();
        }
        return self::$purifier->purify($dirty_html);
    }

    public static function initialize(){
        self::$htmlPurifierConfig = HTMLPurifier_Config::createDefault();
        self::$purifier = new HTMLPurifier(self::$htmlPurifierConfig);
    }
}
