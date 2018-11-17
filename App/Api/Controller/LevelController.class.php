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
        $is_js = I('is_js', 0);
        $model = M('level');
        $data = $model->find($level);
        if(!$data){
            api_json('', 400, ' 参数错误');
        }
        if(!$user['is_js']){
            api_json('', 400, '需要先解锁游戏功能');
        }
        $model->startTrans();
        //是否大于2.5结束
        if($user['game_status']){
            if(!$is_js){
                api_json('', 421, '上一轮游戏已经结束，新一轮游戏需要花费参与游戏的当前等级的10%费用');
            }else{
                if($user['super_token'] < $data['super_token'] * 0.1){
                    api_json('', 400, '积分不足');
                }
                $trade = [
                    'user_id' => $user['id'],
                    'mode' => 'newlevel',
                    'related_id' => $this->systemId,
                    'message' => '用户结束新一轮'.$data['name'].'级别游戏',
                    'status' => 1,
                    'eth' => 0,
                    'token' => $data['super_token'] * 0.1,
                    'create_time' => date('Y-m-d H:i:s', time()),
                    'update_time' => date('Y-m-d H:i:s', time()),
                ];
                $trade_id = M('trades')->add($trade);
                if(!$trade_id){
                    $model->rollback();
                    api_json('', 500, '购买失败，请重试');
                }
                $payment['trade_id'] = $trade_id;
                $payment['mode'] = 'token';
                $payment['beamount'] = $user['eth'];
                $payment['afamount'] = $user['eth'];
                $payment['betoken'] = $user['super_token'] ;
                $payment['aftoken'] = $user['super_token'] - ($data['super_token'] * 0.1);
                $payment['eth'] = 0;
                $payment['token'] = $data['super_token'] * 0.1;
                $payment['status'] = 1;
                $payment['create_time'] = date('Y-m-d H:i:s', time());
                $payment['update_time'] = date('Y-m-d H:i:s', time());
                $rspay = M('payments')->add($payment);
                if($rspay === false){
                    $model->rollback();
                    api_json('', 500, '解锁失败，请重试');
                }
                $newup['game_number'] = $user['game_number'] + 1;
                $newup['game_status'] = 0;
                $newup['super_token'] = $user['super_token'] - $data['super_token'] * 0.1;
                $newrs = M('users')->where('id='.$user['id'])->save($newup);
                if($newrs === false){
                    $model->rollback();
                    api_json('', 500, '解锁失败，请重试');
                }
                $user = M('users')->find($user['id']);
            }
        }
        $isjl = false;
        $where_game['uid'] = $user['id'];
        $where_game['game_number'] = $user['game_number'];
        $is = M('game')->where($where_game)->order('id desc')->find();
        if($is){
            if($is['level_id'] > $level){
                api_json('', 400, '不能购买低级别游戏');
            }
            if($is['level_id'] < $level){
                if($user['one_superid']){
                    if($user['super_token'] < $data['super_token'] * 1.1){
                        api_json('', 400, '积分不足,请先兑换');
                    }
                    $isjl = true;
                }

            }
        }
        if($user['super_token'] < $data['super_token']){
            api_json('', 400, '积分不足,请先兑换');
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
            if($isjl){
                $trade['token'] = $data['super_token'] * 1.1;
            }
            $trade_id = M('trades')->add($trade);
            if(!$trade_id){
                $model->rollback();
                api_json('', 500, '购买失败，请重试');
            }
            $payment['trade_id'] = $trade_id;
            $payment['mode'] = 'token';
            $payment['beamount'] = $user['eth'];
            $payment['afamount'] = $user['eth'];
            $payment['betoken'] = $user['super_token'] ;
            $payment['aftoken'] = $user['super_token'] - $data['super_token'];
            $payment['eth'] = 0;
            $payment['token'] = $data['super_token'];
            if($isjl){
                $payment['aftoken'] = $user['super_token'] - ( $data['super_token'] * 1.1 );
                $payment['token'] = $data['super_token'] * 1.1;
            }
            $payment['status'] = 1;
            $payment['create_time'] = date('Y-m-d H:i:s', time());
            $payment['update_time'] = date('Y-m-d H:i:s', time());
            $rspay = M('payments')->add($payment);
            if($rspay === false){
                $model->rollback();
                api_json('', 500, '购买失败，请重试');
            }
            $userup['super_token'] = $user['super_token'] - $data['super_token'];
            $userup['all_token'] = $user['all_token'] + $data['super_token'];
            if($isjl){
                $userup['super_token'] = $user['super_token'] - ($data['super_token'] * 1.1);
                $userup['all_token'] = $user['all_token'] + ($data['super_token'] * 1.1);
            }
            //清空可支配收益
            $userup['govern_earnings'] = 0;
            $rs =  M('users')->where('id='.$user['id'])->save($userup);
            if($rs === false){
                $model->rollback();
                api_json('', 500, '购买失败，请重试');
            }
            //邀请人获取奖励
            if($isjl){
                $trade = [
                    'user_id' => $user['one_superid'],
                    'mode' => 'lastbuylevel',
                    'related_id' => $this->systemId,
                    'message' => '下级邀请人'.$user['id'].'购买'.$data['name'].'级别游戏获得奖励',
                    'status' => 1,
                    'eth' => 0,
                    'token' => $data['super_token'] * 0.1,
                    'create_time' => date('Y-m-d H:i:s', time()),
                    'update_time' => date('Y-m-d H:i:s', time()),
                ];
                $trade_id = M('trades')->add($trade);
                if(!$trade_id){
                    $model->rollback();
                    api_json('', 500, '购买失败，请重试');
                }
                $super = M('users')->find($user['one_superid']);
                $payment['trade_id'] = $trade_id;
                $payment['mode'] = 'token';
                $payment['beamount'] = $super['eth'];
                $payment['afamount'] = $super['eth'];
                $payment['betoken'] = $super['super_token'] ;
                $payment['aftoken'] = $super['super_token'] + ($data['super_token'] * 0.1);
                $payment['eth'] = 0;
                $payment['token'] = $data['super_token'] * 0.1;
                $payment['status'] = 1;
                $payment['create_time'] = date('Y-m-d H:i:s', time());
                $payment['update_time'] = date('Y-m-d H:i:s', time());
                $rspay = M('payments')->add($payment);
                if($rspay === false){
                    $model->rollback();
                    api_json('', 500, '购买失败，请重试');
                }
                $rsss = M('users')->where('id='.$user['one_superid'])->setInc('super_token',$data['super_token'] * 0.1);
                if($rsss === false){
                    $model->rollback();
                    api_json('', 500, '购买失败，请重试');
                }
            }

            $addganme['uid'] = $user['id'];
            $addganme['type'] = 0;
            $addganme['game_number'] = $user['game_number'];
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
        //计算本局收益
        $start_time = $data['start_time'];
        $times=strtotime(datetimenew())-strtotime($start_time);
        $timei=round($times/60/60);
        if($over){
            if($timei < 24){
                api_json('', 300, '游戏时长不足24小时，不允许结束');
            }
        }
        if($timei > 24){
            $timei = 24;
        }
        $level = M('level')->find($data['level_id']);
        $all = round($level['super_token'] * $level['earnings'] / 100 , 2);
        //不足半小时没有收益
//        if(!$timei){
//            $all = 0;
//        }
        $all = $level['super_token'] * $level['earnings'] / 100 * $timei / 24;
        $all = round($all, 2);
        $is_end = $level['super_token'] * 2.5;
        //累计游戏收益 大于等于2.5倍，强制结束游戏
        $is_dy = 0;
        if($all + $user['all_earnings'] >= $is_end){
            $all = $is_end;
            $over = 1;
            $is_dy = 1;
        }
        $model = M('game');
        $model->startTrans();
        //收益
        $up['all_earnings'] = $all;//收益
        $earnings = $all + $level['super_token'] + $user['frozen_earnings'];
        $up['govern_earnings'] = 0;//游戏中可支配收益为0
        $up['frozen_earnings'] = $earnings-$up['govern_earnings'];//不可支配收益 （收益+本金+上次不可支配资金）-可支配资金
        $up['update_time'] = datetimenew();
        $up['end_time'] = datetimenew();
        $up['type'] = 0;
//        没结束游戏时收益累加
//        $upuser['all_earnings'] = $user['all_earnings'] + $all;
//        $upuser['govern_earnings'] = $user['govern_earnings'] + $up['govern_earnings'];
//        $upuser['frozen_earnings'] = $user['frozen_earnings'] +$up['frozen_earnings'];
        if($over){
            //结束游戏，解锁可支配收益
//            $upuser['all_earnings'] = $user['all_earnings'] + $all;
            $up['govern_earnings'] = round($earnings * $level['random'] / 100, 2);//可支配收益 （收益+本金+上次不可支配资金）*随机数
            $up['frozen_earnings'] = $earnings-$up['govern_earnings'];//不可支配收益 （收益+本金+上次不可支配资金）-可支配资金
            $upuser['govern_earnings'] = $up['govern_earnings'];
            $upuser['frozen_earnings'] = $up['frozen_earnings'];
//            $upuser['is_js'] = 0;//关闭游戏功能，需再次解锁
            $upuser['super_token'] = $user['super_token'] + $up['govern_earnings'];
            $upuser['all_earnings'] = $user['all_earnings'] + $all;
            $supertoken = $up['govern_earnings'];//本次收益,结束游戏本次收益为可支配收益
            $up['type'] = 1;
            //收益大于等于2.5倍，解锁所有收益
            if($is_dy){
                $supertoken = $all;//本次收益为2.5倍
                $up['frozen_earnings'] = 0;
                $up['govern_earnings'] = 0;
                $upuser['all_earnings'] = 0;
                $upuser['govern_earnings'] = 0;
                $upuser['frozen_earnings'] = 0;
                $upuser['game_status'] = 1;
                $upuser['super_token'] = $user['super_token'] + $supertoken;
            }

            //记录收益
            $trade = [
                'user_id' => $user['id'],
                'mode' => 'gameover',
                'related_id' => $this->systemId,
                'message' => '游戏结束获得收益',
                'status' => 1,
                'eth' => 0,
                'token' => $supertoken,
                'create_time' => date('Y-m-d H:i:s', time()),
                'update_time' => date('Y-m-d H:i:s', time()),
            ];
            $trade_id = M('trades')->add($trade);
            if(!$trade_id){
                $model->rollback();
                api_json('', 500, '获取失败，请重试');
            }
            $payment['trade_id'] = $trade_id;
            $payment['mode'] = 'token';
            $payment['beamount'] = $user['eth'];
            $payment['afamount'] = $user['eth'];
            $payment['betoken'] = $user['super_token'] ;
            $payment['aftoken'] = $user['super_token'] + $supertoken;
            $payment['eth'] = 0;
            $payment['token'] = $supertoken;
            $payment['status'] = 1;
            $payment['create_time'] = date('Y-m-d H:i:s', time());
            $payment['update_time'] = date('Y-m-d H:i:s', time());
            $rspay = M('payments')->add($payment);
            if($rspay === false){
                $model->rollback();
                api_json('', 500, '获取失败，请重试');
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
        }

        $model->commit();
        api_json($up, 200, '成功');
    }


    /**
     * 统计
     */
    public function statistics()
    {
        $user = $this->checkLogin();
        $where['mode'] = 'gameover';
        $where['user_id'] = $user['id'];
        $where['status'] = 1;
        $month = date('m', time());
        for ($i=1; $i<=$month; $i++){
            $where['create_time'] = array('like', '%' . date('Y-m', strtotime(date('Y-', time()).$i)) . '%');
            //按月统计
            $yearArr[date('Y-m', strtotime(date('Y-', time()).$i))] =(double)M('trades')->where($where)->sum('token');
        }
        $retuen['year'] = $yearArr;
        $day = date('d', time());
        for ($i=1; $i<=$day; $i++){
            //按天统计
            $where['create_time'] = array('like', '%' . date('Y-m-d', strtotime(date('Y-m-', time()).$i)) . '%');
            $monthArr[date('Y-m-d', strtotime(date('Y-m-', time()).$i))] = (double)M('trades')->where($where)->sum('token');
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
            $dayArr[$i] = (double)M('trades')->where($where)->sum('token');
        }
        $retuen['day'] = $dayArr;


        api_json($retuen, 200, '统计数据');
    }



    public function buySuperToken(){
        return false;
        exit(1);
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