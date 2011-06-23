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


interface DomainHook
{
    
    
    public function onBeforePullUserDocuments(_OfflineDomain &$domain);
    public function onBeforePullSharedDocuments(_OfflineDomain &$domain);
    public function onPullDocument(_OfflineDomain &$domain, Doc &$doc);
    public function onAfterPullUserDocuments(_OfflineDomain &$domain);
    public function onAfterPullSharedDocuments(_OfflineDomain &$domain);
    
    public function onBeforePushTransaction(_OfflineDomain &$domain);
    public function onBeforePushDocument(_OfflineDomain &$domain, Doc &$doc);
    public function onAfterPushDocument(_OfflineDomain &$domain, Doc &$doc);
    public function onAfterPushTransaction(_OfflineDomain &$domain);
    
    
    public function onBeforeSaveDocument(_OfflineDomain &$domain, Doc &$waitDoc, Doc &$refererDoc=null);
    public function onAfterSaveDocument(_OfflineDomain &$domain,  Doc &$updatedDoc);
    
    public function onAfterSaveTransaction(_OfflineDomain &$domain);
}
?>