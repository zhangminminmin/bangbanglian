<?php

namespace app\api\controller;

use app\api\model\Address;
use app\api\model\Cart;
use app\api\model\Collect;
use app\api\model\GoodsLabel;
use app\api\model\GoodsProp;
use app\api\model\OrderInfo;
use app\common\controller\Api;
use app\api\model\GoodsSort;
use app\api\model\Goods as GoodsModel;
use think\Db;
use app\api\model\Order;
use think\route\Domain;

/**
 * 商品模块 控制器
 */
class Goods extends Api
{
    protected $noNeedLogin = ['goodsSortList', 'goodsLabel', 'goodsList', 'goodsInfo', 'goodsProp'];
    protected $noNeedRight = ['collect', 'addCart', 'cartList', 'delCart', 'affirmOrderInfo', 'cartOrder','createdOrderInfo','cretedOrder'];


    /**
     * 商品分类
     */
    public function goodsSortList()
    {
        $list = GoodsSort::where('pid', 0)->select();
        $items = [];
        foreach ($list as $k => $val) {
            $son = GoodsSort::where('pid', $val->id)->select();
            $sons = [];
            foreach ($son as $k => $item) {
                $sons[] = [
                    'id' => $item->id,
                    'name' => $item->name,
                    'image' => cdnurl($item->image, true),
                ];
            }
            $items[] = [
                "id" => $val->id,
                'name' => $val->name,
                'son' => $sons,
            ];
        }
        $param = [
            'list' => $items,
        ];
        return ajaxReturn(200, '分类获取成功！', $param);
    }


    /***
     * 商品标签  筛选标签
     */
    public function goodsLabel()
    {
        $goodsLabel = GoodsLabel::where('id', ">", 0)->select();
        $items = [];
        foreach ($goodsLabel as $k => $val) {
            $items[] = [
                'id' => $val->id,
                'name' => $val->name,
            ];
        }
        $params = [
            'list' => $items,
        ];
        return ajaxReturn(200, '筛选项信息获取成功！', $params);
    }

    /**
     * 热门推荐
     * page 页数
     * limit  每页展示的条数
     * is_hot 是否是热门推荐  1展示热门推荐  2显示全部
     * name 商品名称
     * price_order 价格排序 0正序  1倒序
     * buy_num_order 销量排序 0正序 1倒序
     * label_id 筛选标签
     * sort_id 分类id
     */
    public function goodsList()
    {
        $page = $this->request->request('page');
        $limit = $this->request->request('limit');
        $is_hot = $this->request->request('is_hot');
        $name = $this->request->request('name');
        $price_order = $this->request->request('price_order') ? :0;
        $buy_num_order = $this->request->request('buy_num_order') ? :0;
        $sort_id = $this->request->request('sort_id');
        $label_id = $this->request->request('label_id');

        $GoodsModel = new GoodsModel();
        $GoodsModel->where('id', '>', 0)->where('status', 1)->whereNull('deleted_at');
        if ($is_hot == 1) {
            $GoodsModel->where('position', 1);
        }
        if ($name) {
            $GoodsModel->where('title', 'like', '%' . $name . '%');
        }



        if ($price_order == 1 && $buy_num_order == 1) {
            $GoodsModel->order('virtual_price asc, total_buy_num asc');
        }elseif ($price_order == 1 && $buy_num_order == 0) {
            $GoodsModel->order('virtual_price asc, total_buy_num desc');
        }elseif ($price_order == 0 && $buy_num_order == 1) {
            $GoodsModel->order('virtual_price desc, total_buy_num asc');
        }else{
            $GoodsModel->order('virtual_price desc, total_buy_num desc');
        }
        if ($label_id) {
            $GoodsModel->where('label_id', $label_id);
        }

        if ($sort_id) {
            $GoodsModel->where('sort_two_id', $sort_id);
        }

        $list = $GoodsModel->paginate($limit);

        $items = [];
        foreach ($list as $k => $val) {
            $images = $val->images ? explode(',', $val->images) : '';
            $items[] = [
                'id' => $val->id,
                'title' => $val->title,
                'price' => $val->price,
                'virtual_price' => $val->virtual_price,
                'buy_num' => $val->buy_num,
                'false_buy_num' => $val->false_buy_num,
                'image' => isset($images[0]) ? cdnurl($images[0], true) : "",
            ];
        }

        $params = [
            'total' => $list->total(),
            'list' => $items,
        ];
        return ajaxreturn(200, '数据获取成功！', $params);
    }

