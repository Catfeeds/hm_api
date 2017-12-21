<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . '/controllers/api/Apibase.php';

/**
 * Class Contract 公用模块
 */
class Api_common extends Apibase {

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 上传图片 dir文件夹名称
     */
    public function upload_img_post()
    {
        $dir = $this->input->get_post('dir');
        $files = $this->input->get_post('base64');
        $receive_file_name = $this->input->get_post('name');
        if (!empty($receive_file_name)) {
            $file_ext = strrchr($receive_file_name, '.');
            $temp_arr = strstr($receive_file_name, $file_ext, true);
            $cur_path = $dir . DIRECTORY_SEPARATOR . date('Ymd') . DIRECTORY_SEPARATOR;
            //$savePath = 'statics' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $cur_path;
            $savePath = 'resource' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $cur_path;
            if (!is_dir($savePath)) {
                $mkres = @mkdir($savePath, 0777, TRUE);
            }
            $file_name = $this->make_rand_code() . $file_ext;
            $file_path = FCPATH . $savePath . $file_name;
            if (!$this->base64_to_img($files, $file_path)) {
                writelog(["上传失败" => $file_path]);
            }
            $http_url = base_url() . $savePath . $file_name;
            $http_url = str_replace("\\", "/", $http_url);
            $is_image = isImage($file_path);
            $res = array("url" => $http_url, "real_path" => $file_path, "is_image" => $is_image, "file_ext" => $file_ext, "files" => '');
            $this->returnData($res);
        }
    }

    /**
     * 上传文件 dir文件夹名称
     */
    public function upload_file_post()
    {
        $dir = $this->input->get_post('dir');
        $fileName = $_FILES['file']['name']; //源文件名
        if (!empty($fileName)) {
            $file_ext = strrchr($fileName, '.');
            $cur_path = $dir . DIRECTORY_SEPARATOR . date('Ymd') . DIRECTORY_SEPARATOR;
            //$savePath = 'statics' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $cur_path;
            $savePath = 'resource' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $cur_path;
            if (!is_dir($savePath)) {
                $mkres = @mkdir($savePath, 0777, TRUE);
            }
            $file_name = $this->make_rand_code() . $file_ext; //文件保存名字
            $file_path = FCPATH . $savePath; //文件保存路径
            $tmp_file_name = $_FILES['file']['tmp_name']; //得到上传后的临时文件
            move_uploaded_file($tmp_file_name, $file_path . $file_name); //移动文件到最终保存目录
            $http_url = base_url() . $savePath . $file_name;
            $this->returnData($http_url);
        }
    }

    /**
     * 获取服务器时间
     */
    public function get_time_get()
    {
        echo time();
    }

    //所有的公司信息
    public function corporateName_get()
    {
        //接受并检测数据
        $request_data = $this->check_param([
            'id' => ['主键', 'integer'],
                ], [], 'get');
        $this->load->model('khm/khm_customer');
        $this->model = $this->khm_customer;
        $data = $this->model->f7('name ,id');
        $this->returnData($data);
    }

    public function test_get()
    {

        echo "string";
    }

}
