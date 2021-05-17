<?php

namespace app\api\controller;

use app\api\model\BackGoods;
use app\api\model\GoodsProp;
use think\Db;
use app\api\model\Order;
use app\api\model\Goods;
use app\api\model\Address;
use app\api\model\Collect;
use app\api\model\OrderInfo;
use app\common\controller\Api;
use app\common\model\User as UserModel;

/**
 * 会员接口
 */
class UserCenter extends Api
{
    protected $noNeedLogin = ['userCenter'];
    protected $noNeedRight = ['modifyPassword', 'modifyUserData','addressList','addAddr','getAddress','editAddr','delAddress','collectList','delCollect','myOrder','pay','cancalOrder','myOrderInfo','confirmOrder','backGoods','backGoodsInfo'];

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 会员中心
     */
    public function userCenter()
    {
        $info = [];
        if ($this->auth->isLogin()) {
            $userInfo = $this->auth->getUser();
            $info = [
                'id' => $userInfo->id,
                'nickname' => $userInfo->nickname ? :'',
                'mobile' => $userInfo->mobile,
                'avatar' => $userInfo->avatar ? cdnurl($userInfo->avatar,true) :'',
                'avatar_url' => $userInfo->avatar ? :'',
            ];
        }

        $params = [
            'userInfo' => $info,
        ];
        return ajaxReturn(200, '会员信息获取成功！',$params);
    }


    /**
     * 修改密码
     * password     原密码
     * newpassword  新密码
     * renewpassword 重复新密码
     */
    public function modifyPassword()
    {
        $user = $this->auth->getUser();
        $password = $this->request->request('password');
        $newpassword = $this->request->request('newpassword');
        $renewpassword = $this->request->request('renewpassword');

        if (!$password) {
            return ajaxReturn(202,'原始密码不能为空');
        }

        if ($user->password != $this->auth->getEncryptPassword($password)) {
            return ajaxReturn(202, '原始密码输入不正确');
        }

        if (strlen($newpassword) < 6) {
            return ajaxReturn(202, '密码不能低于六位数！');
        }

        if ($renewpassword != $newpassword) {
            return ajaxReturn(202, '两次密码输入不一致！');
        }

        $data = [
            'password' => $this->auth->getEncryptPassword($newpassword),
        ];

        $add = $user->where('id', $user->id)->update($data);
        return ajaxReturn(200,'密码修改成功！');
    }

    /**
     * 修改个人资料
     * avatar 头像
     * nickname 昵称
     */
    public function modifyUserData()
    {
        $user = $this->auth->getUser();
        $avatar = $this->request->file('avatar');
        $nickname = $this->request->request('nickname');

        $image = '';
        $data = [];
        if ($avatar) {
            // 移动到框架应用根目录/public/uploads/ 目录下
            $info = $avatar->move(ROOT_PATH . 'public' . DS . 'uploads');
            if($info){
                $image = "/uploads/" . $info->getSaveName();
            }else{
                return ajaxReturn(202,'图片上传失败');
            }
        }

        if (!$nickname) {
            return ajaxReturn(202, '请填入昵称！');
        }

        if ($image) {
            $data['avatar'] = $image;
        }

        $data['nickname'] = $nickname;
        $data['username'] = $nickname;

        $update = $user->where('id', $user->id)->update($data);
        return ajaxReturn(200, '信息补充完整！');
    }

    /**
     * 收货地址 列表
     */
    public function addressList()
    {
        $user = $this->auth->getUser();
        $list = Address::where('id','>',0)
                ->where('user_id',$user->id)
                ->order('status', 'desc')
                ->order('id','desc')
                ->select();
        $items = [];
        foreach($list as $k => $val) {
            $items[] = [
                'id' => $val->id,
                'address' => $val->address,
                'address_info' => $val->address_info,
                'realname' => $val->realname,
                'mobile' => $val->mobile,
                'status' => $val->status,
            ];
        }
        $params = [
            'list' => $items,
        ];
        return ajaxReturn(200,'获取地址列表成功！',$params);
    }

