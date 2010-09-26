<?php

require_once 'PHPUnit/Framework.php';
require_once 'Deneb/DummyTest.php';
require_once 'Deneb/DummyCollectionTest.php';
require_once 'Deneb/DB/SelectorTest.php';

class Deneb_AllTests
{
    static public function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Deneb Unit Test Suite');
        $suite->addTestSuite('Deneb_DB_SelectorTest');
        $suite->addTestSuite('Deneb_DummyTest');
        $suite->addTestSuite('Deneb_DummyCollectionTest');
        return $suite;
    }
}
