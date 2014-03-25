<?php

const CONVERSATION_TOTAL = 5;
const CONVERSATION_WITH_ME = 3;

class ImTest extends WebTestCase {

    public $baseUrl = TEST_BASE_URL;

    public $fixtures = array(
        'conversation'=>'Conversation',
        'message'=>'Message',
        'messageConversation'=>'MessageConversation',
        'receiver'=>'Receiver',
    );

    protected function setUp() {
        parent::setUp();
    }

    // Pагрузка разговоров
    public function testConversations()
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
        $this->assertEquals(count($elements), CONVERSATION_WITH_ME);
        echo PHP_EOL.'conversations count:'.count($elements);

        $idConversation = $driver->executeScript('return im.conversations.last().getId();');
        $position = $driver->executeScript("
            var element = document.getElementById('conversation{$idConversation}');
            return $('.conversation').index(element);
        ");
        echo PHP_EOL.'position: '.$position;

        $helper = new ImTestHelper();

        $receivers = $helper->getRandomUsers();
        $receivers[] = Yii::app()->user->getId();
        $messages = array(
            array('body'=>$helper->getRandomText(), 'idUser'=>reset($receivers))
        );
        $idConversation = $helper->addConversation($receivers, $messages);
        $idElement = "conversation{$idConversation}";

        // проверяем, что число сообщений в базе соответствует
        $messagesTotal = Message::model()->conversation($idConversation)->count();
        echo PHP_EOL.'messages total:'.count($messagesTotal);

        $driver->wait(5,250)->until(
            WebDriverExpectedCondition::presenceOfAllElementsLocatedBy(
                WebDriverBy::id("conversation{$idConversation}")
            )
        );

        $elements = $driver->findElements(WebDriverBy::cssSelector('.conversation'));
        $this->assertEquals(count($elements), CONVERSATION_WITH_ME+1);
        echo PHP_EOL.'conversations count after add new conversation:'.count($elements);

        $idElementFound = $driver->executeScript("return $('.conversation').get(0).id");
        echo PHP_EOL.'first element is: #'.$idConversation;
        $this->assertEquals($idElement, $idElementFound);

        $modelMessagesTotal = $driver->executeScript("
            var conversation = im.conversations.get({$idConversation});
            return conversation != null ? conversation.messages.length : 0;
        ");
        echo PHP_EOL.'model messages total:'.$modelMessagesTotal;
        $this->assertEquals($messagesTotal, $modelMessagesTotal);

        $element = $driver->findElement(WebDriverBy::id($idElement));
        $driver->getMouse()->click($element->getCoordinates());


        $elements = null;
        foreach ($driver->findElements(WebDriverBy::cssSelector(".messenger-peer")) as $peer) {
            if ($peer->isDisplayed()) {
                $elements = $peer->findElements(WebDriverBy::cssSelector(".messenger-peer-message"));
                break;
            }
        }

        echo PHP_EOL.'elements messages total:'.count($elements);
        $this->assertEquals(count($elements), $modelMessagesTotal);

        return true;
    }


}
