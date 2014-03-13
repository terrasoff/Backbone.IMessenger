<?php
/**
 * Ticno.com
 * User: Дмитрий
 * Date: 15.05.13
 * Time: 20:02
 */

class SuggestForm extends CFormModel {
    public $query = '';
    public $idConversation = null;
    public $receptions = array();

    public function rules(){
        return array(
            array('query, idConversation', 'required'),
            array('receptions','safe')
        );
    }

}