<?php
	require_once(__DIR__ ."/../config.php");
	
	set_time_limit(0);
	error_reporting(E_ALL);
	
	$current_manifest_raw = get_var("
		SELECT
			setting.meta_value
		FROM `setting`
		WHERE
			setting.meta_key = 'current_manifest'
	");
	
	$current_manifest = ((!empty($current_manifest_raw) && !is_null($current_manifest_raw))?json_decode($current_manifest_raw, true):array());
	
	$attempts = 0;
	$live_manifest = false;
	
	do {
		$attempts++;
		
		// https://www.bungie.net/platform/Destiny/Manifest/
		$live_manifest_raw_text = file_get_contents("https://www.bungie.net/platform/Destiny/Manifest/");
		$live_manifest_raw = json_decode($live_manifest_raw_text, true);
		
		if(!empty($live_manifest_raw['Response']['version'])) {
			$live_manifest = $live_manifest_raw['Response'];
		}
	} while(empty($live_manifest['version']) && ($attempts < 3));
	
	if(empty($live_manifest['version'])) {
		die("Version not found in live manifest\nlive_manifest_raw_text = ". $live_manifest_raw_text ."\n\n");
	}
	
	$manifest_updated = false;
	
	if(empty($current_manifest['version']) || ($current_manifest['version'] != $live_manifest['version'])) {
		$manifest_updated = true;
	}
	
	if(!is_dir(MANIFEST_DIR . $live_manifest['version'])) {
		print "Creating dir: ". MANIFEST_DIR . $live_manifest['version'] ."\n";
		
		mkdir(MANIFEST_DIR . $live_manifest['version'], 0777);
		
		$manifest_updated = true;
	}
	
	// scan through local content paths
	$local_db_paths = array();
	
	if(!empty($current_manifest['mobileAssetContentPath'])) {
		$local_db_paths[] = $current_manifest['mobileAssetContentPath'];
	}
	
	if(!empty($current_manifest['mobileGearAssetDataBases'])) {
		foreach($current_manifest['mobileGearAssetDataBases'] as $asset_db) {
			$local_db_paths[] = $asset_db['path'];
		}
	}
	
	if(!empty($current_manifest['mobileWorldContentPaths'])) {
		foreach($current_manifest['mobileWorldContentPaths'] as $content_lang => $content_path) {
			$local_db_paths[] = $content_path;
		}
	}
	
	$local_db_paths = array_unique($local_db_paths);
	
	
	// scan through live content paths
	$live_db_paths = array();
	
	if(!empty($live_manifest['mobileAssetContentPath'])) {
		$live_db_paths[] = $live_manifest['mobileAssetContentPath'];
	}
	
	if(!empty($live_manifest['mobileGearAssetDataBases'])) {
		foreach($live_manifest['mobileGearAssetDataBases'] as $asset_db) {
			$live_db_paths[] = $asset_db['path'];
		}
	}
	
	if(!empty($live_manifest['mobileWorldContentPaths'])) {
		foreach($live_manifest['mobileWorldContentPaths'] as $content_lang => $content_path) {
			$live_db_paths[] = $content_path;
		}
	}
	
	$live_db_paths = array_unique($live_db_paths);
	
	// download any live content files if necessary
	$zip = new ZipArchive();
	
	foreach($live_db_paths as $live_db_path) {
		$live_filename = basename($live_db_path);
		$live_rel_path = MANIFEST_DIR . $live_manifest['version'] ."/". $live_filename;
		
		if(!file_exists($live_rel_path)) {
			print "Downloading ". $live_filename ."... ";
			
			file_put_contents($live_rel_path .".zip", file_get_contents(DESTINY_CONTENT_BASE . ltrim($live_db_path, "/")));
			
			if(true === ($zip->open($live_rel_path .".zip"))) {
				print "extracting... ";
				
				$zip->extractTo(MANIFEST_DIR . $live_manifest['version'] ."/");
				$zip->close();
			}
			
			@unlink($live_rel_path .".zip");
			
			print "done!\n";
			
			$manifest_updated = true;
		}
	}
	
	if(!$manifest_updated) {
		die("Local manifest version matches live manifest, nothing to do\n");
	}
	
	// update manifest setting
	$updated_manifest_cargo = array(
		'pulled' => time(),
		'version' => $live_manifest['version'],
		'data' => $live_manifest,
	);
	
	db_query("
		INSERT INTO `setting`
		SET
			setting.meta_key = 'current_manifest',
			setting.meta_value = '". esc_sql(json_encode($updated_manifest_cargo)) ."'
		ON DUPLICATE KEY UPDATE
			setting.meta_value = VALUES(setting.meta_value)
	");
	
	
	// remove old manifest data dir
	function rmdir_recursive($dir) {
		if(is_dir($dir)) {
			$objects = scandir($dir);
			
			foreach($objects as $object) {
				if($object != "." && $object != "..") {
					if(is_dir($dir."/".$object)) {
						rmdir_recursive($dir."/".$object);
					} else {
						unlink($dir."/".$object);
					}
				}
			}
			
			rmdir($dir);
		}
	}
	
	if(!empty($current_manifest['version']) && ($current_manifest['version'] != $live_manifest['version']) && is_dir(MANIFEST_DIR . $current_manifest['version'])) {
		rmdir_recursive(MANIFEST_DIR . $live_manifest['version']);
	}
	
	die("Local manifest updated to version ". $live_manifest['version'] ."\n");