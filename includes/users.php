<?php
	// user/session functionality
	global $userCache;
	$userCache = array();
	
	define('SESSION_COOKIE_SALT',	"056yLUx81117ChVA");   // change to reset all active sessions sitewide
	define('USER_PASS_HASH_ALGO',	PASSWORD_DEFAULT);   // http://php.net/manual/en/password.constants.php
	define('USER_PASS_HASH_COST',	10);
	
	function get_user($user_id, $force=false) {
		global $userCache;
		
		if(empty($force) && !empty($user_id['id']) && isset($user_id['display_name'])) {
			return $user_id;
		}
		
		if(is_array($user_id) && !empty($user_id['id']) && is_numeric($user_id['id'])) {
			$user_id = $user_id['id'];
		}
		
		if(empty($user_id) || !is_numeric($user_id)) {
			return false;
		}
		
		if(empty($force) && isset($userCache['by_id'][ $user_id ])) {
			return $userCache['by_id'][ $user_id ];
		}
		
		$user = cache_get('user_by_id:'. $user_id, function() use ($user_id) {
			$raw_user = get_row("
				SELECT
					user.id, user.username, user.display_name, user.avatar, user.status, user.type, user.permissions, user.reset_hash, user.reset_hash_timestamp, user.forum_id, user.timestamp_created, user.timestamp_last_action
				FROM `user`
				WHERE
					user.id = '". esc_sql($user_id) ."'
			");
			
			if(empty($raw_user['id'])) {
				return false;
			}
			
			if(!empty($raw_user['permissions'])) {
				$raw_user['permissions'] = json_decode($raw_user['permissions'], true);
			} else {
				$raw_user['permissions'] = array();
			}
			
			return $raw_user;
		}, (60 * 60 * 4), $force);
		
		$userCache['by_id'][ $user_id ] = $user;
		
		if((false === $user) || empty($user['id'])) {
			return false;
		}
		
		return $user;
	}
	
	// returns more data like favorites, comment votes, etc
	// mostly used for templating, getAuthedUser() is for internal functionality
	function get_authed_user($force=false) {
		global $userCache;
		
		if(empty($force) && !empty($userCache['authed_user_extended']['id'])) {
			return $userCache['authed_user_extended'];
		}
		
		if((false === ($authed_user = getAuthedUser(false, $force))) || empty($authed_user['id'])) {
			return false;
		}
		
		$user = cache_get('authed_user_by_id:'. $authed_user['id'], function() use ($authed_user) {
			$raw_user = get_row("
				SELECT
					user.id, user.username, user.display_name, user.avatar, user.status, user.type, user.permissions, user.reset_hash, user.reset_hash_timestamp, user.forum_id, user.timestamp_created, user.timestamp_last_action
				FROM `user`
				WHERE
					user.id = '". esc_sql($authed_user['id']) ."'
			");
			
			if(empty($raw_user['id'])) {
				return false;
			}
			
			if(!empty($raw_user['permissions'])) {
				$raw_user['permissions'] = json_decode($raw_user['permissions'], true);
			} else {
				$raw_user['permissions'] = array();
			}
			
			// pull media favorites
			$raw_user['media_favorites'] = get_col("
				SELECT
					media_favorite.media_id
				FROM `media_favorite`
				WHERE
					media_favorite.user_id = '". esc_sql($authed_user['id']) ."'
			", 'media_id');
			
			if(!is_array($raw_user['media_favorites'])) {
				$raw_user['media_favorites'] = array();
			}
			
			// pull media votes
			$voted_media = get_rows("
				SELECT
					media_votes.media_id, media_votes.value
				FROM `media_votes`
				WHERE
					media_votes.user_id = '". esc_sql($authed_user['id']) ."'
				ORDER BY
					media_votes.timestamp DESC
			");
			
			$raw_user['media_votes'] = array();
			
			if(!empty($voted_media)) {
				foreach($voted_media as $media_vote) {
					$raw_user['media_votes'][ $media_vote['media_id'] ] = $media_vote['value'];
				}
			}
			
			// pull comment votes
			$voted_comments = get_rows("
				SELECT
					comment_votes.comment_id, comment_votes.value
				FROM `comment_votes`
				WHERE
					comment_votes.user_id = '". esc_sql($authed_user['id']) ."'
				ORDER BY
					comment_votes.timestamp DESC
			");
			
			$raw_user['comment_votes'] = array();
			
			if(!empty($voted_comments)) {
				foreach($voted_comments as $comment_vote) {
					$raw_user['comment_votes'][ $comment_vote['comment_id'] ] = $comment_vote['value'];
				}
			}
			
			return $raw_user;
		}, (60 * 15), $force);
		
		if((false === $user) || empty($user['id'])) {
			return false;
		}
		
		// save to running script cache
		$userCache['authed_user_extended'] = $user;
		
		return $user;
	}
	
	function get_user_profile_url($user, $sub_section="videos", $paged=1) {
		if(false === ($user = get_user($user))) {
			return false;
		}
		
		//$url_relative = "/profile/". $user['username'] ."/";
		$url_relative = "/profile/". mkurl($user['username']) ."_". $user['id'] ."/";
		
		if(in_array(strtolower($sub_section), array("pictures", "favorites", "settings"))) {
			$url_relative .= strtolower($sub_section) ."/";
		}
		
		if(is_numeric($paged) && ($paged > 1) && in_array(strtolower($sub_section), array("videos", "pictures", "favorites"))) {
			$url_relative .= intval($paged) ."/";
		}
		
		return site_url($url_relative);
	}
	
	function get_user_profile_url_paged($user, $sub_section="videos", $paged_var="%PAGED%") {
		if(false === ($user = get_user($user))) {
			return false;
		}
		
		$url_relative = "/profile/". $user['username'] ."/";
		
		if(in_array(strtolower($sub_section), array("pictures", "favorites", "settings"))) {
			$url_relative .= strtolower($sub_section) ."/";
		}
		
		if(in_array(strtolower($sub_section), array("videos", "pictures", "favorites"))) {
			// pagianted paged
			$url_relative .= $paged_var ."/";
		}
		
		return site_url($url_relative);
	}
	
	function get_user_avatar_url($user) {
		if((false === ($user = get_user($user))) || empty($user['id'])) {
			return false;
		}
		
		if(!empty($user['avatar'])) {
			//return cdn_url("/avatars/". ltrim($user['avatar'], "/"));
			//return AVATAR_URI . ltrim($user['avatar'], "/");
			return "https://storage.googleapis.com/". GOOGLE_CLOUD_BUCKET_AVATARS ."/". ltrim($user['avatar'], "/");
		}
		
		return static_url("/images/avatar-default.png");
	}
	
	function get_user_by_username($username) {
		if("" === ($username = trim(strtolower($username)))) {
			return false;
		}
		
		$user_id = cache_get('user_by_username:'. $username, function() use ($username) {
			return get_var("
				SELECT
					user.id
				FROM `user`
				WHERE
					user.username = '". esc_sql($username) ."'
			");
		});
		
		return get_user($user_id);
	}
	
	function is_user_vip($user=false) {
		if((false === $user) && isAuthedUser()) {
			// use current user
			$user = getAuthedUserID();
		}
		
		if((false === ($user = get_user($user))) || empty($user['id'])) {
			return false;
		}
		
		return (!empty($user['status']) && (in_array($user['type'], array("vip", "admin", "super_admin"))));
	}
	
	/**
	 * Determine if current authed user can do an action
	 * @param type $action 
	 * @return type
	 */
	function canAuthedUserAccess($section) {
		if(empty($section) || ("" === ($section = strtolower(trim($section)))) || (false === ($user = getAuthedUser())) || empty($user['id'])) {
			return false;
		}
		
		if(!isset($user['status']) || !isset($user['permissions'])) {
			$user = get_row("
				SELECT
					user.*
				FROM `user`
				WHERE
					user.id = '". esc_sql($user['id']) ."'
					AND user.status NOT IN ('0')
			");
			
			if(empty($user['id'])) {
				// user not found
				unsetAuthedUser();
				
				return false;
			}
		}
		
		if("super_admin" == $user['type']) {
			// super admin can do anything
			return true;
		}
		
		if(is_string($user['permissions'])) {
			$user['permissions'] = json_decode($user['permissions'], true);
		}
		
		// check permission
		if(!empty($user['permissions'][ $section ])) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Delete script memory cache of current authed user
	 * @return type
	 */
	function wipeCacheAuthedUser() {
		global $userCache;
		
		unset($userCache['authed_user']);
	}
	
	/**
	 * get authed user cookie value
	 * @return string|false
	 */
	function getAuthedUserValue() {
		if(empty($_COOKIE[ ADMIN_COOKIE_NAME ])) {
			return false;
		}
		
		return $_COOKIE[ ADMIN_COOKIE_NAME ];
	}
	
	/**
	 * Generate a user session
	 * @param int $user_id User ID
	 * @return string|false False on error, or 32-char session hash
	 */
	function generateUserSession($user_id) {
		if(empty($user_id) || !is_numeric($user_id)) {
			return false;
		}
		
		// find active user
		$user = get_row("
			SELECT
				user.id, user.status, user.password
			FROM `user`
			WHERE
				user.id = '". esc_sql($user_id) ."'
				AND user.status NOT IN ('0')
		");
		
		if(empty($user['id']) || ($user_id != $user['id']) || in_array($user['status'], array("0"))) {
			return false;
		}
		
		
		// expire all user sessions 
		// not really needed but since there's no session manager, do this
		db_query("
			UPDATE `user_session`
			SET
				user_session.active = '0'
			WHERE
				user_session.user_id = '". esc_sql($user['id']) ."'
				AND user_session.active = '1'
				AND user_session.date_last_active < DATE_SUB(CURDATE(), INTERVAL 1 WEEK)
		");
		
		// make new session
		$session_hash = md5(SESSION_COOKIE_SALT ."::". $user['id'] .":". $user['password'] .":". time() .":". microtime(true) .":". getenv("REMOTE_ADDR"));
		
		$insert_id = db_query("
			INSERT INTO `user_session`
			SET
				`hash` = '". esc_sql($session_hash) ."',
				`user_id` = '". esc_sql($user['id']) ."',
				`date_created` = NOW(),
				`date_last_active` = NOW(),
				`active` = '1'
		");
		
		if(empty($insert_id) || is_null($insert_id)) {
			// unable to create new session
			return false;
		}
		
		return $session_hash;
	}
	
	/**
	 * set authentication for user
	 * @param type $user User object
	 * @return bool
	 */
	function setAuthedUser($user) {
		global $userCache;
		
		if(empty($user['id']) || !isset($user['status']) || in_array($user['status'], array("0")) || (false === ($auth_value = generateUserSession($user['id'])))) {
			wipeCacheAuthedUser();
			
			return false;
		}
		
		// set cookie
		setcookie(ADMIN_COOKIE_NAME, $auth_value, (time() + (60 * 60 * 24 * ADMIN_COOKIE_EXPIRES)), ADMIN_COOKIE_PATH, ADMIN_COOKIE_DOMAIN);
		$_COOKIE[ ADMIN_COOKIE_NAME ] = $auth_value;
		
		$userCache['authed_user'] = $user;
		
		return true;
	}
	
	/**
	 * remove user authentication cookie
	 * @return undefined
	 */
	function unsetAuthedUser() {
		if((false !== ($auth_raw_value = getAuthedUserValue())) && (false !== ($auth_user_id = getAuthedUserID()))) {
			// make record inactive in db
			db_query("
				UPDATE `user_session`
				SET
					user_session.active = '0'
				WHERE
					user_session.user_id = '". esc_sql($auth_user_id) ."'
					AND user_session.hash = '". esc_sql($auth_raw_value) ."'
			");
		}
		
		wipeCacheAuthedUser();
		
		if(!empty($_COOKIE[ ADMIN_COOKIE_NAME ])) {
			unset($_COOKIE[ ADMIN_COOKIE_NAME ]);
			
			setcookie(ADMIN_COOKIE_NAME, "", (time() - (60 * 60 * 24)), ADMIN_COOKIE_PATH, ADMIN_COOKIE_DOMAIN);
			$_COOKIE[ ADMIN_COOKIE_NAME ] = "";
		}
	}
	
	/**
	 * get authed user object
	 * @param type|bool $update_session_date Optional. Update session date in database
	 * @param type|bool $force Optional. Force lookup if cached authed user object isn't available
	 * @return array|false False on error, otherwise user object
	 */
	function getAuthedUser($update_session_date=false, $force=false) {
		global $userCache;
		
		if(!empty($force)) {
			wipeCacheAuthedUser();
		}
		
		if(empty($force) && !empty($userCache['authed_user']['id']) && !in_array($userCache['authed_user']['status'], array("0"))) {
			return $userCache['authed_user'];
		}
		
		if(false === ($auth_raw_value = getAuthedUserValue())) {
			return false;
		}
		
		// find session in db
		$session_data = get_row("
			SELECT
				user_session.hash, user_session.user_id, user_session.active
			FROM `user_session`
			WHERE
				user_session.hash = '". esc_sql($auth_raw_value) ."'
				AND user_session.active = '1'
		");
		
		if(empty($session_data['hash']) || empty($session_data['user_id']) || empty($session_data['active'])) {
			// session not found or disabled
			// remove cookie if necessary
			unsetAuthedUser();
			
			return false;
		}
		
		// find user
		$user = get_row("
			SELECT
				user.*
			FROM `user`
			WHERE
				user.id = '". esc_sql($session_data['user_id']) ."'
		");
		
		if(empty($user['id']) || in_array($user['status'], array("0"))) {
			unsetAuthedUser();
			
			return false;
		}
		
		if(!empty($update_session_date)) {
			db_query("
				UPDATE `user_session`
				SET
					user_session.date_last_active = NOW()
				WHERE
					user_session.hash = '". esc_sql($auth_raw_value) ."'
					AND user_session.active = '1'
			");
		}
		
		$userCache['authed_user'] = $user;
		
		return $user;
	}
	
	/**
	 * basic check if user has authentication cookie
	 * @return bool
	 */
	function isAuthedUser() {
		$auth_value = getAuthedUserValue();
		
		if(empty($auth_value) || (false === getAuthedUser())) {
			// remove cookie if necessary
			unsetAuthedUser();
			
			return false;
		}
		
		return true;
	}
	
	/**
	 * get authed user ID
	 * @return int|false False on error, otherwise user ID
	 */
	function getAuthedUserID() {
		if(false === ($auth_user = getAuthedUser())) {
			return false;
		}
		
		return $auth_user['id'];
	}
	
	/**
	 * Check if authed user is an admin
	 * @return bool
	 */
	function isAuthedAdminUser() {
		return (isAuthedUser() && (false !== ($authed_user = getAuthedUser())) && isset($authed_user['type']) && in_array($authed_user['type'], array('admin', 'super_admin')));
	}
	
	/**
	 * Pull in authed user and store into memory cache
	 * @return void
	 */
	function prepareAuthedUser() {
		// return nothing
		getAuthedUser();
	}
	
	/**
	 * get authed user display name
	 * @return string|false False on error, otherwise username
	 */
	function getAuthedUserDisplayName() {
		if(false === ($user = getAuthedUser())) {
			return false;
		}
		
		if(!empty($user['display_name'])) {
			return $user['display_name'];
		}
		
		return $user['username'];
	}
	
	/**
	 * Provided the name of the input, copy the uploaded file, resize, and return relative path of user avatar. Returns false on any error
	 * @param array $user User assoc array
	 * @param string $file_input_name Input name for $_FILES[ xxx ]
	 * @return string|false
	 */
	function saveUploadedAvatar($user, $file_input_name) {
		require_once(INCLUDE_DIR ."admin.php");
		
		if(empty($user['id']) || empty($_FILES[ $file_input_name ]['tmp_name']) || !isset($_FILES[ $file_input_name ]['error']) || (UPLOAD_ERR_OK !== $_FILES[ $file_input_name ]['error'])) {
			return false;
		}
		
		$file_ext = admin_getFileExt($_FILES[ $file_input_name ]['name']);
		
		if(!in_array($file_ext, array("jpg", "jpeg", "gif", "png"))) {
			return false;
		}
		
		if(false === ($avatar_cdn_path = admin_resizeUploadedImage($_FILES[ $file_input_name ]['tmp_name'], $user['id'] ."_". admin_getRandString(4) .".". $file_ext, THUMB_WIDTH_AVATAR, THUMB_HEIGHT_AVATAR, GOOGLE_CLOUD_BUCKET_AVATARS))) {
			return false;
		}
		
		if(!empty($user['avatar']) && ($user['avatar'] != $avatar_cdn_path)) {
			admin_deleteUploadedFile("gs://". GOOGLE_CLOUD_BUCKET_AVATARS ."/". ltrim($user['avatar'], "/"));
		}
		
		return $avatar_cdn_path;
	}
	
	/**
	 * Update a user's recent activity date
	 * @param array|id $user User Array or user ID
	 * @return void
	 */
	function update_user_activity_date($user) {
		if((false === ($user = get_user($user))) || empty($user['id'])) {
			return false;
		}
		
		db_query("
			UPDATE `user`
			SET
				user.timestamp_last_action = UNIX_TIMESTAMP()
			WHERE
				user.id = '". esc_sql($user['id']) ."'
		");
		
		// expire cache
		cache_delete('user_by_id:'. $user['id']);
	}