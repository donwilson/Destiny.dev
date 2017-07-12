<?php
	function verify_recaptcha($response=false) {
		if(!defined('RECAPTCHA_KEY') || !defined('RECAPTCHA_SECRET') || !RECAPTCHA_KEY || !RECAPTCHA_SECRET) {
			return true;
		}
		
		if(false === $response) {
			if(!isset($_POST['g-recaptcha-response'])) {
				return false;
			}
			
			$response = $_POST['g-recaptcha-response'];
		}
		
		$ch = curl_init();
		
		curl_setopt_array($ch, array(
			CURLOPT_FOLLOWLOCATION	=> true,
			CURLOPT_POST			=> true,
			CURLOPT_RETURNTRANSFER	=> true,
			//CURLOPT_SSL_VERIFYPEER	=> false,
			CURLOPT_CONNECTTIMEOUT	=> 20,
			CURLOPT_TIMEOUT			=> 30,
			CURLOPT_POSTFIELDS		=> array(
				'secret'	=> RECAPTCHA_SECRET,
				'response'	=> $response,
				'remoteip'	=> getenv("REMOTE_ADDR"),
			),
			CURLOPT_URL				=> "https://www.google.com/recaptcha/api/siteverify",
		));
		
		$data = curl_exec($ch);
		
		curl_close($ch);
		
		$data_decoded = json_decode($data, true);
		
		return !empty($data_decoded['success']);
	}