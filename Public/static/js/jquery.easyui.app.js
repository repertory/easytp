/**
 * html5上传
 */
(function ($) {
	$.fn.html5Upload = function (options) {
		var crlf = '\r\n';
		var boundary = "easytp";
		var dashes = "--";

		var settings = {
			"name": "upload",
			"postUrl": "",
			"onClientAbort": null,
			"onClientError": null,
			"onClientLoad": null,
			"onClientLoadEnd": null,
			"onClientLoadStart": null,
			"onClientProgress": null,
			"onServerAbort": null,
			"onServerError": null,
			"onServerLoad": null,
			"onServerLoadStart": null,
			"onServerProgress": null,
			"onServerReadyStateChange": null,
			"onSuccess": null
		};

		if (options) {
			$.extend(settings, options);
		}

		return this.each(function (options) {
			var $this = $(this);
			if ($this.is("[type='file']")) {
				$this
					.bind("change", function () {
						var files = this.files;
						for (var i = 0; i < files.length; i++) {
							fileHandler(files[i]);
						}
					});
			} else {
				$this
					.bind("dragenter dragover", function () {
						$(this).addClass("hover");
						return false;
					})
					.bind("dragleave", function () {
						$(this).removeClass("hover");
						return false;
					})
					.bind("drop", function (e) {
						$(this).removeClass("hover");
						var files = e.originalEvent.dataTransfer.files;
						for (var i = 0; i < files.length; i++) {
							fileHandler(files[i]);
						}
						return false;
					});
			}
		});

		function fileHandler(file) {
			if(settings.valid){
				if(!settings.valid(file)) return false;
			}

			var fileReader = new FileReader();
			fileReader.onabort = function (e) {
				if (settings.onClientAbort) {
					settings.onClientAbort(e, file);
				}
			};
			fileReader.onerror = function (e) {
				if (settings.onClientError) {
					settings.onClientError(e, file);
				}
			};
			fileReader.onload = function (e) {
				if (settings.onClientLoad) {
					settings.onClientLoad(e, file);
				}
			};
			fileReader.onloadend = function (e) {
				if (settings.onClientLoadEnd) {
					settings.onClientLoadEnd(e, file);
				}
			};
			fileReader.onloadstart = function (e) {
				if (settings.onClientLoadStart) {
					settings.onClientLoadStart(e, file);
				}
			};
			fileReader.onprogress = function (e) {
				if (settings.onClientProgress) {
					settings.onClientProgress(e, file);
				}
			};
			fileReader.readAsDataURL(file);

			var xmlHttpRequest = new XMLHttpRequest();
			xmlHttpRequest.upload.onabort = function (e) {
				if (settings.onServerAbort) {
					settings.onServerAbort(e, file);
				}
			};
			xmlHttpRequest.upload.onerror = function (e) {
				if (settings.onServerError) {
					settings.onServerError(e, file);
				}
			};
			xmlHttpRequest.upload.onload = function (e) {
				if (settings.onServerLoad) {
					settings.onServerLoad(e, file);
				}
			};
			xmlHttpRequest.upload.onloadstart = function (e) {
				if (settings.onServerLoadStart) {
					settings.onServerLoadStart(e, file);
				}
			};
			xmlHttpRequest.upload.onprogress = function (e) {
				if (settings.onServerProgress) {
					settings.onServerProgress(e, file);
				}
			};
			xmlHttpRequest.onreadystatechange = function (e) {
				if (settings.onServerReadyStateChange) {
					settings.onServerReadyStateChange(e, file, xmlHttpRequest.readyState);
				}
				if (settings.onSuccess && xmlHttpRequest.readyState == 4 && xmlHttpRequest.status == 200) {
					settings.onSuccess(e, file, xmlHttpRequest.responseText);
				}
				if (settings.onError && xmlHttpRequest.readyState == 4 && xmlHttpRequest.status != 200) {
					settings.onError(e, file, xmlHttpRequest);
				}
			};
			xmlHttpRequest.open("POST", settings.postUrl, true);

			if (file.getAsBinary) { // Firefox
				var data = dashes + boundary + crlf +
					"Content-Disposition: form-data;" +
					"name=\"" + settings.name + "\";" +
					"filename=\"" + unescape(encodeURIComponent(file.name)) + "\"" + crlf +
					"Content-Type: application/octet-stream" + crlf + crlf +
					file.getAsBinary() + crlf +
					dashes + boundary + dashes;

				xmlHttpRequest.setRequestHeader("Content-Type", "multipart/form-data;boundary=" + boundary);
				xmlHttpRequest.sendAsBinary(data);

			} else if (window.FormData) { // Chrome

				var formData = new FormData();
				formData.append(settings.name, file);

				xmlHttpRequest.send(formData);
			}
		}
	};
})(jQuery);


