<?php
	function is_mobile_browser() {
		global $cache;
		
		if(!isset($cache['is_mobile_browser'])) {
			@include_once(LIB_DIR ."Mobile-Detect-2.8.19/Mobile_Detect.php");
			
			try {
				if(!class_exists("Mobile_Detect")) {
					throw new Exception("Class 'Mobile_Detect' not defined.");
				}
				
				$detect = new Mobile_Detect;
				
				$cache['is_mobile_browser'] = $detect->isMobile() && !$detect->isTablet();
			} catch(Exception $e) {
				$cache['is_mobile_browser'] = false;
			}
		}
		
		return $cache['is_mobile_browser'];
	}