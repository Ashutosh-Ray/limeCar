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
namespace app\admin\controller;
use app\admin\logic\GoodsLogic;
use think\AjaxPage;
use think\Page;
use think\Db;

class Goods extends Base {
    
    /**
     *  商品分类列表
     */
    public function categoryList(){  
       
        $GoodsLogic = new GoodsLogic(); 

        if ($_SESSION['type']==0)
         {
            $cat_list = $GoodsLogic->goods_cat_list();
         }else{
            $cat_list = $GoodsLogic->goods_cat_list("shop_id=".$_SESSION['admin_id']);
         }              
        
        $this->assign('cat_list',$cat_list);  
        // var_dump($cat_list);die();   
        return $this->fetch();
    } 
     /**
     * 添加修改商品分类 
     * 手动拷贝分类正则 ([\u4e00-\u9fa5/\w]+)  ('393','$1'), 
     * select * from tp_goods_category where id = 393
        select * from tp_goods_category where parent_id = 393
        update tp_goods_category  set parent_id_path = concat_ws('_','0_76_393',id),`level` = 3 where parent_id = 393
        insert into `tp_goods_category` (`parent_id`,`name`) values 
        ('393','时尚饰品'),
     */
    public function addEditCategory(){
      
            $GoodsLogic = new GoodsLogic();        
            if(IS_GET)
            {
                $where = array();
                $where['id'] = I('GET.id',0);
                // if($_SESSION['type']==1) $where['shop_id'] = $_SESSION['admin_id'];
                $goods_category_info = D('GoodsCategory')->where($where)->find();
                $level_cat = $GoodsLogic->find_parent_cat($goods_category_info['id']); // 获取分类默认选中的下拉框
                unset($where['id']);
                $where['parent_id']=0;
                $cat_list = M('goods_category')->where($where)->select(); // 已经改成联动菜单                
                $this->assign('level_cat',$level_cat);                
                $this->assign('cat_list',$cat_list);                 
                $this->assign('goods_category_info',$goods_category_info);      
                return $this->fetch('_category');
                exit;
            }

            $GoodsCategory = D('GoodsCategory'); //

            $type = I('id') > 0 ? 2 : 1; // 标识自动验证时的 场景 1 表示插入 2 表示更新                        
            //ajax提交验证
            if(I('is_ajax') == 1)
            {

                // 数据验证            
                $validate = \think\Loader::validate('GoodsCategory');
                if(!$validate->batch()->check(input('post.')))
                {                        
                    $error = $validate->getError();
                    $error_msg = array_values($error);
                    $return_arr = array(
                        'status' => -1,
                        'msg' => $error_msg[0],
                        'data' => $error,
                    );
                    $this->ajaxReturn($return_arr);
                } else {
                    $data = input('post.');
                    // if ($_SESSION['type']==1) $data['shop_id'] = $_SESSION['admin_id'];
                    $GoodsCategory->data($data,true); // 收集数据
                
                    $GoodsCategory->parent_id = I('parent_id_1');
                    input('parent_id_2') && ($GoodsCategory->parent_id = input('parent_id_2'));
                    //编辑判断
                    if($type == 2){
                        $children_where = array(
                            'parent_id_path'=>array('like','%_'.I('id')."_%")
                        );
                        // if($_SESSION['type']==1) $children_where['shop_id'] = $_SESSION['admin_id'];
                        $children = M('goods_category')->where($children_where)->max('level');
                        if (I('parent_id_1')) {
                            $map = array();
                            $map['id'] = I('parent_id_1');
                            // if($_SESSION['type']==1) $map['shop_id']=$_SESSION['admin_id'];
                            $parent_level = M('goods_category')->where($map)->getField('level', false);
                            if (($parent_level + $children) > 3) {
                                $return_arr = array(
                                    'status' => -1,
                                    'msg'   => '商品分类最多为三级',
                                    'data'  => '',
                                );
                                $this->ajaxReturn($return_arr);
                            }
                        }
                        if (I('parent_id_2')) {
                            $map = array();
                            $map['id'] = I('parent_id_2');
                            // if($_SESSION['type']==1) $map['shop_id']=$_SESSION['admin_id'];
                            $parent_level = M('goods_category')->where($map)->getField('level', false);
                            if (($parent_level + $children) > 3) {
                                $return_arr = array(
                                    'status' => -1,
                                    'msg'   => '商品分类最多为三级',
                                    'data'  => '',
                                );
                                $this->ajaxReturn($return_arr);
                            }
                        }
                    }
                    
                    if($GoodsCategory->id > 0 && $GoodsCategory->parent_id == $GoodsCategory->id)
                    {
                        //  编辑
                        $return_arr = array(
                            'status' => -1,
                            'msg'   => '上级分类不能为自己',
                            'data'  => '',
                        );
                        $this->ajaxReturn($return_arr);                        
                    }
                    if($GoodsCategory->commission_rate > 100)
                    {
                        //  编辑
                        $return_arr = array(
                            'status' => -1,
                            'msg'   => '分佣比例不得超过100%',
                            'data'  => '',
                        );
                        $this->ajaxReturn($return_arr);                        
                    }   
                   
                    if ($type == 2)
                    {
                        $GoodsCategory->isUpdate(true)->save(); // 写入数据到数据库
                        $GoodsLogic->refresh_cat(I('id'));
                    }
                    else
                    {
                        $GoodsCategory->save(); // 写入数据到数据库
                        $insert_id = $GoodsCategory->getLastInsID();
                        $GoodsLogic->refresh_cat($insert_id);
                    }
                    $return_arr = array(
                        'status' => 1,
                        'msg'   => '操作成功',
                        'data'  => array('url'=>U('Admin/Goods/categoryList')),
                    );
                    $this->ajaxReturn($return_arr);
                }  
            }
    }
    
