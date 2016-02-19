<?php
namespace Admin\Controller;
use Admin\Controller\CommonController;

/**
 * 后台管理扩展模块
 * @author wangdong
 */
class ExtendController extends CommonController {
	/**
	 * 应用商店
	 */
	public function store(){
		$menuid     = I('get.menuid');
		$menu_db    = D('Menu');
		$currentpos = $menu_db->currentPos($menuid);  //栏目位置

		$this->assign('title', $currentpos);
		$this->display();
	}


	//////////////////////////////////////////// 钩子管理 ////////////////////////////////////////////


	/**
	 * 钩子管理
	 */
	public function hook($page = 1, $rows = 10, $sort = 'listorder', $order = 'asc'){
		//搜索
		$where = array();

		$this->datagrid(array(
			'db'    => D('Hooks'),
			'where' => $where,
			'page'  => $page,
			'rows'  => $rows,
			'sort'  => $sort,
			'order' => $order,
			'formatter' => function($key, &$val, $info){
				switch($key){
					case 'status':
						$val = $val ? '启用' : '<font color="red">禁用</font>';
						break;
				}
				return $val;
			},
		));
	}

	/**
	 * 添加钩子
	 */
	public function hookAdd(){
		if(IS_POST){
			$hooks_db = D('Hooks');
			$data     = I('post.info');
			if($hooks_db->where(array('name'=>$data['name']))->count()){
				$this->error('插件名称已存在');
			}

			$res = $hooks_db->add($data);
			$res ? $this->success('添加成功') : $this->error('添加失败');
		}else{
			$this->display('hook_add');
		}
	}

	/**
	 * 编辑钩子
	 */
	public function hookEdit(){
		$hooks_db = D('Hooks');
		if(IS_POST){
			$data = I('post.info');
			$res  = $hooks_db->save($data);
			$res ? $this->success('修改成功') : $this->error('修改失败');
		}else{
			$id   = I('get.id');
			$info = $hooks_db->where(array('id'=>$id))->find();

			$this->assign('info', $info);
			$this->display('hook_edit');
		}
	}

	/**
	 * 删除钩子
	 */
	public function hookDelete($ids = ''){
		if(IS_POST){
			$hooks_db = D('Hooks');
			$idList   = explode(',', $ids);
			$result   = $hooks_db->where("id in ({$ids})")->delete();

			if ($result){
				$this->success('删除成功');
			}else {
				$this->error('删除失败');
			}
		}
	}


	//////////////////////////////////////////// 插件管理 ////////////////////////////////////////////


	/**
	 * 插件管理
	 */
	public function addon($page = 1, $rows = 10){
		if(IS_POST){
			$start = ($page - 1) * $rows;
			$list  = D('Addons')->getList();
			$total = count($list);
			$rows  = array_slice($list, $start, $rows);
			$data  = array('total'=>$total, 'rows'=>$rows);
			$this->ajaxReturn($data);
		}else{
			$menuid     = I('get.menuid');
			$menu_db    = D('Menu');
			$currentpos = $menu_db->currentPos($menuid);  //栏目位置
			$toolbars   = $menu_db->getToolBar($menuid);

			$this->assign('title', $currentpos);
			$this->assign('toolbars', $toolbars);
			$this->display();
		}
	}

	/**
	 * 安装插件
	 */
	public function addonInstall($name = ''){
		$class = get_addon_class($name);
		if(!class_exists($class)) $this->error('插件不存在');

		$addons = new $class;
		$info   = $addons->info;
		if(!$info || !$addons->checkInfo()) $this->error('插件信息缺失');

		$flag = $addons->install();
		if(!$flag) $this->error('执行插件预安装操作失败');

		$addons_db = D('Addons');
		if($addons_db->where(array('name'=>$info['name']))->count()) $this->error('请不要重复安装此插件');

		$data = $addons_db->create($info);

		if(is_array($addons->admin_list) && !empty($addons->admin_list)){
			$data['show'] = 1;
		}else{
			$data['show'] = 0;
		}

		if(!$data) $this->error($addons_db->getError());

		if($addons_db->add($data)){
			$config = array('config'=>json_encode($addons->getConfig()));
			$addons_db->where(array('name'=>$name))->save($config);

			$hooks_update = D('Hooks')->updateHooks($name);
			if($hooks_update){
				S('common_behavior_hooks', null);
				$this->success('安装成功');
			}else{
				$addons_db->where(array('name'=>$name))->delete();
				$this->error('更新钩子处插件失败,请卸载后尝试重新安装');
			}

		}else{
			$this->error('写入插件数据失败');
		}
	}

