<?php
/**
 * Return offline domains where current user is affected
 *
 * @author Anakeen
 * @version $Id:  $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package OFFLINE
 */
/**
 */

include_once ("DATA/Class.Collection.php");
include_once ("OFFLINE/Class.ExceptionCode.php");
include_once ("FDL/Class.DocWaitManager.php");

class DomainSyncApi
{
    const abortTransaction = "abortTransaction";
    const successTransaction = "successTransaction";
    const partialTransaction = "partialTransaction";
    const documentNotRecorded = "documentNotRecorded";
    const newPrefix = "DLID-";
    /**
     * internal domain document
     * @var _OFFLINEDOMAIN
     */
    private $domain = null;
    /**
     * parent object
     * @var DomainApi
     */
    private $domainApi = null;
    public function __construct(Dir &$domain = null, DomainApi &$domainApi = null)
    {
        $this->domain = $domain;
        $this->domainApi = $domainApi;
    }
    private static function setError($err)
    {
        throw new Exception($err);
    }
    
    /**
     * test if document must be returned to the client
     * 
     * @param Doc $doc
     * @param array $stillRecorded
     * @return true is client document is uptodate
     */
    public static function isUpToDate(Doc &$doc, array &$stillRecorded)
    {
       
        if (!$stillRecorded[$doc->initid]) return false;
        if (intval($stillRecorded[$doc->initid]->locked) != intval($doc->locked)) return false;
        if (intval($stillRecorded[$doc->initid]->lockdomainid) != intval($doc->lockdomainid)) return false;
        if ($stillRecorded[$doc->initid]->revdate >= $doc->revdate) return true;
        return false;
    }
    
    /**
     * get share folder documents
     * @return DocumentList
     */
    public function getSharedDocuments($config)
    {
        $err = $this->callHook("onBeforePullSharedDocuments");
        if (!$err) {
            $callback = null;
            $stillRecorded = array();
            if (is_array($config->stillRecorded)) {
                
                foreach ( $config->stillRecorded as $record ) {
                    $stillRecorded[$record->initid] = $record;
                }
            }
 
            if ($this->domain->hook()) {
                $domain = $this->domain;
                $callback = function (&$doc) use($domain, $stillRecorded)
                {
                    $isUpToDate = DomainSyncApi::isUpToDate($doc, $stillRecorded);
                    if ($isUpToDate) return false;
                    $err = call_user_func_array(array(
                        $domain->hook(),
                        $method = "onPullDocument"
                    ), array(
                        &$domain,
                        &$doc
                    ));
                    
                    return (empty($err) || ($err === true));
                };
            } else {
                if (count($stillRecorded) > 0) {
                    $callback = function (&$doc) use($stillRecorded)
                    {
                        $isUpToDate = DomainSyncApi::isUpToDate($doc, $stillRecorded);
                        if ($isUpToDate) return false;
                        return true;
                    };
                }
            }
            $out = $this->domainApi->getSharedDocuments($config, $callback);
            $out->documentsToDelete = $this->getIntersect($this->domain->getSharedFolder(), $stillRecorded);
        
        } else {
            $out->error = $err;
        }
        $log = '';
        $log->error = $out->error;
        $log->documentsToDelete = $out->documentsToDelete;
        if (is_array($out->content)) {
            foreach ( $out->content as &$rdoc ) {
                $log->documentsToUpdate[] = $rdoc["properties"]["id"];
            }
        }
        $this->domain->addLog(__METHOD__, $log);
        return $out;
    }
    
    private function getIntersect(Dir &$folder, array &$stillRecorded)
    {
        $serverInitids = $folder->getContentInitid();
        $clientInitids = array_keys($stillRecorded);
        return array_values(array_diff($clientInitids, $serverInitids));
    }
    
    /**
     * unbook document into user space
     * @return Fdl_Document
     */
    public function revertDocument($config)
    {
        $docid = $config->docid;
        $doc = new_doc(getDbaccess(), $docid, true);
        if ($doc->isAlive()) {
            $err = $this->callHook("onPullDocument", $doc);
            if ($err == "" || $err === true) {
                $this->domain->addFollowingStates($doc);
                
                $out = $this->domainApi->revertDocument($config);
            } else {
                $out->error = $err;
            }
        } else {
            $out->error = sprintf(_("document %s not found"), $docid);
        }
        $log = "";
        $log->initid = $doc->initid;
        $log->title = $doc->getTitle();
        $log->error = $out->error;
        $this->domain->addLog(__METHOD__, $log);
        return $out;
    }
    
