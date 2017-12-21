<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . '/controllers/api/Apibase.php';

class Api_notice extends Apibase
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('nm/nm_notice', 'model');
    }

    public function add_post()
    {
        $request_data = $this->check_param([
            'content' => ['公告内容', 'required', 'max_length[200]'],
        ], [], 'post');
        $request_data['user'] = $this->loginData['id'];
        $request_data['create_at'] = time();
        $request_data['cid'] = $this->loginData['cid'];
        $id = $this->model->add($request_data);
        $this->returnData($id);
    }

    public function list_get()
    {
        $request_data = $this->check_param([
            'limit' => ['展示多少条', 'integer']
        ]);
        $cid = $this->loginData['cid'];
        $select = $this->model->getCols('nm_notice');
        $select[] = "bmm_employees.name as 'bmm_employees.name'";
        $this->db->select($select);
        $this->db->from('nm_notice');
        $this->db->join('bmm_employees', 'bmm_employees.id = nm_notice.user', 'left');
        $this->db->where(['nm_notice.cid' => $cid]);
        $this->db->order_by('nm_notice.create_at desc');
        if (!empty($request_data['limit'])) {
            $this->db->limit($request_data['limit']);
        }
        $query = $this->db->get();
        $rows = (!$query->num_rows()) ? NULL : $query->result_array();
        $this->returnData($rows);
    }

}
