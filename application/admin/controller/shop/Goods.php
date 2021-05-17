<?php

namespace app\admin\controller\shop;

use app\common\controller\Backend;
use app\admin\model\GoodsSort;
use app\admin\model\GoodsLabel;
use app\api\model\GoodsProp;
use think\Db;
/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Goods extends Backend
{
    
    /**
     * Goods模型对象
     * @var \app\admin\model\Goods
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Goods;

        $positionList = [
            0 => '不推荐',
            1 => '推荐',
        ];

        $isBackGoodsList = [
            1 => '不允许',
            2 => '允许',
        ];
        $this->view->assign('positionList', $positionList);
        $this->view->assign('isBackGoodsList', $isBackGoodsList);
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
            $total = $this->model
                    
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $row) {
                $row->visible(['id','title','price','virtual_price','images','label_id','label_name','status','prop_names','prop_name_value','is_backgoods','position','send_address','created_at','sort_one_id','sort_two_id']);
                
            }
            $list = collection($list)->toArray();
            foreach($list as $k => $val) {
                $sort_name = '';
                $goodsOneSort = GoodsSort::get($val['sort_one_id']);
                $goodsTwoSort = GoodsSort::get($val['sort_two_id']);
                if ($goodsOneSort && $goodsTwoSort) {
                    $sort_name = $goodsOneSort['name'] . '/' . $goodsTwoSort['name'];
                }
                $list[$k]['sort_name'] = $sort_name;
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                $params['label_name'] = $this->getLabel($params['label_id']);
                $params['total_buy_num'] = $params['buy_num'] + $params['false_buy_num'];
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    $result = $this->model->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $params['label_name'] = $this->getLabel($params['label_id']);
            $params['total_buy_num'] = $params['buy_num'] + $params['false_buy_num'];
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 获取标签
     */
    public function getLabel($label_id)
    {
        $label_info = GoodsLabel::get($label_id);
        $label_name = '';
        if ($label_info['name']) {
            $label_name = $label_info['name'];
        }
        return $label_name;
    }

    /**
     * 设置规格页面
     * 5hE5JLJjh6bwwkFG
     */
    public function prop($ids=null) {
        if (!$ids) {
            $this->error('参数错误 刷新重试！');
        }

        // 商品信息
        $info = $this->model->where('id',$ids)->find();
        $info['prop_names'] = $info['prop_names'] ? json_decode($info['prop_names'],true) : [];
        $info['prop_name_value'] =  $info['prop_name_value'] ? json_decode($info['prop_name_value'],true) : [];
        // sku信息
        $skuInfo = GoodsProp::where('goods_id', $ids)->select();
        $skuInfo = collection($skuInfo)->toArray();
        $this->view->assign('info',$info);
        $this->view->assign('id',$ids);
        $this->view->assign('sku_info', $skuInfo);
        return $this->view->fetch();
    }

    /**
     * 提交接口
     * id 商品id
     * name
     * value 无价格
     */
    public function addPost()
    {
        $input = $this->request->request();

        if (!isset($input['id']) || !$input['id']) {
            $this->error('参数错误 请刷新重试！！');
        }

        if (!isset($input['value']) || !$input['value']) {
            $this->error('请先添加规格！');
        }
        if (!isset($input['name']) || !$input['name']) {
            $this->error('请先添加规格！');
        }

        Db::startTrans();

        // 删除之前sku
        $del = GoodsProp::where('goods_id', $input['id'])->delete();
        if ($del ===false) {
            Db::rollback();
            $this->error('网络出错 刷新重试！');
        }
        if ($del)
        // 更新商品表
        $prop_names = [];
        $prop_name_value = [];
        foreach($input['value'] as $k => $val) {
            if ($k) {
                $prop_names[] = $k;
            }
            if ($val) {
                $prop_name_value[] = $val;
            }
        }

        if (count($prop_names) <= 0 || count($prop_name_value) <= 0) {
            Db::rollback();
            $this->error('请先添加规格！');
        }

        if (count($prop_names) !== count($prop_name_value)) {
            Db::rollback();
            $this->error('每个规格下面至少一个属性！');
        }

        $goodsProp = [
            'prop_names' => json_encode($prop_names),
            'prop_name_value' => json_encode($prop_name_value),
            'status' => 1,
        ];
        $updateGoods = $this->model->where('id', $input['id'])->update($goodsProp);
        if ($updateGoods === false) {
            Db::rollback();
            $this->error('网络出错 规格保存失败！');
        }

        // 更新goods_prop 表
        $data = [];
        foreach($input['name'] as $k => $val) {
            $prop_values = [];
            if ($val['price'] && $val['price'] > 0) {
                $karr = explode('_', $k);
                for($i=0;$i<count($prop_names);$i++) {
                    $prop_values[] = [
                        'prop_name' => $prop_names[$i],
                        'prop_value' => $karr[$i],
                    ];
                }
                $data[] = [
                    'sku_id' => $k,
                    'goods_id' => $input['id'],
                    'prop_values' => json_encode($prop_values),
                    'price' => $val['price'],
                    'price_rmb' => $val['price_rmb'],
                    'stock' => $val['stock'],
                ];
            }
        }

        if (!$data) {
            Db::rollback();
            $this->error('规格至少有一个填写价格');
        }

        $goodsPropModel = new GoodsProp();
        $add = $goodsPropModel->saveAll($data);
        if (!$add) {
            Db::rollback();
            $this->error('网络出错 规格保存失败！');
        }

        Db::commit();
        $this->success('规格保存成功！');
    }
}