    /**
     * get user folder documents
     * @return DocumentList
     */
    public function getUserDocuments($config)
    {
        $err = $this->callHook("onBeforePullUserDocuments");
        if (!$err) {
            $callback = null;
            $stillRecorded = array();
            if (is_array($config->stillRecorded)) {
                foreach ( $config->stillRecorded as $record ) {
                    $stillRecorded[$record->initid] = $record;
                }
            }
            if ($this->domain->hook()) {
                $domain = $this->domain;
                $callback = function (&$doc) use($domain, $stillRecorded)
                {
                    $isUpToDate = DomainSyncApi::isUpToDate($doc, $stillRecorded);
                    if ($isUpToDate) return false;
                    $domain->addFollowingStates($doc);
                    $err = call_user_func_array(array(
                        $domain->hook(),
                        $method = "onPullDocument"
                    ), array(
                        &$domain,
                        &$doc
                    ));
                    return (empty($err) || ($err === true));
                };
            } else {
                    $domain = $this->domain;
                    $callback = function (&$doc) use($domain,$stillRecorded)
                    {
                        $isUpToDate = DomainSyncApi::isUpToDate($doc, $stillRecorded);
                        if ($isUpToDate) return false;
                        $domain->addFollowingStates($doc);
                        return true;
                    };
            }
            $out = $this->domainApi->getUserDocuments($config, $callback);
            $out->documentsToDelete = $this->getIntersect($this->domain->getUserFolder(), $stillRecorded);
        } else {
            $out->error = $err;
        }
        $log = '';
        $log->error = $out->error;
        $log->documentsToDelete = $out->documentsToDelete;
        if (is_array($out->content)) {
            foreach ( $out->content as &$rdoc ) {
                $log->documentsToUpdate[] = $rdoc["properties"]["id"];
            }
        }
        $this->domain->addLog(__METHOD__, $log);
        return $out;
    }
    
    /**
     * get Acknowledgement after user folder documents
     * @return string
     */
    public function getUserDocumentsAcknowledgement($config)
    {
        $out = '';
        $out->acknowledgement = $this->callHook("onAfterPullUserDocuments");
        return $out;
    }
    
    /**
     * get Acknowledgement after user folder documents
     * @return string
     */
    public function getSharedDocumentsAcknowledgement($config)
    {
        $out = '';
        $out->acknowledgement = $this->callHook("onAfterPullSharedDocuments");
        return $out;
    }
    /**
     * set file to document
     * @return D document List
     */
    public function pushFile($config)
    {
        //print_r($config);
        $docid = $config->docid;
        $aid = $config->aid;
        $index = -1;
        if (preg_match('/(.*)\[([0-9]+)\]$/', $aid, $reg)) {
            $index = $reg[2];
            $aid = trim($reg[1]);
        }
        $path = 'php://input';
        $out = '';
        $tmpfile = tempnam(getTmpDir(), 'pushFile');
        if ($tmpfile == false) {
            $err = sprintf("cannot create temporay file %s", $tmpfile);
        } else {
            copy($path, $tmpfile);
            $filename = $config->filename;
            
            if ($this->isLocalIdenticator($docid)) {
                $localid = $docid;
                $docid = $this->numerizeId($docid);
            }
            $wdoc = DocWaitManager::getWaitingDoc($docid);
            //$doc = new_doc(getDbAccess(), $docid);
            if ($wdoc) {
                $doc = $wdoc->getWaitingDocument();
                // print $doc->getTitle();
                $oa = $doc->getAttribute($aid);
                // print_r($oa);
                if ($oa) {
                    if (!$doc->id) {
                        // it is a new doc
                        $doc->id = 0;
                        $doc->initid = $docid;
                        $doc->localid = $localid;
                    }
                    $err = $doc->storeFile($oa->id, $tmpfile, $filename, $index);
                    @unlink($tmpfile);
                    $err = DocWaitManager::saveWaitingDoc($doc, $this->domain->id, $config->transaction);
                }
            
     // $err = DocWaitManager::saveWaitingDoc($doc);
            }
        }
        $this->domain->addLog(__METHOD__, $out);
        $out->error = $err;
        return $out;
    }
    /**
     * 
     * Enter description here ...
     * @param unknown_type $rawdoc
     * 
     */
    private function raw2doc($rawdoc, &$doc)
    {
        $fromid = $rawdoc->properties->fromid;
        $doc = createDoc(getDbAccess(), $fromid, false, false, false); // no default values
        $err = '';
        if (!$doc) {
            $err = sprintf("cannot create document %s", $fromid);
        } else {
            $props = array();
            if ($this->isNewDocument($rawdoc)) {
                $rawdoc->properties->localid = $rawdoc->properties->id;
                $rawdoc->properties->initid = $this->numerizeId($rawdoc->properties->id);
                $rawdoc->properties->id = 0;
            }
            foreach ( $rawdoc->properties as $k => $v ) {
                if (is_array($v)) $v = implode("\n", $v);
                $props[$k] = $v;
            }
            $doc->affect($props);
            foreach ( $rawdoc->values as $k => $v ) {
                
                $oa = $doc->getAttribute($k);
                if ($oa) {
                    if ($oa->type == "docid") {
                        $v = $this->numerizeAllId($v);
                    }
                    if ($v == '') $v = " ";
                    $serr = $doc->setValue($k, $v);
                    if ($serr) {
                        $err .= sprintf("%s : %s", $oa->getLabel(), $serr);
                    }
                }
            }
            $doc->locked = -1; // to not be updated
        }
        return $err;
    
    }
    
