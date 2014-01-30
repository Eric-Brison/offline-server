<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

function off_update(&$action)
{
    $parms = array();
    $parms['download'] = getHttpVars('download', '');
    $parms['version'] = getHttpVars('version', '');
    $parms['buildid'] = getHttpVars('buildid', '');
    $parms['os'] = getHttpVars('os', '');
    $parms['arch'] = getHttpVars('arch', '');
    
    $parms['clientDir'] = $action->parent->getParam('OFFLINE_CLIENT_BUILD_OUTPUT_DIR', '');
    if (!is_dir($parms['clientDir'])) {
        http_400();
        $err = sprintf(sprintf('OFFLINE_CLIENT_BUILD_OUTPUT_DIR: ' . _("OFFLINE:%s directory not found") , $parms['clientDir']));
        error_log(__METHOD__ . " " . $err);
        print $err;
        exit(0);
    }
    $parms['clientDir'] = realpath($parms['clientDir']);
    
    switch ($parms['download']) {
        case 'update':
            off_update_download_update($parms, $action);
            break;

        case 'complete':
            off_update_download_complete($parms, $action);
            break;

        case 'partial':
            off_update_download_partial($parms, $action);
            break;

        default:
            off_update_unknown_download($parms, $action);
    }
}

function http_400()
{
    header('HTTP/1.1 400 Bad Request');
    header('Content-Type: text/plain');
}

function http_404()
{
    header('HTTP/1.1 404 Not Found');
    header('Content-Type: text/plain');
}

function parseInfo($infoFile)
{
    if (!is_file($infoFile)) {
        return array();
    }
    $info = file_get_contents($infoFile);
    if ($info === false) {
        return array();
    }
    $lines = preg_split('/\n/', $info);
    $parsedInfo = array();
    foreach ($lines as & $line) {
        $line = trim($line);
        if (preg_match('/^(?<key>[^=]+)=(?<value>.*)$/', $line, $m)) {
            $parsedInfo[$m['key']] = $m['value'];
        }
    }
    unset($line);
    return $parsedInfo;
}

function sendFile($type, $filename)
{
    if (!is_file($filename)) {
        http_404();
        $err = sprintf("File '%s' not found.", $filename);
        error_log(__METHOD__ . " " . $err);
        return $err;
    }
    
    $fh = fopen($filename, 'r');
    if ($fh === false) {
        http_404();
        $err = sprintf("Error opening file '%s'.", $filename);
        error_log(__METHOD__ . " " . $err);
        return $err;
    }
    
    header('HTTP/1.1 200 OK');
    header('Content-Type: ' . $type);
    header('Content-Length: ' . filesize($filename));
    fpassthru($fh);
    fclose($fh);
    return '';
}

function off_update_unknown_download(&$parms, &$action)
{
    http_400();
    $err = sprintf("Unknown download '%s'.", $parms['download']);
    error_log(__METHOD__ . " " . $err);
    print $err;
    exit(0);
}

