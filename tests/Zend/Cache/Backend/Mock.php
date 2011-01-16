<?php
/**
 * @see Zend_Cache_Backend_Interface
 */
require_once 'Zend/Cache/Backend/ExtendedInterface.php';

/**
 * @see Zend_Cache_Backend
 */
require_once 'Zend/Cache/Backend.php';

/**
 * @see Zend_Cache
 */
require_once 'Zend/Cache.php';

/**
 * Zend_Cache_Backend_Mock - a Zend_Cache backend for unit testing
 *
 * Has no external dependencies (files, memcache, APC) - only stores values
 *   in process memory
 *
 * Cache contents do not persist between requests
 *
 * Based on Zend_Cache_Backend_Blackhole from which it borrows much
 * (non)functionality
 *
 * @package    Zend_Cache
 * @subpackage Zend_Cache_Backend
 * @author     Dan Copeland <dcopeland@empowercampaigns.com>
 * @copyright  2010 Empower Campaigns
 * @license    New BSD License (enclosed)
 * @link       http://github.com/empower/Zend_Cache_Backend_Mock
 */
class Zend_Cache_Backend_Mock
    extends Zend_Cache_Backend
    implements Zend_Cache_Backend_ExtendedInterface
{
    /**
     * Contains all saved data (doesn't persist beyond the request)
     *
     * @var array
     */
    protected $_cache = array();

    /**
     * Test if a cache is available for the given id and (if yes) return it
     * (false else)
     *
     * @param  string $id cache id
     * @param  boolean $doNotTestCacheValidity if set to true, the cache
     *                                         validity won't be tested
     * @return string|false cached datas
     */
    public function load($id, $doNotTestCacheValidity = false)
    {
        if ($this->test($id)) {
            return $this->_cache[$id];
        } else {
            return false;
        }
    }

    /**
     * Test if a cache is available or not (for the given id)
     *
     * @param  string $id cache id
     *
     * @return mixed false (a cache is not available) or "last modified"
     *               timestamp (int) of the available cache record
     */
    public function test($id)
    {
        return array_key_exists($id, $this->_cache);
    }

    /**
     * Save some string datas into a cache record
     *
     * Note : $data is always "string" (serialization is done by the
     * core not by the backend)
     *
     * @param  string $data             Datas to cache
     * @param  string $id               Cache id
     * @param  array  $tags             Array of strings, the cache record will
     *                                  be tagged by each string entry
     * @param  int    $specificLifetime If != false, set a specific lifetime
     *                                  for this cache record (null => infinite
     *                                  lifetime)
     *
     * @return boolean true if no problem
     */
    public function save($data, $id, $tags = array(), $specificLifetime = false)
    {
        $this->_cache[$id] = $data;
        return true;
    }

    /**
     * Remove a cache record
     *
     * @param  string $id cache id
     * @return boolean true if no problem
     */
    public function remove($id)
    {
        unset($this->_cache[$id]);
        return true;
    }

    /**
     * Clean some cache records
     *
     * Available modes are :
     * 'all' (default)  => remove all cache entries ($tags is not used)
     * 'old'            => remove too old cache entries ($tags is not used)
     * 'matchingTag'    => remove cache entries matching all given tags
     *                     ($tags can be an array of strings or a single string)
     * 'notMatchingTag' => remove cache entries not matching one of the given
     *                     tags
     *                     ($tags can be an array of strings or a single string)
     * 'matchingAnyTag' => remove cache entries matching any given tags
     *                     ($tags can be an array of strings or a single string)
     *
     * @param  string $mode clean mode
     * @param  tags array $tags array of tags
     * @return boolean true if no problem
     */
    public function clean(
        $mode = Zend_Cache::CLEANING_MODE_ALL, $tags = array()
    )
    {
        $this->_cache = array();
        return true;
    }

    /**
     * Return an array of stored cache ids
     *
     * @return array array of stored cache ids (string)
     */
    public function getIds()
    {
        return array_keys($this->_cache);
    }

    // @codeCoverageIgnoreStart
    // This backend declares (in its capabilities) that it doesn't support tags
    // So these cannot be called but must exist to implement the interface
    /**
     * Return an array of stored tags
     *
     * @return array array of stored tags (string)
     */
    public function getTags()
    {
        return array();
    }

    /**
     * Return an array of stored cache ids which match given tags
     *
     * In case of multiple tags, a logical AND is made between tags
     *
     * @param array $tags array of tags
     * @return array array of matching cache ids (string)
     */
    public function getIdsMatchingTags($tags = array())
    {
        return array();
    }

    /**
     * Return an array of stored cache ids which don't match given tags
     *
     * In case of multiple tags, a logical OR is made between tags
     *
     * @param array $tags array of tags
     * @return array array of not matching cache ids (string)
     */
    public function getIdsNotMatchingTags($tags = array())
    {
        return array();
    }

    /**
     * Return an array of stored cache ids which match any given tags
     *
     * In case of multiple tags, a logical AND is made between tags
     *
     * @param  array $tags array of tags
     * @return array array of any matching cache ids (string)
     */
    public function getIdsMatchingAnyTags($tags = array())
    {
        return array();
    }
    // @codeCoverageIgnoreEnd

    /**
     * Return the filling percentage of the backend storage
     *
     * @return int integer between 0 and 100
     * @throws Zend_Cache_Exception
     */
    public function getFillingPercentage()
    {
        return 0;
    }

    /**
     * Return an array of metadatas for the given cache id
     *
     * The array must include these keys :
     * - expire : the expire timestamp
     * - tags : a string array of tags
     * - mtime : timestamp of last modification time
     *
     * @param  string $id cache id
     * @return array array of metadatas (false if the cache id is not found)
     */
    public function getMetadatas($id)
    {
        return false;
    }

    /**
     * Give (if possible) an extra lifetime to the given cache id
     *
     * @param  string $id cache id
     * @param  int $extraLifetime
     * @return boolean true if ok
     */
    public function touch($id, $extraLifetime)
    {
        return false;
    }

    /**
     * Return an associative array of capabilities (booleans) of the backend
     *
     * The array must include these keys :
     * - automatic_cleaning (is automating cleaning necessary)
     * - tags (are tags supported)
     * - expired_read (is it possible to read expired cache records
     *                 (for doNotTestCacheValidity option for example))
     * - priority does the backend deal with priority when saving
     * - infinite_lifetime (is infinite lifetime can work with this backend)
     * - get_list (is it possible to get the list of cache ids and the complete
     *   list of tags)
     *
     * @return array associative of with capabilities
     */
    public function getCapabilities()
    {
        return array(
            'automatic_cleaning' => false,
            'tags'               => false,
            'expired_read'       => true,
            'priority'           => false,
            'infinite_lifetime'  => true,
            'get_list'           => false,
        );
    }

    /**
     * PUBLIC METHOD FOR UNIT TESTING ONLY !
     *
     * Force a cache record to expire
     *
     * @param string $id cache id
     */
    // @codeCoverageIgnoreStart
    public function ___expire($id)
    {
        return false;
    }
    // @codeCoverageIgnoreEnd
}