    private function isNewDocument($rawdoc)
    {
        return $this->isLocalIdenticator($rawdoc->properties->id);
    }
    private function isLocalIdenticator($id)
    {
        if (preg_match('/^' . $this::newPrefix . '/', $id)) return true;
        return false;
    }
    
    /**
     * Modify waiting doc
     * @return Fdl_Document document 
     */
    public function pushDocument($config)
    {
        $rawdoc = $config->document;
        
        if ($rawdoc) {
            $out = '';
            $doc = null;
            
            $extraData = $rawdoc->properties->pushextradata;
            if (!$this->isNewDocument($rawdoc)) {
                $refdoc = new_doc(getDbAccess(), $rawdoc->properties->id, true);
                $err = $this->verifyPrivilege($refdoc);
            }
            if ($err == "") {
                
                $err = $this->raw2doc($rawdoc, $doc);
            }
            
            if ($err == "") {
                $err = $this->callHook("onBeforePushDocument", $doc, $extraData);
                
                if (!$err) {
                    
                    $err = DocWaitManager::saveWaitingDoc($doc, $this->domain->id, $config->transaction, $extraData);
                }
                if ($err) {
                    $out->error = $err;
                } else {
                    $message = $this->callHook("onAfterPushDocument", $doc, $extraData);
                    $fdoc = new Fdl_Document($doc->id, null, $doc);
                    $out = $fdoc->getDocument(true, false);
                    $out["message"] = $message;
                }
            } else {
                $waitDoc = DocWaitManager::getWaitingDoc($rawdoc->properties->initid);
                if (!$waitDoc) {
                    $doc = new_doc(getDbAccess(), $rawdoc->properties->id, true);
                    $err = DocWaitManager::saveWaitingDoc($doc, $this->domain->id, $config->transaction, $extraData);
                } else {
                    $waitDoc->transaction = $config->transaction;
                    $waitDoc->status = $waitDoc::invalid;
                    $waitDoc->statusmessage = $err;
                    $waitDoc->modify();
                }
                $out->error = sprintf(_("push:invalid document : %s"), $err);
            }
        } else {
            $out->error = _("push:no document found");
        }
        if (!$waitDoc) {
            $waitDoc = DocWaitManager::getWaitingDoc($rawdoc->properties->initid);
        }
        if ($waitDoc) {
            $log = (object) $waitDoc->getValues();
            unset($log->orivalues);
            unset($log->values);
        } else {
            $log = '';
        }
        $log->error = $out->error;
        if (is_array($out)) {
            $log->message = $out["message"];
        }
        $this->domain->addLog(__METHOD__, $log);
        return $out;
    }
    private function callHook($method, &$arg1 = null, &$arg2 = null, &$arg3 = null)
    {
        
        if ($this->domain->hook()) {
            if (method_exists($this->domain->hook(), $method)) {
                return call_user_func_array(array(
                    $this->domain->hook(),
                    $method
                ), array(
                    &$this->domain,
                    &$arg1,
                    &$arg2,
                    &$arg3
                ));
            }
        }
        return null;
    }
    
