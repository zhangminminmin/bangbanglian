<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\admin\model\Order AS OrderModel;
/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Order extends Backend
{
    
    /**
     * Order模型对象
     * @var \app\admin\model\Order
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Order;

    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    

    /**
     * 查看
     */
    public function index()
    {
        //当前是否为关联查询
        $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                    ->with(['user'])
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with(['user'])
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $row) {
                $row->visible(['id','user_id','order_sn','total_price','total_price_rmb','status','is_backgoods','is_pay','realname','mobile','address','address_info','created_at']);
                $row->visible(['user']);
				$row->getRelation('user')->visible(['id','nickname','mobile']);
            }
            $list = collection($list)->toArray();
            //销售额
            $where_url['is_pay'] = 1;
            $where_url['is_backgoods'] = 0;
            $price = $this->model->where($where)->where($where_url)->sum('total_price');
            $price_rmb = $this->model->where($where)->where($where_url)->sum('total_price_rmb');
            $result = array("total" => $total, "rows" => $list,'price' => $price, 'price_rmb' => $price_rmb);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 设置发货
     */
    public function setGoods($ids='')
    {
        if (!$ids) {
            $this->error('订单参数出错！刷新重试！');
        }

        if (!$orderInfo = OrderModel::get($ids)) {
            $this->error('订单异常 无法发货！');
        }

        if ($orderInfo['is_backgoods'] != 0) {
            $this->error('商品已经选择退货 无法发货 请刷新查看！');
        }

        if ($orderInfo['is_pay'] == 0) {
            $this->error('订单未支付 无法发货！');
        }

        if ($orderInfo['status'] != 2) {
            $this->error('订单状态已经改变 无法发货！');
        }

        $data = [
            'status' => 3,// 已发货
        ];

        $editOrder = OrderModel::where('id', $ids)->update($data);
        $this->success('设置发货成功！');
    }
}
