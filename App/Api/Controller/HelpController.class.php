<?php

namespace  Api\Controller;

class HelpController extends CommonController
{

    protected $userInfo = '';

    public function __construct()
    {
        parent::__construct();
    }

    public function index(){
        $data = M('help')->where('type=1')->getField('content', true);
        api_json($data, 200, '成功');
    }


    public function helpCenter(){

        $data = M('help')->where('type=2')->field('content,content_details')->select();
        foreach ($data as &$value){
            $value['content_details'] = base64_encode($value['content_details']);
        }
        api_json($data, 200, '成功');
    }
}