    /**
     * 获取商品分类 的帅选规格 复选框
     */
    public function ajaxGetSpecList(){
        $GoodsLogic = new GoodsLogic();
        $_REQUEST['category_id'] = $_REQUEST['category_id'] ? $_REQUEST['category_id'] : 0;
        $filter_spec = M('GoodsCategory')->where("id = ".$_REQUEST['category_id'])->getField('filter_spec');        
        $filter_spec_arr = explode(',',$filter_spec);        
        $str = $GoodsLogic->GetSpecCheckboxList($_REQUEST['type_id'],$filter_spec_arr);  
        $str = $str ? $str : '没有可帅选的商品规格';
        exit($str);        
    }
 
    /**
     * 获取商品分类 的帅选属性 复选框
     */
    public function ajaxGetAttrList(){
        $GoodsLogic = new GoodsLogic();
        $_REQUEST['category_id'] = $_REQUEST['category_id'] ? $_REQUEST['category_id'] : 0;
        $filter_attr = M('GoodsCategory')->where("id = ".$_REQUEST['category_id'])->getField('filter_attr');        
        $filter_attr_arr = explode(',',$filter_attr);        
        $str = $GoodsLogic->GetAttrCheckboxList($_REQUEST['type_id'],$filter_attr_arr);          
        $str = $str ? $str : '没有可帅选的商品属性';
        exit($str);        
    }    
    
    /**
     * 删除分类
     */
    public function delGoodsCategory(){
        $id = $this->request->param('id');
        // 判断子分类
        $GoodsCategory = M("goods_category");
        $count = $GoodsCategory->where("parent_id = {$id}")->count("id");
        $count > 0 && $this->error('该分类下还有分类不得删除!',U('Admin/Goods/categoryList'));
        // 判断是否存在商品
        $goods_count = M('Goods')->where("cat_id = {$id}")->count('1');
        $goods_count > 0 && $this->error('该分类下有商品不得删除!',U('Admin/Goods/categoryList'));
        // 删除分类
        DB::name('goods_category')->where('id',$id)->delete();
        $this->success("操作成功!!!",U('Admin/Goods/categoryList'));
    }
    
    
    /**
     *  商品列表
     */
    public function goodsList(){      
        $GoodsLogic = new GoodsLogic();
    
         // if ($_SESSION['type']==0)
         // {
            $brandList = $GoodsLogic->getSortBrands();
            $categoryList = $GoodsLogic->getSortCategory();
         // }else{
         //    $brandList = $GoodsLogic->getSortBrands("shop_id=".$_SESSION['admin_id']);
         //    $categoryList = $GoodsLogic->getSortCategory("shop_id=".$_SESSION['admin_id']);
          
         // } 
        
        if ($_SESSION['type']==0){
            $businessList = M('admin')->where('type=1')->select();
            $this->assign('businessList',$businessList);
         } 
        $cat_id = I('cat_id');
        $cat_id_arr = getCatGrandson($cat_id);
        $where['id'] = array('in',implode(',', $cat_id_arr));
         
        $categoryList = M('GoodsCategory')->where($where)->select();

        $this->assign('categoryList',$categoryList);
        $this->assign('brandList',$brandList);
        $this->assign('parentCates',$parentCates);
        $this->assign('cat_id',$cat_id);

        return $this->fetch();
    }
    
    /**
     *  商品列表
     */
    public function ajaxGoodsList(){            
        
        $where = ' 1 = 1 '; // 搜索条件                
        I('intro')    && $where = "$where and ".I('intro')." = 1" ;        
        I('brand_id') && $where = "$where and g.brand_id = ".I('brand_id') ;
        $where = "$where and g.del_status = 0" ;
        (I('is_on_sale') !== '') && $where = "$where and g.is_on_sale = ".I('is_on_sale') ; 
        $shop_id = I('shop_id'); 
        if($shop_id!='')  $where = "$where and g.shop_id = ".$shop_id ;  
       
        $cat_id = I('cat_id');
        // 关键词搜索               
        $key_word = I('key_word') ? trim(I('key_word')) : '';
        if($key_word)
        {
            $where = "$where and (g.goods_name like '%$key_word%' or g.goods_sn like '%$key_word%')" ;
        }
        
        if($cat_id > 0)
        {
            $grandson_ids = getCatGrandson($cat_id); 
            $where .= " and g.cat_id in(".  implode(',', $grandson_ids).") "; // 初始化搜索条件
        }
        
        if ($_SESSION['type']==0)
         {
          
         }else{
            $where = $where.' AND shop_id='.$_SESSION['admin_id'];
          
         } 
        
        $model = M('Goods');
        $count = Db::table('tb_goods')->alias('g')->join('admin a','g.shop_id = a.admin_id','LEFT')->where($where)->count();
        $Page  = new AjaxPage($count,10);


        /**  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            $Page->parameter[$key]   =   urlencode($val);
        }
        */
         
        $show = $Page->show();
        $order_str = "`{$_POST['orderby1']}` {$_POST['orderby2']}";

        $goodsList=Db::table('tb_goods')->alias('g')->join('admin a','g.shop_id = a.admin_id','LEFT')->where($where)->order($order_str)->limit($Page->firstRow.','.$Page->listRows)->select();
       
        // $goodsList = $model->where($where)->order($order_str)->limit($Page->firstRow.','.$Page->listRows)->select();
       
        $catList = D('goods_category')->select();
        $catList = convert_arr_key($catList, 'id');
        $this->assign('catList',$catList);
        $this->assign('goodsList',$goodsList);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('shop_type',$_SESSION['type']);
        $this->assign('cat_id',$cat_id);

        return $this->fetch();
    }


