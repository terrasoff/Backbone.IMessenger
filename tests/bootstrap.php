<?php
$yiit=__DIR__.'/../../../vendor/yiisoft/yii/framework/yiit.php';
$config=dirname(__FILE__).'/config.php';
require_once($yiit);
Yii::createWebApplication($config);