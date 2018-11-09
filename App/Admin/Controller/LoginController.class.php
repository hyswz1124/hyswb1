<?php

namespace Admin\Controller;

use Common\Model\GoogleAuthenticatorModel;
use Think\Controller;

class LoginController extends Controller {

    /**
     * 登录
     */
    public function index()
    {
        $user = I('user', '', 'trim');
        $pass = I('pwd', '', 'trim');
        $code = I('code', '', 'trim');
        $where['email|mphone|eth_address'] = $user;
        if (!$user) {
            api_json('', 400, '参数为空');
        }
        $where['status'] = 0;
        $where['is_admin'] = 1;
        $where['deleted'] = 0;
        $field = 'id, nickname ,password, mphone, email';
        $data = M('users')->where($where)->field($field)->find();
        if (!$data) {
            api_json('', 400, '用户不存在或者已被禁用');
        }

        if ($code) {
            $googleAuthenticator = new GoogleAuthenticatorModel();
            $checkResult = $googleAuthenticator->verifyCode($data['secret'], $code, 2);    // 2 = 2*30sec clock tolerance
            if (!$checkResult) {
                api_json('', 400, '验证码错误');
            }
        }
        if (!$code) {
            $row = password_verify($pass, $data['password']);
            if (!$row) {
                api_json('', 400, '密码错误');
            }
        }
        unset($data['password']);
        cookie('adminid',$data['id']);
        cookie('mphone',$data['mphone']);
        cookie('nickname',$data['nickname']);
        cookie('email',$data['email']);
        session('adminid', $data['id']);
        api_json($data, 200, '登录成功');
    }

    /* 退出登录 */
    public function logout() {
        if( ! empty(cookie('mphone'))) {
            cookie('adminid',null);
            cookie('mphone',null);
            cookie('nickname',null);
            cookie('email',null);
            session('adminid', null);
        }
        api_json('', 200, '退出成功');
    }
}