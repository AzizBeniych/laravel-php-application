<?php

if (!function_exists('hex_to_bin')) {
	/**
	 * Converts hex-string to binary string.
	 *
	 * @param  string $str
	 * @return string Binary string.
	 */
	function hex_to_bin($str)
	{
		if (strlen($str) % 2 > 0)
			$str = '0' . $str;

		return function_exists('hex2bin') ? hex2bin($str) : pack('H*', $str);
	}
}

if (!function_exists('make_unique_code')) {
	/**
	 * Creates an unique string.
	 *
	 * @param  boolean $case_sensitive
	 * @param  int $more_entropy
	 * @return string
	 * @see hex_to_bin()
	 */
	function make_unique_code($case_sensitive = false, $more_entropy = 0)
	{
		$uid = str_replace('.', '', uniqid('', $more_entropy));
		if (!$case_sensitive) {
			$uid = base_convert($uid, 16, 36);

			return ($more_entropy > 1) ? base_convert(mt_rand(), 10, 36) . $uid : $uid;
		}

		$uid = hex_to_bin($uid);
		if ($more_entropy > 1)
			$uid = hex_to_bin(dechex(mt_rand())) . $uid;

		return str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($uid));
	}
}

if (!function_exists('random_password')) {
	/**
	 * @param  int $length
	 * @param  bool $entropy TRUE for more unique.
	 * @return string
	 */
	function random_password($length = 8, $entropy = false)
	{
		static $chars = '!@#$%^&*';

		$s = str_replace('.', '', uniqid('', !$entropy));
		$s = base64_encode(hex_to_bin($s));
		$s = str_replace(array('+', '/', '='), '', substr($s, 1 - $length));

		while (($l = strlen($s)) < $length) {
			$p = mt_rand(0, $l);
			$i = mt_rand(0, 7); //strlen($chars) - 1
			$s = substr($s, 0, $p) . $chars[$i] . substr($s, $p);
		}

		return $s;
	}
}

if (!function_exists('mb_trim')) {
	/**
	 * @param  string $str
	 * @return string
	 */
	function mb_trim($str)
	{
		return preg_replace('/^\s+|\s+$/u', '', $str);
	}
}

if (!function_exists('mb_ucfirst')) {
	/**
	 * @param  string $str
	 * @return string
	 */
	function mb_ucfirst($str)
	{
		return !function_exists('mb_substr') ? ucfirst($str) : mb_strtoupper(mb_substr($str, 0, 1)) . mb_substr($str, 1);
	}
}

if (!function_exists('full_name')) {
	/**
	 * Builds full-name from first and last name.
	 *
	 * @param  string $first
	 * @param  string $last
	 * @param  string $middle
	 * @return string
	 */
	function full_name($first, $last, $middle = '')
	{
		$s = $first ?: '';
		if (!empty($middle)) $s .= ' ' . $middle;
		if (!empty($last)) $s .= ' ' . $last;

		return $s;
	}
}

if (!function_exists('remove_whitespaces')) {
	/**
	 * @param  string $str
	 * @param  string $spacer
	 * @return string
	 */
	function remove_whitespaces($str, $spacer = ' ')
	{
		return preg_replace('/\s\s+/u', $spacer, mb_trim($str));
	}
}

if (!function_exists('repair_search_query')) {
	/**
	 * Used for MySQL full-text search.
	 *
	 * @param  string $keyword
	 * @return string
	 */
	function repair_search_query($keyword)
	{
		$keyword = trim(preg_replace('/[^\p{L}\p{N}_-]+/u', ' ', $keyword));
		$keyword = preg_replace('/\s+(?=[\p{L}\p{N}_])/u', ' +', ' ' . $keyword);
		return trim(str_replace(' +-', ' -', $keyword));
	}
}

if (!function_exists('is_true')) {
	/**
	 * @param  string $val
	 * @param  boolean $return_null
	 * @return boolean|null
	 */
	function is_true($val, $return_null = false)
	{
		$b = ($val === null) ? null
			: (is_string($val) ? filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : (bool)$val);
		return ($b === null && !$return_null) ? false : $b;
	}
}

