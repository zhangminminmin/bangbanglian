<?php

namespace app\api\model;

use think\Model;

/**
 * 商品模块
 */
class GoodsProp Extends Model
{

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    // 追加属性
    protected $append = [
    ];

}
