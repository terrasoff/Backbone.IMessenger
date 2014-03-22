<?php
/**
 * launch webdriver:
 * java -jar selenium-server-standalone-2.40.0.jar -hub
 */

define('TEST_BASE_URL','http://givetonext/');
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
        $capabilities = array(WebDriverCapabilityType::BROWSER_NAME => 'firefox');
        $this->driver = RemoteWebDriver::create('localhost', $capabilities, 1000);
	}
}