    /**
     * reset all waitings Transaction
     * @return object transactionId
     */
    public function resetWaitingDocs()
    {
        include_once ("FDL/Class.DocWaitManager.php");
        
        $err = DocWaitManager::clearWaitingDocs($this->domain->id, $this->domain->getSystemUserId());
        
        $out = '';
        $out->error = $err;
        
        return $out;
    }
    
    /**
     * update report file 
     * store file user folder
     * @return string content of the file
     */
    public function getReport($config)
    {
        $report='';
        $err = $this->domain->updateReport($this->domain->getSystemUserId(), &$report);
        $out = '';
        if (!$err) $out->report = $report;
        $out->error = $err;
        
        return $out;
    }
    /**
     * Begin Transaction
     * @return object transactionId
     */
    public function beginTransaction()
    {
        $err = $this->callHook("onBeforePushTransaction");
        $out = '';
        $out->error = $err;
        if (!$err) {
            $out->transactionId = DocWaitManager::getTransaction();
        }
        $this->domain->addLog(__METHOD__, $out);
        return $out;
    }
    
    /**
     * Verify all document in list to computeStatus
     * @param DbObjectList $waitings
     * @param stdClass $out
     */
    private function verifyAllConflict(DbObjectList &$waitings, &$out)
    {
        $err = '';
        foreach ( $waitings as $k => $waitDoc ) {
            $status = $waitDoc->computeStatus();
            $out->detailStatus[$waitDoc->refererinitid] = array(
                "statusMessage" => $waitDoc->statusmessage ? $waitDoc->statusmessage : _("verified"),
                "statusCode" => $waitDoc->status
            );
            if (!$waitDoc->isValid()) {
                $err = $waitDoc->statusmessage;
            }
        }
        return $err;
    }
    
    public function verifyPrivilege(Doc &$doc)
    {
        if (!$this->domain->isMember()) return _("not a member domain");
        $err = $doc->canEdit(false);
        
        if (!$err) {
            // verify domain lock
            if ($doc->lockdomainid != $this->domain->id) $err = sprintf(_("lock must be in domain %s"), $this->domain->getTitle());
        
        }
        
        return $err;
    }
    /*
    function numerizeLocalLinks(Doc &$doc)
    {
        $oas = $doc->getNormalAttributes();
        foreach ( $oas as $aid => $oa ) {
            if ($oa->type == "docid") {
                $value = $doc->getValue($aid);
                if ($value) {
                    $doc->setValue($aid, preg_replace("/(DLID-[a-f0-9-]+)/se", "\$this->numerizeId('\\1')", $value));
                }
            }
        
        }
    }*/
    
