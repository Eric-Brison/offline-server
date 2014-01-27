<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/
/**
 * Exception code
 * all code exception use by platform are here
 * @category --category--
 * @package OFFLINE
 * @author anakeen
 * @copyright : anakeen SAS
 * @license : http://www.gnu.org/licenses/agpl.html GNU AFFERO GENERAL PUBLIC LICENSE
 *
 */

namespace Dcp\Offline;
/**
 * Exception code
 * @brief all code exception use by offline are here
 * @class ExceptionCode
 * @author anakeen
 * @package offline
 */
class OfflineExceptionCode
{
    /** create is forbidden @var int */
    const createForbidden = 1000; //!< create is forbidden
    
    /**  domain reference must be unique @var int */
    const referenceExists = 1001; //!< domain reference must be unique
    
    /**  domain reference accept onlyh alphanum characters @var int */
    const referenceInvalid = 1002; //!< domain reference accept onlyh alphanum characters
    
    
}
