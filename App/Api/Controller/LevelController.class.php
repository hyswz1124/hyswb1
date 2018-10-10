<?php
/**
 * Created by PhpStorm.
 * User: hys
 * Date: 2018/10/9
 * Time: 17:23
 */

namespace  Api\Controller;

class LevelController extends CommonController{

    protected $userInfo = '';


    public function __construct()
    {
        parent::__construct();
    }

    public function index(){
        $where['deleted'] = 0;
        $field = 'id, name, super_token, type';
        $data = M('level')->where($where)->field($field)->select();
        api_json($data, 200, '获取级别成功');
    }

    public function buyLevel(){
        $user = $this->checkLogin();
        $level = I('level_id', 0);
        $model = M('level');
        $data = $model->find($level);
        if(!$data){
            api_json($data, 400, ' 参数错误');
        }
        $model->startTrans();
        if($user['super_token'] < $data['super_token']){
            api_json('', 400, '积分不足,请兑换');
        }else{
            $trade = [
                'user_id' => $user['id'],
                'mode' => 'buylevel',
                'related_id' => $this->systemId,
                'message' => '用户购买'.$data['name'].'级别游戏',
                'status' => 1,
                'eth' => 0,
                'token' => $data['super_token'],
                'create_time' => date('Y-m-d H:i:s', time()),
                'update_time' => date('Y-m-d H:i:s', time()),
            ];
            $trade_id = M('trades')->add($trade);
            if(!$trade_id){
                $model->rollback();
                api_json('', 500, '购买失败，请重试');
            }
            $payment['trade_id'] = $trade_id;
            $payment['mode'] = 'buylevel';
            $payment['beamount'] = $user['eth'];
            $payment['afamount'] = $user['eth'];
            $payment['betoken'] = $user['super_token'] ;
            $payment['aftoken'] = $user['super_token'] - $data['super_token'];
            $payment['eth'] = 0;
            $payment['token'] = $data['super_token'];
            $payment['status'] = 1;
            $payment['create_time'] = date('Y-m-d H:i:s', time());
            $payment['update_time'] = date('Y-m-d H:i:s', time());
            $rspay = M('payments')->add($payment);
            if($rspay === false){
                $model->rollback();
                api_json('', 500, '购买失败，请重试');
            }
            $rs = M('users')->where('id='.$user['id'])->setDec('super_token',$data['super_token']);
            if($rs === false){
                $model->rollback();
                api_json('', 500, '购买失败，请重试');
            }
        }
        $model->commit();
        api_json(1, 200, '购买成功');
    }

    public function buySuperToken(){
        $user = $this->checkLogin();
        $super_token = I('super_token', 0);
        if($super_token <= 0 or !is_numeric($super_token)){
            api_json('', 400, '错误的参数');
        }
        //充值积分只允许有两位小数
        $xs = getFloatLength($super_token);
        if($xs > 2){
            api_json('', 400, '积分充值只允许有两位小数');
        }
        if($user['eth'] < $super_token){
            api_json('', 400, '余额不足，请先充值');
        }
        $model = M('users');
        $model->startTrans();
        $trade = [
            'user_id' => $user['id'],
            'mode' => 'buysupertoken',
            'related_id' => $this->systemId,
            'message' => '用户购买'.$super_token.'积分',
            'status' => 1,
            'eth' => $super_token,
            'token' => $super_token,
            'create_time' => date('Y-m-d H:i:s', time()),
            'update_time' => date('Y-m-d H:i:s', time()),
        ];
        $trade_id = M('trades')->add($trade);
        if(!$trade_id){
            $model->rollback();
            api_json('', 500, '购买失败，请重试');
        }
        $payment['trade_id'] = $trade_id;
        $payment['mode'] = 'buysupertoken';
        $payment['beamount'] = $user['eth'];
        $payment['afamount'] = $user['eth'] - $super_token;
        $payment['betoken'] = $user['super_token'] ;
        $payment['aftoken'] = $user['super_token'] + $super_token;
        $payment['eth'] = $super_token;
        $payment['token'] = $super_token;
        $payment['status'] = 1;
        $payment['create_time'] = date('Y-m-d H:i:s', time());
        $payment['update_time'] = date('Y-m-d H:i:s', time());
        $rspay = M('payments')->add($payment);
        if($rspay === false){
            $model->rollback();
            api_json('', 500, '购买失败，请重试');
        }
        $up['super_token'] = $user['super_token'] + $super_token;
        $up['eth'] = $user['eth'] - $super_token;
        $rs= $model->where('id='.$user['id'])->save($up);
        if($rs === false){
            $model->rollback();
            api_json('', 500, '购买失败，请重试');
        }
        $model->commit();
        api_json(1, 200, '购买成功');
    }


}