    /**
     * 商城的详情信息
     * goods_id 商品的id
     */
    public function goodsInfo()
    {
        $goods_id = $this->request->request('goods_id');
        $info = GoodsModel::get($goods_id);
        if (!$info) {
            return ajaxReturn(202, '商品信息不存在或者已下架！');
        }

        if ($info['status'] == 2) {
            return ajaxReturn(202, '商品信息不存在或者已下架！');
        }

        $images = isset($info->images) ? explode(',', $info->images) : '';
        if (is_array($images)) {
            for ($i = 0; $i < count($images); $i++) {
                $images[$i] = cdnurl($images[$i], true);
            }
        }

        if (!$goodsProp = GoodsProp::where('goods_id', $goods_id)->select()) {
            return ajaxReturn(202, '商品信息不存在或者已下架！');
        }

        $num = 0;
        foreach ($goodsProp as $k => $val) {
            $num += $val->stock;
        }

        // 是否收藏 购物车总数
        $collect = 1;
        $cart_total = 0;
        if ($this->auth->isLogin()) {
            $user = $this->auth->getUser();
            if ($collectInfo = Collect::where('user_id', $user->id)->where('goods_id', $goods_id)->find()) {
                $collect = 2;
            }

            $cart_total = Cart::where('user_id', $user->id)->sum('num');
        }

        $goodsInfo = [
            'id' => $info->id,
            'title' => $info->title,
            'images' => $images,
            'buy_num' => $info->buy_num,
            'false_buy_num' => $info->false_buy_num,
            'send_address' => $info->send_address ?: '',
            'price' => $info->price,
            'virtual_price' => $info->virtual_price,
            'content' => $info->content ? getImgThumbUrl($info->content, \think\Config::get('upload.cdnurl')): '',
            'num' => $num,
            'collect' => $collect,//1未收藏  2已收藏
            'cart_total' => $cart_total,
        ];

        $params = [
            'goodsInfo' => $goodsInfo,
        ];
        return ajaxReturn(200, '信息获取成功！', $params);
    }

    /**
     * 规格数据管理
     * goods_id 商品的id
     */
    public function goodsProp()
    {
        $goods_id = $this->request->request('goods_id');
        if (!$info = GoodsModel::get($goods_id)) {
            return ajaxReturn(202, '商品已下架或者不存在！');
        }

        if ($info->status == 2) {
            return ajaxReturn(202, '商品已下架或者不存在！');
        }

        if (!$info->prop_names || !$info->prop_name_value) {
            return ajaxReturn(202, '商品已下架或者不存在');
        }

        $prop_names = json_decode($info['prop_names'], true);
        $prop_name_value = json_decode($info['prop_name_value'], true);

        $num = count($prop_names);
        $items = [];
        for ($i = 0; $i < $num; $i++) {
            $items[$i] = [
                'prop_name' => $prop_names[$i],
                'prop_values' => $prop_name_value[$i],
            ];
        }
        // 商品sku
        $goods_sku = GoodsProp::where('goods_id', $goods_id)->select();
        $sku = [];
        foreach ($goods_sku as $k => $val) {
            $sku[] = [
                'id' => $val->id,
                'goods_id' => $val->goods_id,
                'sku_id' => $val->sku_id,
                'prop_values' => json_decode($val->prop_values),
                'price' => $val->price,
                'price_rmb' => $val->price_rmb,
                'stock' => $val->stock,
            ];
        }
        $params = [
            'props' => $items,
            'prop_sku' => $sku,
        ];
        return ajaxReturn(200, '属性获取成功!', $params);
    }

