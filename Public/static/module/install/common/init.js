define(function(require, exports, module) {
	require('f7');

	module.exports = {
		init: function(){
			window.myApp = new Framework7({
				template7Pages            : true,
				modalTitle                : '',
				modalButtonOk             : '确定',
				modalButtonCancel         : '取消',
				modalPreloaderTitle       : '加载中...',
				smartSelectBackText       : '返回',
				smartSelectPopupCloseText : '关闭',
				scrollTopOnNavbarClick    : true
			});

			window.view  = myApp.addView('.view-main', {dynamicNavbar: true});

			this.events();
		},

		events: function(){
			var _this = this;

			Dom7(document).on('pageBeforeInit', function(e){
				_this.pageBeforeInit(e.detail.page);
			});

			view.showNavbar();
		},

		pageBeforeInit: function(page){
			var name  = page.name;
			var query = page.query;
			var from  = page.from;
			var param = name.toLocaleLowerCase().split("-");

			//路由封装
			if(param.length >= 3){
				var module = param.slice(0, 2).join('/');
				require.async('../' + module, function(router){
					if(router && typeof router[param[2]] == 'function') router[param[2]]();
				});
			}
		}
	};
});