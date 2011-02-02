Deneb: A simple CRUD layer for Zend_Db based models
===================================================

Deneb provides a consistent CRUD interface to using Zend_Db based models and model collections, as well as segregated read and write DB pools and selectors.  Provided are common classes for single objects, as well as collections of objects.

Here's an example model, Deneb_User:

    <?php
    class Deneb_User extends Deneb_Object_Common
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
         * _enableDateCreated
         *
         * @var bool
         */
        protected $_enableDateCreated = true;
    }
    ?>


Single model read example:

    $user = new Deneb_User(array('username' => 'shupp'));


Single model write example:

    $user           = new Deneb_User();
    $user->username = 'shupp';
    $user->email    = 'bshupp@empowercampaigns.com';
    try {
        $user->create();
    } catch (Deneb_Exception $e) {
        echo "There was an error creating this object: " . $e->getMessage();
    }


Collection model example:

    try {
        $users = new Deneb_UserCollection(array('enabled' => 1));
        foreach ($users as $user) {
            echo "Username: $user->username\n";
        }
    } catch (Deneb_Exception_NotFound $e) {
        echo "Error: no users found";
    }


Transparent caching with Zend_Cache:

    $cache = Zend_Cache::factory('Core', 'Libmemcached');
    Deneb::setCache($cache);


Easy to attach logging with via Zend_Log:

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
