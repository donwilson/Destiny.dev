<?php
	function getGAClientId() {
		@include_once(INCLUDE_DIR ."uuid.php");
		
		$client_id = false;
		
		if(!empty($_COOKIE['client_id']) && preg_match("#^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$#si", $_COOKIE['client_id'])) {
			$client_id = $_COOKIE['client_id'];
		}
		
		if(false === $client_id) {
			$client_id = UUID::v4();
		}
		
		if(!isset($_COOKIE['client_id']) || ($_COOKIE['client_id'] !== $client_id)) {
			$_COOKIE['client_id'] = $client_id;
			
			@setcookie('client_id', $client_id, (time() + (60 * 60 * 24 * 30)), "/", extractLocalDomain());
		}
		
		return $client_id;
	}