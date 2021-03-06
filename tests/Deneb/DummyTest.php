<?php

require_once 'Deneb/TestCase.php';
require_once 'Deneb/Dummy.php';
require_once 'Deneb/DB/Selector.php';

class Deneb_DummyTest extends Deneb_TestCase
{
    protected $_objectName = 'Deneb_Dummy';

    public function testConstructorWithArguments()
    {
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

        $this->assertSame($id, $this->_object->id);
        $this->assertSame($username, $this->_object->username);
        $this->assertSame($email, $this->_object->email);
        $this->assertSame($date_created, $this->_object->date_created);
    }

    public function testConstructorWithNoArguments()
    {
        $this->_object->__construct();
        $contents = $this->_object->get();
        $this->assertTrue(empty($contents));
    }

    public function testConstructorWithArgumentsFail()
    {
        $this->setExpectedException('Deneb_Exception_NotFound');
        $this->_object->__construct(array('username' => 'shupp'));
    }

    public function testMagicGetNoMatch()
    {
        $this->_object->__construct();
        $this->assertNull($this->_object->foobar);
    }

    public function testMagicSetGet()
    {
        $this->_object->__construct();
        $this->assertNull($this->_object->foobar);
        $this->_object->foobar = 'barfoo';
        $this->assertSame('barfoo', $this->_object->foobar);
    }

    public function testMagicIssetUnset()
    {
        $this->_object->__construct();
        $this->assertFalse(isset($this->_object->foobar));
        $this->_object->foobar = 'barfoo';
        $this->assertTrue(isset($this->_object->foobar));
        unset($this->_object->foobar);
        $this->assertFalse(isset($this->_object->foobar));
        unset($this->_object->foobar);
        $this->assertFalse(isset($this->_object->foobar));
    }

    public function testSetGet()
    {
        $this->_object->__construct();
        $contents = $this->_object->get();
        $this->assertTrue(empty($contents));

        $newContents = array(
            'id' => 1,
            'username' => 'shupp',
            'email' => 'bshupp@empowercampaigns.com',
        );

        $this->_object->set($newContents);
        $this->assertSame($newContents, $this->_object->get());
    }

    public function testSetGetProtectedFields()
    {
        $contents = $this->_object->get();
        $this->assertTrue(empty($contents));

        $newContents = array(
            'id' => 1,
            'username' => 'shupp',
            'protected_field' => 'bshupp@empowercampaigns.com',
        );

        $this->_object->set($newContents);
        unset($newContents['protected_field']);
        $this->assertSame($newContents, $this->_object->get());

        $this->_object->set($newContents);
        $this->assertSame($newContents, $this->_object->get());
    }

    public function testCreate()
    {
        $id = 1;
        $username = 'shupp';
        $email = 'bshupp@empowercampaigns.com';

        $stmt1 = Zend_Test_DbStatement::createInsertStatement(1);
        $this->_connectionMock->appendLastInsertIdToStack($id);
        $stmt2 = Zend_Test_DbStatement::createSelectStatement(array(array('id' => $id, 'username' => $username, 'email' => $email)));
        $this->_connectionMock->appendStatementToStack($stmt2);
        $this->_connectionMock->appendStatementToStack($stmt1);

        $this->_object->__construct();
        $contents = $this->_object->get();
        $this->assertTrue(empty($contents));

        $this->_object->create(array('username' => $username, 'email' => $email));

        $this->assertSame($id, $this->_object->id);
        $this->assertSame($username, $this->_object->username);
        $this->assertSame($email, $this->_object->email);
    }

    public function testCreateFailPKAlreadySet()
    {
        $this->setExpectedException('Deneb_Exception');

        $data = array(
            'id' => 1,
            'username' => 'shupp',
        );

        $this->_object->__construct();
        $contents = $this->_object->get();
        $this->assertTrue(empty($contents));

        $this->_object->create($data);
    }

    public function testUpdate()
    {
        $data1 = array(
            'id' => 1,
            'username' => 'shupp',
            'email' => 'bshupp@empowercampaigns.com',
        );

        $data2 = array(
            'id' => 1,
            'username' => 'shupp2',
            'email' => 'bshupp@empowercampaigns.com',
        );

        $stmt1 = Zend_Test_DbStatement::createSelectStatement(array($data1));
        $stmt2 = Zend_Test_DbStatement::createUpdateStatement(1);
        $stmt3 = Zend_Test_DbStatement::createSelectStatement(array($data2));

        $this->_connectionMock->appendStatementToStack($stmt3);
        $this->_connectionMock->appendStatementToStack($stmt2);
        $this->_connectionMock->appendStatementToStack($stmt1);

        $this->_object->__construct(array('id' => 1));
        $this->assertSame($data1, $this->_object->get());

        $this->_object->set($data2);
        $this->_object->update();
        $this->assertSame($data2, $this->_object->get());
    }

    public function testUpdateFailNoPKSet()
    {
        $this->setExpectedException('Deneb_Exception');

        $data1 = array(
            'username' => 'shupp',
            'email' => 'bshupp@empowercampaigns.com',
        );

        $stmt1 = Zend_Test_DbStatement::createSelectStatement(array($data1));
        $this->_connectionMock->appendStatementToStack($stmt1);

        $this->_object->__construct();
        $contents = $this->_object->get();
        $this->assertTrue(empty($contents));

        $this->_object->username = 'shupp2';
        $this->_object->update();
    }

    public function testDelete()
    {
        $stmt1 = Zend_Test_DbStatement::createDeleteStatement(1);
        $this->_connectionMock->appendStatementToStack($stmt1);

        $this->_object->__construct();
        $contents = $this->_object->get();
        $this->assertTrue(empty($contents));

        $this->_object->delete(1);
    }

