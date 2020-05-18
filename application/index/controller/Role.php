<?php

namespace app\index\controller;

use app\index\controller\Base;
use think\Request;
use app\index\model\Access;
use app\index\model\RoleAccess;
use app\index\model\Role as RoleModel;


class Role extends Base
{
    // 角色列表
    public function list()
    {
        // 确认权限
        $this->checkAccess();
        $this->assign('title', '角色列表');
        $count = RoleModel::count();
        $roleList = RoleModel::order('id', 'desc')->paginate(10);

        $this->assign('count', $count);
        $this->assign('roleList', $roleList);
        return $this->fetch('list');
    }

    public function add()
    {
        // 确认权限
        $this->checkAccess();
        $this->assign('title', '添加角色');

        return $this->fetch('add');
    }

    // 检测角色名是否可用
    public function checkRoleName(Request $request)
    {
        if (!$request->isAjax()) {
            return $this->error('非法请求');
        }
        $name = trim($request->post('name'));
        $status = 1;
        $message = '角色名可用';
        $role = RoleModel::get(['name' => $name]);
        // 如果在表中查询到该用户名
        if ($role == true) {
            $status = 0;
            $message = '角色名重复,请重新输入';
        }

        return ['status' => $status, 'message' => $message];
    }

    // 添加操作
    public function checkAdd(Request $request)
    {
        if (!$request->isAjax()) {
            return $this->error('非法请求');
        }
        $data = $request->post();
        $status = 0;
        $message = '添加失败';

        $role = RoleModel::create($data);
        if ($role == true) {
            $status = 1;
            $message = '添加成功';
        }

        return ['status' => $status, 'message' => $message];
    }

    // 角色状态变更
    public function setStatus(Request $request)
    {
        if (!$request->isAjax()) {
            return $this->error('非法请求');
        }
        $id = $request->param('id');
        $result = RoleModel::get($id);

        if ($result->getData('status') == 1) {
            RoleModel::update(['status' => 0], ['id' => $id]);
        } else {
            RoleModel::update(['status' => 1], ['id' => $id]);
        }
    }

    // 渲染编辑角色名界面
    public function edit(Request $request)
    {
        // 确认权限
        $this->checkAccess();
        $id = $request->param('id');
        $role = RoleModel::get($id);
        $accessList = Access::all();

        $this->assign('title', '编辑角色名');
        $this->assign('role', $role);
        $this->assign('accessList', $accessList);
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
        $data = $request->except('access_ids');
        $condition = ['id' => $data['id']];
        $role = RoleModel::update($data, $condition);

        if ($role == true) {
            $status = 1;
            $message = '更新成功';
        }

        return ['status' => $status, 'message' => $message];
    }

    // 渲染编辑角色名界面
    public function editAccess(Request $request)
    {
        // 确认权限
        $this->checkAccess();
        $id = $request->param('id');
        $role = RoleModel::get($id);
        $accessList = Access::all();
        $userAccessIds = RoleAccess::field('access_id')->where('role_id', $id)->select();

        // 循环遍历出所有的access_id
        $accessIds = [];
        if ($accessList) {
            foreach ($accessList as $item) {
                $accessIds[] = $item->id;
            }
        }

        // 获取当前角色所拥有的access_id
        $relatedAccessId = [];
        if ($userAccessIds) {
            foreach ($userAccessIds as $item) {
                $relatedAccessId[] = $item->access_id;
                if (!in_array($item->access_id, $accessIds)) {
                    $item->where('access_id', $item->access_id)->delete();
                }
            }
        }

        $this->assign('title', '编辑角色名');
        $this->assign('role', $role);
        $this->assign('accessList', $accessList);
        $this->assign('relatedAccessId', $relatedAccessId);
        return $this->fetch('editAccess');
    }

    // 更新数据操作
    public function checkEditAccess(Request $request)
    {
        if (!$request->isAjax()) {
            return $this->error('非法请求');
        }
        $id = $request->post('id');
        $accessIds = $request->post('access_ids/a') ?? [];

        $roleAccessIds = RoleAccess::field('access_id')->where('role_id', $id)->select();

        /**
         * 找出删除的权限
         * 假如已有的权限集合是A，界面传递过得权限集合是B
         * 权限集合A当中的某个权限不在权限集合B当中，就应该删除
         */
        $relatedAccessId = [];
        if ($roleAccessIds) {
            foreach ($roleAccessIds as $item) {
                $relatedAccessId[] = $item->access_id;
                if (!in_array($item->access_id, $accessIds)) {
                    $item->where('access_id',$item->access_id)->delete();
                }
            }
        }

        /**
         * 找出添加的权限
         * 假如已有的权限集合是A，界面传递过得权限集合是B
         * 权限集合B当中的某个权限不在权限集合A当中，就应该添加
         */
        if ($accessIds) {
            foreach ($accessIds as $item) {
                if (!in_array($item, $relatedAccessId)) {
                    $userRole = new RoleAccess();
                    $userRole->role_id = $id;
                    $userRole->access_id = $item;
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
        $id = $request->post('id');
        RoleModel::destroy($id);
    }
}
