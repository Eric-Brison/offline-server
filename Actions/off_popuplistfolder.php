<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/
/**
 * Context menu view in folder list for a document
 *
 * @author Anakeen 2006
 * @version $Id: ws_popupdocfolder.php,v 1.11 2007/02/12 10:52:00 eric Exp $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package OFFLINE
 * @subpackage
 */

include_once ("FDL/popupdoc.php");
include_once ("FDL/popupdocdetail.php");
// -----------------------------------
function off_popuplistfolder(\Action & $action)
{
    // -----------------------------------
    // define accessibility
    $docid = GetHttpVars("id");
    $dirid = GetHttpVars("dirid");
    $abstract = (GetHttpVars("abstract", 'N') == "Y");
    $zone = GetHttpVars("zone"); // special zone
    $dbaccess = $action->GetParam("FREEDOM_DB");
    $doc = new_Doc($dbaccess, $docid);
    //  if ($doc->doctype=="C") return; // not for familly
    $tsubmenu = array();
    // -------------------- Menu menu ------------------
    $surl = $action->getParam("CORE_STANDURL");
    $tlink = array();
    if ($doc->fromname == 'OFFLINEFOLDER') {
        $tlink+= array(
            "sep1" => array(
                "separator" => true
            ) ,
            "clear" => array(
                "descr" => _("Clear User Folder") ,
                "url" => "?app=OFFLINE&action=OFF_DOMAINAPI&htmlRedirect=" . $doc->initid . "&docid=" . $doc->initid . "&id=" . $doc->getValue("off_domain") . '&method=clearUserDocuments',
                
                "confirm" => "false",
                "control" => "false",
                "icon" => "Images/documentinfo.png",
                "tconfirm" => "",
                "target" => "nresume",
                "visibility" => POPUP_ACTIVE,
                "submenu" => "",
                "barmenu" => "false"
            )
        );
    }
    
    if ($doc->fromname == 'OFFLINEGLOBALFOLDER') {
        if ($doc->control('modify') == "") {
            $tlink+= array(
                "sep1" => array(
                    "separator" => true
                ) ,
                "clear" => array(
                    "descr" => _("Clear Share Folder") ,
                    "url" => "?app=OFFLINE&action=OFF_DOMAINAPI&htmlRedirect=" . $doc->initid . "&docid=" . $doc->initid . "&id=" . $doc->getValue("off_domain") . '&method=clearSharedDocuments',
                    
                    "confirm" => "false",
                    "control" => "false",
                    "icon" => "Images/documentinfo.png",
                    "tconfirm" => "",
                    "target" => "nresume",
                    "visibility" => POPUP_ACTIVE,
                    "submenu" => "",
                    "barmenu" => "false"
                )
            );
        }
    }
    $tlink+= array(
        "sep1" => array(
            "separator" => true
        ) ,
        "properties" => array(
            "descr" => _("Properties") ,
            "url" => "$surl&app=FDL&action=FDL_CARD&id=$docid",
            "confirm" => "false",
            "control" => "false",
            "icon" => "Images/documentinfo.png",
            "tconfirm" => "",
            "target" => "nresume",
            "visibility" => POPUP_ACTIVE,
            "submenu" => "",
            "barmenu" => "false"
        )
    );
    /*
    $tlink+=array(
    
        "editdoc"=>array( "descr"=>_("Edit"),
     "url"=>"$surl&app=GENERIC&action=GENERIC_EDIT&rzone=$zone&id=$docid",
     "confirm"=>"false",
     "tconfirm"=>"",
     "target"=>"",
     "visibility"=>POPUP_ACTIVE,
     "submenu"=>"",
     "barmenu"=>"false"),
    
        "histo"=>array( "descr"=>_("History"),
          "url"=>"$surl&app=FREEDOM&action=HISTO&id=$docid&viewrev=N",
          "confirm"=>"false",
          "tconfirm"=>"",
          "target"=>"",
          "visibility"=>POPUP_ACTIVE,
          "submenu"=>"",
          "barmenu"=>"false"),
    
        "properties"=>array( "descr"=>_("properties"),
        "url"=>"$surl&app=FDL&action=IMPCARD&zone=".((method_exists($doc,"viewsimpleprop"))?"WORKSPACE:VIEWSIMPLEPROP:T":"FDL:VIEWPROPERTIES:T")."&id=$docid",
        "tconfirm"=>"",
        "confirm"=>"false",
        "target"=>"properties$docid",
        "mwidth"=>400,
        "mheight"=>300,
        "visibility"=>POPUP_ACTIVE,
        "submenu"=>"",
        "barmenu"=>"false"));
    
    */
    //  addFamilyPopup($tlink,$doc);
    popupdoc($action, $tlink, $tsubmenu);
}
