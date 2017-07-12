<?php
	function getAllConfig($force=false) {
		return cache_get('site_config', function() {
			$values_by_key = array();
			$rows = get_rows("SELECT `key`, `value` FROM `config`");
			
			if(!empty($rows) && !is_null($rows)) {
				foreach($rows as $row) {
					$values_by_key[ $row['key'] ] = $row['value'];
				}
			}
			
			return $values_by_key;
		}, 3600, $force);
	}
	
	function getConfig($key, $default=false, $force=false) {
		if("" === ($key = strtolower(trim($key)))) {
			return $default;
		}
		
		$config_values = getAllConfig($force);
		
		if(isset($config_values[ $key ])) {
			return $config_values[ $key ];
		}
		
		return $default;
	}
	
	function setConfig($key, $value) {
		if("" === ($key = strtolower(trim($key)))) {
			return false;
		}
		
		if(is_array($value)) {
			$value = json_encode($value);
		}
		
		db_query("
			INSERT INTO `config`
			SET
				`key` = '". esc_sql($key) ."',
				`value` = '". esc_sql($value) ."'
			ON DUPLICATE KEY UPDATE
				`value` = VALUES(`value`)
		");
		
		if(function_exists("cache_delete")) {
			cache_delete('site_config');
		}
		
		return true;
	}
	
	function deleteConfig($key) {
		if("" === ($key = strtolower(trim($key)))) {
			return false;
		}
		
		db_query("DELETE FROM `config` WHERE `key` = '". esc_sql($key) ."'");
		
		if(function_exists("cache_delete")) {
			cache_delete('site_config');
		}
		
		return true;
	}