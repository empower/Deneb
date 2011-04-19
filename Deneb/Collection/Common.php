<?php
/**
 * Deneb_Collection_Common
 *
 * @uses      Deneb
 * @uses      Iterator, Countable
 * @category  Deneb
 * @package   Deneb
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2010 Empower Campaigns
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/empower/deneb
 */

/**
 * Required file
 */
require_once 'Deneb.php';

/**
 * Base class for all colletions of single objects
 *
 * @uses      Deneb
 * @uses      Iterator, Countable
 * @category  Deneb
 * @package   Deneb
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2010 Empower Campaigns
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/empower/deneb
 */
abstract class Deneb_Collection_Common
extends Deneb
implements Iterator, Countable
{
    /**
     * Name of the singlur instance.  i.e. "User" for "Deneb_User"
     *
     * @var string
     */
    protected $_object = null;

    /**
     * The current position of the collection
     *
     * @var int
     * @see Iterator
     */
    protected $_position = 0;

    /**
     * Array of single object instances, in the order which they came back
     * from the DB
     *
     * @var array
     */
    protected $_collection = array();

    /**
     * Array of single object instances, indexed by primary key value
     *
     * @var array
     */
    protected $_collectionByPrimaryKey = array();

    /**
     * Options passed to the constructor, used in multiple methods
     * @var array
     */
    protected $_options = array();

    /**
     * Sets where parameters and options for collection inclusion, then optionally
     * talks to the data store and instantiates the objects
     *
     * @param array $args    The key/values that can be used in a where clause
     * @param array $options Optional limits, offset, order, group, having, fetch
     *
     * @return Deneb_Collection_Common
     */
    public function __construct(array $args, array $options = array())
    {
        $this->_position = 0;
        $this->_init();
        $this->_args    = $args;
        $this->_options = $options;

        if (!isset($options['fetch']) || $options['fetch'] == true) {
            $this->fetch();
        }
    }

    /**
     * Fetches collection objects - usually called by constuctor, but not if
     * options['fetch'] = false
     *
     * @see _fetchFromCacheOrDB(), _fetchFromDB()
     * @return null
     */
    public function fetch()
    {
        $where  = $this->_determineWhere($this->_args);
        $limits = $this->_determineOptions($this->_options);

        $object    = new $this->_object();
        $cacheable = $object->isCacheable();

        if ($cacheable) {
            $this->_fetchFromCacheOrDB($where, $limits, $object);
        } else {
            $this->_fetchFromDB($where, $limits);
        }
    }

    /**
     * Looks up the matching IDs for this collection, and then tries to populate
     * {@link Deneb::$_results} from the cache first.  Any
     * misses are gotten from the DB.
     *
     * @param string              $where  The where clause as determined
     *                                    by _determineWhere()
     * @param string              $limits The limits clause as determined
     *                                    by _determineLimits()
     * @param Model_Object_Common $object An instance of the object for cache access
     *
     * @see $_results, fetch(), _loadObjects()
     * @throws Model_Exception_NotFound on empty results
     * @return void
     */
    protected function _fetchFromCacheOrDB($where, $limits, $object)
    {
        $pk = $object->getPrimaryKey();

        // See if we can skip the ID lookup

        if (count($this->_args) === 1      // Only one element in args
            && empty($this->_options)      // No options are being used
            && isset($this->_args[$pk])    // That arg is the primary key
            && is_array($this->_args[$pk]) // Its value is an array
            && count($this->_args[$pk])) { // The array is not empty

            $keyResults = array();
            foreach ($this->_args[$pk] as $value) {
                $keyResults[] = array($pk => $value);
            }
        } else {
            // Look up IDs from the DB
            $sql = "SELECT {$pk} FROM {$this->_table} $where $limits";
            $keyResults = $this->fetchAll($sql);
        }

        if (!count($keyResults)) {
            throw new static::$_exceptionNotFoundName(
                'No ' . $this->_name . ' objects found'
            );
        }

        // Order results and check the cache first
        $orderedList = array();
        foreach ($keyResults as $result) {
            $orderedList[$result[$pk]] = $object->getFromCache(
                array($pk => $result[$pk])
            );
        }

        // Fetch misses from DB if present
        $misses = array();
        foreach ($orderedList as $key => $value) {
            if ($value === false) {
                $misses[$key] = false;
            }
        }
        if (count($misses)) {
            $idWhere = $this->_determineWhere(
                array($pk => array_keys($misses))
            );
            $sql = "SELECT * FROM {$this->_table} $idWhere";
            $missResults = $this->fetchAll($sql);

            foreach ($missResults as $result) {
                $misses[$result[$pk]] = $result;
            }
        }

        // Map results and load them
        foreach ($orderedList as $key => $value) {
            if ($value === false) {
                if ($misses[$key] !== false) {
                    $orderedList[$key] = $misses[$key];
                } else {
                    // That item got removed from the DB recently
                    $this->getLog()->info(
                        "Item found in id list no longer in db: $key."
                        . " Removing from results set"
                    );
                    unset($orderedList[$key]);
                    unset($misses[$key]);
                }
            }
        }

        if (!count($orderedList)) {
            throw new static::$_exceptionNotFoundName(
                'No ' . $this->_name . ' objects found'
            );
        }
        $this->_loadObjects($orderedList);

        // Update cache for any misses
        foreach (array_keys($misses) as $miss) {
            $this->getByPrimaryKey($miss)->updateCache();
        }
    }

    /**
     * Fetches results from the DB directly
     *
     * @param string $where  The where clause as determined by _determineWhere()
     * @param string $limits The limits clause as determined by _determineLimits()
     *
     * @see fetch(), $_results, _loadObjects()
     * @throws Model_Exception_NotFound on empty results
     * @return void
     */
    protected function _fetchFromDB($where, $limits)
    {
        $sql = "SELECT * FROM {$this->_table} $where $limits";
        $results = $this->fetchAll($sql);
        if (!count($results)) {
            throw new static::$_exceptionNotFoundName(
                'No ' . $this->_name . ' objects found'
            );
        }
        $this->_loadObjects($results);
    }

    /**
     * Takes an array of results and loads it up into Deneb_Object_Common
     * instances, stored in {@link Deneb_Collection_Common::$_collection}
     *
     * @param array $results The results from fetchAll()
     *
     * @see _loadObject()
     * @return void
     */
    protected function _loadObjects(array $results)
    {
        foreach ($results as $item) {
            $this->_collection[] = $this->_loadObject($item);
        }
    }

    /**
     * Instantiates an object from a query result, assigns it to
     * $_collectionByPrimaryKey, and returns it
     *
     * @param array $result The row result from the DB or Cache
     *
     * @return Deneb_Object_Common
     */
    protected function _loadObject(array $result)
    {
        $instance = new $this->_object;
        $instance->initializeValues($result);
        $pk = $instance->getPrimaryKey();
        $this->_collectionByPrimaryKey[$instance->{$pk}] = $instance;

        return $instance;
    }

    /**
     * Returns an object by the primary key of the table
     *
     * @param mixed $key The primary key of the table
     *
     * @return Deneb_Object_Common
     */
    public function getByPrimaryKey($key)
    {
        if (!isset($this->_collectionByPrimaryKey[$key])) {
            throw new static::$_exceptionNotFoundName(
                'Primary key not found: ' . $key
            );
        }
        return $this->_collectionByPrimaryKey[$key];
    }

    /**
     * Returns an array of the primary key values in this result set
     *
     * @return array
     */
    public function getPrimaryKeys()
    {
        return array_keys($this->_collectionByPrimaryKey);
    }

    /**
     * Returns the total number of objects that would be returned under the specified
     * conditions (ignoring options)
     *
     * @return int
     */
    public function countAll()
    {
        $where  = $this->_determineWhere($this->_args);
        $sql = "SELECT COUNT(*) FROM {$this->_table} $where";
        return (int) $this->fetchColumn($sql);
    }

    /**
     * Rewinds the collection interator to position 0
     *
     * @see Iterator
     * @return void
     */
    public function rewind()
    {
        $this->_position = 0;
    }

    /**
     * Returns the current collection object
     *
     * @see Iterator
     * @return Deneb_Object_Common
     */
    public function current()
    {
        return $this->_collection[$this->_position];
    }

    /**
     * Returns the numeric position
     *
     * @see Iterator
     * @return int
     */
    public function key()
    {
        return $this->_position;
    }

    /**
     * Increments the position
     *
     * @see Iterator
     * @return void
     */
    public function next()
    {
        ++$this->_position;
    }

    /**
     * Validates whether the current {@link Deneb_Collection_Common::$_position}
     * exists
     *
     * @see Iterator
     * @return bool
     */
    public function valid()
    {
        return isset($this->_collection[$this->_position]);
    }

    /**
     * Implements the Countable interface
     *
     * @see Countable
     * @return int
     */
    public function count()
    {
        return count($this->_collection);
    }
}
