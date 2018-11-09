<?php

namespace Admin\Controller;
use Think\Controller;

class BonusPoolController extends CommonController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(){

        $data = M('bonus_pool')->where('status=0')->select();
        foreach ($data as $v){
            if($v['type'] == 1){
                $return['fh_pool'] = (double)$v['eth'];
                $return['jd_pool'] = round($return['fh_pool']  * 0.9, 2);
                $return['kf_pool'] = $return['fh_pool'] - $return['jd_pool'];
            }
            if($v['type'] == 2){
                $return['fz_pool'] = (double)$v['eth'];
            }
            if($v['type'] == 3){
                $return['kt_pool'] = (double)$v['eth'];
            }
            if($v['type'] == 4){
                $return['sq_pool'] = (double)$v['eth'];
            }
            if($v['type'] == 5){
                $return['hg_pool'] = (double)$v['eth'];
            }
        }
        api_json($return, 200 ,'成功');
    }
}