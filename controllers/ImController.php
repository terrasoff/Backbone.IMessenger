<?php
/**
 * Ticno.com
 * User: Дмитрий
 * Date: 15.05.13
 * Time: 19:43
 *
 * Контроллер для реализации примера клиента
 */
//class ImController extends JsonController {
class ImController extends CommonApiController {

    public $id = 'IM';

    public function afterAction($action){
        if ($this->api->updating)
            $this->api->update();
        return parent::afterAction($action);
    }

    public function actionIndex()
    {

    }

    public function actionSuggest(){
        $result = false;
        $query = Yii::app()->request->getParam('term',null);
        if($query){
            $condition = new CDbCriteria();
            $condition->addSearchCondition('name',$query,'OR');
            $condition->addSearchCondition('email',$query,'OR');
            $users = User::model()->findAll($condition);
            if($users)
            {
                foreach($users as $user){
                    $result[] = $user->name;
                }
            }
            echo CJavaScript::jsonEncode($result);
            return true;
        }

        return false;
    }

    public function actionNew(){
        $message = new NewMessage();
        if(Yii::app()->request->isPostRequest){
            $data = Yii::app()->request->getPost('NewMessage',null);
            $message->setAttributes($data);
            if($message->validate()){
                $message->save();
            }
        }
        $this->render('new',array('model'=>$message));
    }

    public function actionPost(){

        if(Yii::app()->request->isPostRequest){
            $message = new Message();
            $data = Yii::app()->request->getPost('Message',null);
            $message->setAttributes($data);
            if($message->validate()){
                $message->save();
                Yii::app()->user->setFlash('info','Ваше сообщение добавлено');
                $this->redirect('/im/conversation/'.$message->idConversation);
            }
        }
        throw new CHttpException(403,'Forbidden');
    }

    public function actionConversation($id=null,$timestamp=null){

        $conversation = Conversation::model()->my()->messages()->with('users')->findByPk($id);

        $form = new Message;
        $form->idConversation = $id;

        $invite = new SuggestForm();
        $invite->idConversation = $id;

        $this->render('conversation',
                      array(
                           'conversation'=>$conversation,
                           'form'=>$form,
                           'invite_form'=>$invite,
                      )
        );
    }

    public function actionInvite(){
        $pk = Yii::app()->request->getPost('SuggestForm');
        $conversation = Conversation::model()->findByPk($pk['idConversation']);
        if(!$conversation || $conversation->isPrivate()){
            return false;
        }
        // TODO: приглашаем пользователей

    }

    public function actionRead(){
        $idMessage = Yii::app()->request->getParam('idMessage');
        $message = Message::model()->my()->findByPk($idMessage);
        if($message){
            $message->markRead();
            return true;
        }
        return false;
    }
}