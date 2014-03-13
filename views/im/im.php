<?php
$this->widget('IMessengerWidget',array(
    // id-контейнера для сообщений
    'server'=>Yii::app()->getModule('im')->server,
    'conversations'=>$data,
    'user'=>'app.user',
));