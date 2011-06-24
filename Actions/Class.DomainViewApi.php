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
        $lay->set("FAMID", $family->id);
        $oas = $family->getAttributes();
        
        // need cause defval is not declared as attribute
        simpleQuery($family->dbaccess, sprintf("select defval from docfam where id=%d", $family->id), $defval, true, true);
        $family->defval=$defval;
        $this->_defaultValues=$family->getDefValues($defval);
        $tree = array();
        $node = array();
        foreach ( $oas as $aid => &$oa ) {
            if (($oa->usefor != "Q") && ($oa->type == 'tab')) {
                $node[$aid] = array();
                $tree[$aid] = &$node[$aid];
                if ($oa->ordered === null) {
                    $oa->ordered = $this->getNodeOrder($oa, $oas);
                }
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
                if ($oa->ordered === null) {
                    $oa->ordered = $this->getNodeOrder($oa, $oas);
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
                if ($oa->ordered === null) {
                    $oa->ordered = $this->getNodeOrder($oa, $oas);
                }
            }
        }
        
        foreach ( $oas as $aid => &$oa ) {
            if (($oa->usefor != "Q") && ($oa->type != 'menu') && ($oa->type != 'action')&& ($oa->type != 'array') && ($oa->type != 'frame') && ($oa->type != 'tab')) {
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
    
    private function getNodeOrder(BasicAttribute &$noa, array &$oas)
    {
        if ($noa->ordered === null) {
            foreach ( $oas as $aid => &$oa ) {
                $fid = $oa->fieldSet->id;
                if ($fid == $noa->id) {
                    $noa->ordered = intval($this->getNodeOrder($oa, $oas)) - 1;
                    break;
                }
            }
        }
        return intval($noa->ordered);
    }
    private function bindingViewNodeAttribute(array $node, array &$oas, BasicAttribute &$oa = null)
    {
        $out = '';
        if ($oa) {
            $visibility = $oa->mvisibility ? $oa->mvisibility : $oa->visibility;
        }
        if ((!$oa) || (($visibility != 'I') && ($visibility != 'H') && ($visibility != 'O'))) {
            
            if ($oa) {
                $label = $oa->encodeXml($oa->getLabel(), true);
                $out .= sprintf('<dcpAttribute label="%s" type="%s" attrid="%s">', $label, $oa->type, $oa->id);
            
            }
            $callback = function ($a, $b) use($oas)
            {
                //print "\n$a:".$oas[$a]->ordered. " - $b:".$oas[$b]->ordered;
                if ($oas[$a]->type=="tab" && $oas[$b]->type!="tab") return 1;
                else if ($oas[$a]->type!="tab" && $oas[$b]->type=="tab") return -1;
                if ($oas[$a]->ordered > $oas[$b]->ordered) return 1;
                else if ($oas[$a]->ordered < $oas[$b]->ordered) return -1;
                return 0;
            };
            uksort($node, $callback);
            $tabbox = false;
            /*
            print_r2(array_keys($node));
            foreach ( $node as $k => $v ) {
                print "<br/>$k:".$oas[$k]->ordered;
            }*/
            foreach ( $node as $k => $v ) {
                if ((!$tabbox) && ($oas[$k]->type == "tab")) {
                    $tabbox = true;
                    $out .= "<xul:tabbox><xul:tabs/><xul:tabpanels>";
                }
                if (is_array($v)) {
                    $out .= $this->bindingViewNodeAttribute($v, $oas, $oas[$k]);
                } else {
                    $out .= $this->bindingViewLeafAttribute($oas[$k]) . "\n";
                }
                if ($tabbox && ($oas[$k]->type != "tab")) {
                    $tabbox = false;
                    $out .= "</xul:tabpanels></xul:tabbox>";
                
                }
            }
            if ($tabbox) {
                $out .= "</xul:tabpanels></xul:tabbox>";
            }
            if ($oa) {
                $out .= sprintf('</dcpAttribute>');
            }
        }
        
        return $out;
    }
    
    private function bindingViewLeafAttribute(BasicAttribute &$oa)
    {
        $out = '';
        $visibility = $oa->mvisibility ? $oa->mvisibility : $oa->visibility;
        if (($visibility != "I") && ($visibility != "H")) {
            $label = $oa->encodeXml($oa->getLabel(), true);
            switch ($oa->type) {
            case 'docid' :
                $out = sprintf('<dcpAttribute label="%s" type="%s" attrid="%s" relationFamily="%s" multiple="%s"/>', $label, $oa->type, $oa->id, trim($oa->format), ($oa->getOption("multiple") == "yes") ? "true" : "false");
                break;
            case 'enum' :
                $out = sprintf('<dcpAttribute label="%s" type="%s" attrid="%s"  multiple="%s"/>', $label, $oa->type, $oa->id, ($oa->getOption("multiple") == "yes") ? "true" : "false");
                break;
            default :
                $out = sprintf('<dcpAttribute label="%s" type="%s" attrid="%s"/>', $label, $oa->type, $oa->id);
            }
        }
        return $out;
    }
    
    private function bindingEditNodeAttribute(array $node, array &$oas, BasicAttribute &$oa = null)
    {
        $out = '';
        $visibility = "";
        if ($oa) {
            $visibility = $oa->mvisibility ? $oa->mvisibility : $oa->visibility;
        }
        if ((!$oa) || ($visibility != 'I')) {
            if ($oa) {
                $label = $oa->encodeXml($oa->getLabel(), true);
                $out = sprintf('<dcpAttribute label="%s" type="%s" attrid="%s" visibility="%s">', $label, $oa->type, $oa->id, $visibility);
            }
            $callback = function ($a, $b) use($oas)
            {
                //print "\n$a:".$oas[$a]->ordered. " - $b:".$oas[$b]->ordered;
                if ($oas[$a]->type=="tab" && $oas[$b]->type!="tab") return 1;
                else if ($oas[$a]->type!="tab" && $oas[$b]->type=="tab") return -1;
                if ($oas[$a]->ordered > $oas[$b]->ordered) return 1;
                else if ($oas[$a]->ordered < $oas[$b]->ordered) return -1;
                return 0;
            };
            uksort($node, $callback);
            $tabbox = false;
            foreach ( $node as $k => $v ) {
                
                if ((!$tabbox) && ($oas[$k]->type == "tab")) {
                    $tabbox = true;
                    $out .= "<xul:tabbox><xul:tabs/><xul:tabpanels>";
                }
                if (is_array($v)) {
                    $out .= $this->bindingEditNodeAttribute($v, $oas, $oas[$k]);
                } else {
                    $out .= $this->bindingEditLeafAttribute($oas[$k]) . "\n";
                }
                if ($tabbox && ($oas[$k]->type != "tab")) {
                    $tabbox = false;
                    $out .= "</xul:tabpanels></xul:tabbox>";
                }
            
            }
            if ($tabbox) {
                $out .= "</xul:tabpanels></xul:tabbox>";
            }
            if ($oa) {
                $out .= sprintf('</dcpAttribute>');
            }
        }
        
        return $out;
    }
    
    private function getDefaultValue($aid) {
        if (is_array($this->_defaultValues)) {
            $def=$this->_defaultValues[$aid];
            if (strtolower($def)=="::getuserid()") $def=Doc::getUserId();
            elseif (strtolower($def)=="::userdocid()") $def=Doc::userDocId();
            elseif (substr($def,0,2)=="::") $def='';
            return $def;
        }
        return '';
    }
    
    private function bindingEditLeafAttribute(BasicAttribute &$oa)
    {
        $out = '';
        $visibility = $oa->mvisibility ? $oa->mvisibility : $oa->visibility;
        if ($visibility != 'I') {
            $options = array(
                'esize',
                'elabel',
                'cwidth'
            );
            $opt = '';
            foreach ( $options as $option ) {
                if ($oa->getOption($option)) {
                    $opt .= $option . '="';
                    $opt .= $oa->encodeXml($oa->getOption($option), true);
                    $opt .= '" ';
                }
            }
            $label = $oa->encodeXml($oa->getLabel(), true);
            $common=sprintf(' label="%s" type="%s" attrid="%s" visibility="%s" required="%s" defaultValue="%s" %s', $label, $oa->type, $oa->id, $visibility, $oa->needed?'true':'false', $this->getDefaultValue($oa->id), $opt);
            switch ($oa->type) {
            case 'docid' :
                $out = sprintf('<dcpAttribute %s relationFamily="%s" multiple="%s"/>', $common, trim($oa->format), ($oa->getOption("multiple") == "yes") ? "true" : "false");
                break;
            case 'enum' :
                $out = sprintf('<dcpAttribute %s multiple="%s" />', $common, ($oa->getOption("multiple") == "yes") ? "true" : "false");
                break;
            default :
                $out = sprintf('<dcpAttribute %s/>', $common);
            }
        }
        return $out;
    }
}

?>
