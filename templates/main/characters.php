<?php
	$membership_id = (!empty($_REQUEST['membership_id'])?$_REQUEST['membership_id']:false);
	
	if(empty($membership_id)) {
		redirect_to(home_url());
	}
	
	$raw_manifest = get_current_manifest();
	
	if(empty($raw_manifest['version'])) {
		throw new Exception("Manifest out of date");
	}
	
	$destiny_manifest = new Bungie_Destiny_Manifest($raw_manifest, MANIFEST_DIR . $raw_manifest['version'] ."/", $lang="en");
	
	$data = cache_get("destiny:characters:membership_id__". $membership_id, function() use ($membership_id) {
		$destiny_api = new Bungie_Destiny_API(DESTINY_API_KEY, DESTINY_API_BASE);
		
		$destiny_api->setEndpoint("/{membershipType}/Account/{destinyMembershipId}/Summary/", [
			'membershipType'		=> "1",
			'destinyMembershipId'	=> $membership_id,
		]);
		
		$destiny_api->exec();
		
		return $destiny_api->getResponse();
	});
	
	$account = $data['Response']['data'];
	
	//print "<pre>". print_r(array(
	//	'data'	=> $data,
	//	'debug'	=> $destiny_api->getDebug(),
	//), true) ."</pre>\n";
	
	@include(TEMPLATES ."header.php");
