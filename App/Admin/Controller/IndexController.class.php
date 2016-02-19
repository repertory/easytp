<?php
namespace Admin\Controller;
use Admin\Controller\CommonController;

/**
 * 后台管理通用模块
 * @author wangdong
 */
class IndexController extends CommonController {
	/**
	 * 后台首页
	 */
	public function index(){
		$userInfo = user_info();
		$this->assign('userInfo', $userInfo);

		//头部菜单列表
		$menu_db  = D('Menu');
		$menuList = $menu_db->getMenu();
		$this->assign('menuList', $menuList);

		$this->display();
	}

	public function login(){
		if (IS_POST){
			$admin_db = D('Admin');

			$username = I('post.username', '', 'trim') ? I('post.username', '', 'trim') : $this->error('用户名不能为空！', HTTP_REFERER);
			$password = I('post.password', '', 'trim') ? I('post.password', '', 'trim') : $this->error('密码不能为空！', HTTP_REFERER);

			//验证码判断
			$code = I('post.code', '', 'trim') ? I('post.code', '', 'trim') : $this->error('请输入验证码！', HTTP_REFERER);
			if(!check_verify($code, 'admin')) $this->error('验证码不正确！', HTTP_REFERER);

			if($admin_db->login($username, $password)){
				$this->success('登录成功', U('Index/index'));
			}else{
				$this->error($admin_db->error, HTTP_REFERER);
			}
		}else {
			if(user_info()) $this->redirect('Index/index');
			$this->display();
		}
	}

	public function code($code = null){
		if(IS_POST){
			//ajax验证
			if (check_verify($code, 'admin', false)) {
				exit('true');
			}else{
				exit('false');
			}
		}else{
			$verify = new \Think\Verify();
			$verify->useCurve = true;
			$verify->useNoise = false;
			$verify->bg = array(255, 255, 255);

			if (I('get.code_len')) $verify->length = intval(I('get.code_len'));
			if ($verify->length > 8 || $verify->length < 2) $verify->length = 4;

			if (I('get.font_size')) $verify->fontSize = intval(I('get.font_size'));

			if (I('get.width')) $verify->imageW = intval(I('get.width'));
			if ($verify->imageW <= 0) $verify->imageW = 130;

			if (I('get.height')) $verify->imageH = intval(I('get.height'));
			if ($verify->imageH <= 0) $verify->imageH = 50;

			$verify->entry('admin');
		}
	}

	public function logout(){
		user_info(null);
		$this->success('退出成功！', U('Index/login'));
	}

	public function public_welcome(){
		$userid    = user_info('userid');
		$admin_log = M('admin_log');
		$loginList = $admin_log->where(array('userid'=>$userid))->order("time desc")->limit(5)->select();
		$this->assign('loginList', $loginList);

		$this->display('welcome');
	}

	/**
	 * 左侧菜单
	 */
	public function public_left($menuid = 0) {
		if(IS_POST) {
			$menu_db = D('Menu');
			$data    = array();
			$list    = $menu_db->getMenu($menuid);

			$dict    = dict('', 'Left');

			foreach ($list as $k => $v) {
				$data[$k]         = array();
				$data[$k]['name'] = $v['name'];
				$data[$k]['icon'] = menu_icon($v['level'], $v['icon']);

				$key = 0;
				if(isset($dict[$v['id']])) $key = $v['id'];

				$data[$k]['href'] = U($dict[$key]['href'], array('id' => $v['id']));
			}

			$this->ajaxReturn($data);
		}
	}

	/**
	 * 左侧通用菜单
	 */
	public function public_leftDefault($id = 0) {
		if(IS_GET){
			$menu_db = D('Menu');
			$data    = array();
			$menu    = $menu_db->getMenu($id);

			foreach ($menu as $v){
				array_push($data, array(
					'text'    => $v['name'],
					'id'      => $v['id'],
					'url'     => U($v['c'].'/'.$v['a'].'?menuid='.$v['id'].'&'.$v['data']),
					'iconCls' => menu_icon($v['level'], $v['icon']),
					'open'    => $v['open'],
				));
			}

			$this->assign('data', $data);
			$this->display('left_default');
		}
	}

	/**
	 * 左侧扩展菜单
	 */
	public function public_leftExtend($id = 0) {
		if(IS_GET){
			$addons_db = D('Addons');
			$data      = array();
			$menu      = $addons_db->where(array('status'=>1, 'show'=>1))->select();

			foreach ($menu as $v){
				array_push($data, array(
					'text'    => $v['title'],
					'id'      => $v['id'],
					'url'     => U('Extend/load', array('name'=>$v['name'], 'id'=>$v['id'], 'menuid'=>$id)),
					'iconCls' => menu_icon(0, $v['icon']),
				));
			}

			$this->assign('data', $data);
			$this->display('left_extend');
		}
	}

