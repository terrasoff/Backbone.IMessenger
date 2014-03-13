<?php /* @var $this Controller */ ?>
<?php

echo CHtml::beginForm();

?>
    <div class="row">

        <?php
        echo CHtml::activeLabel($model,'receivers');
        $this->widget(
            'application.extensions.MultiComplete',
            array(
                 'model'=>$model,
                 'attribute'=>'receivers',
                 'splitter'=>',',
                 'sourceUrl'=>'/im/suggest',
                 // additional javascript options for the autocomplete plugin
                 'options'=>array(
                     'minLength'=>'2',
                 ),
                 'htmlOptions'=>array(
                     'size'=>'60'
                 ),
            )
        );
        echo CHtml::error($model,'title');
        ?>
    </div>

    <div class="row">
        <?php
        echo CHtml::activeLabel($model,'title');
        echo CHtml::activeTextField($model,'title');
        echo CHtml::error($model,'title');
        ?>
    </div>
    <div class="row">
        <?php
        echo CHtml::activeLabel($model,'body');
        echo '<br/>';
        echo CHtml::activeTextArea($model,'body');
        echo CHtml::error($model,'title');
        ?>
    </div>
    <div class="actions">
        <?php
        echo CHtml::ajaxSubmitButton('Отправить','/im/new');
        ?>
    </div>
<?php
echo CHtml::activeHiddenField($model,'idConversation');
echo CHtml::endForm();