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
			addonInstall: function(e, row, rows){
				if(!row){
					method.messager.tip('未选择数据', 'error');
					return false;
				}

				if(row.status_text != '未安装'){
					method.messager.tip('请卸载后再安装', 'error');
					return false;
				}

				var href = $(e).data('href');

				method.request.post(href, {name: row.name}, function(){
					module.handle.refresh();
				});
			},

			addonUninstall: function(e, row, rows){
				if(!row){
					method.messager.tip('未选择数据', 'error');
					return false;
				}

				if(row.status_text == '未安装'){
					method.messager.tip('未安装此插件', 'error');
					return false;
				}

				var href = $(e).data('href');

				$.messager.confirm('系统提示', '确定要继续吗？', function (res) {
					if (!res) return false;

					method.request.post(href, {id: row.id}, function () {
						module.handle.refresh();
					});
				});
			},

			addonConfig: function(e, row, rows){
				if(!row){
					method.messager.tip('未选择数据', 'error');
					return false;
				}

				if(row.status_text != '启用'){
					method.messager.tip('请先启用此插件', 'error');
					return false;
				}

				var href = $(e).data('href');
				href += href.indexOf('?') != -1 ? '&id=' + row.id : '?id=' + row.id;

				method.dialog.form(e, {
					width       : 400,
					height      : 400,
					href        : href,
					maximizable : true,
					resizable   : true
				}, function(){
					module.handle.refresh();
				});
			},

			addonDisabled: function(e, row, rows){
				if(!row){
					method.messager.tip('未选择数据', 'error');
					return false;
				}

				if(row.status_text == '未安装'){
					method.messager.tip('请先安装此插件', 'error');
					return false;
				}

				var href = $(e).data('href');

				method.request.post(href, {id: row.id}, function () {
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
			}

		}
	};

	return module;
});
