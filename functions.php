<?php

//== DEVELOPMENT OPTIONS ==========================================================================

	//Set the theme's slug/textdomain (used in __() and _e() functions)
		define('WP_THEME_SLUG','boilerplate-barebones');
		

//=================================================================================================

//Required by WordPress
	add_theme_support('automatic-feed-links');
	
	//CONTENT WIDTH
		if ( ! isset( $content_width ) ) $content_width = 1200;
	
	//MENU
		//Register the main menu
			if ( function_exists( 'register_nav_menu' ) ) {
				register_nav_menu( 'main-menu', __('Main menu',WP_THEME_SLUG) );
			}
	
	//SIDEBAR
		//Register the main sidebar
			$args = array(
				'name'          => 'Main sidebar',
				'description'   => __('The main sidebar used across most pages',WP_THEME_SLUG),
				'before_widget' => '<li class="sidebaritem">',
				'after_widget'  => '</li>',
				'before_title'  => '<span class="title">',
				'after_title'   => '</span>' );
			if ( function_exists('register_sidebar') ){
				register_sidebar($args);
			}
	
//FILTERS

	//Output a custom title suffix based on phrase length
	//This is a feature that improves SEO by selecting a title that is as long
	//as possible, but that doesn't exceed the character limit displayed in search
	//engines. If no short enough suffix is found, the default is used (blog title)
		
		//Sort the array of suffixes by length
			function sort_suffixes($a,$b){
				return strlen($b)-strlen($a);
			}
			
		//Return the title_suffix
			function make_title($separator = "|"){
				$available_suffixes = get_option('boilerplate_dynamic_suffixes',array(get_bloginfo('description')));
				$max_title_length = 65; //The maximum length of the title.
				
				usort($available_suffixes,'sort_suffixes');
				
				//Set the default suffix (the blog's name alone)
					$suffix = ' ' . get_bloginfo('name');
					
				//If it's not the frontpage and this feature is enabled, find an appropriate suffix in the array
					if(!is_front_page() && get_option('boilerplate_use_dynamic_suffix',false)){
						foreach( $available_suffixes as $available_suffix ){
							//If the length of this suffix + title is short enough, make it the final suffix
							if ( strlen(utf8_decode((wp_title($separator,false,'right') . get_bloginfo('name') . ' | ' . $available_suffix))) <= $max_title_length ){
								$suffix = $available_suffix;
								break;//Keep the longest title only
							}
						}
					}
					
				echo( wp_title($separator,false,'right') . get_bloginfo('name') . ' | ' . $suffix );
			}

	//Output a custom description for the meta description tag
	//If the user fills in the description post meta, then that will be used as the blog description.
	//Otherwise, if there is an excerpt for the post, that will be used. Finally, if none of these are
	//set, then the bloginfo description will be used. If disabled, the default behavior will be used.		
		function make_description(){
		//Fill the description tags with a custom description, an excerpt or the blog description
			$description = get_bloginfo('description');//Default value
			if ( get_option('boilerplate_use_dynamic_descriptions',false) ){
				if( isset($post) && get_post_meta($post->ID,'description',true) != ''){
					$description = get_post_meta($post->ID,'description',true);
				}
				elseif(is_single() && get_the_excerpt()!==''){
					$description = get_the_excerpt();
				}
			}			
			echo $description;
		}
		
	//Use the "x days ago" date format
		if( get_option('boilerplate_use_human_readable_dates',false) ){
			function time_ago_date($date){
				return sprintf( _x("Posted %s ago",'The %s parameter is a date like "5 days" or "3 minutes"',WP_THEME_SLUG), human_time_diff(get_the_time('U'), current_time('timestamp')) );
			}
			add_filter('the_date','time_ago_date');
		}

	//Remove inline CSS placed by WordPress
		function my_remove_recent_comments_style() {
			add_filter( 'show_recent_comments_widget_style', '__return_false' );
		}
		add_action( 'widgets_init', 'my_remove_recent_comments_style' );
		
//ADMIN

	//Load the site's CSS in the editor
		add_editor_style('style.css');
		
	//Use a custom logo on the login page
		function change_login_logo() {
			//Adjust the logo to the image size
				$size = getimagesize ( get_option('boilerplate_custom_logo_url','') );
			//Echo the changes to the CSS
				echo '
				<style type="text/css">
					h1,#login{
						width:' . $size[0] . 'px;
					}
					h1 a {
						background-image: url(' . get_option('boilerplate_custom_logo_url','') . ');
						width:' . $size[0] . 'px;
						height:' . $size[1] . 'px;
					}
				</style>';
		}
		if (get_option('boilerplate_custom_logo_url','')!=''){
			add_action('login_head', 'change_login_logo');
		}

	//Hide specific admin menus from non-admin users (if activated)
		if(!current_user_can('administrator')){ //Only hide menus for non-admins
			function remove_menus () {
				global $menu; //The WordPress admin menu. Contains a multi-dimensional array
				$menus_to_hide = array(); //The array of menus to hide, really.
				
				if(get_option('boilerplate_hide_posts_menu',false)) 		array_push($menus_to_hide,__('Posts'));
				if(get_option('boilerplate_hide_pages_menu',false)) 		array_push($menus_to_hide,__('Pages'));
				if(get_option('boilerplate_hide_comments_menu',false)) 		array_push($menus_to_hide,__('Comments'));
				if(get_option('boilerplate_hide_media_menu',false)) 		array_push($menus_to_hide,__('Media'));
				if(get_option('boilerplate_hide_links_menu',false)) 		array_push($menus_to_hide,__('Links'));
				if(get_option('boilerplate_hide_profile_menu',false)) 		array_push($menus_to_hide,__('Profile'));
				if(get_option('boilerplate_hide_tools_menu',false)) 		array_push($menus_to_hide,__('Tools'));
				
				end ($menu);
				while (prev($menu)){
					$value = explode(' ',$menu[key($menu)][0]);
					if(in_array($value[0] != NULL?$value[0]:"" , $menus_to_hide)){unset($menu[key($menu)]);}
				}
			}
			add_action('admin_menu', 'remove_menus');
		}
		
	//Disable the admin bar for logged in users
		if(get_option('boilerplate_hide_admin_bar',false)==true){
			add_filter( 'show_admin_bar', '__return_false' ); 
		}
		
	//Add the plugin options page
		add_action('admin_menu', 'boilerplate_barebones_menu');
		function boilerplate_barebones_menu() {
			add_theme_page('Theme options', 'Theme options', 'manage_options', WP_THEME_SLUG, 'boilerplate_theme_options');
		}
		function boilerplate_theme_options() {
			//Display the theme options
				include('theme-options.php');
		}

	
//LOCALIZATION
	
	//Enable localization
		load_theme_textdomain(WP_THEME_SLUG,get_template_directory() . '/languages');
		
		
//UTILITY
	
	//URL validator
		function is_valid_url($URL) {
			$v = "/^(http|https|ftp):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i";
			return (bool)preg_match($v, $URL);
		}
?>