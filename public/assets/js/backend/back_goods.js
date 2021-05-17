define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'back_goods/index' + location.search,
                    // add_url: 'back_goods/add',
                    // edit_url: 'back_goods/edit',
                    // del_url: 'back_goods/del',
                    // multi_url: 'back_goods/multi',
                    table: 'back_goods',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'user_id', title: __('退货人id')},
                        {field: 'user.nickname', title: __('退货人昵称')},
                        {field: 'user.mobile', title: __('退货人手机号')},
                        {field: 'order_id', title: __('退货订单号')},
                        // {field: 'order_info_id', title: __('Order_info_id')},
                        {field: 'goods_image', title: __('商品图片'), events: Table.api.events.image, formatter: Table.api.formatter.image},
                        // {field: 'goods_id', title: __('Goods_id')},
                        {field: 'goods_name', title: __('商品名称')},
                        {field: 'price', title: __('单价（ZPC）'), operate:'BETWEEN'},
                        {field: 'price_rmb', title: __('单价（￥）'), operate:'BETWEEN'},
                        {field: 'num', title: __('商品数量')},
                        {field: 'created_at', title: __('创建时间') ,operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {
                            field: 'is_backgoods',
                            title: __('退货状态'),
                            formatter:function(value, row,index) {
                                if (row.is_backgoods == 0) {
                                    return '未退货';
                                }else if(row.is_backgoods == 1) {
                                    return '<button class="btn btn-xs btn-success btn-magic btn-ajax">退货中</button>';
                                }else if(row.is_backgoods == 2) {
                                    return '<button class="btn btn-xs btn-primary btn-dialog">已退货</button>';
                                }
                            }
                        },
                        {
                            field: 'operate', title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'ajax',
                                    text: __('处理退货'),
                                    title: __('同意退货吗'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: 'back_goods/back_goods?ids={ids}',
                                    confirm: '确定处理退货吗？',
                                    visible:function(row) {
                                        if (row.is_backgoods == 1) {
                                            return true;
                                        }else{
                                            return false;
                                        }
                                    },
                                    success: function (data, ret) {
                                        
                                    },
                                    error: function (data, ret) {
                                        console.log(data, ret);
                                        Layer.alert(ret.msg);
                                        return false;
                                    }
                                },

                            ],
                            formatter: Table.api.formatter.operate,
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});