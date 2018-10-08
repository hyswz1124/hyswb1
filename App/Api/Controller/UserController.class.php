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

    public function getUser(){
        api_json($this->userInfo, 200, '获取用户信息成功');
    }

    public function changeAccount(){
        $account_old = I('account_old');
        $account_new = I('account_new');
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
        $data['update_time'] = datetimenew();
        $rs = M('Users')->where('id='.$this->userInfo['id'])->save($data);
        if($rs === false){
            api_json('', 500, '更换失败，请重试');
        }
        api_json('', 200, '更改成功');
    }

}