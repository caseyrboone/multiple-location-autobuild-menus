<?php
error_reporting(0);
ini_set('display_errors', 1);
/*
Plugin Name: Multiloc Auto Menus
Description: Plugin automatically generates menu by grabbing location from the Multiloc Vars plugin and comparing it to the parent pages that exist in the post type “pages”. Then it generates the remainder of the menu based off the parent child relationship of the pages. The pages can be removed from the menu by selecting the “remove from menu” option at the bottom of each page in edit mode. The menu is organized A-Z and can be changed by the WordPress native “order” option.
Version: 1.0
Author: Casey Boone
Author URI: https://www.casyrboone.com
Text Domain: multiloc-auto-menu
*/

//////////////////////////////////////////////////////////////////////
//Adding check for dependent Multiloc Vars plugin and alerts if not found

//is_plugin_active($plugin);

if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
}

// update to the plugin you are checking for


if ( !is_plugin_active( 'multi-location-vars-new/multiloc-vars.php' ) ) {
	function require_multiloc_vars_plugin(){?>
			<div class="notice notice-error" >
					<p> Please Enable Multiloc Vars Plugin before using Multiloc Auto Menu plugin</p>
			</div><?php
	 @trigger_error(__('Please Enable Multiloc Vars Plugin before using Multiloc Auto Menu plugin.', 'cln'), E_USER_ERROR);
	}

	add_action('admin_notices','require_multiloc_vars_plugin');
	register_activation_hook(__FILE__, 'require_multiloc_vars_plugin');
}






/////////////////////////////////////////////////
//Add check box to exclude pages from navigation.

// Register style sheet.
add_action( 'wp_enqueue_scripts', 'qam_register_plugin_styles' );

/**
 * Register style sheet.
 */
function qam_register_plugin_styles() {
	wp_enqueue_style( 'multiloc-auto-menu-css', plugins_url( 'multiloc-auto-menu/css/qam-front.css' ) );
	wp_enqueue_script( 'multiloc-auto-menu-js', plugins_url( 'multiloc-auto-menu/js/qam.js' ), array('jquery'), '1.0', true );
	// wp_enqueue_style( 'multiloc-auto-menu' );
}

add_action( 'add_meta_boxes', 'multiloc_add_meta_box' );
 
if ( ! function_exists( 'multiloc_add_meta_box' ) ) {
	/**
	 * Add meta box to page screen
	 *
	 * This function handles the addition of variuos meta boxes to your page or post screens.
	 * You can add as many meta boxes as you want, but as a rule of thumb it's better to add
	 * only what you need. If you can logically fit everything in a single metabox then add
	 * it in a single meta box, rather than putting each control in a separate meta box.
	 *
	 * @since 1.0.0
	 */
	function multiloc_add_meta_box() {
		add_meta_box( 'additional-page-metabox-options', esc_html__( 'Remove Page From Main Nav', 'multiloc' ), 'multiloc_metabox_controls', 'page', 'normal', 'low' );
	}
}

