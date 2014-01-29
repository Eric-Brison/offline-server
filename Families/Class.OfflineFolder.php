<?php
/**
 * Offline domain
 *
 * @author Anakeen
 * @version $Id:  $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package OFFLINE
 */

namespace Dcp\Offline;
use \Dcp\AttributeIdentifiers\OfflineFolder as MyAttributes;

class OfflineFolder extends \Dcp\Family\Dir
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
                $domain=new_doc($this->dbaccess, $this->getRawValue(MyAttributes::off_domain));
                $err = call_user_func(array($doc, $method), $domain, $this);
            }
        } else {
            $err = sprintf(_("document %s not found"), $docid);
        }
        return $err;
    }

    public function preInsertDocument($docid, $multiple=false)
    {
        $err = $this->hookBeforeInsert($docid);
        return $err;
    }
    
    public function postInsertDocument($docid, $multiple=false)
    {
        $err = "";
        $doc = new_doc($this->dbaccess, $docid, true);
        if ($doc->isAlive()) {
            $doc->updateDomains();
            /*
            if ($doc->locked == $doc->userid) {
            $doc->lockToDomain($this->getValue("off_domain"));
            */
            $err=$this->hookAfterInsert($docid);
            if (method_exists($doc, "onAfterInsertIntoDomain")) {
                /** @noinspection PhpUndefinedMethodInspection */
                $err = $doc->onAfterInsertIntoDomain();
            }
        }
        return $err;
    }
    public function postRemoveDocument($docid, $multiple=false)
    {
        $err = '';

        $doc = new_doc($this->dbaccess, $docid, true);
        if ($doc->isAlive()) {
            $doc->updateDomains();
            $docuid = $this->getRawValue(MyAttributes::off_user);
            if ($docuid) {
                $uid = array();
                $err .= simpleQuery($this->dbaccess, sprintf("select id from users where fid=%d", $docuid), $uid, true, true);
                if ($uid) {
                    /** @noinspection PhpIncludeInspection */
                    require_once "FDL/Class.DocWait.php";
                    $docWait = new \DocWait($this->dbaccess, array(
                        $doc->initid,
                        $uid
                    ));
                    if ($docWait->isAffected()) {
                        $err .= $docWait->delete();
                    }
                }
            }
            $err .= $this->hookAfterRemove($docid);
        }
        return $err;
    }
    
    
    public function preRemoveDocument($docid, $multple=false) {
        $err = $this->hookBeforeRemove($docid);
        return $err;
    }
}
