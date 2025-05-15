<?php

class ConvertF3 extends Controller {
  function Show() {
    $str .= '
            </form>
            <form method="post" action="'.$this->f3->get('main_folder').'ConvertF3" enctype="multipart/form-data">
            <input type="hidden" name="MAX_FILE_SIZE" value="10000000" />
            <label for="thefile">Filename:</label> <input type="file" name="thefile" id="thefile"> <input type="submit" name="submit" value="Submit">';
    $edit = new input_item;
    $str .= $edit->chk("chkEdit", "Edit Form", "", "", "", "", "");
    $str .= $edit->chk("chkKeepComments", "Keep Comments", "", "", "", "", "");
    $is_edit = $_POST['chkEdit'];
    $keep_comments = $_POST['chkKeepComments'];
    $str .= '</form>';
    if ($_FILES["thefile"]["error"] > 0) {
      $str = "Return Code: " . $_FILES["thefile"]["error"] . "<br>";
    } else {
      if($file_name = $_FILES["thefile"]["name"]) {
        $func_name = ucwords(str_replace("_", " ", $file_name));
        $func_name = str_replace(" ", "", $func_name);
        $func_name = str_replace(".php", "", $func_name);
        move_uploaded_file($_FILES["thefile"]["tmp_name"], "/home/Edge/uploads/" . $_FILES["thefile"]["name"]);
        $inputFileName = "/home/Edge/uploads/" . urlencode($_FILES["thefile"]["name"]);
        $file_content = file_get_contents($inputFileName);
        $file_content = str_replace('', '', $file_content);
        $file_content = str_replace('', '', $file_content);



        if(!$keep_comments) {
          $file_content = str_replace('//', "//", $file_content);
          $file_content = str_replace('//', "//", $file_content);
          $file_content = str_replace('//', "//", $file_content);
          $file_content = str_replace('//', "//", $file_content);
          $file_content = str_replace('//', "//", $file_content);
          $file_content = str_replace('//', "//", $file_content);
          $file_content = str_replace('//', "//", $file_content);
          $file_content = str_replace('//', "//", $file_content);
          $commentTokens = array(T_COMMENT);
          $commentTokens[] = T_DOC_COMMENT;
          $tokens = token_get_all($file_content);
          foreach ($tokens as $token) {    
            if (is_array($token)) {
              if (in_array($token[0], $commentTokens))
                continue;
              $token = $token[1];
            }
            $content .= $token;
          }
        } else {
          $content = $file_content;
        }
        
        $content = str_replace('require_once("bottom.php");', "", $content);
        $content = str_replace('include("bottom.php");', "", $content);
        $content = str_replace('include("top.php");', "", $content);
        $content = str_replace('require_once("top.php");', "", $content);
        $content = str_replace("include('db_connect.php');", "", $content);
        $content = str_replace('include("db_connect.php");', "", $content);
        $content = str_replace('echo $str;', 'return $str;', $content);
        $content = strrev(preg_replace("/>\?/", "}", strrev($content),1));  //Replacing the last occurrance of closing php
        $content = str_replace('$dbi', '$this->dbi', $content);
        $content = str_replace('$list_obj', '$this->list_obj', $content);
        $content = str_replace('$editor_obj', '$this->editor_obj', $content);
        $content = str_replace('get_chl(', '$this->get_chl(', $content);
        $content = str_replace('get_lookup(', '$this->get_lookup(', $content);
        $content = str_replace('get_blank_lookup(', '$this->get_blank_lookup(', $content);
        $content = str_replace('get_simple_lookup(', '$this->get_simple_lookup(', $content);
        $content = str_replace('standard_date(', '$this->standard_date(', $content);
        $content = str_replace('$this->editor_obj->dbi = $this->dbi;', '', $content);
        $content = str_replace('echo $this->editor_obj->draw_data_editor($this->list_obj);', 'return $this->editor_obj->draw_data_editor($this->list_obj);', $content);
        $content = str_replace('href=\"', 'href=\"".$this->f3->get(\'main_folder\')."', $content);
        $content = str_replace('$this->editor_obj = new data_editor;', '', $content);
        $content = str_replace('echo ', '$str .= ', $content);
        $content = str_replace('mysql_escape_string(', 'mysqli_real_escape_string($this->dbi, ', $content);
        $content = str_replace('mesc(', 'mysqli_real_escape_string($this->dbi, ', $content);
        $content = str_replace('message(', '$this->message(', $content);
        $content = str_replace("<?php \$str .= ", "' . ", $content);
        $content = str_replace("<? \$str .= ", "' . ", $content);
        $content = str_replace("; ?>", " . '", $content);
        $content = preg_replace('/^[ \t]*[\r\n]+/m', '', $content);
        $content = str_replace("\r\n", "\r\n    ", $content);
        $content = preg_replace("/\<\?php\r\n/", ($is_edit ? "  function $func_name() {" : "class $func_name"." extends Controller {\r\n  function Show() {\r\n"), $content, 1);
        $content = str_replace("<?php\r\n", "", $content);

        $content = str_replace('compliance.php', "Compliance", $content);
        $content = str_replace('resources.php', "Resources", $content);
        $content = str_replace('account_details.php', "AccountDetails", $content);
        $content = str_replace('opening_closing.php', "Edit/OpeningClosing", $content);
        $content = str_replace('user_notes.php', "Edit/UserNotes", $content);
        $content = str_replace('equipment_issue.php', "EquipmentIssue", $content);

        $content_show = htmlspecialchars($content);
        $file_content_show = htmlspecialchars($file_content);

        $str .= "<textarea style=\"width: 48%; height: 600px; white-space: pre; overflow-wrap: normal; overflow-x: scroll;\">$file_content_show</textarea>";
        $str .= "<textarea style=\"width: 48%; height: 600px; white-space: pre; overflow-wrap: normal; overflow-x: scroll;\">$content_show</textarea>";
      }
    }
    return $str;
  }
}
?>