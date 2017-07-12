<?php
	// pull manifest
	$raw_manifest = get_current_manifest();
	
	$db_file = (isset($raw_manifest['mobileWorldContentPaths']['en'])?basename($raw_manifest['mobileWorldContentPaths']['en']):false);
	
	if(empty($db_file) || empty($raw_manifest['version']) || !is_file(MANIFEST_DIR . $raw_manifest['version'] ."/". $db_file)) {
		redirect_to(home_url());
	}
	
	if(!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id'])) {
		redirect_to(site_url("/weapons/"));
	}
	
	$destiny_manifest = new Bungie_Destiny_Manifest($raw_manifest, MANIFEST_DIR . $raw_manifest['version'] ."/", $lang="en");
	
	// pull weapon
	$weapon = $destiny_manifest->get_definition('DestinyInventoryItemDefinition', $_REQUEST['id']);
	$bucket = $destiny_manifest->get_definition('DestinyInventoryBucketDefinition', $weapon['bucketTypeHash']);
	$primaryBaseStat = $destiny_manifest->get_definition('DestinyStatDefinition', $weapon['primaryBaseStatHash']);
	
	$perks = array();
	
	if(!empty($weapon['perkHashes'])) {
		foreach($weapon['perkHashes'] as $perkHash) {
			$perks[ $perkHash ] = $destiny_manifest->get_definition('DestinySandboxPerkDefinition', $perkHash);
		}
	}
	
	$talentGrid = $destiny_manifest->get_definition('DestinyTalentGridDefinition', $weapon['talentGridHash']);
	
	$stats = array();
	
	if(!empty($weapon['stats'])) {
		foreach($weapon['stats'] as $statHash => $weapon_stat) {
			$stats[ $statHash ] = array(
				'value' => $weapon_stat['value'],
				'minimum' => $weapon_stat['minimum'],
				'maximum' => $weapon_stat['maximum'],
				'definition' => $destiny_manifest->get_definition('DestinyStatDefinition', $statHash),
			);
		}
	}
	
	$itemCategories = array();
	
	if(!empty($weapon['itemCategoryHashes'])) {
		foreach($weapon['itemCategoryHashes'] as $itemCategoryHash) {
			$itemCategories[ $itemCategoryHash ] = $destiny_manifest->get_definition('DestinyItemCategoryDefinition', $itemCategoryHash);
		}
	}
	
	$sources = array();
	
	if(!empty($weapon['sourceHashes'])) {
		foreach($weapon['sourceHashes'] as $sourceHash) {
			$sources[ $sourceHash ] = $destiny_manifest->get_definition('DestinyRewardSourceDefinition', $sourceHash);
		}
	}
	
	
	// prepare upgrades table
	$talentGridNodes = array();   // [col][row][] = {possible upgrade}
	
	foreach($talentGrid['nodes'] as $node) {
		if(!isset($talentGridNodes[ $node['column'] ])) {
			$talentGridNodes[ $node['column'] ] = array();
		}
		
		if(!isset($talentGridNodes[ $node['column'] ][ $node['row'] ])) {
			$talentGridNodes[ $node['column'] ][ $node['row'] ] = array();
		}
		
		$talentGridNodes[ $node['column'] ][ $node['row'] ] = $node['steps'];
	}
	
	
	@include(TEMPLATES ."header.php");
