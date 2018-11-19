<?php

namespace Api\Controller;
use Common\Model\GoogleAuthenticatorModel;
use Think\Controller;

class RechargeController extends CommonController {
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
        $type = I('type',1,'int');
        if($user['is_js']){
            api_json('', 600, '该功能已经解锁');
        }
        $eth = 0.02;
        if($user['eth'] < $eth){
            api_json(null,'600','账户ETH钱包余额不足');
        }
        $model = M('trades');
        $model->startTrans();
            $trade = [
                'user_id' => $user['id'],
                'mode' => 'unlock',
                'related_id' => $this->systemId,
                'message' => ($type == 1)?'用户解锁邀请码':'用户解锁游戏',
                'status' => 1,
                'eth' => $eth
            ];
            $trade_id = M('trades')->add($trade);
            $payment = [
                'trade_id' => $trade_id,
                'mode' => 'eth',
                'eth' => $eth,
                'beamount' => $user['eth'],
                'afamount' => $user['eth'] + $eth,
                'status' => 1
            ];
           $payment_id = M('payments')->add($payment);
            if ($trade_id && $payment_id) {
                $this->superCheck($user['id'],$eth);
                if(!$user['code']){
                    $code = $this->initcode();
                    $rs = M('users')->where('id='.$user['id'])->save(['is_js'=>1,'code'=> $code,'eth'=>$user['eth']-$eth,'update_time' => date('Y-m-d H:i:s', time())]);
                }else{
                    $code = $user['code'];
                    $rs = M('users')->where('id='.$user['id'])->save(['is_js'=>1,'eth'=>$user['eth']-$eth,'update_time' => date('Y-m-d H:i:s', time())]);
                }
                if($rs === false){
                    $model->rollback();
                    api_json(null,'500','解锁失败');
                }
                $model->commit();
                api_json($code,'200','解锁成功');
            }else{
                $model->rollback();
                api_json(null,'500','解锁失败');
            }
    }

    /**
     * 推荐人获利
     */
    public function superCheck($userId,$eth){
        $eth = round($eth, 4);
        $one_superId = M('users')->where('id='.$userId)->getField('one_superid');
        if(!$one_superId){
            return false;
        }
        $one_super = M('users')->find($one_superId);
        if(!$one_super){
            return false;
        }
        $tradeSuper = [
            'user_id' => $one_superId,
            'mode' => 'income_unlock',
            'related_id' => $this->systemId,
            'message' => '推荐用户解锁获利',
            'status' => 1,
            'eth' => $eth
        ];
        $trade_id_super = M('trades')->add($tradeSuper);
        $paymentSuper = [
            'trade_id' => $trade_id_super,
            'mode' => 'finances',
            'eth' => $eth,
            'beamount' => $one_super['eth'],
            'afamount' => $one_super['eth'] + $eth,
            'status' => 1
        ];
        $payment_id_super = M('payments')->add($paymentSuper);
        if($trade_id_super && $payment_id_super){
            M('users')->where('id='.$one_superId)->save(['eth'=>$one_super['eth']+$eth,'invite_earnings'=>$one_super['invite_earnings']+$eth,'update_time' => date('Y-m-d H:i:s', time())]);
        }
        return true;

    }

    /**
     * 用户提现
     */
    public function cash(){
        $user = $this->userInfo;
        if($user['is_freeze']){
            api_json('', 600, '账号资金被冻结，不允许提现');
        }
        $eth = I('eth');
        $eth = round($eth, 4);
        $code= I('code');
        if(!$eth){
            api_json(null,'300','提现eth额度不能为空');
        }
        if(!$code){
            api_json(null,'400','验证码不能为空');
        }
        $googleAuthenticator = new GoogleAuthenticatorModel();
        if(!$user['secret']){
            api_json(null,'400','未绑定谷歌验证');
        }
//        $oneCode = $googleAuthenticator->getCode($secret['secret']);
        $checkResult = $googleAuthenticator->verifyCode($user['secret'], $code, 2);    // 2 = 2*30sec clock tolerance
        if (!$checkResult) {
            api_json(null,'400','验证码错误');
        }
        if($user['eth'] < $eth){
            api_json(null,'600','账户ETH钱包余额不足');
        }
        if(!$user['eth_address']){
            api_json(null,'600','ETH地址为空,请先填写eth地址');
        }
        M('trades')->startTrans();
        $trade = [
            'user_id' => $user['id'],
            'mode' => 'cash',
            'related_id' => $this->systemId,
            'message' => '用户提现',
            'status' => 0,
            'eth' => $eth
        ];
        $trade_id = M('trades')->add($trade);
        $payment = [
            'trade_id' => $trade_id,
            'mode' => 'eth',
            'eth' => $eth,
            'beamount' => $user['eth'],
            'afamount' => $user['eth'] - $eth,
            'status' => 1
        ];
        $payment_id = M('payments')->add($payment);
        $result = M('users')->where('id='.$user['id'])->setDec('eth',$eth);
        if ($trade_id && $payment_id && $result) {
            M('trades')->commit();
            api_json(1,'200','提现提交成功');
        }else{
            M('trades')->rollback();
            api_json(null,'500','网络故障');
        }

    }


    /**
     * 用户解锁游戏
     */
