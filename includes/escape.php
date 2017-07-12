<?php
	function esc_attr($string) {
		$string = htmlentities($string, ENT_QUOTES, 'UTF-8');
		return $string;
	}
	
	function esc_html($string) {
		$string = htmlentities($string, ENT_COMPAT, 'UTF-8');
		return $string;
	}
	
	// via http://core.trac.wordpress.org/browser/tags/3.4.2/wp-includes/formatting.php#L2546
	function esc_url($url, $protocols = null) {
		$original_url = $url;
		
		if(empty($url)) {
			return $url;
		}
		
		$url = preg_replace('|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\\x80-\\xff]|i', '', $url);
		$strip = array('%0d', '%0a', '%0D', '%0A');
		$url = str_replace($strip, "", $url);
		$url = str_replace(';//', '://', $url);
		
		if(strpos($url, ':') === false && !in_array($url[0], array( '/', '#', '?')) && !preg_match('/^[a-z0-9-]+?\.php/i', $url)) {
			$url = 'http://' . $url;
		}
		
		return $url;
	}
	
	function esc_tag($string) {
		$string = strtolower(preg_replace("#[^A-Za-z0-9_:]#i", "", $string));
		return $string;
	}