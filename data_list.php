<?php

class data_list extends Controller {
  //var $dbi;
  var $id;
  var $mouse_over_color, $colour1, $colour2;
  var $title;
  var $sql;
  var $list_xtra = array();
  var $td_formats = array();
  var $hide_quantity;
  var $is_empty;
  var $custom_process;
  var $table_xtra;
	var $show_num_records;
  //uk-table-middle 
  var $table_class = "uk-table-condensed uk-table-hover uk-table-divider uk-table-striped uk-table-small";
  //Pagination variables
  var $page_num;
  var $num_per_page; //Allows pagination
  var $nav_count;
  //Multi line columns
  var $header_row, $multi_line_col;
  var $top_row; //This is placed before the header rows (useful for extra titles)
  var $headers_every; //Allows the header rows to be displayed every X columns
  var $auto_email;  //Set to 1 to automatically create email addresses
  var $format_xl; //Apply formatting (like grid class) to the excel spreadsheet
  var $num_results;

  function bgCellColour($objPHPExcel,$cells,$colour) {
    $objPHPExcel->getActiveSheet()->getStyle($cells)->getFill()->applyFromArray(array(
      'type' => PHPExcel_Style_Fill::FILL_SOLID,
      'startcolor' => array(
        'rgb' => $colour
      )
    ));
  }

  function escapeJavaScriptText($string) {
    return str_replace("\n", '\n', str_replace('"', '\"', addcslashes(str_replace("\r", '', (string)$string), "\0..\37'\\")));
  }

  function process_quote_products($str) {
    $str = $this->escapeJavaScriptText($str);
    $str = str_replace("#*#", "'", $str);
    return $str;
  }

  function sql_xl($fileName="download.xlsx", &$objPHPExcel = "") {
    $column_array = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "AA", "AB", "AC", "AD", "AE", "AF", "AG", "AH", "AI", "AJ", "AK", "AL", "AM", "AN", "AO", "AP", "AQ", "AR", "AS", "AT", "AU", "AV", "AW", "AX", "AY", "AZ", "BA", "BB", "BC", "BD", "BE", "BF", "BG", "BH", "BI", "BJ", "BK", "BL", "BM", "BN", "BO", "BP", "BQ", "BR", "BS", "BT", "BU", "BV", "BW", "BX", "BY", "BZ", "CA", "CB", "CC", "CD", "CE", "CF", "CG", "CH", "CI", "CJ", "CK", "CL", "CM", "CN", "CO", "CP", "CQ", "CR", "CS", "CT", "CU", "CV", "CW", "CX", "CY", "CZ", "DA", "DB", "DC", "DD", "DE", "DF", "DG", "DH", "DI", "DJ", "DK", "DL", "DM", "DN", "DO", "DP", "DQ", "DR", "DS", "DT", "DU", "DV", "DW", "DX", "DY", "DZ");
    $result = $this->dbi->query($this->sql);
    if(!$objPHPExcel) {
      $objPHPExcel = new PHPExcel();
      $objPHPExcel->setActiveSheetIndex(0);
    }
    

    $row_count = 1;
    $finfo = $result->fetch_fields();
    $columnLength = 0;
    $field_count = 0;
    
    foreach ($finfo as $val) {
      $show_str = ($this->header_row && !$field_count ? 0 : 1);
      if($show_str) {
        $objPHPExcel->getActiveSheet()->SetCellValue($column_array[$columnLength++] . $row_count, $val->name);
        $cols[$field_count] = $val->name;
      }
      $field_count++;
    }
    $objPHPExcel->getActiveSheet()->getStyle($column_array[0]."1:".$column_array[$columnLength]."1")->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->getStyle($column_array[0]."1:".$column_array[$columnLength]."1")->getFont()->setSize(14);


