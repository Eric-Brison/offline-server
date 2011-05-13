<?php
/**
 * Display offline documents
 *
 * @author Anakeen
 * @version $Id:  $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package OFFLINE
 * @subpackage
 */
/**
 */

include_once ("WORKSPACE/ws_navigate.php");

/**
 * View folders and document for exchange them
 * @param Action &$action current action
 */
function off_organizer(Action &$action)
{
    
    $domainId = $action->getArgument("domain");
    $dirid = $action->getArgument("dirid");
    $nav = new ws_Navigate($action);
    if ($domainId) {
        
        $spaces = new SearchDoc($action->dbaccess,'OFFLINEDOMAIN');
        if ($domainId != 'all') {
            $fld = new_Doc($action->dbaccess, $domainId);
            if (!$fld->isAlive()) $action->exitError(sprintf(_("document %s not found"), $domainId));
            
            $spaces->addFilter("id=%d", $fld->initid);
            $domainId=$fld->initid;
        }
        $nav->setSpaces($spaces);
        if (method_exists($fld, "getFamilies")) {
            $families = $fld->getFamilies();
            if (count($families) > 0) {
                $searchFamilies = new SearchDoc($action->dbaccess);
                $searchFamilies->addFilter($searchFamilies->sqlcond($families, "fromid"));
                $nav->setGlobalSearch($searchFamilies);
            }
        }
    }
    $nav->setFolderListInclude("OFFLINE/off_folderListFormat.php");
    $nav->setFolderDocPopup("OFFLINE:OFF_POPUPDOCFOLDER");
    $nav->setFolderPopup("OFFLINE:OFF_POPUPLISTFOLDER");
    $nav->setFolderListColumn('offFolderListFormat::getColumnDescription()');
    
    if ($dirid) {
        $nav->setInitialFolder($dirid);
    } else if ($domainId  && ($domainId!='all')) {
        $nav->setInitialFolder($domainId);
    
    }
    
    $nav->viewMySpace(false);
    $action->lay->set("NAV", $nav->output());
}
?>