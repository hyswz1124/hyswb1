<?php

namespace Admin\Controller;
use Think\Controller;

class OrderController extends CommonController {
    /**
     * 此方法程序为管理员挂单 以及市场挂单记录
     */
    protected $adminInfo = 1;
    public static $total_token = 1000000000;

    public function __construct()
    {
        parent::__construct();
    }
    /**
     * 市场挂单
     */
    /**
     * 市场挂单列表
     * audthor:wmt
     * date:2018-10-12
     */
    public function market_deal_list(){
//        $user = $this->userInfo;
        $where['mode'] = array('in', array('list_deal', 'cancel_list_deal'));
        $where['status'] = 1;
        $month = date('m', time());
        for ($i=1; $i<=$month; $i++){
            $where['create_time'] = array('like', '%' . date('Y-m', strtotime(date('Y-', time()).$i)) . '%');
            //按月统计
            $yearArr[date('Y-m', strtotime(date('Y-', time()).$i))] = empty(M('trades')->where($where)->sum('token'))?0:M('trades')->where($where)->sum('token');
        }
        $retuen['year'] = $yearArr;
        $day = date('d', time());
        for ($i=1; $i<=$day; $i++){
            //按天统计
            $where['create_time'] = array('like', '%' . date('Y-m-d', strtotime(date('Y-m-', time()).$i)) . '%');
            $monthArr[date('Y-m-d', strtotime(date('Y-m-', time()).$i))] = empty(M('trades')->where($where)->sum('token'))?0:M('trades')->where($where)->sum('token');
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
            $dayArr[$i] =empty(M('trades')->where($where)->sum('token'))?0:M('trades')->where($where)->sum('token');
        }
        $retuen['day'] = $dayArr;

        $listwhere = [
            'a.mode'=>'list_deal',
            'c.status'=>0
        ];
        $page = I('page',1,'int');
        $limit = min(30, I('limit',10,'int'));
        $data = M('trades a')->field('b.nickname,b.mphone,b.email,c.id,c.order_no,c.eth,c.token,c.create_time')->join('yt_users b on b.id = a.user_id')->join('yt_orders c on a.order_no = c.order_no')->where($listwhere)->limit($limit*($page-1), $limit)->order("c.id desc")->select();
        $count = M('trades a')->field('b.nickname,b.mphone,b.email,c.order_no,c.eth,c.token,c.create_time')->join('yt_users b on b.id = a.user_id')->join('yt_orders c on a.order_no = c.order_no')->where($listwhere)->count();
        api_json(array('trend'=>$retuen,'res'=>$data,'count'=>empty($count)?0:$count),'200','获取数据成功');
    }

    /**
     * 历史挂单记录列表
     * audthor:wmt
     * date:2018-10-12
     */
    public function history_deal_list(){
        //        $user = $this->userInfo;
        $where = [
            'a.mode'=>'list_deal',
            'c.status'=>1
        ];
        $name = trim(I('name', '', 'addslashes'));
        if ($name) {
            $where['b.mphone|b.email|b.eth_address'] = array('like', '%' . $name . '%');
        }
        $start_time = I('start_time', '', '');
        $end_time = I('end_time', '', '');
        if($start_time and !$end_time){
            $where['c.create_time'] = array('GT', $start_time);
        }
        if($end_time and !$start_time){
            $where['c.create_time'] = array('LT', $end_time);
        }
        if($start_time and $end_time){
            $where['c.create_time'] = array(array('GT', $start_time), array('LT', $end_time));
        }
        $page = I('page',1,'int');
        $limit = min(30, I('limit',10,'int'));
        $data = M('trades a')->field('b.nickname,b.mphone,b.email,c.id,c.order_no,c.eth,c.token,c.create_time,c.related_id')->join('yt_users b on b.id = a.user_id')->join('yt_orders c on a.order_no = c.order_no')->where($where)->limit($limit*($page-1), $limit)->order("c.id desc")->select();
        $count = M('trades a')->field('b.nickname,b.mphone,b.email,c.order_no,c.eth,c.token,c.create_time')->join('yt_users b on b.id = a.user_id')->join('yt_orders c on a.order_no = c.order_no')->where($where)->count();
        if($data){
            foreach($data as $k=>$v){
                $data[$k]['buyer_nickname'] = $data[$k]['buyer_mphone'] = $data[$k]['buyer_email'] = '';
                if($v['related_id']){
                    $buyer = M('users')->field('nickname,mphone,email')->where("id = {$v['related_id']}")->find();
                    if($buyer){
                        $data[$k]['buyer_nickname'] = $buyer['nickname'];
                        $data[$k]['buyer_mphone'] = $buyer['mphone'];
                        $data[$k]['buyer_email'] = $buyer['email'];
                    }
                }
            }
        }
        api_json(array('res'=>$data,'count'=>empty($count)?0:$count),'200','获取数据成功');

    }

    /**
     * 管理员挂单接口
     */
    /**
     * 用户生成挂单
     * author:wmt
     * date:2018-10-11
     */
    public function admin_list(){
        $user =  M('users')->where("id = {$this->adminInfo}")->find();
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
            'message' => '系统管理员挂单',
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
            'message' => '系统管理员挂单',
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
            'eth'=>$order['token'],
            'token'=>$order['token']
        ];
        api_json($data,'200','挂单成功');
    }
    /***
     * 获取用户信息（非管理员信息)
     */
    public function userInfo(){
        $userId = I('userId');
        if(!$userId){
            api_json(null,300,'操作的用户数据参数不足');
        }
        $user = M('users')->where("id = {$userId}")->find();
        if(!$user){
            api_json(null,100,'操作的用户数据不存在');
        }
        return $user;
    }


}