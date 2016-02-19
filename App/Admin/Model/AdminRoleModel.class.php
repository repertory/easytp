<?php
namespace Admin\Model;
use Think\Model;

class AdminRoleModel extends Model{
	protected $tableName = 'admin_role';
	protected $pk        = 'roleid';
	public    $error;
}