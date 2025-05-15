<?php
class ReportsController extends Controller {
  protected $f3;
  var $hide_colours = 0;
  var $cid;
  var $subject_col = "Subject", $assessor_col = "Assessor";
  var $show_links = 1;
  var $show_dates = 1;
  var $headers_every = 20;
  var $show_num_records = 1;
  var $hide_score = 0;
  var $objPHPExcel;

  function __construct($f3) {
    $this->objPHPExcel = new PHPExcel();
    $this->action = (isset($_POST['hdnAction']) ? $_POST['hdnAction'] : null);
    $this->f3 = $f3;
    $this->list_obj = new data_list;
    $this->editor_obj = new data_editor;
    $this->db_init();
  }
  
  function Results() {
    $is_admin = ($this->f3->get('is_admin') ? 1 : 0);
    $cid = (isset($_GET['cid']) ? $_GET['cid'] : $this->cid);
    $subject_id = (isset($_GET['subject_id']) ? $_GET['subject_id'] : null);
    $assessor_id = (isset($_GET['assessor_id']) ? $_GET['assessor_id'] : null);
    $download_excel = (isset($_GET['download_excel']) ? $_GET['download_excel'] : null);
    $hide_colours = $this->hide_colours;
    $is_client = ($this->f3->get('is_client') || $this->f3->get('is_site') ? 1 : 0);
    $chkPending = (isset($_GET['chkPending']) ? $_GET['chkPending'] : null);
    $chkCompleted = (isset($_GET['chkCompleted']) ? $_GET['chkCompleted'] : null);
    $chkCancelled = (isset($_GET['chkCancelled']) ? $_GET['chkCancelled'] : null);
    $selDateMonth = (isset($_REQUEST['selDateMonth']) ? $_REQUEST['selDateMonth'] : null);
    $selDateYear = (isset($_REQUEST['selDateYear']) ? $_REQUEST['selDateYear'] : null);
    
    $excel_url = $this->f3->get('main_folder')."Reporting/ExcelResults?cid=$cid" . ($chkPending ? "&chkPending=$chkPending" : "") . ($chkCompleted ? "&chkCompleted=$chkCompleted" : "") . ($chkCancelled ? "&chkCancelled=$chkCancelled" : "") . ($selDateMonth ? "&selDateMonth=$selDateMonth" : "") . ($selDateYear ? "&selDateYear=$selDateYear" : "");

    if($this->show_dates) {
      $hdnReportFilter = (isset($_REQUEST['hdnReportFilter']) ? $_REQUEST['hdnReportFilter'] : null);
      $url_str = "?a=1";
      if($hdnReportFilter) {
        if($chkCompleted && $chkPending && $chkCancelled) {
          $filter .= " and (compliance_checks.status_id = $chkPending or compliance_checks.status_id = $chkCompleted or compliance_checks.status_id = $chkCancelled) ";
          $chkPending = "checked";
          $chkCompleted = "checked";
          $chkCancelled = "checked";
        } else if($chkCompleted && $chkPending) {
          $filter .= " and (compliance_checks.status_id = $chkPending or compliance_checks.status_id = $chkCompleted) ";
          $chkPending = "checked";
          $chkCompleted = "checked";
        } else if($chkCompleted && $chkCancelled) {
          $filter .= " and (compliance_checks.status_id = $chkCancelled or compliance_checks.status_id = $chkCompleted) ";
          $chkPending = "";
          $chkCancelled = "checked";
          $chkCompleted = "checked";
        } else if($chkCancelled && $chkPending) {
          $filter .= " and (compliance_checks.status_id = $chkPending or compliance_checks.status_id = $chkCancelled) ";
          $chkPending = "checked";
          $chkCancelled = "checked";
        } else if($chkPending) {
          $filter .= " and compliance_checks.status_id = $chkPending ";
          $chkPending = "checked";
        } else if($chkCompleted) {
          $chkPending = "";
          $filter .= " and compliance_checks.status_id = $chkCompleted ";
          $chkCompleted = "checked";
        } else if($chkCancelled) {
          $chkPending = "";
          $filter .= " and compliance_checks.status_id = $chkCancelled ";
          $chkCancelled = "checked";
        }
      } else {
        $filter = " and compliance_checks.status_id != 525" . ($is_client ? " and compliance_checks.status_id != 522" : "");
  //      $chkPending = ($is_client ? "" : "checked");
        $chkPending = "";
        $chkCompleted = "checked";
      }
      
      $nav = new navbar;
      $txtComplianceFilter = (isset($_GET['txtComplianceFilter']) ? $_GET['txtComplianceFilter'] : null);
      $str .= '
        <script language="JavaScript">
          function report_filter() {
            if(document.getElementById("txtComplianceFilter").value != "") {
              document.getElementById("selDateMonth").selectedIndex = 0
              document.getElementById("selDateYear").selectedIndex = 0
            }
            document.getElementById("hdnReportFilter").value=1
            document.frmFilter.submit()
          }
        </script>
        </form>
        <form method="GET" name="frmFilter" onSubmit="report_filter()">
        <input type="hidden" name="hdnReportFilter" id="hdnReportFilter">
        <input type="hidden" name="subject_id" id="subject_id" value="'.$subject_id.'">
        <input type="hidden" name="cid" id="cid" value="'.$cid.'">
        <div class="form-wrapper">
        <div class="form-header" style="height: 25px;"><div class="fl">Filter</div><div class="fr"><a class="list_a" href="'.$excel_url.'">Download Excel</a></div></div>
        <div  style="padding: 10px;">
        '.$nav->month_year(2018).'
        <input value="522" type="checkbox" name="chkPending" '.$chkPending.' id="chkPending"  /> Pending
        <input value="524" type="checkbox" name="chkCompleted" '.$chkCompleted.' id="chkCompleted"  /> Completed
        <input value="525" type="checkbox" name="chkCancelled" '.$chkCancelled.' id="chkCancelled"  /> Cancelled
        <input type="text" name="txtComplianceFilter" id="txtComplianceFilter" value="'.$txtComplianceFilter.'" placeholder="Optional filter by text within answers..." style="width: 300px;" /><input onClick="report_filter()" type="button" value="Go" />
        </div>
        </div>
        </form>
      ';
      if($hdnReportFilter) {
        $month_select = ($selDateMonth ? $selDateMonth : null);
        $year_select = ($selDateYear ? $selDateYear : null);
      } else {
        $str .= '
              <script language="JavaScript">
                change_selDate()
              </script>
        ';
        $month_select = date('m');
        $year_select = date('Y');
      }
      if($month_select > 0) {
        $filter .= " and MONTH(compliance_checks.check_date_time) = $month_select ";
      }
      if($year_select > 0) {
        $filter .= " and YEAR(compliance_checks.check_date_time) = $year_select ";
      }
    }
    
    
//                  CONCAT('<span style=\"color: #009900\">', compliance_check_answers.question, '</span><br />', compliance_check_answers.answer) as `details`,
    $sql = "
    select 
                compliance_checks.id as `idin`, compliance_checks.id as `chid`, compliance_checks.total_out_of,
                CONCAT(if(users.employee_id != '', CONCAT(users.employee_id, ' - '), ''), users.name, ' ', users.surname) as `subject`,
                CONCAT(if(users2.employee_id != '', CONCAT(users2.employee_id, ' - '), ''), users2.name, ' ', users2.surname) as `assessor`,
                users.id as `subject_id`,
                users2.id as `assessor_id`,
                CONCAT('<nobr>', DATE_FORMAT(compliance_checks.check_date_time, '%d-%b-%Y'), '</nobr>') as `date`, compliance.id as `cid`, compliance.title, compliance.hide_score, compliance.score_completed_only,
                compliance_check_answers.question, compliance_check_answers.answer, compliance_check_answers.answer_colour, compliance_check_answers.additional_text, compliance_check_answers.value, compliance_check_answers.out_of
                FROM compliance_checks
                left join compliance_check_answers on compliance_check_answers.compliance_check_id = compliance_checks.id
                left join lookup_fields1 on lookup_fields1.id = compliance_checks.status_id
                left join compliance on compliance.id = compliance_checks.compliance_id
                left join users on users.id = compliance_checks.subject_id
                left join users2 on users2.id = compliance_checks.assessor_id
                where compliance.id = $cid
                " . ($is_client ? " and users2.id = " . $_SESSION['user_id'] : "") . "
                and compliance_check_answers.answer != '' and lookup_fields1.item_name = 'Completed'
                and compliance_check_answers.answer != 'LBL'
                and compliance_check_answers.answer != 'AS1gna7ure1ma6e'
                " . ($subject_id ? " and users.id = $subject_id" : "") . "
                " . ($assessor_id ? " and users2.id = $assessor_id" : "") . "
                $filter
                order by compliance.title asc, check_date_time asc
    ";
   // compliance.category_id in (select id from lookup_fields where item_name LIKE '%request%')
    //$this->list_obj->sql = $sql;
    //return $sql;
    //$str .= $this->list_obj->draw_list();
    //return $str;

    //uk-table-condensed uk-table-hover uk-table-divider uk-table-middle uk-table-striped uk-table-small
    $table_head = '<tr><th>ID</th><th>Date</th><th>'.$this->assessor_col.'</th><th>'.$this->subject_col.'</th>';
    $table_start = '<br /><table class="grid">' . $table_head;
    
    //$chkCompleted = (!$chkCancelled && !$chkPending)

    //$burl = "&hdnReportFilter=$hdnReportFilter&selDateMonth=$month_select&selDateYear=$year_select&chkCompleted=$chkCompleted&chkCancelled=$chkCancelled&chkPending=$chkPending";
    $result = $this->dbi->query($sql);
    if($result->num_rows) {
      $c = 0;
      $old_cid = "";
      $rndm_str = ($this->show_num_records ? "RndmStr39asADGaajjfa" : "");
      $done = 0;
      
      while($myrow = $result->fetch_assoc()) {
        if(!$done) {
          $hide_score = ($this->hide_score ? $this->hide_score : $myrow['hide_score']);
          $score_completed_only = $myrow['score_completed_only'];
          $done = 1;
        }

        $idin = $myrow['idin'];
        $cid = $myrow['cid'];
        $title = $myrow['title'];
        $question = $myrow['question'];

        $total_out_of = $myrow['total_out_of'];
        $value = $myrow['value'];
        $out_of = $myrow['out_of'];

        $answer = nl2br($myrow['answer']);
        $answer_colour = ($hide_colours ? "" : $myrow['answer_colour']);
        $assessor = $myrow['assessor'];
        $assessor_id = $myrow['assessor_id'];
        $subject = $myrow['subject'];
        $subject_id = $myrow['subject_id'];
        $date = $myrow['date'];
        $additional_text = $myrow['additional_text'];

        
        //$subject = ($this->show_links ? "<a href=\"".$this->f3->get('main_folder')."Reporting/Results?cid=$cid&subject_id=$subject_id$burl\">$subject</a>" : $subject);
        //$assessor = ($this->show_links ? "<a href=\"".$this->f3->get('main_folder')."Reporting/Results?cid=$cid&assessor_id=$assessor_id$burl\">$assessor</a>" : $assessor);
        
        if($cid != $old_cid) {
          if($old_cid) {
            $str .= "<tr><td>$old_idin</td><td>$old_date</td><td>$old_assessor</td><td>$old_subject</td>";
            foreach ($questions as $question_out) {
              $str .= "<td>" . ($answers[$question_out] ? $answers[$question_out] . ($hide_score || !$out_ofs[$question_out] ? "" : " (" . $values[$question_out] . "/" . $out_ofs[$question_out] . ")") : "&nbsp;") . "</td>";
            }
            $str .= "</tr></table>";
          }
          $questions = Array();
          $answers = Array();
          $values = Array();
          $out_ofs = Array();
          $total_score = 0;
          $sql = "
            select 
                  compliance_questions.question_title
                  FROM compliance_questions
                  left join compliance on compliance.id = compliance_questions.compliance_id
                  where compliance.id = $cid
                  and compliance_questions.question_type != 'lbl'
                  and compliance_questions.question_type != 'ins'
                  and compliance_questions.question_type != 'sig'
                  order by compliance_questions.sort_order
          ";
          if($result2 = $this->dbi->query($sql)) {
            $question_head = "";
            $num_questions = 0;
            while($myrow2 = $result2->fetch_assoc()) {
              $question_title = $myrow2['question_title'];
              $questions[$question_title] = $question_title;
              $answers[$question_title] = "";
              $colours[$question_title] = "";
              $values[$question_title] = "";
              $out_ofs[$question_title] = "";
              $question_head .= "<th>$question_title</th>";
              $num_questions++;
            }
          }
          $first_item = 1;
        }
        //$str .= "<h3>hs: $hide_score</h3>";
        if($idin != $old_idin) {
          if(!$c) {
            $question_head .= ($hide_score ? "" : "<th>Total Score</th>");

            $str .= "<h3>$title</h3>$rndm_str$table_start$question_head</tr>";
          }
          if($old_idin && !($cid != $old_cid && $old_cid)) {
            
            
            $str .= "<tr><td><a href=\"".$this->f3->get('main_folder')."Compliance?report_view_id=$old_idin\">$old_idin</a>".($is_admin ? "<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."Compliance?report_edit_id=$old_idin\">Edit</a>" : "")."<br/><a uk-tooltip=\"title: View as PDF\" class=\"list_a\" target=\"_blank\" href=\"".$this->f3->get('main_folder')."CompliancePDF/$old_idin\">PDF</a></td><td>$old_date</td><td>$old_assessor</td><td>$old_subject</td>";
            foreach ($questions as $question_out) {
              $str .= '<td ' . ($colours[$question_out] ? 'style="color: white; background-color: '.$colours[$question_out].';"' : "") . '>'
              . ($answers[$question_out] ? $answers[$question_out] . ($hide_score || !$out_ofs[$question_out] ? "" : " (" . $values[$question_out] . "/" . $out_ofs[$question_out] . ")") : "&nbsp;") . "</td>";
              //if(is_numeric($values[$question_out]) === true) $total_score += $values[$question_out];
              $total_score += floatval($values[$question_out]);
            }
            if(!$hide_score) {
              $str .= "<td>$total_score/$total_out_of</td>";
            }
            $str .= "</tr>";
            
            if($this->headers_every) $str .= (!($c % $this->headers_every) ? "$table_head$question_head" : "");
            
            foreach ($answers as $i=>$value) {
              $answers[$i] = NULL;
              $colours[$i] = NULL;
              $values[$i] = NULL;
              $out_ofs[$i] = NULL;
              $total_score = 0;
            }
          }
          $c++;
        }
        $answers[$question] = $answer . ($additional_text ? " ($additional_text)" : "");
        $colours[$question] = $answer_colour;
        $values[$question] = $value;
        $out_ofs[$question] = $out_of;
        
        $old_idin = $idin;
        $old_cid = $cid;
        $old_date = $date;
        $old_subject = $subject;
        $old_assessor = $assessor;
        
      }
      
      $str .= "<tr><td><a href=\"".$this->f3->get('main_folder')."Compliance?report_view_id=$idin\">$idin</a><br/><a uk-tooltip=\"title: View as PDF\" class=\"list_a\" target=\"_blank\" href=\"".$this->f3->get('main_folder')."CompliancePDF/$old_idin\">PDF</a></td><td>$date</td><td>$assessor</td><td>$subject</td>";
      foreach ($questions as $question_out) {
        $str .= '<td ' . ($colours[$question_out] ? 'style="color: white; background-color: '.$colours[$question_out].';"' : "") . '>'
        . ($answers[$question_out] ? $answers[$question_out] . ($hide_score || !$out_ofs[$question_out] ? "" : " (" . $values[$question_out] . "/" . $out_ofs[$question_out] . ")") : "&nbsp;") . "</td>";
        //$total_score += $values[$question_out];
        $total_score += floatval($values[$question_out]);
      }
      if(!$hide_score) {
        $str .= "<td>$total_score/$total_out_of</td>";
      }
      $str .= "</tr>";
      
      $str .= "</tr></table>";
    }

    //$str .= $this->list_obj->draw_list();
    if($this->show_num_records && $c) {
      $str = str_replace($rndm_str, "$c Records", $str);
      $str .= "<h3>$c Records</h3>";
    }

    return $str;
  }

 
  
