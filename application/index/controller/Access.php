<?php

namespace app\index\controller;

use app\index\controller\Base;
use think\Request;
use app\index\model\Access as AccessModel;

class Access extends Base
{
    // 权限列表
    public function list()
    {
        // 确认权限
        $this->checkAccess();
        $this->assign('title', '权限列表');
        // 计数
        $count = AccessModel::count();
        $accessList = AccessModel::order('id', 'desc')->paginate(10);

        $this->assign('count', $count);
        $this->assign('accessList', $accessList);
        return $this->fetch('list');
    }

    // 添加权限页面
    public function add()
    {
        // 确认权限
        $this->checkAccess();
        $this->assign('title', '添加权限');
        return $this->fetch('add');
    }

    //  检测权限名是否可用
    public function checkAccessName(Request $request)
    {
        if (!$request->isAjax()) {
            return $this->error('非法请求');
        }
        $name = trim($request->post('name'));
        $status = 1;
        $message = '权限名可用';
        //  如果在表中查询到该用户名
        if (AccessModel::get(['name' => $name])) {
            $status = 0;
            $message = '权限名重复,请重新输入';
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

        $user = AccessModel::create($data);
        if ($user == true) {
            $status = 1;
            $message = '添加成功';
        }

        return ['status' => $status, 'message' => $message];
    }

    //  权限状态变更
    public function setStatus(Request $request)
    {
        if (!$request->isAjax()) {
            return $this->error('非法请求');
        }
        $id = $request->param('id');
        $result = AccessModel::get($id);
        if ($result->getData('status') == 1) {
            AccessModel::update(['status' => 0], ['id' => $id]);
        } else {
            AccessModel::update(['status' => 1], ['id' => $id]);
        }
    }

    // 渲染编辑权限界面
    public function edit(Request $request)
    {
        // 确认权限
        $this->checkAccess();
        $id = $request->param('id');
        $access = AccessModel::get($id);
        $this->assign('title', '编辑权限信息');
        $this->assign('access', $access);
        return $this->fetch('edit');
    }

    // 更新数据操作
    public function checkEdit(Request $request)
    {
        if (!$request->isAjax()) {
            return $this->error('非法请求');
        }
        $data = $request->post();

        $condition = ['id' => $data['id']];
        $result = AccessModel::update($data, $condition);
        if ($result == true) {
            return ['status' => 1, 'message' => '更新成功'];
        } else {
            return ['status' => 0, 'message' => '更新失败'];
        }
    }

    // 删除操作
    public function delete(Request $request)
    {
        if (!$request->isAjax()) {
            return $this->error('非法请求');
        }
        $id = $request->post('id');
        AccessModel::destroy($id);
    }
}
