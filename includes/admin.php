<?php
	// admin area
	global $adminCfg;
	$adminCfg = array();
	
	function admin_getRandString($length=8) {
		return substr(md5(time() .":". microtime(true) .":". getenv("REMOTE_ADDR") .":". rand(0, 9999)), 0, max(1, min(32, $length)));
	}
	
	/**
	 * Determine if viewing requested admin page
	 * @param string $page_key Page Key (found with $_REQUEST['section'])
	 * @param mixed $return_if_true Returned if viewing page
	 * @param mixed $return_if_false Returned if not viewing page
	 * @param bool $also_true Additional check, used for multiple versions of same page key
	 * @return mixed
	 */
	function isViewingAdminPage($page_key, $return_if_true=true, $return_if_false="", $also_true=true) {
		global $adminCfg;
		
		if(empty($_REQUEST['isAdmin'])) {
			return false;
		}
		
		if(!isset($adminCfg['page_key'])) {
			$actual_page_key = "index";
			
			if(!empty($_REQUEST['section'])) {
				$actual_page_key = $_REQUEST['section'];
			}
			
			$adminCfg['page_key'] = strtolower(trim($actual_page_key));
		}
		
		if(is_array($page_key)) {
			if(in_array($adminCfg['page_key'], $page_key)) {
				if(is_bool($also_true)) {
					if($also_true) {
						return $return_if_true;
					} else {
						return $return_if_false;
					}
				}
				
				return $return_if_true;
			} else {
				return $return_if_false;
			}
		}
		
		if(strtolower(trim($page_key)) == $adminCfg['page_key']) {
			if(is_bool($also_true)) {
				if($also_true) {
					return $return_if_true;
				} else {
					return $return_if_false;
				}
			}
			
			return $return_if_true;
		} else {
			return $return_if_false;
		}
	}
	
	function adminIsLoggedIn() {
		return isAuthedUser();
	}
	
	/**
	 * Checks session data, redirects to login screen if not logged in to active account
	 * @return void
	 */
	function admin_restrictToAuthUser() {
		if(false === ($authed_user = getAuthedUser(true))) {
			header("Location: ". admin_url("/login/"));
			
			die;
		}
	}
	
	/**
	 * Get the file extension of a given file path
	 * @param string $file_path File path
	 * @return string
	 */
	function admin_getFileExt($file_path) {
		$file_path = str_replace("\\", "/", $file_path);
		
		$parts = explode("/", $file_path);
		$part = array_pop($parts);
		$exts = explode(".", $part);
		$ext = strtolower(trim(array_pop($exts)));
		
		return $ext;
	}
	
	/**
	 * Generate a unique slug/seoid for a new table entry
	 * @param string $string Source string
	 * @param string $table Optional. Table name to check
	 * @param string $column Optional. Column name used for seoid
	 * @return string
	 */
	function admin_getUniqueSlug($string, $table="media", $column="seoid") {
		if(("media" === $table) && ("seoid" === $column)) {
			// media slugs require special formatting
			$original_slug = mkurl($string, "_", false);
		} else {
			$original_slug = mkurl($string);
		}
		
		if("" === $original_slug) {
			$original_slug = admin_getRandString(8);
		}
		
		$attempts = 0;
		$slug_exists = false;
		
		do {
			$use_slug = $original_slug . (($attempts > 0)?"-". $attempts:"");
			
			$found_slug = get_var("SELECT `". esc_sql($column) ."` FROM `". esc_sql($table) ."` WHERE `". esc_sql($column) ."` = '". esc_sql($use_slug) ."' LIMIT 1");
			
			$slug_exists = ((false !== $found_slug) && ($use_slug == $found_slug));
			$attempts++;
		} while($slug_exists && ($attempts <= 5));
		
		if($slug_exists && ($attempts > 5)) {
			$use_slug = $original_slug ."-". rand(10000, 99999);
		}
		
		return $use_slug;
	}
	
	/**
	 * Upload a file to Cloud Storage
	 * @param string $src_path Full path to current source file
	 * @param string|false $dest_path Relative path to file on CDN
	 * @param string $bucket_name Optional. Bucket name on Cloud Storage. Defaults to content bucket
	 * @return false|string False on error, CDN path on success
	 */
	function admin_saveUploadedFile($src_path, $dest_path, $file_ext=false, $bucket_name=GOOGLE_CLOUD_BUCKET_CONTENT) {
		if(!is_file($src_path)) {
			return false;
		}
		
		return cdn_file_save($src_path, $dest_path, $bucket_name);
	}
	
	/**
	 * Delete a file 
	 * @param string $cdn_path Path of file on CDN. Acceptable formats include 'path/to/file.jpg' and 'gs://bucket-name/path/to/file.jpg'
	 * @param string $bucket_name Optional. Bucket name on Cloud Storage. Defaults to content bucket
	 * @return bool
	 */
	function admin_deleteUploadedFile($cdn_path, $bucket_name=GOOGLE_CLOUD_BUCKET_CONTENT) {
		if(preg_match("#^gs\://([^/]+)/(.+)$#si", $cdn_path, $match)) {
			$bucket_name = trim($match[1], "/");
			$cdn_path = ltrim($match[2], "/");
		}
		
		return cdn_file_remove($cdn_path, $bucket_name);
	}
	
	/**
	 * Resize an uploaded image file
	 * @param string $src_path Full path to uploaded file
	 * @param string $dest_path Relative path of new file on CDN
	 * @param int $width Optional. Width of resized image. Defaults to THUMB_WIDTH
	 * @param int $height Optional. Height of resized image. Defaults to THUMB_HEIGHT
	 * @param string $bucket_name Optional. Bucket name on Cloud Storage. Defaults to content bucket
	 * @return string|false Path to resized image on CDN or false on error
	 */
	function admin_resizeUploadedImage($src_path, $dest_path, $width=THUMB_WIDTH, $height=THUMB_HEIGHT, $bucket_name=GOOGLE_CLOUD_BUCKET_CONTENT) {
		require_once(CLASS_DIR ."image_resize.php");
		
		clearstatcache();
		
		if(!file_exists($src_path) || ("" === ($dest_path = ltrim($dest_path, "/")))) {
			return false;
		}
		
		if(empty($width) || !is_numeric($width)) {
			$width = THUMB_WIDTH;
		}
		
		if(empty($height) || !is_numeric($height)) {
			$height = THUMB_HEIGHT;
		}
		
		$image_ext = admin_getFileExt($dest_path);
		
		$copied_path = false;
		
		if(substr($src_path, 0, strlen(TMP_DIR)) !== TMP_DIR) {
			// move source file into tmp_dir with extension
			$copied_path = TMP_DIR . admin_getRandString(32) .".". $image_ext;
			
			if(!@copy($src_path, $copied_path)) {
				return false;
			}
			
			$src_path = $copied_path;
		}
		
		// handle thumbnail upload
		$resized_thumbnail_path = TMP_DIR . admin_getRandString(32) .".". $image_ext;
		
		try {
			// resize to thumbnail
			$resizeObj = new resize($src_path);
			$resizeObj->resizeImage($width, $height, 'crop');
			$resizeObj->saveImage($resized_thumbnail_path, 100);
			
			if(false === ($uploaded_file = cdn_file_save($resized_thumbnail_path, $dest_path, $bucket_name))) {
				throw new Exception("Unable to save resized image to CDN");
			}
			
			@unlink($resized_thumbnail_path);
			
			if(!empty($copied_path)) {
				@unlink($copied_path);
			}
			
			return $uploaded_file;
		} catch(Exception $e) {
			@unlink($resized_thumbnail_path);   // just in case
			
			if(!empty($copied_path)) {
				@unlink($copied_path);
			}
			
			return false;
		}
	}
	
	/**
	 * Display Message Alerts
	 * @param type $messages Array of message objects ({'type' => "", 'body' => "..."})
	 * @return void
	 */
	function admin_saveMessage($type, $body) {
		global $_SESSION;
		
		if(empty($type) || empty($body)) {
			return;
		}
		
		if(!isset($_SESSION['messages']) || !is_array($_SESSION['messages'])) {
			$_SESSION['messages'] = array();
		}
		
		if(count($_SESSION['messages']) >= 10) {
			$_SESSION['messages'] = array_slice($_SESSION['messages'], 0, 9);
		}
		
		$_SESSION['messages'][] = array(
			'type' => $type,
			'body' => $body,
		);
	}
	
	/**
	 * Display Message Alerts
	 * @param type $messages Array of message objects ({'type' => "", 'body' => "..."})
	 * @return void
	 */
	function admin_displayMessages($messages=array()) {
		global $_SESSION;
		
		if(!is_array($messages)) {
			$messages = array();
		}
		
		if(isset($_SESSION['messages'])) {
			if(is_array($_SESSION['messages']) && !empty($_SESSION['messages'])) {
				$messages = array_merge($messages, $_SESSION['messages']);
			}
			
			unset($_SESSION['messages']);
		}
		
		if(empty($messages) || !is_array($messages)) {
			return;
		}
		
		foreach($messages as $message): if(!isset($message['type']) && !isset($message['body'])) { continue; } ?>
		<div class="alert <?=esc_attr((!empty($message['type'])?"alert-". $message['type']:""));?> alert-dismissable" role="alert">
			<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<?=(isset($message['body'])?$message['body']:$message);?>
		</div>
		<?php endforeach;
	}
	
	/**
	 * Return key array of admin sections for user permissions
	 * @return array
	 */
	function admin_userPermissionSections() {
		return array(
			'videos'		=> "Videos",
			'pictures'		=> "Pictures",
			'submissions'	=> "User Submissions",
			'dropbox'		=> "Dropbox",
			'comments'		=> "Comments",
			'settings'		=> "Settings",
			'categories'	=> "Categories",
			'rants'			=> "Daily Rants",
			'users'			=> "Users",
			'affiliates'	=> "Affiliates",
			'ads'			=> "Ads",
			'hardlinks'		=> "Hardlinks",
			'analytics'		=> "Analytics",
			'trending'		=> "Trending Content",
			'featured'		=> "Featured Content",
		);
	}
	
	/**
	 * Return key array of user statuses for user edit pages
	 * @return type
	 */
	function admin_userStatuses() {
		return array(
			'1'		=> "Active",
			'0'		=> "Deactivated",
		);
	}
	
	/**
	 * Return key array of user types for user edit pages
	 * @return type
	 */
	function admin_userTypes() {
		return array(
			'user'	=> "Regular",
			'vip'	=> "VIP",
			'admin'	=> "Admin",
			'super_admin' => "Super Admin",
		);
	}
	
	/**
	 * Build a pagination display
	 * @param string $url URL base. If %page% is not in string, (?|&)paged=# will be added instead (paged replaced with $paged_param)
	 * @param int $page Optional. Current page number
	 * @param int $num_pages Optional. Number of pages
	 * @param string $param Optional. Parameter name for page number on URL
	 * @return void
	 */
	function admin_paginate($url, $page=1, $num_pages=1, $param="paged") {
		$url = preg_replace("#%page%#si", "%page%", $url);   // lowercase %page% (if any)
		
		$hash_value = false;
		
		if(false !== ($hash_pos = strpos($url, "#"))) {
			$hash_value = substr($url, $hash_pos);
			$url = substr($url, 0, $hash_pos);
		}
		
		if(false === strpos($url, "%page%")) {
			$url .= ((false !== strpos($url, "?"))?"&":"?") . $param ."=%page%";
		}
		
		if(false !== $hash_value) {
			$url .= $hash_value;
		}
		?>
		<nav>
			<ul class="pagination">
				<?php if($page > 1): ?>
				<li><a href="<?=esc_attr(str_replace("%page%", 1, $url));?>" aria-label="First"><span aria-hidden="true">&laquo;</span></a></li>
				<li><a href="<?=esc_attr(str_replace("%page%", ($page - 1), $url));?>" aria-label="Previous"><span aria-hidden="true">&lsaquo;</span></a></li>
				<?php else: ?>
				<li class="disabled"><span><span aria-hidden="true">&laquo;</span></span></li>
				<?php endif; ?>
				
				<?php if($page > 1): ?><li><a href="<?=esc_attr(str_replace("%page%", ($page - 1), $url));?>"><?=esc_html(number_format( ($page - 1) ));?></a></li><?php endif; ?>
				<li class="active"><span><strong><?=esc_html(number_format($page));?></strong> of <strong><?=esc_html(number_format($num_pages));?></strong> <span class="sr-only">(current)</span></span></li>
				<?php if($page < $num_pages): ?><li><a href="<?=esc_attr(str_replace("%page%", ($page + 1), $url));?>"><?=esc_html(number_format( ($page + 1) ));?></a></li><?php endif; ?>
				
				<?php if($page < $num_pages): ?>
				<li><a href="<?=esc_attr(str_replace("%page%", $page, $url));?>" aria-label="Next"><span aria-hidden="true">&rsaquo;</span></a></li>
				<li><a href="<?=esc_attr(str_replace("%page%", $num_pages, $url));?>" aria-label="Last"><span aria-hidden="true">&raquo;</span></a></li>
				<?php else: ?>
				<li class="disabled"><span><span aria-hidden="true">&raquo;</span></span></li>
				<?php endif; ?>
			</ul>
		</nav>
		<?php
	}