<?php
/**
 * Offline domain
 *
 * @author Anakeen
 * @version $Id:  $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package OFFLINE
 */
/**
 */

/*
 * @begin-method-ignore
 * this part will be deleted when construct document class until end-method-ignore
 */

/**
 * offline domain fonctionalities
 *
 */
class _OFFLINEDOMAIN extends Dir
{
    /*
     * @end-method-ignore
     */
    
    private $hookObject = null;
    /**
     * Add new supported family in offline domain
     * @param string $familyId family identificator
     * @param boolean $includeSubFamily set to false to not include sub families
     * @param mask $mask id of a specific mask for family
     * @return string error message (empty string if no errors)
     */
    public function addFamily($familyId, $includeSubFamily = true, $mask = 0)
    {
        $err = '';
        if ($familyId) {
            $fam = new_doc($this->dbaccess, $familyId);
            if ($fam->isAlive()) {
                if ($fam->doctype == "C") {
                    $famids = $this->getTValue("off_families");
                    $subfamids = $this->getTValue("off_subfamilies");
                    $key = array_search($fam->id, $famids);
                    if ($key === false) {
                        $famids[] = $fam->id;
                        $subfamids[] = ($includeSubFamily ? 'yes' : 'no');
                    } else {
                        $famids[$key] = $fam->id;
                        $subfamids[$key] = ($includeSubFamily ? 'yes' : 'no');
                    }
                    $err = $this->setValue("off_families", $famids);
                    $err .= $this->setValue("off_subfamilies", $subfamids);
                    if (!$err) $err = $this->save();
                } else {
                    $err = sprintf("not a  family %s [%d] not alive", $fam->getTitle(), $fam->id);
                }
            } else {
                
                $err = sprintf("no family %s [%d] not alive", $fam->getTitle(), $fam->id);
            }
        } else {
            $err = sprintf("no family given");
        }
        return $err;
    }
    
    public function addFollowingStates(Doc &$doc)
    {
        if (!$doc->wid) return false;
        if (($doc->lockdomainid == $this->id) && ($doc->locked == $this->getSystemUserId())) {
            $wdoc = new_doc($this->dbaccess, $doc->wid);
            if (!$wdoc->isAlive()) return false;
            if (!$this->canUseWorkflow($doc->fromid)) return false;
            try {
                $wdoc->Set($doc);
                $fs = $wdoc->getFollowingStates();
                $fsout = array();
                foreach ( $fs as $state ) {
                    $tr = $wdoc->getTransition($doc->state, $state);
                    $fsout[$state] = array(
                        "label" => _($state),
                        "color" => $wdoc->getColor($state),
                        "activity" => $wdoc->getActivity($state),
                        "transition" => _($tr["id"])
                    );
                }
                $this->addExtraData($doc, "followingStates", $fsout);
                
                return true;
            } catch ( Exception $e ) {
            }
        }
        
        return false;
    }
    
    public function addExtraData(Doc &$doc, $key, $value)
    {
        $doc->addfields["pullextradata"] = "pullextradata";
        $doc->pullextradata[$key] = $value;
    }
    /**
     * remove supported family in offline domain
     * @param string $familyId family identificator
     * @return string error message (empty string if no errors)
     */
    public function removeFamily($familyId)
    {
        $err = '';
        $fam = new_doc($this->dbaccess, $familyId);
        if ($fam->isAlive()) {
            if ($fam->doctype == "C") {
                $famids = $this->getTValue("off_families");
                $subfamids = $this->getTValue("off_subfamilies");
                $key = array_search($fam->id, $famids);
                if ($key !== false) {
                    unset($famids[$key]);
                    unset($subfamids[$key]);
                }
                $err = $this->setValue("off_families", $famids);
                $err .= $this->setValue("off_subfamilies", $subfamids);
                if (!$err) $err = $this->save();
            } else {
                $err = sprintf("not a  family %s [%d] not alive", $fam->getTitle(), $fam->id);
            }
        }
        return $err;
    }
    
    /**
     * Add new supported family in offline domain
     * @param string $familyId family identificator
     * @param boolean $includeSubFamily set to false to not include sub families
     * @param int $maskId id of a specific mask for family 0 means no mask
     * @return string error message (empty string if no errors)
     */
    public function setFamilyMask($familyId, $includeSubFamily = true, $maskId)
    {
        $err = '';
        if ($familyId) {
            $fam = new_doc($this->dbaccess, $familyId);
            if ($fam->isAlive()) {
                if ($fam->doctype == "C") {
                    $domainFamily = $this->getFamDoc();
                    if ($maskId) {
                        $newMask = new_doc($this->dbaccess, $maskId);
                        if ((!$newMask->isAlive()) || ($newMask->fromname != 'MASK')) {
                            $err = sprintf("not a mask %s", $maskId);
                        }
                    }
                    
                    if (!$err) {
                        $maskId = $newMask->id;
                        $famids = $domainFamily->getParamTValue("off_mskfamilies");
                        $subfamids = $domainFamily->getParamTValue("off_msksubfamilies");
                        $masks = $domainFamily->getParamTValue("off_masks");
                        $key = array_search($fam->id, $famids);
                        if ($key === false) {
                            if ($maskId > 0) {
                                $famids[] = $fam->id;
                                $subfamids[] = ($includeSubFamily ? 'yes' : 'no');
                                $masks[] = $maskId;
                            }
                        } else {
                            if ($maskId > 0) {
                                $famids[$key] = $fam->id;
                                $subfamids[$key] = ($includeSubFamily ? 'yes' : 'no');
                                $masks[$key] = $maskId;
                            } else {
                                unset($famids[$key]);
                                unset($subfamids[$key]);
                                unset($masks[$key]);
                            }
                        }
                        $err = $domainFamily->setParam("off_mskfamilies", $famids);
                        $err .= $domainFamily->setParam("off_msksubfamilies", $subfamids);
                        $err .= $domainFamily->setParam("off_masks", $masks);
                        if (!$err) $err = $domainFamily->modify();
                    }
                } else {
                    $err = sprintf("not a  family %s [%d] not alive", $fam->getTitle(), $fam->id);
                }
            } else {
                
                $err = sprintf("no family %s [%d] not alive", $fam->getTitle(), $fam->id);
            }
        } else {
            $err = sprintf("no family given");
        }
        return $err;
    }
    
