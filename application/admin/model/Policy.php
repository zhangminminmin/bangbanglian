<?php

namespace app\admin\model;

use think\Model;


class Policy extends Model
{

    

    

    // 表名
    protected $name = 'policy';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    

    







}
