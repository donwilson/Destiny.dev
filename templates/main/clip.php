<?php
	$xuid = (!empty($_REQUEST['xuid'])?$_REQUEST['xuid']:false);
	$scid = (!empty($_REQUEST['scid'])?$_REQUEST['scid']:false);
	$gameClipId = (!empty($_REQUEST['gameClipId'])?$_REQUEST['gameClipId']:false);
	
	if(empty($xuid) || empty($scid) || empty($gameClipId)) {
		redirect_to(site_url("/clips/"));
	}
	
	$clip = cache_get('xbox:clip_details:'. $gameClipId, function() use ($xuid, $scid, $gameClipId) {
		$xbox = new Microsoft_Xbox_API(XBOX_API_KEY, XBOX_API_BASE);
		
		$xbox->setEndpoint("/v2/{xuid}/game-clip-details/{scid}/{gameClipId}", [
			'xuid' => $xuid,
			'scid' => $scid,
			'gameClipId' => $gameClipId,
		]);
		
		$xbox->exec();
		
		return $xbox->getResponse();
	});
	
	if(empty($clip)) {
		die("clip(". $clip .") not found");
	}
	
	@include(TEMPLATES ."header.php");
?>
	
	<div id="main">
		<h1><?=esc_html($clip['titleName'] ." Clip". (!empty($clip['clipName'])?" - ". $clip['clipName']:""));?></h1>
		
		<p><?=esc_html("Published ". get_date_since(strtotime($clip['dateRecorded'])));?></p>
		
		<video id="my-video" class="video-js vjs-default-skin vjs-16-9 vjs-big-play-centered" controls preload="auto" width="640" height="264" poster="<?=esc_attr($clip['thumbnails'][1]['uri']);?>" data-setup="<?=esc_attr(json_encode(array(
			
		)));?>">
			<source src="<?=esc_attr($clip['gameClipUris']['0']['uri']);?>" type="video/mp4">
			<p class="vjs-no-js">
				To view this video please enable JavaScript, and consider upgrading to a web browser that <a href="http://videojs.com/html5-video-support/" target="_blank">supports HTML5 video</a>
			</p>
		</video>
		
		<pre><?=esc_html( print_r($clip, true) );?></pre>
		
		
	</div>
	
	<?php ob_start(); ?>
	<link href="http://vjs.zencdn.net/5.8.8/video-js.css" rel="stylesheet" />
	<style type="text/css">
		
	</style>
	<script src="http://vjs.zencdn.net/5.8.8/video.js"></script>
	<script type="text/javascript">
		;jQuery(document).ready(function($) {
			
		});
	</script>
	<?php $append_body = ob_get_clean(); enqueue_to_body_append($append_body); ?>
	
<?php
	@include(TEMPLATES ."footer.php");