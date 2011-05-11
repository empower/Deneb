<?php

require_once 'PHPUnit/Framework/TestCase.php';
require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'Deneb/DummyTest.php';
require_once 'Deneb/DummyCacheTest.php';
require_once 'Deneb/DummyCollectionTest.php';
require_once 'Deneb/DummyCacheCollectionTest.php';
require_once 'Deneb/DummyCacheMultiCollectionTest.php';
require_once 'Deneb/DB/SelectorTest.php';
require_once 'DenebTest.php';

class Deneb_AllTests
{
    static public function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Deneb Unit Test Suite');
        $suite->addTestSuite('Deneb_DB_SelectorTest');
        $suite->addTestSuite('Deneb_DummyTest');
        $suite->addTestSuite('Deneb_DummyCollectionTest');
        $suite->addTestSuite('Deneb_DummyCacheTest');
        $suite->addTestSuite('Deneb_DummyCacheCollectionTest');
        $suite->addTestSuite('Deneb_DummyCacheMultiCollectionTest');
        $suite->addTestSuite('DenebTest');
        return $suite;
    }
}
