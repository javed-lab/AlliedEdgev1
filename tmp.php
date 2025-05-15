  function alter_brightness($colourstr, $steps) {
    $colourstr = str_replace('#','',$colourstr);
    $rhex = substr($colourstr,0,2);
    $ghex = substr($colourstr,2,2);
    $bhex = substr($colourstr,4,2);
    $r = hexdec($rhex);
    $g = hexdec($ghex);
    $b = hexdec($bhex);
    $r = dechex(max(0,min(255,$r + $steps)));
    $g = dechex(max(0,min(255,$g + $steps)));  
    $b = dechex(max(0,min(255,$b + $steps)));
    $r = str_pad($r,2,"0",STR_PAD_LEFT);
    $g = str_pad($g,2,"0",STR_PAD_LEFT);
    $b = str_pad($b,2,"0",STR_PAD_LEFT);
    $cor = '#'.$r.$g.$b;
    return $cor;
  }
  function hex2RGB($color) {
    $color = str_replace('#', '', $color);
    if (strlen($color) != 6) { return array(0,0,0); }
    $rgb = array();
    for ($x=0;$x<3;$x++){
        $rgb[$x] = hexdec(substr($color,(2*$x),2));
    }
    return $rgb;
  }
  function Pdf($f3, $params) {
    session_start();
    $pdfid = $params['p1'];
    if($_SESSION['user_id'] && $pdfid) {
      $allow_access = 1;
      
    }
    if($allow_access) {
      ini_set('display_startup_errors',1);
      ini_set('display_errors',1);
      error_reporting(-1);
      $pdf = new FPDF('P','mm','A4');
      $pdf->AddPage();
      $pdf->SetFont('Arial','',42);
      $sql = "select compliance.id as compliance_id, compliance.title, compliance_checks.id as `compliance_check_id`,
                CONCAT(DATE_FORMAT(compliance_checks.check_date_time, '%d/%b/%Y %h:%i')) as `date`,
                CONCAT(DATE_FORMAT(compliance_checks.last_modified, '%d/%b/%Y %h:%i')) as `last_modified`,
                CONCAT(users.employee_id, ' ', users.client_id, ' ', users.name, ' ', users.surname) as `assessor`,
                CONCAT(if(users.employee_id != '', users.employee_id, ''), ' ', if(users.client_id != '', users.client_id, ''), ' ', users.name, ' ', users.surname) as `assessor`,
                CONCAT(if(users2.employee_id != '', users2.employee_id, ''), ' ', if(users2.client_id != '', users2.client_id, ''), ' ', users2.name, ' ', users2.surname) as `subject`,
                lookup_fields1.item_name as `status`,
                compliance_checks.total_out_of as `out_of`, compliance.score_completed_only
                FROM compliance_checks
    						left join lookup_fields1 on lookup_fields1.id = compliance_checks.status_id
                left join compliance on compliance.id = compliance_checks.compliance_id
                left join users on users.id = compliance_checks.assessor_id
                left join users2 on users2.id = compliance_checks.subject_id
                where compliance_checks.id = $pdfid";
      $pdf->SetFont('Arial','',11);
      if($result = $dbi->query($sql)) {
        while($myrow = $result->fetch_assoc()) {
          $compliance_id = $myrow['compliance_id'];
          $compliance_title = $myrow['title'];
          $status = $myrow['status'];
          $date = $myrow['date'];
          $last_modified = $myrow['last_modified'];
          $subject = $myrow['subject'];
          $assessor = $myrow['assessor'];
          $score_completed_only = $myrow['score_completed_only'];
          $total_out_of = ($score_completed_only ? 0 : round($myrow['out_of']));
          if($compliance_title) {
            $col = $this->hex2RGB("#CCCCCC");
            $pdf->SetDrawColor($col[0], $col[1], $col[2]); 
            $pdf->SetXY(10,10); 	$pdf->Cell(68,6,'Date: ' . $date);
            $pdf->SetXY(10,17); 	$pdf->Cell(68,6,'Last Modified: '. $last_modified);
            $pdf->SetXY(78,10); 	$pdf->Cell(118,6,'Assessor: '. $assessor);
            $pdf->SetXY(78,17); 	$pdf->Cell(118,6,'Subject: ' . $subject);
          }
        }
      }
      $line_height = 5.5;
      $cell_width = 56;
      $startx = 10;
      $starty = 33;
      $y = $starty;
      $oldstarty = $starty;
      $pdf->SetFont('Arial','B',11);
      $pdf->SetXY($startx,$starty-6); 	$pdf->MultiCell($cell_width,6, "Question",1);
      $pdf->SetXY($startx+$cell_width,$starty-6); 	$pdf->MultiCell($cell_width,6, "Answer",1);
      $pdf->SetXY($startx+($cell_width*2),$starty-6); 	$pdf->MultiCell($cell_width,6, "Additional Text",1);
      $pdf->SetXY($startx+($cell_width*3),$starty-6); 	$pdf->MultiCell(22,6, "Score",1);
      $pdf->SetFont('Arial','',11);
      $sql = "SELECT id, question, answer, answer_colour, additional_text, value, out_of, answer_id FROM `compliance_check_answers` WHERE compliance_check_id = $pdfid";
      $x = 0;
      $total_value = 0;
      $pdf->SetTextColor(30, 30, 30);
      if($result = $dbi->query($sql)) {
        while($myrow = $result->fetch_assoc()) {
          $aid = $myrow['id'];
          $answer_id = $myrow['answer_id'];
          $question = strip_tags($myrow['question']);
          $answer = strip_tags($myrow['answer']);
          $answer_colour = $myrow['answer_colour'];
          $additional_text = $myrow['additional_text'];
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
              $pdf->SetXY(10,10); 	$pdf->Cell(68,6,'Date: ' . $date);
              $pdf->SetXY(10,17); 	$pdf->Cell(68,6,'Last Modified: '. $last_modified);
              $pdf->SetXY(78,10); 	$pdf->Cell(118,6,'Assessor: '. $assessor);
              $pdf->SetXY(78,17); 	$pdf->Cell(118,6,'Subject: ' . $subject);
              $pdf->SetFont('Arial','B',11);
              $pdf->SetXY($startx,$starty-6); 	$pdf->MultiCell($cell_width,6, "Question",1);
              $pdf->SetXY($startx+$cell_width,$starty-6); 	$pdf->MultiCell($cell_width,6, "Answer",1);
              $pdf->SetXY($startx+($cell_width*2),$starty-6); 	$pdf->MultiCell($cell_width,6, "Additional Text",1);
              $pdf->SetXY($startx+($cell_width*3),$starty-6); 	$pdf->MultiCell(22,6, "Score",1);
              $pdf->SetFont('Arial','',11);
              $new_page = 1;
            }
          }
          $oldy = $y;
          if($value || $out_of) {
            $total_value += $value;
            $score = "$value/$out_of";
            if($score_completed_only) $total_out_of += $out_of;
          } else {
            $score = "";
          }
          if($answer == "LBL") {
            $pdf->SetXY($startx+0.5,$starty+0.5); 	$pdf->MultiCell($cell_width*3+19,$line_height, $question,0, 'L');
            $y1 = $pdf->GetY();
            $cheight = $line_height;
            $y += $line_height;
          } else if($answer != "AS1gna7ure1ma6e") {
            if($answer_id) {
              $pdf->SetXY($startx+0.5,$starty+0.5); 	$pdf->MultiCell($cell_width-1,$line_height, $question,0, 'L');
              $y1 = $pdf->GetY();
              $pdf->SetXY($startx+$cell_width+0.5,$starty+0.5); 	$pdf->MultiCell($cell_width-1,$line_height, $answer,0, 'L');
              $y2 = $pdf->GetY();
              $pdf->SetXY($startx+($cell_width*2)+0.5,$starty+0.5); 	$pdf->MultiCell($cell_width-1,$line_height, $additional_text,0, 'L');
              $y3 = $pdf->GetY();
              $pdf->SetXY($startx+($cell_width*3)+0.5,$starty+0.5); 	$pdf->MultiCell(22,$line_height, $score,0, 'L');
            } else {
              $pdf->SetXY($startx+0.5,$starty+0.5); 	$pdf->MultiCell($cell_width-1,$line_height, $question,0, 'L');
              $y1 = $pdf->GetY();
              $pdf->SetXY($startx+($cell_width)+0.5,$starty+0.5); 	$pdf->MultiCell($cell_width*2+19,$line_height, $answer,0, 'L');
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
          if($answer_colour) {
            $col = $this->alter_brightness($answer_colour, 150);
            $col = $this->hex2RGB($col);
            $pdf->SetFillColor($col[0], $col[1], $col[2]); 
          } else {
            $pdf->SetFillColor(255, 255, 255);
          }
          if($answer != "LBL") {
            if($answer_id) {
              $pdf->SetXY($startx,$oldstarty); 	$pdf->Cell($cell_width,$cheight, '',1);
              $pdf->SetXY($startx+$cell_width,$oldstarty); 	$pdf->Cell($cell_width,$cheight, '',1,0,'',true);
              $pdf->SetXY($startx+$cell_width*2,$oldstarty); 	$pdf->Cell($cell_width,$cheight, '',1);
              $pdf->SetXY($startx+$cell_width*3,$oldstarty); 	$pdf->Cell(22,$cheight, '',1);
              $pdf->SetXY($startx+$cell_width,$oldstarty); 	$pdf->MultiCell($cell_width,$line_height, $answer,0, 'L');
            } else {
              $pdf->SetXY($startx,$oldstarty); 	$pdf->Cell($cell_width,$cheight, '',1);
              $pdf->SetXY($startx+$cell_width,$oldstarty); 	$pdf->Cell($cell_width*2+22,$cheight, '',1);
            }
          } else {
            $pdf->SetFillColor(255,255,230); 
            $pdf->SetXY($startx,$oldstarty); 	$pdf->Cell($cell_width*3+22,$cheight, '',1,0,'',true);
            $pdf->SetXY($startx+0.5,$oldstarty+0.5); 	$pdf->MultiCell($cell_width*3+19,$line_height, $question ,0, 'L');
          }
          $oldstarty = $starty;
          $old_answer = $answer;
          $x++;
        }
      }
      if($total_value || $total_out_of) {
        $percent = round(($total_value / $total_out_of) * 100);
        $pdf->SetXY($startx+$cell_width*3,$oldstarty); 	$pdf->Cell(22,11, '',1);
        $pdf->SetXY($startx+($cell_width*3),$oldstarty); 	$pdf->MultiCell(22,$line_height, round($total_value).'/'.round($total_out_of).' ('.round($percent).'%)',0);
      }
      $base_dir = "/home/Edge/downloads/images/compliance/$pdfid";
      $file_name = "";
      if(file_exists($base_dir)) {
        if (file_exists($base_dir . "/sig.png")) {
          if($starty + $cheight > 220) $pdf->AddPage();
          $pdf->SetFont('Arial','B',13);
          $pdf->SetXY(15,($starty > 220 ? 10 : ($y + 10))); 	$pdf->MultiCell(50,$line_height*2, "Signature ",0);
          $pdf->Image($base_dir . "/sig.png", 10, ($starty > 220 ? 25 : ($starty + 25)), 150);
        }
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
          if($dir) {
            $d = new RecDir("$base_dir/$dir/",true);
            $sql = "select question_title from compliance_questions where id = $dir";
            if($result = $dbi->query($sql)) {
              while($myrow = $result->fetch_assoc()) {
                $question_title = $myrow['question_title'];
              }
            }
            while (false !== ($entry = $d->read())) {
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
