<?php

namespace Admin\Controller;
use Think\Controller;



/**
 * 公共部分
 */
class CommonController extends Controller
{
    protected $adminid = 0;

    public function __construct()
    {
        parent::__construct();
//        if (!session('?adminid')){
//            api_json('', '109', '未登录或登录失效');
//        }
//        $this->adminid = session('adminid');
        $this->adminid = '1000';
    }
}