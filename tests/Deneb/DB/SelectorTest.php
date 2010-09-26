<?php

require_once 'Deneb/DB/Selector.php';
require_once 'PHPUnit/Framework.php';
require_once 'Zend/Test/DbAdapter.php';
require_once 'Zend/Application.php';

class Deneb_DB_SelectorTest extends PHPUnit_Framework_TestCase
{
    protected $app = null;
    protected $readDB = null;
    protected $writeDB = null;

    public function setUp()
    {
        $config = array();
        $config['selectors'] = array();
        $config['selectors']['user'] = 'default';
        $config['selectors']['no_pools'] = 'no_pools';
        $config['pools']['default'] = array();
        $config['pools']['default']['read'] = array();
        $config['pools']['default']['read']['host'] = 'foo';
        $config['pools']['default']['write'] = array();
        $config['pools']['default']['write']['host'] = 'bar';

        $log = array();
        $log['writerName'] = "Mock";

        $options = array();
        $options['db'] = $config;
        $options['resources']['log']['mock'] = $log;

        $this->app = new Zend_Application('testing');
        $this->app->bootstrap();
        $this->app->setOptions($options);

        $this->readDB  = new Zend_Test_DbAdapter();
        $this->writeDB = new Zend_Test_DbAdapter();
    }

    public function tearDown()
    {
        $this->app = null;
    }

    public function testGetReadAndWriteInstances()
    {
        $mock = $this->getMock('Deneb_DB_Selector',
                               array('_createInstance'),
                               array($this->app, 'user'));

        $mock->expects($this->any())
             ->method('_createInstance')
             ->will($this->returnCallback(array($this, 'createInstanceCallback')));

        $read = $mock->getReadInstance();
        $read2 = $mock->getReadInstance();

        $write = $mock->getWriteInstance();
        $write2 = $mock->getWriteInstance();

        $this->assertNotSame($read, $write);
        $this->assertSame($read, $read2);
        $this->assertSame($write, $write2);
    }

    public function testConstructorFail()
    {
        $this->setExpectedException('Deneb_DB_Exception',
                                    'Selector not defined: bad_selector');
        $mock = $this->getMock('Deneb_DB_Selector',
                               array('_createInstance'),
                               array($this->app, 'bad_selector'));

        $mock->expects($this->any())
             ->method('_createInstance')
             ->will($this->returnCallback(array($this, 'createInstanceCallback')));

        $read = $mock->getReadInstance();
    }

    public function testGetInstanceFail()
    {
        $this->setExpectedException('Deneb_DB_Exception');
        $mock = $this->getMock('Deneb_DB_Selector',
                               array('_createInstance'),
                               array($this->app, 'no_pools'));

        $mock->expects($this->any())
             ->method('_createInstance')
             ->will($this->returnCallback(array($this, 'createInstanceCallback')));

        $read = $mock->getReadInstance();
    }

    public function createInstanceCallback($type)
    {
        if ($type == 'read') {
            return $this->readDB;
        }
        if ($type == 'write') {
            return $this->writeDB;
        }
    }
}
