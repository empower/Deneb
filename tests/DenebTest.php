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
        $cache = Zend_Cache::factory('Core', 'Mock');
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
}
