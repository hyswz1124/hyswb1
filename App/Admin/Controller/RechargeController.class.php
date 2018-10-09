<?php

namespace Admin\Controller;
use Think\Controller;

class RechargeController extends Controller {
    /**
     * 此方法程序为管理员给用户eth充值、积分充值,用户解锁
     * @author  wmt<1027918160@qq.com>
     * @date    2018-10-08
     */
    protected $userInfo = '';

    public function __construct()
    {
        parent::__construct();
    }
    /**
     *后台审核 充值 接口
     */
    public function check(){
        $user = $this->userInfo;
        $tradeId = I('tradeId');
        if(empty($tradeId)){
            api_json(null,'300','交易id不能为空');
        }
//        M('trades')->startTrans();
//        try {
            $settle = trade_settle($tradeId);
            if ($settle['status'] === 'ok') {
                M('trades')->commit();
                api_json(1,'200','操作成功');
            }else{
                api_json(null,'500','操作失败');
            }
//        } catch (\Exception $e) {
//            M('trades')->rollback();
//            api_json(null,'500',$e->getMessage());
//        }
    }

}