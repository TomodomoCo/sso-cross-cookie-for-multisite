SSO Cross Cookie for Multisite
==============================

Log in with a single sign on (SSO) to a custom domain site after the user has logged into the admin panel on an SSL-secured subdomain. For WordPress Multisite.

Designed to be run in concert with [SSL Subdomain for Multisite](https://github.com/vanpattenmedia/ssl-subdomain-for-multisite).

**This plugin *depends* upon [WPMU Domain Mapping](https://wordpress.org/extend/plugins/wordpress-mu-domain-mapping/), which must be installed and network activated for this to work.**

What this Plugin does
---------------------

If you have the [SSL Subdomain for Multisite](https://github.com/vanpattenmedia/ssl-subdomain-for-multisite) plugin installed and network activated, you now have logins and admin happening on `https://demo-site.mynetwork.com`, while normal site access is on `http://demo-site.com`.

This works great. Except, once you log in to `demo-site.mynetwork.com` to do some admin work, then visit the main site, perhaps to post a comment as a logged in WordPress user, you are not logged in on the main site. This means that you can’t, for example, post that comment while logged in — you aren’t logged in there! Other logged-in niceties like the display of the admin bar, or the avoidance of caching, are not available. If you log in again, it logs you in to `https://demo-site.mynetwork.com` but still you remain not logged in on `http://demo-site.com`.

This plugin solves this problem by enabling a single sign on (SSO) for both the admin panel and the main site on the custom domain. Upon login, this plugin bounces the user across to the main site to set a cookie there, then bounces them back to the admin panel.

Now, you can work in the admin panel normally, and if you click ‘Visit Site’ from the Admin panel, you go over to the custom domain, where you are also logged in and can perform all actions as normal. Single Sign On!

(Foolish) Assumptions
---------------------

1.	You are using [WPMU Domain Mapping](https://wordpress.org/extend/plugins/wordpress-mu-domain-mapping/) for custom domains on your Multisite network.

2.	You have SSL configured for your master domain (e.g. `www.mynetwork.com`), and for the wildcard `*.mynetwork.com`. You would like normal site access to happen over the custom domains with HTTP, and all admin and login access over the subdomains of `*.mynetwork.com` with HTTPS.

3.	You have the `FORCE_SSL_LOGIN` setting in `wp-config.php` **ON**.
		
4.	You have the `FORCE_SSL_ADMIN` setting in `wp-config.php` **OFF**. We’ll handle that — WordPress’ forcing of SSL admins may confuse this plugin.

This plugin was tested with and is intended to be used in concert with [SSL Subdomain for Multisite](https://github.com/vanpattenmedia/ssl-subdomain-for-multisite).

Known Issues
------------

*	The `redirect_to` parameter is not fully working at present. Sometimes, you will be sent to the root admin page, instead of the specific page you were trying to access. This needs to be improved, as it does compromise the user experience.
*	This provides better security than only enabling `FORCE_SSL_LOGIN` but not `FORCE_SSL_ADMIN`, since with this plugin and [SSL Subdomain for Multisite](https://github.com/vanpattenmedia/ssl-subdomain-for-multisite), login and admin are served over HTTPS. 
	
	**However**, the nature of this setup means that a man-in-the-middle attacker could theoretically impersonate you for the duration of the login session. It is not possible at the moment to avoid this theoretical attack scenario without serving **everything** over HTTPS (making arbitrary custom domain support impossible), or preventing login to the actual custom domain site.