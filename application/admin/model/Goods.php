<?php

namespace app\admin\model;

use think\Model;
use traits\model\SoftDelete;
class Goods extends Model
{
    use SoftDelete;
    // 表名
    protected $name = 'goods';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    protected $deleteTime = 'deleted_at';

    // 追加属性
    protected $append = [

    ];
    

    







}
