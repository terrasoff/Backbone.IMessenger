<?php /* @var $this Controller */ ?>
<?php
$model->query = Yii::app()->request->getPost('query','');

echo CHtml::beginForm('/im/suggest');?>
    <div class="row">

        <?php
        echo CHtml::activeLabel($model,'query');
        $this->widget(
            'application.extensions.MultiComplete',
            array(
                 'model'=>$model,
                 'attribute'=>'query',
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
        echo CHtml::error($model,'query');
        echo CHtml::activeHiddenField($model,'idConversation');
        ?>
    </div>
    <div class="actions">
        <?php
        echo CHtml::ajaxSubmitButton('Пригласить в чат','/im/invite');
        ?>
    </div>
<?php
echo CHtml::endForm();