<?php
	class Bungie_Destiny_Manifest {
		private $manifest = array();
		
		private $data_dir = "";
		private $data_content_file = "";
		
		private $lang = "en";
		
		private $db = null;
		
		private $table_id_cols = array();
		
		public function __construct($raw_manifest, $manifest_data_dir, $lang="en") {
			$this->manifest = (is_array($raw_manifest)?$raw_manifest:json_decode($raw_manifest, true));
			$this->data_dir = rtrim($manifest_data_dir, "/") ."/";
			$this->lang = $lang;
			
			if(!empty($this->manifest['mobileWorldContentPaths'][ $lang ])) {
				$this->data_content_file = $this->data_dir . basename($this->manifest['mobileWorldContentPaths'][ $lang ]);
				
				$this->generate_table_id_cols();
			}
			
			return $this;
		}
		
		// @todo: public function set_lang($lang="en")
		// @todo: public function get_lang()
		
		private function connect_db() {
			if(empty($this->db) || is_null($this->db)) {
				$this->db = null;
				
				if(!empty($this->data_content_file)) {
					$this->db = new SQLite3($this->data_content_file);
				}
			}
		}
		
		private function generate_table_id_cols() {
			$this->connect_db();
			
			if($this->db) {
				$this->table_id_cols = array();
				
				$result = $this->db->query("SELECT name FROM sqlite_master WHERE type='table'");
				
				while($row = $result->fetchArray()) {
					$result2 = $this->db->query("PRAGMA table_info(". $row['name'] .")");
					
					while($row2 = $result2->fetchArray()) {
						if(0 == $row2['cid']) {
							$this->table_id_cols[ strtolower($row['name']) ] = $row2['name'];
						}
					}
				}
			}
		}
		
		public function query($query) {
			$results = array();
			
			$this->connect_db();
			
			if($this->db) {
				//print "Query: ". $query ."<br />\n";
				
				$result = $this->db->query($query);
				
				while($row = $result->fetchArray()) {
					$key = (is_numeric($row[0])?sprintf('%u', $row[0] & 0xFFFFFFFF):$row[0]);
					
					$results[ $key ] = json_decode($row[1]);
				}
			}
			
			return $results;
		}
		
		public function get_definitions($table) {
			return $this->query("
				SELECT
					". esc_sql($table) .".*
				FROM `". esc_sql($table) ."`
			");
		}
		
		public function get_definition($table, $id) {
			if(!isset($this->table_id_cols[ strtolower($table) ])) {
				return false;
			}
			
			$id_col = $this->table_id_cols[ strtolower($table) ];
			
			$wheres = array();
			
			if($id & 0xFFFFFFFF) {
				$wheres[] = "`". esc_sql($id_col) ."` = \"". esc_sql($id) ."\"";
				$wheres[] = "`". esc_sql($id_col) ."` = \"". esc_sql( ($id - 4294967296) ) ."\"";
			} else {
				$wheres[] = "`". esc_sql($id_col) ."` = \"". esc_sql($id) ."\"";
			}
			
			/*$results = $this->query("
				SELECT
					". esc_sql($table) .".*
				FROM `". esc_sql($table) ."`
				WHERE
					". implode(" OR ", $wheres) ."
			");
			
			if(!isset($results[ $id ])) {
				return false;
			}
			
			return $results[ $id ];*/
			
			$result = $this->db->querySingle("
				SELECT
					". esc_sql($table) .".json
				FROM `". esc_sql($table) ."`
				WHERE
					". implode(" OR ", $wheres) ."
			");
			
			if(false === $result) {
				return false;
			}
			
			return json_decode($result, true);
		}
		
		public function get_definition_value($table, $id, $key) {
			if(false === ($result = $this->get_definition($table, $id))) {
				return false;
			}
			
			if(!isset($result[ $key ])) {
				return false;
			}
			
			return $result[ $key ];
		}
		
		public function get_definition_by_key($table, $key_name, $key_value) {
			$result = $this->db->query("
				SELECT
					". esc_sql($table) .".*
				FROM `". esc_sql($table) ."`
			");
			
			while($row = $result->fetchArray()) {
				$row_data = json_decode($row['json'], true);
				
				if(isset($row_data[ $key_name ]) && ($row_data[ $key_name ] == $key_value)) {
					return $row_data;
				}
			}
			
			return false;
		}
	}