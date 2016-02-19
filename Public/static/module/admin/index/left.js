define(['jquery.easyui.app'], function($) {
	var module = {
		init: function(e){
			var _this = this;

			$(e).each(function(){
				$(this).tree({
					onClick: function(node){
						_this.open(node.text, node.url, node.iconCls, node.open);
					}
				});
			});
		},

		//打开左侧链接
		open: function(title, url, iconCls, open){
			if($('#index-index-layout-center-tabs').tabs('exists', title)){
				$('#index-index-layout-center-tabs').tabs('close', title);
			}

			var option = {title: title, href: url, iconCls: iconCls, closable: true, cache: false};

			if(open == 'iframe'){
				var html = [];
				html.push('<div class="panel-loading" style="position: absolute;width:100%;height:100%;">Loading...</div>');
				html.push('<iframe width="100%" height="100%" allowtransparency="true" src="' + url + '"');
				html.push(' style="background-color:transparent;border:none;margin-bottom:-5px;"');
				html.push(' onload="this.previousSibling.remove()"');
				html.push('></iframe>');
				option['content'] = html.join('');
				option['href']    = null;
			}

			$('#index-index-layout-center-tabs').tabs('add', option);
		},

		refresh: function(){
			$('#index-index-layout-center-tabs').tabs('getSelected').panel('refresh');
		},

		//再次打开（当前选中标签上）
		reopen: function(url, track){
			var tab    = $('#index-index-layout-center-tabs').tabs('getSelected');
			var option = tab.panel('options');
			var last   = $.extend(true, {}, option);

			if(track) url += url.indexOf('?') != -1 ? '&reopen_from=' + encodeURI(last.href) : '?reopen_from=' + encodeURI(last.href);

			$('#index-index-layout-center-tabs').tabs('update', {
				tab: tab,
				options: {
					href: url
				}
			});
			tab.panel('refresh');

			return last; //返回之前的参数
		}
	};

	return module;
});