<?php
/**
 * Ticno.com
 * User: Дмитрий
 * Date: 23.05.13
 * Time: 14:15
 */

class IMApi extends Api {

    public $conversation = null; // ApiConversation

    /**
     * Обновиться после всех действий (используется в ApiController)
     * @var bool
     */
    public $updating = true;

    /**
     * Устанавливаем для условия поиска appId
     * (чтоб знать, к какому приложению относится данный разговор)
     * Задается в Yii::app()->params['ticno']['appId']
     * @param CDbCriteria $criteria
     * @return mixed
     */
    public function setAppId(&$criteria) {
        // разговоры только в рамках нашего приложения
        $criteria->addCondition('t.appId = :appId');
        $criteria->params[':appId'] = $this->getAppId();
    }

    public function attach($file){
        // TODO: не реализовано
        return 'fileId';
    }

    public function user($data){
        $query = $this->bindParam('query',$data);
        $result = false;
        if($query){
            $condition = new CDbCriteria();
            $condition->addSearchCondition('name',$query,'OR');
            $condition->addSearchCondition('email',$query,'OR');
            $users = User::model()->findAll($condition);
            if($users)
            {
                foreach($users as $user){
                    $result[] = array('id'=>$user->id,'name'=>$user->name);
                }
            }

            $this->response->push(__FUNCTION__,$result);
            return true;
        }

        return false;
    }

    public function conversations($data = null){
        $total = $this->bindParam('total',$data);

        try{
            $conversations = Conversation::model()
                ->my()
                ->scopePage($total)
                ->findAll();
            $result = [];
            if($conversations) {
                $messagesCriteria = array('limit'=>10);
                foreach ($conversations as $conversation) {
                    $result[] = $conversation->toJSON(array('users'=>[],'messages'=>$messagesCriteria));
                }
            }
            $this->response->push(__FUNCTION__,$result);
        } catch(IMException $e) {
            $this->response->error(array($e->getMessage()));
        }

        return true;
    }

    public function history($data)
    {
        try {
            $result = [];
            $idConversation = $this->bindParam('idConversation',$data);
            $conversation = Conversation::model()->my()->findByPk($idConversation);

            if ($conversation)
            {
                $module = ImModule::get();
                $criteria = new CDbCriteria();
                $criteria->limit = $module->messageLimit;

                $since = $this->bindParam('since',  $data);
                if ($since) {
                    $criteria->addCondition('Message.idMessage < :since');
                    $criteria->params[':since'] = $since;
                }

                $result = $conversation->toJSON(array('user'=>[], 'messages'=>$criteria));
            }
            $this->response->push(__FUNCTION__,$result);
        } catch(IMException $e) {
            $this->response->error(array($e->getMessage()));
        }
    }

    public function update($data = null){
        $idConversation = $this->bindParam('idConversation',$data);
        $maxId = $this->bindParam('maxId',$data);
        $sinceId = $this->bindParam('sinceId',$data);

        try{
            $limit = 10;
            $criteria = new CDbCriteria();

            if($idConversation && is_int($idConversation)){
                $criteria->addCondition('t.idConversation = :idConversation');
                $criteria->params[':idConversation'] = $idConversation;
            }

            $conversations = Conversation::model()
                ->messages($limit,$sinceId,$maxId)
                ->my()
                ->findAll($criteria);
            $result = [];
            if($conversations) {
                foreach ($conversations as $conversation) {
                    $result[] = $conversation->toJSON(array('users'=>[],'messages'=>[]));
                }
            }
            $this->response->push(__FUNCTION__,$result);
        } catch(IMException $e) {
            $this->response->error(array($e->getMessage()));
        }

        return true;
    }

    public function send($data)
    {
        // TODO проверка параметров (в том числе можем ли мы отправлять сообщ. этому пользователю)
        $message = new Message();
        $message->setAttributes($this->bindParam('message',$data));

        try {
            if (!$message->save())
                $this->response->error($message->getErrors());
        } catch(IMException $e) {
            $this->response->error(array($e->getMessage()));
        }
    }

    public function read($data){
        $idConversation = (int)$this->bindParam('idConversation',$data);
        $maxId = (int)$this->bindParam('maxId',$data);
        $idMessage = (int)$this->bindParam('idMessage',$data);
        $idMessages = (int)$this->bindParam('idMessages',$data);

        try{
            if($idMessage){
                $message = Message::model()->findByPk($idMessage);
                if($message && $message->markRead())
                    $this->response->push(__FUNCTION__,$message->idMessage);
            }else{
                if($idConversation || $maxId || $idMessages){
                    $result = Message::model()->markAllRead($maxId,$idConversation,$idMessages);
                    $this->response->push(__FUNCTION__,array(
                        'idConversation'=>$idConversation,
                        'messages'=>$result['messages'],
                        'unread'=>$result['unread'],
                    ));
                }
            }
        } catch(IMException $e) {
            $this->response->error(array($e->getMessage()));
        }
    }

    public function delete($data){
        $idMessage = (int) $this->bindParam('idMessage',$data);

        try{
            $message = Message::model()->findByPk($idMessage);
            if($message && $message->markDelete())
                $this->response->push(__FUNCTION__,$message->idMessage);
        } catch(IMException $e) {
            $this->response->error(array($e->getMessage()));
        }
    }

    public function invite($data){
        $receivers = $this->bindParam('receivers',$data);
        $idConversation = (int) $this->bindParam('idConversation',$data);

        try{
            $conversation = $this->getConversation($idConversation);

            if($conversation && $conversation->allowInvite()){
                if(!is_array($receivers))
                    $receivers = array($receivers);
                $conversation->addReceivers($receivers);
                return true;
            }
        } catch(IMException $e) {
            $this->response->error(array($e->getMessage()));
        }

        return false;
    }

    public function title($data)
    {
        $idConversation = (int)$this->bindParam('idConversation',$data);
        $title = $this->bindParam('title',$data);

        try{
            $conversation = $this->getConversation($idConversation);
            if($conversation){
                $conversation->title = $title;
                if($conversation->save()){
                    $this->response->push('title',$conversation->toJSON());
                    return true;
                }
            }
        } catch(IMException $e) {
            $this->response->error(array($e->getMessage()));
        }

        return false;
    }

    public function rights($data){
        $idConversation = (int)$this->bindParam('idConversation',$data);
        try{
            $conversation =$this->getConversation($idConversation);
            if($conversation){
                $this->api->response->push(__FUNCTION__,$conversation->getRights());
                return true;
            }
        } catch(IMException $e) {
            $this->response->error(array($e->getMessage()));
        }

        return false;
    }

    public function fork($data){
        $idConversation = (int)$this->bindParam('idConversation',$data);
        $receivers = $this->bindParam('receivers',$data);
        try{
            $conversation = Conversation::model()->findByPk($idConversation);
            $new_conversation = $conversation->fork($receivers);
            if($new_conversation){
                $this->response->push('fork',$new_conversation->toJSON(array('users','messages')));
                return true;
            }
        } catch(IMException $e) {
            $this->response->error(array($e->getMessage()));
        }

        return false;
    }

    public function unread($data) {
        $idConversation = (int)$this->bindParam('idConversation',$data);
        try{
            $conversation = Conversation::model()->findByPk($idConversation);
            $this->response->push('unread',array('unread'=>10));
            return true;
        } catch(IMException $e) {
            $this->response->error(array($e->getMessage()));
        }

        return false;
    }

    private function getConversation($idConversation){
        $converstion = Conversation::model()->findByPk($idConversation);
        return $converstion;
    }
}