<?php

class RosterLogs extends Controller {

    protected $f3;
    protected $db;

    function __construct() {
        /* for Image 
          1= befoe work imaggelocation image; 6 = Final Location image
         * */
        $this->db_init();
    }

    /*
      All job list
     */

    function index() {
		//echo 'sdrsf'; die;
        $template = new Template;
        $view = new View;
        //Get sub job type
        
        $roster_log_types = $this->db->exec("SELECT id,title,Alias FROM roster_log_types WHERE status=1  ORDER BY title ASC");
        $this->f3->set('roster_log_types', $roster_log_types);

        //Get Division List
        $division_types = $this->db->exec("SELECT * FROM companies ORDER BY item_name ASC");
        $this->f3->set('division_types', $division_types);

        //Get Site List
        $site_list = $this->db->exec("SELECT users.id AS `idin`, CONCAT(users.name, ' ', users.surname) AS `item_name`
            FROM users
            INNER JOIN lookup_answers ON lookup_answers.foreign_id = users.id AND lookup_answers.lookup_field_id = 384 AND lookup_answers.table_assoc = 'users'
            INNER JOIN users_user_division_groups ON users_user_division_groups.user_id = lookup_answers.foreign_id
            GROUP BY users.id ORDER BY CONCAT(users.name, ' ', users.surname) asc");
        $this->f3->set('site_list', $site_list);
        
        
        
        $this->f3->set('Job_List', $job_list);
        $this->f3->set('page_title', 'Roster Logs List');
        $this->f3->set('base_url', $this->f3->get('base_url'));
        echo $template->render('header.htm');
        echo $template->render('nav.htm');
        echo $view->render('roster_logs/index.php');
        echo $template->render('footer.htm');
    }

    /*
      Fetch Roster Logs list via ajax
     */

    function fetchRosterLogsAjax() {
       //echo 'dfgfdg'; die;
        $order_by = array();
        $length = $this->f3->get('POST.length');
        $start = $this->f3->get('POST.start');

        if (empty($length)) {
            $length = 10;
            $start = 0;
        }
        
        $columnData = array(
            'roster_logs.created_at',
            'roster_log_types.title',
            'roster_logs.division_name',
            'roster_logs.site_name',
            'roster_logs.team_name',
            'roster_logs.employee_name',
            'roster_logs.roster_start_date',
            'roster_logs.shift_start_date_time',
            'roster_logs.shift_end_date_time',
            'roster_logs.detail',
           'action',
        );
        $sortData = $this->f3->get('POST.order');
        $order_by = $columnData[$sortData[0]['column']];
        $sort_order = $sortData[0]['dir'];

        $searchData = $this->f3->get('POST.searchBox');

        $roster_logtypes_id = $this->f3->get('POST.roster_logtypes_id');
        $division_id = $this->f3->get('POST.division_id');
        $site_id = $this->f3->get('POST.site_id');
        $job_location_id = $this->f3->get('POST.job_location_id');
        $job_status = $this->f3->get('POST.job_status');
        $roster_start_date = $this->f3->get('POST.roster_start_date');
        $from_date = $this->f3->get('POST.from_date');
        $to_date = $this->f3->get('POST.to_date');

        //$where_cond = "jobs.job_delete_status='1' AND jobs.job_type_id='1'";
        $where_cond = "1 ";
        $and = ' AND ';
        if ($searchData) {
            $searchData = trim($searchData);
            $searchData = addslashes($searchData);
            $where_cond .= $and . '(roster_logs.division_name like "%' . $searchData . '%" OR roster_logs.site_name like "%' . $searchData . '%" OR roster_logs.team_name like "%' . $searchData . '%" OR 
                roster_log_types.title like "%' . $searchData . '%" OR roster_logs.employee_name like "%' . $searchData . '%")';
        }
        if (!empty($roster_start_date)) {
           $roster_start_date = date('Y-m-d', strtotime($roster_start_date));
            $where_cond .= $and . "(roster_logs.roster_start_date like '%" . $roster_start_date . "%')";
        }

        if (!empty($from_date)) {
            $from_date = date('Y-m-d', strtotime($from_date));
            //$from_date = $from_date . ' 00:00:00';
            $where_cond .= $and . "(roster_logs.shift_start_date_time like '%" . $from_date . "%')";
        }
        if (!empty($to_date)) {
            $to_date = date('Y-m-d', strtotime($to_date));
            //$to_date = $to_date . ' 23:59:59';
            $where_cond .= $and . "(roster_logs.shift_end_date_time like '%" . $to_date . "%')";
        }

        if (!empty($roster_logtypes_id))
            $where_cond .= $and . "(roster_logs.roster_logtypes_id ='" . $roster_logtypes_id . "')";
        if (!empty($division_id))
            $where_cond .= $and . "(roster_logs.division_id ='" . $division_id . "')";
        if (!empty($site_id))
            $where_cond .= $and . "(roster_logs.site_id ='" . $site_id . "')";
        

         $select_records = "SELECT roster_logs.*,CONCAT(usr.name, ' ', usr.surname) created_byname, roster_log_types.title ,roster_log_types.Alias";
        $select_num_rows = "SELECT count(roster_logs.id) as total_records ";
        $total_records = $this->db->exec("$select_num_rows FROM roster_logs LEFT JOIN roster_log_types ON roster_logs.roster_logtypes_id=roster_log_types.id WHERE $where_cond");


        $total_data = (!empty($total_records) && isset($total_records[0]['total_records'])) ? $total_records[0]['total_records'] : 0;

        $job_list = array();
        
        if ($total_data > 0) {
            $job_list = $this->db->exec("$select_records FROM roster_logs "
                    . " LEFT JOIN users usr on usr.id = roster_logs.created_by "
                    . " LEFT JOIN roster_log_types ON roster_logs.roster_logtypes_id=roster_log_types.id  WHERE $where_cond ORDER BY $order_by $sort_order LIMIT $length OFFSET $start");
        }


        $jsonArray = array(
            'draw' => $this->f3->get('POST.draw'),
            'recordsTotal' => $total_data,
            'recordsFiltered' => $total_data,
            'data' => array(),
        );
        //creating view for business user in datatable
        if (!empty($job_list)) {
            $jobStatusArr = array('0' => 'Open', '5' => 'Cancelled', '2' => 'Pending', '3' => 'Inprogress', '4' => 'Completed');
            $base_url = $this->f3->get('base_url');
            foreach ($job_list as $key => $val) {


                $allocation_link = $base_url . '/job-allocation?job-id=' . $val['id'];
                $edit_link = $base_url . '/edit-job?job-id=' . $val['id'];
                $delete_link = "javaScript:delete_job(" . $val['id'] . ")";
                $view_link = "javaScript:view_more_detail(" . $val['id'] . ")";
                $edit = '<a title="Edit job" class="green-a" href="' . $edit_link . '"><span uk-icon="file-edit"></span></a>';
                $delete = '<a title="Delete job" class="red-a" href="' . $delete_link . '"><span uk-icon="trash"></span></a>';
                $view = '<a title="View detail" class="black-a" href="' . $view_link . '"><span uk-icon="more-vertical"></span></a>';
                $allocation = '<a title="Job allocation" class="black-a" href="' . $allocation_link . '"><span uk-icon="cog"></span></a>';
                if ($val['job_status'] != '0')
                    $edit = $delete = '';

                
                $jsonArray['data'][] = array(
                    'sr_no' => $start + $key + 1,
                    'created_byname' => $val['created_byname']?$val['created_byname']:"Edge Notificaiton",
                    'created_at' => $val['created_at'],
                    'roster_logtypes_id' => $val['roster_logtypes_id'],
                    'roster_id' => $val['roster_id'],
                    'shift_id' => $val['shift_id'],
                    'site_id' => $val['site_id'],
                    'division_id' => $val['division_id'],
                    'division_name' => $val['division_name'],
                    'site_name' => $val['site_name'],
                    'employee_name' => $val['employee_name'],
                    'team_name' => $val['team_name'],
                    'detail' => $val['detail'],
                    'title' => $val['title'],
                   'roster_start_date' => $val['roster_start_date'] ? date('d-m-y', strtotime($val['roster_start_date'])) : "",
                    'shift_start_date_time' => $val['shift_start_date_time'] ? date('d-m-y H:i:s', strtotime($val['shift_start_date_time'])) : "",
                    'shift_end_date_time' => $val['shift_end_date_time'] ? date('d-m-y H:i:s', strtotime($val['shift_end_date_time'])) : "",
                    'created_at' => $val['created_at'] ? date('d-m-y H:i:s', strtotime($val['created_at'])) : "",
                    'action' => $edit . ' ' . $delete . ' ' . $view . ' ' . $allocation,
                );
            }
        }
        //prd($jsonArray);
        echo json_encode($jsonArray);
        exit;
        echo $this->f3->get('POST.draw');
        exit;
    }
   
 

}

?>