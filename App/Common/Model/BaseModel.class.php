<?php
/**
 * 项目model自定义基类
 * 封装通用功能
 */
namespace Common\Model;

use Think\Model\RelationModel;

class BaseModel extends RelationModel
{

    public function __construct($name = '', $tablePrefix = '', $connection = '')
    {
        parent::__construct($name, $tablePrefix, $connection);
    }
}