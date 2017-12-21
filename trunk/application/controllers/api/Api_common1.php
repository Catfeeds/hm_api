<?php

defined('BASEPATH') or exit('No direct script access allowed');

//require_once('sms/restdemo-php/lib/Ucpaas.class.php');

class Api_common1 extends CI_Controller {

    private $resultJSON;
    private $result_code;
    private $result_msg;

    public function __construct()
    {
        header("Access-Control-Allow-Origin: *");
        header('Access-Control-Allow-Methods: GET, POST, PUT,DELETE');
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
        parent::__construct();
    }

    /**
     * 上传图片 dir文件夹名称
     */
    public function upload_img()
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
            $file_name = make_rand_code() . $file_ext;
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
    public function upload_file()
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

    public function returnData($data, $isJSON = false)
    {
        $this->result_code = "200";
        $this->result_msg = "success";
        $this->resultJSON = array(
            "msg" => $this->result_msg,
            "code" => $this->result_code,
            "data" => $data
        );
        if ($isJSON) {
            $str = json_encode($this->resultJSON);
        } else {
            $str = json_encode($this->resultJSON);
        }
        echo $str;
    }

    /**
     * 获取服务器时间
     */
    public function get_time()
    {
        echo time();
    }

    /**
     * 生成随机数 （数字和字母）
     * @prams string $prefix 前缀
     * @prams string $suffix 后缀
     */
    function make_rand_code($pre = '', $suffix = '')
    {
        return $pre . base_convert(time() . substr(microtime(), 2, 6) . rand(10, 99), 10, 16) . $suffix;
    }

    //所有的公司信息
    public function corporateName()
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

}
