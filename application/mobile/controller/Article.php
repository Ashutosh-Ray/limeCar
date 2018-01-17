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
 * $Author: Alince 2015-08-10 $
 */ 
namespace app\mobile\controller;
class Article extends MobileBase {
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
        $list = M('Article')->where("cat_id IN(1,2,3,4,5,6,7)")->select();
        $this->assign('list',$list);
        return $this->fetch();
    }    
    /**
     * 文章内容页
     */
    public function article(){
    	$article_id = I('article_id/d',1);
    	$article = D('article')->where("article_id", $article_id)->find();
    	$this->assign('article',$article);
        return $this->fetch();
    }     
}