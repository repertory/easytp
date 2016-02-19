define(['jquery.easyui.app', 'common/method'], function($, method) {
	var module = {
		//全局事件监听，只在入口文件引用一次即可
		event: function () {
			/* 页面跳转 */
			$(document).on('click', '.easytp-window-open', function () {
				window.open($(this).data('href'));
			});
			$(document).on('click', '.easytp-window-location', function () {
				window.location.href = $(this).data('href');
			});
			$(document).on('click', '.easytp-window-location-confirm', function () {
				var href = $(this).data('href');
				var msg  = $(this).data('msg') || '确定要继续吗？';
				$.messager.confirm('系统提示', msg, function (res) {
					if (res) window.location.href = href;
				});
			});

			/* 弹出层dialog */
			$(document).on('click', '.easytp-dialog-form', function () {
				method.dialog.form(this);
			});
			$(document).on('click', '.easytp-dialog-page', function () {
				method.dialog.page(this);
			});
			$(document).on('click', '.easytp-dialog-content', function () {
				method.dialog.content(this);
			});
			$(document).on('click', '.easytp-dialog-element', function () {
				method.dialog.element(this);
			});

			/* 生成二维码预览 */
			$(document).on('click', '.easytp-qrcode', function(){
				method.qrcode.show(this);
			});

			/* datagrid 点击图片上传或裁剪 */
			$(document).on('upload', 'input[type="image"]', function(){
				var e = this;
				var datagrid = $(e).parents('.datagrid-view:first').find('.datagrid-f');

				method.upload.click(e, {
					callback: function(res) {
						if(!res.status){
							method.messager.tip(res.info, 'error');
							return false;
						}
						if(datagrid){
							var row = $(datagrid).datagrid('getSelected');
							var index = $(datagrid).datagrid('getRowIndex', row);
								$(datagrid).datagrid('beginEdit', index);
							var ed = $(datagrid).datagrid('getEditor', {index:index,field:'value'});
							e = ed.target;
						}
						$(e).prop('src', res.url.upload);

						//裁剪
						if($(e).data('crop')){
							method.image.crop(e, {
								callback: function(res){
									if(!res.status){
										method.messager.tip(res.info, 'error');
										return false;
									}
									if(datagrid){
										$(datagrid).datagrid('beginEdit', index);
										var ed = $(datagrid).datagrid('getEditor', {index:index,field:'value'});
										e = ed.target;
									}
									$(e).prop('src', res.url);
								}
							});
						}
					}
				})
			});

			/* 遮罩层 */
			$(document).on('click', '.easytp-layer:not(.vbox-item)', function () {
				$('.easytp-layer:not(.vbox-item)').venobox({numeratio: true, infinigroup: true});
				var _this = this;
				setTimeout(function(){
					$(_this).trigger('click');
					return false;
				}, 30);
				return false;
			});
		}
	}

	return module;
});