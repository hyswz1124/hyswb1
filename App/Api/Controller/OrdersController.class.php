<?php
namespace Api\Controller;

use Api\Controller\CommonController;
use Think\Controller;

class OrdersController extends CommonController
{
    protected $userInfo = '';

    public function __construct()
    {
        parent::__construct();
        $this->userInfo =  $this->checkLogin();
    }
    public function index()
    {
        var_dump(222);
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
        $model = M('orders');
        $model->startTrans();
        $order = [
            'user_id' => $user['id'],
            'order_no'=>generate_order_no(),
            'related_id' => $this->systemId,
            'message' => '用户挂单',
            'status' => 0,
            'token'=>$super_token,
            'eth' => $eth
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
                'eth' => $eth
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
        $result = M('users')->where('id',$user['id'])->setDec('super_token',$super_token);
        if(!$trade_id || !$payment_id || !$order_id || !$result){
            $model->rollback();
            api_json(null,'600','交易入库失败');
        }
        $model->commit();
        api_json(1,'200','挂单成功');
    }

    /**
     * 用户取消挂单
     * author:wmt
     * date:2018-10-11
     */
    public function cancel(){
        $user = $this->userInfo;
        $order_no = I('order_no');
        if(!$order_no){
            api_json(null,'300','参数不足');
        }
        $old_order = M('orders')->where('order_no',$order_no)->where('status = 0')->find();
        if(!$old_order){
            api_json(null,'100','没有此挂单记录');
        }
        if($user['is_freeze']){
            api_json(null, 600, '账号资金被冻结，不允许操作挂单');
        }
        $old_trade = M('trades')->where('order_no',$old_order['order_no'])->where('user_id',$user['id'])->where('status = 1')->find();
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
        $result = M('users')->where('id',$user['id'])->setInc('super_token',$old_trade['token']);
        $res = M('orders')->where('order_no',$order_no)->save(['status'=>2,'update_time'=>date('Y-m-d H:i:s',time())]);
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
        $where = [
            'trades.mode'=>'list_deal',
            'orders.status'=>0
        ];
        $page = I('page',1,'int');
        $limit = min(30, I('limit',10,'int'));
        $data = M('trades')->field('users.nickname,users.mphone,users.email,orders.order_no,orders.eth,orders.token,orders.create_time')->join('users on users.user_id = trades.user_id')->join('orders on trades.order_no = orders.order_no')->where($where)->limit($limit*($page-1), $limit)->order("orders.id desc")->select();
        api_json($data,'200','获取数据成功');
    }

}