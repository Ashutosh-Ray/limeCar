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
 * Author: Alince
 * Date: 2015-09-09
 */

namespace home\Logic;

use think\Model\RelationModel;


/**
 * 文章模板逻辑
 * Class ArticleLogic
 * @package Home\Logic
 */

class ArticleLogic extends RelationModel
{

	public function getSiteArticle(){
		$syscate =  M('ArticleCat')->where("cat_type  = 1")->select();
		foreach($syscate as $v){
			$cats .= $v['cat_id'].',';
		}
		$cats = trim($cats,',');
		$result = M('Article')->where("cat_id","in",$cats)->select();
		foreach ($result as $val){
			$arr[$val['cat_id']][] = $val;
		}
		
		foreach ($syscate as $v){
			$v['article'] = $arr[$v['cat_id']];
			$brr[] = $v;
		}
		return $brr;
	}
	
	public function getArticleDetail($article_id){
		$article = '';
		return $article;
	}
}