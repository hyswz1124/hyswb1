<?php

namespace Api\Controller;
use Think\Controller;

class RechargeController extends CommonController {
    /**
     * 此方法程序为管理员给用户eth充值、积分充值,用户解锁
     * @author  wmt<1027918160@qq.com>
     * @date    2018-10-08
     */
    protected $userInfo = '';

    public function __construct()
    {
        parent::__construct();
        $this->userInfo =  $this->checkLogin();
    }
    public function unlocking(){
        $user = $this->userInfo;
        $eth = 0.02;
        if($user['eth'] < $eth){
            api_json(null,'600','账户ETH钱包余额不足');
        }
        M('trades')->startTrans();
        try {
            $trade = [
                'user_id' => $user['id'],
                'mode' => 'unlock',
                'related_id' => $this->systemId,
                'message' => '用户解锁',
                'status' => 0,
                'eth' => $eth,
            ];
            $trade_id = M('trades')->add($trade);
            $settle = trade_settle($trade_id,$user);
            if ($settle['status'] === 'ok') {
                $code = $this->initcode();
                M('users')->where('id='.$user['id'])->save(['is_js'=>1,'code'=> $code]);
                M('orders')->commit();
                api_json($code,'200','解锁成功');
            }

        } catch (\Exception $e) {
            M('trades')->rollback();
            api_json(null,'500',$e->getMessage());
        }
    }

    public function create(){
        $user = $this->userInfo;
        $eth = I('eth');
        if(empty($eth)){
            api_json(null,'300','充值eth数不能为空');
        }
        M('trades')->startTrans();
        try {
            $trade = [
                'user_id' => $user['id'],
                'mode' => 'recharge',
                'related_id' => $this->systemId,
                'message' => '用户充值',
                'status' => 0,
                'eth' => $eth,
            ];
            $trade_id = M('trades')->add($trade);
            $settle = trade_settle($trade_id,$user);
            if ($settle['status'] === 'ok') {
                M('trades')->commit();
                api_json(1,'200','解锁成功');
            }

        } catch (\Exception $e) {
            M('trades')->rollback();
            api_json(null,'500',$e->getMessage());
        }
    }
}