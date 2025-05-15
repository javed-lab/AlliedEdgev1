<?php
$servername = "localhost";
$username = "tnsmwdztaz";
$password = "vzZ3mFxE2E";
$dbname = "tnsmwdztaz";

session_start(); // Start the session

$connect = mysqli_connect($servername, $username, $password, $dbname);

if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

class ContractorController extends Controller {
    function Contractors(){
     
        $hr_user = $this->f3->get('hr_user');
        $gid = (isset($_GET['gid']) ? $_GET['gid'] : null);
        $gid2 = (isset($_GET['gid2']) ? $_GET['gid2'] : null);
        //prd($_REQUEST);
        $userType = (isset($_REQUEST['srch_usertype']) ? $_REQUEST['srch_usertype'] : null);
        
        $srchUserDivision = (isset($_REQUEST['srch_userdivision']) ? $_REQUEST['srch_userdivision'] : null);
        
        $userStatus = (isset($_REQUEST['srch_userstatus']) ? $_REQUEST['srch_userstatus'] : null);
        
        $gid_xtra = ($gid ? "inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = $gid and lookup_answers.table_assoc = 'users'" : "").($gid2 ? "inner join lookup_answers2 on lookup_answers2.foreign_id = users.id and lookup_answers2.lookup_field_id = $gid2 and lookup_answers2.table_assoc = 'users'" : "");
    
        $latest_first = (isset($_GET['latest_first']) ? $_GET['latest_first'] : null);
        if($userType){
            $usertype_xtra = " inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id in ($userType) and lookup_answers.table_assoc = 'users' ";
        }else{
            //$usertype_xtra = ($userType ? "inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id in ($userType) and lookup_answers.table_assoc = 'users'" : "");  
             $usertype_xtra = " inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id in (384,107,105,104) and lookup_answers.table_assoc = 'users' ";
        }
        
    
        $search = (trim($_REQUEST['search']) ? trim($_REQUEST['search']) : "");
        $show_inactive = ($_REQUEST['show_inactive'] ? $_REQUEST['show_inactive'] : "");
        $staff_only = ($_REQUEST['staff_only'] ? $_REQUEST['staff_only'] : "");
        
        $hchkim = (isset($_GET['hchkim']) ? $_GET['hchkim'] : null);
        if($hchkim == 'on') {
          echo $this->redirect("UserManager?txtFind=" . urlencode($search));
        }
        
        
        if($_SESSION['u_level'] > 1000 || $hr_user){
          $xtra4 = ", CONCAT('";
          $sql = "select id, item_name from lookup_fields where lookup_id in (select id from lookups where item_name = 'user_group')";
          $result = $this->dbi->query($sql);
          while($myrow = $result->fetch_assoc()) {
            $id = $myrow['id'];
            $item_name = $myrow['item_name'];
            if($tid == $id) {
              $xtra4 .= ",";
            }
            $xtra4 .= "[<a uk-tooltip=\"title: $item_name\" href=\"AccountDetails?uid=', users.id, '\">$item_name</a>]>'";
            $tid = $id;
          }
          
          $xtra4 .= ") as `**`,";
          if($_SESSION['u_level'] >= 1000 || $hr_user) {
            $search_xtra = "or users.employee_id LIKE '%$search%' or users.address LIKE '%$search%' or users.postcode LIKE '%$search%' or states.item_name = '%$search%'";
            $xtra2 = "CONCAT(users.address, '<br />', users.suburb, ' ', if(states.item_name, states.item_name, ''), ' ', users.postcode)  as `Address`, ";
            
        
            $xtra3 = ", CONCAT('<a class=\"list_a\" href=\"JavaScript:edit_user(', users.id, ')\">Edit</a>') as `*`, CONCAT('<a class=\"list_a\" href=\"UserCard?uid=', users.id, '\">Card</a><br />') as `Card`" ;
          }
        
          
          
                       
                      //<br /><a class=\"list_a\" style=\"width: 79px;\" href=\"StaffLicences?manage_mode=1&lookup_id=', users.id, '\">Licences</a><a class=\"list_a\" style=\"width: 79px;\" href=\"staff_availability.php?lookup_id=', users.id, '\">Availability</a>
                      
        } else if((is_array($lids) ? array_search(696, $lids) !== false : $lids == 696)) {
        }
        else if($_SESSION['u_level'] >= 100 && $_SESSION['u_level'] <= 300){
              
              $xtra3 = ",CONCAT('<a class=\"list_a\" href=\"UserCard?uid=', users.id, '\">Card</a><br />') as `Card`" ;
          }
          //$xtra3 .= ", CONCAT('<a target=\"_blank\" class=\"list_a\" style=\"width: 180px;\" href=\"Edit/OpeningClosing?lookup_id=', users.id, '\">Opening/Closing Times</a>') as `Manage`";
        if($search) {
           $search = "
              where (users.name LIKE '%$search%'
              or users.surname LIKE '%$search%'
              or users.preferred_name LIKE '%$search%'
              or CONCAT(users.name, if(users.preferred_name != '', CONCAT(' (', users.preferred_name, ')'), '')) LIKE '%$search%'     
              or CONCAT(users.name, if(users.preferred_name != '', CONCAT(' (', users.preferred_name, ')'), ''), ' ', users.surname) LIKE '%$search%'     
              or users.email LIKE '%$search%'
              or users.phone LIKE '%$search%'
              or users.employee_id LIKE '%$search%'
              or users.supplier_id LIKE '%$search%'
              or users.client_id LIKE '%$search%'
              or users.student_id LIKE '%$search%'
              or users.username LIKE '%$search%'
              or CONCAT(users.name, ' ', users.surname) LIKE '%$search%'
              or CONCAT(users.preferred_name, ' ', users.surname) LIKE '%$search%'
                    $search_xtra)
           " . ($_SESSION['user_id'] != 1 ? " and users.id != 1 " : "");
        }else{
             $search = " where 1 " . ($_SESSION['user_id'] != 1 ? " and users.id != 1 " : "");
        }
    //prd($search);
        $str .= '
          <input type="hidden" name="hdnFilter" id="hdnFilter" />
          <input type="hidden" name="idin" id="idin" />
    
          <script language="JavaScript">
          function edit_user(idin) {
                document.getElementById("idin").value = idin;
                document.frmEdit.action = "Edit/Users";
                document.frmEdit.submit();
          }
          </script>
        ';
        
        $download_xl = (isset($_GET['download_xl']) ? $_GET['download_xl'] : null);
        if($download_xl) {
          $sql = "select id, item_name from lookup_fields where lookup_id in (select id from lookups where item_name = 'user_group') and id in (select lookup_field_id from lookup_answers where table_assoc = 'users') order by item_name";
          $result = $this->dbi->query($sql);
          
          while($myrow = $result->fetch_assoc()) {
            $id = $myrow['id']; 
            $group = $myrow['item_name'];
            if($id == $gid) $group_name = $group;
            if($id == $gid2) $group_name2 = $group;
            $sql_xtra .=  ",(SELECT 'x' FROM `lookup_answers`
            inner join lookup_fields on lookup_fields.id = lookup_answers.lookup_field_id
            where foreign_id = users.id and table_assoc = 'users' and lookup_fields.id = $id) AS `$group`";
          }
          $xl_obj = new data_list;
          $xl_obj->dbi = $this->dbi;
          $xl_obj->sql = "
              SELECT users.employee_id as `Emp ID`, users.client_id as `Client/Site ID`, users.supplier_id as `Supplier ID`, users.name as `Given Name`, users.surname as `Surname`, users.preferred_name as `Preferred Name`,
                 users.email as `Email 1`, users.email2 as `Email 2`,
                         users.phone as `Phone 1`, users.phone2 as `Phone 2`, users.address as `Address`, users.suburb as `Suburb`, states.item_name as `State`, users.postcode as `Postcode`, if(users.dob != '0000-00-00', date_format(users.dob, '%d-%m-%Y'), '') as `DOB`
                 $sql_xtra
                 FROM users
                 left join states on states.id = users.state
                 left join user_status on user_status.id = users.user_status_id
                 $gid_xtra
                 " . ($download_xl == 3 ? "" : "where users.user_status_id in (select id from user_status where item_name " . ($download_xl == 1 ? "" : "!") . "= 'ACTIVE')") . "
            ";
          /* Use this query to get usernames and passwords
          $xl_obj->sql = "
              SELECT users.employee_id as `Emp ID`, concat(users.name, ' ', users.surname) as `Name`, users.phone as `Phone`, users.email as `Email`, users.username as `Username`, users.username as `Password`
                 FROM users
                 left join user_status on user_status.id = users.user_status_id
                 $gid_xtra
                 " . ($download_xl == 3 ? "" : "where users.user_status_id in (select id from user_status where item_name " . ($download_xl == 1 ? "" : "!") . "= 'ACTIVE')") . "
            ";*/
            
          $xl_obj->sql_xl($group_name . ($gid2 ? " and " . $group_name2 : "") . ".xlsx");
        }
        if($_SESSION['u_level'] >= 300) {
          //$xtra = "users.employee_id as `Emp ID`, ";
          if($staff_only || $state) {
            $staff_xtra = "inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = 107 and lookup_answers.table_assoc = 'users'";
          } else {
            $xtra .= "CONCAT(if(users.employee_id != '', CONCAT(users.employee_id, '<br />'), ''), if(users.client_id != '', CONCAT(users.client_id, '<br />'), ''),  if(users.supplier_id != '', CONCAT(users.supplier_id), '')) as `IDs`, ";
            $xtra .= "users.username as `username`, ";
            $staff_xtra = "";
          }
        }
        if($show_inactive) {
          $status_xtra = "user_status.item_name as `Status`,";
        } else {
            if($userStatus == 30 ){
                $search_xtra2 = " and users.user_status_id = (select id from user_status where item_name = 'ACTIVE')";
            }
            else if($userStatus == 40 ){
                $search_xtra2 = " and users.user_status_id = (select id from user_status where item_name = 'INACTIVE')";
            }
            else{
                $search_xtra2 = " and users.user_status_id in (select id from user_status where item_name = 'INACTIVE' || item_name = 'ACTIVE')";
            }
        }
        $view_details = new data_list;
        $view_details->show_num_records = 1;
        $view_details->num_per_page = 50;
        $view_details->nav_count = 20;
        $view_details->dbi = $this->dbi;
        
