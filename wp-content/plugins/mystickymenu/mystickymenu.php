<?php
/*
Plugin Name: myStickymenu
Plugin URI: https://premio.io/
Description: Simple sticky (fixed on top) menu implementation for navigation menu and Welcome bar for announcements and promotion. After install go to Settings / myStickymenu and change Sticky Class to .your_navbar_class or #your_navbar_id.
Version: 2.3.4
Author: Premio
Author URI: https://premio.io/downloads/mystickymenu/
Text Domain: mystickymenu
Domain Path: /languages
License: GPLv2 or later
*/

defined('ABSPATH') or die("Cannot access pages directly.");
define( 'MYSTICKY_VERSION', '2.3.4' );
require_once("mystickymenu-fonts.php");
require_once("welcome-bar.php");

if(is_admin()) {
    include_once 'class-review-box.php';
}

class MyStickyMenuBackend
{
    private $options;

	public function __construct()
	{
		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'mysticky_load_transl') );

		add_action( 'admin_init', array( $this, 'mysticky_default_options' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'mysticky_admin_script' ) );

		add_filter( 'plugin_action_links_mystickymenu/mystickymenu.php', array( $this, 'mystickymenu_settings_link' )  );
		
		add_action( 'activated_plugin', array( $this, 'mystickymenu_activation_redirect' ) );

		add_action("wp_ajax_sticky_menu_update_status", array($this, 'sticky_menu_update_status'));
    }

    public function sticky_menu_update_status() {
        if(!empty($_REQUEST['nonce']) && wp_verify_nonce($_REQUEST['nonce'], 'myStickymenu_update_nonce')) {
            $status = self::sanitize_options($_REQUEST['status']);
            $email = self::sanitize_options($_REQUEST['email']);
            update_option("mystickymenu_update_message", 2);
            if($status == 1) {
                $url = 'https://go.premio.io/api/update.php?email='.$email.'&plugin=myStickymenu';
                $handle = curl_init();
                curl_setopt($handle, CURLOPT_URL, $url);
                curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($handle);
                curl_close($handle);
            }
        }
        echo "1";
        die;
    }

	public function mystickymenu_settings_link($links){
		$settings_link = '<a href="admin.php?page=my-stickymenu-settings">Settings</a>';
		$links['go_pro'] = '<a href="'.admin_url("admin.php?page=my-stickymenu-settings&type=upgrade").'" style="color: #FF5983;font-weight: bold;">'.__( 'Upgrade', 'stars-testimonials' ).'</a>';
		array_unshift($links, $settings_link);
		return $links;
	}
	
	public function mystickymenu_activation_redirect( $plugin) {
		if( $plugin == plugin_basename( __FILE__ ) ) {
		    $is_shown = get_option("mystickymenu_update_message");
		    if($is_shown === false) {
		        add_option("mystickymenu_update_message", 1);
            }
			wp_redirect( admin_url( 'admin.php?page=my-stickymenu-settings' ) ) ;
			exit;
		}
	}

    public function mysticky_admin_script($hook) {
		
		if ( $hook != 'toplevel_page_my-stickymenu-settings' && $hook != 'mystickymenu_page_my-stickymenu-welcomebar' && $hook != 'mystickymenu_page_my-stickymenu-upgrade' ) {
			return;
		}
		wp_enqueue_style('mystickymenuAdminStyle', plugins_url('/css/mystickymenu-admin.css', __FILE__), array(), MYSTICKY_VERSION );    
		wp_enqueue_style( 'wp-color-picker' );		
		//wp_enqueue_script( 'wp-color-picker-alpha', plugins_url('/js/wp-color-picker-alpha.min.js', __FILE__), array( 'wp-color-picker' ), MYSTICKY_VERSION );
		wp_enqueue_style( 'wp-jquery-ui-dialog' );
		wp_enqueue_style('jquery-ui');
		
		wp_enqueue_script('jquery-ui');
		wp_enqueue_script('jquery-ui-slider');
		//wp_enqueue_script('jquery-ui-datepicker');
		wp_enqueue_script( 'jquery-ui-dialog' );	
		wp_enqueue_script( 'my-script-handle', plugins_url('js/iris-script.js', __FILE__ ), array( 'wp-color-picker' ), false, true );
		wp_enqueue_script('mystickymenuAdminScript', plugins_url('/js/mystickymenu-admin.js', __FILE__), array( 'jquery', 'jquery-ui-slider' ), MYSTICKY_VERSION);
	}

	public function mysticky_load_transl(){
		load_plugin_textdomain('mystickymenu', FALSE, dirname(plugin_basename(__FILE__)).'/languages/');
	}

	function sanitize_options($value) {
		$value = stripslashes($value);
		$value = filter_var($value, FILTER_SANITIZE_STRING);
		return $value;
	}

	public function add_plugin_page(){
		// This page will be under "Settings"
		add_menu_page(
			'Settings Admin',
			'myStickymenu',
			'manage_options',
			'my-stickymenu-settings',
			array( $this, 'create_admin_page' )
		);
		add_submenu_page(
			'my-stickymenu-settings',
			'Settings Admin',
			'Settings',
			'manage_options',
			'my-stickymenu-settings',
			array( $this, 'create_admin_page' )
		);
		add_submenu_page(
			'my-stickymenu-settings',
			'Settings Admin',
			'Welcome Bar',
			'manage_options',
			'my-stickymenu-welcomebar',
			array( $this, 'mystickystickymenu_admin_welcomebar_page' )
		);
		add_submenu_page(
			'my-stickymenu-settings',
			'Upgrade to Pro',
			'Upgrade to Pro',
			'manage_options',
			'my-stickymenu-upgrade',
			array( $this, 'mystickymenu_admin_upgrade_to_pro' )
		);
	}

	public function create_admin_page(){
		$upgarde_url = admin_url("admin.php?page=my-stickymenu-upgrade");
		// Set class property
		if (isset($_POST['mysticky_option_name']) && !empty($_POST['mysticky_option_name']) && isset($_POST['nonce'])) {
			if(!empty($_REQUEST['nonce']) && wp_verify_nonce($_REQUEST['nonce'], 'mysticky_option_backend_update')) {
				$post = $_POST['mysticky_option_name'];
				foreach($post as $key=>$value) {
					$post[$key] = self::sanitize_options($value);
				}
				
				$post['device_desktop'] = 'on';
				$post['device_mobile'] = 'on';
				update_option( 'mysticky_option_name', $post);
				echo '<div class="updated settings-error notice is-dismissible "><p><strong>' . esc_html__('Settings saved.','mystickymenu'). '</p></strong></div>';
			} else {
				wp_verify_nonce($_GET['nonce'], 'wporg_frontend_delete');
				echo '<div class="error settings-error notice is-dismissible "><p><strong>' . esc_html__('Unable to complete your request','mystickymenu'). '</p></strong></div>';
			}
		}		

		$mysticky_options = get_option( 'mysticky_option_name');
		$is_old = get_option("has_sticky_header_old_version");
		$is_old = ($is_old == "yes")?true:false;
		$nonce = wp_create_nonce('mysticky_option_backend_update');
        $pro_url = "https://go.premio.io/?edd_action=add_to_cart&download_id=2199&edd_options[price_id]=";
		
        $is_shown = get_option("mystickymenu_update_message");
        if($is_shown == 1) {?>
            <div class="updates-form-form" >
                <div class="popup-form-content">
                    <div id="add-update-folder-title" class="add-update-folder-title">
                        Would you like to get feature updates for myStickymenu in real-time?
                    </div>
                    <div class="folder-form-input">
                        <input id="myStickymenu_update_email" autocomplete="off" value="<?php echo get_option( 'admin_email' ) ?>" placeholder="Email address">
                    </div>
                    <div class="updates-content-buttons">
                        <button href="javascript:;" class="button button-primary form-submit-btn yes">Yes, I want</button>
                        <button href="javascript:;" class="button button-secondary form-cancel-btn no">Skip</button>
                        <div style="clear: both"></div>
                    </div>
                    <input type="hidden" id="myStickymenu_update_nonce" value="<?php echo wp_create_nonce("myStickymenu_update_nonce") ?>">
                </div>
            </div>
        <?php } else { ?>
        <style>
            div#wpcontent {
                background: rgba(101,114,219,1);
                background: -moz-linear-gradient(-45deg, rgba(101,114,219,1) 0%, rgba(238,134,198,1) 67%, rgba(238,134,198,1) 100%);
                background: -webkit-gradient(left top, right bottom, color-stop(0%, rgba(101,114,219,1)), color-stop(67%, rgba(238,134,198,1)), color-stop(100%, rgba(238,134,198,1)));
                background: -webkit-linear-gradient(-45deg, rgba(101,114,219,1) 0%, rgba(238,134,198,1) 67%, rgba(238,134,198,1) 100%);
                background: -o-linear-gradient(-45deg, rgba(101,114,219,1) 0%, rgba(238,134,198,1) 67%, rgba(238,134,198,1) 100%);
                background: -ms-linear-gradient(-45deg, rgba(101,114,219,1) 0%, rgba(238,134,198,1) 67%, rgba(238,134,198,1) 100%);
                background: linear-gradient(135deg, rgba(101,114,219,1) 0%, rgba(238,134,198,1) 67%, rgba(238,134,198,1) 100%);
                filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#6572db', endColorstr='#ee86c6', GradientType=1 );
            }
        </style>
		<div id="mystickymenu" class="wrap mystickymenu">
			<div class="sticky-header-menu">
				<ul>
					<li><a href="<?php echo admin_url( 'admin.php?page=my-stickymenu-settings' ) ?>" class="active" ><?php _e('Sticky Menu', 'mystickymenu'); ?></a></li>
					<li><a href="<?php echo admin_url( 'admin.php?page=my-stickymenu-welcomebar' ) ?>" ><?php _e('Welcome Bar', 'mystickymenu'); ?></a></li>
					<li><a href="<?php echo admin_url( 'admin.php?page=my-stickymenu-upgrade' ) ?>"><?php _e('Upgrade to Pro', 'mystickymenu'); ?></a></li>
				</ul>
			</div>
			<div id="sticky-header-settings" class="sticky-header-content">
				<div class="mystickymenu-heading">
					<div class="myStickymenu-header-title">
						<h3><?php esc_attr_e('How To Make a Sticky Header', 'mystickymenu'); ?></h3>
					</div>
					<p><?php _e("Add sticky menu / header to any theme. <br />Simply change 'Sticky Class' to HTML element class desired to be sticky (div id can be used as well).", 'mystickymenu'); ?></p>
				</div>

				<form class="mysticky-form" method="post" action="#">
				<div class="mystickymenu-content-section sticky-class-sec">
					<table>
						<tr>
							<td>
								<label class="mysticky_title"><?php _e("Sticky Class", 'mystickymenu')?></label>
								<br /><br />
								<?php $nav_menus  = wp_get_nav_menus();
								$menu_locations = get_nav_menu_locations();
								$locations      = get_registered_nav_menus();
								?>
								<select name="mysticky_option_name[mysticky_class_id_selector]" id="mystickymenu-select">
									<option value=""><?php _e( 'Select Sticky Menu', 'mystickymenu' ); ?></option>

									<?php foreach ( (array) $nav_menus as $_nav_menu ) : ?>
										<option value="<?php echo esc_attr( $_nav_menu->slug ); ?>" <?php selected( $_nav_menu->slug, $mysticky_options['mysticky_class_id_selector'] ); ?>>
											<?php
											echo esc_html( $_nav_menu->name );

											if ( ! empty( $menu_locations ) && in_array( $_nav_menu->term_id, $menu_locations ) ) {
												$locations_assigned_to_this_menu = array();
												foreach ( array_keys( $menu_locations, $_nav_menu->term_id ) as $menu_location_key ) {
													if ( isset( $locations[ $menu_location_key ] ) ) {
														$locations_assigned_to_this_menu[] = $locations[ $menu_location_key ];
													}
												}

												/**
												 * Filters the number of locations listed per menu in the drop-down select.
												 *
												 * @since 3.6.0
												 *
												 * @param int $locations Number of menu locations to list. Default 3.
												 */
												$assigned_locations = array_slice( $locations_assigned_to_this_menu, 0, absint( apply_filters( 'wp_nav_locations_listed_per_menu', 3 ) ) );

												// Adds ellipses following the number of locations defined in $assigned_locations.
												if ( ! empty( $assigned_locations ) ) {
													printf(
														' (%1$s%2$s)',
														implode( ', ', $assigned_locations ),
														count( $locations_assigned_to_this_menu ) > count( $assigned_locations ) ? ' &hellip;' : ''
													);
												}
											}
											?>
										</option>
									<?php endforeach; ?>
									<option value="custom" <?php selected( 'custom', $mysticky_options['mysticky_class_id_selector'] ); ?>><?php esc_html_e( 'Other Class Or ID', 'mystickymenu' );?></option>
								</select>

								<input type="text" size="18" id="mysticky_class_selector" class="mystickyinput" name="mysticky_option_name[mysticky_class_selector]" value="<?php echo $mysticky_options['mysticky_class_selector'];?>"  />

								<p class="description"><?php _e("menu or header element class or id.", 'mystickymenu')?></p>
							</td>
							<td>
								<div class="mysticky_device_upgrade">
									<label class="mysticky_title"><?php _e("Devices", 'mystickymenu')?></label>
									<span class="myStickymenu-upgrade"><a class="sticky-header-upgrade-now" href="<?php echo esc_url($upgarde_url); ?>" target="_blank"><?php _e( 'Upgrade Now', 'mystickymenu' );?></a></span>
									
									<ul class="mystickymenu-input-multicheckbox">
										<li>
										<label>
											<input id="disable_css" name="mysticky_option_name[device_desktop]" type="checkbox"  checked  disabled />
											<?php _e( 'Desktop', 'mystickymenu' );?>
										</label>
										</li>
										<li>
										<label>
											<input id="disable_css" name="mysticky_option_name[device_mobile]" type="checkbox" checked disabled />
											<?php _e( 'Mobile', 'mystickymenu' );?>
										</label>
										</li>
									</ul>
								</div>
							</td>
						</tr>
					</table>
				</div>


				<div class="mystickymenu-content-section">
					<h3><?php esc_html_e( 'Settings', 'mystickymenu' );?></h3>
					<table class="form-table">
						<tr>
							<td>
								<label for="myfixed_zindex" class="mysticky_title"><?php _e("Sticky z-index", 'mystickymenu')?></label>
							</td>
							<td>
								<input type="number" min="0" max="2147483647" step="1" class="mysticky-number" id="myfixed_zindex" name="mysticky_option_name[myfixed_zindex]" value="<?php echo $mysticky_options['myfixed_zindex'];?>" />
							</td>
							<td>
								<label class="mysticky_title myssticky-remove-hand"><?php _e("Fade or slide effect", 'mystickymenu')?></label>
							</td>
							<td>
								<label>
								<input name="mysticky_option_name[myfixed_fade]" value= "slide" type="radio" <?php checked( @$mysticky_options['myfixed_fade'], 'slide' );?> />
								<?php _e("Slide", 'mystickymenu'); ?>
								</label>
								<label>
								<input name="mysticky_option_name[myfixed_fade]" value="fade" type="radio"  <?php checked( @$mysticky_options['myfixed_fade'], 'fade' );?> />
								<?php _e("Fade", 'mystickymenu'); ?>
								</label>
							</td>
						</tr>
						<tr>
							<td>
								<label for="myfixed_disable_small_screen" class="mysticky_title"><?php _e("Disable at Small Screen Sizes", 'mystickymenu')?></label>
								<p class="description"><?php esc_attr_e('Less than chosen screen width, set 0 to disable','mystickymenu');?></p>
							</td>
							<td>
								<div class="px-wrap">
									<input type="number" class="" min="0" step="1" id="myfixed_disable_small_screen" name="mysticky_option_name[myfixed_disable_small_screen]" value="<?php echo $mysticky_options['myfixed_disable_small_screen'];?>" />
									<span class="input-px">PX</span>
								</div>
							</td>
							<td>
								<label for="mysticky_active_on_height" class="mysticky_title"><?php _e("Make visible on Scroll", 'mystickymenu')?></label>
								<p class="description"><?php esc_attr_e('If set to 0 auto calculate will be used.','mystickymenu');?></p>
							</td>
							<td>
								<div class="px-wrap">
									<input type="number" class="small-text" min="0" step="1" id="mysticky_active_on_height" name="mysticky_option_name[mysticky_active_on_height]" value="<?php echo $mysticky_options['mysticky_active_on_height'];?>" />
									<span class="input-px">PX</span>
								</div>
							</td>
						</tr>
						<tr>
							<td>
								<label for="mysticky_active_on_height_home" class="mysticky_title"><?php _e("Make visible on Scroll at homepage", 'mystickymenu')?></label>
								<p class="description"><?php _e( 'If set to 0 it will use initial Make visible on Scroll value.', 'mystickymenu' );?></p>
							</td>
							<td>
								<div class="px-wrap">
									<input type="number" class="small-text" min="0" step="1" id="mysticky_active_on_height_home" name="mysticky_option_name[mysticky_active_on_height_home]" value="<?php echo $mysticky_options['mysticky_active_on_height_home'];?>" />
									<span class="input-px">PX</span>
								</div>
							</td>
							<td>
								<label for="myfixed_bgcolor" class="mysticky_title myssticky-remove-hand"><?php _e("Sticky Background Color", 'mystickymenu')?></label>
							</td>
							<td>
								<input type="text" id="myfixed_bgcolor" name="mysticky_option_name[myfixed_bgcolor]" class="my-color-field" data-alpha="true" value="<?php echo $mysticky_options['myfixed_bgcolor'];?>" />

							</td>
						</tr>
						<tr>
							<td>
								<label for="myfixed_transition_time" class="mysticky_title"><?php _e("Sticky Transition Time", 'mystickymenu')?></label>
							</td>
							<td>
								<input type="number" class="small-text" min="0" step="0.1" id="myfixed_transition_time" name="mysticky_option_name[myfixed_transition_time]" value="<?php echo $mysticky_options['myfixed_transition_time'];?>" />
							</td>
							<td>
								<label for="myfixed_opacity" class="mysticky_title myssticky-remove-hand"><?php _e("Sticky Opacity", 'mystickymenu')?></label>
								<p class="description"><?php _e( 'numbers 1-100.', 'mystickymenu');?></p>
							</td>
							<td>
								<input type="hidden" class="small-text mysticky-slider" min="0" step="1" max="100" id="myfixed_opacity" name="mysticky_option_name[myfixed_opacity]"  value="<?php echo $mysticky_options['myfixed_opacity'];?>"  />
								<div id="slider">
								  <div id="custom-handle" class="ui-slider-handle"><?php //echo $mysticky_options['myfixed_opacity'];?></div>
								</div>

							</td>
						</tr>
					</table>
				</div>

				<div class="mystickymenu-content-section <?php echo !$is_old?"mystickymenu-content-upgrade":""?>" >

					<div class="mystickymenu-content-option">
						<label class="mysticky_title css-style-title"><?php _e("Hide on Scroll Down", 'mystickymenu'); ?></label>
						<?php if(!$is_old) { ?><span class="myStickymenu-upgrade"><a class="sticky-header-upgrade-now" href="<?php echo esc_url($upgarde_url); ?>" target="_blank"><?php _e( 'Upgrade Now', 'mystickymenu' );?></a></span><?php } ?>
						<p>
						<label class="mysticky_text">
							<input id="myfixed_disable_scroll_down" name="mysticky_option_name[myfixed_disable_scroll_down]" type="checkbox" <?php checked( @$mysticky_options['myfixed_disable_scroll_down'], 'on' );?> <?php echo !$is_old?"disabled":"" ?> />
							<?php _e("Disable sticky menu at scroll down", 'mystickymenu'); ?>
							</label>
						</p>
					</div>
					<div class="mysticky-page-target-setting mystickymenu-content-option">
						<label class="mysticky_title"><?php esc_attr_e('Page targeting', 'myStickymenu'); ?></label>
						<div class="mystickymenu-input-section mystickymenu-page-target-wrap">
							<div class="mysticky-welcomebar-setting-content-right">
								<div class="mysticky-page-options" id="mysticky-welcomebar-page-options">
									<?php $page_option = (isset($mysticky_options['mysticky_page_settings'])) ? $mysticky_options['mysticky_page_settings'] : array();
									$url_options = array(
										'page_contains' => 'pages that contain',
										'page_has_url' => 'a specific page',
										'page_start_with' => 'pages starting with',
										'page_end_with' => 'pages ending with',
									);

									if(!empty($page_option) && is_array($page_option)) {
										$count = 0;
										foreach($page_option as $k=>$option) {
											$count++;
											?>
											<div class="mysticky-page-option <?php echo $k==count($page_option)?"last":""; ?>">
												<div class="url-content">
													<div class="mysticky-welcomebar-url-select">
														<select name="mysticky_option_name[mysticky_page_settings][<?php echo $count; ?>][shown_on]" id="url_shown_on_<?php echo $count  ?>_option">
															<option value="show_on" <?php echo $option['shown_on']=="show_on"?"selected":"" ?> ><?php esc_html_e( 'Show on', 'mysticky' )?></option>
															<option value="not_show_on" <?php echo $option['shown_on']=="not_show_on"?"selected":"" ?>><?php esc_html_e( "Don't show on", "mysticky" );?></option>
														</select>
													</div>
													<div class="mysticky-welcomebar-url-option">
														<select class="mysticky-url-options" name="mysticky_option_name[mysticky_page_settings][<?php echo $count; ?>][option]" id="url_rules_<?php echo $count  ?>_option">
															<option disabled value=""><?php esc_html_e( "Select Rule", "mysticky" );?></option>
															<?php foreach($url_options as $key=>$value) {
																$selected = ( isset($option['option']) && $option['option']==$key )?" selected='selected' ":"";
																echo '<option '.$selected.' value="'.$key.'">'.$value.'</option>';
															} ?>
														</select>
													</div>
													<div class="mysticky-welcomebar-url-box">
														<span class='mysticky-welcomebar-url'><?php echo site_url("/"); ?></span>
													</div>
													<div class="mysticky-welcomebar-url-values">
														<input type="text" value="<?php echo $option['value'] ?>" name="mysticky_option_name[mysticky_page_settings][<?php echo $count; ?>][value]" id="url_rules_<?php echo $count; ?>_value" />
													</div>
													<div class="mysticky-welcomebar-url-buttons">
														<a class="mysticky-remove-rule" href="javascript:;">x</a>
													</div>
													<div class="clear"></div>
												</div>
											</div>
											<?php
										}
									}
									?>
								</div>
								<a href="javascript:void(0);" class="create-rule" id="mysticky_create-rule"><?php esc_html_e( "Add Rule", "mystickyelements" );?></a>
							</div>
							<input type="hidden" id="mysticky_welcomebar_site_url" value="<?php echo site_url("/") ?>" />
							<div class="mysticky-page-options-html" style="display: none;">
								<div class="mysticky-page-option">
									<div class="url-content">
										<div class="mysticky-welcomebar-url-select">
											<select name="mysticky_option_name[mysticky_page_settings][__count__][shown_on]" id="url_shown_on___count___option" <?php echo !$is_pro_active?"disabled":"" ?>>
												<option value="show_on"><?php esc_html_e("Show on", "mysticky" );?></option>
												<option value="not_show_on"><?php esc_html_e("Don't show on", "mysticky" );?></option>
											</select>
										</div>
										<div class="mysticky-welcomebar-url-option">
											<select class="mysticky-url-options" name="mysticky_option_name[mysticky_page_settings][__count__][option]" id="url_rules___count___option" <?php echo !$is_pro_active?"disabled":"" ?>>
												<option selected="selected" disabled value=""><?php esc_html_e("Select Rule", "mysticky" );?></option>
												<?php foreach($url_options as $key=>$value) {
													echo '<option value="'.$key.'">'.$value.'</option>';
												} ?>
											</select>
										</div>
										<div class="mysticky-welcomebar-url-box">
											<span class='mysticky-welcomebar-url'><?php echo site_url("/"); ?></span>
										</div>
										<div class="mysticky-welcomebar-url-values">
											<input type="text" value="" name="mysticky_option_name[mysticky_page_settings][__count__][value]" id="url_rules___count___value" <?php echo !$is_pro_active?"disabled":"" ?> />
										</div>
										<div class="mysticky-welcomebar-url-buttons">
											<a class="mysticky-remove-rule" href="javascript:void(0);">x</a>
										</div>
										<div class="clear"></div>
									</div>
									<?php if(!$is_old) { ?><span class="myStickymenu-upgrade"><a class="sticky-header-upgrade-now" href="<?php echo esc_url($upgarde_url); ?>" target="_blank"><?php _e( 'Upgrade Now', 'mystickymenu' );?></a></span><?php } ?>
								</div>
							</div>
						</div>
					</div>
					<div class="mystickymenu-content-option">
						<label class="mysticky_title css-style-title"><?php _e("CSS style", 'mystickymenu'); ?></label>
						<span class="mysticky_text"><?php _e( 'Add/edit CSS style. Leave it blank for default style.', 'mystickymenu');?></span>
						<div class="mystickymenu-input-section">
							<textarea type="text" rows="4" cols="60" id="myfixed_cssstyle" name="mysticky_option_name[myfixed_cssstyle]"  <?php echo !$is_old?"disabled":"" ?> ><?php echo @$mysticky_options['myfixed_cssstyle'];?></textarea>
						</div>
						<p><?php esc_html_e( "CSS ID's and Classes to use:", "mystickymenu" );?></p>
						<p>
							#mysticky-wrap { }<br/>
							#mysticky-nav.wrapfixed { }<br/>
							#mysticky-nav.wrapfixed.up { }<br/>
							#mysticky-nav.wrapfixed.down { }<br/>
							#mysticky-nav .navbar { }<br/>
							#mysticky-nav .navbar.myfixed { }<br/>
						</p>
					</div>

					<div class="mystickymenu-content-option">
						<label class="mysticky_title" for="disable_css"><?php _e("Disable CSS style", 'mystickymenu'); ?></label>
						<div class="mystickymenu-input-section">
							<label>
								<input id="disable_css" name="mysticky_option_name[disable_css]" type="checkbox"   <?php echo !$is_old?"disabled":"" ?> <?php checked( @$mysticky_options['disable_css'], 'on' );?> />
								<?php _e( 'Use this option if you plan to include CSS Style manually', 'mystickymenu' );?>
							</label>
						</div>
						<p></p>
					</div>

					<div class="mystickymenu-content-option">
						<label class="mysticky_title"><?php _e("Disable at", 'mystickymenu'); ?></label>
						<?php if(!$is_old) { ?><span class="myStickymenu-upgrade"><a class="sticky-header-upgrade-now" href="<?php echo esc_url($upgarde_url); ?>" target="_blank"><?php _e( 'Upgrade Now', 'mystickymenu' );?></a></span><?php } ?>
						<div class="mystickymenu-input-section">
							<ul class="mystickymenu-input-multicheckbox">
								<li>
									<label>
										<input id="mysticky_disable_at_front_home" name="mysticky_option_name[mysticky_disable_at_front_home]" type="checkbox"  <?php echo !$is_old?"disabled":"" ?>  <?php checked( @$mysticky_options['mysticky_disable_at_front_home'], 'on' );?>/>
										<span><?php esc_attr_e('front page', 'mystickymenu' );?></span>
									</label>
								</li>
								<li>
									<label>
										<input id="mysticky_disable_at_blog" name="mysticky_option_name[mysticky_disable_at_blog]" type="checkbox"  <?php echo !$is_old?"disabled":"" ?>  <?php checked( @$mysticky_options['mysticky_disable_at_blog'], 'on' );?>/>
										<span><?php esc_attr_e('blog page', 'mystickymenu' );?></span>
									</label>
								</li>
								<li>
									<label>
										<input id="mysticky_disable_at_page" name="mysticky_option_name[mysticky_disable_at_page]" type="checkbox"  <?php echo !$is_old?"disabled":"" ?> <?php checked( @$mysticky_options['mysticky_disable_at_page'], 'on' );?> />
										<span><?php esc_attr_e('pages', 'mystickymenu' );?> </span>
									</label>
								</li>
								<li>
									<label>
										<input id="mysticky_disable_at_tag" name="mysticky_option_name[mysticky_disable_at_tag]" type="checkbox"  <?php echo !$is_old?"disabled":"" ?> <?php checked( @$mysticky_options['mysticky_disable_at_tag'], 'on' );?> />
										<span><?php esc_attr_e('tags', 'mystickymenu' );?> </span>
									</label>
								</li>
								<li>
									<label>
										<input id="mysticky_disable_at_category" name="mysticky_option_name[mysticky_disable_at_category]" type="checkbox"  <?php echo !$is_old?"disabled":"" ?>  <?php checked( @$mysticky_options['mysticky_disable_at_category'], 'on' );?>/>
										<span><?php esc_attr_e('categories', 'mystickymenu' );?></span>
									</label>
								</li>
								<li>
									<label>
										<input id="mysticky_disable_at_single" name="mysticky_option_name[mysticky_disable_at_single]" type="checkbox"  <?php echo !$is_old?"disabled":"" ?> <?php checked( @$mysticky_options['mysticky_disable_at_single'], 'on' );?> />
										<span><?php esc_attr_e('posts', 'mystickymenu' );?> </span>
									</label>
								</li>
								<li>
									<label>
										<input id="mysticky_disable_at_archive" name="mysticky_option_name[mysticky_disable_at_archive]" type="checkbox"  <?php echo !$is_old?"disabled":"" ?> <?php checked( @$mysticky_options['mysticky_disable_at_archive'], 'on' );?> />
										<span><?php esc_attr_e('archives', 'mystickymenu' );?> </span>
									</label>
								</li>
								<li>
									<label>
										<input id="mysticky_disable_at_search" name="mysticky_option_name[mysticky_disable_at_search]" type="checkbox"  <?php echo !$is_old?"disabled":"" ?> <?php checked( @$mysticky_options['mysticky_disable_at_search'], 'on' );?> />
										<span><?php esc_attr_e('search', 'mystickymenu' );?> </span>
									</label>
								</li>
								<li>
									<label>
										<input id="mysticky_disable_at_404" name="mysticky_option_name[mysticky_disable_at_404]" type="checkbox"  <?php echo !$is_old?"disabled":"" ?>  <?php checked( @$mysticky_options['mysticky_disable_at_404'], 'on' );?>/>
										<span><?php esc_attr_e('404', 'mystickymenu' );?> </span>
									</label>
								</li>
							</ul>
							
							<?php 
							if  (isset ( $mysticky_options['mysticky_disable_at_page'] ) == true )  {			
								echo '<div class="mystickymenu-input-section">';
								_e('<span class="description"><strong>Except for this pages:</strong> </span>', 'mystickymenu');
						
								printf(
									'<input type="text" size="26" class="mystickymenu_normal_text" id="mysticky_enable_at_pages" name="mysticky_option_name[mysticky_enable_at_pages]" value="%s"  /> ',  
									isset( $mysticky_options['mysticky_enable_at_pages'] ) ? esc_attr( $mysticky_options['mysticky_enable_at_pages']) : '' 
								); 
								
								_e('<span class="description">Comma separated list of pages to enable. It should be page name, id or slug. Example: about-us, 1134, Contact Us. Leave blank if you realy want to disable sticky menu for all pages.</span>', 'mystickymenu');
								echo '</div>';								
							}
							
							if  (isset ( $mysticky_options['mysticky_disable_at_single'] ) == true )  {
			
								echo '<div class="mystickymenu-input-section">';
								_e('<span class="description"><strong>Except for this posts:</strong> </span>', 'mystickymenu');
						
								printf(
									'<input type="text" size="26" class="mystickymenu_normal_text" id="mysticky_enable_at_posts" name="mysticky_option_name[mysticky_enable_at_posts]" value="%s" /> ',  
									isset( $mysticky_options['mysticky_enable_at_posts'] ) ? esc_attr( $mysticky_options['mysticky_enable_at_posts']) : '' 
								); 
								
								_e('<span class="description">Comma separated list of posts to enable. It should be post name, id or slug. Example: about-us, 1134, Contact Us. Leave blank if you realy want to disable sticky menu for all posts.</span>', 'mystickymenu');
								echo '</div>';								
								
							}
							?>
							<p></p>
						</div>
					</div>
					
				</div>
				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e('Save', 'mystickymenu');?>">
				</p>
				<input type="hidden" name="nonce" value="<?php echo $nonce ?>">
				</form>
				<form class="mysticky-hideformreset" method="post" action="">
					<input name="reset_mysticky_options" class="button button-secondary confirm" type="submit" value="<?php esc_attr_e('Reset', 'mystickymenu');?>" >
					<input type="hidden" name="action" value="reset" />
					<?php $nonce = wp_create_nonce('mysticky_option_backend_reset_nonce'); ?>
					<input type="hidden" name="nonce" value="<?php echo $nonce ?>">
				</form>
				<p class="myStickymenu-review"><a href="https://wordpress.org/support/plugin/mystickymenu/reviews/" target="_blank"><?php esc_attr_e('Leave a review','mystickymenu'); ?></a></p>
			</div>
        </div>
        <?php }
	}
	public function mystickystickymenu_admin_welcomebar_page() {
		/* welcome bar save data  */
		if (isset($_POST['mysticky_option_welcomebar']) && !empty($_POST['mysticky_option_welcomebar']) && isset($_POST['nonce'])) {
			if(!empty($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'mysticky_option_welcomebar_update')) {			
				$mysticky_option_welcomebar = filter_var_array( $_POST['mysticky_option_welcomebar'], FILTER_SANITIZE_STRING );
				$mysticky_option_welcomebar['mysticky_welcomebar_height'] = 60;
				$mysticky_option_welcomebar['mysticky_welcomebar_device_desktop'] = 'desktop';
				$mysticky_option_welcomebar['mysticky_welcomebar_device_mobile'] = 'mobile';
				$mysticky_option_welcomebar['mysticky_welcomebar_trigger'] = 'after_a_few_seconds';
				$mysticky_option_welcomebar['mysticky_welcomebar_triggersec'] = '0';
				$mysticky_option_welcomebar['mysticky_welcomebar_expirydate'] = '';
				$mysticky_option_welcomebar['mysticky_welcomebar_page_settings'] = '';
				update_option( 'mysticky_option_welcomebar', $mysticky_option_welcomebar);
				echo '<div class="updated settings-error notice is-dismissible "><p><strong>' . esc_html__('Settings saved.','mystickymenu'). '</p></strong></div>';
			} else {
				wp_verify_nonce($_GET['nonce'], 'wporg_frontend_delete');
				echo '<div class="error settings-error notice is-dismissible "><p><strong>' . esc_html__('Unable to complete your request','mystickymenu'). '</p></strong></div>';
			}
		} 
		if (isset($_POST['mysticky_welcomebar_reset']) && !empty($_POST['mysticky_welcomebar_reset']) && isset($_POST['nonce_reset'])) {
			if(!empty($_POST['nonce_reset']) && wp_verify_nonce($_POST['nonce_reset'], 'mysticky_option_welcomebar_reset')) {	
				$mysticky_option_welcomebar_reset = mysticky_welcomebar_pro_widget_default_fields();				
				update_option( 'mysticky_option_welcomebar', $mysticky_option_welcomebar_reset);
				echo '<div class="updated settings-error notice is-dismissible "><p><strong>' . esc_html__('Reset Settings saved.','mystickymenu'). '</p></strong></div>';
			} else {
				wp_verify_nonce($_GET['nonce'], 'wporg_frontend_delete');
				echo '<div class="error settings-error notice is-dismissible "><p><strong>' . esc_html__('Unable to complete your request','mystickymenu'). '</p></strong></div>';
			}
		}

		$mysticky_options = get_option( 'mysticky_option_name');
		$is_old = get_option("has_sticky_header_old_version");
		$is_old = ($is_old == "yes")?true:false;
		$nonce = wp_create_nonce('mysticky_option_backend_update');
        $pro_url = "https://go.premio.io/?edd_action=add_to_cart&download_id=2199&edd_options[price_id]=";
		
		?>
		<style>
            div#wpcontent {
                background: rgba(101,114,219,1);
                background: -moz-linear-gradient(-45deg, rgba(101,114,219,1) 0%, rgba(238,134,198,1) 67%, rgba(238,134,198,1) 100%);
                background: -webkit-gradient(left top, right bottom, color-stop(0%, rgba(101,114,219,1)), color-stop(67%, rgba(238,134,198,1)), color-stop(100%, rgba(238,134,198,1)));
                background: -webkit-linear-gradient(-45deg, rgba(101,114,219,1) 0%, rgba(238,134,198,1) 67%, rgba(238,134,198,1) 100%);
                background: -o-linear-gradient(-45deg, rgba(101,114,219,1) 0%, rgba(238,134,198,1) 67%, rgba(238,134,198,1) 100%);
                background: -ms-linear-gradient(-45deg, rgba(101,114,219,1) 0%, rgba(238,134,198,1) 67%, rgba(238,134,198,1) 100%);
                background: linear-gradient(135deg, rgba(101,114,219,1) 0%, rgba(238,134,198,1) 67%, rgba(238,134,198,1) 100%);
                filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#6572db', endColorstr='#ee86c6', GradientType=1 );
            }
        </style>
		<div id="mystickymenu" class="wrap mystickymenu">
			<div class="sticky-header-menu">
				<ul>
					<li><a href="<?php echo admin_url( 'admin.php?page=my-stickymenu-settings' ) ?>"><?php _e('Sticky Menu', 'mystickymenu'); ?></a></li>
					<li><a href="<?php echo admin_url( 'admin.php?page=my-stickymenu-welcomebar' ) ?>" class="active" ><?php _e('Welcome Bar', 'mystickymenu'); ?></a></li>
					<li><a href="<?php echo admin_url( 'admin.php?page=my-stickymenu-upgrade' ) ?>"><?php _e('Upgrade to Pro', 'mystickymenu'); ?></a></li>
				</ul>
			</div>
			<div id="sticky-header-welcome-bar" class="sticky-header-content">
				<?php mysticky_welcome_bar_backend(); ?>
			</div>
		</div>
		<?php
	}
	public function mystickymenu_admin_upgrade_to_pro() {
        $pro_url = "https://go.premio.io/checkount/?edd_action=add_to_cart&download_id=2199&edd_options[price_id]=";
        ?>
		<style>
            div#wpcontent {
                background: rgba(101,114,219,1);
                background: -moz-linear-gradient(-45deg, rgba(101,114,219,1) 0%, rgba(238,134,198,1) 67%, rgba(238,134,198,1) 100%);
                background: -webkit-gradient(left top, right bottom, color-stop(0%, rgba(101,114,219,1)), color-stop(67%, rgba(238,134,198,1)), color-stop(100%, rgba(238,134,198,1)));
                background: -webkit-linear-gradient(-45deg, rgba(101,114,219,1) 0%, rgba(238,134,198,1) 67%, rgba(238,134,198,1) 100%);
                background: -o-linear-gradient(-45deg, rgba(101,114,219,1) 0%, rgba(238,134,198,1) 67%, rgba(238,134,198,1) 100%);
                background: -ms-linear-gradient(-45deg, rgba(101,114,219,1) 0%, rgba(238,134,198,1) 67%, rgba(238,134,198,1) 100%);
                background: linear-gradient(135deg, rgba(101,114,219,1) 0%, rgba(238,134,198,1) 67%, rgba(238,134,198,1) 100%);
                filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#6572db', endColorstr='#ee86c6', GradientType=1 );
            }
        </style>
		<div id="mystickymenu" class="wrap mystickymenu">
			<div class="sticky-header-menu">
				<ul>
					<li><a href="<?php echo admin_url( 'admin.php?page=my-stickymenu-settings' ) ?>"><?php _e('Sticky Menu', 'mystickymenu'); ?></a></li>
					<li><a href="<?php echo admin_url( 'admin.php?page=my-stickymenu-welcomebar' ) ?>" ><?php _e('Welcome Bar', 'mystickymenu'); ?></a></li>
					<li><a href="<?php echo admin_url( 'admin.php?page=my-stickymenu-upgrade' ) ?>" class="active" ><?php _e('Upgrade to Pro', 'mystickymenu'); ?></a></li>
				</ul>
			</div>
			<div id="sticky-header-upgrade" class="sticky-header-content">
					<div id="rpt_pricr" class="rpt_plans rpt_3_plans  rpt_style_basic">
						<p class="udner-title">
							<strong class="text-primary">Unlock All Features</strong>
						</p>
						<div class="">
							<div class="rpt_plan  rpt_plan_0  ">
								<div style="text-align:left;" class="rpt_title rpt_title_0">Basic</div>
								<div class="rpt_head rpt_head_0">
									<div class="rpt_recurrence rpt_recurrence_0">For small website owners</div>
									<div class="rpt_price rpt_price_0">$19</div>
									<div class="rpt_description rpt_description_0 rpt_desc">Per year. Renewals for 25% off</div>
									<div style="clear:both;"></div>
								</div>
								<div class="rpt_features rpt_features_0">
									<div class="rpt_feature rpt_feature_0-0"><a href="javascript:;" class="rpt_tooltip"><span class="intool"><b></b>Use myStickymenu on 1 domain</span>1 website<span class="rpt_tooltip_plus" > +</span></a></div>
									<div class="rpt_feature rpt_feature_0-1"><a href="javascript:;" class="rpt_tooltip"><span class="intool"><b></b>You can show the menu when scrolling up, down or both</span>Show on scroll up/down<span class="rpt_tooltip_plus" > +</span></a></div>
									<div class="rpt_feature rpt_feature_0-2"><a href="javascript:;" class="rpt_tooltip"><span class="intool"><b></b>You can disable the sticky effect on desktop or mobile</span>Devices<span class="rpt_tooltip_plus" > +</span></a></div>
									<div class="rpt_feature rpt_feature_0-3"><a href="javascript:;" class="rpt_tooltip"><span class="intool"><b></b>Add CSS of your own to the sticky menu</span>CSS style<span class="rpt_tooltip_plus" > +</span></a></div>
									<div class="rpt_feature rpt_feature_0-4"><a href="javascript:;" class="rpt_tooltip"><span class="intool"><b></b>Show/hide the sticky menu on specific pages</span>Page targeting<span class="rpt_tooltip_plus" > +</span></a></div>
									<div class="rpt_feature rpt_feature_0-5"><a href="javascript:;" class="rpt_tooltip"><span class="intool"><b></b>Fade/Slide, opacity, background color, transition time and more</span>Effects and more<span class="rpt_tooltip_plus" > +</span></a></div>
									<div class="rpt_feature rpt_feature_0-6"><a href="javascript:;" class="rpt_tooltip"><span class="intool"><b></b>Including page targeting, delay and scroll triggers, devices, position, height, expiry date, open link in a new tab and remove credit</span>Welcome bar<span class="rpt_tooltip_plus"> +</span></a></div>
									<div class="rpt_feature rpt_feature_0-9">
										<select data-key="0" class="multiple-options">
											<option data-header="Renewals for 25% off" data-price="19" value="<?php echo esc_url($pro_url."1") ?>">
												<?php esc_html_e("Updates & support for 1 year") ?>
											</option>
											<option data-header="For 3 years" data-price="35" value="<?php echo esc_url($pro_url."4") ?>">
												<?php esc_html_e("Updates & support for 3 years") ?>
											</option>
											<option data-header="For lifetime" data-price="59" value="<?php echo esc_url($pro_url."5") ?>">
												<?php esc_html_e("Updates & support for lifetime") ?>
											</option>
										</select>
									</div>
								</div>
								<div style="clear:both;"></div>
								<a target="_blank" href="https://go.premio.io/?edd_action=add_to_cart&amp;download_id=2199&amp;edd_options[price_id]=1" class="rpt_foot rpt_foot_0">Buy now</a>
							</div>
							<div class="rpt_plan  rpt_plan_1 rpt_recommended_plan ">
								<div style="text-align:left;" class="rpt_title rpt_title_1">Plus<img class="rpt_recommended" src="<?php echo plugins_url("") ?>/mystickymenu/images/rpt_recommended.png" style="top: 27px;"></div>
								<div class="rpt_head rpt_head_1">
									<div class="rpt_recurrence rpt_recurrence_1">For businesses with multiple websites</div>
									<div class="rpt_price rpt_price_1">$39</div>
									<div class="rpt_description rpt_description_1 rpt_desc">Per year. Renewals for 25% off</div>
									<div style="clear:both;"></div>
								</div>
								<div class="rpt_features rpt_features_1">
									<div class="rpt_feature rpt_feature_1-0"><a href="javascript:;" class="rpt_tooltip"><span class="intool"><b></b>Use myStickymenu on 5 domains</span>5 websites<span class="rpt_tooltip_plus" > +</span></a></div>
									<div class="rpt_feature rpt_feature_1-1"><a href="javascript:;" class="rpt_tooltip"><span class="intool"><b></b>You can show the menu when scrolling up, down or both</span>Show on scroll up/down<span class="rpt_tooltip_plus" > +</span></a></div>
									<div class="rpt_feature rpt_feature_1-2"><a href="javascript:;" class="rpt_tooltip"><span class="intool"><b></b>You can disable the sticky effect on desktop or mobile</span>Devices<span class="rpt_tooltip_plus" > +</span></a></div>
									<div class="rpt_feature rpt_feature_1-3"><a href="javascript:;" class="rpt_tooltip"><span class="intool"><b></b>Add CSS of your own to the sticky menu</span>CSS style<span class="rpt_tooltip_plus" > +</span></a></div>
									<div class="rpt_feature rpt_feature_1-4"><a href="javascript:;" class="rpt_tooltip"><span class="intool"><b></b>Show/hide the sticky menu on specific pages</span>Page targeting<span class="rpt_tooltip_plus" > +</span></a></div>
									<div class="rpt_feature rpt_feature_1-5"><a href="javascript:;" class="rpt_tooltip"><span class="intool"><b></b>Fade/Slide, opacity, background color, transition time and more</span>Effects and more<span class="rpt_tooltip_plus" > +</span></a></div>
									<div class="rpt_feature rpt_feature_1-6"><a href="javascript:;" class="rpt_tooltip"><span class="intool"><b></b>Including page targeting, delay and scroll triggers, devices, position, height, expiry date, open link in a new tab and remove credit</span>Welcome bar<span class="rpt_tooltip_plus"> +</span></a></div>
									<div class="rpt_feature rpt_feature_0-9">
										<select data-key="0" class="multiple-options">
											<option data-header="Renewals for 25% off" data-price="39" value="<?php echo esc_url($pro_url."2") ?>">
												<?php esc_html_e("Updates & support for 1 year") ?>
											</option>
											<option data-header="For 3 years" data-price="65" value="<?php echo esc_url($pro_url."6") ?>">
												<?php esc_html_e("Updates & support for 3 years") ?>
											</option>
											<option data-header="For lifetime" data-price="99" value="<?php echo esc_url($pro_url."7") ?>">
												<?php esc_html_e("Updates & support for lifetime") ?>
											</option>
										</select>
									</div>
								</div>
								<div style="clear:both;"></div>
								<a target="_blank" href="https://go.premio.io/?edd_action=add_to_cart&amp;download_id=2199&amp;edd_options[price_id]=2" class="rpt_foot rpt_foot_1">Buy now</a>
							</div>
							<div class="rpt_plan  rpt_plan_2  ">
								<div style="text-align:left;" class="rpt_title rpt_title_2">Agency</div>
								<div class="rpt_head rpt_head_2">
									<div class="rpt_recurrence rpt_recurrence_2">For agencies who manage clients</div>
									<div class="rpt_price rpt_price_2">$79</div>
									<div class="rpt_description rpt_description_2 rpt_desc">Per year. Renewals for 25% off</div>
									<div style="clear:both;"></div>
								</div>
								<div class="rpt_features rpt_features_2">
									<div class="rpt_feature rpt_feature_2-0"><a href="javascript:;" class="rpt_tooltip"><span class="intool"><b></b>Use myStickymenu on 50 domains</span>50 websites<span class="rpt_tooltip_plus" > +</span></a></div>
									<div class="rpt_feature rpt_feature_2-1"><a href="javascript:;" class="rpt_tooltip"><span class="intool"><b></b>You can show the menu when scrolling up, down or both</span>Show on scroll up/down<span class="rpt_tooltip_plus" > +</span></a></div>
									<div class="rpt_feature rpt_feature_2-2"><a href="javascript:;" class="rpt_tooltip"><span class="intool"><b></b>You can disable the sticky effect on desktop or mobile</span>Devices<span class="rpt_tooltip_plus" > +</span></a></div>
									<div class="rpt_feature rpt_feature_2-3"><a href="javascript:;" class="rpt_tooltip"><span class="intool"><b></b>Add CSS of your own to the sticky menu</span>CSS style<span class="rpt_tooltip_plus" > +</span></a></div>
									<div class="rpt_feature rpt_feature_2-4"><a href="javascript:;" class="rpt_tooltip"><span class="intool"><b></b>Show/hide the sticky menu on specific pages</span>Page targeting<span class="rpt_tooltip_plus" > +</span></a></div>
									<div class="rpt_feature rpt_feature_2-5"><a href="javascript:;" class="rpt_tooltip"><span class="intool"><b></b>Fade/Slide, opacity, background color, transition time and more</span>Effects and more<span class="rpt_tooltip_plus" > +</span></a></div>
									<div class="rpt_feature rpt_feature_2-6"><a href="javascript:;" class="rpt_tooltip"><span class="intool"><b></b>Including page targeting, delay and scroll triggers, devices, position, height, expiry date, open link in a new tab and remove credit</span>Welcome bar<span class="rpt_tooltip_plus"> +</span></a></div>
									<div class="rpt_feature rpt_feature_0-9">
										<select data-key="0" class="multiple-options">
											<option data-header="Renewals for 25% off" data-price="79" value="<?php echo esc_url($pro_url."3") ?>">
												<?php esc_html_e("Updates & support for 1 year") ?>
											</option>
											<option data-header="For 3 years" data-price="139" value="<?php echo esc_url($pro_url."8") ?>">
												<?php esc_html_e("Updates & support for 3 years") ?>
											</option>
											<option data-header="For lifetime" data-price="199" value="<?php echo esc_url($pro_url."9") ?>">
												<?php esc_html_e("Updates & support for lifetime") ?>
											</option>
										</select>
									</div>
								</div>
								<div style="clear:both;"></div>
								<a target="_blank" href="https://go.premio.io/?edd_action=add_to_cart&amp;download_id=2199&amp;edd_options[price_id]=3" class="rpt_foot rpt_foot_2">Buy now</a>
							</div>
						</div>
						<div style="clear:both;"></div>
						<div class="client-testimonial">
							<p class="text-center"><span class="dashicons dashicons-yes"></span> 30 days money back guaranteed</p>
							<p class="text-center"><span class="dashicons dashicons-yes"></span> The plugin will always keep working even if you don't renew your license</p>
							<div class="payment">
								<img src="<?php echo plugins_url("") ?>/mystickymenu/images/payment.png" alt="Payment" class="payment-img" />
							</div>
							<div class="testimonial-box">
								<div class="testimonial-image">
									<img src="<?php echo plugins_url("") ?>/mystickymenu/images/testimonial.png" style="top: 27px;">
								</div>
								<div class="testimonial-content">
									This plugin does exactly what it should. It is simple but powerful. I would suggest to anyone who wants to make their menu sticky! I especially love the hide header on scroll down, show on scroll up feature that is built it. Great work!
									<div class="author">Clayton Chase</div>
								</div>
								<div style="clear:both;"></div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php
	}
		
	public function mysticky_default_options() {

		global $options;
		$menu_locations = get_nav_menu_locations();		
		$menu_object = isset($menu_locations['menu-1']) ? wp_get_nav_menu_object( $menu_locations['menu-1'] ) : array();
		
		if ( is_object($menu_object) && $menu_object->slug != '' ) {
			$mysticky_class_id_selector = $menu_object->slug;
		} else {
			$mysticky_class_id_selector = 'custom';
		}
		
		$mystickyClass = '.navbar';		
		$template_name = get_template();
		switch( $template_name ){
			case 'ashe':
				$mysticky_class_id_selector = 'custom';
				$mystickyClass = '#main-nav';
				break;
			case 'astra':
			case 'hello-elementor':
			case 'sydney':
			case 'twentysixteen':
				$mysticky_class_id_selector = 'custom';
				$mystickyClass = 'header.site-header';
				break;
			case 'generatepress':
				$mysticky_class_id_selector = 'custom';
				$mystickyClass = 'nav.main-navigation';
				break;
			case 'transportex':
				$mysticky_class_id_selector = 'custom';
				$mystickyClass = '.transportex-menu-full';
				break;
			case 'hestia':
			case 'neve':	
				$mysticky_class_id_selector = 'custom';
				$mystickyClass = 'header.header';
				break;
			case 'mesmerize':
				$mysticky_class_id_selector = 'custom';
				$mystickyClass = '.navigation-bar';
				break;
			case 'oceanwp':
				$mysticky_class_id_selector = 'custom';
				$mystickyClass = 'header#site-header';
				break;
			case 'shapely':
				$mysticky_class_id_selector = 'custom';
				$mystickyClass = '#site-navigation';
				break;
			case 'storefront':
				$mysticky_class_id_selector = 'custom';
				$mystickyClass = '.storefront-primary-navigation';
				break;
			case 'twentynineteen':
				$mysticky_class_id_selector = 'custom';
				$mystickyClass = '#site-navigation';
				break;				
			case 'twentyseventeen':
				$mysticky_class_id_selector = 'custom';
				$mystickyClass = '.navigation-top';
				break;
			default:
				break;
		}
		
		
		$default = array(
				'mysticky_class_id_selector'	=> $mysticky_class_id_selector,
				'mysticky_class_selector' 		=> $mystickyClass,
				'device_desktop' 				=> 'on',
				'device_mobile' 				=> 'on',
				'myfixed_zindex' 				=> '99990',
				'myfixed_bgcolor' 				=> '#f7f5e7',
				'myfixed_opacity' 				=> '90',
				'myfixed_transition_time' 		=> '0.3',
				'myfixed_disable_small_screen' 	=> '0',
				'myfixed_disable_large_screen' 	=> '0',
				'mysticky_active_on_height' 	=> '0',
				'mysticky_active_on_height_home'=> '0',
				'myfixed_fade' 					=> 'slide',
				'myfixed_cssstyle' 				=> '#mysticky-nav .myfixed { margin:0 auto; float:none; border:0px; background:none; max-width:100%; }'
			);

		if ( get_option('mysticky_option_name') == false ) {
			$status = get_option("sticky_header_status");
			if($status == false) {
				update_option("sticky_header_status", "done");
				update_option("has_sticky_header_old_version", "no");
			}
			update_option( 'mysticky_option_name', $default );
		} else {
			$status = get_option("sticky_header_status");
			if($status == false) {
				update_option("sticky_header_status", "done");
				update_option("has_sticky_header_old_version", "yes");
			}
		}

		if(isset($_POST['reset_mysticky_options'])) {
			if(isset($_REQUEST['nonce']) && !empty($_REQUEST['nonce'])  && wp_verify_nonce($_REQUEST['nonce'], 'mysticky_option_backend_reset_nonce')) {
				update_option('mysticky_option_name', $default);
			} else {

			}
		}
		
		if ( !get_option( 'update_mysticky_version_2_6') ) {
			$mysticky_option_name = get_option( 'mysticky_option_name' );
			$mysticky_option_name['mysticky_class_id_selector'] = 'custom';
			if ($mysticky_option_name['myfixed_fade'] == 'on'){
				$mysticky_option_name['myfixed_fade'] = 'slide';
			}else{
				$mysticky_option_name['myfixed_fade'] = 'fade';
			}
			update_option( 'mysticky_option_name', $mysticky_option_name );
			update_option( 'update_mysticky_version_2_6', true );
		}
	}
}



