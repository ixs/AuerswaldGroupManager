<?php
/**
 * libAuer: Auerswald API functions for PHP
 *
 * Used for limited control over the Auerswald PBX system
 *
 * @author Andreas Thienemann
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

include "group_manager.cfg.php";

/**
 * The language codes the PBX uses
 *
 * @link: http://192.168.0.240/languages_state
 */
$auer_languages = array(
	0 => "deutsch",
	3 => "français",
	7 => "english",
);
$auer_hw_typ = array(
	0 => "unbekannt",
	1 => "Analog",
	2 => "ISDN",
	3 => "VOIP",
);
$auer_module_type = array(
	0 => 'MODULE_TYPE_NONE',
	4 => 'MODULE_TYPE_8AB',
	8 => 'MODULE_TYPE_4S0',
	10 => 'MODULE_TYPE_8S0',
	12 => 'MODULE_TYPE_8UP0',
	14 => 'MODULE_TYPE_2TSM',
	15 => 'MODULE_TYPE_ETH1',
	36 => 'MODULE_TYPE_S2M',
	56 => 'MODULE_TYPE_VOIP8',
	58 => 'MODULE_TYPE_VOIP16',
	60 => 'MODULE_TYPE_VMF',
	74 => 'MODULE_TYPE_4AB',
	76 => 'MODULE_TYPE_2S0UP0',
	78 => 'MODULE_TYPE_2POTS',
	80 => 'MODULE_TYPE_4DSP',
	128 => 'MODULE_TYPE_CPU',
	254 => 'MODULE_TYPE_UNKNOWN',
);
$auer_hw_type = array(
	0 => 'AUERDB_HW_TYP_UNDEF',
	1 => 'AUERDB_HW_TYP_ANALOG',
	2 => 'AUERDB_HW_TYP_ISDN',
	3 => 'AUERDB_HW_TYP_VOIP',
	4 => 'AUERDB_HW_TYP_TSM',
);
$auer_device_type = array(
	0 => 'AUERDB_TN_TYP_ANALOG_UNSPZ',
	33 => 'AUERDB_TN_TYP_ANRUFBEANTW',
	16 => 'AUERDB_TN_TYP_ISDN_TELEFON',
	34 => 'AUERDB_TN_TYP_COMFORT',
	35 => 'AUERDB_TN_TYP_ISDN_PC',
	36 => 'AUERDB_TN_TYP_COMFORT_DECT',
	37 => 'AUERDB_TN_TYP_SIP_AUER',
	38 => 'AUERDB_TN_TYP_SIP_OTHER',
	39 => 'AUERDB_TN_TYP_FAX',
	40 => 'AUERDB_TN_TYP_IP_DECT_AUER',
	41 => 'AUERDB_TN_TYP_IP_DECT',
	42 => 'AUERDB_TN_TYP_SIP_CFT_2500',
	43 => 'AUERDB_TN_TYP_SIP_1200IP',
	44 => 'AUERDB_TN_TYP_SIP_CFT_C_400',
	45 => 'AUERDB_TN_TYP_IP_M5x0_M7x0',
	46 => 'AUERDB_TN_TYP_SOFTPHONE',
);

/**
 * Generic function to read data from Auerswald PBX
 *
 * @param string $url URL to fetch
 * @param string $username Username to authenticate with
 * @param string $password Password to authenticate with
 * @return string The server response.
 */
function fetch_auer($url, $username, $password) {
	static $storedCookie = null;

	$ch = curl_init();
	curl_setopt_array($ch, array(
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HTTPAUTH => CURLAUTH_DIGEST,
		CURLOPT_USERPWD => "$username:$password",
		CURLOPT_COOKIE => $storedCookie ? "AUERSessionID=$storedCookie" : '',
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_SSL_VERIFYHOST => false,
	));

	$response = curl_exec($ch);

	if (curl_errno($ch)) {
		echo 'Error: ' . curl_error($ch);
		exit();
	}

	curl_close($ch);

	if (preg_match('/^Set-Cookie:\s*AUERSessionID=([^;]*)/mi', $response, $matches)) {
		$storedCookie = $matches[1];
	}

	return $response;
}


/**
 * Performs an HTTP POST request with digest authentication and cookie handling.
 *
 * @param string $url The URL to which the request will be sent.
 * @param string $username The username for HTTP authentication.
 * @param string $password The password for HTTP authentication.
 * @param array $data The data to be sent in the POST request.
 * @return string The response from the server.
 */