    /**
     * 添加修改商品
     */
    public function addEditGoods()
    {   

        $cat_id = I('get.cat_id');        
        $GoodsLogic = new GoodsLogic();
        $Goods = new \app\admin\model\Goods(); //
        $type = I('goods_id') > 0 ? 2 : 1; // 标识自动验证时的 场景 1 表示插入 2 表示更新
        //ajax提交验证
        if ((I('is_ajax') == 1) && IS_POST) {
          
            // 数据验证            
            // $validate = \think\Loader::validate('Goods');
            // if(!$validate->batch()->check(input('post.')))
            // {                          
            //     $error = $validate->getError();
            //     $error_msg = array_values($error);
            //     $return_arr = array(
            //         'status' => -1,
            //         'msg' => $error_msg[0],
            //         'data' => $error,
            //     );
            //     $this->ajaxReturn($return_arr);
            // } else {

                // $data = input('post.');
                $data = I('post.');

                // var_dump(I('post.'));die();
                $data['goods_images'] = array_filter($data['goods_images']);
                $data['op_id'] = $_SESSION['admin_id'];//操作员
                if ($_SESSION['type']==1) $data['shop_id'] = $_SESSION['admin_id'];
                $Goods->data($data,true); // 收集数据
         
                $Goods->on_time = time(); // 上架时间

                //$Goods->cat_id = $_POST['cat_id_1'];
                I('cat_id_2') && ($Goods->cat_id = I('cat_id_2'));
                I('cat_id_3') && ($Goods->cat_id = I('cat_id_3'));

                I('extend_cat_id_2') && ($Goods->extend_cat_id = I('extend_cat_id_2'));
                I('extend_cat_id_3') && ($Goods->extend_cat_id = I('extend_cat_id_3'));
                $Goods->shipping_area_ids = implode(',',I('shipping_area_ids/a',[]));
                $Goods->shipping_area_ids = $Goods->shipping_area_ids ? $Goods->shipping_area_ids : '';
                $Goods->goods_type = I('goods_type');
                $Goods->spec_type = $Goods->goods_type; 

                if ($type == 2) {
                    $goods_id = I('goods_id');
                    $Goods->edit_time = time();//添加时间
                    $Goods->isUpdate(true)->save(); // 写入数据到数据库                 
                    // 修改商品后购物车的商品价格也修改一下
                    M('cart')->where("goods_id = $goods_id and spec_key = ''")->save(array(
                            'market_price'=>I('market_price'), //市场价
                            'goods_price'=>I('shop_price'), // 本店价
                            'member_goods_price'=>I('shop_price'), // 会员折扣价                        
                            ));                    
                } else {
                    $Goods->add_time = time();//添加时间
                    $Goods->save(); // 写入数据到数据库                    
                    $goods_id = $insert_id = $Goods->getLastInsID();
                }
                
                $Goods->afterSave($goods_id);

                $GoodsLogic->saveGoodsAttr($goods_id,$data); // 处理商品 属性
                $GoodsLogic->saveGoodsProgramme($goods_id,$data);//处理商品金融方案
                $GoodsLogic->saveGoodsTags($goods_id,$data);//处理商品服务范围
                $GoodsLogic->saveGoodsUserLevel($goods_id,$data);//处理商品会员的可见度
                $GoodsLogic->savaUsedShops($goods_id,$data);//可用门店
                $GoodsLogic->savaConsumeTime($goods_id,$data);//指定消费时间

                $return_arr = array(
                    'status' => 1,
                    'msg' => '操作成功',
                    'data' => array('url' => U('admin/Goods/goodsList')),
                );
               exit(json_encode($return_arr));
            
        }
 
        $goodsInfo = M('Goods')->where(array('goods_id'=>I('GET.id', 0),'del_status'=>0))->find();
        //$cat_list = $GoodsLogic->goods_cat_list(); // 已经改成联动菜单
        $level_cat = $GoodsLogic->find_parent_cat($goodsInfo['cat_id']); // 获取分类默认选中的下拉框
        $level_cat2 = $GoodsLogic->find_parent_cat($goodsInfo['extend_cat_id']); // 获取分类默认选中的下拉框
        $where = array();
        $where['parent_id'] = 0;
        $cat_list = M('goods_category')->where($where)->select(); // 已经改成联动菜单
        $brandList = $GoodsLogic->getSortBrands();
        $map = array();
        $_SESSION['type']==1?$map['shop_id']= $_SESSION['admin_id']:false;
        $goodsType = M("GoodsType")->where($map)->select();
        $suppliersList = M("suppliers")->select(); 
        $seriesList = M('Series')->where(array('brand_id'=>$goodsInfo['brand_id']))->select();
        $versionList = M('Version')->where(array('series_id'=>$goodsInfo['series_id']))->select();
        $plugin_shipping = M('plugin')->where(array('type'=>array('eq','shipping')))->select();//插件物流
        $shipping_area = D('Shipping_area')->getShippingArea();//配送区域
        $goods_shipping_area_ids = explode(',',$goodsInfo['shipping_area_ids']);
        $goodsImages = M("GoodsImages")->where('goods_id =' . I('GET.id', 0))->select();
        $programmes = $GoodsLogic->getGoodsProgramme(I('GET.id', 0));
        $goodsTag = M('GoodsTag')->where(array('goods_id'=>I('GET.id', 0)))->field('tag_id')->select();
        $tags = M('Tag')->where(array('del_status'=>0))->select();
        $userLevel = M('UserLevel')->select();
        $goodsLevel =  M('GoodsLevel')->where(array('goods_id'=>I('GET.id', 0)))->select();
        !empty($goodsInfo['cat_id'])?$cat_id = $goodsInfo['cat_id']:false;
        $gtags = array();
        if (is_array($goodsTag)) {
           foreach ($goodsTag as $key => $tag) {
            $gtags[] = $tag['tag_id'];
            }
        }

        $gLevels = array();
        if (is_array($goodsLevel)) {
           foreach ($goodsLevel as $key => $level) {
            $gLevels[] = $level['level_id'];
            }
        }
        $usedShop = $GoodsLogic->getUsedBusiness(I('GET.id', 0));
        $times = M('ConsumeTime')->where(array('goods_id'=>I('GET.id', 0)))->find();
        $this->assign('times',$times);
        $this->assign('usedShop',$usedShop);
        $this->assign('goods_shipping_area_ids',$goods_shipping_area_ids);
        $this->assign('shipping_area',$shipping_area);
        $this->assign('plugin_shipping',$plugin_shipping);
        $this->assign('suppliersList',$suppliersList);
        $this->assign('level_cat', $level_cat);
        $this->assign('level_cat2', $level_cat2);
        $this->assign('cat_list', $cat_list);
        $this->assign('brandList', $brandList);
        $this->assign('seriesList', $seriesList);
        $this->assign('versionList', $versionList);
        $this->assign('goodsType', $goodsType);
        $this->assign('goodsInfo', $goodsInfo);  // 商品详情
        $this->assign('goodsImages', $goodsImages);  // 商品相册
        $this->assign('programmes', $programmes);// 商品金融方案
        $this->assign('cat_id',$cat_id);//当前添加分类的id
        $this->assign('tags',$tags);//标签
        $this->assign('gtags',$gtags);//商品已经添加的标签
        $this->assign('userLevel',$userLevel);//会员等级
        $this->assign('gLevels',$gLevels);//会员等级
        $this->initEditor(); // 编辑器
       
        $url = $this->jumpType($cat_id);
        if(empty($url)){
            $this->error('该分类还未生成添加页面');
        }else{
            return $this->fetch($url);
        }
        
    }

   
    private function jumpType($cat_id){
       
        $cateRules = array('138'=>'_goods','164'=>'_goodsUsedCar','165'=>'curingGoods');
        $GoodsLogic = new GoodsLogic();
        $data = $GoodsLogic->find_parent_cat($cat_id);
        foreach ($cateRules as $key => $value) {
            if (in_array($key, $data)) {
                return $value;
            }
        }


    }


