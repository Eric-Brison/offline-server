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
class _OFFLINEGLOBALFOLDER extends _OFFLINEFOLDER
{
    /*
     * @end-method-ignore
     */
    
    public function hookBeforeInsert($docid)
    {
        $err = $this->callHookDocument($docid, "onBeforeInsertIntoSharedFolder");
        return $err;
    }
    public function hookAfterInsert($docid)
    {
        $err = $this->callHookDocument($docid, "onAfterInsertIntoSharedFolder");
        return $err;
    }
    public function hookBeforeRemove($docid)
    {
        $err = $this->callHookDocument($docid, "onBeforeRemoveFromSharedFolder");
        return $err;
    }
    public function hookAfterRemove($docid)
    {
        $err = $this->callHookDocument($docid, "onAfterRemoveFromSharedFolder");
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