<?php

namespace app\api\model;

use think\Model;

/**
 * 订单 模块
 */
class OrderInfo Extends Model
{

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'created_at';
    protected $updateTime = false;
    // 追加属性
    protected $append = [
    ];

    /**
     * 订单详情信息
     */
    public static function orderInfo($order_id) {
        $list = self::where('id', '>', 0)
                    ->where('order_id', $order_id)
                    ->select();
        $items = [];
        foreach($list as $k => $val) {
            $items[] = [
                'id' => $val->id,
                'goods_id' => $val->goods_id,
                'goods_name' => $val->goods_name,
                'goods_image' => $val->goods_image,
                'num' => $val->num,
                'prop_values' => json_decode($val->prop_values,true),
                'price' => $val->price,
                'price_rmb' => $val->price_rmb,
                'is_backgoods' => $val->is_backgoods,
            ];
        }
        return $items;
    }
}
