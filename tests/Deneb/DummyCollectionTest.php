<?php

require_once 'Deneb/Dummy.php';
require_once 'Deneb/TestCase.php';

class Deneb_DummyCollectionTest extends Deneb_TestCase
{
    protected $_objectName = 'Deneb_DummyCollection';

    public function testConstructor()
    {
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

        $stmt = Zend_Test_DbStatement::createSelectStatement($rows);
        $this->_connectionMock->appendStatementToStack($stmt);

        $this->_object->__construct(array('status' => 0, 'id' => array(1, 2)), array('limit' => 2, 'offset' => 0, 'order' => 'id', 'group' => 'id', 'having' => 'id > 0'));
        $this->assertSame($this->_object->key(), 0);
        $this->assertTrue($this->_object->valid());
        $this->assertSame($this->_object->current()->id, 1);
        $this->_object->next();
        $this->assertSame($this->_object->current()->id, 2);
        $this->_object->rewind();
        $this->assertSame($this->_object->current()->id, 1);
        $this->_object->next();
        $this->_object->next();
        $this->assertFalse($this->_object->valid());
        $this->assertSame(array(1,2), $this->_object->getPrimaryKeys());
        $this->assertSame(2, count($this->_object));
    }

    public function testConstructorNoOptions()
    {
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

        $stmt = Zend_Test_DbStatement::createSelectStatement($rows);
        $this->_connectionMock->appendStatementToStack($stmt);

        $this->_object->__construct(array('status' => 0));
        $this->assertSame($this->_object->current()->id, 1);
        $this->_object->next();
        $this->assertSame($this->_object->current()->id, 2);
    }

    public function testConstructorFailNotFound()
    {
        $this->setExpectedException('Deneb_Exception_NotFound');
        $this->_object->__construct(array('status' => 0));
    }

    public function testGetByPrimaryKeySuccess()
    {
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

        $stmt = Zend_Test_DbStatement::createSelectStatement($rows);
        $this->_connectionMock->appendStatementToStack($stmt);
        $this->_object->__construct(array());
        $object = $this->_object->getByPrimaryKey(1);
        $this->assertInstanceOf('Deneb_Dummy', $object);
        $this->assertSame(1, $object->id);
    }

    public function testGetByPrimaryKeyFailNotFound()
    {
        $this->setExpectedException('Deneb_Exception_NotFound');
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

        $stmt = Zend_Test_DbStatement::createSelectStatement($rows);
        $this->_connectionMock->appendStatementToStack($stmt);
        $this->_object->__construct(array());
        $object = $this->_object->getByPrimaryKey(3);
    }

    public function testCountAll()
    {
        $rows = array(
            array(
                'id' => 2,
                'username' => 'dcopeland',
                'email' => 'dcopeland@empowercampaigns.com',
                'status' => 0
            ),
        );

        $stmt2 = Zend_Test_DbStatement::createSelectStatement(array(array('COUNT(*)' => 10)));
        $this->_connectionMock->appendStatementToStack($stmt2);
        $stmt = Zend_Test_DbStatement::createSelectStatement($rows);
        $this->_connectionMock->appendStatementToStack($stmt);

        $this->_object->__construct(array('status' => 0, 'id' => array(1, 2)), array('limit' => 1, 'offset' => 0, 'order' => 'id DESC'));
        $this->assertSame(1, count($this->_object));
        $this->assertTrue($this->_object->valid());
        $this->assertSame($this->_object->current()->id, 2);
        $this->assertSame(10, $this->_object->countAll());
    }

    public function testDelayedFetch()
    {
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

        $stmt = Zend_Test_DbStatement::createSelectStatement($rows);
        $this->_connectionMock->appendStatementToStack($stmt);

        $this->_object->__construct(array('status' => 0), array('fetch' => false));
        $this->assertSame(array(), $this->_object->getPrimaryKeys());
        $this->_object->fetch();
        $this->assertSame(array(1,2), $this->_object->getPrimaryKeys());
    }
}
