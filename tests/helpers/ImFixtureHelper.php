<?php
/**
 * description
 * User: terrasoff
 * Date: 3/21/14 11:42 AM
 */

class ImFixtureHelper
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
        // все, кроме меня
        $list = User::model()->findAll(array(
            'limit'=>100,
            'condition'=>'idUser<>'.Yii::app()->user->getId()
        ));
        $receivers_list = array_map(function($model) {return $model->getId();},$list);

        // тексты для сообщений (строка - это одно сообщение)
        $text = preg_split('/\n/u',file_get_contents(__DIR__.'/../../commands/text'));
        foreach ($text as $i=>$t) {
            if ($t == '') // без пустых сообщений
                unset($text[$i]);
        }

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

            $totalReceivers = rand(1,count($receivers_list)-1);
            $currentReceivers = array_rand($receivers_list, $totalReceivers);

            print_r($receivers_list);

            foreach ($currentReceivers as $receiverIndex) {
                $receivers[] = array(
                    'idConversation'=>$idConversation,
                    'idUser'=>$receivers_list[$receiverIndex],
                );
            }
            // и себя добавляем в первые три разговора
            if ($idConversation<=CONVERSATION_WITH_ME)
                $receivers[] = array(
                    'idConversation'=>$idConversation,
                    'idUser'=>Yii::app()->user->getId(),
                );

            $totalMessages = rand(10,30);
            while (--$totalMessages > 0)
            {
                $index = $currentReceivers[array_rand($currentReceivers)];
                $idUser = $receivers_list[$index];
                $messages[] = array(
                    'idMessage'=>++$idMessage,
                    'body'=>$text[array_rand($text)],
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