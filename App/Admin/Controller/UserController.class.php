<?php
namespace Admin\Controller;
use Admin\Controller\CommonController;

/**
 * 系统用户模块
 * @author wangdong
 */
class UserController extends CommonController {

	//////////////////////////////////////////// 角色管理 ////////////////////////////////////////////

	/**
	 * 角色管理
	 */
	public function role($search = array(), $page = 1, $rows = 10, $sort = 'listorder', $order = 'asc'){
		//搜索
		$where = array();
		foreach ($search as $k=>$v){
			if(strlen($v) < 1) continue;
			switch ($k){
				case 'roleid':
				case 'status':
					$where[] = "`{$k}` = '{$v}'";
					break;
				case 'rolename':
					$where[] = "`{$k}` like '%{$v}%'";
					break;
			}
		}
		$where = implode(' and ', $where);

		$this->datagrid(array(
			'db'        => M('admin_role'),
			'where'     => $where,
			'page'      => $page,
			'rows'      => $rows,
			'sort'      => $sort,
			'order'     => $order,
			'formatter' => function($key, &$val, $info){
				switch($key){
					case 'status':
						$val = $val ? '启用' : '<font color="red">禁用</font>';
						break;
				}
				return $val;
			}
		));
	}

	/**
	 * 角色添加
	 */
	public function roleAdd(){
		if(IS_POST){
			$admin_role_db = D('AdminRole');
			$data          = I('post.info');
			if($admin_role_db->where(array('rolename'=>$data['rolename']))->count()){
				$this->error('角色名称已存在');
			}

			$res = $admin_role_db->add($data);
			$res ? $this->success('添加成功') : $this->error('添加失败');
		}else{
			$this->display('role_add');
		}
	}

	/**
	 * 角色编辑
	 */
	public function roleEdit(){
		$admin_role_db = D('AdminRole');
		if(IS_POST){
			$data = I('post.info');
			if($data['roleid'] == '1' && $data['status'] != '1') $this->error('系统默认角色不能被禁用');

			$res  = $admin_role_db->save($data);
			$res ? $this->success('修改成功') : $this->error('修改失败');
		}else{
			$roleid = I('get.id');
			$info   = $admin_role_db->where(array('roleid'=>$roleid))->find();

			$this->assign('info', $info);
			$this->display('role_edit');
		}
	}

	/**
	 * 角色删除
	 */
	public function roleDelete($ids = ''){
		if(IS_POST){
			$admin_role_db = D('AdminRole');
			$admin_db      = D('Admin');
			$idList        = explode(',', $ids);

			//检测
			foreach($idList as $id){
				if($id == '1') $this->error('系统默认角色不能被删除');

				$count = $admin_db->where(array('roleid'=>$id, 'status'=>1))->count();
				if($count) $this->error("角色ID为{$id}下面仍有 <b>{$count}</b> 个用户");
			}

			$result = $admin_role_db->where("roleid in ({$ids})")->delete();

			if ($result){
				$category_priv_db = M('category_priv');
				$category_priv_db->where("roleid in ({$ids})")->delete();

				$this->success('删除成功');
			}else {
				$this->error('删除失败');
			}
		}
	}

	/**
	 * 角色权限
	 */
	public function rolePriv($id = 0){
		$menu_db = D('Menu');
		if(IS_POST){
			$admin_role_priv_db = M('admin_role_priv');
			$ids                = explode(',', I('post.ids'));
			$ids                = array_unique($ids);

			$admin_role_priv_db->where(array('roleid'=>$id))->delete(); //清除旧数据
			//添加新数据
			if(!empty($ids)){
				$menuList = $menu_db->where(array('id'=>array('in', $ids)))->getField('id,c,a', true);
				foreach ($ids as $i){
					$admin_role_priv_db->add(array(
						'roleid' => $id,
						'c'      => $menuList[$i]['c'],
						'a'      => $menuList[$i]['a'],
					));
				}
			}

			$this->success('权限设置成功');
		}else{
			$data = $menu_db->getRoleTree(0, $id);
			$this->assign('data', $data);
			$this->display('role_priv');
		}
	}

