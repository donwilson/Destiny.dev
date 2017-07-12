<?php
	// pull manifest
	$current_manifest = get_current_manifest();
	
	@include(TEMPLATES ."header.php");
?>
	
	<div class="container">
		<ol class="breadcrumb">
			<li><a href="<?=home_url();?>">Home</a></li>
			<li>Databases</li>
		</ol>
		
		<h1>Databases</h1>
		
		<ul>
			<?php
				$local_dbs = array();
				
				if(!empty($current_manifest['mobileAssetContentPath'])) {
					$local_dbs[] = array('key' => "mobileAssetContentPath", 'path' => $current_manifest['mobileAssetContentPath']);
					
					print "<li><a href=\"". site_url("/debug_database_tables/?db_title=". urlencode("mobileAssetContentPath") ."&db_file=". urlencode(basename($current_manifest['mobileAssetContentPath']))) ."\">". esc_html("mobileAssetContentPath") ."</a></li>\n";
				}
				
				if(!empty($current_manifest['mobileGearAssetDataBases'])) {
					foreach($current_manifest['mobileGearAssetDataBases'] as $asset_db_key => $asset_db) {
						print "<li><a href=\"". site_url("/debug_database_tables/?db_title=". urlencode("mobileGearAssetDataBases[". $asset_db_key ."]") ."&db_file=". urlencode(basename($asset_db['path']))) ."\">". esc_html("mobileGearAssetDataBases.". $asset_db_key) ."</a></li>\n";
					}
				}
				
				if(!empty($current_manifest['mobileWorldContentPaths'])) {
					foreach($current_manifest['mobileWorldContentPaths'] as $content_lang => $content_path) {
						print "<li>";
						
						if("en" === $content_lang) {
							print "<strong>";
						}
						
						print "<a href=\"". site_url("/debug_database_tables/?db_title=". urlencode("mobileWorldContentPaths[". $content_lang ."]") ."&db_file=". urlencode(basename($content_path))) ."\">". esc_html("mobileWorldContentPaths.". $content_lang) ."</a>";
						
						if("en" === $content_lang) {
							print "</strong>";
						}
						
						print "</li>\n";
					}
				}
			?>
		</ul>
	</div>
	
<?php
	@include(TEMPLATES ."footer.php");