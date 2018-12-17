<?php
/**
 * Created by PhpStorm.
 * User: hys
 * Date: 2018/12/17
 * Time: 09:49
 */

use Think\Controller;

class InviteController extends Controller
{

    public function index(){
        $id = I('id');
        if($id){
            $data = M('users')->find($id);
            $this->assign('data',$data);
        }
        $this->display();
    }

}