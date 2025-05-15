<?php
class EquipmentIssue extends Controller {
  function Show() {
    $this->list_obj = new data_list;
    $this->editor_obj = new data_editor;
    $str .= '
      <style>
      .rep {
        background-image: url("images/report.gif");
      }
      .back {
        background-image: url("images/home.gif");
      }
      .dl {
        background-image: url("images/download.gif");
        margin-right: 5px;
      }
      </style>';
    $lookup_id = $_GET['lookup_id'];
    $report_mode = $_GET['report_mode'];
    $reissue_id = $_GET['reissue_id'];
    $transfer_id = $_GET['transfer_id'];
    $transfer_to_id = $_GET['transfer_to_id'];
    $quantity = $_GET['txtQuantity'];
    $comment = $_GET['txtTransferComment'];
    $quantity_from = $_GET['hdnQuantityFrom'];
    if(isset($_GET['staff_search'])) $search = $_GET['staff_search'];
    $report_str = '<div class="fr"><a class="list_a" href="?report_mode=1'.(isset($_GET['show_min']) ? "&show_min=1" : "").'">Report Mode</a></div>';
    //$help_str = '<div class="fr"><a class="download_link rep dl" href="DownloadFile?fl='.urlencode($this->encrypt("resources/Guides")).'&f='.urlencode($this->encrypt("Equipment Issue Register.pdf")).'">Download User Guide</a></div><div class="cl"></div>';
    if(!$lookup_id && $report_mode != 1) {
      $str .= '
      </form>
      <form method="get" name="frmFollowSearch">
      <div class="fl"><h3>Find Staff Member for Equipment Issue</h3>
      <input maxlength="50" name="staff_search" id="search" type="text" class="search_box" value="' . $_GET['staff_search'] . '" /><input type="submit" onClick="perform_search(\'' . $search_page . '\')" name="cmdFollowSearch" value="Search" class="search_button" /> ' . $search_msg . '
      </div>
      ' . $report_str . $help_str;
      if($search) {
        $search = "
          where (users.name LIKE '%$search%'
          or users.surname LIKE '%$search%'
          or users.email LIKE '%$search%'
          or users.employee_id LIKE '%$search%'
          or CONCAT(users.name, ' ', users.surname) LIKE '%$search%')
          and users.user_status_id = (select id from user_status where item_name = 'ACTIVE')
        ";
        $sql = "
          SELECT users.id as `idin`, employee_id, student_id, client_id, supplier_id, CONCAT(users.name, ' ', users.surname) as `user_name`,
                 CONCAT('<a href=\"".$this->f3->get('main_folder')."mailto: ', users.email, '\">', users.email, '</a>') as `email`,
                 states.item_name as `state`
                 FROM users
                 left join states on states.id = users.state
                 left join user_status on user_status.id = users.user_status_id
                 $search
          ";
        $result = $this->dbi->query($sql);
          $show_first = 1;
        while($myrow = $result->fetch_assoc()) {
          if($show_first) {
            $str .= '<div class="cl"></div><h3>Search Results</h3>
                  <table class="grid" >
                  <tr><th class="grid" align="left"><nobr>Emp ID</nobr></th>
				  <th class="grid" align="left"><nobr>Full Name</nobr></th>
				  <th class="grid" align="left"><nobr>Email</nobr></th>
				  <th class="grid" align="left"><nobr>State</nobr></th>
				  <th class="grid" align="left"><nobr>Issue</nobr></th></tr>';
          }
          $idin = $myrow['idin'];
          $employee_id = $myrow['employee_id'];
          $user_name = $myrow['user_name'];
          $email = $myrow['email'];
          $state = $myrow['state'];
          $setup_str = "<td valign=\"top\"><a class=\"list_a\" href=\"".$this->f3->get('main_folder')."EquipmentIssue?lookup_id=$idin".(isset($_GET['show_min']) ? "&show_min=1" : "")."\">Equipment Issue</a></a></td>";
          $str .= '<tr>
					<td valign="top">'.$employee_id.'&nbsp;</td>
					<td valign="top">'.$user_name.'</td>
					<td valign="top">'.$email.'</td>
					<td valign="top">'.$state.'</td>
					'.$setup_str.'
					</tr>
					';
          $show_first = 0;
        }
        $str .= "</table>";
      }
    }
    if($lookup_id) {
      $sql = "select employee_id, name, surname from users where id = $lookup_id";
      $result = $this->dbi->query($sql);
      if($myrow = $result->fetch_assoc()) {
        $employee_id = $myrow['employee_id'];
        $name = $myrow['name'];
        $surname = $myrow['surname'];
      }
    }
    if($transfer_id) {
      if(!$quantity || $quantity >= $quantity_from) {
        $sql = "update equipment_issue set issued_to_id = $transfer_to_id, updated_date = now(), issued_by_id = " . $_SESSION['user_id'] . " where id = $transfer_id";
        $this->dbi->query($sql);
      } else {
      $sql = "update equipment_issue set qty_issued = (qty_issued - $quantity), updated_date = now() where id = $transfer_id";
            $this->dbi->query($sql);
        $sql = "insert into equipment_issue (category_id, issued_by_id, issued_to_id, status_id, updated_date, date_issued, qty_issued, comments)
                (select category_id, '" . $_SESSION['user_id'] . "', '$transfer_to_id', status_id, now(), now(), '$quantity', '$comment' from equipment_issue where id = $transfer_id);";
        $this->dbi->query($sql);
      }
      $sql = "select employee_id, name, surname from users where id = $transfer_to_id";
      $result = $this->dbi->query($sql);
      if($myrow = $result->fetch_assoc()) {
        $to_employee_id = $myrow['employee_id'];
        $to_name = $myrow['name'];
        $to_surname = $myrow['surname'];
      }
    }
    if($lookup_id) {
      $j1 = "";
      $j2 = "";
      $r1 = ", CONCAT('<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."EquipmentIssue?lookup_id=$lookup_id&reissue_id=', equipment_issue.id, '".(isset($_GET['show_min']) ? "&show_min=1" : "")."\">Reissue</a>') as `Reissue`";
    } else {
      $list_xtra = "1";
      $r1 = ", CONCAT('<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."EquipmentIssue?lookup_id=', users2.id ,'&reissue_id=', equipment_issue.id, '".(isset($_GET['show_min']) ? "&show_min=1" : "")."\">Reissue</a>') as `Reissue`";
      $filter_string = "filter_string";
    }
    $j1 = ", CONCAT(users2.name, ' ', users2.surname) as `Issued To`";
    $j2 = " left join users2 on users2.id = equipment_issue.issued_to_id ";
    if($reissue_id) {
      $list_xtra = " equipment_issue.id = $reissue_id ";
    } else {
      if($lookup_id) $list_xtra = " equipment_issue.issued_to_id = $lookup_id ";
      $list_items = ", equipment_issue.return_due_date as `Return Due Date`, equipment_issue.return_date as `Return Date`, equipment_issue.comments as `Comments` $r1, 'Edit' as `*`, 'Delete' as `!`";
    }
    $this->list_obj->sql = "
          select equipment_issue.id as idin, lookup_fields1.item_name as `Item`, lookup_fields2.item_name as `Status`, equipment_issue.updated_date as `Updated On`, CONCAT(users.name, ' ', users.surname) as `Issued By` $j1,
          equipment_issue.qty_issued as `Qty`,
          equipment_issue.date_issued as `Issue Date` $list_items
          FROM equipment_issue
          left join lookup_fields1 on lookup_fields1.id = equipment_issue.category_id
          left join lookup_fields2 on lookup_fields2.id = equipment_issue.status_id
          left join users on users.id = equipment_issue.issued_by_id
          $j2
          where $list_xtra $filter_string
          order by equipment_issue.status_id, equipment_issue.date_issued DESC
      ";
    if($transfer_id) $str .= "<br /><h5>Equipment Item Issued to $to_employee_id $to_name $to_surname [<a href=\"".$this->f3->get('main_folder')."EquipmentIssue?lookup_id=$transfer_to_id".(isset($_GET['show_min']) ? "&show_min=1" : "")."\">View Equipment Issue for $to_name</a>]</h5></br />";
    if($reissue_id) {
      $str .= "<h3>Equipment Issue for $employee_id $name $surname</h3>";
    }
    if($reissue_id) {
      if(isset($_GET['transfer_search'])) $search = $_GET['transfer_search'];
      $search_str = $search;
      $this->list_obj->show_num_records = 0;
      $str .= $this->list_obj->draw_list();
      $str .= '
      </form>
      <form method="get" name="frmFollowSearch">
      <input type="hidden" name="lookup_id" id="lookup_id" value="' . $lookup_id . '" />
      <input type="hidden" name="reissue_id" id="reissue_id" value="' . $reissue_id . '" />
      <input type="hidden" name="transfer_to_id" id="transfer_to_id" />
      <input type="hidden" name="transfer_id" id="transfer_id" />
      <div class="fl"><h3>Find Staff Member for Item Transfer</h3>
      <input maxlength="50" name="transfer_search" id="search" type="text" class="search_box" value="' . $_GET['transfer_search'] . '" /><input type="submit" name="cmdFollowSearch" value="Search" class="search_button" /> ' . $search_msg . '
      </div>
      <div class="cl"></div>';
      if($search) {
        $search = "
          where (users.name LIKE '%$search%'
          or users.surname LIKE '%$search%'
          or users.email LIKE '%$search%'
          or users.employee_id LIKE '%$search%'
          or CONCAT(users.name, ' ', users.surname) LIKE '%$search%')
          and users.user_status_id = (select id from user_status where item_name = 'ACTIVE')
        ";
        $sql = "
          SELECT users.id as `idin`, employee_id, student_id, client_id, supplier_id, CONCAT(users.name, ' ', users.surname) as `user_name`,
                 CONCAT('<a href=\"".$this->f3->get('main_folder')."mailto: ', users.email, '\">', users.email, '</a>') as `email`,
                 states.item_name as `state`
                 FROM users
                 left join states on states.id = users.state
                 left join user_status on user_status.id = users.user_status_id
                 $search
          ";
        $result = $this->dbi->query($sql);
          $show_first = 1;
        while($myrow = $result->fetch_assoc()) {
          $sql = "select qty_issued from equipment_issue where id = $reissue_id;";
          $result2 = $this->dbi->query($sql);
          if($myrow2 = $result2->fetch_assoc()) {
            $qty_issued = $myrow2['qty_issued'];
          }
          if($show_first) {
            $str .= '<input type="hidden" name="hdnQuantityFrom" value="'.$qty_issued.'" /><br /><br />';
            $str .= '<div class="fl" style="padding-right: 5px;">
                  <b>Qty</b><br />
                  <input value="1" type="number" onkeyup="limit_number('.$qty_issued.')" onclick="limit_number('.$qty_issued.')" min="0" max="'.$qty_issued.'" name="txtQuantity" id="txtQuantity" style="color: #222222;  background-color: white;  border: 1px solid #999999;  padding: 6px 2px 6px 2px;  font-size: 12pt;  height: 27px; width: 100px;" /><br />
                  </div>
                  <div class="fl">
                  <b>Comment</b><br /><input name="txtTransferComment" id="txtTransferComment" type="text" style="width: 900px;" /><br />
                  </div>
                  <div class="cl"></div>
                  <h3>Search Results</h3>
                  <table class="grid" >
                  <tr><th class="grid" align="left"><nobr>Emp ID</nobr></th><th class="grid" align="left"><nobr>Full Name</nobr></th><th class="grid" align="left">
                  <nobr>Email</nobr></th><th class="grid" align="left"><nobr>State</nobr></th><th class="grid" align="left"><nobr>Transfer</nobr></th></tr>';
          }
          $idin = $myrow['idin'];
          $employee_id = $myrow['employee_id'];
          $client_id = $myrow['client_id'];
          $student_id = $myrow['student_id'];
          $supplier_id = $myrow['supplier_id'];
          $user_name = $myrow['user_name'];
          $email = $myrow['email'];
          $state = $myrow['state'];
          $setup_str = "<td valign=\"top\"><input type=\"button\" value=\"Transfer to $user_name\" onClick=\"transfer_item($reissue_id, $lookup_id, $reissue_id, $idin)\"></td>";
          $str .= '<tr><td valign="top">'.$employee_id.'</td><td valign="top">'.$user_name.'<br /><a class="list_a" href="?lookup_id='.$idin.'">Equipment Issue</a></a></td><td valign="top">'.$email.'</td><td valign="top">'.$state.'</td>'.$setup_str.'</tr>';
          $show_first = 0;
        }
        $str .= "</table>";
      }
      $str .= '
      <script>
      function transfer_item(transfer_id, lookup_id, reissue_id, transfer_to_id) {
      //alert(transfer_to_id)
        document.getElementById("lookup_id").value = lookup_id;
        document.getElementById("reissue_id").value = reissue_id;
        document.getElementById("transfer_to_id").value = transfer_to_id;
        document.getElementById("transfer_id").value = transfer_id;
        //alert(document.getElementById("transfer_to_id").value)
        document.frmFollowSearch.submit();
      }
      function limit_number(num) {
        if(document.getElementById("txtQuantity").value > num) {
          document.getElementById("txtQuantity").value = num
        }
        if(document.getElementById("txtQuantity").value < 1) {
          document.getElementById("txtQuantity").value = 1
        }
      }
      </script>';
    } else if($report_mode || $lookup_id) {
      $this->list_obj->show_num_records = 1;
      $this->editor_obj->add_now = "updated_date";
      $this->editor_obj->update_now = "updated_date";
      $this->editor_obj->custom_field = "issued_by_id";
      $this->editor_obj->custom_value = $_SESSION['user_id'];
      $this->editor_obj->xtra_id_name = "issued_to_id";
      $this->editor_obj->xtra_id = $lookup_id;
      $list_top = "select * from (select 0 as id, -10000 as `sort_order`, '--- Select ---' as item_name union all";
      $this->editor_obj->table = "equipment_issue";
      $style = 'style="width: 220px;"';
      $style_small = 'style="width: 130px;"';
      $style_notes = 'style="width: 700px; height: 120px;"';
      $this->editor_obj->form_attributes = array(
               array("selCategory", "selIssueStatus", "txtQuantityIssued", "txaComments", "calDateIssued", "calReturnDueDate", "calReturnDate"),
               array("Item", "Status", "Qty Issued", "Comments", "Date Issued", "Return Due Date", "Return Date"),
               array("category_id", "status_id", "qty_issued", "comments", "date_issued", "return_due_date", "return_date"),
               array($this->get_lookup('equipment_category'), $this->get_lookup('equipment_issue_status'), "", "", "", "", "", ""),
               array('', $style_small, $style_small, $style_notes, $style_small, $style_small, $style_small),
               array("c", "n", "n", "n", "n", "n"),
               array("", "", "", "", Date("d-M-Y"), ""),
               array("", "", "", "", "", "")
      );
      if($lookup_id) {
        $this->editor_obj->button_attributes = array(
          array("Add New", "Save", "Reset", "Filter"),
          array("cmdAdd", "cmdSave", "cmdReset", "cmdFilter"),
          array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();", "filter()"),
          array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit", "js_function_filter")
        );
      } else {
        $this->editor_obj->button_attributes = array(
          array("Save", "Reset", "Filter"),
          array("cmdSave", "cmdReset", "cmdFilter"),
          array("if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();", "filter()"),
          array("js_function_save", "js_function_delete", "js_function_edit", "js_function_filter")
        );
      }
      $this->editor_obj->form_template = '
                <div style="float: left;"><nobr>tselCategory [<a tabindex="-1" target="_blank" href="Edit/LookupItems?lookup_id=98">Edit</a>]</nobr><br />selCategory</div>
                <div style="float: left;"><nobr>tselIssueStatus [<a tabindex="-1" target="_blank" href="Edit/LookupItems?lookup_id=96">Edit</a>]</nobr><br />selIssueStatus</div>
                <div style="float: left;"><nobr>ttxtQuantityIssued</nobr><br />txtQuantityIssued</div>
                <div style="float: left;"><nobr>tcalDateIssued</nobr><br />calDateIssued</div>
                <div style="float: left;"><nobr>tcalReturnDueDate</nobr><br />calReturnDueDate</div>
                <div style="float: left;"><nobr>tcalReturnDate</nobr><br />calReturnDate</div>
                <div style="clear: both;"></div>
                <div style="float: left;"><nobr>ttxaComments</nobr><br />txaComments</div>
                <div style="clear: both;"></div>
                '.$this->editor_obj->button_list();
      if($report_mode) {
        $rep_xtra = "(Report Mode)";
        $report_str = '<div class="fr"><a class="list_a" href="EquipmentIssue">Back</a></div>';
      } else if($lookup_id) {
        $rep_xtra = "for $employee_id $name $surname";
      }
      $this->editor_obj->editor_template = '
                  <div class="fl"><table border="0">
                  <tr>
                  <td valign="top">
                  <table class="standard_form">
                  <tr><td class="form_header">Equipment Issue '.$rep_xtra.'</td>
                  </tr>
                  <tr><td>editor_form</td></tr>
                  </table>
                  </td>
                  <td valign="top">
                  <div style="padding-left: 10px; padding-right: 10px; padding-top: 0px; padding-bottom: 0px;">'.$page_content.'</div>
                  </td>
                  </tr>
                  </table>
                  </div>
                  '.$report_str.$help_str.'
                  editor_list
      ';
      $str .= $this->editor_obj->draw_data_editor($this->list_obj);
      $str .= "<script>\nif(!document.frmEdit.selIssueStatus.selectedIndex) document.frmEdit.selIssueStatus.selectedIndex = 1;\n</script>";
    }

    return $str;
  }
}
    