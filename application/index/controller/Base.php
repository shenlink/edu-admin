<?php

namespace app\index\controller;

use think\Controller;
use think\Session;
use app\index\model\User;
use app\index\model\Access;
use app\index\model\UserRole;
use app\index\model\RoleAccess;

class Base extends Controller
{
    public $privilegeUrls = [];

    // 重写初始化方法
    protected function _initialize()
    {
        parent::_initialize();
        define('USER_ID', Session::has('user_id') ? Session::get('user_id') : null);
    }

    // 判断是否登录
    protected function isLogin()
    {
        if (is_null(USER_ID)) {
            return $this->error('用户未登录', 'user/login');
        }
    }

    // 判断是否已经登录
    protected function alreadyLogin()
    {
        if (USER_ID) {
            return $this->error('用户已经登陆,请勿重复登陆', 'index/index');
        }
    }

    // 获取当前URL
    public function getUrl()
    {
        $url = '/' . request()->controller() . '/' . request()->action();
        $url = strtolower($url);
        return $url;
    }

    // 确认权限
    public function checkAccess()
    {
        if (!$this->checkPrivilege()) {
            return $this->error('没有权限');
        }
    }

    // 检查是否有访问指定链接的权限
    public function checkPrivilege()
    {
        $url = $this->getUrl();
        $user_id = Session::get('user_id');
        $isAdmin = User::where('id', $user_id)->value('is_admin');
        //如果是超级管理员 也不需要权限判断
        if ($isAdmin) {
            return true;
        }

        $result = in_array($url, $this->getRolePrivilege($user_id));
        return $result;
    }

    /*
	* 获取某用户的所有权限
	* 取出指定用户的所属角色，
	* 在通过角色 取出 所属 权限关系
	* 在权限表中取出所有的权限链接
	*/
    public function getRolePrivilege($user_id = 0)
    {
        $roleIds = UserRole::field('role_id')->where(['user_id' => $user_id])->select();

        if ($roleIds) {
            $accessIds = [];
            foreach ($roleIds as $roleId) {
                //在通过角色 取出 所属 权限关系
                $temp = RoleAccess::field('access_id')->where('role_id',  $roleId->role_id)->select();
                $accessIds = array_merge($temp, $accessIds);
            }

            // 在权限表中取出所有的权限链接
            $urls = [];
            foreach ($accessIds as $accessId) {
                $temp = Access::field('urls')->where('id', $accessId->access_id)->select();
                $urls = array_merge($temp, $urls);
            }

            if ($urls) {
                foreach ($urls as $url) {
                    $this->privilegeUrls[] = $url->urls;
                }
            }
        }

        return $this->privilegeUrls;
    }
}
