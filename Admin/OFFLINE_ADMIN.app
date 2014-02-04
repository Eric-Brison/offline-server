<?php

$app_desc = array(
    "name" => "OFFLINE_ADMIN", //Name
    "short_name" => N_("offline admin"), //Short name
    "description" => N_("offline admin"), //long description
    "access_free" => "N", //Access free ? (Y,N)
    "icon" => "offline_admin.png", //Icon
    "displayable" => "Y", //Should be displayed on an app list (Y,N)
    "with_frame" => "Y", //Use multiframe ? (Y,N)
    "tag" => "ADMIN SYSTEM"
);

$app_acl = array(
	array(
		"name" => "ADMIN",
		"description" =>N_("OFFLINE:access client build"),
		"group_default" =>"N"
    )
);

$action_desc = array(
    array(
        "name" => "ADMIN",
        "short_name" => N_("OFFLINE:build clients"),
        "acl" => "ADMIN",
        "root" => "Y"
    )
);
