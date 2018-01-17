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
 *   
 * Date: 2015-09-09
 */
namespace app\admin\controller;

use think\Page;
use think\Db;

class Credit extends Base {

//超级管理员查看商户
   public function index()
   {
       
       
       if(!empty($_GET['id']))
       {
           
           $aUserCredit = D('Credit')->where(array('id'=>intval($_GET['id'])))->find();
           
      
            $sToken = empty(cookie('access_token'))?$this->getJxlAccessToken() : cookie('access_token');
            $sSecret = '6053e5b164f840b1816c3c006dcc9827';
            
            $sName = $aUserCredit['name'];
            $sPhone = $aUserCredit['phone'];
            $sIdCard = $aUserCredit['id_card'];
            
            $sUrl = "https://www.juxinli.com/api/access_report_data?access_token=$sToken&name=$sName&phone=$sPhone&client_secret=$sSecret&idcard=$sIdCard";
            $sData = file_get_contents($sUrl);
            $aData = json_decode($sData,true);
            
            
            if($aData['success']==true && $sData!='{"note":"报告未生成或报告生成失败","success":"false"}')
            {
                $aInsertJxlData['credit_id'] = $aUserCredit['id'];
                $aInsertJxlData['data'] = $sData;
                $aInsertJxlData['create_time'] = time();
            
                D ( "Jxl_data" )->add($aInsertJxlData);
                D ( "Credit" )->where(array('id'=>$aUserCredit['id']))->save(array('jxl_status'=>1));
        }
           
       }
       
       
       
   	    $aWhere = array();
        $sKeyWords = I('get.key_word');
        $check_status = I('get.check_status');
        if ($sKeyWords) {
        $aWhere['shop_name'] = array('like',"%$sKeyWords%");
        }
        if (in_array($check_status,array('0','1','-1'))) {
           $aWhere['check_status'] = $check_status;
        }
        
   	    $iCount = D('Credit')->where($aWhere)->count();
   	    $oPage = new Page($iCount,10);// 实例化分页类 传入总记录数和每页显示的记录数
        $sPage = $oPage->show();
        $aData = M('Credit')->where($aWhere)->limit($page->firstRow.','.$page->listRows)->select();
        $this->assign('list',$aData);
        
        
    	$this->assign('page',$sPage);

        return $this->fetch('index');
   }

   public function add()
   {
       
       if(!empty(I('post.name')) && !empty(I('post.id_card'))&& !empty(I('post.phone')))
       {
           $aInsert['name'] = I('post.name');
           $aInsert['id_card'] = I('post.id_card');
           $aInsert['phone'] = I('post.phone');
           $aInsert['create_time'] = time();
           
           M('Credit')->add($aInsert);
       }
       
       return $this->fetch('add');
   }
   
   
   public function jxtReport()
   {
       $aJxlData = D ( "Jxl_data" )->where(array('credit_id'=>$_GET['id']))->order('id desc')->find();
       $aJxlData = json_decode($aJxlData['data'],true);
       foreach ($aJxlData[report_data][contact_list] as $one)
       {
       
           $iKey = $one['call_cnt'].rand(100000,999999);
       
           if(!empty( $aJxlData[report_data][contact_list2][$iKey]))
           {
               $iKey = $one['call_cnt'].rand(100000,999999);
           }
       
           $aJxlData[report_data][contact_list2][$iKey] = $one;
       }
       krsort($aJxlData[report_data][contact_list2]);
       
       
       //判断是否有联系人
       if ($aJxlData[report_data][application_check][5]&&$aJxlData[report_data][application_check][6]) {
           $this->assign('contactTrue',1);
       }
       
       
       $this->assign('aCustomer',$aCustomer);
       $this->assign('aJxlData',$aJxlData);
       
       return $this->fetch('jxt_data');
       
       
   }
      
    private function getJxlAccessToken()
    {
        $aSeting = array('org_name'=>'zxzhihe','client_secret'=>'6053e5b164f840b1816c3c006dcc9827','hours'=>1);
        $sUrl = "https://www.juxinli.com/api/v2/access_report_token";
        $aData = $this->getData($sUrl,$aSeting);
        $aData = json_decode($aData[1],true);
    
        cookie('access_token',$aData['access_token'],300);
        
        $sToken = $aData['access_token'];
       
        return $sToken;
    
    }
    
    private function getData($url,$data)
    {
        $url = $url.'?'.http_build_query($data);
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_CUSTOMREQUEST,'GET');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8'
        ));
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close ( $ch );
        return array($httpCode, $response);
    }
      
      
      
}