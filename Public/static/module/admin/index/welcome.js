define(['jquery.easyui.app'], function($) {
	var module = {
		init: function(e){
			$(e).panel({
				onResize: function(){
					$(this).find('.easyui-portal').portal({border:false, fit:true});
				}
			});
		}
	};
	return module;
});