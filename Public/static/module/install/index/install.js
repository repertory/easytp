define(function(require, exports, module){
	var $       = require('jq');
	var md5     = require('../lib/md5');
	var install = {
		step1: function(){
			$('.easytp-reload').on('click', function(){
				window.location.href = window.location.href;
			});
			$('.easytp-next').on('click', function(){
				var url = $(this).data('url');
				url += (url.indexOf('?') == -1 ? '?' : '&') + new Date().getTime();
				myApp.modalPassword('请输入安装密码:', function(password) {
					if(md5(md5(password)) == 'feac5fa2c3a50e4dab7cfccd8b3782d5') view.router.load({url:url});
					else myApp.alert('密码错误', '系统提示');
				});

			});
		},

		step2: function(){
			install.form.event();
			$('.easytp-step2-next').on('click', function(){
				if(!install.form.valid('#step2-form')) return false;

				var data = $('#step2-form').serialize();
				var url  = $('#step2-form').attr('action');
				$.ajax({
					type: 'POST',
					url: url,
					data: data,
					dataType: 'json',
					success: function(res){
						if(res.status){
							res.url += (res.url.indexOf('?') == -1 ? '?' : '&') + new Date().getTime();
							view.router.load({url:res.url});
						}else{
							myApp.alert(res.info || '出错了，请重试！')
						}
					},
					error: function(){
						myApp.alert('数据库信息有误！')
					}
				});
			});
		},

		step3: function(){},

		step4: function(){
			$('.easytp-link').on('click', function(){
				window.location.href = $(this).attr('href');
			});

		},

		/* 表单模块 */
		form: {
			//监听事件
			event: function(){
				$('.valid').off('change');
				$('.valid').on('change', install.form.check);
			},
			check: function(e, that){
				var _this = that || this;
				$(_this).attr('valid', true);

				var item  = ['novalidate', 'required', 'confirm', 'minlength', 'maxlength', 'min', 'max', 'pattern'];
				var value = $(_this).val();

				var need  = $(_this).attr('novalidate') ? false : (((!$(_this).attr('required') && value.length > 0) || $(_this).attr('required')) ? true : false);

				for(var i = 0; i < item.length; i++){
					var option = $(_this).attr(item[i]) || null;
					if(option === null) continue;

					switch(item[i]){
						case 'novalidate':
							$(_this).attr('valid', true); return;
							break;
						case 'required':
							if(!value.length) $(_this).attr('valid', '');
							break;
						case 'pattern':
							try{
								var reg = eval('/' + option + '/');
							}catch(e){}
							if(need && reg && !value.match(reg)) $(_this).attr('valid', '');
							break;
						case 'minlength':
							if(need && value.length < option) $(_this).attr('valid', '');
							break;
						case 'maxlength':
							if(need && value.length > option) $(_this).attr('valid', '');
							break;
						case 'min':
							if(need && value < option) $(_this).attr('valid', '');
							break;
						case 'max':
							if(need && value > option) $(_this).attr('valid', '');
							break;
						case 'confirm':
							if(need && $(option).val() != value) $(_this).attr('valid', '');
							break;
					}
				}
			},
			//执行验证
			valid: function(selector){
				var status = true;

				$(selector).find('.valid').each(function(){
					install.form.check(null, this);

					var valid = $(this).attr('valid') || false;
					if(!valid){
						status = false;
						var tip = $(this).attr('tip') || '格式不正确';
						var e   = this;
						myApp.alert(tip, function(){$(e).focus();});
						return false;
					}
				});

				return status;
			}
		}
	};

	module.exports = install;
});