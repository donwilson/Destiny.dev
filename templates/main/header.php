<?php
	// custom body classes based on site settings
	$body_classes = array();
	
	$menu_items = array(
		array(
			'sections' => array("frontpage"),
			'url' => home_url(),
			'title' => "Home",
		),
		array(
			'sections' => array("characters", "character"),
			'url' => site_url("/characters/"),
			'title' => "Characters",
		),
		array(
			'sections' => array("weapons", "weapon"),
			'url' => site_url("/weapons/"),
			'title' => "Weapons",
		),
		array(
			'sections' => array("armors", "armor"),
			'url' => site_url("/armors/"),
			'title' => "Armor",
		),
		array(
			'sections' => array("clips", "clip"),
			'url' => site_url("/clips/"),
			'title' => "Clips",
		),
		array(
			'sections' => array("debug_databases", "debug_database_tables", "debug_database_table", "debug_database_table_row"),
			'url' => site_url("/debug_databases/"),
			'title' => "Debug: DB",
		),
	);
	
?><!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	
	<title>DestinyDev</title>
	
	<link rel="stylesheet" href="<?=static_url("/css/bootstrap.min.css");?>" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
	
	<link rel="stylesheet" type="text/css" href="<?=static_url("/css/style.css");?>" />
	<link rel="stylesheet" type="text/css" href="http://fonts.googleapis.com/css?family=Lato:300,400,700,900" />
	<!-- <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css" /> -->
	
	<link rel="Shortcut Icon" type="image/x-icon" href="<?=site_url("favicon.ico");?>" />
	
	<!--[if lt IE 9]>
		<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->
	
	<!--[if lt IE 9]>
		<script src="http://css3-mediaqueries-js.googlecode.com/svn/trunk/css3-mediaqueries.js"></script>
	<![endif]-->
	
	<meta name="description" content="Destiny development test site" />
	
	<?php append_to_head(); ?>
</head>
<body<?php if(!empty($body_classes)): ?> class="<?=esc_attr(implode(" ", $body_classes));?>"<?php endif; ?>>
	<?php prepend_to_body(); ?>
	
	<nav class="navbar navbar-inverse">
		<div class="container">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
					<span class="sr-only">Toggle navigation</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<a href="<?=home_url();?>" class="navbar-brand">destiny.dev</a>
			</div>
			<div id="navbar" class="collapse navbar-collapse">
				<ul class="nav navbar-nav">
					<?php foreach($menu_items as $menu_item): ?>
					<li<?php if(!empty($_REQUEST['_section_']) && !empty($menu_item['sections']) && is_array($menu_item['sections']) && in_array($_REQUEST['_section_'], $menu_item['sections'])): ?> class="active"<?php endif; ?>><a href="<?=esc_attr($menu_item['url']);?>"><?=esc_html($menu_item['title']);?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	</nav>
	