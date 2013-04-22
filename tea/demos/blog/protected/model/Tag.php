<?php
Tea::loadCore('db/TeaModel');

class Tag extends TeaModel{

    /**
     * @var int Max length is 11.  unsigned.
     */
    public $id;

    /**
     * @var varchar Max length is 145.
     */
    public $name;

    public $_table = 'tag';
    public $_primarykey = 'id';
    public $_fields = array('id','name');


    public function  __construct() {
        parent::setupModel(__CLASS__);
    }

    public function getVRules() {
        return array(
                'id' => array(
                        array( 'integer' ),
                        array( 'min', 0 ),
                        array( 'maxlength', 11 ),
                        array( 'notnull' ),
                ),

                'name' => array(
                        array( 'maxlength', 145 ),
                        array( 'notnull' ),
                )
            );
    }

    public function validate($checkMode='all', $requireMode='null'){
        //You do not need this if you extend TeaModel or TeaSmartModel
        //MODE: all, all_one, skip
        Tea::loadHelper('TeaValidator');
        $v = new TeaValidator;
        $v->checkMode = $checkMode;
		$v->requiredMode = $requireMode;
        return $v->validate(get_object_vars($this), $this->getVRules());
    }

}
?>