<?php

namespace app\admin\controller;

use app\common\controller\Backend;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class OrderInfo extends Backend
{
    
    /**
     * OrderInfo模型对象
     * @var \app\admin\model\OrderInfo
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\OrderInfo;

    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    

    /**
     * 查看
     */
    public function index($ids=null)
    {
        //当前是否为关联查询
        $this->relationSearch = false;
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

            $map = [];
            if ($ids) $map['order_id'] = $ids;
            $total = $this->model

                    ->where($map)
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model

                    ->where($map)
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $row) {
                $row->visible(['id','order_id','goods_id','goods_name','goods_image','price','price_rmb','num','prop_values','is_backgoods','created_at']);
                
            }
            $list = collection($list)->toArray();
            foreach($list as $k => $val) {
                $prop_values = json_decode($val['prop_values'],true);
                $prop_values_str = '';

                foreach($prop_values as $kk => $item) {
                    $prop_values_str .= $item['prop_name'] . ":" . $item['prop_value'] . " ";
                }
                $list[$k]['prop_values'] = $prop_values_str;
            }
            $result = array("total" => $total, "rows" => $list, $map);

            return json($result);
        }
        return $this->view->fetch();
    }
}
