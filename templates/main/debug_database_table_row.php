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
			*
		FROM `". esc_sql($db_table) ."`
		WHERE
			`id` = '". esc_sql($_REQUEST['id']) ."'
	");
	
	$row = $result1->fetchArray();
	
	if(!isset($row[0])) {
		die("Unable to find requested row");
	}
	
	function is_json($string) {
		return in_array(substr($string, 0, 1), array("{", "["));
	}
	
	function displayRawRowValue($value) {
		?>
		<?php if(is_array($value)): ?>
			<pre><?=esc_html(print_r($value, true));?></pre>
		<?php elseif(preg_match("#\.(jpe?g|gif|png)$#si", $value)): ?>
			<img src="<?=esc_attr(DESTINY_CONTENT_BASE . ltrim($value, "/"));?>" alt="<?=esc_attr($value);?>" />
		<?php else: ?>
			<?=esc_html($value);?>
		<?php endif;
	}
	
	@include(TEMPLATES ."header.php");
?>
	<div class="container">
		<ol class="breadcrumb">
			<li><a href="<?=home_url();?>">Home</a></li>
			<li><a href="<?=site_url("/debug_databases/");?>">Databases</a></li>
			<li><a href="<?=site_url("/debug_database_tables/?db_file=". urlencode($db_file) ."&db_title=". urlencode($db_title));?>"><?=esc_html($db_title);?></a></li>
			<li><a href="<?=site_url("/debug_database_table/?db_file=". urlencode($db_file) ."&db_title=". urlencode($db_title) ."&db_table=". urlencode($db_table));?>"><?=esc_html($db_table);?></a></li>
			<li><?=esc_html($row['id']);?></li>
		</ol>
		
		<h1><?=esc_html($db_table ." :: ". $row[0]);?></h1>
		
		<div class="form-horizontal">
			<?php $row_values = json_decode($row['json'], true); foreach($row_values as $row_key => $row_value): ?>
			<div class="form-group">
				<div class="col-sm-3 text-right"><strong><?=esc_html($row_key);?></strong></div>
				<div class="col-sm-9">
					<?php displayRawRowValue($row_value); ?>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
	
<?php
	@include(TEMPLATES ."footer.php");