    /**
     * 收藏商品
     */
    public function collect()
    {
        $user = $this->auth->getUser();
        $goods_id = $this->request->request('goods_id');
        if (!$goods_id) {
            return ajaxReturn(202, '商品参数错误  轻刷新重试！');
        }
        $collect = Collect::where('user_id', $user->id)->where('goods_id', $goods_id)->find();
        if ($collect) {
            $del = Collect::where('id', $collect->id)->delete();
            return ajaxReturn(200, '取消收藏成功!');
        } else {
            $collectModel = new Collect();
            $data = [
                'user_id' => $user->id,
                'goods_id' => $goods_id,
            ];
            $add = $collectModel->save($data);
            return ajaxReturn(200, '收藏成功!');
        }
    }


    /***
     * 加入购物车
     * goods_id 商品 id
     * sku_id 商品的skuid
     * num 商品数量
     */
    public function addCart()
    {
        $user = $this->auth->getUser();
        $goods_id = $this->request->request('goods_id');
        $sku_id = $this->request->request('sku_id');
        $num = $this->request->request('num');

        if (!$goods_id) {
            return ajaxReturn(202, '商品参数错误  请刷新重试！');
        }

        if (!$sku_id) {
            return ajaxReturn(202, '商品的规格参数错误 刷新重试！');
        }

        if (!$num) {
            return ajaxReturn(202, '请选择商品的数量');
        }

        if (!$propInfo = GoodsProp::where('goods_id', $goods_id)->where('sku_id', $sku_id)->find()) {
            return ajaxReturn(202, '商品已下架或者被删除 无法加入到购物车');
        }


        if ($info = Cart::where('goods_id', $goods_id)->where('user_id', $user->id)->where('sku_id', $sku_id)->find()) {
            if (($info->num + $num) > $propInfo->stock) {
                return ajaxReturn(202, '库存不足无法加入购物车');
            }
            $addCart = Cart::where('id', $info->id)->setInc('num', $num);
        } else {
            if ($num > $propInfo->stock) {
                return ajaxReturn(202, '库存不足无法加入购物车');
            }
            $data = [
                'user_id' => $user->id,
                'goods_id' => $goods_id,
                'sku_id' => $sku_id,
                'num' => $num,
                'price' => $propInfo->price,
                'price_rmb' => $propInfo->price_rmb,
            ];

            $cartModel = new Cart();
            $addCart = $cartModel->save($data);
        }

        return ajaxReturn(200, '购物车添加成功！');
    }

    /**
     * 购物车列表页面
     */
    public function cartList()
    {
        $user = $this->auth->getUser();
        $list = Cart::where('user_id', $user->id)->select();

        $normal_cart = [];
        $abnormal_cart = [];
        $shop_total = 0;
        foreach ($list as $k => $val) {
            $goodsInfo = GoodsModel::get($val->goods_id);
            $goodsProp = GoodsProp::where('sku_id', $val->sku_id)->find();

            $images = $goodsInfo->images ? explode(',', $goodsInfo->images) : '';
            $image = isset($images[0]) ? cdnurl($images[0], true) : "";
            if ($goodsProp) {
                if ($val->num > $goodsProp->stock) {
                    $abnormal_cart[] = [
                        'id' => $val->id,
                        'goods_id' => $val->goods_id,
                        'sku_id' => $val->sku_id,
                        'image' => $image,
                        'title' => $goodsInfo->title,
                        'prop' => json_decode($goodsProp->prop_values, true),
                        'price' => $goodsProp->price,
                        'price_rmb' => $goodsProp->price_rmb,
                        'num' => $val->num,
                    ];
                } else {
                    $normal_cart[] = [
                        'id' => $val->id,
                        'goods_id' => $val->goods_id,
                        'sku_id' => $val->sku_id,
                        'image' => $image,
                        'title' => $goodsInfo->title,
                        'prop' => json_decode($goodsProp->prop_values, true),
                        'price' => $goodsProp->price,
                        'price_rmb' => $goodsProp->price_rmb,
                        'num' => $val->num,
                    ];
                }

            }

            $shop_total += $val->num;
        }
        $params = [
            'shop_total' => $shop_total,
            'abnormal_cart' => $abnormal_cart,
            'normal_cart' => $normal_cart
        ];
        return ajaxReturn(200, '购物车列表获取成功！', $params);
    }

