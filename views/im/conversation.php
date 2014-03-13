<?php
/**
 * @var $this Controller
 * @var $conversation Conversation
 * @var $form Message
 * @var $invite_form SuggestForm
 * */
?>

<h3><?php echo $conversation->title;?></h3>

<?php $this->renderPartial('forms/suggest_form',array('model'=>$invite_form));?>

<hr>

<?php foreach ($conversation->messages as $message) {?>
    <div class="message <?php echo ($message->isRead()) ? 'read' : 'unread'?>">
        <?php if($message->title){ ?><h4><?php echo $message->title?></h4><?php }?>
        <span class="ts"><?php echo $message->ts?> by <?php echo $message->author->email;?></span>
        <p>
            <?php echo $message->body;?>
        </p>
    </div>
<?php }?>

<div class="post">
    <?php /* @var $this Controller */ ?>
    <?php echo CHtml::beginForm('/im/post'); ?>
    <div class="row">
        <?php
        echo CHtml::activeLabel($form,'title');
        echo CHtml::activeTextField($form,'title');
        echo CHtml::error($form,'title');
        ?>
    </div>
    <div class="row">
        <?php
        echo CHtml::activeLabel($form,'body');
        echo '<br/>';
        echo CHtml::activeTextArea($form,'body');
        echo CHtml::error($form,'title');
        ?>
    </div>
    <div class="actions">
        <?php
        echo CHtml::submitButton('Отправить');
        ?>
    </div>
    <?php
    echo CHtml::activeHiddenField($form,'idConversation');
    echo CHtml::endForm();?>
</div>

