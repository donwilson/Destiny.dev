<?php
	// nonce library
	// http://fullthrottledevelopment.com/php-nonce-library
	
	define('FT_NONCE_UNIQUE_KEY',	"Ox11458bt561454Q");
	define('FT_NONCE_DURATION',		(60 * 30));   // number of seconds the link or form good for from time of generation
	define('FT_NONCE_KEY',			"_nonce");
	
	// generates the nonce timestamp
	function ft_nonce_generate_hash($action="", $user="") {
		return md5(ceil((time() / (FT_NONCE_DURATION / 2))) . $action . $user . $action);
	}
	
	// creates an nonce
	function ft_nonce_create($action="", $user="") {
		return substr(ft_nonce_generate_hash($action . $user), -12, 10);
	}
	
	// creates a param/value pair for a url string
	function ft_nonce_create_query_string($action="", $user="") {
		return FT_NONCE_KEY ."=". ft_nonce_create($action, $user);
	}
	
	// creates an nonce for a form field
	function ft_nonce_create_form_input($action="", $user="") {
		print "<input type=\"hidden\" name=\"". FT_NONCE_KEY ."\" value=\"". ft_nonce_create($action . $user) ."\" />";
	}
	
	// validates an nonce
	function ft_nonce_is_valid($nonce, $action="", $user="") {
		// Nonce generated 0-12 hours ago
		return (substr(ft_nonce_generate_hash($action . $user), -12, 10) == $nonce);
	}