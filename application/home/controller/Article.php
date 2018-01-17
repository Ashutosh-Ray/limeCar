<?php
/**
 * ThinkPHP [ WE CAN DO IT JUST THINK ]
 +----------------------------------------------------------------------
 * * Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
 * 
 +----------------------------------------------------------------------
 * 
 * 
 +----------------------------------------------------------------------
 * $Author: IT宇宙人 2015-08-10 $
 */
namespace app\home\controller;
use app\home\logic\CartLogic;
use app\home\logic\GoodsLogic;
use think\AjaxPage;
use think\Controller;
use think\Url;
use think\Config;
use think\Page;
use think\Verify;
use think\Db;

class Article extends Base {
    
    public function index(){       
        $article_id = I('article_id/d',38);
    	$article = D('article')->where("article_id", $article_id)->find();
    	$this->assign('article',$article);
        return $this->fetch();
    }
 
    /**
     * 文章内列表页
     */
    public function articleList(){
        $article_cat = M('ArticleCat')->where("parent_id  = 0")->select();
        $this->assign('article_cat',$article_cat);
        return $this->fetch();
    }    
    /**
     * 文章内容页
     */
    public function detail(){
    	$article_id = I('article_id/d',1);
    	$article = D('article')->where("article_id", $article_id)->find();
    	if($article){
    		$parent = D('article_cat')->where("cat_id",$article['cat_id'])->find();
    		$this->assign('cat_name',$parent['cat_name']);
    		$this->assign('article',$article);
    	}
        return $this->fetch();
    } 
   
}