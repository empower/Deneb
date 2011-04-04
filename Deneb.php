<?php
/**
 * Deneb
 *
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
require_once 'Deneb/Exception.php';
require_once 'Deneb/Exception/NotFound.php';
require_once 'Deneb/DB/Selector.php';

/**
 * Implements methods and properties common to both
 * {@link Deneb_Object_Common} and {@link Deneb_Collection_Common}.  Also a
 * placeholder for {@link Zend_Cache} and {@link Zend_Log} instances if used.
 *
 * @category  Deneb
 * @package   Deneb
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2010 Empower Campaigns
 * @link      http://github.com/empower/deneb
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 */
abstract class Deneb
{
    /**
     * Instance of {@link Zend_Application}, providing access to the config and
     * log instances
     *
     * @see Zend_Application
     */
    static protected $_application = null;

    /**
     * Name of the base deneb exception to throw.  Use this if you don't
     * want to use Deneb_Exception.  (Useful if you are migrating to Deneb
     * after other exceptions have been used).
     *
     * @see setExceptionName()
     */
    static protected $_exceptionName = 'Deneb_Exception';

    /**
     * Name of the "not found" deneb exception to throw.  Use this if you don't
     * want to use Deneb_Exception_NotFound.  (Useful if you are migrating to
     * Deneb after other exceptions have been used).
     *
     * @see setExceptionName()
     */
    static protected $_exceptionNotFoundName = 'Deneb_Exception_NotFound';

    /**
     * Instance of {@link Zend_Log}
     *
     * @see getLog(), setLog()
     */
    static protected $_log = null;

    /**
     * Instance of {@link Zend_Cache}
     *
     * @see getCache(), setCache()
     */
    static protected $_cache = null;

    /**
     * Number of milliseconds of execution after which a SQL query should be
     *   logged as a slow query
     *
     * @var integer
     */
    static protected $_slowQueryThreshold = 500;

    /**
     * The read instance of {@link Zend_Db_Adapter}
     *
     * @var Zend_Db_Adapter
     */
    protected $_readDB = null;

    /**
     * The write instance of {@link Zend_Db_Adapter}
     *
     * @var Zend_Db_Adapter
     */
    protected $_writeDB = null;

    /**
     * The name of the selector to use
     *
     * @var string
     */
    protected $_selector = 'default';

    /**
     * The name of the table for use in SQL queries
     *
     * @var string
     */
    protected $_table = null;

    /**
     * The object name for use in exceptions
     *
     * @var string
     */
    protected $_object = null;

    /**
     * The query results
     *
     * @var array
     */
    protected $_results = array();

    /**
     * Instance of {@link Deneb_DB_Selector}
     *
     * @var Deneb_DB_Selector
     * @see getReadDB(), getWriteDB()
     */
    protected $_dbSelectorInstance = null;

    /**
     * Arguments passed to the constructor, used in later calls e.g. countAll()
     * in collections
     *
     * @var array
     */
    protected $_args = array();

    /**
     * Loads up the read and write {@link Zend_Db_Adapter} objects
     *
     * @return void
     */
    protected function _init()
    {
        $this->_dbSelectorInstance = $this->_createDBSelector();
    }

    // @codeCoverageIgnoreStart
    /**
     * Creates an instance of {@link Deneb_DB_Selector}.  Abstracted for testing.
     *
     * @return Deneb_DB_Selector
     */
    protected function _createDBSelector()
    {
        // Instantiate Zend_Db_Adapter instances for read and write
        return new Deneb_DB_Selector(self::getApplication(),
                                     $this->_selector);
    }
    // @codeCoverageIgnoreEnd

    /**
     * Determines the where clause to use based on the $args argument passed
     * to __construct(). array('id' => 1) would transform into
     *
     * WHERE id=1
     *
     * You can also use the form array('id' => array(1,2,3), which would
     * transform into
     *
     * WHERE id IN (1, 2, 3)
     *
     * @param array  $args The args passed in
     *
     * @return string
     */
    protected function _determineWhere(array $args)
    {
        $where = '';
        foreach ($args as $key => $value) {
            $key = '`' . $this->_table . '`.' . $key;

            if ($where != '') {
                $where .= ' AND';
            }
            if (is_array($value)) {
                if (count($value)) {
                    $quoted = array_map(
                        array($this, 'quoteArrayContents'), $value
                    );
                    $where .= ' ' . $key . ' IN (' . implode(',', $quoted) . ')';
                }
            } elseif ($value instanceof Zend_Db_Expr) {
                // Notice there's no assignment here - that's for use with
                // bitwise statements
                $where .= ' ' . $key . ' ' . $value;
            } else {
                $where .= ' ' . $key . '=' . $this->_getReadDB()->quote($value);
            }
        }

        if (strlen($where)) {
            $where = 'WHERE ' . $where;
        }

        return $where;
    }

    /**
     * Quotes the values in an array for use in an IN clause
     *
     * @param string $value The element value to quote
     *
     * @see array_map(), _determineWhere()
     * @return string
     */
    public function quoteArrayContents($value)
    {
        return $this->_getReadDB()->quote($value);
    }

    /**
     * Parses any limit, offset, order, group, and having options for the query
     *
     * @param array $options Options array
     *
     * @return string
     */
    protected function _determineOptions(array $options)
    {
        $this->getLog()->debug('options: ' . print_r($options, true));
        if (empty($options)) {
            return null;
        }

        $limits = '';

        if (isset($options['group'])) {
            $limits .= ' GROUP BY ' . $options['group'];
        }

        if (isset($options['having'])) {
            $limits .= ' HAVING ' . $options['having'];
        }

        if (isset($options['order'])) {
            $limits .= ' ORDER BY ' . $options['order'];
        }

        if (isset($options['limit'])) {
            $limits .= ' LIMIT ' . intval($options['limit']);
        }

        if (isset($options['offset'])) {
            $limits .= ' OFFSET ' . intval($options['offset']);
        }

        return $limits;
    }

