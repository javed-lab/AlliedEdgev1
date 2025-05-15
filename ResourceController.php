<?php

class ResourceController extends Controller {
	/*
  protected $f3;
  function __construct($f3) {
    $this->f3 = $f3;
  }
  */
  
  function glob_i($string) {
    $result = '';
    for($i = 0, $len = strlen($string); $i < $len; ++$i) {
        if(ctype_alpha($string[$i])) {
            $result.='['.lcfirst($string[$i]).ucfirst($string[$i]).']';  // add 2-character pattern
        } else {
            $result .= $string[$i];  // add non-letter character
        }
    }
    return $result;  // return the prepared string
  }
  
  function check_folder_access($folder) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT distinct(lookup_fields.id) FROM `lookup_answers`
            left join lookup_fields on lookup_fields.id = lookup_answers.lookup_field_id
            left join resource_access on resource_access.id = lookup_answers.foreign_id
            where lookup_answers.table_assoc = 'resource_access'
            and resource_access.resource_name = '$folder'
            and lookup_fields.id in (" . implode(",", $this->f3->get('lids')) . ");";
            //$str .= "<p>$sql</p>";
    $result = $this->dbi->query($sql);
    
    return $result->num_rows;
  }
  
  function Show() {
    $search = (isset($_REQUEST['txtFind']) ? $_REQUEST['txtFind'] : null);
    $show_min = (isset($_GET['show_min']) ? $_GET['show_min'] : null);
    
		//File Icon Array
		$icons = array(
			".pdf" => "pdf-icon.png", "docx" => "word-icon.png", ".doc" => "word-icon.png", "xlsx" => "excel-icon.png", ".xls" => "excel-icon.png", ".mp3" => "mp3-icon.png",
			".zip" => "zip-icon.png", ".png" => "png-icon.png", ".jpg" => "jpg-icon.png", ".gif" => "gif-icon.png", ".ppt" => "powerpoint-icon.png", "pptx" => "powerpoint-icon.png",
			".PDF" => "pdf-icon.png", "DOCX" => "word-icon.png", ".DOC" => "word-icon.png", "XLSX" => "excel-icon.png", ".XLS" => "excel-icon.png", ".MP3" => "mp3-icon.png",
			".ZIP" => "zip-icon.png", ".PNG" => "png-icon.png", ".JPG" => "jpg-icon.png", ".GIF" => "gif-icon.png", ".PPT" => "powerpoint-icon.png", "PPTX" => "powerpoint-icon.png"
		);

    
    if($search) {
      $main_dir = $this->f3->get('download_folder') . "resources/";
      $dir = $main_dir . "*/*" . $this->glob_i($search) . "*";

      // Open a known directory, and proceed to read its contents
      $main_dir_len = strlen($main_dir);
      $str .= '<h3 class="fl">Resources</h3><p class="fr"><input placeholder="Search for files..." maxlength="100" name="txtFind" id="search" type="text" class="form-control"  style="padding: 2px !important; height: 28px !important;" value="'.$search.'" /><button onClick="this.form.submit()" name="cmdFollowSearch" value="Search" class="btn btn-default" style="margin-left: -4px;" /><i class="fa fa-search"></i></button></p><div class="cl"></div>';
      $test = "";
      $search_results .= '<table class="uk-table-condensed uk-table-hover uk-table-divider uk-table-middle uk-table-striped uk-table-small"><tr><th>&nbsp;</th><th>Name</th><th>Folder</th>';
      foreach(glob($dir) as $file) {
        //$str .= "filename: $file : filetype: " . filetype($file) . "<br />";
        //$parts1 = split(
        $test = substr($file, $main_dir_len);
        $parts = explode("/", $test);
        $folder = $parts[0];
        if($old_folder != $folder) {
          $allow_folder = $this->check_folder_access($folder);
        }
        //$allow_folder = 1;
        //$str .= "test: $allow_folder";
        
        if($allow_folder) {
        
          $file = $parts[1];
          $entry = $target_dir . $file;
          $ext = substr($file, (strlen($file)-4), 4);
          $file_show = substr($file, 0, (strlen($file)-(substr($ext, 0, 1) == "." ? 4 : 5)));
          //$str .= "<h3>Folder: $folder, File: $file";
          $search_results .= '<tr><td><img width="20" src="'.$this->f3->get('img_folder').$icons[$ext].'" /></td>';
          $search_results .= '<td><a href="'.$this->f3->get('main_folder').'DownloadFile?fl='.urlencode($this->encrypt($main_dir . $folder)).'&f='.urlencode($this->encrypt($file)).'">'.$file_show.'</a></td>';
          $search_results .= '<td><a href="?current_dir=' . $folder . ($show_min ? '&show_min='.$show_min : '') . '">'.$folder.'</a></td>';
          $search_results .= "</tr>";
        }
        $old_folder = $folder;
      }
      $str .= "</p>Results for: <i>$search</i></p>" . ($test ? $search_results : "No files found...");
      $str .= '</table><br /><br /><p><a class="list_a" href="' . ($show_min ? '?show_min='.$show_min : '') . '">&lt;&lt; Back to Resouces Main Page</a></p>';
    } else {

      $show_upload_form = (isset($_GET['show_upload_form']) ? $_GET['show_upload_form'] : null);


      $current_dir = (isset($_GET['current_dir']) ? $_GET['current_dir'] : ($this->f3->get('is_staff') ? "Forms" : "Client Access"));
      $current_subdir = (isset($_GET['current_subdir']) ? $_GET['current_subdir'] : NULL);

      if($_SESSION['u_level'] < 100 && ($current_dir != 'compliance' || !$current_subdir)) exit;

      $str .= '<input type="hidden" name="move_file" id="move_file" /><input type="hidden" name="directory_to" id="directory_to" />';

      //return $current_dir;

      if($current_dir == "user_files" && !$hr_user) {
        //$current_subdir = $_SESSION['user_id'];
      }


      $move_file = (isset($_POST['move_file']) ? $_POST['move_file'] : NULL);
      $directory_to = (isset($_POST['directory_to']) ? $_POST['directory_to'] : NULL);

      if($move_file || $directory_to) {
        $move_files = explode("/", $move_file);
        $file_move = $move_files[sizeof($move_files) - 1];
        $add_dir = "resources";
        $base_dir = $this->f3->get('download_folder') . "$add_dir/";
      }

      if($move_file && $directory_to) {
        rename($move_file, $base_dir . $directory_to . "/" . $file_move);
        $str .= "<h3>&quot;$file_move&quot; moved to &quot;$directory_to&quot;</h3><br /><br /><a class=\"list_a\" href=\"".$this->f3->get('main_folder')."Resources\">&lt;&lt; Back to Resources</a><br /><br /><br /><a class=\"list_a\" href=\"".$this->f3->get('main_folder')."Resources?current_dir=$directory_to&show_min=$show_min\">Go to $directory_to</a>";
      } else if($move_file) {
        $str .= "<h3>Move $file_move to:</h3>";
        $x = 0;
        $dir_list[] = Array();
        $dir = new DirectoryIterator($base_dir);
        foreach ($dir as $fileinfo) {
          if ($fileinfo->isDir() && !$fileinfo->isDot()) {
            $access_dir = $fileinfo->getFilename();
            $dir_list[$x] = $access_dir;
          }
          $x++;
        }
        sort($dir_list);
        foreach ($dir_list as $dir) {
          if($dir) {
            if($dir != $current_dir) {
              $dirs .= '<li><a class="list_a" href="JavaScript:move_file(\''.$move_file.'\', \''.$dir.'\')">' . $dir . '</a></li>';
            }
          }
        }
        $str .= "<ul>$dirs</ul>";

      } else {
        $access_mode = (isset($_GET['access_mode']) ? $_GET['access_mode'] : NULL);
        if($access_mode) {
          $input_obj = new input_item;
          $list_obj = new data_list;
          if(!isset($_GET['current_dir'])) $current_dir = "";
          $add_dir = "resources";
          $base_dir = $this->f3->get('download_folder') . "/$add_dir/";
          
          $x = 0;
          $dir_list[] = Array();
          $dir = new DirectoryIterator($base_dir);
          foreach ($dir as $fileinfo) {
            if ($fileinfo->isDir() && !$fileinfo->isDot()) {
              $dir_list[$x] = $fileinfo->getFilename();
            }
            $x++;
          }
          sort($dir_list);
          foreach ($dir_list as $dir) {
            if($dir) $this->dbi->query("insert ignore into resource_access (resource_name) values ('$dir');");
          }
          //$str .= $dirs;
          $list_obj->title = "Resource Access";
          $list_obj->sql = "select id as `idin`, id as `ID`, resource_name as `Folder`, 'Edit' as `*`, 'Delete' as `!` from resource_access";
          $editor_obj = new data_editor;
          $editor_obj->dbi = $this->dbi;
          $editor_obj->table = "resource_access";

          $editor_obj->form_attributes = array(
            array("chlAccess", "hdnName"),
            array("Access", "Name"),
            array("id", "resource_name"),
            array($this->get_chl('user_group'), ""),
            array("", ""),
            array("", ""),
            array("user_group", "")
          );

          $editor_obj->button_attributes = array(
            array("Add New", "Save", "Reset"),
            array("cmdAdd", "cmdSave", "cmdReset"),
            array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
            array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
          );
          $editor_obj->form_template = 'hdnName tchlAccess<br />chlAccess</div><div class="cl"></div>'.$editor_obj->button_list();
          $editor_obj->editor_template = '
                      <div class="form-wrapper" style="width: 100%;">
                      <div class="form-header">Resource Access</div>
                      <div class="form-content">editor_form</div>
                      editor_list
          ';
          $str .= $editor_obj->draw_data_editor($list_obj);

          
        } else {

          $img = "";
          $msg = "";
          $file_list = "";
          if($current_dir == "user_files") {
            $add_dir = "user_files";
            $sql = "select users.name, users.surname, states.item_name, users.state from users left join states on states.id = users.state where users.id = $current_subdir";
            $result = $this->dbi->query($sql);
            if($myrow = $result->fetch_assoc()) {
              $employee_id = $myrow['employee_id'];
              $name = $myrow['name'];
              $surname = $myrow['surname'];
              $state = $myrow['item_name'];
            }
            $head_title = "Files for $name $surname ($state).";
          } else if($current_dir == "course_notes") {
            $add_dir = "course_notes";
            $sql = "select code, name from courses where id = $current_subdir";
            $result = $this->dbi->query($sql);
            if($myrow = $result->fetch_assoc()) {
              $code = $myrow['code'];
              $name = $myrow['name'];
            }
            $head_title = "Course Notes for $name ($code).";
          } else if($current_dir == "compliance") {
            $add_dir = "compliance";
            $job_application_id = (isset($_GET['job_application_id']) ? $_GET['job_application_id'] : null);
            if($job_application_id) {
              $head_title = 'Attach Your <ol><li>Resume.</li><li>Cover Letter.</li><li>Appropriate Licences/Certificates.</li>';
            } else {
              $view_details = new data_list;
              //$view_details->mouse_over_color = MOUSE_OVER_COLOUR;
              $view_details->dbi = $this->dbi;
              if(strpos($current_subdir, "downloads") !== false) {
                $compliance_downloads = true;
                $components = explode("/", $current_subdir);
                $current_subdir = $components[1];
                $view_details->sql = "select title as `Title`, description as `Description` FROM compliance where id = $current_subdir;";
              } else {
                $view_details->sql = "
                       select 
                        compliance_checks.id as idin, compliance_checks.id as `ID`, CONCAT(compliance_checks.percent_complete, '%') as `% Done`, $title_str lookup_fields1.item_name as `Status`,
                        CONCAT('<nobr>', DATE_FORMAT(compliance_checks.check_date_time, '%d/%b/%Y'), '</nobr>') as `Date`,
                        CONCAT(users.employee_id, ' ', users.client_id, ' ', users.name, ' ', users.surname) as `Assessor`,
                        CONCAT(users2.employee_id, ' ', users2.client_id, ' ', users2.name, ' ', users2.surname) as `Subject`,
                        CONCAT('<a title=\"View Online\" class=\"list_a\" href=\"".$this->f3->get('main_folder')."Compliance?report_view_id=', compliance_checks.id, '\">View</a>', '<a title=\"View as PDF\" class=\"list_a\" href=\"".$this->f3->get('main_folder')."Compliance?pdfid=', compliance_checks.id, '\">PDF</a>') as `***`,
                        if(lookup_fields1.item_name = 'Pending' and users.id = " . $_SESSION['user_id'] . ",
                        CONCAT('<a title=\"Edit Checklist\" class=\"list_a\" href=\"".$this->f3->get('main_folder')."Compliance?report_edit_id=', compliance_checks.id, '\">Edit</a>'),
                        '<span class=\"list_a\"><strike>Edit</strike></span>')
                        as `**`
                        FROM compliance_checks
                        left join lookup_fields1 on lookup_fields1.id = compliance_checks.status_id
                        left join compliance on compliance.id = compliance_checks.compliance_id
                        left join users on users.id = compliance_checks.assessor_id
                        left join users2 on users2.id = compliance_checks.subject_id
                        where compliance_checks.id = $current_subdir
                ";
              }
              $head_title = ($compliance_downloads ? "Downloads" : 'Attachments');
            //$str .= "<textarea>$view_details->sql</textarea>";
            }

          } else {
            $add_dir = "resources";
            $head_title = "Resources";
          }

          //$head_title .= ($current_dir ? " - $current_dir" : "");
          $str .= '<h3 class="fl">' . $head_title . '</h3>';
          
          if($head_title == 'Resources') {
            $str .= '<p class="fr input-group custom-search-form"><input placeholder="Search for files..." maxlength="100" name="txtFind" id="search" type="text" class="form-control"  style="padding: 2px !important; height: 28px !important;" value="'.$search.'" /><button onClick="this.form.submit()" name="cmdFollowSearch" value="Search" class="btn btn-default" style="margin-left: -4px;" /><i class="fa fa-search"></i></button></p>'.($show_min && $_SESSION['page_access'] == 4 ? '<div class="fr" style="margin-right: 20px; margin-top: 4px;"><a class="list_a" target="_top" title="Click Here to Manage Files..." href="Resources'.($current_dir ? '?current_dir='.urlencode($current_dir) : '').'">File Manager</a></div>' : '');
          }
          $str .= '<div class="cl"></div>';
          $hide_list = ($compliance_downloads && !$show_upload_form ? 1 : 0);
          if($current_dir == "compliance" && !$job_application_id && !$hide_list) $str .= $view_details->draw_list() . "<br />";
          $base_dir = $this->f3->get('download_folder') . "$add_dir/";
          
          if($current_subdir) {
            $base_dir .= "$current_subdir/";
            if (!file_exists($base_dir)) {
//              return $base_dir;
              mkdir($base_dir);
              chmod($base_dir, 0755);
            }
            $target_dir = $base_dir;
            $download_dir .= "$current_dir/$current_subdir";
          } else {
            $target_dir = $base_dir . $current_dir . "/";
            $download_dir = "resources/" . $current_dir;
          }
          
          //$str .= $current_dir;
          if($target_dir) {

            if(($_SESSION['page_access'] == 4 || $current_dir == 'compliance') && (!$show_min || $show_upload_form)) {
              if(isset($_POST['del'])) { $del = $_POST['del']; } else { $del = ""; };

              //$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"][0]);
              //$target_file2 = $target_dir . basename($_FILES["fileToUpload"]["name"][1]);
              //$FileType = pathinfo($target_file,PATHINFO_EXTENSION);
              // Check if image file is a actual image or fake image
              if(isset($_POST["submit"])) {
                for($x = 0; $x < 4; $x++) {
                  $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"][$x]);
                  if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"][$x], $target_file)) {
                    $str .= "<p>The file ". basename( $_FILES["fileToUpload"]["name"][$x]). " has been uploaded.</p>";
                  } else {
                    if($_FILES['fileToUpload']['error'][$x] != 4) $str .= "<p>Sorry, there was an error uploading your file.</p>";
                  }
                }
              }

              if($del) {
                unlink($del);
                $msg .= "<h3>File Deleted...</h3>";
              } 
              $str .= '<input type="hidden" name="del" id="del" /></form>';
              $str .= '<form name="frmUpload" method="post" enctype="multipart/form-data" onSubmit="return check_exists(tfiles)">
                      <div class="fileUpload">
                      <h3><nobr>File 1<input type="file" class="upload" name="fileToUpload[]" id="fileToUpload"></nobr>
                      <nobr>File 2<input type="file" class="upload" name="fileToUpload[]" id="fileToUpload"></nobr>
                      <nobr>File 3<input type="file" class="upload" name="fileToUpload[]" id="fileToUpload"></nobr>
                      <nobr>File 4<input type="file" class="upload" name="fileToUpload[]" id="fileToUpload"></nobr>
                      <input type="submit" value="Upload File(s)" name="submit"></h3>
                      <div class="cl"></div>
                      </div>
                    </form><div class="cl"></div>';


              $str .= $msg  . '<div class="cl"></div>';
            }
            //$str .= $current_dir;
            if($current_dir != "user_files" && $current_dir != "course_notes") {

              $x = 0;
              $dir_list[] = Array();
              $dir = new DirectoryIterator($base_dir);
              foreach ($dir as $fileinfo) {
                if ($fileinfo->isDir() && !$fileinfo->isDot()) {
                  $access_dir = $fileinfo->getFilename();
                  //if($show_min) {
                    $sql = "SELECT distinct(lookup_fields.id) FROM `lookup_answers`
                            left join lookup_fields on lookup_fields.id = lookup_answers.lookup_field_id
                            left join resource_access on resource_access.id = lookup_answers.foreign_id
                            where lookup_answers.table_assoc = 'resource_access'
                            and resource_access.resource_name = '$access_dir'
                            and lookup_fields.id in (" . implode(",", $this->f3->get('lids')) . ");";
                    				//$str .= "<p>$sql</p>";
                    $result = $this->dbi->query($sql);
                    if($result->num_rows) {
                      $dir_list[$x] = $access_dir;
                    }
                  //} else {
                    //$dir_list[$x] = $access_dir;
                  //}
                }
                $x++;
              }
              sort($dir_list);
              
              foreach ($dir_list as $dir) $dirs .= ($dir ? '<a href="?current_dir=' . $dir . ($show_min ? '&show_min='.$show_min : '') . '" class="dir_item '.($dir == $current_dir ? 'dir_selected' : '').'">' . $dir . '</a>' : '');
            }
            $dir = new DirectoryIterator($target_dir);
            $x = 0;
            $file_list = Array();
            foreach ($dir as $fileinfo) {
              if (!$fileinfo->isDir() && !$fileinfo->isDot()) {
                $file_list[$x] = $fileinfo->getFilename() . ";*;" . gmdate("d-M-Y", $fileinfo->getMTime());
                $x++;
              }
            }
            
            $files .= '<table class="uk-table-condensed uk-table-hover uk-table-divider uk-table-middle uk-table-striped uk-table-small"><tr><th>&nbsp;</th><th>Name</th><th>Modified</th>';
            if(($_SESSION['page_access'] == 4 || $current_dir == 'compliance') && (!$show_min || $show_upload_form)) {
              $files .= '<th colspan="2">&nbsp;</th>';
            }
            $files .= '</tr>';
            if($x) {
              sort($file_list);
              foreach ($file_list as $file) {
                $comps = explode(";*;", $file);
                $file = $comps[0];
                if($file) {
                  $date_modified = $comps[1];
                  $entry = $target_dir . $file;
                  $ext = substr($file, (strlen($file)-4), 4);
                  $file_show = substr($file, 0, (strlen($file)-(substr($ext, 0, 1) == "." ? 4 : 5)));
                  if($_SESSION['page_access'] == 1 || $show_min) $list_item_read = "list_item_read";
                  $files .= '<tr><td><img width="20" src="'.$this->f3->get('img_folder').$icons[$ext].'" /></td>';
                  $files .= '<td><a href="'.$this->f3->get('main_folder').'DownloadFile?fl='.urlencode($this->encrypt($download_dir)).'&f='.urlencode($this->encrypt($file)).'">'.$file_show.'</a></td>';
                  $files .= "<td><pre style=\"display: inline;\">$date_modified</pre></td>";
                  if(($_SESSION['page_access'] == 4 || $current_dir == 'compliance') && (!$show_min || $show_upload_form)) {
                    $files .= '<td><img width="20" onClick="JavaScript:del_file(\''.$entry.'\')" src="'.$this->f3->get('img_folder').'delete-icon.png" /></td>';
                    if($current_dir != 'compliance' && $current_dir != 'user_files') $files .= '<td><a class="list_a" href="JavaScript:move_file(\''.$entry.'\')" />Move</a></td>';
                  }
                  $files .= "</tr>";
                  $js_files .= '"' . $file . '",';
                }
              }
            }
            $files .= "</table>";

            if(!$file) $files = "<i><b>No Files...</b></i>";
            $str .= "<div class=\"reverse_wrap\">$dirs</div><div class=\"dir_area\">$files</div>";
            
            $js_files = substr($js_files, 0, (strlen($js_files)-1));
            $str .= "<script>	var tfiles = [$js_files];</script>";
          }
          
          if($job_application_id) {
            $str .= '<br /><br /><input type="button" onClick="window.close();" value="&lt;&lt; Click Here to Return to Job Application" />';
          }
        } //endif access_mode
      } //endif move_file
    } //endif
		
		return $str;
  }
}

?>