	/**
	 * 角色栏目权限
	 */
	public function roleCat($id = 0){
		if(IS_POST){
			if(I('get.grid') == 'treegrid'){
				$category_db = M('category');
				$where  = array('parentid'=>$id);
				$order  = array('listorder'=>'asc', 'catid'=>'desc');
				$total  = $category_db->where($where)->count();

				$roleid = I('get.roleid');
				//获取已设置的权限列表
				$category_priv_db = M('category_priv');
				$privList         = $category_priv_db->where(array('roleid'=>$roleid))->select();
				$privs            = array();
				foreach($privList as $priv){
					$privs[$priv['action'] . '_' . $priv['catid']] = true;
				}

				//获取栏目数据
				$list = $total ? $category_db->where($where)->order($order)->select() : array();
				$dict = dict('', 'Category');
				foreach($list as &$info){
					$type = $dict['type'][substr($info['type'], 0, 1)];
					if($info['type'] > 9) $type = $type['son'][$info['type']];

					$info['iconCls'] = $info['icon'] ?: $type['icon'];
					$info['type']    = $type['name'];

					if($category_db->where(array('parentid'=>$info['catid']))->count()){
						$info['state'] = 'closed';
					}
					$info[auths]  = $type['auth'];
					$info['auth'] = array();
					foreach($type['auth'] as $auth){
						$key = $auth . '_' . $info['catid'];
						$checked = isset($privs[$key]) ? ' checked' : '';
						array_push($info['auth'], "<label><input type=\"checkbox\" data-catid=\"{$info['catid']}\" value=\"{$auth}\" {$checked}>{$dict['auth'][$auth]}</label>");
					}
					$info['auth'] = implode(' | ', $info['auth']);
				}

				$this->ajaxReturn($list);
			}else{
				$category_priv_db = M('category_priv');
				$info = I('post.info', '[]', 'json_decode');
				$data = array();

				$category_priv_db->where(array('roleid'=>$id))->delete();
				foreach($info as $catid=>$auths){
					foreach($auths as $auth){
						array_push($data, array('roleid'=>$id, 'catid'=>$catid, 'action'=>$auth));
					}
				}
				$res = $category_priv_db->addAll($data);
				$res ? $this->success('操作成功') : $this->error('操作失败');
			}
		}else{
			$this->display('role_cat');
		}
	}

	/**
	 * 验证角色名称是否存在
	 */
	public function public_checkRoleName($rolename){
		if (I('get.default') == $rolename) {
			exit('true');
		}
		$admin_role_db = D('AdminRole');
		$exists = $admin_role_db->where(array('rolename'=>$rolename))->field('rolename')->find();
		if ($exists) {
			exit('false');
		}else{
			exit('true');
		}
	}



	//////////////////////////////////////////// 用户管理 ////////////////////////////////////////////

	/**
	 * 用户列表
	 */
	public function user($search = array(), $page = 1, $rows = 10, $sort = 'lastlogintime', $order = 'desc'){
		//搜索
		$where = array("`status` = 1");

		foreach ($search as $k=>$v){
			if(strlen($v) < 1) continue;
			switch ($k){
				case 'roleid':
				case 'userid':
				case 'email':
					$where[] = "`{$k}` = '{$v}'";
					break;
				case 'username':
				case 'realname':
				case 'lastloginip':
					$where[] = "`{$k}` like '%{$v}%'";
					break;
				case 'lastlogintime.begin':
					if(!check_datetime($v)){
						unset($search[$k]);
						continue;
					}
					$v       = strtotime($v);
					$where[] = "`lastlogintime` >= '{$v}'";
					break;
				case 'lastlogintime.end':
					if(!check_datetime($v)){
						unset($search[$k]);
						continue;
					}
					$v       = strtotime($v);
					$where[] = "`lastlogintime` <= '{$v}'";
					break;
			}
		}
		$where = implode(' and ', $where);

		//角色列表
		$roleList     = M('admin_role')->getField('roleid,rolename,status', true);
		$combobox = array();
		foreach($roleList as $info){
			array_push($combobox, array(
				'value' => $info['roleid'],
				'text'  => $info['rolename'],
			));
		}

		$this->datagrid(array(
			'db'        => D('Admin'),
			'where'     => $where,
			'page'      => $page,
			'rows'      => $rows,
			'sort'      => $sort,
			'order'     => $order,
			'formatter' => function($key, &$val, $info) use ($roleList){
				switch($key){
					case 'lastloginip':
					case 'realname':
						$val = $val ? $val : '-';
						break;
					case 'lastlogintime':
						$val = $val ? date('Y-m-d H:i:s', $val) : '-';
						break;
					case 'roleid':
						$val = isset($roleList[$val]) ? ($roleList[$val]['status'] ? $roleList[$val]['rolename'] : '<font color="grey">' . $roleList[$val]['rolename'] . '[冻结]</font>') : '<font color="red">未设置角色</font>';
						break;
				}
				return $val;
			},
			'assign'    => array(
				'combobox' => $combobox
			),
		));
	}

