<?php
/**
 * Ticno.com
 * User: terrasoff
 *
 * API для недвижки
 */

class ApiController extends CommonApiController {

    public $id = 'IM';

    public $commands = array(
        'ignore'=>array('update')
    );

    public function afterAction($action){
        // всегда пробуем найти обновления
        $data = Yii::app()->request->getParam('data',null);
        if ($this->api->updating)
            $this->api->update($data);

        return parent::afterAction($action);
    }

}