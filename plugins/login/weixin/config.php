<?php

return array(
    'code'=> 'weixin',
    'name' => '微信登陆',
    'version' => '1.0',
    'author' => 'zyq',
    'desc' => '微信登陆插件 ',
    'icon' => 'logo.jpg',
    'config' => array(
        array('name' => 'app_id','label'=>'app_id','type' => 'text',   'value' => ''),
        array('name' => 'app_secret','label'=>'app_secret','type' => 'text',   'value' => '')
    )
);