<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;
use app\admin\model\Order;
use app\admin\model\OrderInfo;
/**
 * 
 *
 * @icon fa fa-circle-o
 */
class BackGoods extends Backend
{
    
    /**
     * BackGoods模型对象
     * @var \app\admin\model\BackGoods
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\BackGoods;

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
                $row->visible(['user_id','order_id','order_info_id','goods_image','goods_id','goods_name','price','price_rmb','num','prop_values','memo','images','created_at','id','is_backgoods']);
                $row->visible(['user']);
				$row->getRelation('user')->visible(['nickname','mobile']);
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 处理退货
     */
    public function back_goods($ids=null)
    {
        if (!$ids) {
            $this->error('参数错误 请刷新重试！');
        }

        if (!$info = $this->model->where('id', $ids)->find()) {
            $this->error('退货信息异常！');
        }

        if ($info['is_backgoods'] != 1) {
            $this->error('退货订单已处理 请刷新查看');
        }

        $d = [
            'is_backgoods' => 2
        ];

        Db::startTrans();
        // 更改退货订单的信息
        $editBackOrder = $this->model->where('id', $ids)->update(['is_backgoods'=> 2]);
        if ($editBackOrder === false) {
            Db::rollback();
            $this->error('状态更新失败 请刷新重试！');
        }
        // 更改订单的信息
        $editOrder = Order::where('id', $info['order_id'])->update(['is_backgoods'=> 2]);
        if ($editOrder === false) {
            Db::rollback();
            $this->error('状态更新失败 请刷新重试！');
        }
        // 更改订单详情的信息
        $editOrderInfo = OrderInfo::where('id', $info['order_info_id'])->update(['is_backgoods'=> 2]);
        if ($editOrderInfo === false) {
            Db::rollback();
            $this->error('状态更新失败 请刷新重试！');
        }

        Db::commit();
        $this->success('退货处理成功！');
    }
}
