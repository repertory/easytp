define(['jquery.easyui.app', 'common/method'], function($, method) {
	var module = {
		option: {
			treegrid : null
		},

		idField  : 'catid',
		treeField: 'catname',

		init: function(e){
			this.option.treegrid = e;

			method.treegrid.init(e, this);
		},

		//防止更换页面显示问题
		onBeforeLoad: function(row, param){
			if(!row) param.id = 0;
		},

		//对应php代码controller中的action名称
		action: {
			categoryAdd: function(e, row, rows){
				var href = $(e).data('href');
				if(row){
					href += href.indexOf('?') != -1 ? '&parentid=' + row.catid : '?parentid=' + row.catid;
				}

				method.dialog.form(e, {
					width : 400,
					height: 400,
					href  : href
				}, function(){
					var target = '';
					if(row){
						var node = $(module.option.treegrid).treegrid('getParent', row.catid);
						if(node) target = node.catid;
					}
					module.handle.refresh(target);
				});
			},

			categoryEdit: function(e, row, rows){
				if(!row){
					method.messager.tip('未选择数据', 'error');
					return false;
				}

				var href = $(e).data('href');
				href += href.indexOf('?') != -1 ? '&id=' + row.catid : '?id=' + row.catid;

				method.dialog.form(e, {
					width : 400,
					height: 400,
					href  : href
				}, function(){
					var node   = $(module.option.treegrid).treegrid('getParent', row.catid);
					var target = node ? node.catid : '';
					module.handle.refresh(target);
				});
			},

			categoryDelete: function(e, row, rows){
				if(!row){
					method.messager.tip('未选择数据', 'error');
					return false;
				}
				var href = $(e).data('href');
				var ids  = [];
				for(var i = 0; i < rows.length; i++){
					ids.push(rows[i]['catid']);
				}

				$.messager.confirm('系统提示', '确定要继续吗？', function (res) {
					if(!res) return false;

					var node   = rows.length > 1 ? null : $(module.option.treegrid).treegrid('getParent', row.catid);
					var target = node ? node.catid : '';
					if(target){
						var node   = $(module.option.treegrid).treegrid('getParent', node.catid);
						var target = node ? node.catid : '';
					}

					method.request.post(href, {ids: ids.join(',')}, function(){
						module.handle.refresh(target);
					});
				});
			}
		},

		//其他操作
		handle: {
			//刷新
			refresh: function(target){
				$(module.option.treegrid).treegrid('reload', target);
			},

			//收起全部
			collapseAll: function(e, row, rows){
				$(module.option.treegrid).treegrid('collapseAll');
			},

			//展开全部
			expandAll: function(e, row, rows){
				$(module.option.treegrid).treegrid('expandAll');
			},

			//收起
			collapse: function(e, row, rows){
				$(module.option.treegrid).treegrid('collapse', row.catid);
			},

			//展开
			expand: function(e, row, rows){
				$(module.option.treegrid).treegrid('expand', row.catid);
			}
		}
	};

	return module;
});
