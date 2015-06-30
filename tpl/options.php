<?php
/**
 * Settings page template
 * package pootle_page_builder
 */
?>

<div class="wrap">
	<h2>Pootle Page Builder</h2>
	<?php settings_errors(); ?>
	<form action='options.php' method="POST">
		<?php
		do_settings_sections( 'pootlepage-display' );
		settings_fields( 'pootlepage-display' );
		submit_button();
		?>
	</form>
</div>