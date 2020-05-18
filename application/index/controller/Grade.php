<?php

namespace app\index\controller;

use app\index\model\Grade as GradeModel;
use think\Request;

class Grade extends Base
{
    // 班级列表
    public function  list()
    {
        // 确认权限
        $this->checkAccess();
        $gradeList = GradeModel::order('id', 'desc')->paginate(10);
        $count = GradeModel::count();

        // 遍历grade表，把教师的名字赋值给grade的teacher
        foreach ($gradeList as $grade) {
            if (isset($grade->teacher->name)) {
                $grade->teacher = $grade->teacher->name;
            } else {
                $grade->teacher = '<span style="color:red;">未分配</span>';
            }
        }

        $this->assign('gradeList', $gradeList);
        $this->assign('count', $count);
        return $this->fetch('list');
    }

    // 渲染班级添加界面
    public function add()
    {
        // 确认权限
        $this->checkAccess();
        $this->assign('title', '编辑班级');

        // 渲染添加模板
        return $this->fetch('add');
    }

    // 添加班级
    public function checkAdd(Request $request)
    {
        if (!$request->isAjax()) {
            return $this->error('非法请求');
        }
        $data = $request->post();
        $result = GradeModel::create($data);
        // 设置返回数据的初始值
        $status = 0;
        $message = '添加失败,请检查';
        // 检测更新结果,将结果返回给grade_edit模板中的ajax提交回调处理
        if (true == $result) {
            $status = 1;
            $message = '恭喜, 添加成功~~';
        }
        return ['status' => $status, 'message' => $message];
    }

    // 班级状态变更
    public function setStatus(Request $request)
    {
        if (!$request->isAjax()) {
            return $this->error('非法请求');
        }
        $id = $request->param('id');
        $grade = GradeModel::get($id);
        // 启用和禁用处理
        if ($grade->getData('status') == 1) {
            GradeModel::update(['status' => 0], ['id' => $id]);
        } else {
            GradeModel::update(['status' => 1], ['id' => $id]);
        }
    }

    // 渲染班级编辑界面
    public function edit(Request $request)
    {
        $this->checkAccess();
        $id = $request->param('id');
        $grade = GradeModel::get($id);
        // 关联查询,获取与当前班级对应的教师姓名
        if (isset($grade->teacher->name)) {
            $grade->teacher = $grade->teacher->name;
        } else {
            $grade->teacher = '未分配';
        }

        $this->assign('title', '编辑班级');
        $this->assign('grade', $grade);

        return $this->fetch('edit');
    }

    // 班级更新
    public function checkEdit(Request $request)
    {
        if (!$request->isAjax()) {
            return $this->error('非法请求');
        }
        // 从提交表单中排除关联字段teacher字段
        $data = $request->except('teacher');
        // 设置更新条件
        $condition = ['id' => $data['id']];
        $result = GradeModel::update($data, $condition);
        // 设置返回数据
        $status = 0;
        $message = '更新失败,请检查';
        
        if (true == $result) {
            $status = 1;
            $message = '恭喜, 更新成功~~';
        }

        return ['status' => $status, 'message' => $message];
    }

    // 软删除操作
    public function delete(Request $request)
    {
        if (!$request->isAjax()) {
            return $this->error('非法请求');
        }
        $id = $request->param('id');
        GradeModel::update(['is_delete' => 1], ['id' => $id]);
        GradeModel::destroy($id);
    }

    // 恢复删除操作
    public function unDelete(Request $request)
    {
        if (!$request->isAjax()) {
            return $this->error('非法请求');
        }
        GradeModel::update(['delete_time' => NULL], ['is_delete' => 1]);
    }
}
