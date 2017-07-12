<?php
	// pull manifest
	$raw_manifest = get_current_manifest();
	
	$db_file = (isset($raw_manifest['mobileWorldContentPaths']['en'])?basename($raw_manifest['mobileWorldContentPaths']['en']):false);
	
	if(empty($db_file) || empty($raw_manifest['version']) || !is_file(MANIFEST_DIR . $raw_manifest['version'] ."/". $db_file)) {
		redirect_to(home_url());
	}
	
	$destiny_manifest = new Bungie_Destiny_Manifest($raw_manifest, MANIFEST_DIR . $raw_manifest['version'] ."/", $lang="en");
	
	// specific weapon ids
	$weapon_ids = array(
		"-226389881",   // Grasp of Malok
	);
	
	@include(TEMPLATES ."header.php");
?>
	
	<div class="container">
		<ol class="breadcrumb">
			<li><a href="<?=home_url();?>">Home</a></li>
			<!-- <li><a href="<?=site_url("/weapons/");?>">Weapons</a></li> -->
			<li>Weapons</li>
		</ol>
		
		<h1>Weapons</h1>
		
		<table class="table">
			<thead>
				<tr>
					<th>Image</th>
					<th>Name</th>
					<th>Slot</th>
					<th>Type</th>
					<th>ID</th>
				</tr>
			</thead>
			<tbody>
				<?php
					$db = new SQLite3(MANIFEST_DIR . $raw_manifest['version'] ."/". $db_file);
					
					$result = $db->query("
						SELECT
							*
						FROM `DestinyInventoryItemDefinition`
						WHERE
							`id` IN ('". implode("','", esc_sql($weapon_ids)) ."')
					");
					
					while($row = $result->fetchArray()):
						$weapon_data = json_decode($row['json'], true);
						$view_url = site_url("/weapon/?id=". urlencode($row['id']));
						$bucket = $destiny_manifest->get_definition('DestinyInventoryBucketDefinition', $weapon_data['bucketTypeHash']);
				?>
					<tr>
						<td><a href="<?=esc_attr($view_url);?>"><img src="<?=esc_attr(DESTINY_CONTENT_BASE . ltrim($weapon_data['icon'], "/"));?>" alt="<?=esc_attr($weapon_data['itemName']);?>" /></a></td>
						<td><a href="<?=esc_attr($view_url);?>"><?=esc_html($weapon_data['itemName']);?></a></td>
						<td><?=esc_html($bucket['bucketName']);?></td>
						<td><?=esc_html($weapon_data['itemTypeName']);?></td>
						<td><?=esc_html($row['id']);?></td>
					</tr>
				<?php endwhile; ?>
			</tbody>
		</table>
	</div>
	
<?php
	@include(TEMPLATES ."footer.php");