<?php

namespace app\index\controller;

use app\index\controller\Base;

class Index extends Base
{
    public function index()
    {
        $this->isLogin();
        $this->assign('title', '教务管理系统');
        return $this->fetch();
    }
}
