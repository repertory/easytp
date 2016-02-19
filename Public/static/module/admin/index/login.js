define(['jquery.easyui.app', 'common/method'], function($, method) {
	var module = {
		init: function(){
			this.event();
			this.default();
		},

		event: function(){
			$('.easyui-dialog:first').dialog({
				buttons: [{
					text:'登录',
					iconCls:'fa fa-sign-in',
					handler: module.login
				}]
			});

			$('img.image', '#index-login-form').on('click', function(){
				var src = $(this).attr('src');
				$(this).prop('src', src + '&' + Math.random());
			});

			$('#index-login-form').on('keyup', function(event){
				if(event.keyCode == 13) module.login();
			});
		},

		default: function(){
			$.parser.parse();
			$('#body-lock-screen-loading').remove();  //移除锁屏进度条
			$('input:text:first').focus();
		},

		login: function(){
			var isValid = $('#index-login-form').form('validate');
			if (!isValid) return false;

			var url = $('#index-login-form').attr('action');

			$.ajax({
				type: 'POST',
				url: url,
				data: $('#index-login-form').serialize(),
				dataType: 'json',
				cache: false,
				beforeSend: function(){
					$.messager.progress({text:'登录中，请稍候...'});
				},
				success: function(data){
					$.messager.progress('close');
					if(!data.status){
						method.messager.tip(data.info, 'error');

						$('img.image:first', '#index-login-form').trigger('click');
					}else{
						window.location.href = data.url;
					}
				},
				error: function(){
					$.messager.progress('close');
					method.messager.tip('出错了，请重试！', 'error');

					$('img.image:first', '#index-login-form').trigger('click');
				}
			});
			return false;
		}
	};

	return module;
});