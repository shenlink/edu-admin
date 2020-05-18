<?php

namespace app\index\controller;

use app\index\model\Teacher as TeacherModel;
use think\Request;
use app\index\model\Grade;

class Teacher extends Base
{
    // 教师列表
    public function  list()
    {
        // 确认权限
        $this->checkAccess();
        $teacherList = TeacherModel::order('id', 'desc')->paginate(10);
        $count = TeacherModel::count();
        // 遍历teacher表，把grade表的name赋值给teacher,若没有name，显示未分配
        foreach ($teacherList as $teacher) {
            if (isset($teacher->grade->name)) {
                $teacher->grade = $teacher->grade->name;
            } else {
                $teacher->grade = '<span style="color:red;">未分配</span>';
            }
        }

        $this->assign('teacherList', $teacherList);
        $this->assign('count', $count);
        $this->assign('title', '教师列表');

        return $this->fetch('list');
    }

    // 渲染教师添加界面
    public function add()
    {
        // 确认权限
        $this->checkAccess();
        $this->assign('title', '添加教师');
        $gradeList = Grade::order('id', 'desc')->select();

        // 将班级表中所有数据赋值给当前模板
        $this->assign('gradeList', $gradeList);
        return $this->fetch('add');
    }

    // 添加教师
    public function checkAdd(Request $request)
    {
        if (!$request->isAjax()) {
            return $this->error('非法请求');
        }
        $data = $request->param();
        $result = TeacherModel::create($data);
        $status = 0;
        $message = '添加失败,请检查';

        if ($result == true) {
            $status = 1;
            $message = '恭喜, 添加成功~~';
        }

        return ['status' => $status, 'message' => $message];
    }

    // 教师状态变更
    public function setStatus(Request $request)
    {
        if (!$request->isAjax()) {
            return $this->error('非法请求');
        }
        $id = $request->param('id');
        $result = TeacherModel::get($id);

        if ($result->getData('status') == 1) {
            TeacherModel::update(['status' => 0], ['id' => $id]);
        } else {
            TeacherModel::update(['status' => 1], ['id' => $id]);
        }
    }

    // 渲染教师编辑界面
    public function edit(Request $request)
    {
        // 确认权限
        $this->checkAccess();
        $id = $request->param('id');
        $teacher = TeacherModel::get($id);
        $gradeList = Grade::order('id', 'desc')->select();

        if (isset($teacher->grade->name)) {
            $teacher->grade =  $teacher->grade->name;
        } else {
            $teacher->grade = '未分配';
        }

        // 给当前教师编辑页面模板赋值
        $this->assign('title', '编辑教师信息');
        $this->assign('teacher', $teacher);
        $this->assign('gradeList', $gradeList);
        return $this->fetch('edit');
    }

    // 教师更新
    public function checkEdit(Request $request)
    {
        if (!$request->isAjax()) {
            return $this->error('非法请求');
        }
        $data = $request->except('grade');
        $condition = ['id' => $data['id']];
        $result = TeacherModel::update($data, $condition);
        $status = 0;
        $message = '更新失败,请检查';

        if ($result == true) {
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
        TeacherModel::update(['is_delete' => 1], ['id' => $id]);
        TeacherModel::destroy($id);
    }

    // 恢复删除操作
    public function unDelete(Request $request)
    {
        if (!$request->isAjax()) {
            return $this->error('非法请求');
        }
        TeacherModel::update(['delete_time' => NULL], ['is_delete' => 1]);
    }
}
