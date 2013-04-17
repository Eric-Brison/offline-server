<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/
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
     *
     * @param string $familyId family identificator
     * @param boolean $includeSubFamily set to false to not include sub families
     *
     * @return string error message (empty string if no errors)
     */
    public function addFamily($familyId, $includeSubFamily = true)
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
                    $err.= $this->setValue("off_families", $famids);
                    $err.= $this->setValue("off_subfamilies", $subfamids);
                    if (!$err) $err = $this->store();
                } else {
                    $err.= sprintf("not a  family %s [%d] not alive", $fam->getTitle() , $fam->id);
                }
            } else {
                $err.= sprintf("no family %s [%d] not alive", $fam->getTitle() , $fam->id);
            }
        } else {
            $err.= sprintf("no family given");
        }
        if ($err) {
            AddLogMsg(__METHOD__ . $err);
        }
        return $err;
    }
    
    public function addFollowingStates(Doc & $doc)
    {
        if (!$doc->wid) {
            return false;
        }
        if (($doc->lockdomainid == $this->id) && ($doc->locked == $this->getSystemUserId())) {
            $wdoc = new_doc($this->dbaccess, $doc->wid);
            /* @var $wdoc WDoc */
            if (!$wdoc->isAlive()) {
                return false;
            }
            if (!$this->canUseWorkflow($doc->fromid)) {
                return false;
            }
            try {
                $wdoc->Set($doc);
                $fs = $wdoc->getFollowingStates(true);
                $fsout = array();
                foreach ($fs as $state) {
                    $tr = $wdoc->getTransition($doc->state, $state);
                    $fsout[$state] = array(
                        "label" => _($state) ,
                        "color" => $wdoc->getColor($state) ,
                        "activity" => $wdoc->getActivity($state) ,
                        "transition" => isset($tr["id"]) ? _($tr["id"]) : sprintf(_("OFFLINE:Transition non authorized from %s to %s") , _($doc->state) , _($state))
                    );
                }
                $this->addExtraData($doc, "followingStates", $fsout);
                return true;
            }
            catch(Exception $e) {
                AddLogMsg(__METHOD__ . $e->getMessage());
            }
        }
        return false;
    }
    
    public function addExtraData(Doc & $doc, $key, $value)
    {
        /** @noinspection PhpUndefinedFieldInspection */
        $doc->addfields["pullextradata"] = "pullextradata";
        /** @noinspection PhpUndefinedFieldInspection */
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
                $err.= $this->setValue("off_subfamilies", $subfamids);
                if (!$err) $err = $this->store();
            } else {
                $err = sprintf("not a  family %s [%d] not alive", $fam->getTitle() , $fam->id);
            }
        }
        return $err;
    }
    /**
     * Add new supported family in offline domain
     *
     * @param string $familyId family identificator
     * @param boolean $includeSubFamily set to false to not include sub families
     * @param int $maskId id of a specific mask for family 0 means no mask
     *
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
                        /* @var $newMask _MASK */
                        /** @noinspection PhpUndefinedFieldInspection */
                        if ((!$newMask->isAlive()) || ($newMask->fromname != 'MASK')) {
                            $err = sprintf("not a mask %s", $maskId);
                        }
                    }
                    
                    if (!$err) {
                        $maskId = isset($newMask) ? $newMask->id : 0;
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
                        $domainFamily->setParam("off_mskfamilies", $famids);
                        $domainFamily->setParam("off_msksubfamilies", $subfamids);
                        $domainFamily->setParam("off_masks", $masks);
                        if (!$err) {
                            $err = $domainFamily->modify();
                        }
                    }
                } else {
                    $err = sprintf("not a  family %s [%d] not alive", $fam->getTitle() , $fam->id);
                }
            } else {
                
                $err = sprintf("no family %s [%d] not alive", $fam->getTitle() , $fam->id);
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
            foreach ($families as $currentFamily) {
                if ($currentFamily["off_families"]) {
                    $fams[] = $currentFamily["off_families"];
                    if ($currentFamily["off_subfamilies"] != "no") {
                        $fams = array_merge(array_keys($this->getChildFam($currentFamily["off_families"], false)) , $fams);
                    }
                }
            }
            
            $famids = array_unique($fams);
            
            $this->disableEditControl();
            $err = $this->setValue("off_allfamilies", $famids);
            $err.= $this->modify();
            
            if ($err) {
                AddLogMsg($err);
            }
            
            $this->enableEditControl();
        }
        return $famids;
    }
    
    public function canUseWorkflow($familyId)
    {
        $families = $this->getAValues("off_t_families");
        foreach ($families as $currentFamily) {
            if ($currentFamily["off_families"] == $familyId) {
                if ($currentFamily["off_useworkflow"] == "yes") {
                    return true;
                }
            }
        }
        return false;
    }
    /**
     * Generate a report file based on log for a user
     * @param int $userId
     * @param string $report
     * @return string error message (empty string if no errors)
     */
    public function updateReport($userId, &$report)
    {
        $userId = $this->getDomainUserId($userId);
        $folder = $this->getUserFolder($userId);
        $tmpfile = tempnam(getTmpDir() , 'syncReport');
        $report = $this->generateReport($userId);
        file_put_contents($tmpfile, $report);
        
        $folder->disableEditControl();
        $err = $folder->storeFile("off_report", $tmpfile, sprintf(_("Sync report %s.html") , date('Y-m-d')));
        if (!$err) $err = $folder->modify();
        $folder->enableEditControl();
        @unlink($tmpfile);
        return $err;
    }
    
    public function generateReport($userId)
    {
        global $action;
        $lay = new Layout(getLayoutFile("OFFLINE", "syncreport.html") , $action);
        $q = new QueryDb($this->dbaccess, "DocLog");
        $q->addQuery(sprintf("initid=%d", $this->id));
        $q->addQuery(sprintf("uid=%d", $userId));
        $q->order_by = "date desc";
        
        $r = $q->query(0, 1000, "TABLE");
        $tsync = array();
        foreach ($r as $k => $v) {
            
            $v = (object)$v;
            $v->arg = unserialize($v->arg);
            
            $tsync[] = array(
                "oddClass" => ($k % 2 == 0) ? "even" : "odd",
                "syncDate" => $this->reportGetDate($v) ,
                "syncCode" => substr($v->code, strlen('DomainSyncApi::')) ,
                "syncAction" => $this->reportGetAction($v) ,
                "syncMessage" => $this->reportGetMessage($v) ,
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
        $status = "";
        switch ($sync->code) {
            case 'DomainSyncApi::endTransaction':
                switch ($sync->arg->status) {
                    case DomainSyncApi::successTransaction:
                        $status = "ok";
                        foreach ($sync->arg->detailStatus as $dstatus) {
                            $dstatus = (object)$dstatus;
                            if ($dstatus->saveInfo->onAfterSaveChangeState || $dstatus->saveInfo->onAfterSaveDocument) {
                                $status = "warn";
                                break;
                            }
                        }
                        break;

                    case DomainSyncApi::partialTransaction:
                        $status = "partial";
                        break;

                    case DomainSyncApi::abortTransaction:
                        $status = "ko";
                        break;
                }
                break;

            default:
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
        $message = "";
        $updateMessage = "";
        $deleteMessage = "";
        
        switch ($sync->code) {
            case 'DomainSyncApi::endTransaction':
                $list = new DocumentList();
                $list->addDocumentIdentificators(array_keys($sync->arg->detailStatus));
                $msgdoc = array();
                foreach ($sync->arg->detailStatus as $docid => $status) {
                    if ($docid < 0) {
                        $msgdoc[$docid] = $this->reportFormatEndStatus((object)$status, _("new document"));
                    }
                }
                foreach ($list as $id => $doc) {
                    /* @var $doc Doc */
                    $status = (object)$sync->arg->detailStatus[$doc->initid];
                    $msgdoc[$id] = $this->reportFormatEndStatus($status, sprintf("%s <span>%s</span>", $doc->getTitle() , $doc->initid));
                }
                if (count($msgdoc) > 1) {
                    $message = '<ul><li>' . implode('</li><li>', $msgdoc) . '</li></ul>';
                } elseif (count($msgdoc) == 1) {
                    $message = current($msgdoc);
                } else {
                    $message.= _("no documents uploaded");
                }
                break;

            case 'DomainSyncApi::pushDocument':
                if ($sync->arg->refererinitid < 0) {
                    $message = sprintf(_("document creation %s") , $message = $sync->arg->title);
                } elseif ($sync->arg->refererinitid == null) {
                    $message = sprintf(_("document creation failed"));
                } else {
                    $message = $sync->arg->title;
                }
                if ($sync->arg->error) {
                    $message.= ' : ' . $sync->arg->error;
                }
                if ($sync->arg->message) {
                    $message.= ' : ' . $sync->arg->message;
                }
                break;

            case 'DomainSyncApi::beginTransaction':
                $message = $sync->arg->error;
                break;

            case 'DomainSyncApi::bookDocument':
            case 'DomainSyncApi::unbookDocument':
            case 'DomainSyncApi::revertDocument':
                if ($sync->arg->error) {
                    $message = sprintf("%s : %s", $sync->arg->title, $sync->arg->error);
                } else {
                    $message = sprintf(_("%s has been downloaded") , sprintf("%s <span>%s</span>", $sync->arg->title, $sync->arg->initid));
                }
                break;

            case 'DomainSyncApi::getUserDocuments':
            case 'DomainSyncApi::getSharedDocuments':
                if (is_array($sync->arg->documentsToUpdate)) {
                    $list = new DocumentList();
                    $list->addDocumentIdentificators($sync->arg->documentsToUpdate);
                    $msgdoc = array();
                    foreach ($list as $docid => $doc) {
                        /* @var $doc Doc */
                        $msgdoc[$docid] = sprintf("%s <span>%s</span>", $doc->getTitle() , $doc->initid);
                    }
                    if (count($msgdoc) > 1) {
                        $updateMessage = _("download documents :") . '<ul><li>' . implode('</li><li>', $msgdoc) . '</li></ul>';
                    } elseif (count($msgdoc) == 1) {
                        $updateMessage = sprintf(_("download document %s") , current($msgdoc));
                    } else {
                        $updateMessage = '';
                    }
                }
                if (is_array($sync->arg->documentsToDelete)) {
                    $list = new DocumentList();
                    $list->addDocumentIdentificators($sync->arg->documentsToDelete);
                    $msgdoc = array();
                    foreach ($list as $docid => $doc) {
                        $msgdoc[$docid] = $doc->getTitle();
                    }
                    if (count($msgdoc) > 1) {
                        $deleteMessage = _("delete documents :") . '<ul><li>' . implode('</li><li>', $msgdoc) . '</li></ul>';
                    } elseif (count($msgdoc) == 1) {
                        $deleteMessage = sprintf(_("delete document %s") , current($msgdoc));
                    } else {
                        $deleteMessage = '';
                    }
                }
                $message = '';
                if ($sync->arg->error) $message = $sync->arg->error;
                if ($updateMessage && $deleteMessage) {
                    $message.= nl2br($updateMessage . "\n" . $deleteMessage);
                } elseif ($updateMessage) {
                    $message.= $updateMessage;
                } elseif ($deleteMessage) {
                    $message.= $deleteMessage;
                } else {
                    $message.= _("no documents to retrieve");
                }
                break;

            default:
                $message = "-";
            }
            return $message;
    }
    
    private function reportFormatEndStatus($status, $title = '')
    {
        $msgConstraint = "";
        $msgdoc = "";
        $status->saveInfo = (object)$status->saveInfo;
        switch ($status->statusCode) {
            case 'constraint':
                if (count($status->saveInfo->constraint) > 0) {
                    $msgConstraint = '';
                    
                    foreach ($status->saveInfo->constraint as $constraint) {
                        $msgConstraint.= sprintf("%s : %s", $constraint["label"], $constraint["err"]);
                    }
                    $statusMessage = $msgConstraint;
                    $msgdoc = sprintf(_("%s following constraints are not validated: %s") , $title, $statusMessage);
                }
                break;

            case 'uptodate':
                $msgdoc = sprintf(_("%s has been recorded") , $title);
                break;

            default:
                $msgdoc = '';
        }
        $statusMessage = '';
        if ($status->saveInfo->onAfterSaveDocument) {
            $statusMessage.= sprintf(_("after save warning:%s\n") , $status->saveInfo->onAfterSaveDocument);
        }
        if ($status->saveInfo->onAfterSaveChangeState) {
            $statusMessage.= sprintf(("%s\n") , $status->saveInfo->onAfterSaveChangeState);
        }
        if (!$msgConstraint) {
            $statusMessage.= $status->statusMessage;
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
            /* @var $user User */
            if ($user->isAffected()) {
                /** @noinspection PhpUndefinedFieldInspection */
                if ($user->isgroup == 'Y') {
                    $aidMember = 'off_group_members';
                    $aidMode = 'off_group_mode';
                } else {
                    $aidMember = 'off_user_members';
                    $aidMode = 'off_user_mode';
                }
                $members = $this->getTValue($aidMember);
                $mode = $this->getTValue($aidMode);
                /** @noinspection PhpUndefinedFieldInspection */
                $key = array_search($user->fid, $members);
                if ($key === false) {
                    /** @noinspection PhpUndefinedFieldInspection */
                    $members[] = $user->fid;
                    $mode[] = ($hasManagePrivilege ? 'advanced' : 'standard');
                } else {
                    /** @noinspection PhpUndefinedFieldInspection */
                    $members[$key] = $user->fid;
                    $mode[$key] = ($hasManagePrivilege ? 'advanced' : 'standard');
                }
                $err.= $this->setValue($aidMember, $members);
                $err.= $this->setValue($aidMode, $mode);
                if (!$err) $err = $this->store();
            } else {
                $err.= sprintf("no user %s  not alive", $userId);
            }
        } else {
            $err.= sprintf("no user given");
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
                /** @noinspection PhpUndefinedFieldInspection */
                if ($user->isgroup == 'Y') {
                    $aidMember = 'off_group_members';
                    $aidMode = 'off_group_mode';
                } else {
                    $aidMember = 'off_user_members';
                    $aidMode = 'off_user_mode';
                }
                $members = $this->getTValue($aidMember);
                $mode = $this->getTValue($aidMode);
                /** @noinspection PhpUndefinedFieldInspection */
                $key = array_search($user->fid, $members);
                if ($key !== false) {
                    unset($members[$key]);
                    unset($mode[$key]);
                }
                $err.= $this->setValue($aidMember, $members);
                $err.= $this->setValue($aidMode, $mode);
                if (!$err) {
                    $err = $this->store();
                }
            } else {
                $err.= sprintf("no user %s  not alive", $userId);
            }
        } else {
            $err.= sprintf("no user given");
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
        $fids+= $this->getTValue("off_group_members");
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
        /** @noinspection PhpIncludeInspection */
        include_once ("FDL/Class.SearchDoc.php");
        
        $out = array();
        // group members
        $um = $this->getTValue("off_group_members");
        foreach ($um as $k => $v) {
            if (!$v) unset($um[$k]);
        }
        if (count($um) > 0) {
            $s = new SearchDoc($this->dbaccess, "IGROUP");
            $s->addFilter($s->sqlCond($um, "initid", true));
            $s->noViewControl();
            $users = $s->search();
            foreach ($users as $guser) {
                $g = new User($this->dbaccess, $guser["us_whatid"]);
                if ($g->isAffected()) {
                    $members = $g->getUserMembers();
                    foreach ($members as $user) {
                        $index = $useSystemId ? $user["id"] : $user["fid"];
                        $out[$index] = array(
                            "id" => $user["id"],
                            "docid" => $user["fid"],
                            "displayName" => trim($user["firstname"] . " " . $user["lasttname"]) ,
                            "login" => $user["login"]
                        );
                    }
                }
            }
        }
        // user members
        $um = $this->getTValue("off_user_members");
        foreach ($um as $k => $v) {
            if (!$v) unset($um[$k]);
        }
        if (count($um) > 0) {
            $s = new SearchDoc($this->dbaccess, "IUSER");
            $s->addFilter($s->sqlCond($um, "initid", true));
            $users = $s->search();
            foreach ($users as $user) {
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
     *
     * @param int $uid system identificator for a group or a single user. Can use also logical name of relative document
     *
     * @return boolean true if has, false else. - null if $userId not exists
     */
    public function isMember($uid = 0)
    {
        /** @noinspection PhpIncludeInspection */
        include_once ("FDL/Class.SearchDoc.php");
        $userId = $this->getDomainUserId($uid);
        $fid = $this->uid2fid($userId);
        
        if ($fid) {
            $um = $this->getTValue("off_user_members");
            if (in_array($fid, $um)) return true;
            
            $um = $this->getTValue("off_group_members");
            foreach ($um as $k => $v) {
                if (!$v) unset($um[$k]);
            }
            if (count($um) > 0) {
                $s = new SearchDoc($this->dbaccess, "IGROUP");
                $s->addFilter($s->sqlCond($um, "initid", true));
                $s->noViewControl();
                $users = $s->search();
                foreach ($users as $guser) {
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
     *
     * @param int $uid member systeme identificator
     *
     * @return array ["id"=>, "docid"=>,"displayName"=>,"managePrivilege"=>true/false,isGroup=>true/false]
     */
    public function getMemberInfo($uid)
    {
        $out = null;
        if ($this->isMember($uid)) {
            $user = new User($this->dbaccess, $uid);
            $userMode = $this->getUserMode($uid);
            /** @noinspection PhpUndefinedFieldInspection */
            $out = array(
                "id" => $user->id,
                "docid" => $user->fid,
                "displayName" => trim($user->firstname . ' ' . $user->lastname) ,
                "login" => $user->login,
                "managePrivilege" => ($userMode == 'advanced')
            );
        }
        return $out;
    }
    
    private function uid2fid($uid)
    {
        $err = simpleQuery($this->dbaccess, sprintf("select fid from users where id=%d", $uid) , $docuid, true, true);
        if (!$err) return $docuid;
        return 0;
    }
    /**
     * GetUserMode : advanced or standard mode
     *
     * @param int $uid user what id
     * @return string advanced or standard
     */
    public function getUserMode($uid)
    {
        $userMode = "";
        
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
                foreach ($ugroups as $k => $gid) {
                    if ($gu->setFid($gid)) {
                        $members = $gu->getUserMembers();
                        foreach ($members as $member) {
                            if ($member['id'] == $uid) {
                                $userMode = $this->getTValue("off_group_mode", '', $k);
                                break;
                            }
                        }
                        if ($userMode === 'advanced') {
                            break;
                        }
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
     *
     * @param Dir $folder
     * @param Doc $doc
     * @param int $userId user identificator (system id/or logical name)
     * @param boolean $reserve set to false if want readonly else reserved by $userId
     *
     * @return string error message (empty string if no errors)
     */
    private function insertDocument(Dir $folder, Doc & $doc, $userId = 0, $reserve = true)
    {
        if ($doc->isAlive()) {
            if ($reserve && ($doc->lockdomainid > 0) && ($doc->lockdomainid != $this->id)) {
                $err = sprintf(_("document is already book in other domain : %s") , $this->getTitle($doc->lockdomainid));
            } else {
                $point = "insertDocument" . $doc->id;
                $this->savePoint($point);
                $err = $folder->AddFile($doc->initid);
                if (!$err) {
                    if ($reserve) {
                        $err = $doc->lockToDomain($this->id);
                        if ($err != '') {
                            return $err;
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
            $err = sprintf(_("document to book not exists %s") , $doc->id);
        }
        return $err;
    }
    /**
     * send events for workspace interface
     * @param Doc $doc
     */
    private static function sendEvents(Doc & $doc)
    {
        global $action;
        $fdlids = $doc->getParentFolderIds();
        foreach ($fdlids as $fldid) {
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
    public function insertShareCollection(DocumentList $collection, $reservedBy = 0)
    {
        $sfolder = $this->getSharedFolder();
        if ($sfolder) {
            $err = $this->insertCollection($sfolder, $collection, $reservedBy, ($reservedBy > 0));
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
     * @param Dir $folder
     * @param DocumentList $collection document identificator
     * @param int $userId user identificator (system id/or logical name)
     * @param boolean $reserve set to false if want readonly else reserved by $userId
     * @return string error message (empty string if no errors)
     */
    private function insertCollection(Dir & $folder, DocumentList $collection, $userId, $reserve = true)
    {
        $err = '';
        foreach ($collection as $doc) {
            $err.= $this->insertDocument($folder, $doc, $userId, $reserve);
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
        /* TODO implement unlock */
        $ufolder = $this->getSharedFolder();
        return $this->clearFolder($ufolder);
    }
    /**
     * clear all documents from foldder
     * if unlock is true all document lock by current user are unlocked
     * @param Dir $folder
     * @param boolean $unlock set to false to not unlock documents
     * @return string error message (empty string if no errors)
     */
    private function clearFolder(Dir & $folder, $unlock = true)
    {
        $dl = $folder->getDocumentList();
        $err = $folder->clear();
        if ($err == "") {
            foreach ($dl as $doc) {
                /* @var $doc _OFFLINEDOMAIN */
                $doc->updateDomains();
                if ($unlock) {
                    $doc->unlock();
                }
            }
            /** @noinspection PhpIncludeInspection */
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
            if ($err != '') {
                return $err;
            }
            $err = $this->setReservation($doc->id, 0);
            if ($err != '') {
                return $err;
            }
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
            if (!is_numeric($userId)) {
                // search by login
                $user = new User($this->dbaccess);
                $user->SetLoginName($userId);
                if ($user->isAffected()) {
                    /** @noinspection PhpUndefinedFieldInspection */
                    $userId = $user->id;
                }
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
            foreach ($families as $k => $famid) {
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
     *
     * @throws Exception
     *
     * @return Dir the folder document (null is user is not recorded)
     */
    public function getUserFolder($userId = 0)
    {
        $userId = $this->getDomainUserId($userId);
        $user = new User($this->dbaccess, $userId);
        $userFolder = null;
        if ($user->isAffected()) {
            /** @noinspection PhpUndefinedFieldInspection */
            $login = $user->login;
            $userFolderId = $this->getUserFolderId($login);
            $userFolder = new_doc($this->dbaccess, $userFolderId);
            if (!$userFolder->isAlive()) {
                /* Quircks to handle old user notation*/
                /** @noinspection PhpUndefinedFieldInspection */
                $userArray = array(
                    "id" => $user->id,
                    "docid" => $user->fid,
                    "displayName" => trim($user->firstname . " " . $user->lasttname) ,
                    "login" => $user->login
                );
                $usersFolder = $this->getUsersFolder();
                $userFolder = $this->generateUserFolder($userFolderId, $userArray, $usersFolder);
            }
        } else {
            throw new Exception(__METHOD__ . " user $userId is not affected");
        }
        return $userFolder;
    }
    /**
     * return all documents reserved by user
     * @param int $userId system user identificator
     * @return array of document id
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
            foreach ($dl as $v) {
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
            foreach ($dl as $v) {
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
    /**
     * postModify : create subdirectories
     *
     * @return string|void
     */
    public function postModify()
    {
        $err = $this->createSubDirectories();
        $err.= $this->deleteValue("off_allfamilies");
        return $err;
    }
    /**
     * preCreated : check if reference is unique
     *
     * @return string|void
     */
    public function preCreated()
    {
        $ref = $this->getValue("off_ref");
        if ($ref) {
            $exists = $this->getTitle($ref);
            if ($exists) {
                return sprintf(_("reference %s exists") , $ref);
            }
        }
        return '';
    }
    /**
     * postCreated : create user subdirectories
     *
     * @return string|void
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
     *
     * @return string
     */
    private function getShareId()
    {
        return sprintf("offshared_%s", $this->name);
    }
    /**
     * add unique logical name for user folder
     *
     * @param string $login user login
     * @return string
     */
    private function getUserFolderId($login)
    {
        return sprintf("offuser_%s_%s", $this->name, $login);
    }
    /**
     * Create subdir of the offline domain
     *
     * Create shared folder and users folders
     *
     * @return string
     */
    public function createSubDirectories()
    {
        $err = "";
        if (!$this->name) {
            $err.= $this->setLogicalIdentificator($this->getValue("off_ref"));
        }
        if (($this->getValue("off_sharepolicy") == "admin") || ($this->getValue("off_sharepolicy") == "users")) {
            $sharedID = $this->getShareId();
            $sharedFolder = new_doc($this->dbaccess, $sharedID);
            if (!$sharedFolder->isAlive()) {
                $sharedFolder = createDoc($this->dbaccess, "OFFLINEGLOBALFOLDER", false);
                $sharedFolder->setValue("ba_title", sprintf(_("%s Share folder") , $this->getTitle()));
                $sharedFolder->setValue("off_domain", $this->id);
                $err.= $sharedFolder->add();
                $err.= $sharedFolder->setLogicalIdentificator($sharedID);
                $err.= $this->addFile($sharedFolder->initid);
            } else {
                $sharedFolder = new_doc($this->dbaccess, $sharedID);
                $err.= $this->addFile($sharedFolder->initid);
            }
            $sharedFolder->disableEditControl();
            $sharedFolder->setValue("off_admins", $this->getValue("off_admins"));
            $sharedFolder->setValue("off_users", array_merge($this->getTValue("off_group_members") , $this->getTValue("off_user_members")));
            $sharedFolder->setValue("fld_allbut", "1"); // add restrictions
            $sharedFolder->setValue("fld_famids", $this->getValue("off_families"));
            $sharedFolder->setValue("fld_subfam", $this->getValue("off_subfamilies"));
            $err.= $sharedFolder->modify();
            if ($this->getValue("off_sharepolicy") == "admin") {
                $sharedFolder->setProfil("PRF_OFFGLOBFOLDERADMIN");
            } elseif ($this->getValue("off_sharepolicy") == "users") {
                $sharedFolder->setProfil("PRF_OFFGLOBFOLDERUSER");
            }
            $sharedFolder->enableEditControl();
        } else {
            $sharedID = $this->getShareId();
            $sharedFolder = new_doc($this->dbaccess, $sharedID);
            if ($sharedFolder->isAlive()) {
                // need to delete it
                $err.= $this->clearSharedFolder();
                if ($err == "") {
                    $sharedFolder->delete();
                }
            }
        }
        $usersID = $this->getUsersFolderId();
        try {
            $usersFolder = $this->generateUsersFolder($usersID);
            $members = $this->getUserMembersInfo(true);
            foreach ($members as $member) {
                $userFolderID = $this->getUserFolderId($member["login"]);
                $this->generateUserFolder($userFolderID, $member, $usersFolder);
            }
        }
        catch(Exception $e) {
            $err.= $e->getMessage();
        }
        
        return $err;
    }
    /**
     * Return or generateUser Folder
     *
     * @param string $usersID users folder Id
     *
     * @return DIR
     *
     * @throws Exception
     */
    public function generateUsersFolder($usersID)
    {
        $err = "";
        $usersFolder = new_doc($this->dbaccess, $usersID);
        /* @var $usersFolder DIR */
        if (!$usersFolder->isAlive()) {
            $usersFolder = createDoc($this->dbaccess, "DIR", false);
            $usersFolder->setValue("ba_title", sprintf(_("%s Users folder") , $this->getTitle()));
            $usersFolder->setValue("off_domain", $this->id);
            
            $err.= $usersFolder->add();
            $err.= $usersFolder->setLogicalIdentificator($usersID);
            $err.= $this->addFile($usersFolder->initid);
        } else {
            $usersFolder = new_doc($this->dbaccess, $usersID);
            $err.= $this->addFile($usersFolder->initid);
        }
        $usersFolder->disableEditControl();
        $usersFolder->setValue("fld_allbut", "1"); // add restrictions
        $usersFolder->setValue("fld_famids", getFamIdFromName($this->dbaccess, "OFFLINEFOLDER"));
        $usersFolder->setValue("fld_subfam", "no");
        $usersFolder->setValue("off_admins", $this->getValue("off_admins"));
        $usersFolder->setValue("off_users", array_merge($this->getTValue("off_group_members") , $this->getTValue("off_user_members")));
        $err.= $usersFolder->modify();
        $usersFolder->enableEditControl();
        if ($err) {
            throw new Exception(__METHOD__ . $err);
        }
        return $usersFolder;
    }
    
    protected function getUsersFolderId()
    {
        $usersID = sprintf("offusers_%s", $this->name);
        return $usersID;
    }
    /**
     * getUsersFolder
     *
     * @return Dir
     * @throws Exception
     */
    protected function getUsersFolder()
    {
        $usersFolderId = $this->getUsersFolderId();
        $usersFolder = new_Doc("", $usersFolderId);
        if (!$usersFolder->isAlive()) {
            throw new Exception(__METHOD__ . " usersFolder : $usersFolderId");
        }
        return $usersFolder;
    }
    /**
     * Generate a userFolder
     *
     * @param String $userFolderID
     * @param Array $member
     * @param Dir $usersFolder
     *
     * @throws Exception
     *
     * @return Dir
     */
    protected function generateUserFolder($userFolderID, $member, Dir $usersFolder)
    {
        $err = "";
        $userFolder = new_doc($this->dbaccess, $userFolderID);
        if (!$userFolder->isAlive()) {
            $userFolder = createDoc($this->dbaccess, "OFFLINEFOLDER", false);
            $userFolder->setValue("ba_title", sprintf(_("%s User folder") , $member["login"]));
            $userFolder->setValue("off_domain", $this->id);
            $userFolder->setValue("off_user", $member["docid"]);
            
            $err.= $userFolder->add();
            $err.= $userFolder->setLogicalIdentificator($userFolderID);
            $err.= $usersFolder->addFile($userFolder->initid);
        } else {
            $userFolder = new_doc($this->dbaccess, $userFolderID);
            $err.= $usersFolder->addFile($userFolder->initid);
        }
        $userFolder->disableEditControl();
        if ($this->hasManagePrivilege($member["id"])) {
            $userFolder->setValue("off_advanceduser", $member["docid"]);
        } else {
            $userFolder->deleteValue("off_advanceduser");
        }
        $userFolder->setValue("fld_allbut", "1"); // add restrictions
        $userFolder->setValue("fld_famids", $this->getValue("off_families"));
        $userFolder->setValue("fld_subfam", $this->getValue("off_subfamilies"));
        $err.= $userFolder->modify();
        $userFolder->enableEditControl();
        if ($err) {
            throw new Exception(__METHOD__ . ' ' . $err);
        }
        return $userFolder;
    }
    /**
     * Test if the file exist and if it contains a class that implements DomainHook
     *
     * @param $filepath
     * @return string
     */
    public function isPHPFile($filepath)
    {
        if ($filepath) {
            if (strstr($filepath, '..')) {
                return sprintf(_("file %s must be relative") , $filepath);
            }
            if (!file_exists(sprintf("%s/%s", DEFAULT_PUBDIR, $filepath))) {
                return sprintf(_("file %s not exists") , $filepath);
            }
            if (!preg_match('/\.php$/', $filepath)) {
                return sprintf(_("file %s must be a PHP file") , $filepath);
            }
            $fileContent = file_get_contents($filepath);
            /* TODO : use reflection */
            if (!preg_match('/class\s+([a-z_0-9]+)\s+implements\s+DomainHook/ims', $fileContent, $regs)) {
                return sprintf(_("file %s not implement DomainHook") , $filepath);
            }
        }
        return "";
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
                    /** @noinspection PhpIncludeInspection */
                    require_once $hookPath;
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
        /** @noinspection PhpIncludeInspection */
        include_once ("FDL/Class.SearchDoc.php");
        $users = $this->getUserMembersInfo();
        $userIds = implode(',', array_keys($users));
        $sql = sprintf("update doc set lockdomainid = null where lockdomainid = %d and locked > 0 and locked not in (%s)", $this->id, $userIds);
        
        $err = $this->exec_query($sql);
        $fuid = array();
        foreach ($users as $u) {
            $fuid[] = $u["docid"];
        }
        if (count($fuid) > 0) {
            $userFids = "'" . implode("','", $fuid) . "'";
            $searchDoc = new SearchDoc($this->dbaccess, "OFFLINEFOLDER");
            $searchDoc->only = true;
            $searchDoc->addFilter("off_domain = '%d'", $this->id);
            $searchDoc->addFilter(sprintf("off_user not in (%s)", $userFids));
            $searchDoc->setObjectReturn();
            $searchDoc->search();
            while ($doc = $searchDoc->nextDoc()) {
                $doc->delete();
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