    public function ajax_attributes()
     {
          $cat_id = I('cat_id');
          $goodsAttribute =M('Attribute')->alias('a')
          ->field('a.*,av.id AS attr_val_id,av.attr_val ')
          ->join('__ATTR_VAL__ av','av.attribute_id = a.id','LEFT')
          ->where(array('a.cat_id'=>$cat_id,'del_status'=>0,'av.goods_id'=>I('goods_id')))
          ->select();
          $allAtrributes = M('Attribute')->where(array('cat_id'=>I('cat_id')))->select();
          foreach ($goodsAttribute as $k => $val) {
              foreach ($allAtrributes as $key => $value) {
                if ($val['id']==$value['id']) {
                   $allAtrributes[$key]=$val;
                }
              }
          }

          $this->assign('attributes', $allAtrributes);// 商品属性
          return $this->fetch();
     } 

    public function search_goods()
    {
        $GoodsLogic = new GoodsLogic;
        $_SESSION['type']==1? $brandList = $GoodsLogic->getSortBrands('shop_id='.$_SESSION['admin_id'].' and del_status=0'):$brandList = $GoodsLogic->getSortBrands('del_status=0');
        $this->assign('brandList', $brandList);
        $_SESSION['type']==1? $categoryList = $GoodsLogic->getSortCategory('shop_id='.$_SESSION['admin_id'].' and del_status=0'):$categoryList = $GoodsLogic->getSortCategory('del_status=0');
        
        $this->assign('categoryList', $categoryList);

        $goods_id = I('goods_id');
        $where = ' is_on_sale = 1 and store_count>0 ';//搜索条件
        if (!empty($goods_id)) {
            $where .= " and goods_id not in ($goods_id) ";
        }
        I('intro') && $where = "$where and " . I('intro') . " = 1";
        if (I('cat_id')) {
            $this->assign('cat_id', I('cat_id'));
            $grandson_ids = getCatGrandson(I('cat_id'));
            $where = " $where  and cat_id in(" . implode(',', $grandson_ids) . ") "; // 初始化搜索条件
        }
        if (I('brand_id')) {
            $this->assign('brand_id', I('brand_id'));
            $where = "$where and brand_id = " . I('brand_id');
        }
        if (!empty($_REQUEST['keywords'])) {
            $this->assign('keywords', I('keywords'));
            $where = "$where and (goods_name like '%" . I('keywords') . "%' or keywords like '%" . I('keywords') . "%')";
        }
        $_SESSION['type']==1?$where.=' and shop_id='.$_SESSION['admin_id']:false;
        $count = M('goods')->where($where)->count();
        $Page = new Page($count, 10);
        $goodsList = M('goods')->where($where)->order('goods_id DESC')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $show = $Page->show();//分页显示输出
        $this->assign('page', $show);//赋值分页输出
        $this->assign('goodsList', $goodsList);
        $this->assign('pager', $Page);//赋值分页输出

        return $this->fetch();
    }

