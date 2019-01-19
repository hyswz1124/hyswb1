<?php
namespace Admin\Controller;
use Think\Controller;

class UserController extends CommonController
{
    public $uid = 0;
    public $userInfo = '';
    public $page = 0;
    public $pageSize = 0;

   public function __construct()
   {
       parent::__construct();
       $this->uid = I('uid');
       if(!$this->uid){
           api_json('', 400, '缺少用户 id');
       }
       $this->userInfo = M('users')->find($this->uid);
       if(!$this->userInfo){
           api_json('', 400, '用户id错误');
       }

       $this->page = I('page', 1, 'intval');
       $this->pageSize = I('pageSize', 20, 'intval');
   }


   /*
    * 用户金库
    */
   public function ethList(){
       $user['eth'] = $this->userInfo['eth'];
       $user['all_earnings'] = $this->userInfo['all_earnings'];
       $user['govern_earnings'] = $this->userInfo['all_earnings'];
       $user['frozen_earnings'] = $this->userInfo['frozen_earnings'];

       $where['t.status'] = 1;
       $where['p.mode'] = 'eth';
       $where['t.user_id'] = $this->userInfo['id'];
       $list = M('Trades as t')
           ->join('__PAYMENTS__ as p on p.trade_id = t.id')
           ->where($where)->field('p.*')->order('t.create_time desc')->limit($this->pageSize)->page($this->page)->select();
       $count = M('Trades as t')
           ->join('__PAYMENTS__ as p on p.trade_id = t.id')
           ->where($where)->count();
       $return = array();
       $return['user'] = $user;
       if($list){
           foreach ($list as $value){
               $add['mphone'] = $this->userInfo['mphone'];
               $add['email'] = $this->userInfo['email'];
               $add['eth_address'] = $this->userInfo['eth_address'];
               $add['num'] = round($value['afamount'] - $value['beamount'] , 4);
               $add['time'] = $value['create_time'];
               $return['list'][] = $add;
           }
       }
       $pageList = array();
       $pageList['total'] = $count;
       $pageList['page'] = $this->page;
       $pageList['pageSize'] = $this->pageSize;

       $return['page'] = $pageList;
       api_json($return, 200, '用户金库数据');
   }

   /*
    * 用户积分
    */
   public function tokenList(){
       $user['super_token'] = $this->userInfo['super_token'];
       $user['dividend_earnings'] = $this->userInfo['dividend_earnings'];
       $user['node_earnings'] = $this->userInfo['node_earnings'];
       $user['paradrop_earnings'] = $this->userInfo['paradrop_earnings'];

       $where['t.status'] = 1;
       $where['p.mode'] = 'token';
       $where['t.user_id'] = $this->userInfo['id'];
       $list = M('Trades as t')
           ->join('__PAYMENTS__ as p on p.trade_id = t.id')
           ->where($where)->field('p.*')->order('t.create_time desc')->limit($this->pageSize)->page($this->page)->select();
       $count = M('Trades as t')
           ->join('__PAYMENTS__ as p on p.trade_id = t.id')
           ->where($where)->count();
       $return = array();
       $return['user'] = $user;
       if($list){
           foreach ($list as $value){
               $add['mphone'] = $this->userInfo['mphone'];
               $add['email'] = $this->userInfo['email'];
               $add['eth_address'] = $this->userInfo['eth_address'];
               $add['num'] = round($value['aftoken'] - $value['betoken'] , 2);
               $add['time'] = $value['create_time'];
               $return['list'][] = $add;
           }
       }
       $pageList = array();
       $pageList['total'] = $count;
       $pageList['page'] = $this->page;
       $pageList['pageSize'] = $this->pageSize;

       $return['page'] = $pageList;
       api_json($return, 200, '用户积分数据');
   }

   /*
    * 提现记录
    */
   public function withdrawList(){
       $where['t.mode'] = 'cash';
       $where['p.mode'] = 'eth';
       $where['t.user_id'] = $this->userInfo['id'];
       $list = M('Trades as t')
           ->join('__PAYMENTS__ as p on p.trade_id = t.id')
           ->where($where)->field('p.*,t.status as txstatus')->order('t.create_time desc')->limit($this->pageSize)->page($this->page)->select();
       $count = M('Trades as t')
           ->join('__PAYMENTS__ as p on p.trade_id = t.id')
           ->where($where)->count();
       $return = array();

       if($list){
           foreach ($list as $value){
               $add['mphone'] = $this->userInfo['mphone'];
               $add['email'] = $this->userInfo['email'];
               $add['eth_address'] = $this->userInfo['eth_address'];
               $add['allnum'] = round($value['beamount'] , 2);
               $add['num'] = round($value['eth'] , 2);
               $add['status'] = $value['txstatus'];
               $add['time'] = $value['create_time'];
               $return['list'][] = $add;
           }
       }
       $pageList = array();
       $pageList['total'] = $count;
       $pageList['page'] = $this->page;
       $pageList['pageSize'] = $this->pageSize;

       $return['page'] = $pageList;
       api_json($return, 200, '用户提现数据');
   }

