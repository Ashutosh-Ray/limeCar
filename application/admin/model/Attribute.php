<?php
namespace app\admin\model;
use think\model;
/**
* 
*/
class Attribute extends Model
{
   public function attr_val()
    {
        return $this->hasOne('AttrVal','attribute_id')->field('attr_name,attr_val');
    }
}