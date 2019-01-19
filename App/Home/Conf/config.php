<?php
return array(
	//'配置项'=>'配置值'
    'THINK_EMAIL' => array(
        'SMTP_DRIVER' => 'smtp', //SMTP服务器
        'SMTP_HOST' => 'smtp.exmail.qq.com', //SMTP服务器

        'SMTP_PORT' => '25', //SMTP服务器端口


        'REPLY_EMAIL' => '', //回复EMAIL（留空则为发件人EMAIL）

        'REPLY_NAME' => '', //回复名称（留空则为发件人名称）

        'SESSION_EXPIRE'=>'72',
    ),

    'TMPL_PARSE_STRING' => array(
        '__PUBLIC__'    => '/Public/web/', // 更改默认的/Public 替换规则
        '__JS__'        => '/Public/web/js/', // 增加新的JS类库路径替换规则
        '__CSS__'       => '/Public/web/css', // 增加新的css路径替换规则
        '__IMAGES__'    => '/Public/web/images/', // 增加新的images路径替换规则
        '__LAYER__'     => '/Public/web/layer/', // 增加新的images路径替换规则
        '__BOOTSTRAP__' => '/Public/web/bootstrap-3.3.5/',
    ),
);