class MyStickyMenuFrontend
{

	public function __construct()
	{
		add_action( 'wp_head', array( $this, 'mysticky_build_stylesheet_content' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'mysticky_disable_at' ) );
	}

	public function mysticky_build_stylesheet_content() {

		$mysticky_options = get_option( 'mysticky_option_name' );

		if (isset($mysticky_options['disable_css'])) {
			//do nothing
		} else {
			$mysticky_options['disable_css'] = false;
		}

		if  ($mysticky_options ['disable_css'] == false ) {

			echo '<style id="mystickymenu" type="text/css">';
			echo '#mysticky-nav { width:100%; position: static; }';
			echo '#mysticky-nav.wrapfixed { position:fixed; left: 0px; margin-top:0px;  z-index: '. $mysticky_options ['myfixed_zindex'] .'; -webkit-transition: ' . $mysticky_options ['myfixed_transition_time'] . 's; -moz-transition: ' . $mysticky_options ['myfixed_transition_time'] . 's; -o-transition: ' . $mysticky_options ['myfixed_transition_time'] . 's; transition: ' . $mysticky_options ['myfixed_transition_time'] . 's; -ms-filter:"progid:DXImageTransform.Microsoft.Alpha(Opacity=' . $mysticky_options ['myfixed_opacity'] . ')"; filter: alpha(opacity=' . $mysticky_options ['myfixed_opacity'] . '); opacity:' . $mysticky_options ['myfixed_opacity'] / 100 . '; background-color: ' . $mysticky_options ['myfixed_bgcolor'] . ';}';


			if  ($mysticky_options ['myfixed_disable_small_screen'] > 0 ){
			//echo '@media (max-width: '.$mysticky_options['myfixed_disable_small_screen'].'px) {#mysticky-nav.wrapfixed {position: static;} }';
			};
			if ( !isset( $mysticky_options['myfixed_cssstyle'] ) )  {
				echo '#mysticky-nav .myfixed { margin:0 auto; float:none; border:0px; background:none; max-width:100%; }';
			}
			if ( isset( $mysticky_options['myfixed_cssstyle'] ) && $mysticky_options['myfixed_cssstyle'] != '' )  {
				echo $mysticky_options ['myfixed_cssstyle'];
			}
			echo '</style>';
			$template_name = get_template();
			?>
			<style type="text/css">
				<?php if( $template_name == 'hestia' ) { ?>
					#mysticky-nav.wrapfixed {box-shadow: 0 1px 10px -6px #0000006b,0 1px 10px 0 #0000001f,0 4px 5px -2px #0000001a;}
					#mysticky-nav.wrapfixed .navbar {position: relative;background-color: transparent;box-shadow: none;}
				<?php } ?>
				<?php if( $template_name == 'shapely' ) { ?>
					#mysticky-nav.wrapfixed #site-navigation {position: relative;}
				<?php } ?>
				<?php if( $template_name == 'storefront' ) { ?>
					#mysticky-nav.wrapfixed > .site-header {margin-bottom: 0;}
					#mysticky-nav.wrapfixed > .storefront-primary-navigation {padding: 10px 0;}
				<?php } ?>
				<?php if( $template_name == 'transportex' ) { ?>
					#mysticky-nav.wrapfixed > .transportex-menu-full {margin: 0 auto;}
					.transportex-headwidget #mysticky-nav.wrapfixed .navbar-wp {top: 0;}
				<?php } ?>
				<?php if( $template_name == 'twentynineteen' ) { ?>
					#mysticky-nav.wrapfixed {padding: 10px;}
				<?php } ?>
				<?php if( $template_name == 'twentysixteen' ) { ?>
					#mysticky-nav.wrapfixed > .site-header {padding-top: 0;padding-bottom: 0;}
				<?php } ?>
				<?php if( $template_name == 'twentytwenty' ) { ?>
					#site-header {background: transparent;}
				<?php } ?>
			</style>
			<?php
		}
	}
	