    /**
     * 批量删除 购物车信息
     * cart_ids购物车 id
     */
    public function delCart()
    {
        $user = $this->auth->getUser();
        $cart_ids = $this->request->request('cart_ids');
        if (!$cart_ids) {
            return ajaxReturn(202, '未选择要删除的商品');
        }
        $delCart = Cart::where('id', 'in', $cart_ids)->where('user_id', $user->id)->delete();
        return ajaxReturn(200, '信息删除成功！');
    }

    /**
     * 订单确认页面数据
     * cart_ids 购物车页面
     */
    public function affirmOrderInfo()
    {
        $user = $this->auth->getUser();
        $cart_ids = $this->request->request('cart_ids');
        if (!$cart_ids) {
            return ajaxReturn(202, '请选择购物车要购买的商品');
        }

        $cart_list = Cart::where('user_id', $user->id)->where('id', 'in', $cart_ids)->select();

        $items = [];
        $total_price = 0;
        $total_price_rmb = 0;
        foreach ($cart_list as $k => $val) {
            $goodsInfo = GoodsModel::get($val->goods_id);
            $goodsProp = GoodsProp::where('sku_id', $val->sku_id)->find();
            $images = $goodsInfo->images ? explode(',', $goodsInfo->images) : '';
            $image = isset($images[0]) ? cdnurl($images[0], true) : "";

            if ($goodsInfo && $goodsProp) {
                $total_price = $val->num * $goodsProp->price;
                $total_price_rmb = $val->num * $goodsProp->price_rmb;
                $items[] = [
                    'id' => $val->id,
                    'goods_id' => $val->goods_id,
                    'sku_id' => $val->sku_id,
                    'image' => $image,
                    'title' => $goodsInfo->title,
                    'prop' => json_decode($goodsProp->prop_values, true),
                    'price' => $goodsProp->price,
                    'price_rmb' => $goodsProp->price_rmb,
                    'num' => $val->num,
                ];
            }
        }
        $params = [
            'total_price' => $total_price,
            'total_price_rmb' => $total_price_rmb,
            'list' => $items
        ];
        return ajaxReturn(200, '订单详情信息获取成功！', $params);
    }

