<?php
namespace Admin\Controller;
use Admin\Controller\CommonController;

/**
 * 内容管理模块
 * @author wangdong
 */
class ContentController extends CommonController {
	/**
	 * 权限控制，默认为查看 view
	 */
	public function _initialize(){
		parent::_initialize();

		//权限判断
		if(user_info('roleid') != 1 && strpos(ACTION_NAME, 'public_') === false) {

			$category_priv_db = M('category_priv');
			$tmp = explode('_', ACTION_NAME, 1);
			$action = strtolower($tmp[0]);
			unset($tmp);

			$auth = dict('auth', 'Category');  //权限列表
			if(!in_array($action, array_keys($auth))) $action = 'view';

			$catid  = I('get.catid', 0, 'intval');
			$roleid = user_info('roleid');

			$info = $category_priv_db->where(array('catid'=>$catid, 'roleid'=> $roleid, 'action'=>$action))->count();
			if(!$info){
				//兼容iframe加载
				if(IS_GET && strpos(ACTION_NAME,'_iframe') !== false){
					exit('<style type="text/css">body{margin:0;padding:0}</style><div style="padding:6px;font-size:12px">您没有权限操作该项</div>');
				}
				//普通返回
				if(IS_AJAX && IS_GET){
					exit('<div style="padding:6px">您没有权限操作该项</div>');
				}else {
					$this->error('您没有权限操作该项');
				}
			}
		}
	}

	/**
	 * 获取栏目字段数据(propertygrid)
	 * @param int|array $param
	 * @param mix $formatter
	 * @param array $info
	 * @return array
	 */
	private function field($param, $formatter = '', $info = array()){
		if(!is_array($param)){
			$param = array(
				'catid' => (is_numeric($param) ? $param : I('param.catid')),
				'field' => I('param.field'),
				'type'  => I('param.type'),
			);
		}
//		extract($param);
		$catid   = I('data.catid', 0, 'intVal', $param);
		$field   = I('data.field', 0, 'intVal', $param);
		$type    = I('data.type', 0, 'intVal', $param);

		$result  = array();
		$dict    = dict('', 'Category');
		if(!$field){
			if(!$type) {
				$category_db = D('Category');
				$type = $category_db->where(array('catid' => $catid))->getField('type');
			}
			$field = $dict['type'][substr($type, 0, 1)];
			if($type > 9) $field = $field['son'][$type];
			$field = $field['field'];
		}

		if(!is_array($info)) $info = array();

		$data  = $dict['field'][$field];
		foreach ($data as $key=>$fieldInfo){
			$fieldInfo['name']  = isset($fieldInfo['required']) && $fieldInfo['required'] ? "*{$fieldInfo['name']}" : $fieldInfo['name'];
			$fieldInfo['value'] = isset($info[$key]) ? $info[$key] : (isset($fieldInfo['default']) ? $fieldInfo['default'] : '');
			$fieldInfo['key']   = $key;

			if($formatter) $formatter($key, $fieldInfo, $data);

			array_push($result, $fieldInfo);
		}
		return $result;
	}

	//获取当前model
	private function db($param){
		if(!is_array($param)){
			$param = array(
				'catid' => (is_numeric($param) ? $param : I('param.catid')),
				'field' => I('param.field'),
				'type'  => I('param.type'),
			);
		}
		$catid   = I('data.catid', 0, 'intVal', $param);
		$field   = I('data.field', 0, 'intVal', $param);
		$type    = I('data.type', 0, 'intVal', $param);

		$result  = array();
		$dict    = dict('', 'Category');
		if(!$field){
			if(!$type) {
				$category_db = D('Category');
				$type = $category_db->where(array('catid' => $catid))->getField('type');
			}
			$field = $dict['type'][substr($type, 0, 1)];
			if($type > 9) $field = $field['son'][$type];
			$field = $field['field'];
		}
		$model = isset($dict['map'][$field]) ? $dict['map'][$field] : $field;
		return M($model);
	}

