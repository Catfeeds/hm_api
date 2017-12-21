<?php

/*
 * 权限模型
 * @author:jacky Version 1.0.0 2016-9-19
 */
require_once APPPATH . '/models/Modelbase.php';

class Am_permission extends Modelbase {

    public $_table;

    public function __construct()
    {
        parent::__construct();
        $this->_table = strtolower(__CLASS__);
    }

    /**
     * 根据角色取得用户权限
     * @param int $role 角色id
     * @return []
     */
    public function get_permission_by_role($role)
    {
        $query_role = $this->db->get_where('am_admin_role', array('id' => $role), 1);
        $role_info = $query_role->row_array(); //取得role记录
        $permission_ids = explode(',', $role_info['permission']);
        $this->db->select('id,name,permkey,permkey,controller,method,http_method');
        $this->db->from($this->_table);
        if ('*' !== $role_info['permission']) {
            $this->db->where_in('id', $permission_ids);
        }
        $query = $this->db->get();
        return (!$query->num_rows()) ? NULL : $query->result_array();
    }

    /**
     * 根据角色取得用户菜单权限
     * @param int $role 角色id
     * @return []
     */
    public function get_permission_item_by_role($role, $class = "youliang")
    {
        $query_role = $this->db->get_where('am_admin_role', array('id' => $role), 1);
        $role_info = $query_role->row_array(); //取得role记录
        $permission_ids = explode(',', $role_info['permission']);
        $this->db->select('id,name,permkey,controller,method,http_method');
        $this->db->from($this->_table);
        if ('*' !== $role_info['permission']) {
            $this->db->where_in('id', $permission_ids);
        }
        $this->db->where('controller', $class);
        $query = $this->db->get();
        return (!$query->num_rows()) ? NULL : $query->result_array();
    }

    /**
     * 添加权限
     * @param string $name 权限名称
     * @param string $class 控制器类名称
     * @param string $method 方法名称
     * @param string $http_method http请求方式
     * @return int
     */
    public function add_permission($name, $class, $method, $http_method)
    {
        $now_date = date("Y-m-d H:i:s");
        $permkey = $class . "@" . $method . "@" . $http_method;
        if (empty($name)) {
            $name = $permkey;
        }
        $insert_data = array(
            'name' => $name,
            'permkey' => $permkey,
            'class' => $class,
            'method' => $method,
            'http_method' => $http_method,
            'create_at' => $now_date,
            'update_at' => $now_date
        );

        $this->db->insert($this->_table, $insert_data);
        return $this->db->insert_id();
    }

    /**
     * 查询权限记录
     * @param type $class
     * @param type $method
     * @param type $http_method
     * @return type
     */
    public function get_permission_by_param($class, $method, $http_method)
    {
        $query = $this->db->get_where($this->_table, ['controller' => $class, 'method' => $method, 'http_method' => $http_method]);
        return (!$query->num_rows()) ? NULL : $query->row_array();
    }

}
