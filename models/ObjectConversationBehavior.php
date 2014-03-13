<?php
/**
 * При обновлении информации об объекте, нужно так же обновить информацию в разговоре
 * Так же нужно учитывать и сохранение владельца (@link UserObjectConversationBehavior)
 * User: terrasoff
 * Date: 9/10/13 1:31 PM
 */

class ObjectConversationBehavior extends CActiveRecordBehavior
{

    public function beforeSave($event) {
        $obj = $this->getOwner();
        $obj->updateConversation();
        return parent::beforeSave($event);
    }

    /**
     * Обновляем инфу по разговору
     * @param null $user
     */
    public function updateConversation($user = null) {
        $obj = $this->getOwner();
        // обновляем инфу по объекту для разговора
        if ($obj->ObjectConversation) {
            if (!$user) $user = User::model()->findByPk($obj->user_id);
            $obj->ObjectConversation->saveInfo($user, $obj);
        }
    }
}