/**
 * easyui扩展方法
 */
(function($){
	/**
	 * datagrid扩展
	 */
	$.extend($.fn.datagrid.defaults.editors, {
		image: {
			init: function(container, options){
				var html = ['<input type="image" class="datagrid-editable-input" alt="点击上传图片" title="点击上传图片" style="cursor:pointer;display:block"'];
				if(options.upload)   html.push('data-upload="'+ options.upload +'"');
				if(options.multiple) html.push('data-multiple="'+ options.multiple +'"');
				if(options.accept)   html.push('data-accept="'+ options.accept +'"');
				if(options.size)     html.push('data-size="'+ options.size +'"');

				if(options.crop)     html.push('data-crop="'+ options.crop +'"');
				if(options.subfix)   html.push('data-subfix="'+ options.subfix +'"');
				if(options.width)    html.push('data-width="'+ options.width +'"');
				if(options.height)   html.push('data-height="'+ options.height +'"');
				if(options.fit)      html.push('data-fit="'+ options.fit +'"');

				html.push('onclick="$(this).trigger(\'upload\')"');

				html.push('/>');
				return $(html.join(' ')).appendTo(container);
			},
			destroy: function(target){
				$(target).remove();
			},
			getValue: function(target){
				return $(target).attr('src');
			},
			setValue: function(target, value){
				$(target).prop('src', value);
			},
			resize: function(target, width){
				var fit = $(target).data('fit');
				if(fit){
					$(target)._outerWidth(width);
				}else{
					$(target).css('max-width', width);
				}
			}
		},
		password: {
			init: function(container, options){
				return $('<input type="password" class="datagrid-editable-input" />').appendTo(container);
			},
			destroy: function(target){
				$(target).remove();
			},
			getValue: function(target){
				return $(target).val();
			},
			setValue: function(target, value){
				$(target).val(value);
			},
			resize: function(target, width){
				$(target)._outerWidth(width);
			}
		},
		color: {
			init: function(container, options){
				return $('<input type="color" class="datagrid-editable-input" />').appendTo(container);
			},
			destroy: function(target){
				$(target).remove();
			},
			getValue: function(target){
				return $(target).val();
			},
			setValue: function(target, value){
				$(target).val(value);
			},
			resize: function(target, width){
				$(target)._outerWidth(width);
			}
		}
	});

	/**
	 * validatebox扩展
	 */
	$.extend($.fn.validatebox.defaults.rules, {
		equals: {
			validator: function(value,param){
				return value == $(param[0]).val();
			},
			message: '两次密码不一致'
		},
		controller: {
			validator: function(value){
				return /^([A-Z][a-z1-9]*)+$/.test(value);
			},
			message: '必须为首字母大写的驼峰法命名'
		},
		action: {
			validator: function(value){
				return /^[a-z_]*([a-z][a-z0-9]+[A-Z0-9]?)+$/.test(value);
			},
			message: '必须为首字母小写的驼峰法命名'
		},
		querystring: {
			validator: function(value){
				return /^([^=&]+=[^=&]+)(&([^=&]+=[^=&]+))*$/.test(value);
			},
			message: '必须为querystring格式'
		},
		zh: {
			validator: function(value){
				return /^[\u4e00-\u9fa5]+$/.test(value);
			},
			message: '必须为中文字符'
		},
		ip: {
			validator: function(value){
				return /^((25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\.){3}(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})$/.test(value);
			},
			message: '必须为IP地址'
		},
		ipv6: {
			validator: function(value){
				return /^\s*((([0-9A-Fa-f]{1,4}:){7}([0-9A-Fa-f]{1,4}|:))|(([0-9A-Fa-f]{1,4}:){6}(:[0-9A-Fa-f]{1,4}|((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){5}(((:[0-9A-Fa-f]{1,4}){1,2})|:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){4}(((:[0-9A-Fa-f]{1,4}){1,3})|((:[0-9A-Fa-f]{1,4})?:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){3}(((:[0-9A-Fa-f]{1,4}){1,4})|((:[0-9A-Fa-f]{1,4}){0,2}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){2}(((:[0-9A-Fa-f]{1,4}){1,5})|((:[0-9A-Fa-f]{1,4}){0,3}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){1}(((:[0-9A-Fa-f]{1,4}){1,6})|((:[0-9A-Fa-f]{1,4}){0,4}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(:(((:[0-9A-Fa-f]{1,4}){1,7})|((:[0-9A-Fa-f]{1,4}){0,5}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:)))(%.+)?\s*$/.test(value);
			},
			message: '必须为IPV6地址'
		},
		idcard: {
			validator: function(value){
				var Wi = [ 7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2, 1 ];// 加权因子;
				var ValideCode = [ 1, 0, 10, 9, 8, 7, 6, 5, 4, 3, 2 ];// 身份证验证位值，10代表X;

				if (value.length == 15) {
					return isValidityBrithBy15IdCard(value);
				}else if (value.length == 18){
					var a_idCard = value.split('');// 得到身份证数组
					if (isValidityBrithBy18IdCard(value)&&isTrueValidateCodeBy18IdCard(a_idCard)) {
						return true;
					}
					return false;
				}
				return false;

				function isTrueValidateCodeBy18IdCard(a_idCard) {
					var sum = 0; // 声明加权求和变量
					if (a_idCard[17].toLowerCase() == 'x') {
						a_idCard[17] = 10;// 将最后位为x的验证码替换为10方便后续操作
					}
					for ( var i = 0; i < 17; i++) {
						sum += Wi[i] * a_idCard[i];// 加权求和
					}
					valCodePosition = sum % 11;// 得到验证码所位置
					if (a_idCard[17] == ValideCode[valCodePosition]) {
						return true;
					}
					return false;
				}
				function isValidityBrithBy18IdCard(idCard18){
					var year = idCard18.substring(6,10);
					var month = idCard18.substring(10,12);
					var day = idCard18.substring(12,14);
					var temp_date = new Date(year,parseFloat(month)-1,parseFloat(day));
					// 这里用getFullYear()获取年份，避免千年虫问题
					if(temp_date.getFullYear()!=parseFloat(year) || temp_date.getMonth()!=parseFloat(month)-1 || temp_date.getDate()!=parseFloat(day)){
						return false;
					}
					return true;
				}
				function isValidityBrithBy15IdCard(idCard15){
					var year =  idCard15.substring(6,8);
					var month = idCard15.substring(8,10);
					var day = idCard15.substring(10,12);
					var temp_date = new Date(year,parseFloat(month)-1,parseFloat(day));
					// 对于老身份证中的你年龄则不需考虑千年虫问题而使用getYear()方法
					if(temp_date.getYear()!=parseFloat(year) || temp_date.getMonth()!=parseFloat(month)-1 || temp_date.getDate()!=parseFloat(day)){
						return false;
					}
					return true;
				}
			},
			message: '必须为身份证号码'
		}
	});
})(jQuery);