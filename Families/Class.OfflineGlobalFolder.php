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
use \Dcp\AttributeIdentifiers\OfflineGlobalFolder as MyAttributes;

/**
 * offline domain fonctionalities
 *
 */
class OfflineGlobalFolder extends \Dcp\Family\OfflineFolder
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
}
