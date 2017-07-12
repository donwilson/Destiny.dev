<?php
	function print_pre($var) {
		if(is_bool($var)) {
			$var = "(print_pre: BOOLEAN) ". ($var?"TRUE":"FALSE");
		} elseif(is_array($var) || is_object($var)) {
			$var = print_r($var, 1);
		}
		
		print "<pre>". esc_html($var) ."</pre>\n";
	}
	
	function dev_mode_enabled() {
		return (defined('DEV_MODE') && DEV_MODE);
	}