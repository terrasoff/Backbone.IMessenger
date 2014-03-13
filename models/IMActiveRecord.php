<?php

class IMActiveRecord extends CActiveRecord  {

    public function getDbConnection() {
        $module = Yii::app()->getModule(ImModule::$id);
        $db = $module->getDbConnection();
        $db->setActive(true);
        return $db;
    }
    
}

?>