    //    select * from lookup_answers where lookup_field_id = 107
      //  select item_name from lookup_fields where lookup_id = 107
      
        $hide_menu = (!$search && !$gid && !$gid2 ? 1 : 0);
        $hide_menu = ($search && !$gid && !$gid2 ? 1 : 0);
        //return "$search - $gid - $gid2";

        ?>
    
        <?php
        if($gid || $gid2 || $search || $userType || $userStatus) {
          $view_details->title = ($gid ? "Members of Group: $group_name" . ($gid2 ? " / " . $group_name2 : "") : "Contractor/Suppliers List");
          $view_details->title = '<script>
          function exportSelected() {
              var selectedRows = document.querySelectorAll(\'input[class="reccheck"]:checked\');
              
              if (selectedRows.length === 0) {
                  alert("No rows selected for export.");
                  return;
              }
          
              var selectedIds = Array.from(selectedRows).map(function (checkbox) {
                  return checkbox.value;
              });
          
              // Send the selectedIds to the server for export
              var formData = new FormData();
              formData.append(\'ids\', selectedIds.join(\',\'));
          
              // Use AJAX to send the data to the backend
              var xhr = new XMLHttpRequest();
              xhr.open(\'POST\', \'/app/controllers/ContractorExport.php\', true);
              xhr.responseType = \'blob\'; // Set responseType to blob
          
              xhr.onload = function () {
                  if (xhr.status === 200) {
                      // Handle the successful response
                      var blob = new Blob([xhr.response], { type: \'text/csv\' });
                      var link = document.createElement(\'a\');
                      link.href = window.URL.createObjectURL(blob);
                      link.download = \'complete_contractors_list_edge.csv\';
                      document.body.appendChild(link);
                      link.click();
                      document.body.removeChild(link);
                  } else {
                      // Handle errors
                      alert(\'Error: \' + xhr.status);
                  }
              };
          
              xhr.send(formData);
          }
      </script>
      
      <button onclick="exportSelected()">Export Selected</button>';
      
          


        //             CONCAT('<a class=\"list_a\" href=\"EquipmentIssue?lookup_id=', users.id, '\">Equipment Issue</a>') as `Admin`
          
           $user_id = $_SESSION['user_id'];
          $loginUserDivisions = $this->get_divisions($user_id,0,1);
          
          //if($userType == 104){
              
              if($loginUserDivisions){
                //$loginUserDivisions = "104,105,107,384,".$loginUserDivisions;
                 $loginUserDivisions = $loginUserDivisions;
              }
            else {
               //$loginUserDivisions = "104,105,107,384";  
               $loginUserDivisions = "";  
            }
              
          //}
    //      else 
              
         if($srchUserDivision){
            $divisionFilterSql = " left join lookup_answers sla on sla.foreign_id = users.id and sla.lookup_field_id in (".$loginUserDivisions.") and sla.lookup_field_id = '".$srchUserDivision."'";
            //$divisionFilterSql = " left join lookup_answers sla on sla.foreign_id = users.id and sla.lookup_field_id = '".$srchUserDivision."'";
            
            //$srchFilterSql = ' and ((lookup_answers.lookup_field_id = 107 and sla.id IS NOT NULL) or (lookup_answers.lookup_field_id != 107))';
            //$srchFilterSql = ' and ((sla3.lookup_field_id = 107 AND sla.id IS NOT NULL) OR (sla3.lookup_field_id IS NULL)) ';
              
            //$srchFilterSql = " and sla.id IS NOT NULL";
          }else{
              if($user_id < 6)
              {
                 $divisionFilterSql = " left join lookup_answers sla on sla.foreign_id = users.id ";   
              }else{
                $divisionFilterSql = " left join lookup_answers sla on sla.foreign_id = users.id and sla.lookup_field_id in (".$loginUserDivisions.")";
              }
              //$divisionFilterSql = "";
              //$srchFilterSql = ""; 
              //$srchFilterSql = ' and IF (sla.table_assoc = \'users\', \'and (sla.id IS NOT NULL)\',1)';
              //$srchFilterSql = ' and ((lookup_answers.lookup_field_id = 107 and sla.id IS NOT NULL) or (lookup_answers.lookup_field_id != 107))';
              //$srchFilterSql = ' and ((lookup_answers.lookup_field_id IN (384,107,105) AND sla.id IS NOT NULL) OR (lookup_answers.lookup_field_id = 104)) ';
               //$srchFilterSql = ' and ((sla3.lookup_field_id = 107 AND sla.id IS NOT NULL) OR (sla3.lookup_field_id IS NULL)) ';
              
             // $srchFilterSql = " and (sla.id IS NOT NULL) ";
          }
          $srchFilterSql = ' and ((lookup_answers.lookup_field_id IN (384,107,105) AND sla.id IS NOT NULL) OR (lookup_answers.lookup_field_id = 104)) ';
             $userLocationPermission= "";
             $userAllowedPermission = "";
          
                $userTypeId = $this->userDataAccessType($user_id);
                  if($userTypeId == 2){
                      $assignedStateData = $this->getAssignedStateIds($user_id);
                      if (is_array($assignedStateData)) {
                            $stateAssignedDataArray = array_column($assignedStateData, 'id');
                        } else {
                            $stateAssignedDataArray = array();
                        }
    
                        $stateAssignedDataStr = implode(',', $stateAssignedDataArray);
                      
                    $where_cond .= ' and locationState.id in ('.$stateAssignedDataStr.') ';
                   
                      
                  }
                  else if ($userTypeId == 3) {
                    $parentSites = 0;
                    //$stateList = $this->getStateIds();
                    $parentSites = $this->getUserSiteIds($user_id);
                    $assignedSiteArray = array();
                    if($parentSites != "") {
                       //$assignedSiteArray = explode(',', $parentSites);                }
                        $where_cond .= ' and users2.id in ('.$parentSites.') ';  
                    }else{
                         $where_cond .= ' and users2.id in ('.$parentSites.') ';
                    }
                    
                    $userLocationPermission= "";
                    
                    
                    $userLocationPermission = ' AND IF (sla2.table_assoc = \'users\' and sla2.lookup_field_id IN (384), users.id IN ('.$parentSites.'),1)';
                    
                    //$parentSites = $this->getUserAccessOfSiteIds($parentSites);
                    
                    
                    
                    $userAllowedPermission = ' AND IF (sla3.table_assoc = \'users\' and sla3.lookup_field_id IN (107), users.id IN ('
                            . 'SELECT ass.child_user_id FROM associations ass WHERE ass.association_type_id = 4 AND ass.parent_user_id IN ('.$parentSites.')'
                            . '),1)';
             
                  }
          
          
          
          
              

              $subqueryDivision = ",(SELECT GROUP_CONCAT(' ',com.item_name) FROM lookup_answers la 
    LEFT JOIN companies com ON com.id = la.lookup_field_id
    WHERE la.foreign_id = users.id AND com.id IS NOT NULL) as `Division`";
             
    
    $view_details->sql = "
    SELECT CONCAT('<input type=\"checkbox\" id=\"recid[]\" class=\"reccheck\" name=\"recid[]\" value =\"', users.id, '\">') as '<input type=\"checkbox\" id=\"userid[]\" class=\"allreccheck\" onclick=\"allreccheck()\" name=\"userid[]\">' ,users.id as `idin`,       
    CONCAT('<a target=\"_blank\" href=\"ShowImage?re_encrypt=1&no_crypt=1&i=induction_user_image/',users.id, '/user_',users.id,'.png\"><img onError=\"this.style.display=\'none\';\" style=\"max-height: 70px;\" src=\"Image?no_crypt=1&i=induction_user_image/', users.id, '/user_',users.id,'.png\"></a>', 
    if(users.commencement_date = '0000-00-00', '', CONCAT('<br/>', DATE_FORMAT(users.commencement_date, '%d-%b-%Y')))) as `Photo/Start Date`,
    $status_xtra $xtra CONCAT(users.name, if(users.preferred_name != '', CONCAT(' (', users.preferred_name, ')'), ''), ' ', users.surname) as `Full Name`,
    CONCAT('<a href=\"mailto: ', users.email, '\">', users.email, '</a><br /><a href=\"mailto: ', users.email2, '\">', users.email2, '</a>') as `Email Addresses`,
    CONCAT('<span style=\"color: ', if(user_status.item_name = 'ACTIVE', '#0000CC', '#CC0000'), ';\">', user_status.item_name, '</span>', ' &nbsp; <a class=\"list_a\" href=\"JavaScript:user_action(''status'',', users.id, ', \'\')\">', if(user_status.item_name = 'ACTIVE', 'DE', ''), 'ACTIVATE</a>') as `status`,
    " . ($_SESSION['u_level'] >= 700 ? "CONCAT(users.phone, if(users.phone2, CONCAT('<br />', users.phone2), '')) as `Phone`," : "") . "
    $xtra2 states.item_name as `State` 
    $subqueryDivision   
    " . ($_SESSION['u_level'] >= 700 ? ", CONCAT('<nobr><a class=\"list_a\" href=\"Compliance?subject_id=', users.id, '\">For</a><a class=\"list_a\" href=\"Compliance?auditor_id=', users.id, '\">By</a></nobr>') as `Reports`" : "") . " $xtra3
    FROM users
    LEFT JOIN states ON states.id = users.state
    LEFT JOIN user_status ON user_status.id = users.user_status_id
    LEFT JOIN lookup_answers sla2 ON sla2.foreign_id = users.id AND sla2.lookup_field_id = 384 AND sla2.table_assoc = 'users'
    LEFT JOIN lookup_answers sla3 ON sla3.foreign_id = users.id AND sla3.lookup_field_id = 107 AND sla3.table_assoc = 'users'
    $divisionFilterSql
    $gid_xtra
    $usertype_xtra    
    $staff_xtra
    ".($gid ? "" : "$search")."
    $search_xtra2
    $srchFilterSql  
    $userLocationPermission  
    $userAllowedPermission     
    AND !(users.id IN (1, 2))
    AND users.user_maintype = 4
    AND users.user_status_id = 30
    GROUP BY users.id ORDER BY users." . ($latest_first ? "commencement_date DESC" : "name") . "
";



    
          $homeSrch = $_REQUEST['srch_home'];
          if($homeSrch){
              $sql = $view_details->sql;
              $result = $this->dbi->query($sql);
              $rkey = 0;
              while($myrow = $result->fetch_assoc()){
                    //prd($myrow);            
                    $searchResult[$rkey]['fullname'] = $myrow['Full Name'];
                    $rkey++;
              }
              $jsonSearchResult = json_encode($searchResult);
              echo $jsonSearchResult;
              die;
              //$result = $this->homeSearchResult();
          }
          
          
         
          $view_details->num_per_page = 100;
          $str .= '
        <script>
          function user_action(action, id) {
            var str_in
            if(action != "status") {
              str_in = document.getElementById(id + \'-7\').value
            }
    
            $.ajax({
              type:"get",
                  url:"' . $main_folder . 'UserAction",
                  data:{ user_id: id, action: action, str: str_in },
    
                  success:function(msg){
                    var str = msg
                    if(action == "status") {
                      str += \'&nbsp;<a class="list_a" href="JavaScript:user_action(\' + String.fromCharCode(39) + \'status\' + String.fromCharCode(39) + \', \' + id + \')">\'
                      if(msg == "ACTIVE") {
                        str += \'DE\'
                        document.getElementById(id+\'-7\').style.color = \'#0000CC\'
                      } else {
                        document.getElementById(id+\'-7\').style.color = \'#CC0000\'
                      }
                      str += \'ACTIVATE</a>\'
                      document.getElementById(id+\'-7\').innerHTML = str
                    } else if((action == "username" || action == "email" || action == "pw") && str) {
                        
                      document.getElementById("s" + id).innerHTML = str
                    }
                    
                  }
            }); 
          }
        </script>';
         if($userType == 107){
            $str .= '<div class="fr"><a class="list_a" href="javascript:void(0)" onClick="deleteSearchUser()"> Delete All </a></div> '.$view_details->draw_list();
         } else {
             $str .= $view_details->draw_list(); 
         }
          //$str = "<textarea>{$view_details->sql}</textarea>";
        }
        return $str;
      }
    }

    ?>
