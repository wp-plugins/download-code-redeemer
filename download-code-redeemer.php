<?php
/*
Plugin Name: Download Code Redeemer
Plugin URI: http://tmertz.com/projects/download-code-redeemer/
Description: A simple plugin designed to make it easy for you to provide download codes for special downloads on your website.
Version: 1.0
Author: Thomas Mertz
Author URI: http://tmertz.com/
*/

$dcr_db_version = "1.0";

####################################################################
#
# INSTALLATION
#
####################################################################
function dcr_install () {
	global $wpdb;
	global $dcr_db_version;

	$table_name = $wpdb->prefix . "dcr_downloads";
	if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
		$sql = "CREATE TABLE " . $table_name . " (
				`ID` MEDIUMINT( 11 ) NOT NULL AUTO_INCREMENT ,
				`name` VARCHAR( 200 ) NOT NULL ,
				`description` LONGTEXT NOT NULL ,
				`url` LONGTEXT NOT NULL ,
				PRIMARY KEY (  `ID` ) ,
				INDEX (  `ID` )
		);";
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}
	
	$table_name = $wpdb->prefix . "dcr_codes";
	if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
		$sql = "CREATE TABLE " . $table_name . " (
				`ID` MEDIUMINT( 11 ) NOT NULL AUTO_INCREMENT ,
				`downloadID` MEDIUMINT( 11 ) NOT NULL ,
				`is_used` BINARY( 1 ) NULL DEFAULT  '0',
				`is_unlimited` BINARY( 1 ) NULL DEFAULT  '0',
				`code` VARCHAR( 20 ) NOT NULL ,
				PRIMARY KEY (  `ID` ) ,
				INDEX (  `ID` )
		);";
				
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		
		add_option("dcr_db_version", $dcr_db_version);
	}
	
	$installed_ver = get_option( "dcr_db_version" );
	
	if( $installed_ver != $dcr_db_version ) {
		$table_name = $wpdb->prefix . "dcr_downloads";
		$sql = "CREATE TABLE " . $table_name . " (
				`ID` MEDIUMINT( 11 ) NOT NULL AUTO_INCREMENT ,
				`name` VARCHAR( 200 ) NOT NULL ,
				`description` LONGTEXT NOT NULL ,
				`url` LONGTEXT NOT NULL ,
				PRIMARY KEY (  `ID` ) ,
				INDEX (  `ID` )
		);";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		
		$table_name = $wpdb->prefix . "dcr_codes";
		$sql = "CREATE TABLE " . $table_name . " (
				`ID` MEDIUMINT( 11 ) NOT NULL AUTO_INCREMENT ,
				`downloadID` MEDIUMINT( 11 ) NOT NULL ,
				`is_used` BINARY( 1 ) NULL DEFAULT  '0',
				`is_unlimited` BINARY( 1 ) NULL DEFAULT  '0',
				`code` VARCHAR( 20 ) NOT NULL ,
				PRIMARY KEY (  `ID` ) ,
				INDEX (  `ID` )
		);";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		update_option( "dcr_db_version", $dcr_db_version );
	  }
	  update_option("dcr-language","en");
}
register_activation_hook(__FILE__,'dcr_install');

####################################################################
#
# UNINSTALLATION
#
####################################################################
function dcr_uninstall() {
	global $wpdb;
	
	$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'dcr_downloads;');
	$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'dcr_codes;');

	delete_option("dcr_db_version");
}
register_deactivation_hook(__FILE__,'dcr_uninstall');

####################################################################
#
# ADMIN SECTION
#
####################################################################
add_action('admin_menu', 'dcr_plugin_menu');

function dcr_plugin_menu() {
	add_menu_page( 'DCR', 'DCR', 'publish_posts', 'dcr', 'dcr_overview', '' );
}

