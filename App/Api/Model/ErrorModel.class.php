<?php

namespace Api\Model;
use Think\Model;

/**
 * 编码对应信息
 */
class ErrorModel extends Model {

    /**
     * 通过状态值来获取状态文本
     * @param status
     * @return string
     */
    public function getText($code){

        switch($code){
            case 100:
                $text = '没有相关的信息';
                break;
            case 200:
                $text = '获取成功';
                break;
            case 300:
                $text = '缺少参数';
                break;
            case 400:
                $text = '参数错误';
                break;
            case 500:
                $text = '添加成功';
                break;
            case 600:
                $text = '添加失败';
                break;
            default:
                $text = '网络出错,请刷新后再试';
                break;
        }

        return $text;
    }












}