    /**
     * return families restriction
     * @return array of families identificator
     */
    public function getFamilies()
    {
        $famids = $this->getTValue("off_allfamilies");
        if (count($famids) == 0) {
            $families = $this->getAValues("off_t_families");
            $fams = array();
            foreach ( $families as $k => $v ) {
                if ($v["off_families"]) {
                    $fams[] = $v["off_families"];
                    if ($v["off_subfamilies"] != "no") {
                        $fams = array_merge(array_keys($this->getChildFam($v["off_families"], false)), $fams);
                    }
                }
            }
            
            $famids = array_unique($fams);
            
            $this->disableEditControl();
            $err = $this->setValue("off_allfamilies", $famids);
            $err .= $this->modify();
            
            $this->enableEditControl();
        }
        return $famids;
    }
    
    public function canUseWorkflow($familyId)
    {
        $families = $this->getAValues("off_t_families");
        $fams = array();
        foreach ( $families as $k => $v ) {
            if ($v["off_families"]==$familyId) {
                if ($v["off_useworkflow"] == "yes") {
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * Generate a report file based on log for a user
     * @param int $userId 
     * @return string error message (empty string if no errors)
     */
    public function updateReport($userId, &$report)
    {
        $userId = $this->getDomainUserId($userId);
        $folder = $this->getUserFolder($userId);
        $tmpfile = tempnam(getTmpDir(), 'syncReport');
        $report = $this->generateReport($userId);
        file_put_contents($tmpfile, $report);
        
        $folder->disableEditControl();
        $err = $folder->storeFile("off_report", $tmpfile, sprintf(_("Sync report %s.html"), date('Y-m-d')));
        if (!$err) $err = $folder->modify();
        $folder->enableEditControl();
        @unlink($tmpfile);
        return $err;
    }
    
    public function generateReport($userId)
    {
        global $action;
        $lay = new Layout(getLayoutFile("OFFLINE", "syncreport.html"), $action);
        $q = new QueryDb($this->dbaccess, "DocLog");
        $q->addQuery(sprintf("initid=%d", $this->id));
        $q->addQuery(sprintf("uid=%d", $userId));
        $q->order_by = "date desc";
        
        $r = $q->query(0, 1000, "TABLE");
        $tsync = array();
        foreach ( $r as $k => $v ) {
            
            $v = (object) $v;
            $v->arg = unserialize($v->arg);
            
            $tsync[] = array(
                "oddClass" => ($k % 2 == 0) ? "even" : "odd",
                "syncDate" => $this->reportGetDate($v),
                "syncCode" => substr($v->code, strlen('DomainSyncApi::')),
                "syncAction" => $this->reportGetAction($v),
                "syncMessage" => $this->reportGetMessage($v),
                "syncStatus" => $this->reportGetStatus($v)
            );
        }
        $lay->setBlockData("MSG", $tsync);
        $lay->set("date", FrenchDateToLocaleDate($this->getTimeDate()));
        $lay->set("domain", $this->getHTMLTitle());
        $lay->set("username", User::getDisplayName($this->getSystemUserId()));
        //print $lay->gen();
        return $lay->gen();
    }
    
    private function reportGetDate($sync)
    {
        return FrenchDateToLocaleDate(strtok($sync->date, '.'));
    
    }
    private function reportGetStatus($sync)
    {
        switch ($sync->code) {
        case 'DomainSyncApi::endTransaction' :
            switch ($sync->arg->status) {
            case DomainSyncApi::successTransaction :
                $status = "ok";
                foreach ( $sync->arg->detailStatus as $dstatus ) {
                    $dstatus = (object) $dstatus;
                    if ($dstatus->saveInfo->onAfterSaveChangeState || $dstatus->saveInfo->onAfterSaveDocument) {
                        $status = "warn";
                        break;
                    }
                }
                break;
            
            case DomainSyncApi::partialTransaction :
                $status = "partial";
                break;
            
            case DomainSyncApi::abortTransaction :
                $status = "ko";
                break;
            }
            break;
        default :
            if ($sync->arg->error != '') {
                $status = "ko";
            } else {
                $status = "ok";
            }
        }
        return $status;
    
    }
    private function reportGetAction($sync)
    {
        return _($sync->code); #  _("DomainSyncApi::bookDocument");_("DomainSyncApi::unbookDocument"); _("DomainSyncApi::removeUserDocument");_("DomainSyncApi::endTransaction"); _("DomainSyncApi::beginTransaction");_("DomainSyncApi::getUserDocuments");_("DomainSyncApi::getSharedDocuments"); _("DomainSyncApi::revertDocument"); _("DomainSyncApi::pushDocument");
    

    }
    private function reportGetMessage($sync)
    {
        
        switch ($sync->code) {
        case 'DomainSyncApi::endTransaction' :
            
            $list = new DocumentList();
            $list->addDocumentIdentificators(array_keys($sync->arg->detailStatus));
            $msgdoc = array();
            foreach ( $sync->arg->detailStatus as $docid => $status ) {
                if ($docid < 0) {
                    $msgdoc[$docid] = $this->reportFormatEndStatus((object) $status, _("new document"));
                }
            }
            
            foreach ( $list as $id => $doc ) {
                $status = (object) $sync->arg->detailStatus[$doc->initid];
                
                $msgdoc[id] = $this->reportFormatEndStatus($status, sprintf("%s <span>%s</span>", $doc->getTitle(), $doc->initid));
            
            }
            if (count($msgdoc) > 1) {
                $message = '<ul><li>' . implode('</li><li>', $msgdoc) . '</li></ul>';
            } elseif (count($msgdoc) == 1) {
                $message = current($msgdoc);
            } else {
                $message = _("no documents uploaded");
            }
            //$message .= '<pre>' . print_r($sync->arg, true) . "</pre>";
            break;
        
        case 'DomainSyncApi::pushDocument' :
            if ($sync->arg->refererinitid < 0) $message = sprintf(_("document creation %s"), $message = $sync->arg->title);
            elseif ($sync->arg->refererinitid == null) $message = sprintf(_("document creation failed"));
            else $message = $sync->arg->title;
            if ($sync->arg->error) $message .= ' : ' . $sync->arg->error;
            if ($sync->arg->message) $message .= ' : ' . $sync->arg->message;
            //$message .= '<pre>' . print_r($sync->arg, true) . "</pre>";
            break;
        
        case 'DomainSyncApi::beginTransaction' :
            $message = $sync->arg->error;
            
            //$message .= '<pre>' . print_r($sync->arg, true) . "</pre>";
            break;
        
        case 'DomainSyncApi::bookDocument' :
        case 'DomainSyncApi::unbookDocument' :
        case 'DomainSyncApi::revertDocument' :
            
            if ($sync->arg->error) $message = sprintf("%s : %s", $sync->arg->title, $sync->arg->error);
            else $message = sprintf(_("%s has been downloaded"), sprintf("%s <span>%s</span>", $sync->arg->title, $sync->arg->initid));
            //$message .= '<pre>' . print_r($sync->arg, true) . "</pre>";
            break;
        case 'DomainSyncApi::getUserDocuments' :
        case 'DomainSyncApi::getSharedDocuments' :
            if (is_array($sync->arg->documentsToUpdate)) {
                $list = new DocumentList();
                $list->addDocumentIdentificators($sync->arg->documentsToUpdate);
                $msgdoc = array();
                foreach ( $list as $docid => $doc ) {
                    $msgdoc[$docid] = sprintf("%s <span>%s</span>", $doc->getTitle(), $doc->initid);
                }
                if (count($msgdoc) > 1) {
                    $updateMessage = _("download documents :") . '<ul><li>' . implode('</li><li>', $msgdoc) . '</li></ul>';
                } elseif (count($msgdoc) == 1) {
                    $updateMessage = sprintf(_("download document %s"), current($msgdoc));
                } else {
                    $updateMessage = '';
                }
            }
            if (is_array($sync->arg->documentsToDelete)) {
                $list = new DocumentList();
                $list->addDocumentIdentificators($sync->arg->documentsToDelete);
                $msgdoc = array();
                foreach ( $list as $docid => $doc ) {
                    $msgdoc[$docid] = $doc->getTitle();
                }
                if (count($msgdoc) > 1) {
                    $deleteMessage = _("delete documents :") . '<ul><li>' . implode('</li><li>', $msgdoc) . '</li></ul>';
                } elseif (count($msgdoc) == 1) {
                    $deleteMessage = sprintf(_("delete document %s"), current($msgdoc));
                } else {
                    $deleteMessage = '';
                }
            }
            $message = '';
            if ($sync->arg->error) $message = $sync->arg->error;
            if ($updateMessage && $deleteMessage) $message .= nl2br($updateMessage . "\n" . $deleteMessage);
            elseif ($updateMessage) $message .= $updateMessage;
            elseif ($deleteMessage) $message .= $deleteMessage;
            else $message .= _("no documents to retrieve");
            // $message .= '<pre>' . print_r($sync->arg, true) . "</pre>";
            break;
        default :
            //$message = '<pre>' . print_r($sync->arg, true) . "</pre>";
            $message = "-";
        }
        return $message;
    }
    
    private function reportFormatEndStatus($status, $title = '')
    {
        $status->saveInfo = (object) $status->saveInfo;
        switch ($status->statusCode) {
        case 'constraint' :
            if (count($status->saveInfo->constraint) > 0) {
                $msgConstraint = '';
                
                foreach ( $status->saveInfo->constraint as $aid => $constraint ) {
                    $msgConstraint .= sprintf("%s : %s", $constraint["label"], $constraint["err"]);
                }
                $statusMessage = $msgConstraint;
                $msgdoc = sprintf(_("%s following constraints are not validated: %s"), $title, $statusMessage);
            }
            break;
        case 'uptodate' :
            $msgdoc = sprintf(_("%s has been recorded"), $title);
            break;
        default :
            $msgdoc = '';
        }
        $statusMessage = '';
        if ($status->saveInfo->onAfterSaveDocument) {
            $statusMessage .= sprintf(_("after save warning:%s\n"), $status->saveInfo->onAfterSaveDocument);
        }
        if ($status->saveInfo->onAfterSaveChangeState) {
            $statusMessage .= sprintf(("%s\n"), $status->saveInfo->onAfterSaveChangeState);
        }
        if (!$msgConstraint) {
            $statusMessage .= $status->statusMessage;
        }
        
        return $statusMessage . $msgdoc;
    }
    
    /**
     * add new member in offline domain
     * @param integer $userId system identificator for a group or a single user. Can use also logical name of relative document or login
     * @param boolean $hasManagePrivilege set to false if the user/group cannot has privilege to choice document to synchronise
     * @return string error message (empty string if no errors)
     */
    public function addMember($userId, $hasManagePrivilege = false)
    {
        $userId = $this->getDomainUserId($userId);
        
        $err = '';
        if ($userId) {
            $user = new User($this->dbaccess, $userId);
            if ($user->isAffected()) {
                if ($user->isgroup == 'Y') {
                    $aidMember = 'off_group_members';
                    $aidMode = 'off_group_mode';
                } else {
                    $aidMember = 'off_user_members';
                    $aidMode = 'off_user_mode';
                
                }
                $members = $this->getTValue($aidMember);
                $mode = $this->getTValue($aidMode);
                $key = array_search($user->fid, $members);
                if ($key === false) {
                    $members[] = $user->fid;
                    $mode[] = ($hasManagePrivilege ? 'advanced' : 'standard');
                } else {
                    $members[$key] = $user->fid;
                    $mode[$key] = ($hasManagePrivilege ? 'advanced' : 'standard');
                }
                $err = $this->setValue($aidMember, $members);
                $err .= $this->setValue($aidMode, $mode);
                if (!$err) $err = $this->save();
            } else {
                $err = sprintf("no user %s  not alive", $userId);
            }
        } else {
            $err = sprintf("no user given");
        }
        return $err;
    }
    /**
     * remove new member in offline domain
     * @param integer $userId system identificator for a group or a single user. Can use also logical name of relative document
     * @return string error message (empty string if no errors)
     */
    public function removeMember($userId)
    {
        $userId = $this->getDomainUserId($userId);
        $err = '';
        if ($userId) {
            $user = new User($this->dbaccess, $userId);
            if ($user->isAffected()) {
                if ($user->isgroup == 'Y') {
                    $aidMember = 'off_group_members';
                    $aidMode = 'off_group_mode';
                } else {
                    $aidMember = 'off_user_members';
                    $aidMode = 'off_user_mode';
                }
                $members = $this->getTValue($aidMember);
                $mode = $this->getTValue($aidMode);
                $key = array_search($user->fid, $members);
                if ($key !== false) {
                    unset($members[$key]);
                    unset($mode[$key]);
                }
                $err = $this->setValue($aidMember, $members);
                $err .= $this->setValue($aidMode, $mode);
                if (!$err) $err = $this->save();
            } else {
                $err = sprintf("no user %s  not alive", $userId);
            }
        } else {
            $err = sprintf("no user given");
        }
        return $err;
    }
    
    /**
     * change privilege of a group/user
     * @param integer $userId system identificator for a group or a single user. Can use also logical name of relative document
     * @param boolean $hasManagePrivilege set to false if the user/group cannot has privilege to choice document to synchronise
     * @return string error message (empty string if no errors)
     */
    public function setManagePrivilege($userId, $hasManagePrivilege)
    {
        $userId = $this->getDomainUserId($userId);
        $fid = $this->uid2fid($userId);
        $fids = $this->getTValue("off_user_members");
        $fids += $this->getTValue("off_group_members");
        if (in_array($fid, $fids)) {
            $err = $this->addMember($userId, $hasManagePrivilege);
        } else {
            $err = sprintf("user %s is not a member", $userId);
        }
        return $err;
    }
    
    /**
     * verify if user has manage privilege
     * @param integer $userId system identificator for a group or a single user. Can use also logical name of relative document
     * @return boolean true if has, false else. - null if $userId not exists
     */
    public function hasManagePrivilege($userId)
    {
        return ($this->getUserMode($userId) == 'advanced');
    }
    
    /**
     * retrieve all user members system id or document id - inspect groups and sub groups)
     * @param boolean $useSystemId set to true to set the associative key to system id else use document id
     * @return array array of [key]=>["id"=>, "docid"=>,"displayName"=>, "login"=>]
     */
    public function getUserMembersInfo($useSystemId = true)
    {
        include_once ("FDL/Class.SearchDoc.php");
        
        $out = array();
        
        // group members
        $um = $this->getTValue("off_group_members");
        foreach ( $um as $k => $v ) {
            if (!$v) unset($um[$k]);
        }
        if (count($um) > 0) {
            $s = new SearchDoc($this->dbaccess, "IGROUP");
            $s->addFilter($s->sqlCond($um, "initid", true));
            $s->noViewControl();
            $users = $s->search();
            foreach ( $users as $kg => $guser ) {
                $g = new User($this->dbaccess, $guser["us_whatid"]);
                if ($g->isAffected()) {
                    $members = $g->getUserMembers();
                    foreach ( $members as $k => $user ) {
                        $index = $useSystemId ? $user["id"] : $user["fid"];
                        $out[$index] = array(
                            "id" => $user["id"],
                            "docid" => $user["fid"],
                            "displayName" => trim($user["firstname"] . " " . $user["lasttname"]),
                            "login" => $user["login"]
                        );
                    
                    }
                }
            }
        }
        
        // user members
        $um = $this->getTValue("off_user_members");
        foreach ( $um as $k => $v ) {
            if (!$v) unset($um[$k]);
        }
        if (count($um) > 0) {
            $s = new SearchDoc($this->dbaccess, "IUSER");
            $s->addFilter($s->sqlCond($um, "initid", true));
            $users = $s->search();
            foreach ( $users as $k => $user ) {
                $index = $useSystemId ? $user["us_whatid"] : $user["initid"];
                $out[$index] = array(
                    "id" => $user["us_whatid"],
                    "docid" => $user["initid"],
                    "displayName" => $user["title"],
                    "login" => $user["us_login"]
                );
            }
        }
        return $out;
    }
    
    /**
     * verify if user is member of domain
     * @param integer $userId system identificator for a group or a single user. Can use also logical name of relative document
     * @return boolean true if has, false else. - null if $userId not exists
     */
    public function isMember($uid = 0)
    {
        include_once ("FDL/Class.SearchDoc.php");
        $userId = $this->getDomainUserId($uid);
        $fid = $this->uid2fid($userId);
        
        if ($fid) {
            // group members
            $um = $this->getTValue("off_user_members");
            if (in_array($fid, $um)) return true;
            
            $um = $this->getTValue("off_group_members");
            foreach ( $um as $k => $v ) {
                if (!$v) unset($um[$k]);
            }
            if (count($um) > 0) {
                $s = new SearchDoc($this->dbaccess, "IGROUP");
                $s->addFilter($s->sqlCond($um, "initid", true));
                $s->noViewControl();
                $users = $s->search();
                foreach ( $users as $kg => $guser ) {
                    $g = new User($this->dbaccess, $guser["us_whatid"]);
                    if ($g->isAffected()) {
                        if ($g->isMember($userId)) return true;
                    }
                }
            }
        
        }
        return false;
    }
    
    /**
     * retrieve group or user recorded in the domain
     * @param int $uid member systeme identificator
     * @return array ["id"=>, "docid"=>,"displayName"=>,"managePrivilege"=>true/false,isGroup=>true/false]
     */
    public function getMemberInfo($uid)
    {
        $out = null;
        if ($this->isMember($uid)) {
            $u = new User($this->dbaccess, $uid);
            $userMode = $this->getUserMode($uid);
            $out = array(
                "id" => $u->id,
                "docid" => $u->fid,
                "displayName" => trim($u->firstname . ' ' . $u->lastname),
                "login" => $u->login,
                "managePrivilege" => ($userMode == 'advanced')
            );
        }
        return $out;
    }
    
    private function uid2fid($uid)
    {
        $err = simpleQuery($this->dbaccess, sprintf("select fid from users where id=%d", $uid), $docuid, true, true);
        if (!$err) return $docuid;
        return 0;
    }
    /**
     * 
     * @return string advanced or standard
     */
    public function getUserMode($uid)
    {
        $out = '';
        
        $uid = $this->getDomainUserId($uid);
        $docuid = $this->uid2fid($uid);
        if ($docuid) {
            
            $umembers = $this->getTValue("off_user_members");
            $key = array_search($docuid, $umembers);
            if ($key !== false) {
                $userMode = $this->getTValue("off_user_mode", '', $key);
            } else {
                // search in groups
                $ugroups = $this->getTValue("off_group_members");
                $gu = new User($this->dbaccess);
                foreach ( $ugroups as $k => $gid ) {
                    if ($gu->setFid($gid)) {
                        $members = $gu->getUserMembers();
                        foreach ( $members as $member ) {
                            if ($member['id'] == $uid) {
                                $userMode = $this->getTValue("off_group_mode", '', $k);
                                break;
                            }
                        }
                        if ($userMode == 'advanced') break;
                    }
                }
            }
        
        }
        return $userMode;
    }
    
    /**
     * add new document into share folder
     * @param integer $documentId document identificator
     * @param int $reservedBy user identificator which reserved document
     * @return string error message (empty string if no errors)
     */
    public function insertSharedDocument($documentId, $reservedBy = null)
    {
        
        $sfolder = $this->getSharedFolder();
        $doc = new_doc($this->dbaccess, $documentId, true);
        
        return $this->insertDocument($sfolder, $doc, $reservedBy, ($reservedBy > 0));
    }
    
    /**
     * remove document of share folder
     * @param integer $documentId document identificator
     * @return string error message (empty string if no errors)
     */
    public function removeSharedDocument($documentId)
    {
        $err = '';
        $sfolder = $this->getSharedFolder();
        $doc = new_doc($this->dbaccess, $documentId, true);
        
        if ($doc->isAlive()) {
            $err = $sfolder->delFile($doc->initid);
            $this->sendEvents($doc);
        }
        return $err;
    }
    
    /**
     * add new document into user folder
     * if reserve is true and document is reserved by another user, the document is not added
     * @param integer $documentId document identificator
     * @param int $userId user identificator (system id/or logical name)
     * @param boolean $reserve set to false if want readonly else reserved by $userId
     * @return string error message (empty string if no errors)
     */
    public function insertUserDocument($documentId, $userId = 0, $reserve = true)
    {
        
        $userId = $this->getDomainUserId($userId);
        $ufolder = $this->getUserFolder($userId);
        $doc = new_doc($this->dbaccess, $documentId, true);
        
        return $this->insertDocument($ufolder, $doc, $userId, $reserve);
    }
    
    /**
     * add new document into folder
     * if reserve is true and document is reserved by another user, the document is not added
     * @param integer $documentId document identificator
     * @param int $userId user identificator (system id/or logical name)
     * @param boolean $reserve set to false if want readonly else reserved by $userId
     * @return string error message (empty string if no errors)
     */
    private function insertDocument(Dir $folder, Doc &$doc, $userId = 0, $reserve = true)
    {
        if ($doc->isAlive()) {
            if ($reserve && ($doc->lockdomainid > 0) && ($doc->lockdomainid != $this->id)) {
                $err = sprintf(_("document is already book in other domain : %s"), $this->getTitle($doc->lockdomainid));
            } else {
                $point = "insertDocument" . $doc->id;
                $this->savePoint($point);
                $err = $folder->AddFile($doc->initid);
                if (!$err) {
                    if ($reserve) {
                        $err = $doc->lock(false, $userId); // lock by $userId
                        if (!$err) {
                            $doc->lockToDomain($this->id);
                        }
                    }
                
                }
                if ($err) $this->rollbackPoint($point);
                else {
                    $this->commitPoint($point);
                    $this->sendEvents($doc);
                
                }
            }
        } else {
            $err = sprintf(_("document to book not exists %s"), $doc->id);
        }
        return $err;
    }
    
    /**
     * send events for workspace interface
     * @param Doc $doc
     */
    private static function sendEvents(Doc &$doc)
    {
        global $action;
        $fdlids = $doc->getParentFolderIds();
        foreach ( $fdlids as $fldid ) {
            $action->AddActionDone("MODFOLDERCONTAINT", $fldid);
        }
    }
    /**
     * insert all documents where are into collection in share folder (not recursive in subfolders)
     * if reserve is true and document is reserved by another user, the document is not added
     * @code
      $s=new SearchDoc($action->dbaccess, "ZOO_ENCLOS");
      $s->setObjectReturn();
      $list=$s->search()->getDocumentList();
      $err=$domain->insertShareCollection($list);
     * @endcode
     * @param DocumentList $collection document identificator
     * @param int $reservedBy user identificator which reserved document
     * @return string error message (empty string if no errors)
     */
    public function insertShareCollection(DocumentList $collection, $reserveBy = 0)
    {
        $sfolder = $this->getSharedFolder();
        if ($sfolder) {
            $err = $this->insertCollection($sfolder, $collection, $reserveBy, ($reserveBy > 0));
        } else {
            $err = sprintf("share folder not found");
        }
        return $err;
    }
    /**
     * insert all documents where are into collection in user folder (not recursive in subfolders)
     * if reserve is true and document is reserved by another user, the document is not added
     * @param DocumentList $collection document identificator
     * @param int $userId user identificator (system id/or logical name)
     * @param boolean $reserve set to false if want readonly else reserved by $userId
     * @return string error message (empty string if no errors)
     */
    public function insertUserCollection(DocumentList $collection, $userId, $reserve = true)
    {
        $userId = $this->getDomainUserId($userId);
        $ufolder = $this->getUserFolder($userId);
        if ($ufolder) {
            $err = $this->insertCollection($ufolder, $collection, $userId, $reserve);
        } else {
            $err = sprintf("user folder %s noy found", $userId);
        }
        return $err;
    }
    
    /**
     * insert all documents where are into collection in user folder (not recursive in subfolders)
     * if reserve is true and document is reserved by another user, the document is not added
     * @param DocumentList $collection document identificator
     * @param int $userId user identificator (system id/or logical name)
     * @param boolean $reserve set to false if want readonly else reserved by $userId
     * @return string error message (empty string if no errors)
     */
    private function insertCollection(Dir &$folder, DocumentList $collection, $userId, $reserve = true)
    {
        $err = '';
        foreach ( $collection as $doc ) {
            $err .= $this->insertDocument($folder, $doc, $userId, $reserve);
        }
        return $err;
    }
    /**
     * clear all documents from user folder
     * if unlock is true all document lock by user are unlocked
     * @param int $userId user identificator (system id/or logical name)
     * @param boolean $unlock set to false to not unlock documents
     * @return string error message (empty string if no errors)
     */
    public function clearUserFolder($userId = 0, $unlock = true)
    {
        $userId = $this->getDomainUserId($userId);
        $ufolder = $this->getUserFolder($userId);
        return $this->clearFolder($ufolder);
    
    }
    /**
     * clear all documents from user foldder
     * if unlock is true all document lock by current user are unlocked
     * @param boolean $unlock set to false to not unlock documents
     * @return string error message (empty string if no errors)
     */
    public function clearSharedFolder($unlock = true)
    {
        $ufolder = $this->getSharedFolder();
        return $this->clearFolder($ufolder);
    }
    
    /**
     * clear all documents from foldder
     * if unlock is true all document lock by current user are unlocked
     * @param boolean $unlock set to false to not unlock documents
     * @return string error message (empty string if no errors)
     */
    private function clearFolder(Dir &$folder, $unlock = true)
    {
        $dl = $folder->getDocumentList();
        $err = $folder->clear();
        if ($err == "") {
            foreach ( $dl as $doc ) {
                $doc->updateDomains();
                if ($unlock) $doc->unlock();
            }
            include_once ("FDL/Class.DocWaitManager.php");
            DocWaitManager::clearWaitingDocs($this->domain, $this->getSystemUserId());
        } else {
            $this->setError($err);
        }
        return $err;
    }
    
    /**
     * remove document of share folder
     * no errors are set if document is not in user folder
     * @param integer $documentId document identificator
     * @param int $userId user identificator (system id/or logical name)
     * @return string error message (empty string if no errors)
     */
    public function removeUserDocument($documentId, $userId)
    {
        $err = '';
        $userId = $this->getDomainUserId($userId);
        $ufolder = $this->getUserFolder($userId);
        $doc = new_doc($this->dbaccess, $documentId, true);
        
        if ($doc->isAlive()) {
            $err = $ufolder->delFile($doc->initid);
            
            $this->sendEvents($doc);
        }
        return $err;
    }
    /**
     * change reservation
     * @param integer $documentId document identificator
     * @param int $reservedBy user identificator which reserved document set to null to cancel reservation
     * @return string error message (empty string if no errors)
     */
    public function setReservation($documentId, $reservedBy)
    {
        $doc = new_doc($this->dbaccess, $documentId, true);
        if ($doc->isAlive()) {
            $err = $doc->canEdit(false);
            if (!$err) {
                if ($reservedBy > 0) {
                    $err = $doc->lockToDomain($this->id, $reservedBy);
                } else {
                    // cancel book
                    $err = $doc->lockToDomain(0);
                }
            }
        } else {
            $err = sprintf("document %s not exists", $documentId);
        }
        return $err;
    }
    
    /**
     * get share folder
     * @return Dir the folder document
     */
    public function getSharedFolder()
    {
        $shared = new_doc($this->dbaccess, $this->getShareId());
        if ($shared->isAlive()) return $shared;
        return null;
    }
    
    private function getDomainUserId($userId)
    {
        if (!$userId) {
            $userId = $this->getSystemUserId();
        } else {
            if (is_numeric($userId)) {
                //
            } else {
                // search by login 
                $u = new User($this->dbaccess);
                $u->SetLoginName($userId);
                if ($u->isAffected()) $userId = $u->id;
            }
        }
        return $userId;
    }
    /**
     * get mask to be applied in a document when view it with offline application
     * @param int $family family identificator
     * @return int mask identificator - 0 of not found
     */
    public function getOfflineMask($family)
    {
        $fam = $this->getFamDoc();
        $families = $fam->getParamTValue("off_mskfamilies");
        $familyMask = new_doc($this->dbaccess, $family);
        if ($familyMask->isAlive()) {
            // first : easy test
            $key = array_search($familyMask->id, $families);
            
            if ($key !== false) {
                return $fam->getParamTValue("off_masks", '', $key);
            }
            // second search in sub
            $sub = $fam->getParamTValue("off_msksubfamilies");
            foreach ( $families as $k => $famid ) {
                if ($sub[$k] == "yes") {
                    $subFamIds = array_keys($this->getChildFam($famid, false));
                    if (in_array($familyMask->id, $subFamIds)) return $fam->getParamTValue("off_masks", '', $k);
                }
            }
        }
        return null;
    }
    
    /**
     * get user folder
     * @param int $userId user identificator (system id/or logical name)
     * @code
       
     * @endcode
     * @return Dir the folder document (null is user is not recorded)
     */
    public function getUserFolder($userId = 0)
    {
        $userId = $this->getDomainUserId($userId);
        $u = new User($this->dbaccess, $userId);
        if ($u->isAffected()) {
            $login = $u->login;
            $userFolder = new_doc($this->dbaccess, $this->getUserFolderId($login));
            if ($userFolder->isAlive()) return $userFolder;
        }
        return null;
    }
    
    /**
     * return all documents reserved by user
     * @param int $userId system user identificator
     * @retrun array of document id
     */
    public function getReservedDocumentIds($userId = 0)
    {
        $ids = array();
        $userId = $this->getDomainUserId($userId);
        $userFolder = $this->getUserFolder($userId);
        if ($userFolder) {
            $s = new SearchDoc($this->dbaccess);
            $s->useCollection($userFolder->initid);
            $s->setObjectReturn();
            $s->addFilter("locked = %d", $userId);
            $s->addFilter("lockdomainid = %d", $this->id);
            $dl = $s->search()->getDocumentList();
            foreach ( $dl as $k => $v ) {
                $ids[] = $v->initid;
            }
        }
        
        $shareFolder = $this->getSharedFolder();
        if ($shareFolder) {
            $s = new SearchDoc($this->dbaccess);
            $s->useCollection($shareFolder->initid);
            $s->setObjectReturn();
            $s->addFilter("locked = %d", $userId);
            $s->addFilter("lockdomainid = %d", $this->id);
            $dl = $s->search()->getDocumentList();
            foreach ( $dl as $k => $v ) {
                $ids[] = $v->initid;
            }
        }
        return array_unique($ids);
    }
    
    /**
     * get user folder documents
     * @param int $userId user identificator (system id/or logical name)
     * @code
       $domains=DomainManager::getDomains();
       foreach ($domains as $doc) {
         print "doc=".$doc->getTitle()."\n";
         $k=0;
         $contents=$doc->getUserDocuments();
         foreach ($contents as $id=>$cdoc) {
             printf("%4d) id:%s - %s\n", $k++ , $id, $cdoc->getTitle());
          }
      }
     * @endcode
     * @return DocumentList the folder document (null is user is not recorded)
     */
    public function getUserDocuments($userId = 0)
    {
        $userFolder = $this->getUserFolder($userId);
        if ($userFolder) {
            return $userFolder->getDocumentList();
        }
        return null;
    }
    
    /**
     * get share folder documents
     
     * @return DocumentList the folder document (null is user is not recorded)
     */
    public function getSharedDocuments()
    {
        $shareFolder = $this->getSharedFolder();
        if ($shareFolder) {
            return $shareFolder->getDocumentList();
        }
        return null;
    }
    public function postModify()
    {
        $err = $this->createSubDirectories();
        $err .= $this->deleteValue("off_allfamilies");
        return $err;
    }
    public function specRefresh()
    {
        //$this->createSubDirectories();
    }
    
    /**
     * refernece must be unique
     * 
     */
    public function preCreated()
    {
        $ref = $this->getValue("off_ref");
        if ($ref) {
            $exists = $this->getTitle($ref);
            if ($exists) return sprintf("reference %s exists", $ref);
        }
        return '';
    }
    /**
     * create user subdirectories
     */
    public function postCreated()
    {
        $ref = $this->getValue("off_ref");
        if ($ref) {
            $err = $this->setLogicalIdentificator($ref);
            return $err;
        }
        $err = $this->createSubDirectories();
        return $err;
    }
    
    /**
     * add unique logical name for shared folder
     */
    private function getShareId()
    {
        return sprintf("offshared_%s", $this->name);
    }
    /**
     * add unique logical name for user folder
     */
    private function getUserFolderId($login)
    {
        return sprintf("offuser_%s_%s", $this->name, $login);
    }
    
    public function createSubDirectories()
    {
        $err = "";
        if (!$this->name) {
            $err = $this->setLogicalIdentificator($this->getValue("off_ref"));
        }
        if (($this->getValue("off_sharepolicy") == "admin") || ($this->getValue("off_sharepolicy") == "users")) {
            $sharedID = $this->getShareId();
            $shared = new_doc($this->dbaccess, $sharedID);
            if (!$shared->isAlive()) {
                $shared = createDoc($this->dbaccess, "OFFLINEGLOBALFOLDER", false);
                $shared->setValue("ba_title", sprintf(_("%s Share folder"), $this->getTitle()));
                $shared->setValue("off_domain", $this->id);
                $err .= $shared->add();
                $err .= $shared->setLogicalIdentificator($sharedID);
                $err .= $this->addFile($shared->initid);
            
            } else {
                $shared = new_doc($this->dbaccess, $sharedID);
                $err .= $this->addFile($shared->initid);
            }
            $shared->disableEditControl();
            $shared->setValue("off_admins", $this->getValue("off_admins"));
            $shared->setValue("off_users", array_merge($this->getTValue("off_group_members"), $this->getTValue("off_user_members")));
            $shared->setValue("fld_allbut", "1"); // add restrictions
            $shared->setValue("fld_famids", $this->getValue("off_families"));
            $shared->setValue("fld_subfam", $this->getValue("off_subfamilies"));
            $err .= $shared->modify();
            if ($this->getValue("off_sharepolicy") == "admin") {
                $shared->setProfil("PRF_OFFGLOBFOLDERADMIN");
            } elseif ($this->getValue("off_sharepolicy") == "users") {
                $shared->setProfil("PRF_OFFGLOBFOLDERUSER");
            }
            $shared->enableEditControl();
        } else {
            $sharedID = $this->getShareId();
            $shared = new_doc($this->dbaccess, $sharedID);
            if ($shared->isAlive()) {
                // need to delete it
                $err .= $this->clearSharedFolder();
                if ($err == "") {
                    $err .= $shared->delete();
                }
            }
        }
        $usersID = sprintf("offusers_%s", $this->name);
        $users = new_doc($this->dbaccess, $usersID);
        if (!$users->isAlive()) {
            $users = createDoc($this->dbaccess, "DIR", false);
            $users->setValue("ba_title", sprintf(_("%s Users folder"), $this->getTitle()));
            $users->setValue("off_domain", $this->id);
            
            $err .= $users->add();
            $err .= $users->setLogicalIdentificator($usersID);
            $err .= $this->addFile($users->initid);
        
        } else {
            $users = new_doc($this->dbaccess, $usersID);
            $err .= $this->addFile($users->initid);
        }
        $users->disableEditControl();
        $users->setValue("fld_allbut", "1"); // add restrictions
        $users->setValue("fld_famids", getFamIdFromName($this->dbaccess, "OFFLINEFOLDER"));
        $users->setValue("fld_subfam", "no");
        $users->setValue("off_admins", $this->getValue("off_admins"));
        $users->setValue("off_users", array_merge($this->getTValue("off_group_members"), $this->getTValue("off_user_members")));
        $err .= $users->modify();
        
        $users->enableEditControl();
        $members = $this->getUserMembersInfo(true);
        foreach ( $members as $uid => $member ) {
            $userFolderID = $this->getUserFolderId($member["login"]);
            $userfolder = new_doc($this->dbaccess, $userFolderID);
            if (!$userfolder->isAlive()) {
                $userfolder = createDoc($this->dbaccess, "OFFLINEFOLDER", false);
                $userfolder->setValue("ba_title", sprintf(_("%s User folder"), $member["login"]));
                $userfolder->setValue("off_domain", $this->id);
                $userfolder->setValue("off_user", $member["docid"]);
                
                $err .= $userfolder->add();
                $err .= $userfolder->setLogicalIdentificator($userFolderID);
                $err .= $users->addFile($userfolder->initid);
            
            } else {
                $userfolder = new_doc($this->dbaccess, $userFolderID);
                $err .= $users->addFile($userfolder->initid);
            
            }
            $userfolder->disableEditControl();
            if ($this->hasManagePrivilege($member["id"])) {
                $userfolder->setValue("off_advanceduser", $member["docid"]);
            } else {
                $userfolder->deleteValue("off_advanceduser");
            }
            $userfolder->setValue("fld_allbut", "1"); // add restrictions
            $userfolder->setValue("fld_famids", $this->getValue("off_families"));
            $userfolder->setValue("fld_subfam", $this->getValue("off_subfamilies"));
            $err .= $userfolder->modify();
            $userfolder->enableEditControl();
        }
        
        return $err;
    }
    public function isPHPFile($filepath)
    {
        
        if ($filepath) {
            if (strstr($filepath, '..')) {
                return sprintf(_("file %s must be relative"), $filepath);
            }
            if (!file_exists(sprintf("%s/%s", DEFAULT_PUBDIR, $filepath))) {
                return sprintf(_("file %s not exists"), $filepath);
            }
            
            if (!preg_match('/\.php$/', $filepath)) {
                return sprintf(_("file %s must be a PHP file"), $filepath);
            }
            $fileContent = file_get_contents($filepath);
            if (!preg_match('/class\s+([a-z_0-9]+)\s+implements\s+DomainHook/ims', $fileContent, $regs)) {
                return sprintf(_("file %s not implement DomainHook"), $filepath);
            }
        }
    }
    /**
     * return object hook 
     * @return DomainHook
     */
    public function hook()
    {
        if (!$this->hookObject) {
            $hookPath = $this->getValue('off_hookpath');
            if ($hookPath) {
                if (!strstr($hookPath, '..')) {
                    include_once ($hookPath);
                    // search the classname
                    $fileContent = file_get_contents($hookPath);
                    if (preg_match('/class\s+([a-z_0-9]+)\s+implements\s+DomainHook/i', $fileContent, $regs)) {
                        $className = $regs[1];
                        $this->hookObject = new $className();
                    } else {
                        addWarningMsg("hook class not implement DomainHook");
                    }
                }
            }
        }
        return $this->hookObject;
    }
    
    /**
     * delete all user folder not used
     * domain unlock all documents which are not in a domain folder
     */
    public function cleanAll()
    {
        include_once ("FDL/Class.SearchDoc.php");
        $users = $this->getUserMembersInfo();
        $userIds = implode(',', array_keys($users));
        $sql = sprintf("update doc set lockdomainid = null where lockdomainid = %d and locked > 0 and locked not in (%s)", $this->id, $userIds);
        
        $err = $this->exec_query($sql);
        $fuid = array();
        foreach ( $users as $u ) {
            $fuid[] = $u["docid"];
        }
        if (count($fuid) > 0) {
            $userFids = "'" . implode("','", $fuid) . "'";
            $s = new SearchDoc($this->dbaccess, "OFFLINEFOLDER");
            $s->only = true;
            $s->addFilter("off_domain = '%d'", $this->id);
            $s->addFilter(sprintf("off_user not in (%s)", $userFids));
            $s->setObjectReturn();
            $s->search();
            while ( $doc = $s->nextDoc() ) {
                $err .= $doc->delete();
            }
        }
        
        return $err;
    }
    
/*
 * @begin-method-ignore
 * this part will be deleted when construct document class until end-method-ignore
 */
}

/*
 * @end-method-ignore
 */
?>