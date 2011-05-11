<?php

require_once 'Deneb.php';
require_once 'Deneb/DummyCache.php';
require_once 'Deneb/DB/Selector.php';
require_once 'Deneb/TestCase.php';

class DenebTest extends Deneb_TestCase
{
    protected $_objectName = 'Deneb_DummyCache';

    public function testSetCache()
    {
        $cache = Zend_Cache::factory('Core', 'EC_Cache_Backend_Mock', array(), array(), false, true);
        Deneb::setCache($cache);
        $this->assertSame($cache, $this->_object->getCache());
        Deneb::setCache(null);
        $this->assertNull($this->_object->getCache());
    }

    public function testSetSlowQueryThreshold()
    {
        $default = Deneb::getSlowQueryThreshold();
        Deneb::setSlowQueryThreshold(100);
        $this->assertSame(100, Deneb::getSlowQueryThreshold());
        Deneb::setSlowQueryThreshold($default);
        $this->assertSame($default, Deneb::getSlowQueryThreshold());
    }

    public function testFetchAllException()
    {
        $this->setExpectedException('Deneb_Exception');

        $adapter = $this->getMock('Zend_Db_Adapter_Pdo_Mysql', array('fetchAll'), array(), '', false);
        $adapter->expects($this->once())
                ->method('fetchAll')
                ->will($this->throwException(new Deneb_Exception()));

        $logger = $this->getMock('Zend_Log', array('crit'), array(), '', false);
        $logger->expects($this->once())
               ->method('crit')
               ->will($this->returnValue(null));
        Deneb::setLog($logger);

        $this->_object->fetchAll('SELECT * from foo', $adapter);
    }
}
