<?php

namespace app\index\controller;

use app\index\model\Student as StudentModel;
use think\Request;
use app\index\model\Grade;

class Student extends Base
{
    //渲染学生信息列表
    public function  list()
    {
        // 确认权限
        $this->checkAccess();
        $studentList = StudentModel::order('id', 'desc')->paginate(10);
        $count = StudentModel::count();
        //给结果集对象数组中的每个模板对象添加班级关联数据
        foreach ($studentList as $student) {
            $student->grade = $student->grade->name;
        }

        $this->view->assign('studentList', $studentList);
        $this->view->assign('count', $count);
        return $this->view->fetch('list');
    }

    //渲染学生添加界面
    public function add()
    {
        // 确认权限
        $this->checkAccess();
        $this->view->assign('title', '添加学生');
        $gradeList = Grade::order('id', 'desc')->select();

        //将班级表中所有数据赋值给当前模板
        $this->view->assign('gradeList', $gradeList);
        return $this->view->fetch('add');
    }

    //添加学生到表中
    public function checkAdd(Request $request)
    {
        if (!$request->isAjax()) {
            return $this->error('非法请求');
        }
        $data = $request->post();
        $result = StudentModel::create($data);
        //设置返回数据
        $status = 0;
        $message = '添加失败,请检查';
        //检测更新结果,将结果返回给grade_edit模板中的ajax提交回调处理
        if ($result == true) {
            $status = 1;
            $message = '恭喜, 添加成功~~';
        }

        return ['status' => $status, 'message' => $message];
    }

    //学生状态变更
    public function setStatus(Request $request)
    {
        if (!$request->isAjax()) {
            return $this->error('非法请求');
        }
        $id = $request->param('id');
        $student = StudentModel::get($id);

        if ($student->getData('status') == 1) {
            StudentModel::update(['status' => 0], ['id' => $id]);
        } else {
            StudentModel::update(['status' => 1], ['id' => $id]);
        }
    }

    //渲染学生编辑界面
    public function edit(Request $request)
    {
        // 确认权限
        $this->checkAccess();
        $id = $request->param('id');
        $student = StudentModel::get($id);
        //学生一开始就在一个班级里了
        $student->grade = $student->grade->name;
        $gradeList = Grade::order('id', 'desc')->select();

        $this->view->assign('title', '编辑班级');
        $this->view->assign('student', $student);
        $this->view->assign('gradeList', $gradeList);
        return $this->view->fetch('edit');
    }

    //更新学生信息
    public function checkEdit(Request $request)
    {
        if (!$request->isAjax()) {
            return $this->error('非法请求');
        }
        $data = $request->param();
        $condition = ['id' => $data['id']];
        $result = StudentModel::update($data, $condition);
        $status = 0;
        $message = '更新失败,请检查';
        //检测更新结果,将结果返回给edit模板中的ajax提交回调处理
        if ($result == true) {
            $status = 1;
            $message = '恭喜, 更新成功~~';
        }

        return ['status' => $status, 'message' => $message];
    }

    //软删除操作
    public function delete(Request $request)
    {
        if (!$request->isAjax()) {
            return $this->error('非法请求');
        }
        $id = $request->param('id');
        StudentModel::update(['is_delete' => 1], ['id' => $id]);
        StudentModel::destroy($id);
    }

    //恢复删除操作
    public function unDelete(Request $request)
    {
        if (!$request->isAjax()) {
            return $this->error('非法请求');
        }
        StudentModel::update(['delete_time' => NULL], ['is_delete' => 1]);
    }
}