  function AllExcel($selDateMonth = 0, $selDateYear = 0, $chkPending = 0, $chkCompleted = 0, $chkCancelled = 0) {
    $objPHPExcel = $this->objPHPExcel;
    $c = 0; // Initialize the variable $c
    $filter = ""; // Initialize the variable $filter
    $assessor_id = ""; // Initialize the variable $assessor_id
    $subject_id = ""; // Initialize the variable $subject_id
    $old_idin = ""; // Initialize the variable $old_idin
    $idin = 0;
   
    $title = "";
    $selDateMonth = ($selDateMonth ? $selDateMonth : (isset($_REQUEST['selDateMonth']) ? $_REQUEST['selDateMonth'] : date("m", strtotime("-1 month"))));
    $selDateYear = ($selDateYear ? $selDateYear : (isset($_REQUEST['selDateYear']) ? $_REQUEST['selDateYear'] : date("Y", strtotime("-1 month"))));
    
    //$selDateMonth = -1;
    //$selDateYear = -1;

    $sql = "SELECT id, title 
FROM compliance 
WHERE title NOT LIKE '%MAIN TEMPLATE%' 
    AND title NOT LIKE '%TEST%' 
    AND id IN (
        SELECT compliance_id 
        FROM compliance_checks 
        WHERE status_id = 524 
            AND percent_complete > 0 
            " . ($selDateMonth == -1 ? "" : "AND MONTH(check_date_time) = '$selDateMonth'") . "
            " . ($selDateYear == -1 ? "" : "AND YEAR(check_date_time) = '$selDateYear'")
    . ") 
ORDER BY title";


    if($result = $this->dbi->query($sql)) {
        while($myrow = $result->fetch_assoc()) {
            $c++;
            $cid = $myrow['id'];
            $title = $myrow['title']; // Define $title here
            // Pass $filter as an argument to the ExcelResults function
            $this->ExcelResults($c, $cid, 0, 524, 0, $selDateMonth, $selDateYear, $assessor_id, $subject_id, $idin, $old_idin, $filter, $title);
        }
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->setSelectedCell('A1');

        $selDateMonth = ($selDateMonth == -1 ? "All_Months" : $selDateMonth);
        $selDateYear = ($selDateYear == -1 ? "All Years" : $selDateYear);
        $fileName = "Reports_{$selDateMonth}_{$selDateYear}.xlsx";
        header('Content-type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel); 
        $objWriter->save('php://output');
    }
}






  function ExcelResults($use_return = 0, $cid = 0, $idin = 0, $chkPending = 0, $chkCompleted = 0, $chkCancelled = 0, $selDateMonth = 0, $selDateYear = 0, $assessor_id = "", $subject_id = "", $filter = "", $c = 0, $title = "") {
    $cid = ($cid ? $cid : (isset($_GET['cid']) ? $_GET['cid'] : $this->cid));
    $subject_id = ($subject_id ? $subject_id : (isset($_GET['subject_id']) ? $_GET['subject_id'] : null));
    $assessor_id = ($assessor_id ? $assessor_id : (isset($_GET['assessor_id']) ? $_GET['assessor_id'] : null));
    $hide_colours = $this->hide_colours;
    $is_client = ($this->f3->get('is_client') || $this->f3->get('is_site') ? 1 : 0);
    $chkPending = ($chkPending ? $chkPending : (isset($_GET['chkPending']) ? $_GET['chkPending'] : null));
    $chkCompleted = ($chkCompleted ? $chkCompleted : (isset($_GET['chkCompleted']) ? $_GET['chkCompleted'] : null));
    $chkCancelled = ($chkCancelled ? $chkCancelled : (isset($_GET['chkCancelled']) ? $_GET['chkCancelled'] : null));
    $selDateMonth = ($selDateMonth ? $selDateMonth : (isset($_REQUEST['selDateMonth']) ? $_REQUEST['selDateMonth'] : null));
    $selDateYear = ($selDateYear ? $selDateYear : (isset($_REQUEST['selDateYear']) ? $_REQUEST['selDateYear'] : null));

    $column_array = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "AA", "AB", "AC", "AD", "AE", "AF", "AG", "AH", "AI", "AJ", "AK", "AL", "AM", "AN", "AO", "AP", "AQ", "AR", "AS", "AT", "AU", "AV", "AW", "AX", "AY", "AZ", "BA", "BB", "BC", "BD", "BE", "BF", "BG", "BH", "BI", "BJ", "BK", "BL", "BM", "BN", "BO", "BP", "BQ", "BR", "BS", "BT", "BU", "BV", "BW", "BX", "BY", "BZ", "CA", "CB", "CC", "CD", "CE", "CF", "CG", "CH", "CI", "CJ", "CK", "CL", "CM", "CN", "CO", "CP", "CQ", "CR", "CS", "CT", "CU", "CV", "CW", "CX", "CY", "CZ",
    "DA", "DB", "DC", "DD", "DE", "DF", "DG", "DH", "DI", "DJ", "DK", "DL", "DM", "DN", "DO", "DP", "DQ", "DR", "DS", "DT", "DU", "DV", "DW", "DX", "DY", "DZ",
    "EA", "EB", "EC", "ED", "EE", "EF", "EG", "EH", "EI", "EJ", "EK", "EL", "EM", "EN", "EO", "EP", "EQ", "ER", "ES", "ET", "EU", "EV", "EW", "EX", "EY", "EZ",
    "FA", "FB", "FC", "FD", "FE", "FF", "FG", "FH", "FI", "FJ", "FK", "FL", "FM", "FN", "FO", "FP", "FQ", "FR", "FS", "FT", "FU", "FV", "FW", "FX", "FY", "FZ"
    );

    $objPHPExcel = $this->objPHPExcel;
    if($use_return) $objPHPExcel->createSheet($use_return - 1);
    $objPHPExcel->setActiveSheetIndex(($use_return ? ($use_return - 1) : 0));
    $sheet = $objPHPExcel->getActiveSheet();
    
    if($this->show_dates) {

      $hdnReportFilter = 1;
      if($hdnReportFilter) {
        if($chkCompleted && $chkPending && $chkCancelled) {
          $filter .= " and (compliance_checks.status_id = $chkPending or compliance_checks.status_id = $chkCompleted or compliance_checks.status_id = $chkCancelled) ";
        } else if($chkCompleted && $chkPending) {
          $filter .= " and (compliance_checks.status_id = $chkPending or compliance_checks.status_id = $chkCompleted) ";
        } else if($chkCompleted && $chkCancelled) {
          $filter .= " and (compliance_checks.status_id = $chkCancelled or compliance_checks.status_id = $chkCompleted) ";
          $chkPending = "";
        } else if($chkCancelled && $chkPending) {
          $filter .= " and (compliance_checks.status_id = $chkPending or compliance_checks.status_id = $chkCancelled) ";
        } else if($chkPending) {
          $filter .= " and compliance_checks.status_id = $chkPending ";
        } else if($chkCompleted) {
          $filter .= " and compliance_checks.status_id = $chkCompleted ";
          $chkCompleted = "checked";
        } else if($chkCancelled) {
          $filter .= " and compliance_checks.status_id = $chkCancelled ";
        }
      } else {
        $filter = " and compliance_checks.status_id != 525" . ($is_client ? " and compliance_checks.status_id != 522" : "");
      }

      if($hdnReportFilter) {
        $month_select = ($selDateMonth ? $selDateMonth : null);
        $year_select = ($selDateYear ? $selDateYear : null);
      } else {
        $month_select = date('m');
        $year_select = date('Y');
      }
      if($month_select > 0) {
        $filter .= " and MONTH(compliance_checks.check_date_time) = $month_select ";
      }
      if($year_select > 0) {
        $filter .= " and YEAR(compliance_checks.check_date_time) = $year_select ";
      }
    }
    
    $sql = "
    SELECT 
        compliance_checks.id AS `idin`, 
        compliance_checks.id AS `chid`, 
        compliance_checks.total_out_of,
        CONCAT(IF(users.employee_id != '', CONCAT(users.employee_id, ' - '), ''), users.name, ' ', users.surname) AS `subject`,
        CONCAT(IF(users2.employee_id != '', CONCAT(users2.employee_id, ' - '), ''), users2.name, ' ', users2.surname) AS `assessor`,
        users.id AS `subject_id`,
        users2.id AS `assessor_id`,
        CONCAT(DATE_FORMAT(compliance_checks.check_date_time, '%d-%b-%Y')) AS `date`, 
        compliance.id AS `cid`, 
        compliance.title, 
        compliance.hide_score, 
        compliance.score_completed_only,
        compliance_check_answers.question, 
        compliance_check_answers.answer, 
        compliance_check_answers.answer_colour, 
        compliance_check_answers.additional_text, 
        compliance_check_answers.value, 
        compliance_check_answers.out_of
    FROM 
        compliance_checks
    LEFT JOIN 
        compliance_check_answers ON compliance_check_answers.compliance_check_id = compliance_checks.id
    LEFT JOIN 
        lookup_fields1 ON lookup_fields1.id = compliance_checks.status_id
    LEFT JOIN 
        compliance ON compliance.id = compliance_checks.compliance_id
    LEFT JOIN 
        users ON users.id = compliance_checks.subject_id
    LEFT JOIN 
        users AS users2 ON users2.id = compliance_checks.assessor_id
    WHERE 
        compliance.id = $cid
        AND compliance_check_answers.answer != '' 
        AND lookup_fields1.item_name = 'Completed'
        AND compliance_check_answers.answer NOT IN ('LBL', 'AS1gna7ure1ma6e')
        " . ($subject_id ? " AND users.id = $subject_id" : "") . "
        " . ($assessor_id ? " AND users2.id = $assessor_id" : "") . "
        " . ($is_client ? " AND users2.id = " . $_SESSION['user_id'] : "") . "
";



    $sheet->SetCellValue($column_array[0] . "1", "ID");
    $sheet->SetCellValue($column_array[1] . "1", "Date");
    $sheet->SetCellValue($column_array[2] . "1", $this->assessor_col);
    $sheet->SetCellValue($column_array[3] . "1", $this->subject_col);

    
    $result = $this->dbi->query($sql);
    if($result->num_rows) {
      $c = 0;
      $old_cid = "";
      $rndm_str = ($this->show_num_records ? "RndmStr39asADGaajjfa" : "");
      
      while($myrow = $result->fetch_assoc()) {
        if($idin) {
          $hide_score = ($this->hide_score ? $this->hide_score : $myrow['hide_score']);
          $score_completed_only = $myrow['score_completed_only'];
        }

        $idin = $myrow['idin'];
        $cid = $myrow['cid'];
        $title = $myrow['title'];
        $question = $myrow['question'];

        $total_out_of = $myrow['total_out_of'];
        $value = $myrow['value'];
        $out_of = $myrow['out_of'];

        $answer = nl2br($myrow['answer']);
        $answer_colour = ($hide_colours ? "" : $myrow['answer_colour']);
        $assessor = $myrow['assessor'];
        $assessor_id = $myrow['assessor_id'];
        $subject = $myrow['subject'];
        $subject_id = $myrow['subject_id'];
        $date = $myrow['date'];
        $additional_text = $myrow['additional_text'];

        if($cid != $old_cid) {
          if($old_cid) {
  
          }
          $questions = Array();
          $answers = Array();
          $values = Array();
          $out_ofs = Array();
          $total_score = 0;
          $sql = "
            select 
                  compliance_questions.question_title
                  FROM compliance_questions
                  left join compliance on compliance.id = compliance_questions.compliance_id
                  where compliance.id = $cid
                  and compliance_questions.question_type != 'lbl'
                  and compliance_questions.question_type != 'ins'
                  and compliance_questions.question_type != 'sig'
                  order by compliance_questions.sort_order
          ";
          if($result2 = $this->dbi->query($sql)) {
            $question_head = "";
            $num_questions = 0;
            while($myrow2 = $result2->fetch_assoc()) {
              $question_title = $myrow2['question_title'];
              $questions[$question_title] = $question_title;
              $answers[$question_title] = "";
              $colours[$question_title] = "";
              $values[$question_title] = "";
              $out_ofs[$question_title] = "";
              $question_head .= "<th>$question_title</th>";
              $num_questions++;
              $cellCoordinate = $column_array[3] . "1"; // Combine column identifier "D" with row number "1"
              $sheet->SetCellValue($cellCoordinate, $question_title);
              }
          }
          $first_item = 1;
        }
        if($idin != $old_idin) {
          if(!$c) {

          }
          if($old_idin && !($cid != $old_cid && $old_cid)) {

            $sheet->SetCellValue($column_array[0] . (1 + $c), $old_idin);
            $sheet->SetCellValue($column_array[1] . (1 + $c), $old_date);
            $sheet->SetCellValue($column_array[2] . (1 + $c), $old_assessor);
            $sheet->SetCellValue($column_array[3] . (1 + $c), $old_subject);

            $col_count = 0;      
            foreach ($questions as $question_out) {
             
              $str = ($answers[$question_out] ? $answers[$question_out] . ($hide_score || !$out_ofs[$question_out] ? "" : " (" . $values[$question_out] . "/" . $out_ofs[$question_out] . ")") : "");

              $curr_cell = $column_array[4+$col_count] . (1 + $c);
              
              if($colours[$question_out]) {
                $sheet->getStyle($curr_cell)->applyFromArray(
                    array(
                      'font' => array('color' => array('rgb' => 'FFFFFF')),
                      'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => substr($colours[$question_out], 1)))
                    )
                );
              }

              $sheet->SetCellValue($curr_cell, $str);
              $total_score += floatval($values[$question_out]);
              $col_count++;
            }
  
            
            foreach ($answers as $i=>$value) {
              $answers[$i] = NULL;
              $colours[$i] = NULL;
              $values[$i] = NULL;
              $out_ofs[$i] = NULL;
              $total_score = 0;
            }
          }
          $c++;
        }
        $answers[$question] = $answer . ($additional_text ? " ($additional_text)" : "");
        $colours[$question] = $answer_colour;
        $values[$question] = $value;
        $out_ofs[$question] = $out_of;
        
        $old_idin = $idin;
        $old_cid = $cid;
        $old_date = $date;
        $old_subject = $subject;
        $old_assessor = $assessor;
        
      }
      
      
      
      $sheet->SetCellValue($column_array[0] . (1 + $c), $old_idin);
      $sheet->SetCellValue($column_array[1] . (1 + $c), $old_date);
      $sheet->SetCellValue($column_array[2] . (1 + $c), $old_assessor);
      $sheet->SetCellValue($column_array[3] . (1 + $c), $old_subject);

      $col_count = 0;      
      foreach ($questions as $question_out) {
       
        $curr_cell = $column_array[4+$col_count] . (1 + $c);
        if($colours[$question_out]) {
          $sheet->getStyle($curr_cell)->applyFromArray(
              array(
                'font' => array('color' => array('rgb' => 'FFFFFF')),
                'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => substr($colours[$question_out], 1)))
              )
          );
        }

        $str = ($answers[$question_out] ? $answers[$question_out] . ($hide_score || !$out_ofs[$question_out] ? "" : " (" . $values[$question_out] . "/" . $out_ofs[$question_out] . ")") : "");

        $sheet->SetCellValue($curr_cell, $str);
        $total_score += floatval($values[$question_out]);
        $col_count++;
      }

    }

    if($this->show_num_records && $c) {

    }
    
    $title = str_replace(' - ', ' ', $title);
    $title = str_replace("'", "", $title);
    $title = str_replace(" / ", " ", $title);
    // Remove invalid characters
$title = preg_replace('/[^\w\s]/', '', $title);

    $title = substr($title, 0, 31);
    $sheet->setTitle($title);
    
    if(!$use_return) {
      $objPHPExcel->getActiveSheet()->setSelectedCell('A1');
      $fileName = "$title.xlsx";
      header('Content-type: application/vnd.ms-excel');
      header('Content-Disposition: attachment; filename="' . $fileName . '"');
      $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel); 
      $objWriter->save('php://output');
    }
  }
    
    
  
