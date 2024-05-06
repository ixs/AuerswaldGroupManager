<?php
/**
* Auerswald GroupManager
*
* Control group subscriptions for Gigaset based COMfortel DECT phones
* via the xhtml/RAP interface.
*
* @author Andreas Thienemann
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*/

include("librap.php");
include("libAuer.php");

rap_authenticate();
$client = parse_rap_get();

// Auerswald PBX communication
$ext = find_extension_by_ipui(strtolower($client["ipui"]))["nr"];
$groups = get_groups_for_extension($ext);
$group_cfg = get_groups_configuration();

// Login/Logout actions
if (array_key_exists("o", $_GET)) {
    $gnr = (int)$_GET["o"];
    // Special case, we want to log out of all groups
    if ($gnr == "none") {
        foreach ($groups as $gnr => $group) {
            if ($group["going"]) {
                logout_group($gnr, $ext, "going");
            }
        }
    // Else iterate normaly and log into the specific group,
    // which will automatically log out other groups.
    } elseif (!$groups[$gnr]["going"]) {
        login_group($gnr, $ext, "going");
    }
    
    // Handle incoming groups, multiple can be selected.
    foreach ($groups as $gnr => $group) {
        if (array_key_exists("i${gnr}", $_GET)) {
            if (!$group["coming"]) {
                login_group($gnr, $ext, "coming");
            }
        } else {
            if ($group["coming"]) {
                logout_group($gnr, $ext, "coming");
            }
        }
    }
    
    // Update groups again as they might have changed.
    $groups = get_groups_for_extension($ext);
}

?>
<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//OMA//DTD XHTML Mobile 1.2//EN"
  "http://www.openmobilealliance.org/tech/DTD/xhtml-mobile12.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
 <head>
  <title><?= lang("__gm_title") ?> <?= lang("__gm_extension") ?> <?= $ext ?></title>
 </head>
 <body>
  <form action="<?= $_SERVER["SCRIPT_NAME"] ?>" method="get">
   <p style="font-weight:bold"><?= lang("__gm_outgoing") ?></p>
<?php
// Always offer a no group option for outgoing calls to fall back to the handset defaults.
$checked = !in_array(true, array_column($groups, 'going')) ? ' checked="checked"' : '';
echo '   <p>'.lang("__gm_nogroup").' <input type="radio" name="o" value="none"' . $checked . '/></p>'."\n";

foreach ($groups as $gnr => $data) {
    $checked = $data['going'] === true ? ' checked="checked"' : '';
    echo "   <p>$data[name] <input type=\"radio\" name=\"o\" value=\"$gnr\"$checked/></p>\n";
}
?>
   <p></p>
   <p style="font-weight:bold"><?= lang("__gm_incoming") ?></p>
<?php
foreach ($groups as $gnr => $data) {
    $checked = $data['coming'] === true ? ' checked="checked"' : '';
    echo "   <p>$data[name] <input type=\"checkbox\" name=\"i${gnr}\" value=\"{$gnr}\"$checked/></p>\n";
}
?>
   <p><input type="submit" value="<?= lang("__gm_send_form") ?>" /></p>
  </form>
 </body>
</html>
