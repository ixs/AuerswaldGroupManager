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
    if (!$groups[$gnr]["going"]) {
        login_group($gnr, $ext, "going");
    }
    
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
    
    // Update groups again
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