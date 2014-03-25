<?php

return CMap::mergeArray(require(__DIR__.'/../../../config/test.php'), array(

    'import'=>array(
        'im.tests.helpers.*',
        'im.extensions.*',
        'im.models.*',
    ),

    'components'=>array(
        'fixture'=>array(
            'class'=>'system.test.CDbFixtureManager',
            'basePath'=>__DIR__.'/fixtures/',
            'connectionID'=>'db_im',

        ),

        'urlManager'=>array(
            'baseUrl'=>'http://givetonext/'
        ),

        'response'=>array(
            'class'=>'application.extensions.response.ETestResponse'
        ),

//        'db'=>require('db-test.php')

    )
));