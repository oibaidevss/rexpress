<h1>Plugin Settings</h1>


<!-- ----------------------------- -->
<div class="_errors">
    <?php settings_errors(); ?>
</div>

<form method="post" action="options.php">
    <?php settings_fields( 'rex-settings-group' ); ?>
    <?php do_settings_sections( 'rexpress_settings' ); ?>
    <?php submit_button(); ?>
</form>
