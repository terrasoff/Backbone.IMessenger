<!DOCTYPE HTML>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <title><?=$this->pageTitle?></title>
</head>
<body>
<h1>Личные сообщения</h1>
<div class="user-block">
    <?php $this->widget('extensions.Creomind.authclient.widgets.LoginInfo');?>
</div>
<?php if(!Yii::app()->user->isGuest){ ?>
    Привет, <?php echo Yii::app()->user->getName();?>! <a href="/im">список сообщений</a> | <a href="/im/new">новое сообщение</a>
<?php }?>
<hr>
<?php echo $content;?>
<footer>2013, Ticno</footer>
</body>
</html>
