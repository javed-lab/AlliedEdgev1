<?php
class JobApplications extends Controller {
  function is_dir_empty($dir) {
    if (!is_readable($dir)) return 1; 
    $handle = opendir($dir);
    while (false !== ($entry = readdir($handle))) {
      if ($entry != "." && $entry != "..") {
        return 0;
      }
    }
    return 1;
  }
  function MyJobApplications() {
    $msg = (isset($_GET['message']) ? $_GET['message'] : "");
    if($msg) {
      $str .= "<h3 class=\"message\">$msg</h3>";
    }
    $this->list_obj = new data_list;
    $this->list_obj->title = "My Job Applications";
    $this->list_obj->sql = "
          select job_applications.id as idin, CONCAT('<span style=\"font-weight: bold; color: ', job_application_status.colour, '\">', job_application_status.item_name, '<span>') as `Status`, 
          job_ads.title as `Job Advertisement`, 
          job_applications.date_applied as `Date Applied`
          from job_applications
          left join job_application_status on job_application_status.id = job_applications.status_id
          left join job_ads on job_ads.id = job_applications.job_ad_id
          where job_applications.user_id = ".$_SESSION['user_id']."
          order by job_applications.date_applied DESC
    ";
    $str .= $this->list_obj->draw_list();
    return $str;
  }
  function Show() {
    $this->list_obj = new data_list;
    
    //$this->list_obj->sql = "select ID, name as `Name`, surname as `Surname`, email as `Email`, pw as `Password` from users order by ID";
    //$str .= $this->list_obj->draw_list() . "<br /><br />";
    
    
    $selShowStat = (isset($_GET['selShowStat']) ? $_GET['selShowStat'] : 10);
    $selFilterJob = (isset($_GET['selFilterJob']) ? $_GET['selFilterJob'] : null);
    $more_info = (isset($_GET['more_info']) ? $_GET['more_info'] : null);
    $catid = (isset($_GET['catid']) ? $_GET['catid'] : 0);
    
    
    $curr_date_time = date('Y-m-d h:i:s');
    $search = (isset($_GET['txtFindApplicant']) ? $_GET['txtFindApplicant'] : null);
    $search_str = $search;
    if($_SERVER['QUERY_STRING'] && strpos($_SERVER['QUERY_STRING'], "more_info") === false) $_SESSION['query_string'] = $_SERVER['QUERY_STRING'];
    $sql = "select id, item_name, colour from job_application_status order by id";
    $result = $this->dbi->query($sql);
    $stat_count = 0;
    $stat_selections .= '<option value="1">---------- ALL ----------</option>';
    while($myrow = $result->fetch_assoc()) {
      $stat_str = "CONCAT('<input type=\"button\" class=\"cs_button\" style=\" font-size: 10pt !important; color: ".$myrow['colour']." !important;\" value=\"".$myrow['item_name']."\" onClick=\"change_status(', job_applications.id, ', ".$myrow['id'].", \'".$myrow['item_name']."\')\" />')";
      $stat_str2 .= "$stat_xtra $stat_str";
      $stat_xtra = ", ";
      $stat_selections .= '<option ' . ($selShowStat == $myrow['id'] ? "selected" : "") . ' value="' . $myrow['id'] . '">'.$myrow['item_name'].'</option>';
      $stat_count++;
    }
    $stat_str = $stat_str2;
    $show_change_status = ",  CONCAT('<nobr>', $stat_str, '</nobr>') as `Change Status`";
    $sql = "
    select '0' as `id`, '---------- SHOW ALL  ----------' as `title`
    UNION
    select '-2', '**************  Current  **************'
    UNION
    select id, title from job_ads
    where closing_date >= now()
    UNION
    select '-3', '**************  Archived  **************'
    UNION
    select id, title from job_ads
    where closing_date < now()
    ";
    $result = $this->dbi->query($sql);
    while($myrow = $result->fetch_assoc()) {
      $job_selections .= '<option ' . ($selFilterJob == $myrow['id'] ? "selected" : "") . ' value="' . $myrow['id'] . '">'.$myrow['title'].'</option>';
    }
    $hdnChangeItem = (isset($_REQUEST['hdnChangeItem']) ? $_REQUEST['hdnChangeItem'] : null);
    if($hdnChangeItem) {
      $hdnChangeStatus = (isset($_REQUEST['hdnChangeStatus']) ? $_REQUEST['hdnChangeStatus'] : null);
      $sql = "update job_applications set status_id = $hdnChangeStatus where id = " . $hdnChangeItem;
      $result = $this->dbi->query($sql);
      $str .= $this->message("Status Changed...", 3000);
    }
    if($more_info) {
      $txtNotes = (isset($_POST['txtNotes']) ? $this->dbi->real_escape_string($_POST['txtNotes']) : NULL);
      $send_email = (isset($_POST['hdnSendEmail']) ? $this->dbi->real_escape_string($_POST['hdnSendEmail']) : NULL);
      $status_change = (isset($_POST['hdnStatusChange']) ? $this->dbi->real_escape_string($_POST['hdnStatusChange']) : NULL);
      $txtEmail = (isset($_POST['txtEmail']) ? str_replace("\\r\\n", "\r\n", $this->dbi->real_escape_string(nl2br($_POST['txtEmail']))) : NULL);
//echo "$email_message || $send_email";
      $msg = "";
      if($txtEmail && $send_email) {
        $mail = new email_q($this->dbi);
        //$mail->AddAddress($send_email);
        $mail->AddAddress("eggaweb@gmail.com");
        $mail->Subject = "Job Application";
        $mail->Body    = $txtEmail;
        $mail->queue_message();
        //$mail->send();
        $msg = "Email Message Sent, Status Changed... ";
        $sql = "update job_applications set status_id = $status_change where id = $more_info";
        $result = $this->dbi->query($sql);
      }
      
      if(isset($_POST['txtNotes']) && !$hdnChangeItem) {
        $sql = "update job_applications set notes = '$txtNotes' where id = $more_info";
        $this->dbi->query($sql);
        $msg .= "Notes Saved...";
      }
      
      if($msg) $str .= $this->message($msg, 3000);
    }
    /*if(!$more_info) {
      $sql = "select id, item_name from states order by item_name";
      if($result = $this->dbi->query($sql)) {
        $done = 0;
        while($myrow = $result->fetch_assoc()) {
          $cid = $myrow['id'];
          $cat_name = $myrow['item_name'];
          if(!$done) {
            $str .= '<div class="category_item '.(!$catid ? 'category_selected' : '').'">
                     <a class="category_a" href="JobApplications?' . (isset($_GET['show_min']) ? "show_min=".$_GET['show_min']."&" : "") . '">ALL STATES</a></div>';
            $done = 1;
          }
          $str .= '<div class="category_item '.($cid == $catid ? 'category_selected' : '').'">
                   <a class="category_a" href="JobApplications?' . (isset($_GET['show_min']) ? "show_min=".$_GET['show_min']."&" : "") . 'catid='.$cid.'">'.$cat_name.'</a></div>';
        }
      }
      if($done) $str .= '<div class="cl" style="margin-bottom: 12px;"></div>';
    }*/
    $str .= '
      <script language="JavaScript">
        function change_status(change_itm, status_in, status_txt) {
          var confirmation;
          confirmation = "Are you sure about changing the status this record to "+status_txt+"?";
          if (confirm(confirmation)) {
            document.getElementById("hdnChangeItem").value = change_itm
            document.getElementById("hdnChangeStatus").value = status_in
            document.frmEdit.submit()
          }
        }
        function report_filter() {
          document.getElementById("hdnReportFilter").value=1
          document.frmFilter.submit()
        }
        function filter_by_name(name) {
          document.getElementById("txtFindApplicant").value = name
          report_filter()
        }
      </script>
      <input type="hidden" name="hdnChangeItem" id="hdnChangeItem" />
      <input type="hidden" name="hdnChangeStatus" id="hdnChangeStatus" />
    ';
    if(!$more_info) {
      $nav_month = (isset($_GET['selDateMonth']) ? $_GET['selDateMonth'] : -1);
      $nav_year = (isset($_GET['selDateYear']) ? $_GET['selDateYear'] : -1);
      $report_view_id = (isset($_GET['report_view_id']) ? $_GET['report_view_id'] : null);
      if(!$nav_month) {
        $def_month = 1;
        $nav_month = date("m");
        $nav_year = date("Y");
      }
      $nav = new navbar;
      $str .= '
          </form>
          <form method="GET" name="frmFilter">
          <input type="hidden" name="hdnReportFilter" id="hdnReportFilter" />
          ' . (isset($_GET['show_min']) ? '<input type="hidden" name="show_min" value="1" />' : "") . '
          <div class="form-wrapper">
          <div class="form-header">Filter</div>
          <div  style="padding: 10px;">
          '.$nav->month_year(2015).'
          <select style="width: 100px !important;" name="selShowStat">'.$stat_selections.'</select>
          <select style="width: 400px !important;" name="selFilterJob">'.$job_selections.'</select>
          <input maxlength="50" placeholder="Filter By Applicant Name..." name="txtFindApplicant" style="height: 23px;" id="txtFindApplicant" type="text" value="'.$search_str.'" />
          <input onClick="report_filter()" type="button" value="Go" /> 
          </div>
          </div>
          </form>
      ';
    }
    if($def_month) {
      $str .= '
        <script language="JavaScript">
          change_selDate()
        </script>
      ';
    }
    if($nav_month > 0) {
      $nav1 = " and MONTH(job_applications.date_applied) = $nav_month ";
    } else {
      $nav_month = "ALL Months";
    }
    if($nav_year > 0) {
      $nav2 = " and YEAR(job_applications.date_applied) = $nav_year ";
    } else {
      $nav_year = "ALL Years";
    }
    if (is_numeric($nav_month)) $for_month = $nav_month - 1;
    $for_year = $nav_year;
    if(!$for_month) {
      $for_month = 12;
      if(is_numeric($for_year)) $for_year--;
    }
    if($search) $search = "and CONCAT(trim(job_applications.name), ' ', trim(job_applications.surname)) LIKE '%$search%'";
    $this->list_obj->title = '<div class="fl">' . ($more_info ? "<div class=\"fl\">Applicant Details</div>" : "Job Applications") . '</div><div class="fr"><a class="list_a" href="#bottom">VV Bottom of Page</a></div><div class="cl"></div>';
    $xtra = ($more_info ? "" : ", CONCAT('<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."JobApplications?" . (isset($_GET['show_min']) ? "show_min=".$_GET['show_min']."&" : "") . "more_info=', job_applications.id, '\">More Info</a>') as `&nbsp;`");
    $this->list_obj->sql = "
          select job_applications.id as idin, states.item_name as `State`, CONCAT('<span style=\"font-weight: bold; color: ', job_application_status.colour, '\">', job_application_status.item_name, '<span>') as `Status`, 
          CONCAT('<nobr>" . ($more_info ? "" : "<a href=\"JavaScript:filter_by_name(\\'', job_applications.name, ' ', job_applications.surname, '\\')\" class=\"list_a\">Filter By Name</a>") . "<a href=\"mailto:', job_applications.email, '\">', job_applications.name, ' ', job_applications.surname, '</a></nobr>') as `Applicant`, job_applications.phone as `Phone`, job_ads.title as `Job Advertisement`,
          job_applications.date_applied as `Date Applied` $show_change_status $xtra,
          if(job_applications.notes != '', '<span style=\"color: green;\">YES</span>', '<span style=\"color: red;\">NO</span>') as `Emailed`
          from job_applications
          left join job_application_status on job_application_status.id = job_applications.status_id
          left join job_ads on job_ads.id = job_applications.job_ad_id
          left join states on states.id = job_ads.state_id
    ";
    

    //$cat_xtra = ;
    $this->list_obj->sql .= ($more_info ? " where job_applications.id = $more_info" :
                      " where 1 $nav1 $nav2 " . ($selShowStat > 1 ? " and job_applications.status_id = $selShowStat" : "") . ($selFilterJob > 0 ? " and job_ads.id = $selFilterJob" : "")
                    . ($catid ? " and job_ads.state_id = $catid" : "") . " $search order by job_application_status.id, job_applications.date_applied DESC");
    $str .= $this->list_obj->draw_list();
    //echo "<textarea>{$this->list_obj->sql}</textarea>";
    $str .= '<div class="cl"></div>';
    if($more_info) {
      $str .= '
        <input type="hidden" name="hdnSendEmail" id="hdnSendEmail" />
        <input type="hidden" name="hdnStatusChange" id="hdnStatusChange" />
        <script language="JavaScript">
          function save_notes() {
            document.frmEdit.submit();
          }
          function send_email() {
            document.getElementById("hdnSendEmail").value = 1
            document.frmEdit.submit();
          }
          function add_notes(txt, email, status) {
            document.getElementById("txtNotes").value += txt + "\\r\\n"
            email = email.replace(/<br ?\/?>/g, "\r\n");
            document.getElementById("txtEmail").value = email + "\\r\\n"
            document.getElementById("hdnStatusChange").value = status
          }
          function clear_notes() {
            document.getElementById("txtNotes").value = ""
          }
        </script>
      ';
      

      //File Icon Array
      $icons = array(
        ".pdf" => "pdf-icon.png", "docx" => "word-icon.png", ".doc" => "word-icon.png", "xlsx" => "excel-icon.png", ".xls" => "excel-icon.png", ".mp3" => "mp3-icon.png",
        ".zip" => "zip-icon.png", ".png" => "png-icon.png", ".jpg" => "jpg-icon.png", ".gif" => "gif-icon.png", ".ppt" => "powerpoint-icon.png", "pptx" => "powerpoint-icon.png",
        ".PDF" => "pdf-icon.png", "DOCX" => "word-icon.png", ".DOC" => "word-icon.png", "XLSX" => "excel-icon.png", ".XLS" => "excel-icon.png", ".MP3" => "mp3-icon.png",
        ".ZIP" => "zip-icon.png", ".PNG" => "png-icon.png", ".JPG" => "jpg-icon.png", ".GIF" => "gif-icon.png", ".PPT" => "powerpoint-icon.png", "PPTX" => "powerpoint-icon.png"
      );
      
      $sql = "select job_ads.id as `jaid`, CONCAT(job_applications.name, ' ', job_applications.surname) as `applicant`, users.username as `username`, job_applications.email, job_applications.phone, job_ads.title as `position`, job_applications.notes, job_applications.compliance_check_id
            from job_applications
            left join job_ads on job_ads.id = job_applications.job_ad_id
            left join users on users.id = job_applications.user_id
            where job_applications.id = $more_info
            ";
      $result = $this->dbi->query($sql);
      $sql = "select question, answer from job_application_answers where job_application_id = $more_info";
      $result2 = $this->dbi->query($sql);
      $str .= "<table class=\"grid2\">";
      while($myrow2 = $result2->fetch_assoc()) {
        $str .= "<tr><th style=\"vertical-align: top; width: 30%;" . ($myrow2['answer'] == 'LBL' ? ' font-weight: bold;" colspan="2"' : '"') . ">{$myrow2['question']}</th>" . ($myrow2['answer'] == 'LBL' ? "" : "<td style=\"vertical-align: top;\">{$myrow2['answer']}&nbsp;</td>") . "</tr>";
      }
      $sql = "select compliance_check_id from job_applications where id = $more_info;";
      if($result2 = $this->dbi->query($sql)) {
        if($myrow2 = $result2->fetch_assoc()) $compliance_check_id = $myrow2['compliance_check_id'];
      }
      
      $target_dir = ($compliance_check_id ? $this->f3->get('download_folder') . "compliance/$compliance_check_id/" : $this->f3->get('upload_folder') . "cv/$more_info/");
      if(file_exists($target_dir)) {
        $dir = new DirectoryIterator($target_dir);
        $x = 0;
        $file_list[] = Array();
        foreach ($dir as $fileinfo) {
          if (!$fileinfo->isDir() && !$fileinfo->isDot()) {
            $file_list[$x] = $fileinfo->getFilename() . ";*;" . gmdate("d-M-Y", $fileinfo->getMTime());
            $x++;
          }
        }
        if($x) {
          sort($file_list);
          $x = 0;
          //echo count($file_list);
          //print_r($file_list);
          //exit;
          foreach ($file_list as $file) {
            $comps = explode(";*;", $file);
            $file = $comps[0];
            if($file) {
              $x++;
              $date_modified = $comps[1];
              $entry = $target_dir . $file;
              $ext = substr($file, (strlen($file)-4), 4);
              $file_show = substr($file, 0, (strlen($file)-(substr($ext, 0, 1) == "." ? 4 : 5)));
              $str .= '<tr><th>Attachment ' . $x . '</th><td><img width="20" src="'.$this->f3->get('img_folder').$icons[$ext].'" />
                         <a href="'.$this->f3->get('main_folder').'DownloadFile?fl='.urlencode($this->encrypt(($compliance_check_id ? $this->f3->get('download_folder') . "compliance/$compliance_check_id/" : $this->f3->get('upload_folder') . "cv/$more_info/"))).'&f='.urlencode($this->encrypt($file)).'">'.$file_show.'</a></td></tr>';
            }
          }
        }
      }
      $str .= "</table>";
      $str .= '<br /><a class="list_a" href="' . $this->f3->get('main_folder') . 'JobApplications?'.$_SESSION['query_string'].'">&lt;&lt; Back to Job Applications...</a> | ';
      while($myrow = $result->fetch_assoc()) {
        $name = $myrow['applicant'];
        $username = $myrow['username'];
        $email = $myrow['email'];
        $notes = $myrow['notes'];
        $position = $myrow['position'];
        $url = $this->f3->get('full_url')."Login?name=" . urlencode($name) . "&page_from=".urlencode($this->f3->get('main_folder'))."MyJobApplications";

        

        $sql = "select title, description, status_change_id from job_letters";
        $result2 = $this->dbi->query($sql);
        while($myrow2 = $result2->fetch_assoc()) {
          $title = $myrow2['title'];
          $status_change_id = $myrow2['status_change_id'];
          $description = strip_tags($myrow2['description']);
          $description = str_replace('[Name]', $name, $description);
          $description = str_replace('[Position]', $position, $description);
          $description = str_replace('[URL]', $url, $description);
          $description = str_replace('[Username]', $username, $description);
//          $str .= '<a onClick="add_notes(\'LETTER SENT ('.Date('d-M-Y').'): ' . $title . '\')" class="list_a" href="mailto:'.$email.'?subject=Job%20Application%20for%20' . rawurlencode($position) . '&body=' . rawurlencode($description) . '">' . $title . '</a>';
          $str .= '<a class="list_a" href="JavaScript:add_notes(\'LETTER SENT ('.Date('d-M-Y').'): ' . $title . '\', \'' . nl2br($description) . '\', \'' . $status_change_id . '\')">' . $title . '</a>';
        }
      }  
      $str .= '<div class="cl"></div><div style="width: 98%;"><h3 class="fl">Email</h3><div class="fr"><input type="button" onClick="send_email()" value="Send Email" /></div></div><div class="cl"></div><textarea name="txtEmail" id="txtEmail" placeholder="Email to Send..." style="width: 98%; height: 240px;"></textarea>
               <div style="width: 98%;"><h3 class="fl">Notes</h3><div class="fr"><input type="button" onClick="send_email()" value="Send Email" /></div></div><div class="cl"></div><textarea name="txtNotes" id="txtNotes" placeholder="Enter Notes Here..." style="width: 98%; height: 100px;">'.$notes.'</textarea><br />
               <input class="list_a" onClick="save_notes()" type="button" value="Save Notes"><input class="list_a" onClick="clear_notes()" type="button" value="Clear Notes"></p>';


      $str .= '<br /><br /><a class="list_a" href="' . $this->f3->get('main_folder') . 'JobApplications?'.$_SESSION['query_string'].'">&lt;&lt; Back to Job Applications...</a>';
    }
    $str .= '
    <a name="bottom"></a>
    <div class="fr"><a class="list_a" href="#top">^^ Top of Page</a></div><div class="cl"></div>
    ';
    return $str;
  }
}