   /*
    * 充值记录
    */
   public function rechargeList(){
       $where['mode'] = 'recharge';
       $where['user_id'] = $this->userInfo['id'];
       $list = M('Trades')
           ->where($where)->field('id, eth, photo, status, create_time')->order('create_time desc')->limit($this->pageSize)->page($this->page)->select();
       $count = M('Trades')
           ->where($where)->count();
       $return = array();

       if($list){
           foreach ($list as $value){
               $add['mphone'] = $this->userInfo['mphone'];
               $add['email'] = $this->userInfo['email'];
               $add['eth_address'] = $this->userInfo['eth_address'];
               $add['num'] = round($value['eth'] , 2);
               $add['status'] = $value['status'];
               if($value['photo']){
                   $add['photo'] = C('APPHOST').$value['photo'];
               }else{
                   $add['photo'] = '';
               }
               $add['time'] = $value['create_time'];
               $return['list'][] = $add;
           }
       }
       $pageList = array();
       $pageList['total'] = $count;
       $pageList['page'] = $this->page;
       $pageList['pageSize'] = $this->pageSize;

       $return['page'] = $pageList;
       api_json($return, 200, '用户充值数据');
   }

   /*
    * 交易记录
    */
    public function ordersList(){
        $where = [
            'mode'=>'list_deal',
            'user_id'=>$this->userInfo['id'],
        ];
        $list = M('Trades')
            ->where($where)
            ->field('id, order_no, eth, token, status, create_time')
            ->order('create_time desc')->limit($this->pageSize)->page($this->page)->select();
        $count = M('Trades')
            ->where($where)->count();
        $return = array();

        if($list){
            foreach ($list as $value){
                $add['mphone'] = $this->userInfo['mphone'];
                $add['email'] = $this->userInfo['email'];
                $add['eth_address'] = $this->userInfo['eth_address'];
                $add['token_num'] = round($value['token'] , 2);
                $add['eth_num'] = round($value['eth'] , 4);
                $add['status'] = $value['status'];
                if($value['status'] == 1){
                    $where_m['mode'] = 'buyers_deal';
                    $where_m['order_no'] = $value['order_no'];
                    $user_id = M('Trades')->where($where_m)->field('user_id')->find();
                    $user_m = M('users')->where('id='.$user_id['user_id'])->find();
                    if($user_m){
                       $add['buy_mphone'] = $user_m['mphone'];
                       $add['buy_email'] = $user_m['email'];
                       $add['buy_eth_address'] = $user_m['eth_address'];
                    }
                }
                unset($add['status']);
                $add['time'] = $value['create_time'];
                $return['list'][] = $add;
            }
        }
        $pageList = array();
        $pageList['total'] = $count;
        $pageList['page'] = $this->page;
        $pageList['pageSize'] = $this->pageSize;

        $return['page'] = $pageList;
        api_json($return, 200, '用户充值数据');
    }

    /*
     * 邀请记录
     */
    public function inviteList(){
        $user['invite_earnings'] = $this->userInfo['invite_earnings'];

        $where['one_superid'] = $this->userInfo['id'];
        $list = M('users')
            ->where($where)
            ->field('id, mphone, email, eth_address, create_time')
            ->order('create_time desc')->limit($this->pageSize)->page($this->page)->select();
        $count = M('users')
            ->where($where)->count();
        $return = array();
        $return['user'] = $user;
        if($list){
            foreach ($list as $value){
                $add['mphone'] = $this->userInfo['mphone'];
                $add['email'] = $this->userInfo['email'];
                $add['eth_address'] = $this->userInfo['eth_address'];
                $add['invite_mphone'] = $value['mphone'];
                $add['invite_email'] = $value['email'];
                $add['invite_eth_address'] = $value['eth_address'];
                $is = M('users')->where('one_superid='.$value['id'])->find();
                if($is){
                    $add['next_mphone'] = $is['mphone'];
                    $add['next_email'] = $is['email'];
                    $add['next_eth_address'] = $is['eth_address'];
                }else{
                    $add['next_mphone'] = '';
                    $add['next_email'] = '';
                    $add['next_eth_address'] = '';
                }
                $add['time'] = $value['create_time'];
                $return['list'][] = $add;
            }
        }
        $pageList = array();
        $pageList['total'] = $count;
        $pageList['page'] = $this->page;
        $pageList['pageSize'] = $this->pageSize;

        $return['page'] = $pageList;
        api_json($return, 200, '用户邀请数据');
    }
}