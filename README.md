Deneb: A simple CRUD layer for Zend_Db based models
===================================================

Deneb provides a consistent CRUD interface to using Zend_Db based models and model collections, as well as segregated read and write DB pools and selectors.  Provided are common classes for single objects, as well as collections of objects.  Other features include attaching Zend_Log and Zend_Cache interfaces.

Here's an example model, Deneb_User:

    <?php
    class Model_User extends Deneb_Object_Common
    {
        /**
         * The name of the object for use in exception messages
         *
         * @var string
         */
        protected $_name = 'user';

        /**
         * The table to use in the DB
         *
         * @var string
         */
        protected $_table = 'users';

        /**
         * The DB selector
         *
         * @var string
         */
        protected $_selector = 'default';

        /**
         * Whether or not to insert NOW() in a date_created field on INSERT
         *
         * @var bool
         */
        protected $_enableDateCreated = true;

        /**
         * Whether caching should be enabled for this model.
         * Enabled by default.
         * 
         * @var bool
         */
        protected $_cacheEnabled = true;
    }
    ?>


And the relevant schema for that Model:

    CREATE TABLE `users` (
      `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      `status` TINYINT UNSIGNED NOT NULL default 0,
      `first_name` VARCHAR(255) NOT NULL,
      `last_name` VARCHAR(255) NOT NULL,
      `username` VARCHAR(15) NOT NULL,
      `password` VARCHAR(255) NOT NULL,
      `email` VARCHAR(100) NOT NULL,
      `date_created` DATETIME NOT NULL,
      `last_modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY  (`id`),
      UNIQUE KEY `idx_username` (`username`),
      UNIQUE KEY `idx_email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;


Single model read example:

    $user = new Model_User(array('username' => 'shupp'));


Single model write example:

    $user           = new Model_User();
    $user->username = 'shupp';
    $user->email    = 'bshupp@empowercampaigns.com';
    try {
        $user->create();
    } catch (Deneb_Exception $e) {
        echo "There was an error creating this object: " . $e->getMessage();
    }


Here's an example model collection:

    <?php
    class Model_UserCollection extends Deneb_Collection_Common
    {
        /**
         * The name of the singular object
         * 
         * @var string
         */
        protected $_object = 'Model_User';

        /**
         * The name of the object for use in exception messages
         * 
         * @var string
         */
        protected $_name = 'user';

        /**
         * The table to use in the DB
         * 
         * @var string
         */
        protected $_table = 'users';

        /**
         * The DB selector
         * 
         * @var string
         */
        protected $_selector = 'default';
    }
    ?>


Collection model example:

    try {
        $users = new Model_UserCollection(array('status' => 1));
        foreach ($users as $user) {
            echo "Username: $user->username\n";
        }
    } catch (Deneb_Exception_NotFound $e) {
        echo "Error: no users found";
    }


Transparent caching with Zend_Cache:

    $cache = Zend_Cache::factory('Core', 'Libmemcached');
    Deneb::setCache($cache);


Easy to attach an instance of Zend_Log:

    $writer = new Zend_Log_Writer_Stream('/tmp/deneb.log');
    $log    = new Zend_Log($writer);
    Deneb::setLog($log);


DB selector config example:

    db.selectors.user = "default"
    db.adapter = 'PDO_MYSQL'
    db.pools.default.write.username = "app_write"
    db.pools.default.write.password = "secret"
    db.pools.default.write.host = "1.2.3.4"
    db.pools.default.write.port = "3306"
    db.pools.default.write.dbname = "production"
    db.pools.default.read.username = "app_read"
    db.pools.default.read.password = "secret"
    db.pools.default.read.host = "5.6.7.8"
    db.pools.default.read.port = "3306"
    db.pools.default.read.dbname = "production"


And here's example direct usage, if you need it:

     $application = new Zend_Application(
         APPLICATION_ENV,
         APPLICATION_PATH . '/configs/application.ini'
     );
     $application->bootstrap();

     $selector = new Deneb_DB_Selector($application, 'user');
     $readDB   = $selector->getReadInstance();
     $writeDB  = $selector->getWriteInstance();