    /**
     * Returns {@link Deneb::$_readDB}.  Abstracted for testing.
     *
     * @return Zend_Db_Adapter
     */
    protected function _getReadDB()
    {
        if ($this->_readDB === null) {
            $this->_readDB  = $this->_dbSelectorInstance->getReadInstance();
        }
        return $this->_readDB;
    }

    /**
     * Returns {@link Deneb::$_writeDB}.  Abstracted for testing.
     *
     * @return Zend_Db_Adapter
     */
    protected function _getWriteDB()
    {
        if ($this->_writeDB === null) {
            $this->_writeDB  = $this->_dbSelectorInstance->getWriteInstance();
        }
        return $this->_writeDB;
    }

    /**
     * Wrapper for DB adapter's fetchAll method to add logging
     *
     * @param string $sql
     * @param Zend_Db_Adapter $dbAdapter DB adapter to use, default readDB
     *
     * @return array DB rows
     */
    public function fetchAll($sql, $dbAdapter = null)
    {
        if ($dbAdapter === null) {
            $dbAdapter = $this->_getReadDB();
        }
        $startTime = microtime(true);
        try {
            $result = $dbAdapter->fetchAll($sql);
        } catch (Exception $e) {
            $this->getLog()->crit('Exception SQL query : ' . $sql);
            throw $e;
        }
        $endTime   = microtime(true);
        $ms        = (int)(($endTime - $startTime) * 1000);
        if (self::$_slowQueryThreshold && $ms >= self::$_slowQueryThreshold) {
            $this->getLog()->warn('SLOW SQL query (' . $ms . ' ms): ' . $sql);
        } else {
            $this->getLog()->debug('SQL query (' . $ms . ' ms): ' . $sql);
        }
        return $result;
    }

    /**
     * Wrapper for DB statement's fetchColumn method to add logging
     *
     * @param string $sql
     * @param integer $col Column number to retrieve, default 0
     * @param Zend_Db_Adapter $dbAdapter DB adapter to use, default readDB
     *
     * @return array DB rows
     */
    public function fetchColumn($sql, $col = 0, $dbAdapter = null)
    {
        if ($dbAdapter === null) {
            $dbAdapter = $this->_getReadDB();
        }
        $startTime = microtime(true);
        $result    = $dbAdapter->query($sql)->fetchColumn($col);
        $endTime   = microtime(true);
        $ms        = (int)(($endTime - $startTime) * 1000);
        if (self::$_slowQueryThreshold && $ms >= self::$_slowQueryThreshold) {
            $this->getLog()->warn('SLOW SQL query (' . $ms . ' ms): ' . $sql);
        } else {
            $this->getLog()->debug('SQL query (' . $ms . ' ms): ' . $sql);
        }
        return $result;
    }

    /**
     * Gets an instance of the logger
     *
     * @return Zend_Log
     */
     /**
     * Gets an instance of the logger
     *
     * @return Zend_Log
     */
    public function getLog()
    {
        if (self::$_log === null) {
            self::$_log = self::getApplication()->getBootstrap()
                                                ->getResource('Log');
        }
        return self::$_log;
    }

    /**
     * Sets a custom instance of {@link Zend_Log}
     *
     * @param Zend_Log $log The logger instance
     *
     * @return void
     */
    public static function setLog($log)
    {
        self::$_log = $log;
    }

    /**
     * Sets a local reference to your {@link Zend_Application}
     *
     * @param Zend_Application $application The Zend_Application instance
     *
     * @return void
     */
    static public function setApplication(Zend_Application $application)
    {
        self::$_application = $application;
    }

    /**
     * Returns the local reference to the {@link Zend_Application} instance
     *
     * @return Zend_Application
     */
    static public function getApplication()
    {
        return self::$_application;
    }

    /**
     * Sets the name of exception to use
     *
     * @param mixed $type base, db, notfound
     * @param mixed $name The class name to throw
     *
     * @return void
     * @throws Deneb_Exception on invalid type
     */
    static public function setExceptionName($type, $name)
    {
        switch ($type) {
            case 'base':
                self::$_exceptionName = $name;
                break;
            case 'notfound':
                self::$_exceptionNotFoundName = $name;
                break;
            default:
                throw new Deneb_Exception('Invalid exception type');
        }
    }

    /**
     * Sets an instance of {@link Zend_Cache} to use
     *
     * @param Zend_Cache $cache The Zend_Cache instance
     *
     * @return void
     */
    static public function setCache($cache)
    {
        self::$_cache = $cache;
    }

    /**
     * Gets the {@link Zend_Cache} instance
     *
     * @return Zend_Cache|null
     */
    public function getCache()
    {
        return self::$_cache;
    }

    /**
     * Set the number of milliseconds of execution after which a SQL query
     *   should be logged as a slow query, or zero to disable slow query
     *   loging
     *
     * @param integer $threshold Slow query threshold in milliseconds
     */
    static public function setSlowQueryThreshold($threshold)
    {
        self::$_slowQueryThreshold = (int)$threshold;
    }

    /**
     * Get the number of milliseconds of execution after which a SQL query
     *   should be logged as a slow query
     *
     * @return integer Slow query threshold in milliseconds
     */
    static public function getSlowQueryThreshold()
    {
        return self::$_slowQueryThreshold;
    }
}
