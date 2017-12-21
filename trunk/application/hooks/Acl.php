<?php

/*
 * Acl权限控制
 * @author:jacky Version 1.0.0 2016-9-19
 */

class Acl {

    private $CI;
    private $dir;
    private $url_model;
    private $url_method;
    private $http_method;

    function __construct()
    {
        $this->CI = & get_instance();
        //$this->CI->load->library('session');
        $this->dir = $this->CI->uri->segment(1);
        $this->url_model = $this->CI->uri->segment(2);
        $this->url_method = $this->CI->uri->segment(3, 'index');
        $this->http_method = $this->CI->input->method();
    }

    function auth()
    {
        
    }

}
