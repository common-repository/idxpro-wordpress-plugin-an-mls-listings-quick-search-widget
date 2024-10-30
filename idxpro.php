<?php 
/**
 * @package IDXPro
 * @since 3.0
 */

/*
Plugin Name: IDXPro
Plugin URI: http://idxpro.com/wordpress
Description: Easily embed your own fully customizable IDX widgets within minutes. 1) Click Activate, 2) <a href="http://idxpro.cisdata.net/Signup/index/?rcode=wordpress_plugin">Sign up for an IDXPro account</a>, and 3) Go to your <a href="plugins.php?page=idxpro-config">IDXPro configuration</a> page, and enter your account number.
Version: 1.2
Author: iHOUSEweb
Author URI: http://ihouseweb.com
License: personal, non-exclusive
*/

/*  
Copyright 2011  iHOUSEweb  (email : sales@ihouseweb.com)

The IDXPro wordpress PLUGIN is free software; you can redistribute it 
but not modify it.
	
The PLUGIN merely acts as a convenience method for embedding our actual 
IDXPro PRODUCT into a wordpress based website.
	
The product IDXPro is licensed on an individual, non-exclusive basis, 
and is subject to the terms of service and conditions of use
found at http://www.idxpro.com/terms.html
*/



define('IDXPRO_PLUGIN_VERSION', '1.2');
define('IDXPRO_PLUGIN_URL', plugin_dir_url( __FILE__ ));
$idxpro_host = 'idxpro.cisdata.net';

// stores idxpro account info (if there is a registered account)
$idxpro_account;
idxpro_get_account();
$idxpro_account_id = idxpro_get_id();


// don't expose any info if called directly
if ( ! function_exists( 'add_action' ) ) {
	echo "I'm just a plugin, not much I can do when called directly.";
	exit;
}

include_once dirname( __FILE__ ) . '/widget.php';

if ( is_admin() )
	require_once dirname( __FILE__ ) . '/admin.php';

/*
// initialize idxpro plugin for all of wordpress
function idxpro_init() {
	
	// do we need to do anything here???
	
}
add_action('init', 'idxpro_init');
*/


// gets idxpro account info either from existing global var (cache) or from db
function idxpro_get_account($sync = false) {
	global $idxpro_account;
	
	// if it hasn't been defined yet, get value from db
	//if ( empty($idxpro_account) ) 
		$idxpro_account = get_option('idxpro_account');
	
	if ( ! empty($idxpro_account) && ! empty($idxpro_account['id']) ) {
	
		$now = time();
		$last_sync = idxpro_convert_date_to_unix( $idxpro_account['last_sync'] );
		$seconds_elapsed = ($now - $last_sync);
		
		// need to sync account periodically to see if it has changed
		switch($idxpro_account['status']) {
			
			// test drive accounts, ok to sync as often as every minute
			// checking for if active now
			case 'T':
			case 'X':
				if ( $now - $last_sync >= 60 ) {
					$sync = 1;
				}
			break;
			
			// pending accounts, only check as often as once an hour
			// checking for if approval has come through
			case 'P':
				if ( $now - $last_sync >= 3600 ) {
					$sync = 1;
				}
			break;
			
			// active accounts, only check as often as once a day
			// checking if it got cancelled, suspended, etc.
			case 'A':
				if ( $now - $last_sync >= 86400 ) {
					$sync = 1;
				}
			break;
		}
		if ( ! empty($sync) )
			idxpro_sync_account( $idxpro_account['id'] );
		
	} // end if there's an account
}

// checks that we have an account (with all required attributes)
// returns boolean
function idxpro_has_account() {
	global $idxpro_account;
	return ( ! empty($idxpro_account) && ! empty($idxpro_account['id']) && ! empty($idxpro_account['status']) && ! empty($idxpro_account['last_sync'])  );
}

// returns an AR number or an empty string
function idxpro_get_id() {
	global $idxpro_account;
	return ( ! empty($idxpro_account) && ! empty($idxpro_account['id']) ) ? $idxpro_account['id'] : '';
}

