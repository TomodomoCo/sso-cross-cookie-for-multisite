<?php
/*
Plugin Name: SSO Cross Cookie for Multisite
Plugin URI: http://www.vanpattenmedia.com/
Description: Log in with a single sign on (SSO) to a custom domain site after the user has logged into the admin panel on an SSL-secured subdomain. This plugin <strong>depends</strong> upon WPMU Domain Mapping, which must be installed and activated for this to work.
Version: 1.0
Author: Peter Upfold
Author URI: http://peter.upfold.org.uk/
License: GPL2
*/
/*  Copyright (C) 2012 Peter Upfold.

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class SSO_XCookie
{

	public function redirect_to_set_xcookie()
	{
	/*
		Redirect across to the custom page to set the logged in cookie there.
		It will redirect us back here.
	*/
	
		if (!function_exists('domain_mapping_siteurl'))
		{
			return false;
		}

		if (!array_key_exists('wordpress_test_cookie', $_COOKIE) || !array_key_exists(LOGGED_IN_COOKIE, $_COOKIE))
		{
			// cookies are non-functional, so don't redirect or we'll cause a loop
			return false;
		}
		
		if (array_key_exists('action', $_REQUEST) && $_REQUEST['action'] == 'logout')
		{
			// clear it
			return SSO_XCookie::redirect_to_clear_xcookie();
		}
		
		// if we are on the master site, then don't redirect at all
		$customDomain = domain_mapping_siteurl(false);
		
		$cdHost = parse_url($customDomain, PHP_URL_HOST);
		$tdHost = parse_url(get_site_url(), PHP_URL_HOST);
		
		if ($cdHost == $tdHost)
		{
			return false;
		}
	
		// only redirect if the xcookie has not already been attempted to be set
		if (!array_key_exists('has_set_xcookie_'.md5(get_site_url().LOGGED_IN_SALT), $_COOKIE))
		{
		
			// prepare the redirect_to
			if ( isset( $_REQUEST['redirect_to'] ) ) {
				$redirect_to = $_REQUEST['redirect_to'];
			}
			else {
				$redirect_to = admin_url();
			}
			
			$customDomain = preg_replace('/^https:\/\//', 'http://', $customDomain);
			
			$expiration = time() + apply_filters('auth_cookie_expiration', 172800);
			$expire = 0;
						
			setcookie('has_set_xcookie_'.md5(get_site_url().LOGGED_IN_SALT), 'true', $expiration, SITECOOKIEPATH);
			wp_redirect($customDomain.'/wp-login.php?xcookie='.urlencode($_COOKIE[LOGGED_IN_COOKIE]).'&redirect_to='.urlencode($redirect_to));
			die();
			
		}
	
	}
	
	public function set_xcookie_and_redirect_back()
	{
	/*
		If we have arrived at the login page because of an XCookie set request, set the cookie
		and redirect back to the source.
		
		(Note that since we can only really hook login_init, in a moment we check to see what we want
		to do -- logout clear, redirect to logout clear, or login set.)
	*/
	
		/* If this is a logout request */	
		if (array_key_exists('action', $_REQUEST) && $_REQUEST['action'] == 'logout')
		{
			if (array_key_exists('have_bounced', $_GET))
			{ /* and we are back on the 'custom' domain, clear the cookie and send back */
				return SSO_XCookie::clear_xcookie_and_redirect_back();
			}
			else { /* else, redirect over to the 'custom' domain in order to clear the cookie */
				return SSO_XCookie::redirect_to_clear_xcookie();
			}
		}
	
		/* otherwise, it's not a logout request, so if it's an xcookie login request, set the xcookie */	
		if (!array_key_exists('xcookie', $_GET))
		{
			return false;
		}
		
		$expiration = time() + apply_filters('auth_cookie_expiration', 172800);
		$expire = 0;
		
		// check the cross-token
		// generate a valid login cookie with wp_generate_auth_cookie($user_id, $expiration, 'logged_in'); ???
				
		setcookie(LOGGED_IN_COOKIE, $_GET['xcookie'], $expire, COOKIEPATH, COOKIE_DOMAIN, false, true);
		
		add_filter('allowed_redirect_hosts', array('SSO_XCookie', 'allow_original_domain_redirect'));
		
		wp_safe_redirect($_REQUEST['redirect_to']);
		die();	
			
	}
	
	public function redirect_to_clear_xcookie()
	{
	/*
		Upon a logout request, bounce over to the custom domain and clear the xcookie so we 
		are logged out in both places.
	*/
	
		if (!function_exists('domain_mapping_siteurl'))
		{
			return false;
		}	
	
		// clear the has_set cookie
		if (array_key_exists('has_set_xcookie_'.md5(get_site_url().LOGGED_IN_SALT), $_COOKIE))
		{
			setcookie('has_set_xcookie_'.md5(get_site_url().LOGGED_IN_SALT), null, time()-(3600*24*7*52), SITECOOKIEPATH);
		
			// bounce over to clear the xcookie
			$customDomain = domain_mapping_siteurl(false);
			$customDomain = preg_replace('/^https:\/\//', 'http://', $customDomain);
			wp_redirect($customDomain.'/wp-login.php?xcookie='.urlencode($_COOKIE[LOGGED_IN_COOKIE]).'&have_bounced=true&action=logout&_wpnonce='.
							urlencode($_GET['_wpnonce']));
			die();
		
		}
	
	}
	
	public function clear_xcookie_and_redirect_back()
	{
	/*
		Clear the XCookie and send back to the proper logout page to finish up.
	*/
		if (!function_exists('get_original_url'))
		{
			return false;
		}
	
		setcookie(LOGGED_IN_COOKIE, null, time()-(3600*24*7*52), COOKIEPATH, COOKIE_DOMAIN, false, true);
		
		$originalURL = get_original_url('site_url');
		
		wp_redirect($originalURL.'/wp-login.php?action=logout&_wpnonce='.urlencode($_REQUEST['_wpnonce']));
		die();
	
	}
	
	public function allow_original_domain_redirect($doms)
	{
	/*
		Allow a safe redirect to the 'original host' for the Admin panel, from a XCookie login bounce.
	*/
	
		if (!function_exists('get_original_url'))
		{
			return $doms;
		}
		
		$originalURL = get_original_url('site_url');
		
		if ($originalURL)
		{
		
			$originalURL = parse_url($originalURL, PHP_URL_HOST);
			$doms[] = $originalURL;
			return $doms;		
		}
		else {
			return $doms;
		}
	
	}

};

add_action('admin_init', array('SSO_XCookie', 'redirect_to_set_xcookie'));
add_action('login_init', array('SSO_XCookie', 'set_xcookie_and_redirect_back'));

?>