<?php

namespace app\index\model;

use think\Model;

class Access extends Model
{
    // 是否需要自动写入时间戳，如果设置为字符串，则表示时间字段的类型
    protected $autoWriteTimestamp = true;
    // 创建时间字段
    protected $createTime = 'create_time';
    // 更新时间字段
    protected $updateTime = 'update_time';
    // 时间字段取出后的默认时间格式
    protected $dateFormat = 'Y年m月d日';

    //状态字段:status返回值处理
    public function getStatusAttr($value)
    {
        $status = [
            0 => '已禁用',
            1 => '已启用'
        ];
        return $status[$value];
    }

    public function role()
    {
        return $this->belongsToMany('Role', '\app\index\model\RoleAccess', 'role_id');
    }
}