  function ReportSummary() {

    $nav_month = (isset($_GET['selDateMonth']) ? $_GET['selDateMonth'] : null);
    $nav_year = (isset($_GET['selDateYear']) ? $_GET['selDateYear'] : null);
    if(!$nav_month) {
      $def_month = 1;
      $nav_month = date("m");
      $nav_year = date("Y");
    }
    $compare_date = "$nav_year-" . ($nav_month < 10 ? "0$nav_month" : $nav_month) . "-01";
    //$str .= $compare_date;
    if($nav_month > 0) {
      $nav1 = "and (MONTH(compliance_checks.check_date_time) = $nav_month or DATE_ADD(CONCAT(YEAR(compliance_checks.check_date_time), '-', MONTH(compliance_checks.check_date_time), '-01'), interval -1 month) = '$compare_date')";
    } else {
      $nav_month = "ALL Months";
    }
    if($nav_year > 0) {
      $nav2 = "and YEAR(compliance_checks.check_date_time) = $nav_year";
    } else {
      $nav_year = "ALL Years";
    }

    
    $nav = new navbar;
    $filter_box = '
      <script language="JavaScript">
        function report_filter() {
          document.getElementById("hdnReportFilter").value=1
          document.frmFilter.submit()
        }
      </script>
      </form>
      <form method="GET" name="frmFilter">
      <input type="hidden" name="hdnReportFilter" id="hdnReportFilter">
      <input type="hidden" name="division_id" value="'.$division_id.'">
      <div class="fl">
      <h3>Report Summary</h3>
      </div>
      <div  style="padding: 10px;" class="fr">
      '.$nav->month_year(2016).'    <input onClick="report_filter()" type="button" value="Go" /> 
      </form>
    ';
    if($def_month) {
      $filter_box .= ' 
        <script language="JavaScript">
          change_selDate()
        </script>
      ';
    }
    
    $str = $filter_box . '</div><div class="cl"></div><hr />';
    //$this->list_obj->title = 'Compliance Checks';
    $this->list_obj->show_num_records = 1;
//    $this->list_obj->form_nav = 1;   $this->list_obj->num_per_page = 100;       $this->list_obj->nav_count = 20;

    $this->list_obj->sql = "
                  select compliance.title as `Compliance Title`,
                  CONCAT('<nobr>', DATE_FORMAT(compliance_checks.check_date_time, '%d-%b-%Y %H:%i'), '</nobr>') as `Date`,
                  CONCAT(if(users.employee_id != '', users.employee_id, ''), ' ', if(users.client_id != '', users.client_id, ''), ' ', users.name, ' ', users.surname) as `Assessor`,
                  CONCAT(if(users2.employee_id != '', users2.employee_id, ''), ' ', if(users2.client_id != '', users2.client_id, ''), ' ', users2.name, ' ', users2.surname) as `Subject`,
                  CONCAT('<a uk-tooltip=\"title: View as PDF\" class=\"list_a\" target=\"_blank\" href=\"".$this->f3->get('main_folder')."CompliancePDF/', compliance_checks.id, '\">PDF</a>') as `PDF`,
                  CONCAT('<a uk-tooltip=\"title: View as PDF\" class=\"list_a\" target=\"_blank\" href=\"".$this->f3->get('main_folder')."Compliance?report_view_id=', compliance_checks.id, '\">View Online</a>') as `View`
                  FROM compliance_checks
                  inner join lookup_fields1 on lookup_fields1.id = compliance_checks.status_id
                  left join compliance on compliance.id = compliance_checks.compliance_id
                  left join lookup_fields2 on lookup_fields2.id = compliance.category_id
                  left join users on users.id = compliance_checks.assessor_id
                  left join users2 on users2.id = compliance_checks.subject_id
                  where lookup_fields1.item_name = 'Completed'
                  
          $nav1 $nav2
    ";
// and (lookup_fields2.item_name LIKE '%audit%')
    // or lookup_fields2.item_name LIKE '%report%'

  //$str .= "<textarea>{$this->list_obj->sql}</textarea>";
    $str .= $this->list_obj->draw_list();
    
    return $str;
    
  }

