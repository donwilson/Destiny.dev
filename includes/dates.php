<?php
	/**
	 * Return a human friendly "time since X" string (eg '3 hours ago')
	 * @param string|int $timestamp Timestamp or machine readable date (via strtotime)
	 * @param string $append Optional. Appended string when friendly string is generated
	 * @param string $prepend Optional. Prepended string when friendly string is generated
	 * @return string
	 */
	function get_date_since($timestamp, $append=" ago", $prepend="") {
		if(!preg_match("#^([0-9]+)$#si", $timestamp)) {
			$timestamp = strtotime($timestamp);
		}
		
		if(empty($timestamp)) {
			return false;
		}
		
		$date_diff = (time() - $timestamp);
		
		$ranges = array(
			'millennium'	=> 1 * 60 * 60 * 24 * 365 * 1000,
			'century'		=> 1 * 60 * 60 * 24 * 365 * 100,
			'decade'		=> 1 * 60 * 60 * 24 * 365 * 10,
			'year'			=> 1 * 60 * 60 * 24 * 365,
			'month'			=> 1 * 60 * 60 * 24 * 30,
			'week'			=> 1 * 60 * 60 * 24 * 7,
			'day'			=> 1 * 60 * 60 * 24,
			'hour'			=> 1 * 60 * 60,
			'minute'		=> 1 * 60,
			'second'		=> 1,
		);
		
		$return = false;
		
		foreach($ranges as $name => $seconds) {
			if($date_diff >= $seconds) {
				if($name == "second") {
					return "just now";
				}
				
				$div = floor( @($date_diff / $seconds) );
				
				return (("" !== $prepend)?$prepend:"") . $div ." ". $name . (($div <> 1)?"s":"") . ((!empty($append))?$append:"");
			}
		}
		
		return false;
	}	function date_since($timestamp, $append=" ago", $prepend=false) { print get_date_since($timestamp, $append, $prepend); }
	
	function get_time_display($seconds) {
		$hours = 0;
		$minutes = 0;
		
		if(!empty($seconds)) {
			$minutes = floor( ($seconds / 60) );
			$seconds %= 60;
			
			if($minutes >= 60) {
				$hours = floor( ($minutes / 60) );
				$minutes %= 60;
			}
		}
		
		if(!empty($hours)) {
			return $hours .":". str_pad($minutes, 2, STR_PAD_LEFT, "0") .":". str_pad($seconds, 2, STR_PAD_LEFT, "0");
		}
		
		return $minutes .":". str_pad($seconds, 2, STR_PAD_LEFT, "0");
	}