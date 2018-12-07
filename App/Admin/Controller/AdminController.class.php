<?php

namespace Admin\Controller;
use Think\Controller;

class AdminController extends CommonController
{
    public function __construct()
    {
        parent::__construct();
    }

    /*
     * 用户列表
     */
    public function userList(){
        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 20, 'intval');
        $name = trim(I('user', '', 'addslashes'));
        if ($name) {
            $where['mphone|email|eth_address'] = array('like', '%' . $name . '%');
        }
        $start_time = I('start_time', '', '');
        $end_time = I('end_time', '', '');
        if($start_time and !$end_time){
            $where['create_time'] = array('GT', $start_time);
        }
        if($end_time and !$start_time){
            $where['create_time'] = array('LT', $end_time);
        }
        if($start_time and $end_time){
            $where['create_time'] = array(array('GT', $start_time), array('LT', $end_time));
        }

        $where['deleted'] = 0;
        $where['status'] = 0;
        $field = 'id, mphone, email, eth_address, code, create_time';
        $list = M('users')
            ->where($where)
            ->field($field)
            ->order('create_time desc')
            ->limit($pageSize)->page($page)->select();
        $count =  M('users')->where($where)->count();
        if($list){
            foreach ($list as &$value){
                $value['num'] = M('users')->where('one_superid='.$value['id'])->count();
                $value['url'] = C('INVITEHOST').$value['id'];
            }
        }

        $pageList = array();
        $pageList['total'] = $count;
        $pageList['page'] = $page;
        $pageList['pageSize'] = $pageSize;

        $return['list'] = $list;
        $return['page'] = $pageList;
        api_json($return, 200, '用户列表');
    }

    /*
     * 修改密码
     */
    public function change_password(){
        $new = I('new_pwd', '', 'trim');
        $old = I('old_pwd', '', 'trim');

        if(!$new or !$old){
            api_json('', 400, '密码不能为空');
        }

        if($new == $old){
            api_json('', 400, '新密码不能跟原始密码一致');
        }

        if (strlen($new) < 6 || strlen($new) > 20) {
            echo api_json(null,'400','密码长度不合理');exit();
        }

        $user = M('users')->find($this->adminid);
        if(!$user){
            api_json('', 500, '登录失效，请重新登录');
        }
        $row=password_verify($old,$user['password']);
        if(!$row){
            api_json('', 400, '原始密码错误');
        }
        $data['password'] = password_hash($new, PASSWORD_DEFAULT);

        $result = M('users')->where('id='.$user['id'])->save(['password'=>password_hash($new, PASSWORD_DEFAULT)]);
        if($result === false){
            api_json('', 400, '修改失败，请重试');
        }
        api_json('', 200, '修改成功');
    }
}