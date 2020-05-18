<?php

namespace app\index\controller;

use think\Request;
use app\index\model\User as UserModel;
use app\index\model\Role;
use app\index\model\UserRole;
use think\Session;

class User extends Base
{
    //登录界面
    public function login()
    {
        // 确认是否已经登录
        $this->alreadyLogin();
        $this->assign('title', '用户登录');
        return $this->fetch();
    }

    //登录验证
    public function checkLogin(Request $request)
    {
        if (!$request->isAjax()) {
            return $this->error('非法请求');
        }
        $status = 0;
        $result = '验证失败';
        $data = $request->post();
        //验证规则
        $rule = [
            'username|姓名' => 'require',
            'password|密码' => 'require',
            'captcha|验证码' => 'require|captcha'
        ];
        $result = $this->validate($data, $rule);
        //通过验证后,进行数据表查询
        if ($result === true) {
            $condition = [
                'username' => $data['username'],
                'password' => md5($data['password'])
            ];
            $user = UserModel::get($condition);
            if ($user == null) {
                $result = '用户名或密码错误';
            } else {
                $status = 1;
                $result = '登录成功';
                //创建2个session,用来检测用户登录状态和防止重复登录
                Session::set('user_id', $user->id);
                Session::set('user_info', $user->getData());
                //更新用户登录次数:自增1
                $user->setInc('login_count');
            }
        }
        return ['status' => $status, 'message' => $result, 'data' => $data];
    }

    //退出登录
    public function logout()
    {
        //退出前先更新登录时间字段
        UserModel::update(['login_time' => time()], ['id' => Session::get('user_id')]);
        Session::delete('user_id');
        Session::delete('user_info');
        $this->success('退出登录,正在返回', url('user/login'));
    }

    // 用户列表
    public function list()
    {
        // 确认权限
        $this->checkAccess();
        $this->assign('title', '用户列表');
        $userList = UserModel::order('id', 'desc')->paginate(10);
        $count = UserModel::count();

        $this->assign('count', $count);
        $this->assign('userList', $userList);
        return $this->fetch('list');
    }

    // 添加操作的界面
    public function add()
    {
        // 确认权限
        $this->checkAccess();
        $this->assign('title', '添加用户');

        return $this->fetch('add');
    }

    // 检测用户名是否可用
    public function checkUsername(Request $request)
    {
        if (!$request->isAjax()) {
            return $this->error('非法请求');
        }
        $username = trim($request->param('username'));
        $status = 1;
        $message = '用户名可用';
        //如果在表中查询到该用户名
        if (UserModel::get(['username' => $username])) {
            $status = 0;
            $message = '用户名重复,请重新输入';
        }
        return ['status' => $status, 'message' => $message];
    }

    // 添加操作
    public function checkAdd(Request $request)
    {
        if (!$request->isAjax()) {
            return $this->error('非法请求');
        }
        $data = $request->except('role_ids');
        $status = 0;
        $message = '添加失败';
        $rule = [
            'username|用户名' => "require|min:3|max:10",
            'password|密码' => "require|min:3|max:10",
        ];
        $result = $this->validate($data, $rule);
        if ($result === true) {
            $user = UserModel::create($data);
            if ($user == true) {
                $status = 1;
                $message = '添加成功';
            }
        }
        return ['status' => $status, 'message' => $message];
    }

    // 管理员状态变更
    public function setStatus(Request $request)
    {
        if (!$request->isAjax()) {
            return $this->error('非法请求');
        }
        $user_id = $request->param('id');
        $result = UserModel::get($user_id);
        if ($result->getData('status') == 1) {
            UserModel::update(['status' => 0], ['id' => $user_id]);
        } else {
            UserModel::update(['status' => 1], ['id' => $user_id]);
        }
    }

    // 渲染编辑管理员界面
    public function edit(Request $request)
    {
        $this->checkAccess();
        $id = $request->param('id');
        $user = UserModel::get($id);
        $this->assign('title', '编辑用户信息');
        $this->assign('user', $user);

        return $this->fetch('edit');
    }

    // 更新数据操作
    public function checkEdit(Request $request)
    {
        if (!$request->isAjax()) {
            return $this->error('非法请求');
        }
        $status = 0;
        $message = '更新失败';
        $data = $request->post();
        $condition = ['id' => $data['id']];
        $user = UserModel::update($data, $condition);

        if ($user == true) {
            $status = 1;
            $message = '更新成功';
        }
        return ['status' => $status, 'message' => $message];
    }

    // 渲染编辑用户界面
    public function editRole(Request $request)
    {
        // 确认权限
        $this->checkAccess();
        $id = $request->param('id');
        $user = UserModel::get($id);
        $roleList = Role::all();
        $userRoleIds = UserRole::field('role_id')->where('user_id', $id)->select();

        // 循环遍历出所有的role_id
        $roleIds = [];
        if ($roleList) {
            foreach ($roleList as $item) {
                $roleIds[] = $item->id;
            }
        }

        // 获取当前用户所拥有的role_id
        $relatedRoleId = [];
        if ($userRoleIds) {
            foreach ($userRoleIds as $item) {
                $relatedRoleId[] = $item->role_id;
                if (!in_array($item->role_id, $roleIds)) {
                    $item->where('role_id', $item->role_id)->delete();
                }
            }
        }

        $this->assign('roleList', $roleList);
        $this->assign('relatedRoleId', $relatedRoleId);
        $this->assign('title', '编辑用户角色信息');
        $this->assign('user', $user);

        return $this->fetch('editRole');
    }

    // 更新数据操作
    public function checkEditRole(Request $request)
    {
        if (!$request->isAjax()) {
            return $this->error('非法请求');
        }
        $id = $request->post('id');
        $roleIds = $request->post('role_ids/a') ?? [];

        $userRoleIds = UserRole::field('role_id')->where('user_id', $id)->select();

        /**
         * 找出要删除的角色
         * 假如已有的角色集合是A，前端传来的角色集合是B
         * 角色集合A当中的某个角色不在角色集合B当中，就应该删除
         */
        $relatedRoleId = [];
        if ($userRoleIds) {
            foreach ($userRoleIds as $item) {
                $relatedRoleId[] = $item->role_id;
                if (!in_array($item->role_id, $roleIds)) {
                    $item->where('role_id', $item->role_id)->delete();
                }
            }
        }

        /**
         * 找出要添加的角色
         * 假如已有的角色集合是A，前端传过来的角色集合是B
         * 角色集合B当中的某个角色不在角色集合A当中，就应该添加
         */
        if ($roleIds) {
            foreach ($roleIds as $item) {
                if (!in_array($item, $relatedRoleId)) {
                    $userRole = new UserRole();
                    $userRole->user_id = $id;
                    $userRole->role_id = $item;
                    $userRole->save();
                }
            }
        }

        $status = 1;
        $message = '编辑成功';

        return ['status' => $status, 'message' => $message];
    }

    // 删除操作
    public function delete(Request $request)
    {
        if (!$request->isAjax()) {
            return $this->error('非法请求');
        }
        $id = $request->param('id');
        UserModel::update(['is_delete' => 1], ['id' => $id]);
        UserModel::destroy($id);
    }

    // 恢复删除操作
    public function unDelete(Request $request)
    {
        if (!$request->isAjax()) {
            return $this->error('非法请求');
        }
        UserModel::update(['delete_time' => NULL], ['is_delete' => 1]);
    }
}
