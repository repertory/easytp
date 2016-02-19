define(['semantic'], function($) {
	var M = {
		init: function(){
			this.menu();
		},

		menu: function(){
			$('.ui.sidebar').sidebar('attach events', '.sidebar.icon');
		}
	};

	return M;
});