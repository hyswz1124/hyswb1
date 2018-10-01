<?php
/**
 * Created by PhpStorm.
 * User: hys
 * Date: 2018/10/1
 * Time: 15:10
 */

namespace Api\Controller;


class LoginController extends CommonController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index(){
        $user = I('user', '');
        $pass = I('pwd', '');
        $where['mphone|email'] = $user;
        $where['password'] = password_hash($pass, PASSWORD_DEFAULT);
        $where['status'] = 0;
        $where['deleted'] = 0;
        $data = M('users')->where($where)->find();
        if(!$data){
            api_json('', 400, '登录失败');
        }
        $token  = $this->inittoken();
        $up['token'] = $token;
        $result =  M('users')->where('id='.$data['id'])->save($up);
        if (false === $result) {
            api_json('', 400, '登录失败');
        }

        $return['nickname'] = $data['nickname'];
        $return['mphone'] = $data['mphone'];
        $return['email'] = $data['email'];
        $return['super_token'] = $data['super_token'];
        $return['all_earnings'] = $data['earnings'];
        $return['govern_earnings'] = $data['govern_earnings'];
        $return['frozen_earnings'] = $data['frozen_earnings'];
        $return['token'] = $token;
        api_json($return, 200, '登录成功');
    }
}