if ( ! function_exists( 'multiloc_metabox_controls' ) ) {
	/**
	 * Meta box render function
	 *
	 * @param  object $post Post object.
	 * @since  1.0.0
	 */
	function multiloc_metabox_controls( $post ) {
		$meta = get_post_meta( $post->ID );
		$multiloc_checkbox_value = ( isset( $meta['multiloc_checkbox_value'][0] ) &&  '1' === $meta['multiloc_checkbox_value'][0] ) ? 1 : 0;
		$multiloc_am_img_url = get_post_meta($post->ID, "multiloc_am_img_url");
		$multiloc_textbox_value = get_post_meta($post->ID, 'multiloc_textbox_value');
		wp_nonce_field( 'multiloc_control_meta_box', 'multiloc_control_meta_box_nonce' ); // Always add nonce to your meta boxes!
		?>
		<style type="text/css">
			.post_meta_extras p{margin: 20px;}
			.post_meta_extras label{display:block; margin-bottom: 10px;}
		</style>
		<div class="post_meta_extras">
			<p>
				<label><input type="checkbox" name="multiloc_checkbox_value" value="1" <?php checked( $multiloc_checkbox_value, 1 ); ?> /><?php esc_attr_e( 'Check this box if you would like to exclude this page from the main nav menu.', 'multiloc' ); ?></label>
			</p>
			<p>
				<label for="multiloc_am_img_url"><?php esc_attr_e( 'Menu Image URL:', 'multiloc' ); ?></label>
				<input type="text" class="qam-meta-image components-text-control__input" name="multiloc_am_img_url" id="multiloc_am_img_url" value="<?= $multiloc_am_img_url[0]; ?>" />
		        <input type="button" class="button image-upload" value="Browse">
			</p>
			<p class="qam-image-preview"><img src="<?= $multiloc_am_img_url[0]; ?>" style="max-width: 250px;"></p>
			<p>
				<label for="multiloc_textbox_value">Menu item name:</label>
				<input type="textbox" name="multiloc_textbox_value" id="multiloc_textbox_value" value="<?= $multiloc_textbox_value[0]; ?>">
			
			</p>
		    

			<script>
    jQuery(document).ready(function($) {
        // Instantiates the variable that holds the media library frame.
        var meta_image_frame
        // Runs when the image button is clicked.
        $('.image-upload').click(function(e) {
        // Get preview pane
        var meta_image_preview = $(this)
        .parent()
        .parent()
        .children('.qam-image-preview')
        // Prevents the default action from occuring.
        e.preventDefault()
        var meta_image = $(this)
        .parent()
        .children('.qam-meta-image')
        // If the frame already exists, re-open it.
        if (meta_image_frame) {
        meta_image_frame.open()
        return
        }
        // Sets up the media library frame
        meta_image_frame = wp.media.frames.meta_image_frame = wp.media({
        title: meta_image.title,
        button: {
        text: meta_image.button,
        },
        })
        // Runs when an image is selected.
        meta_image_frame.on('select', function() {
        // Grabs the attachment selection and creates a JSON representation of the model.
        var media_attachment = meta_image_frame
        .state()
        .get('selection')
        .first()
        .toJSON()
        // Sends the attachment URL to our custom image input field.
        meta_image.val(media_attachment.url)
        meta_image_preview.children('img').attr('src', media_attachment.url)
        })
        // Opens the media library frame.
        meta_image_frame.open()
        })
        })
    </script>
		</div>
		<?php
	}
}

add_action( 'save_post', 'multiloc_save_metaboxes' );
if ( ! function_exists( 'multiloc_save_metaboxes' ) ) {
	/**
	 * Save controls from the meta boxes
	 *
	 * @param  int $post_id Current post id.
	 * @since 1.0.0
	 */
	function multiloc_save_metaboxes( $post_id ) {
		if ( ! isset( $_POST['multiloc_control_meta_box_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['multiloc_control_meta_box_nonce'] ), 'multiloc_control_meta_box' ) ) { // Input var okay.
			return $post_id;
		}
 
		// Check the user's permissions.
		if ( isset( $_POST['post_type'] ) && 'page' === $_POST['post_type'] ) { // Input var okay.
			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return $post_id;
			}
		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return $post_id;
			}
		}
		/*
		 * If this is an autosave, our form has not been submitted,
		 * so we don't want to do anything.
		 */
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}
		/* Ok to save */ 
		$multiloc_checkbox_value = ( isset( $_POST['multiloc_checkbox_value'] ) && '1' === $_POST['multiloc_checkbox_value'] ) ? 1 : 0; // Input var okay.

		update_post_meta( $post_id, 'multiloc_checkbox_value', esc_attr( $multiloc_checkbox_value ) );
		
		$multiloc_am_img_url = $_POST['multiloc_am_img_url']; // Input var okay.

		update_post_meta( $post_id, 'multiloc_am_img_url', esc_attr( $multiloc_am_img_url ) );

		$multiloc_textbox_value = $_POST['multiloc_textbox_value']; // Input var okay.

		update_post_meta( $post_id, 'multiloc_textbox_value', esc_attr( $multiloc_textbox_value ) );

	}
}

