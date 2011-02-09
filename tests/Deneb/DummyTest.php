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
        $this->_connectionMock->appendStatementToStack($stmt1);
        $this->_connectionMock->appendStatementToStack($stmt2);

        $this->_object->__construct();
        $contents = $this->_object->get();
        $this->assertTrue(empty($contents));

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
}
