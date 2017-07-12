<?php
	$membership_id = (!empty($_REQUEST['membership_id'])?$_REQUEST['membership_id']:false);
	$character_id = (!empty($_REQUEST['character'])?$_REQUEST['character']:false);
	
	if(empty($membership_id) || empty($character_id)) {
		redirect_to(home_url());
	}
	
	@include(TEMPLATES ."header.php");
?>
	
	<div id="main">
		<h1>Character</h1>
		
		<?php
			try {
				$raw_manifest = get_current_manifest();
				
				if(empty($raw_manifest['version'])) {
					throw new Exception("Manifest out of date");
				}
				
				$destiny_manifest = new Bungie_Destiny_Manifest($raw_manifest, MANIFEST_DIR . $raw_manifest['version'] ."/", $lang="en");
				
				$data = cache_get("destiny:character:membership_id__". $membership_id .":character_id__". $character_id, function() use ($membership_id, $character_id) {
					$destiny_api = new Bungie_Destiny_API(DESTINY_API_KEY, DESTINY_API_BASE);
					
					$destiny_api->setEndpoint("/{membershipType}/Account/{destinyMembershipId}/Character/{characterId}/Inventory/", [
						'membershipType'		=> "1",
						'destinyMembershipId'	=> $membership_id,
						'characterId'			=> $character_id,
					]);
					
					$destiny_api->exec();
					
					return $destiny_api->getResponse();
				});
				
				//die("<pre>". print_r(array(
				//	'data'	=> $data,
				//	//'debug'	=> $destiny_api->getDebug(),
				//), true) ."</pre>\n");
				
				if(!empty($data['ErrorStatus']) && ("Success" !== $data['ErrorStatus'])) {
					throw new Exception($data['Message']);
				}
				?>
				
				<?php
					$stats = array();
					$raw_stats = $destiny_manifest->get_definitions('DestinyStatDefinition');
					
					if(!empty($raw_stats)) {
						foreach($raw_stats as $raw_stat) {
							$raw_stat = (array)$raw_stat;
							
							$stats[ $raw_stat['statHash'] ] = $raw_stat;
						}
					}
					
					foreach($data['Response']['data']['buckets']['Equippable'] as $equippable):
						$bucket = $destiny_manifest->get_definition('DestinyInventoryBucketDefinition', $equippable['bucketHash']);
						$bucket_key = trim(preg_replace("#^bucket_#si", "", strtolower($bucket['bucketIdentifier'])), "_");
						
						if("build" == $bucket_key) { continue; }
				?>
					<h1><?=esc_html($bucket_key);?></h1>
					
					<?php foreach($equippable['items'] as $equippable_item):
						$item = $destiny_manifest->get_definition('DestinyInventoryItemDefinition', $equippable_item['itemHash']);
						$itemInstanceId = $equippable_item['itemInstanceId'];
					?>
						<h2><?=esc_html($item['itemName']);?></h2>
						
						<img src="<?=DESTINY_CONTENT_BASE . $item['icon'];?>" /><br />
						
						
						<h3>Perks</h3>
						<table cellpadding="5" cellspacing="0" border="0">
							<?php foreach($equippable_item['perks'] as $equip_perk): $perk = $destiny_manifest->get_definition('DestinySandboxPerkDefinition', $equip_perk['perkHash']); ?>
							<tr>
								<td><strong><?=esc_html($perk['displayName']);?></strong></td>
								<td>
									<?php /*if(!empty($stat['icon']) && !preg_match("#missing_icon#si", $stat['icon'])): ?><span style="display: inline-block; width: 24px; height: 20px; background-color: #c0c0c0;"><img src="<?=DESTINY_CONTENT_BASE . $stat['icon'];?>" width="20" height="20" align="left" /></span>&nbsp; &nbsp;<?php endif;*/ ?><?=esc_html($perk['displayDescription']);?>
									
									<?php /*<pre><?=print_r(array('equip_perk' => $equip_perk, 'perk' => $perk), true);?></pre>*/ ?>
								</td>
							</tr>
							<?php endforeach; ?>
						</table>
						
						<h3>Stats</h3>
						<table cellpadding="5" cellspacing="0" border="0">
							<?php foreach($equippable_item['stats'] as $equip_stat): $stat = $stats[ $equip_stat['statHash'] ]; ?>
							<tr>
								<td><strong><?=esc_html($stat['statName']);?></strong></td>
								<td>
									<div style="position: relative; display: inline-block; width: 100px; height: 10px; background-color: #c0c0c0;">
										<div style="position: absolute; top: 0; left: 0; display: inline-block; width: <?=( (($equip_stat['value'] / $equip_stat['maximumValue']) * 100) );?>px; height: 10px; background-color: #000;"></div>
									</div>
								</td>
								<td>
									<?php /*if(!empty($stat['icon']) && !preg_match("#missing_icon#si", $stat['icon'])): ?><span style="display: inline-block; width: 24px; height: 20px; background-color: #c0c0c0;"><img src="<?=DESTINY_CONTENT_BASE . $stat['icon'];?>" width="20" height="20" align="left" /></span>&nbsp; &nbsp;<?php endif;*/ ?><?=esc_html($stat['statDescription']);?>
									
									<?php /*<pre><?=print_r(array('equip_stat' => $equip_stat, 'stat' => $stat), true);?></pre>*/ ?>
								</td>
							</tr>
							<?php endforeach; ?>
						</table>
						
						<pre><?php print_r($equippable_item); ?></pre>
					<?php endforeach; ?>
				
				<?php endforeach; ?>
				
				<?php
				print "<hr /><pre>". print_r($data, true) ."</pre>\n";
			} catch(Exception $e) {
				print "<div class=\"error\">Error (". $e->getCode() ."): ". $e->getMessage() ."</div>\n";
			}
		?>
	</div>
	
	<?php ob_start(); ?>
	<style type="text/css">
		
	</style>
	<script type="text/javascript">
		;jQuery(document).ready(function($) {
			
		});
	</script>
	<?php enqueue_to_body_append(ob_get_clean()); ?>
	
<?php
	@include(TEMPLATES ."footer.php");