//    public function ungame(){
//        $user = $this->userInfo;
//        if($user['is_jsgame']){
//            api_json('', 600, '该功能已经解锁');
//        }
//        $eth = 0.02;
//        if($user['eth'] < $eth){
//            api_json(null,'600','账户ETH钱包余额不足');
//        }
//        $model = M('trades');
//        $model->startTrans();
//            $trade = [
//                'user_id' => $user['id'],
//                'mode' => 'unlock',
//                'related_id' => $this->systemId,
//                'message' => '用户解锁游戏',
//                'status' => 1,
//                'eth' => $eth
//            ];
//            $trade_id = M('trades')->add($trade);
//            $payment = [
//                'trade_id' => $trade_id,
//                'mode' => 'balance',
//                'eth' => $eth,
//                'beamount' => $user['eth'],
//                'afamount' => $user['eth'] + $eth,
//                'status' => 1
//            ];
//           $payment_id = M('payments')->add($payment);
//            if ($trade_id && $payment_id) {
//                $rs = M('users')->where('id='.$user['id'])->save(['is_jsgame'=>1, 'eth'=>$user['eth']-$eth,'update_time' => date('Y-m-d H:i:s', time())]);
//                if($rs === false){
//                    $model->rollback();
//                    api_json(null,'500','解锁失败');
//                }
//                $model->commit();
//                api_json(1,'200','解锁成功');
//            }else{
//                $model->rollback();
//                api_json(null,'500','解锁失败');
//            }
//    }


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
            if($_FILES){
                $root_directory = './Public/';
                $subdirectory = 'Uploads/img/';
                $img = $this->upload($root_directory,$subdirectory);
                if(!$img || $img['status'] == 'no'){
                    api_json(null,'500',$img['data']);
                }
                $trade['photo'] = $img['data'];
            }
 //充值改为直接通过
            $trade_id = M('trades')->add($trade);
          if($trade_id){
              $tradeId = $trade_id;
              $settle = trade_settle($tradeId);
              if ($settle['status'] === 'ok') {
//                  $logTitle = '管理员:' . $this->adminid . '同意充值(id:'.$tradeId.')';
//                  D('AdminLog')->save_log($this->adminid, '充值审核', $logTitle, 'trades', $tradeId);
                  api_json(1,'200','操作成功');
              }else{
                  api_json(null,'500','操作失败');
              }
          }else{
              api_json(null,'500','交易入库失败，请重试');
          }

            $trade_id = M('trades')->add($trade);
//            if ($trade_id) {
//                api_json(1,'200','充值提交成功');
//            }else{
//                api_json(null,'500','网络故障');
//            }

