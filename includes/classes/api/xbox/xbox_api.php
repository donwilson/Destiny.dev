<?php
	// https://xboxapi.com/documentation
	
	class Microsoft_Xbox_API {
		private $api_key = "";
		private $api_base = "";
		
		private $debug = array();
		
		private $is_post = false;
		private $params = array();
		private $endpoint = "";
		
		private $called_api = false;
		private $response_data = null;
		
		public function __construct($api_key, $api_base) {
			$this->api_key = $api_key;
			$this->api_base = rtrim($api_base, "/");
			
			return $this;
		}
		
		public function setAPIKey($api_key="") {
			$this->api_key = $api_key;
			
			return $this;
		}
		
		public function setAPIBase($api_base="") {
			$this->api_base = $api_base;
			
			return $this;
		}
		
		/**
		 * Set the endpoint, with an optional endpoint replacement logic. Example of deliminated URL variable endpoint is "/{paramName}/path/{anotherParamName}/" with paramName and anotherParamName as keys in $replacements.
		 * @param type|string $endpoint Partial API endpoint with optional 
		 * @param array $replacements Optional. Values to replace deliminated URL variables
		 * @return instance
		 */
		public function setEndpoint($endpoint="", $replacements=array()) {
			$this->endpoint = ltrim($endpoint, "/");
			
			if(!empty($replacements)) {
				foreach($replacements as $replace_param => $replace_value) {
					$this->endpoint = str_replace("{". $replace_param ."}", $replace_value, $this->endpoint);
				}
			}
			
			if(false !== strpos($this->endpoint, "?")) {
				$bits = explode("?", $this->endpoint, 2);
				
				$this->endpoint = $bits[0];
				
				if("" !== ($extra_param_query = trim($bits[1], "?&"))) {
					parse_str($extra_param_query, $extra_params);
					
					if(!empty($extra_params) && is_array($extra_params)) {
						$this->params = array_merge($this->params, $extra_params);
					}
				}
			}
			
			return $this;
		}
		
		public function exec() {
			$url = $this->api_base ."/". $this->endpoint;
			
			$this->debug[] = "Changed URL to ". $url;
			
			$ch = curl_init();
			
			curl_setopt_array($ch, array(
				CURLOPT_FOLLOWLOCATION	=> true,
				CURLOPT_RETURNTRANSFER	=> true,
				CURLOPT_CONNECTTIMEOUT	=> 20,
				CURLOPT_MAXREDIRS		=> 10,
				CURLOPT_TIMEOUT			=> 60,
				CURLOPT_URL				=> $url,
				//CURLOPT_USERAGENT		=> "",
				CURLOPT_HTTPHEADER		=> array(
					"X-AUTH: ". $this->api_key,
				),
			));
			
			if(!empty($this->is_post)) {
				curl_setopt_array($ch, array(
					CURLOPT_POST		=> true,
					CURLOPT_POSTFIELDS	=> $this->params,
				));
			} else {
				if(!empty($this->params)) {
					$appended_url = $this->api_base ."/". $this->endpoint ."?". http_build_query($this->params);
					
					curl_setopt($ch, CURLOPT_URL, $appended_url);
					
					$this->debug[] = "Changed URL to ". $appended_url;
				}
			}
			
			$this->response_data = curl_exec($ch);
			
			$this->debug[] = "Contents: ". $this->response_data;
			
			$curl_debug = curl_getinfo($ch);
			
			curl_close($ch);
			
			// convert from JSON when necessary
			if(in_array(substr($this->response_data, 0, 1), array("[", "{"))) {
				$this->response_data = json_decode($this->response_data, true);
			}
			
			return $this;
		}
		
		public function getResponse() {
			return $this->response_data;
		}
		
		public function getDebug() {
			return $this->debug;
		}
	}