	public function mystickymenu_google_fonts_url() {
		$welcomebar = get_option( 'mysticky_option_welcomebar' );
		
		$default_fonts = array('Arial', 'Tahoma', 'Verdana', 'Helvetica', 'Times New Roman', 'Trebuchet MS', 'Georgia' );
		$fonts_url        = '';
		$fonts            = array();
		$font_args        = array();
		$base_url         =  "https://fonts.googleapis.com/css";		
		$fonts['family']['Lato'] = 'Lato:400,500,600,700';
		if ( isset($welcomebar['mysticky_welcomebar_font']) && $welcomebar['mysticky_welcomebar_font'] !='' && !in_array( $welcomebar['mysticky_welcomebar_font'], $default_fonts) ) {
			$fonts['family'][$welcomebar['mysticky_welcomebar_font']] = $welcomebar['mysticky_welcomebar_font'] . ':400,500,600,700';
		}
		if ( isset($welcomebar['mysticky_welcomebar_btnfont']) && $welcomebar['mysticky_welcomebar_btnfont'] !='' && !in_array( $welcomebar['mysticky_welcomebar_btnfont'], $default_fonts) ) {
			$fonts['family'][$welcomebar['mysticky_welcomebar_btnfont']] = $welcomebar['mysticky_welcomebar_btnfont'] . ':400,500,600,700';
		}
		
		/* Prepapre URL if font family defined. */
		if( !empty( $fonts['family'] ) ) {

			/* format family to string */
			if( is_array($fonts['family']) ){
				$fonts['family'] = implode( '|', $fonts['family'] );
			}

			$font_args['family'] = urlencode( trim( $fonts['family'] ) );

			if( !empty( $fonts['subsets'] ) ){

				/* format subsets to string */
				if( is_array( $fonts['subsets'] ) ){
					$fonts['subsets'] = implode( ',', $fonts['subsets'] );
				}

				$font_args['subsets'] = urlencode( trim( $fonts['subsets'] ) );
			}

			$fonts_url = add_query_arg( $font_args, $base_url );
		}
		
		return esc_url_raw( $fonts_url );
	}

