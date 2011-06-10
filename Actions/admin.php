<?php

function admin(&$action) {
	$action->parent->AddJsRef("OFFLINE/Layout/admin.js");
	$action->parent->AddCssRef("OFFLINE:admin.css");

	$command = getHttpVars('command', '');

	switch($command) {
		case 'build':
			return _admin_build($action);
			break;
	}

	$action->lay->set('STATUS_CLASS', 'nop');
	$action->lay->set('STATUS_MESSAGE', '');
	$action->lay->set('ERROR_LOG', '');

	return;
}

function _admin_build(&$action) {
	include_once('OFFLINE/Class.OfflineClientBuilder.php');

	$dest_dir = $action->parent->getParam('OFFLINE_CLIENT_BUILD_OUTPUT_DIR', '');
	if( ! is_dir($dest_dir) ) {
		$action->ExitError(sprintf(_("OFFLINE:%s directory not found"), $dest_dir));
		return;
	}
	if( ! is_writable($dest_dir) ) {
		$action->ExitError(sprintf(_("OFFLINE:%s directory not writable"), $dest_dir));
		return;
	}
	$dest_dir = realpath($dest_dir);

	$opts = array();

	$customize_dir = $action->parent->getParam('OFFLINE_CLIENT_CUSTOMIZE_DIR', '');
	if( $customize_dir != '' ) {
		if( ! is_dir($customize_dir) ) {
			$action->ExitError(sprintf(_("OFFLINE:%s directory not found"), $customize_dir));
			return;
		}
		$customize_dir = realpath($customize_dir);
	}
	$opts['CUSTOMIZE_DIR'] = $customize_dir;

	$ocb = new OfflineClientBuilder($dest_dir, $opts);

	$ret = $ocb->buildAll();
	if( $ret === false ) {
		$action->lay->set('STATUS_CLASS', 'error');
		$action->lay->set('STATUS_MESSAGE', 'Error');
		$action->lay->set('ERROR_LOG', htmlspecialchars($ocb->error));
		return;
	}

	$action->lay->set('STATUS_CLASS', 'success');
	$action->lay->set('STATUS_MESSAGE', 'OK');
	$action->lay->set('ERROR_LOG', '');
	return;
}

?>