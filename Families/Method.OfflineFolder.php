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
class _OFFLINEFOLDER extends Dir
{
    /*
     * @end-method-ignore
     */
    
    
    public function hookBeforeInsert($docid)
    {
        $err = $this->callHookDocument($docid, "onBeforeInsertIntoUserFolder");
        return $err;
    }
    public function hookAfterInsert($docid)
    {
        $err = $this->callHookDocument($docid, "onAfterInsertIntoUserFolder");
        return $err;
    }
    public function hookBeforeRemove($docid)
    {
        $err = $this->callHookDocument($docid, "onBeforeRemoveFromUserFolder");
        return $err;
    }
    public function hookAfterRemove($docid)
    {
        $err = $this->callHookDocument($docid, "onAfterRemoveFromUserFolder");
        return $err;
    }
    
    protected function callHookDocument($docid, $method) {
        $err = '';
        $doc = new_doc($this->dbaccess, $docid, true);
        if ($doc->isAlive()) {
            if (method_exists($doc, $method)) {
                $domain=new_doc($this->dbaccess, $this->getValue("off_domain"));
                $err = call_user_func(array($doc, $method), $domain, $this);
            }
        } else {
            $err = sprintf(_("document %s not found"), $docid);
        }
        return $err;
    }

    public function preInsertDoc($docid)
    {
        $err = $this->hookBeforeInsert($docid);
        return $err;
    }
    
    public function postInsertDoc($docid)
    {
        $doc = new_doc($this->dbaccess, $docid, true);
        if ($doc->isAlive()) {
            $doc->updateDomains();
            /*
            if ($doc->locked == $doc->userid) {
            $doc->lockToDomain($this->getValue("off_domain"));
            */
            $err=$this->hookAfterInsert($docid);
            if (method_exists($doc, "onAfterInsertIntoDomain")) {
                $err = $doc->onAfterInsertIntoDomain();
            }
        }
        return $err;
    }
    public function postUnlinkDoc($docid)
    {
        
        $doc = new_doc($this->dbaccess, $docid, true);
        if ($doc->isAlive()) {
            $doc->updateDomains();
            $docuid = $this->getValue("off_user");
            if ($docuid) {
                $uid = 0;
                $err = simpleQuery($this->dbaccess, sprintf("select id from users where fid=%d", $docuid), $uid, true, true);
                if ($uid) {
                    include_once ("FDL/Class.DocWait.php");
                    $w = new DocWait($this->dbaccess, array(
                        $doc->initid,
                        $uid
                    ));
                    if ($w->isAffected()) $w->delete();
                }
            }
            $err=$this->hookAfterRemove($docid);
        }
    }
    
    
    public function preUnlinkDoc($docid) {
        $err = $this->hookBeforeRemove($docid);
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