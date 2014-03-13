<?php

/**
 * @desc Работаем с сообщениями
 * @author terrasoff
 */

class IMComponent extends CComponent {

    public $total   = 0; // сообщений всего
    public $unread  = 0; // число непрочитанных сообщений

    // инициализация - понятное дело
    function init() {
        if (Yii::app()->user->isGuest)
            return;
        $this->total  = Message::model()->getMessagesTotal();
        $this->unread = Message::model()->getMessagesTotal(true);
    }

    public function getTotalUnread(){
        return $this->unread;
    }

    public function getTotal(){
        return $this->total;
    }

    public function hasNewMessages(){
        return (bool)$this->unread > 0;
    }

    public function total() {
        return $this->unread > 0 ? $this->unread : $this->total;
    }

    public function toJSON() {
        return array(
            'unread'=>$this->unread,
            'total'=>$this->total,
        );
    }

}