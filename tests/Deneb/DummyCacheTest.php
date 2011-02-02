<?php

require_once 'Deneb/TestCase.php';
require_once 'Deneb/DummyCache.php';
require_once 'Deneb/DB/Selector.php';

class Deneb_DummyCacheTest extends Deneb_TestCase
{
    protected $_objectName = 'Deneb_DummyCache';

    public function testConstructorWithArgumentsAndCache()
    {
        $cache = Zend_Cache::factory('Core', 'Mock');
        Deneb::setCache($cache);

        $id = 1;
        $username = 'shupp';
        $email = 'bshupp@empowercampaigns.com';
        $date_created = '2000-01-01 00:00:00';

        $results = array(array(
            'id' => $id,
            'username' => $username,
            'type' => 1,
            'email' => $email,
            'date_created' => $date_created,
        ));

        $stmt1 = Zend_Test_DbStatement::createSelectStatement($results);
        $this->_connectionMock->appendStatementToStack($stmt1);
        $status = new Zend_Db_Expr('& 1');
        $this->_object->__construct(array('username' => $username, 'email' => $email, 'status' => $status));
        // Run again with PK
        $this->_object->__construct(array('username' => $username, 'id' => 1, 'email' => $email, 'status' => $status));

        $this->assertSame($id, $this->_object->id);
        $this->assertSame($username, $this->_object->username);
        $this->assertSame($email, $this->_object->email);
        $this->assertSame($date_created, $this->_object->date_created);

        $this->_object->invalidateCache();
        Deneb::setCache(null);
        unset($cache);
        $this->assertNull($this->_object->getCache());
    }
}
