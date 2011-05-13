/**
 * @author Anakeen
 * @version $Id:  $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package OFFLINE
 * @subpackage
 */

function initWithFolder(event, dirid) {
	viewFolder(event,dirid);
}

addEvent(window,'load',function(event) {initWithFolder(event,'9');});
