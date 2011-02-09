<?php

require_once 'Deneb/Dummy.php';
require_once 'Deneb/TestCase.php';

class Deneb_DummyCacheCollectionTest extends Deneb_TestCase
{
    protected $_objectName = 'Deneb_DummyCacheCollection';

    public function testConstructor()
    {
        $cache = Zend_Cache::factory('Core', 'Mock');
        Deneb::setCache($cache);

        $ids = array(
            array(
                'id' => 1,
            ),
            array(
                'id' => 2,
            ),
            array(
                'id' => 3,
            ),
        );

        $dummyCache = new Deneb_DummyCache();
        $dummyCache->set(array(
                'id' => 3,
                'username' => 'user1',
                'email' => 'user1@empowercampaigns.com',
                'status' => 0
            )
        );

        $dummyCache->updateCache();

        $rows = array(
            array(
                'id' => 1,
                'username' => 'shupp',
                'email' => 'bshupp@empowercampaigns.com',
                'status' => 0
            ),
            array(
                'id' => 2,
                'username' => 'dcopeland',
                'email' => 'dcopeland@empowercampaigns.com',
                'status' => 0
            ),
        );

        $stmt2 = Zend_Test_DbStatement::createSelectStatement($rows);
        $this->_connectionMock->appendStatementToStack($stmt2);

        $stmt1 = Zend_Test_DbStatement::createSelectStatement($ids);
        $this->_connectionMock->appendStatementToStack($stmt1);

        $this->_object->__construct(array('status' => 0, 'id' => array(1, 2)), array('limit' => 2, 'offset' => 0, 'order' => 'id', 'group' => 'id', 'having' => 'id > 0'));
        $this->assertSame($this->_object->key(), 0);
        $this->assertTrue($this->_object->valid());
        $this->assertSame($this->_object->current()->id, 1);
        $this->_object->next();
        $this->assertSame($this->_object->current()->id, 2);
        $this->_object->next();
        $this->assertSame($this->_object->current()->id, 3);
        $this->_object->rewind();
        $this->assertSame($this->_object->current()->id, 1);
        $this->_object->next();
        $this->_object->next();
        $this->_object->next();
        $this->assertFalse($this->_object->valid());
        $this->assertSame(array(1,2,3), $this->_object->getPrimaryKeys());
        $this->assertSame(3, count($this->_object));

        Deneb::setCache(null);
        unset($cache);
        $this->assertNull($this->_object->getCache());
    }

    public function testConstructorNoResults()
    {
        $this->setExpectedException('Deneb_Exception_NotFound');

        $cache = Zend_Cache::factory('Core', 'Mock');
        Deneb::setCache($cache);

        $stmt1 = Zend_Test_DbStatement::createSelectStatement(array());
        $this->_connectionMock->appendStatementToStack($stmt1);

        $this->_object->__construct(array('status' => 0, 'id' => array(1, 2)), array('limit' => 2, 'offset' => 0, 'order' => 'id', 'group' => 'id', 'having' => 'id > 0'));
    }


    public function testConstructorResultsWentAway()
    {
        $this->setExpectedException('Deneb_Exception_NotFound');

        $cache = Zend_Cache::factory('Core', 'Mock');
        Deneb::setCache($cache);

        $stmt1 = Zend_Test_DbStatement::createSelectStatement(array('id' => 1));
        $this->_connectionMock->appendStatementToStack($stmt1);

        $this->_object->__construct(array('status' => 0, 'id' => array(1, 2)), array('limit' => 2, 'offset' => 0, 'order' => 'id', 'group' => 'id', 'having' => 'id > 0'));

        Deneb::setCache(null);
        unset($cache);
        $this->assertNull($this->_object->getCache());
    }

    public function testConstructorWithArrayOfPKs()
    {
        $cache = Zend_Cache::factory('Core', 'Mock');
        Deneb::setCache($cache);

        $dummyCache = new Deneb_DummyCache();
        $dummyCache->set(array(
                'id' => 1,
                'username' => 'user1',
                'email' => 'user1@empowercampaigns.com',
                'status' => 0
            )
        );
        $dummyCache->updateCache();

        $dummyCache2 = new Deneb_DummyCache();
        $dummyCache2->set(array(
                'id' => 2,
                'username' => 'user2',
                'email' => 'user2@empowercampaigns.com',
                'status' => 0
            )
        );
        $dummyCache2->updateCache();

        $this->_object->__construct(array('id' => array(1,2)));
        $this->assertSame(2, count($this->_object));

        $this->assertSame(1, $this->_object->current()->id);
        $this->_object->next();
        $this->assertSame(2, $this->_object->current()->id);
        $this->_object->next();
        $this->assertFalse($this->_object->valid());
    }

    public function tearDown()
    {
        parent::tearDown();
        Deneb::setCache(null);
    }
}
