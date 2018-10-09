<?php

namespace Api\Controller;
use Think\Controller;

class RechargeController extends CommonController {
    /**
     * 此方法程序为用户eth充值、积分充值,用户解锁
     * @author  wmt<1027918160@qq.com>
     * @date    2018-10-08
     */
    protected $userInfo = '';

    public function __construct()
    {
        parent::__construct();
//        $this->userInfo =  $this->checkLogin();
    }
    public function unlocking(){
        $user = $this->userInfo;
        $eth = 0.02;
        if($user['eth'] < $eth){
            api_json(null,'600','账户ETH钱包余额不足');
        }

            $trade = [
                'user_id' => $user['id'],
                'mode' => 'unlock',
                'related_id' => $this->systemId,
                'message' => '用户解锁',
                'status' => 1,
                'eth' => $eth
            ];
            $trade_id = M('trades')->add($trade);
            $payment = [
                'trade_id' => $trade_id,
                'mode' => 'balance',
                'eth' => $eth,
                'beamount' => $user['eth'],
                'afamount' => $user['eth'] + $eth,
                'status' => 1
            ];
           $payment_id = M('payments')->add($payment);
            if ($trade_id && $payment_id) {
                $code = $this->initcode();
                M('users')->where('id='.$user['id'])->save(['is_js'=>1,'code'=> $code,'eth'=>$user['eth']-$eth,'update_time' => 'now()']);
//                M('users')->where("id = {$user['id']}")->setDec('eth',$eth);
                api_json($code,'200','解锁成功');
            }else{
                api_json(null,'500','解锁失败');
            }
    }

    /**
     * 用户充值接口
     */
    public function create(){
        $user = $this->userInfo;
        $eth = I('eth');
        if(empty($eth)){
            api_json(null,'300','充值eth数目不能为空');
        }

//        M('trades')->startTrans();
//        try {
            $trade = [
                'user_id' => $user['id'],
                'mode' => 'recharge',
                'related_id' => $this->systemId,
                'message' => '用户充值',
                'status' => 0,
                'eth' => $eth
            ];
            if(I('file')){
                $root_directory = './Public/';
                $subdirectory = 'Uploads/img/';
                $img = $this->upload($root_directory,$subdirectory);
                if(!$img || $img['status'] == 'no'){
                    api_json(null,'500',$img['data']);
                }
                $trade['photo'] = $img['data'];
            }
            $trade_id = M('trades')->add($trade);
            if ($trade_id) {
                api_json(1,'200','充值提交成功');
            }else{
                api_json(null,'500','网络故障');
            }

//        } catch (\Exception $e) {
//            M('trades')->rollback();
//            api_json(null,'500',$e->getMessage());
//        }
    }
}