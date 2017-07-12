<?php
	use Google\Cloud\Storage\StorageClient;
	
	/**
	 * Transfer file to CDN/Storage
	 * @param string $src_path Full file path to source file
	 * @param string $cdn_path Relative file path on CDN/Storage
	 * @param string|false $bucket_name Bucket name
	 * @return false|string Returns relative CDN/Storage path on success or false on error
	 */
	function cdn_file_save($src_path, $cdn_path, $bucket_name=GOOGLE_CLOUD_BUCKET_CONTENT) {
		// constants that are required by this function
		if(!defined('GOOGLE_CLOUD_PROJECT_ID') || !GOOGLE_CLOUD_PROJECT_ID || !defined('GOOGLE_CLOUD_SERVICE_ACCOUNT_JSON') || !GOOGLE_CLOUD_SERVICE_ACCOUNT_JSON) {
			return false;
		}
		
		if(("" === $src_path) || !file_exists($src_path) || ("" === ($cdn_path = ltrim($cdn_path, "/")))) {
			return false;
		}
		
		try {
			$storage = new StorageClient([
				'projectId' => GOOGLE_CLOUD_PROJECT_ID,
				'keyFilePath' => GOOGLE_CLOUD_SERVICE_ACCOUNT_JSON,
			]);
			
			$bucket = $storage->bucket($bucket_name);
			
			$new_object = $bucket->upload(
				fopen($src_path, 'r'),
				array(
					'name' => $cdn_path,
					'predefinedAcl' => "publicRead",
				)
			);
			
			//return $cdn_path;
			return $new_object->name();
		} catch(Exception $e) {
			return false;
		}
	}
	
	/**
	 * Remove an object from CDN/Storage
	 * @param string $cdn_path Relative path to file in CDN
	 * @param string $bucket_name Bucket name
	 * @return bool
	 */
	function cdn_file_remove($cdn_path, $bucket_name=GOOGLE_CLOUD_BUCKET_CONTENT) {
		// constants that are required by this function
		if(!defined('GOOGLE_CLOUD_PROJECT_ID') || !GOOGLE_CLOUD_PROJECT_ID || !defined('GOOGLE_CLOUD_SERVICE_ACCOUNT_JSON') || !GOOGLE_CLOUD_SERVICE_ACCOUNT_JSON) {
			return false;
		}
		
		if(empty($cdn_path) || ("" === ($cdn_path = ltrim($cdn_path, "/\\")))) {
			return true;
		}
		
		try {
			$storage = new StorageClient([
				'projectId' => GOOGLE_CLOUD_PROJECT_ID,
				'keyFilePath' => GOOGLE_CLOUD_SERVICE_ACCOUNT_JSON,
			]);
			
			$bucket = $storage->bucket($bucket_name);
			
			$object = $bucket->object($cdn_path);
			
			if($object->exists()) {
				$object->delete();
			}
			
			return true;
		} catch(Exception $e) {
			return false;
		}
	}
	
	/**
	 * Store uploaded file into tmp directory, record into database, then return stored key
	 * @param string $input_name Name of input type=file
	 * @param string $source Optional. Source of upload
	 * @param string $cargo Optional. Cargo array
	 * @return string|false
	 */
	function storeTempFileUpload($input_name, $source="", $cargo=array()) {
		if(!isset($_FILES[ $input_name ]['error'])) {
			return false;
		}
		
		if($_FILES[ $input_name ]['error'] !== UPLOAD_ERR_OK) {
			return false;
		}
		
		// generate random key
		do {
			$tmp_key = md5(getenv("REMOTE_ADDR") .":". microtime(true) .":". time() .":". rand(1, 9999) .":". $input_name);
			
			$found_key = get_row("
				SELECT
					tmp_file.key
				FROM `tmp_file`
				WHERE
					tmp_file.key = '". esc_sql($tmp_key) ."'
				LIMIT 1
			");
		} while(!empty($found_key) && !is_null($found_key));
		
		if(!move_uploaded_file($_FILES[ $input_name ]['tmp_name'], TMP_DIR . $tmp_key)) {
			return false;
		}
		
		if(!is_string($cargo)) {
			$cargo = json_encode($cargo);
		}
		
		db_query("
			INSERT INTO `tmp_file`
			SET
				`key` = '". esc_sql($tmp_key) ."',
				`original_name` = '". esc_sql($_FILES[ $input_name ]['name']) ."',
				`timestamp` = UNIX_TIMESTAMP(),
				`source` = ". (!empty($source)?"'". esc_sql($source) ."'":"NULL") .",
				`cargo` = ". (!empty($cargo)?"'". esc_sql($cargo) ."'":"NULL") ."
		");
		
		return $tmp_key;
	}
	
	/**
	 * Generate a JPG screenshot from a video file and store on Cloud Storage
	 * @param string $file_path Full filepath to video file
	 * @param string $cdn_path Relative path on CDN to store image
	 * @param int $width Width of output image
	 * @param int $height Height of output image
	 * @param string $bucket_name Optional. Google Cloud bucket name
	 * @param int $seconds Optional. Number of seconds into the video. Becomes 5 when empty or non integer
	 * @return false|string Relative CDN path or false on error
	 */
	function generateImageFromVideoFile($file_path, $cdn_path, $width, $height, $bucket_name=GOOGLE_CLOUD_BUCKET_CONTENT, $seconds=5) {
		require_once(INCLUDE_DIR ."admin.php");
		
		if(!defined('FFMPEG_PATH') || !FFMPEG_PATH || empty($file_path) || !is_file($file_path) || !preg_match("#\.(mov|avi|flv|mp4|mpe?g|wmv)$#si", $file_path)) {
			return false;
		}
		
		if(empty($seconds) || is_null($seconds)) {
			$seconds = 5;
		}
		
		$width = round($width);
		$height = round($height);
		
		if(preg_match("#^gs\://([^/]+)/(.+)$#si", $cdn_path, $match)) {
			$bucket_name = trim($match[1], "/");
			$cdn_path = ltrim($match[2], "/");
		}
		
		$cdn_path = ltrim($cdn_path, "/");
		
		// make sure cdn path has trailing .jpg
		if(!preg_match("#\.jpg#si", $cdn_path)) {
			$cdn_path = preg_replace("#\.[a-z0-9]{3,4}#si", "", $cdn_path);
			$cdn_path = rtrim($cdn_path, "/.");
			$cdn_path .= ".jpg";
		}
		
		// setup temporary location for image
		$tmp_image_path = TMP_DIR . admin_getRandString(12) .".jpg";
		
		// run command
		exec(FFMPEG_PATH ." -i ". escapeshellarg($file_path) ." -deinterlace -an -ss ". escapeshellarg($seconds) ." -f mjpeg -t 1 -r 1 -y -s ". escapeshellarg($width ."x". $height) ." ". escapeshellarg($tmp_image_path) ." 2>&1");
		
		if(!file_exists($tmp_image_path)) {
			// failed
			return false;
		}
		
		// upload to cdn, set cdn path to false on failure
		if(!cdn_file_save($tmp_image_path, $cdn_path, $bucket_name)) {
			$cdn_path = false;
		}
		
		// delete local image file
		@unlink($tmp_image_path);
		
		return $cdn_path;
	}