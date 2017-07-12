<?php
	function determineValue($requestKey = false, $acceptedKeys = false, $defaultKey = false) {
		$key = $defaultKey;
		$requestedValue = (isset($_REQUEST[ $requestKey ])?strtolower($_REQUEST[ $requestKey ]):false);
		
		if($requestedValue !== false) {
			if(in_array($requestedValue, $acceptedKeys)) {
				$key = $requestedValue;
			}
		}
		
		return $key;
	}
	
	function extractDomain($url) {
		// find domain from URL string
		$domain = $url;
		$domain = preg_replace("#^https?\://(?:www\.)?([^/]+?)(/(?:.*)$|$)#si", "$1", $domain);
		$domain = preg_replace("#^www\.#si", "", $domain);
		$domain = trim($domain, "/");
		$domain_bits = explode("/", $domain);
		$domain = strtolower($domain_bits[0]);
		
		return $domain;
	}
	
	function extractLocalDomain() {
		// find local domain
		return extractDomain(ABS_URI);
	}
	
	function redirect_to($address) {
		header("Location: ". $address);
		
		die;
	}