if (!function_exists('is_alpha_dash')) {
	/**
	 * @param  string $val
	 * @return string
	 */
	function is_not_empty($val)
	{
		return isset($val) && '' !== $val;
	}

	/**
	 * @param  string $val
	 * @return string
	 */
	function is_alpha_dash($val)
	{
		return isset($val) && preg_match('/^[\w-]+$/', $val);
	}
}

if (!function_exists('make_real_url')) {
	/**
	 * @param  string $url
	 * @param  string $protocol
	 * @return string
	 */
	function make_real_url($url, $protocol = 'http')
	{
		if (!empty($url) && !preg_match('#^[A-Za-z]+://#i', $url)) {
			return $protocol . ':' . (substr($url, 0, 2) != '//' ? '//' : '') . $url;
		}
		return $url;
	}
}

if (!function_exists('is_url_match')) {
	/**
	 * @param  string $haystack
	 * @param  string $needle
	 * @return bool
	 */
	function is_url_match($haystack, $needle)
	{
		return preg_match('#' . preg_quote($needle, '#') . '(?:\?|/?$)#i', $haystack);
	}
}

if (!function_exists('html_count_words')) {
	/**
	 * Counts number of words from a HTML content.
	 *
	 * @param  string $html
	 * @return int|null
	 */
	function html_count_words($html)
	{
		// remove html tags, space chars, and punctuations
		$html = preg_replace(['/<.[^<>]*?>/', '/&nbsp;|&#160;/i', '/[.(),;:!?%#$\'"_+=\/\-“”’]*/u'], [' ', ' ', ''], $html);

		return preg_match_all('/\b\w+\b/', $html, $m, PREG_SET_ORDER) ? count($m) : null;
	}
}

