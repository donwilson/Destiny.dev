<?php
	function mkurl($string, $separator="-", $lowercase=true) {
		if(!empty($lowercase)) {
			$string = strtolower($string);
		}
		
		$string = trim($string);
		$string = preg_replace("#([^A-Za-z0-9". (("-" !== $separator)?preg_quote($separator, "#"):"") ."])#si", $separator, $string);
		$string = preg_replace("#". preg_quote($separator, "#") ."(". preg_quote($separator, "#") ."+)#si", $separator, $string);
		$string = trim($string, $separator);
		
		return $string;
	}
	
	function highlight_string_in_text($string="", $text="") {
		if("" === ($string = strtolower(trim($string)))) {
			return $text;
		}
		
		$strings = explode(" ", $string);
		$strings = array_unique($strings);
		
		foreach($strings as $string) {
			if(in_array($string, array("strong", "href", "src"))) {
				continue;
			}
			
			$text = preg_replace("#(". preg_quote($string, "#") .")#si", "<strong>\\1</strong>", $text);
		}
		
		return $text;
	}
	
	// http://stackoverflow.com/a/14167216/103337
	function formatPhoneNumber($phoneNumber) {
		$phoneNumber = preg_replace('/[^0-9]/','',$phoneNumber);
		
		if(strlen($phoneNumber) > 10) {
			$countryCode = substr($phoneNumber, 0, strlen($phoneNumber)-10);
			$areaCode = substr($phoneNumber, -10, 3);
			$nextThree = substr($phoneNumber, -7, 3);
			$lastFour = substr($phoneNumber, -4, 4);
			
			$phoneNumber = '+'.$countryCode.' ('.$areaCode.') '.$nextThree.'-'.$lastFour;
		} elseif(strlen($phoneNumber) == 10) {
			$areaCode = substr($phoneNumber, 0, 3);
			$nextThree = substr($phoneNumber, 3, 3);
			$lastFour = substr($phoneNumber, 6, 4);
			
			$phoneNumber = '('.$areaCode.') '.$nextThree.'-'.$lastFour;
		} elseif(strlen($phoneNumber) == 7) {
			$nextThree = substr($phoneNumber, 0, 3);
			$lastFour = substr($phoneNumber, 3, 4);
			
			$phoneNumber = $nextThree.'-'.$lastFour;
		}
		
		return $phoneNumber;
	}
	
	function clean_and_paragraph_text($string, $strip_html=true, $escape_html=true) {
		$string = trim($string);
		
		$strings = preg_split("#\n{1,}#si", $string, -1, PREG_SPLIT_NO_EMPTY);
		
		if($strip_html) {
			$strings = array_map("strip_tags", $strings);
		}
		
		if($escape_html) {
			$strings = array_map("esc_html", $strings);
		}
		
		return "<p>". implode("</p><p>", $strings) ."</p>";
	}