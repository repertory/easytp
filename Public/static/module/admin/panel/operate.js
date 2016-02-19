define(['jquery.easyui.app', 'common/method'], function($, method) {
	var module = {
		option: {
			datagrid: null
		},

		init: function(e){
			this.option.datagrid = e;

			method.datagrid.init(e, this);
		},

		//点击操作
		onClickCell: function(index, field, value){
			switch(field){
				//查看参数详情
				case 'querystring':
					module.handle.detail(value);
					break;
			}
		},

		//对应php代码controller中的action名称
		action: {
			//删除数据
			operateDelete: function(e, row, rows){
				var url = $(e).data('href');
				method.request.post(url, null, function(){
					module.handle.refresh();
				});
			}
		},

		//其他操作
		handle: {
			//刷新
			refresh: function(){
				$(module.option.datagrid).datagrid('reload');
			},

			//查看参数详情
			detail: function(content){
				method.dialog.content(null, {
					title       : '详细参数',
					content     : '<pre>' + content + '</pre>',
					iconCls     : 'fa fa-file-o',
					width       : 400,
					height      : 300,
					maximizable : true,
					resizable   : true
				});
			}
		}
	};

	return module;
});