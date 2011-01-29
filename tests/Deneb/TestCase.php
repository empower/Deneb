<?php

require_once 'PHPUnit/Framework/TestCase.php';
require_once 'Deneb/DB/Selector.php';
require_once 'Zend/Test/DbAdapter.php';
require_once 'Zend/Application.php';

class Deneb_TestCase extends PHPUnit_Framework_TestCase
{
    protected $_objectName = '';
    protected $_connectionMock = '';
    protected $_object = null;
    protected $_application = null;
    protected $_dbSelector = null;

    public function setUp()
    {
        parent::setUp();
        require_once str_replace('_', '/', $this->_objectName) . '.php';

        $this->_connectionMock = new Zend_Test_DbAdapter();

        $config = array();
        $config['selectors'] = array();
        $config['selectors']['default'] = 'default';
        $config['pools']['default'] = array();
        $config['pools']['default']['read'] = array();
        $config['pools']['default']['read']['host'] = 'foo';
        $config['pools']['default']['write'] = array();
        $config['pools']['default']['write']['host'] = 'bar';

        $log = array();
        $log['writerName'] = "Mock";
        $log['filterName'] = "Priority";
        $log['filterParams']['priority'] = 4;

        $options = array();
        $options['db'] = $config;
        $options['resources']['log']['mock'] = $log;

        $this->_application = new Zend_Application('testing');
        $this->_application->setOptions($options);
        $this->_application->bootstrap();
        Deneb::setApplication($this->_application);

        $this->_dbSelector = $this->getMock(
            'Deneb_DB_Selector',
            array('getInstance'),
            array($this->_application, 'default')
        );
        $this->_dbSelector->expects($this->any())
                          ->method('getInstance')
                          ->will($this->returnValue($this->_connectionMock));

        $this->_object = $this->getMock(
            $this->_objectName,
            array('_createDBSelector'),
            array(),
            '',
            false
        );

        $this->_object->expects($this->any())
                      ->method('_createDBSelector')
                      ->will($this->returnValue($this->_dbSelector));
    }

    public function getDenebMock($modelName, $application = null)
    {
        $conn = new Zend_Test_DbAdapter();

        if ($application === null) {
            $application = $this->_application;
        }

        $selector = $this->getMock(
            'Deneb_DB_Selector',
            array('getInstance'),
            array($application, 'default')
        );

        $selector->expects($this->any())
                 ->method('getInstance')
                 ->will($this->returnValue($conn));

        $modelMock = $this->getMock(
            $modelName,
            array('_createDBSelector'),
            array(),
            '',
            false
        );

        $modelMock->expects($this->any())
                  ->method('_createDBSelector')
                  ->will($this->returnValue($selector));

        return array($modelMock, $conn);
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->_connectionMock = null;
        $this->_object = null;
        $this->_application = null;
        $this->_dbSelector = null;
    }
}
