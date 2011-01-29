<?php

require_once('PEAR/PackageFileManager2.php');

PEAR::setErrorHandling(PEAR_ERROR_DIE);

$packagexml = new PEAR_PackageFileManager2;

$packagexml->setOptions(array(
    'baseinstalldir'    => '/',
    'simpleoutput'      => true,
    'packagedirectory'  => './',
    'filelistgenerator' => 'file',
    'ignore'            => array(
        'runTests.php',
        'generatePackage.php',
        'makedoc.sh',
        'phpdoc',
        'phpunit.xml',
        'phpunit-bootstrap.php',
        'coverage'
    ),
    'dir_roles' => array(
        'tests'     => 'test',
        'examples'  => 'doc'
    ),
));

$packagexml->setPackage('Deneb');
$packagexml->setSummary('Simple CRUD layer for DB based models');
$packagexml->setDescription(
    'Deneb provides a consistent CRUD interface to using Zend_Db base models and model collections, as well as segregated read and write DB pools and selectors'
);

$packagexml->setChannel('shupp.github.com/pirum');
$packagexml->setAPIVersion('0.4.0');
$packagexml->setReleaseVersion('0.4.0');

$packagexml->setReleaseStability('alpha');

$packagexml->setAPIStability('alpha');

$packagexml->setNotes('
* Added support for creating a record with the primary key value supplied
* After creation, all values are updated in the current object
* Updated tests to work with PHPUnit 3.5
');
$packagexml->setPackageType('php');
$packagexml->addRelease();

$packagexml->detectDependencies();

$packagexml->addMaintainer('lead',
                           'shupp',
                           'Bill Shupp',
                           'hostmaster@shupp.org');

$packagexml->setLicense('New BSD License',
                        'http://www.opensource.org/licenses/bsd-license.php');

$packagexml->setPhpDep('5.0.0');
$packagexml->setPearinstallerDep('1.4.0b1');
$packagexml->addPackageDepWithChannel('required', 'Zend', 'zend.googlecode.com/svn', '1.10.7');

$packagexml->generateContents();
$packagexml->writePackageFile();

?>
