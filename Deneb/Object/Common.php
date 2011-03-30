<?php
/**
 * Deneb_Object_Common
 *
 * @uses      Deneb
 * @uses      Deneb_Object_Interface
 * @category  Deneb
 * @package   Deneb
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2010 Empower Campaigns
 * @link      http://github.com/empower/deneb
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 */

/**
 * Required files
 */
require_once 'Deneb.php';
require_once 'Deneb/Object/Interface.php';

/**
 * Implementation of single objects.
 *
 * @uses      Deneb
 * @uses      Deneb_Object_Interface
 * @category  Deneb
 * @package   Deneb
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2010 Empower Campaigns
 * @link      http://github.com/empower/deneb
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 */
abstract class Deneb_Object_Common
    extends Deneb
    implements Deneb_Object_Interface
{
    /**
     * The primary key name
     *
     * @var string
     */
    protected $_primaryKey = 'id';

    /**
     * Whether to automatically populate a date_created column with the
     * current datetime value.  Defaults to false.
     *
     * @var bool
     */
    protected $_enableDateCreated = false;

    /**
     * If true, assume date_created is an integer column and write unix timestamps
     *   to it.  If false (default), assume it's a datetime column.
     *
     * @var bool
     */
    protected $_dateCreatedAsTimestamp = false;

    /**
     * A list of additional columns to cache an object by
     *
     * @var array
     */
    protected $_additionalCacheIndexes = array();

    /**
     * Whether caching should be enabled for this object
     *
     * @var bool
     */
    protected $_cacheEnabled = true;

    /**
     * Array of field names for which values should not be returned by
     * {@link Deneb_Object_Common::get()}
     *
     * @var array
     */
    protected $_protectedFields = array();

    /**
     * Array of valid status values for
     * {@link Deneb_Object_Common::setStatus()} /
     * {@link Deneb_Object_Common::hasStatus()}
     *
     * These should be constants defined in the child class with values
     *   1, 2, 4, 8, 16...  order in this array is not important
     *
     * @var array
     */
    protected $_validStatuses = array();

    /**
     * Whether the object should have only one status at a time (actually, makes
     * {@link Deneb_Object_Common::setStatus()} clear before adding a status)
     *
     * @var boolean
     */
    protected $_enforceSingleStatus = false;

    /**
     * The object property values
     *
     * @see Deneb_Object_Common::__get()
     * @see Deneb_Object_Common::__set()
     * @see Deneb_Object_Common::__isset()
     * @see Deneb_Object_Common::__unset()
     * @see Deneb_Object_Common::set()
     * @var array
     */
    protected $_values = array();

    /**
     * Names of object properties that have been modified since loading - only those
     *   fields will be written to the database by update()
     *
     * Actually, the property names are KEYS in the array
     *
     * @see Deneb_Object_Common::update()
     * @var array
     */
    protected $_propertiesModified = array();

    /**
     * If no $args argument is passed, an empty object is created.  If the
     * $args argument is passed, the object is looked up optionally in the
     * cache first, and then the data store.  If a lookup is performed and no
     * object is found, a {@link Deneb_Exception_NotFound} exception is thrown.
     *
     * Below is an example where Model_User extends {@link Deneb_Object_Common}:
     *
     * Read example:
     * <code>
     * try {
     *     $user = new Model_User(array('username' => 'shupp'));
     * } catch (Deneb_Exception_NotFound $e) {
     *     echo "Sorry, could not find user with username 'shupp'";
     * }
     * </code>
     *
     *
     * Create example:
     * <code>
     * $user = new Model_User();
     * $user->username = 'shupp';
     * $user->email = 'bshupp@empowercampaigns.com';
     * try {
     *     $user->create();
     * } catch (Deneb_Exception $e) {
     *    echo "Unable to create new user: " . $e->getMessage();
     * }
     * </code>
     *
     * @param array $args Arguments to used in a "where clause".  Leave empty
     *                    when creating a new object
     *
     * @return Deneb_Object_Common
     * @throws Deneb_Exception_NotFound on lookup failure
     */
    public function __construct(array $args = array())
    {
        $this->_init();
        $this->_args = $args;

        if (!empty($args)) {
            // Try to get the data from the cache
            $fromCache = $this->getFromCache($args);
            if ($fromCache !== false) {
                $this->_values = $fromCache;
                return;
            }

            // Else, do the lookup manually and then cache it
            $this->_loadFromDB($args);
            $this->updateCache();
        }
    }

    /**
     * Makes a call to the DB to load data into the object
     *
     * @param array  $args The contents of the first constructor argument
     * @param string $type The read/write type.  Defaults to read.
     *
     * @return void
     */
    protected function _loadFromDB(array $args, $type = 'read')
    {
        $db = ($type == 'write') ? $this->_getWriteDB() : $this->_getReadDB();

        $where = $this->_determineWhere($args);

        $sql = "SELECT * FROM {$this->_table} $where";
        $this->_results = $this->fetchAll($sql, $db);
        if (!count($this->_results)) {
            throw new static::$_exceptionNotFoundName(
                'No ' . $this->_object . ' found: '

                . print_r($args, true)
            );
        }
        $this->_values = current($this->_results);
    }

    /**
     * Allows external checking of whether caching can be used
     *
     * @return bool
     */
    public function isCacheable()
    {
        if ($this->_cacheEnabled && self::$_cache !== null) {
            return true;
        }

        return false;
    }

    /**
     * Gets object data from the cache and returns it.
     *
     * @param array $args
     *
     * @return  array on success, false on failure
     */
    public function getFromCache(array $args)
    {
        if (!$this->isCacheable()) {
            return false;
        }

        $cache   = $this->getCache();
        $indexes = $this->_getCacheIndexes();

        foreach ($indexes as $index) {
            if (!isset($args[$index])) {
                continue;
            }

            $key    = $this->getCacheKey($index, $args[$index]);
            $result = $cache->load($key);

            if ($result !== false) {
                return unserialize($result);
            }
        }

        return false;
    }

    /**
     * Gets an array of cache indexes for use with
     * {@link Deneb_Object_Common::getCacheKey()}.  Includes the primary key,
     * as well as any additional indexes.
     *
     * @see $_additionalCacheIndexes, $_primaryKey
     * @return array
     */
    protected function _getCacheIndexes()
    {
        $columns = array_unique(
            array_merge(
                $this->_additionalCacheIndexes,
                array($this->_primaryKey)
            )
        );

        return $columns;
    }

    /**
     * Removes all cache entries for the current object
     *
     * @return void
     */
    public function invalidateCache()
    {
        if (!$this->isCacheable()) {
            return false;
        }

        $cache   = $this->getCache();
        $indexes = $this->_getCacheIndexes();

        foreach ($indexes as $index) {
            $key = $this->getCacheKey($index, $this->_values[$index]);
            $cache->remove($key);
        }
    }

    /**
     * Stores the current values in all cache indexes
     *
     * @return void
     */
    public function updateCache()
    {
        if (!$this->isCacheable()) {
            return false;
        }

        $cache   = $this->getCache();
        $indexes = $this->_getCacheIndexes();
        $data    = serialize($this->_values);
        foreach ($indexes as $index) {
            $key = $this->getCacheKey($index, $this->_values[$index]);
            $cache->save($data, $key);
        }
    }

    /**
     * Helper for creating cache keys (md5) based on the class name,
     * index name, and index value
     *
     * @param string $index The index name (column)
     * @param mixed  $value The index value
     *
     * @return string
     */
    public function getCacheKey($index, $value)
    {
        return md5(get_class($this) . '.' . $index . '.' . $value);
    }

    /**
     * Checks to see whether a given status bit has been set
     *
     * @param int $bit The bit flag to check
     *
     * @return bool
     */
    public function hasStatus($bit)
    {
        return (bool) ($this->status & (int)$bit);
    }

    /**
     * Enables a specific status bit
     *
     * @param int $bit The bit to enable
     *
     * @return Deneb_Object_Common
     * @throws Deneb_Exception for an invalid bit
     */
    public function setStatus($bit)
    {
        if (!in_array($bit, $this->_validStatuses, true)) {
            throw new static::$_exceptionName('Invalid status bit: ' . $bit);
        }
        if ($this->_enforceSingleStatus) {
            $this->status = $bit;
        } else {
            $this->status = ($this->status | $bit);
        }
        return $this;
    }

    /**
     * Unsets a status bit
     *
     * @param int $bit The bit to disable
     *
     * @return Deneb_Object_Common
     * @throws Deneb_Exception for an invalid bit
     */
    public function unsetStatus($bit)
    {
        if (!in_array($bit, $this->_validStatuses, true)) {
            throw new static::$_exceptionName('Invalid status bit: ' . $bit);
        }
        $this->status = ($this->status & ~$bit);
        return $this;
    }

    /**
     * Magic {@link Deneb_Object_Interface::__get()} method implementation.
     * Allows for easy property access.
     *
     * <code>
     * $username = $user->username;
     * </code>
     *
     * @param string $name
     *
     * @return mixed|null
     */
    public function __get($name)
    {
        if (isset($this->_values[$name])) {
            return $this->_values[$name];
        }
        return null;
    }

    /**
     * Magic {@link Deneb_Object_Interface::__set()} method implementation.
     * Allows for easy property value assignment.
     *
     * <code>
     * $user->username = 'shupp';
     * </code>
     *
     * @param string $name  The property name to set
     * @param mixed  $value The value to set
     *
     * @return mixed|null
     */
    public function __set($name, $value)
    {
        $this->_values[$name] = $value;
        $this->_propertiesModified[$name] = true;
    }

    /**
     * Magic {@link Deneb_Object_Interface::__unset()} method implementation.
     * Allows for easily unsetting property values.
     *
     * @param string $name The property value to unset
     *
     * @return void
     */
    public function __unset($name)
    {
        unset($this->_values[$name]);
        unset($this->_propertiesModified[$name]);
    }

    /**
     * Magic {@link Deneb_Object_Interface::__isset()} method implementation.
     * Allows for easy checking whether a value is set or not.
     *
     * @param string $name The property name to check
     *
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->_values[$name]);
    }

    /**
     * Sets all values in an object, removing any previously set values.
     *
     * @param array $values Associative array of property/values
     *
     * @see $_values
     * @return void
     */
    public function set(array $values)
    {
        $this->_values = $values;
        foreach (array_keys($values) as $name) {
            $this->_propertiesModified[$name] = true;
        }
    }

    /**
     * Gets the current property values of an object as an associative array,
     * omitting anything listed in
     * {@link Deneb_Object_Common::$_protectedFields}.  This is useful for
     * making values available to a view.
     *
     * @return array
     */
    public function get()
    {
        $values = $this->_values;
        foreach ($this->_protectedFields as $name) {
            unset($values[$name]);
        }
        return $values;
    }

    /**
     * Creates an object in the data store.  If a cache is being used, it will
     * be updated.  The current objects values will be updated with a follow up
     * read from the data store, as this insures that any auto-generated values
     * are populated in this object instance.
     *
     * @param array $args Optional set of values to use, overrides any values
     *                    already set
     *
     * @see $_values, set()
     * @throws Deneb_Exception on insert failure
     * @return void
     */
    public function create(array $args = array())
    {
        if (!empty($args)) {
            $this->set($args);
        }

        if ($this->_enableDateCreated
            && !isset($this->_values['date_created'])) {

            $this->_values['date_created'] = ($this->_dateCreatedAsTimestamp
                ? new Zend_Db_Expr('UNIX_TIMESTAMP()')
                : new Zend_Db_Expr('NOW()'));
        }

        $this->_getWriteDB()->insert($this->_table, $this->_values);

        // Now let's update our local values with what's actually in the row
        if (!isset($this->_values[$this->_primaryKey])) {
            $pkValue = $this->_getWriteDB()->lastInsertId();
        } else {
            $pkValue = $this->_values[$this->_primaryKey];
        }

        $args = array($this->_primaryKey => $pkValue);
        $this->_loadFromDB($args, 'write');
        $this->updateCache();
    }

    /**
     * Stores any changes to an object in the data store.  If a cache is used,
     * it gets updated.
     *
     * @throws Deneb_Exception on failure
     * @return void
     */
    public function update()
    {
        if (!isset($this->_values[$this->_primaryKey])) {
            throw new static::$_exceptionName('Primary key value is not set');
        }

        if (count($this->_propertiesModified)) {
            $valuesToWrite = array();
            foreach ($this->_propertiesModified as $name => $present) {
                $valuesToWrite[$name] = $this->_values[$name];
            }

            $where = "{$this->_primaryKey} = {$this->_values[$this->_primaryKey]}";
            $this->_getWriteDB()->update($this->_table, $valuesToWrite, $where);
        }
        $this->invalidateCache();
    }

    /**
     * Deletes an object form the data store.  If a cache is used, the cache
     * is invalidated.
     *
     * @param mixed $id Optional primary key value to use instead of the
     *                  current object's value. Used when you don't want to
     *                  determine first if an object exists
     *
     * @return void
     */
    public function delete($id = null)
    {
        if ($id !== null) {
            $this->{$this->_primaryKey} = $id;
        }

        if (!isset($this->_values[$this->_primaryKey])) {
            throw new static::$_exceptionName('Primary key value is not set');
        }

        $where  = $this->_primaryKey . ' = ';
        $where .= $this->_values[$this->_primaryKey];
        $this->_getWriteDB()->delete($this->_table, $where);
        $this->invalidateCache();
    }

    /**
     * Returns {@link Deneb_Object_Common::$_primaryKey}
     *
     * @return string
     */
    public function getPrimaryKey()
    {
        return $this->_primaryKey;
    }
}
