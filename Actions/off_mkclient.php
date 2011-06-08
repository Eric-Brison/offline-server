<?php

function off_mkclient(&$action) {
	include_once('OFFLINE/Class.OFflineClientBuilder.php');

	$dest_dir = $action->parent->getParam('OFFLINE_CLIENT_BUILD_OUTPUT_DIR', '');
	
	if( ! is_dir($dest_dir) ) {
		$action->ExitError(sprintf(_("OFFLINE:%s directory not found"), $dest_dir));
		return;
	}
	if( ! is_writable($dest_dir) ) {
		$action->ExitError(sprintf(_("OFFLINE:%s directory not writable"), $dest_dir));
		return;
	}

	$ocb = new OfflineClientBuilder($dest_dir);

	$ret = $ocb->buildAll();
	if( $ret === false ) {
		$action->ExitError(sprintf(_("OFFLINE:client build failed with error: %s"), $ocb->error));
		return;
	}

	$action->ExitError(sprintf("OK"));
	return;
}

?>