   // public function test(){
   //      $goods_id = 99;
   //     $data = M('GoodsProgramme')->where(array('goods_id'=>'99'))->field('down_payments,first_year')->order('down_payments asc')->group('down_payments,first_year')->select();
   //     var_dump($data);die();
   //  }
   
    /**
    *
    *每个分类属性列表
    */
     public function catAttributeList(){
        $cat_id = I('get.cat_id');
        $where = array();
        $where['cat_id'] = $cat_id;
        $goodsTypeList = M('GoodsCategory')->select();
        $this->assign('goodsTypeList',$goodsTypeList);
        $this->assign('cat_id',$cat_id);
        return $this->fetch();
     }
    

    /**
     *  商品属性列表
     */
    public function ajaxGoodsAttributeList(){            
        //ob_start('ob_gzhandler'); // 页面压缩输出
        $where = array(); // 搜索条件                        
        // I('type_id')   && $where = "$where and type_id = ".I('type_id') ;   
        if(I('type_id')) $where['cat_id']=I('type_id');
            $where['del_status']=0; 
            $keywords = I('keywords');
            if (!empty($keywords)) {
                      $where['attr_name']=array('like', '%' . $keywords . '%');
                 } 
                 
        // 关键词搜索               
        $model = M('Attribute');
        $count = $model->where($where)->count();
        $Page       = new AjaxPage($count,13);
        $show = $Page->show();
        $goodsAttributeList = $model->where($where)->order('id DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
        $goodsTypeList = M("GoodsType")->getField('id,name');
        $attr_input_type = array(0=>'手工录入',1=>' 从列表中选择',2=>' 多行文本框');
        $this->assign('attr_input_type',$attr_input_type);
        $this->assign('goodsTypeList',$goodsTypeList);        
        $this->assign('goodsAttributeList',$goodsAttributeList);
        $this->assign('page',$show);// 赋值分页输出
        return $this->fetch();
    }   
    
    /**
     * 添加修改编辑  商品属性
     */
    public  function addEditGoodsAttribute(){

           $cat_id = I('cat_id');
            

           $type = I('attr_id') > 0 ? 2 : 1; // 标识自动验证时的 场景 1 表示插入 2 表示更新
        
           if(IS_POST){
              $data=I('post.');
              $data['cat_id'] = $data['type_id'];
              foreach ($data as $key => $value) {
                  $data[$key] = trim($value);
              }
              if($type==1){
                $count = M('Attribute')->where(array('attr_name'=>$data['attr_name'],'cat_id'=>$data['type_id'],'del_status'=>0))->count();

                if($count>0) exit(json_encode(array('status'=>0,'msg'=>'此类型的该属性已存在')));
                
                $data['add_time'] = time();
                $result= M('Attribute')->add($data);

                $allChildren = $this->getChildCates($cat_id);
                foreach ($allChildren as $k => $child) {
                    $data['cat_id'] = $child;
                    M('Attribute')->add($data);
                }

              }else{
                $data['edit_time'] = time();
                $allChildren = $this->getChildCates($cat_id);
                
                $result= M('Attribute')->where(array('id'=>I('attr_id')))->save($data);
              }
              
              if ($result!==false) {
                 exit(json_encode(array('status'=>1,'msg'=>'添加成功')));
              }else{
                exit(json_encode(array('status'=>0,'msg'=>'添加失败')));
              }

           }else{
            $data = M('Attribute')->where(array('id'=>I('attr_id'),'del_status'=>0))->find();
           }
           if (!empty($cat_id)&&is_numeric($cat_id)) {
              $data['cat_id'] = $cat_id;
           }
          
           $categoryList = M('GoodsCategory')->select();
           $this->assign('goodsAttribute',$data);
           $this->assign('cat_id',$cat_id);
           $this->assign('categorys',$categoryList);
           return $this->fetch('_goodsAttribute');
    }

    /**
    *获取该分类下的子分类id
    *
    */ 

    private function getChildCates($cat_id)
     {
        $allChildren = array();
        $secondChildren = M('GoodsCategory')->where(array('parent_id'=>$cat_id))->select();
        foreach ($secondChildren as $key => $second) {
            $allChildren[] = $second['id'];
            $thirdChildren = M('GoodsCategory')->where(array('parent_id'=>$second['id']))->select();
            foreach ($thirdChildren as $k => $third) {
               $allChildren[] = $third['id'];
            }
        }

        return $allChildren;
     } 
    
    /**
     * 更改指定表的指定字段
     */
    public function updateField(){
        $primary = array(
                'goods' => 'goods_id',
                'goods_category' => 'id',
                'brand' => 'id',            
                'goods_attribute' => 'attr_id',
        		'ad' =>'ad_id',            
        );        
        $model = D($_POST['table']);
        $model->$primary[$_POST['table']] = $_POST['id'];
        $model->$_POST['field'] = $_POST['value'];        
        $model->save();   
        $return_arr = array(
            'status' => 1,
            'msg'   => '操作成功',                        
            'data'  => array('url'=>U('Admin/Goods/goodsAttributeList')),
        );
        $this->ajaxReturn($return_arr);
    }
    /**
     * 动态获取商品属性输入框 根据不同的数据返回不同的输入框类型
     */
    public function ajaxGetAttrInput(){
        $GoodsLogic = new GoodsLogic();
        $str = $GoodsLogic->getAttrInput($_REQUEST['goods_id'],$_REQUEST['type_id']);
        exit($str);
    }
        
    /**
     * 删除商品
     */
    public function delGoods()
    {
        $goods_id = $_GET['id'];
        $error = '';
        
        // 判断此商品是否有订单
        // $c1 = M('OrderGoods')->where("goods_id = $goods_id")->count('1');
        // $c1 && $error .= '此商品有订单,不得删除! <br/>';
        
        
        //  // 商品团购
        // $c1 = M('group_buy')->where("goods_id = $goods_id")->count('1');
        // $c1 && $error .= '此商品有团购,不得删除! <br/>';   
        
        //  // 商品退货记录
        // $c1 = M('return_goods')->where("goods_id = $goods_id")->count('1');
        // $c1 && $error .= '此商品有退货记录,不得删除! <br/>';
        
        if($error)
        {
            $return_arr = array('status' => -1,'msg' =>$error,'data'  =>'',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);        
            $this->ajaxReturn($return_arr);
        }
        
        // 删除此商品        
        M("Goods")->where('goods_id ='.$goods_id)->save(array('del_status'=>1));  //商品表
        M("cart")->where('goods_id ='.$goods_id)->delete();  // 购物车
        M("comment")->where('goods_id ='.$goods_id)->delete();  //商品评论
        M("goods_consult")->where('goods_id ='.$goods_id)->delete();  //商品咨询
        M("goods_images")->where('goods_id ='.$goods_id)->delete();  //商品相册
        M("spec_goods_price")->where('goods_id ='.$goods_id)->delete();  //商品规格
        M("spec_image")->where('goods_id ='.$goods_id)->delete();  //商品规格图片
        M("goods_attr")->where('goods_id ='.$goods_id)->delete();  //商品属性     
        M("goods_collect")->where('goods_id ='.$goods_id)->delete();  //商品收藏          
                     
        $return_arr = array('status' => 1,'msg' => '操作成功','data'  =>'',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);        
        $this->ajaxReturn($return_arr);
    }       

    /**
     * 删除商品属性
     */
    public function delGoodsAttribute()
    {
        $id = input('id');
        // 判断 有无商品使用该属性
        $count = M("AttrVal")->where("attribute_id = {$id}")->count("1");
        $count > 0 && $this->error('有商品使用该属性,不得删除!',U('Admin/Goods/goodsAttributeList'));
        // 删除 属性
        M('Attribute')->where("id = {$id}")->save(array('del_status'=>1,'edit_time'=>time()));
        $this->success("操作成功!!!");
    }            
    
    /**
     * 删除商品规格
     */
    // public function delGoodsSpec()
    // {
    //     $id = input('id');
    //     // 判断 商品规格项
    //     $count = M("SpecItem")->where("spec_id = {$id}")->count("1");
    //     $count > 0 && $this->error('清空规格项后才可以删除!',U('Admin/Goods/specList'));
    //     // 删除分类
    //     M('Spec')->where("id = {$id}")->delete();
    //     $this->success("操作成功!!!",U('Admin/Goods/specList'));
    // } 
    
    /**
     * 品牌列表
     */
    public function brandList(){  
        $model = M("Brand"); 
        $where = array();
        $keyword = I('keyword');
        if ($keyword) $where['name']=array('like',"%$keyword%");
        if ($_SESSION['type']==1) $where['shop_id']=$_SESSION['admin_id'];
        
        
        $count = $model->where($where)->count();
        $Page = $pager = new Page($count,10);        
        $brandList = $model->where($where)->order("`sort` asc")->limit($Page->firstRow.','.$Page->listRows)->select();
        $show  = $Page->show(); 
        $cat_list = M('goods_category')->where("parent_id = 0")->getField('id,name'); // 已经改成联动菜单
        $this->assign('cat_list',$cat_list);       
        $this->assign('pager',$pager);
        $this->assign('show',$show);
        $this->assign('brandList',$brandList);
        return $this->fetch('brandList');
    }
    
    /**
     * 添加修改编辑  商品品牌
     */
    public  function addEditBrand(){        
            $id = I('id');            
            if(IS_POST)
            {
                    $data = input('post.');
                    if($_SESSION['type']==1) $data['shop_id']=$_SESSION['admin_id'];
                    if($id)
                        M("Brand")->update($data);
                    else
                        M("Brand")->insert($data);
                    
                    $this->success("操作成功!!!",U('Admin/Goods/brandList',array('p'=>input('p'))));
                    exit;
            }
            $where = array();
            if ($_SESSION['type']==1) $where['shop_id'] = $_SESSION['admin_id'];
                $where['parent_id']='0';         
           $cat_list = M('goods_category')->where($where)->select(); // 已经改成联动菜单
           $this->assign('cat_list',$cat_list);           
           $brand = M("Brand")->find($id);             
           $this->assign('brand',$brand);
           return $this->fetch('_brand');
    }


    /**
    type,brand_id,series_id
     * 添加修改编辑  商品品牌 系列 型号
     */
    public  function addBrandSeriesVersion(){        
            $type = I('type');
            $this->assign('type',$type);
            $this->assign('info',I('get.'));
            if(IS_POST)
            {
                $data = input('post.');
                unset($data['type']);
                if($_SESSION['type']==1) $data['op_id']=$_SESSION['admin_id'];
                $res=0;
                $data['add_time']=time();
                switch ($type) {
                            case '1':
                              $count = M("Brand")->where(array('name'=>I('name'),'cat_id'=>I('cat_id')))->count();
                              $count>0?$res=0:$res= M("Brand")->add($data);
                              
                                break;
                            case '2':
                            $count = M("Series")->where(array('series_name'=>I('series_name'),'brand_id'=>I('brand_id')))->count();
                              $count>0?$res=0:$res= M("Series")->add($data);

                                 // $res= M("Series")->add($data);
                                break;
                            case '3':
                             $count = M("Version")->where(array('version_name'=>I('version_name'),'series_id'=>I('series_id')))->count();
                              $count>0?$res=0:$res= M("Version")->add($data);
                                 // $res= M("Version")->add($data);
                                break;    
                            default:
                                # code...
                                break;
                        }
                 if($res){
                    exit(json_encode(array('status'=>1,'msg'=>'操作成功')));
                }else{
                     exit(json_encode(array('status'=>0,'msg'=>'操作失败:该名称已经存在')));
                }
                 
            }else{
                switch ($type) {
                            case '1':
                                return $this->fetch('addBrandSeriesVersion');
                                break;
                            case '2':
                                $brands = M("Brand")->where(array('del_status'=>0))->select();             
                                $this->assign('brands',$brands);
                                return $this->fetch('addBrandSeriesVersion');
                                break;
                            case '3':
                               $brands = M("Brand")->where(array('del_status'=>0))->select();             
                                $this->assign('brands',$brands);
                                 $serieses = M("Series")->where(array('del_status'=>0))->select();             
                                $this->assign('serieses',$serieses);
                                return $this->fetch('addBrandSeriesVersion');
                                break;    
                            default:
                                # code...
                                break;
                        }
            }          
                
    }    
    

     public  function ajaxBrandSeriesVersion(){ 

            $type = I('get.type');
            $map = I('get.');
            unset($map['type']);
            $data = array();
            switch ($type) {
                            case '1':
                                $data = M('Brand')->where($map)->select();
                                break;
                            case '2':
                               $data = M('Series')->where($map)->field(array('id','series_name'=>'name'))->select();
                                break;
                            case '3':
                                $data = M('Version')->where($map)->field(array('id','version_name'=>'name'))->select();
                                break;    
                            default:
                                # code...
                                break;
                        }
            exit(json_encode(array('status'=>1,'data'=>$data)));
         
    }
    /**
     * 删除品牌
     */
    public function delBrand()
    {        
        // 判断此品牌是否有商品在使用
        $goods_count = M('Goods')->where("brand_id = {$_GET['id']}")->count('1');
        if($goods_count)
        {
            $return_arr = array('status' => -1,'msg' => '此品牌有商品在用不得删除!','data'  =>'',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);        
            $this->ajaxReturn($return_arr);
        }
        
        $model = M("Brand"); 
        $model->where('id ='.$_GET['id'])->delete(); 
        $return_arr = array('status' => 1,'msg' => '操作成功','data'  =>'',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);        
        $this->ajaxReturn($return_arr);
    }      
    
    /**
     * 初始化编辑器链接     
     * 本编辑器参考 地址 http://fex.baidu.com/ueditor/
     */
    private function initEditor()
    {
        $this->assign("URL_upload", U('admin/Ueditor/imageUp',array('savepath'=>'goods'))); // 图片上传目录
        $this->assign("URL_imageUp", U('admin/Ueditor/imageUp',array('savepath'=>'article'))); //  不知道啥图片
        $this->assign("URL_fileUp", U('admin/Ueditor/fileUp',array('savepath'=>'article'))); // 文件上传s
        $this->assign("URL_scrawlUp", U('admin/Ueditor/scrawlUp',array('savepath'=>'article')));  //  图片流
        $this->assign("URL_getRemoteImage", U('admin/Ueditor/getRemoteImage',array('savepath'=>'article'))); // 远程图片管理
        $this->assign("URL_imageManager", U('admin/Ueditor/imageManager',array('savepath'=>'article'))); // 图片管理        
        $this->assign("URL_getMovie", U('admin/Ueditor/getMovie',array('savepath'=>'article'))); // 视频上传
        $this->assign("URL_Home", "");
    }    
    
    
    
    /**
     * 商品规格列表    
     */
    public function specList(){
       $where = array();
        if ($_SESSION['type']==1) {
             $where['shop_id'] = $_SESSION['admin_id'];
        }       
        $goodsTypeList = M("GoodsType")->where($where)->select();
        $this->assign('goodsTypeList',$goodsTypeList);
        return $this->fetch();
    }
    
    
    /**
     *  商品规格列表
     */
    public function ajaxSpecList(){ 
        //ob_start('ob_gzhandler'); // 页面压缩输出
        $where = ' 1 = 1 '; // 搜索条件                        
        I('type_id')   && $where = "$where and type_id = ".I('type_id') ;
         if ($_SESSION['type']==1) $where = "$where and shop_id = ".$_SESSION['admin_id'] ;
        // 关键词搜索               
        $model = D('spec');
        $count = $model->where($where)->count();
        $Page       = new AjaxPage($count,13);
        $show = $Page->show();
        $specList = $model->where($where)->order('`type_id` desc')->limit($Page->firstRow.','.$Page->listRows)->select();        
        $GoodsLogic = new GoodsLogic();        
        foreach($specList as $k => $v)
        {       // 获取规格项     
                $arr = $GoodsLogic->getSpecItem($v['id']);
                $specList[$k]['spec_item'] = implode(' , ', $arr);
        }
        
        $this->assign('specList',$specList);
        $this->assign('page',$show);// 赋值分页输出
        $goodsTypeList = M("GoodsType")->select(); // 规格分类
        $goodsTypeList = convert_arr_key($goodsTypeList, 'id');
        $this->assign('goodsTypeList',$goodsTypeList);        
        return $this->fetch();
    }      
    /**
     * 添加修改编辑  商品规格
     */
    public  function addEditSpec(){
                        
            $model = D("spec");                      
            $type = I('id') > 0 ? 2 : 1; // 标识自动验证时的 场景 1 表示插入 2 表示更新             
            if((I('is_ajax') == 1) && IS_POST)//ajax提交验证
            {                
                // 数据验证
                $validate = \think\Loader::validate('Spec');
                $post_data = input('post.');
                if($_SESSION['type'] == 1) $post_data['shop_id'] = $_SESSION['admin_id'];

                if ($type == 2) {
                    //更新数据
                    $check = $validate->scene('edit')->batch()->check($post_data);
                } else {
                    //插入数据
                    $check = $validate->batch()->check($post_data);
                }
                if (!$check) {
                    $error = $validate->getError();
                    $error_msg = array_values($error);
                    $return_arr = array(
                        'status' => -1,
                        'msg' => $error_msg[0],
                        'data' => $error,
                    );
                    $this->ajaxReturn($return_arr);
                }
                $model->data($post_data, true); // 收集数据
                if ($type == 2) {
                    $model->isUpdate(true)->save(); // 写入数据到数据库
                    $model->afterSave(I('id'));
                } else {
                    $model->save(); // 写入数据到数据库
                    $insert_id = $model->getLastInsID();
                    $model->afterSave($insert_id);
                }
                $return_arr = array(
                    'status' => 1,
                    'msg' => '操作成功',
                    'data' => array('url' => U('Admin/Goods/specList')),
                );
                $this->ajaxReturn($return_arr);
            }                
           // 点击过来编辑时                 
           $id = I('id/d',0);
           $spec = $model->find($id);
           $GoodsLogic = new GoodsLogic();  
           $items = $GoodsLogic->getSpecItem($id);
           $spec[items] = implode(PHP_EOL, $items); 
           $this->assign('spec',$spec);
           if($_SESSION['type']==1)  $goodsTypeList = M("GoodsType")->where(array('shop_id'=>$_SESSION['admin_id']))->select();
           else  $goodsTypeList = M("GoodsType")->select();
                     
           $this->assign('goodsTypeList',$goodsTypeList);           
           return $this->fetch('_spec');
    }  
    
    
    /**
     * 动态获取商品规格选择框 根据不同的数据返回不同的选择框
     */
    public function ajaxGetSpecSelect(){
        $goods_id = I('get.goods_id/d') ? I('get.goods_id/d') : 0;        
        $GoodsLogic = new GoodsLogic();
        //$_GET['spec_type'] =  13;
        $specList = M('Spec')->where("type_id = ".I('get.spec_type/d'))->order('`order` desc')->select();
        foreach($specList as $k => $v)        
            $specList[$k]['spec_item'] = M('SpecItem')->where("spec_id = ".$v['id'])->order('id')->getField('id,item'); // 获取规格项                
        
        $items_id = M('SpecGoodsPrice')->where('goods_id = '.$goods_id)->getField("GROUP_CONCAT(`key` SEPARATOR '_') AS items_id");
        $items_ids = explode('_', $items_id);       
        
        // 获取商品规格图片                
        if($goods_id)
        {
           $specImageList = M('SpecImage')->where("goods_id = $goods_id")->getField('spec_image_id,src');                 
        }        
        $this->assign('specImageList',$specImageList);
        
        $this->assign('items_ids',$items_ids);
        $this->assign('specList',$specList);
        return $this->fetch('ajax_spec_select');        
    }    
    
    /**
     * 动态获取商品规格输入框 根据不同的数据返回不同的输入框
     */    
    public function ajaxGetSpecInput(){     
         $GoodsLogic = new GoodsLogic();
         $goods_id = I('goods_id/d') ? I('goods_id/d') : 0;
         $str = $GoodsLogic->getSpecInput($goods_id ,I('post.spec_arr/a',[[]]));
         exit($str);   
    }
    
    /**
     * 删除商品相册图
     */
    public function del_goods_images()
    {
        $path = I('filename','');
        M('goods_images')->where("image_url = '$path'")->delete();
    }

    public function tagLists()
    {
        $count = M('Tag')->where(array('del_status'=>0))->count();
        $page = new Page($count,10);
        $list = M('Tag')->where(array('del_status'=>0))->limit($page->firstRow.','.$page->listRows)->order('id desc')->select();
        $this->assign('list',$list);
        $this->assign('page',$page->show);
        return $this->fetch();
    }

    public function addEditTag()
    {
        if (IS_GET) {
            $id=I('get.id');
            if (empty($id)||!is_numeric($id)){
                 return $this->fetch();
            }else{
                 $data = M('Tag')->where(array('id'=>$id))->find();

                $this->assign('data',$data) ;
                 return $this->fetch();
            }
    
        }


        if (IS_POST) {
            $data = I('post.');
            if (!empty($data['id'])&&is_numeric($data['id'])) {
               $res = M('Tag')->where(array('id'=>$data['id']))->save(array('name'=>$data['name']));
            }else{
                $res = M('Tag')->add($data);
            }


            if ($res!==false) {
               $this->success('操作成功');
            }else{
                $this->error('操作失败');
            }
        }
        
    }

    public function del_tag()
    {
        $id = I('get.id');
        $res = M('Tag')->where(array('id'=>$id))->save(array('del_status'=>1));
        if ($res!==false) {
           exit(json_encode(array('status'=>1,'msg'=>'删除成功')));
        }else{
           exit(json_encode(array('status'=>0,'msg'=>'删除失败'))); 
        }
    }
}