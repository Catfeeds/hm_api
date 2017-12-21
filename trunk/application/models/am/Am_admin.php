<?php

/*
 * 管理后台
 * @author:jacky Version 1.0.0 2016-9-19
 */
require_once APPPATH . '/models/Modelbase.php';

class Am_admin extends Modelbase
{

    public $_table;

    public function __construct()
    {
        parent::__construct();
        $this->_table = strtolower(__CLASS__);
    }

    //curl请求地址抓到数据
    function request($url, $https = false, $post = 'get', $data = 'null')
    {
        //初始化curl
        $ch = curl_init();
        //设置基本参数
        //设置返回值不直接输出
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);

        if ($post == 'post') {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        if ($https) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        }
        $content = curl_exec($ch);
        curl_close($ch);
        return $content;
    }

    public function check_login($username, $pass)
    {
        $this->db->select("uid,role_id,username,create_at,login_at,login_ip,avatar");
        $this->db->distinct(TRUE);
        $this->db->from($this->_table);
        $pass = md5($pass);
        $this->db->where('username', $username);
        $this->db->where('userpass', $pass);
        $query = $this->db->get();
        $userinfo = (!$query->num_rows()) ? NULL : $query->row_array();
        return $userinfo;
    }


    public function update_login($uid)
    {
        $ip_address = $this->input->ip_address();
        if (!empty($ip_address) && !empty($uid)) {
            $this->db->where("{$this->_table}.uid", $uid);
            $this->db->update("{$this->_table}", array("login_ip" => $ip_address, "login_at" => date('Y-m-d H:i:s')));
            $num = $this->db->affected_rows();
            return $num;
        }
        return FALSE;
    }

}
