<?php

/**
 * Context menu view in folder list for a document
 *
 * @author Anakeen 2006
 * @version $Id: ws_popupdocfolder.php,v 1.11 2007/02/12 10:52:00 eric Exp $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package OFFLINE
 * @subpackage 
 */
/**
 */
include_once ("FDL/popupdoc.php");
include_once ("FDL/popupdocdetail.php");

// -----------------------------------
function off_popupdocfolder(&$action) {
    // -----------------------------------
    // define accessibility
    $docid = GetHttpVars("id");
    $dirid = GetHttpVars("dirid");
    $abstract = (GetHttpVars("abstract", 'N') == "Y");
    $zone = GetHttpVars("zone"); // special zone


    $dbaccess = $action->GetParam("FREEDOM_DB");
    $doc = new_Doc($dbaccess, $docid);
    $fld = new_Doc($dbaccess, $dirid);

    //  if ($doc->doctype=="C") return; // not for familly


    $tsubmenu = array();
    $islink = ($doc->prelid != $fld->initid);

    // -------------------- Menu menu ------------------


    $surl = $action->getParam("CORE_STANDURL");
    $tlink = array();
    addOfflinePopup($tlink, $doc, $target = "nresume", "");

                
    unset($tlink[""]);
 

    //  addFamilyPopup($tlink,$doc);
    popupdoc($action, $tlink, $tsubmenu);
}

