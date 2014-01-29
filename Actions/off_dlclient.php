<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

function off_dlclient(\Action & $action)
{
    include_once ('OFFLINE/Class.OfflineClientBuilder.php');
    
    $action->parent->AddJsRef("OFFLINE/Layout/off_dlclient.js");
    $action->parent->AddCssRef("CORE:welcome.css", true);
    $action->parent->AddCssRef("OFFLINE:off_dlclient.css", true);
    
    $action->lay->set("thisyear", strftime("%Y", time()));
    $action->lay->set("userRealName", $action->user->firstname . " " . $action->user->lastname);
    /* Get parameters */
    $parms = array();
    $parms['os'] = getHttpVars('os', 'none');
    $parms['arch'] = getHttpVars('arch', 'none');
    
    $clientDir = $action->parent->getParam('OFFLINE_CLIENT_BUILD_OUTPUT_DIR', '');
    if (!is_dir($clientDir)) {
        $action->ExitError(sprintf('OFFLINE_CLIENT_BUILD_OUTPUT_DIR: ' . _("OFFLINE:%s directory not found") , $clientDir));
        return null;
    }
    $clientDir = realpath($clientDir);
    // 3rd parties developpement
    $fext = $action->getLayoutFile("off_externals.xml");
    $action->lay->set("HAVE_EXTERNALS", false);
    $trd = array();
    if (file_exists($fext)) {
        $fdata = file($fext);
        foreach ($fdata as $k => $v) {
            $action->lay->set("HAVE_EXTERNALS", true);
            $ds = explode("#", $v);
            $trd[] = array(
                "site" => $ds[0],
                "name" => $ds[1],
                "license" => $ds[2]
            );
        }
    }
    $action->lay->setBlockData("EXTERNALS", $trd);
    /**
     * os/arch download requested?
     */
    if ($parms['os'] != 'none' && $parms['arch'] != 'none') {
        return sendClient($action, $parms['os'], $parms['arch']);
    }
    /**
     * Create list of os/arch clients available to download
     */
    $ocb = new \Dcp\Offline\OfflineClientBuilder();
    $osArchList = $ocb->getOsArchList();
    
    $action->lay->set("version", $ocb->getOfflineInfo('Version'));
    $action->lay->set("buildid", $ocb->getOfflineInfo('BuildID'));
    
    $dl_list = array();
    foreach ($osArchList as & $spec) {
        $file = sprintf('%s/%s', $clientDir, $spec['file']);
        if (!is_file($file)) {
            continue;
        }
        $dl_list[] = array(
            'DL_OS' => htmlspecialchars($spec['os']) ,
            'DL_ARCH' => htmlspecialchars($spec['arch']) ,
            'DL_TITLE' => htmlspecialchars($spec['title']) ,
            'DL_ICON' => $spec['icon']
        );
    }
    unset($spec);
    
    $action->lay->setBlockData('DL_LIST', $dl_list);
    
    return null;
}

function sendClient(\Action & $action, $os, $arch)
{
    include_once ('OFFLINE/Class.OfflineClientBuilder.php');
    
    $clientDir = $action->parent->getParam('OFFLINE_CLIENT_BUILD_OUTPUT_DIR', '');
    if (!is_dir($clientDir)) {
        $action->ExitError(sprintf('OFFLINE_CLIENT_BUILD_OUTPUT_DIR: ' . _("OFFLINE:%s directory not found") , $clientDir));
        return null;
    }
    $clientDir = realpath($clientDir);
    
    $ocb = new \Dcp\Offline\OfflineClientBuilder();
    $osArchList = $ocb->getOsArchList();
    
    $filename = '';
    foreach ($osArchList as & $spec) {
        if ($spec['os'] == $os && $spec['arch'] == $arch) {
            $filename = $spec['file'];
            break;
        }
    }
    unset($spec);
    
    if ($filename != '') {
        $sendFile = sprintf('%s/%s', $clientDir, $filename);
        if (!is_file($sendFile)) {
            $action->ExitError(sprintf(_("OFFLINE:File '%s' not found") , $sendFile));
            return null;
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
    $action->ExitError(sprintf(_("OFFLINE:File not found for '%s'") , sprintf("{os:'%s', arch:'%s'}", $os, $arch)));
    return null;
}