  function ReportOverview() {
    $download_xl = (isset($_GET['download_xl']) ? $_GET['download_xl'] : null);
    $cat_id = (isset($_GET['cat_id']) ? $_GET['cat_id'] : null);
    
    
    
    $curr_year = date('Y');
    $curr_month = date('m');
    $nav_month = (isset($_GET['selDateMonth']) ? $_GET['selDateMonth'] : $current_month);
    $nav_year = (isset($_GET['selDateYear']) ? $_GET['selDateYear'] : $current_year);
    if($nav_month == -1) $nav_month = $current_month;
    if($nav_year == -1) $nav_year = $current_year;
    if(!$nav_month) {
      $def_month = 1;
      $nav_month = date("m");
      $nav_year = date("Y");
    }

    $month_diff = (($curr_year - $nav_year) * 12) + ($curr_month - $nav_month);

    if(!$download_excel) {
      $nav = new navbar;
      $filter_box = '
        <script language="JavaScript">
          function report_filter() {
            document.getElementById("hdnReportFilter").value=1
            document.frmFilter.submit()
          }
        </script>
        </form>
        <form method="GET" name="frmFilter">
        <input type="hidden" name="hdnReportFilter" id="hdnReportFilter">
        <input type="hidden" name="division_id" value="'.$division_id.'">
        <div class="fl">
        <h3>Report Overview &nbsp;&nbsp;&nbsp;<a class="list_a" href="' . $this->f3->get('main_folder') . 'Reporting/ReportOverview?download_xl=1&'.$_SERVER['QUERY_STRING'].'">Download Excel</a>
        ' . ($cat_id ? ' <a class="list_a" href="' . $this->f3->get('main_folder') . 'Reporting/ReportOverview">Show All Categories</a> ' : '') . '</h3>
        </div>
        <div  style="padding: 10px;" class="fr">Select the Finish Month for a 12 month report &gt;&gt; &nbsp; &nbsp; &nbsp; &nbsp;
        '.$nav->month_year(2016).'    <input onClick="report_filter()" type="button" value="Go" /> 
        </form>
      ';
      if($def_month) {
        $filter_box .= ' 
          <script language="JavaScript">
            change_selDate()
          </script>
        ';
      }
      
      $str = $filter_box . '</div><div class="cl"></div><hr />';
      $this->list_obj->show_num_records = 1;
    }
//    $this->list_obj->form_nav = 1;   $this->list_obj->num_per_page = 100;       $this->list_obj->nav_count = 20;
    if($cat_id && !$download_xl) $this->list_obj->title = $this->get_sql_result("select item_name as `result` from lookup_fields where id = $cat_id");

    $this->list_obj->sql = "select 
    ".(!$cat_id  || $download_xl ? " CONCAT('<a href=\"" . $this->f3->get('main_folder') . "Reporting/ReportOverview?cat_id=', lookup_fields.id, '\">', lookup_fields.item_name, '</a>') as `Category`,  " : "")."
    compliance.title as `Report Title`,";
    for($n = 11; $n >= 0; $n--) {
      $this->list_obj->sql .= ($n != 11 ? "," : "");
      $c = $n + $month_diff;
      $dt = strtotime("-$c month");
      $test_month = date("mY", $dt);
      $show_month = date("M-Y", $dt);
      
      $this->list_obj->sql .= "(select COUNT(compliance_checks.id) from compliance_checks where compliance_checks.compliance_id = compliance.id and DATE_FORMAT(compliance_checks.check_date_time, '%m%Y') = '$test_month') as `$show_month`";

    }
    $this->list_obj->sql .= "FROM compliance 
    left join lookup_fields on lookup_fields.id = compliance.category_id
    where title not like '%MAIN TEMPLATE%' and is_active = 1
    " . ($cat_id ? " and compliance.category_id = $cat_id " : "") . " 
    order by lookup_fields.item_name, compliance.title;";

// and (lookup_fields2.item_name LIKE '%audit%')
    // or lookup_fields2.item_name LIKE '%report%'

  //$str .= $this->ta($this->list_obj->sql);
    if($download_xl) {
      $this->list_obj->sql_xl("report_overview.xlsx");
    } else {
      $str .= $this->list_obj->draw_list();
      return $str;
    }
    
  }


