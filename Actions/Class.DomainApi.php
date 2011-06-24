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

include_once ("FDL/Class.SearchDoc.php");
include_once ("DATA/Class.Collection.php");
include_once ("OFFLINE/Class.ExceptionCode.php");

class DomainApi
{
    /**
     * internal domain document
     * @var _OFFLINEDOMAIN
     */
    private $domain = null;
    public function __construct(Dir &$domain = null)
    {
        $this->domain = $domain;
    }
    private static function setError($err)
    {
        throw new Exception($err);
    }
    /**
     * List all domain availables by current user
     * @return object document List
     */
    public static function getDomains()
    {
        include_once ("OFFLINE/Class.DomainManager.php");
        $col = new Fdl_Collection();
        $col->useDocumentList(DomainManager::getDomains());
        $col->setContentOnlyValue(true);
        $col->setContentCompleteProperties(false);
        $out = $col->getContent();
        
        return $out;
    }
    
    /**
     * user mode
     * @return stdClass ->userMode : advanced or standard 
     */
    public function getUserMode()
    {
        $out = '';
        if ($this->domain) {
            
            $uid = $this->domain->getSystemUserId();
            $out->userMode = $this->domain->getUserMode($uid);
        } else {
            $this->setError(_("domain not set"));
        }
        return $out;
    }
    
    /**
     * book document into user space
     * @return Fdl_Document
     */
    public function bookDocument($config)
    {
        $out = '';
        if ($this->domain) {
            $docid = $config->docid;
            $err = $this->domain->insertUserDocument($docid, $this->domain->getSystemUserId(), true);
            if ($err) $this->setError($err);
            $fdoc = new Fdl_Document($docid);
            $out = $fdoc->getDocument(true, false);
        
        } else {
            $this->setError(_("domain not set"));
        }
        return $out;
    }
    
    /**
     * unbook document into user space
     * @return Fdl_Document
     */
    public function unbookDocument($config)
    {
        $out = '';
        if ($this->domain) {
            $docid = $config->docid;
            $err = $this->domain->setReservation($docid, 0);
            if ($err) $this->setError($err);
            $fdoc = new Fdl_Document($docid);
            $out = $fdoc->getDocument(true, false);
        
        } else {
            $this->setError(_("domain not set"));
        }
        return $out;
    }
    
    /**
     * unbook document into user space
     * @return Fdl_Document
     */
    public function revertDocument($config)
    {
        $out = '';
        if ($this->domain) {
            $docid = $config->docid;
            $doc = new_doc(getDbaccess(), $docid, true);
            if ($doc->isAlive()) {
                include_once ("FDL/Class.DocWaitManager.php");
                $this->updateUnResolvedLocalLinks($doc);
                $uid=$this->domain->getSystemUserId();
                if ($doc->lockdomainid == $this->domain->id && $doc->locked==$uid) {
                    $wdoc=new DocWait($this->domain->dbaccess, array($doc->initid, $uid));
                    if ($wdoc->isAffected()) $wdoc->resetWaitingDocument();
                } else {
                  $err = DocWaitManager::clearWaitingDocs($this->domain->id, $uid, $doc->initid);
                }
            }
            if ($err) $this->setError($err);
            $fdoc = new Fdl_Document(0, null, $doc);
            $out = $fdoc->getDocument(true, false);
        } else {
            $this->setError(_("domain not set"));
        }
        return $out;
    }
    
    private function updateUnResolvedLocalLinks(Doc &$doc) {
        $oas = $doc->getNormalAttributes();
           $unresolvedLinks=DocWaitManager::getUnresolvedLocalLinks($this->domain->id, $this->domain->getSystemUserId());
           $localIds=array_keys($unresolvedLinks);
           $serverIds=array_values($unresolvedLinks);
            foreach ( $oas as $aid => $oa ) {
                if ($oa->type == "docid") {
                    $value = $doc->getValue($aid);
                    if ($value) {
                        $nvalue = str_replace($serverIds, $localIds, $value);
                        if ($nvalue != $value) {
                            $doc->$aid=$nvalue; // need to by pass setValue cause incorrect docid syntax
                        }
                    }
                }
            }
    }
    
    /**
     * put document into user space read only
     * @return Fdl_Document
     */
    public function insertUserDocument($config)
    {
        $out = '';
        if ($this->domain) {
            $docid = $config->docid;
            
            $err = $this->domain->insertUserDocument($docid, $this->domain->getSystemUserId(), false);
            if ($err) $this->setError($err);
            $fdoc = new Fdl_Document($docid);
            $out = $fdoc->getDocument(true, false);
        
        } else {
            $this->setError(_("domain not set"));
        }
        return $out;
    }
    
    /**
     * remove document from user space read only
     * @return Fdl_Document
     */
    public function removeUserDocument($config)
    {
        $out = '';
        if ($this->domain) {
            $docid = $config->docid;
            
            $err = $this->domain->removeUserDocument($docid, $this->domain->getSystemUserId());
            if ($err) $this->setError($err);
            $fdoc = new Fdl_Document($docid);
            $out = $fdoc->getDocument(true, false);
        
        } else {
            $this->setError(_("domain not set"));
        }
        return $out;
    }
    
    /**
     * put document into user space read only
     * @return Fdl_Document
     */
    public function insertSharedDocument($config)
    {
        $out = '';
        if ($this->domain) {
            $docid = $config->docid;
            
            $err = $this->domain->insertSharedDocument($docid);
            if ($err) $this->setError($err);
            $fdoc = new Fdl_Document($docid);
            $out = $fdoc->getDocument(true, false);
        
        } else {
            $this->setError(_("domain not set"));
        }
        return $out;
    }
    
