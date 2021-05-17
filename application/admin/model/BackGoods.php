<?php

namespace app\admin\model;

use think\Model;


class BackGoods extends Model
{

    

    

    // 表名
    protected $name = 'back_goods';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'backed_time_text'
    ];
    

    



    public function getBackedTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['backed_time']) ? $data['backed_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setBackedTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
