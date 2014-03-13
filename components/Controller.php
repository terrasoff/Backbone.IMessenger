<?php
class Controller extends CController
{
    public $layout = '//layouts/default';

    /**
     * @var array context menu items. This property will be assigned to {@link CMenu::items}.
     */
    public $menu = array();
    /**
     * @var array the breadcrumbs of the current page. The value of this property will
     * be assigned to {@link CBreadcrumbs::links}. Please refer to {@link CBreadcrumbs::links}
     * for more details on how to specify this property.
     */
    public $breadcrumbs = array();

    public function checkUser($required = true)
    {

        if (!Yii::app()->user->isGuest)
            return Yii::app()->user->getId();
        else if ($required)
//            $this->rest(403, array('status'=>'invalid', 'error'=>'Пожалуйста авторизуйтесь'));
            throw new CHttpException(403, CJavaScript::jsonEncode(array('status' => 'invalid', 'error' => 'Пожалуйста авторизуйтесь')));
        else return 0;
    }
	

}
