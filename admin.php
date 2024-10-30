<?php

// admin specific stuff goes here


// stores idxpro account info (if there is a registered account)
$idxpro_account;
idxpro_get_account();
$idxpro_account_id = idxpro_get_id();


// class for adding custom tinymce button
include_once dirname( __FILE__ ) . "/tinymce/tinymce.php";



function idxpro_admin_init() {
    global $wp_version;
	
    // require a minimum wordpress version
    if ( ! function_exists('is_multisite') && version_compare( $wp_version, '3.0', '<' ) ) {
        
        function idxpro_version_warning() {
            echo "
            <div id='idxpro-warning' class='updated fade'><p><strong>".sprintf(__('IDXPro %s requires WordPress 3.0 or higher.'), IDXPRO_PLUGIN_VERSION) ."</strong> ".sprintf(__('Please <a href="%s">upgrade WordPress</a> to the most current version'), 'http://codex.wordpress.org/Upgrading_WordPress'). "</p></div>
            ";
        }
        add_action('admin_notices', 'idxpro_version_warning');
        
        return; 
    }
}
add_action('admin_init', 'idxpro_admin_init');






function idxpro_config_page() {
	global $idxpro_account;
	
	// sets up a config page where they can input their acnt
	$idxpro_config_page = add_plugins_page(__('IDXPro Configuration'), __('IDXPro Configuration'), 'manage_options', 'idxpro-config', 'idxpro_conf');
	
	// only load our custom styles and scripts on this new config page (don't add to all admin pages)
	add_action("admin_print_styles-$idxpro_config_page", 'idxpro_admin_styles');
	add_action("admin_print_scripts-$idxpro_config_page", 'idxpro_admin_scripts');
	
}
add_action( 'admin_menu', 'idxpro_config_page' );


function idxpro_admin_styles() {
	wp_register_style('idxpro.css', IDXPRO_PLUGIN_URL . 'idxpro.css');
	wp_enqueue_style('idxpro.css');
}


function idxpro_admin_scripts() {
	wp_register_script('idxpro.js', IDXPRO_PLUGIN_URL . 'idxpro.js', array('jquery'));
	wp_enqueue_script('idxpro.js');
}

// writes warning messages if any
idxpro_admin_warnings();


// extra security for our account registration form
$idxpro_nonce = 'idxpro-register-account';


// after you Activate the plugin, this adds the "Settings" link to the list of action links for the plugin
// eg. Deactivate | Edit | Settings
function idxpro_plugin_action_links( $links, $file ) {
	if ( $file == plugin_basename( dirname(__FILE__).'/idxpro.php' ) ) {
		$links[] = '<a href="plugins.php?page=idxpro-config" title="Register your account">'.__('Settings').'</a>';
		
		// also add a 'Buy Now' link if there is a test drive account
		if ( idxpro_is_testdrive() ) {
			$links[] = '<a href="' . idxpro_get_conversion_url() . '" target="_blank" title="Upgrade to the paid version">'.__('Buy Now').'</a>';
		}
	}
	return $links;
}
add_filter( 'plugin_action_links', 'idxpro_plugin_action_links', 10, 2 );



