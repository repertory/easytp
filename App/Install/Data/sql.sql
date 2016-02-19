DROP TABLE IF EXISTS `[[DB_PREFIX]]admin`;
CREATE TABLE `[[DB_PREFIX]]admin` (
  `userid` mediumint(6) unsigned NOT NULL AUTO_INCREMENT COMMENT '用户id',
  `username` varchar(20) DEFAULT NULL COMMENT '用户名',
  `password` varchar(32) DEFAULT NULL COMMENT '密码',
  `encrypt` varchar(6) DEFAULT NULL COMMENT '密码加密码',
  `roleid` smallint(5) DEFAULT '0' COMMENT '角色id',
  `lastloginip` varchar(15) DEFAULT NULL COMMENT '最后登录ip',
  `lastlogintime` int(10) unsigned DEFAULT '0' COMMENT '最后登录时间',
  `email` varchar(40) DEFAULT NULL COMMENT '邮箱',
  `realname` varchar(50) NOT NULL DEFAULT '' COMMENT '真实姓名',
  `status` enum('1','0') NOT NULL DEFAULT '1' COMMENT '状态',
  PRIMARY KEY (`userid`),
  KEY `username` (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT '系统用户表';

DROP TABLE IF EXISTS `[[DB_PREFIX]]admin_role`;
CREATE TABLE `[[DB_PREFIX]]admin_role` (
  `roleid` tinyint(3) unsigned NOT NULL AUTO_INCREMENT COMMENT '角色id',
  `rolename` varchar(50) NOT NULL COMMENT '角色名称',
  `description` text NOT NULL COMMENT '描述',
  `listorder` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `status` enum('1','0') NOT NULL DEFAULT '1' COMMENT '状态',
  PRIMARY KEY (`roleid`),
  KEY `listorder` (`listorder`),
  KEY `status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT '系统用户角色表';

DROP TABLE IF EXISTS `[[DB_PREFIX]]admin_role_priv`;
CREATE TABLE `[[DB_PREFIX]]admin_role_priv` (
  `roleid` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '角色id',
  `c` varchar(20) NOT NULL COMMENT 'controller名称',
  `a` varchar(20) NOT NULL COMMENT 'action名称',
  KEY `roleid` (`roleid`,`c`,`a`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT '系统用户角色权限表';

DROP TABLE IF EXISTS `[[DB_PREFIX]]admin_log`;
CREATE TABLE `[[DB_PREFIX]]admin_log` (
  `logid` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `userid` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `username` varchar(20) NOT NULL COMMENT '用户名',
  `httpuseragent` VARCHAR(1000) NOT NULL COMMENT '浏览器useragent信息',
  `sessionid` varchar(30) NOT NULL COMMENT 'sessionid',
  `ip` varchar(15) NOT NULL COMMENT 'IP地址',
  `time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '登录时间',
  `type` varchar(30) NOT NULL COMMENT '类型',
  PRIMARY KEY (`logid`),
  KEY `username` (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT '系统用户登录日志表';

DROP TABLE IF EXISTS `[[DB_PREFIX]]category`;
CREATE TABLE `[[DB_PREFIX]]category` (
  `catid` smallint(5) unsigned NOT NULL AUTO_INCREMENT COMMENT '栏目id',
  `type` smallint(4) unsigned NOT NULL DEFAULT '1' COMMENT '栏目类型',
  `parentid` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '父级栏目id',
  `catname` varchar(30) NOT NULL COMMENT '栏目名称',
  `description` text NOT NULL COMMENT '描述',
  `setting` text default NULL COMMENT '配置参数',
  `icon` varchar(255) NOT NULL DEFAULT '' COMMENT '图标class',
  `listorder` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `status` enum('1','0') NOT NULL DEFAULT '1' comment '是否启用',
  `level` tinyint(2) unsigned NOT NULL DEFAULT '1' COMMENT '菜单级别',
  PRIMARY KEY (`catid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT '栏目表';

DROP TABLE IF EXISTS `[[DB_PREFIX]]category_priv`;
CREATE TABLE `[[DB_PREFIX]]category_priv` (
  `catid` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '栏目id',
  `roleid` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '角色id',
  `action` varchar(30) NOT NULL COMMENT 'action名称',
  KEY `catid` (`catid`,`roleid`,`action`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT '栏目权限表';

DROP TABLE IF EXISTS `[[DB_PREFIX]]log`;
CREATE TABLE `[[DB_PREFIX]]log` (
  `logid` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `controller` varchar(15) NOT NULL COMMENT 'controller名称',
  `action` varchar(20) NOT NULL COMMENT 'action名称',
  `querystring` text NOT NULL COMMENT '请求url参数',
  `userid` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `username` varchar(20) NOT NULL COMMENT '用户名',
  `ip` varchar(15) NOT NULL COMMENT 'ip',
  `time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '操作时间',
  PRIMARY KEY (`logid`),
  KEY `module` (`controller`,`action`),
  KEY `username` (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT '系统操作日志表';

DROP TABLE IF EXISTS `[[DB_PREFIX]]menu`;
CREATE TABLE `[[DB_PREFIX]]menu` (
  `id` smallint(6) unsigned NOT NULL AUTO_INCREMENT COMMENT '菜单id',
  `name` varchar(40) NOT NULL DEFAULT '' COMMENT '菜单名称',
  `parentid` smallint(6) NOT NULL DEFAULT '0' COMMENT '父级菜单id',
  `c` varchar(20) NOT NULL DEFAULT '' COMMENT 'controller名称',
  `a` varchar(20) NOT NULL DEFAULT '' COMMENT 'action名称',
  `data` varchar(255) NOT NULL DEFAULT '' COMMENT 'querystring数据',
  `listorder` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `display` enum('1','0') NOT NULL DEFAULT '1' COMMENT '是否显示',
  `icon` varchar(255) NOT NULL DEFAULT '' COMMENT '图标class',
  `toolbar` enum('1','0') NOT NULL DEFAULT '0' COMMENT '工具栏显示',
  `level` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '菜单级别',
  `open` varchar(10) NOT NULL DEFAULT 'ajax' COMMENT '打开方式',
  PRIMARY KEY (`id`),
  KEY `listorder` (`listorder`),
  KEY `parentid` (`parentid`),
  KEY `module` (`c`,`a`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT '系统菜单表';

DROP TABLE IF EXISTS `[[DB_PREFIX]]article`;
CREATE TABLE `[[DB_PREFIX]]article` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `catid` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '栏目id',
  `uuid` varchar(40) NOT NULL COMMENT 'UUID，评论插件或其他功能识别用',
  `title` varchar(80) NOT NULL DEFAULT '' COMMENT '标题',
  `keywords` varchar(40) NOT NULL DEFAULT '' COMMENT '关键字',
  `description` mediumtext NOT NULL COMMENT '描述',
  `thumb` varchar(100) NOT NULL DEFAULT '' COMMENT '缩略图',
  `content` mediumtext NOT NULL COMMENT '内容',
  `status` enum('1','0') NOT NULL DEFAULT '1' COMMENT '启用状态',
  `islink` enum('1','0') NOT NULL DEFAULT '0' COMMENT '是否开启转向链接',
  `url` varchar(100) NOT NULL COMMENT '转向链接地址，启用后才能使用',
  `istop` enum('1','0') NOT NULL DEFAULT '0' COMMENT '是否置顶',
  `author` varchar(20) NOT NULL COMMENT '作者',
  `addtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `updatetime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `catid` (`catid`,`status`,`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT '文章内容表';

DROP TABLE IF EXISTS `[[DB_PREFIX]]page`;
CREATE TABLE `[[DB_PREFIX]]page` (
  `catid` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '栏目id',
  `uuid` varchar(40) NOT NULL COMMENT 'UUID，评论插件或其他功能识别用',
  `title` varchar(160) NOT NULL COMMENT '标题',
  `keywords` varchar(40) NOT NULL COMMENT '关键字',
  `description` text NOT NULL COMMENT '描述',
  `content` text NOT NULL COMMENT '内容',
  `status` enum('1','0') NOT NULL DEFAULT '1' COMMENT '启用状态',
  `updatetime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  KEY `catid` (`catid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT '页面内容表';

DROP TABLE IF EXISTS `[[DB_PREFIX]]times`;
CREATE TABLE `[[DB_PREFIX]]times` (
  `username` char(40) NOT NULL COMMENT '用户名',
  `ip` char(15) NOT NULL COMMENT '最后操作IP',
  `time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后操作时间',
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '类型 0:后台用户，1:前台用户',
  `times` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '次数',
  PRIMARY KEY (`username`,`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT '次数统计表';

DROP TABLE IF EXISTS `[[DB_PREFIX]]setting`;
CREATE TABLE `[[DB_PREFIX]]setting` (
  `name` varchar(50) NOT NULL COMMENT '配置名称',
  `value` varchar(5000) DEFAULT '' COMMENT '参数',
  `type` varchar(10) DEFAULT '' COMMENT '参数类型',
  PRIMARY KEY (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT '系统配置表';

DROP TABLE IF EXISTS `[[DB_PREFIX]]member`;
CREATE TABLE `[[DB_PREFIX]]member` (
  `memberid` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '本站用户id',
  `username` varchar(30) NOT NULL COMMENT '帐号',
  `head` varchar(255) DEFAULT NULL COMMENT '头像',
  `nick` varchar(50) DEFAULT NULL COMMENT '昵称',
  `gender` tinyint(1) DEFAULT '0' COMMENT '0:保密,1:男,2:女',
  `password` varchar(32) NOT NULL COMMENT '密码',
  `encrypt` varchar(6) NOT NULL COMMENT '密码加密码',
  `typeid` smallint(5) DEFAULT '0' COMMENT '分类id',
  `status` enum('1','0') DEFAULT '0' COMMENT '0:待认证1:已认证',
  `remark` text COMMENT '备注',
  `lastloginip` varchar(15) DEFAULT NULL COMMENT '最后登录IP',
  `lastlogintime` int(10) DEFAULT '0' COMMENT '最后登录时间',
  `regip` varchar(15) NOT NULL COMMENT '注册IP',
  `regtime` int(10) NOT NULL DEFAULT '0' COMMENT '注册时间',
  `mobile` bigint(11) DEFAULT NULL COMMENT '手机号',
  `constellation` tinyint(2) DEFAULT NULL COMMENT '星座(1-12对应开头月份)',
  `signature` varchar(500) DEFAULT NULL COMMENT '个性签名',
  PRIMARY KEY (`memberid`),
  KEY `username` (`username`, `mobile`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT '会员表';

DROP TABLE IF EXISTS `[[DB_PREFIX]]member_oauth`;
CREATE TABLE `[[DB_PREFIX]]member_oauth` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `memberid` int(11) NOT NULL  comment '本站用户id',
  `openid` varchar(50) NOT NULL DEFAULT '' comment '唯一标识',
  `email` varchar(40) DEFAULT NULL comment '邮箱',
  `nick` varchar(80) DEFAULT NULL comment '昵称',
  `head` varchar(255) DEFAULT NULL comment '用户图像',
  `gender` varchar(10) DEFAULT NULL comment '性别',
  `link` varchar(255) DEFAULT NULL comment '用户链接',
  `type` varchar(50) NOT NULL DEFAULT '' comment '类型',
  `addtime` int(10) DEFAULT '0' comment '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT '会员联合登录表';

DROP TABLE IF EXISTS `[[DB_PREFIX]]member_type`;
CREATE TABLE `[[DB_PREFIX]]member_type` (
  `typeid` tinyint(3) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `typename` varchar(50) NOT NULL COMMENT '分类名称',
  `description` text NOT NULL COMMENT '描述',
  `listorder` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `status` enum('1','0')  NOT NULL DEFAULT '1' COMMENT '状态',
  PRIMARY KEY (`typeid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT '会员分类表';

DROP TABLE IF EXISTS `[[DB_PREFIX]]email`;
CREATE TABLE `[[DB_PREFIX]]email` (
  `id` smallint(4) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `code` varchar(40) NOT NULL COMMENT '模板编号',
  `subject` varchar(255) NOT NULL COMMENT '邮件主题',
  `content` text NOT NULL COMMENT '模板内容',
  `addtime` int(10) DEFAULT '0' COMMENT '添加时间',
  `edittime` int(10) DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT '邮件模板表';

DROP TABLE IF EXISTS `[[DB_PREFIX]]hooks`;
CREATE TABLE `[[DB_PREFIX]]hooks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(40) NOT NULL DEFAULT '' COMMENT '钩子名称',
  `description` text NULL  COMMENT '描述',
  `addons` varchar(255) NOT NULL DEFAULT '' COMMENT '钩子挂载的插件，分割',
  `listorder` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `status` enum('1','0') NOT NULL DEFAULT '1' COMMENT '状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='钩子表';

DROP TABLE IF EXISTS `[[DB_PREFIX]]addons`;
CREATE TABLE `[[DB_PREFIX]]addons` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(40) NOT NULL COMMENT '插件名或标识',
  `title` varchar(20) NOT NULL DEFAULT '' COMMENT '中文名',
  `description` text COMMENT '插件描述',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态',
  `config` text COMMENT '配置',
  `author` varchar(40) DEFAULT '' COMMENT '作者',
  `version` varchar(20) DEFAULT '' COMMENT '版本号',
  `icon` varchar(255) NOT NULL DEFAULT '' COMMENT '图标class',
  `open` VARCHAR(10)  NOT NULL  DEFAULT 'ajax'  COMMENT '打开方式',
  `show` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '安装列表展示',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='插件表';

DROP TABLE IF EXISTS `[[DB_PREFIX]]extend`;


INSERT INTO `[[DB_PREFIX]]admin` (`userid`, `username`, `password`, `roleid`, `encrypt`,`email`) VALUES (1, 'wangdong', '9877eb2a924c51143c66668d7cc11c2e', 1, 'gKkcJn', '531381545@qq.com');
INSERT INTO `[[DB_PREFIX]]admin_role` VALUES (1,'超级管理员','超级管理员',99,1),(2,'普通用户','普通用户',0,1);
INSERT INTO `[[DB_PREFIX]]member_type` VALUES ('1','普通用户','本地用户', '1', '1');
INSERT INTO `[[DB_PREFIX]]menu` (`id`, `name`, `parentid`, `c`, `a`, `data`, `listorder`, `display`, `icon`, `toolbar`, `level`, `open`) VALUES
	(1,'我的面板',0,'Panel','menu1','',1,'1','','0',1,'ajax'),
	(2,'系统管理',0,'System','menu1','',2,'1','','0',1,'ajax'),
	(3,'内容管理',0,'Content','menu1','',3,'1','','0',1,'ajax'),
	(4,'会员管理',0,'Member','menu1','',4,'1','','0',1,'ajax'),
	(5,'应用管理',0,'Extend','menu1','',5,'0','','0',1,'ajax'),

	(6,'应用中心',5,'Extend','menu21','',1,'1','','0',2,'ajax'),
	(7,'安装列表',5,'Extend','menu22','',2,'1','','0',2,'ajax'),
	(8,'应用商店',6,'Extend','store','',1,'1','','0',3,'ajax'),
	(9,'插件管理',6,'Extend','addon','',2,'1','','0',3,'ajax'),
	(10,'钩子管理',6,'Extend','hook','',3,'1','','0',3,'ajax'),

	(11,'安全记录',1,'Panel','menu21','',1,'1','fa fa-book','0',2,'ajax'),
	(12,'登录日志',11,'Panel','login','',1,'1','fa fa-file-text-o','0',3,'ajax'),
	(13,'操作日志',11,'Panel','operate','',2,'1','fa fa-file-text-o','0',3,'ajax'),
	(14,'设置中心',2,'System','menu21','',1,'1','fa fa-cogs','0',2,'ajax'),
	(15,'全局设置',14,'System','setting','',1,'1','fa fa-sliders','0',3,'ajax'),

	(16,'菜单设置',14,'System','menu','',2,'1','fa fa-tasks','0',3,'ajax'),
	(17,'邮件模板',14,'System','email','',3,'1','fa fa-envelope-o','0',3,'ajax'),
	(18,'系统用户',2,'User','menu22','',2,'1','fa fa-users','0',2,'ajax'),
	(19,'角色管理',18,'User','role','',1,'1','fa fa-group','0',3,'ajax'),
	(20,'用户管理',18,'User','user','',2,'1','fa fa-user','0',3,'ajax'),

	(21,'删除一个月前记录',12,'Panel','loginDelete','',1,'1','fa fa-minus-square-o','1',4,'ajax'),
	(22,'删除一个月前记录',13,'Panel','operateDelete','',1,'1','fa fa-minus-square-o','1',4,'ajax'),
	(23,'添加',19,'User','roleAdd','',1,'1','fa fa-plus-square-o','1',4,'ajax'),
	(24,'编辑',19,'User','roleEdit','',2,'1','fa fa-pencil-square-o','1',4,'ajax'),
	(25,'删除',19,'User','roleDelete','',3,'1','fa fa-minus-square-o','1',4,'ajax'),

	(26,'权限控制',19,'User','rolePriv','',4,'1','fa fa-expeditedssl','1',4,'ajax'),
	(27,'栏目权限',19,'User','roleCat','',4,'1','fa fa-expeditedssl','1',4,'ajax'),
	(28,'添加',20,'User','userAdd','',1,'1','fa fa-plus-square-o','1',4,'ajax'),
	(29,'编辑',20,'User','userEdit','',2,'1','fa fa-pencil-square-o','1',4,'ajax'),
	(30,'删除',20,'User','userDelete','',3,'1','fa fa-minus-square-o','1',4,'ajax'),

	(31,'重置密码',20,'User','userReset','',4,'1','fa fa-key','1',4,'ajax'),
	(32,'添加',17,'System','emailAdd','',1,'1','fa fa-plus-square-o','1',4,'ajax'),
	(33,'编辑',17,'System','emailEdit','',2,'1','fa fa-pencil-square-o','1',4,'ajax'),
	(34,'删除',17,'System','emailDelete','',3,'1','fa fa-minus-square-o','1',4,'ajax'),
	(35,'添加',16,'System','menuAdd','',1,'1','fa fa-plus-square-o','1',4,'ajax'),

	(36,'编辑',16,'System','menuEdit','',2,'1','fa fa-pencil-square-o','1',4,'ajax'),
	(37,'删除',16,'System','menuDelete','',3,'1','fa fa-minus-square-o','1',4,'ajax'),
	(38,'保存',15,'System','settingSave','',1,'1','fa fa-floppy-o','1',4,'ajax'),
	(39,'还原',15,'System','settingReset','',2,'1','fa fa-registered','1',4,'ajax'),
	(40,'会员管理',4,'Member','menu21','',1,'1','fa fa-users','0',2,'ajax'),

	(41,'会员类型',40,'Member','type','',1,'1','fa fa-group','0',3,'ajax'),
	(42,'会员中心',40,'Member','user','',2,'1','fa fa-user','0',3,'ajax'),
	(43,'添加',41,'Member','typeAdd','',1,'1','fa fa-plus-square-o','1',4,'ajax'),
	(44,'编辑',41,'Member','typeEdit','',2,'1','fa fa-pencil-square-o','1',4,'ajax'),
	(45,'删除',41,'Member','typeDelete','',3,'1','fa fa-minus-square-o','1',4,'ajax'),

	(46,'添加',42,'Member','userAdd','',1,'1','fa fa-plus-square-o','1',4,'ajax'),
	(47,'编辑',42,'Member','userEdit','',2,'1','fa fa-pencil-square-o','1',4,'ajax'),
	(48,'删除',42,'Member','userDelete','',3,'1','fa fa-minus-square-o','1',4,'ajax'),
	(49,'重置密码',42,'Member','userReset','',4,'1','fa fa-key','1',4,'ajax'),
	(50,'栏目管理',3,'Category','menu21','',1,'1','','0',2,'ajax'),

	(51,'内容管理',3,'Content','menu22','',2,'1','','0',2,'ajax'),
	(52,'栏目管理',50,'Category','category','',1,'1','','0',3,'ajax'),
	(53,'添加',52,'Category','categoryAdd','',1,'1','fa fa-plus-square-o','1',4,'ajax'),
	(54,'编辑',52,'Category','categoryEdit','',2,'1','fa fa-pencil-square-o','1',4,'ajax'),
	(55,'删除',52,'Category','categoryDelete','',3,'1','fa fa-minus-square-o','1',4,'ajax'),

	(56, '安装', 9, 'Extend', 'addonInstall', '', 1, '1', 'fa fa-plus-circle', '1', 4, 'ajax'),
	(57, '卸载', 9, 'Extend', 'addonUninstall', '',2, '1', 'fa fa-times-circle', '1', 4, 'ajax'),
	(58, '设置', 9, 'Extend', 'addonConfig', '', 3, '1', 'fa fa-cog', '1', 4, 'ajax'),
	(59, '禁用', 9, 'Extend', 'addonDisabled', '', 4, '1', 'fa fa-ban', '1', 4, 'ajax'),
	(60, '添加', 10, 'Extend', 'hookAdd', '', 1, '1', 'fa fa-plus-square-o', '1', 4, 'ajax'),

	(61, '编辑', 10, 'Extend', 'hookEdit', '', 2, '1', 'fa fa-pencil-square-o', '1', 4, 'ajax'),
	(62, '删除', 10, 'Extend', 'hookDelete', '', 3, '1', 'fa fa-minus-square-o', '1', 4, 'ajax');