	/**
	 * 用户添加
	 */
	public function userAdd(){
		if(IS_POST){
			$admin_db = D('Admin');
			$data     = I('post.info');
			if($admin_db->where(array('username'=>$data['username']))->count()){
				$this->error('用户名称已经存在');
			}

			//邮件模版
			$email_db = M('email');
			$email    = $email_db->field(array('subject', 'content'))->where(array('code'=>'user.useradd'))->find();
			if($email){
				$email = array_merge($email, array(
					'email'   => $data['email'],
					'content' => str_replace(array('{username}', '{password}', '{site}'), array($data['username'], $data['password'], SITE_URL), htmlspecialchars_decode($email['content']))
				));
			}

			$info             = password($data['password']);
			$data['password'] = $info['password'];
			$data['encrypt']  = $info['encrypt'];

			$id = $admin_db->add($data);
			if($id){
				if($email) send_email($email['email'], $email['subject'], $email['content'], array('isHtml'=>true, 'charset'=>'GB2312'));
				$this->success('添加成功');
			}else {
				$this->error('添加失败');
			}
		}else{
			$admin_role_db = D('AdminRole');
			$rolelist      = $admin_role_db->where(array('status'=>'1'))->order('listorder asc')->getField('roleid,rolename', true);
			$this->assign('rolelist', $rolelist);

			$this->display('user_add');
		}
	}

	/**
	 * 用户编辑
	 */
	public function userEdit(){
		$admin_db = D('Admin');
		if(IS_POST){
			$data = I('post.info');
			if($data['userid'] == '1' && $data['roleid'] != '1' ) $this->error('默认用户角色不能被修改');
			$res = $admin_db->save($data);
			$res ? $this->success('修改成功') : $this->error('修改失败');
		}else{
			$id   = I('get.id');
			$info = $admin_db->where(array('userid'=>$id))->find();
			$this->assign('info', $info);

			$admin_role_db = D('AdminRole');
			$rolelist      = $admin_role_db->where(array('status'=>'1'))->order('listorder asc')->getField('roleid,rolename', true);
			$this->assign('rolelist', $rolelist);

			$this->display('user_edit');
		}
	}

	/**
	 * 用户删除
	 */
	public function userDelete($ids = ''){
		if(IS_POST){
			$ids = explode(',', $ids);

			//检测
			foreach($ids as $id){
				if($id == '1') $this->error('系统默认用户不能被删除');
			}

			$admin_db = D('Admin');
			$res      = $admin_db->where(array('userid'=>array('in', $ids)))->save(array('status'=>0));
			$res ? $this->success('删除成功') : $this->error('删除失败');
		}
	}

	/**
	 * 用户密码重置
	 */
	public function userReset($id = 0){
		if(IS_POST){
			if($id == '1') $this->error('系统默认用户不能被重置');

			$admin_db = D('Admin');
			$password = rand(100000, 999999);
			$info     = password($password);
			$data     = array(
				'password' => $info['password'],
				'encrypt'  => $info['encrypt']
			);
			$result = $admin_db->where(array('userid'=>$id))->save($data);

			//邮件模版
			$email_db = M('email');
			$email    = $email_db->field(array('subject', 'content'))->where(array('code'=>'user.userreset'))->find();
			if($email){
				$userInfo = $admin_db->field('username,email')->where(array('userid'=>$id))->find();
				$email    = array_merge($email, array(
					'email' => $userInfo['email'],
					'content' => str_replace(array('{username}', '{password}', '{site}'), array($userInfo['username'], $password, SITE_URL), htmlspecialchars_decode($email['content']))
				));
			}

			if ($result){
				if($email) send_email($email['email'], $email['subject'], $email['content'], array('isHtml'=>true, 'charset'=>'GB2312'));
				$this->ajaxReturn(array('status'=>1, 'info'=>'重置成功', 'password'=>$password));
			}else {
				$this->error('重置失败');
			}
		}
	}

	/**
	 * 验证邮箱是否存在
	 */
	public function public_checkEmail($email = 0){
		if (I('get.default') == $email) {
			exit('true');
		}
		$admin_db = D('Admin');
		$exists   = $admin_db->where(array('email'=>$email))->field('email')->find();
		if ($exists) {
			exit('false');
		}else{
			exit('true');
		}
	}

	/**
	 * 验证用户名
	 */
	public function public_checkName($name){
		if (I('get.default') == $name) {
			exit('true');
		}
		$admin_db = D('Admin');
		$exists   = $admin_db->where(array('username'=>$name))->field('username')->find();
		if ($exists) {
			exit('false');
		}else{
			exit('true');
		}
	}
}