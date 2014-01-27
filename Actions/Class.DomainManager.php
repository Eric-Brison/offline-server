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

include_once ("FDL/Class.SearchDoc.php");
include_once ("OFFLINE/Class.ExceptionCode.php");

class DomainManager
{
    private static $error = '';
    private static function getUserId()
    {
        return \Doc::getSystemUserId();
    }
    private static function setError($err)
    {
        throw new \Exception($err);
    }
    /**
     * List all domain availables by current user
     * @code
     $domains=DomainManager::getDomains();
     foreach ($domains as $domain) {
     print $domain->getTitle()."\n";
     }
     * @endcode
     * @return \DocumentList search results
     */
    public static function getDomains()
    {
        include_once ("FDL/Class.DocumentList.php");
        $userId = self::getUserId();
        $s = new \SearchDoc(getDbAccess() , "OFFLINEDOMAIN");
        $s->setObjectReturn();
        $s->search();
        $err = $s->getError();
        
        if ($err) {
            self::setError($err);
        }
        
        $s->search();
        /* @var \Dcp\Family\OfflineDomain $doc */
        while ($doc = $s->getNextDoc()) {
            $users = array_keys($doc->getUserMembersInfo());
            if (!in_array($userId, $users)) {
                $s->addFilter("initid != %d", $doc->initid);
            }
        }
        $s->reset();
        return $s->getDocumentList();
    }
    /**
     * create a new domain
     * @code
     * $domain=DomainManager::createDomain("myDomain");
     * $err =$domain->addFamily("TST_ARTICLE");
     * $err.=$domain->addUserMember("john.doe");
     * $err.=$domain->insertUserDocument("1254","john.doe");
     * @endcode
     * @param $reference
     * @throws \Exception if no abilities or if reference is already set by another
     * @exception OfflineExceptionCode::referenceExists, OfflineExceptionCode::createForbidden
     * @return \Dcp\Family\OfflineDomain document
     */
    public static function createDomain($reference)
    {
        $domain = createDoc(getDbAccess() , "OFFLINEDOMAIN");
        if (!$domain) {
            throw new \Exception(_("no privilege to create offline domain") , \Dcp\Offline\OfflineExceptionCode::createForbidden);
        }
        $domain->setValue("off_ref", $reference);
        $err = $domain->verifyAllConstraints();
        if ($err) {
            throw new \Exception($err, \Dcp\Offline\OfflineExceptionCode::referenceInvalid);
        }
        $err = $domain->add();
        if ($err) {
            throw new \Exception($err, \Dcp\Offline\OfflineExceptionCode::referenceExists);
        }
        return $domain;
    }
}
