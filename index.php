<?php
	@include_once(__DIR__ ."/config.php");
	
	if(defined('DEV_MODE') && DEV_MODE) {
		// prevent browser cache
		// used only for development
		header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
	}
	
	// determine the template
	// determine the page
	$section = determineValue('_section_', array(
		"frontpage",
		
		"characters",
		"character",
		
		"clips",
		"clip",
		
		"weapons",
		"weapon",
		
		"armor",
		
		"debug_databases",
		"debug_database_tables",
		"debug_database_table",
		"debug_database_table_row",
	), "frontpage");
	
	define('TEMPLATES', TEMPLATE_DIR ."main/");
	
	// init()
	try {
		@include_once(TEMPLATES ."functions.php");
		
		if(false === display_tpl($section)) {
			throw new Exception("Template not found.");
		}
	} catch (Exception $e) {
		if(!display_tpl("404")) {
			die("There was an error processing your request: ". $e->getMessage());
		}
	}