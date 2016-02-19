define(['jquery.easyui.app', 'common/event', 'common/method'], function($, event, method) {
	var module = {
		init: function(url){
			this.event();
			this.default(url);
		},

		//事件监听
		event: function(){
			var _this = this;

			/* 顶部导航按钮 */
			$('.easytp-navbar-button', '#index-index-layout-north').on('click', function(){
				_this.left.load(this);
			});

			/* 全屏模式 */
			$('.fullscreen-button', '#index-index-layout-north').on('click', function () {
				var raw = method.fullscreen.init();
				if(!raw){
					method.messager.tip('当前浏览器不支持全屏模式', 'error');
					return false;
				}

				method.fullscreen.toggle();
				$(this).menu('setText', {target: this, text:( method.fullscreen.status() ? '退出全屏' : '全屏模式')});
				$(this).menu('setIcon', {target: this, iconCls:( method.fullscreen.status() ? 'fa fa-compress' : 'fa fa-expand')});
			});

			event.event(); //全局事件监听
		},

		//初始化后默认操作
		default: function(url){
			$.parser.parse(); //初始化easyui layout效果
			$('#body-lock-screen-loading').fadeIn('slow');  //移除锁屏进度条

			$('.navbar a:first', '#index-index-layout-north').trigger('click'); //默认选中第一个菜单

			//防止登录超时
			setInterval(function(){
				$.post(url, function(res){
					if(!res.status){
						$.messager.show({title: '系统提示', msg: res.info, timeout:3000, showType:'slide'});
						setTimeout(function(){window.location.href = res.url;}, 3000);
					}
				}, 'json');
			}, 15000);
		},

		//左侧区域
		left: {
			accordion: '#index-index-layout-west-accordion',

			//清空左侧内容
			clear: function(){
				var _this = this;
				var panels = $(_this.accordion).accordion("panels");
				$.each(panels, function(i, n) {
					if(n){
						var title = n.panel("options").title;
						$(_this.accordion).accordion("remove", title);
					}
				});
				var selected = $(_this.accordion).accordion('getSelected');
				if(selected) {
					var title = selected.panel('options').title;
					$(_this.accordion).accordion("remove", title);
				}
				var panels = $(_this.accordion).accordion("panels");
				$.each(panels, function(i, n) {
					if(n){
						var title = n.panel("options").title;
						$(_this.accordion).accordion("remove", title);
					}
				});
			},

			//加载数据
			load: function(e){
				var _this = this;
				var url   = $(e).data('href');
				var title = $(e).text();
				var icon  = $(e).data('icon');

				//加个判断，防止多次点击重复加载
				var options = $('body').layout('panel', 'west').panel('options');
				if(title == options.title) return false;

				//开始获取左侧栏目
				$.ajax({
					type: 'POST',
					url: url,
					cache: false,
					beforeSend: function(){
						_this.clear();

						//更新标题名称
						$('body').layout('panel', 'west').panel({
							title: title,
							iconCls: icon,
							tools:[{
								iconCls:'fa fa-refresh',
								handler:function(){
									var panel = $(_this.accordion).accordion('getSelected');
									if(panel) panel.panel('refresh');
								}
							},{
								iconCls:'fa fa-folder-open-o',
								handler:function(){
									var panel = $(_this.accordion).accordion('getSelected');
									if(panel){
										$(panel).find('.easyui-tree').each(function(){
											$(this).tree('expandAll');
										});
									}
								}
							},{
								iconCls:'fa fa-folder-o',
								handler:function(){
									var panel = $(_this.accordion).accordion('getSelected');
									if(panel){
										$(panel).find('.easyui-tree').each(function(){
											$(this).tree('collapseAll');
										});
									}
								}
							}]
						});
						$(_this.accordion).accordion("add", {content: '<div class="panel-loading">Loading...</div>'});
					},
					success: function(data){
						_this.clear();

						//左侧内容更新
						$.each(data, function(i, menu) {
							$(_this.accordion).accordion("add", {
								title: menu.name,
								href: menu.href,
								iconCls: menu.icon
							});
						});
					}
				});

				//选中点击菜单
				$('.easytp-navbar-button', '#index-index-layout-north').removeClass('focus');
				$(e).addClass('focus');
			}
		}

	};

	return module;
});