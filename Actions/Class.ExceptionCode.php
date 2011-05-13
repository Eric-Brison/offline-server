<?php
/**
 * Exception code
 * all code exception use by platform are here
 * @category --category--
 * @package platform
 * @author anakeen
 * @copyright : anakeen SAS
 * @license : http://www.gnu.org/licenses/agpl.html GNU AFFERO GENERAL PUBLIC LICENSE
**/


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
    const createForbidden =   1000; //!< create is forbidden
    /**  domain reference must be unique @var int */
    const referenceExists =    1001  ; //!< domain reference must be unique
    /**  domain reference accept onlyh alphanum characters @var int */
    const referenceInvalid =   1002 ; //!< domain reference accept onlyh alphanum characters
    

}

?>
