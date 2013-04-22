<?php
/**
 * TeaDbExpression class file.
 *
 * @author Leng Sheng Hong <darkredz@gmail.com>
 * @link http://www.Tea.com/
 * @copyright Copyright &copy; 2009 Leng Sheng Hong
 * @license http://www.Tea.com/license
 */


/**
 * TeaDbExpression represents a DB expression that does not need escaping.
 * TeaDbExpression is mainly used with TeaSqlMagic as attribute values. When inserting or updating a database record,
 * attribute values of type TeaDbExpression will be directly put into the corresponding SQL statement without escaping.
 * A typical usage is that an attribute is set with 'NOW()' expression so that saving the record would fill the corresponding column with the current DB server timestamp.
 * <code>
 * Tea::loadCore('db/TeaDbExpression');
 * $usr = new User;
 * $usr->create_date = new TeaDbExpression('NOW()');
 * //$usr->create_date = 'NOW()';  will insert the date as a string 'NOW()' as it is escaped.
 * Tea::db()->insert($usr);
 * </code>
 *
 * @author Leng Sheng Hong <darkredz@gmail.com>
 * @version $Id: TeaDbExpression.php 1000 2009-07-7 18:27:22
 * @package Tea.db
 * @since 1.0
 */
class TeaDbExpression {

    private $expression;

	/**
	 * Skip parameter binding on values.
	 * @var bool
	 */
	public $skipBinding;

	/**
	 * Use OR statement instead of AND
	 * @var bool
	 */
	public $useOrStatement;

    function  __construct($expression, $useOrStatement=FALSE, $skipBinding=FALSE) {
        $this->expression = $expression;
		$this->useOrStatement = $useOrStatement;
		$this->skipBinding = $skipBinding;
    }

    function  __toString() {
        return $this->expression;
    }
}
