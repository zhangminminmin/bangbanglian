define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'shop/goods/index' + location.search,
                    add_url: 'shop/goods/add',
                    edit_url: 'shop/goods/edit',
                    del_url: 'shop/goods/del',
                    multi_url: 'shop/goods/multi',
                    table: 'goods',
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
                        {field: 'title', title: __('商品名称')},
                        {field: 'images', title: __('商品图片'), formatter: Table.api.formatter.images},
                        {field: 'price', title: __('商品价格（￥）'), operate:'BETWEEN'},
                        {field: 'virtual_price', title: __('商品价格（ZPC）'), operate:'BETWEEN'},
                        // {field: 'label_id', title: __('Label_id')},
                        {field: 'label_name', title: __('标签名称')},
                        {
                            field: 'status',
                            title: __('商品上下架'),
                            formatter:function(value, row,index) {
                                if (row.status == 1) {
                                    return '<button class="btn btn-xs btn-success btn-magic btn-ajax">上架</button>';
                                }else if(row.is_backgoods == 2) {
                                    return '<button class="btn btn-xs btn-primary btn-dialog">下架</button>';
                                }
                            }
                        },
                        {
                            field: 'is_backgoods',
                            title: __('是否允许退货'),
                            formatter:function(value, row,index) {
                                if (row.is_backgoods == 1) {
                                    return '<button class="btn btn-xs btn-success btn-magic btn-ajax">不允许</button>';
                                }else if(row.is_backgoods == 2) {
                                    return '<button class="btn btn-xs btn-primary btn-dialog">允许</button>';
                                }
                            }
                        },
                        {
                            field: 'position',
                            title: __('推荐位'),
                            formatter:function(value, row,index) {
                                if (row.position == 0) {
                                    return '<button class="btn btn-xs btn-success btn-magic btn-ajax">未推荐</button>';
                                }else if(row.position == 1) {
                                    return '<button class="btn btn-xs btn-primary btn-dialog">推荐</button>';
                                }
                            }
                        },
                        {field: 'send_address', title: __('发货地')},
                        {field: 'sort_name', title: __('分类名称')},
                        // {field: 'sort_two_id', title: __('二级分类')},
                        {field: 'created_at', title: __('创建时间') ,operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'addtabs',
                                    text: __('设置规格'),
                                    title: __('设置规格'),
                                    classname: 'btn btn-xs btn-warning btn-addtabs',
                                    icon: 'fa fa-folder-o',
                                    url: 'shop/goods/prop?ids={$ids}'
                                }
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