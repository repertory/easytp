<?php
namespace Admin\Controller;
use Admin\Controller\CommonController;

/**
 * 会员模块
 * @author wangdong
 */
class MemberController extends CommonController {
	//////////////////////////////////////////// 类型管理 ////////////////////////////////////////////

	/**
	 * 类型列表
	 */
	public function type($search = array(), $page = 1, $rows = 10, $sort = 'listorder', $order = 'asc'){
		//搜索
		$where = array();

		foreach ($search as $k=>$v){
			if(strlen($v) < 1) continue;
			switch ($k){
				case 'typeid':
				case 'status':
					$where[] = "`{$k}` = '{$v}'";
					break;
				case 'typename':
					$where[] = "`{$k}` like '%{$v}%'";
					break;
			}
		}
		$where = implode(' and ', $where);

		$this->datagrid(array(
			'db'        => M('member_type'),
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
			},
		));
	}

	/**
	 * 类型添加
	 */
	public function typeAdd(){
		if(IS_POST){
			$member_type_db = M('member_type');
			$data           = I('post.info');
			if($member_type_db->where(array('typename'=>$data['typename']))->count()){
				$this->error('类型名称已存在');
			}

			$res = $member_type_db->add($data);
			$res ? $this->success('添加成功') : $this->error('添加失败');
		}else{
			$this->display('type_add');
		}
	}

	/**
	 * 类型编辑
	 */
	public function typeEdit(){
		$member_type_db = M('member_type');
		if(IS_POST){
			$data = I('post.info');
			$res  = $member_type_db->save($data);
			$res ? $this->success('修改成功') : $this->error('修改失败');
		}else{
			$typeid = I('get.id');
			$info   = $member_type_db->where(array('typeid'=>$typeid))->find();

			$this->assign('info', $info);
			$this->display('type_edit');
		}
	}

	/**
	 * 类型删除
	 */
	public function typeDelete($ids = ''){
		if(IS_POST){
			$member_type_db = M('member_type');
			$member_db      = M('member');
			$idList        = explode(',', $ids);

			//检测
			foreach($idList as $id){
				$count = $member_db->where(array('typeid'=>$id))->count();
				if($count) $this->error("类型ID为{$id}下面仍有 <b>{$count}</b> 个会员");
			}

			$result = $member_type_db->where("typeid in ({$ids})")->delete();

			if ($result){
				$this->success('删除成功');
			}else {
				$this->error('删除失败');
			}
		}
	}

	/**
	 * 验证分类名称是否存在
	 */
	public function public_checkTypeName($typename){
		if (I('get.default') == $typename) {
			exit('true');
		}
		$member_type_db = M('member_type');
		$exists         = $member_type_db->where(array('typename'=>$typename))->count();
		if ($exists) {
			exit('false');
		}else{
			exit('true');
		}
	}



	//////////////////////////////////////////// 会员管理 ////////////////////////////////////////////

	/**
	 * 会员列表
	 */
	public function user($search = array(), $page = 1, $rows = 10, $sort = 'lastlogintime', $order = 'desc'){
		//搜索
		$where = array();

		foreach ($search as $k=>$v){
			if(strlen($v) < 1) continue;
			switch ($k){
				case 'memberid':
				case 'gender':
				case 'constellation':
				case 'status':
					$where[] = "`{$k}` = '{$v}'";
					break;
				case 'username':
				case 'nick':
				case 'mobile':
					$where[] = "`{$k}` like '%{$v}%'";
					break;
				case 'regtime.begin':
					if(!check_datetime($v)){
						unset($search[$k]);
						continue;
					}
					$v       = strtotime($v);
					$where[] = "`regtime` >= '{$v}'";
					break;
				case 'regtime.end':
					if(!check_datetime($v)){
						unset($search[$k]);
						continue;
					}
					$v       = strtotime($v);
					$where[] = "`regtime` <= '{$v}'";
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
		$typeList = M('member_type')->getField('typeid,typename,status', true);
		$combobox = array();
		foreach($typeList as $info){
			array_push($combobox, array(
				'value' => $info['typeid'],
				'text'  => $info['typename'],
			));
		}

		$dict         = dict('', 'Member');
		$dictCombobox = array('gender'=>array(), 'constellation'=>array());
		foreach($dict['gender'] as $key=>$val){
			array_push($dictCombobox['gender'], array(
				'text'  => $val,
				'value' => $key,
			));
		}
		foreach($dict['constellation'] as $key=>$val){
			array_push($dictCombobox['constellation'], array(
				'text'  => $val,
				'value' => $key,
			));
		}

		$this->datagrid(array(
			'db'        => M('member'),
			'where'     => $where,
			'page'      => $page,
			'rows'      => $rows,
			'sort'      => $sort,
			'order'     => $order,
			'formatter' => function($key, &$val, $info) use ($typeList, $dict){
				switch($key){
					case 'head':
						$val = '<img class="easytp-layer" src="' . member_head($info['head']) . '" height="50"/>';
						break;
					case 'regtime':
					case 'lastlogintime':
						$val = $val ? date('Y-m-d H:i:s', $val) : '-';
						break;
					case 'lastloginip':
						$val = $val ? $val : '-';
						break;
					case 'typeid':
						$val = isset($typeList[$val]) ? ($typeList[$val]['status'] ? $typeList[$val]['typename'] : '<font color="grey">' . $typeList[$val]['typename'] . '[冻结]</font>') : '<font color="red">未设置类型</font>';
						break;
					case 'status':
						$val = $val ? '已认证' : '<font color="red">未认证</font>';
						break;
					case 'gender':
					case 'constellation':
						$val = isset($dict[$key][$val]) ? $dict[$key][$val] : '-';
						break;
				}
				return $val;
			},
			'assign'    => array(
				'combobox' => $combobox,
				'dict'     => $dictCombobox,
			),
		));
	}

	/**
	 * 用户添加
	 */
	public function userAdd(){
		if(IS_POST){
			$member_db        = M('member');
			$data             = I('post.info');

			$info             = password($data['password']);
			$data['password'] = $info['password'];
			$data['encrypt']  = $info['encrypt'];
			$data['username'] = member_username();
			$data['regtime']  = time();
			$data['regip']    = get_client_ip(false, true);

			//验证用户名
			if($member_db->where(array('username'=>$data['username']))->count()){
				$this->error('用户名称已经存在');
			}

			$res = $member_db->add($data);
			$res ? $this->success('添加成功') : $this->error('添加失败');
		}else{
			$member_type_db = M('member_type');
			$typelist       = $member_type_db->where(array('status'=>'1'))->order('listorder asc')->getField('typeid,typename', true);
			$this->assign('typelist', $typelist);

			$dict = dict('', 'Member');
			$this->assign('dict', $dict);

			$this->display('user_add');
		}
	}

	/**
	 * 用户编辑
	 */
	public function userEdit(){
		$member_db = M('member');
		if(IS_POST){
			$data = I('post.info');

			if(isset($data['password'])) unset($data['password']);

			$res  = $member_db->save($data);
			$res ? $this->success('修改成功') : $this->error('修改失败');
		}else{
			$id   = I('get.id');
			$info = $member_db->where(array('memberid'=>$id))->find();
			$this->assign('info', $info);

			$member_type_db = M('member_type');
			$typelist       = $member_type_db->where(array('status'=>'1'))->order('listorder asc')->getField('typeid,typename', true);
			$this->assign('typelist', $typelist);

			$dict = dict('', 'Member');
			$this->assign('dict', $dict);

			$this->display('user_edit');
		}
	}

	/**
	 * 用户删除
	 */
	public function userDelete($ids = ''){
		if(IS_POST){
			$ids       = explode(',', $ids);
			$member_db = M('member');
			$res       = $member_db->where(array('memberid'=>array('in', $ids)))->delete();
			$res ? $this->success('删除成功') : $this->error('删除失败');
		}
	}

	/**
	 * 用户密码重置
	 */
	public function userReset($id = 0){
		if(IS_POST){
			$member_db = M('member');
			$password  = rand(100000, 999999);
			$info      = password($password);
			$data      = array(
				'password' => $info['password'],
				'encrypt'  => $info['encrypt']
			);
			$res = $member_db->where(array('memberid'=>$id))->save($data);

			$res ? $this->ajaxReturn(array('status'=>1, 'info'=>'重置成功', 'password'=>$password)) : $this->error('重置失败');
		}
	}

	/**
	 * 验证手机号
	 */
	public function public_checkMobile($mobile){
		if (I('get.default') == $mobile) {
			exit('true');
		}
		$member_db = M('member');
		$exists    = $member_db->where(array('mobile'=>$mobile))->count();
		if ($exists) {
			exit('false');
		}else{
			exit('true');
		}
	}

	/**
	 * 验证昵称
	 */
	public function public_checkNick($nick){
		if (I('get.default') == $nick) {
			exit('true');
		}
		$member_db = M('member');
		$exists    = $member_db->where(array('nick'=>$nick))->count();
		if ($exists) {
			exit('false');
		}else{
			exit('true');
		}
	}
}