//-------------> Build navigation menu


add_action( 'build_the_menu', 'build_my_menu' );


function build_my_menu() {

	global $post, $wpdb;
	// $menu_location = do_shortcode( '[multiloc_vars id="location"]');
	$location   = do_shortcode( '[multiloc_vars id="location"]');
        $currentPromo = do_shortcode( '[multiloc_vars id="current_promo"]');
        $fbbc_march_start_dt=get_option('fbbc_march_start_dt');
        $fbbc_march_end_dt=get_option('fbbc_march_end_dt');
        $marchPromoStartDate = strtotime($fbbc_march_start_dt);
        $marchPromoEndDate = strtotime($fbbc_march_end_dt);
        $currentTime = strtotime(date('Y-m-d H:i'));
	$page = get_page_by_path($location);

	if ( wp_get_nav_menu_items($location)) {
			wp_nav_menu(
				array(
					'menu'				=> $location,
					'depth'				=> 4,
					'container'			=> 'nav',
					'container_id'	=> 'menu',
					'menu_class'	=> 'main-menu clearfix',
					'theme_location'	=> $location,
				)
			);
		}

	else {

?>

<?php

		$pageIDList = "";
		if ($page) {
			$pageId = $page->ID;
		} else {
			$pageId = 0;
		}

			function get_top_ancestor($id){
				$current = get_post($id);
				if(!$current->post_parent){
					return $current->ID;
				} else {
					return get_top_ancestor($current->post_parent);
				}
			}

			$current_page_id = "0";
			if(is_page()) {
				$current_page_id = $post->ID;
			} else {
				$page_url_id = get_page_by_path( $location );
				$current_page_id = $page_url_id->ID;
			}

			$current_page_info = get_top_ancestor($current_page_id);;

			$tbl = $wpdb->prefix.'postmeta';
			$meta_value = "1";
			$prepare_guery = $wpdb->prepare( "SELECT post_id FROM $tbl where meta_key ='multiloc_checkbox_value' and meta_value = '%d'", $meta_value );
			$excluded_pages = $wpdb->get_col( $prepare_guery );
			// print_r($get_values);


			$excluded_pages = implode(',', $excluded_pages);

			$all_pages = get_pages( array('parent'=>$current_page_info, 'exclude'=>$excluded_pages, 'sort_column'=> 'menu_order'));

			$menu_id = str_replace(" ","",get_option('multiloc_fl_id')); ?>

		  	<div class="multiloc_auto_menu <?= esc_attr(get_option('multiloc_fl_classes')); ?>" <?php if($menu_id <> "") { ?>id="<?php echo esc_attr($menu_id); ?>" <?php } ?>>
			  	<nav id="main_menu" class="navbar navbar-expand-lg main_menu qam">
			  	<?php echo "<ul class='nav navbar-nav' id='menu-main-menu'>";

			foreach($all_pages as $page) {

				$text_link = get_post_meta( $page->ID, 'multiloc_textbox_value', true );

				  $resources_array = array();
				  if(!empty($all_pages)) {
				  	  	  $loop_count = "1";

						  	$children = get_pages( array( 'parent' => $page->ID, 'exclude' =>  $pageIDList, ) );

						  	$qa_mega_menu_pos = "multiloc_fl_mega_menu_pos".$loop_count;
						  	$qa_mega_val = "";

						    if(get_option($qa_mega_menu_pos) == 'on' ) {
						    	$qa_mega_val = "1";
						    }

						    // if have sub pages
						  	if($children) {

						  		$ch_gp_args = array(
							      'post_type' => 'page',
							      'post_parent' => $page->ID,
							      'order' => 'ASC',
							      'orderby' => 'menu_order title',
							      'exclude' =>  $excluded_pages,
							      'posts_per_page' => -1
							  );
							  $ch_all_pages = get_posts($ch_gp_args);

							// if mega menu is enabled for this menu position
							if(get_option('multiloc_fl_mega_menu') == 'on' && $qa_mega_val=="1") {

								// 	$text_link = get_post_meta( $page->ID, 'multiloc_textbox_value', true );
								// 	if ($text_link !== ""){
								// 		$text_link = $text_link;
								// 	}
								// 	else {
								// 		$text_link = $page->post_title;
								// 	}

								// 	if($page->ID == $current_page_id) {
								// 		$current_menu_class = " current_page_item current-menu-ancestor current_page_parent current_page_ancestor";
								// 	} else {
								// 		$current_menu_class = "";
								// 	}

								// 	echo "<li class='dropdown mega-dropdown menu-item-has-children".$current_menu_class."'><a class=' ' data-toggle='dropdown' href='".get_permalink($page->ID)."'>".$text_link."</a>";
								// 	$array_splitup_numbers = array("1","4","7","10","13","16","19","22","25","28");
								// 	$array_splitup_end_numbers = array("3","6","9","12","15","18","21","24","27","30");
								// 	echo "<ul class='dropdown-menu sub-menu mega-dropdown-menu sub-menu'>";
								// 		echo "<li><ul class='row'>";
								// 			$ch_count_loop = 1;
								// 			foreach ($ch_all_pages as $ch_page) {

								// 				if(in_array($ch_count_loop, $array_splitup_numbers)) {

								// 					echo "<li class='col-sm-3'><ul>";
								// 				}

								// 				$post_feat_img = get_post_meta($ch_page->ID, 'multiloc_am_img_url', true);
								// 				$text_link_child = get_post_meta( $ch_page->ID, 'multiloc_textbox_value', true );
								// 				if ($text_link_child != ""){
								// 					$text_link_child = $text_link_child;
								// 				}
								// 				else {
								// 					$text_link_child = $ch_page->post_title;
								// 				}



								// 				echo "<li class='page_item nav-item '>";
								// 				if ( ! empty ( $post_feat_img ) ) {

								// 					echo "<img src='".$post_feat_img."' alt='".$text_link_child."' />";

								// 				}
								// 				echo "<a class='' href='".get_permalink($ch_page->ID)."'>".$text_link_child."</a>";
								// 				echo "</li>";
								// 				if(in_array($ch_count_loop, $array_splitup_end_numbers)) {

								// 					echo "</ul></li>";
								// 				} elseif((!in_array(count($ch_all_pages), $array_splitup_end_numbers))&&(count($ch_all_pages)==$ch_count_loop)) {

								// 					echo "</ul></li>";
								// 				}
								// 				$ch_count_loop++;
								// 			}

								// 		echo "</ul></li>";
								// 	echo "</ul>";
								// echo "</li>";

							}
							// if mega menu is NOT enabled for this menu position
							else {
								$text_link = get_post_meta( $page->ID, 'multiloc_textbox_value', true );
								if ($text_link !== ""){
									$text_link = $text_link;
								}
								else {
									$text_link = $page->post_title;
								}

								if($page->ID == $current_page_id) {
									$current_menu_class = " current_page_item current-menu-ancestor current_page_parent current_page_ancestor";
								} else {
									$current_menu_class = "";
								}

						  	  echo "<li class='page_item nav-item dropdown".$current_menu_class."'><a class=' ' href='".get_permalink($page->ID)."'>".$text_link."</a>";

						  	  echo "<ul class='sub-menu' role='menu' aria-labelledby='dropdownMenuButton'>";


						  	  if(strtolower($page->post_title) == $location." - resources") {



							  	if( $location == '' || $location == "Corporate" || $location == "Default - Edit this to Corporate location" || $location == "default" ){

							  		$url = home_url("/fbbcblog/");
									$target = "_self";

								} else {

									$path = $location."/fbbcblog";
									$page_info = get_page_by_path($path);
									if(empty($page_info)) {
										$url = home_url("/fbbcblog/");
										$target = "_blank";
									} else {
										if($page_info->post_status == "publish") {
											$url = home_url($path);
											$target = "_self";
										} else {
											$url = home_url("/fbbcblog/");
											$target = "_blank";
										}

									}
								}

								$blog_id = url_to_postid($url);
								if($blog_id == $current_page_id) {
									$current_menu_class_sub = " current_page_item current-menu-item";
								} else {
									$current_menu_class_sub = "";
								}

								echo "<li class='page_item nav-item".$current_menu_class_sub."'><a class='' href='".$url."' target='".$target."'>Blog</a></li>";

								echo "<li class='page_item nav-item '><a class='' href='".home_url("/podcast/")."' target='_blank'>Podcast</a></li>";

							  }

							  foreach ($ch_all_pages as $ch_page) {
									$text_link_child = get_post_meta( $ch_page->ID, 'multiloc_textbox_value', true );
									if ($text_link_child != ""){
										$text_link_child = $text_link_child;
									}
									else {
										$text_link_child = $ch_page->post_title;
									}
						  	    $post_feat_img = get_post_meta($ch_page->ID, 'multiloc_am_img_url', true);

								if ( ! empty ( $post_feat_img ) ) {

							  		echo "<li class='page_item nav-item'><img src='".$post_feat_img."' alt='".$text_link_child."' /></li>";

								}

								if($ch_page->ID == $current_page_id) {
									$current_menu_class_sub = " current_page_item current-menu-item";
								} else {
									$current_menu_class_sub = "";
								}

							  	echo "<li class='page_item nav-item".$current_menu_class_sub."'><a class='' href='".get_permalink($ch_page->ID)."'>".$text_link_child."</a></li>";

							  }
							  echo "<li class='page_item nav-item '><a class='' href='https://support.fitbodybootcamp.com/' target='_blank'>Customer Support</a></li>";
						  	  echo "</ul>";
							  echo "</li>";
							}
							wp_reset_query();
						  	}
						  	// if DOESN'T have sub pages
						  	else {

					  			if($page->ID == $current_page_id) {
									$current_menu_class = " current_page_item current-menu-ancestor current_page_parent current_page_ancestor";
								} else {
									$current_menu_class = "";
								}
								$text_link = get_post_meta( $page->ID, 'multiloc_textbox_value', true );
								if ($text_link !== ""){
									$text_link = $text_link;
								}
								else {
									$text_link = $page->post_title;
								}
								//echo "<h1 style='color:yellow;'>" . $text_link . "</h1>";



								$resources_array[] = $page->post_title;

								// If the page title is location name + - resources, then add blog and podcast links to the menu
								if(strtolower($page->post_title) == $location." - resources") {

								echo "<li class='page_item nav-item dropdown".$current_menu_class."'><a class=' ' href='".get_permalink($page->ID)."'>".$text_link."</a>";

						  		echo "<ul class='sub-menu' role='menu' aria-labelledby='dropdownMenuButton'>";

							  	if( $location == '' || $location == "Corporate" || $location == "Default - Edit this to Corporate location" || $location == "default" ){

									$url = home_url("/fbbcblog/");
									$target = "_self";

								} else {

									$path = $location."/fbbcblog";
									$page_info = get_page_by_path($path);
									if(empty($page_info)) {
										$url = home_url("/fbbcblog/");
										$target = "_blank";
									} else {
										if($page_info->post_status == "publish") {
											$url = home_url($path);
											$target = "_self";
										} else {
											$url = home_url("/fbbcblog/");
											$target = "_blank";
										}

									}
								}

								$blog_id = url_to_postid($url);
								if($blog_id == $current_page_id) {
									$current_menu_class_sub = " current_page_item current-menu-item";
								}

								echo "<li class='page_item nav-item".$current_menu_class_sub."'><a class='' href='".$url."' target='".$target."'>Blog</a></li>";

								echo "<li class='page_item nav-item '><a class='' href='".home_url("/podcast/")."' target='_blank'>Podcast</a></li>";
								echo "<li class='page_item nav-item '><a class='' href='https://support.fitbodybootcamp.com/' target='_blank'>Customer Support</a></li>";

						  	    echo "</ul>";
							    echo "</li>";


							  } else {

						  		echo "<li class='page_item nav-item".$current_menu_class."'><a class='' href='".get_permalink($page->ID)."'>".$text_link."</a></li>";

							  }

						  	}

						  	$loop_count++;
						  }

					  	 ?>

				  <?php }

				}
					if( $location == '' || $location == "Corporate" || $location == "Default - Edit this to Corporate location" || $location == "default" ){
						// $prod_28 = home_url()."/offers/28-days-for-77/";
						$prod_28 = home_url()."/#get_a_free_week";
						//$prod_28 = (empty($currentPromo)) ? home_url()."/offers/28-days-for-77/" : home_url()."/offers/new-year-new-you-non-member/" ;
					} else {
						// $prod_28 = home_url($location.'/offers/28-days-for-77/');
						$prod_28 = home_url($location.'/#get_a_free_week');
						//$prod_28 = (empty($currentPromo)) ? home_url($location.'/offers/28-days-for-77/') : home_url($location.'/offers/new-year-new-you-non-member/') ;
					}
					//condition for the March Promotion for the Desktop button 
					$productText = ($currentPromo == "marchpromo" && (($currentTime >= $marchPromoStartDate) && ($currentTime <= $marchPromoEndDate))) ? "3 FREE DAYS" : "3 FREE DAYS";
					//$productText = (empty($currentPromo)) ? "GET 28 DAYS FOR $77!" : $currentPromo ;
					if($location == "9359-chino-hills-ca" && is_page_template('page-templates/new-year-new-you-non-client.php')) {
										
					} else {
						echo "<li id='menu-item-5816' class='main_menu_highlight get_a_free_week_section menu-item menu-item-type-post_type menu-item-object-page menu-item-5816'><a href='".$prod_28."'>".$productText."</a></li>";
					}
				?>

			</ul>
		</nav>
	</div>

<?php }

