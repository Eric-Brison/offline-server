<?php

include_once ("OFFLINE/Interface.DomainHook.php");

class testHook implements DomainHook
{
    public function onBeforePushTransaction(_OfflineDomain &$domain)
    {
        $domain->addComment(__METHOD__);
        return '';
    }
    
    public function onAfterPushTransaction(_OfflineDomain &$domain)
    {
        
        $domain->addComment(__METHOD__);
    }
    public function onAfterSaveTransaction(_OfflineDomain &$domain)
    {
        
        $domain->addComment(__METHOD__);
    }
    public function onBeforePullUserDocuments(_OfflineDomain &$domain)
    {
        $domain->addComment(__METHOD__);
        return '';
    }
    
    public function onAfterPullSharedDocuments(_OfflineDomain &$domain)
    {
        
        $domain->addComment(__METHOD__);
    }
    
    public function onBeforePullSharedDocuments(_OfflineDomain &$domain)
    {
        $domain->addComment(__METHOD__);
    
     //return 'pas de partage';
    }
    
    public function onAfterPullUserDocuments(_OfflineDomain &$domain)
    {
        
        $domain->addComment(__METHOD__);
    }
    public function onAfterSaveDocument(_OfflineDomain &$domain, Doc &$updatedDoc, $data=null)
    {
        
        $domain->addComment(__METHOD__ . $updatedDoc->getTitle());
        $updatedDoc->addComment(__METHOD__);
    }
    public function onPullDocument(_OfflineDomain &$domain, Doc &$doc)
    {
        return true;
        $doc->addComment(__METHOD__);
        $classid = $doc->getValue("es_classe");
        if ($classid != '1126') {
            
            $domain->addComment(__METHOD__ . ' ' . $doc->getTitle(), HISTO_INFO);
        
        } else {
            
            $domain->addComment(__METHOD__ . " not pull $classid " . $doc->getTitle(), HISTO_ERROR);
            return 'not a reptilia';
        }
        return true;
    
    }
    
    public function onBeforePushDocument(_OfflineDomain &$domain, Doc &$doc, $data=null)
    {
        $domain->addComment(__METHOD__ . $doc->getTitle());
        $doc->addComment(__METHOD__.':'.serialize($data));
    }
    public function onAfterPushDocument(_OfflineDomain &$domain, Doc &$doc, $data=null)
    {
        $domain->addComment(__METHOD__ . $doc->getTitle());
        $doc->addComment(__METHOD__.':'.serialize($data));
    }
    public function onBeforeSaveDocument(_OfflineDomain &$domain, Doc &$waitDoc, Doc &$refererDoc=null, $data=null)
    {
        
        if ($refererDoc) {
        $domain->addComment(__METHOD__ . $refererDoc->getTitle());
        $refererDoc->addComment(__METHOD__.':'.serialize($data));
            $err .= simpleQuery($refererDoc->dbaccess, "select id, locked, revision from doc where locked != -1 and initid=" . $refererDoc->initid, $res, false, true);
            //print "Before";print_r2($res);
            $err = $refererDoc->addRevision("before save in synchro");
            $err .= simpleQuery($refererDoc->dbaccess, "select id, locked, revision from doc where locked != -1 and initid=" . $refererDoc->initid, $res, false, true);
        }
        //print "After";print_r2($res);
        // if ($refererDoc->getValue("es_poids") < 98) {
        // $waitDoc->setValue("es_poids", $refererDoc->getValue("es_poids") + 1);
        //}
        //$refererDoc->addComment("add one in es_poids :" . ($waitDoc->getValue("es_poids") ));
        return $err;
    }

}
