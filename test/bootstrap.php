<?php

error_reporting(E_ALL);
if (class_exists('PHPUnit_Runner_Version', true)) {
    $phpUnitVersion = PHPUnit_Runner_Version::id();
    if ('@package_version@' !== $phpUnitVersion && version_compare($phpUnitVersion, '4.0.0', '<')) {
        echo 'This version of PHPUnit (' . PHPUnit_Runner_Version::id() . ') is not supported.'
           . ' Supported is version 4.0.0 or higher.' . PHP_EOL;
        exit(1);
    }
    unset($phpUnitVersion);
}

/**
 * Start output buffering, if enabled
 */
if (defined('TESTS_AUTOWP_OB_ENABLED') && constant('TESTS_AUTOWP_OB_ENABLED')) {
    ob_start();
}

defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));

defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'testing'));



// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, [
    realpath(APPLICATION_PATH . '/../library'),
    './vendor/zendframework/zendframework1/library'
]));

require __DIR__ . '/../vendor/autoload.php';

require_once 'Zend/Application.php';

$application = new Zend_Application(
        APPLICATION_ENV,
        dirname(__FILE__) . '/_files/application.ini'
);
$application->bootstrap();

Zend_Session::$_unitTestEnabled = true;

// Locale
$locale = new Zend_Locale('en_GB');

// Translation
$translate = new Zend_Translate('Array', APPLICATION_PATH . '/languages', null, array(
    'scan'            => Zend_Translate::LOCALE_FILENAME,
    'disableNotices'  => true,
    'logUntranslated' => false,
    'locale'          => $locale,
));

$translate->addTranslation(array(
    'content' => APPLICATION_PATH . '/../vendor/zendframework/zendframework1/resources/languages/',
    'scan'    => Zend_Translate::LOCALE_DIRECTORY,
    'locale'  => $locale,
));
$translate->setLocale($locale);

// populate for wide-engine
Zend_Registry::set('Zend_Translate', $translate);
Zend_Registry::set('Zend_Locale', $locale);

$bootstrap = $application->getBootstrap();

$front = $bootstrap->getResource('FrontController');

$front->setParam('bootstrap', $bootstrap);

$request = new Zend_Controller_Request_Http('http://www.autowp.ru');
$front->setRequest($request);