<?php

define('TEST_BASE_URL','http://givetonext/');
define('WEBDRIVER_HOST','http://localhost:4444/wd/hub');
require_once(Yii::getPathOfAlias('vendor').'/facebook/webdriver/lib/__init__.php');

/**
 * The base class for functional test cases.
 * In this class, we set the base URL for the test application.
 * We also provide some common methods to be used by concrete test classes.
 */
class WebTestCase extends EWebTestCase
{
    public $driver;

	protected function setUp()
	{
		parent::setUp();

        $host = WEBDRIVER_HOST;
        $capabilities = array(WebDriverCapabilityType::BROWSER_NAME => 'firefox');
        $this->driver = RemoteWebDriver::create($host, $capabilities, 5000);
	}
}
