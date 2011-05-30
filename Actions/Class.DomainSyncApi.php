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
    const abordTransaction = "abordTransaction";
    const successTransaction = "successTransaction";
    const partialTransaction = "partialTransaction";
    const documentNotRecorded = "documentNotRecorded";
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
        if ($stillRecorded[$doc->initid] == $doc->revdate) return true;
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
            foreach ( $config->stillRecorded as $record ) {
                $stillRecorded[$record->initid] = $record->revdate;
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
        } else {
            $out->error = $err;
        }
        return $out;
    }
    
    /**
     * unbook document into user space
     * @return Fdl_Document
     */
    public function revertDocument($config)
    {
        // TODO test onPullDocument hook
        $out = $this->domainApi->revertDocument($config);
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
            foreach ( $config->stillRecorded as $record ) {
                $stillRecorded[$record->initid] = $record->revdate;
            }
            if ($this->domain->hook()) {
                $domain = $this->domain;
                $callback = function (&$doc) use($domain)
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
            $out = $this->domainApi->getUserDocuments($config, $callback);
        } else {
            $out->error = $err;
        }
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
        if (preg_match('/^([^\]+)\[([0-9]+)\]$/', $aid, $reg)) {
            //   print_r($reg);
            $index = $reg[2];
        }
        $path = 'php://input';
        $out = '';
        $tmpfile = tempnam(getTmpDir(), 'pushFile');
        if ($tmpfile == false) {
            $err = sprintf("cannot create temporay file %s", $tmpfile);
        } else {
            copy($path, $tmpfile);
            $filename = $config->filename;
            $wdoc = DocWaitManager::getWaitingDoc($docid);
            //$doc = new_doc(getDbAccess(), $docid);
            if ($wdoc) {
                $doc = $wdoc->getWaitingDocument();
                // print $doc->getTitle();
                $oa = $doc->getAttribute($aid);
                // print_r($oa);
                if ($oa) {
                    $err = $doc->storeFile($oa->id, $tmpfile, $filename, $index);
                    
                    @unlink($tmpfile);
                    $err = DocWaitManager::saveWaitingDoc($doc, $this->domain->id, $config->transaction);
                }
            
     // $err = DocWaitManager::saveWaitingDoc($doc);
            }
        }
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
        $doc = createTmpDoc(getDbAccess(), $fromid);
        $err = '';
        if (!$doc) {
            $err = sprintf("cannot create document %s", $fromid);
        } else {
            $props = array();
            foreach ( $rawdoc->properties as $k => $v ) {
                if (is_array($v)) $v = implode("\n", $v);
                $props[$k] = $v;
            }
            $doc->affect($props);
            foreach ( $rawdoc->values as $k => $v ) {
                
                $oa = $doc->getAttribute($k);
                if ($oa) {
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
            if ($rawdoc->properties->id) {
                $refdoc = new_doc(getDbAccess(), $rawdoc->properties->id, true);
                $err = $this->verifyPrivilege($refdoc);
            }
            if ($err == "") {
                
                $err = $this->raw2doc($rawdoc, $doc);
            }
            
            if ($err == "") {
                $err = $this->callHook("onBeforePushDocument", $doc);
                
                if (!$err) {
                    
                    $err = DocWaitManager::saveWaitingDoc($doc, $this->domain->id, $config->transaction);
                }
                if ($err) {
                    $out->error = $err;
                } else {
                    $message = $this->callHook("onAfterPushDocument", $doc);
                    $fdoc = new Fdl_Document($doc->id, null, $doc);
                    $out = $fdoc->getDocument(true, false);
                    $out["message"] = $message;
                }
            } else {
                $waitDoc = DocWaitManager::getWaitingDoc($rawdoc->properties->initid);
                if (!$waitDoc) {
                    $doc = new_doc(getDbAccess(), $rawdoc->properties->id, true);
                    $err = DocWaitManager::saveWaitingDoc($doc, $this->domain->id, $config->transaction);
                }
                $waitDoc->transaction = $config->transaction;
                $waitDoc->status = $waitDoc::invalid;
                $waitDoc->statusmessage = $err;
                $waitDoc->modify();
                $out->error = sprintf(_("push:invalid document : %s"), $err);
            }
        } else {
            $out->error = _("push:no document found");
        }
        return $out;
    }
    private function callHook($method, &$arg1 = null, &$arg2 = null)
    {
        
        if ($this->domain->hook()) {
            if (method_exists($this->domain->hook(), $method)) {
                return call_user_func_array(array(
                    $this->domain->hook(),
                    $method
                ), array(
                    &$this->domain,
                    &$arg1,
                    &$arg2
                ));
            }
        }
        return null;
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
                $point = "synchro" . $config->transaction;
                if ($policy == "global") {
                    $this->domain->savePoint($point);
                }
                foreach ( $waitings as $k => $waitDoc ) {
                    if ($waitDoc->status == $waitDoc::invalid) {
                        $out->detailStatus[$waitDoc->refererinitid] = array(
                            "statusMessage" => $waitDoc->statusmessage,
                            "statusCode" => $waitDoc->status,
                            "isValid" => false
                        );
                    } else {
                        $waitPoint = "docw" . $k;
                        $this->domain->savePoint($waitPoint);
                        
                        $saveerr = $this->callHook("onBeforeSaveDocument", $waitDoc->getWaitingDocument(), $waitDoc->getRefererDocument());
                        if (!$saveerr) {
                            $saveerr = $this->verifyPrivilege($waitDoc->getRefererDocument());
                        }
                        if ($saveerr == "") {
                            $saveerr = $waitDoc->save();
                            $out->detailStatus[$waitDoc->refererinitid] = array(
                                "statusMessage" => $waitDoc->statusmessage,
                                "statusCode" => $waitDoc->status,
                                "isValid" => $waitDoc->isValid()
                            );
                            if ($saveerr == '') {
                                $waitDoc->getRefererDocument()->addComment("synchronised");
                                $this->domain->commitPoint($waitPoint);
                            } else {
                                $this->domain->rollbackPoint($waitPoint);
                                // need to redo modify cause rollback
                                $waitDoc->status = $out->detailStatus[$waitDoc->refererinitid]["statusCode"];
                                $waitDoc->statusmessage = $out->detailStatus[$waitDoc->refererinitid]["statusMessage"];
                                $waitDoc->modify();
                                $waitDoc->getRefererDocument()->addComment(sprintf(_("synchro: %s"), $saveerr), HISTO_ERROR);
                            }
                        } else {
                            $out->detailStatus[$waitDoc->refererinitid] = array(
                                "statusMessage" => $saveerr,
                                "statusCode" => self::documentNotRecorded,
                                "isValid" => false
                            );
                            $this->domain->rollbackPoint($waitPoint);
                            
                            // need to redo modify cause rollback
                            $waitDoc->status = $out->detailStatus[$waitDoc->refererinitid]["statusCode"];
                            $waitDoc->statusmessage = $out->detailStatus[$waitDoc->refererinitid]["statusMessage"];
                            $waitDoc->modify();
                            $waitDoc->getRefererDocument()->addComment(sprintf(_("synchro: %s"), $err), HISTO_ERROR);
                        }
                    }
                }
                
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
                        $out->status = self::abordTransaction;
                    } else {
                        // nothing has be done / no work is a good work
                        $out->status = self::successTransaction;
                    }
                } else {
                    $out->status = $completeSuccess ? self::successTransaction : self::partialTransaction;
                    $message = $this->callHook("onAfterSaveTransaction");
                }
                
                if ($policy == "global") {
                    if ($out->status == self::successTransaction) {
                        $this->domain->commitPoint($point);
                    } else {
                        $this->domain->rollbackPoint($point);
                    }
                }
                $out->message = $message;
                $out->error = $err;
            } else {
                $out->status = self::abordTransaction;
            }
        } else {
            $out->error = _("endTransaction:no transaction identificator");
            $out->status = self::abordTransaction;
        }
        $out->manageWaitingUrl = getParam("CORE_EXTERNURL") . '?app=OFFLINE&action=MANAGEWAITING&domain=' . $this->domain->id . '&transaction=' . $config->transaction;
        return $out;
    }

}

?>