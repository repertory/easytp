define(['common/config'], function(config){
	return {
		set: function(name, value, expire, path){
			var expire = expire || config.cookie.expire;
			var path   = path || config.cookie.path;
			var exdate = new Date();
			exdate.setDate(exdate.getDate() + expire);
			document.cookie = name + '=' + encodeURIComponent(value) + (expire ? ';expires=' + exdate.toGMTString() : '') + (path ? ';path=' + path : '');
		},
		get: function(name){
			var arr,reg = new RegExp("(^| )" + name + "=([^;]*)(;|$)");
			arr = document.cookie.match(reg);
			return arr ? arr[2] : null;
		},
		del: function(name, path){
			var path = path || config.cookie.path;
			var exp  = new Date();
			var cval = this.get(name);
			exp.setTime(exp.getTime() - 1);

			if(cval != null)
				document.cookie = name + "=" + cval + ";expires=" + exp.toGMTString() + (path ? ';path=' + path : '');
		},
		clear: function(path){
			var keys = document.cookie.match(/[^ =;]+(?=\=)/g);
			if (keys){
				for (var i = keys.length; i--;) this.del(keys[i], path);
			}
		}
	};
});