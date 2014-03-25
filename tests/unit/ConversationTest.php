<?php
/**
 * description
 * User: terrasoff
 * Date: 3/20/14 3:08 PM
 */


const CONVERSATION_TOTAL = 5;

class ConversationTest extends CDbTestCase
{
    public $fixtures = array(
        'conversations'=>'Conversation',
        'receivers'=>'Receiver',
        'messages'=>'Message',
        'messagesConversation'=>'MessageConversation',
    );

    public function testNew()
    {
        $conversation = Conversation::model()->findByPk(1);
        $this->assertTrue($conversation !== null);

        $receivers1 = Receiver::model()->findAllByAttributes(array('idConversation'=>1));
        $receivers2 = Conversation::model()->findByPk(1)->getRelated('Receiver');
        $this->assertTrue(count($receivers1) === count($receivers2));
    }
}