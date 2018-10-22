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
            api_json('', 400, ' 参数错误');
        }
        if(!$user['is_jsgame']){
            api_json('', 400, '需要先解锁游戏功能');
        }
        $model->startTrans();
        if($user['super_token'] < $data['super_token']){
            api_json('', 400, '积分不足,请兑换');
        }else{
            $is_game_where['uid'] = $user['id'];
            $is_game_where['type'] = 0;
            $is_game = M('game')->where($is_game_where)->find();

            if($is_game){
                api_json('', 400, '存在进行中的游戏，不允许购买');
            }

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
            $rss = M('users')->where('id='.$user['id'])->setInc('all_token',$data['super_token']);
            if($rs === false){
                $model->rollback();
                api_json('', 500, '购买失败，请重试');
            }
            if($rss === false){
                $model->rollback();
                api_json('', 500, '购买失败，请重试');
            }
            $addganme['uid'] = $user['id'];
            $addganme['type'] = 0;
            $addganme['level_id'] = $level;
            $addganme['start_time'] = datetimenew();
            $addganme['create_time'] = datetimenew();

            $ganme = M('game')->add($addganme);
            if($ganme === false){
                $model->rollback();
                api_json('', 500, '购买失败，请重试');
            }
        }
        $model->commit();
        api_json($ganme, 200, '购买成功');
    }


    public function game(){
        $game_id = I('game_id');
        $over = I('over',0);
        $user = $this->checkLogin();
        $where['uid'] = $user['id'];
        $where['id'] = $game_id;
        $data = M('game')->where($where)->find();
        if(!$data){
            api_json('', 404, '错误的参数');
        }
        if($data['type']){
            api_json('', 400, '游戏已结束');
        }
        //计算收益
        $start_time = $data['start_time'];
        $times=strtotime(datetimenew())-strtotime($start_time);
        $timei=round($times/60/60);
        $level = M('level')->find($data['level_id']);
        $all = $level['super_token'] * 1 / 100 * $timei / 24;
        $is_end = $level['super_token'] * 2.5;
        //收益大于等于2.5倍，强制结束游戏
        if($all >= $is_end){
            $all = $is_end;
            $over = 1;
        }
        $model = M('game');
        $model->startTrans();
        //更新收益
        $all = round($all, 2);
        $up['all_earnings'] = $all;
        $up['govern_earnings'] = round($all*0.2, 2);
        $up['frozen_earnings'] = $all-$up['govern_earnings'];
        $up['update_time'] = datetimenew();
        $up['type'] = 0;
        $upuser['all_earnings'] = $user['all_earnings'] + $all;
        $upuser['govern_earnings'] = $up['govern_earnings'];
        $upuser['frozen_earnings'] = $up['frozen_earnings'];
        if($over){
            //强制结束游戏，解锁所有收益
            $upuser['all_earnings'] = $user['all_earnings'] + $all;
            $upuser['govern_earnings'] = 0;
            $upuser['frozen_earnings'] = 0;
            $upuser['is_jsgame'] = 0;//关闭游戏功能，需再次解锁
            $upuser['super_token'] = $user['super_token'] + $all;

            $up['type'] = 1;
            $up['govern_earnings'] = 0;
            $up['frozen_earnings'] = 0;

            //记录收益
            $trade = [
                'user_id' => $user['id'],
                'mode' => 'gameover',
                'related_id' => $this->systemId,
                'message' => '游戏结束获得收益',
                'status' => 1,
                'eth' => 0,
                'token' => $all,
                'create_time' => date('Y-m-d H:i:s', time()),
                'update_time' => date('Y-m-d H:i:s', time()),
            ];
            $trade_id = M('trades')->add($trade);
            if(!$trade_id){
                $model->rollback();
                api_json('', 500, '获取失败，请重试');
            }
            $payment['trade_id'] = $trade_id;
            $payment['mode'] = 'gameover';
            $payment['beamount'] = $user['eth'];
            $payment['afamount'] = $user['eth'];
            $payment['betoken'] = $user['super_token'] ;
            $payment['aftoken'] = $user['super_token'] + $all;
            $payment['eth'] = 0;
            $payment['token'] = $all;
            $payment['status'] = 1;
            $payment['create_time'] = date('Y-m-d H:i:s', time());
            $payment['update_time'] = date('Y-m-d H:i:s', time());
            $rspay = M('payments')->add($payment);
            if($rspay === false){
                $model->rollback();
                api_json('', 500, '获取失败，请重试');
            }
        }
        $rss = M('users')->where('id='.$user['id'])->save($upuser);
        if($rss === false){
            $model->rollback();
            api_json('', 500, '获取失败，请重试');
        }
        $rs = $model->where($where)->save($up);
        if($rs === false){
            $model->rollback();
            api_json('', 500, '获取失败，请重试');
        }
        $model->commit();
        api_json($up, 200, '成功');
    }



    public function buySuperToken(){
        $user = $this->checkLogin();
        if($user['is_freeze']){
            api_json('', 100, '账号资金被冻结，不允许交易');
        }
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