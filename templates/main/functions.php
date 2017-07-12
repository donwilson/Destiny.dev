<?php
	require_once(CLASS_DIR ."api/xbox/xbox_api.php");
	require_once(CLASS_DIR ."api/destiny/destiny_api.php");
	require_once(CLASS_DIR ."api/destiny/destiny_manifest.php");
	
	function get_current_manifest() {
		$current_manifest_data = get_var("
			SELECT
				setting.meta_value
			FROM `setting`
			WHERE
				setting.meta_key = 'current_manifest'
		");
		
		$current_manifest = ((!is_null($current_manifest_data) && !empty($current_manifest_data))?json_decode($current_manifest_data, true):array());
		
		if(!isset($current_manifest['data'])) {
			return array();
		}
		
		return $current_manifest['data'];
	}