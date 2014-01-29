<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/
/**
 * Return offline domains where current user is affected
 *
 * @author Anakeen
 * @version $Id:  $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package OFFLINE
 */

namespace Dcp\Offline;

interface DomainHook {
    /**
     * call before synchronize all user documents  from server to client
     * if return error the pull is aborted
     * @param \Dcp\Family\OfflineDomain $domain the current domain document
     * @return string error message
     */
    public function onBeforePullUserDocuments(\Dcp\Family\OfflineDomain & $domain);
    /**
     * call before synchronize all shared documents  from server to client
     * if return error the pull is aborted
     * @param \Dcp\Family\OfflineDomain $domain the current domain document
     * @return string error message
     */
    public function onBeforePullSharedDocuments(\Dcp\Family\OfflineDomain & $domain);
    /**
     * call before synchronize a documents from server to client
     * if return error the pull of the document is aborted
     * @param \Dcp\Family\OfflineDomain $domain the current domain document
     * @param \Doc $doc the document to pull
     * @return string error message
     */
    public function onPullDocument(\Dcp\Family\OfflineDomain & $domain, \Doc & $doc);
    /**
     * call after all user documents are be transfered from server to client
     *
     * @param \Dcp\Family\OfflineDomain $domain the current domain document
     * @return void
     */
    public function onAfterPullUserDocuments(\Dcp\Family\OfflineDomain & $domain);
    /**
     * call after all shared documents are be transfered from server to client
     *
     * @param \Dcp\Family\OfflineDomain $domain the current domain document
     * @return void
     */
    public function onAfterPullSharedDocuments(\Dcp\Family\OfflineDomain & $domain);
    /**
     * call before begin transaction to transfered modification from client to server
     * if return error the transaction is aborted
     * @param \Dcp\Family\OfflineDomain $domain the current domain document
     * @return string error message
     */
    public function onBeforePushTransaction(\Dcp\Family\OfflineDomain & $domain);
    /**
     * call before push document from client to server
     * if return error the push is aborted
     * @param \Dcp\Family\OfflineDomain $domain the current domain document
     * @param \Doc $doc the document to push
     * @param Object $extraData extra data set by client
     * @return string error message
     */
    public function onBeforePushDocument(\Dcp\Family\OfflineDomain & $domain, \Doc & $doc, $extraData = null);
    /**
     * call after push document from client to server
     * the document waiting from the transation end to be recording in database
     * @param \Dcp\Family\OfflineDomain $domain the current domain document
     * @param \Doc $doc the document to push
     * @param Object $extraData extra data set by client
     * @return void
     */
    public function onAfterPushDocument(\Dcp\Family\OfflineDomain & $domain, \Doc & $doc, $extraData = null);
    /**
     * call after all documents are been transfered from client to server
     * at the step documents waiting to be recorded
     * @param \Dcp\Family\OfflineDomain $domain the current domain document
     * @return void
     */
    public function onAfterPushTransaction(\Dcp\Family\OfflineDomain & $domain);
    /**
     * call before record document which waiting after a push request
     * if return error the recording is aborted
     * @param \Dcp\Family\OfflineDomain $domain the current domain document
     * @param \Doc $waitDoc the waiting document
     * @param \Doc $refererDoc the referer document (can be null if it is a document creation)
     * @param Object $extraData extra data set by client
     * @return string error message
     */
    public function onBeforeSaveDocument(\Dcp\Family\OfflineDomain & $domain, \Doc & $waitDoc, \Doc & $refererDoc = null, $extraData = null);
    /**
     * call after record document
     *
     * @param \Dcp\Family\OfflineDomain $domain the current domain document
     * @param \Doc $updatedDoc the updated document
     * @param Object $extraData extra data set by client
     * @return void
     */
    public function onAfterSaveDocument(\Dcp\Family\OfflineDomain & $domain, \Doc & $updatedDoc, $extraData = null);
    /**
     * final call  after all document records
     *
     * @param \Dcp\Family\OfflineDomain $domain the current domain document
     * @return void
     */
    public function onAfterSaveTransaction(\Dcp\Family\OfflineDomain & $domain);
}