    private function numerizeAllId($s)
    {
        return preg_replace("/(DLID-[a-f0-9-]+)/se", "\$this->numerizeId('\\1')", $s);
    }
    /**
     * localid to numeric id
     * @param string $s DLID-<uuid>
     * @return int
     */
    private function numerizeId($s)
    {
        $u = crc32($s);
        if ($u < 0) return $u;
        $u = abs($u);
        if (($u >> 31) == 0) return -($u);
        return -round($u / 2);
    }
    /**
     * change local relation link by server document identificator
     * @param stdClass $results
     */
    private function updateLocalLink(&$results)
    {
        $details = $results->detailStatus;
        $localIds = array();
        $serverIds = array();
        foreach ( $details as $k => $v ) {
            $lid = $v['localId'];
            if ($lid) {
                $localIds[] = $this->numerizeId($lid);
                $serverIds[] = $k;
            }
        }
        
        $list = new DocumentList();
        $list->addDocumentIdentificators($serverIds);
        foreach ( $list as $id => $doc ) {
            $oas = $doc->getNormalAttributes();
            $needModify = false;
            foreach ( $oas as $aid => $oa ) {
                if ($oa->type == "docid") {
                    $value = $doc->getValue($aid);
                    if ($value) {
                        $nvalue = str_replace($localIds, $serverIds, $value);
                        if ($nvalue != $value) {
                            $doc->setValue($aid, $nvalue);
                            $needModify = true;
                        }
                    }
                }
            }
            if ($needModify) $doc->modify();
        }
    }
    /**
     * End transaction
     * @return object 
     */
    public function endTransaction($config)
    {
        if ($config->transaction) {
            $out = '';
            $err = '';
            $waitings = DocWaitManager::getWaitingDocs($config->transaction);
            
            $policy = $this->domain->getValue('off_transactionpolicy');
            if ($policy == "global") {
                // need verify global conflict
                $status = $this->verifyAllConflict($waitings, $out);
                $err = $status;
            }
            if (!$err) {
                $err = $this->callHook("onAfterPushTransaction");
            }
            if (!$err) {
                
                $out->detailStatus = array();
                $beforeSavePoint = "synchro" . $config->transaction;
                if ($policy == "global") {
                    $this->domain->savePoint($beforeSavePoint);
                }
                
                // main save is here
                $out->detailStatus = $this->saveWaitings($waitings);
                
                // analyze results
                $completeSuccess = true;
                $allFailure = true;
                foreach ( $out->detailStatus as $aStatus ) {
                    
                    if ($aStatus['isValid']) {
                        $allFailure = false;
                    } else {
                        $completeSuccess = false;
                    }
                }
                $message = '';
                if ($allFailure) {
                    if (count($out->detailStatus) > 0) {
                        $out->status = self::abortTransaction;
                    } else {
                        // nothing has be done / no work is a good work
                        $out->status = self::successTransaction;
                    }
                } else {
                    $out->status = $completeSuccess ? self::successTransaction : self::partialTransaction;
                    if ($completeSuccess || ($policy != "global")) {
                        $this->updateLocalLink($out);
                        $message = $this->callHook("onAfterSaveTransaction");
                    }
                }
                
                if ($policy == "global") {
                    if ($out->status == self::successTransaction) {
                        $this->domain->commitPoint($beforeSavePoint);
                    } else {
                        $out->status = self::abortTransaction; // no partial in global mode
                        $this->domain->rollbackPoint($beforeSavePoint);
                    }
                }
                $out->message = $message;
                $out->error = $err;
            } else {
                $out->status = self::abortTransaction;
                $out->statusMessage = $err;
            }
        } else {
            $out->error = _("endTransaction:no transaction identificator");
            $out->status = self::abortTransaction;
        }
        $this->domain->addLog(__METHOD__, $out);
        $ufolder = $this->domain->getUserFolder();
        $out->manageWaitingUrl = getParam("CORE_EXTERNURL") . '?app=OFFLINE&action=OFF_ORGANIZER&domain=0&dirid=' . $ufolder->id . '&transaction=' . $config->transaction;
        return $out;
    }
    
