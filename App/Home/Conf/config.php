<?php
return array(
	//'配置项'=>'配置值'
    'THINK_EMAIL' => array(
        'SMTP_DRIVER' => 'smtp', //SMTP服务器
        'SMTP_HOST' => 'smtp.exmail.qq.com', //SMTP服务器

        'SMTP_PORT' => '25', //SMTP服务器端口

        'SMTP_USER' => 'noreply@cholding.com.cn', //SMTP服务器用户名

        'SMTP_PASS' => 'Chh@2016', //SMTP服务器密码

        'FROM_EMAIL' => 'noreply@cholding.com.cn',

        'FROM_NAME' => '长合汽车', //发件人名称

        'REPLY_EMAIL' => '', //回复EMAIL（留空则为发件人EMAIL）

        'REPLY_NAME' => '', //回复名称（留空则为发件人名称）

        'SESSION_EXPIRE'=>'72',
    ),
);