<?php
	function display_tpl($tpl_name) {
		if(false === strpos($tpl_name, ".php")) {
			$tpl_name = $tpl_name .".php";
		}
		
		$templates_base = rtrim(realpath(TEMPLATES), "/\\") ."/";
		$requested_tpl_path = realpath($templates_base . $tpl_name);
		
		try {
			if($templates_base !== substr($requested_tpl_path, 0, strlen($templates_base))) {
				throw new Exception("Template '". esc_html($tpl_name) ."' improperly formatted");
			}
			
			if(!file_exists($requested_tpl_path)) {
				throw new Exception("Template '". esc_html($tpl_name) ."' not found");
			}
			
			return include($requested_tpl_path);
		} catch(Exception $e) {
			if(function_exists("dev_mode_enabled") && dev_mode_enabled()) {
				print "<div class=\"alert alert-danger\">". $e->getMessage() ."</div><br />\n";
			}
			
			return false;
		}
	}