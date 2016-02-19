define(['jquery.easyui.app', 'common/method', 'index/left'], function($, method, left) {
	var module = {
		option: {
			panel: null
		},

		init: function(e){
			this.option.panel = e;

			$('form', module.option.panel).on('submit', function(){
				return false;
			});

			$(module.option.panel).on('click', '.toolbar-handle', function(){
				var handle = $(this).data('handle');
				if(module['handle'] && typeof module['handle'][handle] == 'function'){
					module['handle'][handle](this);
				}
			});

			$(module.option.panel).on('keyup', 'input', function(event){
				if(event.keyCode ==13) module.handle.save();
				return false;
			});

			$('.editor', module.option.panel).each(function(){
				var id = $(this).attr('id');
				method.editor.init(id);
			});

			$('.propertygrid', module.option.panel).each(function(){
				method.propertygrid.init(this);
				$(this).propertygrid('resize');
			});
		},

		handle: {
			refresh: function(){
				left.refresh();
			},

			save: function(){
				var data  = {};
				var error = null;

				$('.form', module.option.panel).each(function(){
					if(error) return false;
					var form = this;

					var group = $(this).attr('group') || $(this).data('group');
					if(!group) return false;

					if(!data[group]) data[group] = {};

					var info = $(this).serializeArray();
					for(var i in info){
						data[group][info[i]['name']] = info[i]['value'];
					}

					$('input.required,textarea.required,select.required', this).each(function(){
						if(error) return false;

						var name = $(this).attr('name') || $(this).data('name');
						if(!name) return false;

						if(!data[group][name]){
							//获取当前内容对应的tab索引，并同时切换
							var tabs = 0;
							$('.easyui-tabs:first', module.option.panel).find('.panel').each(function(i){
								$('.form', this).each(function(){
									if(this == form) tabs = i;
								});
							});
							$('.easyui-tabs', module.option.panel).eq(0).tabs('select', tabs);

							//光标选中错误位置
							var type = $(this).attr('type') || ($(this).data('type') || 'input');
							switch(type){
								case 'editor':
									var focus = $(this).attr(type) || $(this).data(type);
									if(focus) method.editor.focus(focus);
									break;

								default:
									$(this).trigger('focus');
							}

							//错误提示
							var text = $(this).parents('td').prev('th').text();
							error = text + '不能为空';
							return false;
						}
					});
				});

				$('.propertygrid.datagrid-f', module.option.panel).each(function(){
					if(error) return false;
					var group = $(this).attr('group') || $(this).data('group');
					if(!group) return false;

					if(!data[group]) data[group] = {};
					var $propertygrid = $(this);
					var rows = $propertygrid.propertygrid('getRows');

					for(var i=0; i<rows.length; i++){
						$propertygrid.propertygrid('endEdit', i);

						//验证字段
						if(rows[i]['required'] && !rows[i]['value']){
							$propertygrid.propertygrid('selectRow', i).propertygrid('beginEdit', i);
							var ed = $propertygrid.propertygrid('getEditor', {index:i,field:'value'});

							$(ed.target).trigger('focus');
							error = rows[i]['name'].replace('*', '') + '不能为空';
							break;
						}

						data[group][rows[i]['key']] = rows[i]['value'];
					}
				});

				if(error){
					method.messager.tip(error, 'error');
					return false;
				}

				var url = $(module.option.panel).data('submit') || $(module.option.panel).data('href');
				method.request.post(url, data);

			}
		}
	};

	return module;
});
