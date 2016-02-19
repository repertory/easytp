define(['jquery.easyui.app', 'common/config', 'editor', 'common/qrcode'], function($, config, editor, qrcode) {
	var module = {
		//获取配置参数
		config   : function(key){
			if(!config[key]) return {};

			return $.extend(true, {}, config[key]); //防止config变量被修改
		},

		//消息提示模块
		messager: {
			tip: function(msg, icon, title, timeout, showType){
				var option = module.config('messager');
				var text   = []
				text.push('<div class="messager-icon messager-');
				text.push(icon || 'info');
				text.push('"></div>');
				text.push('<div>' + msg + '</div>');
				$.messager.show({
					title: title || option.title,
					msg: text.join(''),
					timeout: timeout || option.timeout,
					showType: showType || option.showType
				});
			}
		},

		//弹出层模块
		dialog: {
			dialog: '#globel-dialog',
			items:  ['submit', 'href', 'content', 'title', 'width', 'height', 'icon', 'modal', 'maximized','collapsible','minimizable','maximizable','closable','resizable','draggable'],

			/* 解析选项中自定义属性 */
			option: function(e){
				var option = module.config('dialog'); //读取默认配置文件
				if(!e) return option;

				for(var i = 0; i < this.items.length; i++){
					var key   = this.items[i];
					var value = $(e).data(key);

					switch (key){
						case 'title':
							if(!value) value = $(e).text();
							break;
						case 'content':
							if(!value) value = $(e).html();
							break;
						case 'icon':
							if(!value) value = $(e).attr('iconCls');
							key   = 'iconCls';
							break;
					}

					if(typeof value == 'undefined') continue;
					option[key] = value;
				}

				option['submit'] = option['submit'] || option['href'];
				return option;
			},

			/* 表单 支持提交功能 */
			form: function(e, merge, success, error){
				var _this  = this;
				var option = _this.option(e);

				option['buttons'] = [{
					text: '确定',
					iconCls: 'fa fa-check',
					handler: function(){
						$(_this.dialog).find('form').eq(0).form('submit', {
							onSubmit: function(){
								var isValid = $(this).form('validate');
								if (!isValid) return false;

								module.request.post(option['submit'], $(this).serialize(), function(res){
									$(_this.dialog).dialog('close');
									if(typeof success == 'function') success(res);
								}, function(res){
									if(typeof error == 'function') error(res);
								});

								return false;
							}
						});
					}
				},{
					text: '取消',
					iconCls: 'fa fa-close',
					handler: function(){
						$(_this.dialog).dialog('close');
					}
				}];
				//回车默认点击第一个按钮
				option['onLoad'] = function(){
					$(_this.dialog).find('form').eq(0).on('keyup', function(event){
						if(event.keyCode ==13) option['buttons'][0].handler();
					});
				};

				//合并参数
				if(typeof merge == 'object') $.extend(option, merge);

				$(_this.dialog).dialog(option).dialog('center');
			},

			/* 显示页面 只能关闭 */
			page: function(e, merge){
				var _this  = this;
				var option = _this.option(e);

				option['buttons'] = [{
					text: '关闭',
					iconCls: 'fa fa-close',
					handler: function(){
						$(_this.dialog).dialog('close');
					}
				}];

				//合并参数
				if(typeof merge == 'object') $.extend(option, merge);

				$(_this.dialog).dialog(option).dialog('center');
			},

			/* 显示内容(不支持href)，只能关闭 */
			content: function(e, merge){
				var _this  = this;
				var option = _this.option(e);

				option['buttons'] = [{
					text: '关闭',
					iconCls: 'fa fa-close',
					handler: function(){
						$(_this.dialog).dialog('close');
					}
				}];

				//合并参数
				if(typeof merge == 'object') $.extend(option, merge);

				option['href'] = null;
				$(_this.dialog).dialog(option).dialog('center');
			},

			/* 显示其他内容区域 */
			element: function(e, merge){
				var dialog = e;
				var option = this.option(dialog);

				//合并参数
				if(typeof merge == 'object') $.extend(option, merge);

				option['href'] = null;
				$(dialog).dialog(option).dialog('center');
			}
		},

		//datagrid
		datagrid: {
			datagrid : null,
			items    : ['title', 'icon', 'url', 'toolbar', 'tools', 'fit', 'border'],

			/* 解析选项中自定义属性 */
			option   : function(){
				var option = module.config('datagrid'); //读取默认配置文件
				for(var i = 0; i < this.items.length; i++){
					var key   = this.items[i];
					var value = $(this.datagrid).data(key);

					switch (key){
						case 'title':
							if(!value) value = $(this.datagrid).find('caption').eq(0).text();
							break;
						case 'icon':
							if(!value) value = $(this.datagrid).attr('iconCls');
							key   = 'iconCls';
							break;
					}

					if(typeof value == 'undefined') continue;
					option[key] = value;
				}
				return option;
			},

			//初始化页面
			init     : function(e, merge){
				this.datagrid = e;
				var option    = this.option();

				//合并参数
				if(typeof merge == 'object') $.extend(option, merge);

				//自动开启右键菜单功能
				if($(e).data('menu')){
					var _this = this;
					option['onRowContextMenu'] = function(e, index, row){
						if(index < 0) return false;
						var menu = $(_this.datagrid).data('menu');
						if(!$(menu)) return false;

						e.preventDefault();
						$(_this.datagrid).datagrid('unselectAll');
						$(_this.datagrid).datagrid('selectRow', index);
						$(menu).menu('show',{left: e.pageX, top: e.pageY});
					};
				}

				option['init']   && delete(option['init']);
				option['option'] && delete(option['option']);
				option['action'] && delete(option['action']);
				option['handle'] && delete(option['handle']);

				$(this.datagrid).datagrid(option);

				this.event.toolbar(e, merge);
				this.event.menu(e, merge);
			},

			//监听工具栏和菜单
			event: {
				toolbar: function(e, obj){
					var selecter = $(e).data('toolbar');
					if(!selecter) return false;

					$(selecter).on('click', '.toolbar-action', function(){
						var action = $(this).data('action');
						if(obj && obj['action'] && typeof obj['action'][action] == 'function'){
							var selected    = $(e).datagrid('getSelected');   //当前选中的行
							var allSelected = $(e).datagrid('getSelections'); //全部选中的行

							obj['action'][action](this, selected, allSelected);
						}
					});

					$(selecter).on('click', '.toolbar-handle', function(){
						var handle = $(this).data('handle');
						if(obj && obj['handle'] && typeof obj['handle'][handle] == 'function'){
							var selected    = $(e).datagrid('getSelected');   //当前选中的行
							var allSelected = $(e).datagrid('getSelections'); //全部选中的行

							obj['handle'][handle](this, selected, allSelected);
						}
					});

					$('.toolbar-search', selecter).on('click', function(){
						var option = {};
						var data       = $(this).data('data') || '[]';
						var showGroup  = $(this).data('group') === true ? 'true' : 'false';
						var close      = $(this).data('close') || false; //搜索完毕后是否关闭弹出层
						var scrollbar  = $(this).data('scrollbar') === true ? 18 : 0;
						option.title   = $(this).text();
						option.iconCls = $(this).attr('iconCls') || $(this).data('icon');
						option.content = '<table class="easyui-propertygrid" data-options="data:' + data + ',showGroup:' + showGroup + ',border:false,fit:true,scrollbarSize:' + scrollbar + ',columns:[[{field:\'name\',title:\'字段名称\',width:100},{field:\'value\',title:\'筛选条件\',width:200}]]"></table>';

						option.width   = $(this).data('width');
						option.height  = $(this).data('height');

						option['buttons'] = [{
							text: '确定',
							iconCls: 'fa fa-check',
							handler: function(){
								var $propertygrid     = $(module.dialog.dialog).find('.easyui-propertygrid').eq(0);
								var rows              = $propertygrid.propertygrid('getRows');
								var queryParams       = $(e).datagrid('options').queryParams;
								queryParams['search'] = {};

								for(var i=0; i<rows.length; i++){
									queryParams['search'][rows[i]['field']] = rows[i]['value'];
								}
								$(e).datagrid({pageNumber: 1});

								if(close === true) $(module.dialog.dialog).dialog('close');
							}
						},{
							text: '取消',
							iconCls: 'fa fa-close',
							handler: function(){
								$(module.dialog.dialog).dialog('close');
							}
						}];

						module.dialog.content(null, option);
					});
				},
				//右键菜单
				menu: function(e, obj){
					var selecter = $(e).data('menu');
					if(!selecter) return false;

					$(selecter).on('click', '.menu-action', function(){
						var action = $(this).data('action');
						if(obj && obj['action'] && typeof obj['action'][action] == 'function'){
							var selected    = $(e).datagrid('getSelected');   //当前选中的行
							var allSelected = $(e).datagrid('getSelections'); //全部选中的行

							obj['action'][action](this, selected, allSelected);
						}
					});

					$(selecter).on('click', '.menu-handle', function(){
						var handle = $(this).data('handle');
						if(obj && obj['handle'] && typeof obj['handle'][handle] == 'function'){
							var selected    = $(e).datagrid('getSelected');   //当前选中的行
							var allSelected = $(e).datagrid('getSelections'); //全部选中的行

							obj['handle'][handle](this, selected, allSelected);
						}
					});
				}
			}
		},

		//treegrid
		treegrid: {
			treegrid : null,
			items    : ['title', 'icon', 'url', 'toolbar', 'tools', 'id', 'name', 'lines', 'animate', 'fit', 'border'],

			/* 解析选项中自定义属性 */
			option   : function(){
				var option = module.config('treegrid'); //读取默认配置文件
				for(var i = 0; i < this.items.length; i++){
					var key   = this.items[i];
					var value = $(this.treegrid).data(key);

					switch (key){
						case 'title':
							if(!value) value = $(this.treegrid).find('caption').eq(0).text();
							break;
						case 'icon':
							if(!value) value = $(this.treegrid).attr('iconCls');
							key   = 'iconCls';
							break;
						case 'id':
							if(!value) value = $(this.treegrid).attr('idField');
							key   = 'idField';
							break;
						case 'name':
							if(!value) value = $(this.treegrid).attr('treeField');
							key   = 'treeField';
							break;
					}

					if(typeof value == 'undefined') continue;
					option[key] = value;
				}
				return option;
			},

			//初始化页面
			init     : function(e, merge){
				this.treegrid = e;
				var option    = this.option();

				//合并参数
				if(typeof merge == 'object') $.extend(option, merge);

				//自动开启右键菜单功能
				if($(e).data('menu')){
					var _this = this;
					option['onContextMenu'] = function(e, row){
						if(!row) return false;
						var menu = $(_this.treegrid).data('menu');
						if(!$(menu)) return false;

						e.preventDefault();
						$(_this.treegrid).treegrid('unselectAll');
						var id = $(_this.treegrid).treegrid('options').idField || 'id';
						$(_this.treegrid).treegrid('select', row[id]);
						$(menu).menu('show',{left: e.pageX, top: e.pageY});
					};
				}

				option['init']   && delete(option['init']);
				option['option'] && delete(option['option']);
				option['action'] && delete(option['action']);
				option['handle'] && delete(option['handle']);

				$(this.treegrid).treegrid(option);

				this.event.toolbar(e, merge);
				this.event.menu(e, merge);
			},

			//监听工具栏
			event: {
				toolbar: function(e, obj){
					var selecter = $(e).data('toolbar');
					if(!selecter) return false;

					$(selecter).on('click', '.toolbar-action', function(){
						var action = $(this).data('action');
						if(obj && obj['action'] && typeof obj['action'][action] == 'function'){
							var selected    = $(e).treegrid('getSelected');   //当前选中的行
							var allSelected = $(e).treegrid('getSelections'); //全部选中的行

							obj['action'][action](this, selected, allSelected);
						}
					});

					$(selecter).on('click', '.toolbar-handle', function(){
						var handle = $(this).data('handle');
						if(obj && obj['handle'] && typeof obj['handle'][handle] == 'function'){
							var selected    = $(e).treegrid('getSelected');   //当前选中的行
							var allSelected = $(e).treegrid('getSelections'); //全部选中的行

							obj['handle'][handle](this, selected, allSelected);
						}
					});
				},
				menu: function(e, obj){
					var selecter = $(e).data('menu');
					if(!selecter) return false;

					$(selecter).on('click', '.menu-action', function(){
						var action = $(this).data('action');
						if(obj && obj['action'] && typeof obj['action'][action] == 'function'){
							var selected    = $(e).treegrid('getSelected');   //当前选中的行
							var allSelected = $(e).treegrid('getSelections'); //全部选中的行

							obj['action'][action](this, selected, allSelected);
						}
					});

					$(selecter).on('click', '.menu-handle', function(){
						var handle = $(this).data('handle');
						if(obj && obj['handle'] && typeof obj['handle'][handle] == 'function'){
							var selected    = $(e).treegrid('getSelected');   //当前选中的行
							var allSelected = $(e).treegrid('getSelections'); //全部选中的行

							obj['handle'][handle](this, selected, allSelected);
						}
					});
				}
			}
		},

		//propertygrid
		propertygrid: {
			propertygrid : null,
			items        : ['title', 'icon', 'url', 'toolbar', 'tools', 'fit', 'border'],

			/* 解析选项中自定义属性 */
			option       : function(){
				var option = module.config('propertygrid'); //读取默认配置文件
				for(var i = 0; i < this.items.length; i++){
					var key   = this.items[i];
					var value = $(this.propertygrid).data(key);

					switch (key){
						case 'title':
							if(!value) value = $(this.propertygrid).find('caption').eq(0).text();
							break;
						case 'icon':
							if(!value) value = $(this.propertygrid).attr('iconCls');
							key   = 'iconCls';
							break;
					}

					if(typeof value == 'undefined') continue;
					option[key] = value;
				}
				return option;
			},

			//初始化页面
			init         : function(e, merge){
				this.propertygrid = e;
				var option        = this.option();

				//合并参数
				if(typeof merge == 'object') $.extend(option, merge);

				//自动开启右键菜单功能
				if($(e).data('menu')){
					var _this = this;
					option['onRowContextMenu'] = function(e, index, row){
						if(index < 0) return false;
						var menu = $(_this.propertygrid).data('menu');
						if(!$(menu)) return false;

						e.preventDefault();
						$(_this.propertygrid).propertygrid('unselectAll');
						$(_this.propertygrid).propertygrid('selectRow', index);
						$(menu).menu('show',{left: e.pageX, top: e.pageY});
					};
				}

				option['init']   && delete(option['init']);
				option['option'] && delete(option['option']);
				option['action'] && delete(option['action']);
				option['handle'] && delete(option['handle']);

				$(this.propertygrid).propertygrid(option);

				this.event.toolbar(e, merge);
				this.event.menu(e, merge);
			},

			//监听工具栏
			event: {
				toolbar: function(e, obj){
					var selecter = $(e).data('toolbar');
					if(!selecter) return false;

					$(selecter).on('click', '.toolbar-action', function(){
						var action = $(this).data('action');
						if(obj && obj['action'] && typeof obj['action'][action] == 'function'){
							var selected    = $(e).propertygrid('getSelected');   //当前选中的行
							var allSelected = $(e).propertygrid('getSelections'); //全部选中的行

							obj['action'][action](this, selected, allSelected);
						}
					});

					$(selecter).on('click', '.toolbar-handle', function(){
						var handle = $(this).data('handle');
						if(obj && obj['handle'] && typeof obj['handle'][handle] == 'function'){
							var selected    = $(e).propertygrid('getSelected');   //当前选中的行
							var allSelected = $(e).propertygrid('getSelections'); //全部选中的行

							obj['handle'][handle](this, selected, allSelected);
						}
					});
				},

				menu: function(e, obj){
					var selecter = $(e).data('menu');
					if(!selecter) return false;

					$(selecter).on('click', '.menu-action', function(){
						var action = $(this).data('action');
						if(obj && obj['action'] && typeof obj['action'][action] == 'function'){
							var selected    = $(e).propertygrid('getSelected');   //当前选中的行
							var allSelected = $(e).propertygrid('getSelections'); //全部选中的行

							obj['action'][action](this, selected, allSelected);
						}
					});

					$(selecter).on('click', '.menu-handle', function(){
						var handle = $(this).data('handle');
						if(obj && obj['handle'] && typeof obj['handle'][handle] == 'function'){
							var selected    = $(e).propertygrid('getSelected');   //当前选中的行
							var allSelected = $(e).propertygrid('getSelections'); //全部选中的行

							obj['handle'][handle](this, selected, allSelected);
						}
					});
				}
			}
		},

		//request
		request: {
			post: function(url, data, success, error){
				if(!url) return false;

				var option = module.config('request');

				$.ajax({
					url     : url,
					type    : 'POST',
					data    : data || {},
					dataType: option.dataType,
					timeout : option.timeout,
					success : function(res){
						if(res.status){
							module.messager.tip(res.info || '操作成功', 'info');
							if(typeof success == 'function') success(res);
						}else{
							module.messager.tip(res.info || '操作失败', 'error');
							if(typeof error == 'function') error(res);
						}
					},
					error   : function(XMLHttpRequest, textStatus, errorThrown){
						console.log([XMLHttpRequest, textStatus, errorThrown]);
						module.messager.tip('系统错误，错误信息[' + textStatus + ']', 'error');
					},
					beforeSend: function(){
						$.messager.progress({text:'处理中，请稍候...'});
					},
					complete: function(){
						$.messager.progress('close');
					}
				});
			}
		},

		//上传模块
		upload: {
			items: ['id', 'href', 'upload', 'multiple', 'accept', 'size', 'name'],
			option: function(e, merge){
				var option = {
					id       : 'globel-upload',
					dialog   : false,
					upload   : '',
					callback : null,
					valid    : null,
					name     : 'upload',
					multiple : false,
					accept   : '*/*'
				};

				for(var i = 0; i < this.items.length; i++){
					var key   = this.items[i];
					var value = $(e).data(key);

					if(typeof value == 'undefined') continue;
					option[key] = value;
				}

				if(!option.upload) option.upload = option.href || '';

				//合并参数
				if(typeof merge == 'object') $.extend(option, merge);
				return option;
			},

			//点击上传
			click: function(e, merge){
				var option = this.option(e, merge);

				if(!option.upload){
					module.messager.tip('缺少上传参数', 'error');
					return false;
				}

				var html = [];
				html.push('<form style="padding:15px 10px;text-align:center">');
				html.push('<input type="file" name="' + option.name + '"');
				if(option.multiple) html.push(' multiple');
				if(option.accept) html.push(' accept="' + option.accept + '"');
				html.push(' />');
				html.push('</form>');

				$('#globel-upload').html(html.join(''));

				var $selecter = $('input[type="file"][name="upload"]', '#' + option.id);
				this.init($selecter, option);
				$('#' + option.id).find('input[type="file"][name="upload"]:first').trigger('click');
			},

			//拖拽上传
			drag: function(e, merge){
				var option = this.option(e, merge);

				if(!option.upload){
					module.messager.tip('缺少上传参数', 'error');
					return false;
				}

				option['valid'] = function(file){
					if(option.accept){
						var valid = false;
						var type  = file.type.replace(/^\s+|\s+$/g, '').toLocaleLowerCase().split('/');
						var types = option.accept.toLocaleLowerCase().split(',');
						for(var i = 0; i < types.length; i++){
							var info = types[i].replace(/^\s+|\s+$/g, '').split('/');
							var step = 0;
							if(info[1] == '*'){
								step++;
							}else{
								if(type[0] == info[0]) step++;
							}
							if(info[1] == '*'){
								step++;
							}else{
								if(type[1] == info[1]) step++;
							}
							if(step == 2) return true;
						}
						if(!valid) module.messager.tip('不支持的文件类型', 'error');
						return valid;
					}
					return true;
				}

				var $selecter = $(e);
				this.init($selecter, option);
			},

			//初始化上传
			init: function(selecter, option){
				selecter.html5Upload({
					name   : option.name,
					postUrl: option.upload,
					valid: function(file){
						if(option.size){
							if(file.size > option.size){
								module.messager.tip('文件大小超过限制', 'error');
								return false;
							}
						}

						if(option.valid) return option.valid(file);

						return true;
					},
					onServerAbort: function(e, file){
						try{
							$.messager.progress('close');
						}catch(e){}

						if(option.callback) option.callback({status:0, info:'传输被取消'}, file);
					},
					onServerError: function(e, file){
						try{
							$.messager.progress('close');
						}catch(e){}

						if(option.callback) option.callback({status:0, info:'传输错误'}, file);
					},
					onServerProgress : function(e, file){
						if(e.lengthComputable) {
							var percent = Math.floor(e.loaded / e.total * 100);
							$.messager.progress({
								title: '文件名：' + file.name.substring(0, 30),
								text: '上传中：' + percent + '%' ,
								interval: 0
							});
							$.messager.progress('bar').find('div.progressbar-value').width(percent + '%');
							if(percent == 100) $.messager.progress('close');
						}
					},
					onSuccess: function(e, file, res){
						var json = {status:0, info:'返回数据格式有误'};
						try{
							json = $.parseJSON(res);
						}catch(e){}

						if(option.callback) option.callback(json, file);
					},
					onError: function(e, file, res){
						if(option.callback) option.callback({status:0, info:res.statusText}, file);
					}
				});

				//关闭弹出层
				if(option.dialog){
					$('#' + option.id).dialog('close');
					option.dialog = false;
				}
			}
		},

		//图片模块
		image: {
			crop: function(e, merge){
				var option = {
					width             : $(e).data('width') || '240',
					height            : $(e).data('height') || '180',
					cropUrl           : $(e).data('crop') || $(e).data('href'),
					loadPicture       : $(e).attr('src') || $(e).data('src'),
					callback          : null,

					modal             : true,
					imgEyecandy       : true,
					imgEyecandyOpacity: 0.1,
					loaderHtml        : '<div class="loader bubblingG"><span id="bubblingG_1"></span><span id="bubblingG_2"></span><span id="bubblingG_3"></span></div>',
					onReset           : function(){
						this.destroy();
					},
					onError           : function(error){
						module.messager.tip(error, 'error');
					}
				};

				//合并参数
				if(typeof merge == 'object') $.extend(option, merge);

				if(!option.cropUrl || !option.loadPicture) return false;
				if(/^\//.test(option.loadPicture)) option.loadPicture = window.location.origin + option.loadPicture;

				var subfix = $(e).attr('subfix') || $(e).data('subfix');
				if(subfix) option.cropUrl += option.cropUrl.indexOf('?') != -1 ? '&subfix=' + decodeURIComponent(subfix) : '?subfix=' + decodeURIComponent(subfix);

				if(typeof option.callback == 'function'){
					option.onAfterImgCrop = option.callback;
					delete(option.callback);
				}
				$('#globel-croppic').width(option.width).height(option.height);
				new Croppic('globel-croppic', option);
			}
		},

		//编辑器模块
		editor: {
			items: ['toolbar', 'limit', 'width', 'height'],
			option: function(e){
				var option = module.config('editor') || {}; //读取默认配置文件
				for(var i = 0; i < this.items.length; i++){
					var key   = this.items[i];
					var value = $(e).data(key);

					switch (key){
						case 'toolbar':
							key = 'toolbars';
							switch(value){
								case 'mini':
									value = [[
										'fontfamily', 'fontsize', '|',
										'bold', 'italic', 'underline', 'strikethrough', '|',
										'forecolor', 'backcolor', 'insertorderedlist', 'insertunorderedlist', '|',
										'justifyleft', 'justifycenter', 'justifyright', 'justifyjustify', '|',
										'link', 'unlink', '|', 'source'
									]];
									break;
								default:
									value = undefined;
							}
							break;
						case 'limit':
							key = 'maximumWords';
							break;
						case 'width':
							key = 'initialFrameWidth';
							break;
						case 'height':
							key = 'initialFrameHeight';
							break;
					}

					if(typeof value == 'undefined') continue;
					option[key] = value;
				}
				return option;
			},
			init: function(id, merge){
				var option = this.option('#' + id);

				//合并参数
				if(typeof merge == 'object') $.extend(option, merge);

				return editor.getEditor(id, option);
			},
			focus: function(id){
				editor.getEditor(id).focus();
			}
		},

		//全屏模式
		fullscreen: {
			init: function() {
				var val;
				var valLength;

				var fnMap = [
					[
						'requestFullscreen',
						'exitFullscreen',
						'fullscreenElement',
						'fullscreenEnabled',
						'fullscreenchange',
						'fullscreenerror'
					],
					// new WebKit
					[
						'webkitRequestFullscreen',
						'webkitExitFullscreen',
						'webkitFullscreenElement',
						'webkitFullscreenEnabled',
						'webkitfullscreenchange',
						'webkitfullscreenerror'

					],
					// old WebKit (Safari 5.1)
					[
						'webkitRequestFullScreen',
						'webkitCancelFullScreen',
						'webkitCurrentFullScreenElement',
						'webkitCancelFullScreen',
						'webkitfullscreenchange',
						'webkitfullscreenerror'

					],
					[
						'mozRequestFullScreen',
						'mozCancelFullScreen',
						'mozFullScreenElement',
						'mozFullScreenEnabled',
						'mozfullscreenchange',
						'mozfullscreenerror'
					],
					[
						'msRequestFullscreen',
						'msExitFullscreen',
						'msFullscreenElement',
						'msFullscreenEnabled',
						'MSFullscreenChange',
						'MSFullscreenError'
					]
				];

				var i = 0;
				var l = fnMap.length;
				var ret = {};

				for (; i < l; i++) {
					val = fnMap[i];
					if (val && val[1] in document) {
						for (i = 0, valLength = val.length; i < valLength; i++) {
							ret[fnMap[0][i]] = val[i];
						}
						return ret;
					}
				}

				return false;
			},
			full: function(elem) {
				var raw = module.fullscreen.init();
				if(!raw) return false;

				var keyboardAllowed = typeof Element !== 'undefined' && 'ALLOW_KEYBOARD_INPUT' in Element;
				var request = raw.requestFullscreen;

				elem = elem || document.documentElement;

				if (/5\.1[\.\d]* Safari/.test(navigator.userAgent)) {
					elem[request]();
				} else {
					elem[request](keyboardAllowed && Element.ALLOW_KEYBOARD_INPUT);
				}
			},
			mini: function() {
				var raw = module.fullscreen.init();
				if(!raw) return false;
				document[raw.exitFullscreen]();
			},
			toggle: function(elem) {
				if (this.status()) {
					this.mini();
				} else {
					this.full(elem);
				}
			},
			status: function(){
				var raw = module.fullscreen.init();
				if(!raw) return false;
				return !!document[raw.fullscreenElement];
			}
		},

		//二维码
		qrcode: {
			qrcode: '#globel-qrcode',
			items: ['text', 'title', 'size', 'color', 'bgcolor', 'round', 'shadow', 'img'],
			option: function(e){
				var option = module.config('qrcode');

				for(var i = 0; i < this.items.length; i++){
					var key   = this.items[i];
					var value = $(e).data(key);

					switch (key){
						case 'title':
							if(!value) value = $(e).attr('title') || '生成二维码';
							break;
						case 'text':
							if(!value) value = $(e).text();
							break;
						case 'bgcolor':
							if(value) key = 'background';
							break;
						case 'round':
							if(value){
								key   = 'type';
								value = 'round';
							}
							break;
						case 'img':
							if(value) value = {src: value};
							break;
					}

					if(typeof value == 'undefined') continue;
					option[key] = value;
				}
				return option;
			},
			show: function(e, merge){
				var option = this.option(e);

				//合并参数
				if(typeof merge == 'object') $.extend(option, merge);

				var image = qrcode.init(option);
				$(this.qrcode).prop('title', option.title).prop('src', image).trigger('click');
			},
			get: function(e, merge){
				var option = this.option(e);

				//合并参数
				if(typeof merge == 'object') $.extend(option, merge);

				return qrcode.init(option);
			}
		}
	};

	return module;
});