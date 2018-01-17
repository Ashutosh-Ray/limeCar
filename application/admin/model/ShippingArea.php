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

namespace app\admin\model;
use think\model;
class ShippingArea extends model {

    /**
     * 获取配送区域
     * @return mixed
     */
    public function getShippingArea()
    {
        $shipping_areas = M('shipping_area')->where('1=1')->select();
        foreach($shipping_areas as $key => $val){
            $shipping_areas[$key]['config'] = unserialize($shipping_areas[$key]['config']);
        }
        return $shipping_areas;
    }

}