    /**
     * 购物车合并订单
     * cart_ids 购物车的id
     * cart_nums 商品的数量
     * address_id 地址的id
     */
    public function cartOrder()
    {
        $user = $this->auth->getUser();
        $cart_ids = $this->request->request('cart_ids');
        $cart_nums = $this->request->request('cart_nums');
        $address_id = $this->request->request('address_id');

        if (!$cart_ids) {
            return ajaxReturn(202, '没有选择购买的商品');
        }

        if (!$cart_nums) {
            return ajaxReturn(202, '请传入商品的数量' );
        }

        $cart_ids_arr = explode(',',$cart_ids);
        $cart_nums_arr = explode(',',$cart_nums);
        if (count($cart_ids_arr) != count($cart_nums_arr)) {
            return ajaxReturn(202, '参数错误 请刷新重试');
        }

        Db::startTrans();
        for($i=0;$i<count($cart_ids_arr);$i++) {
            if ($cart_nums_arr[$i] <= 0) {
                Db::rollback();
                return ajaxReturn(202, '商品个数必须大于零');
            }
            $editCart = Cart::where('id', $cart_ids_arr[$i])->update(['num' => $cart_nums_arr[$i]]);
        }

        if (!$address_id) {
            return ajaxReturn(202, '请选择收货地址！');
        }

        if (!$address = Address::get($address_id)) {
            return ajaxReturn(202, '请选择收货地址！');
        }

        $cart_list = Cart::where('user_id', $user->id)->where('id', 'in', $cart_ids)->select();
        if (!$cart_list) {
            return ajaxReturn(202, '选择的商品已经失效！');
        }
        do {
            $order_sn = date('Ymd') . rand(10000, 99999) . $user->id;
            $info = Order::where('order_sn', $order_sn)->find();
        } while ($info);
        $data = [
            'user_id' => $user->id,
            'order_sn' => $order_sn,
            'realname' => $address->realname,
            'mobile' => $address->mobile,
            'address' => $address->address,
            'address_info' => $address->address_info,
        ];
        $orderModel = new Order();
        if ($orderModel->save($data)) {
            $order_id = $orderModel->id;
        } else {
            Db::rollback();
            return ajaxReturn(202, '订单生成失败！');
        }

        $items = [];
        $total_price = 0;
        $total_price_rmb = 0;
        foreach ($cart_list as $k => $val) {
            $goodsInfo = GoodsModel::get($val->goods_id);
            $goodsProp = GoodsProp::where('sku_id', $val->sku_id)->find();
            $images = $goodsInfo->images ? explode(',', $goodsInfo->images) : '';
            $image = isset($images[0]) ? cdnurl($images[0], true) : "";

            if (!$goodsInfo || !$goodsProp) {
                Db::rollback();
                return ajaxReturn(202, '商品中存在已下架或者删除的商品  请刷新重试！');
            }

            if ($val->num > $goodsProp->stock) {
                Db::rollback();
                return ajaxReturn(202, '存在库存不足的商品 请刷新重试！');
            }

            $total_price_rmb += $val->num * $val->price_rmb;
            $total_price += $val->num * $val->price;
            $items[] = [
                'order_id' => $order_id,
                'goods_id' => $goodsInfo->id,
                'goods_name' => $goodsInfo->title,
                'goods_image' => $image,
                'sku_id' => $goodsProp->sku_id,
                'price' => $goodsProp->price,
                'price_rmb' => $goodsProp->price_rmb,
                'num' => $val->num,
                'prop_values' => $goodsProp->prop_values,
            ];
        }

        $orderInfoModel = new OrderInfo();
        if (!$orderInfoModel->saveAll($items)) {
            Db::rollback();
            return ajaxReturn(202, '订单生成失败');
        }

        $price = [
            'total_price_rmb' => $total_price_rmb,
            'total_price' => $total_price,
        ];
        if (!$editOrder = Order::where('id', $order_id)->update($price)) {
            Db::rollback();
            return ajaxReturn(202, '订单生成失败');
        }

        if (!Cart::where('id', 'in', $cart_ids)->delete()) {
            Db::rollback();
            return ajaxReturn(202, '订单生成失败');
        }
        Db::commit();
        $params = [
            'order_id' => (int)$order_id
        ];
        return ajaxReturn(200, '订单生成成功！', $params);
    }