    /**
     * 添加收货地址信息
     * realname 姓名
     * mobile 电话
     * address 收货地址
     * address_info 收货地址详情
     * status 是否为默认  1不默认  2默认
     */
    public function addAddr()
    {
        $user = $this->auth->getUser();
        $realname = $this->request->request('realname');
        $mobile = $this->request->request('mobile');
        $address = $this->request->request('address');
        $address_info = $this->request->request('address_info');
        $status = $this->request->request('status');

        if (!$realname) {
            return ajaxReturn(202,'请填写收货人！');
        }

        if (! \think\Validate::regex($mobile, "^1\d{10}$")) {
            return ajaxReturn(202, '手机号格式不正确');
        }

        if (!$address) {
            return ajaxReturn(202,'请选择地址');
        }

        if (!$address_info) {
            return ajaxReturn(202,'请输入详细地址！');
        }

        if (!$status) {
            $status = 1;
        }

        if ($status == 2) {
            $data = [
                'status' => 1,
            ];
            Address::where('user_id', $user->id)->update($data);
        }

        $data = [
            'user_id' => $user->id,
            'realname' => $realname,
            'mobile' => $mobile,
            'address' => $address,
            'address_info' => $address_info,
            'status' => $status,
        ];
        $addAddr = new Address();
        $addAddr = $addAddr->save($data);
        return ajaxReturn(200, '收货地址添加成功!');
    }

    /**
     * 获取地址详情
     * 地址id  address_id
     */
    public function getAddress()
    {
        $user = $this->auth->getUser();
        $address_id = $this->request->request('address_id');
        if (!$address_id) {
            return ajaxReturn(202,'参数错误 请刷新重试！');
        }
        $info = Address::where('user_id', $user->id)
                ->where('id', $address_id)
                ->field('id,realname,mobile,address,address_info,status')
                ->find();
        if (!$info) {
            return ajaxReturn(202, '地址信息不存在，可能已被删除  刷新重试');
        }

        $params = [
            'address' => $info,
        ];
        return ajaxReturn(200,'地址信息获取成功',$params);
    }

    /**
     * 修改地址
     * address_id 地址id
     * realname 姓名
     * mobile 手机号
     * address 地址
     * address_info 地址详情
     * status 1不默认  2默认
     */
    public function editAddr()
    {
        $user = $this->auth->getUser();
        $address_id = $this->request->request('address_id');
        $realname = $this->request->request('realname');
        $mobile = $this->request->request('mobile');
        $address = $this->request->request('address');
        $address_info = $this->request->request('address_info');
        $status = $this->request->request('status');

        if (!$address_id) {
            return ajaxReturn(202, '参数错误 请刷新重试！');
        }
        $info = Address::where('user_id', $user->id)
            ->where('id', $address_id)
            ->find();
        if (!$info) {
            return ajaxReturn(202, '非法操作！');
        }

        if (!$realname) {
            return ajaxReturn(202,'请填写收货人！');
        }

        if (! \think\Validate::regex($mobile, "^1\d{10}$")) {
            return ajaxReturn(202, '手机号格式不正确');
        }

        if (!$address) {
            return ajaxReturn(202,'请选择地址');
        }

        if (!$address_info) {
            return ajaxReturn(202,'请输入详细地址！');
        }

        if (!$status) {
            $status = 1;
        }

        if ($status == 2) {
            $data = [
                'status' => 1,
            ];
            Address::where('user_id', $user->id)->update($data);
        }

        $data = [
            'user_id' => $user->id,
            'realname' => $realname,
            'mobile' => $mobile,
            'address' => $address,
            'address_info' => $address_info,
            'status' => $status,
        ];
        $addAddr = new Address();
        $editAddr = $addAddr->where('id', $address_id)->update($data);
        return ajaxReturn(200, '收货地址编辑成功!');
    }

    /**
     * 删除收货地址
     * address_id
     */
    public function delAddress()
    {
        $user = $this->auth->getUser();
        $address_id = $this->request->request('address_id');

        if (!$address_id) {
            return ajaxReturn(202, '参数错误 请刷新重试！');
        }
        $info = Address::where('user_id', $user->id)
                ->where('id', $address_id)
                ->find();
        if (!$info) {
            return ajaxReturn(202, '此地址已删除 请勿重复操作！');
        }

        $del = Address::where('id', $address_id)->delete();
        return ajaxReturn(200, '地址删除成功！');
    }