	public function mystickymenu_script() {

		$mysticky_options = get_option( 'mysticky_option_name' );

		if ( is_admin_bar_showing() ) {
			$top = "true";
		} else {
			$top = "false";
		}
		
		$welcomebar = get_option( 'mysticky_option_welcomebar' );		
		if ( isset($welcomebar['mysticky_welcomebar_enable']) && $welcomebar['mysticky_welcomebar_enable'] == 1 ) {
			wp_enqueue_style('google-fonts', $this->mystickymenu_google_fonts_url(),array(), MYSTICKY_VERSION );
		}

		// needed for update 1.7 => 1.8 ... will be removed in the future ()
		if (isset($mysticky_options['mysticky_active_on_height_home'])) {
			//do nothing
		} else {
			$mysticky_options['mysticky_active_on_height_home'] = $mysticky_options['mysticky_active_on_height'];
		}


		if  ($mysticky_options['mysticky_active_on_height_home'] == 0 ) {
			$mysticky_options['mysticky_active_on_height_home'] = $mysticky_options['mysticky_active_on_height'];
		}


		if ( is_front_page() && is_home() ) {

			$mysticky_options['mysticky_active_on_height'] = $mysticky_options['mysticky_active_on_height_home'];

		} elseif ( is_front_page()){

			$mysticky_options['mysticky_active_on_height'] = $mysticky_options['mysticky_active_on_height_home'];

		}
		wp_register_script('detectmobilebrowser', plugins_url( 'js/detectmobilebrowser.js', __FILE__ ), array('jquery'), MYSTICKY_VERSION, true);
		wp_enqueue_script( 'detectmobilebrowser' );
		
		wp_register_script('mystickymenu', plugins_url( 'js/mystickymenu.min.js', __FILE__ ), array('jquery'), MYSTICKY_VERSION, true);
		wp_enqueue_script( 'mystickymenu' );

		$myfixed_disable_scroll_down = isset($mysticky_options['myfixed_disable_scroll_down']) ? $mysticky_options['myfixed_disable_scroll_down'] : 'false';
		$mystickyTransition = isset($mysticky_options['myfixed_fade']) ? $mysticky_options['myfixed_fade'] : 'fade';
		$mystickyDisableLarge = isset($mysticky_options['myfixed_disable_large_screen']) ? $mysticky_options['myfixed_disable_large_screen'] : '0';

		$mystickyClass = ( $mysticky_options['mysticky_class_id_selector'] != 'custom') ? '.menu-' . $mysticky_options['mysticky_class_id_selector'] .'-container' : $mysticky_options['mysticky_class_selector'];
		
		if ( $mysticky_options['mysticky_class_id_selector'] != 'custom' ) {
			$template_name = get_template();
			switch( $template_name ){
				case 'ashe':
					$mystickyClass = '#main-nav';
					break;
				case 'astra':
				case 'hello-elementor':
				case 'sydney':
				case 'twentysixteen':
					$mystickyClass = 'header.site-header';
					break;
				case 'generatepress':
					$mystickyClass = 'nav.main-navigation';
					break;
				case 'transportex':
					$mystickyClass = '.transportex-menu-full';
					break;
				case 'hestia':
				case 'neve':				
					$mystickyClass = 'header.header';
					break;
				case 'mesmerize':
					$mystickyClass = '.navigation-bar';
					break;
				case 'oceanwp':
					$mystickyClass = 'header#site-header';
					break;
				case 'shapely':
					$mystickyClass = '#site-navigation';
					break;
				case 'storefront':
					$mystickyClass = '.storefront-primary-navigation';
					break;
				case 'twentynineteen':
					$mystickyClass = '#site-navigation';
					break;				
				case 'twentyseventeen':
					$mystickyClass = '.navigation-top';
					break;
				default:
					break;
			}
		}
		

		$mysticky_translation_array = array(
		    'mystickyClass' 			=> $mystickyClass,
			'activationHeight' 			=> $mysticky_options['mysticky_active_on_height'],
			'disableWidth' 				=> $mysticky_options['myfixed_disable_small_screen'],
			'disableLargeWidth' 		=> $mystickyDisableLarge,
			'adminBar' 					=> $top,
			'device_desktop'			=> true,
			'device_mobile' 			=> true,
			'mystickyTransition' 		=> $mystickyTransition,
			'mysticky_disable_down' 	=> $myfixed_disable_scroll_down,


		);
		wp_localize_script( 'mystickymenu', 'option', $mysticky_translation_array );		
	}