	/**
	 * 左侧内容管理列表
	 */
	public function public_leftContent($id = 0) {
		if(IS_GET){
			$category_db = D('Category');
			$data        = $category_db->getTree(0, $id);

			$this->assign('data', $data);
			$this->display('left_content');
		}
	}



	/**
	 * 个人信息
	 */
	public function public_userInfo($info = array()){
		$userid   = user_info('userid');
		$admin_db = D('Admin');

		if (IS_POST){
			$fields = array('email','realname');
			foreach ($info as $k=>$value) {
				if (!in_array($k, $fields)){
					unset($info[$k]);
				}
			}
			$state = $admin_db->where(array('userid'=>$userid))->save($info);
			if($state){
				$userInfo = user_info();
				$userInfo = array_merge($userInfo, $info);
				user_info('', $userInfo);
			}
			$state ? $this->success('修改成功') : $this->error('修改失败');
		}else {
			$info = $admin_db->where(array('userid'=>$userid))->find();
			$this->assign('info', $info);

			$this->display('user_info');
		}
	}

	/**
	 * 修改密码
	 */
	public function public_userPwd($info = array()){
		$userid   = user_info('userid');
		$admin_db = D('Admin');

		if (IS_POST){
			$info = $admin_db->where(array('userid'=>$userid))->field('password,encrypt')->find();
			if(password(I('post.old_password'), $info['encrypt']) !== $info['password'] ) $this->error('旧密码输入错误');
			if(I('post.new_password')) {
				$state = $admin_db->editPassword($userid, I('post.new_password'));
				if(!$state) $this->error('密码修改失败');
			}
			$this->success('修改成功', U('Index/logout'));
		}else {
			$info = $admin_db->where(array('userid'=>$userid))->find();
			$this->assign('info', $info);

			$this->display('user_pwd');
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
	 * 验证密码
	 */
	public function public_checkPassword($password = 0){
		$userid   = user_info('userid');
		$admin_db = D('Admin');
		$info     = $admin_db->where(array('userid'=>$userid))->field('password,encrypt')->find();
		if (password($password, $info['encrypt']) == $info['password'] ) {
			exit('true');
		}else {
			exit('false');
		}
	}

	/**
	 * 防止登录超时
	 */
	public function public_sessionLife(){
		$userInfo = user_info();
		//单设备登录判断
		if(C('LOGIN_ONLY_ONE')){
			$loginInfo = S('USER_LOGIN_INFO_' . $userInfo['userid']);

			if(session_id() != $loginInfo['sessid']){
				$this->error("帐号已在其他设备登录(ip:{$loginInfo['ip']})，您已被迫下线！", U('Index/logout'));
			}
			
			if(get_client_ip(0, true) != $loginInfo['ip']){
				$this->error("帐号已在其他地方登录(ip:{$loginInfo['ip']})，您已被迫下线！", U('Index/logout'));
			}
		}
		//防止cookie超时
		$identity = cookie('identity');
		cookie('identity', $identity);
		$this->success('正常登录');
	}

	/**
	 * 更新缓存
	 */
	public function public_clearCatche(){
		$list = dict('', 'Cache');
		if(is_array($list) && !empty($list)){
			foreach ($list as $modelName=>$funcName){
				D($modelName)->$funcName();
				$this->show("更新模块：{$modelName} ...... 成功 <br/>");
			}
		}
		$this->show("缓存更新完毕");
	}

	/**
	 * 系统信息
	 */
	public function public_sysInfo(){
		$sysinfo   = \Admin\Plugin\SysinfoPlugin::getinfo();
		$os        = explode(' ', php_uname());
		$net_state = null; //网络使用状况

		if ($sysinfo['sysReShow'] == 'show' && false !== ($strs = @file("/proc/net/dev"))) {
			for ($i = 2; $i < count($strs); $i++) {
				preg_match_all("/([^\s]+):[\s]{0,}(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/", $strs[$i], $info);
				$net_state.="{$info[1][0]} : 已接收 : <font color=\"#CC0000\"><span id=\"NetInput{$i}\">" . $sysinfo['NetInput' . $i] . "</span></font> GB &nbsp;&nbsp;&nbsp;&nbsp;已发送 : <font color=\"#CC0000\"><span id=\"NetOut{$i}\">" . $sysinfo['NetOut' . $i] . "</span></font> GB <br />";
			}
		}

		$this->assign('sysinfo', $sysinfo);
		$this->assign('os', $os);
		$this->assign('net_state', $net_state);
		$this->display("systeminfo");
	}

	/**
	 * 反馈
	 */
	public function public_feedback(){
		$this->display('feedback');
	}

	/**
	 * 关于
	 */
	public function public_about(){
		$this->display('about');
	}
}