    /**
     * 我的收藏列表
     * page 页数
     * limit 每页展示的条数
     */
    public function collectList()
    {
        $user = $this->auth->getUser();
        $page = $this->request->request('page');
        $limit = $this->request->request('limit');
        $collect = Collect::where('user_id', $user->id)->select();
        $collect = array_column(collection($collect)->toArray(),'goods_id');

        $goodsModel = new Goods();
        $list = $goodsModel->where('id', 'in', $collect)->paginate($limit);
        $items = [];
        foreach($list as $k => $val) {
            $images = $val->images ? explode(',', $val->images) : '';
            $items[] = [
                'id' => $val->id,
                'title' => $val->title,
                'price' => $val->price,
                'virtual_price' => $val->virtual_price,
                'buy_num' => $val->buy_num,
                'false_buy_num' => $val->false_buy_num,
                'image' => isset($images[0]) ? cdnurl($images[0],true) :"",
            ];
        }
        $params = [
            'total' => $list->total(),
            'collect' => $items,
        ];
        return ajaxReturn(200,'收藏列表获取成功!', $params);
    }

    /***
     * 编辑收藏列表
     * goods_ids 商品的id
     */
    public function delCollect()
    {
        $user = $this->auth->getUser();
        $goods_ids = $this->request->request('goods_ids');
        if (!$goods_ids) {
            return ajaxReturn(202, '没有选择要取消的项！');
        }
        $del = Collect::where('user_id', $user->id)
               ->where('goods_id', 'in', $goods_ids)
               ->delete();
        return ajaxReturn(200, '收藏删除成功！');
    }

    /**
     * 我的订单
     * status 1 待支付  2待发货  3已发货  4已完成 5售后
     * page 当前页数
     * limit 每页展示的条数
     */
    public function myOrder()
    {
        $user = $this->auth->getUser();
        $page = $this->request->request('page');
        $limit = $this->request->request('limit');
        $status = $this->request->request('status');


        $order = new Order();
        $order->order('id','desc')->where('id', '>', 0)->where('user_id', $user->id);
        if ($status == 1) {
            $order->where('status', 1)->where('is_pay', 0);
        } elseif($status == 2) {
            $order->where('status', 2)->where('is_pay', 1);
        } elseif ($status == 3) {
            $order->where('status', 3)->where('is_pay', 1);
        } elseif ($status == 4) {
            $order->where('status', 4)->where('is_pay', 1);
        } elseif ($status == 5) {
            $order->where('is_backgoods', "<>",0);
        }

        $list = $order->paginate($limit);

        $items = [];
        foreach($list as $k => $val) {
            $res = OrderInfo::orderInfo($val->id);
            $total_num = OrderInfo::where('order_id', $val->id)->sum('num');
            $items[] = [
                'id' => $val->id,
                'order_sn' => $val->order_sn,
                'status' => $val->status,
                'total_price' => $val->total_price,
                'total_price_rmb' => $val->total_price_rmb,
                'total_num' => $total_num,
                'order_info' => $res,
            ];
        }

        $params = [
            'total' => $list->total(),
            'list' => $items,
        ];
        return ajaxReturn(200,'我的订单获取成功！', $params);
    }

