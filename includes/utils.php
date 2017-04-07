<?phpnamespace Monk\Utils;define('MONK_UTILS', true);/** *  *  * @staticvar array $partials * @param type $namespace * @param type $exclude_in_ajax * @return type */function can_skip($limit = 1, $exclude_in_ajax = true, $namespace = ''){            static $partials = [];        $file       = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file'];    $partial    = $namespace . DIRECTORY_SEPARATOR . $file;        array_key_exists($partial, $partials) || $partials[$partial] = 0;                if(            is_template_query()        ||  ($exclude_in_ajax && defined('DOING_AJAX') && DOING_AJAX)        ||  ($limit && $partials[$partial] >= (int)$limit)    )        $skip = true;        else{                $skip = false;        $partials[$partial]++;            }            return apply_filters('monk\skip', $skip, $file, $namespace, $exclude_in_ajax);    }/** * Email Encrypter */class email_encrypt {		private $cache		= array();	private function encryptMailto($str){		$r      = '';		$enc	= '';		$salt	= rand(1, 9);		$arr	= str_split(str_replace(' ', '', 'mailto:' . strtolower($str)));		foreach($arr as $a){                    $n = ord($a) + $salt;                    if($n > 126)                        $r .= "/**$n**/";                    else                        $r .= chr($n);                }			$r .= ord($salt);                $len = strlen($r);                		for ($i = 0; $i < $len; $i = $i + 2) {                    if ($i + 1 === $len){                      $enc .= substr($r, $i);                    }else{                      $enc .= substr($r, $i+1, 1) . substr($r, $i, 1);                    }		}				return esc_attr($enc);	}		private function escapeEncode($str){		$ret = "";		$arr = unpack("C*", $str);		foreach ($arr as $char)                    $ret .= sprintf("%%%X", $char);		return $ret;	}		private function randomString($len){		$alphanum	= 'abcdefghijklmnopqrstvuwxyz';		$str		= '';				for($i = 0; $i < $len; $i++){                    $random = floor( (rand(0, 100) / 100) * (strlen($alphanum) - 1) );                    $str .= substr($alphanum, $random, 1);		}		if(  in_array( $str, $this->cache ) )			return $this->randomString ( $len );		else			$this->cache[] = $str;			return $str;	}				function encode($email, $text = null, $atts = ''){		$text		= antispambot($text ?: $email);		$href		= $this->encryptMailto($email);		$html		= "<a data-href='$href' $atts >$text</a>";                $map            = range(0, strlen($html) - 1);		$email_var	= $this->randomString(strlen($email));		$index_var	= $this->randomString(strlen($email));		$resolve_var    = $this->randomString(strlen($email));		$mail_arr	= "var $email_var = [";		$index_arr	= "$index_var = [";                shuffle($map);		for($i = 0; $i < count($map); $i++){			$mail_arr  .= "'" . addslashes($html[$map[$i]]) . "',";			$index_arr .= $map[$i] . ',';		}		$decoder = rtrim($mail_arr, ',') . "], " . rtrim($index_arr, ',') . "], $resolve_var = [];for(var i=0;i<$index_var.length;i++){$resolve_var}[{$index_var}[i]] = {$email_var}[i];$resolve_var.join('')";		return '<script style="display:none !important; visibility:hidden !important" type="text/email-crypt" data-stream="' . $this->escapeEncode( $decoder ) . '" ></script>';	}	}/* * Email Encrypter API wrapper. * The function is wired to 'the_content' filter and will automatically parse the post content for email addresses. * Note: If the email address is not already wrapped in a anchor tag, then the encrypter will generate one. *  * @version 1.1 *  */function email_encode($content){	if( class_exists(__NAMESPACE__ . '\email_encrypt') ){                                    $encrypter = new email_encrypt();            $fallback = "<noscript><em>" . _('Sorry, for security reasons our email address can only be displayed with JavaScript enabled.') . "</em></noscript>";                        $content = preg_replace_callback(                    '/<a(\s*[^>]*)href=(?:\"|\')mailto:([\w\.\-]+\@[\w\.\-]+?\.[a-zA-Z\.]{2,6}\w)(?:\"|\')([^>]*)\>(.*?)<\/a>' // Capture either email addresses in anchor tags...                .   '|(?:^|\s+)\b([\w\.\-]+\@[\w\.\-]+?\.[a-zA-Z\.]{2,6})\b(?:\s+|$)' // Or as a stand along email address wrapped with whitespace or line breaks.                .   '/Smix',                 function($m) use ($fallback, $encrypter){                                                                            $return = '';                                        list($full, $atts_before, $href, $atts_after, $text, $email) = $m + array_fill(0, 6, null);                    if(!empty ( $href ) )                            $return = $encrypter->encode($href, $text, trim($atts_before . $atts_after));                    elseif(!empty ( $email ) )                            $return = $encrypter->encode($email);                                        return $return . $fallback;                },                 $content            );	}	return $content;}/** * Patch for wpautop.  *  * Strips invalid P tags that are wrapped around non-permittable elements such as flow content or block-level elements.  *  * @staticvar array $allowed Stores array of html elements that are allowed with <P> tags. * @param string $content - The string to parse of invalid Pees * @param type $allowedTags - An array to append to the list of allowed tags to pass over. * @return string - Returns $content stripped of invalid Pees if any found. */function strip_invalid_pees($content, $allowedTags = array()){        // Caching variables	static $allowed = false;        static $patterns = false;        static $passes = false;        static $test = false;        static $replace = '';        $passes === false &&                $passes = apply_filters('ep/strip_invalid_pees/passes', 2);	$allowed === false &&		$allowed = implode('|', apply_filters('ep/pee_allowed_tags', array_unique(array_merge( ['abbr', 'audio', 'b', 'bdo', 'br', 'button', 'canvas', 'cite', 'code', 'command', 'datalist', 'dfn', 'em', 'embed',			'i', 'iframe', 'img', 'input', 'kbd', 'keygen', 'label', 'mark', 'math', 'meter', 'noscript', 'object', 'output', 'progress',			'q', 'ruby', 'samp', 'script', 'select', 'small', 'span', 'strong', 'sub', 'sup', 'svg', 'textarea', 'time', 'var', 'video', 'wbr',			'a', 'area', 'del', 'ins', 'link', 'map', 'meta'], $allowedTags ))));                $patterns === false &&                $patterns = apply_filters('ep/strip_invalid_pees/patterns', [                    "^<p>(\s*<)(?!$allowed)",                    "(?<!\/|$allowed)(>\s*)<\/p>$",                    "(^<(?!p|$allowed).+?>)<\/p>$"                ]);        if($replace === '')            for($i = 1; $i <= count ($patterns); $i++)                $replace .= '$' . $i;                $test === false &&                $test = "/" . implode('|', $patterns) . "/Sim";        if($passes){            for($i = 0; $i <= $passes; $i++)                $content = preg_replace ($test, $replace, $content);        }	return $content;}/** * Document me! *  * @param type $conds * @param type $check * @return type */function conditionalTagCheck($conds, $check = ''){    $returns = empty($conds) ? [false] : array_map(function($cb) use ($check) {        if($cb !== __NAMESPACE__ . __FUNCTION__ and is_callable($cb))            return (bool)call_user_func ($cb, $check);        else            return false;    }, (array)$conds);    return in_array(true, $returns, true);}/** * Based on Google's suggested SQL implementation of the Haversine Formula for calculating the great-circle distance between * two points on a sphere based on their latitude/longitude coordinates. *  * Original SQL:  * 6371 * acos( cos( radians(37) ) * cos( radians( lat ) ) * cos( radians( lng ) - radians(-122) ) + sin( radians(37) ) * sin( radians( lat ) ) ); * Here's the SQL statement that will find the closest locations that are within a radius set by the $restrict param in kilometers. *  * $to and $from objects should supply the following properties: * public $address; // Formatted address supplied by Google Maps Places Service * public $lat; // The latitude coordinates * public $lng; // The longitude coordinates *  * NOTE: Typically ACF's map field returns this object structure. *  * @param object $from First set of coords to get distance from. * @param object $to Second set of coords to get distance to. * @param int $restrict The radius to restrict the distance to. If the calculated distance exceeds this limit, false if returned. * @return int|boolean Optional. Returns the distance between to points in kilometers or false if either the $from or $to params supply empty objects or invalid coordinates. * * @see https://en.wikipedia.org/wiki/Haversine_formula * @see https://developers.google.com/maps/articles/phpsqlsearch_v3#finding-locations-with-mysql */function get_latlng_coords_distance($from, $to, $restrict = -1){    if(empty($from->address) or empty($to->address) or ($from->lat + $from->lng) === 0 or ($to->lat + $to->lng) === 0)        return false;    $fromLat    = deg2rad( (float)$from->lat );    $fromLng    = deg2rad( (float)$from->lng );    $tolat      = deg2rad( (float)$to->lat );    $toLng      = deg2rad( (float)$to->lng );    $dist = 6371 * acos( cos($fromLat) * cos($tolat) * cos($toLng - $fromLng) + sin($fromLat) * sin($tolat) );    if($restrict > -1)        if($dist < $restrict)            return $dist;        else            return false;    else         return $dist;    }