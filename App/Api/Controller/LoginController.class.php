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

    /**
     * 登录
     */
    public function index(){
        $user = I('user', '', 'trim');
        $pass = I('pwd', '', 'trim');
        $where['mphone|email'] = $user;
        if(!$user or !$pass){
            api_json('', 400, '参数为空');
        }
//        $where['password'] = password_hash($pass, PASSWORD_DEFAULT);
        $where['status'] = 0;
        $where['deleted'] = 0;
        $field = 'id, nickname, code,password, mphone, email, is_js, token, super_token, eth, eth_address, all_earnings,all_token, all_eth, dynamic_earnings, dividend_earnings, node_earnings, paradrop_earnings, invite_earnings, govern_earnings, frozen_earnings';
        $data = M('users')->where($where)->field($field)->find();
        if(!$data){
            api_json('', 400, '用户不存在或者已被禁用');
        }
        $row=password_verify($pass,$data['password']);
        if(!$row){
            api_json('', 400, '密码错误');
        }
        $token  = $this->inittoken();
        $up['token'] = $token;
        $result =  M('users')->where('id='.$data['id'])->save($up);
        if (false === $result) {
            api_json('', 400, '登录失败');
        }
        unset($data['password']);
        $data['token'] = $token;
        api_json($data, 200, '登录成功');
    }
}