<?php
/**
 * Return offline domains where current user is affected
 *
 * @author Anakeen
 * @version $Id:  $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package OFFLINE
 * @subpackage
 */
/**
 */

include_once ("OFFLINE/Class.DomainApi.php");

/**
 * View folders and document for exchange them
 * @param Action &$action current action
 */
function off_domainapi(Action &$action)
{
    $method = $action->getArgument("method");
    $id = $action->getArgument("id");
    $use = $action->getArgument("use");
    $redirect = $action->getArgument("htmlRedirect");

    $out = new stdClass();
    $out->error = '';
    if (method_exists("DomainApi", $use ? $use : $method)) {
        if ($id) {
            $domain = new_doc($action->dbaccess, $id);
            if ((!$domain->isAlive()) || ($domain->control('view') != "")) {
                $out->error = sprintf("unknow domain %s ", $id);
                $domain = null;
            }
        } else {
            $domain = null;
        }
        if (!$out->error) {
            try {
                $apiDomain = new DomainApi($domain);
                if ($action->getArgument("use")) {
                    $apiDomain = $out = call_user_func(array(
                        $apiDomain,
                        $action->getArgument("use")
                    ));
                    if (!method_exists($apiDomain, $method)) {
                        $out->error = sprintf("method %s::%s not registered",get_class($apiDomain), $method);
                    }
                } else {
                
                }
                if (!$out->error) {
                    $aconfig=array_merge($_GET, $_POST);
                    $config=new stdClass();
                    $strip = get_magic_quotes_gpc();
                    foreach ($aconfig as $k=>$v) {
                        if ($strip) $v=stripslashes($v);
                        $vd=json_decode($v);
                        $config->$k=$vd?$vd:$v;
                    }
                    $out = call_user_func(array(
                        $apiDomain,
                        $method
                    ), $config);
                   
                }
            } catch ( Exception $e ) {
                $out->error = $e->getMessage();
                $out->errorContext=sprintf("exception in method %s",  $method);
            }
        }
    } else {
        $out->error = sprintf("method %s not registered", $method);
    }
    if ($redirect) {
        if ($out->error) $action->addWarningMsg($out->error);
         redirect($action, 'FDL','FDL_CARD&latest=Y&refreshfld=Y&id='.$redirect);
    } else {
    $action->lay->template = json_encode($out);
    $action->lay->noparse = true; // no need to parse after - increase performances
    }
}
