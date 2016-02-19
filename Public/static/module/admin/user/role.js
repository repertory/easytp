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
				//查看描述
				case 'description':
					module.handle.detail(value);
					break;
			}
		},

		//对应php代码controller中的action名称
		action: {
			//添加
			roleAdd: function(e, row, rows){
				method.dialog.form(e, {
					width : 400,
					height: 290
				}, function(){
					module.handle.refresh();
				});
			},

			//编辑
			roleEdit: function(e, row, rows){
				if(!row){
					method.messager.tip('未选择数据', 'error');
					return false;
				}

				var href = $(e).data('href');
				href += href.indexOf('?') != -1 ? '&id=' + row.roleid : '?id=' + row.roleid;

				method.dialog.form(e, {
					width : 400,
					height: 290,
					href  : href
				}, function(){
					module.handle.refresh();
				});
			},

			//删除
			roleDelete: function(e, row, rows){
				if(!row){
					method.messager.tip('未选择数据', 'error');
					return false;
				}
				var href = $(e).data('href');
				var ids  = [];
				for(var i = 0; i < rows.length; i++){
					ids.push(rows[i]['roleid']);
				}

				$.messager.confirm('系统提示', '确定要继续吗？', function (res) {
					if(!res) return false;

					method.request.post(href, {ids: ids.join(',')}, function(){
						module.handle.refresh();
					});
				});
			},

			//权限
			rolePriv: function(e, row, rows){
				if(!row){
					method.messager.tip('未选择数据', 'error');
					return false;
				}

				if(row.roleid == 1){
					method.messager.tip('该角色无需设置权限', 'error');
					return false;
				}

				var href = $(e).data('href');
				href += href.indexOf('?') != -1 ? '&id=' + row.roleid : '?id=' + row.roleid;

				var option = {
					width  : 400,
					height : 300,
					href   : href
				};
				option['buttons'] = [{
					text: '确定',
					iconCls: 'fa fa-check',
					handler: function(){
						module.handle.rolePriv(href);
					}
				},{
					text: '取消',
					iconCls: 'fa fa-close',
					handler: function(){
						$(method.dialog.dialog).dialog('close');
					}
				}];
				method.dialog.page(e, option);
			},

			//栏目权限
			roleCat: function(e, row, rows){
				if(!row){
					method.messager.tip('未选择数据', 'error');
					return false;
				}

				if(row.roleid == 1){
					method.messager.tip('该角色无需设置权限', 'error');
					return false;
				}

				var href = $(e).data('href');
				href += href.indexOf('?') != -1 ? '&id=' + row.roleid : '?id=' + row.roleid;

				var option = {
					width  : 600,
					height : 400,
					href   : href,
					onLoad : function(){
						var dialog   = method.dialog.dialog;
						var treegrid = $(dialog).find('table:first').attr('id');
						treegrid = '#' + treegrid;
						method.treegrid.init(treegrid, {
							pagination: false,
							idField  : 'catid',
							treeField: 'catname',
							singleSelect: true,
							rownumbers: false,
							onBeforeLoad: function(row, param){
								if(!row) param.id = 0;
							},
							onDblClickRow: function(arr){
								//当前行的全选或取消
								var checked = $(dialog).find('input[type="checkbox"][data-catid="' + arr.catid + '"]:checked').length;
								$(dialog).find('input[type="checkbox"][data-catid="' + arr.catid + '"]').each(function(i, obj){
									if(arr.auths.length != checked){
										this.checked = true;
									}else{
										this.checked = false;
									}
								});
							},
							handle: {
								//收起全部
								collapseAll: function(e, row, rows){
									$(treegrid).treegrid('collapseAll');
								},

								//展开全部
								expandAll: function(e, row, rows){
									$(treegrid).treegrid('expandAll');
								},
							}
						});
					}
				};
				option['buttons'] = [{
					text: '确定',
					iconCls: 'fa fa-check',
					handler: function(){
						module.handle.roleCat(href);
					}
				},{
					text: '取消',
					iconCls: 'fa fa-close',
					handler: function(){
						$(method.dialog.dialog).dialog('close');
					}
				}];
				method.dialog.page(e, option);
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
				if(content.length < 50) return false;

				method.dialog.content(null, {
					title       : '详细参数',
					content     : '<p>' + content + '</p>',
					iconCls     : 'fa fa-file-o',
					width       : 350,
					height      : 200,
					maximizable : true,
					resizable   : true
				});
			},

			rolePriv: function(href){
				var tree = $('.easyui-tree:first', method.dialog.dialog).tree('getChecked');
				var ids = [];
				for(var i = 0; i < tree.length; i++){
					ids.push(tree[i]['id']);
					ids.push(tree[i]['attributes']['parent']);
				}
				ids = module.handle.unique(ids.join(',').split(','));
				method.request.post(href, {ids: ids.join(',')}, function(){
					$(method.dialog.dialog).dialog('close');
				});
			},

			roleCat: function(href){
				var dialog = method.dialog.dialog;
				var data   = {};
				$(dialog).find('input[type="checkbox"]:checked').each(function(i, obj){
					var catid = $(this).data('catid');
					var val   = $(this).val();
					if(catid && val){
						if(!data[catid]) data[catid] = [];
						data[catid].push(val);
					}
				});
				method.request.post(href, {info: JSON.stringify(data)}, function(){
					$(method.dialog.dialog).dialog('close');
				});
			},

			//数组去空去重
			unique: function(arr){
				var hash   = {},
					len    = arr.length,
					result = [];

				for (var i = 0; i < len; i++){
					if(!arr[i]) continue;

					if (!hash[arr[i]]){
						hash[arr[i]] = true;
						result.push(arr[i]);
					}
				}
				return result;
			}
		}
	};

	return module;
});
