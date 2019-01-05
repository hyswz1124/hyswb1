<?php
namespace Api\Controller;

use Api\Controller\CommonController;
use Think\Controller;

class OrdersController extends CommonController
{
    protected $userInfo = '';
    public static $total_token = 1000000000;

    public function __construct()
    {
        parent::__construct();
        $this->userInfo =  $this->checkLogin();
    }

    /**
     * 统计
     */
    public function statistics()
    {
        $user = $this->userInfo;
        $where['mode'] = array('in', array('list_deal', 'cancel_list_deal'));
        $where['user_id'] = $user['id'];
        $where['status'] = 1;
        $month = date('m', time());
        for ($i=1; $i<=$month; $i++){
            $where['create_time'] = array('like', '%' . date('Y-m', strtotime(date('Y-', time()).$i)) . '%');
            //按月统计
            $yearArr[date('Y-m', strtotime(date('Y-', time()).$i))] = M('trades')->where($where)->count();
        }
        $retuen['year'] = $yearArr;
        $day = date('d', time());
        for ($i=1; $i<=$day; $i++){
            //按天统计
            $where['create_time'] = array('like', '%' . date('Y-m-d', strtotime(date('Y-m-', time()).$i)) . '%');
            $monthArr[date('Y-m-d', strtotime(date('Y-m-', time()).$i))] = M('trades')->where($where)->count();
        }
        $retuen['month'] = $monthArr;

        $hour = date('H', time());
        for ($i=0; $i<=$hour; $i++){
            if($i < 10){
                $where['create_time'] = array('like', '%' . date('Y-m-d ', time()).'0'.$i . '%');
            }else{
                $where['create_time'] = array('like', '%' . date('Y-m-d ', time()).$i . '%');
            }
            //当天按小时统计
            $dayArr[$i] = M('trades')->where($where)->count();
        }
        $retuen['day'] = $dayArr;


        api_json($retuen, 200, '统计数据');
    }

    /**
     * 用户生成挂单
     * author:wmt
     * date:2018-10-11
     */
    public function create(){
        $user = $this->userInfo;
        $eth = I('eth');
        $super_token  = I('super_token');
        if($user['is_freeze']){
            api_json(null, 600, '账号资金被冻结，不允许挂单');
        }
        if(empty($eth) || empty($super_token)){
            api_json(null,'300','积分或eth不能为空');
        }
        if($user['super_token'] < $super_token){
            api_json(null,'600','用户积分不足');
        }
        $total_Token = total_token_count();
        if($total_Token > self::$total_token){
            api_json(null,'600','系统总积分已超上限，不容许挂单');
        }
        $model = M('orders');
        $model->startTrans();
        $order = [
            'user_id' => $user['id'],
            'order_no'=>generate_order_no(),
            'related_id' => $this->systemId,
            'message' => '用户挂单',
            'status' => 0,
            'token'=>$super_token,
            'eth' => $eth,
        ];
        $order_id = $model->add($order);
            $trade = [
                'user_id' => $user['id'],
                'mode' => 'list_deal',
                'order_no'=>$order['order_no'],
                'related_id' => $this->systemId,
                'message' => '用户挂单',
                'status' => 1,
                'token'=>$super_token,
                'eth' => $eth,
            ];
            $trade_id = M('trades')->add($trade);
        if(!$trade_id){
            $model->rollback();
            api_json(null,'600','交易入库失败');
        }
            $payment = [
                'trade_id' => $trade_id,
                'mode' => 'token',
                'token' => $super_token,
                'betoken' => $user['super_token'],
                'aftoken' => $user['super_token'] - $super_token,
                'status' => 1
            ];
         $payment_id = M('payments')->add($payment);
        $result = M('users')->where('id='.$user['id'])->setDec('super_token',$super_token);
        if(!$trade_id || !$payment_id || !$order_id || !$result){
            $model->rollback();
            api_json(null,'600','交易入库失败');
        }
        $model->commit();
        $data = [
            'nickname'=>$user['nickname'],
            'mphone'=>$user['mphone'],
            'email'=>$user['email'],
            'order_no'=>$order['order_no'],
            'eth'=>$order['eth'],
            'token'=>$order['token']
        ];
        api_json($data,'200','挂单成功');
    }