function off_update_download_update(&$parms, &$action)
{
    $buildList = getBuildFor($parms['os'], $parms['arch']);
    if (count($buildList) <= 0) {
        http_404();
        $err = sprintf("No builds found for {os='%s', arch='%s'}.", $parms['os'], $parms['arch']);
        error_log(__METHOD__ . " " . $err);
        print $err;
        exit(0);
    }
    
    $core_externurl = getParam('CORE_EXTERNURL');
    
    $build = array_shift($buildList);
    
    $completeInfoFile = sprintf('%s/%s.complete.mar.info', $parms['clientDir'], $build['mar_basename']);
    $completeInfo = parseInfo($completeInfoFile);
    
    $partialInfoFile = sprintf('%s/%s.partial.mar.info', $parms['clientDir'], $build['mar_basename']);
    $partialInfo = parseInfo($partialInfoFile);
    
    $xml = "";
    $xml.= "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    $xml.= "<updates xmlns=\"http://www.mozilla.org/2005/app-update\">\n";
    $xml.= sprintf("<update type=\"major\" version=\"%s\" extensionVersion=\"%s\" buildID=\"%s\" detailsURL=\"%s\" >\n", isset($completeInfo['version']) ? $completeInfo['version'] : '', isset($completeInfo['version']) ? $completeInfo['version'] : '', isset($completeInfo['buildid']) ? $completeInfo['buildid'] : '', sprintf("%s?app=OFFLINE&amp;action=OFF_DLCLIENT", $core_externurl));
    
    if (isset($partialInfo['version_from']) && isset($partialInfo['buildid_from']) && $parms['version'] == $partialInfo['version_from'] && $parms['buildid'] == $partialInfo['buildid_from']) {
        /* Serve the partial update only if the client match the partial update */
        $xml.= sprintf("  <patch type=\"partial\" URL=\"%s\" hashFunction=\"%s\" hashValue=\"%s\" size=\"%s\" />\n", sprintf("%sguest.php?app=OFFLINE&amp;action=OFF_UPDATE&amp;download=partial&amp;version=%%VERSION%%&amp;buildid=%%BUILD_ID%%&amp;os=%s&amp;arch=%s", $core_externurl, $parms['os'], $parms['arch']) , isset($partialInfo['hashfunction']) ? $partialInfo['hashfunction'] : '', isset($partialInfo['hashvalue']) ? $partialInfo['hashvalue'] : '', isset($partialInfo['size']) ? $partialInfo['size'] : '');
    }
    /* Serve the complete update */
    $xml.= sprintf("  <patch type=\"complete\" URL=\"%s\" hashFunction=\"%s\" hashValue=\"%s\" size=\"%s\" />\n", sprintf("%sguest.php?app=OFFLINE&amp;action=OFF_UPDATE&amp;download=complete&amp;version=%%VERSION%%&amp;buildid=%%BUILD_ID%%&amp;os=%s&amp;arch=%s", $core_externurl, $parms['os'], $parms['arch']) , isset($completeInfo['hashfunction']) ? $completeInfo['hashfunction'] : '', isset($completeInfo['hashvalue']) ? $completeInfo['hashvalue'] : '', isset($completeInfo['size']) ? $completeInfo['size'] : '');
    
    $xml.= "</update>\n";
    $xml.= "</updates>\n";
    
    header('HTTP/1.1 200 OK');
    header('Content-Type: text/xml');
    header('Content-Length: ' . strlen($xml));
    echo $xml;
    exit(0);
}

function off_update_download_complete(&$parms, &$action)
{
    $buildList = getBuildFor($parms['os'], $parms['arch']);
    if (count($buildList) <= 0) {
        http_404();
        $err = sprintf("No builds found for {os='%s', arch='%s'}.", $parms['os'], $parms['arch']);
        error_log(__METHOD__ . " " . $err);
        print $err;
        exit(0);
    }
    
    $build = array_shift($buildList);
    
    $completeMar = sprintf('%s/%s.complete.mar', $parms['clientDir'], $build['mar_basename']);
    if (!is_file($completeMar)) {
        http_404();
        $err = sprintf("Complete MAR '%s' not found.", $completeMar);
        error_log(__METHOD__ . " " . $err);
        exit(0);
    }
    
    sendFile('application/binary', $completeMar);
    exit(0);
}

function off_update_download_partial(&$parms, &$action)
{
    $buildList = getBuildFor($parms['os'], $parms['arch']);
    if (count($buildList) <= 0) {
        http_404();
        $err = sprintf("No builds found for {os='%s', arch='%s'}.", $parms['os'], $parms['arch']);
        error_log(__METHOD__ . " " . $err);
        print $err;
        exit(0);
    }
    
    $build = array_shift($buildList);
    
    $partialMar = sprintf('%s/%s.partial.mar', $parms['clientDir'], $build['mar_basename']);
    
    sendfile('application/binary', $partialMar);
    exit(0);
}

function getBuildFor($os, $arch)
{
    include_once ('OFFLINE/Class.OfflineClientBuilder.php');
    
    $ocb = new \Dcp\Offline\OfflineClientBuilder();
    $buildList = $ocb->getOsArchList();
    
    $res = array();
    foreach ($buildList as & $build) {
        if ($build['os'] == $os && $build['arch'] == $arch) {
            $res[] = $build;
        }
    }
    unset($build);
    
    return $res;
}
