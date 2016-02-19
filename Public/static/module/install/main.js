define(function(require){
	var init = require('./common/init');
	init.init();

	if(page){
		if(!page.status){
			myApp.alert(page.msg || '出错了，请刷新后重试！', function(){
				window.location.href = page.url;
			});
			return false;
		}
		page.url += (page.url.indexOf('?') == -1 ? '?' : '&') + new Date().getTime();
		view.router.load({url:page.url});
	}
});