<?php
namespace Admin\Controller;
use Admin\Controller\CommonController;

/**
 * 系统模块
 * @author wangdong
 */
class SystemController extends CommonController {
	//////////////////////////////////////////// 系统设置 ////////////////////////////////////////////

	/**
	 * 设置列表
	 */
	public function setting(){
		if(IS_POST){
			$setting_db = D('Setting');
			$data       = $setting_db->getSetting();
			$this->ajaxReturn($data);
		}else{
			$menu_db    = D('Menu');
			$menuid     = I('get.menuid');
			$currentpos = $menu_db->currentPos(I('get.menuid'));  //栏目位置
			$toolbars   = $menu_db->getToolBar($menuid);

			$this->assign('title', $currentpos);
			$this->assign('toolbars', $toolbars);
			$this->display();
		}
	}

	/**
	 * 设置保存
	 */
	public function settingSave(){
		if(IS_POST){
			$setting_db = D('Setting');
			$res        = $setting_db->set($_POST['info']);
			$res ? $this->success('操作成功') : $this->error('操作失败');
		}
	}

	/**
	 * 设置恢复
	 */
	public function settingReset(){
		if(IS_POST){
			$setting_db = D('Setting');
			if($setting_db->where('1')->count()){
				$res = $setting_db->where('1')->delete();
				if($res){
					$setting_db->clearCatche();
					$this->success('操作成功');
				}else{
					$this->error('操作失败');
				}
			}
			$this->success('操作成功');
		}
	}

	//////////////////////////////////////////// 菜单设置 ////////////////////////////////////////////

	/**
	 * 菜单列表
	 */
	public function menu($id = 0, $page = 1, $rows = 10){
		$menu_db    = D('Menu');
		if(IS_POST){
			$where  = array('parentid'=>$id);
			$limit  = ($page - 1) * $rows . "," . $rows;
			$order  = array('listorder'=>'asc');
			$total  = $menu_db->where($where)->count();

			if($id) $limit = null;

			$list   = $total ? $menu_db->where($where)->order($order)->limit($limit)->select() : array();
			foreach($list as &$info){
				if($menu_db->where(array('parentid'=>$info['id']))->count()){
					$info['state'] = 'closed';
				}
				$info['display']  = $info['display'] ? '显示' : '<font color="red">隐藏</font>';
				$info['iconCls'] = menu_icon($info['level'], $info['icon']);
				if($info['icon']) $info['iconCls'] = $info['icon'];
			}

			$data = $id ? $list : array('total'=>$total, 'rows'=>$list);
			$this->ajaxReturn($data);
		}else{
			$menuid     = I('get.menuid');
			$currentpos = $menu_db->currentPos(I('get.menuid'));  //栏目位置
			$toolbars   = $menu_db->getToolBar($menuid);

			$this->assign('title', $currentpos);
			$this->assign('toolbars', $toolbars);

			$this->display();
		}
	}

	/**
	 * 菜单添加
	 */
	public function menuAdd(){
		if(IS_POST){
			$menu_db = D('Menu');
			$data    = I('post.info');

			//菜单级别
			if($data['parentid'] > 0){
				$level = $menu_db->where(array('id'=>$data['parentid']))->getField('level');
				$data['level'] = $level + 1;
			}else{
				$data['level'] = 1;
			}

			$res = $menu_db->add($data);

			$res ? $this->success('添加成功') : $this->error('添加失败');
		}else{
			$this->display('menu_add');
		}
	}

	/**
	 * 菜单编辑
	 */
	public function menuEdit(){
		$menu_db = D('Menu');
		if(IS_POST){
			$data = I('post.info');

			//菜单级别
			if($data['parentid'] > 0){
				$level = $menu_db->where(array('id'=>$data['parentid']))->getField('level');
				$data['level'] = $level + 1;
			}else{
				$data['level'] = 1;
			}

			//上级菜单验证
			if(!$menu_db->checkParentId($data['id'], $data['parentid'])){
				$this->error('上级菜单设置失败');
			}

			$res  = $menu_db->save($data);
			if($res) $menu_db->setSonLevel($data['level'], $data['id']);

			$res ? $this->success('修改成功') : $this->error('修改失败');
		}else{
			$id   = I('get.id');
			$info = $menu_db->where(array('id'=>$id))->find();

			$this->assign('info', $info);
			$this->display('menu_edit');
		}
	}

