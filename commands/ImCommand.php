<?php

class ImCommand extends CConsoleCommand
{
    /**
     * база с сообщениями
     * @var CComponent
     */
    public $db_im;

    // влалделец заводов, газет, пароходов'
    public $user = array(
        'id'=>1,
    );

    /**
     * используемые sql команды
     * @var array
     */
    public $commands;

    /**
     * Тексты для сообщений
     * array
     * @var
     */
    public $text;

    public function beforeAction($action,$params){
        $this->db_im = Yii::app()->db_im;
        $this->commands = array(
            'getConversation'=>$this->db_im->createCommand('SELECT * FROM `Conversation` ORDER BY idConversation LIMIT 1'),
            'conversation'=>$this->db_im->createCommand('INSERT INTO `Conversation` (`type`,`appId`) VALUES (:type,:appId);'),
            'receivers'=>$this->db_im->createCommand('INSERT INTO `Receiver` (`idConversation`,`idUser`) VALUES (:idConversation,:idUser);'),
            'Message'=>$this->db_im->createCommand('INSERT INTO `Message` (`body`,`idUser`,`ts`,`replyIdMessage`, `toUserId`) VALUES (:body,:idUser,:ts,:replyIdMessage,:toUserId);'),
            'MessageConversation'=>$this->db_im->createCommand('INSERT INTO `MessageConversation` (`idMessage`,`idConversation`) VALUES (:idMessage,:idConversation);'),
            'ObjectConversation'=>$this->db_im->createCommand('INSERT INTO `ObjectConversation` (`idObject`,`idConversation`) VALUES (:idObject,:idConversation);'),
            'getUnread'=>$this->db_im->createCommand('SELECT messages.idMessage FROM `Conversation` `t`
LEFT OUTER JOIN `Receiver` `my` ON (`my`.`idConversation`=`t`.`idConversation`)
LEFT OUTER JOIN `MessageConversation` `messages_messages` ON (`t`.`idConversation`=`messages_messages`.`idConversation`)
LEFT OUTER JOIN `Message` `messages` ON (`messages`.`idMessage`=`messages_messages`.`idMessage`)
LEFT OUTER JOIN `ReadMessage` `read` ON (`read`.`idMessage`=`messages`.`idMessage`)
LEFT OUTER JOIN `ObjectConversation` `objects` ON (`objects`.`idConversation`=`t`.`idConversation`)
WHERE appId = \'domva\' AND read.idMessage IS NULL
AND my.idUser = :idUser
AND t.idConversation = :idConversation'),
            'getRead'=>$this->db_im->createCommand('SELECT messages.idMessage FROM `Conversation` `t`
LEFT OUTER JOIN `Receiver` `my` ON (`my`.`idConversation`=`t`.`idConversation`)
LEFT OUTER JOIN `MessageConversation` `messages_messages` ON (`t`.`idConversation`=`messages_messages`.`idConversation`)
LEFT OUTER JOIN `Message` `messages` ON (`messages`.`idMessage`=`messages_messages`.`idMessage`)
LEFT OUTER JOIN `ReadMessage` `read` ON (`read`.`idMessage`=`messages`.`idMessage`)
LEFT OUTER JOIN `ObjectConversation` `objects` ON (`objects`.`idConversation`=`t`.`idConversation`)
WHERE appId = \'domva\' AND read.idMessage IS NOT NULL
AND my.idUser = :idUser
AND t.idConversation = :idConversation'),
            'read'=>$this->db_im->createCommand('INSERT INTO `ReadMessage` (idMessage,idUser) VALUES (:idMessage,:idUser);'),
            'unread'=>$this->db_im->createCommand('DELETE FROM `ReadMessage` WHERE idMessage=:idMessage AND idUser=:idUser;'),
        );

        // тексты для сообщений (строка - это одно сообщение)
        $this->text = preg_split('/\n/u',file_get_contents(dirname(__FILE__).'/text'));
        foreach ($this->text as $i=>$t) {
            if ($t == '') // без пустых сообщений
                unset($this->text[$i]);
        }

        return parent::beforeAction($action,$params);
    }

    /**
     * Новое сообщение в разговоре
     */
    public function actionSend(){
        $idConversation = 1348;
        $from = 24;
        $to = 31;
        $transaction= $this->db_im->beginTransaction();
        try
        {
            $text = $this->text[array_rand($this->text)];
            $id = $this->sendMessage($idConversation,$from,$text,null,null,$to);
            echo 'send message ('.$id.') to conversation ('.$idConversation.')'.
                PHP_EOL.$from.' -> '.$to.
                PHP_EOL.'message: '.$text;
            $transaction->commit();
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
            $transaction->rollback();
        }
    }

    // читаем сообщения юзера из разговора
    public function actionRead($flag = 1){
        $idConversation = 714;
        $idUser = 24;
        $random = true;
        $flag = (bool)$flag;

        $transaction= $this->db_im->beginTransaction();
        try
        {
            // выбираем сообщения
            echo $action = $flag ? 'getRead' : 'getUnread';
            $messages = $this->commands[$action]
                ->bindParam(":idUser",$idUser,PDO::PARAM_INT)
                ->bindParam(":idConversation",$idConversation,PDO::PARAM_INT)
                ->query()
                ->readAll();

            echo 'total: '.count($messages).PHP_EOL;
            echo $flag
                ? 'reading...'
                : 'unreading...';

            foreach ($messages as $m) {
                if (!empty($random) && rand(0,10)>2) continue;

                $action = $flag ? 'unread' : 'read';
                $messages = $this->commands[$action]
                    ->bindParam(":idMessage",$m['idMessage'],PDO::PARAM_INT)
                    ->bindParam(":idUser",$idUser,PDO::PARAM_INT)
                    ->execute();

                echo $m['idMessage'].PHP_EOL;
            }
            $transaction->commit();
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
            $transaction->rollback();
        }
    }

    public function actionCreate($appId) {
        $module = ImModule::get();
        $module->appId = $appId;
        $users = array(2,3,4,5); // обычные люди
        foreach ($users as $user) {
            $transaction= $this->db_im->beginTransaction();
            $idUser = $user;
            $conversation = Conversation::get($idUser);
            $idConversation = $conversation->idConversation;
            // фигачим сообщения
            $totalMessages = rand(30,100);
            $ts = new DateTime();
            try {
                while (--$totalMessages > 0) {
                    $body = $this->text[array_rand($this->text)];
                    $date = $ts->format('Y-m-d H:i:s');
                    $idMessage = $this->sendMessage($idConversation,$idUser,$body,$date,null,$this->user['id']);
                    $ts->modify('+'.rand(0,10).' minutes');

                    // будем отвечать?
                    if (rand(0,10)>5) {
                        $body = $this->text[array_rand($this->text)];
                        $date = $ts->format('Y-m-d H:i:s');
                        $this->sendMessage($idConversation,$this->user['id'],$body,$date,$idMessage,$idUser);
                        $ts->modify('+'.rand(0,10).' minutes');
                    }
                }
            } catch (CException $e) {
                echo $e->getMessage();
                $transaction->rollback();
            }
            $transaction->commit();
        }
    }

    private function sendMessage($idConversation,$idUser,$body,$date = null,$replyIdMessage = NULL,$to = null) {
        // дата по-умолчанию
        if (!$date) {
            $date = new DateTime();
            $date = $date->format('Y-m-d H:i:s');
        }

        $this->commands['Message']
            ->bindParam(":body",$body,PDO::PARAM_STR)
            ->bindParam(":idUser",$idUser,PDO::PARAM_INT)
            ->bindParam(":ts",$date,PDO::PARAM_STR)
            ->bindParam(":replyIdMessage",$replyIdMessage,PDO::PARAM_STR)
            ->bindParam(":toUserId",$to,PDO::PARAM_STR)
            ->execute();

        $id = $this->db_im->getLastInsertID();

        $this->commands['MessageConversation']
            ->bindParam(":idConversation",$idConversation,PDO::PARAM_INT)
            ->bindParam(":idMessage",$id,PDO::PARAM_INT)
            ->execute();

        /*// свое отправленное сообщение считаем прочитанным самим собой же
        $this->commands['read']
            ->bindParam(":idMessage",$id,PDO::PARAM_INT)
            ->bindParam(":idUser",$idUser,PDO::PARAM_INT)
            ->execute();*/

        return $id;
    }

}