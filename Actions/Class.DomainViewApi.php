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

include_once ("DATA/Class.Collection.php");
include_once ("OFFLINE/Class.ExceptionCode.php");
include_once ("FDL/Class.DocWaitManager.php");

class DomainViewApi
{
    
    /**
     * internal domain document
     * @var _OFFLINEDOMAIN
     */
    private $domain = null;
    /**
     * parent object
     * @var DomainApi
     */
    private $domainApi = null;
    public function __construct(Dir &$domain = null, DomainApi &$domainApi = null)
    {
        $this->domain = $domain;
        $this->domainApi = $domainApi;
    }
    private static function setError($err)
    {
        throw new Exception($err);
    }
    
    public function getFamiliesBindings($config)
    {
        include_once ("FDL/Class.DocumentList.php");
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
        

        $out = '';
        foreach ( $list as $family ) {
            $out->bindings[$family->getProperty('name')] = $this->getFamilyBindings($family);
        }
        return $out;
    }
    
    public function getFamilyBindings(DocFam &$family)
    {
        $lay = new Layout(getLayoutFile("OFFLINE", "familyBinding.xml"));
        $lay->set("FAMNAME", $family->name);
        $oas = $family->getAttributes();
        $tree = array();
        $node = array();
        foreach ( $oas as $aid => &$oa ) {
            if (($oa->usefor != "Q") && ($oa->type == 'tab')) {
                $node[$aid] = array();
                $tree[$aid] = &$node[$aid];
            }
        }
        foreach ( $oas as $aid => &$oa ) {
            if (($oa->usefor != "Q") && ($oa->type == 'frame')) {
                $fid = $oa->fieldSet->id;
                if ($fid && $fid != "FIELD_HIDDENS") {
                    $node[$aid] = array();
                    //$tree[$aid] = &$node[$aid];
                    $node[$fid][$aid] = &$node[$aid];
                } else {
                    $node[$aid] = array();
                    $tree[$aid] = &$node[$aid];
                }
            }
        }
        
        foreach ( $oas as $aid => &$oa ) {
            if (($oa->usefor != "Q") && ($oa->type == 'array')) {
                $fid = $oa->fieldSet->id;
                if ($fid && $fid != "FIELD_HIDDENS") {
                    $node[$aid] = array();
                    // $tree[$aid] = &$node[$aid];
                    $node[$fid][$aid] = &$node[$aid];
                } else {
                    $node[$aid] = array();
                    $tree[$aid] = &$node[$aid];
                }
            }
        }
        
        foreach ( $oas as $aid => &$oa ) {
            if (($oa->usefor != "Q") && ($oa->type != 'array') && ($oa->type != 'frame') && ($oa->type != 'tab')) {
                $fid = $oa->fieldSet->id;
                if ($fid && $fid != "FIELD_HIDDENS") {
                    $node[$aid] = '';
                    $node[$fid][$aid] = $node[$aid];
                } else {
                    $node[$aid] = array();
                    $tree[$aid] = &$node[$aid];
                }
            }
        }
        unset($tree["FIELD_HIDDENS"]);
        //print_r2($tree);
        $lay->set("viewContent", $this->bindingViewNodeAttribute($tree, $oas));
        
       
        $lay->set("editContent", $this->bindingEditNodeAttribute($tree, $oas));
        
        //print_r2($viewbinding);
        $binding = $lay->gen();
        return $binding;
    }
    
    private function bindingViewNodeAttribute(array $node, array &$oas, BasicAttribute &$oa=null)
    {
        $out = '';
        if ($oa) {
            $out = sprintf('<dcpAttribute type="%s" attrid="%s">', $oa->type, $oa->id);
        }
        $callback = function ($a, $b) use($oas)
                {
                    if ($oas[$a].ordered > $oas[$b].ordered) return 1;
                    else if ($oas[$a].ordered < $oas[$b].ordered) return -1;
                    return 0;
                };
        uksort($node, $callback);
        
        foreach ( $node as $k => $v ) {
            if (is_array($v)) {
                $out .= $this->bindingViewNodeAttribute( $v, $oas, $oas[$k]);
            } else {
                $out .= $this->bindingViewLeafAttribute($oas[$k]) . "\n";
            }
        }
        if ($oa) {
            $out .= sprintf('</dcpAttribute>');
        }
        return $out;
    }
    
    private function bindingViewLeafAttribute(BasicAttribute &$oa)
    {
        $out = '';
        switch ($oa->type) {
        case 'docid' :
            $out = sprintf('<dcpAttribute type="%s" attrid="%s" relationFamily="%s" multiple="%s"/>', $oa->type, $oa->id, trim($oa->format), ($oa->getOption("multiple") == "yes") ? "true" : "false");
            break;
        case 'enum' :
            $out = sprintf('<dcpAttribute type="%s" attrid="%s"  multiple="%s"/>', $oa->type, $oa->id, ($oa->getOption("multiple") == "yes") ? "true" : "false");
            break;
        default :
            $out = sprintf('<dcpAttribute type="%s" attrid="%s"/>', $oa->type, $oa->id);
        }
        return $out;
    }
    

    private function bindingEditNodeAttribute(array $node, array &$oas, BasicAttribute &$oa=null)
    {
        $out = '';
        if ($oa) {
            $out = sprintf('<dcpAttribute type="%s" attrid="%s">', $oa->type, $oa->id);
        }
        foreach ( $node as $k => $v ) {
            if (is_array($v)) {
                $out .= $this->bindingEditNodeAttribute( $v, $oas, $oas[$k]);
            } else {
                $out .= $this->bindingEditLeafAttribute($oas[$k]) . "\n";
            }
        }
        if ($oa) {
            $out .= sprintf('</dcpAttribute>');
        }
        return $out;
    }
    
    private function bindingEditLeafAttribute(BasicAttribute &$oa)
    {
        $out = '';
        switch ($oa->type) {
        case 'docid' :
            $out = sprintf('<dcpAttribute type="%s" attrid="%s" relationFamily="%s" multiple="%s"/>', $oa->type, $oa->id, trim($oa->format), ($oa->getOption("multiple") == "yes") ? "true" : "false");
            break;
        case 'enum' :
            $out = sprintf('<dcpAttribute type="%s" attrid="%s"  multiple="%s"/>', $oa->type, $oa->id, ($oa->getOption("multiple") == "yes") ? "true" : "false");
            break;
        default :
            $out = sprintf('<dcpAttribute type="%s" attrid="%s"/>', $oa->type, $oa->id);
        }
        return $out;
    }
}

?>