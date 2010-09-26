<?php
/**
 * Deneb_DummyCollection
 * 
 * @uses      Deneb_Collection_Common
 * @category  Deneb
 * @package   Deneb
 * @author    Bill Shupp <hostmaster@shupp.org> 
 * @copyright 2010 Empower Campaigns
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/empower/deneb
 */

require_once 'Deneb/Collection/Common.php';

/**
 * Dummy model collection for testing
 * 
 * @uses      Deneb_Collection_Common
 * @category  Deneb
 * @package   Deneb
 * @author    Bill Shupp <hostmaster@shupp.org> 
 * @copyright 2010 Empower Campaigns
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/empower/deneb
 */
class Deneb_DummyCollection extends Deneb_Collection_Common
{
    /**
     * The name of the singular object
     * 
     * @var string
     */
    protected $_object = 'Deneb_Dummy';

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
}
