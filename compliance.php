<?php

class compliance extends notes {
  var $dbi;
  var $id;
  var $compliance_check_id;
  var $show_percent = 1;
  var $print_results = 0;
  var $hide_score;
  var $hide_attachments;
  var $title;
  var $str;
  var $score_completed_only;
  //Variable for the notes function
  var $show_notes = 1;

  function display_results() {
      
    $dbi = $this->dbi;
    if(!$hide_attachments) {
      //File Icon Array
      $icons = array(
        ".pdf" => "pdf-icon.png",
        "docx" => "word-icon.png",
        ".doc" => "word-icon.png",
        "xlsx" => "excel-icon.png",
        ".xls" => "excel-icon.pn",
        ".zip" => "zip-icon.png",
        ".png" => "png-icon.png",
        ".jpg" => "jpg-icon.png",
        ".gif" => "gif-icon.png"
      );
      $compliance_check_id = $this->compliance_check_id;
      $files = "<br /><br /><h3>Attachments</h3>";
      $target_dir = "/home/Edge/downloads/compliance/$compliance_check_id/";
      $download_dir = "compliance/$compliance_check_id/";
      if(file_exists($target_dir)) {
        $dir = new DirectoryIterator($target_dir);
        $x = 0;
        $file_list = [];
        foreach ($dir as $fileinfo) {
          if (!$fileinfo->isDir() && !$fileinfo->isDot()) {
            $file_list[$x] = $fileinfo->getFilename() . ";*;" . gmdate("d-M-Y", $fileinfo->getMTime());
            $x++;
          }
        }
        
        $files .= '<table class="uk-table-condensed uk-table-hover uk-table-divider uk-table-middle uk-table-striped uk-table-small"><tr><th>&nbsp;</th><th>Name</th><th>Modified</th>';
        $files .= '</tr>';
        sort($file_list);
        foreach ($file_list as $file) {
          $comps = explode(";*;", $file);
          $file = $comps[0];
          if($file) {
            $date_modified = $comps[1];
            $entry = $target_dir . $file;
            $ext = strtolower(substr($file, (strlen($file)-4), 4));
            $file_show = substr($file, 0, (strlen($file)-(substr($ext, 0, 1) == "." ? 4 : 5)));
            $files .= '<tr><td><img width="24px" src="/images/'.$icons[$ext].'" /></td>';
            $files .= '<td><a href="DownloadFile?fl='.urlencode($this->encrypt($download_dir)).'&f='.urlencode($this->encrypt($file)).'">'.$file_show.'</a></td>';
            $files .= "<td><pre style=\"display: inline;\">$date_modified</pre></td>";
            $files .= "</tr>";
          }
        }
        $files .= "</table>";
        
      //https://www.sctraining.com.au/elogin/DownloadFile?fl=uhvrxufhv2Irupv&f=frpsoldqfh#0#Frs|1{ov{

        $this->str .= ($file ? $files : "<p><i><b>No Files Attached...</b></i></p>");
      }
    }
    $sql = "select compliance_checks.total_out_of, compliance.hide_score, compliance.score_completed_only from compliance_checks left join compliance on compliance.id = compliance_checks.compliance_id where compliance_checks.id = $compliance_check_id";
    $result = $dbi->query($sql);
    if($myrow = $result->fetch_assoc()) {
      $this->hide_score = $myrow['hide_score'];
      $this->score_completed_only = $myrow['score_completed_only'];
      $total_out_of = ($this->score_completed_only ? 0 : round($myrow['total_out_of']));
    }

    $this->str .= "<br /><br /><h3>" . ($this->title ? $this->title : "Results") . "</h3>";
    $this->str .= '<table class="uk-table-condensed uk-table-hover uk-table-divider uk-table-middle uk-table-striped uk-table-small">
    <tr>
    <th>Question</th>
    <th>Answer</th>
    <th>Additional Text</th>
    ' . ($this->hide_score ? '' : '<th>Score</th>') . ' 
    </tr>
    ';


    $sql = "SELECT id, question_id, question, answer, answer_colour, additional_text, value, out_of, answer_id FROM `compliance_check_answers` WHERE compliance_check_id = $compliance_check_id";
    $result = $dbi->query($sql);
    $x = 0;
    $total_value = 0;
    $base_dir = "images/compliance/$compliance_check_id";
    while($myrow = $result->fetch_assoc()) {
      $x++;
      $aid = $myrow['id'];
      $answer_id = $myrow['answer_id'];
      $question_id = $myrow['question_id'];
      $question = $myrow['question'];
      $answer = nl2br($myrow['answer']);
      $answer_colour = $myrow['answer_colour'];
      $additional_text = nl2br($myrow['additional_text']);
      $value = $myrow['value'];
      $out_of = $myrow['out_of'];
      
      if($answer != "LBL") {
        $this->str .= "<tr><td ".($answer == "AS1gna7ure1ma6e" ? "colspan=\"" . ($this->hide_score ? "3" : "4") . '"' : "").">" . ($old_question == $question ? "&nbsp;" : $question) . "</td>";
        $postfix++;
      } else {
        //if($old_answer != "LBL" && !$this->score_completed_only) {
          if($old_answer == "LBL") $this->str .= "<tr><td colspan=\"" . ($this->hide_score ? "3" : "4") . "\"><i>No answers given for this section...</i></td>";
          $this->str .= "<tr><td style=\"background-color: #FFFFEE; font-weight: bold; border-bottom: 1px solid #AA0000;\" colspan=\"" . ($this->hide_score ? "3" : "4") . "\">$question</td>";
        //}
      }
      if(!$answer_id && $answer != "LBL") {

        if($answer == "AS1gna7ure1ma6e") {
          //$this->str .= "$base_dir/sig.png";
          $answer = '<img src="Image?i=' . urlencode($this->encrypt("$base_dir/sig.png")) . '">';
          $this->str .= "</tr><tr><td colspan=\"" . ($this->hide_score ? "3" : "4") . "\">$answer</td>";
        } else {
          $this->str .= "<td colspan=\"" . ($this->hide_score ? "2" : "3") . "\">$answer</td>";
        }
      } else {
        if($answer != "LBL") {
          $this->str .= "
          <td style=\"color: white; background-color: $answer_colour;\">$answer</td>
          <td>$additional_text</td>
        ";
        }
        if(!$this->hide_score) {
          if($value || $out_of) {
            $total_value += $value;
            $this->str .= "<td>$value/$out_of</td>";
            if($this->score_completed_only) $total_out_of += $out_of;
          } else {
            if($answer != "LBL") $this->str .= "<td>&nbsp;</td>";
          }
        }
      }
      $old_answer = $answer;
      $old_question = $question;
      $this->str .= "</tr>";
//      $target_dir = "uploads/compliance/$compliance_check_id/$question_id";
      $target_dir = $this->f3->get('download_folder') . "images/compliance/$compliance_check_id/$question_id";
      //$this->str .= "<h3>$target_dir</h3>";
      if(file_exists($target_dir)) {
        //require_once('Classes/rec_dir.class.php');
        $d = new RecDir("$target_dir/",true);
        $imgs = "";
        while (false !== ($entry = $d->read())) {
            $file_name = explode("/", $entry);
            $file_use = substr($entry, strlen($this->f3->get('download_folder')));
            $file_name = $file_name[count($file_name)-1];
            $imgs .= '<div class="fl d_image"><a target="_blank" href="Image?i=' . urlencode($this->encrypt($file_use)) . '"><img width="250" src="Image?i=' . urlencode($this->encrypt($file_use)) . '"></a></div>';
        }
        if($imgs) {
          $this->str .= '<tr><td colspan="' . ($this->hide_score ? "3" : "4") . '">';
          $this->str .= "<h3>Attached Images</h3>$imgs";
          $this->str .= "</tr>";
        }
        $d->close();
        $this->str .= '<div class="cl"></div>';
      }
    }
    if(!$this->hide_score) {
      if(($total_value || $total_out_of) && $this->show_percent) {
        $percent = ($total_value / $total_out_of) * 100;
    //    $this->str .= '<tr><td align="right" colspan="3">TOTAL: </td><td><nobr>'.round($total_value).'/'.round($total_out_of).' ('.round($percent).'%)</nobr></td></tr>';
        $this->str .= '<tr><td align="right" colspan="3">TOTAL: </td><td><nobr>'.round($percent).'%</nobr></td></tr>';
      }
    }
    
    $this->str .= "</table>";
    //$target_dir = "uploads/compliance/$compliance_check_id";
    $target_dir = "/home/Edge/downloads/images/compliance/$compliance_check_id";
    $imgs = "";
    if(file_exists($target_dir)) {
      //require_once('Classes/rec_dir.class.php');
      $dh  = opendir($target_dir);
      while (false !== ($filename = readdir($dh))) {
        if($filename !== '.' && $filename != '..' && is_dir($filename)) {
      //echo $target_dir.$filename;
          $sql = "select id from compliance_check_answers where compliance_check_id = $compliance_check_id and question_id = $filename;";
          $result = $dbi->query($sql);
          if(!$result->num_rows) {
            $testdir = "$target_dir/$filename";
            //$this->str .= $testdir . "<br />";
            $dh2 = opendir("$testdir/");
            while (false !== ($entry = readdir($dh2))) {
              if($entry !== '.' && $entry != '..') {
                //$this->str .= $testdir . $entry . "<br />";
                $sql = "select question_title from compliance_questions where id = $filename;";
                $result = $dbi->query($sql);
                if($myrow = $result->fetch_assoc()) {
                  $question = $myrow['question_title'];
                } else {
                  $question = "";
                }
                $imgs .= '<div class="fl d_image">'.$question.'<img width="250" src="Image?i=' . urlencode($this->encrypt("$testdir/$entry")) . '"></div>';
              }
              //$file_name = explode("/", $entry);
            }
          }
        }
      }
      if($imgs) $this->str .= "<h3>Appendix</h3>$imgs<div class=\"cl\"></div>";
    }
    if($this->print_results) echo $this->str;
    if($this->show_notes) {
      $this->notes_heading = "Notes";
      $this->id = $compliance_check_id;
      $this->table = "compliance_check_notes";
      $this->table_id_name = "compliance_check_id";
      $this->display_notes();
    }
  }
  
}



?>