    $row_count++;
    while($myrow = $result->fetch_assoc()) {
      $field_count = 0;
      $keys = array_keys($myrow);
      for ($i = 0; $i < $columnLength; $i++) {
        if($this->header_row && !$field_count) $header_itm = $myrow[$keys[$i]];
        if($old_header_itm != $header_itm) {
          $objPHPExcel->getActiveSheet()->getStyle($column_array[0].$row_count.":".$column_array[0].$row_count)->getFont()->setBold(true);
          $objPHPExcel->getActiveSheet()->getStyle($column_array[0].$row_count.":".$column_array[0].$row_count)->getFont()->setSize(14);
          $objPHPExcel->getActiveSheet()->SetCellValue($column_array[0] . $row_count, $header_itm);
          $row_count++;
        }
        $curr_cell = $column_array[($this->header_row ? ($i ? $i - 1 : 0) : $i)] . $row_count;
        $objPHPExcel->getActiveSheet()->SetCellValue($curr_cell, strip_tags($myrow[$keys[$i]]));
        if($this->multi_line_col && $cols[$i] == $this->multi_line_col) {
          $objPHPExcel->getActiveSheet()->getStyle($curr_cell)->getAlignment()->setWrapText(true);
        }
        if($this->header_row && !$field_count) $old_header_itm = $header_itm;
        $field_count++;
      }
      $row_count++;

    
    }
    $objPHPExcel->getActiveSheet()->getStyle("A1:".$column_array[$columnLength].$row_count)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);

    for ($i = 1; $i != $columnLength; $i++) {
      $objPHPExcel->getActiveSheet()->getColumnDimension($column_array[$i])->setAutoSize(true);
    }
    $objPHPExcel->getActiveSheet()->calculateColumnWidths();
    for ($i = 1; $i != $columnLength; $i++) {
      $curr_width = $objPHPExcel->getActiveSheet()->getColumnDimension($column_array[$i])->getWidth();
      $objPHPExcel->getActiveSheet()->getColumnDimension($column_array[$i])->setAutoSize(false)->setWidth($curr_width * 0.86);
    }
    
    if($this->format_xl) {
      if($i % 2) $this->bgCellColour($objPHPExcel, "A1:{$column_array[$columnLength-1]}1", "DDDDDA");
      for($i = 2; $i < $row_count; $i++) {
        if($i % 2) $this->bgCellColour($objPHPExcel, "A$i:{$column_array[$columnLength-1]}$i", "EEEEEE");
      }
      $objPHPExcel->getActiveSheet()->getStyle("A1:" . $column_array[$columnLength-1] . ($row_count-1))->applyFromArray(array(
        'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => 'AAAAAA')))
      ));
    }
    
    header('Content-type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel); 
    $objWriter->save('php://output');
    exit; 
  }
  function draw_list() {
      //$str .= "<h3>Test: $itm</h3>";
    if($this->num_per_page) {
      $this->sql = trim($this->sql);
      if(substr($this->sql, strlen($this->sql) - 1, 1) == ';') $this->sql = substr($this->sql, 0, strlen($this->sql) - 1);
      if(strpos($this->sql, "filter_string") === false) $_SESSION['stored_sql'] = $this->sql;
      if(strpos($this->sql, "filter_string") !== false && isset($_REQUEST['page_num']) && isset($_SESSION['stored_sql'])) $this->sql = $_SESSION['stored_sql'];
      if(strpos($this->sql, "filter_string") !== false && !isset($_REQUEST['page_num']) && isset($_SESSION['stored_sql'])) unset($_SESSION['stored_sql']);
      //if($_SESSION['stored_sql']) $this->sql = $_SESSION['stored_sql'];
      
//    
      $this->page_num = (isset($_REQUEST['page_num']) ? $_REQUEST['page_num'] : 1);
      
      /*$test_sql = str_ireplace(" FROM ", " FROM ", $this->sql);
      $test = explode(" FROM ", $test_sql);
      $test_sql = "select count(*) as `num_rows`";
      for($x = 1; $x < count($test); $x++) $test_sql .= " from " . $test[$x];
      if($result = $this->dbi->query($test_sql)) {
        if($myrow = $result->fetch_assoc()) {
          $num_rows = $myrow['num_rows'];
        }
      }*/
      if($result = $this->dbi->query($this->sql)) {
        $num_rows = ($num_rows ? $num_rows : $result->num_rows);
      }
      //echo "n: " . $_REQUEST['page_num'];
      $this->sql .= " LIMIT " . (($this->page_num - 1) * $this->num_per_page) . ", " . $this->num_per_page;
      
      $qry = $_SERVER["QUERY_STRING"];
      $qry_explode = explode("&", $qry);
      if(count($qry_explode)) {
        $qry = "";
        foreach($qry_explode as $qs) {
        //echo "<h3>$qs</h3>";
          if(strpos($qs, "page_num") === false) {
            $qry .= "&$qs";
          }
        }
      }
      //echo "t: " . $_SESSION['stored_sql'];
    }
    
    if($result = $this->dbi->query($this->sql)) {
      //echo "nr: " . $num_rows . ", npp: " . $qry;
      $num_rows = ($num_rows ? $num_rows : $result->num_rows);
      $this->num_results = $num_rows;
      if($num_rows) {
        $str .= '<style>td { vertical-align: top; }</style>';
        if($this->num_per_page && $num_rows > $this->num_per_page) {
          $num_pages = ceil($num_rows / $this->num_per_page);
          if($this->form_nav) $str .= '<input type="hidden" name="page_num" id="page_num" value="1" />';
          $nav .= '
                  <script>
                    function nav(page_num) {
                      alert(page_num)
                      document.getElementById("page_num").value = page_num;
                      document.frmEdit.submit()
                      //alert(document.getElementById("page_num").value)
                      //filter();
                    }
                  </script>
                  <ul class="pagination">';
          if($this->page_num > ceil($this->nav_count / 2)) {
            $start_pos = $this->page_num - floor($this->nav_count / 2);
            $end_pos = $this->page_num + floor($this->nav_count / 2);
          } else {
            $start_pos = 1;
            $end_pos = $this->nav_count;
          }
          if($end_pos > $num_pages) {
            $end_pos = $num_pages;
            $start_pos = $num_pages - $this->nav_count;
            if($start_pos < 1) $start_pos = 1;
          }
          $next_page = ($this->page_num < $num_pages ? ($this->page_num + 1) : "");
          $previous_page = ($this->page_num > 1 ? ($this->page_num - 1) : "");
          if($this->form_nav) {
            $nav_first = "JavaScript:nav(1)";
            $nav_prev = "JavaScript:nav($previous_page)";
            $nav_next = "JavaScript:nav($next_page)";
            $nav_last = "JavaScript:nav($num_pages)";
          } else {
            $nav_first = "?page_num=1$qry";
            $nav_prev = "?page_num=$previous_page$qry";
            $nav_next = "?page_num=$next_page$qry";
            $nav_last = "?page_num=$num_pages$qry";
          }
          $nav .= '<li><a href="'.$nav_first.'" aria-label="First"><span aria-hidden="true">|&laquo;</span></a></li>';
          $nav .= (!$previous_page ? '<li class="disabled">' : '<li><a href="'.$nav_prev.'" aria-label="Previous">') . '<span aria-hidden="true">&laquo;</span></a></li>';
          for($x = $start_pos; $x <= $end_pos; $x++) {
            $nav_num = ($this->form_nav ? "JavaScript:nav($x)" : "?page_num=$x$qry");
            $nav .= "<li " . ($x == $this->page_num ? 'class="active"' : '') . "><a href=\"$nav_num\">$x </a></li>";
          }
          $nav .= (!$next_page ? '<li class="disabled">' : '<li><a href="'.$nav_next.'" aria-label="Next">') . '<span aria-hidden="true">&raquo;</span></a></li>';
          $nav .= '<li><a href="'.$nav_last.'" aria-label="Last"><span aria-hidden="true">&raquo;|</span></a></li>';
          $nav .= "</ul>";
        }
        $str .= $nav;
        $finfo = $result->fetch_fields();
        if($this->title) $str .= '<h3>'.$this->title . " ";
        if($this->show_num_records) $str .= "<p>" . ($this->num_per_page ? "Showing " . ($this->num_per_page * ($this->page_num - 1) + 1) . "-" . (($this->num_per_page * $this->page_num) < $num_rows ? ($this->num_per_page * $this->page_num) : $num_rows) . " of " : "") . "$num_rows Record" . ($num_rows == 1 ? "" : "s") . ". </p>";
        if(!$this->hide_quantity) {
          if(!$this->title) $str .= '<h3>';
        }
        if($this->title || !$this->hide_quantity) $str .= '</h3>';
        $str .= '<div style="overflow-x:auto;"><table class="'.$this->table_class.'" '.$this->table_xtra.'>';
        $header .= $this->top_row . '<thead><tr>';
        $field_count = 0;
        foreach ($finfo as $val) {
				  $show_str = 1;
 				  if($val->name == "*" && ($_SESSION['page_access'] == 1 || $_SESSION['page_access'] == 3)) $show_str = 0;
					if($val->name == "!" && ($_SESSION['page_access'] == 1 || $_SESSION['page_access'] == 2 || $_SESSION['page_access'] == 5)) $show_str = 0;
          
          if($this->header_row && !$field_count) $show_str = 0;
          
          if($val->name != "id" && $val->name != "idin" && $show_str) $header .= '<th  class=\"uk-table-shrink\" '.$this->td_formats[$field_count-1].' class="'.$this->table_class.'" align="left"><nobr>' . $val->name . '<nobr></th>';
          $field_count++;
        }
        $num_fields = $field_count;
        $header .= "</tr></thead><tbody>";
        $str .= $header;

	      while($myrow = mysqli_fetch_object($result)) {
          if($recnum && $this->headers_every && !($recnum % $this->headers_every)) $str .= $header;
          $colour = ($recnum % 2 ? $this->colour1 : $this->colour2);
          if($this->mouse_over_color) {
            $styler = 'class="row"';
          }
          
          if($myrow->idin == $_REQUEST["idin"] && $_REQUEST["idin"]) {
             $astr_tmp = "<b>";
             $bstr_tmp = "</b>";
          } else {
             $astr_tmp = "";
             $bstr_tmp = "";
          }
          $field_count = 0;
          foreach ($finfo as $val) {
            $td_format = $this->td_formats[$field_count-1];
            $itm = $val->name;
            $add_email = ($this->auto_email && stripos($itm, "email") !== false ? 1 : 0);
            $data_type = $val->type;
            
            if($itm == "idin") $row_id = $myrow->$itm;

            if($itm != "id" && $val->name != "idin") {
              $itm = $myrow->$itm;
              if(!$field_count) {
                if($this->header_row) {
                  $header_itm = $itm;
                  if($header_itm != $old_header_itm) {
                    $str .= '<tr><td colspan="'.$num_fields.'"><h3>'.$itm.'</h3></td></tr>';
                  }
                } else {
                  $str .= '<tr ' . $styler . '>';
                }
              }
              if(($this->custom_process-1) == $field_count) $itm = $this->process_quote_products($itm);
              $tmp = $this->list_xtra[$field_count-1];
              if($tmp && $itm) {
                $itm = str_replace("display_itm", $itm, $tmp);
              }
              if(strtoupper($itm) == "EDIT" && ($_SESSION['page_access'] == 2 || $_SESSION['page_access'] == 4 || $_SESSION['page_access'] == 5)) {
                $itm = '<span title="Edit Item" class="" data-uk-icon="icon: file-edit" data-uk-tooltip></span>';
                $astr = '<a class="green-a" href="JavaScript:edit_record(' . $myrow->idin . ');">';
                $bstr = "</a>";
              } else if(strtoupper($itm) == "DELETE" && ($_SESSION['page_access'] == 3 || $_SESSION['page_access'] == 4)) {
                $itm = '<span title="Remove Item" class="" data-uk-icon="icon: trash" data-uk-tooltip></span>';
                $astr = '<a class="red-a" href="JavaScript:delete_record(' . $myrow->idin . ');">';
                $bstr = "</a>";
              } else {
                $astr = $astr_tmp;
                $bstr = $bstr_tmp;
              }
              if($data_type == 12 || $data_type == 10) {    //Date/time format
                if($itm != '0000-00-00 00:00:00' && $itm != '0000-00-00') {
                  if($data_type == 10) {
                    $itm = "<nobr>" . Date("d-M-Y", strtotime($itm)) . "</nobr>";
                  } else {
                    $itm = "<nobr>" . Date("d-M-Y H:i", strtotime($itm)) . "</nobr>";
                  }
                } else {
                  $itm = "";
                }
              } else if ($data_type == 11) {
                $itm = "<nobr>" . Date("H:i", strtotime($itm)) . "</nobr>";
              }
							$show_str = 1;
              if(strtoupper($itm) == "EDIT" && ($_SESSION['page_access'] == 1 || $_SESSION['page_access'] == 3)) $show_str = 0;
              if(strtoupper($itm) == "DELETE" && ($_SESSION['page_access'] == 1 || $_SESSION['page_access'] == 2 || $_SESSION['page_access'] == 5)) $show_str = 0;

              if($this->header_row && !$field_count) {
                $old_header_itm = $header_itm;
                $show_str = 0;
              }
              if($add_email) { $astr = "<a href=\"mailto:$itm\">"; $bstr = "</a>"; }
              //valign=\"top\"
							if($show_str) $str .= "<td id=\"".($row_id ? $row_id : ($recnum+1))."-$field_count\" $td_format>$astr" . $itm . "$bstr</td>\n\n"; 
              
            }
            $field_count++;
          }
          $str .= "</tr>";
          $recnum++;
        }
        $str .= "</tbody></table></div>";
        $str .= $nav;
      } else {
        $this->is_empty = 1;
        $str = "";
      }
      return $str;
    }
    $this->dbi->close();
  }


}

?>