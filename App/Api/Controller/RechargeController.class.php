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
        $this->userInfo =  $this->checkLogin();
    }

    /**
     * 用户解锁邀请码
     */
    public function unlocking(){
        $user = $this->userInfo;
        if($user['is_js']){
            api_json('', 600, '该功能已经解锁');
        }
        $eth = 0.02;
        if($user['eth'] < $eth){
            api_json(null,'600','账户ETH钱包余额不足');
        }

            $trade = [
                'user_id' => $user['id'],
                'mode' => 'unlock',
                'related_id' => $this->systemId,
                'message' => '用户解锁邀请码',
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
        $is_where['mode'] = 'recharge';
        $is_where['status'] = 0;
        $is_where['user_id'] = $user['id'];
        $is_ing = M('trades')->where($is_where)->find();
        if($is_ing){
            api_json('', 300, '存在待审核的交易');
        }
        $eth = I('eth');
        if(empty($eth) or !is_numeric($eth)){
            api_json(null,'300','充值eth数目不正确');
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


    /**
     * 充值交易详情
     */
    public function details(){
        $user = $this->userInfo;
        $where['mode'] = 'recharge';
        $where['user_id'] = $user['id'];
        $order = 'create_time desc';
        $data = M('trades')->where($where)->order($order)->find();
        $return = array();
        if($data){
            $return['status'] = $data['status'];
            $return['photo'] =  C('APPHOST').$data['photo'];
            $return['eth'] = $data['eth'];
            $return['eth_address'] = $user['eth_address'];
        }
        api_json($return, 200, '交易详情');
    }

    /**
     * 用户撤回交易
     */
    public function recall(){
        $user = $this->userInfo;
        $where['mode'] = 'recharge';
        $where['user_id'] = $user['id'];
        $order = 'create_time desc';
        $data = M('trades')->where($where)->order($order)->find();
        $return = array();
        if(!$data){
            api_json('', 400, '无可撤销交易');
        }
        if($data['status'] == 1){
            api_json('', 400, '交易已完成，不可撤销');
        }
        if($data['status'] == 2 or $data['status'] == 3){
            api_json('', 400, '交易已关闭，无需撤销');
        }
        $up['status'] = 2;
        $up_where['id'] = $data['id'];
        $rs = M('trades')->where($up_where)->save($up);
        if($rs === false){
            api_json('', 500, '撤销失败，请重试');
        }
        api_json(1, 200, '撤销成功');
    }
}