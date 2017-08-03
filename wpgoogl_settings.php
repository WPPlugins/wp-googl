<div class="wrap">
	<h2>WP Goo.gl Options</h2>
	<form method="POST" action="options.php">
	<?php
		if ( function_exists( 'settings_field' ) ) {
			settings_field( 'wpgoogl-setting-vars' );
		} else {
			wp_nonce_field( 'update-options' );
	?>
		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="page_options" value="wpgoogl_authenticated_requests,wpgoogl_custom_key_name,wpgoogl_token" />
	<?php
		}
	?>
		WP Goo.gl will even work without Enabling Google Authentication, but if you need to track history and shortened URLs and other analytics you need to enable authentication.<br /><br />
		<input type="checkbox" name="wpgoogl_authenticated_requests" onclick="if (!this.checked) { jQuery('#wpgoogl_gauth').css('display','none'); } else { jQuery('#wpgoogl_gauth').css('display','block') } return true;" value="1" <?php if (get_option('wpgoogl_authenticated_requests')) { echo 'checked="checked" '; } ?>/> Enable Google Authentication<br />
		<div id="wpgoogl_gauth" style="border: 1px solid; width:400px; margin: 20px; padding: 10px; display:<?php if (get_option('wpgoogl_authenticated_requests')) { echo 'block'; } else { echo 'none'; } ?>">
		<?php if (!$wpgoogl_token) { ?>
		Click the button below to authenticate the plugin with Google. The Plugin will then create short URLs using authenticated requests and you will be able to track history and other analytics available via Goo.gl URL shortener.<br />
		<input type="button" value="Authenticate via Google" class="button-primary" onclick="window.location = '<?php echo $google_auth_url; ?>';"/>
		<?php } else { ?>
			Successfully completed the google authentication.<br /><br />
			Note: Authentication token will automatically be revoked on plugin deactivation via the plugin menu.
		<?php } ?>
		</div>
		<br />
		Custom key name: <input type="text" name="wpgoogl_custom_key_name" value="<?php echo get_option( 'wpgoogl_custom_key_name' ); ?>" /> 
		<p class="submit">
			<input type="submit" value="Save Settings" class="button-primary" name="submit" />
		</p>
	</form>
</div>