if (!function_exists('purify_html')) {
	/**
	 * Gets content inside HTML &lt;body&gt; tag.
	 *
	 * @param  string $html
	 * @return string
	 */
	function get_html_body($html)
	{
		return trim(preg_replace('#^.*<body(\s[^>]*)?>|</body>.*$#isu', '', $html));
	}

	/**
	 * Semi-clean dirty HTML.
	 *
	 * @param  string $html
	 * @param  boolean $multiline
	 * @param  string $encoding
	 * @return string
	 */
	function purify_html($html, $multiline = false, $encoding = 'UTF-8')
	{
		if (empty($html) || !($html = get_html_body($html)))
			return $html;

		// normalize HTML
		$dom = new \DOMDocument('1.0', $encoding);
		$dom->recover = true;
		@$dom->loadHTML('<body>' . mb_convert_encoding($html, 'HTML-ENTITIES', $encoding) . '</body>');

		/** @noinspection SpellCheckingInspection */
		static $inline_tags = array('html', 'body', 'abbr', 'address', 'b', 'cite', 'code', 'del', 'em', 'i', 'ins', 'samp', 'small', 'strong', 'sub', 'sup', 'u', 'img', 'a', 'span', 'br');
		/** @noinspection SpellCheckingInspection */
		static $block_tags = array('h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'hr', 'blockquote', 'pre', 'ul', 'ol', 'li', 'dl', 'dt', 'dd', 'table', 'th', 'tr', 'td', 'thead', 'tbody', 'tfoot', 'video', 'source', 'div', 'section', 'article', 'aside', 'embed', 'object', 'param');

		// clean dirty tags
		foreach ((new \DOMXpath($dom))->query('//node()') as $node) {
			/* @var $node \DOMNode */
			if ($node->nodeType != XML_TEXT_NODE) {
				if (!in_array($node->nodeName, $inline_tags) && (!$multiline || !in_array($node->nodeName, $block_tags))) {
					$node->parentNode->removeChild($node);

				} elseif ($node->hasAttributes()) {
					/* @var $attr \DOMAttr */
					foreach ($node->attributes as $attr) {
						if (stripos($attr->name, 'on') === 0 || stripos($attr->name, 'data') === 0 || stripos($attr->value, 'javascript') === 0)
							$attr->ownerElement->removeAttribute($attr->name);
					}
				}
			}
		}

		$html = get_html_body($dom->saveHTML($dom->documentElement));
		unset($dom);

		// break newlines
		$arr = preg_split('/(<[^>]+>)/', $html, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
		unset($html);

		foreach ($arr as &$s) {
			if ($s[0] != '<' || substr($s, -1) != '>') {
				// NOTE: decode HTML entities
				//$s = htmlspecialchars(html_entity_decode($s, ENT_COMPAT, $encoding), ENT_COMPAT, $encoding);
				$s = !preg_match('/[^\s]/u', $s) ? '' : nl2br($s);
			}
		}

		return implode('', $arr);
	}
}

if (!function_exists('try_make_dir')) {
	/**
	 * @param string $path
	 * @param int $mode
	 * @return bool
	 */
	function try_make_dir($path, $mode = 0777)
	{
		return is_dir($path) ? true : @mkdir($path, $mode, true); // true for recursive create
	}
}

if (!function_exists('delete_file_match')) {
	/**
	 * @param string $pattern The glob pattern.
	 */
	function delete_file_match($pattern)
	{
		$a = glob($pattern);
		if (!empty($a)) {
			foreach ($a as $filename)
				@unlink($filename);
		}
	}
}

if (!function_exists('del_tree')) {
	/**
	 * @param string $dir
	 * @return bool
	 */
	function del_tree($dir)
	{
		foreach (scandir($dir) as $file) {
			if ($file == '.' || $file == '..')
				continue;

			if (is_dir($path = "$dir/$file")) {
				if (!del_tree($path)) return false;
			} else {
				if (!@unlink($path)) return false;
			}
		}
		return @rmdir($dir);
	}
}

if (!function_exists('rename_if_exists')) {
	/**
	 * @param string $dir
	 * @param string $filename
	 * @return string The new filename.
	 */
	function rename_if_exists($dir, $filename)
	{
		if (file_exists("$dir/$filename")) {
			$suffix = '_' . base_convert(uniqid(), 16, 36);

			$i = strrpos($filename, '.');
			if ($i === false)
				$filename .= $suffix;
			else
				$filename = substr($filename, 0, $i) . $suffix . substr($filename, $i);
		}

		return $filename;
	}
}

if (!function_exists('invoke_member')) {
	/**
	 * @param  object $obj
	 * @param  string $memberName
	 * @return mixed
	 */
	function invoke_member($obj, $memberName)
	{
		if (substr($memberName, -2) == '()')
			return $obj->{substr($memberName, 0, -2)}();
		else
			return $obj->{$memberName};
	}
}

if (!function_exists('format_state')) {
	/**
	 * @param  string $stateCode
	 * @param  bool $returnName To return display name of the state.
	 * @return string
	 */
	function format_state($stateCode, $returnName = false)
	{
		if ($returnName) {
			$name = Config::get('settings.state_options.United States.' . $stateCode);
			if (empty($name)) $name = Config::get('settings.state_options.Canada.' . $stateCode);

			if (!empty($name)) return $name;
		}
		return preg_replace('/^[A-Za-z]{2}-/', '', $stateCode);
	}
}

if (!function_exists('format_phone')) {
	/**
	 * @param string $str
	 * @param string $sep Separator
	 * @return string
	 */
	function format_phone($str, $sep = null)
	{
		// remove country code
		$str = preg_replace('/^\s*\+\s*\d+[^\d\(]+/', '', $str);
		return ($sep === null) ? $str : trim(preg_replace('/(^|\d+)\s*[^\d\s]\s*/', '$1' . $sep, $str), $sep);
	}
}

if (!function_exists('redirect_back')) {
	/**
	 * Redirect to the previous URL.
	 *
	 * @param  string $route Default route name.
	 * @param  array $parameters Default route parameters.
	 * @return \Illuminate\Http\RedirectResponse
	 */
	function redirect_back($route = 'home', $parameters = array())
	{
		/** @noinspection SpellCheckingInspection */
		return Input::has('ret') ? Redirect::to(Input::get('ret'))
			: (($back = Request::header('referer')) ? Redirect::away($back) : Redirect::route($route, $parameters));
	}

	/**
	 * Returns the previous URL.
	 *
	 * @param  string $route Default route name.
	 * @param  array $parameters Default route parameters.
	 * @return string
	 */
	function previous_url($route = 'home', $parameters = array())
	{
		/** @noinspection SpellCheckingInspection */
		return Input::get('ret', ($back = Request::header('referer')) ? URL::to($back) : URL::route($route, $parameters));
	}
}