function post_auer($url, $username, $password, $data) {
	static $storedCookie = null;

	$ch = curl_init();

	// The Auerswald device seems to need the bang character unencoded.
	$postData = http_build_query($data, "", "&", PHP_QUERY_RFC3986);
	$postData = str_replace("%21", "!", $postData);

	curl_setopt_array($ch, array(
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HTTPAUTH => CURLAUTH_DIGEST,
		CURLOPT_USERPWD => "$username:$password",
		CURLOPT_COOKIE => $storedCookie ? "AUERSessionID=$storedCookie" : '',
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_SSL_VERIFYHOST => false,
        	CURLOPT_POST => true,
		CURLOPT_HTTPHEADER => array('Content-Type: application/x-www-form-urlencoded;charset=UTF-8'),
		CURLOPT_POSTFIELDS => $postData,
	));

	$response = curl_exec($ch);

	if (curl_errno($ch)) {
		echo 'Error: ' . curl_error($ch);
		exit();
	}

	curl_close($ch);

	if (preg_match('/^Set-Cookie:\s*AUERSessionID=([^;]*)/mi', $response, $matches)) {
		$storedCookie = $matches[1];
	}

	return $response;
}

/**
 * Get all internal users/extensions
 *
 * @return mixed[]
 */
function get_extensions() {
	global $auer_address, $auer_admin_user, $auer_admin_pass, $auer_hw_typ;

	$devices = array();

	foreach (json_decode(fetch_auer(sprintf("https://%s/devices_state", $auer_address),
		$auer_admin_user, $auer_admin_pass))->{"rows"} as $row) {
		$device = array();

		// CELL_DEV_ID 0
		$device["id"] = $row->{"data"}[0];
		// CELL_DEV_TYPE 1
		switch ($row->{"data"}[1]) {
			case 0:
				$device["typ"] = "Teilnehmer";
				break;
			case 1:
				$device["typ"] = "Türstation";
				break;
		}
		// CELL_DEV_NR 2
		$device["nr"] = $row->{"data"}[2];
		// CELL_DEV_NAME 3
		$device["name"] = $row->{"data"}[3];
		// CELL_DEV_MODULE 4
		// $device["modulePort"] = $row->{"data"}[4];
		$device["modulePort"] = $row->{"userdata"}->{"module"};
		// CELL_DEV_HWTYPE 5
		// $device["hw"] = $row->{"data"}[5];
		$device["hw"] = $auer_hw_typ[$row->{"userdata"}->{"hwTyp"}] ?? $auer_hw_typ[0];
		$device["date"] = "";
		// CELL_DEV_SYSTEL
		$device["systel"] = $row->{"data"}[6];
		// CELL_DEV_STATE
		// $device["state"] = $row->{"data"}[7];
		$device["state"] = array();

		$device["userdata"] = (array)$row->{"userdata"};
		$devices[] = $device;
	}

	usort($devices, function ($a, $b) { return $a['nr'] - $b['nr'];	});

	return $devices;
}

/**
 * Get info about an extension identified by IP address
 *
 * @param string $ip The IP address of the VOIP phone
 * @return mixed[]|false The single extension information
 */
function find_extension_by_ip($ip) {
	foreach (get_extensions() as $k => $v) {
		if ($v["userdata"]["ipAddr"] == $ip) {
			return $v;
		}
	}
	return false;
}

/**
 * Get info about an extension identified by DECT IPUI
 *
 * This only works on a COMfortel WS-500 system
 *
 * @param string $ipui The IPUI address of the DECT phone
 * @return mixed[]|false The single extension information
 */
function find_extension_by_ipui($ipui) {
	foreach (get_extensions() as $k => $v) {
		if ($v["userdata"]["ipui"] == $ipui) {
			return $v;
		}
	}
	return false;
}

/**
 * Get groups configuration from PBX
 *
 * @return mixed[] The groups configuration from the PBX.
 */
function get_groups_configuration() {
	global $auer_address, $auer_admin_user, $auer_admin_pass;

	$groups = array();

	foreach (json_decode(fetch_auer(sprintf("https://%s/cfg_data_gruppen", $auer_address),
		$auer_admin_user, $auer_admin_pass)) as $row) {
		$groups[$row->{"nr"}] = array("id" => $row->{"id"}, "name" => $row->{"name"});
	}

	return $groups;
}

/**
 * Get group members from PBX
 *
 * @param int $gid Group ID
 * @return mixed[]
 */
