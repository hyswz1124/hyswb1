<?php


namespace Admin\Model;

use Think\Exception;
use Think\Model\RelationModel;

class AdminLogModel extends RelationModel {


    /**
     * 写入管理员操作日期
     * @param  $adminid
     * @param  $moduleName 操作类型
     * @param  $title标题
     * @param $relateType 相关Model
     * @param array $relateid 相关id
     */
    public function save_log($adminid, $moduleName, $title, $relateType, $relateid) {
        try {
            $data['adminid'] = intval($adminid);
            $data['module_name'] = $moduleName;
            $data['title'] = $title;
            $data['relate_type'] = $relateType;
            $data['relate_id'] = $relateid;
            $data['create_time'] = datetimenew();
            if ($this->create($data)) {
                if ($this->add())
                    return true;
                else
                    return false;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }
}