  function Pdf($f3, $params) {
    session_start();
    $pdfid = $params['p1'];
    if($_SESSION['user_id'] && $pdfid) {
      $allow_access = 1;
      
    }
    $this->f3->set('lids', $_SESSION['lids']);
    $this->f3->set('is_client', (array_search(104, $this->f3->get('lids')) !== false) ? 1 : 0);
    $this->f3->set('is_site', (array_search(384, $this->f3->get('lids')) !== false) ? 1 : 0);
    $is_client = ($this->f3->get('is_client') || $this->f3->get('is_site') ? 1 : 0);
    if($allow_access) {
      ini_set('display_startup_errors',1);
      ini_set('display_errors',1);
      error_reporting(-1);
      $pdf = new FPDF('P','mm','A4');
      $pdf->AddPage();
      $pdf->SetFont('Arial','',42);
      $sql = "select compliance.id as compliance_id, compliance.title, compliance_checks.id as `compliance_check_id`,
                CONCAT(DATE_FORMAT(compliance_checks.check_date_time, '%d-%b-%Y %H:%i')) as `date`,
                CONCAT(DATE_FORMAT(compliance_checks.last_modified, '%d-%b-%Y %H:%i')) as `last_modified`,
                CONCAT(users.name, ' ', users.surname) as `assessor`,
                CONCAT(if(users2.employee_id != '', users2.employee_id, ''), ' ', if(users2.client_id != '', users2.client_id, ''), ' ', users2.name, ' ', users2.surname) as `subject`, users2.image as `image`,
                lookup_fields1.item_name as `status`,
                compliance_checks.total_out_of as `out_of`, compliance.score_completed_only, compliance.hide_score
                FROM compliance_checks
    						left join lookup_fields1 on lookup_fields1.id = compliance_checks.status_id
                left join compliance on compliance.id = compliance_checks.compliance_id
                left join users on users.id = compliance_checks.assessor_id
                left join users2 on users2.id = compliance_checks.subject_id

                where compliance_checks.id = $pdfid";
      $pdf->SetFont('Arial','',11);
      if($result = $this->dbi->query($sql)) {
        while($myrow = $result->fetch_assoc()) {
          $compliance_id = $myrow['compliance_id'];
          $compliance_title = $myrow['title'];
          $status = $myrow['status'];
          $date = $myrow['date'];
          $last_modified = $myrow['last_modified'];
          $subject = $myrow['subject'];
          $assessor = $myrow['assessor'];
          $hide_score = $myrow['hide_score'];
          $logo = $myrow['image'];
          $score_completed_only = $myrow['score_completed_only'];
          $total_out_of = ($score_completed_only ? 0 : round($myrow['out_of']));
          if($compliance_title) {
            $col = $this->hex2RGB("#CCCCCC");
            $pdf->SetDrawColor($col[0], $col[1], $col[2]); 
//            $pdf->SetXY(10,10); 	$pdf->Cell(68,6,'Date: ' . $date);
  //          $pdf->SetXY(10,17); 	$pdf->Cell(68,6,'Last Modified: '. $last_modified);
            if($logo) {
              $logo = str_replace(".svg", ".png", $logo);
              $pdf->Image($this->f3->get('base_img_folder') . "company_logos/" . $logo, 10, 9, 55);
            } else {
              $pdf->Image($this->f3->get('base_img_folder') . "logo.png", 10, 9, 55);
            }
            $pdf->SetFont('Arial','B',12);
            $pdf->SetXY(72,7); 	$pdf->Cell(110,6,$compliance_title);
            $pdf->SetFont('Arial','',11);
            $pdf->SetXY(72,12.5); 	$pdf->Cell(110,6,'By: ' . $assessor . ' on ' . $date);
            $pdf->SetXY(72,18); 	$pdf->Cell(110,6,'Subject: ' . $subject);
          }
        }
      }
      //****************************************
      //$hide_score = 1;
      
      $line_height = 5.5;
      $cell_width = 56;
      $startx = 10;
      $starty = 33;
      $score_width = 21;
      $score_width = ($hide_score ? 21 : 22);
      
      $cell_width = ($hide_score ? $cell_width + $score_width / 3 : $cell_width);
      
      $y = $starty;
      $oldstarty = $starty;
      $pdf->SetFont('Arial','B',11);
      $pdf->SetXY($startx,$starty-6); 	$pdf->MultiCell($cell_width,6, "Question",1);
      $pdf->SetXY($startx+$cell_width,$starty-6); 	$pdf->MultiCell($cell_width,6, "Answer",1);
      $pdf->SetXY($startx+($cell_width*2),$starty-6); 	$pdf->MultiCell($cell_width,6, "Additional Text",1);
      if(!$hide_score) { $pdf->SetXY($startx+($cell_width*3),$starty-6); 	$pdf->MultiCell($score_width,6, "Score",1); }
      $pdf->SetFont('Arial','',11);
      $sql = "SELECT id, question, answer, answer_colour, additional_text, value, out_of, answer_id FROM `compliance_check_answers` WHERE compliance_check_id = $pdfid";
      $x = 0;
      $old_question = "";
      $total_value = 0;
      $pdf->SetTextColor(30, 30, 30);
      if($result = $this->dbi->query($sql)) {
        while($myrow = $result->fetch_assoc()) {
          $aid = $myrow['id'];
          $answer_id = $myrow['answer_id'];
          $question = strip_tags($myrow['question']);
          $question_show = ($old_question == $question ? "" : $question);
          //$answer = strip_tags($myrow['answer']);
          $answer = str_replace(['‘', '’'], "'", html_entity_decode(strip_tags($myrow['answer'])));
          $answer_colour = $myrow['answer_colour'];
          //$additional_text = $myrow['additional_text'];
          $additional_text = str_replace(['‘', '’'], "'", html_entity_decode($myrow['additional_text']));

          $value = $myrow['value'];
          $out_of = $myrow['out_of'];
          $new_page = 0;
          if(isset($cheight)) {
            if($y + $cheight > 270) {
              $startx = 10;
              $starty = 33;
              $y = $starty;
              $y2 = 0;
              $y3 = 0;
              $oldstarty = $starty;
              $pdf->AddPage();
              if($logo) {
                $logo = str_replace(".svg", ".png", $logo);
                $pdf->Image($this->f3->get('base_img_folder') . "company_logos/" . $logo, 10, 9, 55);
              } else {
                $pdf->Image($this->f3->get('base_img_folder') . "logo.png", 10, 9, 55);
              }
              $pdf->SetFont('Arial','B',12);
              $pdf->SetXY(72,7); 	$pdf->Cell(110,6,$compliance_title);
              $pdf->SetFont('Arial','',11);
              $pdf->SetXY(72,12.5); 	$pdf->Cell(110,6,'By: ' . $assessor . ' on ' . $date);
              $pdf->SetXY(72,18); 	$pdf->Cell(110,6,'Subject: ' . $subject);
              $pdf->SetFont('Arial','B',11);
              $pdf->SetXY($startx,$starty-6); 	$pdf->MultiCell($cell_width,6, "Question",1);
              $pdf->SetXY($startx+$cell_width,$starty-6); 	$pdf->MultiCell($cell_width,6, "Answer",1);
              $pdf->SetXY($startx+($cell_width*2),$starty-6); 	$pdf->MultiCell($cell_width,6, "Additional Text",1);
              if(!$hide_score) { $pdf->SetXY($startx+($cell_width*3),$starty-6); 	$pdf->MultiCell($score_width,6, "Score",1); }
              $pdf->SetFont('Arial','',11);
              $new_page = 1;
            }
          }
          $oldy = $y;
          if(!$hide_score) { 
            if($value || $out_of) {
              $total_value += $value;
              $score = "$value/$out_of";
              if($score_completed_only) $total_out_of += $out_of;
            } else {
              $score = "";
            }
          }
          if($answer == "LBL") {
            $pdf->SetXY($startx+0.5,$starty+0.5); 	$pdf->MultiCell($cell_width*3+19,$line_height, $question_show,0, 'L');
            $y1 = $pdf->GetY();
            $cheight = $line_height;
            $y += $line_height;
          } else if($answer != "AS1gna7ure1ma6e") {
            if($answer_id) {
              $pdf->SetXY($startx+0.5,$starty+0.5); 	$pdf->MultiCell($cell_width-1,$line_height, $question_show,0, 'L');
              $y1 = $pdf->GetY();
              $pdf->SetXY($startx+$cell_width+0.5,$starty+0.5); 	$pdf->MultiCell($cell_width-1,$line_height, $answer,0, 'L');
              $y2 = $pdf->GetY();
              $pdf->SetXY($startx+($cell_width*2)+0.5,$starty+0.5); 	$pdf->MultiCell($cell_width-1,$line_height, $additional_text,0, 'L');
              $y3 = $pdf->GetY();
              if(!$hide_score) { $pdf->SetXY($startx+($cell_width*3)+0.5,$starty+0.5); 	$pdf->MultiCell($score_width,$line_height, $score,0, 'L'); }
            } else {
              $pdf->SetXY($startx+0.5,$starty+0.5); 	$pdf->MultiCell($cell_width-1,$line_height, $question_show,0, 'L');
              $y1 = $pdf->GetY();
              $pdf->SetXY($startx+($cell_width)+0.5,$starty+0.5); 	$pdf->MultiCell($cell_width*2,$line_height, $answer,0, 'L');
              $y2 = $pdf->GetY();
            }
          }
          $y = (isset($y2) ? ($y1 >= $y2 ? $y1 : $y2) : 33);
          $y += (isset($y2) ? ($y1 >= $y2 ? 0 : 0) : $line_height);
          if(isset($y3)) {
            if($y3 >= $y) $y = $y3;
          }
          $starty = $y;
          $cheight = ($new_page ? 6 : $y - $oldy);
/*          if($answer_colour) {
            //$col = $this->alter_brightness($answer_colour, 150);
            //$col = $this->hex2RGB($col);
            $col = $this->hex2RGB($answer_colour);
            $pdf->SetTextColor($col[0], $col[1], $col[2]); 
            //$pdf->SetFillColor($col[0], $col[1], $col[2]); 
          } else {
            $pdf->SetTextColor(0, 0, 0); 
            $pdf->SetFillColor(255, 255, 255);
          }*/
          if($answer != "LBL") {
            $pdf->SetFillColor(255,255,255); 
            if($answer_id) {
              $pdf->SetXY($startx,$oldstarty); 	$pdf->Cell($cell_width,$cheight, '',1);
              $pdf->SetXY($startx+$cell_width,$oldstarty); 	$pdf->Cell($cell_width,$cheight, '',1,0,'',true);
              $pdf->SetXY($startx+$cell_width*2,$oldstarty); 	$pdf->Cell($cell_width,$cheight, '',1);
              if(!$hide_score) { $pdf->SetXY($startx+$cell_width*3,$oldstarty); 	$pdf->Cell($score_width,$cheight, '',1); }
              if($answer_colour) {
                $col = $this->alter_brightness($answer_colour, -50);
                $col = $this->hex2RGB($col);
                $pdf->SetTextColor($col[0], $col[1], $col[2]); 
              }
              $pdf->SetXY($startx+$cell_width,$oldstarty); 	$pdf->MultiCell($cell_width,$line_height, $answer,0, 'L');
              $pdf->SetTextColor(0, 0, 0); 
            } else {
              $pdf->SetXY($startx,$oldstarty); 	$pdf->Cell($cell_width,$cheight, '',1);
              $pdf->SetXY($startx+$cell_width,$oldstarty); 	$pdf->Cell($cell_width*2+($hide_score ? 0 : $score_width),$cheight, '',1);
            }
          } else {
            $pdf->SetFillColor(255,255,230); 
            $pdf->SetTextColor(0, 0, 0); 
            $pdf->SetXY($startx,$oldstarty); 	$pdf->Cell($cell_width*3+($hide_score ? 0 : $score_width),$cheight, '',1,0,'',true);
            $pdf->SetXY($startx+0.5,$oldstarty+0.5); 	$pdf->MultiCell($cell_width*3+19,$line_height, $question_show ,0, 'L');
          }
          $oldstarty = $starty;
          $old_question = $question;
          $x++;
        }
      }
      if(!$hide_score) { 
        if($total_value || $total_out_of) {
          $percent = round(($total_value / $total_out_of) * 100);
          $pdf->SetXY($startx+$cell_width*3,$oldstarty); 	$pdf->Cell($score_width,11, '',1);
          $pdf->SetXY($startx+($cell_width*3),$oldstarty); 	$pdf->MultiCell($score_width,$line_height, round($total_value).'/'.round($total_out_of).' ('.round($percent).'%)',0);
        }
      }
      $base_dir = $this->f3->get('download_folder') . "images/compliance/$pdfid";
      $file_name = "";
      if(file_exists($base_dir)) {
        if (file_exists($base_dir . "/sig.png")) {
          if($starty + $cheight > 220) $pdf->AddPage();
          $pdf->SetFont('Arial','B',13);
          $pdf->SetXY(15,($starty > 220 ? 10 : ($y + 10))); 	$pdf->MultiCell(50,$line_height*2, "Signature ",0);
          $pdf->Image($base_dir . "/sig.png", 10, ($starty > 220 ? 25 : ($starty + 25)), 150);
        }
        $x = 0;
        $dir_list = [];
        $dir = new DirectoryIterator($base_dir);
        foreach ($dir as $fileinfo) {
          if ($fileinfo->isDir() && !$fileinfo->isDot()) {
            $dir_list[$x] = $fileinfo->getFilename();
          }
          $x++;
        }
        sort($dir_list);
        foreach ($dir_list as $dir) {
          if($dir) {
            $d = new RecDir("$base_dir/$dir/",true);
            $sql = "select question_title from compliance_questions where id = $dir";
            if($result = $this->dbi->query($sql)) {
              while($myrow = $result->fetch_assoc()) {
                $question_title = $myrow['question_title'];
              }
            }
            while (false !== ($entry = $d->read())) {
              $ext = strtolower(substr($entry, -3));
              if($ext == "jpg") {
                $file_type = mime_content_type($entry);
                if($file_type == 'image/png') {
                  $new_entry = substr($entry, 0, strlen($entry) - 3) . "png";
                  rename($entry, $new_entry);
                  $entry = $new_entry;
                }
              }
              $pdf->AddPage();
              $pdf->SetFont('Arial','B',13);
              $pdf->SetXY(155,10); 	$pdf->MultiCell(50,$line_height*2, "Image Attachments",0);
              $pdf->SetFont('Arial','',11);
              if(isset($question_title)) { $pdf->SetXY(10,18); 	$pdf->MultiCell(190,$line_height*2, $question_title,0); }
              $file_name = explode("/", $entry);
              $file_name = $file_name[count($file_name)-1];
              $pdf->Image($entry, 10, 35, 150);
            }
            $d->close();
          }
        }
      }
      $pdf->Output();
    }
  }
  
