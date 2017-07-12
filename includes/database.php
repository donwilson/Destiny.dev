<?php
	function db_connect() {
		if(!isset($GLOBALS['db_instance'])) {
			$GLOBALS['db_instance'] = @mysql_connect(DB_HOST, DB_USER, DB_PASSWORD) or die("Could not connect to the database server.");
			@mysql_select_db(DB_NAME, $GLOBALS['db_instance']) or die("Could not connect to the database.");
			
			@mysql_set_charset('utf8', $GLOBALS['db_instance']);
			
			@mysql_query("SET NAMES utf8", $GLOBALS['db_instance']);
		}
	}
	
	function db_change_database($database_name=false) {
		db_connect();
		
		if((false === $database_name) || ("" === $database_name)) {
			$database_name = DB_NAME;
		}
		
		@mysql_select_db($database_name, $GLOBALS['db_instance']);
	}
	
	function esc_sql($string) {
		db_connect();
		
		if(is_array($string)) {
			return array_map('esc_sql', $string);
		}
		
		return @mysql_real_escape_string($string, $GLOBALS['db_instance']);
	}
	
	function db_query($query) {
		db_connect();
		
		if(false === ($result = @mysql_query($query, $GLOBALS['db_instance']))) {
			if(defined('PRINT_SQL_ERRORS') && PRINT_SQL_ERRORS) {
				die(mysql_error($GLOBALS['db_instance']));
			}
			
			return false;
		}
		
		if(preg_match("#^\s*INSERT#si", $query)) {
			return @mysql_insert_id($GLOBALS['db_instance']);
		}
		
		return $result;
	}
	
	function get_rows($query, $column_key=false) {
		if(false === ($result = db_query($query))) {
			return false;
		}
		
		$rows = array();
		
		if(@mysql_num_rows($result) > 0) {
			while($row = mysql_fetch_assoc($result)) {
				if((false !== $column_key) && isset($row[ $column_key ])) {
					$rows[ $row[ $column_key ] ] = $row;
				} else {
					$rows[] = $row;
				}
			}
		}
		
		return $rows;
	}	function get_results($query, $column_key=false) { return get_rows($query, $column_key); }
	
	function get_col($query, $column_key=0) {
		if(false === ($result = db_query($query))) {
			return false;
		}
		
		$rows = array();
		
		if(@mysql_num_rows($result) > 0) {
			while($row = mysql_fetch_array($result)) {
				if(isset($row[ $column_key ])) {
					$rows[] = $row[ $column_key ];
				} else {
					$rows[] = array_shift($row);
				}
			}
		}
		
		return $rows;
	}
	
	function get_row($query) {
		if((false === ($result = db_query($query))) || (@mysql_num_rows($result) <= 0)) {
			return false;
		}
		
		return mysql_fetch_assoc($result);
	}
	
	function get_var($query, $column=false) {
		if(false === ($row = get_row($query))) {
			return false;
		}
		
		if(false !== $column) {
			if(isset($row[ $column ])) {
				return $row[ $column ];
			}
			
			return false;
		}
		
		return array_shift($row);
	}