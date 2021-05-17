define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'order/index' + location.search,
                    // add_url: 'order/add',
                    // edit_url: 'order/edit',
                    // del_url: 'order/del',
                    multi_url: 'order/multi',
                    table: 'order',
                }
            });

            var table = $("#table");

            table.on('load-success.bs.table', function (e, data) {
                //这里可以获取从服务端获取的JSON数据
                console.log(data);
                //这里我们手动设置底部的值
                $("#price").text(data.price);
                $("#price_rmb").text(data.price_rmb);
            });

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'user_id', title: __('购买人id')},
                        {field: 'user.mobile', title: __('购买人手机号'),operate:false},
                        {field: 'order_sn', title: __('订单号')},
                        {field: 'total_price', title: __('虚拟币总数（ZPC）'), operate:'BETWEEN'},
                        {field: 'total_price_rmb', title: __('人民币总数（￥）'), operate:'BETWEEN'},
                        {
                            field: 'status',
                            title: __('发货状态'),
                            formatter:function(value,row,index){
                                if (row.status == 1) {
                                    return '待支付';
                                }else if(row.status == 2) {
                                    return '待发货';
                                }else if(row.status == 3) {
                                    return '已发货';
                                }else if(row.status == 4) {
                                    return '已完成';
                                }else if(row.status == 5) {
                                    return '已取消';
                                }else if(row.status == 6) {
                                    return '退货';
                                }
                            },
                        },
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
                            field: 'is_pay',
                            title: __('支付状态'),
                            formatter:function(value, row,index) {
                                if (row.is_pay == 0) {
                                    return '<button class="btn btn-xs btn-primary btn-dialog">未支付</button>';
                                }else if(row.is_pay == 1) {
                                    return '<button class="btn btn-xs btn-success btn-magic btn-ajax">已支付</button>';
                                }
                            }
                        },
                        {field: 'realname', title: __('收货人姓名')},
                        {field: 'mobile', title: __('收货人手机')},
                        {field: 'address', title: __('收货人地址')},
                        {field: 'address_info', title: __('收货人地址详情')},
                        {field: 'created_at', title: __('创建时间'),operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    text: __('详情'),
                                    title: __('详情'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'order_info?ids={ids}',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                },
                                {
                                    name: 'ajax',
                                    text: __('发货'),
                                    title: __('设置发货'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: 'order/setGoods?ids={ids}',
                                    confirm: '确认设置发货吗？',
                                    visible:function(row) {
                                        if (row.status == 2 && row.is_backgoods==0 && row.is_pay == 1) {
                                            return true;
                                        }else{
                                            return false;
                                        }
                                    },
                                    success: function (data, ret) {
                                        //Layer.alert(ret.msg + ",返回数据：" + JSON.stringify(data));
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;
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