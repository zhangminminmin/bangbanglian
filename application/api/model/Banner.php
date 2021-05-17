<?php

namespace app\api\model;

use think\Model;

/**
 * banner图
 */
class Banner Extends Model
{

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'created_at';
    protected $updateTime = false;
    // 追加属性
    protected $append = [
    ];

}
