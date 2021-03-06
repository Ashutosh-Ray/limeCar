<?php

/**
 * ThinkPHP [ WE CAN DO IT JUST THINK ]
 +----------------------------------------------------------------------
 * Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
 * 
 +----------------------------------------------------------------------
 * 
 * 
 +----------------------------------------------------------------------
 * Author: 当燃
 * Date: 2015-09-09
 */

namespace app\admin\controller;
use app\admin\logic\UpgradeLogic;
use think\Controller;
use think\Db;
use think\response\Json;
use think\Session;
class Base extends Controller {

    /**
     * 析构函数
     */
    function __construct() 
    {
        Session::start();
        header("Cache-control: private");  // history.back返回后输入框值丢失问题 参考文章 http://www.imshop.com/article_id_1465.html  http://blog.csdn.net/qinchaoguang123456/article/details/29852881
        parent::__construct();
        $upgradeLogic = new UpgradeLogic();
        $upgradeMsg = $upgradeLogic->checkVersion(); //升级包消息        
        $this->assign('upgradeMsg',$upgradeMsg);    
        //用户中心面包屑导航
        $navigate_admin = navigate_admin();
        $this->assign('navigate_admin',$navigate_admin);
        tpversion();        
   }    
    
    /*
     * 初始化操作
     */
    public function _initialize() 
    {
        //过滤不需要登陆的行为
        if(in_array(ACTION_NAME,array('login','logout','vertify')) || in_array(CONTROLLER_NAME,array('Ueditor','Uploadify'))){
        	//return;
        }else{
        	if(session('admin_id') > 0 ){
        		$this->check_priv();//检查管理员菜单操作权限
        	}else{
        		$this->error('请先登陆',U('Admin/Admin/login'),1);
        	}
        }
        $admin_info = getAdminInfo(session('admin_id'));
        $this->assign('admin_info',$admin_info);
        $type = session('type');
        $this->assign('admin_type',$type);
        $this->public_assign();
    }
    
    /**
     * 保存公告变量到 smarty中 比如 导航 
     */
    public function public_assign()
    {
       $imshop_config = array();
       $tp_config = M('config')->cache(true)->select();
       foreach($tp_config as $k => $v)
       {
          $imshop_config[$v['inc_type'].'_'.$v['name']] = $v['value'];
       }
       $this->assign('imshop_config', $imshop_config);       
    }
    
    public function check_priv()
    {
    	$ctl = CONTROLLER_NAME;
    	$act = ACTION_NAME;
        $act_list = session('act_list');
        
		//无需验证的操作
		$uneed_check = array('login','logout','vertifyHandle','vertify','imageUp','upload','login_task');
    	if($ctl == 'Index' || $act_list == 'all'){
    		//后台首页控制器无需验证,超级管理员无需验证
    		return true;
    	}elseif(strpos($act,'ajax') || in_array($act,$uneed_check)){
    		//所有ajax请求不需要验证权限
    		return true;
    	}else{
    		$right = M('system_menu')->where("id", "in", $act_list)->cache(true)->getField('right',true);

    		foreach ($right as $val){
    			$role_right .= $val.',';
    		}
    		$role_right = explode(',', $role_right);
    		//检查是否拥有此操作权限
    		if(!in_array($ctl.'Controller@'.$act, $role_right)){
    			$this->error('您没有操作权限,请联系超级管理员分配权限',U('Admin/Index/welcome'));
    		}
    	}
    }
    
    public function ajaxReturn($data,$type = 'json'){                        
            exit(json_encode($data));
    }    
}