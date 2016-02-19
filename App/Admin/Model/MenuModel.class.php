<?php
namespace Admin\Model;
use Think\Model;

class MenuModel extends Model{
	protected $tableName = 'menu';
	protected $pk        = 'id';
	public    $error;
	
	/**
	 * 按父ID查找菜单子项
	 * @param integer $parentid   父菜单ID  
	 * @param integer $withSelf  是否包括他自己
	 */
	public function getMenu($parentid = 0, $withSelf = 0) {
		$parentid = intval($parentid);
		$roleid   = user_info('roleid');
		$result   = $this->where(array('parentid'=>$parentid, 'display'=>1))->order('listorder ASC')->limit(1000)->select();

		if (!is_array($result)) $result=array();
		if($withSelf) {
			$result2 = $this->where(array('id'=>$parentid))->limit(1)->select();
			$result  = array_merge($result2, $result);
		}

		//菜单图标
		foreach($result as &$info){
			$info['icon'] = menu_icon($info['level'], $info['icon']);
		}

		//权限检查
		if($roleid == 1) return $result;

		$admin_role_priv_db = M('admin_role_priv');
		$array              = array();
		foreach($result as $v) {
			$action = $v['a'];
			if(preg_match('/^public_/',$action)) {
				$array[] = $v;
			} else {
				if(preg_match('/^ajax_(\w+)_/',$action,$_match)) $action = $_match[1];
				$r = $admin_role_priv_db->where(array('c'=>$v['c'],'a'=>$action,'roleid'=>$roleid))->find();
				if($r) $array[] = $v;
			}
		}
		return $array;
	}

	/**
	 * 获取工具栏按钮
	 * @param $id
	 * @return array
	 */
	public function getToolBar($id){
		$roleid = user_info('roleid');
		$result = $this->where(array('parentid'=>$id, 'display'=>1, 'toolbar'=>1))->order('listorder ASC')->limit(1000)->select();

		//菜单图标
		foreach($result as &$info){
			$info['icon'] = menu_icon($info['level'], $info['icon']);
		}

		//权限检查
		if($roleid == 1) return $result ? $result : array();
		$admin_role_priv_db = M('admin_role_priv');
		$array              = array();
		foreach($result as $v) {
			$action = $v['a'];
			if(preg_match('/^public_/',$action)) {
				$array[] = $v;
			} else {
				if(preg_match('/^ajax_(\w+)_/',$action,$_match)) $action = $_match[1];
				$r = $admin_role_priv_db->where(array('c'=>$v['c'],'a'=>$action,'roleid'=>$roleid))->find();
				if($r) $array[] = $v;
			}
		}
		return $array;
	}
	
	/**
	 * 当前位置
	 * @param $id 菜单id
	 * @return string
	 */
	public function currentPos($id) {
		$info = $this->where(array('id'=>$id))->find(array('id','name','parentid','level','icon'));
		$str = '';
		if($info['parentid']) {
			$str = $this->currentPos($info['parentid']);
		}
		return $str . $info['name'] . " <i class='fa fa-angle-double-right'></i> ";
	}
	
	/**
	 * 菜单列表
	 */
	public function getTree($parentid = 0) {
		$field = array('id','`name`','listorder','`id` as `operateid`');
		$order = '`listorder` ASC,`id` DESC';
		$data  = $this->field($field)->where(array('parentid'=>$parentid))->order($order)->select();
		if (is_array($data)){
			foreach ($data as &$arr){
				$arr['children'] = $this->getTree($arr['id']);
			}
		}else{
			$data = array();
		}
		return $data;
	}
	
	/**
	 * 权限管理列表
	 */
	public function getRoleTree($parentid = 0, $roleid = 0){
		$field = array('id','`name` as `text`','c','a', 'level', 'icon');
		$order = '`listorder` ASC,`id` DESC';

		$data = $this->field($field)->where("`parentid`='{$parentid}'")->order($order)->select();
		if (is_array($data)){
			$admin_role_priv_db = M('admin_role_priv');
			foreach ($data as $k=>&$arr){
				$arr['attributes']['parent'] = $this->getParentIds($arr['id']);
				$arr['children'] = $this->getRoleTree($arr['id'], $roleid);
				$arr['iconCls']  = menu_icon($arr['level'], $arr['icon']);
				if(is_array($arr['children']) && !empty($arr['children']) ){
					$arr['state'] = 'closed';
				}else{
					//勾选默认菜单
					$check = $admin_role_priv_db->where(array('c'=>$arr['c'],'a'=>$arr['a'],'roleid'=>$roleid))->count();
					if($check) $arr['checked'] = true;
				}
			}
		}else{
			$data = array();
		}
		return $data;
	}
	
	/**
	 * 获取菜单父级id
	 */
	public function getParentIds($id, $result = null){
		$parentid = $this->where(array('id'=>$id))->getField('parentid');
		if($parentid){
			$result .= $result ? ','.$parentid : $parentid;
			$result = $this->getParentIds($parentid, $result);
		}
		return $result;
	}

	/**
	 * 菜单下拉列表
	 */
	public function getSelectTree($parentid = 0){
		$field = array('id','`name` as `text`', 'level', 'icon');
		$order = '`listorder` ASC,`id` DESC';
		$data  = $this->field($field)->where(array('parentid'=>$parentid, 'level'=>array('LT', 4)))->order($order)->select();

		if (is_array($data)){
			foreach ($data as &$arr){
				$arr['iconCls']  = menu_icon($arr['level'], $arr['icon']);
				$arr['children'] = $this->getSelectTree($arr['id']);
			}
		}else{
			$data = array();
		}
		return $data;
	}


	/**
	 * 检查上级菜单设置是否正确
	 */
	public function checkParentId($id, $parentid){
		if($id == $parentid) return false;  //上级菜单不能与本级菜单相同

		$data = $this->field(array('id'))->where(array('parentid'=>$id))->order('`listorder` ASC,`id` DESC')->select();
		if(is_array($data)){
			foreach ($data as &$arr){
				if($arr['id'] == $parentid) return false; //上级菜单不能与本级菜单子菜单

				return $this->checkParentId($arr['id'], $parentid);
			}
		}else{
			return true;
		}
		return true;
	}

	/**
	 * 设置子菜单级别
	 * @param int $level
	 * @param int $parentid
	 * @return bool
	 */
	public function setSonLevel($level, $parentid){
		$list = $this->field('id')->where(array('parentid'=>$parentid))->select();

		if(is_array($list)){
			$level = $level + 1;
			$this->where(array('parentid'=>$parentid))->save(array('level'=>$level));

			foreach($list as $info){
				$this->setSonLevel($level, $info['id']);
			}
		}
		return true;
	}

	/**
	 * 删除子菜单
	 * @param int $parentid
	 * @return bool
	 */
	public function deleteSonMenu($parentids){
		$list = $this->field('id')->where(array('parentid'=>array('in', $parentids)))->select();

		if(is_array($list)){
			$this->where(array('parentid'=>array('in', $parentids)))->delete();

			foreach($list as $info){
				$this->deleteSonMenu($info['id']);
			}
		}
		return true;
	}
}