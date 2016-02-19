<?php
namespace Admin\Model;
use Think\Model;

class CategoryModel extends Model{
	protected $tableName = 'category';
	protected $pk = 'catid';


	/**
	 * 内容管理左侧导航
	 */
	public function getTree($parentid = 0, $menuid = ''){
		$level = C('CATEGORY_LEVEL');

		$field = array('catid as id', '`catname` as `text`', 'type', 'icon');
		$order = '`listorder` asc, `id` desc';
		$list  = $this->field($field)->where(array('parentid'=>$parentid, 'status'=>1, 'level'=>array('elt', $level)))->order($order)->select();
		if (is_array($list)) {
			$dict = dict('type', 'Category');
			foreach ($list as $k => &$arr) {
				$type = $dict[substr($arr['type'], 0, 1)];
				if($arr['type'] > 9) $type = $type['son'][$arr['type']];

				$arr['url']      = U($type['url'], array('field'=>$type['field'], 'type'=>$arr['type'], 'menuid'=>$menuid, 'catid'=>$arr['id']));
				$arr['iconCls']  = $arr['icon'] ?: $type['icon'];
				$arr['children'] = $this->getTree($arr['id'], $menuid);
			}
		} else {
			$list = array();
		}
		return $list;
	}

	/**
	 * 栏目下拉列表
	 */
	public function getSelectTree($parentid = 0){
		$level = C('CATEGORY_LEVEL');

		$field = array('`catid` as `id`','`catname` as `text`', 'type', 'icon');
		$order = '`listorder` asc, `id` desc';
		$data = $this->field($field)->where(array('parentid'=>$parentid, 'level'=>array('lt', $level)))->order($order)->select();
		if (is_array($data)){
			$dict = dict('type', 'Category');
			foreach ($data as &$arr){
				$type = $dict[substr($arr['type'], 0, 1)];
				if($arr['type'] > 9) $type = $type['son'][$arr['type']];

				$arr['children'] = $this->getSelectTree($arr['id']);
				$arr['iconCls']  = $arr['icon'] ?: $type['icon'];
			}
		}else{
			$data = array();
		}
		return $data;
	}

	/**
	 * 当前位置
	 * @param $id 栏目id
	 * @return string
	 */
	public function currentPos($id) {
		$info = $this->field(array('catid','catname','parentid','type'))->where(array('catid'=>$id))->find();
		$str  = '';

		if($info['parentid']) {
			$str = $this->currentPos($info['parentid']);
		}

		$dict = dict('type', 'Category');
		$type = $dict[substr($info['type'], 0, 1)];
		if($info['type'] > 9) $type = $type['son'][$info['type']];

		return $str . $info['catname'] . " <i class='fa fa-angle-double-right'></i> ";
	}

	/**
	 * 检查上级栏目设置是否正确
	 * @param int $id       栏目id
	 * @param int $parentid 上级id
	 * @return bool
	 */
	public function checkParentId($id, $parentid){
		if($id == $parentid) return false;  //上级栏目不能与本级菜单相同

		$data = $this->field(array('catid'))->where(array('parentid'=>$id))->order('`listorder` ASC,`catid` DESC')->select();
		if(is_array($data)){
			foreach ($data as &$arr){
				if($arr['id'] == $parentid) return false; //上级菜单不能与本级菜单子菜单

				return $this->checkParentId($arr['catid'], $parentid);
			}
		}else{
			return true;
		}
		return true;
	}

	/**
	 * 设置子栏目级别
	 * @param int $level
	 * @param int $parentid
	 * @return bool
	 */
	public function setSonLevel($level, $parentid){
		$list = $this->field('catid')->where(array('parentid'=>$parentid))->select();

		if(is_array($list)){
			$level = $level + 1;
			$this->where(array('parentid'=>$parentid))->save(array('level'=>$level));

			foreach($list as $info){
				$this->setSonLevel($level, $info['catid']);
			}
		}
		return true;
	}

	/**
	 * 删除子栏目
	 * @param int $parentids
	 * @return bool
	 */
	public function deleteSonCategory($parentids){
		$list = $this->field('catid')->where(array('parentid'=>array('in', $parentids)))->select();

		if(is_array($list)){
			$this->where(array('parentid'=>array('in', $parentids)))->delete();

			foreach($list as $info){
				$this->deleteSonCategory($info['catid']);
			}
		}
		return true;
	}

	/**
	 * 获取当前页面可用的工具栏列表
	 * @param int $catid
	 * @param int $type
	 * @return array
	 */
	public function getToolbars($catid, $type){
		if(!$type) $type = $this->where(array('catid' => $catid))->getField('type');

		$dict   = dict('type', 'Category');
		$roleid = user_info('roleid');

		$field = $dict[substr($type, 0, 1)];
		if($type > 9) $field = $field['son'][$type];
		$toolbars = $field['toolbar'];

		if($roleid == 1) return $toolbars; //管理员不需要设置权限

		//根据权限显示可用的工具栏
		$category_priv_db = M('category_priv');
		$list = $category_priv_db->field('action')->where(array(
			'catid'=>$catid,
			'roleid'=> $roleid,
			'action'=>array('in', array_keys($toolbars))
		))->select();

		$result = array();
		foreach($list as $info){
			$result[$info['action']] = $toolbars[$info['action']];
		}

		return $result;
	}
}