    /**
     * 我的订单详情
     * order_id 订单的id
     */
    public function myOrderInfo()
    {
        $user = $this->auth->getUser();
        $order_id = $this->request->request('order_id');
        if (!$order_id) {
            return ajaxReturn(202, '订单参数错误 请刷新重试！');
        }

        if (!$orderinfo = Order::get($order_id)) {
            return ajaxReturn(202, '订单参数错误 请刷新重试！');
        }
        // 商品信息 （详细信息）
        $res = OrderInfo::orderInfo($order_id);
        $info = [
            'id' => (int)$order_id,
            'address' => $orderinfo['address'],
            'address_info' => $orderinfo['address_info'],
            'mobile' => $orderinfo['mobile'],
            'realname' => $orderinfo['realname'],
            'total_price' => $orderinfo['total_price'],
            'total_price_rmb' => $orderinfo['total_price_rmb'],
            'goods_num' => count($res),
            'total_num' => count($res),
            'order_sn' => $orderinfo['order_sn'],
            'created_at' => date('Y-m-d H:i',$orderinfo['created_at']),
            'status' => $orderinfo['status'],
            'order_info' => $res,
        ];

        $params = [
            'order_info' => $info,
        ];
        return ajaxReturn(200, '订单详情获取成功！', $params);
    }
    /**
     * 立即支付
     * transaction_id
     * order_id
     */
    public function pay()
    {
        $user = $this->auth->getUser();
        $order_id = $this->request->request('order_id');
        $transaction_id = $this->request->request('transaction_id');

        if(!$order_id) {
            return ajaxReturn(202, '订单号不能为空');
        }

        if (!$orderInfo = Order::get($order_id)) {
            return ajaxReturn(202,'订单不存在或者已取消！');
        }

        if ($orderInfo['is_pay'] == 1) {
            return ajaxReturn(202, '订单已支付 请勿重复操作！');
        }

        if (!$transaction_id) {
            return ajaxReturn(202, '交易号不能为空');
        }
        $data = [
            'transaction_id' => $transaction_id,
        ];
        $res = $this->curlGet('http://47.93.206.115/api.php','post',$data);
        $res = json_decode($res,true);
        if ($res['code'] != 0) {
            return ajaxReturn(202, '订单支付信息获取失败！请稍后重试！');
        }

        $amount = $res['data']['amount'];
        if ($amount == $orderInfo['total_price']) {
            $expir_time = config('site.expir_time') * 60;
            $ctime = $res['data']['transaction_time'] - $orderInfo['created_at'];
            if ($ctime > $expir_time) {
                return ajaxReturn(202,'支付已过期 请联系管理员处理!');
            }

            $data = [
                'is_pay' => 1,
                'status' => 2,
            ];
            Order::where('id', $order_id)->update($data);
            \app\api\model\User::where('id', $user->id)->setInc('buy_num', 1);
            \app\api\model\User::where('id', $user->id)->setInc('total_buy_num', 1);
            // 减库存
            $info = OrderInfo::where('order_id', $order_id)->select();
            if ($info) {
                foreach($info as $k => $val) {
                    $goodsProp = GoodsProp::where('sku_id',$val->sku_id)->where('goods_id', $val->goods_id)->find();
                    $stock = ($goodsProp->stock - $val->num) > 0 ? $goodsProp->stock - $val->num : 0;
                    GoodsProp::where('sku_id',$val->sku_id)->where('goods_id', $val->goods_id)->update(['stock' => $stock]);
                }

            }
            return ajaxReturn(200,'支付成功！');
        }else{
            return ajaxReturn(202, '支付失败!');
        }
    }

    /**
     * 取消订单
     * order_id 订单id
     */
    public function cancalOrder()
    {
        $user = $this->auth->getUser();
        $order_id = $this->request->request('order_id');
        if (!$order_id) {
            return ajaxReturn(202, '订单参数出错 请刷新重试！');
        }

        if (!$orderInfo = Order::get($order_id)) {
            return ajaxReturn(202, '订单不存在');
        }

        if ($orderInfo['status'] == 5) {
            return ajaxReturn(202, '订单已取消！请勿重复操作！');
        }

        if ($orderInfo['status'] != 1 && $orderInfo['status'] != 2) {
            return ajaxReturn(202, '订单已发货无法取消');
        }

        $data = [
            'status' => 5,
        ];
        $edit = Order::where('id', $order_id)->update($data);
        return ajaxReturn(200, '订单取消成功！');
    }

    /**
     * 确认订单
     * order_id 订单的id
     */
    public function confirmOrder()
    {
        $user = $this->auth->getUser();
        $order_id = $this->request->request('order_id');
        if (!$order_id) {
            return ajaxReturn(202, '订单参数错误 请刷新重试！');
        }

        if (!$orderInfo = Order::get($order_id)) {
            return ajaxReturn(202, '订单出错 请刷新重试！');
        }

        if ($orderInfo['status'] == 5) {
            return ajaxReturn(202, '订单已取消 无法确认收货！');
        }

        if ($orderInfo['status'] == 1) {
            return ajaxReturn(202, '订单未支付 无法确认收货！');
        }

        $data = [
            'status' => 4,
        ];
        $edit = Order::where('id', $order_id)->update($data);
        return ajaxReturn(200, '订单确认收获成功！');
     }