	/**
	 * 菜单删除
	 */
	public function menuDelete($ids = ''){
		if(IS_POST){
			$ids = explode(',', $ids);

			$menu_db = D('Menu');
			$res     = $menu_db->where(array('id'=>array('in', $ids)))->delete();
			if($res) $menu_db->deleteSonMenu($ids);

			$res ? $this->success('删除成功') : $this->error('删除失败');
		}
	}

	/**
	 * 菜单下拉框
	 */
	public function public_menuSelectTree(){
		$menu_db = D('Menu');
		$data    = $menu_db->getSelectTree();
		$data    = array(0=>array('id'=>0,'text'=>'作为一级菜单','iconCls'=>'fa fa-home','children'=>$data));
		$this->ajaxReturn($data);
	}

	/**
	 * 验证菜单名称(level:3)是否已存在
	 */
	public function public_menuNameCheck($name){
		if(I('get.default') == $name) {
			exit('true');
		}

		$menu_db = D('Menu');
		$exists  = $menu_db->where(array('name'=>$name, 'level'=>3))->count();

		if ($exists) {
			exit('false');
		}else{
			exit('true');
		}
	}



	//////////////////////////////////////////// 邮件模板 ////////////////////////////////////////////

	/**
	 * 邮件模板列表
	 */
	public function email($search = array(), $page = 1, $rows = 10, $sort = 'id', $order = 'desc'){
		//搜索
		$where = array();

		foreach ($search as $k=>$v){
			if(strlen($v) < 1) continue;
			switch ($k){
				case 'id':
				case 'code':
					$where[] = "`{$k}` = '{$v}'";
					break;
				case 'subject':
					$where[] = "`{$k}` like '%{$v}%'";
					break;
				case 'addtime.begin':
					if(!check_datetime($v)){
						unset($search[$k]);
						continue;
					}
					$v       = strtotime($v);
					$where[] = "`addtime` >= '{$v}'";
					break;
				case 'addtime.end':
					if(!check_datetime($v)){
						unset($search[$k]);
						continue;
					}
					$v       = strtotime($v);
					$where[] = "`addtime` <= '{$v}'";
					break;
				case 'edittime.begin':
					if(!check_datetime($v)){
						unset($search[$k]);
						continue;
					}
					$v       = strtotime($v);
					$where[] = "`edittime` >= '{$v}'";
					break;
				case 'edittime.end':
					if(!check_datetime($v)){
						unset($search[$k]);
						continue;
					}
					$v       = strtotime($v);
					$where[] = "`edittime` <= '{$v}'";
					break;
			}
		}
		$where = implode(' and ', $where);

		$this->datagrid(array(
			'db'        => M('email'),
			'where'     => $where,
			'page'      => $page,
			'rows'      => $rows,
			'sort'      => $sort,
			'order'     => $order,
			'formatter' => function($key, &$val, $info){
				switch($key){
					case 'addtime':
					case 'edittime':
						$val = $val ? date('Y-m-d H:i:s', $val) : '-';
						break;
				}
				return $val;
			},
		));
	}

	/**
	 * 邮件模板添加
	 */
	public function emailAdd(){
		if(IS_POST){
			$email_db        = M('email');
			$data            = I('post.info');
			$data['addtime'] = time();
			$res             = $email_db->add($data);

			$res ? $this->success('操作成功') : $this->error('操作失败');
		}else{
			$this->display('email_add');
		}
	}

	/**
	 * 邮件模板编辑
	 */
	public function emailEdit(){
		$email_db = M('email');
		if(IS_POST){
			$data             = I('post.info');
			$data['edittime'] = time();
			$res              = $email_db->save($data);

			$res ? $this->success('操作成功') : $this->error('操作失败');
		}else{
			$id   = I('get.id');
			$info = $email_db->where(array('id'=>$id))->find();
			$this->assign('info', $info);
			$this->display('email_edit');
		}
	}

	/**
	 * 邮件模板删除
	 */
	public function emailDelete($ids = ''){
		if(IS_POST){
			$ids = explode(',', $ids);

			$email_db = M('email');
			$res      = $email_db->where(array('id'=>array('in', $ids)))->delete();

			$res ? $this->success('删除成功') : $this->error('删除失败');
		}
	}

	/**
	 * 模版编号验证
	 */
	public function public_emailCodeCheck($code){
		if(I('get.default') == $code) {
			exit('true');
		}

		$email_db = M('email');
		$exists   = $email_db->where(array('code' => $code))->count();
		if ($exists) {
			exit('false');
		}else{
			exit('true');
		}
	}
}