//        } catch (\Exception $e) {
//            M('trades')->rollback();
//            api_json(null,'500',$e->getMessage());
//        }
    }


    public function ktjl(){
        $user = $this->userInfo;
        if(!$user['is_kt']){
            api_json('', 400, '充值ETH 大于0.1才可以享受空投奖励');
        }
        $rs = airdrop_reward($user['id']);
        if(!$rs){
            api_json('', 400, '未知的错误');
        }
        api_json('', 200, '已获得空投奖励');
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
    /**
     * 用户领取列表 （分红，节点奖励，空投奖励，推荐人获利）
     */
    public function draw_list(){
        $user = $this->userInfo;
        $type = I('type',0,'int');
        $page = I('page', 1, 'int');
        $limit = I('pageSize', 20, 'int');
        $where['a.user_id'] = $user['id'];
        $where['a.status'] = 1;
        $filed = 'a.id,a.mode,a.message,a.eth,a.token,a.create_time,b.beamount,b.afamount,b.betoken,b.aftoken';
        $checked = false;
        switch($type){
            case 0:
//                $where['a.mode'] = 'income_deal';
                break;
            case 1:
                $where['a.mode'] = 'income_airdrop_reward';
                break;
            case 2:
                $where['a.mode'] = 'income_node_reward';
                break;
            case 3:
                $checked = true;
//                $where['a.mode'] = 'income_user_recommender_one or income_user_recommender_two';
                break;
            case 4:
                $where['a.mode'] = 'income_deal';
                break;
            case 5:
                $where['a.mode'] = 'unlock';
                break;
            case 6:
                $where['a.mode'] = 'cash';
                break;
            default:
                api_json(null,300,'type参数错误');
                break;
        }
        $sql = m('trades a')->join('yt_payments b on b.trade_id = a.id')
            ->field($filed)
            ->where($where);
        if($checked){
            $sql = $sql->where("a.mode = 'income_user_recommender_one' or a.mode = 'income_user_recommender_two'");
        }
//        api_json(m('trades a')->_sql(),200,'获取成功');
        $data = $sql->where(("a.mode = 'income_airdrop_reward' or a.mode = 'income_node_reward' or a.mode = 'income_user_recommender_one' or a.mode = 'income_user_recommender_two'"))->limit($limit*($page-1), $limit)->order("a.id desc")->select();
        if($checked){
            $count = m('trades a')->join('yt_payments b on b.trade_id = a.id')
                ->where($where)->where(("a.mode = 'income_airdrop_reward' or a.mode = 'income_node_reward' or a.mode = 'income_user_recommender_one' or a.mode = 'income_user_recommender_two'"))->where("a.mode = 'income_user_recommender_one' or a.mode = 'income_user_recommender_two'")->count();
        }else{
            $count = m('trades a')->join('yt_payments b on b.trade_id = a.id')
                ->where($where)->where(("a.mode = 'income_airdrop_reward' or a.mode = 'income_node_reward' or a.mode = 'income_user_recommender_one' or a.mode = 'income_user_recommender_two'"))->count();
        }
       $result = [];
        foreach($data as $k=>$v){
            $result[$k]['id'] = $v['id'];
            $result[$k]['message'] = $v['message'];
            $result[$k]['create_time'] = $v['create_time'];
            $mode = $v['mode'];
            switch($mode){
                case 'income_airdrop_reward':
                    $result[$k]['type'] = 1;
                    break;
                case 'income_node_reward':
                    $result[$k]['type'] = 2;
                    break;
                case 'income_user_recommender_one':
                    $result[$k]['type'] = 3;
                    break;
                case 'income_user_recommender_two':
                    $result[$k]['type'] = 3;
                    break;
            }
            if($mode == 'income_airdrop_reward' || $mode == 'income_node_reward' || $mode == 'income_deal' || $mode == 'unlock'
                || $mode == 'cash' || $mode == 'recharge' || $mode == 'buyers_deal' || $mode == 'income_unlock' || $mode == 'income_user_recommender_one' || $mode == 'income_user_recommender_two'){
                $result[$k]['amount'] = $v['eth'].'eth';
                if($v['afamount'] >= $v['beamount']){
                    $result[$k]['s_amount'] = '+'.$v['eth'].'eth';
                }else{
                    $result[$k]['s_amount'] = '-'.$v['eth'].'eth';
                }
            }elseif($mode == 'buylevel' || $mode == 'cancel_list_deal' || $mode == 'lastbuylevel' || $mode == 'gameover'
                || $mode == 'income_buyers_deal_reward' || $mode == 'income_buyers_deal_token' || $mode == 'buyers_deal'){
                $result[$k]['amount'] = $v['token'].'token';
                if($v['aftoken'] >= $v['betoken']){
                    $result[$k]['s_amount'] = '+'.$v['token'].'token';
                }else{
                    $result[$k]['s_amount'] = '-'.$v['token'].'token';
                }
            }

        }
        api_json(array('data'=>$result,'count'=>$count?$count:0),200,'获取成功');
    }

}