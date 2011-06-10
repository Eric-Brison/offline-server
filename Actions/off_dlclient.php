<?php

function off_dlclient(&$action) {
	include_once('OFFLINE/Class.OfflineClientBuilder.php');

	$action->parent->AddJsRef("OFFLINE/Layout/off_dlclient.js");
	$action->parent->AddCssRef("OFFLINE:off_dlclient.css");

	/* Get parameters */
	$parms = array();
	$parms['os'] = getHttpVars('os', 'none');
	$parms['arch'] = getHttpVars('arch', 'none');

	$clientDir = $action->parent->getParam('OFFLINE_CLIENT_BUILD_OUTPUT_DIR', '');
	if( ! is_dir($clientDir) ) {
		$action->ExitError(sprintf('OFFLINE_CLIENT_BUILD_OUTPUT_DIR: '._("OFFLINE:%s directory not found"), $clientDir));
		return;
	}
	$clientDir = realpath($clientDir);

	/**
	 * os/arch download requested?
	 */
	if( $parms['os'] != 'none' && $parms['arch'] != 'none' ) {
		return sendClient($action, $parms['os'], $parms['arch']);
	}

	/**
	 * Create list of os/arch clients available to download
	 */
	$ocb = new OfflineClientBuilder();
	$osArchList = $ocb->getOsArchList();

	$dl_list = array();
	foreach( $osArchList as &$spec ) {
		$file = sprintf('%s/%s', $clientDir, $spec['file']);
		if( ! is_file($file) ) {
			continue;
		}
		$dl_list []= array(
			'DL_OS' => htmlspecialchars($spec['os']),
			'DL_ARCH' => htmlspecialchars($spec['arch']),
			'DL_TITLE' => htmlspecialchars($spec['title']),
			'DL_ICON' => $spec['icon']
		);
	}
	unset($spec);

	$action->lay->setBlockData('DL_LIST', $dl_list);
	
	return;
}

function sendClient(&$action, $os, $arch) {
	include_once('OFFLINE/Class.OfflineClientBuilder.php');

	$clientDir = $action->parent->getParam('OFFLINE_CLIENT_BUILD_OUTPUT_DIR', '');
	if( ! is_dir($clientDir) ) {
		$action->ExitError(sprintf('OFFLINE_CLIENT_BUILD_OUTPUT_DIR: '._("OFFLINE:%s directory not found"), $clientDir));
		return;
	}
	$clientDir = realpath($clientDir);
	
	$ocb = new OfflineClientBuilder();
	$osArchList = $ocb->getOsArchList();

	$filename = '';
	foreach( $osArchList as &$spec ) {
		if( $spec['os'] == $os && $spec['arch'] == $arch ) {
			$filename = $spec['file'];
			break;
		}
	}
	unset($spec);

	if( $filename != '' ) {
		$sendFile = sprintf('%s/%s', $clientDir, $filename);
		if( ! is_file($sendFile) ) {
			$action->ExitError(sprintf(_("OFFLINE:File '%s' not found"), $sendFile));
			return;
		}
		$fileSize = filesize($sendFile);
		$fh = fopen($sendFile, 'rb');
		header(sprintf('Content-Type: application/binary'));
		header(sprintf('Content-Length: %s', $fileSize));
		header(sprintf('Content-Disposition: attachment; filename=%s', $filename));
		fpassthru($fh);
		fclose($fh);
		exit;
	}
	$action->ExitError(sprintf(_("OFFLINE:File not found for '%s'"), sprintf("{os:'%s', arch:'%s'}", $os, $arch)));
	return;
}

?>