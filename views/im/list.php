<?php /* @var $this Controller */ ?>
<?php if($conversations) { ?>
    <?php foreach ($conversations as $conversation) { ?>
        <? /* @var $conversation Converstion */ ?>
        <div>
            <h1><?= $conversation->title ?></h1>
            <div class="item">
                <a href="<?php echo $conversation->link?>">
                    <?php foreach ($conversation->Receiver as $user) {
                        if($user->idUser!==Yii::app()->user->getId())
                        echo $user->user->email. '&nbsp;';
                    }
                    ?>
                    <?php if($conversation->lastPost) echo $conversation->lastPost->body;?><br><br>
                </a>
            </div>
        <div>
    <?php }
}?>