// does a lookup on the ar number to see if it's legit
// if so, it updates the value of $idxpro_account, setting a new last_sync timestamp
// if not, it returns an error string (returns false if successful)
function idxpro_sync_account($ar_number) {
	global $idxpro_account, $idxpro_host;
	
	$idxpro_account_check = wp_remote_get("http://" . $idxpro_host . "/" . $ar_number . "/Idx/get_acnt/");
	
	/*
	echo "<pre>";
	print_r($idxpro_account_check);
	echo "</pre>";
	*/
	
	// check if there was a wordpress error
	if( is_wp_error( $idxpro_account_check ) ) {	
		
		$error = 'Wordpress error.';
	}
	// check if it's valid account
	else if ( $idxpro_account_check['response']['code'] == 404 ) {
	
		$error = 'Invalid account number. Please check it and try again.';
	}
	// else it's valid
	else {
		
		// get account info out of body
		$idxpro_account = unserialize($idxpro_account_check['body']);
		
		// add timestamp for last time account was synchronized
		$idxpro_account['last_sync'] = idxpro_get_timestamp();
		
		// save to wp db
		update_option('idxpro_account', $idxpro_account);
		
		$error = false;
	}
	return $error;
	
	//return false;
}

// returns a string representation of the account status
// returns empty string if there is no account or if it's invalid
function idxpro_get_status() {
	global $idxpro_account;
	if ( ! empty($idxpro_account) ) {
	
		switch ( $idxpro_account['status'] ) {
			// active, approved
			case 'A':
				$status = 'Active';
			break;
			
			// purchased but pending mls approval
			case 'P':
				$status = 'Pending';
			break;
			
			// test drive
			case 'T':
				$status = 'Test drive';
			break;
			
			// expired test drive
			case 'X':
				$status = 'Expired test drive';
			break;
			
			// suspended case is a temporary hold usually for non-payment
			case 'S':
				$status = 'Suspended';
			break;
			
			// canceled/disabled
			case 'D':
				$status = 'Canceled';
			break;
			
			// incomplete test drive - started signing up for one but didn't complete the process
			case 'C':
				$status = 'Imcomplete test drive';
			break;
			
			// catch all
			default:
				$status = '';
			break;
		}
		return $status;
	}
	// if no account
	return '';
}


// returns boolean for if idxpro account is testdrive status (includes expired test drives)
function idxpro_is_testdrive() {
	global $idxpro_account;
	return ( ! empty($idxpro_account) && ! empty( $idxpro_account['status'] ) && ( $idxpro_account['status'] == 'T' || $idxpro_account['status'] == 'X') );
}


// returns boolean for if idxpro account is expired testdrive status
function idxpro_is_expired() {
	global $idxpro_account;
	return ( ! empty($idxpro_account) && ! empty( $idxpro_account['status'] ) && $idxpro_account['status'] == 'X' );
}

// returns boolean for if idxpro account is pending
function idxpro_is_pending() {
	global $idxpro_account;
	return ( ! empty($idxpro_account) && ! empty( $idxpro_account['status'] ) && $idxpro_account['status'] == 'P' );
}

// returns boolean for if idxpro account is suspended
function idxpro_is_suspended() {
	global $idxpro_account;
	return ( ! empty($idxpro_account) && ! empty( $idxpro_account['status'] ) && $idxpro_account['status'] == 'S' );
}

// returns boolean for if idxpro account is canceled
function idxpro_is_canceled() {
	global $idxpro_account;
	return ( ! empty($idxpro_account) && ! empty( $idxpro_account['status'] ) && $idxpro_account['status'] == 'D' );
}

// returns boolean for if idxpro account is viewable (not expired, suspended, or canceled)
function idxpro_is_viewable() {
	global $idxpro_account;
	return ( ! empty($idxpro_account) && ! empty( $idxpro_account['status'] ) && $idxpro_account['status'] != 'X' && $idxpro_account['status'] != 'S' && $idxpro_account['status'] != 'D' );
}




/**
 * @param - $as_integer - formats return as int (default is string)
 * @return - number of days until test drive expiration
 */
function idxpro_get_days_left($as_integer = false) {
	global $idxpro_account;
	if ( idxpro_is_testdrive() ) {
		$exp_date = $idxpro_account['testdrive_expires'];
		$yyyy = substr($exp_date,0,4);
		$mm = substr($exp_date,5,2);
		$dd = substr($exp_date,8,2);
		$days_left = round((mktime(0,0,0,$mm,$dd,$yyyy) - time())/86400);
		
		if ($as_integer) {
			return (int)$days_left;
		} else {
			$plural = ($days_left != 1) ? 's' : '';
			return "<strong>$days_left</strong> day$plural";
		}
	}
	return;
}



