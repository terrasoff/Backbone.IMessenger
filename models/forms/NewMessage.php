<?php
/**
 * Ticno.com
 * User: Дмитрий
 * Date: 16.05.13
 * Time: 17:51
 */

class NewMessage extends Message {
    public $receivers = null;
    public $idConversation = 'new';

    public function rules(){
        $parent = parent::rules();
        return CMap::mergeArray(
            array(
                 array('receivers,idConversation', 'safe'),
            ),
            $parent
        );
    }

    public function afterSave(){
        // создаем новый диалог
        $conversation = $this->getConversation();
        if($this->title && $conversation->getIsNewRecord()){
            $conversation->title = $this->title;
            $conversation->isClosed = true;
            $conversation->save();
        }

        $this->idConversation = $conversation->idConversation;

        // добавим получателей
        $usersIds = explode(',',$this->receivers);

        if(!$conversation->addReceivers($usersIds)){
            throw new MessageException(400, 'Не все получатели добавлены к диалогу');
        }

        return parent::afterSave();
    }

    private $_conversation = null;

    private function getConversation(){
        if($this->_conversation===null)
        {
            $receivers = explode(',',$this->receivers);
            if($this->idConversation=='new'){
                // определяем не диалог ли с одним пользователем
                if(count($receivers)==1){
                    $conversation = Conversation::model()->getSingle($receivers[0],$this->idUser);
                }else{
                    $conversation = new Conversation();
                    $conversation->save();
                }
                $conversation->addReceivers(array($this->idUser)); // добавим автора для наблюдения за чатом
            }
            else{
                $conversation = Conversation::model()->findByPk($this->idConversation);
            }

            $this->_conversation = $conversation;
        }
        return $this->_conversation;
    }
}