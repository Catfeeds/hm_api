<?php

/*
 * 模型的基类
 * @author:jacky Version 1.0.0 2016-9-19
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Modelbase extends CI_Model {

    public $_table = '';

    public function __construct()
    {
        parent::__construct();
        $this->inits();
        $this->_table = strtolower(get_called_class());
    }

    /**
     * 子类控制器实例化
     * [inits description]
     * @return [type] [description]
     */
    public function inits()
    {
        # code...
    }

    /**
     * 设置表
     */
    public function set_table($table)
    {
        $this->_table = strtolower($table);
    }

    /**
     * 添加记录
     */
    public function add($insert_data = [])
    {
        $this->db->insert($this->_table, $insert_data);
        return $this->db->insert_id();
    }

    /**
     * 删除记录
     * @param array/string $filter (示例：$filter = ['id'=>10] 或者 $filter = "id=10 or money>5")
     * 批量删除 未例 $filter = "id in(1,3,5)";
     */
    public function del($filter = NULL)
    {
        $this->db->where($filter);
        return $this->db->delete($this->_table);
    }

    /**
     * 更新记录
     *  @param array/string $filter (示例：$filter = ['id'=>10] 或者 $filter = "id=10 or money>5")
     */
    public function edit($filter = NULL, $postdata = [], $return_resource = false)
    {
        $this->db->where($filter);
        $query = $this->db->update($this->_table, $postdata);
        if ($return_resource) {
            return $query;
        }
        return $this->db->affected_rows();
    }

    /**
     * 获取1行记录
     * @param array/string $filter (示例：$filter = ['id'=>10] 或者 $filter = "id=10 or money>5")
     */
    public function get_one($select = '*', $filter = NULL, $order = NULL, $group_by = NULL)
    {
        $this->db->select($select);
        $this->db->from($this->_table);
        if (!empty($filter)) {
            $this->db->where($filter);
        }
        if (!empty($order)) {
            $this->db->order_by($order);
        }
        if (!empty($group_by)) {
            $this->db->group_by($group_by);
        }
        $this->db->limit(1);
        return $this->db->get()->row_array();
    }

    /**
     * 获取统计数量
     * @param array/string $filter (示例：$filter = ['id'=>10] 或者 $filter = "id=10 or money>5")
     */
    public function get_count_one($filter = NULL, $count_select_column = '', $count_select_aliax = '')
    {
        if (empty($count_select_column)) {
            return false;
        }
        $count_select_aliax = !empty($count_select_aliax) ? $count_select_aliax : $count_select_column;
        $this->db->from($this->_table);
        if (!empty($filter)) {
            $this->db->where($filter);
        }
        $this->db->select_sum($count_select_column, $count_select_aliax);
        $this->db->limit(1);
        return $this->db->get()->row_array();
    }

    /**
     * 获取统计数量
     * @param array/string $filter (示例：$filter = ['id'=>10] 或者 $filter = "id=10 or money>5")
     */
    public function countTotal($filter = NULL)
    {
        $this->db->from($this->_table);
        if (!empty($filter)) {
            $this->db->where($filter);
        }
        return $this->db->count_all_results();
    }

    /**
     * 获取多行记录 (所有)
     * @param array/string $filter (示例：$filter = ['id'=>10] 或者 $filter = "id=10 or money>5")
     */
    public function get_all($select = '*', $filter = NULL, $order = NULL, $group_by = NULL, $page_size = 0, $page = 1)
    {
        $this->db->select($select);
        $this->db->from($this->_table);
        if (!empty($filter)) {
            $this->db->where($filter);
        }
        if (!empty($order)) {
            $this->db->order_by($order);
        }
        if (!empty($group_by)) {
            $this->db->group_by($group_by);
        }
        if (isset($page_size) && $page_size > 0) {
            $this->db->limit($page_size, ($page - 1) * $page_size);
        }
        return $this->db->get()->result_array();
    }

    /**
     * 批量添加记录
     */
    public function add_batch($insert_data = [])
    {
        if (array_depth($insert_data) >= 2) {   //检测是否是二维以上数组
            $this->db->insert_batch($this->_table, $insert_data);
            return $this->db->insert_id(); //最后1行ID
        }
        return false;
    }

    /**
     * 批量更新记录
     * @param array/string $filter (示例：$filter = ['id'=>10] 或者 $filter = "id=10 or money>5")
     */
    public function edit_batch($ids, $filter = NULL, $postdata = [], $return_resource = false)
    {
        $this->db->where($filter);
        if (is_array($ids) && array_depth($ids) > 1) {
            $this->db->update_batch($this->_table, $postdata);
        } else {
            //$this->db->where('id', $ids);
            //$this->db->update($this->_table, $postdata);
            //多行记录更新相同的参数的值
            $this->db->where_in($this->_primary, $ids); //$ids = 1,2,3
            if (array_depth($postdata) > 1 || $ids < 0) {
                return false;
            }
            $this->db->update($this->_table, $postdata);
        }

        //多行记录更新不相同的参数的值
        //$this->db->update_batch($this->_table, $postdata);
        if ($return_resource) {
            return $query;
        }
        return $this->db->affected_rows();
    }

    /**
     * 打印sql语句 (已执行操作)
     */
    public function show_sql()
    {
        $sql = $this->db->last_query();
        return $sql;
    }

    /**
     * 获取列表查询的SQL，如果需要关联查询，复写此方法即可
     * @param type $select
     * @param type $filter
     */
    public function getListSql($select = "*", $filter = NULL)
    {
        $this->db->select($select);
        $this->db->from($this->_table);
        if (!empty($filter)) {
            $this->db->where($filter);
        }
        return $this->compile_sql();
    }

    /**
     * 打印sql语句 (未执行操作,即构造生成的sql语句)
     */
    public function compile_sql()
    {
        return $this->db->compile_select();
    }

    /**
     * 插入sql关键词及对应语句,只支持简单sql，即不能包含子查询，如果是复杂查询则忽略
     * @param type $sql
     * @param string $key
     * @param type $value
     * @return string
     */
    final static private function repairsql($sql, $key, $value)
    {
        $arr = preg_split('/\s' . $key . '\s/i', $sql);
        if (count($arr) === 2) {
            if ("WHERE" == strtoupper($key)) {
                $sql = $arr[0] . " " . $key . " (" . $value . ") and " . $arr[1];
            } else if ("ORDER BY" == strtoupper($key)) {
                $sql = $arr[0] . " " . $key . " " . $value . ", " . $arr[1];
            }
        } else if (count($arr) > 2) {
            $sql = $sql;
        } else {
            $sql = $sql . " " . $key . " " . $value;
        }
        return $sql;
    }

    /* public function setQuery($it, $select = "*", $filter = NULL, $join = [])
      {
      $it->db->select($select);
      $it->db->distinct(TRUE);
      $it->db->from($this->_table);
      //加入join语法 $join = [0=>["am_admin_role", "am_admin_role.id = am_admin.role_id", "left"]];
      if (!empty($join)) {
      foreach ($join as $jv) {
      if (!isset($jv[0]) || !isset($jv[1]))
      continue;
      $joinTable = $jv[0];
      $joinConditon = $jv[1];
      $joinType = isset($jv[2]) ? $jv[2] : 'left';

      $it->db->join($joinTable, $joinConditon, $joinType);
      }
      }
      if (!empty($filter)) {
      $it->db->where($filter);
      }
      return $it;
      } */

    public function setQuery($it, $select = "*", $filter = NULL)
    {
        $it->db->select($select);
        $it->db->distinct(TRUE);
        $it->db->from($this->_table);
        if (!empty($filter)) {
            $it->db->where($filter);
        }
        return $it;
    }

    /**
     * 输入表名 获取所有字段
     */
    public function getCols($table)
    {
        $rescolumns = $this->db->query("SHOW FULL COLUMNS FROM {$table}");
        $row = $rescolumns->result_array();
        $select = [];
        foreach ($row as $i => $v) {
            $select[] = "{$table}.{$v['Field']} as '{$table}.{$v['Field']}'";
        }
        return $select;
    }

    public function newGrid($select = "*", $filter = NULL, $page = 1, $page_size = 6, $order = '', $join = [])
    {
        return $this->grid($select, $filter, $page, $page_size, null, null, $order, true, $join);
    }

    /**
     * 后台系统分页列表
     */
    public function grid($select = "*", $filter = NULL, $page = 1, $page_size = 6, $sidx = NULL, $sord = NULL, $order = NULL, $isCount = TRUE, $join = [])
    {
        if (empty($page_size) || $page_size == 0) {
            $page_size = 6;
        }
        if (empty($page) || $page == 0) {
            $page = 1;
        }
        $it = $this;
        $select = empty($select) ? "*" : $select;
        $it = $it->setQuery($it, $select, $filter);
        if (!empty($sidx)) {
            if (strstr($sidx, " ")) {
                $it->db->order_by($sidx);
            } else {
                if (empty($sord)) {
                    $sord = "ASC";
                }
                $it->db->order_by($sidx, $sord);
            }
        } else if (!empty($order)) {
            $it->db->order_by($order);
        }
        //加入join语法 $join = [0=>["am_admin_role", "am_admin_role.id = am_admin.role_id", "left"]];
        if (!empty($join)) {
            foreach ($join as $jv) {
                if (!isset($jv[0]) || !isset($jv[1]))
                    continue;
                $joinTable = $jv[0];
                $joinConditon = $jv[1];
                $joinType = isset($jv[2]) ? $jv[2] : 'left';

                $it->db->join($joinTable, $joinConditon, $joinType);
            }
        }
        $it->db->limit($page_size, ($page - 1) * $page_size);
        $query = $it->db->get();
        $rows = (!$query->num_rows()) ? NULL : $query->result_array();
        $query_sql = "";
        if ("production" != ENVIRONMENT) {
            $query_sql = $it->db->last_query();
        }
        //writelog(['sql' => $query_sql]);
        $records = 0;
        $total = 0;
        $count_sql = "";
        if ($isCount) {
            $it = $it->setQuery($it, $select, $filter, $join);
            $records = $it->db->count_all_results();
            $total = ceil($records / $page_size);
            if ("production" != ENVIRONMENT) {
                $count_sql = $it->db->last_query();
            }
        }
        $grid = array("page" => $page, "page_size" => $page_size, "records" => $records, "rows" => $rows, "total" => $total, "query_sql" => $query_sql, "count_sql" => $count_sql, "code" => 0);
        //writelog($grid);
        return $grid;
    }

    /**
     * 移动端分页列表数据接口实现
     */
    public function list_do($sql, $order = NULL, $page = 1, $page_size = 20, $isCount = TRUE)
    {
        $begintime = microtime();
        if (empty($sql)) {
            return false;
        }
        if (empty($page_size) || $page_size == 0) {
            $page_size = 20;
        }
        if (empty($page) || $page == 0) {
            $page = 1;
        }
        $page = intval($page, 10);
        $query_sql = $sql;
        if (!empty($order)) {
            $query_sql = self::repairsql($query_sql, 'ORDER BY', $order);
        }
        $query_sql = str_ireplace('limit', 'limit', $query_sql);
        $query_sql = explode('limit', $query_sql);
        $query_sql = trim($query_sql[0]);
        $limit = " LIMIT " . ($page - 1) * $page_size . "," . $page_size;
        $query_sql = $query_sql . $limit;
        $query = $this->db->query($query_sql);
        $rows = $query->result_array();
        $querytime = microtime();
        $counttime = microtime();
        $count_sql = "";
        $grid = [];
        if ($isCount) {
            $records = 0;
            $total = 0;
            //利用子查询获取总记录条数
            $count_sql = "SELECT count(*) countnum FROM (" . $sql . ") as counttable";
            $lastresult = $this->db->query($count_sql);
            $records = intval($lastresult->row_array()['countnum'], 10);
            $total = ceil($records / $page_size);
            $counttime = microtime();
            $grid['records'] = $records;
            $grid['total'] = $total;
        }
        $grid['page'] = $page;
        $grid['rows'] = $rows;
        if (isset($_GET['debug']) && $_GET['debug'] == "true") {
            //调试模式下显示sql统计
            $counttime = $counttime - $querytime;
            $querytime = $querytime - $begintime;
            $grid['query_sql'] = $query_sql;
            $grid['count_sql'] = $count_sql;
            $grid['querytime'] = $querytime . "秒";
            $grid['counttime'] = $counttime . "秒";
        }
        return $grid;
    }

    public function info($select = "*", $filter = NULL, $order = NULL)
    {
        $it = $this;
        $select = empty($select) ? "*" : $select;
        $it = $it->setQuery($it, $select, $filter);
        if (!empty($order)) {
            $it->db->order_by($order);
        }
        $it->db->limit(1, 0);
        $query = $it->db->get();
        $info = (!$query->num_rows()) ? NULL : $query->row_array();
        return $info;
    }

    public function f7($select = "*", $filter = NULL, $order = NULL)
    {
        $select = empty($select) ? "*" : $select;
        $this->db->select($select);
        $this->db->distinct(TRUE);
        $this->db->from($this->_table);
        if (!empty($filter)) {
            $this->db->where($filter);
        }
        if (!empty($order)) {
            $this->db->order_by($order);
        }
        $query = $this->db->get();
        $rows = (!$query->num_rows()) ? NULL : $query->result_array();
        return $rows;
    }

}
