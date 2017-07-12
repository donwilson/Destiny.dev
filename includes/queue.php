<?php
	function enqueue_to_body_prepend($string="") {
		global $cache;
		
		if("" === ($string = trim($string))) {
			return;
		}
		
		if(!isset($cache['_body_prepend'])) {
			$cache['_body_prepend'] = array();
		}
		
		$cache['_body_prepend'][] = $string;
	}
	
	function enqueue_to_body_append($string="") {
		global $cache;
		
		if("" === ($string = trim($string))) {
			return;
		}
		
		if(!isset($cache['_body_append'])) {
			$cache['_body_append'] = array();
		}
		
		$cache['_body_append'][] = $string;
	}
	
	function enqueue_to_head_append($string="") {
		global $cache;
		
		if("" === ($string = trim($string))) {
			return;
		}
		
		if(!isset($cache['_head_append'])) {
			$cache['_head_append'] = array();
		}
		
		$cache['_head_append'][] = $string;
	}
	
	function prepend_to_body() {
		global $cache;
		
		if(!isset($cache['_body_prepend']) || empty($cache['_body_prepend'])) {
			return;
		}
		
		print implode("\n", $cache['_body_prepend']);
		
		unset($cache['_body_prepend']);
	}
	
	function append_to_body() {
		global $cache;
		
		if(!isset($cache['_body_append']) || empty($cache['_body_append'])) {
			return;
		}
		
		print implode("\n", $cache['_body_append']);
		
		unset($cache['_body_append']);
	}
	
	function append_to_head() {
		global $cache;
		
		if(!isset($cache['_head_append']) || empty($cache['_head_append'])) {
			return;
		}
		
		print implode("\n", $cache['_head_append']);
		
		unset($cache['_head_append']);
	}