?>
	
	<style type="text/css">
		.character_equipment_item { display: inline-block; position: relative; }
			.character_equipment_item .image { display: block; margin: 0; padding: 0; }
			.character_equipment_item .progress { position: absolute; z-index: 50; bottom: 0; left: 0; display: inline-block; width: 0; height: 10px; background-color: #00cc00; }
		
			.character_equipment_item.small { width: 30px; height: 30px; display: inline-block; margin-right: 5px; }
				.character_equipment_item.small .image { width: 30px; height: 30px; }
	</style>
	
	<div id="main">
		<h1>Character List</h1>
		
		<h3><?=esc_html("Grimoire Score: ". number_format($account['grimoireScore']));?></h3>
		
		<?php if(!empty($data['ErrorStatus']) && ("success" === strtolower($data['ErrorStatus']))): ?>
				<table class="table" width="100%">
					<thead>
						<tr>
							<th width="100" align="center">Emblem</th>
							<th align="left">Character</th>
							<th>Loadout</th>
							<th></th>
							<th></th>
							<th align="right">debug</th>
						</tr>
					</thead>
					<tbody>
						<?php
							function displayCharacterEquipmentItem($item, $size=false) {
								?>
								<span class="character_equipment_item <?php if("small" === $size): ?> small <?php endif; ?>">
									<img src="<?=esc_attr(DESTINY_CONTENT_BASE . $item[0]['icon']);?>" alt="<?=esc_attr($item[0]['name']);?>"  class="image" />
								</span>
								<?php
							}
							
							function buildCharacterEquipmentObject($equipment_array, $destiny_manifest) {
								$items = array();
								
								if(empty($equipment_array) || !($destiny_manifest instanceof Bungie_Destiny_Manifest)) {
									return false;
								}
								
								$item_cats = array();
								$raw_item_cats = $destiny_manifest->get_definitions('DestinyItemCategoryDefinition');
								
								if(!empty($raw_item_cats)) {
									foreach($raw_item_cats as $raw_item_cat) {
										$item_cats[ $raw_item_cat->itemCategoryHash ] = $raw_item_cat;
									}
								}
								
								foreach($equipment_array as $equipment) {
									$equipment_def = $destiny_manifest->get_definition('DestinyInventoryItemDefinition', $equipment['itemHash']);
									
									$bucket = $destiny_manifest->get_definition('DestinyInventoryBucketDefinition', $equipment_def['bucketTypeHash']);
									$bucket_key = trim(preg_replace("#^bucket_#si", "", strtolower($bucket['bucketIdentifier'])), "_");
									
									$item = array(
										'itemHash' => $equipment_def['itemHash'],
										'name' => $equipment_def['itemName'],
										'icon' => (!empty($equipment_def['hasIcon'])?$equipment_def['icon']:false),
										'bucket' => $bucket,
										'itemCategories' => array(),
										'_raw_equipment' => $equipment,
										'_raw_item' => $equipment_def,
									);
									
									foreach($equipment_def['itemCategoryHashes'] as $item_cat_hash) {
										$item_category = $item_cats[ $item_cat_hash ];
										
										$item['itemCategories'][] = array(
											'itemCategoryHash' => $item_category->itemCategoryHash,
											'identifier' => $item_category->identifier,
										);
									}
									
									if(!isset($items[ $item['bucket']['bucketIdentifier'] ])) {
										$items[ $bucket_key ] = array();
									}
									
									$items[ $bucket_key ][] = $item;
								}
								
								return $items;
							}
							
							foreach($data['Response']['data']['characters'] as $character):
								$character_id = $character['characterBase']['characterId'];
								$character_url = site_url("/character/?membership_id=". $membership_id ."&character=". $character_id);
								
								$character_class = $destiny_manifest->get_definition_by_key('DestinyClassDefinition', 'classType', $character['characterBase']['classType']);
								$character_race = $destiny_manifest->get_definition_value('DestinyRaceDefinition', $character['characterBase']['raceHash'], 'raceName');
								
								$time_played_raw = $character['characterBase']['minutesPlayedTotal'];
								$time_played = floor( ($time_played_raw / 60) ) ."hr ". round( ($time_played_raw % 60) ) ."min";
								
								$character_equipment = buildCharacterEquipmentObject($character['characterBase']['peerView']['equipment'], $destiny_manifest);
						?>
						<tr>
							<td width="100" valign="top" align="center">
								<a href="<?=$character_url;?>" style="display: block; width: 96px; height: 96px; position: relative;">
									<img src="<?=DESTINY_CONTENT_BASE . $character['emblemPath'];?>" />
									<img src="<?=DESTINY_CONTENT_BASE . $character_equipment['build'][0]['icon'];?>" width="24" height="24" style="display: block; position: absolute; z-index: 50; bottom: 0; right: 0;" />
								</a>
							</td>
							<td valign="top" align="left">
								<a href="<?=$character_url;?>" style="font-size: 24px;"><strong><?=esc_html($character_class['className']);?></strong></a><br />
								<ul>
									<li>Light: <strong><?=$character['characterBase']['stats']['STAT_LIGHT']['value'];?></strong></li>
									<li>Level: <strong><?=$character['characterLevel'];?></strong></li>
									<li>Time Played: <strong><?=$time_played;?></strong></li>
									<li>Race: <strong><?=esc_html($character_race);?></strong></li>
								</ul>
							</td>
							<td valign="top">
								<table cellspacing="5" cellpadding="5" border="5" bordercolor="#ffffff" bgcolor="#f0f0f0">
									<tr>
										<td width="120" valign="top" align="left" bgcolor="#ffffff">
											<?php displayCharacterEquipmentItem($character_equipment['primary_weapon']); ?>
										</td>
										<td width="120" valign="top" align="left" bgcolor="#ffffff">
											<?php displayCharacterEquipmentItem($character_equipment['special_weapon']); ?>
										</td>
										<td width="120" valign="top" align="left" bgcolor="#ffffff">
											<?php displayCharacterEquipmentItem($character_equipment['heavy_weapon']); ?>
										</td>
										<td width="100" valign="bottom" align="left" bgcolor="#ffffff">
											
											<?php displayCharacterEquipmentItem($character_equipment['vehicle'], "small"); ?>
											<?php displayCharacterEquipmentItem($character_equipment['class_items'], "small"); ?>
											<?php displayCharacterEquipmentItem($character_equipment['ghost'], "small"); ?>
											<?php displayCharacterEquipmentItem($character_equipment['artifact'], "small"); ?>
										</td>
									</tr>
									<tr>
										<td width="120" valign="top" align="left" bgcolor="#ffffff">
											<?php displayCharacterEquipmentItem($character_equipment['head']); ?>
										</td>
										<td width="120" valign="top" align="left" bgcolor="#ffffff">
											<?php displayCharacterEquipmentItem($character_equipment['arms']); ?>
										</td>
										<td width="120" valign="top" align="left" bgcolor="#ffffff">
											<?php displayCharacterEquipmentItem($character_equipment['chest']); ?>
										</td>
										<td width="120" valign="top" align="left" bgcolor="#ffffff">
											<?php displayCharacterEquipmentItem($character_equipment['legs']); ?>
										</td>
									</tr>
								</table>
							</td>
							<td valign="top"></td>
							<td valign="top"></td>
							<td valign="top" align="right"><button role="button" class="toggle_collapse" data-target="#<?="log_char_". $character_id;?>">Debug</button></td>
						</tr>
						<tr id="<?="log_char_". $character_id;?>" class="hidden" style="background-color: #f0f0f0;">
							<td colspan="6" valign="top" align="left">
								<?php /*<pre><?=print_r($character, true);?></pre>*/ ?>
								<pre><?=print_r($character_equipment['special_weapon'], true);?></pre>
							</td>
						</tr>
						<tr><td colspan="6" style="background-color: #f0f0f0;">&nbsp;</td></tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				
				<button role="button" class="toggle_collapse" data-target="#log_char_full_debug">Full Debug</button>
				<div id="log_char_full_debug" class="hidden"><hr /><pre><?=print_r($data['Response']['data'], true);?></pre></div>
		<?php else: ?>
			<div class="error">Error - <?=esc_html($data['Message']);?></div>
		<?php endif; ?>
	</div>
	
	<?php ob_start(); ?>
	<style type="text/css">
		.hidden { display: none; }
	</style>
	<script type="text/javascript">
		;jQuery(document).ready(function($) {
			$(".toggle_collapse").on('click', function(e) {
				var target = $(this).attr('data-target') || "";
				
				e.preventDefault();
				
				if(!target || !$(target).length) {
					return;
				}
				
				$(target).toggleClass("hidden");
			});
		});
	</script>
	<?php enqueue_to_body_append(ob_get_clean()); ?>
	
<?php
	@include(TEMPLATES ."footer.php");