define(['common/cookie'], function(cookie){
	return {
		/* cookie配置信息 */
		cookie: {
			path   : '/',
			expire : 1
		},

		request: {
			dataType : 'json',
			timeout  : 3000
		},

		messager: {
			title    : '提示信息',
			timeout  : 3000,
			showType : 'slide'
		},

		/* dialog配置信息 */
		dialog: {
			closed         : false,
			iconCls        : null,
			buttons        : null,
			maximized      : false,
			collapsible    : false,
			minimizable    : false,
			maximizable    : false,
			closable       : true,
			resizable      : false,
			draggable      : true,
			openAnimation  : 'fade',
			closeAnimation : 'fade',
			modal          : true,
			content        : null,
			href           : null
		},

		/* datagrid配置 */
		datagrid: {
			border        : false,
			fit           : true,
			fitColumns    : true,
			rownumbers    : true,
			singleSelect  : false,
			striped       : true,
			multiSort     : true,
			pagination    : true,
			pageList      : [20,30,50,80,100],
			pageSize      : cookie.get('datagrid-pageSize') || 20
		},

		/* treegrid配置 */
		treegrid: {
			border        : false,
			fit           : true,
			fitColumns    : true,
			rownumbers    : true,
			singleSelect  : false,
			striped       : true,
			idField       : 'id',
			treeField     : 'name',
			animate       : true,
			lines         : true,
			pagination    : true,
			pageList      : [10, 20, 30, 40, 50],
			pageSize      : cookie.get('treegrid-pageSize') || 10
		},

		//propertygrid
		propertygrid: {
			border        : false,
			fit           : true,
			fitColumns    : true,
			showHeader    : true,
			rownumbers    : false,
			singleSelect  : false,
			striped       : true,
			showGroup     : true,
			//scrollbarSize : 0,
			columns       : [[
				{field: 'name', title: '名称', width: 80, sortable: true},
				{field: 'value', title: '参数', width: 200, sortable: false, formatter:function(value, arr){
					var editor = '';
					if(typeof arr.editor == 'object'){
						editor = arr.editor.type;
					}else{
						editor = arr.editor;
					}
					switch(editor){
						case 'color':
							var html = [];
							html.push('<div>');
							html.push('<div style="float:right;width:18px;height:18px;background:'+value+'">&nbsp;</div>');
							html.push(value);
							html.push('<div style="clear:both"></div>');
							html.push('</div>');
							return html.join('');
							break;
						case 'password':
							return value.replace(/./g, '●');
							break;

						default:
							return value;
					}

				}}
			]],
			pagination    : false,
			pageList      : [20,30,50,80,100],
			pageSize      : cookie.get('propertygrid-pageSize') || 20
		},

		//编辑器
		editor: {
			webAppKey         : '9HrmGf2ul4mlyK8ktO2Ziayd',
			zIndex            : 1,
			initialFrameWidth : '100%',
			autoHeightEnabled : false
		},

		//二维码
		qrcode: {
			text : 'http://jeasytp.com',
			size: 200,
			color: {
				'0': 'rgb(1, 158, 213)',
				'0.2': 'rgb(30, 169, 224)',
				'0.6': 'rgb(0, 120, 191)',
				'1': 'rgb(1, 119, 255)'
			},
			background: "#ffffff",
			type: "round"
		}
	};
});