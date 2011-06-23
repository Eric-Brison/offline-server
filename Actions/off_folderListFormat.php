<?php
/**
 * Format column for folder list
 *
 * @author Anakeen 2006
 * @version $Id:  $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package OFFLINE
 * @subpackage
 */
/**
 */

/**
 * Format column for folder list
 * 
 */
class offFolderListFormat
{
    
    static public function getIcon(Doc &$doc)
    {
        $icon = $doc->getIcon('', 20);
        
        return sprintf('<img class="icon" src="%s">', $icon);
    
    }
    
    static public function getStatus(Doc &$doc)
    {
        
        $icon = $doc->getEmblem(20);
        
        return sprintf('<img class="icon" src="%s">', $icon);
    
    }
    static public function getSyncStatus(Doc &$doc)
    {
        $domains = $doc->getDomainIds();
        if (count($domains) > 0) {
            $sDomain = array();
            ;
            foreach ( $domains as $domain ) {
                $sDomain[] = $doc->getHTMLTitle($domain);
            }
            global $action;
            $img = sprintf('<img class="icon" src="%s"> ', $action->getImageUrl('domainsync.png', true, 20));
            
            return $img . implode(", ", $sDomain);
        }
        /*
        if ($doc->isInDomain()) {
            
        
        }*/
        return '';
    
    }
    
    static public function getStatusMessage(Doc &$doc)
    {
        static $w = null;
        global $action;
        
        if ($w == null) {
            include_once ("FDL/Class.DocWait.php");
            $w = new DocWait($doc->dbaccess);
        }
        if ($w->select(array(
            $doc->initid,
            $doc->userid
        ))) {
            $img = '';
            switch ($w->status) {
            case docWait::invalid :
                $img = sprintf('<img title="%s" class="icon" src="%s"> ', _("off:invalid"), $action->getImageUrl('status_invalid.png', true, 20));
                 $message=$w->statusmessage;
                break;
            case docWait::constraint :
                $img = sprintf('<img title="%s" class="icon" src="%s"> ', _("off:constraint"), $action->getImageUrl('status_constraint.png', true, 20));
                 $message=$w->statusmessage;
                break;
            case docWait::upToDate :
                $img = sprintf('<img title="%s" class="icon" src="%s"> ', _("off:upToDate"), $action->getImageUrl('status_uptodate.png', true, 20));
                $message=sprintf("last update %s", FrenchDateToLocaleDate($w->date));
                break;
            case docWait::conflict :
                $img = sprintf('<img title="%s" class="icon" src="%s"> ', _("off:conflict"), $action->getImageUrl('status_conflict.png', true, 20));
                 $message=$w->statusmessage;
                break;
            default :
                $img = $w->status;
                $message=$w->statusmessage;
            }
            return $img . $message;
        
        }
        return '';
    
    }
    
    static public function getColumnDescription()
    {
        return array(
            "icon" => array(
                "htitle" => _("icon"),
                "horder" => "title",
                "issort" => false,
                "method" => "offFolderListFormat::getIcon(THIS)"
            ),
            "title" => array(
                "htitle" => _("title"),
                "horder" => "title",
                "issort" => false,
                "method" => "::getHtmlTitle()"
            ),
            "date" => array(
                "htitle" => _("Modification Date Menu"),
                "horder" => "date",
                "issort" => false,
                "method" => "wsFolderListFormat::getMDate(THIS)"
            ),
            "domain" => array(
                "htitle" => _("Domain"),
                "horder" => "status",
                "issort" => false,
                "method" => "offFolderListFormat::getSyncStatus(THIS)"
            ),
            "status" => array(
                "htitle" => _("Status"),
                "horder" => "status",
                "issort" => false,
                "method" => "offFolderListFormat::getStatus(THIS)"
            ),
            "reason" => array(
                "htitle" => _("Reason"),
                "horder" => "",
                "issort" => false,
                "method" => "offFolderListFormat::getStatusMessage(THIS)"
            )
        );
    }
}
?>
