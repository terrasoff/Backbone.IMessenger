<?php

const CONVERSATION_TOTAL = 5;
const CONVERSATION_WITH_ME = 3;

class ImTest extends WebTestCase {

    public $baseUrl = TEST_BASE_URL;

//    public $fixtures = array(
//        'conversation'=>'Conversation',
//        'message'=>'Message',
//        'messageConversation'=>'MessageConversation',
//        'receiver'=>'Receiver',
//    );

    public function setUp() {
        parent::setUp(WEBDRIVER_HOST, 4444, 'firefox' );
    }

    public function testGoogle()
    {
        /** @var $driver RemoteWebDriver */
        $driver = $this->driver;

        $url = Yii::app()->createUrl('site/im');
        $driver->get($url);

        $driver->wait(5,250)->until(
            WebDriverExpectedCondition::presenceOfAllElementsLocatedBy(
                WebDriverBy::cssSelector('.conversation')
            )
        );

        $elements = $driver->findElements(WebDriverBy::cssSelector('.conversation'));
        $this->assertEqual(count($elements), CONVERSATION_WITH_ME);

        $sScriptResult = $driver->executeScript(array(
            'script' => 'alert(123);',
            'args' => array(),
        ));

        print_r($sScriptResult);

        $driver->quit();
    }
}