function dcr_overview() {
	echo "<div class=\"wrap nosubsub\">";
	if( $_GET["action"] == "manage" AND isset( $_GET["id"] ) ) {
	
		global $wpdb;
		$downloadID = $wpdb->prepare( $_GET["id"] );
		$dcr_downloads_table = $wpdb->prefix . "dcr_downloads";
		$download = $wpdb->get_row("SELECT * FROM {$dcr_downloads_table} WHERE ID = '{$downloadID}'");
		echo "<div id=\"icon-edit\" class=\"icon32\"><br></div><h2><a href=\"/wp-admin/admin.php?page=dcr\">DCR</a> &raquo; {$download->name}</h2>";
		
		echo "<h3>Add New Download Codes</h3>";
		echo "<form method=\"post\" action=\"" . $_SERVER["REQUEST_URI"] . "\">";
		echo "<table class=\"form-table\">";
		echo "  <tbody>";
		echo "    <tr valign=\"top\">";
		echo "      <th scope=\"row\"><label for=\"dcr-code-amount\">How many:</label></th>";
		echo "      <td><select name=\"dcr-amount\" id=\"dcr-amount\">";
		echo "        <option value=\"1\">1</option>";
		echo "        <option value=\"2\">2</option>";
		echo "        <option value=\"3\">3</option>";
		echo "        <option value=\"4\">4</option>";
		echo "        <option value=\"5\">5</option>";
		echo "        <option value=\"10\">10</option>";
		echo "        <option value=\"20\">20</option>";
		echo "        <option value=\"30\">30</option>";
		echo "        <option value=\"50\">50</option>";
		echo "        <option value=\"100\">100</option>";
		echo "        <option value=\"500\">500</option>";
		echo "        <option value=\"1000\">1.000</option>";
		echo "        <option value=\"10000\">10.000</option>";
		echo "      </select></td>";
		echo "    </tr>";
		echo "    <tr valign=\"top\">";
		echo "      <th scope=\"row\"><label for=\"dcr-code-type\">Type:</label></th>";
		echo "      <td><fieldset>";
		echo "        <label><input name=\"dcr-code-type\" type=\"radio\" id=\"dcr-code-type\" value=\"1\"> <span>Unlimited uses</span></label><br />";
		echo "        <label><input name=\"dcr-code-type\" type=\"radio\" id=\"dcr-code-type\" value=\"1\"> <span>Single use</span></label>";
		echo "      </fieldset></td>";
		echo "    </tr>";
		echo "  </tbody>";
		echo "</table>";
		echo "<p class=\"submit\"><input type=\"submit\" name=\"submit\" id=\"submit\" class=\"button\" value=\"Add New Codes\"></p>";
		echo "<input type=\"hidden\" name=\"flash\" value=\"thunder\" />";
		echo "</form>";
		
		echo "<h3>Manage Codes</h3>";
		echo "<table class=\"wp-list-table widefat fixed posts\" cellspacing=\"0\">";
		echo "  <thead>";
		echo "    <tr>";
		echo "      <th scope=\"col\" class=\"manage-column column-cb check-column\" style=\"\"></th>";
		echo "      <th scope=\"col\" class=\"manage-column column-title\" style=\"\">Type</th>";
		echo "      <th scope=\"col\" class=\"manage-column column-title\" style=\"\">No. of codes</th>";
		echo "      <th scope=\"col\" class=\"manage-column column-title\" style=\"\">Redeemed</th>";
		echo "    </tr>";
		echo "  </thead>";

		echo "  <tfoot>";
		echo "    <tr>";
		echo "      <th scope=\"col\" class=\"manage-column column-cb check-column\" style=\"\"></th>";
		echo "      <th scope=\"col\" class=\"manage-column column-title\" style=\"\">Type</th>";
		echo "      <th scope=\"col\" class=\"manage-column column-title\" style=\"\">No. of codes</th>";
		echo "      <th scope=\"col\" class=\"manage-column column-title\" style=\"\">Redeemed</th>";
		echo "    </tr>";
		echo "  </tfoot>";

		echo "  <tbody id=\"the-list\">";
		$dcr_codes_table = $wpdb->prefix . "dcr_codes";
		$codes = $wpdb->get_results("SELECT COUNT(*) as cnt FROM {$dcr_codes_table} WHERE downloadID = '{$downloadID}' GROUP BY batchID");
		echo "    <tr class=\"alternate author-self status-publish format-default iedit\" valign=\"top\">";
		echo "      <th scope=\"row\" class=\"check-column\"></th>";
		echo "      <td class=\"column-title\"></td>";
		echo "      <td class=\"column-title\"></td>";
		echo "      <td class=\"column-title\"></td>";
		echo "    </tr>";
		echo "  </tbody>";
		echo "</table>";
	
	} elseif( $_GET["action"] == "delete" AND isset( $_GET["id"] ) ) {
		
		global $wpdb;
		$downloadID = $wpdb->prepare( $_GET["id"] );
		$dcr_downloads_table = $wpdb->prefix . "dcr_downloads";
		$download = $wpdb->get_row("SELECT * FROM {$dcr_downloads_table} WHERE ID = '{$downloadID}'");
		echo "<div id=\"icon-edit\" class=\"icon32\"><br></div><h2>Delete {$download->name}</h2>";
	
	} else {
	
		echo "<div id=\"icon-edit\" class=\"icon32\"><br></div><h2>Download Code Redeemer</h2>";
		
		if( $_POST["flash"]=="thunder" ) {
			global $wpdb;
			$dcr_name = $wpdb->prepare( $_POST["dcr-name"] );
			$dcr_url = $wpdb->prepare( $_POST["dcr-url"] );
			$dcr_description = $wpdb->prepare( $_POST["dcr-description"] );
			$dcr_downloads_table = $wpdb->prefix . "dcr_downloads";
			$wpdb->insert( $dcr_downloads_table, array( 'name'=>$dcr_name , 'description'=>$dcr_description , 'url'=>$dcr_url ) );
			echo "<div id=\"message\" class=\"updated below-h2\"><p>The download, <strong>{$_POST["dcr-name"]}</strong>, has been created.</p></div>";
		}
		
		echo "<div id=\"col-container\">";
		
		/* RIGHT COLUMN */
		echo "<div id=\"col-right\">";
		echo "<div class=\"col-wrap\">";
		
		echo "<table class=\"wp-list-table widefat fixed tags\" cellspacing=\"0\">";
		echo "<thead>";
		echo "<tr>";
		echo "<th scope=\"col\" id=\"name\" class=\"manage-column check-column desc\"></th>";
		echo "<th scope=\"col\" id=\"name\" class=\"manage-column column-name desc\">Download</th>";
		echo "<th scope=\"col\" id=\"description\" class=\"manage-column column-description desc\">Codes</th>";
		echo "</tr>";
		echo "</thead>";
	
		echo "<tfoot>";
		echo "<tr>";
		echo "<th scope=\"col\" id=\"name\" class=\"manage-column check-column desc\"></th>";
		echo "<th scope=\"col\" id=\"name\" class=\"manage-column column-name desc\">Download</th>";
		echo "<th scope=\"col\" id=\"description\" class=\"manage-column column-description desc\">Codes</th>";
		echo "</tfoot>";
	
		echo "<tbody id=\"the-list\" class=\"list:tag\">";
		
		global $wpdb;
		$dcr_downloads_table = $wpdb->prefix . "dcr_downloads";
		$downloads = $wpdb->get_results("SELECT ID, name FROM {$dcr_downloads_table} ORDER BY name ASC");
		foreach($downloads as $download) {
		echo "<tr id=\"tag-1\" class=\"\">";
		echo "  <td class=\"name check-column\"></td>";
		echo "  <td class=\"description column-description\"><strong>" . $download->name . "</strong><br /><a href=\"" . $_SERVER["REQUEST_URI"] . "&action=manage&id=" . $download->ID . "\">Manage</a> | <a href=\"" . $_SERVER["REQUEST_URI"] . "&action=delete&id=" . $download->ID . "\">Delete</a></td>";
		echo "  <td class=\"description column-description\">";
		$dcr_codes_table = $wpdb->prefix . "dcr_codes";
		$codes = $wpdb->get_results("SELECT COUNT(*) as cnt FROM {$dcr_codes_table} WHERE downloadID = '{$download->ID}' GROUP BY is_unlimited");
		foreach($codes as $code) {
		echo "    ";
		}
		echo "  </td>";
		echo "</tr>";
		}
		echo "</tbody>";
		echo "</table>";
		
		echo "</div>";
		echo "</div><!-- /col-right -->";
		
		/* LEFT COLUMN */		
		echo "<div id=\"col-left\">";
		echo "<div class=\"col-wrap\">";
		
		echo "<h3>Add New Download Code";
		
		echo "<div class=\"form-wrap\">";
		echo "<form id=\"addtag\" method=\"post\" action=\"" . $_SERVER["REQUEST_URI"] . "\" class=\"validate\">";
		
		echo "<div class=\"form-field form-required\">";
		echo "<label for=\"dcr-name\">Name</label>";
		echo "<input name=\"dcr-name\" id=\"dcr-name\" type=\"text\" size=\"40\" aria-required=\"true\">";
		echo "<p>Give your download a name, preferably one that makes it easy to recognize it.</p>";
		echo "</div>";
		
		echo "<div class=\"form-field form-required\">";
		echo "<label for=\"dcr-url\">URL</label>";
		echo "<input name=\"dcr-url\" id=\"dcr-url\" type=\"text\" size=\"40\" aria-required=\"true\">";
		echo "<p>Enter the full public link to your download (remember to include http://).</p>";
		echo "</div>";
		
		echo "<div class=\"form-field\">";
		echo "<label for=\"dcr-description\">Description</label>";
		echo "<textarea name=\"dcr-description\" id=\"dcr-description\" rows=\"5\" cols=\"40\"></textarea>";
		echo "<p>The description is not prominent by default; however, it may make it easier for you to manage your downloads.</p>";
		echo "</div>";
		
		echo "<p class=\"submit\"><input type=\"submit\" name=\"submit\" id=\"submit\" class=\"button\" value=\"Add New Download\"></p>";
		echo "<input type=\"hidden\" name=\"flash\" value=\"thunder\" />";
		
		echo "</form>";
		echo "</div>";
		
		echo "</div>";
		echo "</div><!-- /col-left -->";
		echo "</div><!-- /col-container -->";
	
	}
	echo "</div>";
}
?>