add_shortcode('build_menu', 'build_my_menu');

//------------> End buildMenu


// create custom plugin settings menu
add_action('admin_menu', 'multiloc_menu_create_admin_menu');


function multiloc_menu_create_admin_menu() {
	//create new top-level menu
	add_menu_page('Multiloc Menu Settings', 'Multiloc Menus', 'manage_options', 'multiloc-menus', 'multiloc_menu_settings_page', 'dashicons-menu', 26); 
}

//register the settings fields
add_action( 'admin_init', 'multiloc_menu_register_settings' );

function multiloc_menu_register_settings() {
	//register the settings to store the input field values
	register_setting( 'multiloc-menu-settings-group', 'multiloc_fl_classes' );
	register_setting( 'multiloc-menu-settings-group', 'multiloc_fl_id' );
	register_setting( 'multiloc-menu-settings-group', 'multiloc_fl_mega_menu' );
	register_setting( 'multiloc-menu-settings-group', 'multiloc_fl_mega_menu_pos1' );
	register_setting( 'multiloc-menu-settings-group', 'multiloc_fl_mega_menu_pos2' );
	register_setting( 'multiloc-menu-settings-group', 'multiloc_fl_mega_menu_pos3' );
	register_setting( 'multiloc-menu-settings-group', 'multiloc_fl_mega_menu_pos4' );
	register_setting( 'multiloc-menu-settings-group', 'multiloc_fl_mega_menu_pos5' );
	register_setting( 'multiloc-menu-settings-group', 'multiloc_fl_mega_menu_pos6' );
	register_setting( 'multiloc-menu-settings-group', 'multiloc_fl_mega_menu_pos7' );
	register_setting( 'multiloc-menu-settings-group', 'multiloc_fl_mega_menu_pos8' );
}