/**
 * @return - string timestamp formatted like 'YYYY-MM-DD hh:mm:ss'
 */
function idxpro_get_timestamp() {
	return date( "Y-m-d H:i:s");
	//return time();
}

/**
 * @param $date - string formatted like 'Y-m-d H:i:s'
 * @return - unix timestamp (number of seconds)
 */
function idxpro_convert_date_to_unix($date) {
	
	$year = substr($date,0,4);
	$month = substr($date,5,2);
	$day = substr($date,8,2);
	$hour = substr($date,11,2);
	$minute = substr($date,14,2);
	$second = substr($date,17,2);
	
	return mktime($hour, $minute, $second, $month, $day, $year);
}


function idxpro_get_testdrive_url() {
	global $idxpro_host;
	return "http://$idxpro_host/Signup/testdrive/?rcode=wordpress_plugin";
}

function idxpro_get_signup_url() {
	global $idxpro_host;
	return "http://$idxpro_host/Signup/signup/?rcode=wordpress_plugin";
}

function idxpro_get_conversion_url() {
	global $idxpro_host;
	return "http://$idxpro_host/" . idxpro_get_id() . "/Signup/signup/?rcode=wordpress_plugin";
}

function idxpro_get_admin_menu_url() {
	global $idxpro_host;
	return "http://$idxpro_host/" . idxpro_get_id() . "/Admin/index/";
}

function idxpro_get_site_url() {
	global $idxpro_host;
	return "http://$idxpro_host/" . idxpro_get_id() . "/Search/index/";
}

function idxpro_get_shortcode_app_url() {
	global $idxpro_host;
	return "http://$idxpro_host/" . idxpro_get_id() . "/Embed/idxpro_app/";
}

function idxpro_get_quick_search_widget_url() {
	global $idxpro_host;
	return "http://$idxpro_host/" . idxpro_get_id() . "/Embed/search_widget/";
}

function idxpro_get_widget_type_url( $type, $id ) {
    global $idxpro_host;
    return "http://$idxpro_host/" . idxpro_get_id() . "/Embed/$type/?id=$id";
}


function idxpro_get_product_url() {
	//global $idxpro_host; // anather global for idxpro_company_host??
	return "http://www.idxpro.com";
}

function idxpro_get_contact_url() {
	//global $idxpro_host; // anather global for idxpro_product_host??
	return "http://www.idxpro.com/contact.html";
}

function idxpro_get_company_url() {
	//global $idxpro_host; // anather global for idxpro_product_host??
	return "http://www.ihouseweb.com";
}



/*
 * Add shortcodes for idxpro
 * These are basically macros.
 * For our purposes, this is just a convenience wrapper for a javascript snippet -
 * the same one that we give to our normal idxpro customers anyway.
 */
function idxpro_shortcodes( $atts ) {
	global $idxpro_host, $idxpro_account;
	extract( shortcode_atts( array(
		'account' => false, // required
		'widget' => 'app' // values would be something like: 'quick search', 'map', 'search links', 'custom form', 'slide show'
		// 'widget_id' => 0 // do we need this or can we just use a generic type of widget?
	), $atts ) );
	
	
	//if ( empty($atts) || empty($atts['account']) ) return '';
	
	//if ()
	
	
	// main app will only have an account_id attribute
	// widget will also have a widget attribute
	
	if( @$atts['widget'] )
	{
	    if( $atts['widget'] == 'quick searck' ) {
	        $html = '<script type="text/javascript" src="' . idxpro_get_quick_search_widget_url() . '"></script>';
	    } else {
	        $w = explode( '|', $atts['widget'] );
	        if( $w[0] && $w[1] ) {
	            $html = '<script type="text/javascript" src="' . idxpro_get_widget_type_url( $w[0], $w[1] ) . '"></script>';
	        } else {
	            return '';
	        }
	    }	
	}
	else
	{
		$html = '<script type="text/javascript" src="' . idxpro_get_shortcode_app_url() . '"></script>';
	}

	return $html;
}
add_shortcode('idxpro', 'idxpro_shortcodes');





?>