  function draw_graph($id, $title, $labels, $data_items) {
    $str .= '
  <div style="width:75%; text-align:center!important; object-align:center!important; justify-content: center!important;"><canvas id="' . $id . '"></canvas></div>
  <script>
  var ctx = document.getElementById(\'' . $id . '\').getContext(\'2d\');
  var ' . $id . ' = new Chart(ctx, {
      type: \'bar\',
      data: {
          responsive: true,
          maintainAspectRatio: false,

          labels: [' . $labels . '],
          datasets: [{
              label: \'' . $title . '\',
              data: [' . $data_items . '],
              backgroundColor: [
                  \'rgba(255, 99, 132, 1)\',
                  \'rgba(54, 162, 235, 1)\',
                  \'rgba(255, 206, 86, 1)\',
                  \'rgba(75, 192, 192, 1)\',
                  \'rgba(153, 102, 255, 1)\',
                  \'rgba(255, 159, 64, 1)\',
                  \'rgba(255, 99, 132, 1)\',
                  \'rgba(54, 162, 235, 1)\',
                  \'rgba(255, 206, 86, 1)\',
                  \'rgba(75, 192, 192, 1)\',
                  \'rgba(153, 102, 255, 1)\',
                  \'rgba(255, 159, 64, 1)\',
                  \'rgba(255, 99, 132, 1)\',
                  \'rgba(54, 162, 235, 1)\',
                  \'rgba(255, 206, 86, 1)\',
                  \'rgba(75, 192, 192, 1)\',
                  \'rgba(153, 102, 255, 1)\',
                  \'rgba(255, 159, 64, 1)\',
                  \'rgba(255, 99, 132, 1)\',
                  \'rgba(54, 162, 235, 1)\',
                  \'rgba(255, 206, 86, 1)\',
                  \'rgba(75, 192, 192, 1)\',
                  \'rgba(153, 102, 255, 1)\',
                  \'rgba(255, 159, 64, 1)\'
              ],
              borderColor: [
                  \'rgba(255, 99, 132, 1)\',
                  \'rgba(54, 162, 235, 1)\',
                  \'rgba(255, 206, 86, 1)\',
                  \'rgba(75, 192, 192, 1)\',
                  \'rgba(153, 102, 255, 1)\',
                  \'rgba(255, 159, 64, 1)\',
                  \'rgba(255, 99, 132, 1)\',
                  \'rgba(54, 162, 235, 1)\',
                  \'rgba(255, 206, 86, 1)\',
                  \'rgba(75, 192, 192, 1)\',
                  \'rgba(153, 102, 255, 1)\',
                  \'rgba(255, 159, 64, 1)\',
                  \'rgba(255, 99, 132, 1)\',
                  \'rgba(54, 162, 235, 1)\',
                  \'rgba(255, 206, 86, 1)\',
                  \'rgba(75, 192, 192, 1)\',
                  \'rgba(153, 102, 255, 1)\',
                  \'rgba(255, 159, 64, 1)\',
                  \'rgba(255, 99, 132, 1)\',
                  \'rgba(54, 162, 235, 1)\',
                  \'rgba(255, 206, 86, 1)\',
                  \'rgba(75, 192, 192, 1)\',
                  \'rgba(153, 102, 255, 1)\',
                  \'rgba(255, 159, 64, 1)\'
              ],
              borderWidth: 1
          }]
      },
      options: {
          legend: {
            labels: {
               boxWidth: 0,
             }
          },
          scales: {
              yAxes: [{
                  ticks: {
                      beginAtZero: true
                  }
              }]
          }
      }
  });


  </script>      
   ';
    return $str;
}



  function MyStats() {

    $calendar = new CalendarController($this->f3);
    return $calendar->draw();
    
    
    $sql = "select id from stats where title like '%bonnyrigg%'";
    $result = $this->dbi->query($sql);
    while($myrow = $result->fetch_assoc()) {
      $hide_js = ($id ? 1 : 0);
      $id = $myrow['id'];
      $str .= '<div class="fl">' . $this->ShowStats($id, $hide_js) . '</div>';
    }
    return $str;    
  }

  function ShowStats($stat_id=0, $hide_js=0){
    
    $hide_choices = ($stat_id ? 1 : 0);
    
    $str .= ($hide_js ? "" : '
      <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.3/Chart.js"></script>
    ');
    $stat_id = ($stat_id ? $stat_id : (isset($_GET['stat_id']) ? $_GET['stat_id'] : 0));
    
    
    
    $loginUserDivisions = $this->get_divisions($_SESSION['user_id'],0,1);
              $loginUserDivisionsArray = explode(',',$loginUserDivisions);
              $loginUserDivisionsArray = array_map('trim', $loginUserDivisionsArray);
              $loginUserDivisionsStr = implode(',',$loginUserDivisionsArray); 
            $allowedcat = [2101,2007,2008,2009,2010];   
            
            $userAllowedSiteIds = $this->getUserSiteIdsByUserId($_SESSION['user_id']);

            if ($userAllowedSiteIds != "all") {
                $filterAllowedSiteIds = "and cc.subject_id in (" . $userAllowedSiteIds . ")";
            }else{
                $filterAllowedSiteIds = "";
            }
            
           
            $dataStat .= '<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.3/Chart.js"></script>';           
            
         $statSql =  "select CONCAT(' <a class=\"\list_a\"  href=\"/Reporting/ShowStats?stat_id=', compliance.id, '\">', compliance.title , '</a> ') as `link`            
              FROM compliance
              left join lookup_fields2 on lookup_fields2.id = compliance.category_id
              where compliance.category_id in (".implode(",",$allowedcat).") and (compliance.division_id = '' or compliance.division_id in (".$loginUserDivisionsStr.")) and 
              compliance.min_user_level_id <= " . $_SESSION['u_level'] . " and compliance.is_active = 1 and 
              ((compliance.id in (select compliance_id from compliance_auditors where user_id = ".$_SESSION['user_id'].")) or (compliance.allow_all_access = 1 and compliance.id in (select foreign_id from lookup_answers where table_assoc = 'compliance')))
              order by title ";       
            
    
    if(!$hide_choices) {
  
      $result = $this->dbi->query($statSql);
      while($myrow = $result->fetch_assoc()) {
        $str .= $myrow['link'];
      }
    }
    $stat_id = $_REQUEST['stat_id'];
    
    if($stat_id) {
              
   $sql = "SELECT concat(MONTHNAME(cc.check_date_time),' ',YEAR(cc.check_date_time)) label,COUNT(cc.id) value, com.title 
FROM compliance_checks cc
LEFT JOIN compliance com ON com.id = cc.compliance_id
WHERE com.category_id in (" . implode(",", $allowedcat) . ") and (com.division_id = '' or com.division_id in (" . $loginUserDivisionsStr . ")) and cc.compliance_id = " . $stat_id . " AND cc.status_id = 524 " . $filterAllowedSiteIds . "
    and datediff(curdate(),cc.check_date_time) < 549
GROUP BY YEAR(cc.check_date_time) ,MONTH(cc.check_date_time) 
ORDER BY YEAR(cc.check_date_time) ASC,MONTH(cc.check_date_time) ASC";
   
      $result = $this->dbi->query($sql);
      while($myrow = $result->fetch_assoc()) {
        $title = $myrow['title'];
        $label = $myrow['label'];
        $value = $myrow['value'];
        if($label) {
          $labels .= "'$label',";
          $data_items .= "$value,";
        }
      }
      $labels = substr($labels, 0, strlen($labels) - 1);
      $data_items = substr($data_items, 0, strlen($data_items) - 1);
      
      
      $this->list_obj->title = "Results Table";
      $this->list_obj->sql = "SELECT concat(MONTHNAME(cc.check_date_time),' ',YEAR(cc.check_date_time)) Label,COUNT(cc.id) Result, com.title Title
FROM compliance_checks cc
LEFT JOIN compliance com ON com.id = cc.compliance_id
WHERE com.category_id in (" . implode(",", $allowedcat) . ") and (com.division_id = '' or com.division_id in (" . $loginUserDivisionsStr . ")) and cc.compliance_id = " . $stat_id . " AND cc.status_id = 524 " . $filterAllowedSiteIds . "
    and datediff(curdate(),cc.check_date_time) < 549
GROUP BY YEAR(cc.check_date_time) ,MONTH(cc.check_date_time) 
ORDER BY YEAR(cc.check_date_time) ASC,MONTH(cc.check_date_time) ASC";
      

    if($title != ""){      
          $str .= '</div><div class="col-xl-12 col-m-12 col-l-12 col-lg-12"><h3>Graph of Results for '.$title.'</h3>';
          $str .= '<div class="col-xl-12 col-m-12 col-l-12 col-lg-12 d-flex justify-content-center">'; // Ensure the graph div spans the full width and centers the content
          $str .= $this->draw_graph("cha" . preg_replace('/[^a-z0-9]+/i', '', $title), $title, $labels, $data_items);
          $str .= '</div>'; // Close the col-12 div for the graph

           // Table positioned below the graph
    $str .= '<div class="col-xl-12 col-m-12 col-l-12 col-lg-12">';
    $tableHtml = $this->list_obj->draw_list(); // Assuming this draws the table
    $tableHtml = str_replace('<table ', '<table class="uk-table uk-table-hover uk-table-divider uk-table-striped uk-table-small" ', $tableHtml);
    $str .= $tableHtml;
    $str .= '</div>';
    }else{
        $str .= '</div><div class="col-xl-12 col-m-12 col-l-12 col-lg-12"><h3> Record Not Found </h3>';
    }
          $str .= '</div><div class="col-xl-12 col-m-12 col-l-12 col-lg-12"></div><script>
            // Reload the page every 30 seconds (30000 milliseconds)
            setTimeout(function () {
                location.reload();
            }, 30000);
        </script>';

        }
    
    return $str;
    
  }
  
  function EditStats() {
//return $this->ShowStats(2);
  include('generate_stats.php');
  
    $regen = (isset($_GET['regen']) ? $_GET['regen'] : null);

//return "<textarea>" . $this->generate_stats() . "</textarea>";

    if($regen) {
      $rgn = new stats_generator;
      $rgn->generate_stats($this->dbi);
      echo $this->redirect('EditStats');
    }
    $str .= '<a class="list_a" href="EditStats?regen=1">Regenerate Stats</a>';
    
    $this->list_obj->sql = "select stats.id as `idin`, lookup_fields.item_name as `Update Frequency`, CONCAT('<nobr>', lookup_fields2.item_name, ' x ', stats.repeat_count, '</nobr>') as `Repeat`, stats.date_field as `Date Field`, stats.title as `Title`, stats.query as `Query`, 'Edit' as `*`, 'Delete' as `!` from stats
                            left join lookup_fields on lookup_fields.id = stats.update_frequency_id
                            left join lookup_fields2 on lookup_fields2.id = stats.repeat_id

    ;";
    //return $this->list_obj->sql;
    $this->editor_obj->table = "stats";
    $style = 'style="width: 225px;"';
    $qry = $this->get_lookup('stats_update_frequency');
    $this->editor_obj->form_attributes = array(
      array("selUpdateFrequency", "txtTitle", "txaQuery", "selRepeatID", "txtRepeatCount", "txtDateField"),
      array("Update Frequency", "Title", "Query", "Repeat By", "Repeat Count", "Date Field"),
      array("update_frequency_id", "title", "query", "repeat_id", "repeat_count", "date_field"),
      array($qry, "", "", $qry, "", ""),
      array($style, $style, 'style="width: 100%; height: 220px;"', $style, $style, $style),
      array("n", "c", "c", "n", "n", "n")
    );
    $this->editor_obj->button_attributes = array(
      array("Add New", "Save", "Reset", "Filter"),
      array("cmdAdd", "cmdSave", "cmdReset", "cmdFilter"),
      array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();", "filter()"),
      array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit", "js_function_filter")
    );
    $this->editor_obj->form_template = '
    <div class="form-wrapper" style="">
      <div class="form-header" style="">Stats</div>
      <div class="form-content">
        <div class="fl"><nobr>tselUpdateFrequency</nobr><br />selUpdateFrequency</div>
        <div class="fl"><nobr>ttxtTitle</nobr><br />txtTitle</div>
        <div class="fl"><nobr>tselRepeatID</nobr><br />selRepeatID</div>
        <div class="fl"><nobr>ttxtRepeatCount</nobr><br />txtRepeatCount</div>
        <div class="fl"><nobr>ttxtDateField</nobr><br />txtDateField</div>
        <div class="cl"></div>
        <nobr>ttxaQuery</nobr><br />txaQuery
        <div class="cl"></div>
        '.$this->editor_obj->button_list().'
      </div>
    </div>';
    $this->editor_obj->editor_template = 'editor_form<div class="cl"></div>editor_list';
    $str .= $this->editor_obj->draw_data_editor($this->list_obj);
    return $str;
  }    
  
  Function OccurrenceReports() {
    /*
    Alarm Systems: alarm or alarm system
    Cars / Car Parks: carpark, "car park", parking, vehicle, "cle theft", "car theft", "stolen car", abandon, 
    CCTV: cctv, camera
    Prohibition Notice: prohibition
    Vandalism/Damage: vandalism or damage
    Antisocial Behaviour
    Work Health and Safety: WH&S or "health" or "safety"
    Tennancy Issues: tennant, tennancy, tenancy
    Operational Issues: broken, "not working"
    */
    $sid = (isset($_GET['sid']) ? $_GET['sid'] : null);
    if(!$sid) exit;
    
    $site = $this->get_sql_result("select TRIM(CONCAT(name, ' ', surname)) as `result` from users where id = $sid");
    
    $month = (isset($_GET['month']) ? $_GET['month'] : -1);

    $month_count = 12;
    $str .= "<h3>Occurrence Log Reports for $site from ". ($month == -1 ? "Last Month" : (!$month ? 'This Month' : abs($month) . " Months Ago"))  ."</h3>";
    for($i = 0; $i <= $month_count; $i++) {
      $str .= '<a class="division_nav_item '.($month == -$i ? "division_nav_selected" : "").'" uk-tooltip="title: View Reports from<br />' . ($i == 0 ? 'This Month' : ($i == 1 ? 'Last Month' : "$i Months Ago")) . '." href="'.$this->f3->get('main_folder').'Reporting/OccurrenceReports?sid='.$sid.'&month=' . -$i . (isset($_GET['show_min']) ? "&show_min=1" : '') . '">' . ($i == 0 ? 'This Month' : ($i == 1 ? 'Last Month' : $i)) . '</a>';
    }

    $str .= '
    <script>
    function selectText(containerid) {
      if (document.selection) { // IE
        var range = document.body.createTextRange();
        range.moveToElementText(document.getElementById(containerid));
        range.select();
      } else if (window.getSelection) {
        var range = document.createRange();
        range.selectNode(document.getElementById(containerid));
        window.getSelection().removeAllRanges();
        window.getSelection().addRange(range);
      }
    }
    </script>
    <div id="selectable" onclick="selectText(\'selectable\')">
    ';
    
    /*$titles = Array("Alarm Systems", "Cars / Car Parks", "CCTV", "Prohibition Notices", "Vandalism/Damage", "Antisocial Behaviour", "Work Health and Safety", "Tennancy Issues", "Operational Issues");
    $searches = Array(
      array("alarm","alarm system"),
      array("carpark","car park","parking","vehicle","le theft","car theft","stolen car","abandon"),
    );*/
    
    $sql = "select item_name, value from lookup_fields where lookup_id = 123 and value != '' order by sort_order, item_name";
    if($result = $this->dbi->query($sql)) {
      $c = 0;
      while($myrow = $result->fetch_assoc()) {
        $item_name = $myrow['item_name'];
        $value = $myrow['value'];
        //$values = explode(",", $value);
        $values = array_map('trim', explode(',', $value));

        $titles[$c] = $item_name;
        $searches[$c] = $values;
        $c++;
      }
    }

    $qrys = Array();
    $i = 0;
    foreach($searches as $search) {
      $j = 0; $j1 = 0; $j2 = 0; $include = ""; $exclude = "";
      foreach($search as $srch) {
        $srch = mysqli_real_escape_string($this->dbi, $srch);
        if(substr($srch, 0, 1) == "-") {
          $cond = "NOT";
          $srch = substr($srch, 1);
          $exclude .= ($j1 ? " and " : "") . " LOWER(description) NOT like '%$srch%' ";
          $j1++;
        } else {
          $include .= ($j2 ? " or " : "") . " LOWER(description) like '%$srch%' ";
          $j2++;
        }
//        $qrys[$i] .= ($j ? " or " : "") . " LOWER(description) $cond like '%$srch%' ";
        $j++;
      }
      $qrys[$i] = ($include ? "($include)" : "") . ($exclude ? " and ($exclude)" : "");
      //$str .= "<h3>{$qrys[$i]}</h3>";
      $i++;
    }

    
    for($x = 0; $x < sizeof($titles); $x++) {
      $txt = "";
      $sql = "select occurrence_log.id, CONCAT(DATE_FORMAT(occurrence_log.date, '%a %d-%b-%Y at %H:%i')) as `date`,
                     CONCAT(users.name, ' ', users.surname) as `added_by`, occurrence_log.description
                     from occurrence_log
                     left join users on users.id = occurrence_log.user_id
                     left join users2 on users2.id = occurrence_log.site_id
                     where users2.id = 335
                     AND YEAR(occurrence_log.date) = YEAR(CURRENT_DATE + INTERVAL $month MONTH)
                     AND MONTH(occurrence_log.date) = MONTH(CURRENT_DATE + INTERVAL $month MONTH)
                     and
                     ({$qrys[$x]})
                     order by occurrence_log.date";
//                     return $sql;
                     
                     
      $result = $this->dbi->query($sql);
      if($result->num_rows) {
        $str .= "<br /><br /><h3>{$titles[$x]} ({$result->num_rows} Item".$this->pluralise($result->num_rows).")</h3>";
        while($myrow = $result->fetch_assoc()) {
          $description = nl2br($myrow['description']);
          $lines = explode("<br />", $description);
          $description = "";
          foreach($lines as $line) {
            //$str .= $line;
            $line = trim($line);
            foreach($searches[$x] as $search) {
              //$str .= "$line ";
              if(stripos($line, $search) !== false) {
                $description .= $line . "<br />";
                break;
              }
            }
            
          }
          //$description = str_replace("<br /><br /><br />", "<br /><br />", $description);
          if($description) $str .=  "<br /><b>" . $myrow['added_by'] . " wrote on " . $myrow['date'] . "</b><br />$description";
        }
      }
    }
    $str .= "</div>";
    
    return $str;
  }

  function ReportScheduler() {
      $this->editor_obj->table = "compliance_schedules";
      $style = 'style="width: 100%;"';
      $style_small = 'style="width: 120px;"';
      $style_mid_sel = 'style="width: 260px;"';
       $style_mid_txt = 'style="width: 300px;"';
      
      $report_sql = "select * from (select 0 as id, '--- Select ---' as item_name) as a union all select * from (select id, title as `item_name` from compliance where title NOT LIKE '%MAIN TEMPLATE%' order by item_name) as b";
      
      
      $staff_sql = $this->user_dropdown(107,504,0,0,0,1,1);
      $site_sql = $this->user_dropdown(384,107,530,0,0,1,1);
      
      $this->list_obj->sql = "
      select compliance_schedules.id as `idin`, compliance.title as `Report`, lookup_fields.item_name as `Frequency`, 
      compliance_schedules.start_date as `Start Date`, compliance_schedules.send_time as `Send Time`, CONCAT('<a href=\"mailto:', users.email, '\">', users.name, ' ', users.surname, '</a>') as `Staff Member`,
      CONCAT(users2.name, ' ', users2.surname) as `Location`,subject `Subject`, if(compliance_schedules.is_email = '', 'No', 'Yes') as `Email`, if(compliance_schedules.is_sms = '', 'No', 'Yes') as `SMS`, if(compliance_schedules.avoid_public_holidays = '', 'No', 'Yes') as `Avoid Holidays`,
      'Edit' as `*`, 'Delete' as `!`
      from compliance_schedules
      left join compliance on compliance.id = compliance_schedules.compliance_id
      left join users on users.id = compliance_schedules.staff_id
      left join users2 on users2.id = compliance_schedules.site_id
      left join lookup_fields on lookup_fields.id = compliance_schedules.frequency_id      
      order by compliance.title
      ";
/*
  `frequency_id` bigint(20) NOT NULL,
  `start_date` date NOT NULL,
  `send_time` time NOT NULL,
  `is_email` tinyint(3) NOT NULL,
  `is_sms` tinyint(3) NOT NULL
*/
      $this->editor_obj->form_attributes = array(
        array("selReport", "cmbStaff", "cmbSite","txtSubject","selFrequency", "calStartDate", "ti2SendTime", "chkIsEmail", "chkIsSMS", "chkAvoidPublicHolidays"),
        array("Report", "Staff Member to Schedule", "Location","Assessment Subject","Frequency", "Start Date", "Send Time", "Send Email", "Send SMS", "Avoid Public Holidays"),
        array("compliance_id", "staff_id", "site_id","subject","frequency_id", "start_date", "send_time", "is_email", "is_sms", "avoid_public_holidays"),
        array($report_sql, $staff_sql, $site_sql,'',$this->get_lookup('schedule_frequency')),
        array($style_mid_sel, "", "", $style_mid_txt,$style_small,$style_small, $style_small, "", ""),
        array("n", "c", "c", "c", "n","n","n", "n", "n", "n")
      );
      $this->editor_obj->button_attributes = array(
        array("Add New", "Save", "Reset"),
        array("cmdAdd", "cmdSave", "cmdReset"),
        array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
        array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
      );
      $this->editor_obj->form_template = '
      
          <div class="fl"><nobr>tselReport</nobr><br />selReport</div>
          <div class="fl"><nobr>tcmbStaff</nobr><br />cmbStaff</div>
          <div class="fl"><nobr>tcmbSite</nobr><br />cmbSite</div>
          <div class="fl"><nobr>ttxtSubject</nobr><br />txtSubject</div>
          <div class="fl"><nobr>tselFrequency</nobr><br />selFrequency</div>
          <div class="fl"><nobr>tcalStartDate</nobr><br />calStartDate</div>
          <div class="fl"><nobr>tti2SendTime</nobr><br />ti2SendTime</div>
          <div class="fl"><nobr>chkIsEmail tchkIsEmail</nobr>
          <br /><nobr>chkIsSMS tchkIsSMS</nobr>
          <br /><nobr>chkAvoidPublicHolidays tchkAvoidPublicHolidays</nobr>
          </div>

          <div class="cl"></div>
          <br /><div class="fl">'.$this->editor_obj->button_list() . '</div>
          <div class="cl"></div>';
          // <div class="fl"><b class="fl">&nbsp; &nbsp; &nbsp;On Filter: </b>' . $checks . '</div>
      $this->editor_obj->editor_template= '<h3>Report Scheduler</h3>editor_form<div class="cl"></div>editor_list';
    
    $str .= $this->editor_obj->draw_data_editor($this->list_obj);
    
    $action = (isset($_POST['hdnAction']) ? $_POST['hdnAction'] : null);
        if ($action == "add_record" || $action == "save_record") {

                if($action == "add_record"){
                    $save_id = $this->editor_obj->last_insert_id;
                } else if ($action == "save_record") {
                    $save_id = $this->editor_obj->idin;
                                    }
                
                $this->complianceSchedulesCronUpdate($save_id);
                
                
        }
    
    
    
    
    
    
    
    return $str;
    
  }

  
}

?>