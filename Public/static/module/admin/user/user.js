define(['jquery.easyui.app', 'common/method'], function($, method) {
	var module = {
		option: {
			datagrid: null
		},

		init: function(e){
			this.option.datagrid = e;

			method.datagrid.init(e, this);
		},

		//对应php代码controller中的action名称
		action: {
			userAdd: function(e, row, rows){
				method.dialog.form(e, {
					width : 400,
					height: 320
				}, function(){
					module.handle.refresh();
				});
			},

			userEdit: function(e, row, rows){
				if(!row){
					method.messager.tip('未选择数据', 'error');
					return false;
				}

				var href = $(e).data('href');
				href += href.indexOf('?') != -1 ? '&id=' + row.userid : '?id=' + row.userid;

				method.dialog.form(e, {
					width : 400,
					height: 250,
					href  : href
				}, function(){
					module.handle.refresh();
				});
			},

			userDelete: function(e, row, rows){
				if(!row){
					method.messager.tip('未选择数据', 'error');
					return false;
				}
				var href = $(e).data('href');
				var ids  = [];
				for(var i = 0; i < rows.length; i++){
					ids.push(rows[i]['userid']);
				}

				$.messager.confirm('系统提示', '确定要继续吗？', function (res) {
					if(!res) return false;

					method.request.post(href, {ids: ids.join(',')}, function(){
						module.handle.refresh();
					});
				});
			},

			userReset: function(e, row, rows){
				if(!row){
					method.messager.tip('未选择数据', 'error');
					return false;
				}

				var href = $(e).data('href');
				$.messager.confirm('系统提示', '确定要继续吗？', function (res) {
					if(!res) return false;

					method.request.post(href, {id: row.userid}, function(res){
						$.messager.alert('提示信息', '密码已重置为：' + res.password + '，请牢记新密码！', 'info');
					});
				});
			}
		},

		//其他操作
		handle: {
			//刷新
			refresh: function(){
				$(module.option.datagrid).datagrid('reload');
			}
		}
	};

	return module;
});
