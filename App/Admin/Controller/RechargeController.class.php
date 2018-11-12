<?php

namespace Admin\Controller;
use Think\Controller;

class RechargeController extends CommonController {
    /**
     * 此方法程序为管理员给用户eth充值、积分充值,用户解锁
     */
    protected $userInfo = '';

    public function __construct()
    {
        parent::__construct();
    }
    /**
     *后台审核 充值 同意接口
     */
    public function check(){
        $tradeId = I('tradeId');
        if(empty($tradeId)){
            api_json(null,'300','交易id不能为空');
        }
//        M('trades')->startTrans();
//        try {
            $settle = trade_settle($tradeId);
            if ($settle['status'] === 'ok') {
                M('trades')->commit();
                $logTitle = '管理员:' . $this->adminid . '同意充值(id:'.$tradeId.')';
                D('AdminLog')->save_log($this->adminid, '充值审核', $logTitle, 'trades', $tradeId);
                api_json(1,'200','操作成功');
            }else{
                api_json(null,'500','操作失败');
            }
//        } catch (\Exception $e) {
//            M('trades')->rollback();
//            api_json(null,'500',$e->getMessage());
//        }
    }
    /**
     * 后台审核充值不通过接口
     * date2018-11-4 飞机上
     */
    public function checkno(){
        $user = $this->userInfo();
        $tradeId = I('tradeId');
        $reason = I('reason','');
        if(empty($tradeId)){
            api_json(null,'300','交易id不能为空');
        }
        $tradeDB  = M('trades');
        $trade = $tradeDB->where("user_id = {$user['id']} and id={$tradeId} and status = 0 and mode = 'recharge'")->find();
        if(!$trade){
            api_json(null,'100','该充值记录不存在');
        }
        $result = M('trades')->where("id = {$tradeId}")->save(['status'=>2,'update_time' =>date('Y-m-d H:i:s',time())]);
        if($result){
            api_json(1,'200','操作成功');
        }
        api_json(null,'500','网络故障，操作失败，请稍后重试');
    }
    /**
     * 后台审核提现通过接口
     */
    public function cash(){
        $user = $this->userInfo();
        $tradeId = I('tradeId');
//        $reason = I('reason','');
        if(empty($tradeId)){
            api_json(null,'300','交易id不能为空');
        }
        $tradeDB  = M('trades');
        $trade = $tradeDB->where("user_id = {$user['id']} and id={$tradeId} and status = 0 and mode = 'cash'")->find();
        if(!$trade){
            api_json(null,'100','该提现记录不存在');
        }
        $result = $tradeDB->where("id={$tradeId}")->save(['status'=>1,'update_time' =>date('Y-m-d H:i:s',time())]);
        if($result){
            api_json(1,200,'提现操作成功');
        }
        api_json(null,500,'网络故障，提现操作失败，请稍后重试');
    }

    /**
     * 后台审核提现不通过接口
     */
    public function cashno(){
        $user = $this->userInfo();
        $tradeId = I('tradeId');
        $reason = I('reason','');
        if(empty($tradeId)){
            api_json(null,'300','交易id不能为空');
        }
        $tradeDB  = M('trades');
        $trade = $tradeDB->where("user_id = {$user['id']} and id={$tradeId} and status = 0 and mode = 'cash'")->find();
        if(!$trade){
            api_json(null,'100','该提现记录不存在');
        }
        $tradeDB->startTrans();
        $add = [
            'user_id' => $trade['user_id'],
            'mode' => 'refund',
            'related_id' => $trade['related_id'],
//            'trade_ids' => '{' . $trade['id'] . '}',
            'message' => '交易' . $trade['id'] . '还款' . ": {$reason}",
            'amount' => $trade['amount'],
            'status' => 1
        ];
        $trade_id = M('trades')->add($add);             //插入退款交易记录
        //  M('orders_action_log')->data(array('order_id'=>$id, 'user_id'=>$user_id, 'action'=>'14', 'timeline'=>time(), 'message'=>$reason))->add();

        $add_payment['trade_id'] = $trade_id;
        $add_payment['mode'] = 'eth';
        $add_payment['beamount'] = $user['eth'];
        $add_payment['afamount'] = $user['eth'] + $trade['amount'];
        $add_payment['amount'] = $trade['amount'];
        $add_payment['status'] = 1;
        $res = M('payments')->add($add_payment);              //插入退款支付记录

        $result = M('users')->where("id = {$trade['user_id']}")->save(['eth' => $user['eth'] + $trade['amount']]);       //修改用户余额
        $results = M('trades')->where("id = {$trade['id']}")->save(['status' => 2,'update_time' =>date('Y-m-d H:i:s',time())]);                //修改交易状态
        if(!$res || !$trade_id || !$result || !$results){
            $tradeDB->rollback();
            api_json(null, 500, '网络故障，退款失败');
        }else{
            $tradeDB->commit();
            api_json(1, 200, '审核不通过，退款成功');
        }
    }
    /***
     * 获取用户信息（非管理员信息)
     */
    public function userInfo(){
        $userId = I('userId');
        if(!$userId){
            api_json(null,300,'操作的用户数据参数不足');
        }
        $user = M('user')->where('id = {$userId}')->find();
        if(!$user){
            api_json(null,100,'操作的用户数据不存在');
        }
        return $user;
    }
    /**
     * 获取充值列表
     */
    public function checkList(){
        $type = I('type',0,'int');
        switch($type){
            case 0:
                $where = "a.mode = 'recharge' and status >= 0";
                break;
            case 1:
                $where = "mode = 'recharge' and status = 0";
                break;
            case 2:
                $where = "mode = 'recharge' and status = 1";
                break;
            case 3:
                $where = "mode = 'recharge' and status = 2";
                break;
            default:
                $where = "";
                api_json(null,300,'type参数错误');
                break;
        }
        $data = M('trades a')->field('b.nickname,b.email,b.eth_address,b.mphone,a.*')->join('yt_users b on b.id = a.user_id')->where($where)->get();
        api_json(data,200,'获取成功');
    }

}