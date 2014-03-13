<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Дмитрий
 * Date: 08.08.12
 * Time: 14:08
 * To change this template use File | Settings | File Templates.
 */
class SiteController extends Controller
{

    public function beforeAction($action){
        $this->layout = 'messenger';
//        if(Yii::app()->user->isGuest)
//            throw new CHttpException(403,'Forbidden');

        return true;
    }

    public function actionIndex()
    {
        $cs = Yii::app()->clientScript;
        $cs->registerPackage('jquery');
        $cs->registerPackage('core');

        $this->render('/im/im',array('data'=>ImApi::instance()->update(null)));
    }

    public function actionError()
    {
        if ($error = Yii::app()->errorHandler->error) {
            if (Yii::app()->request->isAjaxRequest)
                echo $error['message'];
            else
                $this->render('error', $error);

        }
    }

    public function actionGlobals()
    {
        header('Content-Type:application/javascript');
        echo $this->renderGlobals();
    }

}
