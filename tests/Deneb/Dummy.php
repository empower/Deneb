<?php
/**
 * Deneb_Dummy
 *
 * @uses      Deneb_Object_Common
 * @category  Deneb
 * @package   Deneb
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2010 Empower Campaigns
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/empower/deneb
 */

require_once 'Deneb/Object/Common.php';

/**
 * Dummy model for testing
 *
 * @uses      Deneb_Object_Common
 * @category  Deneb
 * @package   Deneb
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2010 Empower Campaigns
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/empower/deneb
 */
class Deneb_Dummy extends Deneb_Object_Common
{
    /**
     * The name of the object for use in exception messages
     *
     * @var string
     */
    protected $_name = 'dummy';

    /**
     * The table to use in the DB
     *
     * @var string
     */
    protected $_table = 'Dummys';

    /**
     * The DB selector
     *
     * @var string
     */
    protected $_selector = 'default';

    /**
     * _enableDateCreated
     *
     * @var bool
     */
    protected $_enableDateCreated = true;

    /**
     * _cacheEnabled
     *
     * @var bool
     */
    protected $_cacheEnabled = false;

    /**
     * Array of field names for which values should not be returned by get()
     *
     * @var array
     */
    protected $_protectedFields = array('protected_field');
}
