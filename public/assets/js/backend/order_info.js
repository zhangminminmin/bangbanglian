define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'order_info/index' + location.search,
                    add_url: 'order_info/add',
                    edit_url: 'order_info/edit',
                    del_url: 'order_info/del',
                    multi_url: 'order_info/multi',
                    table: 'order_info',
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
                        {field: 'order_id', title: __('订单id')},
                        // {field: 'goods_id', title: __('Goods_id')},
                        {field: 'goods_name', title: __('商品名称')},
                        {field: 'goods_image', title: __('商品图片'), events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'price', title: __('商品单价（ZPC）'), operate:'BETWEEN'},
                        {field: 'price_rmb', title: __('商品单价(￥)'), operate:'BETWEEN'},
                        {field: 'num', title: __('购买个数')},
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
                        {field: 'prop_values', title: __('商品规格'),operate:false},
                        {field: 'created_at', title: __('创建时间'),operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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