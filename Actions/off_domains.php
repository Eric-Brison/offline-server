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

include_once ("FDL/Class.SearchDoc.php");
include_once ("DATA/Class.Collection.php");
include_once ("OFFLINE/Class.DomainManager.php");

/**
 * View folders and document for exchange them
 * @param Action &$action current action
 */
function off_domains(Action &$action)
{
    
   
    $out=DomainManager::getDomains();
    //print_r2($out);
    $action->lay->template=json_encode($out);
    $action->lay->noparse=true; // no need to parse after - increase performances
}
?>