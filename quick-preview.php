<?php
/*
Plugin Name: Quick Preview
Plugin URI: http://github.com/fajitanachos/quick-preview
Description: Use Ctrl-S to save and preview your post
Version: 1.1.1
Author: FajitaNachos
Author URI: https://www.fajitanachos.com
 Copyright 2012 FajitaNachos
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
register_activation_hook( __FILE__, 'set_default_options' );
add_action('init','register_quick_preview');
add_action('admin_menu', 'plugin_admin_add_page');
add_action('admin_init', 'plugin_admin_init');
add_action('admin_init', 'pass_option_value');
add_action('admin_print_scripts-post.php', 'load_quick_preview_js');
add_action('admin_print_scripts-post-new.php', 'load_quick_preview_js');
add_filter('plugin_action_links_quick-preview/quick-preview.php', 'quick_preview_settings_link' );
add_filter( 'tiny_mce_before_init', 'visual_editor_save' );

function visual_editor_save( $initArray ) {
    $initArray['setup'] = <<<JS
[function(ed) {
    ed.onKeyDown.add(function(ed, e) {
	
        if (document.cookie.indexOf("previewCookie") >= 0){  
		//expires added for IE
		document.cookie="previewCookie=true; max-age=0;expires=0;path=/wp-admin/"; 			
		
		//quickPreviewOption is set in quick-preview.php  
		var previewURL = document.getElementById('post-preview');
		if(quickPreviewOption === 'current'){ 		                                    
			window.location = previewURL;
		}
		if(quickPreviewOption === 'new'){
			window.open(previewURL,"wp_PostPreview","","true");
		}
	}
       if((e.ctrlKey || e.metaKey) && e.keyCode == 83){
			//Find #save post if it's a draft. If post is published, #save-post doesn't exist.
			if(jQuery('#save-post').length){
				jQuery('#save-post').click();	
			}
			else if(jQuery('#publish').length){
				jQuery('#publish').click();
			}
			
			//Sets a cookie to open the preview on page refresh. Saving a post auotmatically refreshes the page.
			document.cookie = "previewCookie = true;max-age = 60;path=/wp-admin/";  	
					
		}
    	
	});

return false;	
}][0]
JS;
    return $initArray;
}

function register_quick_preview(){
	wp_register_script('quick_preview',plugins_url('/quick-preview/quick-preview.js'),array('jquery'));
}

function set_default_options(){
	$default_option = array('window_preference' => 'current');
	add_option('quick_preview_options',$default_option);
}

function load_quick_preview_js(){
	wp_enqueue_script('quick_preview');
}

function quick_preview_settings_link($links) { 
  $settings_link = '<a href="options-general.php?page=quick_preview.php">Settings</a>'; 
  array_unshift($links, $settings_link); 
  return $links; 
}

function pass_option_value(){      //Gets the preview option and passes it to the quick_preview script
	$options = get_option('quick_preview_options');
	wp_localize_script( 'quick_preview', 'quickPreviewOption', $options['window_preference'] );
}

function plugin_admin_add_page() {
	add_options_page('Quick Preview', 'Quick Preview', 'manage_options', 'quick_preview', 'qp_options_page');
}

function plugin_admin_init(){
	register_setting( 'quick_preview_options', 'quick_preview_options', 'qp_options_validate');
	add_settings_section('plugin_main', 'Main Settings', 'plugin_section_text', 'quick_preview');
	add_settings_field('plugin_window_preference', 'Window Preference', 'plugin_setting_string', 'quick_preview', 'plugin_main');
}

function plugin_section_text() {
	echo '<p>Choose whether you would like the post preview to open in a new window or the current window.</p>';
}

function plugin_setting_string() {
	$options = get_option('quick_preview_options');?>
	<p><input id='plugin_window_preference' name='quick_preview_options[window_preference]' size='40' type='radio' value='current' <?php if($options['window_preference'] === "current"){echo ("checked='checked'");}?> /> Current Window </p>
	<input id='plugin_window_preference' name='quick_preview_options[window_preference]' size='40' type='radio' value='new' <?php if($options['window_preference'] === "new"){echo ("checked='checked'");}?> /> New Window<p class="description">If popups are disabled you will need to add your site to the exception list in your broswer. </p>
	<?php 
}

function qp_options_validate($input) {
	$newinput = array('window_preference' => trim($input['window_preference']));
	if( $newinput['window_preference'] != "new" && $newinput['window_preference'] != "current" ) {
		$newinput[ 'window_preference' ] = "";
	}
	return $newinput;
}

function qp_options_page() { ?>
	<div class="wrap">
		<div id="icon-options-general" class="icon32">
			<br>
		</div>
		<h2>Quick Preview Options</h2>
		<form action="options.php" method="post">
			<?php settings_fields('quick_preview_options'); ?>
			<?php do_settings_sections('quick_preview'); ?>
			<p class="submit">
			<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" />
			</p>
		</form>
	</div>
<?php } 
?>