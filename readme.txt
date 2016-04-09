=== LinkedIn Company Updates ===
Contributors: rockwell15 
Tags: linkedin,company,update,updates,feed,news,recent,latest,posts
Requires at least: 3.5
Tested up to: 4.4
Stable tag: 1.4
License: GPLv2
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html

Allows users to display their Company's LinkedIn updates with [shortcode] or PHP.


== Description ==

This plugin uses LinkedIn's API to retrieve the user's company updates, which can then be displayed with [shortcode] or PHP.

**Features:**

* Optional custom styling
* Copy & paste customizable shortcode
* Custom CSS classes option
* Displays token expiration date
* PHP implementation option
* Responsive default styling
* Images now appear in feed

**Coming Soon:**

* Email & admin notification for expired tokens
* Extra Style Options


== Installation ==

**Installation & Activation**

1. Download plugin
2. Unzip plugin on computer
3. Add unzipped folder to /wp-content/plugins/

**OR**

1. Go to your admin area and select Plugins -> Add new from the menu
2. Search for "Company Updates for LinkedIn"
3. Click install

4. Activate the plugin in the 'Plugins' menu in the admin area
5. Go to Tools -> 'LinkedIn Company Updates' to configure

**Using the Plugin**

1. After installing "LinkedIn Company Updates", go to developer.linkedin.com

2. Click on "My Apps" & Sign in with your LinkedIn account. Create one if needed

3. Click on "Create Application" & Fill out the required forms

4. Copy the Client ID & Client secret to the plugin settings fields

5. Then copy the redirect URL on the plugins settings page

6. And paste / click "Add" under OAuth 2.0 Authentication redirect URLs

7. DONT FORGET TO CHECK OFF "rw\_company\_admin" AND UPDATE THE LINKEDIN APP

8. Add CSS classes for the feed container & items if you plan on custom design

9. Add the company ID

9. Save the plugin options

10. Click "Regenerate Access Token"

11. Enter LinkedIn information and click "Allow Access"

12. Copy the shortcode from the bottom of the page & paste it into any page visual editor.


== Screenshots ==
1. An example feed of the default styling for the plugin

2. Screenshot of the admin area for the plugin

 `[youtube https://www.youtube.com/watch?v=ncl81v5g-YU]`


== Changelog ==
**Version 1.4 - 4/6/16**
* Added cURL fallback & error message if allow_url_fopen is disabled as well

**Version 1.3 - 3/20/16**

* Added debugging helpers/ error messages
* Posts open in new tab
* Images now appear in feed
* All Links are now clickable
* Neatened up the code just a bit

**Version 1.2.3 - 12/31/15**

* Added cross browser CSS support

**Version 1.2 - 12/31/15**

* Added responsive styling
* Added PHP functionality
* Added date of expiration for token
* Added error messages

* Fixed potential CSS selector bug

**Version 1.1 - 12/30/15**

* Added default styling

* Fixed access token bug