    private function saveWaitings(&$waitings)
    {
        $out = array();
        foreach ( $waitings as $k => $waitDoc ) {
            if ($waitDoc->status == $waitDoc::invalid) {
                $out[$waitDoc->refererinitid] = array(
                    "statusMessage" => $waitDoc->statusmessage,
                    "statusCode" => $waitDoc->status,
                    "isValid" => false
                );
            } else {
                $waitPoint = "docw" . $k;
                $this->domain->savePoint($waitPoint);
                
                $eExtra = $waitDoc->getExtraData();
                $saveerr = $this->callHook("onBeforeSaveDocument", $waitDoc->getWaitingDocument(), $waitDoc->getRefererDocument(), $eExtra);
                $savectxerr='';
                if (!$saveerr) {
                    if ($waitDoc->getRefererDocument()) {
                        $saveerr = $this->verifyPrivilege($waitDoc->getRefererDocument());
                        $savectxerr="getRefererDocument";
                    }
                } else {
                    $savectxerr="onBeforeSaveDocument";
                }
                if ($saveerr == "") {
                    $saveInfo = null;
                    $saveerr = $waitDoc->save($saveInfo);
                    $out[$waitDoc->refererinitid] = array(
                        "statusMessage" => $waitDoc->statusmessage,
                        "saveInfo" => $saveInfo,
                        "statusCode" => $waitDoc->status,
                        "localId" => $waitDoc->localid,
                        "isValid" => $waitDoc->isValid()
                    );
                    if ($saveerr == '') {
                        if ($waitDoc->localid) {
                            $this->domain->insertUserDocument($waitDoc->refererinitid, $this->domain->getSystemUserId(), true);
                            $morelinks = $this->resolveLocalLinks($waitDoc->localid, $waitDoc->refererinitid);
                            foreach ( $morelinks as $mid => $link ) {
                                if (!$out[$mid]) $out[$mid] = $link;
                            }
                        }
                        $message = $this->callHook("onAfterSaveDocument", $waitDoc->getRefererDocument(), $eExtra);
                        $out[$waitDoc->refererinitid]["saveInfo"]->onAfterSaveDocument = $message;
                        if ($eExtra->changeState) {
                            $message = $this->afterSaveChangeState($waitDoc->getRefererDocument(), $eExtra->changeState);
                            $out[$waitDoc->refererinitid]["saveInfo"]->onAfterSaveChangeState = $message;
                        }
                        $waitDoc->getRefererDocument()->addComment("synchronised");
                        $this->domain->commitPoint($waitPoint);
                    } else {
                        $this->domain->rollbackPoint($waitPoint);
                        // need to redo modify cause rollback
                        $waitDoc->status = $out[$waitDoc->refererinitid]["statusCode"];
                        $waitDoc->statusmessage = $out[$waitDoc->refererinitid]["statusMessage"];
                        $waitDoc->modify();
                        if ($waitDoc->getRefererDocument()) {
                            $waitDoc->getRefererDocument()->addComment(sprintf(_("synchro: %s"), $saveerr), HISTO_ERROR);
                        }
                    }
                } else {
                    $out[$waitDoc->refererinitid] = array(
                        "statusMessage" => $saveerr,
                        "statusContext" => $savectxerr,
                        "statusCode" => self::documentNotRecorded,
                        "isValid" => false
                    );
                    $this->domain->rollbackPoint($waitPoint);
                    
                    // need to redo modify cause rollback
                    $waitDoc->status = $out[$waitDoc->refererinitid]["statusCode"];
                    $waitDoc->statusmessage = $out[$waitDoc->refererinitid]["statusMessage"];
                    $waitDoc->modify();
                    $waitDoc->getRefererDocument()->addComment(sprintf(_("synchro: %s"), $waitDoc->statusmessage), HISTO_ERROR);
                }
            }
        
        }
        
        return $out;
    }
    
    private function resolveLocalLinks($localId, $serverId)
    {
        $numLocalId = $this->numerizeId($localId);
        $waitings = DocWaitManager::getWaitingDocsByDomain($this->domain->id);
        $out = array();
        foreach ( $waitings as $k => $waitDoc ) {
            if ($waitDoc->status == $waitDoc::upToDate) {
                
                $doc = $waitDoc->getRefererDocument();
                if ($doc) {
                    $oas = $doc->getNormalAttributes();
                    $needModify = false;
                    foreach ( $oas as $aid => $oa ) {
                        if ($oa->type == "docid") {
                            $value = $doc->getValue($aid);
                            if ($value) {
                                $nvalue = str_replace($numLocalId, $serverId, $value);
                                if ($nvalue != $value) {
                                    $doc->setValue($aid, $nvalue);
                                    $needModify = true;
                                }
                            }
                        }
                    }
                    if ($needModify) {
                        $doc->modify();
                        
                        $out[$waitDoc->refererinitid] = array(
                            "statusMessage" => $waitDoc->statusmessage,
                            "statusCode" => $waitDoc->status,
                            "isValid" => true
                        );
                    }
                }
            }
        }
        
        return $out;
    }
    
    private function afterSaveChangeState(Doc &$doc, $newState)
    {
        
        $err = $doc->setState($newState, sprintf(_("synchronize change state to %s"), $newState));
        return $err;
    }
}

?>