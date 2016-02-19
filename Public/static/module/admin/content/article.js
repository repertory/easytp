define(['jquery.easyui.app', 'common/method', 'index/left'], function($, method, left) {
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
			//添加
			addArticle: function(e, row, rows){
				var href  = $(e).data('href');
				left.reopen(href, true);
			},

			//编辑
			editArticle: function(e, row, rows){
				if(!row){
					method.messager.tip('未选择数据', 'error');
					return false;
				}

				var href = $(e).data('href');
				href += href.indexOf('?') != -1 ? '&id=' + row.id : '?id=' + row.id;

				left.reopen(href, true);
			},

			//删除
			deleteArticle: function(e, row, rows){
				if(!row){
					method.messager.tip('未选择数据', 'error');
					return false;
				}
				var href = $(e).data('href');
				var ids  = [];
				for(var i = 0; i < rows.length; i++){
					ids.push(rows[i]['id']);
				}

				$.messager.confirm('系统提示', '确定要继续吗？', function (res) {
					if(!res) return false;

					method.request.post(href, {ids: ids.join(',')}, function(){
						module.handle.refresh();
					});
				});
			},

			//置顶
			topArticle: function(e, row, rows){
				if(!row){
					method.messager.tip('未选择数据', 'error');
					return false;
				}
				var href = $(e).data('href');
				var ids  = [];
				for(var i = 0; i < rows.length; i++){
					ids.push(rows[i]['id']);
				}

				method.request.post(href, {ids: ids.join(',')}, function(){
					module.handle.refresh();
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
