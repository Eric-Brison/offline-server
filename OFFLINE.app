<?php
// ---------------------------------------------------------------
// $Id:  $


$app_desc = array(
    "name" => "OFFLINE", //Name
    "short_name" => N_("offline management"), //Short name
    "description" => N_("offline management"), //long description
    "access_free" => "N", //Access free ? (Y,N)
    "icon" => "offline.png", //Icon
    "displayable" => "Y", //Should be displayed on an app list (Y,N)
    "with_frame" => "Y", //Use multiframe ? (Y,N)
    "childof" => "ONEFAM" // instance of ONEFAM application	
);

$app_acl = array()

;
$action_desc = array(
    array(
        "name" => "OFF_ORGANIZER",
        "short_name" => N_("interface to organize offline documents"),
        "acl" => "ONEFAM_READ",
        "root" => "N"
    ),
    
    array(
        "name" => "OFF_DOMAINAPI",
        "short_name" => N_("api domains of current user"),
        "acl" => "ONEFAM_READ",
        "root" => "N"
    ),
    
    array(
        "name" => "OFF_FOLDERLIST",
        "short_name" => N_("list documents in space"),
        "acl" => "ONEFAM_READ",
        "root" => "N"
    ),
    
    array(
        "name" => "OFF_POPUPDOCFOLDER",
        "short_name" => N_("list documents in space"),
        "acl" => "ONEFAM_READ",
        "root" => "N"
    ),
    
    array(
        "name" => "OFF_POPUPLISTFOLDER",
        "short_name" => N_("list documents in space"),
        "acl" => "ONEFAM_READ",
        "root" => "N"
    )
);

?>