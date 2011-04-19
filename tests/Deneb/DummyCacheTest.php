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
        $newObject = $this->getMock(
            $this->_objectName,
            array('_createDBSelector'),
            array(),
            '',
            false
        );
        $newObject->__construct(array('username' => $username, 'id' => 1, 'email' => $email, 'status' => $status));

        $this->assertSame($id, $newObject->id);
        $this->assertSame($username, $newObject->username);
        $this->assertSame($email, $newObject->email);
        $this->assertSame($date_created, $newObject->date_created);

        $this->_object->invalidateCache();
        Deneb::setCache(null);
        unset($cache);
        $this->assertNull($this->_object->getCache());
    }

    public function testInvalidateAdditionalIndexes()
    {
        $cache = Zend_Cache::factory('Core', 'Mock');
        Deneb::setCache($cache);

        $id = 1;
        $username = 'shupp';
        $email = 'bshupp@empowercampaigns.com';
        $date_created = '2000-01-01 00:00:00';
        $newEmail = 'bshupp@example.com';

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

        //  Look up by old email address
        $newObject = $this->getMock(
            $this->_objectName,
            array('_createDBSelector'),
            array(),
            '',
            false
        );
/*        $db = $this->getMock('StdClass', array('update', 'quote'));
        $newObject->expects($this->once())
                  ->method('_getWriteDB')
                  ->will($this->returnValue($db));
        $newObject->expects($this->any())
                  ->method('_getReadDB')
                  ->will($this->returnValue($db));
*/        $newObject->__construct(array('email' => $email));
        $this->assertSame($id, $newObject->id);
        $this->assertSame($email, $newObject->email);

        //  Now update email and test cache updates
        $newObject->email = $newEmail;
        $newObject->updateCache();

        //  Look up by new email address - should work
        $newObject2 = $this->getMock(
            $this->_objectName,
            array('_createDBSelector'),
            array(),
            '',
            false
        );
        $newObject2->__construct(array('email' => $newEmail));
        $this->assertSame($id, $newObject->id);
        $this->assertSame($newEmail, $newObject->email);

        //  Look up by OLD email address - should FAIL
        $newObject3 = $this->getMock(
            $this->_objectName,
            array('_createDBSelector', '_loadFromDB'),
            array(),
            '',
            false
        );
        $newObject3->expects($this->once())
                   ->method('_loadFromDB')
                   ->will($this->throwException(new Deneb_Exception_NotFound()));
        $this->setExpectedException('Deneb_Exception_NotFound');
        $newObject3->__construct(array('email' => $email));
    }
}