?>
	
	<div class="container">
		<ol class="breadcrumb">
			<li><a href="<?=home_url();?>">Home</a></li>
			<li><a href="<?=site_url("/weapons/");?>">Weapons</a></li>
			<li><a href="<?=site_url("/weapons/?bucketTypeHash=". urlencode($weapon['bucketTypeHash']));?>"><?=esc_html($bucket['bucketName']);?></a></li>
			<li><a href="<?=site_url("/weapons/?itemType=". urlencode($weapon['itemType']));?>"><?=esc_html($weapon['itemTypeName']);?></a></li>
			<li><a href="<?=site_url("/weapons/?tierTypeName=". urlencode($weapon['tierTypeName']));?>"><?=esc_html($weapon['tierTypeName']);?></a></li>
			<li><?=esc_html($weapon['itemName']);?></li>
		</ol>
		
		<h1><?=esc_html($weapon['itemName']);?></h1>
		
		<blockquote><?=esc_html($weapon['itemDescription']);?></blockquote>
		
		<ul class="nav nav-tabs">
			<li role="presentation" class="active"><a href="#">Upgrades</a></li>
		</ul>
		
		
		<div id="weapon__upgrades">
			
			<div class="talent_grid">
				<?php foreach($talentGridNodes as $column => $rows): ?>
				<div class="slot_column">
					<?php foreach($rows as $row): ?>
						<?php if(count($row) > 1): ?>
							<?php ob_start(); ?>
								<table class="table talent_grid_choices">
									<?php foreach($row as $step): ?>
									<tr>
										<td><span class="choice_icon_wrapper"><img src="<?=esc_attr( DESTINY_CONTENT_BASE . $step['icon'] );?>" alt="<?=esc_attr($step['nodeStepName']);?>" class="choice_icon" /></span></td>
										<td>
											<strong><?=esc_html($step['nodeStepName']);?></strong><br />
											<em><?=esc_html($step['nodeStepDescription']);?></em>
										</td>
									</tr>
									<?php endforeach; ?>
								</table>
							<?php $popover_contents = ob_get_clean(); ?>
								
							<div class="slot">
								<button type="button" class="slot_icon_wrapper" data-toggle="popover" data-trigger="focus" title="<?=esc_attr("<strong>Possible Choices</strong>");?>" data-html="true" data-content="<?=esc_attr($popover_contents);?>"><i class="glyphicon glyphicon-random slot_icon"></i></button>
							</div>
						<?php else: ?>
							<div class="slot">
								<span class="slot_icon_wrapper"><img src="<?=esc_attr( DESTINY_CONTENT_BASE . $row[0]['icon'] );?>" alt="<?=esc_attr();?>" class="slot_icon" /></span>
							</div>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
				<?php endforeach; ?>
			</div>
			
			<style type="text/css">
				.talent_grid { display: table; width: 100%; }
					.talent_grid .slot_column { display: inline-block; float: left; padding: 0 15px; }
						.talent_grid .slot_column .slot { position: relative; display: block; text-align: center; margin: 0; padding: 15px 0; }
							.talent_grid .slot_column .slot .slot_icon_wrapper { display: inline-block; width: 100px; height: 100px; padding: 15px; border-radius: 50px; background-color: #5b5bff; border: 0; outline: none; cursor: pointer; }
								.talent_grid .slot_column .slot .slot_icon_wrapper .slot_icon { width: 100%; }
				
				.popover { width: 500px; }
				
				.talent_grid_choices .choice_icon_wrapper { display: inline-block; width: 40px; height: 40px; padding: 8px; border-radius: 50px; background-color: #5b5bff; }
					.talent_grid_choices .choice_icon_wrapper .choice_icon { width: 100%; }
			</style>
		</div>
		
		
		<br /><br /><br /><br /><br />
		<p><button role="button" class="btn btn-default toggle_collapse" data-target="#full_weapon_debug">Full Debug</button></p>
		<div id="full_weapon_debug">
			<hr />
			
			<h2>
				$weapon
				<button role="button" class="btn btn-sm btn-default toggle_collapse" data-target="#weapon_debug__weapon">Debug</button>
			</h2>
			<div id="weapon_debug__weapon" class="hidden"><pre><?=esc_html(print_r($weapon, true));?></pre></div>
			
			<h2>
				$bucket
				<button role="button" class="btn btn-sm btn-default toggle_collapse" data-target="#weapon_debug__bucket">Debug</button>
			</h2>
			<div id="weapon_debug__bucket" class="hidden"><pre><?=esc_html(print_r($bucket, true));?></pre></div>
			
			<h2>
				$perks
				<button role="button" class="btn btn-sm btn-default toggle_collapse" data-target="#weapon_debug__perks">Debug</button>
			</h2>
			<div id="weapon_debug__perks" class="hidden"><pre><?=esc_html(print_r($perks, true));?></pre></div>
			
			<h2>
				$talentGrid
				<button role="button" class="btn btn-sm btn-default toggle_collapse" data-target="#weapon_debug__talentGrid">Debug</button>
			</h2>
			<div id="weapon_debug__talentGrid" class="hidden"><pre><?=esc_html(print_r($talentGrid, true));?></pre></div>
			
			<h2>
				$primaryBaseStat
				<button role="button" class="btn btn-sm btn-default toggle_collapse" data-target="#weapon_debug__primaryBaseStat">Debug</button>
			</h2>
			<div id="weapon_debug__primaryBaseStat" class="hidden"><pre><?=esc_html(print_r($primaryBaseStat, true));?></pre></div>
			
			<h2>
				$stats
				<button role="button" class="btn btn-sm btn-default toggle_collapse" data-target="#weapon_debug__stats">Debug</button>
			</h2>
			<div id="weapon_debug__stats" class="hidden"><pre><?=esc_html(print_r($stats, true));?></pre></div>
			
			<h2>
				$itemCategories
				<button role="button" class="btn btn-sm btn-default toggle_collapse" data-target="#weapon_debug__itemCategories">Debug</button>
			</h2>
			<div id="weapon_debug__itemCategories" class="hidden"><pre><?=esc_html(print_r($itemCategories, true));?></pre></div>
			
			<h2>
				$sources
				<button role="button" class="btn btn-sm btn-default toggle_collapse" data-target="#weapon_debug__sources">Debug</button>
			</h2>
			<div id="weapon_debug__sources" class="hidden"><pre><?=esc_html(print_r($sources, true));?></pre></div>
			
			<hr />
			
			<ul>
				<li><a href="<?=esc_attr("http://db.planetdestiny.com/items/view/". $weapon['hash']);?>" target="_blank">PlanetDestiny DB</a></li>
			</ul>
		</div>
	</div>
	
	<?php ob_start(); ?>
	<style type="text/css">
		.hidden { display: none; }
	</style>
	<script type="text/javascript">
		;jQuery(document).ready(function($) {
			$('[data-toggle="popover"]').popover();
			
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