    /**
     * 退货
     * order_id 订单id
     * order_info_ids 商品订单详情的id  格式 1,2,3
     * memo 退货备注
     * images  退货图片
     */
    public function backGoods()
    {
        $user = $this->auth->getUser();
        $order_id = $this->request->request('order_id');
        $order_info_ids = $this->request->request('order_info_ids');
        $memo = $this->request->request('memo');
        $files = $this->request->file('images');

        if (!$order_id) {
            return ajaxReturn(202, '参数错误 请刷新重试！');
        }
        if (!$orderInfo = Order::where('id', $order_id)->where('user_id', $user->id)->find()) {
            return ajaxReturn(202, '订单数据出错  请刷新重试！');
        }
        if (!$order_info_ids) {
            return ajaxReturn(202, '选择要退的商品');
        }

        if ($orderInfo['status'] == 1 || $orderInfo['status'] == 2) {
            return ajaxReturn(202, '订单未支付或者未发货 可以直接取消订单');
        }

        if ($orderInfo['status'] == 5) {
            return ajaxReturn(202, '订单已取消 无法退货');
        }

        if (!$files && !$memo) {
            return ajaxReturn(202, '请输入退货理由');
        }

        $order_info_ids = explode(',', $order_info_ids);
        $orders = OrderInfo::where('id', 'in', $order_info_ids)->select();

        $images = '';
        if ($files) {
            foreach($files as $file){
                $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
                if($info){
                    $images .= "/uploads/" . $info->getSaveName() . ',';
                }
            }
        }

        $images = $images ? substr($images,0,-1) : '';
        Db::startTrans();

        $edit_order = Order::where('id', $order_id)->update(['is_backgoods' => 1,'status' => 6]);
        if ($edit_order === false) {
            Db::rollback();
            return ajaxReturn(202,'退货状态失败！#1');
        }

        $items = [];
        foreach($orders as $k => $val) {
            $goodsInfo = Goods::get($val->goods_id);
            if ($goodsInfo['is_backgoods'] == 1) {
                Db::rollback();
                return ajaxReturn(202, '存在不可退货的商品  请检查后重试!');
            }

            $items = [
                'user_id' => $user->id,
                'order_id' => $order_id,
                'order_info_id' => $val->id,
                'goods_image' => $val->goods_image,
                'goods_id' => $val->goods_id,
                'goods_name' => $val->goods_name,
                'sku_id' => $val->sku_id,
                'price' => $val->price,
                'price_rmb' => $val->price_rmb,
                'num' => $val->num,
                'prop_values' => $val->prop_values,
                'memo' => $memo ? :'',
                'images' => $images,
            ];

            if (!BackGoods::where('order_id', $order_id)->where('order_info_id', $val->id)->find()  &&  $order_id == $val->order_id) {
                // 更改商品状态
                $d = [
                    'is_backgoods' => 1
                ];
                $editOrderInfo = Order::where('id', $order_id)->update($d);
                if ($editOrderInfo === false) {
                    Db::rollback();
                    return ajaxReturn(202,'退货状态失败！#2');
                }
                // OrderInfo中退货详情状态更改
                $editOrder = OrderInfo::where('id', $val->id)->update($d);
                if ($editOrder === false) {
                    Db::rollback();
                    return ajaxReturn(202,'退货状态失败！#3');
                }
                // 添加到退货商品中
                $backGoods = new BackGoods();
                if (!$backGoods->save($items)) {
                    Db::rollback();
                    return ajaxReturn(202, '网络开小差！退货失败！');
                }
            }
        }
        Db::commit();
        return ajaxReturn(200, '退货申请成功！请等待审核！');
    }

    /**
     * 退货详情信息查看
     * order_info_id
     */
    public function backGoodsInfo()
    {
        $user = $this->auth->getUser();
        $order_info_id = $this->request->request('order_info_id');

        $info = BackGoods::where('order_info_id', $order_info_id)->find();
        if (!$info) {
            return ajaxReturn(202, '不存在的退货订单');
        }

        $order = Order::get($info['order_id']);
        $images = [];
        $images_arr = [];
        if ($info['images']) {
            $images_arr = explode(',',$info['images']);
            for($i=0;$i<count($images_arr);$i++) {
                $images[$i] = cdnurl($images_arr[$i],true);
            }
        }
        $backGoodsInfo = [
            'id' => $info->id,
            'goods_name' => $info['goods_name'],
            'goods_image' => $info['goods_image'],
            'price' => $info['price'],
            'price_rmb' => $info['price_rmb'],
            'num' => $info['num'],
            'prop_values' => json_decode($info['prop_values'], true),
            'memo' => $info->memo ? :'',
            'images' => $images,
            'is_backgoods' => $info->is_backgoods,
            'created_at' => date('Y-m-d H:i', $info->created_at),
            'backed_code' => $order['order_sn'],
        ];

        $params = [
            'back_goods_info' => $backGoodsInfo,
        ];
        return ajaxReturn(200, '退货详情信息获取成功！',$params);
    }
    /**
     * curl 辅助函数
     */
    function curlGet($url, $method = 'get', $data = '') {
        $ch = curl_init();
        $header = array("Accept-Charset: utf-8");
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        }
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $temp = curl_exec($ch);
        return $temp;
    }
}
