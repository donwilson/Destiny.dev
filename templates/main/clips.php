<?php
	//$gamertag = (!empty($_REQUEST['gamertag'])?$_REQUEST['gamertag']:false);
	$gamertag = "";
	
	if(empty($gamertag)) {
		redirect_to(home_url());
	}
	
	$xuid = cache_get('xbox:gamertag_to_xuid:'. $gamertag, function() use ($gamertag) {
		$xbox = new Microsoft_Xbox_API(XBOX_API_KEY, XBOX_API_BASE);
		
		$xbox->setEndpoint("/v2/xuid/{gamertag}", [
			'gamertag' => $gamertag,
		]);
		
		$xbox->exec();
		
		return $xbox->getResponse();
	});
	
	if(empty($xuid) || !is_numeric($xuid)) {
		die("xuid(". $xuid .") not found");
	}
	
	@include(TEMPLATES ."header.php");
?>
	
	<div id="main">
		<h1>Xbox Clips</h1>
		
		<?php
			try {
				$data = cache_get("xbox:xuid_". $xuid .":clips", function() use ($xuid) {
					$xbox = new Microsoft_Xbox_API(XBOX_API_KEY, XBOX_API_BASE);
					
					$destinyTitleId = "247546985";
					
					$xbox->setEndpoint("/v2/{xuid}/game-clips/{titleId}", [
						'xuid' => $xuid,
						'titleId' => $destinyTitleId,
					]);
					
					$xbox->exec();
					
					return $xbox->getResponse();
				});
				
				//print "<pre>". print_r(array(
				//	'data'	=> $data,
				//), true) ."</pre>\n";
				
				if(empty($data)) {
					throw new Exception("No clip data found");
				}
				
				?>
				
				<ul class="video_list">
					<?php foreach($data as $clip): ?>
					<li class="item">
						<a href="<?=site_url("/clip/?xuid=". $clip['xuid'] ."&scid=". $clip['scid'] ."&gameClipId=". $clip['gameClipId']);?>" class="anchor">
							<img src="<?=esc_attr($clip['thumbnails'][0]['uri']);?>" class="image" />
							<span class="duration"><?=esc_html( get_time_display($clip['durationInSeconds']) );?></span>
							<span class="age"><?=esc_html( get_date_since(strtotime($clip['dateRecorded'])) );?></span>
						</a>
					</li>
					<?php endforeach; ?>
				</ul>
				
				<?php
			} catch(Exception $e) {
				print "<div class=\"error\">Error (". $e->getCode() ."): ". $e->getMessage() ."</div>\n";
			}
		?>
	</div>
	
	<?php ob_start(); ?>
	<style type="text/css">
		.video_list { display: table; width: 100%; list-style: none; margin: 0; padding: 0; }
			.video_list .item { list-style: none; margin: 0; padding: 0; float: left; display: inline-block; width: 90%; max-width: 250px; vertical-align: top; }
				.video_list .item .anchor { position: relative; display: block; text-align: center; padding: 5px; }
					.video_list .item .anchor .image { display: block; width: 100%; }
					.video_list .item .anchor .duration { position: absolute; display: inline-block; bottom: 10px; left: 10px; z-index: 50; background-color: rgba(0, 0, 0, 0.6); padding: 3px 5px; font-weight: bold; font-size: 12px; line-height: 12px; color: #fff; font-family: Verdana; }
					.video_list .item .anchor .age { position: absolute; display: inline-block; bottom: 10px; right: 10px; z-index: 50; background-color: rgba(0, 0, 0, 0.6); padding: 3px 5px; font-weight: bold; font-size: 12px; line-height: 12px; color: #fff; font-family: Verdana; }
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
	<?php $append_body = ob_get_clean(); enqueue_to_body_append($append_body); ?>
	
<?php
	@include(TEMPLATES ."footer.php");
