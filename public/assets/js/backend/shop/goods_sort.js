define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'shop/goods_sort/index' + location.search,
                    add_url: 'shop/goods_sort/add',
                    edit_url: 'shop/goods_sort/edit',
                    del_url: 'shop/goods_sort/del',
                    multi_url: 'shop/goods_sort/multi',
                    table: 'goods_sort',
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
                        {field: 'pid', title: __('Pid')},
                        {field: 'name', title: __('Name')},
                        {field: 'image', title: __('Image'), events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'created_at', title: __('Created_at'),operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            // 监控值的变化
            $("#c-pid").change(function(){
                var pid = $(this).val();
                if (pid == '顶级') {
                    $('#image_none').hide();
                }else{
                    $('#image_none').show();
                }
            })
            Controller.api.bindevent();
        },
        edit: function () {
            var name = $('#c-pid').val();
            if (name == 0) {
                $('#image_none').hide();
            }else{
                $('#image_none').show();
            }
            // alert(name);
            $("#c-pid").change(function(){
                var pid = $(this).val();
                if (pid == '顶级') {
                    $('#image_none').hide();
                }else{
                    $('#image_none').show();
                }
            })
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        },


    };
    return Controller;
});