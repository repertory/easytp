define(['semantic'], function($) {
	var M = {
		init: function(){
			this.menu();
		},

		menu: function(){
			$('.masthead').visibility({once: false,
				onBottomPassed: function() {
					$('.fixed.menu').transition('fade in');
				},
				onBottomPassedReverse: function() {
					$('.fixed.menu').transition('fade out');
				}
			});
			// create sidebar and attach to menu open
			$('.ui.sidebar').sidebar('attach events', '.toc.item');
		}
	};

	return M;
});