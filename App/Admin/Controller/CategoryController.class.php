<?php
namespace Admin\Controller;
use Admin\Controller\CommonController;

/**
 * 栏目管理模块
 * @author wangdong
 */
class CategoryController extends CommonController {
	/**
	 * 登录日志
	 */
	public function category($id = 0, $page = 1, $rows = 10){

		if(IS_POST){
			$level = C('CATEGORY_LEVEL');
			$category_db = M('category');
			$where  = array('parentid'=>$id, 'level'=>array('elt', $level));
			$limit  = ($page - 1) * $rows . "," . $rows;
			$order  = array('listorder'=>'asc', 'catid'=>'desc');
			$total  = $category_db->where($where)->count();

			if($id) $limit = null;

			$list = $total ? $category_db->where($where)->order($order)->limit($limit)->select() : array();
			$dict = dict('type', 'Category');
			foreach($list as &$info){
				$type = $dict[substr($info['type'], 0, 1)];
				if($info['type'] > 9) $type = $type['son'][$info['type']];

				$info['iconCls'] = $info['icon'] ?: $type['icon'];
				$info['status']  = $info['status'] ? '启用' : '<font color="red">禁用</font>';
				$info['type']    = $type['name'];

				if($category_db->where(array('parentid'=>$info['catid'],'level'=>array('elt', $level)))->count()){
					$info['state'] = 'closed';
				}
			}

			$data = $id ? $list : array('total'=>$total, 'rows'=>$list);
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
	 * 栏目添加
	 */
	public function categoryAdd(){
		if(IS_POST){
			$category_db = D('Category');
			$data        = I('post.info');

			//栏目级别
			if($data['parentid'] > 0){
				$level = $category_db->where(array('catid'=>$data['parentid']))->getField('level');
				$data['level'] = $level + 1;
			}else{
				$data['level'] = 1;
			}
			if(C('CATEGORY_LEVEL') < $data['level']) $this->error('超过级数限制');

			$status = $category_db->add($data);
			$status ? $this->success('添加成功') : $this->error('添加失败');
		}else{
			$parentid = I('get.parentid', 0);
			if($parentid > 0){
				$category_db = D('Category');
				$level       = $category_db->where(array('catid'=>$parentid))->getField('level');
				if(C('CATEGORY_LEVEL') <= $level) $parentid = 0;
			}
			$this->assign('parentid', $parentid);

			$type = dict('type', 'Category');
			$this->assign('typeList', $type);
			$this->display('category_add');
		}
	}

	/**
	 * 栏目编辑
	 */
	public function categoryEdit(){
		$category_db = D('Category');
		if(IS_POST){
			$data = I('post.info');

			//菜单级别
			if($data['parentid'] > 0){
				$level = $category_db->where(array('catid'=>$data['parentid']))->getField('level');
				$data['level'] = $level + 1;
			}else{
				$data['level'] = 1;
			}
			if(C('CATEGORY_LEVEL') < $data['level']) $this->error('超过级数限制');

			//上级菜单验证
			if(!$category_db->checkParentId($data['catid'], $data['parentid'])){
				$this->error('上级栏目设置失败');
			}

			$status = $category_db->save($data);
			if($status) $category_db->setSonLevel($data['level'], $data['catid']);
			$status ? $this->success('修改成功') : $this->error('修改失败');
		}else{
			$id   = I('get.id');
			$info = $category_db->where(array('catid'=>$id))->find();
			$this->assign('info', $info);

			$type = dict('type', 'Category');
			$this->assign('typeList', $type);

			$this->display('category_edit');
		}
	}

	/**
	 * 栏目删除
	 */
	public function categoryDelete($ids = ''){
		if(IS_POST){
			$ids = explode(',', $ids);

			$category_db = D('Category');
			$res     = $category_db->where(array('catid'=>array('in', $ids)))->delete();
			if($res) $category_db->deleteSonCategory($ids);

			$res ? $this->success('删除成功') : $this->error('删除失败');
		}
	}

	/**
	 * 栏目下拉框
	 */
	public function public_categorySelect(){
		$category_db = D('Category');
		$data = $category_db->getSelectTree();
		$data = array(0=>array('id'=>0,'text'=>'作为一级栏目', 'iconCls'=>'fa fa-home', 'children'=>$data));
		$this->ajaxReturn($data);
	}
}