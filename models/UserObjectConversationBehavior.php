<?php
/**
 * При обновлении информации о владельце, нужно так же обновить информацию в разговоре
 * Так же нужно учитывать и сохранение объекта (@link ObjectConversationBehavior)
 * User: terrasoff
 * Date: 9/10/13 1:31 PM
 */

class UserObjectConversationBehavior extends CActiveRecordBehavior
{

    public function beforeSave($event) {
        $obj = $this->getOwner();
        $items = Items::model()->mine()->findAll();
        foreach ($items as $item) {
            $item->updateConversation($obj);
        }

        return parent::beforeSave($event);
    }
}