function get_group_members($gid) {
	global $auer_address, $auer_admin_user, $auer_admin_pass;

	$members = array();

	foreach (json_decode(fetch_auer(sprintf("https://%s/gruppetnzuordnung_tngrp?grpId=%d", $auer_address, $gid),
			$auer_admin_user, $auer_admin_pass))->{"rows"} as $mb) {
		$member = array(
			"id" => $mb->{"data"}[0],
			"name_raw" => $mb->{"data"}[1],
			"type" => null,
			"nr" => null,
			"name" => null,
			"prio" => $mb->{"data"}[3],
			"coming" => $mb->{"data"}[4],
			"going" => $mb->{"data"}[5],
			"delay" => $mb->{"data"}[6],
		);
		list($member["type"], $tmp) = explode(":", $mb->{"data"}[1]);
		list($member["nr"], $member["name"]) = array_map("trim", explode("|", $tmp));
		$members[$member["nr"]] = $member;
	}
	return $members;
}

/**
 * Get a groups overview for an extension number
 *
 * @param int $extension The pbx extension to fetch group info for.
 * @return mixed[] The groups info for the specific extension.
 */
function get_groups_for_extension($extension) {
	global $auer_address, $auer_admin_user, $auer_admin_pass, $auer_hw_typ;

	foreach (json_decode(fetch_auer(sprintf("https://%s/tngrpuebersicht_state", $auer_address),
		$auer_admin_user, $auer_admin_pass))->{"rows"} as $row) {

		$id = $row->{"id"};
		list($nr, $name) = array_map("trim", explode("|", $row->{"data"}[0]));
		$g_nrs = array();
		$g_names = array();
		foreach (explode("<br>", $row->{"data"}[1]) as $group) {
			list($g_nr, $g_name) = array_map("trim", explode("|", $group));
			$g_nrs[] = $g_nr;
			$g_names[] = $g_name;
		}
		// Translate german strings into boolean values
		$coming = array_map(function($value) {
				return $value === "Eingeloggt" ? true : false;
			}, explode("<br>", $row->{"data"}[2]));
		$going = array_map(function($value) {
				return $value === "Eingeloggt" ? true : false;
			}, explode("<br>", $row->{"data"}[3]));

		// Combine data into $groups array
		$groups = array();
		for ($i = 0; $i < count($g_nrs); $i++) {
			$groups[$g_nrs[$i]] = array(
				"name" => $g_names[$i],
				"coming" => $coming[$i],
				"going" => $going[$i],
			);
		}
		if ($nr == $extension) {
			return $groups;
		}
	}
}

/**
 * Log into a group
 *
 * @param int $group Group number to log into.
 * @param int $extension The extension that should be logged in.
 * @param string $direction Direction to log into for _coming_ or _going_.
 * @param int $state Whether to be logged out (0), logged in (1) or fixed (2).
 * @param bool $delay Ring delay. (not implemented)
 * return bool
 */
function _group_update($group, $extension, $direction, $state, $delay=false) {
	global $auer_address, $auer_admin_user, $auer_admin_pass;

	$groups = get_groups_configuration();
	$gid = $groups[$group]["id"];

	$mb = get_group_members($gid)[$extension] or die("Extension not found...");
	$mbid = $mb["id"];

	$data = array(
		"${mbid}_gr_id" => $mbid,
		"${mbid}_btnup" => "",
		"${mbid}_rufnName" => $mb["name_raw"],
		"${mbid}_btndown" => "",
		"${mbid}_prio" => $mb["prio"],
		"${mbid}_grpKomEingeloggt" => $mb["coming"],
		"${mbid}_grpGehEingeloggt" => $mb["going"],
		"${mbid}_klingelVerz" => $mb["delay"],
		"${mbid}_realTn" => $mb["type"] == "Tn" ? 1 : 0,
		"${mbid}_!nativeeditor_status" => "updated",
		"ids" => $mbid,
	);

	if ($direction == "going") {
		$data["${mbid}_grpGehEingeloggt"] = $state;
	} elseif ($direction == "coming") {
		$data["${mbid}_grpKomEingeloggt"] = $state;
	} else {
		die("Incorrect destination");
	}

	post_auer(sprintf("https://%s/gruppetnzuordnung_save?grpId=%d", $auer_address, $gid),
                        $auer_admin_user, $auer_admin_pass, $data) or die("Error updating");

	return true;
}

/**
 * Log-In to a group
 *
 * @param int $group Group extension numbers
 * @param int $extension Extension number
 * @param string $direction _coming_ or _going_ group.
 * @return bool
 */
function login_group($group, $extension, $direction) {
	return _group_update($group, $extension, $direction, 1);
}

/**
 * Log-Out of a group
 *
 * @param int $group Group extension numbers
 * @param int $extension Extension number
 * @param string $direction _coming_ or _going_ group.
 * @return bool
 */
function logout_group($group, $extension, $direction) {
	return _group_update($group, $extension, $direction, 0);
}
