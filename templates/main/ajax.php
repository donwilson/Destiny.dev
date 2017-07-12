<?php
	//require_once(INCLUDE_DIR ."ajax.php");
	
	// which ajax action is being requested
	if("" === ($ajax_action = (!empty($_REQUEST['ajax_action'])?strtolower(trim($_REQUEST['ajax_action'])):""))) {
		die_json('error', array(), "AJAX method was not specified or active");
	}
	
	// current user ID
	$authed_user = getAuthedUser();
	
	
	//////////////
	// CONTENT //
	/////////////
	
	if("rate_content" === $ajax_action) {
		// rate content
		// 
		// id = media.id
		// value = media_votes.value
		// intent = [save|remove] (optional)
		// hash = get_post_rate_ajax_hash(id)
		
		if(empty($authed_user['id']) || empty($_REQUEST['id']) || !isset($_REQUEST['value']) || ("" === ($raw_value = trim($_REQUEST['value']))) || !in_array($raw_value, array("up", "down")) || (false === ($post = get_post($_REQUEST['id']))) || empty($post['id']) || empty($_REQUEST['hash']) || ($_REQUEST['hash'] !== get_post_rate_ajax_hash($post['id']))) {
			die_json('error', array(), "Unable to save rating");
		}
		
		// determine what the intent was (save value or remove value (eg when user clicks Love it when Love it is already highlighted to remove value))
		if(isset($_REQUEST['intent']) && ("remove" === strtolower(trim($_REQUEST['intent'])))) {
			// remove any rating for content
			db_query("
				DELETE FROM `media_votes`
				WHERE
					media_votes.media_id = '". esc_sql($post['id']) ."'
					AND media_votes.user_id = '". esc_sql($authed_user['id']) ."'
			");
		} else {
			// save rating for content
			db_query("
				INSERT INTO `media_votes`
				SET
					`media_id` = '". esc_sql($post['id']) ."',
					`user_id` = '". esc_sql($authed_user['id']) ."',
					`value` = '". esc_sql($raw_value) ."',
					`timestamp` = UNIX_TIMESTAMP(),
					`ip_address` = INET_ATON('". esc_sql(getenv("REMOTE_ADDR")) ."')
				ON DUPLICATE KEY UPDATE
					`value` = VALUES(`value`),
					`timestamp` = VALUES(`timestamp`),
					`ip_address` = VALUES(`ip_address`)
			");
		}
		
		// calculate current votes_up/votes_down counts then update content row
		$tally_votes_up = get_var("
			SELECT
				COUNT(*)
			FROM `media_votes`
			WHERE
				media_votes.media_id = '". esc_sql($post['id']) ."'
				AND media_votes.value = 'up'
		");
		
		$tally_votes_down = get_var("
			SELECT
				COUNT(*)
			FROM `media_votes`
			WHERE
				media_votes.media_id = '". esc_sql($post['id']) ."'
				AND media_votes.value = 'down'
		");
		
		if(is_null($tally_votes_up)) {
			$tally_votes_up = 0;
		}
		
		if(is_null($tally_votes_down)) {
			$tally_votes_down = 0;
		}
		
		if(($tally_votes_up != $post['votes_up']) || ($tally_votes_down != $post['votes_down'])) {
			db_query("
				UPDATE `media`
				SET
					media.votes_up = '". esc_sql($tally_votes_up) ."',
					media.votes_down = '". esc_sql($tally_votes_down) ."'
				WHERE
					media.id = '". esc_sql($post['id']) ."'
			");
			
			// clear cache on content
			cache_delete('media_by_id:'. $post['id']);
		}
		
		// clear cache on user
		cache_delete('authed_user_by_id:'. $authed_user['id']);
		
		// update user's last activity date
		update_user_activity_date($authed_user);
		
		// done
		die_json('success', array(
			'action' => $ajax_action,
			'media_id' => $post['id'],
			'intent' => ((isset($_REQUEST['intent']) && ("remove" === strtolower(trim($_REQUEST['intent']))))?"remove":"save"),
			'value' => strtolower(trim($_REQUEST['value'])),
			'votes_up' => number_format($tally_votes_up),
			'votes_down' => number_format($tally_votes_down),
		));
	}
	
	if("favorite_content" === $ajax_action) {
		// favorite content
		// 
		// id = media.id
		// intent = [save|remove] (optional)
		// hash = get_post_favorite_ajax_hash(id)
		
		if(empty($authed_user['id']) || empty($_REQUEST['id']) || (false === ($post = get_post($_REQUEST['id']))) || empty($post['id']) || empty($_REQUEST['hash']) || ($_REQUEST['hash'] !== get_post_favorite_ajax_hash($post['id']))) {
			die_json('error', array(), "Unable to save favorite");
		}
		
		// determine what the intent was (save value or remove value (eg when user clicks Love it when Love it is already highlighted to remove value))
		if(isset($_REQUEST['intent']) && ("remove" === strtolower(trim($_REQUEST['intent'])))) {
			// remove favorite for content
			db_query("
				DELETE FROM `media_favorite`
				WHERE
					media_favorite.media_id = '". esc_sql($post['id']) ."'
					AND media_favorite.user_id = '". esc_sql($authed_user['id']) ."'
			");
		} else {
			// save favorite for content
			db_query("
				INSERT INTO `media_favorite`
				SET
					`media_id` = '". esc_sql($post['id']) ."',
					`user_id` = '". esc_sql($authed_user['id']) ."',
					`timestamp` = UNIX_TIMESTAMP(),
					`ip_address` = INET_ATON('". esc_sql(getenv("REMOTE_ADDR")) ."')
				ON DUPLICATE KEY UPDATE
					`timestamp` = VALUES(`timestamp`),
					`ip_address` = VALUES(`ip_address`)
			");
		}
		
		//// clear cache on content
		//cache_delete('media_by_id:'. $post['id']);
		
		// clear cache on user
		cache_delete('authed_user_by_id:'. $authed_user['id']);
		cache_delete(CONTENT_CACHE_PREFIX .'user_content__'. $authed_user['id'] .':sub_section__favorites');
		
		// update user's last activity date
		update_user_activity_date($authed_user);
		
		// done
		die_json('success', array(
			'action' => $ajax_action,
			'intent' => ((isset($_REQUEST['intent']) && ("remove" === strtolower(trim($_REQUEST['intent']))))?"remove":"save"),
			'media_id' => $post['id'],
		));
	}
	
	
	//////////////
	// COMMENTS //
	//////////////
	
	function ajax__get_comment_properties($entity_id=false) {
		$data = array();
		
		$comment_section = determineValue('type', array(
			'media',
			'rant',
		), 'media');
		
		$data['section'] = $comment_section;
		
		switch($comment_section) {
			case 'rant':
				$data['comment_table'] = "rant_comment";
				$data['comment_table_entity_column'] = "rant_id";
				$data['votes_table'] = "rant_comment_votes";
				
				$data['entity'] = (!empty($entity_id)?get_rant($entity_id):false);
				
				$data['cache_keys'] = array(
					get_rant_cache_salt() .'rant_by_id:'. $entity_id,
					get_rant_cache_salt() .'comments:by_date:rant_id__'. $entity_id,
					get_rant_cache_salt() .'comments:by_votes:rant_id__'. $entity_id,
				);
			break;
			
			case 'media':
			default:
				$data['comment_table'] = "media_comment";
				$data['comment_table_entity_column'] = "media_id";
				$data['votes_table'] = "comment_votes";
				
				$data['entity'] = (!empty($entity_id)?get_post($entity_id):false);
				
				$data['cache_keys'] = array(
					'media_by_id:'. $entity_id,
					'comments:by_date:media_id__'. $entity_id,
					'comments:by_votes:media_id__'. $entity_id,
				);
		}
		
		return $data;
	}
	
	if("add_comment" === $ajax_action) {
		// add a comment
		// 
		// id = media.id
		// parent = media_comment.id OR rant_comment.id (optional)
		// comment = media_comment.body OR rant_comment.body
		// hash = get_comment_add_ajax_hash(id)
		
		if(empty($authed_user['id']) || empty($_REQUEST['id']) || !isset($_REQUEST['comment']) || ("" === ($raw_comment = trim($_REQUEST['comment'])))) {
			die_json('error', array(), "Unable to save comment");
		}
		
		$data_property = ajax__get_comment_properties($_REQUEST['id']);
		
		if(empty($data_property['entity']['id'])) {
			die_json('error', array(), "Unable to save comment");
		}
		
		if(empty($_REQUEST['hash']) || ($_REQUEST['hash'] !== get_comment_add_ajax_hash($data_property['entity']['id']))) {
			die_json('error', array(), "Unable to save comment");
		}
		
		// save comment
		$insert_id = db_query("
			INSERT INTO `". esc_sql($data_property['comment_table']) ."`
			SET
				`". esc_sql($data_property['comment_table_entity_column']) ."` = '". esc_sql($data_property['entity']['id']) ."',
				`user_id` = '". esc_sql($authed_user['id']) ."',
				`reply_to` = ". ((!empty($_REQUEST['parent']) && is_numeric($_REQUEST['parent']))?"'". esc_sql($_REQUEST['parent']) ."'":"NULL") .",
				`body` = '". esc_sql($raw_comment) ."',
				`timestamp` = UNIX_TIMESTAMP(),
				`ip_address` = INET_ATON('". esc_sql(getenv("REMOTE_ADDR")) ."')
		");
		
		// clear comment cache on content
		if(!empty($data_property['cache_keys'])) {
			foreach($data_property['cache_keys'] as $cache_key) {
				cache_delete($cache_key);
			}
		}
		
		// update user's last activity date
		update_user_activity_date($authed_user);
		
		// done
		die_json('success', array(
			'action' => $ajax_action,
			'comment_id' => $insert_id,
			$data_property['comment_table_entity_column'] => $data_property['entity']['id'],
			'parent_id' => ((!empty($_REQUEST['parent']) && is_numeric($_REQUEST['parent']))?$_REQUEST['parent']:false),
			'comment_body' => clean_and_paragraph_text($raw_comment),
			'author_name' => $authed_user['display_name'],
			'author_url' => get_user_profile_url($authed_user),
			'author_is_vip' => is_user_vip($authed_user),
			'author_avatar' => get_user_avatar_url($authed_user),
			'comment_timestamp' => time(),
			'comment_timestamp_attr' => date("r"),
			'comment_timestamp_display' => date("F j, Y"),
			'reply_hash' => get_comment_add_ajax_hash($insert_id),
			'delete_hash' => get_comment_delete_ajax_hash($insert_id),
			'rate_hash' => get_comment_rate_ajax_hash($insert_id),
		));
	}
	
	if("delete_comment" === $ajax_action) {
		// delete a comment
		// 
		// id = media_comment.id OR rant_comment.id
		// hash = get_comment_add_ajax_hash(id)
		
		if(empty($authed_user['id']) || empty($_REQUEST['id']) || empty($_REQUEST['hash'])) {
			die_json('error', array(), "Unable to delete comment". __LINE__);
		}
		
		$data_property = ajax__get_comment_properties($_REQUEST['id']);
		
		$existing_comment = get_row("
			SELECT
				". esc_sql($data_property['comment_table']) .".id, ". esc_sql($data_property['comment_table']) .".user_id, ". esc_sql($data_property['comment_table']) .".". esc_sql($data_property['comment_table_entity_column']) ."
			FROM `". esc_sql($data_property['comment_table']) ."`
			WHERE
				". esc_sql($data_property['comment_table']) .".id = '". esc_sql($_REQUEST['id']) ."'
		");
		
		if(empty($existing_comment['id']) || ($_REQUEST['hash'] !== get_comment_delete_ajax_hash($existing_comment['id']))) {
			die_json('error', array(), "Unable to delete comment". __LINE__);
		}
		
		$data_property = ajax__get_comment_properties($existing_comment[ $data_property['comment_table_entity_column'] ]);
		
		if(empty($data_property['entity']['id'])) {
			die_json('error', array(), "Unable to delete comment". __LINE__);
		}
		
		if(!in_array($authed_user['type'], array("admin", "super_admin")) && ($existing_comment['user_id'] != $authed_user['id'])) {
			// admins are able to delete any comment, but also check if authed user is comment author
			die_json('error', array(), "Unable to delete comment". __LINE__);
		}
		
		// delete comment
		db_query("
			DELETE FROM `". esc_sql($data_property['comment_table']) ."`
			WHERE
				". esc_sql($data_property['comment_table']) .".id = '". esc_sql($existing_comment['id']) ."'
		");
		
		// clear comment cache on content
		if(!empty($data_property['cache_keys'])) {
			foreach($data_property['cache_keys'] as $cache_key) {
				cache_delete($cache_key);
			}
		}
		
		// update user's last activity date
		update_user_activity_date($authed_user);
		
		// done
		die_json('success', array(
			'action' => $ajax_action,
			'comment_id' => $existing_comment['id'],
			$data_property['comment_table_entity_column'] => $existing_comment[ $data_property['comment_table_entity_column'] ],
		));
	}
	
	if("rate_comment" === $ajax_action) {
		// rate comment
		// 
		// id = media_comment.id OR rant_comment.id
		// value = media_comment.value OR rant_comment.value
		// intent = [save|remove] (optional)
		// hash = get_comment_rate_ajax_hash(id)
		
		if(empty($authed_user['id']) || empty($_REQUEST['id']) || !isset($_REQUEST['value']) || ("" === ($raw_value = trim($_REQUEST['value']))) || !in_array($raw_value, array("up", "down")) || empty($_REQUEST['hash']) || ($_REQUEST['hash'] !== get_comment_rate_ajax_hash($_REQUEST['id']))) {
			die_json('error', array(), "Unable to rate comment");
		}
		
		$data_property = ajax__get_comment_properties();
		
		$comment = get_row("
			SELECT
				". esc_sql($data_property['comment_table']) .".id, ". esc_sql($data_property['comment_table']) .".". esc_sql($data_property['comment_table_entity_column']) .", ". esc_sql($data_property['comment_table']) .".user_id, ". esc_sql($data_property['comment_table']) .".votes_up, ". esc_sql($data_property['comment_table']) .".votes_down
			FROM `". esc_sql($data_property['comment_table']) ."`
			WHERE
				". esc_sql($data_property['comment_table']) .".id = '". esc_sql($_REQUEST['id']) ."'
		");
		
		if(empty($comment['id']) || ($comment['user_id'] == $authed_user['id'])) {
			// check for existing comment and make sure authed user isnt comment author
			die_json('error', array(), "Unable to rate comment");
		}
		
		$data_property = ajax__get_comment_properties($comment[ $data_property['comment_table_entity_column'] ]);
		
		// determine what the intent was (save value or remove value (eg when user clicks Love it when Love it is already highlighted to remove value))
		if(isset($_REQUEST['intent']) && ("remove" === strtolower(trim($_REQUEST['intent'])))) {
			// remove any rating for comment
			db_query("
				DELETE FROM `". esc_sql($data_property['votes_table']) ."`
				WHERE
					". esc_sql($data_property['votes_table']) .".comment_id = '". esc_sql($comment['id']) ."'
					AND ". esc_sql($data_property['votes_table']) .".user_id = '". esc_sql($authed_user['id']) ."'
			");
		} else {
			// save rating for comment
			db_query("
				INSERT INTO `". esc_sql($data_property['votes_table']) ."`
				SET
					`comment_id` = '". esc_sql($comment['id']) ."',
					`user_id` = '". esc_sql($authed_user['id']) ."',
					`value` = '". esc_sql($raw_value) ."',
					`timestamp` = UNIX_TIMESTAMP(),
					`ip_address` = INET_ATON('". esc_sql(getenv("REMOTE_ADDR")) ."')
				ON DUPLICATE KEY UPDATE
					`value` = VALUES(`value`),
					`timestamp` = VALUES(`timestamp`),
					`ip_address` = VALUES(`ip_address`)
			");
		}
		
		// calculate current votes_up/votes_down counts then update comment row
		$tally_votes_up = get_var("
			SELECT
				COUNT(*)
			FROM `". esc_sql($data_property['votes_table']) ."`
			WHERE
				". esc_sql($data_property['votes_table']) .".comment_id = '". esc_sql($comment['id']) ."'
				AND ". esc_sql($data_property['votes_table']) .".value = 'up'
		");
		
		$tally_votes_down = get_var("
			SELECT
				COUNT(*)
			FROM `". esc_sql($data_property['votes_table']) ."`
			WHERE
				". esc_sql($data_property['votes_table']) .".comment_id = '". esc_sql($comment['id']) ."'
				AND ". esc_sql($data_property['votes_table']) .".value = 'down'
		");
		
		if(is_null($tally_votes_up)) {
			$tally_votes_up = 0;
		}
		
		if(is_null($tally_votes_down)) {
			$tally_votes_down = 0;
		}
		
		if(($tally_votes_up != $comment['votes_up']) || ($tally_votes_down != $comment['votes_down'])) {
			db_query("
				UPDATE `". esc_sql($data_property['comment_table']) ."`
				SET
					". esc_sql($data_property['comment_table']) .".votes_up = '". esc_sql($tally_votes_up) ."',
					". esc_sql($data_property['comment_table']) .".votes_down = '". esc_sql($tally_votes_down) ."'
				WHERE
					". esc_sql($data_property['comment_table']) .".id = '". esc_sql($comment['id']) ."'
			");
		}
		
		// clear cache on user
		cache_delete('authed_user_by_id:'. $authed_user['id']);
		
		if(!empty($data_property['cache_keys'])) {
			foreach($data_property['cache_keys'] as $cache_key) {
				cache_delete($cache_key);
			}
		}
		
		// update user's last activity date
		update_user_activity_date($authed_user);
		
		// done
		die_json('success', array(
			'action' => $ajax_action,
			$data_property['comment_table_entity_column'] => $comment[ $data_property['comment_table_entity_column'] ],
			'comment_id' => $comment['id'],
			'intent' => ((isset($_REQUEST['intent']) && ("remove" === strtolower(trim($_REQUEST['intent']))))?"remove":"save"),
			'value' => strtolower(trim($_REQUEST['value'])),
			'display_value' => (($tally_votes_up < $tally_votes_down)?"-":"+") . number_format( abs($tally_votes_up - $tally_votes_down) ),
		));
	}
	
	
	// nothing else to do, show error
	die_json('error', array(), "AJAX method was not specified or active");