    /**
     * 立即购买确认订单  按钮
     * goods_id
     * sku_id
     * num
     */
    public function createdOrderInfo()
    {
        $user = $this->auth->getUser();
        $goods_id = $this->request->request('goods_id');
        $sku_id = $this->request->request('sku_id');
        $num = $this->request->request('num');

        if (!$goods_id) {
            return ajaxReturn(202, '商品参数出错 请刷新重试！');
        }

        if (!$sku_id) {
            return ajaxReturn(202, '商品的规格出错 轻刷新重试!');
        }

        if (!$num) {
            return ajaxReturn(202, '请选择商品数量！');
        }

        if (!$goodsInfo = GoodsModel::get($goods_id)) {
            return ajaxReturn(202, '商品的信息不存在或者已经下架');
        }

        if (!$goodsProp = GoodsProp::where('sku_id', $sku_id)->find()) {
            return ajaxReturn(202, '商品的规格参数出错！');
        }
        if ($num > $goodsProp->stock) {
            return ajaxReturn(202, '库存不足 无法购买');
        }
        $images = $goodsInfo->images ? explode(',', $goodsInfo->images) : '';
        $image = isset($images[0]) ? cdnurl($images[0], true) : "";
        $info = [
            'goods_id' => $goodsInfo->id,
            'sku_id' => $sku_id,
            'image' => $image,
            'title' => $goodsInfo->title,
            'prop' => json_decode($goodsProp->prop_values, true),
            'price' => $goodsProp->price,
            'price_rmb' => $goodsProp->price_rmb,
            'num' => (int)$num,
            'total_price' => $num * $goodsProp->price,
            'total_price_rmb' => $num * $goodsProp->price_rmb,
        ];

        $params = [
            'info' => $info,
        ];
        return ajaxReturn(200, '页面信息获取成功！',$params);
    }

    /**
     * 立即购买生成订单
     * goods_id 商品的id
     * sku_id  商品的sku_id
     * num 购买数量
     * address_id 地址的id
     */
    public function cretedOrder()
    {
        $user = $this->auth->getUser();
        $goods_id = $this->request->request('goods_id');
        $sku_id = $this->request->request('sku_id');
        $num = $this->request->request('num');
        $address_id = $this->request->request('address_id');

        if (!$goods_id) {
            return ajaxReturn(202, '商品参数出错 请刷新重试！');
        }

        if (!$sku_id) {
            return ajaxReturn(202, '商品的规格出错 轻刷新重试!');
        }

        if (!$num) {
            return ajaxReturn(202, '请选择商品数量！');
        }
        if (!$address_id) {
            return ajaxReturn(202, '请选择收货地址');
        }

        if (!$address_info = Address::where('id', $address_id)->find()) {
            return ajaxReturn(202, '请选择收货地址');
        }

        if (!$goods_info = GoodsModel::get($goods_id)) {
            return ajaxReturn(202, '商品的信息不存在或者已经下架');
        }

        if (!$goodsProp = GoodsProp::where('sku_id', $sku_id)->find()) {
            return ajaxReturn(202, '商品的规格参数出错！');
        }

        do {
            $order_sn = date('Ymd') . rand(10000, 99999) . $user->id;
            $info = Order::where('order_sn', $order_sn)->find();
        } while ($info);
        $order = [
            'user_id' => $user->id,
            'order_sn' => $order_sn,
            'total_price' => $num * $goodsProp->price,
            'total_price_rmb' => $num * $goodsProp->price_rmb,
            'realname' => $address_info->realname,
            'mobile' => $address_info->mobile,
            'address' => $address_info->address,
            'address_info' => $address_info->address_info,
        ];

        Db::startTrans();
        $orderModel = new Order();
        if ($orderModel->save($order)) {
            $order_id = $orderModel->id;
        }else{
            Db::rollback();
            return ajaxReturn(202, '订单生成失败！');
        }
        $images = $goods_info->images ? explode(',', $goods_info->images) : '';
        $image = isset($images[0]) ? cdnurl($images[0], true) : "";
        $data = [
            'order_id' => $order_id,
            'goods_id' => $goods_id,
            'goods_name' => $goods_info->title,
            'goods_image' => $image,
            'sku_id' => $sku_id,
            'price' => $goodsProp->price,
            'price_rmb' => $goodsProp->price_rmb,
            'num' => $num,
            'prop_values' => $goodsProp->prop_values,
        ];

        $orderInfoModel = new OrderInfo();
        if (!$orderInfoModel->save($data)) {
            Db::rollback();
            return ajaxReturn(202, '订单生成失败！');
        }
        Db::commit();
        $params = [
            'order_id' => (int)$order_id
        ];
        return ajaxReturn(200, '订单生成成功！',$params);
    }
}