	/**
	 * 页面管理
	 */
	public function page($catid = 0){
		$db = $this->db($catid);

		if(IS_POST){
			//右侧字段数据
			$right = $db->field(array('content'), true)->where(array('catid'=>$catid))->find(); //右侧数据
			$data  = $this->field($catid, function($key, &$info, $list){
				switch($key){
					case 'status':
						$info['value'] = $info['value'] ? '发布' : '不发布';
						break;
				}
			}, $right);
			$this->ajaxReturn($data);

		}else{
			$db   = $this->db($catid);
			$info = $db->where(array('catid'=>$catid))->find();
			$this->assign('info', $info);

			//面包屑
			$category_db = D('Category');
			$currentpos  = $category_db->currentPos($catid);  //栏目位置
			$menuid      = I('get.menuid');
			$menu_db     = D('Menu');
			$currentpos  = $menu_db->currentPos(I('get.menuid')) . $currentpos;  //栏目位置
			$this->assign('title', $currentpos);

			$this->display();
		}
	}

	/**
	 * 页面保存
	 */
	public function savePage($catid = 0){
		if(IS_POST){
			$db = $this->db($catid);

			$data               = I('post.base');
			$data['updatetime'] = time();
			$data['status']     = (isset($data['status']) && $data['status'] == '发布') ? '1' : '0';

			if($db->where(array('catid'=>$catid))->count()){
				$res = $db->where(array('catid'=>$catid))->save($data);
			}else{
				$data['catid'] = $catid;
				$data['uuid']  = uuid();
				$res = $db->add($data);
			}
			$res ? $this->success('操作成功') : $this->error('操作失败');
		}
	}

	/**
	 * 文章列表管理
	 */
	public function article($catid = 0, $search = array(), $page = 1, $rows = 10, $sort = 'istop,updatetime', $order = 'asc,desc'){
		$db = $this->db($catid);

		if(IS_POST){
			//搜索
			$where = array("catid = '{$catid}'");

			foreach ($search as $k=>$v){
				if(strlen($v) < 1) continue;
				switch ($k){
					case 'id':
					case 'istop':
					case 'status':
						$where[] = "`{$k}` = '{$v}'";
						break;
					case 'title':
					case 'keywords':
					case 'description':
					case 'author':
						$where[] = "`{$k}` like '%{$v}%'";
						break;
					case 'updatetime.begin':
						if(!check_datetime($v)){
							unset($search[$k]);
							continue;
						}
						$v       = strtotime($v);
						$where[] = "`updatetime` >= '{$v}'";
						break;
					case 'updatetime.end':
						if(!check_datetime($v)){
							unset($search[$k]);
							continue;
						}
						$v       = strtotime($v);
						$where[] = "`regtime` <= '{$v}'";
						break;
				}
			}
			$where = implode(' and ', $where);

			//排序，支持多个字段
			$sorts  = explode(',', $sort);
			$orders = explode(',', $order);
			$order  = array();
			foreach($sorts as $k=>$sort){
				$order[$sort] = $orders[$k];
			}

			$limit  = ($page - 1) * $rows . "," . $rows;
			$total  = $db->where($where)->count();
			$list   = $total ? $db->where($where)->order($order)->limit($limit)->select() : array();

			foreach($list as &$info){
				foreach($info as $key=>&$val){
					switch($key){
						case 'status':
							$val = $val ? '发布' : '<font color="red">未发布</font>';
							break;
						case 'istop':
							$val = $val ? '<font color="red">置顶</font>' : '未置顶';
							break;
						case 'updatetime':
							$val = date('Y-m-d H:i:s', $val);
							break;
					}
				}
			}

			$data = array('total'=>$total, 'rows'=>$list);
			$this->ajaxReturn($data);
		}else{
			//面包屑
			$category_db = D('Category');
			$currentpos  = $category_db->currentPos($catid);  //栏目位置
			$menuid      = I('get.menuid');
			$menu_db     = D('Menu');
			$currentpos  = $menu_db->currentPos(I('get.menuid')) . $currentpos;  //栏目位置
			$this->assign('title', $currentpos);

			//工具栏
			$toolbars = $category_db->getToolbars($catid, $type);
			$this->assign('toolbars', $toolbars);

			$this->display();
		}
	}