function addOfflinePopup(&$tlink, Doc &$doc, $target = "_self", $menu = 'offline') {
    include_once ("OFFLINE/Class.DomainManager.php");
    $onlysub = getHttpVars("submenu");
    $docDomainsId = $doc->getDomainIds();
    $allDomains = DomainManager::getDomains();
    $canDownload=false;
    foreach ($allDomains as $domain) {
        if ($domain->isAlive()) {
            $families = $domain->getFamilies();
            if (!in_array($doc->fromid, $families))
                continue;
            if ($domain->isMember($doc->getSystemUserId())) {
                $canDownload=true;
                $tlink["dom" . $domain->id] = array(
                    "descr" => sprintf(_("Domain %s"), $domain->getTitle()),
                    "url" => "",
                    "separator" => true,
                    "confirm" => "false",
                    "control" => "false",
                    "tconfirm" => "",
                    "target" => "$target",
                    "visibility" => POPUP_ACTIVE,
                    "submenu" => $menu,
                    "barmenu" => "false"
                );

                $ufolder = $domain->getUserFolder();
                if ($domain->getUserMode($doc->getSystemUserId()) == 'advanced') {
                    $tlink["book" . $domain->id] = array(
                        "descr" => _("Book in my space"),
                        "title" => _("book the document to modify it with offline application"),
                        "url" => "?app=OFFLINE&action=OFF_DOMAINAPI&htmlRedirect=" . $doc->initid . "&docid=" . $doc->initid . "&id=" . $domain->initid . '&method=bookDocument',
                        "confirm" => "false",
                        "control" => "false",
                        "color" => $domain->getValue("gui_color"),
                        "tconfirm" => "",
                        "target" => "$target",
                        "visibility" => ((($doc->CanLockFile() == '') && ($doc->lockdomainid == 0)) ? POPUP_ACTIVE : POPUP_INACTIVE),
                        "submenu" => $menu,
                        "barmenu" => "false"
                    );
                    $docDomainsId = $doc->getDomainIds(false, true);
                    $inDomain = in_array($ufolder->name, $docDomainsId);
                    $tlink["bookread" . $domain->id] = array(
                        "descr" => _("Set in my space to read it"),
                        "title" => _("insert the document to see it with offline application"),
                        "url" => "?app=OFFLINE&action=OFF_DOMAINAPI&htmlRedirect=" . $doc->initid . "&docid=" . $doc->initid . "&id=" . $domain->initid . '&method=insertUserDocument',
                        "confirm" => "false",
                        "control" => "false",
                        "color" => $domain->getValue("gui_color"),
                        "tconfirm" => "",
                        "target" => "$target",
                        "visibility" => ($inDomain) ? POPUP_INACTIVE : POPUP_ACTIVE,
                        "submenu" => $menu,
                        "barmenu" => "false"
                    );

                    $tlink["unset" . $domain->id] = array(
                        "descr" => _("remove from my space"),
                        "title" => _("remove the document from my space"),
                        "url" => "?app=OFFLINE&action=OFF_DOMAINAPI&htmlRedirect=" . $doc->initid . "&docid=" . $doc->initid . "&id=" . $domain->initid . '&method=removeUserDocument',
                        "confirm" => "false",
                        "control" => "false",
                        "color" => $domain->getValue("gui_color"),
                        "tconfirm" => "",
                        "target" => "$target",
                        "visibility" => ($inDomain) ? POPUP_ACTIVE : POPUP_INACTIVE,
                        "submenu" => $menu,
                        "barmenu" => "false"
                    );

                    if (($domain->getValue("off_sharepolicy") == "admin") || ($domain->getValue("off_sharepolicy") == "users")) {
                        $share = $domain->getSharedFolder();
                        if ($share->canModify() == "") {
                            $inDomain = in_array($share->name, $docDomainsId);
                            $tlink["sharebookread" . $domain->id] = array(
                                "descr" => sprintf(_("Set in %s"), $share->getHtmlTitle()),
                                "title" => _("Share the document to see it with offline application"),
                                "url" => "?app=OFFLINE&action=OFF_DOMAINAPI&htmlRedirect=" . $doc->initid . "&docid=" . $doc->initid . "&id=" . $domain->initid . '&method=insertSharedDocument',
                                "confirm" => "false",
                                "control" => "false",
                                "color" => $domain->getValue("gui_color"),
                                "tconfirm" => "",
                                "target" => "$target",
                                "visibility" => ($inDomain) ? POPUP_INACTIVE : POPUP_ACTIVE,
                                "submenu" => $menu,
                                "barmenu" => "false"
                            );

                            $tlink["shareunset" . $domain->id] = array(
                                "descr" => sprintf(_("Remove from %s"), $share->getHtmlTitle()),
                                "title" => _("Remove the document from share space"),
                                "url" => "?app=OFFLINE&action=OFF_DOMAINAPI&htmlRedirect=" . $doc->initid . "&docid=" . $doc->initid . "&id=" . $domain->initid . '&method=removeSharedDocument',
                                "confirm" => "false",
                                "control" => "false",
                                "color" => $domain->getValue("gui_color"),
                                "tconfirm" => "",
                                "target" => "$target",
                                "visibility" => ($inDomain) ? POPUP_ACTIVE : POPUP_INACTIVE,
                                "submenu" => $menu,
                                "barmenu" => "false"
                            );
                        }
                    }
                }
                $tlink["access" . $domain->id] = array(
                    "descr" => _("view my space"),
                    "title" => _("access to documents of my space"),
                    "url" => "?app=OFFLINE&action=OFF_ORGANIZER&domain=0&dirid=" . $ufolder->initid,
                    "confirm" => "false",
                    "control" => "false",
                    "color" => $domain->getValue("gui_color"),
                    "tconfirm" => "",
                    "target" => "",
                    "visibility" => POPUP_ACTIVE,
                    "submenu" => $menu,
                    "barmenu" => "false"
                );
            }
        }
    }
    
    if ($canDownload) {
        $tlink["offdownload"] = array(
            "descr" => sprintf(_("Download offline client application")),
            "url" => "?app=OFFLINE&action=OFF_DLCLIENT",
            "separator" => true,
            "confirm" => "false",
            "control" => "false",
            "tconfirm" => "",
            "target" => "_offdl",
            "visibility" => POPUP_ACTIVE,
            "submenu" => $menu,
            "barmenu" => "false"
        );
    }
}

?>
