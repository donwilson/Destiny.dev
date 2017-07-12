<?php
	$current_manifest = get_current_manifest();
	
	$paged = ((!empty($_REQUEST['paged']) && is_numeric($_REQUEST['paged']))?intval($_REQUEST['paged']):1);
	$per_page = 20;
	$num_items = 0;
	$num_pages = 0;
	
	//die("<pre>". print_r($_REQUEST, true) ."</pre>\n");
	
	$db_file = ((!empty($_REQUEST['db_file']) && (false == strpos($_REQUEST['db_file'], "/")))?$_REQUEST['db_file']:"");
	$db_table = (!empty($_REQUEST['db_table'])?$_REQUEST['db_table']:"");
	
	if(empty($db_file) || empty($current_manifest['version']) || !is_file(MANIFEST_DIR . $current_manifest['version'] ."/". $db_file)) {
		redirect_to(site_url("/debug_databases/"));
	}
	
	$db_title = (!empty($_REQUEST['db_title'])?$_REQUEST['db_title']:$db_file);
	
	if(empty($db_table)) {
		redirect_to(site_url("/debug_database_tables/?db_file=". urlencode($db_file) ."&db_title=". urlencode($db_title)));
	}
	
	
	$db = new SQLite3(MANIFEST_DIR . $current_manifest['version'] ."/". $db_file);
	
	$table_cols = array();
	
	$result5 = $db->query("SELECT * FROM `". esc_sql($db_table) ."` LIMIT 1");
	$first_row = $result5->fetchArray();
	$first_row_json_cols = array();
	
	if(!empty($first_row['json'])) {
		$first_row_json = json_decode($first_row['json'], true);
		
		$first_row_json_cols = array_keys($first_row_json);
	}
	
	$result0 = $db->query("PRAGMA table_info(". esc_sql($db_table) .")");
	
	while($row0 = $result0->fetchArray()) {
		$col_name = $row0[1];
		
		if("json" != $col_name) {
			$table_cols[] = $col_name;
		}
	}
	
	$result1 = $db->query("
		SELECT
			Count(*)
		FROM `". esc_sql($db_table) ."`
		". (!empty($_REQUEST['query'])?" WHERE `json` LIKE '%". esc_sql($_REQUEST['query']) ."%' ":"") ."
	");
	
	$row1 = $result1->fetchArray();
	
	if(!empty($row1[0])) {
		$num_items = $row1[0];
		
		$num_pages = ceil( ($num_items / $per_page) );
	}
	
	function is_json($string) {
		return in_array(substr($string, 0, 1), array("{", "["));
	}
	
	@include(TEMPLATES ."header.php");
?>
	<div class="container">
		<ol class="breadcrumb">
			<li><a href="<?=home_url();?>">Home</a></li>
			<li><a href="<?=site_url("/debug_databases/");?>">Databases</a></li>
			<li><a href="<?=site_url("/debug_database_tables/?db_file=". urlencode($db_file) ."&db_title=". urlencode($db_title));?>"><?=esc_html($db_title);?></a></li>
			<li><?=esc_html($db_table);?></li>
		</ol>
		
		<h1><?=esc_html($db_title ." :: ". $db_table);?></h1>
		
		<form method="get" action="<?=site_url("/debug_database_table/");?>">
			<input type="hidden" name="db_file" value="<?=esc_attr($db_file);?>" />
			<input type="hidden" name="db_title" value="<?=esc_attr($db_title);?>" />
			<input type="hidden" name="db_table" value="<?=esc_attr($db_table);?>" />
			
			<p>Database: <a href="<?=site_url("/debug_database_tables/?db_file=". urlencode($db_file) ."&db_title=". urlencode($db_title));?>"><?=esc_html($db_title);?></a> / Table: <a href="<?=site_url("/debug_database_table/?db_file=". urlencode($db_file) ."&db_title=". urlencode($db_title) ."&db_table=". urlencode($db_table));?>"><?=esc_html($db_table);?></a></p>
			<p>Browsing page <input type="text" name="paged" value="<?=esc_attr($paged);?>" style="width: 50px; text-align: center;" onfocus="this.focus();this.select();" /> of <strong><?=esc_html(number_format($num_pages));?></strong> <input type="submit" value="Go" style="font-weight: bold;" /></p>
		</form>
		
		<p>Page: <?php for($i = 1; $i <= $num_pages; $i++): ?>
			<?php if($i == $paged): ?><strong><?=esc_html( number_format($i) );?></strong><?php else: ?><a href="<?=site_url("/debug_database_table/?db_file=". urlencode($db_file) ."&db_title=". urlencode($db_title) ."&db_table=". urlencode($db_table) . (!empty($_REQUEST['query'])?"&query=". urlencode($_REQUEST['query']):"") ."&paged=". $i);?>"><?=esc_html( number_format($i) );?></a><?php endif; ?>
		<?php endfor; ?></p>
		
		<table class="table">
			<thead>
				<tr>
					<?php foreach($table_cols as $table_col): ?>
					<th><?=esc_html($table_col);?></th>
					<?php endforeach; ?>
					<?php foreach($first_row_json_cols as $first_row_json_col): ?>
					<th><u><?=esc_html($first_row_json_col);?></u></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php
					$result = $db->query("
						SELECT
							". esc_sql($db_table) .".*
						FROM `". esc_sql($db_table) ."`
						". (!empty($_REQUEST['query'])?" WHERE `json` LIKE '%". esc_sql($_REQUEST['query']) ."%' ":"") ."
						LIMIT ". esc_sql( ($per_page * ($paged - 1)) ) .", ". esc_sql($per_page) ."
					");
					
					while($row = $result->fetchArray()): if(isset($row['json'])) { $row['json'] = json_decode($row['json'], true); } ?>
					<tr>
						<?php foreach($table_cols as $table_col): ?>
							
							<?php if("id" == $table_col): ?>
								<td valign="top"><a href="<?=site_url("/debug_database_table_row/?db_file=". urlencode($db_file) ."&db_title=". urlencode($db_title) ."&db_table=". urlencode($db_table) ."&id=". urlencode($row[ $table_col ]));?>"><?=esc_html($row[ $table_col ]);?></a></td>
							<?php else: ?>
								<td valign="top"><?=(is_json($row[ $table_col ])?"<pre>". esc_html(print_r(json_decode($row[ $table_col ], true), true)) ."</pre>":esc_html($row[ $table_col ]));?></td>
							<?php endif; ?>
						<?php endforeach; ?>
						<?php foreach($first_row_json_cols as $first_row_json_col): ?>
						<td valign="top"><?php
							$value = "";
							
							if(isset($row['json'][ $first_row_json_col ])) {
								$value = $row['json'][ $first_row_json_col ];
							}
							
							if(is_string($value)) {
								if(preg_match("#\.(jpe?g|png|gif)$#si", $value)) {
									// image
									print "<img src=\"". esc_attr(DESTINY_CONTENT_BASE . ltrim($value, "/")) ."\" alt=\"". esc_attr($value) ."\" style=\"max-width: 100px; max-height: 100px;\" />";
								} else {
									print esc_html($value);
								}
							} else {
								print "<pre>". esc_html(print_r($value, true)) ."</pre>";
							}
						?></td>
						<?php endforeach; ?>
					</tr>
				<?php endwhile; ?>
			</tbody>
		</table>
		
		<p>Page: <?php for($i = 1; $i <= $num_pages; $i++): ?>
			<?php if($i == $paged): ?><strong><?=esc_html( number_format($i) );?></strong><?php else: ?><a href="<?=site_url("/debug_database_table/?db_file=". urlencode($db_file) ."&db_title=". urlencode($db_title) ."&db_table=". urlencode($db_table) . (!empty($_REQUEST['query'])?"&query=". urlencode($_REQUEST['query']):"") ."&paged=". $i);?>"><?=esc_html( number_format($i) );?></a><?php endif; ?>
		<?php endfor; ?></p>
	</div>
	
<?php
	@include(TEMPLATES ."footer.php");