function multiloc_menu_settings_page() { ?>
<div class="wrap">

	<h1>Multiloc Menu Settings</h1>

	<form method="post" action="options.php">
	    <?php settings_fields( 'multiloc-menu-settings-group' ); ?>
	    <?php do_settings_sections( 'multiloc-menu-settings-group' ); ?>
	    
	    <table class="form-table">
	        <tr valign="top">
	        <th scope="row">Add classes for the menu</th>
	        <td><input type="text" name="multiloc_fl_classes" id="multiloc_fl_classes" value="<?php echo esc_attr( get_option('multiloc_fl_classes') ); ?>" /><br/><small><strong>Tip:</strong> To add multiple classes, use space between classes. Example: class1 class2 class3</small></td>
	        </tr>
	         
	        <tr valign="top">
	        <th scope="row">Add ID for the menu</th>
	        <td><input type="text" name="multiloc_fl_id" id="multiloc_fl_id" value="<?php echo esc_attr( get_option('multiloc_fl_id') ); ?>" /></td>
	        </tr>
	     	<tr valign="top">
	        <th scope="row">Enable Mega Menu</th>
	        <td><input type="checkbox" name="multiloc_fl_mega_menu" id="multiloc_fl_mega_menu" <?php if(get_option('multiloc_fl_mega_menu') == 'on' ) { echo 'checked'; } ?> /></td>
	        </tr>
	    </table>

	    <table class="form-table en_mega_menu_b_o">

	        <tr valign="top">
	        <th scope="row">Mega Menu Position</th>
	        <td>1 <input type="checkbox" name="multiloc_fl_mega_menu_pos1" id="multiloc_fl_mega_menu_pos1" <?php if(get_option('multiloc_fl_mega_menu_pos1') == 'on' ) { echo 'checked'; } ?> /></td>
	        <td>2 <input type="checkbox" name="multiloc_fl_mega_menu_pos2" id="multiloc_fl_mega_menu_pos2" <?php if(get_option('multiloc_fl_mega_menu_pos2') == 'on' ) { echo 'checked'; } ?> /></td>
	        <td>3 <input type="checkbox" name="multiloc_fl_mega_menu_pos3" id="multiloc_fl_mega_menu_pos3" <?php if(get_option('multiloc_fl_mega_menu_pos3') == 'on' ) { echo 'checked'; } ?> /></td>
	        <td>4 <input type="checkbox" name="multiloc_fl_mega_menu_pos4" id="multiloc_fl_mega_menu_pos4" <?php if(get_option('multiloc_fl_mega_menu_pos4') == 'on' ) { echo 'checked'; } ?> /></td>
	        <td>5 <input type="checkbox" name="multiloc_fl_mega_menu_pos5" id="multiloc_fl_mega_menu_pos5" <?php if(get_option('multiloc_fl_mega_menu_pos5') == 'on' ) { echo 'checked'; } ?> /></td>
	        <td>6 <input type="checkbox" name="multiloc_fl_mega_menu_pos6" id="multiloc_fl_mega_menu_pos6" <?php if(get_option('multiloc_fl_mega_menu_pos6') == 'on' ) { echo 'checked'; } ?> /></td>
	        <td>7 <input type="checkbox" name="multiloc_fl_mega_menu_pos7" id="multiloc_fl_mega_menu_pos7" <?php if(get_option('multiloc_fl_mega_menu_pos7') == 'on' ) { echo 'checked'; } ?> /></td>
	        <td>8 <input type="checkbox" name="multiloc_fl_mega_menu_pos8" id="multiloc_fl_mega_menu_pos8" <?php if(get_option('multiloc_fl_mega_menu_pos8') == 'on' ) { echo 'checked'; } ?> /></td>
	       
	        </tr>
	    </table>
		

	    <?php submit_button(); ?>

	</form>

</div>
<?php } 