    /**
     * 用户取消挂单
     * author:wmt
     * date:2018-10-11
     */
    public function cancel(){
        $user = $this->userInfo;
        $order_no = I('order_no');
//        var_dump($order_no);exit();
        if(!$order_no){
            api_json(null,'300','参数不足');
        }
        $old_order = M('orders')->where("order_no = '{$order_no}' and status = 0")->find();
        if(!$old_order){
            api_json(null,'100','没有此挂单记录');
        }
        if($user['is_freeze']){
            api_json(null, 600, '账号资金被冻结，不允许操作挂单');
        }
        $old_trade = M('trades')->where("order_no = '{$old_order['order_no']}' and user_id = {$user['id']} and status = 1")->find();

        if(!$old_trade){
            api_json(null,'100','没有此挂单交易记录');
        }
        $model = M('trades');
        $model->startTrans();
        $trade = [
            'user_id' => $user['id'],
            'mode' => 'cancel_list_deal',
            'order_no'=>$old_trade['order_no'],
            'related_id' => $this->systemId,
            'message' => '用户取消订单号为'.$old_trade['order_no'].'的挂单',
            'status' => 1,
            'token'=>$old_trade['token'],
            'eth' => $old_trade['eth']
        ];
        $trade_id = M('trades')->add($trade);
        if(!$trade_id){
            $model->rollback();
            api_json(null,'600','交易入库失败');
        }

        $payment = [
            'trade_id' => $trade_id,
            'mode' => 'token',
            'token' => $old_trade['token'],
            'betoken' => $user['super_token'],
            'aftoken' => $user['super_token'] + $old_trade['token'],
            'status' => 1
        ];
        $payment_id = M('payments')->add($payment);
        $result = M('users')->where('id='.$user['id'])->setInc('super_token',$old_trade['token']);
        $res = M('orders')->where('order_no='.$order_no)->save(['status'=>2,'update_time'=>date('Y-m-d H:i:s',time())]);
        if(!$trade_id || !$payment_id || !$res || !$result){
            $model->rollback();
            api_json(null,'600','交易入库失败');
        }
        $model->commit();
        api_json(1,'200','取消挂单成功');
    }
    /**
     * 挂单列表
     * audthor:wmt
     * date:2018-10-12
     */
    public function deal_list(){
        $user = $this->userInfo;
        $type = I('type',1,'int');
        $where = [
            'a.mode'=>'list_deal',
            'c.status'=>0
        ];
        if($type == 2){
            $where['a.user_id'] = $user['id'];
        }
        $page = I('page',1,'int');
        $limit = min(30, I('limit',10,'int'));
        $data = M('trades a')->field('b.nickname,b.mphone,b.email,c.order_no,c.eth,c.token,c.create_time')->join('yt_users b on b.id = a.user_id')->join('yt_orders c on a.order_no = c.order_no')->where($where)->limit($limit*($page-1), $limit)->order("c.id desc")->select();
        $count = M('trades a')->field('b.nickname,b.mphone,b.email,c.order_no,c.eth,c.token,c.create_time')->join('yt_users b on b.id = a.user_id')->join('yt_orders c on a.order_no = c.order_no')->where($where)->count();
        api_json(array('res'=>$data,'count'=>$count),'200','获取数据成功');
    }
    /**
     * 用户购买挂单
     * audthor:wmt
     * date:2018-10-15
     */
    public function buy_deal(){
        $user = $this->userInfo;
        $order_no = I('order_no');
        if(!$order_no){
            api_json(null,300,'订单编号不能为空');
        }
        $order = M('orders')->where('order_no='.$order_no)->find();
        if(!$order){
            api_json(null,100,'该挂单不存在');
        }
        if($user['id'] == $order['user_id']){
            api_json(null,100,'不能购买自己的挂单');
        }
        if($order['status']){
            api_json(null,100,'该挂单已被交易或者已取消');
        }
        $sellerUser = M('users')->where('id='.$order['user_id'])->find();
        if(!$sellerUser){
            api_json(null,100,'该挂单不存在');
        }
        if($sellerUser['is_freeze']){
            api_json(null, 600, '卖方账户被冻结，此挂单不可交易');
        }
        if($user['eth'] < $order['eth']){
            api_json(null, 600, '余额不足，请充值');
        }
        $settle = order_settle($order,$user);
        if ($settle['status'] === 'ok') {
//            M('trades')->commit();
            api_json(1,'200','购买成功');
        }else{
            api_json(null,'500','网络错误');
        }

    }


}