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
 * DummyCache model for testing
 *
 * @uses      Deneb_Object_Common
 * @category  Deneb
 * @package   Deneb
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2010 Empower Campaigns
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/empower/deneb
 */
class Deneb_DummyCache extends Deneb_Object_Common
{
    /**
     * The name of the object for use in exception messages
     *
     * @var string
     */
    protected $_object = 'dummycache';

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
     * A list of additional columns to cache an object by
     *
     * @var array
     */
    protected $_additionalCacheIndexes = array('email');
}
