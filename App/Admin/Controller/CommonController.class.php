<?php

namespace Admin\Controller;
use Think\Controller;



/**
 * 公共部分
 */
class CommonController extends Controller
{
    protected $adminid = 0;

    public function __construct()
    {
        parent::__construct();
        $is = false;
//        if (!session('?adminid')){
//            $is = true;
//            api_json('', '109', '未登录或登录失效');
//        }else{
//            $is = false;
//            $this->adminid = session('adminid');
//        }
//        if($is){
//            $token = I('token');
//            if(!$token){
//                api_json(null, 108, '缺少 token');
//            }
//            $where['token'] = $token;
//            $where['is_admin'] = 1;
//            $field = 'id, nickname,is_kt,one_superid,two_superid, game_status, game_number, code, mphone, secret, email, is_js, is_freeze,  token, super_token, eth, eth_address, all_earnings,all_token, all_eth, dynamic_earnings, dividend_earnings, node_earnings, paradrop_earnings, invite_earnings, govern_earnings, frozen_earnings';
//            $data = M('users')->where($where)->field($field)->find();
//            if (!$data) {
//                api_json(null, 109, 'token错误');
//            }
//            $this->adminid = $data['id'];
//        }
    }
}