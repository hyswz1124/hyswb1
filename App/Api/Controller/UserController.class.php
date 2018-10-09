<?php
/**
 * Created by PhpStorm.
 * User: hys
 * Date: 2018/10/1
 * Time: 16:21
 */

namespace Api\Controller;


class UserController extends CommonController
{
    protected $userInfo = '';

    public function __construct()
    {
        parent::__construct();
        $this->userInfo =  $this->checkLogin();
    }

    /**
     * 用户信息
     */
    public function getUser(){
        api_json($this->userInfo, 200, '获取用户信息成功');
    }

    /**
     * 更换账号
     */
    public function changeAccount(){
        $account_old = I('account_old', '', 'trim');
        $account_new = I('account_new', '', 'trim');
        if(!$account_old or !$account_new){
            api_json('', 400, '参数不能为空');
        }
        if($account_old != $this->userInfo['mphone'] and $account_old != $this->userInfo['email']){
            api_json('', 400, '信息不匹配不允许更改');
        }

        if(!preg_match('/1[0-9]{10}/', $account_new) || strlen($account_new) != 11) {
            if(!preg_match('/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/', $account_new)){
                echo api_json(null,'400','手机号码或邮箱格式不正确');
            }else{
                $data['email'] = $account_new;
            }
        }else{
            $data['mphone'] = $account_new;
        }
        $is = M('Users')->where($data)->find();
        if($is){
            api_json('', 400, '手机号或者邮箱已存在');
        }
        $data['update_time'] = datetimenew();
        $rs = M('Users')->where('id='.$this->userInfo['id'])->save($data);
        if($rs === false){
            api_json('', 500, '更换失败，请重试');
        }
        api_json('', 200, '更改成功');
    }

    /**
     * 填写 ETH 地址
     */
    public function setEthAddress(){
        $eth_address = I('eth_address', '', 'trim');
        if(!$eth_address){
            api_json('', 400, 'ETH 地址不能为空');
        }
        $data['eth_address'] = $eth_address;
        $data['update_time'] = datetimenew();
        $rs = M('Users')->where('id='.$this->userInfo['id'])->save($data);
        if($rs === false){
            api_json('', 500, '填写失败，请重试');
        }
        api_json('', 200, '填写成功');
    }
}