// renders idxpro config page?
function idxpro_conf() {
	global $idxpro_nonce, $idxpro_account, $idxpro_testdrive_days_left, $idxpro_host;
	
	// if we're manually requesting an account sync 
	if ( isset($_REQUEST['sync_account']) )
		idxpro_get_account(1); // pass in a param of true to force a sync of account
	
	
	// **** TEMPORARY FOR DEV USE ONLY!!!
	// deletes the idxpro account field from the wp db
	if ( isset($_REQUEST['delete_account']) )
		delete_option('idxpro_account');
	
	
	
	$idxpro_account_id = idxpro_get_id();
	
	//echo "<pre>df;lkjasf;lasdfj;al</pre>";
	
	
	// on post
	if ( isset($_POST['submit']) ) {
		$message = 'Account saved';
		
		// prevent unauthorized user access
		if ( function_exists('current_user_can') && ! current_user_can('manage_options') )
			die(__('Cheatin&#8217; uh?'));
		
		// check nonce
		check_admin_referer( $idxpro_nonce );
		
		// first check that we have a value
		if ( $_POST['idxpro_account_id']) {
			$idxpro_account_id = $_POST['idxpro_account_id'];
			
			// do a bit more format validation before we do a real lookup
			if ( preg_match('/^AR[0-9]{5,}[a-z]?$/i', $idxpro_account_id) != 0 ) {
				
				// does a lookup on the AR number to see if the account is legit. If so, it saves to db.
				// if not, it returns an error string (returns false if successful)
				$error = idxpro_sync_account($idxpro_account_id);
				if ( $error ) $message = $error;
				
				
				// use only for testing to remove wp db field
				//delete_option('idxpro_account');
				
				
			} else { // invalid format
				$message = 'Invalid account number format. Your account number starts with the letters "AR" and is followed by numbers.';
			}
			
		}
		// there is no value for account number
		else {
			$message = 'Please enter your IDXPro Account Number';
		}
	} // end if post

?>

<?php if ( ! empty($_POST['submit'] ) ) : ?>
<div id="message" class="updated fade"><p><strong><?php _e($message) ?></strong></p></div>
<?php endif; ?>

	
<div class="wrap">

	<div id="icon-plugins" class="icon32"><br /></div>
	<h2><?php _e('IDXPro Configuration'); ?></h2>
	
	
	<div id="poststuff" class="metabox-holder has-right-sidebar">
		
		<div id="side-info-column" class="inner-sidebar">
			
			<div class="postbox">
				<h3>Questions?</h3>
				<div class="inside">
					<p>
						Contact Us Toll Free at <strong>1-866-645-7702</strong> or 
						<a href="<?php echo idxpro_get_contact_url(); ?>" target="_blank">send us an email</a>
					</p>
					<p>
						More information is available at <a href="<?php echo idxpro_get_product_url(); ?>" target="_blank">http://www.idxpro.com</a>
					</p>
					<p>
						Copyright &copy; <?php echo date('Y'); ?> - iHOUSEweb, Inc.<br />
						<a href="<?php echo idxpro_get_company_url(); ?>" target="_blank">http://www.ihouseweb.com</a> 
					</p>
				</div>
			</div>
			
		</div><!-- #side-info-column .inner-sidebar -->
		
		<div id="post-body">
			<div id="post-body-content">
				
				<?php if ( ! idxpro_has_account() ) { // if there is NOT an account ?>
					
					<div class="stuffbox">
						<h3>1. Sign Up for an Account</h3>
						<div class="inside">
							<p>
								If you don&rsquo;t have an IDXPro account yet, you&rsquo;ll need one for this plugin to work.
								Once you&rsquo;ve signed up, check your email for your IDXPro Account Number and register it below.
							</p>
							<p>
								<a class="button" href="<?php echo idxpro_get_testdrive_url(); ?>" target="_blank">Create a FREE Test Drive</a> 
								<a class="button" href="<?php echo idxpro_get_signup_url(); ?>" target="_blank">Buy IDXPro Now</a>
							</p>
							<p class="disclaimer">
								Test drives are completely free, no obligation and last for 14 days. 
								Test drive data has been fabricated and is for presentation purposes only.
								Real data will be displayed after you purchase IDXPro and are approved by your MLS.
							</p>
						</div><!-- .inside -->
					</div><!-- .stuffbox -->
					
				<?php } // end if there is NOT an account ?>
				
					
					<div class="stuffbox">
						<h3><?php echo ( ! idxpro_has_account() ) ? '2. Register Your Account' : 'IDXPro Account'; ?></h3>
						<div class="inside">
							
							<?php if ( ! idxpro_has_account() ) { // if there is NOT an account ?>
							
								<p>Enter your account number here. Account numbers start with an 'AR'. example: 'AR123456' </p>
							
							<?php } else { // else there IS an account?>
								<p>
									Your Account Number is <strong><?php echo $idxpro_account['id']; ?></strong>. 
									[ <a href="#" class="idxpro-account-edit">Edit</a> ]
									
																		
								</p>
							<?php } // end there IS an account ?>
							
							
							<form action="" method="post" id="idxpro-conf" style="display:<?php print( ! idxpro_has_account() ) ? 'block' : 'none'; ?>;">
														
							
								<p class="idxpro_admin_block">
									<input id="idxpro_account_id" name="idxpro_account_id" type="text" size="15" value="<?php echo $idxpro_account_id; ?>" style="font-size: 1.5em;" />
									<?php wp_nonce_field($idxpro_nonce) ?>
									<input type="submit" name="submit" class="button" value="<?php _e('Save Account Number'); ?>" />
								</p>
								
							</form>
							
							<?php if ( idxpro_has_account() ) { // if there IS an account ?>
								<p>
									Account Status: <strong><?php echo idxpro_get_status(); ?></strong>
									<?php if ( idxpro_is_testdrive() ) { // if this account is a test drive ?>
										<?php if ( ! idxpro_is_expired() ) { // if test drive has not yet expired ?>
											| expires in <?php echo idxpro_get_days_left(); ?>. 
										<?php } // end if test drive has not expired ?>
										<a class="button" href="<?php echo idxpro_get_conversion_url(); ?>" target="_blank">Buy Now</a>
									<?php } // end if it's a testdrive ?>
									[ <a href="plugins.php?page=idxpro-config&sync_account=1">Refresh Status</a> ]
								</p>
								
								<?php if ( idxpro_is_testdrive() ) { // if account status is test drive ?>
									
									<p class="disclaimer">
										<?php if ( idxpro_is_expired() ) { // if test drive is expired ?>
											
											Your IDXPro test drive account has <strong>expired</strong>! 
											Please <a href='<?php echo idxpro_get_conversion_url() ?>' target='_blank'>buy now</a> or
											<a href='<?php echo idxpro_get_contact_url() ?>' target='_blank'>contact us</a> to get a test drive extension.
											
										<?php } else { // if test drive is NOT expired yet ?>
											
											Test drives are completely free, no obligation and last for 14 days. 
											Test drive data has been fabricated and is for presentation purposes only.
											Real data will be displayed after you purchase IDXPro and are approved by your MLS.
											
										<?php } // end if test drive is NOT expired ?>
									</p>
									
								
								<?php } else if ( idxpro_is_pending() ) { // else if account status is pending ?>
								
									<p class="disclaimer">
										Your account has been activated. 
										However, real MLS data will not be shown until you have been approved by your MLS.
										A Customer Service Representative will contact you soon to walk you through any required paperwork.
									</p>
									
								<?php } // end if status is pending?>
								
							<?php } // end if there IS an account ?>
							
						</div><!-- .inside -->
					</div><!-- .stuffbox -->
					
					
				<?php if ( idxpro_has_account() ) { // if there is an account ?>
					<?php if ( idxpro_is_viewable() ) { // if account is NOT expired, suspended, or canceled ?>
				
						<div class="stuffbox">
							<h3>IDXPro Admin Menu | <a href="<?php echo idxpro_get_admin_menu_url(); ?>" target="_blank">Go to Admin Menu &raquo;</a></h3>
							<div class="inside">
								<p>
									Your <a href="<?php echo idxpro_get_admin_menu_url(); ?>" target="_blank">IDXPro Admin Menu</a> is where you can manage your leads,
									edit your IDX Search pages, change settings, and more.
								</p>
							</div>
						</div>
						
						<div class="stuffbox">
							<h3>IDX Search Page | <a href="<?php echo idxpro_get_site_url(); ?>" target="_blank">Go to Search Page &raquo;</a></h3>
							<div class="inside">
								<p>
									Here is your IDX Search link: <a href="<?php echo idxpro_get_site_url(); ?>" target="_blank"><?php echo idxpro_get_site_url(); ?></a>.<br />
									It is best used to open IDXPro in a new window.
								</p>
								<p>
									As you edit your posts and pages in WordPress, there is now an IDXPro menu buttons in your editor window. You can now select between either the IDXPro application or the IDXPro Quick Search widget.
								</p>
								<p>
									<img src="<?php echo IDXPRO_PLUGIN_URL; ?>/screenshot-1.png" alt="new idxpro button for tinymce editor" />
								</p>
								<p>
									Place your cursor where you want your IDXPro application or IDXPro quick search widget to appear on your page, then press one of the available options.
									It will insert a WordPress shortcode. When you view this page or post, your IDX Search will be framed in
									seamlessly, expanding or contracting to fit the available space.
								</p>
							</div>
						</div>
						
						<div class="stuffbox">
							<h3>IDX Search Widget</h3>
							<div class="inside">
								<p>
									You also have a new &ldquo;IDX Search&rdquo; widget available.
									You can manage it from <a href="widgets.php">Appearance &raquo; Widgets</a> just like any other standard WordPress widget.
									The IDX Search widget will open your search results in a new window.<br />
									<em>Note that your WordPress Theme will need to support widgets.</em>
								</p>
							</div>
						</div>
				
					<?php } // end if account is NOT expired, suspended, or canceled ?>
				
					
				<?php } // end if there is an account ?>
				
				
			</div><!-- #post-body-content -->
		</div><!-- #post-body -->
		
	</div><!-- #poststuff .meta-holder .has-right-sidebar -->
		
		
</div>
<div class="clear"></div>
<?php
}