	public function mysticky_disable_at() {


		$mysticky_options = get_option( 'mysticky_option_name' );

		$mysticky_disable_at_front_home = isset($mysticky_options['mysticky_disable_at_front_home']);
		$mysticky_disable_at_blog = isset($mysticky_options['mysticky_disable_at_blog']);
		$mysticky_disable_at_page = isset($mysticky_options['mysticky_disable_at_page']);
		$mysticky_disable_at_tag = isset($mysticky_options['mysticky_disable_at_tag']);
		$mysticky_disable_at_category = isset($mysticky_options['mysticky_disable_at_category']);
		$mysticky_disable_at_single = isset($mysticky_options['mysticky_disable_at_single']);
		$mysticky_disable_at_archive = isset($mysticky_options['mysticky_disable_at_archive']);
		$mysticky_disable_at_search = isset($mysticky_options['mysticky_disable_at_search']);
		$mysticky_disable_at_404 = isset($mysticky_options['mysticky_disable_at_404']);
		$mysticky_enable_at_pages = isset($mysticky_options['mysticky_enable_at_pages']) ? $mysticky_options['mysticky_enable_at_pages'] : '';
		$mysticky_enable_at_posts = isset($mysticky_options['mysticky_enable_at_posts']) ? $mysticky_options['mysticky_enable_at_posts'] : '';

		// Trim input to ignore empty spaces
		$mysticky_enable_at_pages_exp = array_map('trim', explode(',', $mysticky_enable_at_pages));
		$mysticky_enable_at_posts_exp = array_map('trim', explode(',', $mysticky_enable_at_posts));




		if ( is_front_page() && is_home() ) { /* Default homepage */

			if ( $mysticky_disable_at_front_home == false ) {
				$this->mystickymenu_script();
			}
		} elseif ( is_front_page()){ /* Static homepage */

			if ( $mysticky_disable_at_front_home == false ) {
				$this->mystickymenu_script();
			}

		} elseif ( is_home()){ /* Blog page */

			if ( $mysticky_disable_at_blog == false ) {
				$this->mystickymenu_script();
			}

		} elseif ( is_page() ){ /* Single page*/

			if ( $mysticky_disable_at_page == false ) {
				$this->mystickymenu_script();
			}
			if ( is_page( $mysticky_enable_at_pages_exp  )  ){
				$this->mystickymenu_script();
			}

		} elseif ( is_tag()){ /* Tag page */

			if ( $mysticky_disable_at_tag == false ) {
				$this->mystickymenu_script();
			}

		} elseif ( is_category()){ /* Category page */

			if ( $mysticky_disable_at_category == false ) {
				$this->mystickymenu_script();
			}

		} elseif ( is_single()){ /* Single post */

			if ( $mysticky_disable_at_single == false ) {
				$this->mystickymenu_script();
			}

			if ( is_single( $mysticky_enable_at_posts_exp  )  ){
				$this->mystickymenu_script();
			}

		} elseif ( is_archive()){ /* Archive */

			if ( $mysticky_disable_at_archive == false ) {
				$this->mystickymenu_script();
			}

		} elseif ( is_search()){ /* Search */

			if ( $mysticky_disable_at_search == false ) {
				$this->mystickymenu_script();
			}

		} elseif ( is_404()){ /* 404 */

			if ( $mysticky_disable_at_404 == false ) {
				$this->mystickymenu_script();
			}
		}

	}

}

if( is_admin() ) {
	new MyStickyMenuBackend();
	require_once 'mystickymenu-affiliate.php';
	
} else {
	new MyStickyMenuFrontend();
}