	/**
	 * 卸载插件
	 */
	public function addonUninstall($id = 0){
		$addons_db = D('Addons');
		$hooks_db  = D('Hooks');
		$addon     = $addons_db->find($id);

		if(!$addon) $this->error('插件不存在');

		$class = get_addon_class($addon['name']);

		if(class_exists($class)){
			$addons = new $class;
			$flag   = $addons->uninstall();

			if(!$flag) $this->error('执行插件预卸载操作失败');
		}

		$hooks_update = $hooks_db->removeHooks($addon['name']);
		if(class_exists($class) && !$hooks_update) $this->error('卸载插件所挂载的钩子数据失败');

		S('common_behavior_hooks', null);
		$delete = $addons_db->where(array('name'=>$addon['name']))->delete();
		if($delete === false){
			$this->error('卸载插件失败');
		}else{
			$this->success('卸载成功');
		}
	}

	/**
	 * 设置插件
	 */
	public function addonConfig($id = 0){
		$addons_db = M('Addons');
		if(IS_POST){
			$config = I('post.config');
			$flag   = $addons_db->where(array('id'=>$id))->setField('config', json_encode($config));
			$flag ? $this->success('保存成功') : $this->error('保存失败');
		}else{
			$addon  =   $addons_db->find($id);
			if(!$addon) $this->error('插件未安装');

			$addon_class = get_addon_class($addon['name']);
			if(!class_exists($addon_class)) trace("插件{$addon['name']}无法实例化,",'ADDONS','ERR');

			$data = new $addon_class;
			if(!$data->config_file) $this->error('此插件未使用配置项');

			$addon['addon_path']    = $data->addon_path;
			$addon['custom_config'] = $data->custom_config;

			$db_config       = $addon['config'];
			$addon['config'] = include $data->config_file;

			if($db_config){
				$db_config = json_decode($db_config, true);
				foreach ($addon['config'] as $key => $value) {
					if($value['type'] != 'group'){
						$addon['config'][$key]['value'] = $db_config[$key];
					}else{
						foreach ($value['options'] as $gourp => $options) {
							foreach ($options['options'] as $gkey => $value) {
								$addon['config'][$key]['options'][$gourp]['options'][$gkey]['value'] = $db_config[$gkey];
							}
						}
					}
				}
			}
			$this->assign('data',$addon);
			if($addon['custom_config']) $this->assign('custom_config', $this->fetch($addon['addon_path'].$addon['custom_config']));

			$this->assign('id', $id);
			$this->display('addon_config');
		}
	}

	/**
	 * 禁用插件
	 */
	public function addonDisabled($id = 0){
		if(IS_POST){
			$res = M()->execute("update ". C('DB_PREFIX') ."addons set status = status * -1 + 1 where id = {$id}");
			$res ? $this->success('操作成功') : $this->error('操作失败');
		}
	}

	//////////////////////////////////////////// 已安装插件列表 ////////////////////////////////////////////

	public function load($name = '', $page = 1, $rows = 10){
		$this->assign('name', $name);
		$class = get_addon_class($name);
		if(!class_exists($class)) $this->error('插件不存在');

		$addon = new $class();
		$this->assign('addon', $addon);

		$param = $addon->admin_list;

		if(!$param) $this->error('插件列表信息不正确');

		extract($param);

		if(!isset($fields))
			$fields = '*';
		if(!isset($search_key))
			$key = 'title';
		else
			$key = $search_key;
		if(isset($_REQUEST[$key])){
			$map[$key] = array('like', '%'.$_GET[$key].'%');
			unset($_REQUEST[$key]);
		}



		if(isset($model)){
			$model  =   D("Addons://{$name}/{$model}");
			// 条件搜索
			$map    =   array();
			foreach($_REQUEST as $name=>$val){
				if($fields == '*'){
					$fields = $model->getDbFields();
				}
				if(in_array($name, $fields)){
					$map[$name] = $val;
				}
			}
			if(!isset($order))  $order = '';
			$order = explode(' ', $order);

			$fields = array();
			foreach ($list_grid as &$value) {
				// 字段:标题:链接
				$val = explode(':', $value);
				// 支持多个字段显示
				$field = explode(',', $val[0]);
				$value = array('field' => $field, 'title' => $val[1]);
				if(isset($val[2])){
					// 链接信息
					$value['href'] = $val[2];
					// 搜索链接信息中的字段信息
					preg_replace_callback('/\[([a-z_]+)\]/', function($match) use(&$fields){$fields[]=$match[1];}, $value['href']);
				}
				if(strpos($val[1],'|')){
					// 显示格式定义
					list($value['title'],$value['format']) = explode('|',$val[1]);
				}
				foreach($field as $val){
					$array = explode('|',$val);
					$fields[] = $array[0];
				}
			}

			$option = array(
				'db'      => $model,
				'where'   => $map,
				'page'    => $page,
				'rows'    => $rows,
				'sort'    => $order[0] ?: '',
				'order'   => $order[1] ?: '',
				'assign'  => array(
					'param'  => $param,
					'addon'  => $addon,
					'fields' => $fields,
				),
				'display' => 'load_datagrid',
			);
			$this->datagrid($option);
		}

		if($addon->custom_adminlist)
			$this->assign('custom_adminlist', $this->fetch($addon->addon_path.$addon->custom_adminlist));


	}
}