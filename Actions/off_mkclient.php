<?php

function off_mkclient(&$action) {
	include_once('OFFLINE/Class.OfflineClientBuilder.php');

	$action->parent->AddJsRef("OFFLINE/Layout/off_mkclient.js");
	$action->parent->AddCssRef("OFFLINE:off_mkclient.css");

	$dest_dir = $action->parent->getParam('OFFLINE_CLIENT_BUILD_OUTPUT_DIR', '');
	
	if( ! is_dir($dest_dir) ) {
		$action->ExitError(sprintf(_("OFFLINE:%s directory not found"), $dest_dir));
		return;
	}
	if( ! is_writable($dest_dir) ) {
		$action->ExitError(sprintf(_("OFFLINE:%s directory not writable"), $dest_dir));
		return;
	}

	$opts = array();

	$customize_dir = $action->parent->getParam('OFFLINE_CLIENT_CUSTOMIZE_DIR', '');
	if( $customize_dir != '' ) {
		$opts['CUSTOMIZE_DIR'] = $customize_dir;
	}

	$ocb = new OfflineClientBuilder($dest_dir, $opts);

	$ret = $ocb->buildAll();
	if( $ret === false ) {
		$action->ExitError(sprintf(_("OFFLINE:client build failed with error: %s"), $ocb->error));
		return;
	}

	return;
}

?>