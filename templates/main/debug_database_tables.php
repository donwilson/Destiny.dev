<?php
	$current_manifest = get_current_manifest();
	
	//die("<pre>". print_r($_REQUEST, true) ."</pre>\n");
	
	$db_file = ((!empty($_REQUEST['db_file']) && (false == strpos($_REQUEST['db_file'], "/")))?$_REQUEST['db_file']:"");
	
	if(empty($db_file) || empty($current_manifest['version']) || !is_file(MANIFEST_DIR . $current_manifest['version'] ."/". $db_file)) {
		redirect_to(site_url("/debug_databases/"));
	}
	
	$db_title = (!empty($_REQUEST['db_title'])?$_REQUEST['db_title']:$db_file);
	
	@include(TEMPLATES ."header.php");
?>
	
	<div class="container">
		<ol class="breadcrumb">
			<li><a href="<?=home_url();?>">Home</a></li>
			<li><a href="<?=site_url("/debug_databases/");?>">Databases</a></li>
			<li><?=esc_html($db_title);?></li>
		</ol>
		
		<h1><?=esc_html($db_title);?></h1>
		
		<?php
			$db = new SQLite3(MANIFEST_DIR . $current_manifest['version'] ."/". $db_file);
			
			$result = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
			
			while($row = $result->fetchArray()) {
				$table_cols = array();
				$table_name = $row['name'];
				
				$result2 = $db->query("PRAGMA table_info(". esc_sql($table_name) .")");
				
				while($row2 = $result2->fetchArray()) {
					if("json" != $row2[1]) {
						$table_cols[] = $row2[1];
					}
				}
				
				$result5 = $db->query("SELECT * FROM `". esc_sql($table_name) ."` LIMIT 1");
				$first_row = $result5->fetchArray();
				$first_row_json_cols = array();
				
				if(!empty($first_row['json'])) {
					$first_row_json = json_decode($first_row['json'], true);
					
					$first_row_json_cols = array_keys($first_row_json);
				}
				?>
				
				<h3><a href="<?=site_url("/debug_database_table/?db_file=". urlencode($db_file) ."&db_title=". urlencode($db_title) ."&db_table=". urlencode($table_name));?>"><?=esc_html($table_name);?></a></h3>
				
				<table class="table">
					<tr>
						<?php foreach($table_cols as $table_col): ?>
						<td><?=esc_html($table_col);?></td>
						<?php endforeach; ?>
						<?php foreach($first_row_json_cols as $first_row_json_col): ?>
						<th><u><?=esc_html($first_row_json_col);?></u></th>
						<?php endforeach; ?>
					</tr>
				</table>
				
				<hr />
				<?php
			}
		?>
	</div>
	
<?php
	@include(TEMPLATES ."footer.php");