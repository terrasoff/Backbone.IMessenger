<?php
/**
 * description
 * User: terrasoff
 * Date: 3/21/14 11:42 AM
 */

class ImFixtureHelper extends ImTestHelper
{
    public $conversations = [];
    public $receivers = [];
    public $messages = [];
    public $messagesConversation = [];

    public static $instance = null;

    public static function get() {
        if (!self::$instance)
            self::$instance = new self();
        return self::$instance;
    }

    public function __construct()
    {
        $messages = [];
        $messagesConversation = [];
        $conversations = [];
        $receivers = [];

        $idMessage = 0;
        for ($idConversation=1; $idConversation<=CONVERSATION_TOTAL; $idConversation++)
        {
            $conversations[] = array(
                'idConversation'=>$idConversation,
                'title'=>'test conversation '.$idConversation,
                'appId'=>'givetonext',
                'type'=>1,
            );

            $receivers_list = $this->getRandomUsers();
            // и себя добавляем в первые три разговора
            if ($idConversation<=CONVERSATION_WITH_ME)
                $receivers_list[] = Yii::app()->user->getId();

            foreach ($receivers_list as $idReceiver)
                $receivers[] = array(
                    'idConversation'=>$idConversation,
                    'idUser'=>$idReceiver
                );

            $totalMessages = rand(10,30);
            while (--$totalMessages > 0)
            {
                $idUser = $receivers_list[array_rand($receivers_list)];
                $messages[] = array(
                    'idMessage'=>++$idMessage,
                    'body'=>$this->getRandomText(),
                    'idUser'=>$idUser,
                );
                $messagesConversation[] = array(
                    'idMessage'=>$idMessage,
                    'idConversation'=>$idConversation,
                );
            }
        }

        $this->conversations = $conversations;
        $this->messages = $messages;
        $this->messagesConversation = $messagesConversation;
        $this->receivers = $receivers;
    }
}