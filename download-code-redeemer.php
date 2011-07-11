<?php
/*
Plugin Name: Download Code Redeemer
Plugin URI: http://tmertz.com/projects/download-code-redeemer/
Description: A simple plugin designed to make it easy for you to provide download codes for special downloads on your website.
Version: 1.1
Author: Thomas Mertz
Author URI: http://tmertz.com/
*/

$dcr_db_version = "1.1";

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
				`labeltext` VARCHAR( 200 ) NOT NULL ,
				`successtext` LONGTEXT NOT NULL ,
				`failtext` LONGTEXT NOT NULL ,
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
				`batchID` MEDIUMINT( 11 ) NOT NULL ,
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
				`labeltext` VARCHAR( 200 ) NOT NULL ,
				`successtext` LONGTEXT NOT NULL ,
				`failtext` LONGTEXT NOT NULL ,
				`url` LONGTEXT NOT NULL ,
				PRIMARY KEY (  `ID` ) ,
				INDEX (  `ID` )
		);";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		
		$table_name = $wpdb->prefix . "dcr_codes";
		$sql = "CREATE TABLE " . $table_name . " (
				`ID` MEDIUMINT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`code` VARCHAR( 50 ) NOT NULL ,
				`ip` VARCHAR( 50 ) NOT NULL ,
				`timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
				PRIMARY KEY (  `ID` ) ,
				INDEX (  `ID` )
		);";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		update_option( "dcr_db_version", $dcr_db_version );
	  }
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
# MISC SUPPORT FUNCTIONS
#
####################################################################
function dcrRandomString() {
    $length = 10;
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = "";    
    for ($p = 0; $p < $length; $p++) {
        $string .= $characters[mt_rand(0, strlen($characters))];
    }
    return $string;
}
function countRedeemedCodes($batchID) {
	global $wpdb;
	$dcr_codes_table = $wpdb->prefix . "dcr_codes";
	$count = $wpdb->get_var("SELECT COUNT(*) FROM {$dcr_codes_table} WHERE is_used='1' AND batchID='{$batchID}'");
	return $count;
}
function getDownload($downloadID) {
	global $wpdb;
	$downloadID = $wpdb->prepare($downloadID);
	$dcr_downloads_table = $wpdb->prefix . "dcr_downloads";
	$download = $wpdb->get_var("SELECT url FROM {$dcr_downloads_table} WHERE ID = '{$downloadID}'");
	return $download;
}
function exportToCSV() {

	if( isset( $_GET["id"] ) AND isset( $_GET["batch"] ) AND ( $_GET["export"] == "csv" ) ) {
	
		global $wpdb;
		$wpdb->show_errors();
		header("Content-type:text/octect-stream");
    	header("Content-Disposition:attachment;filename=export.csv");
		
		$dcr_codes_table = $wpdb->prefix . "dcr_codes";
		$downloadID = $wpdb->prepare($_GET["id"]);
		$batchID = $wpdb->prepare($_GET["batch"]);
		$codes = $wpdb->get_results("SELECT code FROM {$dcr_codes_table} WHERE downloadID = '{$downloadID}' AND batchID = '{$batchID}'");
	
		foreach($codes as $code) {
		
			echo '"' . $code->code . "\"\n";
		
		}
    	exit;
    }
}
function dcr_stylesheet() {
	$myStyleUrl = WP_PLUGIN_URL . '/assets/css/dcr.css';
	$myStyleFile = WP_PLUGIN_DIR . '/assets/css/dcr.css';
	if ( file_exists($myStyleFile) ) {
		wp_register_style('dcr-styles', $myStyleUrl);
		wp_enqueue_style( 'dcr-styles');
	}
}
function returnDownloadData($downloadID) {
	global $wpdb;
	$downloadID = $wpdb->prepare($downloadID);
	$dcr_downloads_table = $wpdb->prefix . "dcr_downloads";
	$download = $wpdb->get_row("SELECT * FROM {$dcr_downloads_table} WHERE ID = '{$downloadID}'");
	return $download;
}
function redeemCode() {
	
	if( !empty( $_POST["did"] ) AND !empty( $_POST["code"] ) AND is_numeric( $_POST["did"] ) ) {
		
		global $wpdb;
		$code = $wpdb->prepare( $_POST["code"] );
		$did = $wpdb->prepare( $_POST["did"] );
		$dcr_codes_table = $wpdb->prefix . "dcr_codes";
		$match = $wpdb->get_row("SELECT * FROM {$dcr_codes_table} WHERE downloadID = '{$did}' AND code = '{$code}' AND is_used='0'");
		if( empty($match) ) {
		
			wp_redirect( $_SERVER["REQUEST_URI"] . "?success=0" );
			exit;
		
		} else {
		
			if( $match->is_unlimited == 0 ) {
			
				$wpdb->update( $dcr_codes_table, array( 'is_used'=>1 ), array( 'code'=>$code ) );
			
			}
			
			header("Location: " .  getDownload($match->downloadID) );
			exit;
			
		}
	
	}
	
}

####################################################################
#
# ACTION PLUGS
#
####################################################################
add_action('init', 'exportToCSV');
add_action('init', 'redeemCode');
wp_enqueue_style('dcr', plugins_url('download-code-redeemer/assets/css/dcr.css'), false, $dcr_db_version, 'all');

####################################################################
#
# SHORTCODE
#
####################################################################
function show_redeemer( $atts ) {
	
	$download = returnDownloadData( $atts["download"] );
	if( isset( $_GET["success"] ) ) {
		switch($_GET["success"]) {
		case '0':
			echo "<p>" . stripslashes( $download->failtext ) . "</p>";
		break;
		case '1':
			echo "<p>" . stripslashes( $download->successtext ) . "</p>";
		break;
		}
	} else {
	?>
	<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>" id="dcr-form">
		<label for="code"><?php echo $download->labeltext; ?></label>
		<input type="text" name="code" id="code" />
		<input type="hidden" name="did" id="did" value="<?php echo $atts["download"]; ?>" />
		<button type="submit">Redeem</button>
	</form>
	<?php
	}
		
}
add_shortcode( 'redeemer', 'show_redeemer' );

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
		
		if( $_POST["flash"]=="thunder" ) {
		
			$i = 0;
			$amount = $wpdb->prepare( $_POST["dcr-amount"] );
			$type = $wpdb->prepare( $_POST["dcr-code-type"] );
			$downloadID = $wpdb->prepare( $_GET["id"] );
			$dcr_codes_table = $wpdb->prefix . "dcr_codes";
			$batchID = $wpdb->get_var("SELECT batchID FROM {$dcr_codes_table} WHERE downloadID ='{$downloadID}' ORDER BY batchID DESC LIMIT 1");
			if( empty($batchID) ) {
				$batchID = 1;
			} else {
				$batchID++;
			}
						
			while($i < $amount) {
			
				$code = dcrRandomString();
				$does_code_exist = $wpdb->get_var("SELECT COUNT(ID) FROM {$dcr_codes_table} WHERE code ='{$code}' LIMIT 1");
				if( $does_code_exist == 0 ) {
					$wpdb->insert( $dcr_codes_table, array( 'downloadID'=>$downloadID , 'is_unlimited'=>$type , 'code'=>$code , 'batchID'=>$batchID ) );
					$i++;
				}
			
			}
			
			echo "<div id=\"message\" class=\"updated below-h2\"><p><strong>{$i}</strong> new download codes have been created.</p></div>";	
		
		}
		if( isset( $_GET["id"] ) AND isset( $_GET["batch"] ) AND ( $_GET["batchaction"] == "delete" ) ) {
	
			global $wpdb;
			$dcr_codes_table = $wpdb->prefix . "dcr_codes";
			$downloadID = $wpdb->prepare($_GET["id"]);
			$batchID = $wpdb->prepare($_GET["batch"]);
			$wpdb->get_results("DELETE FROM {$dcr_codes_table} WHERE downloadID = '{$downloadID}' AND batchID = '{$batchID}'");
			
			echo "<div id=\"message\" class=\"updated below-h2\"><p>A batch of codes has been deleted.</p></div>";	
			
    	}
		
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
		echo "        <option value=\"5000\">5.000</option>";
		echo "        <option value=\"10000\">10.000</option>";
		echo "      </select></td>";
		echo "    </tr>";
		echo "    <tr valign=\"top\">";
		echo "      <th scope=\"row\"><label for=\"dcr-code-type\">Type:</label></th>";
		echo "      <td><fieldset>";
		echo "        <label><input name=\"dcr-code-type\" type=\"radio\" id=\"dcr-code-type\" value=\"1\"> <span>Unlimited uses</span></label><br />";
		echo "        <label><input name=\"dcr-code-type\" type=\"radio\" id=\"dcr-code-type\" value=\"0\"> <span>Single use</span></label>";
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
		$batchcount = $wpdb->get_var("SELECT COUNT(*) FROM {$dcr_codes_table} WHERE downloadID = '{$downloadID}'");
		if( $batchcount == 0 ) {
	
			echo "    <tr valign=\"top\">";
			echo "      <th scope=\"row\" class=\"check-column\"></th>";
			echo "      <td class=\"column-title\" colspan=\"3\">No codes have been created for this download.</td>";
			echo "    </tr>";
		
		} else {
			
			$codes = $wpdb->get_results("SELECT *,COUNT(*) as cnt FROM {$dcr_codes_table} WHERE downloadID = '{$downloadID}' GROUP BY batchID");
			foreach($codes as $code) {
			
				echo "    <tr class=\"alternate author-self status-publish format-default iedit\" valign=\"top\">";
				echo "      <th scope=\"row\" class=\"check-column\"></th>";
				echo "      <td class=\"column-title\">";
				if( $code->is_unlimited == 0 ){
					echo "        <strong>Single use</strong><br />";
				} else {
					echo "        <strong>Unlimited use</strong><br />";
				}
				echo "       <a href=\"" . $_SERVER["REQUEST_URI"] . "&export=csv&batch=" . $code->batchID . "\">Export as CSV</a> | <a href=\"" . $_SERVER["REQUEST_URI"] . "&batchaction=delete&batch=" . $code->batchID . "\">Delete</a>";
				echo "      </td>";
				
				echo "      <td class=\"column-title\">" . $code->cnt . "</td>";
				echo "      <td class=\"column-title\">" . countRedeemedCodes($code->batchID) . "</td>";
				echo "    </tr>";
			
			}

		}
		
		echo "  </tbody>";
		echo "</table>";
	
	} elseif( $_GET["action"] == "delete" AND isset( $_GET["id"] ) ) {
		
		global $wpdb;
		$downloadID = $wpdb->prepare( $_GET["id"] );
		$dcr_downloads_table = $wpdb->prefix . "dcr_downloads";
		$download = $wpdb->get_row("SELECT * FROM {$dcr_downloads_table} WHERE ID = '{$downloadID}'");
		echo "<div id=\"icon-edit\" class=\"icon32\"><br></div><h2>Delete {$download->name}</h2>";
		echo "<form method=\"post\" action=\"/wp-admin/admin.php?page=dcr\">";
		echo "<input type=\"hidden\" name=\"deleteDownload\" value=\"yes\" />";
		echo "<input type=\"hidden\" name=\"downloadID\" value=\"" . $_GET["id"] . "\" />";
		echo "<p><strong>Are you absolutely sure you want to delete this download?</strong> Remember, this will delete both the download and all generated codes.</p>";
		echo "<p><strong>Note:</strong> The download reference will only be removed. You must manually pull the file offline.</p>";
		echo "<p class=\"submit\">";
		echo "<input type=\"submit\" name=\"submit\" id=\"submit\" class=\"button\" value=\"Delete\">";
		echo " <a href=\"#\" onclick=\"window.history.go(-1);return false;\">No, I changed my mind</a>";
		echo "</p></form>";
	
	} else {
	
		echo "<div id=\"icon-edit\" class=\"icon32\"><br></div><h2>Download Code Redeemer</h2>";
		
		if( $_POST["flash"]=="thunder" ) {
			global $wpdb;
			$dcr_name = $wpdb->prepare( $_POST["dcr-name"] );
			$dcr_url = $wpdb->prepare( $_POST["dcr-url"] );
			$dcr_description = $wpdb->prepare( $_POST["dcr-description"] );
			$dcr_labeltext = $wpdb->prepare( $_POST["dcr-labeltext"] );
			$dcr_successtext = $wpdb->prepare( $_POST["dcr-successtext"] );
			$dcr_failtext = $wpdb->prepare( $_POST["dcr-failtext"] );
			$dcr_downloads_table = $wpdb->prefix . "dcr_downloads";
			$wpdb->insert( 
				$dcr_downloads_table, 
				array( 
					'name'=>$dcr_name , 
					'description'=>$dcr_description , 
					'labeltext'=>$dcr_labeltext , 
					'successtext'=>$dcr_successtext , 
					'failtext'=>$dcr_failtext , 
					'url'=>$dcr_url 
				) 
			);
			echo "<div id=\"message\" class=\"updated below-h2\"><p>The download, <strong>{$_POST["dcr-name"]}</strong>, has been created.</p></div>";
		}
		if( $_POST["deleteDownload"] == "yes" AND is_numeric( $_POST["downloadID"] ) ) {
		
			global $wpdb;
			$downloadID = $wpdb->prepare( $_POST["downloadID"] );
			$dcr_downloads_table = $wpdb->prefix . "dcr_downloads";
			$wpdb->get_results("DELETE FROM {$dcr_downloads_table} WHERE ID = '{$downloadID}'");
			$dcr_codes_table = $wpdb->prefix . "dcr_codes";
			$wpdb->get_results("DELETE FROM {$dcr_codes_table} WHERE downloadID = '{$downloadID}'");
			echo "<div id=\"message\" class=\"updated below-h2\"><p>The download was succesfully deleted.</p></div>";
		
		}
		
		echo "<div id=\"col-container\">";
		
		/* RIGHT COLUMN */
		echo "<div id=\"col-right\">";
		echo "<div class=\"col-wrap\">";
		
		echo "<table class=\"wp-list-table widefat fixed tags\" cellspacing=\"0\">";
		echo "<thead>";
		echo "<tr>";
		echo "<th scope=\"col\" id=\"id\" width=\"5%\" class=\"manage-column column-name desc\">ID</th>";
		echo "<th scope=\"col\" id=\"name\" class=\"manage-column column-name desc\">Download</th>";
		echo "<th scope=\"col\" id=\"description\" class=\"manage-column column-description desc\">Options</th>";
		echo "</tr>";
		echo "</thead>";
	
		echo "<tfoot>";
		echo "<tr>";
		echo "<th scope=\"col\" id=\"id\" class=\"manage-column column-name desc\">ID</th>";
		echo "<th scope=\"col\" id=\"name\" class=\"manage-column column-name desc\">Download</th>";
		echo "<th scope=\"col\" id=\"description\" class=\"manage-column column-description desc\">Options</th>";
		echo "</tfoot>";
	
		echo "<tbody id=\"the-list\" class=\"list:tag\">";
		
		global $wpdb;
		$dcr_downloads_table = $wpdb->prefix . "dcr_downloads";
		$downloads = $wpdb->get_results("SELECT ID, name, description, url FROM {$dcr_downloads_table} ORDER BY name ASC");
		foreach($downloads as $download) {
		echo "<tr id=\"tag-1\" class=\"\">";
		echo "  <td class=\"name column-description\">" . $download->ID . "</td>";
		echo "  <td class=\"description column-description\">";
		echo "    <strong>" . $download->name . "</strong><br />";
		echo "    <small><a href=\"" . $download->url . "\">" . $download->url . "</a></small><br />";
		echo "    {$download->description}";
		echo "  </td>";
		echo "  <td class=\"description column-description\">";
		echo "    <a href=\"" . $_SERVER["REQUEST_URI"] . "&action=manage&id=" . $download->ID . "\">Manage</a> | ";
		echo "    <a href=\"" . $_SERVER["REQUEST_URI"] . "&action=delete&id=" . $download->ID . "\">Delete</a>";
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
		
		echo "<div class=\"form-field\">";
		echo "<label for=\"dcr-labeltext\">Label text</label>";
		echo "<input name=\"dcr-labeltext\" id=\"dcr-labeltext\" type=\"text\" size=\"40\" aria-required=\"true\">";
		echo "<p>The text that goes on the form label, where users will enter their redemption code.</p>";
		echo "</div>";
		
		echo "<div class=\"form-field\">";
		echo "<label for=\"dcr-successtext\">Success Text</label>";
		echo "<textarea name=\"dcr-successtext\" id=\"dcr-successtext\" rows=\"5\" cols=\"40\"></textarea>";
		echo "<p>This is the congratulatory text for succesful redemptions.</p>";
		echo "</div>";
		
		echo "<div class=\"form-field\">";
		echo "<label for=\"dcr-failtext\">Failure Text</label>";
		echo "<textarea name=\"dcr-failtext\" id=\"dcr-failtext\" rows=\"5\" cols=\"40\"></textarea>";
		echo "<p>This text is what is shown when someone enters an invalid code, whether it's already been used or it simply doesn't exist.</p>";
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