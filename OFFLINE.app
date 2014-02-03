<?php

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

$app_acl = array(
	array(
		"name" => "OFF_DLCLIENT",
		"description" =>N_("OFFLINE:access list of clients to download"),
		"group_default"  =>"Y"),
    array(
        "name" => "OFF_UPDATE",
        "description" =>N_("OFFLINE:access client update"),
        "group_default" =>"Y")
);

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
    ),
    
    array(
        "name" => "OFF_DLCLIENT",
        "short_name" => N_("OFFLINE:list of clients to download"),
        "acl" => "OFF_DLCLIENT",
        "root" => "N"
    ),
    
    array(
        "name" => "OFF_UPDATE",
        "short_name" => N_("OFFLINE:client update"),
        "acl" => "OFF_UPDATE",
        "root" => "N"
    )
);
