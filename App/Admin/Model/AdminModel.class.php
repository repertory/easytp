<?php
namespace Admin\Model;
use Think\Model;

class AdminModel extends Model{
	protected $tableName = 'admin';
	protected $pk        = 'userid';
	public    $error;
	
	/**
	 * 登录验证
	 */
	public function login($username, $password){
		$times_db = M('times');

		//查询帐号
		$info = $this->where(array('username'=>$username, 'status'=>1))->find();
		if(!$info){
			$this->error = '用户不存在！';
			return false;
		}
		
		//密码错误剩余重试次数
		$rtime = $times_db->where(array('username'=>$username, 'type'=>'0'))->find();
		if($rtime['times'] >= C('MAX_LOGIN_TIMES')) {
			$minute = C('LOGIN_WAIT_TIME') - floor((time()-$rtime['time'])/60);
			if ($minute > 0) {
				$this->error = "密码重试次数太多，请过{$minute}分钟后重新登录！";
				return false;
			}else {
				$times_db->where(array('username'=>$username, 'type'=>'0'))->delete();
			}
		}

		$password = md5(md5($password).$info['encrypt']);
		$ip       = get_client_ip(0, true);

		if($info['password'] != $password) {
			if($rtime && $rtime['times'] < C('MAX_LOGIN_TIMES')) {
				$times = C('MAX_LOGIN_TIMES') - intval($rtime['times']);
				$times_db->where(array('username'=>$username, 'type'=>'0'))->save(array('ip'=>$ip));
				$times_db->where(array('username'=>$username, 'type'=>'0'))->setInc('times');
			} else {
				$times_db->where(array('username'=>$username,'type'=>'0'))->delete();
				$times_db->add(array('username'=>$username,'ip'=>$ip,'type'=>'0','time'=>time(),'times'=>1));
				$times = C('MAX_LOGIN_TIMES');
			}
			$this->error = "密码错误，您还有{$times}次尝试机会！";
			return false;
		}
		
		$times_db->where(array('username'=>$username, 'type'=>'0'))->delete();
		$this->where(array('userid'=>$info['userid']))->save(array('lastloginip'=>$ip,'lastlogintime'=>time()));
		
		//登录日志
		$admin_log_db = M('admin_log');
		$admin_log_db->add(array(
			'userid'        => $info['userid'],
			'username'      => $username,
			'httpuseragent' => $_SERVER['HTTP_USER_AGENT'],
			'ip'            => $ip,
			'time'          => date('Y-m-d H:i:s'),
			'type'          => 'login',
			'sessionid'     => session_id(),
		));

		$admin_role_db = D('AdminRole');
		$roleInfo = $admin_role_db->field(array('rolename','roleid'))->where(array('roleid'=>$info['roleid'], 'status'=>1))->find();
		if(!$roleInfo){
			$this->error = '用户已被冻结！';
			return false;
		}
		$info['rolename'] = $roleInfo['rolename'];
		user_info('', $info);

		//登录信息更新
		S('USER_LOGIN_INFO_' . $info['userid'], array(
			'sessid'    => session_id(),
			'time'      => date('Y-m-d H:i:s'),
			'useragent' => $_SERVER['HTTP_USER_AGENT'],
			'ip'        => $ip,
			'identity'  => cookie('identity'),
		));
		return true;
	}
	
	/**
	 * 修改密码
	 */
	public function editPassword($userid, $password){
		$userid = intval($userid);
		if($userid < 1) return false;
		$passwordinfo = password($password);
		return $this->where(array('userid'=>$userid))->save($passwordinfo);
	}
}