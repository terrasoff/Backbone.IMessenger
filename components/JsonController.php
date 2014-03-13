<?php
/**
 * Extended CController, w/ rest method for making right REST answers
 */
class JsonController extends Controller
{
    public function init()
    {
//        $this->checkOrigin();
        header('Content-type: application/json');
    }

    /**
     * ответ сервера (ajax)
     * @param $data
     */
    public function sendJSON($data) {
        echo CJSON::encode($data);
        Yii::app()->end();
    }

    /**
     * сообщенаем об ошибке (ajax)
     * @param array
     */
    public function sendErrors($errors) {
        echo CJSON::encode($errors);
        Yii::app()->end();
    }

}