    public function testDeleteFailNoPKSet()
    {
        $this->setExpectedException('Deneb_Exception');

        $this->_object->__construct();
        $contents = $this->_object->get();
        $this->assertTrue(empty($contents));

        $this->_object->delete();
    }

    public function testGetPrimaryKey()
    {
        $this->_object->__construct();
        $this->assertSame('id', $this->_object->getPrimaryKey());
    }

    public function testSetExceptionType()
    {
        $this->setExpectedException('Deneb_Exception', 'Invalid exception type');

        // Set them to something else
        Deneb::setExceptionName('base', 'FoobarException');
        Deneb::setExceptionName('notfound', 'FoobarException');

        // Set them back
        Deneb::setExceptionName('base', 'Deneb_Exception');
        Deneb::setExceptionName('notfound', 'Deneb_Exception_NotFound');

        // Test setting failure
        Deneb::setExceptionName('foobar', 'FoobarException');
    }

    public function testSetLog()
    {
        $stream = fopen('/dev/null', 'a', false);
        $writer = new Zend_Log_Writer_Stream($stream);
        $logger = new Zend_Log($writer);

        Deneb::setLog($logger);
        $this->_object->__construct();
        $this->assertSame($logger, $this->_object->getLog());
    }

    public function testSetStatusHasStatusUnsetStatus()
    {
        $this->_object->__construct();
        $this->assertFalse($this->_object->hasStatus(Deneb_Dummy::STATUS_ONE));
        $this->_object->setStatus(Deneb_Dummy::STATUS_ONE);
        $this->assertTrue($this->_object->hasStatus(Deneb_Dummy::STATUS_ONE));
        $this->_object->setStatus(Deneb_Dummy::STATUS_THREE);
        $this->assertTrue($this->_object->hasStatus(Deneb_Dummy::STATUS_ONE));
        $this->assertTrue($this->_object->hasStatus(Deneb_Dummy::STATUS_THREE));
        $this->_object->unsetStatus(Deneb_Dummy::STATUS_ONE);
        $this->assertFalse($this->_object->hasStatus(Deneb_Dummy::STATUS_ONE));
        $this->assertTrue($this->_object->hasStatus(Deneb_Dummy::STATUS_THREE));
    }

    public function testSetStatusEnforceSingleStatus()
    {
        $this->_object->__construct();
        $this->_object->_enforceSingleStatus = true;
        $this->assertFalse($this->_object->hasStatus(Deneb_Dummy::STATUS_ONE));
        $this->_object->setStatus(Deneb_Dummy::STATUS_ONE);
        $this->assertTrue($this->_object->hasStatus(Deneb_Dummy::STATUS_ONE));
        $this->_object->setStatus(Deneb_Dummy::STATUS_THREE);
        $this->assertFalse($this->_object->hasStatus(Deneb_Dummy::STATUS_ONE));
        $this->assertTrue($this->_object->hasStatus(Deneb_Dummy::STATUS_THREE));
        $this->_object->unsetStatus(Deneb_Dummy::STATUS_THREE);
        $this->assertFalse($this->_object->hasStatus(Deneb_Dummy::STATUS_ONE));
        $this->assertFalse($this->_object->hasStatus(Deneb_Dummy::STATUS_THREE));
    }

    public function testSetStatusFailure()
    {
        $this->setExpectedException('Deneb_Exception');
        $this->_object->__construct();
        $this->_object->setStatus('foobar');
    }

    public function testUnsetStatusFailure()
    {
        $this->setExpectedException('Deneb_Exception');
        $this->_object->__construct();
        $this->_object->unsetStatus('foobar');
    }

    public function testSlowQueryLog()
    {
        Deneb::setSlowQueryThreshold(-1);
        $logger = $this->getMock('Zend_Log', array('warn', 'debug', 'info'));
        $logger->expects($this->once())
               ->method('warn')
               ->will($this->returnValue(null));
        $logger->expects($this->exactly(0))
               ->method('debug')
               ->will($this->returnValue(null));
        Deneb::setLog($logger);

        $results = array(array(
            'id' => 1,
            'username' => 'dcopeland',
        ));

        $stmt1 = Zend_Test_DbStatement::createSelectStatement($results);
        $this->_connectionMock->appendStatementToStack($stmt1);
        $this->_object->__construct();
        $this->assertSame(
            $results,
            $this->_object->fetchAll('SELECT * FROM users WHERE id = 1')
        );
    }

    public function testSlowQueryLogDisabled()
    {
        Deneb::setSlowQueryThreshold(0);
        $logger = $this->getMock('Zend_Log', array('warn', 'debug', 'info'));
        $logger->expects($this->exactly(0))
               ->method('warn')
               ->will($this->returnValue(null));
        $logger->expects($this->once())
               ->method('debug')
               ->will($this->returnValue(null));
        Deneb::setLog($logger);

        $results = array(array(
            'id' => 1,
            'username' => 'dcopeland',
        ));

        $stmt1 = Zend_Test_DbStatement::createSelectStatement($results);
        $this->_connectionMock->appendStatementToStack($stmt1);
        $this->_object->__construct();
        $this->assertSame(
            $results,
            $this->_object->fetchAll('SELECT * FROM users WHERE id = 1')
        );
    }

    public function testInitializeValuesException()
    {
        $this->_object->__construct();

        $newContents = array(
            'id' => 1,
            'username' => 'shupp',
            'email' => 'bshupp@empowercampaigns.com',
        );

        $this->_object->set($newContents);

        $this->setExpectedException(
            'Deneb_Exception',
            'Cannot call initializeValues() on already initialized object!'
        );
        $this->_object->initializeValues($newContents);
    }
}
