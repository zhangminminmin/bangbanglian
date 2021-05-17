<?php

namespace app\api\model;

use think\Model;

/**
 * 单页面
 */
class Page Extends Model
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
