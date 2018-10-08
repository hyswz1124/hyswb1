<?php
/**
 * 用户模型
 */
namespace Common\Model;

use Think\Model\RelationModel;

class UserModel extends RelationModel
{
    protected $patchValidate = true;

    /*自动验证规则*/
    protected $_validate = array(
        array('mphone', '11', '手机号为11个字符', self::EXISTS_VALIDATE, 'length'),
        array('mphone', '', '手机号已存在', self::EXISTS_VALIDATE, 'unique'), //用户名被占用
        array('email', '', '邮箱已存在', self::EXISTS_VALIDATE, 'unique'), //邮箱被占用
    );

}