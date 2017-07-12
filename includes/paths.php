<?php
	function home_url() {
		return ABS_URI;
	}
	
	function site_url($url) {
		if(ABS_URI !== substr($url, 0, strlen(ABS_URI))) {
			$url = preg_replace("#^https?\://([^/]+)/#si", "", $url);   // remove domain
			$url = ltrim($url, "/");
			
			$url = ABS_URI . $url;
		}
		
		return $url;
	}
	
	function admin_url($url) {
		if(ADMIN_ABS_URI !== substr($url, 0, strlen(ADMIN_ABS_URI))) {
			$url = preg_replace("#^https?\://([^/]+)/#si", "", $url);   // remove domain
			$url = preg_replace("#^/?admin/#si", "", $url);   // remove /admin/
			$url = ltrim($url, "/");
			
			$url = ADMIN_ABS_URI . $url;
		}
		
		//if(!preg_match("#^https?\:\/\/#si", $url)) {
		//	$url = ADMIN_ABS_URI . ltrim($url, "/");
		//}
		//
		//$url = preg_replace("#^https?://(?:www\.)domain\.com/#si", ADMIN_ABS_URI, $url);
		
		return $url;
	}
	
	function cdn_url($url) {
		if(CDN_URI !== substr($url, 0, strlen(CDN_URI))) {
			$url = preg_replace("#^https?\://([^/]+)/#si", "", $url);   // remove domain
			$url = preg_replace("#^/?cdn/#si", "", $url);   // remove /cdn/
			$url = ltrim($url, "/");
			
			$url = CDN_URI . $url;
		}
		
		return $url;
	}
	
	function static_url($url) {
		if(STATIC_URI !== substr($url, 0, strlen(STATIC_URI))) {
			$url = preg_replace("#^https?\://([^/]+)/#si", "", $url);   // remove domain
			$url = preg_replace("#^/?static/#si", "", $url);   // remove /static/
			$url = ltrim($url, "/");
			
			$url = STATIC_URI . $url;
		}
		
		//if("104.189.159.194" === getenv("REMOTE_ADDR")) {
		//	$url = preg_replace("#static\.domain\.com/#si", "domain.com/static/", $url);
		//	$url = preg_replace("#m=([0-9]+)#si", "m=". microtime(true) . time(), $url);
		//}
		
		return $url;
	}