<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

namespace Dcp\Offline;

include_once ("OFFLINE/Interface.DomainHook.php");

class testHook implements DomainHook
{
    public function onBeforePushTransaction(\Dcp\Family\OfflineDomain & $domain)
    {
        $domain->addHistoryEntry(__METHOD__);
        return '';
    }
    
    public function onAfterPushTransaction(\Dcp\Family\OfflineDomain & $domain)
    {
        
        $domain->addHistoryEntry(__METHOD__);
    }
    public function onAfterSaveTransaction(\Dcp\Family\OfflineDomain & $domain)
    {
        
        $domain->addHistoryEntry(__METHOD__);
    }
    public function onBeforePullUserDocuments(\Dcp\Family\OfflineDomain & $domain)
    {
        $domain->addHistoryEntry(__METHOD__);
        return '';
    }
    
    public function onAfterPullSharedDocuments(\Dcp\Family\OfflineDomain & $domain)
    {
        
        $domain->addHistoryEntry(__METHOD__);
    }
    
    public function onBeforePullSharedDocuments(\Dcp\Family\OfflineDomain & $domain)
    {
        $domain->addHistoryEntry(__METHOD__);
        //return 'pas de partage';
        
    }
    
    public function onAfterPullUserDocuments(\Dcp\Family\OfflineDomain & $domain)
    {
        
        $domain->addHistoryEntry(__METHOD__);
    }
    public function onAfterSaveDocument(\Dcp\Family\OfflineDomain & $domain, \Doc & $updatedDoc, $data = null)
    {
        
        $domain->addHistoryEntry(__METHOD__ . $updatedDoc->getTitle());
        $updatedDoc->addHistoryEntry(__METHOD__ . ':' . serialize($data));
    }
    public function onPullDocument(\Dcp\Family\OfflineDomain & $domain, \Doc & $doc)
    {
        //$domain->addExtraData($doc, "test", "one");
        //$doc->addComment(__METHOD__);
        return true;
        /*
        $doc->addComment(__METHOD__);
        $classid = $doc->getValue("es_classe");
        if ($classid != '1126') {
            
            $domain->addComment(__METHOD__ . ' ' . $doc->getTitle(), HISTO_INFO);
        
        } else {
            
            $domain->addComment(__METHOD__ . " not pull $classid " . $doc->getTitle(), HISTO_ERROR);
            return 'not a reptilia';
        }
        return true;
        */
    }
    
    public function onBeforePushDocument(\Dcp\Family\OfflineDomain & $domain, \Doc & $doc, $data = null)
    {
        $domain->addHistoryEntry(__METHOD__ . $doc->getTitle());
        $doc->addHistoryEntry(__METHOD__ . ':' . serialize($data));
        // return "stop the push";
        
    }
    public function onAfterPushDocument(\Dcp\Family\OfflineDomain & $domain, \Doc & $doc, $data = null)
    {
        $domain->addHistoryEntry(__METHOD__ . $doc->getTitle());
        $doc->addHistoryEntry(__METHOD__ . ':' . serialize($data));
    }
    public function onBeforeSaveDocument(\Dcp\Family\OfflineDomain & $domain, \Doc & $waitDoc, \Doc & $refererDoc = null, $data = null)
    {
        $err = '';
        if ($refererDoc) {
            $domain->addHistoryEntry(__METHOD__ . $refererDoc->getTitle());
            $refererDoc->addHistoryEntry(__METHOD__ . ':' . serialize($data));
            $err.= simpleQuery($refererDoc->dbaccess, "select id, locked, revision from doc where locked != -1 and initid=" . $refererDoc->initid, $res, false, true);
            //print "Before";print_r2($res);
            $err = $refererDoc->addHistoryEntry("before save in synchro");
            $err.= simpleQuery($refererDoc->dbaccess, "select id, locked, revision from doc where locked != -1 and initid=" . $refererDoc->initid, $res, false, true);
        }
        //print "After";print_r2($res);
        // if ($refererDoc->getValue("es_poids") < 98) {
        // $waitDoc->setValue("es_poids", $refererDoc->getValue("es_poids") + 1);
        //}
        //$refererDoc->addComment("add one in es_poids :" . ($waitDoc->getValue("es_poids") ));
        return $err;
    }
}