//
function idxpro_admin_warnings() {
	global $idxpro_account, $idxpro_account_id, $idxpro_testdrive_days_left, $idxpro_conversion_url;
	
	// check for any warnings and write them to admin menu. 
	function idxpro_warning() {
		global $idxpro_account, $idxpro_account_id, $idxpro_testdrive_days_left, $idxpro_conversion_url;
		
		// * * * * * * *
		// TODO: limit these warnings to only certain pages in the Admin Menu??
		// * * * * * * *
		
		// these warnings are suppressed when posting a form - which may have it's own confirmation/validation messages that appear instead
		if ( ! isset($_POST['submit']) ) {
			
			// if there's not an account registered yet
			if ( ! idxpro_has_account() ) {
				echo "
					<div id='idxpro-warning' class='updated fade'>
						<p><strong>IDXPro is almost ready.</strong> You must <a href='plugins.php?page=idxpro-config'>register your IDXPro account number</a> for it to work.</p>
					</div>
				";
			}
			
			// if there are 7 days or less left before test drive expires
			if ( idxpro_has_account() && idxpro_is_testdrive() && ! idxpro_is_expired() && idxpro_get_days_left(1) <= 7 ) {
				echo "
					<div id='idxpro-warning' class='updated fade'>
						<p>Your IDXPro test drive account expires in " . idxpro_get_days_left() . ". <a href='" . idxpro_get_conversion_url() . "' target='_blank'>Buy Now</a></p>
					</div>
				";
			}
			
			// if there is an expired test drive.
			if ( idxpro_has_account() && idxpro_is_expired() ) {
				echo "
					<div id='idxpro-warning' class='updated fade'>
						<p>
							Your IDXPro test drive account has <strong>expired</strong>! Please <a href='" . idxpro_get_conversion_url() . "' target='_blank'>buy now</a> or
							<a href='" . idxpro_get_contact_url() . "' target='_blank'>contact us</a> to get a test drive extension.
						</p>
					</div>
				";
			}
			
			// if it's suspended or canceled
			if ( idxpro_has_account() && ( idxpro_is_suspended() || idxpro_is_canceled() ) ) {
				echo "
					<div id='idxpro-warning' class='updated fade'>
						<p>
							Your IDXPro account has been <strong>suspended</strong> or <strong>canceled</strong>! 
							Please <a href='" . idxpro_get_contact_url() . "' target='_blank'>contact Customer Service</a> to reactivate it.
						</p>
					</div>
				";
			}
			
		} // end if not posting
		
		return;
		
	}
	add_action('admin_notices', 'idxpro_warning');
	
}


?>