<?php
	define('TMP_MEMBERSHIP_ID', "");
	
	@include(TEMPLATES ."header.php");
?>
	
	<div id="main">
		<h1>Account List</h1>
		
		<?php
			try {
				$membership_type = "1";
				$display_name = "";
				
				$data = cache_get("destiny:accounts:membership_type__". $membership_type .":display_name__". $display_name, function() use ($membership_type, $display_name) {
					$destiny = new Bungie_Destiny_API(DESTINY_API_KEY, DESTINY_API_BASE);
					
					$destiny->setEndpoint("/SearchDestinyPlayer/{membershipType}/{displayName}/", [
						'membershipType' => $membership_type,
						'displayName' => $display_name,
					]);
					
					$destiny->exec();
					
					return $destiny->getResponse();
				});
				
				//print "<pre>". print_r(array(
				//	'data'	=> $data,
				//	'debug'	=> $destiny->getDebug(),
				//), true) ."</pre>\n";
				
				if(!empty($data['ErrorStatus']) && ("Success" !== $data['ErrorStatus'])) {
					throw new Exception($data['Message']);
				}
				
				//print "<pre>". print_r($data, true) ."</pre>\n";
				
				?>
				<table class="table">
					<thead>
						<tr>
							<th>Service</th>
							<th>Account</th>
						</tr>
					</thead>
					<tbody>
						<?php
							foreach($data['Response'] as $account):
								$icon_path = $account['iconPath'];
								$membership_id = $account['membershipId'];
								$display_name = $account['displayName'];
						?>
						<tr>
							<td valign="top"><a href="<?=site_url("/characters/?membership_id=". $membership_id);?>"><img src="<?=esc_attr(DESTINY_CONTENT_BASE . $icon_path);?>" /></a></td>
							<td valign="top">
								<a href="<?=site_url("/characters/?membership_id=". $membership_id);?>"><?=esc_html($display_name);?></a>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php
			} catch(Exception $e) {
				print "<div class=\"error\">Error (". $e->getCode() ."): ". $e->getMessage() ."</div>\n";
			}
		?>
	</div>
	
<?php
	@include(TEMPLATES ."footer.php");
