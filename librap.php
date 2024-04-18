<?php
/**
 * RAP protocol related functions
 *
 * @author Andreas Thienemann
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */


include "group_manager.cfg.php";

/**
 * Authenticate clients
 * 
 * @todo Actually implement authentication, now it's just a stub
 * @return void
 */
function rap_authenticate() {
	global $ws_address;

	// For now just do a simple client_ip check
	if ($_SERVER["CLIENT_IP"] == $ws_address) {
		return true;
	}

	echo <<< EOF
	<?xml version="1.0" encoding="utf-8"?>
	<!DOCTYPE html PUBLIC "-//OMA//DTD XHTML Mobile 1.2//EN"
	  "http://www.openmobilealliance.org/tech/DTD/xhtml-mobile12.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	 <head>
	  <title>Permission denied for ${_SERVER['REMOTE_ADDR']}</title>
	 </head>
	 <body>
	  <p>Authentication needed</p>
	 </body>
	</html>

	EOF;
	exit();
}

/**
* Parse the GET string and extract mobile handset information
*
* @return array The handset information available.
*/
function parse_rap_get() {
	//lang=2&tz=0&mac=000952071D0A&cc=49&handsetid=035593B06E&sipid=720&provid=1

	$rap_languages = array(
		0 => "Undefined",
		1 => "US",
		2 => "German",
		3 => "English International",
	);

	$lang = $rap_languages[$_GET["lang"]] ?? "Undefined";
	$base_mac = $_GET["mac"] ?? "";
	$ipui = $_GET["handsetid"] ?? "";
	$sip_id = $_GET["sipid"] ?? "";

	return array(
		"language" => $lang,
		"basestation_mac" => $base_mac,
		"ipui" => $ipui,
		"number" => $sip_id,
	);
}

/**
* Simple localization support for strings.
*
* @param string index The string identifier to localize
* @return string The localized string.
*/
function lang($index) {
	$lang = parse_rap_get();
	$lang = $lang["language"];

	// Default is US
	if ($lang != "US" && $lang != "German") {
		$lang = "US";
	}

	$words = array(
		"US" => array(
			"__gm_title" => "Groups",
			"__gm_available" => "Groups available",
			"__gm_incoming" => "Incoming groups",
			"__gm_outgoing" => "Outgoing group",
			"__gm_extension" => "ext.",
			"__gm_send_form" => "Send",
		),
		"German" => array(
			"__gm_title" => "Gruppen",
			"__gm_available" => "Verfügbare Gruppen",
			"__gm_incoming" => "Kommende Gruppen",
			"__gm_outgoing" => "Gehende Gruppe",
			"__gm_extension" => "Tn.",
			"__gm_send_form" => "Senden",
		),
	);

	return isset($words[$lang][$index]) ?
			$words[$lang][$index] :
			"unknown";
}