    /**
     * remove document from user space read only
     * @return Fdl_Document
     */
    public function removeSharedDocument($config)
    {
        $out = '';
        if ($this->domain) {
            $docid = $config->docid;
            
            $err = $this->domain->removeSharedDocument($docid, $this->domain->getSystemUserId());
            if ($err) $this->setError($err);
            $fdoc = new Fdl_Document($docid);
            $out = $fdoc->getDocument(true, false);
        
        } else {
            $this->setError(_("domain not set"));
        }
        return $out;
    }
    
    /**
     * return families restriction
     * @return DocumentList of families
     */
    public function getAvailableFamilies()
    {
        include_once ("FDL/Class.DocumentList.php");
        
        if ($this->domain) {
            $families = $this->domain->getFamilies();
            $list = new DocumentList();
            $list->addDocumentIdentificators($families);
            
            $domain = $this->domain;
            $callback = function (&$family) use($domain)
            {
                $maskId = $domain->getOfflineMask($family->id);
                if ($maskId) {
                    $family->applyMask($maskId, true);
                }
            };
            $list->listMap($callback); // apply specific offline mask
            $col = new Fdl_Collection();
            $col->useDocumentList($list);
            $col->setContentOnlyValue(false);
            $col->setContentCompleteProperties(false);
            $out = $col->getContent();
        
        } else {
            $this->setError(_("domain not set"));
        }
        return $out;
    }
    
    /**
     * delete all documents from user folder
     * @return void
     */
    public function clearUserDocuments()
    {
        if ($this->domain) {
            $err = $this->domain->clearUserFolder($this->domain->getSystemUserId(), false);
            if ($err) $this->setError($err);
        
        } else {
            $this->setError(_("domain not set"));
        }
        return;
    }
    
    /**
     * delete all documents from user folder
     * @return void
     */
    public function clearSharedDocuments()
    {
        if ($this->domain) {
            $err = $this->domain->clearSharedFolder(false);
            if ($err) $this->setError($err);
        
        } else {
            $this->setError(_("domain not set"));
        }
        return;
    }
    
    /**
     * get user folder documents
     * @param function $callback filter on documents
     * @return DocumentList
     */
    public function getUserDocuments($config, $callback = null)
    {
        $date = $config->until;
        if ($this->domain) {
            $folder = $this->domain->getUserFolder();
            $out = $this->getFolderDocuments($folder, $date, $callback);
        } else {
            $this->setError(_("domain not set"));
        }
        return $out;
    }
    
    /**
     * get share folder documents
     * @param function $callback filter on documents
     * @return DocumentList
     */
    public function getSharedDocuments($config, $callback = null)
    {
        $date = $config->until;
        if ($this->domain) {
            $folder = $this->domain->getSharedFolder();
            if ($folder) {
                $out = $this->getFolderDocuments($folder, $date, $callback);
            } else {
                $this->setError(_("no share folder"));
            }
        } else {
            $this->setError(_("domain not set"));
        }
        return $out;
    }
    
    /**
     * get folder documents
     * @param Dir $folder the folder to inspect
     * @param string $mdate filter on modified date 
     * @param function $callback filter on documents
     * @return DocumentList
     */
    private function getFolderDocuments(Dir &$folder, $mdate = '', $callback = null)
    {
        include_once ("FDL/Class.DocumentList.php");
        
        if ($this->domain) {
            $col = new Fdl_Collection(0, null, $folder);
            
            if ($callback) $col->setContentMap($callback);
            $col->setContentOnlyValue(true);
            $col->setContentCompleteProperties(false);
            if ($mdate) {
                $umdate = stringDateToUnixTs($mdate);
                $sqlDate = sprintf("revdate > %d", $umdate);
                $col->setContentFilter($sqlDate);
            }
            //$col->setContentSlice(1000);
            $out = $col->getContent();
            
            if ($out->totalCount > 0) $this->setReserveWaitingDocument($folder);
        
        } else {
            $this->setError(_("domain not set"));
        }
        return $out;
    }
    
    /**
     * init waiting doc for reserved document
     * @param Dir $folder
     */
    private function setReserveWaitingDocument(Dir &$folder)
    {
        include_once ("FDL/Class.DocWaitManager.php");
        if ($folder) {
            
            $s = new SearchDoc($this->dbaccess);
            $s->useCollection($folder->initid);
            $s->setObjectReturn();
            $uid = $folder->getSystemUserId();
            $s->addFilter("locked = %d", $uid);
            $s->addFilter("lockdomainid = %d", $this->domain->id);
            $dl = $s->search()->getDocumentList();
            $w = new DocWait($this->dbaccess);
            foreach ( $dl as $k => $v ) {
                if (!$w->select(array(
                    $v->initid,
                    $uid
                ))) {
                    DocWaitManager::saveWaitingDoc($v, $this->domain->id);
                }
            }
        }
    }
    
    /**
     * get document locked by user
     * @return DocumentList
     */
    public function getReservedDocumentIds()
    {
        include_once ("FDL/Class.DocumentList.php");
        $out = null;
        if ($this->domain) {
            $ids = $this->domain->getReservedDocumentIds();
            
            $out->reservedDocumentIds = $ids;
        
        } else {
            $this->setError(_("domain not set"));
        }
        return $out;
    }
    
    /**
     * return object sync 
     * @return DomainSyncApi
     */
    public function sync()
    {
        include_once ("OFFLINE/Class.DomainSyncApi.php");
        return new DomainSyncApi($this->domain, $this);
    }
    
    /**
     * return object sync 
     * @return DomainViewApi
     */
    public function view()
    {
        include_once ("OFFLINE/Class.DomainViewApi.php");
        return new DomainViewApi($this->domain, $this);
    }
}
?>