<?php
/**
 * Created by PhpStorm.
 * User: hys
 * Date: 2018/10/1
 * Time: 16:21
 */

namespace Api\Controller;


class UserController extends CommonController
{
    protected $userInfo = '';

    public function __construct()
    {
        parent::__construct();
        $this->userInfo =  $this->checkLogin();
    }

    public function getUser(){
        api_json($this->userInfo, 200, '获取用户信息成功');
    }

}