	//添加文章
	public function addArticle($catid = 0){
		if(IS_POST){
			//右侧字段数据
			if(I('get.grid') == 'propertygrid'){
				$data = $this->field($catid, function($key, &$info, $list){
					switch($key){
						case 'status':
							$info['value'] = $info['value'] ? '发布' : '不发布';
							break;
					}
				});
				$this->ajaxReturn($data);

			//提交数据
			}else{
				$db = $this->db($catid);

				$data               = I('post.base');

				$data['islink']     = (isset($data['islink']) && $data['islink'] == '开启') ? '1' : '0';
				if($data['islink'] == '1' && !strlen(trim($data['url']))) $this->error('转向链接地址不能为空');

				$data['addtime']    = $data['addtime'] ? strtotime($data['addtime']) : time();
				$data['updatetime'] = time();
				$data['status']     = (isset($data['status']) && $data['status'] == '发布') ? '1' : '0';
				$data['istop']      = (isset($data['istop']) && $data['istop'] == '开启') ? '1' : '0';
				$data['catid']      = $catid;
				$data['uuid']       = uuid();

				$res = $db->add($data);

				$res ? $this->success('操作成功') : $this->error('操作失败');
			}
		}else{
			//面包屑
			$category_db = D('Category');
			$currentpos  = $category_db->currentPos($catid);  //栏目位置
			$menuid      = I('get.menuid');
			$menu_db     = D('Menu');
			$currentpos  = $menu_db->currentPos(I('get.menuid')) . $currentpos;  //栏目位置
			$this->assign('title', $currentpos);

			$this->display('article_add');
		}
	}

	//编辑文章
	public function editArticle($catid = 0, $id = 0){
		$db = $this->db($catid);

		if(IS_POST){
			//右侧字段数据
			if(I('get.grid') == 'propertygrid'){
				$right = $db->where(array('id'=>$id))->find();
				$data  = $this->field($catid, function($key, &$info, $list){
					switch($key){
						case 'status':
							$info['value'] = $info['value'] ? '发布' : '不发布';
							break;
						case 'istop':
						case 'islink':
							$info['value'] = $info['value'] ? '开启' : '关闭';
							break;
						case 'addtime':
							$info['value'] = $info['value'] > 100000000 ? date('Y-m-d H:i:s', $info['value']) : '';
							break;
					}
				}, $right);
				$this->ajaxReturn($data);

			//提交数据
			}else{
				$data               = I('post.base');

				$data['islink']     = (isset($data['islink']) && $data['islink'] == '开启') ? '1' : '0';
				if($data['islink'] == '1' && !strlen(trim($data['url']))) $this->error('转向链接地址不能为空');

				$data['addtime']    = $data['addtime'] ? strtotime($data['addtime']) : time();
				$data['updatetime'] = time();
				$data['status']     = (isset($data['status']) && $data['status'] == '发布') ? '1' : '0';
				$data['istop']      = (isset($data['istop']) && $data['istop'] == '开启') ? '1' : '0';

				$res = $db->save($data);

				$res ? $this->success('操作成功') : $this->error('操作失败');
			}
		}else{
			//面包屑
			$category_db = D('Category');
			$currentpos  = $category_db->currentPos($catid);  //栏目位置
			$menuid      = I('get.menuid');
			$menu_db     = D('Menu');
			$currentpos  = $menu_db->currentPos(I('get.menuid')) . $currentpos;  //栏目位置
			$this->assign('title', $currentpos);

			$id   = I('get.id', 0);
			$info = $db->where(array('id'=>$id))->find();
			$this->assign('info', $info);
			$this->display('article_edit');
		}
	}

	//删除文章
	public function deleteArticle($catid = 0, $ids = ''){
		if (IS_POST) {
			$db  = $this->db($catid);
			$ids = explode(',', $ids);
			$res = $db->where(array('catid' => $catid, 'id' => array('in', $ids)))->delete();

			$res ? $this->success('删除成功') : $this->error('删除失败');
		}
	}

	//置顶文章
	public function topArticle($catid = 0, $ids = ''){
		if (IS_POST) {
			$db  = $this->db($catid);
			$ids = explode(',', $ids);
			$res = $db->where(array('catid'=>$catid, 'id'=>array('in', $ids)))->save(array('istop'=>1, 'updatetime'=>time()));

			$res ? $this->success('操作成功') : $this->error('操作失败');
		}
	}
}