	<footer id="footer">
		<p>&copy; <?=esc_html(date("Y"));?> destiny.dev <span> | </span> All Rights Reserved.</p>
	</footer>
	
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
	<script src="<?=static_url("/js/bootstrap.min.js");?>" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
	<script src="<?=static_url("/js/core.js");?>"></script>
	
	<?php if(defined('GOOGLE_ANALYTICS_ID') && GOOGLE_ANALYTICS_ID): ?>
	<script>
		(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');
		
		ga('create', '<?=GOOGLE_ANALYTICS_ID;?>', 'auto');
		ga('send', 'pageview');
	</script>
	<?php endif; ?>
	
	<?php append_to_body(); ?>
</body>
</html>