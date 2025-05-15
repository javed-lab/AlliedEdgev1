<?php

class ComplianceController extends Controller {

    protected $f3;

    function __construct($f3) {
        $this->f3 = $f3;
        $this->list_obj = new data_list;
        $this->editor_obj = new data_editor;
        $this->db_init();
    }

    function incident_report() {
        $str = '
      <h3>Type of Incident</h3><br />
      <a class="list_a" href="Compliance?report_id=5&new_report=1">Security Incident</a> &nbsp; &nbsp; <a class="list_a" href="Compliance?report_id=15&new_report=1">Mobile Patrol - Incident</a><br /><br />
      <a class="list_a" href="Compliance?report_id=6&new_report=1">Public Liability</a><br /><br />
      <a class="list_a" href="Compliance?report_id=10&new_report=1">Damage or Loss Report</a><br /><br />
      <a class="list_a" href="Compliance?report_id=29&new_report=1">Hazard or Near Miss Report</a><br /><br />

      <h3>Bonnyrigg Plaza Incident Reports Below</h3>
      <a class="list_a" href="Compliance?report_id=12&site_id=169">Start a Slips and Falls Report for Bonnyrigg Plaza</a><br /><br />
      
    ';
        return $str;
    }

    function GetMultiCellHeight($pdf, $width, $text) {
        $pdf2 = clone $pdf;
        $pdf2->SetXY(0, 0);
        $pdf2->AddPage();
        $pdf2->MultiCell($width, 5.5, $text, 0, 'L');
        $height = $pdf2->getY();
        //$pdf2->deletePage($pdf2->getPage());
        return $height;
    }

    function new_page($hide_score, $cell_width, $compliance_title, $assessor, $date, $subject, $score_width, &$pdf, &$startx, &$starty, &$y, &$y2, &$y3, &$oldstarty, &$new_page, $img) {
        $startx = 10;
        $starty = 33;
        $y = $starty;
        $y2 = 0;
        $y3 = 0;
        $oldstarty = $starty;
        $pdf->AddPage();

        $pdf->Image($img, 10, 8, 55);

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetXY(72, 7);
        $pdf->Cell(110, 6, $compliance_title);
        $pdf->SetFont('Arial', '', 11);
        $pdf->SetXY(72, 12.5);
        $pdf->Cell(110, 6, 'By: ' . $assessor . ' on ' . $date);
        $pdf->SetXY(72, 18);
        $pdf->Cell(110, 6, 'Subject: ' . $subject);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetXY($startx, $starty - 6);
        $pdf->MultiCell($cell_width, 6, "Question", 1);
        $pdf->SetXY($startx + $cell_width, $starty - 6);
        $pdf->MultiCell($cell_width, 6, "Answer", 1);
        $pdf->SetXY($startx + ($cell_width * 2), $starty - 6);
        $pdf->MultiCell($cell_width, 6, "Additional Text", 1);
        if (!$hide_score) {
            $pdf->SetXY($startx + ($cell_width * 3), $starty - 6);
            $pdf->MultiCell($score_width, 6, "Score", 1);
        }
        $pdf->SetFont('Arial', '', 11);
        $new_page = 1;
    }

    function Pdf($f3, $params) {

        session_start();
        $pdfid = $params['p1'];

        if ($_SESSION['user_id'] && $pdfid) {
            $allow_access = 1;
            $this->f3->set('lids', $_SESSION['lids']);
            $this->f3->set('is_client', (array_search(104, $this->f3->get('lids')) !== false) ? 1 : 0);
            $this->f3->set('is_site', (array_search(384, $this->f3->get('lids')) !== false) ? 1 : 0);
            $is_client = ($this->f3->get('is_client') || $this->f3->get('is_site') ? 1 : 0);
        }
        $default_img = $this->f3->get('base_img_folder_static') . "logo-print.png";
        $img = $default_img;
        if (!$allow_access) {
            echo $this->redirect($this->f3->get('full_url') . "login?page_from=" . urlencode($this->f3->get('main_folder') . "CompliancePDF/$pdfid"));
        } else {
            ini_set('display_startup_errors', 1);
            ini_set('display_errors', 1);
            error_reporting(-1);
            $pdf = new FPDF('P', 'mm', 'A4');
            $pdf->AddPage();
            $pdf->SetFont('Arial', '', 42);
            $sql = "select rosters.division_id rosterDivisionId,compliance.division_id,compliance_checks.assessor_id, associations.parent_user_id clientid,compliance.id as compliance_id, compliance.title, compliance.logo_print, compliance_checks.id as `compliance_check_id`,
                CONCAT(DATE_FORMAT(compliance_checks.check_date_time, '%d-%b-%Y %H:%i')) as `date`,
                CONCAT(DATE_FORMAT(compliance_checks.last_modified, '%d-%b-%Y %H:%i')) as `last_modified`,
                CONCAT(users.name, ' ', users.surname) as `assessor`,
                CONCAT(if(users2.employee_id != '', users2.employee_id, ''), ' ', if(users2.client_id != '', users2.client_id, ''), ' ', users2.name, ' ', users2.surname) as `subject`, users2.image as `image`,
                lookup_fields1.item_name as `status`,
                compliance_checks.total_out_of as `out_of`, compliance.score_completed_only, compliance.hide_score
                FROM compliance_checks
                left join roster_times_staff rts on rts.id = compliance_checks.subject_id
                left join roster_times rt on rt.id = rts.roster_time_id
                left join rosters  on rosters.id = rt.roster_id
    		left join lookup_fields1 on lookup_fields1.id = compliance_checks.status_id
                left join compliance on compliance.id = compliance_checks.compliance_id
                left join users on users.id = compliance_checks.assessor_id
                left join users2 on users2.id = compliance_checks.subject_id
                left join associations on association_type_id = 1 and associations.child_user_id = users2.id 
                where compliance_checks.id = $pdfid and compliance.min_user_level_id <= " . $_SESSION['u_level'];
            $pdf->SetFont('Arial', '', 11);
            if ($result = $this->dbi->query($sql)) {
                while ($myrow = $result->fetch_assoc()) {
                    $assessor_id = $myrow['assessor_id'];
                    $compliance_id = $myrow['compliance_id'];
                    $division_id = $myrow['division_id'];
                    $rosterDivisionId = $myrow['rosterDivisionId'];
                    $compliance_title = $myrow['title'];
                    $status = $myrow['status'];
                    $date = $myrow['date'];
                    $last_modified = $myrow['last_modified'];
                    $clientId = $myrow['clientid'];
                    $subject = $myrow['subject'];
                    $assessor = $myrow['assessor'];
                    $hide_score = $myrow['hide_score'];
                    $logo = $myrow['image'];
                    $logo_print = $myrow['logo_print'];
                    $score_completed_only = $myrow['score_completed_only'];
                    $total_out_of = ($score_completed_only ? 0 : round($myrow['out_of']));

                    $licenseList = $this->userLicenseList($assessor_id);
                    // prd($licenseList);





                    if ($compliance_title) {

                        $col = $this->hex2RGB("#CCCCCC");
                        $pdf->SetDrawColor($col[0], $col[1], $col[2]);
//            $pdf->SetXY(10,10); 	$pdf->Cell(68,6,'Date: ' . $date);
                        //          $pdf->SetXY(10,17); 	$pdf->Cell(68,6,'Last Modified: '. $last_modified);

                        if ($logo_print) {

                            $img = $this->f3->get('base_img_folder') . "company_logos/$logo_print";
                            if (!file_exists($img))
                                $img = $default_img;
                        } else {
                            if ($compliance_id == 132) {
                                $getDivisionLogo = $this->divisionWiseReportLogo($rosterDivisionId);
                            } else {
                                $getDivisionLogo = $this->divisionWiseReportLogo($division_id);
                            }
                            $default_img = $this->f3->get('base_img_folder_static') . $getDivisionLogo;
                            $img = $default_img;
                        }
                        $img_file = "";
                        if ($clientId) {
                            $main_folder = $this->f3->get('main_folder');
                            $flder = $this->f3->get('download_folder') . "user_files/";
                            $img_file = "$flder$clientId/profile.jpg";
                            //$show_img = $this->f3->get('base_url')."/user_files/$clientId/profile.jpg";

                            if (file_exists($img_file)) {
                                //$str="".$clientId;
                                //$str = '<div class="cell_wrap"><div class="cell_head"><a target="_blank" href="' . $show_img . '"><img style="max-width: 200px" src="'.$show_img.'" /></a></div></div>';
                            } else {
                                $img_file = "";
                            }
                        }



                        $pdf->Image($img, 10, 8, 55);

                        $pdf->SetFont('Arial', 'B', 12);
                        $pdf->SetXY(72, 7);
                        $pdf->Cell(110, 6, $compliance_title);
                        $pdf->SetFont('Arial', '', 11);
                        $pdf->SetXY(72, 12.5);
                        $pdf->Cell(110, 6, 'By: ' . $assessor . ' on ' . $date);
                        $pdf->SetXY(72, 18);
                        $pdf->Cell(110, 6, 'Subject: ' . $subject);
                        $pdf->SetXY(72, 23.5);
                        $pdf->Cell(110, 6, 'Report No: ' . $pdfid);
                        if ($img_file) {
                            //$pdf->SetXY(72,29); 
                            $pdf->Image($img_file, 160, 8, 25);
                        }
                        ///$pdf->Cell(110,6,'Client: ' .$str );
                    }
                }
            }





            //****************************************
            //$hide_score = 1;
            if ($result->num_rows) {
                $line_height = 5.5;
                $cell_width = 56;
                $startx = 10;
                $starty = 45;
                $score_width = 21;
                $score_width = ($hide_score ? 21 : 22);

                $cell_width = ($hide_score ? $cell_width + $score_width / 3 : $cell_width);

                $y = $starty;
                $oldstarty = $starty;
                $pdf->SetFont('Arial', 'B', 11);
                $pdf->SetXY($startx, $starty - 6);
                $pdf->MultiCell($cell_width, 6, "Question", 1);
                $pdf->SetXY($startx + $cell_width, $starty - 6);
                $pdf->MultiCell($cell_width, 6, "Answer", 1);
                $pdf->SetXY($startx + ($cell_width * 2), $starty - 6);
                $pdf->MultiCell($cell_width, 6, "Additional Text", 1);
                if (!$hide_score) {
                    $pdf->SetXY($startx + ($cell_width * 3), $starty - 6);
                    $pdf->MultiCell($score_width, 6, "Score", 1);
                }
                $pdf->SetFont('Arial', '', 11);
                $sql = "SELECT id, question, answer, answer_colour, additional_text, value, out_of, answer_id FROM `compliance_check_answers` WHERE compliance_check_id = $pdfid";
                $x = 0;
                $old_question = "";
                $total_value = 0;
                $pdf->SetTextColor(30, 30, 30);
                if ($result = $this->dbi->query($sql)) {

                    while ($myrow = $result->fetch_assoc()) {
                        //$pdf->SetXY(10,27); 	$pdf->Cell($cell_width * 3, 248, '',1,0,'',false);
                        $aid = $myrow['id'];
                        $answer_id = $myrow['answer_id'];
                        $question = $this->br2nl($myrow['question']);
                        $question = strip_tags($question);
                        $question_show = ($old_question == $question ? "" : $question);
                        //$answer = strip_tags($myrow['answer']);
                        $answer = str_replace(['‘', '’'], "'", html_entity_decode(strip_tags($myrow['answer'])));
                        $answer_colour = $myrow['answer_colour'];
                        //$additional_text = $myrow['additional_text'];
                        $additional_text = str_replace(['‘', '’'], "'", html_entity_decode($myrow['additional_text']));

                        $value = $myrow['value'];
                        $out_of = $myrow['out_of'];
                        $new_page = 0;
                        if (isset($cheight)) {
                            if ($y + $cheight > 270) {
                                $this->new_page($hide_score, $cell_width, $compliance_title, $assessor, $date, $subject, $score_width, $pdf, $startx, $starty, $y, $y2, $y3, $oldstarty, $new_page, $img);
                            }
                        }
                        $oldy = $y;
                        if (!$hide_score) {
                            if ($value || $out_of) {
                                $total_value += $value;
                                $score = "$value/$out_of";
                                if ($score_completed_only)
                                    $total_out_of += $out_of;
                            } else {
                                $score = "";
                            }
                        }
                        if ($answer == "LBL") {
                            $pdf->SetXY($startx, $starty);
                            $text_height = $this->GetMultiCellHeight($pdf, $cell_width * 3, $question_show);
                            if ($starty + $text_height > 270) {
                                $this->new_page($hide_score, $cell_width, $compliance_title, $assessor, $date, $subject, $score_width, $pdf, $startx, $starty, $y, $y2, $y3, $oldstarty, $new_page, $img);
                            }
                            $pdf->SetFillColor(255, 255, 245);
                            $pdf->SetTextColor(0, 0, 0);
                            $pdf->MultiCell($cell_width * 3, $line_height, $question_show, 1, 'L', 1);
                            //$pdf->SetXY($startx,$oldstarty); 	$pdf->Cell($cell_width*3+($hide_score ? 0 : $score_width),$cheight, '',1,0,'',false);
                            $y1 = $pdf->GetY();
                            $y2 = $y1;
                            $y3 = $y1;
                            $cheight = $line_height;

                            //$y += $line_height;
                        } else if ($answer != "AS1gna7ure1ma6e") {
                            if ($answer_id) {
                                $pdf->SetXY($startx + 0.5, $starty + 0.5);
                                $pdf->MultiCell($cell_width - 1, $line_height, $question_show, 0, 'L');
                                $y1 = $pdf->GetY();
                                $pdf->SetXY($startx + $cell_width + 0.5, $starty + 0.5);
                                $pdf->MultiCell($cell_width - 1, $line_height, $answer, 0, 'L');
                                $y2 = $pdf->GetY();
                                $pdf->SetXY($startx + ($cell_width * 2) + 0.5, $starty + 0.5);
                                $pdf->MultiCell($cell_width - 1, $line_height, $additional_text, 0, 'L');
                                $y3 = $pdf->GetY();
                                if (!$hide_score) {
                                    $pdf->SetXY($startx + ($cell_width * 3) + 0.5, $starty + 0.5);
                                    $pdf->MultiCell($score_width, $line_height, $score, 0, 'L');
                                }
                            } else {
                                $pdf->SetXY($startx + 0.5, $starty + 0.5);
                                $pdf->MultiCell($cell_width - 1, $line_height, $question_show, 0, 'L');
                                $y1 = $pdf->GetY();
                                $pdf->SetXY($startx + ($cell_width) + 0.5, $starty + 0.5);
                                $pdf->MultiCell($cell_width * 2, $line_height, $answer, 0, 'L');
                                $y2 = $pdf->GetY();
                            }
                        }
                        $y = (isset($y2) ? ($y1 >= $y2 ? $y1 : $y2) : 33);
                        $y += (isset($y2) ? ($y1 >= $y2 ? 0 : 0) : $line_height);
                        if (isset($y3)) {
                            if ($y3 >= $y)
                                $y = $y3;
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
                          } */
                        if ($answer != "LBL") {
                            $pdf->SetFillColor(255, 255, 255);
                            if ($answer_id) {
                                $pdf->SetXY($startx, $oldstarty);
                                $pdf->Cell($cell_width, $cheight, '', 1);
                                $pdf->SetXY($startx + $cell_width, $oldstarty);
                                $pdf->Cell($cell_width, $cheight, '', 1, 0, '', true);
                                $pdf->SetXY($startx + $cell_width * 2, $oldstarty);
                                $pdf->Cell($cell_width, $cheight, '', 1);
                                if (!$hide_score) {
                                    $pdf->SetXY($startx + $cell_width * 3, $oldstarty);
                                    $pdf->Cell($score_width, $cheight, '', 1);
                                }
                                if ($answer_colour) {
                                    $col = $this->alter_brightness($answer_colour, -50);
                                    $col = $this->hex2RGB($col);
                                    $pdf->SetTextColor($col[0], $col[1], $col[2]);
                                }
                                $pdf->SetXY($startx + $cell_width, $oldstarty);
                                $pdf->MultiCell($cell_width, $line_height, $answer, 0, 'L');
                                $pdf->SetTextColor(0, 0, 0);
                            } else {
                                $pdf->SetXY($startx, $oldstarty);
                                $pdf->Cell($cell_width, $cheight, '', 1);
                                $pdf->SetXY($startx + $cell_width, $oldstarty);
                                $pdf->Cell($cell_width * 2 + ($hide_score ? 0 : $score_width), $cheight, '', 1);
                            }
                        } else {
                            $pdf->SetFillColor(255, 255, 230);
                            $pdf->SetTextColor(0, 0, 0);
                            //$pdf->SetXY($startx,$oldstarty); 	$pdf->Cell($cell_width*3+($hide_score ? 0 : $score_width),$cheight, '',1,0,'',true);
                            //$pdf->SetXY($startx,$oldstarty); 	$pdf->Cell($cell_width*3+($hide_score ? 0 : $score_width),$cheight, '',1,0,'',false);
                            //$pdf->SetXY($startx+0.5,$oldstarty+0.5); 	$pdf->MultiCell($cell_width*3, $line_height, $question_show ,0, 'L'); 
                        }
                        $oldstarty = $starty;
                        $old_question = $question;
                        $x++;
                    }
                }
                if (!$hide_score) {
                    if ($total_value || $total_out_of) {
                        $percent = round(($total_value / $total_out_of) * 100);
                        $pdf->SetXY($startx + $cell_width * 3, $oldstarty);
                        $pdf->Cell($score_width, 11, '', 1);
                        $pdf->SetXY($startx + ($cell_width * 3), $oldstarty);
                        $pdf->MultiCell($score_width, $line_height, round($total_value) . '/' . round($total_out_of) . ' (' . round($percent) . '%)', 0);
                    }
                }
                $base_dir = $this->f3->get('download_folder') . "images/compliance/$pdfid";
                $file_name = "";
                if (file_exists($base_dir)) {
                    if (file_exists($base_dir . "/sig.png")) {
                        if ($starty + $cheight > 220)
                            $pdf->AddPage();
                        $pdf->SetFont('Arial', 'B', 13);
                        $pdf->SetXY(15, ($starty > 220 ? 10 : ($y + 10)));
                        $pdf->MultiCell(50, $line_height * 2, "Signature ", 0);
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
                        if ($dir) {
                            $d = new RecDir("$base_dir/$dir/", true);
                            $sql = "select question_title from compliance_questions where id = $dir";
                            if ($result = $this->dbi->query($sql)) {
                                while ($myrow = $result->fetch_assoc()) {
                                    $question_title = $myrow['question_title'];
                                }
                            }
                            while (false !== ($entry = $d->read())) {
                                $ext = strtolower(substr($entry, -3));
                                if ($ext == "jpg") {
                                    $file_type = mime_content_type($entry);
                                    if ($file_type == 'image/png') {
                                        $new_entry = substr($entry, 0, strlen($entry) - 3) . "png";
                                        rename($entry, $new_entry);
                                        $entry = $new_entry;
                                    }
                                }
                                $pdf->AddPage();
                                $pdf->SetFont('Arial', 'B', 13);
                                $pdf->SetXY(155, 10);
                                $pdf->MultiCell(50, $line_height * 2, "Image Attachments", 0);
                                $pdf->SetFont('Arial', '', 11);
                                if (isset($question_title)) {
                                    $pdf->SetXY(10, 18);
                                    $pdf->MultiCell(190, $line_height * 2, $question_title, 0);
                                }
                                $file_name = explode("/", $entry);
                                $file_name = $file_name[count($file_name) - 1];
                                $pdf->Image($entry, 10, 35, 150);
                            }
                            $d->close();
                        }
                    }
                }

                //$licennseRow = 1;

                if (!empty($licenseList)) {
                    $pdf->AddPage();
                    $line_height = 5.5;
                    $cell_width = 180;
                    //$startx = 50;
                    $starty = 10;
                    $score_width = 21;
                    $score_width = ($hide_score ? 21 : 22);

                    $cell_width = ($hide_score ? $cell_width + $score_width / 3 : $cell_width);

                    $y = $starty;

                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->SetXY(10, 7);
                    $pdf->Cell(10, 6, "License Info");

                    $startx = 10;
                    // $starty = 51;
                    $pdf->SetFont('Arial', 'B', 8);
                    //$pdf->SetXY($startx,$starty+10); 	
                    $flder = $this->f3->get('download_folder') . "licences/1000/";
                    $img_file = "$flder$clientId/licence.jpg";
                    $show_img = $this->f3->get('base_url') . "licences/1000/licence.jpg";
                    // $img_file = "$flder$clientId/licence.jpg";
                    //$show_img = $this->f3->get('base_url')."/user_files/$clientId/profile.jpg";

                    if (file_exists($img_file)) {
                        //$str="".$clientId;
                        $str = '<div class="cell_wrap"></div>';
                    } else {
                        $str = "";
                    }
                    //$str = "";
//            $pdf->Cell($cell_width,6,'Client: ' .$str );

                    foreach ($licenseList as $key => $licenseVal) {
                        $liceanceInfoArray = array();
                        if ($key == 0) {
                            $starty = $starty + 10;
                        } else {
                            $starty = $starty + 50;
                        }
                        $pdf->SetXY($startx, $starty);
                        $liceanceInfoArray[] = "Licence Holder : " . $licenseVal['Licence_Holder'];
                        $liceanceInfoArray[] = "Licence Division : " . $licenseVal['Licence_Division'];
                        $liceanceInfoArray[] = "Licence Type : " . $licenseVal['Licence_Type'];
                        $liceanceInfoArray[] = "Licence Number : " . $licenseVal['Licence_Number'];
                        $liceanceInfoArray[] = "Licence Compliance : " . $licenseVal['Class_Compliance'];
                        $liceanceInfoArray[] = "Licence Expired : " . $licenseVal['Licence_Expired'];
                        $liceanceInfoArray[] = "Licence Issued : " . $licenseVal['Licence_Issued'];
                        $pdf->MultiCell($cell_width, 6, implode("\n", $liceanceInfoArray), 1);
                    }

                    $pdf->Footer();

                    //$pdf->SetFont('Arial','',11); */
                }


                $pdf->Output("", $pdfid . "_" . $date . '.pdf');
            }
        }
    }

    function MyReports() {
        $uid = ($this->f3->get('is_client_staff') ? $this->get_site_id() : $_SESSION['user_id']);
        $_GET['subject_id'] = $uid;
        return $this->show();
    }

    function Requests() {
        $is_client = ($this->f3->get('is_client') || $this->f3->get('is_site') ? 1 : 0);

        $rep = new ReportsController($f3);
        $rep->cid = 33;
        $rep->show_links = 0;
        $rep->show_dates = 0;

        $rep->subject_col = "Site";
        $rep->assessor_col = "Made By";

        return $rep->Results();
    }

    function exportToExcel($sql_query, $filename) {
        $xl_obj = new data_list;
        $xl_obj->dbi = $this->dbi;
        $xl_obj->sql = $sql_query; // SQL query to fetch data for Excel export
        $xl_obj->sql_xl($filename); // Export data to Excel file with provided filename
    }

    function ReportSummary() {

        $subject_id = (isset($_GET['subject_id']) ? $_GET['subject_id'] : null);
        $compliance_id = (isset($_GET['compliance_id']) ? $_GET['compliance_id'] : null);
        $assessor_id = (isset($_GET['assessor_id']) ? $_GET['assessor_id'] : null);

        $nav_month = (isset($_GET['selDateMonth']) ? $_GET['selDateMonth'] : null);
        $nav_year = (isset($_GET['selDateYear']) ? $_GET['selDateYear'] : null);
        if (!$nav_month) {
            $def_month = 1;
            $nav_month = date("m");
            $nav_year = date("Y");
        }
        $selDateMonth = $nav_month;
        $selDateYear = $nav_year;
        $compare_date = "$nav_year-" . ($nav_month < 10 ? "0$nav_month" : $nav_month) . "-01";
        //$str .= $compare_date;
        if ($nav_month > 0) {
            $nav1 = "and (MONTH(compliance_checks.check_date_time) = $nav_month or DATE_ADD(CONCAT(YEAR(compliance_checks.check_date_time), '-', MONTH(compliance_checks.check_date_time), '-01'), interval -1 month) = '$compare_date')";
        } else {
            $nav_month = "ALL Months";
        }
        if ($nav_year > 0) {
            $nav2 = "and YEAR(compliance_checks.check_date_time) = $nav_year";
        } else {
            $nav_year = "ALL Years";
        }

        $url_str = ($hdnReportFilter ? "&hdnReportFilter=$hdnReportFilter" : "") . ($selDateMonth ? "&selDateMonth=$selDateMonth" : "") . ($selDateYear ? "&selDateYear=$selDateYear" : "") . ($division_id ? "&division_id=$division_id" : "");
        $url_str_xl = ($subject_id ? "&subject_id=$subject_id" : "") . ($compliance_id ? "&compliance_id=$compliance_id" : "") . ($assessor_id ? "&assessor_id=$assessor_id" : "");

        $xl = (isset($_GET['xl']) ? $_GET['xl'] : null);
        $xl2 = (isset($_GET['xl2']) ? $_GET['xl2'] : null);

        $xl = ($xl ? $xl : $xl2);

        if ($xl) {
            $xl_obj = new data_list;
            $xl_obj->dbi = $this->dbi;
            if ($xl2) {
                $xl_obj->sql = "
                SELECT 
                    compliance_checks.id as `Check ID`,
                    compliance.title as `Report Title`,
                    CONCAT('<nobr>', DATE_FORMAT(compliance_checks.check_date_time, '%d-%b-%Y %H:%i'), '</nobr>') as `Date`,
                    CONCAT(if(users.employee_id != '', users.employee_id, ''), ' ', if(users.client_id != '', users.client_id, ''), ' ', users.name, ' ', users.surname) as `Assessor`,
                    CONCAT(if(users2.employee_id != '', users2.employee_id, ''), ' ', if(users2.client_id != '', users2.client_id, ''), ' ', users2.name, ' ', users2.surname) as `Subject`,
                    compliance_checks.check_date_time,
                    compliance_checks.last_modified,
                    compliance_checks.percent_complete,
                    compliance_checks.total_out_of
                FROM compliance_checks
                INNER JOIN lookup_fields1 ON lookup_fields1.id = compliance_checks.status_id
                LEFT JOIN compliance ON compliance.id = compliance_checks.compliance_id
                LEFT JOIN lookup_fields2 ON lookup_fields2.id = compliance.category_id
                LEFT JOIN users ON users.id = compliance_checks.assessor_id
                LEFT JOIN users2 ON users2.id = compliance_checks.subject_id
                WHERE lookup_fields2.item_name NOT LIKE '%induct%'
                $nav1 $nav2
                " . ($compliance_id ? " AND compliance_checks.compliance_id = $compliance_id" : "") . ($subject_id ? " AND compliance_checks.subject_id = $subject_id" : "") . ($assessor_id ? " AND compliance_checks.assessor_id = $assessor_id" : "") . "
                ORDER BY compliance_checks.check_date_time DESC";
            
            
            } else {
                $xl_obj->sql = "
                SELECT 
                    compliance_checks.id as `Check ID`,
                    compliance.title as `Report Title`,
                    CONCAT('<nobr>', DATE_FORMAT(compliance_checks.check_date_time, '%d-%b-%Y %H:%i'), '</nobr>') as `Date`,
                    CONCAT(if(users.employee_id != '', users.employee_id, ''), ' ', if(users.client_id != '', users.client_id, ''), ' ', users.name, ' ', users.surname) as `Assessor`,
                    CONCAT(if(users2.employee_id != '', users2.employee_id, ''), ' ', if(users2.client_id != '', users2.client_id, ''), ' ', users2.name, ' ', users2.surname) as `Subject`,
                    compliance_checks.check_date_time,
                    compliance_checks.last_modified,
                    compliance_checks.percent_complete,
                    compliance_checks.total_out_of
                FROM compliance_checks
                INNER JOIN lookup_fields1 ON lookup_fields1.id = compliance_checks.status_id
                LEFT JOIN compliance ON compliance.id = compliance_checks.compliance_id
                LEFT JOIN lookup_fields2 ON lookup_fields2.id = compliance.category_id
                LEFT JOIN users ON users.id = compliance_checks.assessor_id
                LEFT JOIN users2 ON users2.id = compliance_checks.subject_id
                WHERE lookup_fields2.item_name NOT LIKE '%induct%'
                $nav1 $nav2
                " . ($compliance_id ? " AND compliance_checks.compliance_id = $compliance_id" : "") . ($subject_id ? " AND compliance_checks.subject_id = $subject_id" : "") . ($assessor_id ? " AND compliance_checks.assessor_id = $assessor_id" : "") . "
                ORDER BY compliance_checks.check_date_time DESC";
            
            
            
            }
            $xl_obj->sql_xl('report_summary.xls');
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
      <input type="hidden" name="division_id" value="' . $division_id . '">
      <div class="fl">
      <h3>Report Summary &nbsp; <a class="list_a" href="' . $this->f3->get('main_folder') . "ReportSummary?xl=1$url_str$url_str_xl" . '">Download Excel</a> &nbsp; <a class="list_a" href="' . $this->f3->get('main_folder') . "ReportSummary?xl2=1" . '">Raw Report</a></h3>
      </div>
      <div  style="padding: 10px;" class="fr">
      ' . $nav->month_year(2016) . '    <input onClick="report_filter()" type="button" value="Go" /> 
      </form>
    ';
        if ($def_month) {
            $filter_box .= ' 
        <script language="JavaScript">
          change_selDate()
        </script>
      ';
        }

        $str .= $filter_box . '</div><div class="cl"></div><hr />';
        //$this->list_obj->title = 'Compliance Checks';
        $this->list_obj->show_num_records = 1;
//    $this->list_obj->form_nav = 1;   $this->list_obj->num_per_page = 100;       $this->list_obj->nav_count = 20;



        $this->list_obj->sql = "
                  select
                  compliance.title as `Report Title`,
                  CONCAT('<nobr>', DATE_FORMAT(compliance_checks.check_date_time, '%d-%b-%Y %H:%i'), '</nobr>') as `Date`
                  , CONCAT(if(users.employee_id != '', users.employee_id, ''), ' ', if(users.client_id != '', users.client_id, ''), ' ', users.name, ' ', users.surname) as `Assessor`
                  , CONCAT(if(users2.employee_id != '', users2.employee_id, ''), ' ', if(users2.client_id != '', users2.client_id, ''), ' ', users2.name, ' ', users2.surname) as `Subject`
                  , CONCAT('<a uk-tooltip=\"title: View as PDF\" class=\"list_a\" target=\"_blank\" href=\"" . $this->f3->get('main_folder') . "CompliancePDF/', compliance_checks.id, '\">PDF</a>') as `PDF`,
                  CONCAT('<a uk-tooltip=\"title: View Online\" class=\"list_a\" target=\"_blank\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_view_id=', compliance_checks.id, '\">View Online</a>') as `View`,
                  
                  
                  CONCAT('<a uk-tooltip=\"title: View By Title (', compliance.title ,')\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "ReportSummary?compliance_id=', compliance.id, '$url_str\">T</a>'
                  '<a uk-tooltip=\"title: View By Assessor (', users.name ,')\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "ReportSummary?assessor_id=', users.id, '$url_str\">A</a>'
                  '<a uk-tooltip=\"title: View Subject (', users2.name ,')\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "ReportSummary?subject_id=', users2.id, '$url_str\">S</a>')
                  as `Filters`
                  
                  FROM compliance_checks
                  inner join lookup_fields1 on lookup_fields1.id = compliance_checks.status_id
                  left join compliance on compliance.id = compliance_checks.compliance_id
                  left join lookup_fields2 on lookup_fields2.id = compliance.category_id
                  left join users on users.id = compliance_checks.assessor_id
                  left join users2 on users2.id = compliance_checks.subject_id
                  where lookup_fields1.item_name = 'Completed' and (lookup_fields2.item_name NOT LIKE '%induct%')
                  $nav1 $nav2
                  " . ($compliance_id ? " and compliance_checks.compliance_id = $compliance_id" : "") . ($subject_id ? " and compliance_checks.subject_id = $subject_id" : "") . ($assessor_id ? " and compliance_checks.assessor_id = $assessor_id" : "") . "
                  order by check_date_time DESC
    ";

        // or lookup_fields2.item_name LIKE '%report%'
        //$str .= "<textarea>{$this->list_obj->sql}</textarea>";
        $str .= $this->list_obj->draw_list();

        $export = (isset($_GET['export']) ? $_GET['export'] : null);

        // If 'export' is set, trigger the export to Excel
        if ($export) {
            // Define your SQL query to retrieve the data you want to export
            $sqlQueryForExport ="select 
            compliance_checks.subject_id,
            compliance_checks.id as `compliance_check_id`,                                   
            CONCAT(users2.name, ' ', users2.surname) as `assessor`,
            CONCAT(users3.name, ' ', users3.surname) as `subject`,
            users3.site_contact_email1,
            users3.site_contact_email2,
            users3.site_contact_email3,
            users3.civil_manager_email,
            users3.facilities_manager_email,
            users3.pest_manager_email,
            users3.security_manager_email,
            users3.traffic_manager_email,
            users3.civil_manager_email2,
            users3.facilities_manager_email2,
            users3.pest_manager_email2,
            users3.security_manager_email2,
            users3.traffic_manager_email2,
            compliance.title, DATE_FORMAT(compliance_checks.check_date_time, '%d-%b %H:%i') as `date`
            FROM compliance_checks                    
            inner join users2 on users2.id = compliance_checks.assessor_id
            inner join users users3 on users3.id = compliance_checks.subject_id
            inner join compliance on compliance.id = compliance_checks.compliance_id                  
            where compliance_checks.id = $report_edit_id";
    
            // Call the export function to generate Excel when 'export' is triggered
            exportToExcel($sqlQueryForExport, 'report_summary_export.xls');
        }
    
        // HTML code for the button to trigger the export
        $exportButton = '
            <form method="GET" action="">
                <input type="hidden" name="export" value="1">
                <button type="submit">Export to Excel</button>
            </form>
        ';
    
        // Add the export button to the output
        $str .= $exportButton;

        return $str;

        
    }


    function Show() {

        $is_client = ($this->f3->get('is_client') || $this->f3->get('is_site') ? 1 : 0);
        $company_ids = $_SESSION['company_ids'];
        $job_application_id = (isset($_GET['job_application_id']) ? $_GET['job_application_id'] : null);
        $job_application2_id = (isset($_GET['job_application2_id']) ? $_GET['job_application2_id'] : null);
        $request_id = (isset($_GET['request_id']) ? $_GET['request_id'] : null);
        $template_id = (isset($_GET['template_id']) ? $_GET['template_id'] : null);
        $template_name = (isset($_GET['template_name']) ? $_GET['template_name'] : null);
        $survey_mode = (isset($_GET['survey_mode']) ? $_GET['survey_mode'] : null);
        $survey_results = (isset($_GET['survey_results']) ? $_GET['survey_results'] : null);
        $survey_comments = (isset($_GET['survey_comments']) ? $_GET['survey_comments'] : null);
        $show_min = (isset($_GET['show_min']) ? $_GET['show_min'] : null);
        $complete_application = (isset($_GET['complete_application']) ? $_GET['complete_application'] : null);
        $prefill = (isset($_GET['prefill']) ? $_GET['prefill'] : null);
        $curr_date = date('Y-m-d');
        $curr_date_time = date('Y-m-d H:i:s');
        $hdnSubmitReport = (isset($_POST['hdnSubmitReport']) ? $_POST['hdnSubmitReport'] : null);
        $form_message = (isset($_GET['form_message']) ? $_GET['form_message'] : null);
        if ($template_id) {

            $sql = "select item_name from compliance_colours;";
            $i = 0;
            if ($result = $this->dbi->query($sql)) {
                while ($myrow = $result->fetch_assoc()) {
                    $colour = $myrow['item_name'];
                    $colour_list .= ($i ? "," : "") . $colour;
                    $i = 1;
                }
            }
            $column_array = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "AA", "AB", "AC", "AD", "AE", "AF", "AG", "AH", "AI", "AJ", "AK", "AL", "AM", "AN", "AO", "AP", "AQ", "AR", "AS", "AT", "AU", "AV", "AW", "AX", "AY", "AZ", "BA", "BB", "BC", "BD", "BE", "BF", "BG", "BH", "BI", "BJ", "BK", "BL", "BM", "BN", "BO", "BP", "BQ", "BR", "BS", "BT", "BU", "BV", "BW", "BX", "BY", "BZ", "CA", "CB", "CC", "CD", "CE", "CF", "CG", "CH", "CI", "CJ", "CK", "CL", "CM", "CN", "CO", "CP", "CQ", "CR", "CS", "CT", "CU", "CV", "CW", "CX", "CY", "CZ");
            $objPHPExcel = new PHPExcel();
            $objPHPExcel->setActiveSheetIndex(0);
            $row = 1;
            $objPHPExcel->getActiveSheet()->getStyle("A$row:BZ$row")->getFont()->setBold(true);
            for ($i = 2; $i <= 500; $i++) {
                $objValidation = $objPHPExcel->getActiveSheet()->getCell("C$i")->getDataValidation();
                $objValidation->setType(PHPExcel_Cell_DataValidation::TYPE_LIST);
                $objValidation->setErrorStyle(PHPExcel_Cell_DataValidation::STYLE_INFORMATION);
                $objValidation->setShowInputMessage(true);
                $objValidation->setShowErrorMessage(true);
                $objValidation->setShowDropDown(true);
                $objValidation->setErrorTitle('Input error');
                $objValidation->setError('Value is not in list.');
                $objValidation->setPromptTitle('Choose Input Type from the Dropdown List');
                $objValidation->setPrompt("\nlbl: Label.\ntxt: Text Box.\nopt: Single Select Options.\nchl: Multiple Select Options.\nclm: Calendar.\ntim: Time Field.\nins: Just show text (e.g. Instructions).\nsig: Get Drawing (e.g. Signature)");
                $objValidation->setFormula1('"lbl,txt,opt,chl,clm,tim,ins,sig"');
                for ($x = 1; $x < 12; $x++) {
                    $objValidation = $objPHPExcel->getActiveSheet()->getCell($column_array[$x * 4 + 3] . "$i")->getDataValidation();
                    $objValidation->setType(PHPExcel_Cell_DataValidation::TYPE_LIST);
                    $objValidation->setErrorStyle(PHPExcel_Cell_DataValidation::STYLE_INFORMATION);
                    $objValidation->setShowInputMessage(true);
                    $objValidation->setShowErrorMessage(true);
                    $objValidation->setShowDropDown(true);
                    $objValidation->setErrorTitle('Input error');
                    $objValidation->setError('Value is not in list.');
                    $objValidation->setPromptTitle('Choose a Colour');
                    $objValidation->setPrompt("\nPlease select a colour from the drop-down list.");
                    $objValidation->setFormula1('"' . $colour_list . '"');
                    $objValidation = $objPHPExcel->getActiveSheet()->getCell($column_array[$x * 4 + 4] . "$i")->getDataValidation();
                    $objValidation->setShowInputMessage(true);
                    $objValidation->setPromptTitle('Comments/Further Questions');
                    $objValidation->setPrompt("\nAdd text here if a comment box is required.\n\nAdd a number here if the question leads to further questions.\n\nComment::txt - Textbox\n::clm - Calendar\n::ti2 - Time\n::5 - Define number of lines");
                }
                $objValidation = $objPHPExcel->getActiveSheet()->getCell("D$i")->getDataValidation();
                $objValidation->setShowInputMessage(true);
                $objValidation->setPromptTitle('Choices/Row or Num Rows of Text');
                $objValidation->setPrompt("\nFor single or multi-select options, choose the number of items shown on each row.\n\nFor text boxes, choose the number of rows of text.");
                $objValidation = $objPHPExcel->getActiveSheet()->getCell("E$i")->getDataValidation();
                $objValidation->setShowInputMessage(true);
                $objValidation->setPromptTitle('Parent Number');
                $objValidation->setPrompt("To hide this item until selected, add a number corresponding to an above comment within a choice.");
            }
            $objPHPExcel->getActiveSheet()->getStyle("A1:BZ1")->applyFromArray(array('borders' => array('bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => '000000')))));
            $objPHPExcel->getActiveSheet()->SetCellValue("A$row", "Question");
            $objPHPExcel->getActiveSheet()->SetCellValue("B$row", "Answer");
            $objPHPExcel->getActiveSheet()->SetCellValue("C$row", "Type");
            $objPHPExcel->getActiveSheet()->SetCellValue("D$row", "Choices/Row");
            $objPHPExcel->getActiveSheet()->SetCellValue("E$row", "Parent");
            $objPHPExcel->getActiveSheet()->getStyle("E1:E500")->applyFromArray(array('borders' => array('right' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => '000000')))));
            for ($x = 1; $x < 12; $x++) {
                $objPHPExcel->getActiveSheet()->SetCellValue($column_array[$x * 4 + 1] . "$row", "Choice");
                $objPHPExcel->getActiveSheet()->SetCellValue($column_array[$x * 4 + 2] . "$row", "Value");
                $objPHPExcel->getActiveSheet()->SetCellValue($column_array[$x * 4 + 3] . "$row", "Colour");
                $objPHPExcel->getActiveSheet()->SetCellValue($column_array[$x * 4 + 4] . "$row", "Comment");
                $objPHPExcel->getActiveSheet()->getStyle($column_array[$x * 4 + 4] . "1:" . $column_array[$x * 4 + 4] . "500")->applyFromArray(array('borders' => array('right' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => '000000')))));
            }
            $sql = "select id, question_type, question_title, answer, choices_per_row, parent from compliance_questions where compliance_id = $template_id order by sort_order;";
            if ($result = $this->dbi->query($sql)) {
                while ($myrow = $result->fetch_assoc()) {
                    $row++;
                    $col = 1;
                    $compliance_question_id = $myrow['id'];
                    $question_title = $myrow['question_title'];
                    $question_type = $myrow['question_type'];
                    $answer = $myrow['answer'];
                    $choices_per_row = $myrow['choices_per_row'];
                    $parent = $myrow['parent'];
                    $objPHPExcel->getActiveSheet()->SetCellValue("A$row", $question_title);
                    if ($answer)
                        $objPHPExcel->getActiveSheet()->SetCellValue("B$row", $answer);
                    $objPHPExcel->getActiveSheet()->SetCellValue("C$row", $question_type);
                    $objPHPExcel->getActiveSheet()->SetCellValue("D$row", $choices_per_row);
                    $objPHPExcel->getActiveSheet()->SetCellValue("E$row", $parent);
                    if ($question_type == 'lbl') {
                        $objPHPExcel->getActiveSheet()->getStyle("A$row:E$row")->getFont()->setBold(true);
                    }
                    if ($question_type == 'opt' || $question_type == 'chl') {
                        $sql = "select compliance_question_choices.choice, compliance_question_choices.choice_value, compliance_question_choices.additional_text_required,
                    lookup_fields.item_name as `colour` from compliance_question_choices
                    left join lookup_fields on lookup_fields.id = compliance_question_choices.colour_scheme_id
                    where compliance_question_choices.compliance_question_id = $compliance_question_id order by compliance_question_choices.sort_order;";
                        if ($result2 = $this->dbi->query($sql)) {
                            while ($myrow2 = $result2->fetch_assoc()) {
                                $choice = $myrow2['choice'];
                                $choice_value = $myrow2['choice_value'];
                                $additional_text_required = $myrow2['additional_text_required'];
                                $colour = $myrow2['colour'];
                                $objPHPExcel->getActiveSheet()->SetCellValue($column_array[$col * 4 + 1] . "$row", $choice);
                                $objPHPExcel->getActiveSheet()->SetCellValue($column_array[$col * 4 + 2] . "$row", $choice_value);
                                $objPHPExcel->getActiveSheet()->SetCellValue($column_array[$col * 4 + 3] . "$row", $colour);
                                $objPHPExcel->getActiveSheet()->SetCellValue($column_array[$col * 4 + 4] . "$row", $additional_text_required);
                                $col++;
                            }
                        }
                    }
                }
            }
            header('Content-type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="' . $template_name . '.xlsx"');
            $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
            $objWriter->save('php://output');
        } else {

            $itm = new input_item;
            $itm->hide_filter = 1;
            $str .= $itm->setup_cal();
            $str .= $itm->setup_ti2();
            if ($complete_application) {

                $mail = new email_q($this->dbi);
                $str .= '<div class="message">' . ($request_id ? "Your request has been submitted to your manager..." : 'Thank you for applying for employment at SCGS.<br /><br />We will get back to you shortly...') . '<br /><br />Please double check the information provided below and review if necessary.' . ($request_id ? '' : '<br /><br /><div style="padding: 10px; border: 2px solid red; display: inline-block; background-color: #FFFFEE;"><u>Please Note</u> that Your Username is:<br /><br />' . $_SESSION['username'] . '<br /><br />(Please keep a record of the above username for future job applications)</div>') . '</div>';
                $headers_top = 'MIME-Version: 1.0' . "\r\n";
                $headers_top .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
                if ($request_id) {

                    $sql = "select compliance.show_all_entities, users.name as `requester_name`, users.surname as `requester_surname`, users.email as `requester_email`,
                  users2.name as `manager_name`, users2.surname as `manager_surname`, users2.email as `manager_email`, compliance.title, application_requests.compliance_check_id
                  from application_requests 
                  left join users on users.id = application_requests.requester_id
                  left join users2 on users2.id = application_requests.manager_id
                  left join compliance on compliance.id = application_requests.compliance_id
                  where application_requests.id = $request_id;";
                    if ($result = $this->dbi->query($sql)) {
                        if ($myrow = $result->fetch_assoc()) {
                            $requester_name = $myrow['requester_name'];
                            $requester_surname = $myrow['requester_surname'];
                            $manager_name = $myrow['manager_name'];
                            $manager_surname = $myrow['manager_surname'];
                            $manager_email = $myrow['manager_email'];
                            $requester_email = $myrow['requester_email'];
                            $manager_surname = $myrow['manager_surname'];
                            $app_title = $myrow['title'];
                            $compliance_obj2 = new compliance;
                            $compliance_obj2->hide_score = 1;
                            $compliance_obj2->dbi = $this->dbi;
                            $compliance_obj2->title = "Request Details";
                            $compliance_obj2->compliance_check_id = $myrow['compliance_check_id'];
                            $compliance_obj2->display_results();
                        }
                    }
                    $subject = $app_title;
                    $msg = "Hello $requester_name,<br /><br />";
                    $msg .= "Your request has been sent to $manager_name $manager_surname.";
                    $msg .= $compliance_obj2->str;
                    $msg .= "<br /><br />Regards,<br />Edge Admin.";
                    $mail->clear_all();
                    $mail->AddReplyTo($manager_email);
                    $mail->AddAddress($requester_email);
                    $mail->Subject = $subject;
                    $mail->Body = $css . "\n\n" . $msg;
                    $mail->queue_message();
                    $msg = "Hello $manager_name,<br /><br />";
                    $msg .= "A request has been made by $requester_name $requester_surname.";
                    $msg .= "<br /><br />Please go to " . $this->f3->get('main_folder') . "Applications?process_mode=1&more_info=$request_id to process the request.";
                    $msg .= $compliance_obj2->str;
                    $msg .= "<br /><br />Regards,<br />Edge Admin.";
                    $mail->clear_all();
                    $mail->AddReplyTo($requester_email);
                    $mail->AddAddress($manager_email);
                    $mail->Subject = $subject;
                    $mail->Body = $css . "\n\n" . $msg;
                    $mail->queue_message();
                } else if ($job_application_id) {

                    $sql = "select users.id as idin, concat(users.name, ' ', users.surname) as `applicant`, users.email, job_ads.title as `job_ad`, users.username,
                  job_applications.date_applied as `date_applied`
                  from job_applications
                  left join users on users.id = job_applications.user_id
                  left join job_application_status on job_application_status.id = job_applications.status_id
                  left join job_ads on job_ads.id = job_applications.job_ad_id
                  left join states on states.id = users.state
                  where job_applications.id = $job_application_id";
                    if ($result = $this->dbi->query($sql)) {
                        if ($myrow = $result->fetch_assoc()) {
                            $applicant = $myrow['applicant'];
                            $job_ad = $myrow['job_ad'];
                            $username = $myrow['username'];
                            $applicant_email = $myrow['email'];
                            $date_applied = Date("d-M-Y", strtotime($myrow['date_applied']));
                            $subject = "Job Application: $job_ad";
                            $compliance_obj2 = new compliance;
                            $compliance_obj2->hide_score = 1;
                            $compliance_obj2->hide_attachments = 1;
                            $compliance_obj2->dbi = $this->dbi;
                            $compliance_obj2->title = $subject;
                            $compliance_obj2->compliance_check_id = $_GET['report_view_id'];
                            $compliance_obj2->display_results();
                        }
                    }
                    $msg = "Hello $applicant,<br /><br />";
                    $msg .= "<p>Your job application for $job_ad has been submitted.</p>";
                    $msg .= "Please <a href=\"" . $this->f3->get('main_folder') . "/Login?username=$username&page_from=MyJobApplications\">login here</a> to review your application and/or make changes.";
                    $msg .= $compliance_obj2->str;
                    $msg .= "<br /><br />Regards,<br />Human Resources Department.";
                    $mail->clear_all();
                    $mail->AddReplyTo($this->f3->get('hr_email'));
                    $mail->AddAddress($applicant_email);
                    $mail->Subject = $subject;
                    $mail->Body = $css . "\n\n" . $msg;
                    $mail->queue_message();
                }
            }

//      $alloedPermission = '2458,2456,2455,2454,'
//      $sql = "select users.id from users inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id in (111, 112, 504, 403, 114, 115, 534, 2039)
//      and lookup_answers.table_assoc = 'users' and users.id = ".$_SESSION['user_id'].";";
//      echo $sql;
//      die;
//      if($result = $this->dbi->query($sql)) $is_manager = ($result->num_rows ? 1 : 0);

            $is_manager = 1;
            $str .= '
      <input type="hidden" name="hdnChangeItem" id="hdnChangeItem" />
      <input type="hidden" name="hdnChangeStatus" id="hdnChangeStatus" />
      <script type="text/javascript">
        function customAlert(msg,duration) {
          var styler = document.createElement("span");
          styler.setAttribute("style","position: absolute !important; width: 100%; top: 0; left: 0px; border: solid 2px green;background-color:#FFFFDD;color:red;text-align: center; padding: 50px; font-size: 16px;");
          styler.innerHTML = "<h1>"+msg+"</h1>";
          setTimeout(function() {
            styler.parentNode.removeChild(styler);
          },duration);
          document.body.appendChild(styler);
        }
        function caller(msg) {
          customAlert(msg,"3500");
        }
        function display_hide(itm) {
          if(document.getElementById(itm).style.display == "block") {
            document.getElementById(itm).style.display = "none";
            document.getElementById("btnShowHide").value = "Show Image Attachments";
          } else {
            document.getElementById(itm).style.display = "block";
            document.getElementById("btnShowHide").value = "Hide Image Attachments";
          }
        }
      </script>
      ';
            $report_edit_id = (isset($_GET['report_edit_id']) ? $_GET['report_edit_id'] : null);

            $report_view_id = (isset($_GET['report_view_id']) ? $_GET['report_view_id'] : null);
            if ($report_edit_id || $report_view_id) {
                $isExistInductionReport = 0;
                $correctQuestionArray = array();
                if ($report_edit_id) {
                    $inductionReportIds = array(120, 121, 122, 123, 124);
                    $getReportDetail = $this->getReportDetail($report_edit_id);
                    $correctQuestionArray = array();
                    if ($getReportDetail) {
                        //prd($getReportDetail);
                        if ($getReportDetail['compliance_id'] == '132' && $getReportDetail['start_time_date'] != "0000-00-00 00:00:00") {
                            echo $this->redirect($this->f3->get('main_folder') . 'clock2');
                        }
                        if (in_array($getReportDetail['compliance_id'], $inductionReportIds)) {
                            $isExistInductionReport = 1;

                            $sqlQuestion = "select group_concat(question_id) correctQuestion from compliance_check_answers where compliance_check_id = $report_edit_id and out_of = 1 and out_of = value;";
                            $resultQuestion = $this->dbi->query($sqlQuestion);
                            if ($myRowQuestion = $resultQuestion->fetch_assoc()) {
                                $correctQuestion = $myRowQuestion['correctQuestion'];
                                $correctQuestionArray = explode(",", $correctQuestion);
                            }
                        }
                    }

                    $str .= '<script>
          function goodbye(e) {
            if(!e) e = window.event;
            e.cancelBubble = true; //e.cancelBubble is supported by IE - this will kill the bubbling process.
            e.returnValue = "Are you sure you want to leave this page?"; //This is displayed on the dialog
            if (e.stopPropagation) { //e.stopPropagation works in Firefox.
              e.stopPropagation();
              e.preventDefault();
            }
          }
          window.onbeforeunload=goodbye;
          
          </script>';
                }

                $vid = ($report_edit_id ? $report_edit_id : $report_view_id);
                $sql = "select compliance_checks.id as `result` from compliance_checks
                left join compliance on compliance.id = compliance_checks.compliance_id
                where compliance_checks.id = $vid and compliance.allow_all_access = 1;";
                $all_access = $this->get_sql_result($sql);

                $compliance_category = $this->get_sql_result("select lookup_fields.item_name as `result` from compliance_checks
                inner join compliance on compliance.id = compliance_checks.compliance_id
                inner join lookup_fields on lookup_fields.id = compliance.category_id
                where compliance_checks.id = $vid;");

                //$compliance_category = ""; 
                $this->get_sql_result("select lookup_fields.item_name as `result` from compliance_checks
                inner join compliance on compliance.id = compliance_checks.compliance_id
                inner join lookup_fields on lookup_fields.id = compliance.category_id
                where compliance_checks.id = $vid;");

                if ($compliance_category == "Survey") {
                    $is_survey = 1;
                } else if ($compliance_category == "Forms") {
                    $is_form = 1;
                } else if ($compliance_category == "Induction") {
                    $is_induction = 1;
                } else if ($compliance_category == "PreStartCheckList") {
                    $is_prestart = 1;
                } else if ($compliance_category == "Request Forms") {
                    $is_request = 1;
                } else {
                    $is_survey = 0;
                    $is_form = 0;
                    $is_request = 0;
                    $is_survey = 0;
                    $is_prestart = 0;
                }

                if ($is_manager || $all_access) {
                    $mgr_sql = " union select foreign_id from lookup_answers where table_assoc = 'compliance' and foreign_id in (select compliance_id from compliance_checks where id = $vid);";
                }
//                    $allow_access = $this->num_results("select child_user_id from associations where association_type_id = 1 and child_user_id = $user_id and parent_user_id = {$_SESSION['user_id']} ");


                $sql = "select id from compliance_auditors where compliance_id = (SELECT compliance.id FROM `compliance_checks`
                left join compliance on compliance.id = compliance_checks.compliance_id
                WHERE compliance_checks.id = $vid LIMIT 1) and (user_id = " . $_SESSION['user_id'] . "
                or compliance_checks.subject_id in (select child_user_id from associations where association_type_id = 1 and parent_user_id = " . $_SESSION['user_id'] . "))
                $mgr_sql";
                //return "A: $all_access  $sql";
                if ($report_view_id)
                    $own_access = ($this->f3->get('is_site') || $this->f3->get('is_client') ? ($this->get_sql_result("select subject_id as `result` from `compliance_checks` where id = $vid;") == $_SESSION['user_id'] ? 1 : 0) : 0);


                if ($result = $this->dbi->query($sql)) {
                    if (!$result->num_rows && !$all_access && !$own_access) {
                        $report_edit_id = 0;
                        $report_view_id = 0;
                        exit;
                    }
                }
            }

            $new_report = (isset($_GET['new_report']) ? $_GET['new_report'] : null);
            $report_id = (isset($_GET['report_id']) ? $_GET['report_id'] : null);
            $site_id = (isset($_GET['site_id']) ? $_GET['site_id'] : null);
            if ($new_report) {
                $compliance_category = $this->get_sql_result("select lookup_fields.item_name as `result` from compliance
                inner join lookup_fields on lookup_fields.id = compliance.category_id
                where compliance.id = $report_id;");

                //$str .= "CC: $compliance_category - reid: $report_edit_id - rvid: $report_view_id";
                if ($compliance_category == "Forms" && $new_report) {
                    //echo $this->redirect($this->f3->get('main_folder').'Compliance?form_message=1&report_id='.$report_id.'&site_id='.$_SESSION['user_id']);
                }
            }
            $subject_id = (isset($_GET['subject_id']) ? $_GET['subject_id'] : null);
            $auditor_id = (isset($_GET['auditor_id']) ? $_GET['auditor_id'] : null);
            if ($report_id || $subject_id || $auditor_id) {
                $hdnReportFilter = (isset($_REQUEST['hdnReportFilter']) ? $_REQUEST['hdnReportFilter'] : null);
                if ($hdnReportFilter) {

                    $chkPending = (isset($_GET['chkPending']) ? $_GET['chkPending'] : null);
                    $chkCompleted = (isset($_GET['chkCompleted']) ? $_GET['chkCompleted'] : null);
                    $chkCancelled = (isset($_GET['chkCancelled']) ? $_GET['chkCancelled'] : null);
                    if ($chkCompleted && $chkPending && $chkCancelled) {
                        $filter .= " and (compliance_checks.status_id = $chkPending or compliance_checks.status_id = $chkCompleted or compliance_checks.status_id = $chkCancelled) ";
                        $chkPending = "checked";
                        $chkCompleted = "checked";
                        $chkCancelled = "checked";
                    } else if ($chkCompleted && $chkPending) {
                        $filter .= " and (compliance_checks.status_id = $chkPending or compliance_checks.status_id = $chkCompleted) ";
                        $chkPending = "checked";
                        $chkCompleted = "checked";
                    } else if ($chkCompleted && $chkCancelled) {
                        $filter .= " and (compliance_checks.status_id = $chkCancelled or compliance_checks.status_id = $chkCompleted) ";
                        $chkCancelled = "checked";
                        $chkCompleted = "checked";
                    } else if ($chkCancelled && $chkPending) {
                        $filter .= " and (compliance_checks.status_id = $chkPending or compliance_checks.status_id = $chkCancelled) ";
                        $chkPending = "checked";
                        $chkCancelled = "checked";
                    } else if ($chkPending) {
                        $filter .= " and compliance_checks.status_id = $chkPending ";
                        $chkPending = "checked";
                    } else if ($chkCompleted) {
                        $filter .= " and compliance_checks.status_id = $chkCompleted ";
                        $chkCompleted = "checked";
                    } else if ($chkCancelled) {
                        $filter .= " and compliance_checks.status_id = $chkCancelled ";
                        $chkCancelled = "checked";
                    }
                } else {
                    $filter = " and compliance_checks.status_id != 525" . ($is_client ? " and compliance_checks.status_id != 522" : "");
                    $chkPending = ($is_client ? "" : "checked");
                    $chkCompleted = "checked";
                }

                if (!$new_report && !$site_id) {
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
            <input type="hidden" name="report_id" id="report_id" value="' . $report_id . '">
            <input type="hidden" name="site_id" id="site_id" value="' . $site_id . '">
            <input type="hidden" name="new_report" id="new_report" value="' . $new_report . '">
            <input type="hidden" name="report_edit_id" id="report_edit_id" value="' . $report_edit_id . '">
            <input type="hidden" name="report_view_id" id="report_view_id" value="' . $report_view_id . '">
            <input type="hidden" name="subject_id" id="subject_id" value="' . $subject_id . '">
            <input type="hidden" name="auditor_id" id="auditor_id" value="' . $auditor_id . '">
            <div class="form-wrapper">
            <div class="form-header">Filter</div>
            <div  style="padding: 10px;">
            ' . $nav->month_year(2010) . '
            <input value="522" type="checkbox" name="chkPending" ' . $chkPending . ' id="chkPending"  /> Pending
            <input value="524" type="checkbox" name="chkCompleted" ' . $chkCompleted . ' id="chkCompleted"  /> Completed
            <input value="525" type="checkbox" name="chkCancelled" ' . $chkCancelled . ' id="chkCancelled"  /> Cancelled';

                    $divisionSql = $this->getDivisionIds('all');
                    $selDivision = "<div class=\"fl med_textbox \"><nobr>Division</nobr><br><select name='complianceDivision'  id ='complianceDivision' style=\"width: 230px;border-radius:2px !important\"><option value=\"\"> Select Division </option>";
                    foreach ($divisionSql as $key => $svalue) {
                        if ($svalue['id'] == $_REQUEST['complianceDivision']) {
                            $selected = "selected";
                        } else {
                            $selected = "";
                        }

                        $selDivision .= '<option value="' . $svalue['id'] . '" ' . $selected . ' >' . $svalue['item_name'] . '</option>';
                    }
                    $selDivision .= '</select></div>';

                    // $selDivision = "";
                    $str .= '<br> <br><div class="fl small_textbox calicon"><nobr>
                   
                      <nobr>Start Date</nobr>
                          <input readonly="" onclick="javascript:NewCssCal(&quot;calReportStartDate&quot;, &quot;ddMMMyyyy&quot;)" value = "' . $_REQUEST["calReportStartDate"] . '"  type="text" id="calReportStartDate" name="calReportStartDate" class="full_width" value="">
                             
                       </div>
                         
                       <div class="fl small_textbox calicon"><nobr>    <nobr>End Date</nobr>
                         <input readonly="" onclick="javascript:NewCssCal(&quot;calReportEndDate&quot;, &quot;ddMMMyyyy&quot;)" value = "' . $_REQUEST["calReportEndDate"] . '"  type="text" id="calReportEndDate" name="calReportEndDate" class="full_width" value="">
                      </div>' . $selDivision . ' <div class=\"fl large_textbox \"><nobr>Search</nobr><br><input type="text" name="txtComplianceFilter" id="txtComplianceFilter" value="' . $txtComplianceFilter . '" placeholder="Optional filter by text within answers..." style="width: 300px;" /><input onClick="report_filter()" type="button" value="Go" /></div>
            </div>
            </div>
            </form>
          ';
                    if ($hdnReportFilter) {
                        $month_select = (isset($_REQUEST['selDateMonth']) ? $_REQUEST['selDateMonth'] : null);
                        $year_select = (isset($_REQUEST['selDateYear']) ? $_REQUEST['selDateYear'] : null);
                    } else {
                        $str .= '
                  <script language="JavaScript">
                    change_selDate()
                  </script>
            ';
                        $month_select = date('m');
                        $year_select = date('Y');
                    }
                    if ($month_select > 0) {
                        $filter .= " and MONTH(compliance_checks.check_date_time) = $month_select ";
                    }
                    if ($year_select > 0) {
                        $filter .= " and YEAR(compliance_checks.check_date_time) = $year_select ";
                    }
                }
            }



            if ($hdnSubmitReport) {
                $sql = "delete from compliance_check_answers where compliance_check_id = $report_edit_id;";
                $result = $this->dbi->query($sql);
                foreach ($_POST as $param_name => $param_val) {
                    $bits = explode("_", $param_name);
                    $ptype = $bits[0];
                    $param_id = $bits[1];
                    //echo "<h3>$ptype -- $param_id</h3>";
                    $param_num = ($bits[2] ? $bits[2] : 0);
                    $param_seq = $bits[2];
                    if ($ptype != "hdn" && $param_val) {
                        $fields = explode(";*;", $param_val);
                        if ($ptype == "opt" || $ptype == "chk") {
                            $fid = $fields[0];
                            $additional_text = $fields[1];
                            if ($ptype == "opt") {
                                $question = $this->mesc($fields[2]);
                                $answer = $this->mesc($fields[3]);
                                $val = $fields[4];
                                $answer_id = $fields[5];
                                $answer_colour = $fields[6];
                                $sql = "select max(convert(choice_value, SIGNED)) as `max_val` from compliance_question_choices where compliance_question_id = $param_id";
                                //echo $sql;
                                if ($result_c = $this->dbi->query($sql)) {
                                    if ($myrow_c = $result_c->fetch_assoc()) {
                                        $p_out_of = $myrow_c['max_val'];
                                    } else {
                                        $p_out_of = 0;
                                    }
                                }
                                //echo "<h3>$sql</h3>";
                            } else {
                                $question = $this->mesc($fields[1]);
                                $answer = $this->mesc($fields[2]);
                                $val = $fields[3];
                                $answer_id = $fields[4];
                                $answer_colour = $fields[5];
                                $sql = "select choice_value from compliance_question_choices where id = $answer_id;";
                                //$str .= $sql;
                                if ($result_c = $this->dbi->query($sql)) {
                                    if ($myrow_c = $result_c->fetch_assoc()) {
                                        $p_out_of = abs($myrow_c['choice_value']);
                                    } else {
                                        $p_out_of = 0;
                                    }
                                }
                            }
                            $sql = "insert into compliance_check_answers(question_id, compliance_check_id, question, answer, value, answer_id, out_of, answer_colour) values ($param_id, $report_edit_id, '$question', '$answer', '$val', '$answer_id', '$p_out_of', '$answer_colour')";
                            //echo "<h1>$sql</h1>";
                            if ($result = $this->dbi->query($sql))
                                $last_idtmp[$param_num] = $this->dbi->insert_id;
                        } else {
                            if ($param_id == $old_param_id) {
                                $last_id = ($old_ptype == "opt" ? $last_idtmp[0] : $last_idtmp[$param_num]);
                                $sql = "select id from compliance_question_choices where compliance_question_id = '$param_id';";

                                if ($result = $this->dbi->query($sql)) {
                                    if ($myrow = $result->fetch_assoc()) {
                                        if ($myrow['id']) {
                                            $sql = "update compliance_check_answers set additional_text = '$param_val' where id = $last_id;";
                                            $result = $this->dbi->query($sql);
                                        }
                                    }
                                }
                            } else {
                                $param_val = $this->mesc($param_val);
                                //          echo "<h3>$param_val</h3>";
                                $sql = "select question_title from compliance_questions where id = $param_id";
                                if ($result = $this->dbi->query($sql)) {
                                    if ($myrow = $result->fetch_assoc()) {
                                        $question = $this->mesc($myrow['question_title']);
                                    }
                                }
                                $sql = "insert into compliance_check_answers(question_id, compliance_check_id, question, answer) values ($param_id, $report_edit_id, '$question', '$param_val')";
                                $result = $this->dbi->query($sql);
                            }
                        }
                        $old_param_id = $param_id;
                        $old_ptype = $ptype;
                        $old_additional_text = $additional_text;
                    }
                }
                $sql = "select sum(tvalues) as total_value from
                (select max(CONVERT(choice_value, SIGNED)) as tvalues from compliance_question_choices where compliance_question_id in
                (select id from compliance_questions where compliance_id in (select compliance_id from compliance_checks where id = $report_edit_id) and question_type = 'opt')
                group by compliance_question_id)
                as subquery";
                if ($result = $this->dbi->query($sql)) {
                    if ($myrow = $result->fetch_assoc()) {
                        $total_out_of = $myrow['total_value'];
                    }
                }
                $sql = "select sum(choice_value) as total_value from compliance_question_choices where compliance_question_id in
        (select id from compliance_questions where compliance_id in (select compliance_id from compliance_checks where id = $report_edit_id) and question_type = 'chl')";
                if ($result = $this->dbi->query($sql)) {
                    if ($myrow = $result->fetch_assoc()) {
                        $total_out_of += $myrow['total_value'];
                    }
                }
                $sql = "select round((select count(distinct(question_id)) as num_answers from compliance_check_answers where compliance_check_id = $report_edit_id) /
        (select count(id) as `num_questions` from compliance_questions where compliance_id in (select compliance_id from compliance_checks where id = $report_edit_id)) * 100) as `percent_complete`";
                if ($result = $this->dbi->query($sql)) {
                    if ($myrow = $result->fetch_assoc()) {
                        $percent_complete = $myrow['percent_complete'];
                    }
                }
                $sql = "update compliance_checks set percent_complete = '$percent_complete', total_out_of = '$total_out_of', last_modified = '$curr_date_time' where id = $report_edit_id";
                $this->dbi->query($sql);
                if ($_POST['hdnCompleteReport']) {
                    $sql = "update compliance_checks set status_id = 524 where id = " . $report_edit_id;
                    $result = $this->dbi->query($sql);
                    $redirect_str = $this->f3->get('main_folder') . 'Compliance?report_view_id=' . $report_edit_id . ($is_form || $is_survey || $is_request ? "&form_message=1" : "");
                    $redirect_str .= ($job_application_id ? "&job_application_id=$job_application_id" : ($job_application2_id ? "&job_application2_id=$job_application2_id" : ($request_id ? "&request_id=$request_id" : "")));

//          $sql = "select 
//                    compliance_checks.subject_id,
//                    compliance_checks.id as `compliance_check_id`,
//                    compliance_email_to.site_id as `site_id_in`,
//                    CONCAT(users.name, ' ', users.surname) as `send_to`,
//                    CONCAT(users2.name, ' ', users2.surname) as `assessor`,
//                    CONCAT(users3.name, ' ', users3.surname) as `subject`,
//                    compliance.title, DATE_FORMAT(compliance_checks.check_date_time, '%d-%b %H:%i') as `date`,
//                    users.email as `email_send`
//                    FROM compliance_checks
//                    inner join users2 on users2.id = compliance_checks.assessor_id
//                    inner join users3 on users3.id = compliance_checks.subject_id
//                    inner join compliance on compliance.id = compliance_checks.compliance_id
//                    inner join compliance_email_to on compliance_email_to.compliance_id = compliance.id
//                    inner join users on users.id = compliance_email_to.staff_id
//                    where compliance_checks.id = $report_edit_id";


                    $sql = "select 
                    compliance_checks.subject_id,
                    compliance_checks.id as `compliance_check_id`,                                   
                    CONCAT(users2.name, ' ', users2.surname) as `assessor`,
                    CONCAT(users3.name, ' ', users3.surname) as `subject`,
                    users3.site_contact_email1,
                    users3.site_contact_email2,
                    users3.site_contact_email3,
                    users3.civil_manager_email,
                    users3.facilities_manager_email,
                    users3.pest_manager_email,
                    users3.security_manager_email,
                    users3.traffic_manager_email,
                    users3.civil_manager_email2,
                    users3.facilities_manager_email2,
                    users3.pest_manager_email2,
                    users3.security_manager_email2,
                    users3.traffic_manager_email2,
                    compliance.title, DATE_FORMAT(compliance_checks.check_date_time, '%d-%b %H:%i') as `date`
                    FROM compliance_checks                    
                    inner join users2 on users2.id = compliance_checks.assessor_id
                    inner join users users3 on users3.id = compliance_checks.subject_id
                    inner join compliance on compliance.id = compliance_checks.compliance_id                  
                    where compliance_checks.id = $report_edit_id";

//          $sql = "select 
//                    compliance_checks.subject_id,
//                    compliance_checks.id as `compliance_check_id`,
//                    compliance_email_to.site_id as `site_id_in`,
//                    CONCAT(users.name, ' ', users.surname) as `send_to`,
//                    CONCAT(users2.name, ' ', users2.surname) as `assessor`,
//                    CONCAT(users3.name, ' ', users3.surname) as `subject`,
//                    compliance.title, DATE_FORMAT(compliance_checks.check_date_time, '%d-%b %H:%i') as `date`,
//                    users.email as `email_send`
//                    FROM compliance_checks
//                    inner join users2 on users2.id = compliance_checks.assessor_id
//                    inner join users3 on users3.id = compliance_checks.subject_id
//                    inner join compliance on compliance.id = compliance_checks.compliance_id
//                    inner join compliance_email_to on compliance_email_to.compliance_id = compliance.id
//                    inner join users on users.id = compliance_email_to.staff_id
//                    where compliance_checks.id = $report_edit_id";
                    // die("hello new completed");
                    $result = $this->dbi->query($sql);
                    // prd($sql);
                    //$str .= $this->ta($sql) . "<br/>";
                    if ($result = $this->dbi->query($sql)) {
                        $mail = new email_q($this->dbi);
                        while ($myrow = $result->fetch_assoc()) {
                            $subject_id = $myrow['subject_id'];
                            $site_id_in = $myrow['site_id_in'];
                            if (!$site_id_in || $site_id_in == $subject_id) {

                                // $send_to = $myrow['send_to'];

                                $compliance_check_id = $myrow['compliance_check_id'];

                                $email_send = array($myrow['site_contact_email1'], $myrow['site_contact_email2'], $myrow['site_contact_email3'],
                                    $myrow['civil_manager_email'], $myrow['facilities_manager_email'], $myrow['pest_manager_email'], $myrow['security_manager_email'], $myrow['traffic_manager_email'],
                                    $myrow['civil_manager_email2'], $myrow['facilities_manager_email2'], $myrow['pest_manager_email2'], $myrow['security_manager_email2'], $myrow['traffic_manager_email2']);

                                $uniqueEmailSend = array_values(array_filter($email_send));

                                // $uniqueEmailSend = array("mahavir.jain@dotsquares.com");

                                if (!empty($uniqueEmailSend)) {
                                    $compliance_obj2 = new compliance;
                                    $compliance_obj2->dbi = $this->dbi;
                                    $compliance_obj2->compliance_check_id = $report_edit_id;
                                    $compliance_obj2->display_results();
                                    foreach ($uniqueEmailSend as $toEmail) {
                                        $mail->clear_all();
//                       echo $toEmail;
//                       die;

                                        $assessor = $myrow['assessor'];
                                        $subject = $myrow['subject'];
                                        $title = $myrow['title'];
                                        $date = $myrow['date'];

                                        $msg = "Hello,<br /><br />";

                                        $msg .= "<h3>The following report or form was just added to " . $this->f3->get('software_name') . ":</h3>
                        <h3>$title</h3> <b>Performed on</b> $date <b>by</b> $assessor " . ($assessor != $subject ? "<b>on</b> $subject" : "");
                                        $msg .= $compliance_obj2->str;
                                        $msg .= "<br/><a href=\"{$this->f3->get('full_url')}Compliance?report_edit_id=$compliance_check_id\">Edit Report</a> | ";
                                        $msg .= "<a href=\"{$this->f3->get('full_url')}Compliance?report_view_id=$compliance_check_id\">View Report</a> | ";
                                        $msg .= "<a href=\"{$this->f3->get('full_url')}CompliancePDF/$compliance_check_id\">Download PDF</a><br/>";
                                        $msg .= "<br /><br />Regards,<br />" . $this->f3->get('company_name');
                                        $str .= $msg;
                                        //$mail->AddReplyTo($assessor_email);
                                        $mail->AddAddress($toEmail);
                                        $mail->Subject = "Report or Form Created on " . $this->f3->get('software_name');
                                        $mail->Body = $css . "\n\n" . $msg;

                                        $mail->queue_message();
                                    }
                                    if ($_SESSION['user_id'] == 5) {
                                        //prd($uniqueEmailSend);
                                    }
                                }

                                //  $str .= $msg;
                            }
                        }
                    }
                    //return $str;
                    $str .= $this->redirect($redirect_str);
                }
                $str .= "<script type=\"text/javascript\">
                  caller('Report Saved');
                  </script>";
            }

            $detect = new Mobile_Detect;
            /* if($detect->isTablet()){
              $m_pfix = "mobile_";
              $standard_font = "22pt";
              $m_font = "mobile_font";
              $question_font = "font-size: 18pt;    padding-bottom: 10px;";
              $q_lbl_font = "font-size: 18pt; padding-top: 10px; padding-bottom: 5px;";
              $button_font = "    padding-top: 15px !important;    padding-bottom: 15px !important;    font-size: 16pt !important;";
              $height_multiplier = "41";
              $text_box_font = "font-size: 26pt !important;    width: 99%;";
              $compliance_auditor_answer_font = "font-size: 16pt; padding: 12px;";
              $answer_button_style = "width: 50px; height: 50px; padding-bottom: 0px;";
              $question_area_padding = "padding-bottom: 0px;  padding-top: 15px;";
              } else if($detect->isMobile()){
              $m_pfix = "mobile_";
              $standard_font = "26pt";
              $m_font = "mobile_font";
              $question_font = "font-size: 25pt;    padding-bottom: 15px;";
              $q_lbl_font = "font-size: 25pt; padding-top: 15px; padding-bottom: 10px;";
              $button_font = "    padding-top: 20px !important;    padding-bottom: 20px !important;    font-size: 22pt !important;     background-size: auto 48px;";
              $height_multiplier = "34";
              $text_box_font = "font-size: 22pt !important;    width: 99%;";
              $compliance_auditor_answer_font = "font-size: 18pt; padding: 15px;";
              $answer_button_style = "width: 70px; height: 70px; padding-bottom: 10px;";
              $question_area_padding = "padding-bottom: 0px;  padding-top: 15px;";
              } else { */
            $standard_font = "11pt";
            $question_font = "font-size: 12pt;    padding-bottom: 5px;";
            $q_lbl_font = "font-size: 12pt; padding-top: 10px; padding-bottom: 5px;";
            $button_font = "    padding-top: 7px !important;    padding-bottom: 7px !important;    font-size: 12pt !important;     background-size: auto 24px;";
            $height_multiplier = "17";
            $text_box_font = "font-size: 11pt !important;    width: 99%;";
            $compliance_auditor_answer_font = "font-size: 11pt; padding: 10px;";
            $answer_button_style = "width: 30px; height: 30px; padding-bottom: 0px;";
            $question_area_padding = "padding-bottom: 0px; padding-left: 0px; padding-right: 0px; padding-top: 15px;";
            //}


            $button_col = "#C9C9C9";
            $str .= '
      <style type="text/css">
       .question_area {
          margin-bottom: 15px;
          margin-top: 25px;
          padding-left: 10px;
          ' . $question_area_padding . '
          border-bottom: 1px solid #BBBBBB;
          /*background-color: #DDDDDD;
          border-bottom: 2px solid #BBBBBB;
          border-top: 2px solid white;*/
        }
         .q_lbl {
            ' . $q_lbl_font . '
            padding-left: 10px;
            margin-top: 20px;
            border-bottom: 2px solid red; background-color: #FFFFCC; font-weight: bold;
         }
         .instructions {
            ' . $q_lbl_font . '
            padding: 15px;
            margin-top: 10px;
            margin-bottom: 10px;
            border: 1px solid #006600; background-color: #FFFFEE;
         }
        .compliance_question {
          ' . $question_font . '
          float: left;
        }
       .save_button {
          ' . $button_font . ';
          width: 49% !important;
          border-radius: 6px 6px 6px 6px;
          -moz-border-radius: 6px 6px 6px 6px;
          -webkit-border-radius: 6px 6px 6px 6px;
          margin-right: 3px;
          margin-top: 10px;
          border: none !important;
        }
        /*.save {
          background-image: url(' . $this->f3->get('img_folder') . 'save.gif);
        }
        .complete {
          background-image: url(' . $this->f3->get('img_folder') . 'save_complete.gif);
        }*/
        .cb {
          text-align: center;
          border: 1px solid #BBBBBB !important;
          background-color: ' . $button_col . ';
          ' . $button_font . ';
          color: black;
          margin: 1px;
          float: left;
          border-radius: 6px 6px 6px 6px;
          -moz-border-radius: 6px 6px 6px 6px;
          -webkit-border-radius: 6px 6px 6px 6px;
          border: 0px solid #000000;border: 0px solid #000000;
          border: 0px solid #000000;
          /*border: 0px solid #000000;    */
        }
        /*.c1 { width: 99%; }
        .c2 { width: 48.7%; }
        .c3 { width: 32%; }
        .c4 { width: 24%; }
        .c5 { width: 19%; }
        .c6 { width: 15.8%; }
        .c7 { width: 13.5%; }
        .c8 { width: 11.8%; }
        .c9 { width: 10.5%; }
        .c10 { width: 9.4%; }
        .c11 { width: 8.55%; }
        .c12 { width: 7.8%; }*/
        
        .c1 { width: 99%; }
        .c2 { width: 47.7%; }
        .c3 { width: 31%; }
        .c4 { width: 23%; }
        .c5 { width: 19%; }
        .c6 { width: 15.4%; }
        .c7 { width: 13.5%; }
        .c8 { width: 11.8%; }
        .c9 { width: 10.5%; }
        .c10 { width: 9.4%; }
        .c11 { width: 8.55%; }
        .c12 { width: 7.8%; }
        
        
        .compliance_answer_button {
          background-size: cover; 
          float: right;
          ' . $answer_button_style . '
          background-image: url(' . $this->f3->get('img_folder') . 'show.gif);
        }
        .img_btn {
          background-size: cover; 
          float: right;
          ' . $answer_button_style . '
          background-image: url(' . $this->f3->get('img_folder') . 'image_show.gif);
        }
        .compliance_add_image {
          background-size: cover; 
          float: right;
          ' . $answer_button_style . '
          background-image: url(' . $this->f3->get('img_folder') . 'show.gif);
        }
        .d_image {
          padding-right: 5px;
          max-width: 255px;
        }
        .compliance_auditor_answer {
          ' . $compliance_auditor_answer_font . '
          background-color: #FFFFEE;
          border: 1px solid #660000;
          margin-right: 10px;
        }
        .compliance_mini_answer {
          display: block;
          ' . $compliance_auditor_answer_font . '
          background-color: #FFFFEE;
          border: 1px solid #660000;
          margin-top: 8px;
          margin-bottom: 8px;
        }
        .img_uploader {
          display: none;
          padding: 0px;
          border: none;
        }
        .compliance_text_box {
          background-color: white;
          color: #222222;
          border: 1px solid #CCCCCC;
          ' . $text_box_font . '
        }
        .compliance_text_box:focus {
          -webkit-text-size-adjust: 100%;
        }
        .time_box {
          width: 300px;
          font-size: 36pt;
        }
        .date_box {
          width: 100%;
          font-size: 36pt;
        }
        .additional_box {
          display: none;
        }
        .opt {
          display: none;
        }
        .time_click_mobile {
          cursor: hand; background-color: #CCCCCC;
          padding-left: 60px !important; padding-right: 60px !important;
          padding-top: 5px; padding-bottom: 5px;
        }
        table.mobile_grid {
          border-width: 1px;
          border-spacing: 0px;
          border-style: outset;
          border-color: #DDDDDD;
          border-collapse: collapse;
          font: 24pt arial;
        }
        table.mobile_grid th {
          border-width: 1px;
          padding: 8px;
          border-style: solid;
          border-color: #DDDDDD;
          background-color: white;
          font: 24pt arial;
          font-weight: bold;
        }
        table.mobile_grid td {
          border-width: 1px;
          padding: 8px;
          border-style: solid;
          border-color: #DDDDDD;
          font: 24pt arial;
          padding-top: 25px;
          padding-bottom: 25px;
        }
        table.mobile_grid tr:nth-child(even) {background-color: #F9F9F9;}
        table.mobile_grid tr:nth-child(odd) {background-color: #FFFFFF;}
        table.mobile_grid td a {
          font: 24pt arial;
        }
        .status_button_mobile {
          padding-top: 42px !important;
          padding-bottom: 42px !important;
          padding-left: 32px !important;
          padding-right: 32px !important;
          border: none !important;
          font-size: 22pt !important;
        }
        .status_button_mobile:hover {
        }
        #file_upload {
          display: none;
        }
        .subject_a {
          font-size: 20px;
          display: block;
          padding: 20px;
          margin-bottom: 8px;
          margin-left: -8px;
          width: 100%;
          background-color: #E9E9E9;
          border-top: 1px solid white;
          border-left: 1px solid white;
          border-bottom: 1px solid #AAAAAA;
          border-right: 1px solid #AAAAAA;
        }
        .subject_a:hover {
          text-decoration: none !important;
          background-color: #DDEEEE;
          border-bottom: 1px solid #9999AA;
          border-right: 1px solid #9999AA;
        }
        .cell_wrap {
          float: left;
          display: inline-block;
          padding: 0px;
          border: 1px solid #DDDDDD;
        }
        .cell_head {
          padding: 6px;
          background-color: white;
        }
        .cell_foot {
          padding: 6px;
          background-color: #F9F9F9;
          border-top: 1px solid #DDDDDD;
        }
      </style> 
      <script type="text/javascript">
        function hostReachable() {
          return true;
    // Handle IE and more capable browsers
          var xhr = new ( window.ActiveXObject || XMLHttpRequest )( "Microsoft.XMLHTTP" );
          var status;
    // Open new request as a HEAD to the root hostname with a random param to bust the cache
          xhr.open( "HEAD", "//" + window.location.hostname + "/?rand=" + Math.floor((1 + Math.random()) * 0x10000), false );
    // Issue request and handle response
          try {
            xhr.send();
            return ( xhr.status >= 200 && (xhr.status < 300 || xhr.status === 304) );
          } catch (error) {
            return false;
          }
        }
        var off_col = "' . $button_col . '"
        var x, bg_col
        var hexDigits = new Array("0","1","2","3","4","5","6","7","8","9","A","B","C","D","E","F");
        function hex(x) {
          return isNaN(x) ? "00" : hexDigits[(x - x % 16) / 16] + hexDigits[x % 16];
        }
        function rgb2hex(rgb) {
         rgb = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
         return "#" + hex(rgb[1]) + hex(rgb[2]) + hex(rgb[3]);
        }
        function show_hide_answer(id) {
          if(document.getElementById("auditor_answer"+id).style.display == "block") {
            document.getElementById("auditor_answer"+id).style.display = "none";
            document.getElementById("compliance_answer_button"+id).style.backgroundImage = "url(' . $this->f3->get('img_folder') . 'show.gif)";
    //compliance_answer_button
          } else {
            document.getElementById("auditor_answer"+id).style.display = "block";
            document.getElementById("compliance_answer_button"+id).style.backgroundImage = "url(' . $this->f3->get('img_folder') . 'hide.gif)";
          }
        }
        function show_hide_img(id,target_dir) {
          if(document.getElementById("img_uploader"+id).style.display == "block") {
            document.getElementById("img_uploader"+id).style.display = "none";
            document.getElementById("img_btn"+id).style.backgroundImage = "url(' . $this->f3->get('img_folder') . 'image_show.gif)";
          } else {
            document.getElementById("img_uploader"+id).style.display = "block";
            document.getElementById("img_btn"+id).style.backgroundImage = "url(' . $this->f3->get('img_folder') . 'image_hide.gif)";
            if(!document.getElementById("photo_upload"+id).src) document.getElementById("photo_upload"+id).src  = "' . $this->f3->get('main_folder') . 'Fileman?show_min=1&target_dir="+target_dir+"&target_subdir="+id;
          }
        }
        function toggle_opt(item_id, num, num_of, sel_col, additional_text_required, choice, first, from_further) {
          if(first === undefined) first = 0;
          if(from_further === undefined) from_further = 0;
          //alert(from_further)
          var reg = new RegExp(\'^\\\\d+$\');
          var is_follow = (reg.test(additional_text_required) === true ? 1 : 0)
          var allow = 1
          //if(is_follow) {
          //  alert(item_id)
            for(x = 1; x < groups.length; x++) {
              if(first) allow = groups[x] == groups[additional_text_required]
              if(allow) {
                if(x == additional_text_required) {
                  e = document.getElementById("child" + additional_text_required)
                  cs = document.defaultView.getComputedStyle(e,null);
                  disp = cs.getPropertyValue("display");
                  e.style.display = (disp == "block" ? "none" : "block");
                  if(e.style.display == "block") {
                    ch = document.getElementById("child_header" + additional_text_required)
                    e.style.borderColor = sel_col
                    e.style.borderBottomColor = sel_col
                    e.style.borderLeftColor = "#EEEEEE"
                    e.style.borderRightColor = "#EEEEEE"
                    ch.style.borderColor = sel_col
                    ch.innerHTML = "Further Questions/Instructions for the Answer " + choice
                  }
                } else {
                  if(from_further) {
                    e = document.getElementById("child" + x)
                    e.style.display = "none";
                  }
                }
              }
            }
          //}
          bg_col = sel_col;
          for(x = 1; x <= num_of; x++) {
            if(document.getElementById("cont" + item_id + x) && !is_follow) {
              document.getElementById("cont" + item_id + x).style.display = "none";
            }
            if(x == num) {
              e = document.getElementById("div" + item_id + x)
              cs = document.defaultView.getComputedStyle(e,null);
              bg = rgb2hex(cs.getPropertyValue("background-color"));
              if(bg == sel_col) sel_col = off_col;
              bg_col = sel_col;
              if(additional_text_required && sel_col != off_col && !is_follow) {
                document.getElementById("cont" + item_id + num).style.display = "block";
              }
              if(sel_col != off_col) {
    //alert(item_id + " " + num)
    //alert(document.getElementsByName("hdnChoices["+x+"]").value)
                  document.getElementById("opt_"+item_id+"_"+num).checked = true;
                  fg_col = "white";
              } else {
                  document.getElementById("opt_"+item_id+"_"+num).checked = false;
                  fg_col = "black";
              }
            } else {
              bg_col = off_col;
              fg_col = "black";
            }
            document.getElementById("div" + item_id + x).style.backgroundColor = bg_col;
            document.getElementById("div" + item_id + x).style.color = fg_col;
          }
        }
        function toggle_chl(item_id, num, num_of, sel_col, additional_text_required, choice, first) {
          if(first === undefined) first = 0;
          var reg = new RegExp(\'^\\\\d+$\');
          var is_follow = (reg.test(additional_text_required) === true ? 1 : 0)
          if(is_follow) {
            e = document.getElementById("child" + additional_text_required)
            cs = document.defaultView.getComputedStyle(e,null);
            disp = cs.getPropertyValue("display");
            e.style.display = (disp == "block" ? "none" : "block");
            ch = document.getElementById("child_header" + additional_text_required)
            e.style.borderColor = sel_col
            ch.style.borderColor = sel_col
            ch.innerHTML = choice + " Further Questions"
          }
          e = document.getElementById("div" + item_id + num)
          cs = document.defaultView.getComputedStyle(e,null);
          bg = rgb2hex(cs.getPropertyValue("background-color"));
          if(bg == sel_col) {
            sel_col = off_col;
            document.getElementById("chk_"+item_id+"_"+num).checked = false;
            if(additional_text_required && !is_follow) {
              document.getElementById("cont" + item_id + num).style.display = "none";
            } 
            fg_col = "black";
          } else {
            document.getElementById("chk_"+item_id+"_"+num).checked = true;
            if(additional_text_required && !is_follow) {
              document.getElementById("cont" + item_id + num).style.display = "block";
            } 
            fg_col = "white";
          }
          document.getElementById("div" + item_id + num).style.backgroundColor = sel_col;
          document.getElementById("div" + item_id + num).style.color = fg_col;
        }
        function submit_report(x) {
         window.onbeforeunload = "";
          if(!navigator.onLine) {
            document.getElementById("hdnCompleteReport").value = 0
            alert("You are currently not online.\nThe form HAS NOT been submitted.\nPlease check your connection and try again...")
          } else {
            for(x = 1; x < groups.length; x++) {
              e = document.getElementById("child" + x)
              cs = document.defaultView.getComputedStyle(e,null);
              disp = cs.getPropertyValue("display");
              var srch = e.getElementsByTagName("*");
              if(disp == "none") {
                for(var i = 0; i < srch.length; i++) {
                  if(srch[i].type == "textarea" && srch[i].value != "") srch[i].value = ""
                  if(srch[i].tagName == "INPUT") {
                    if(srch[i].getAttribute("type") == "radio") {
                      srch[i].checked = false
                    } else if(srch[i].getAttribute("type") == "text") {
                      srch[i].value = ""
                    }
                  } else {
                    //if(srch[i].getAttribute("type") != null) alert (srch[i].getAttribute("type"))
                  }
                }
              }
            }
    //Saving Signature (if there is one)
          if(typeof save_canvas !== "undefined"){
            save_canvas();
            setTimeout(function() {document.getElementById("hdnSubmitReport").value = 1, document.frmEdit.submit()},2000);          
          }else{
            document.getElementById("hdnSubmitReport").value = 1
            document.frmEdit.submit()
            
          }
          
            if(typeof save_canvas !== "undefined")   save_canvas();
                
                document.getElementById("hdnSubmitReport").value = 1
                document.frmEdit.submit()
         }       
        }
        function complete_report(x) {
          confirmation = "' . ($job_application_id || $job_application2_id || $request_id ? "Do you wish to save all of your details now?" : ($is_induction ? "Save Induction Quiz?" : ($is_request || $is_form ? "Save and Complete this Form Now?" : ($is_survey ? "Save this Survey Now?" : "Are you sure about completing this report?\\n\\nWARNING: THIS CANNOT BE REVERSED!")))) . '";
          if(confirm(confirmation)) {
            document.getElementById("hdnCompleteReport").value = 1
            submit_report(x)
          }
        }
        function change_status(change_itm, status_in, status_txt) {
          var confirmation;
          confirmation = "Are you sure about changing the status this record to "+status_txt+"?";
          if (confirm(confirmation)) {
            document.getElementById("hdnChangeItem").value = change_itm
            document.getElementById("hdnChangeStatus").value = status_in
            document.frmEdit.submit()
          }
        }
      </script>
      ';
            $view_details = new data_list;
            $view_details->show_num_records = 1;

            $view_details->dbi = $this->dbi;
            //$view_details->table_class = $m_pfix."grid";
            //$str .= "$report_edit_id || $report_view_id && !$is_induction";


            if ($report_edit_id || $report_view_id && !$is_induction) {

                $rid = ($report_edit_id ? $report_edit_id : $report_view_id);
                $sql = "select compliance.id as compliance_id, compliance.title, compliance_checks.id as `compliance_check_id`, compliance.show_answers,
                  CONCAT('<nobr>', DATE_FORMAT(compliance_checks.check_date_time, '%d-%b-%Y %H:%i'), '</nobr>') as `date`,
                  CONCAT('<nobr>', DATE_FORMAT(compliance_checks.last_modified, '%d-%b-%Y %H:%i'), '</nobr>') as `last_modified`,
                  CONCAT(if(users.employee_id != '', users.employee_id, ''), ' ', if(users.client_id != '', users.client_id, ''), ' ', users.name, ' ', users.surname) as `assessor`, CONCAT(users.name, ' ', users.surname) as `assessor_fullname`, users.phone, CONCAT(users.address, ' ', users.suburb, ' ', states.item_name, ' ', users.postcode) as `address`,
                  CONCAT(if(users2.employee_id != '', users2.employee_id, ''), ' ', if(users2.client_id != '', users2.client_id, ''), ' ', users2.name, ' ', users2.surname) as `subject`, " . ($job_application_id || $job_application2_id || $request_id || $is_survey || $is_induction || $z || !$this->f3->get('is_staff') ? "" : "
                  CONCAT('<a target=\"_blank\" uk-tooltip=\"title: View Online\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_view_id=', compliance_checks.id, '\">View</a>') as `view`,
                  CONCAT('<a target=\"_blank\" uk-tooltip=\"title: View as PDF\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "CompliancePDF/', compliance_checks.id, '\">PDF</a>', '<a target=\"_blank\" uk-tooltip=\"title: Upload/View Attachemts\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Resources?current_dir=compliance&current_subdir=', compliance_checks.id, '\">Attachments...</a>',
                  '<a target=\"_blank\" uk-tooltip=\"title: Add Notes\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Page/ComplianceCheckNotes?compliance_check_id=', compliance_checks.id, '\">Notes...</a>',
                  '<a target=\"_blank\" uk-tooltip=\"title: Resolve Issues\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "ComplianceFollowup?compliance_check_id=', compliance_checks.id, '\">Resolve Issues...</a>'
                  ) as `pdf`,
                  CONCAT('<a uk-tooltip=\"title: Edit Checklist\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_edit_id=', compliance_checks.id, '\">Edit</a>')	as `edit`,") . "
                  " . ($request_id ? "concat('<a target=\"_blank\" uk-tooltip=\"title: Upload/View Attachemts\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Resources?current_dir=compliance&current_subdir=', compliance_checks.id, '\">Attachments...</a>') as `attachments`, " : "") . "
                  lookup_fields1.item_name as `status`,
                  compliance_checks.total_out_of as `out_of`
                  FROM compliance_checks
                  left join lookup_fields1 on lookup_fields1.id = compliance_checks.status_id
                  left join compliance on compliance.id = compliance_checks.compliance_id
                  left join users on users.id = compliance_checks.assessor_id
                  left join users2 on users2.id = compliance_checks.subject_id
                  left join states on states.id = users.state
                  where compliance_checks.id = $rid";
                //$str .= "<textarea>{$sql}</textarea>";
                if ($result = $this->dbi->query($sql)) {
                    while ($myrow = $result->fetch_assoc()) {
                        $compliance_id = $myrow['compliance_id'];
                        $compliance_title = $myrow['title'];
                        $show_answers = $myrow['show_answers'];
                        $status = $myrow['status'];
                        $assessor_address = $myrow['address'];
                        $assessor_phone = $myrow['phone'];
                        $date = $myrow['date'];
                        $last_modified = $myrow['last_modified'];
                        $subject = $myrow['subject'];
                        $assessor = $myrow['assessor'];
                        $assessor_fullname = $myrow['assessor_fullname'];
                        if (!$this->f3->get('is_mobile') && !$this->f3->get('is_tablet')) {
                            $vxtra = (($report_edit_id || $status == 'Pending') && !$is_survey && !$is_induction && !$is_request && !$is_form && $this->f3->get('is_staff') ? '<div class="cell_wrap"><div class="cell_head">**</div><div class="cell_foot">' . $myrow[($report_edit_id ? 'view' : 'edit')] . $myrow['pdf'] . '</div></div>' : '');
                        }
                        if ($job_application_id || $job_application2_id || $request_id || $form_id) {
                            $vxtra = "";
                            $st_xtra = "";
                            if ($job_application_id) {
                                $str .= '<div class="q_lbl" style="margin-bottom: 20px;">' . ($report_view_id ? "<h1>Step 3 of 3, Review and Complete.</h1><h3>Please review your answers below and click \"Complete Application\" when finished.<br /><br />* Don't forget to upload your resume, cover letter and approprite licances/certificates by clicking the link below...</h3>" : "<h1>Step 2 of 3, More Information.</h1><h3>Please answer <u>ALL</u> of the questions below and save them to complete your job application.</h3>") . "</div>";
                            } else {
                                $str .= '<div class="q_lbl" style="margin-bottom: 20px;">' . ($report_view_id ? "<h1>Review and Complete.</h1><h3>Please review the answers below...</h3>" : ($request_id ? "<h1>Request Form.</h1><h3>Please answer the questions below and save the form.</h3>" : "<h1>Interview Checklist.</h1><h3>Please go through the checklist below and save it.</h3>")) . "</div>";
                            }
                            if (($report_view_id || $report_edit_id) && ($job_application_id || $job_application2_id)) {
                                $tmp_id = ($report_view_id ? $report_view_id : $report_edit_id);
                                $sql2 = "update job_applications set compliance_check_id_interviewe" . ($job_application_id ? "e" : "r") . " = $tmp_id where id = " . ($job_application_id ? $job_application_id : $job_application2_id);
                                $result2 = $this->dbi->query($sql2);
                            }
                            if ($job_application_id)
                                $str .= '<a target="_blank" class="list_a" href' . $this->f3->get('main_folder') . 'Resources?job_application_id=' . $job_application_id . '&current_dir=compliance&current_subdir=' . ($report_view_id ? $report_view_id : $report_edit_id) . '">Click Here to Attach Your Resume, Cover Letter and Appropriate Licences</a><br /><br />';
                            if ($request_id && !$report_edit_id) {
                                $str .= "<p><a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_edit_id=$report_view_id&" . ($job_application_id ? "job_application_id" : ($request_id ? "request_id" : "job_application2_id")) . "=" .
                                        ($job_application_id ? $job_application_id : ($request_id ? $request_id : $job_application2_id)) . "\"><< Go Back to Answering Questions</a>      " .
                                        ($job_application_id || $request_id ? (!$complete_application ? "<a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_view_id=$report_view_id&job_application_id=$job_application_id&request_id=$request_id&complete_application=1\">Complete Application >></a></p>" : "") : "<a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "JobApplications?more_info=$job_application2_id\">View Outcome >></a></p>");
                            }
                        } else {

                            $str .= "<h3>" . ($is_survey ? "Survey Details" : ($is_induction ? "Induction Questions" : ($is_request || $is_form ? "" : "Details"))) . "</h3>";
                            $vxtra = '
                ' . ($assessor != $subject ? '
                <div class="cell_wrap"><div class="cell_head">Assessor</div><div class="cell_foot">' . $assessor . '</div></div>
                <div class="cell_wrap"><div class="cell_head">Subject</div><div class="cell_foot">' . $subject . '</div></div>
                ' : '<div class="cell_wrap"><div class="cell_head">By</div><div class="cell_foot">' . $assessor . '</div></div>') . $vxtra .
                                    ($is_survey || $is_induction || !$this->f3->get('is_staff') || $is_induction || $is_request || $is_form || $this->f3->get('is_mobile') || $this->f3->get('is_tablet') ? '' : '<div class="cell_wrap"><div class="cell_head">&lt;&lt;</div><div class="cell_foot">' . "<a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_id=$compliance_id\">Back</a></div>" . '</div>');
                        }
                        $vxtra .= ($request_id ? '<div class="cell_wrap"><div class="cell_head">Attachments</div><div class="cell_foot">' . $myrow['attachments'] . '</div></div>' : '');
                        if ($compliance_title && !(($is_induction || $is_request || $is_form) && $form_message)) {
                            if (!($request_id || $is_induction || $is_request || $is_form))
                                $str .= '<div class="cell_wrap"><div class="cell_head">Status</div><div class="cell_foot">' . $status . '</div></div>';
                            $str .= '
              <div class="cell_wrap"><div class="cell_head">Date</div><div class="cell_foot">' . $date . '</div></div>
              <div class="cell_wrap"><div class="cell_head">Last Modified</div><div class="cell_foot">' . $last_modified . '</div></div>
              <div class="cell_wrap"><div class="cell_head">Title</div><div class="cell_foot">' . $compliance_title . '</div></div>
              ' . $vxtra;
                        }
                        $str .= '<div class="cl"></div>';
                        if ($job_application_id && !$report_view_id) {
                            $str .= "<br /><br /><h3>Please answer <u>ALL</u> of the following questions as accurately as possible to be considered for the role.</h3>";
                        }
                    }
                }
            }


            if ($report_id || $subject_id || $auditor_id) {

                if ($report_id && $report_id != "ALL") {


                    $for_by = " for ";
                    $sql = "select title, show_all_entities, description,division_id from compliance where id = $report_id";
                    if ($result = $this->dbi->query($sql)) {
                        if ($myrow = $result->fetch_assoc()) {
                            $title = $myrow['title'];
                            $show_all_entities = $myrow['show_all_entities'];
                            $search_desc = $myrow['description'];
                            $division_id = $myrow['division_id'];
                        }
                    }
                } else if ($subject_id) {
                    $xtra = "2";
                    $xtra2 = "";
                    $for_by = " for ";
                    $is_about = "Assessor";
                } else {

                    $subject_id = $auditor_id;
                    $xtra = "";
                    $xtra2 = "2";
                    $for_by = " by ";
                    $is_about = "Subject";
                }

                if ($subject_id || $auditor_id) {
                    $sql = "select employee_id, client_id, name, surname from users where id = $subject_id";
                    if ($result = $this->dbi->query($sql)) {
                        if ($myrow = $result->fetch_assoc()) {
                            $title = $myrow['employee_id'] . " " . $myrow['client_id'] . " " . $myrow['name'] . " " . $myrow['surname'];
                        }
                    }
                    $where_cond = " where users$xtra.id = $subject_id ";
                } else {

                    $loginUserId = $_SESSION['user_id'];
                    $loginUserDivisions = $this->get_divisions($loginUserId, 0, 1);
                    $loginUserDivisionsArray = explode(',', $loginUserDivisions);
                    $loginUserDivisionsArray = array_map('trim', $loginUserDivisionsArray);
                    $loginUserDivisionsStr = implode(',', $loginUserDivisionsArray);

                    /* by Mj */
                    $where_cond = " where compliance.category_id in (2101,2007,2008,2009,2010,2420,2011,2147,2221) and " . ($report_id == "ALL" ? "(compliance.division_id = '' or compliance.division_id in (" . $loginUserDivisionsStr . "))" : "(compliance.division_id = '' or compliance.division_id in (" . $loginUserDivisionsStr . ")) and compliance_checks.compliance_id = $report_id");

                    $userTypeId = $this->userDataAccessType($loginUserId);
                    if ($userTypeId == 2) {
                        $assignedStateData = $this->getAssignedStateIds($loginUserId);
                        if (is_array($assignedStateData)) {
                            $stateAssignedDataArray = array_column($assignedStateData, 'id');
                        } else {
                            $stateAssignedDataArray = array();
                        }

                        $stateAssignedDataStr = implode(',', $stateAssignedDataArray);

                        $where_cond .= ' and locationState.id in (' . $stateAssignedDataStr . ') ';
                    } else if ($userTypeId == 3) {
                        $parentSites = 0;
                        $stateList = $this->getStateIds();
                        $parentSites = $this->getUserSiteIds($loginUserId);
                        $assignedSiteArray = array();
                        if ($parentSites != "") {
                            //$assignedSiteArray = explode(',', $parentSites);                }
                            $where_cond .= ' and users2.id in (' . $parentSites . ') ';
                        } else {
                            $where_cond .= ' and users2.id in (' . $parentSites . ') ';
                        }
                    }




                    //$where_cond = " where " . ($report_id == "ALL" ? "1" : "compliance_checks.compliance_id = $report_id");
                }
                if ($report_id && $subject_id) {
                    $where_cond = " where users2.id = $subject_id and compliance_checks.compliance_id = $report_id ";
                }
                if (!$is_manager) {
                    $sql = "select id from compliance where id = $report_id and allow_all_access = 1;";
                    if ($result = $this->dbi->query($sql))
                        $all_access = $result->num_rows;
                }


                if ($new_report) {
                    if (!$show_all_entities)
                        $str .= "<h3 class=\"mobile_font\">$title</h3><br />";
                    $search_str = trim((isset($_GET['compliance_search']) ? $_GET['compliance_search'] : ($search_desc ? $search_desc : null)));
                    if ($show_all_entities && !$_GET['compliance_search'])
                        $search_str = "ALL";
                    if ($search_str) {
                        if ($search_str != 'ALL') {
                            $filter = " and (users.name LIKE '%$search_str%' or users.surname LIKE '%$search_str%' or users.email LIKE '%$search_str%' or users.employee_id LIKE '%$search_str%' or users.client_id LIKE '%$search_str%'
              or CONCAT(users.name, ' ', users.surname) LIKE '%$search_str%')";
                        } else {
                            $filter = "";
                        }
                        $subqueryDivision = ",(SELECT GROUP_CONCAT(' ',com.id) FROM lookup_answers la 
LEFT JOIN companies com ON com.id = la.lookup_field_id
WHERE la.foreign_id = users.id AND com.id IS NOT NULL) as `division_ids`";

                        // $userType = 384;
                        //$usertype_xtra = ($userType ? "inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = $userType and lookup_answers.table_assoc = 'users'" : "");
                        //company division query
                        $filter .= ($company_ids ? " and users.id in (select user_id from users_user_groups where user_group_id in ($company_ids)) " : "");

                        $sql = "select * from (
                      select users.id as `user_id`, users.name, users.surname as sname, users.employee_id, users.client_id
                      $subqueryDivision
                      from compliance_auditors_subjects
                      left join users on users.id = compliance_auditors_subjects.user_id
                     
                      where compliance_auditors_subjects.compliance_auditor_id = (select id from compliance_auditors where user_id = " . $_SESSION['user_id'] . " and compliance_id = $report_id ) $filter
                      ";
                        if ($is_manager || $all_access) {

                            $sql = "$sql union distinct select users.id as `user_id`, users.name, users.surname as sname, users.employee_id, users.client_id
                  $subqueryDivision
                        from users
                        
                      where id in (
                        select users.id from users inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id
                        in (select lookup_field_id from lookup_answers where table_assoc = 'compliance' and foreign_id = $report_id)
                        and lookup_answers.table_assoc = 'users' and users.user_status_id = (select id from user_status where item_name = 'ACTIVE') $filter
                      )";
                        }
                        $sql .= ") a order by sname";

//           echo $sql;
//           die;
                        if ($result = $this->dbi->query($sql))
                            $num_items = $result->num_rows;
                        //$num_items = $this->num_results($sql);
                    } else {
                        $num_items = 0;
                    }

                    if (!$num_items) {
                        if ($this->num_results("SELECT id FROM lookup_fields where value = 'SITE' and id = (select lookup_field_id from `lookup_answers` where foreign_id = $report_id and table_assoc = 'compliance')")) {

                            $sql = "select users.id as `user_id`, users.name, users.surname as sname, users.employee_id, users.client_id
                   $subqueryDivision
                                from users                                
                                left join associations on associations.parent_user_id = users.id
                                where associations.association_type_id = (select id from association_types where name = 'site_staff') and associations.child_user_id = " . $_SESSION['user_id'];
                            
                            if ($result = $this->dbi->query($sql))
                                $num_items = $result->num_rows;
                        }
                    }

                    $subject_type = $this->get_sql_result("SELECT lookup_fields.item_name as `result` FROM `lookup_answers`
          left join lookup_fields on lookup_fields.id = lookup_answers.lookup_field_id
          where table_assoc = 'compliance' and lookup_answers.foreign_id = $report_id");
                    $subject_type = ($subject_type ? $subject_type : "Subject");
                   
                    $str .= '
                    </form>
                    <script type="text/javascript">
                        function show_all() {
                            document.getElementById("compliance_search").value = "ALL";
                            document.frmFollowSearch.submit();
                        }
                    </script>
                    <form method="get" name="frmFollowSearch">
                        <input type="hidden" name="report_id" value="' . $report_id . '" />
                        <input type="hidden" name="new_report" value="' . $new_report . '" />
                        <div class="fl">
                            <input maxlength="50" value="Search for a ' . $subject_type . ' here" name="compliance_search" id="compliance_search" type="text" class="search_box off-search" />
                            <input type="submit" name="cmdFollowSearch" value="Search" class="search_button" />
                            <input type="button" id="showAllButton" onClick="show_all()" name="cmdFollowSearch" value="Show All" class="search_button" style="margin-left: 2px;" />
                        </div>
                        <div class="cl"></div><br /><br />
                    </form>
                ';
                
                
            
                
                    
                    

                    /* Vestigial code from the KPI system
                      if($num_items) $str .= "<div style=\"font-weight: bold; font-size: 20px;\">Select a $subject_type for $title:</div><br />";
                      $sql = "SELECT check_date_time,
                      concat(users.name, ' ', users.surname) as `subject`, concat(users2.name, ' ', users2.surname) as `assessor`,
                      compliance.category_id as `ccat`
                      FROM `compliance_checks`
                      left join users on users.id = compliance_checks.subject_id
                      left join users2 on users2.id = compliance_checks.assessor_id
                      left join compliance on compliance.id = compliance_checks.compliance_id
                      where compliance_id = $report_id and compliance_checks.status_id != 525
                      order by check_date_time DESC LIMIT 1
                      ";
                      if($result2 = $this->dbi->query($sql)) {
                      if($myrow2 = $result2->fetch_assoc()) {
                      if(Date("m-Y", strtotime($myrow2['check_date_time'])) == Date("m-Y") && $myrow2['ccat'] == 2010) $str .= "<h3 style=\"color: red;\">NOTE: A KPI for " . $myrow2['subject'] . " has already been performed this month.</h3><br />";
                      }
                      }
                     */



                    if ($num_items) {
                        //$str .= "<div style=\"font-weight: bold; font-size: 20px;\">Select a $subject_type for $title:</div><br />";
                        $complianceDivisionId = $this->getComplianceDivisions($report_id);

                        $divisionNameRecord = $this->getDivisionIds($complianceDivisionId);
                        if ($divisionNameRecord) {
                            $divisionName = $divisionNameRecord[0]['item_name'];
                        } else {
                            $divisionName = "All";
                        }

                        $str .= "<div style=\"font-weight: bold; font-size: 20px;\">Select a Location($divisionName) for $title:</div><br />";

                        while ($myrow = $result->fetch_assoc()) {
                            $user_id = $myrow['user_id'];
                            $name = $myrow['name'];
                            $client_id = $myrow['client_id'];
                            $employee_id = $myrow['employee_id'];
                            $surname = $myrow['sname'];

                            $userDivisionId = $myrow['division_ids'];
                            if ($complianceDivisionId) {
                                $validDivisionId = $this->checkValidComplianceUserDivisionId($complianceDivisionId, $userDivisionId);
                            } else {
                                $validDivisionId = 1;
                            }
                            if ($user_id && $validDivisionId) {
                                //prd($user_id);
                                $isLocationTypeUser = $this->isUserLocationType($user_id);
                                if ($isLocationTypeUser) {
                                    $sql = "SELECT check_date_time, concat(users.name, ' ', users.surname) as `assessor`
                        FROM `compliance_checks`
                        left join users on users.id = compliance_checks.assessor_id
                        where compliance_id = $report_id and compliance_checks.status_id != 525 and compliance_checks.subject_id = '$user_id'
                        order by check_date_time DESC LIMIT 1
                ";
                                    if ($result2 = $this->dbi->query($sql)) {
                                        if ($myrow2 = $result2->fetch_assoc()) {
                                            $perform_xtra = "<span style=\"font-size: 10pt;\">(Last Performed By " . $myrow2['assessor'] . ": " . Date("d-M-Y", strtotime($myrow2['check_date_time'])) . ")</span>";
                                            if (Date("m-Y", strtotime($myrow2['check_date_time'])) == Date("m-Y") && $myrow2['ccat'] == 2010)
                                                $str .= "<h3 style=\"color: red;\">NOTE: A KPI for " . $myrow2['subject'] . " has already been performed this month.</h3><br />";
                                        } else {
                                            $perform_xtra = "";
                                        }
                                    }
                                    $str .= "<a class=\"subject_a\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_id=$report_id&site_id=$user_id\">$employee_id $name $surname $perform_xtra</a>";
                                }
                            }
                        }
                    }
                } else {

                    if ($site_id) {
                        $sql = "insert into compliance_checks (compliance_id, assessor_id, subject_id, check_date_time, last_modified, status_id) values ($report_id, " . $_SESSION['user_id'] . ", $site_id, '$curr_date_time', '$curr_date_time', 522)";
                        //$str .= "<h3>$sql</h3>";
                        $result = $this->dbi->query($sql);
                        //print_r($this->dbi->error);
                        $check_id = $this->dbi->insert_id;
                        $redirect_str = $this->f3->get('main_folder') . 'Compliance?report_edit_id=' . $check_id . ($prefill ? "&prefill=$prefill" : "");
                        $redirect_str .= ($job_application_id ? "&job_application_id=$job_application_id" : ($job_application2_id ? "&job_application2_id=$job_application2_id" : ($request_id ? "&request_id=$request_id" : "")));
                        //$str .= $redirect_str;
                        $str .= $this->redirect($redirect_str);
                    } else {
                        if ($report_id && $report_id != "ALL" && !($subject_id))
                            $str .= "<div style=\"margin-top: 50px; margin-bottom: 50px;\"><a class=\"mobile_font\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_id=$report_id&new_report=1\">Start a New Report for $title</a></div>";
                        if ($_POST['hdnChangeItem']) {
                            $sql = "update compliance_checks set status_id = " . $_POST['hdnChangeStatus'] . " where id = " . $_POST['hdnChangeItem'];
                            $result = $this->dbi->query($sql);
                        }
                        $sql = "select id, sort_order, value, item_name from lookup_fields where lookup_id in (select id from lookups where item_name = 'compliance_status') order by sort_order";
                        if ($result = $this->dbi->query($sql)) {
                            $stat_xtra = "";
                            $stat_xtra2 = "";
                            while ($myrow = $result->fetch_assoc()) {
                                $str_tmp = "CONCAT('<input type=\"button\" class=\"cs_button\" style=\"font-size: $standard_font !important;\" value=\"" . $myrow['value'] . "\" onClick=\"change_status(', compliance_checks.id, ', " . $myrow['id'] . ", \'" . $myrow['item_name'] . "\')\" />')";
                                //if($myrow['sort_order'] != 20) $stat_str = $str_tmp;
                                if ($myrow['sort_order'] != 20) {
                                    $stat_str .= "$stat_xtra $str_tmp";
                                    $stat_xtra = ", ";
                                }
                                if ($myrow['sort_order'] != 10) {
                                    $stat_str2 .= "$stat_xtra2 $str_tmp";
                                    $stat_xtra2 = ", ";
                                }
                            }
                        }
                        $stat_str = "
                          if(lookup_fields1.item_name = 'Pending',
                          CONCAT($stat_str2),
                          CONCAT($stat_str))
                          as `Change Status`,
            ";
                        if ($report_id == "ALL") {
                            $view_details->title = "Checklists Overview";
                        } else {
                            $view_details->title = "Checklists $for_by $title";
                        }
                        if ($subject_id || $auditor_id) {
                            $title_str = " compliance.title as `Title`, ";
                        }
                        if ($is_manager || $all_access) {
                            $mgr_xtra = "or compliance.id in (select foreign_id from lookup_answers where table_assoc = 'compliance')";
                        }
                        if ($report_id == "ALL") {
                            $title_xtra = "compliance.title as `Title`, ";
                            $filter .= " and compliance_checks.percent_complete != 0 ";
                            $reloadLocation = "<script>setTimeout(function () {
            location.reload();
        }, 30000);</script>";
                        }
                        if ($txtComplianceFilter)
                            $where_cond .= " and (compliance_check_answers.answer LIKE '%$txtComplianceFilter%' or compliance_check_answers.additional_text LIKE '%$txtComplianceFilter%' or users2.name like '%$txtComplianceFilter%' or users.name like '%$txtComplianceFilter%' or compliance.title like '%$txtComplianceFilter%' or compliance_checks.id = '$txtComplianceFilter') ";
                        $where_cond .= " and compliance.title not like '%job application%' ";

                        //company division query
                        $where_cond .= ($company_ids ? " and users2.id in (select user_id from users_user_groups where user_group_id in ($company_ids)) " : "");

                        if ($is_client) {
                            $view_details->sql = "
                select 
                compliance_checks.id as idin,
                CONCAT('<a target=\"_blank\" uk-tooltip=\"title: View as PDF\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "CompliancePDF/', compliance_checks.id, '\">PDF</a>') as `***`,
                CONCAT('<a uk-tooltip=\"title: View This Report Online\" class=\"list_a\" target=\"_blank\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_view_id=', compliance_checks.id, '\">View Online</a>') as `View`,
                CONCAT(if(users.employee_id != '', users.employee_id, ''), ' ', if(users.client_id != '', users.client_id, ''), ' ', users.name, ' ', users.surname) as `Assessor`,
                $title_xtra
                CONCAT(users2.name, ' ', users2.surname) as `Site`,
                CONCAT('<nobr>', DATE_FORMAT(compliance_checks.check_date_time, '%d-%b-%Y'), '</nobr>') as `Date`, $title_str lookup_fields1.item_name as `Status`,
                if(compliance_checks.total_out_of = 0, 'N/A', CONCAT((select sum(value) from compliance_check_answers where compliance_check_id = compliance_checks.id), '/', compliance_checks.total_out_of)) as `Score`
                FROM compliance_checks
                left join lookup_fields1 on lookup_fields1.id = compliance_checks.status_id
                left join compliance on compliance.id = compliance_checks.compliance_id
                left join users on users.id = compliance_checks.assessor_id
                left join users2 on users2.id = compliance_checks.subject_id
                left join compliance_check_answers on compliance_check_answers.compliance_check_id = compliance_checks.id
                where users2.id = $subject_id and compliance.min_user_level_id <= " . $_SESSION['u_level'] . " and compliance.is_active = 1 and compliance.category_id not in (select id from lookup_fields where item_name LIKE '%request%')
                $filter
                " . ($txtComplianceFilter ? " and (compliance_check_answers.answer LIKE '%$txtComplianceFilter%' or compliance_check_answers.additional_text LIKE '%$txtComplianceFilter%' or users2.name like '%$txtComplianceFilter%') " : "") . "
                or users2.id in (select child_user_id from associations where association_type_id = 1 and parent_user_id = $subject_id)
                $filter
                
                group by compliance_checks.id
                order by check_date_time desc
              ";

                            //return "<textarea>{$view_details->sql}</textarea>";
                        } else {

                            $reportStartDate = $_REQUEST['calReportStartDate'];
                            $calReportEndDate = $_REQUEST['calReportEndDate'];

                            if ($reportStartDate && $calReportEndDate) {
//                    echo $reportStartDate;
//                     echo date('Y-m-d',strtotime($reportStartDate));
//                    die;
                                $reportStartDateConv = date('Y-m-d', strtotime($reportStartDate));
                                $reportEndDateConv = date('Y-m-d', strtotime($calReportEndDate));
                                $where_cond .= " and DATE(compliance_checks.check_date_time) > '" . $reportStartDateConv . "' and DATE(compliance_checks.check_date_time) < '" . $reportEndDateConv . "'";
                            } else if ($reportStartDate) {
//                    echo $reportStartDate;
//                     echo date('Y-m-d',strtotime($reportStartDate));
//                    die;
                                $reportStartDateConv = date('Y-m-d', strtotime($reportStartDate));
                                $where_cond .= " and DATE(compliance_checks.check_date_time) = '" . $reportStartDateConv . "'";
                            } else if ($calReportEndDate) {
//                    echo $reportStartDate;
//                     echo date('Y-m-d',strtotime($reportStartDate));
//                    die;
                                $reportEndDateConv = date('Y-m-d', strtotime($calReportEndDate));
                                $where_cond .= " and DATE(compliance_checks.check_date_time) = '" . $reportEndDateConv . "'";
                            }


                            $srchDivisionId = $_REQUEST['complianceDivision'];
                            if ($srchDivisionId) {
                                $where_cond .= " and compliance.division_id = '" . $srchDivisionId . "'";
                            }




                            $filterSql = "
                select compliance_checks.id as idin,
                CONCAT('<a uk-tooltip=\"title: View Online\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_view_id=', compliance_checks.id, '\">View</a>', '<a uk-tooltip=\"title: View as PDF\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "CompliancePDF/', compliance_checks.id, '\">PDF</a><br /><a uk-tooltip=\"title: Upload/View Attachemts\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Resources?current_dir=compliance&current_subdir=', compliance_checks.id, '\">Attachments...</a>
                <br /><a uk-tooltip=\"title: Add Notes\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Page/ComplianceCheckNotes?compliance_check_id=', compliance_checks.id, '\">Notes...</a>',
                '<a uk-tooltip=\"title: Add Notes\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "ComplianceFollowup?compliance_check_id=', compliance_checks.id, '\">Resolve Issues...</a>') as `***`,
                if((lookup_fields1.item_name = 'Pending' and users.id = " . $_SESSION['user_id'] . ") or (lookup_fields1.item_name = 'Pending' and compliance.allow_sharing = 1),
                CONCAT('<a uk-tooltip=\"title: Edit Checklist\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_edit_id=', compliance_checks.id, '\">Edit</a>'),
                '<span class=\"list_a\"><strike>Edit</strike></span>')
                as `**`,
                CONCAT(if(users.employee_id != '', users.employee_id, ''), ' ', if(users.client_id != '', users.client_id, ''), '<br />', users.name, ' ', users.surname) as `Assessor`,                CONCAT(if(users2.employee_id != '', users2.employee_id, ''), ' ', if(users2.client_id != '', users2.client_id, ''), '<br />', users2.name, ' ', users2.surname) as `Subject`, $title_xtra
                (SELECT if(comp.id,comp.item_name,'All') as divisionName FROM compliance com
LEFT JOIN companies comp  on comp.id = com.division_id where comp.id = compliance.division_id LIMIT 1 ) division,
CONCAT('<nobr>', DATE_FORMAT(compliance_checks.check_date_time, '%d-%b-%Y'), '</nobr>') as `Date`, $title_str concat(lookup_fields1.item_name, '<br />', compliance_checks.percent_complete, '% Done') as `Status`, $stat_str
                compliance_checks.id as `ID`,
                if((lookup_fields1.item_name = 'Pending' and users.id = " . $_SESSION['user_id'] . ") or (lookup_fields1.item_name = 'Pending' and compliance.allow_sharing = 1),
                CONCAT('<a uk-tooltip=\"title: Edit Checklist\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_edit_id=', compliance_checks.id, '\">Edit</a>'),
                '<span class=\"list_a\"><strike>Edit</strike></span>')
                as `** `,
                if(compliance_checks.total_out_of = 0, 'N/A', CONCAT((select sum(value) from compliance_check_answers where compliance_check_id = compliance_checks.id), '/', compliance_checks.total_out_of)) as `Score`
                FROM compliance_checks
                left join lookup_fields1 on lookup_fields1.id = compliance_checks.status_id
                left join compliance on compliance.id = compliance_checks.compliance_id
                left join users on users.id = compliance_checks.assessor_id
                left join users2 on users2.id = compliance_checks.subject_id
                left join states locationState ON locationState.id = users2.state 
                left join compliance_check_answers on compliance_check_answers.compliance_check_id = compliance_checks.id
                $where_cond
                $filter
                ";
//              if($_REQUEST['complianceDivision'] != ""){
//                  $filterSql .= " and compliance.division_id = '".$_REQUEST['complianceDivision']."'";
//              }
                            $filterSql .= " and compliance.min_user_level_id <= " . $_SESSION['u_level'] . " and compliance.is_active = 1
                and (compliance_checks.subject_id in (select user_id from compliance_auditors_subjects where compliance_auditor_id in (select id from compliance_auditors where user_id = " . $_SESSION['user_id'] . "))
                $mgr_xtra)
                
                group by compliance_checks.id
                order by check_date_time desc
              ";

                            $view_details->sql = $filterSql;

//              echo $view_details->sql;
//              die;
                        }
                    }
                }
            } else if ($report_edit_id) {
                if ($_SESSION['user_id'] == 1) {
                    //die;
                }
                //die;
                $txt_itm = new input_item;
                $str .= $txt_itm->setup_ti2();

                //if($detect->isTablet() || $detect->isMobile()) {
                $str .= $txt_itm->setup_clm();
                $str .= $txt_itm->setup_tad();
                //} else {
//          $str .= $txt_itm->setup_cal();
                //}

                $str .= '
        <input type="hidden" id="hdnSubmitReport" name="hdnSubmitReport" />
        <input type="hidden" id="hdnCompleteReport" name="hdnCompleteReport" />
        ';
                //$str .= $this->f3->get('download_folder') . "compliance/$compliance_id";
                if (file_exists($this->f3->get('download_folder') . "compliance/$compliance_id") && !$is_induction) {
                    $str .= '<iframe width="100%" height="180" src="' . $this->f3->get('main_folder') . 'Resources?show_min=1&current_dir=compliance&current_subdir=downloads' . urlencode('/') . $compliance_id . '" frameborder="0"></iframe>';
                }

                if (!$is_survey && !$is_prestart && !$is_induction && !$is_request && !$is_form) {

                    $str .= ((!$job_application_id && !$is_prestart && !$job_application2_id && !$request_id && !$is_induction && !$is_survey && !$is_request && !$is_form) ? '<input onClick="submit_report(2)" class="save_button save" type="button" value="Save For Later" />' : "");
                    $str .= '<input onClick="complete_report(2)" class="save_button complete" type="button" value="' . ($job_application_id || $job_application2_id || $request_id || $is_induction || $is_survey || $is_request || $is_form ? 'Save and Review >>' : 'Save and Complete') . '"/>';
                }
                $x = 0;
                $prefix = 0;
                $postfix = 0;
                $js_arr = "var groups = [0";
                $hidden_started = 0;

                $preitems['[fullname]'] = $assessor_fullname;
                $preitems['[phone]'] = $assessor_phone;
                $preitems['[address]'] = $assessor_address;
                $preitems['[today]'] = date("d-M-Y");

                $sql = "select id, question_title, question_type, answer, choices_per_row, parent from compliance_questions
                where compliance_id = (select compliance_id from compliance_checks where id = $report_edit_id)
                order by sort_order";

                if ($result = $this->dbi->query($sql)) {
                    $txt_count = 0;
                    while ($myrow = $result->fetch_assoc()) {
                        $x++;
                        $qid = $myrow['id'];
                        $question_title = nl2br($myrow['question_title']);
                        $question_type = $myrow['question_type'];
                        $question_answer = nl2br($myrow['answer']);
                        $parent = $myrow['parent'];
                        if ($old_parent && $old_parent != $parent) {
                            $str .= "</div>";
                            $hidden_started = 0;
                        }
                        if ($parent && $old_parent != $parent) {
                            $hidden_started = 1;
                            $str .= '<div style="display: none; border: 3px solid !important;" name="child' . $parent . '" id="child' . $parent . '"><div id="child_header' . $parent . '" style="border-bottom: 3px solid; background-color: #FFFFEE; padding: 5px;"></div>';
                        }
                        $choices_per_row = $myrow['choices_per_row'];
                        if (!$choices_per_row) {
                            $choices_per_row = 2;
                        }
                        $text_height = ($choices_per_row * $height_multiplier) . "px";
                        if ($question_type == "lbl" || $question_type == "ins") {
                            if ($question_type == "lbl") {
                                if ($x > 1)
                                    $prefix++;
                                $postfix = 0;
                                $str .= "<input type=\"hidden\" name=\"lbl_$qid\" value=\"LBL\" /><div class=\"q_lbl\">$prefix.0 $question_title</div>";
                            } else {
                                $str .= "<div class=\"instructions\">$question_title</div>";
                            }
                        } else {
                            $postfix++;

                            if(($question_type == "opt" || $question_type == "chl") && $isExistInductionReport && in_array($qid, $correctQuestionArray)) {
                                $questionCorrect = "&#9989";
                                $questionCorrectClass = 'correctAnswer';
                                //questionHide
                                $hideQuestion = ''; //questionHide';
                            } else if($isExistInductionReport && ($question_type == "opt" || $question_type == "chl")) {
                                $questionCorrect = "&#10060";
                                $questionCorrectClass = ' wrongAnswer';
                                $hideQuestion = '';
                            }
                            else{
                                $questionCorrect = "";
                                $questionCorrectClass = '';
                                $hideQuestion = '';
                            }
                            $str .= '<div class="question_area ' . $hideQuestion . '">';
                            $str .= "<div class=\"compliance_question " . $questionCorrectClass . " \">$prefix.$postfix. $question_title.$questionCorrect</div>";
                            if ($question_answer && substr($question_answer, 0, 1) != '[') {
                                if (!$show_answers)
                                    $str .= '<div onClick="show_hide_answer(' . $qid . ');" class="compliance_answer_button" id="compliance_answer_button' . $qid . '"></div>';
                                $str .= '<div class="cl"></div>
                  <div ' . ($show_answers ? "" : 'style="display: none;"') . ' class="compliance_auditor_answer" id="auditor_answer' . $qid . '">' . $question_answer . '</div>';
                            }
                            $str .= '<div class="cl"></div>';
                        }
                        if ($question_type == "txt" || $question_type == "cal" || $question_type == "tim" || $question_type == "clm" || $question_type == "tad") {
                            if ($question_type == "txt") {
                                $txt_count++;
                            }
                            $sql = "select answer from compliance_check_answers where compliance_check_id = $report_edit_id and question_id = $qid;";
                            $result3 = $this->dbi->query($sql);
                            if ($myrow2 = $result3->fetch_assoc()) {
                                $tmp_answer = $myrow2['answer'];
                            } else {
                                $tmp_answer = ($x == 1 && $prefill ? $prefill : "");
                            }
                            if (substr($question_answer, 0, 1) == '[') {
                                //$tmp_answer = $preitems[substr($question_answer, 1, strlen($question_answer) - 2)];
                                $tmp_answer = $preitems[$question_answer];
                            }


                            if ($question_type == "txt") {
                                $str .= ($choices_per_row > 1 ? "<textarea style=\"height: $text_height;\" name=\"txt_$qid\" class=\"compliance_text_box\">$tmp_answer</textarea>" : "<input type=\"text\" style=\"height: $text_height;\" name=\"txt_$qid\" class=\"compliance_text_box\" value=\"$tmp_answer\" />");
                            } else if ($question_type == "tim") {
                                $str .= $txt_itm->ti2("txt_$qid", "", ' class="compliance_text_box" value="' . $tmp_answer . '" ', "", "", "");
                            } else if ($question_type == "cal") {
                                $str .= $txt_itm->cal("txt_$qid", "$tmp_answer", ' class="compliance_text_box" ', "", "", "");
                            } else if ($question_type == "clm") {
                                $str .= $txt_itm->clm("txt_$qid", "$tmp_answer", ' class="compliance_text_box" ', "", "", "");
                            } else if ($question_type == "tad") {
                                if (!$tmp_answer) {
                                    // $tmp_answer = date('d-M-Y H:i');
                                }
                                $str .= $txt_itm->tad("txt_$qid", "$tmp_answer", ' class="compliance_text_box" ', "", "", "");
                            }
                        } else if ($question_type == "opt" || $question_type == "chl") {
                            $y = 0;
                            $z = 0;
                            $done = 0;
                            $sql = "select compliance_question_choices.id as `choice_id`, compliance_question_choices.choice_value, compliance_question_choices.choice, compliance_question_choices.additional_text_required, lookup_fields1.value as `colour`
                      from compliance_question_choices
                      left join lookup_fields1 on lookup_fields1.id = compliance_question_choices.colour_scheme_id
                      where compliance_question_choices.compliance_question_id = $qid
                      order by compliance_question_choices.sort_order
                     ";

                            $from_further = 0;
                            $sql_test_further = "select additional_text_required from compliance_question_choices where compliance_question_id = $qid and additional_text_required REGEXP '^-?[0-9]+$'";
                            if ($result_further = $this->dbi->query($sql_test_further)) {
                                if ($result_further->num_rows)
                                    $from_further = 1;
                            }


                            if ($result2 = $this->dbi->query($sql)) {
                                $num_of = $result2->num_rows;
                                if ($isExistInductionReport && in_array($qid, $correctQuestionArray)) {
                                    $str .= '<div class="questionHide">';
                                }
                                while ($myrow2 = $result2->fetch_assoc()) {
                                    $y++;
                                    $choice = $myrow2['choice'];
                                    $choice_id = $myrow2['choice_id'];
                                    $choice_value = $myrow2['choice_value'];
                                    if (!$choice_value)
                                        $choice_value = 0;
                                    $additional_text_required = $myrow2['additional_text_required'];
                                    if (is_numeric($additional_text_required)) {
                                        if (!$done)
                                            $group++;
                                        $js_arr .= ", $group";
                                        $done = 1;
                                    }
                                    $colour = $myrow2['colour'];
                                    if ($question_type == "opt") {
                                        $str .= '<input class="opt" type="radio" id="opt_' . $qid . '_' . $y . '" name="opt_' . $qid . '" value="' . $qid . ';*;' . $additional_text_required . ';*;' . $question_title . ';*;' . $choice . ';*;' . $choice_value . ';*;' . $choice_id . ';*;' . $colour . '" />';
                                        $update_txt = ($additional_text_required ? "update_txt(" . $qid . "," . $y . ",ys" . $qid . ");" : "");
                                    } else {
                                        $str .= '<input class="opt" type="checkbox" id="chk_' . $qid . '_' . $y . '" name="chk_' . $qid . '_' . $y . '" value="' . $qid . ';*;' . $question_title . ';*;' . $choice . ';*;' . $choice_value . ';*;' . $choice_id . ';*;' . $colour . '" />';
                                        $update_txt = "";
                                    }
                                    $str .= "\n\n<div id=\"div$qid$y\" onClick=\"$update_txt toggle_$question_type('$qid',$y,$num_of,'$colour','$additional_text_required','$choice'" . ($question_type == 'opt' && $from_further ? ", 0, 1" : "") . ");\" class=\"cb c$choices_per_row\">$choice</div>";
                                    $sql = "select answer, additional_text from compliance_check_answers where compliance_check_id = $report_edit_id and question_id = $qid and answer_id = $choice_id;";
                                    if ($result3 = $this->dbi->query($sql)) {
                                        if ($myrow3 = $result3->fetch_assoc()) {
                                            $js_str .= "\ntoggle_$question_type('$qid', $y, $num_of, '$colour', '$additional_text_required', '$choice', 1);";
                                            $text_additional = ($myrow3['additional_text'] ? $myrow3['additional_text'] : "");
                                        } else {
                                            $text_additional = "";
                                        }
                                    }
                                    if ($additional_text_required) {
                                        if (strpos($additional_text_required, "::") !== false) {
                                            $components = explode("::", $additional_text_required);
                                            $additional_text_required = $components[0];
                                            if (is_numeric($components[1])) {
                                                $additional_height = $components[1] * 18;
                                                $itm_type = "txa";
                                            } else {
                                                $itm_type = $components[1];
                                                $additional_height = 25;
                                            }
                                        } else {
                                            $itm_type = "txa";
                                            $additional_height = 100;
                                        }
                                        $z++;
                                        if ($z == 1) {
                                            $js_text .= "\nys$qid = [];";
                                        }
                                        $js_text .= "\nys$qid" . "[" . ($z - 1) . "]=$y;";
                                        $add_text .= "<div class=\"additional_box\" id=\"cont$qid$y\"><div class=\"cl\"></div><span class=\"compliance_question\">$additional_text_required</span><br />";
                                        $add_text .= $itm->$itm_type("txt_$qid" . "_$y", $text_additional, '  style="height: ' . $additional_height . 'px;" class="compliance_text_box" ', "", "", "", "") . "</div>";
                                    }
                                }
                                if ($isExistInductionReport && in_array($qid, $correctQuestionArray)) {
                                    $str .= '</div>';
                                }
                            }
                            if ($add_text) {
                                $str .= $add_text;
                                $add_text = "";
                            }
                        } else if ($question_type == "sig") {

                            $sig = new draw();
                            $sig->width = 800;
                            $sig->height = 240;
                            $sig->target_dir = "images/compliance/$report_edit_id";
                            $target_dir = $this->f3->get('download_folder') . "images/compliance/$report_edit_id";
                            $sig->target_dir2 = $target_dir;

                            if (!file_exists($target_dir)) {
                                mkdir($target_dir, 0777, true);
                                // $myfile = fopen($target_dir."/sig.png", "w");
                                // mkdir($target_dir."sig.png",0755,true);
                                //chmod($target_dir, 0755);
                            }

                            $sig->file_name = "sig.png";

                            $str .= $sig->display_canvas();
//              echo $str;
//              die;
                            $str .= "<input type=\"hidden\" value=\"AS1gna7ure1ma6e\" name=\"sig_$qid\" />";
                        }
                        $str .= "<div class=\"cl\"></div>";
                        $target_dir = $this->f3->get('download_folder') . "images/compliance/$report_edit_id";
                        if ($question_type != "lbl" && $question_type != "ins") {
                            if ($job_application_id || $is_survey || $is_induction) {
                                $str .= "<br />";
                            } else {
                                $str .= '<div onClick="show_hide_img(' . $qid . ',\'' . $target_dir . '\');" class="img_btn" id="img_btn' . $qid . '"></div><div class="cl"></div><div class="img_uploader" id="img_uploader' . $qid . '">
            <iframe id="photo_upload' . $qid . '" frameborder="0" width="100%" height="500px;"></iframe>
            </div>';
                                if (count(glob("$target_dir/$qid/*"))) {
                                    $str .= '<script>show_hide_img(' . $qid . ',\'' . $target_dir . '\');</script>';
                                }
                            }
                            $str .= '<div class="cl"></div>';
                            $str .= "</div>";
                        }
                        $old_parent = $parent;
                    }
                }



                if ($hidden_started)
                    $str .= "</div>";
                $js_arr .= "];\n";
                $str .= '
              <script>
                ' . $js_arr . '
                ' . $js_text . '
                function update_txt(qid,y,ys) {
                  if(ys.length > 1) {
                    var change_to = ""
                    for(x = 0; x < ys.length; x++) {
                      tst = x + 1
                      if(document.getElementsByName("txt_"+qid+"_"+tst)[0].value) {
                        change_to = document.getElementsByName("txt_"+qid+"_"+tst)[0].value;
                      }
                      if(tst != y) {
                        document.getElementsByName("txt_"+qid+"_"+tst)[0].value = "";
                      }
                    }
                    for(x = 0; x < ys.length; x++) {
                      tst = x + 1
                      if(tst == y) {
                        document.getElementsByName("txt_"+qid+"_"+tst)[0].value = change_to;
                      } else {
                        document.getElementsByName("txt_"+qid+"_"+tst)[0].value = "";
                      }
                    }
                  }
                }
              </script>
        ';
                $str .= ((!$job_application_id && !$job_application2_id && !$is_prestart && !$request_id && !$is_survey && !$is_induction && !$is_request && !$is_form) ? '<input onClick="submit_report(2)" class="save_button save" type="button" value="Save For Later" />' : "");
                $str .= '<input onClick="complete_report(2)" class="save_button complete" type="button" value="' . ($job_application_id || $job_application2_id || $request_id ? 'Save and Review >>' : ($is_induction ? 'Complete Induction' : ($is_request || $is_form ? 'Save Form' : 'Save and Complete'))) . '"/>';
                $target_dir = "uploads/compliance/$report_edit_id";
            } else if ($report_view_id) {


                $compliance_obj = new compliance;

                if ($is_induction) {
                    $induction_obj = new UserController();
                    $compliance_obj->title = " ";
                    // $str .= $induction_obj->induction(1);
                    //$str .= "<h3>Induction Results</h3>";
                }

                if ($job_application_id || $job_application2_id || $request_id || $form_id)
                    $compliance_obj->title = "Please review your answers below and click \"Complete Application\" when finished...";



                $compliance_obj->dbi = $this->dbi;

                $compliance_obj->title = ($form_message ? ($is_survey ? "Survey Results" : ($is_induction ? "Induction Results" : ($is_request || $is_form ? "Form Completed..." : ""))) : "");
                $compliance_obj->compliance_check_id = $report_view_id;
                $compliance_obj->display_results();
                $str .= $compliance_obj->str;

                if (($is_request || $is_form || $is_survey || $is_prestart) && $form_message) {

                    $str .= '<div class="inline_message">';
                    if ($is_request) {
                        $time_in15 = date("g:ia", strtotime(date("Y-m-d H:i:s") . " +15 minutes"));
                        $str .= 'The request will be sent at ' . $time_in15 . ' (15 minutes after the request was saved).';
                    } else if ($is_form) {
                        $str .= "The form has been submitted...";
                    } else if ($is_prestart) {
                        $str .= "The Pre Start Form has been submitted...";
                    } else {
                        $str .= "The survey has been submitted...";
                    }

                    $str .= '<br /><br />Are any changes needed to this form? <br /><br />
          <a class="list_a" href="' . $this->f3->get('main_folder') . 'Compliance?report_edit_id=' . $report_view_id . '">Click Here to Make Changes</a> 
          <a class="list_a" target=\"_blank\" href="' . $this->f3->get('main_folder') . 'CompliancePDF/' . $report_view_id . '">View PDF</a><br/><br/>';

//                   if($is_prestart){
//            
//               $str .= '<a class="list_a" href="'.$this->f3->get('main_folder').'Clock2">&lt;&lt; Back to Signup On/Off </a></div>';
//          }else{
//              $str .= '<a class="list_a" href="'.$this->f3->get('main_folder').'">&lt;&lt; Back to Dashboard</a></div>';
//          }
                    //curr_date_time
                }

                if ($is_prestart) {
                    echo $this->redirect($this->f3->get('main_folder') . 'Clock2');
                    // $str .= '<a class="list_a" href="'.$this->f3->get('main_folder').'Clock2">&lt;&lt; Back to Signup On/Off </a></div>';
                }


                if ($job_application_id || $job_application2_id || $request_id || $form_id) {
                    $str .= "<p><a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_edit_id=$report_view_id&" . ($job_application_id ? "job_application_id" : ($request_id ? "request_id" : "job_application2_id")) . "=" .
                            ($job_application_id ? $job_application_id : ($request_id ? $request_id : $job_application2_id)) . "\"><< Go Back to Answering Questions</a>      " .
                            ($job_application_id || $request_id ? (!$complete_application ? "<a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_view_id=$report_view_id&job_application_id=$job_application_id&request_id=$request_id&complete_application=1\">Complete Application >></a></p>" : "") : "<a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "JobApplications?more_info=$job_application2_id\">View Outcome >></a></p>");
                }
            } else if ($survey_mode) {
                $view_details->title = "Surveys";
                $view_details->show_num_records = 0;
                $view_details->sql = "
              select compliance.id as idin, compliance.title as `Title`, closing_date as `Closing Date`,
              if(compliance.id in (select compliance_id from compliance_checks where subject_id = " . $_SESSION['user_id'] . "),
              CONCAT('<a target=\"_top\" class=\"list_a\" uk-tooltip=\"title: Continue Answering the Survey Questions\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_edit_id=', compliance_checks.id, '\">Continue Survey...</a>'),
              CONCAT('<a target=\"_top\" class=\"list_a\" uk-tooltip=\"title: Start Answering the Survey\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_id=', compliance.id, '&site_id=" . $_SESSION['user_id'] . "\">Start Survey...</a>')
              ) as `***`
              FROM compliance
              left join compliance_checks on compliance_checks.compliance_id = compliance.id 
              where
              if(compliance.id in (select compliance_id from compliance_checks where subject_id = " . $_SESSION['user_id'] . "),
              subject_id = " . $_SESSION['user_id'] . ", '1 = 1')
              and
              compliance.category_id in (select id from lookup_fields where item_name = 'Survey') and closing_date >= now()
              group by compliance_checks.compliance_id
              order by title
          ";
            } else if ($survey_results) {
                $survey_summary = (isset($_GET['survey_summary']) ? $_GET['survey_summary'] : null);
                $view_details->title = "Survey Results";
                $view_details->show_num_records = 0;
                if ($survey_results == 1) {
                    $sql = "select id FROM compliance where category_id in (select id from lookup_fields where item_name = 'Survey')";
                    $result = $this->dbi->query($sql);
                    if (!$result->num_rows)
                        $str .= "<h3>There are currently no surveys.</h3>";

                    $view_details->sql = "
                select compliance.id as idin, compliance.title as `Title`, closing_date as `Closing Date`,
                CONCAT('<a target=\"_top\" class=\"list_a\" uk-tooltip=\"title: Show the general results of this survey\" href=\"" . $this->f3->get('main_folder') . "Compliance?survey_results=', compliance.id, '\">Overall Results</a>',
                '<a target=\"_top\" class=\"list_a\" uk-tooltip=\"title: Show the results of this survey by individual question\" href=\"" . $this->f3->get('main_folder') . "Compliance?survey_summary=1&survey_results=', compliance.id, '\">Results By Question</a>
                <a target=\"_top\" class=\"list_a\" uk-tooltip=\"title: Show the results of this survey by individual question\" href=\"" . $this->f3->get('main_folder') . "Compliance?survey_comments=1&survey_results=', compliance.id, '\">Answers to Questions</a>')
                as `***`
                FROM compliance
                left join compliance_checks on compliance_checks.compliance_id = compliance.id 
                where
                compliance.category_id in (select id from lookup_fields where item_name = 'Survey')
                group by compliance.id
                order by title
            ";
                } else {
                    if ($survey_summary) {
                        $view_details->sql = "
              select compliance_check_answers.question as `Question`, CONCAT(round((sum(compliance_check_answers.value)/sum(compliance_check_answers.out_of)) * 100), '%') as `Average Score`
                    from compliance_check_answers
                    left join compliance_checks on compliance_checks.id = compliance_check_answers.compliance_check_id
                    left join compliance on compliance.id = compliance_checks.compliance_id
                    left join compliance_questions on compliance_questions.id = compliance_check_answers.question_id
                    where compliance.id = $survey_results
                    and compliance_checks.status_id != 525
                    and compliance_questions.question_type != 'lbl' and compliance_questions.question_type != 'txt'
                    group by compliance_check_answers.question
            ";
                    } else if ($survey_comments) {
                        $view_details->sql = "
              select compliance_check_answers.question as `Question`, REPLACE(compliance_check_answers.additional_text, '\n', '<br />') as `Comment`, REPLACE(compliance_check_answers.answer, '\n', '<br />') as `Answer`
                    from compliance_check_answers
                    left join compliance_checks on compliance_checks.id = compliance_check_answers.compliance_check_id
                    left join compliance on compliance.id = compliance_checks.compliance_id
                    left join compliance_questions on compliance_questions.id = compliance_check_answers.question_id
                    where compliance.id = $survey_results
                    and compliance_checks.status_id != 525
                    and compliance_questions.question_type != 'lbl'
                    and (compliance_check_answers.answer not in (select distinct(choice) from compliance_question_choices where compliance_question_id in (select id from compliance_questions where compliance_id = $survey_results))
                    or compliance_check_answers.additional_text != '')
            ";
                    } else {

                        $sql = "
              select compliance_checks.id, compliance.title, CONCAT(users.name, ' ', users.surname) as `subject`, states.item_name as `state_name`,
                    users.id as `subject_id`, compliance_checks.total_out_of, compliance_checks.check_date_time, compliance_checks.percent_complete
                    from compliance_checks
                    left join users on users.id = compliance_checks.subject_id
                    left join compliance on compliance.id = compliance_checks.compliance_id
                    left join states on states.id = users.state
                    where compliance.id = $survey_results
                    and compliance_checks.status_id != 525
                    order by compliance_checks.check_date_time DESC
            ";
                        if ($result = $this->dbi->query($sql)) {
                            while ($myrow = $result->fetch_assoc()) {
                                $scount++;
                                $ccid = $myrow['id'];
                                $subject = $myrow['subject'];
                                $subject_id = $myrow['subject_id'];
                                $assessor = $myrow['assessor'];
                                $title = $myrow['title'];
                                $state_name = $myrow['state_name'];
                                $check_date = Date("d-M-Y", strtotime($myrow['check_date_time']));
                                $out_of = $myrow['total_out_of'];
                                $percent_complete = $myrow['percent_complete'];
                                $sql = "select (sum(value)/$out_of)*100 as `score` from compliance_check_answers where compliance_check_id = $ccid";
                                if ($result2 = $this->dbi->query($sql)) {
                                    if ($myrow2 = $result2->fetch_assoc()) {
                                        $score = round($myrow2['score']) . "%";
                                    }
                                    $total_score += $score;
                                    if ($percent_complete > 10)
                                        $ccount++;
                                    if ($scount == 1) {
                                        $str .= "<h3>Results for $title</h3>";
                                        $str .= "<table class=\"grid\"><tr><th>Date</th><th>% Done</th><th>By</th><th>State</th><th>Score</th><th>View</th></tr>";
                                    }
                                    $str .= "<tr><td>$check_date</td><td>$percent_complete</td>
                  <td>$subject</td><td>$state_name</td><td>$score</td><td><a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_view_id=$ccid\">View</a></td></tr>";
                                }
                                $avg = round($total_score / $ccount);
                                $str .= "<tr><th colspan=\"4\" style=\"text-align: right !important;\">Average Score: </th><th colspan=\"2\">$avg%</td></tr>";
                                $str .= "</table>";
                            }
                        } else {
                            //$str .= "<h3>There are currently no survey results.</h3>";
                        }
                    }
                }
            } else {

                $search_str = (isset($_REQUEST['compliance_search']) ? $_REQUEST['compliance_search'] : null);
                $category_id = (isset($_REQUEST['category_id']) ? $_REQUEST['category_id'] : null);

                if ($_SESSION['u_level'] >= 300) {

                    /* For  get category  */
                    $sql = "select lookup_fields.id as idin, lookup_fields.item_name, lookup_fields.value from lookup_fields where lookup_fields.item_name NOT LIKE '%request%' and lookup_fields.item_name NOT LIKE '%survey%'  and lookup_fields.item_name NOT LIKE '%induction%' and lookup_id = 100 and id in (select category_id from compliance) order by sort_order, lookup_fields.item_name";
                    $str .= '<style>
    .ASM { color: #009DE0; }
    .AFM { color: #64B446; }
    .ACM { color: #E9C229; }
    .ATM { color: #F18D2B; }
    .APM { color: #DF3330; }
    .division{ color:#00008B; }

    .ASM_selected { background-color: #009DE0; color: white; }
    .AFM_selected { background-color: #64B446; color: white; }
    .ACM_selected { background-color: #E9C229; color: white; }
    .ATM_selected { background-color: #F18D2B; color: white; }
    .APM_selected { background-color: #DF3330; color: white; }
     .division_selected{ background-color: #00008B; color: white;}
     

.ASM_selected:hover { background-color: #009DE0; color: white; }
    .AFM_selected:hover { background-color: #64B446; color: white; }
    .ACM_selected:hover { background-color: #E9C229; color: white; }
    .ATM_selected:hover { background-color: #F18D2B; color: white; }
    .APM_selected:hover { background-color: #DF3330; color: white; }
     .division_selected{ background-color: #00008B; color: white;}


      .division_selected:hover{ background-color: #00008B; color: white;}

    .ASM:hover { background-color: #009DE0; color: white; }
    .AFM:hover { background-color: #64B446; color: white; }
    .ACM:hover { background-color: #E9C229; color: white; }
    .ATM:hover { background-color: #F18D2B; color: white; }
    .APM:hover { background-color: #DF3330; color: white; }
    .division:hover{ background-color: #00008B; color: white;}
     

    .divisionbroder{
     -webkit-box-sizing: content-box;
-moz-box-sizing: content-box;
box-sizing: content-box;
display: inline-block;
text-align: center;

width: auto;
border-radius: 8px 8px 8px 8px;
-moz-border-radius: 8px 8px 8px 8px;
-webkit-border-radius: 8px 8px 8px 8px;
margin: 0px;

white-space: nowrap;
font-weight: normal !important;
font-size: 11pt !important;
border: 1px solid #DDDDDD;
    border-top-color: rgb(221, 221, 221);
    border-right-color: rgb(221, 221, 221);
    border-bottom-color: rgb(221, 221, 221);
    border-left-color: rgb(221, 221, 221);
    }
    
.ASM, .AFM, .APM, .ACM, .ATM, .division {
	border-radius: 30px;
	margin-right: 5px;
	font-size: 11px;
	font-weight: bold;
	padding: 6px 14px !important;
	border: 1px solid #ccc;
}     
    </style>';

                    $str .= '<a class="divisionbroder division" ' . ($category_id ? '' : 'style="color: white !important; background-color: #242E48 !important;"') . ' href="Compliance">All Categories</a> ';
                    $loginUserId = $_SESSION['user_id'];
                    $loginUserRole = $this->userRoleNameByUid($loginUserId);
                    if ($result = $this->dbi->query($sql)) {

                        $loginUserDivisions = $this->get_divisions($loginUserId, 0, 1);
                        $loginUserDivisionsArray = explode(',', $loginUserDivisions);
                        $loginUserDivisionsArray = array_map('trim', $loginUserDivisionsArray);
                        $loginUserDivisionsStr = implode(',', $loginUserDivisionsArray);
                        while ($myrow = $result->fetch_assoc()) {
                            $item_name = $myrow['item_name'];
                            $class = $this->divisionClass($item_name);
                            $cat_id = $myrow['idin'];

                            $checkCategoryIdArray = array(2101, 2007, 2008, 2009, 2010);
                            $allowedComplianceCategory = 1;

                            if (in_array($cat_id, $checkCategoryIdArray)) {
                                $allowedComplianceCategory = $this->allowedComplianceCategory($cat_id, $loginUserDivisionsArray);
                            } else if ($loginUserRole == 'EMPLOYEE') {

                                $allowedComplianceCategory = 0;
                            }

                            if ($allowedComplianceCategory) {
                                if ($cat_id == $category_id) {
                                    $style_xtra = "style=\"color: white !important; background-color: #242E48 !important;\"";
                                    $class = $class . " " . $class . "_selected";
                                    $heading = $myrow['item_name'];
                                } else {
                                    $style_xtra = "";
                                    $class = $class;
                                }

                                $value = $myrow['value'];
                                $str .= '<a class="divisionbroder ' . $class . '"  href="?category_id=' . $cat_id . '">' . $item_name . '</a> ';
                            }
                        }
                    }


                    if ($category_id || $search_str) {

                        if ($search_str) {
                            $filter_xtra = " and (compliance.title LIKE '%$search_str%' || compliance.description LIKE '%$search_str%') ";
                            $search_msg = "Filtered By: $search_str";
                        } else {
                            //compliance.title LIKE '%kpi%' || 
                            $filter_xtra = " and NOT(compliance.title LIKE '%performance appraisal%' || compliance.title LIKE '%request%' || compliance.title LIKE '%job app%') ";
                        }
                        if ($category_id) {
                            $filter_xtra .= " and compliance.category_id = $category_id";
                        }
                    }



                    $str .= '
          </form>
          <hr />
          <style>
            .selected_search {
              background-color: #FFFFEE;
            }
          </style>
          <form method="get" name="frmFollowSearch" id="frmFollowSearch" class="uk-search">
          <input type="hidden" id="category_id" name="category_id" value="' . $_REQUEST['category_id'] . '">
            
              
          <p class="input-group custom-search-form" style="padding-right: 8px; margin-bottom:0;width: 270px;">
          <input placeholder="Search for reports..." autocomplete="off" value="' . $_REQUEST['compliance_search'] . '" maxlength="70" name="compliance_search" id="compliance_search" type="text" class="form-control off-search"  style="padding: 2px !important; height: 32px !important; width: 216px;
border-radius: 0;"  /><button onClick="this.form.submit()" name="cmdFollowSearch" value="Search" class="button_a" style="height: 38px;" /><span class="fa fa-search"></span></button></p>
          
          <ul id="searchComplianceResult" class="autosearch" style="padding-left:0px; margin-top:0px"></ul>

          <!-- <span data-uk-search-icon class="search-icon"></span><input class="uk-search-input search-field" type="text" maxlength="70" placeholder="Search for reports..."  name="compliance_search" id="search" value="' . $search_str . '"> -->
                  
          ';

                    //$str .= "  <b>Quick Search: </b>";
                    $sql = "select id, item_name, value from lookup_fields where lookup_id in (select id from lookups where item_name = 'compliance_keywords') order by sort_order, item_name";
                    if ($result = $this->dbi->query($sql)) {
                        while ($myrow = $result->fetch_assoc()) {
                            $keyword = $myrow['item_name'];
                            $value = $myrow['value'];
                            //$str .= '<a class="divisionbroder '.($keyword == $_GET['compliance_search'] ? 'selected_search' : '').'" href="?compliance_search=' . $keyword . '">'.$value.'</a>';
                        }
                    }
                } else {
                    $str .= '<h3>Reporting</h3>';
                }

                /* $str .= '
                  <div class="fr"><a class="download_link" href="'.$this->f3->get('main_folder').'downloadFile?fl=' . urlencode($this->encrypt("resources/Guides")) . '&f=' . urlencode($this->encrypt(($search_str == "kpi" ? "KPI" : "Compliance System" . ".pdf"))) . '">Download User Guide</a></div>';


                  if($search_str != "kpi") {
                  $str .= '<div class="fr"><a class="download_link" href="'.$this->f3->get('main_folder').'downloadFile?fl=1FwKortBoY8ocRpVL9brjwvEG0p5pdtIJZ9Uf27ppZF1ahXTogvnd5A3wHLuvHnF%2BFeeaRZucaJhvkfqXlxde9&f=9Ndny5XKieFLoKeoyW9iUAE1pppVI7vSdNdZ7GDeAktLgbCzSfoaxDwxKGFA4nXNdKlT9dnIaHAOXGMde5VHqZ5Tb8NWqa%2F1QG%2Bfn1LiOur0ne1DjOTKLoo%2BW6iai5TRk%3D">Download Meeting Minutes Form</a></div>';
                  } */
                $str .= '<br /><br /></form><div class="cl"></div>';

                if ($search_str == "kpi") {
                    $view_details->title = "KPI";
                    $str .= "<b>Please Note:</b> The current month is " . date('F') . "; so KPI scores added now are for last month (" . date('F', strtotime("-1 month")) . ").<br /><br />";
                } else {
                    $view_details->title = $heading;
                }
                $is_admin = $this->f3->get('is_admin');

                $mgr_xtra = ($is_manager ? "or compliance.id in (select foreign_id from lookup_answers where table_assoc = 'compliance')" : "");
                if ($loginUserRole == 'EMPLOYEE') {

                    $allowedcat = [2101, 2007, 2008, 2009, 2010];
                } else {
                    $allowedcat = [2101, 2007, 2008, 2009, 2010, 2420, 2011, 2147, 2221];
                }

                $view_details->sql = "
              select compliance.id as idin,
              CONCAT('<a class=\"list_a\" uk-tooltip=\"title: Show Reports\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_id=', compliance.id, '\">Reports</a>') as `Reports`,
              CONCAT('<a class=\"list_a\" uk-tooltip=\"title: Start a New " . ($search_str == "kpi" ? "KPI" : "Report") . "\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_id=', compliance.id, '&new_report=1\">New " . ($search_str == "kpi" ? "KPI" : "Report") . "</a>') as `New`,
              CONCAT('<span style=\"color: ', IF(compliance.title = 'Vicinity KPI', 'blue', 'black'), '\">', compliance.title, '</span>') as `Title`
              
              " . ($this->f3->get('is_mobile') || $this->f3->get('is_tablet') || !$is_admin ? "" : "
              , lookup_fields2.item_name as `Category`,
              
              
              CONCAT('<a class=\"list_a\" uk-tooltip=\"title: Download the Template file in Excel here.\" style=\"width: 80px;\" href=\"" . $this->f3->get('main_folder') . "Compliance?template_id=', compliance.id, '&template_name=', compliance.title, '\">Template</a><a style=\"width: 80px;\" class=\"list_a\" uk-tooltip=\"title: View Summary\" href=\"" . $this->f3->get('main_folder') . "ComplianceResults?report_id=', compliance.id, '\">Summary</a><a style=\"width: 90px;\" class=\"list_a\" uk-tooltip=\"title: View Summary\" href=\"" . $this->f3->get('main_folder') . "Reporting/Results?cid=', compliance.id, '\">Results Table</a>') as `More`") . "
              FROM compliance
              left join lookup_fields2 on lookup_fields2.id = compliance.category_id
              where compliance.category_id in (" . implode(",", $allowedcat) . ") and (compliance.division_id = '' or compliance.division_id in (" . $loginUserDivisionsStr . ")) and 
              compliance.min_user_level_id <= " . $_SESSION['u_level'] . " and compliance.is_active = 1 and 
              ((compliance.id in (select compliance_id from compliance_auditors where user_id = " . $_SESSION['user_id'] . ")
              $mgr_xtra) or (compliance.allow_all_access = 1 and compliance.id in (select foreign_id from lookup_answers where table_assoc = 'compliance')))

              $filter_xtra
              
              order by title
        ";
            }
            if ($is_induction) {
                $induction_obj = new UserController();
                $induction_obj->title = " ";
                $str .= $induction_obj->induction(1);
                $str .= "<h3>Induction Results</h3>";
            }
            // return $str;
//      echo $filter_xtra;
//      die;
            $compliance_search = $_REQUEST['compliance_search'];
            $ajaxSrch = $_REQUEST['ajaxsrch'];
            if ($compliance_search && $ajaxSrch) {

                $sql = $view_details->sql;
                $result = $this->dbi->query($sql);
                $rkey = 0;
                $searchResult = array();
                while ($myrow = $result->fetch_assoc()) {
                    //prd($myrow);            
                    $searchResult[$rkey]['title'] = $myrow['Title'];
                    $rkey++;
                }
                $jsonSearchResult = json_encode($searchResult);
                echo $jsonSearchResult;
                die;
                //$result = $this->homeSearchResult();
            }


//      echo $view_details->sql;
//      die;
//                    //$str .= $this->ta($view_details->sql);
            $str .= $reloadLocation;
            $str .= ($view_details->sql ? $view_details->draw_list() : "");
            if ($survey_results != 1 && $survey_results) {
                $str .= "<a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Compliance?survey_results=1\"><< Back to Survey Results</a>";
                if ($survey_summary || $survey_comments)
                    $str .= "<a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Compliance?survey_results=$survey_results\">Overall Scores</a>";
                if (!$survey_summary)
                    $str .= "<a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Compliance?survey_results=$survey_results&survey_summary=1\">View By Question</a>";
                if (!$survey_comments)
                    $str .= "<a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Compliance?survey_results=$survey_results&survey_comments=1\">Answers to Question</a>";
            }
            if ($js_str)
                $str .= "<script type=\"text/javascript\">\n$js_str\n</script>";
        }
        return $str;
    }

    function Show_bkp_18_11_2021() {


        $is_client = ($this->f3->get('is_client') || $this->f3->get('is_site') ? 1 : 0);
        $company_ids = $_SESSION['company_ids'];
        $job_application_id = (isset($_GET['job_application_id']) ? $_GET['job_application_id'] : null);
        $job_application2_id = (isset($_GET['job_application2_id']) ? $_GET['job_application2_id'] : null);
        $request_id = (isset($_GET['request_id']) ? $_GET['request_id'] : null);
        $template_id = (isset($_GET['template_id']) ? $_GET['template_id'] : null);
        $template_name = (isset($_GET['template_name']) ? $_GET['template_name'] : null);
        $survey_mode = (isset($_GET['survey_mode']) ? $_GET['survey_mode'] : null);
        $survey_results = (isset($_GET['survey_results']) ? $_GET['survey_results'] : null);
        $survey_comments = (isset($_GET['survey_comments']) ? $_GET['survey_comments'] : null);
        $show_min = (isset($_GET['show_min']) ? $_GET['show_min'] : null);
        $complete_application = (isset($_GET['complete_application']) ? $_GET['complete_application'] : null);
        $prefill = (isset($_GET['prefill']) ? $_GET['prefill'] : null);
        $curr_date = date('Y-m-d');
        $curr_date_time = date('Y-m-d H:i:s');
        $hdnSubmitReport = (isset($_POST['hdnSubmitReport']) ? $_POST['hdnSubmitReport'] : null);
        $form_message = (isset($_GET['form_message']) ? $_GET['form_message'] : null);
        if ($template_id) {

            $sql = "select item_name from compliance_colours;";
            $i = 0;
            if ($result = $this->dbi->query($sql)) {
                while ($myrow = $result->fetch_assoc()) {
                    $colour = $myrow['item_name'];
                    $colour_list .= ($i ? "," : "") . $colour;
                    $i = 1;
                }
            }
            $column_array = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "AA", "AB", "AC", "AD", "AE", "AF", "AG", "AH", "AI", "AJ", "AK", "AL", "AM", "AN", "AO", "AP", "AQ", "AR", "AS", "AT", "AU", "AV", "AW", "AX", "AY", "AZ", "BA", "BB", "BC", "BD", "BE", "BF", "BG", "BH", "BI", "BJ", "BK", "BL", "BM", "BN", "BO", "BP", "BQ", "BR", "BS", "BT", "BU", "BV", "BW", "BX", "BY", "BZ", "CA", "CB", "CC", "CD", "CE", "CF", "CG", "CH", "CI", "CJ", "CK", "CL", "CM", "CN", "CO", "CP", "CQ", "CR", "CS", "CT", "CU", "CV", "CW", "CX", "CY", "CZ");
            $objPHPExcel = new PHPExcel();
            $objPHPExcel->setActiveSheetIndex(0);
            $row = 1;
            $objPHPExcel->getActiveSheet()->getStyle("A$row:BZ$row")->getFont()->setBold(true);
            for ($i = 2; $i <= 500; $i++) {
                $objValidation = $objPHPExcel->getActiveSheet()->getCell("C$i")->getDataValidation();
                $objValidation->setType(PHPExcel_Cell_DataValidation::TYPE_LIST);
                $objValidation->setErrorStyle(PHPExcel_Cell_DataValidation::STYLE_INFORMATION);
                $objValidation->setShowInputMessage(true);
                $objValidation->setShowErrorMessage(true);
                $objValidation->setShowDropDown(true);
                $objValidation->setErrorTitle('Input error');
                $objValidation->setError('Value is not in list.');
                $objValidation->setPromptTitle('Choose Input Type from the Dropdown List');
                $objValidation->setPrompt("\nlbl: Label.\ntxt: Text Box.\nopt: Single Select Options.\nchl: Multiple Select Options.\nclm: Calendar.\ntim: Time Field.\nins: Just show text (e.g. Instructions).\nsig: Get Drawing (e.g. Signature)");
                $objValidation->setFormula1('"lbl,txt,opt,chl,clm,tim,ins,sig"');
                for ($x = 1; $x < 12; $x++) {
                    $objValidation = $objPHPExcel->getActiveSheet()->getCell($column_array[$x * 4 + 3] . "$i")->getDataValidation();
                    $objValidation->setType(PHPExcel_Cell_DataValidation::TYPE_LIST);
                    $objValidation->setErrorStyle(PHPExcel_Cell_DataValidation::STYLE_INFORMATION);
                    $objValidation->setShowInputMessage(true);
                    $objValidation->setShowErrorMessage(true);
                    $objValidation->setShowDropDown(true);
                    $objValidation->setErrorTitle('Input error');
                    $objValidation->setError('Value is not in list.');
                    $objValidation->setPromptTitle('Choose a Colour');
                    $objValidation->setPrompt("\nPlease select a colour from the drop-down list.");
                    $objValidation->setFormula1('"' . $colour_list . '"');
                    $objValidation = $objPHPExcel->getActiveSheet()->getCell($column_array[$x * 4 + 4] . "$i")->getDataValidation();
                    $objValidation->setShowInputMessage(true);
                    $objValidation->setPromptTitle('Comments/Further Questions');
                    $objValidation->setPrompt("\nAdd text here if a comment box is required.\n\nAdd a number here if the question leads to further questions.\n\nComment::txt - Textbox\n::clm - Calendar\n::ti2 - Time\n::5 - Define number of lines");
                }
                $objValidation = $objPHPExcel->getActiveSheet()->getCell("D$i")->getDataValidation();
                $objValidation->setShowInputMessage(true);
                $objValidation->setPromptTitle('Choices/Row or Num Rows of Text');
                $objValidation->setPrompt("\nFor single or multi-select options, choose the number of items shown on each row.\n\nFor text boxes, choose the number of rows of text.");
                $objValidation = $objPHPExcel->getActiveSheet()->getCell("E$i")->getDataValidation();
                $objValidation->setShowInputMessage(true);
                $objValidation->setPromptTitle('Parent Number');
                $objValidation->setPrompt("To hide this item until selected, add a number corresponding to an above comment within a choice.");
            }
            $objPHPExcel->getActiveSheet()->getStyle("A1:BZ1")->applyFromArray(array('borders' => array('bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => '000000')))));
            $objPHPExcel->getActiveSheet()->SetCellValue("A$row", "Question");
            $objPHPExcel->getActiveSheet()->SetCellValue("B$row", "Answer");
            $objPHPExcel->getActiveSheet()->SetCellValue("C$row", "Type");
            $objPHPExcel->getActiveSheet()->SetCellValue("D$row", "Choices/Row");
            $objPHPExcel->getActiveSheet()->SetCellValue("E$row", "Parent");
            $objPHPExcel->getActiveSheet()->getStyle("E1:E500")->applyFromArray(array('borders' => array('right' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => '000000')))));
            for ($x = 1; $x < 12; $x++) {
                $objPHPExcel->getActiveSheet()->SetCellValue($column_array[$x * 4 + 1] . "$row", "Choice");
                $objPHPExcel->getActiveSheet()->SetCellValue($column_array[$x * 4 + 2] . "$row", "Value");
                $objPHPExcel->getActiveSheet()->SetCellValue($column_array[$x * 4 + 3] . "$row", "Colour");
                $objPHPExcel->getActiveSheet()->SetCellValue($column_array[$x * 4 + 4] . "$row", "Comment");
                $objPHPExcel->getActiveSheet()->getStyle($column_array[$x * 4 + 4] . "1:" . $column_array[$x * 4 + 4] . "500")->applyFromArray(array('borders' => array('right' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => '000000')))));
            }
            $sql = "select id, question_type, question_title, answer, choices_per_row, parent from compliance_questions where compliance_id = $template_id order by sort_order;";
            if ($result = $this->dbi->query($sql)) {
                while ($myrow = $result->fetch_assoc()) {
                    $row++;
                    $col = 1;
                    $compliance_question_id = $myrow['id'];
                    $question_title = $myrow['question_title'];
                    $question_type = $myrow['question_type'];
                    $answer = $myrow['answer'];
                    $choices_per_row = $myrow['choices_per_row'];
                    $parent = $myrow['parent'];
                    $objPHPExcel->getActiveSheet()->SetCellValue("A$row", $question_title);
                    if ($answer)
                        $objPHPExcel->getActiveSheet()->SetCellValue("B$row", $answer);
                    $objPHPExcel->getActiveSheet()->SetCellValue("C$row", $question_type);
                    $objPHPExcel->getActiveSheet()->SetCellValue("D$row", $choices_per_row);
                    $objPHPExcel->getActiveSheet()->SetCellValue("E$row", $parent);
                    if ($question_type == 'lbl') {
                        $objPHPExcel->getActiveSheet()->getStyle("A$row:E$row")->getFont()->setBold(true);
                    }
                    if ($question_type == 'opt' || $question_type == 'chl') {
                        $sql = "select compliance_question_choices.choice, compliance_question_choices.choice_value, compliance_question_choices.additional_text_required,
                    lookup_fields.item_name as `colour` from compliance_question_choices
                    left join lookup_fields on lookup_fields.id = compliance_question_choices.colour_scheme_id
                    where compliance_question_choices.compliance_question_id = $compliance_question_id order by compliance_question_choices.sort_order;";
                        if ($result2 = $this->dbi->query($sql)) {
                            while ($myrow2 = $result2->fetch_assoc()) {
                                $choice = $myrow2['choice'];
                                $choice_value = $myrow2['choice_value'];
                                $additional_text_required = $myrow2['additional_text_required'];
                                $colour = $myrow2['colour'];
                                $objPHPExcel->getActiveSheet()->SetCellValue($column_array[$col * 4 + 1] . "$row", $choice);
                                $objPHPExcel->getActiveSheet()->SetCellValue($column_array[$col * 4 + 2] . "$row", $choice_value);
                                $objPHPExcel->getActiveSheet()->SetCellValue($column_array[$col * 4 + 3] . "$row", $colour);
                                $objPHPExcel->getActiveSheet()->SetCellValue($column_array[$col * 4 + 4] . "$row", $additional_text_required);
                                $col++;
                            }
                        }
                    }
                }
            }
            header('Content-type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="' . $template_name . '.xlsx"');
            $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
            $objWriter->save('php://output');
        } else {

            $itm = new input_item;
            $itm->hide_filter = 1;
            $str .= $itm->setup_cal();
            $str .= $itm->setup_ti2();
            if ($complete_application) {

                $mail = new email_q($this->dbi);
                $str .= '<div class="message">' . ($request_id ? "Your request has been submitted to your manager..." : 'Thank you for applying for employment at SCGS.<br /><br />We will get back to you shortly...') . '<br /><br />Please double check the information provided below and review if necessary.' . ($request_id ? '' : '<br /><br /><div style="padding: 10px; border: 2px solid red; display: inline-block; background-color: #FFFFEE;"><u>Please Note</u> that Your Username is:<br /><br />' . $_SESSION['username'] . '<br /><br />(Please keep a record of the above username for future job applications)</div>') . '</div>';
                $headers_top = 'MIME-Version: 1.0' . "\r\n";
                $headers_top .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
                if ($request_id) {

                    $sql = "select compliance.show_all_entities, users.name as `requester_name`, users.surname as `requester_surname`, users.email as `requester_email`,
                  users2.name as `manager_name`, users2.surname as `manager_surname`, users2.email as `manager_email`, compliance.title, application_requests.compliance_check_id
                  from application_requests 
                  left join users on users.id = application_requests.requester_id
                  left join users2 on users2.id = application_requests.manager_id
                  left join compliance on compliance.id = application_requests.compliance_id
                  where application_requests.id = $request_id;";
                    if ($result = $this->dbi->query($sql)) {
                        if ($myrow = $result->fetch_assoc()) {
                            $requester_name = $myrow['requester_name'];
                            $requester_surname = $myrow['requester_surname'];
                            $manager_name = $myrow['manager_name'];
                            $manager_surname = $myrow['manager_surname'];
                            $manager_email = $myrow['manager_email'];
                            $requester_email = $myrow['requester_email'];
                            $manager_surname = $myrow['manager_surname'];
                            $app_title = $myrow['title'];
                            $compliance_obj2 = new compliance;
                            $compliance_obj2->hide_score = 1;
                            $compliance_obj2->dbi = $this->dbi;
                            $compliance_obj2->title = "Request Details";
                            $compliance_obj2->compliance_check_id = $myrow['compliance_check_id'];
                            $compliance_obj2->display_results();
                        }
                    }
                    $subject = $app_title;
                    $msg = "Hello $requester_name,<br /><br />";
                    $msg .= "Your request has been sent to $manager_name $manager_surname.";
                    $msg .= $compliance_obj2->str;
                    $msg .= "<br /><br />Regards,<br />Edge Admin.";
                    $mail->clear_all();
                    $mail->AddReplyTo($manager_email);
                    $mail->AddAddress($requester_email);
                    $mail->Subject = $subject;
                    $mail->Body = $css . "\n\n" . $msg;
                    $mail->queue_message();
                    $msg = "Hello $manager_name,<br /><br />";
                    $msg .= "A request has been made by $requester_name $requester_surname.";
                    $msg .= "<br /><br />Please go to " . $this->f3->get('main_folder') . "Applications?process_mode=1&more_info=$request_id to process the request.";
                    $msg .= $compliance_obj2->str;
                    $msg .= "<br /><br />Regards,<br />Edge Admin.";
                    $mail->clear_all();
                    $mail->AddReplyTo($requester_email);
                    $mail->AddAddress($manager_email);
                    $mail->Subject = $subject;
                    $mail->Body = $css . "\n\n" . $msg;
                    $mail->queue_message();
                } else if ($job_application_id) {
                    $sql = "select users.id as idin, concat(users.name, ' ', users.surname) as `applicant`, users.email, job_ads.title as `job_ad`, users.username,
                  job_applications.date_applied as `date_applied`
                  from job_applications
                  left join users on users.id = job_applications.user_id
                  left join job_application_status on job_application_status.id = job_applications.status_id
                  left join job_ads on job_ads.id = job_applications.job_ad_id
                  left join states on states.id = users.state
                  where job_applications.id = $job_application_id";
                    if ($result = $this->dbi->query($sql)) {
                        if ($myrow = $result->fetch_assoc()) {
                            $applicant = $myrow['applicant'];
                            $job_ad = $myrow['job_ad'];
                            $username = $myrow['username'];
                            $applicant_email = $myrow['email'];
                            $date_applied = Date("d-M-Y", strtotime($myrow['date_applied']));
                            $subject = "Job Application: $job_ad";
                            $compliance_obj2 = new compliance;
                            $compliance_obj2->hide_score = 1;
                            $compliance_obj2->hide_attachments = 1;
                            $compliance_obj2->dbi = $this->dbi;
                            $compliance_obj2->title = $subject;
                            $compliance_obj2->compliance_check_id = $_GET['report_view_id'];
                            $compliance_obj2->display_results();
                        }
                    }
                    $msg = "Hello $applicant,<br /><br />";
                    $msg .= "<p>Your job application for $job_ad has been submitted.</p>";
                    $msg .= "Please <a href=\"" . $this->f3->get('main_folder') . "/Login?username=$username&page_from=MyJobApplications\">login here</a> to review your application and/or make changes.";
                    $msg .= $compliance_obj2->str;
                    $msg .= "<br /><br />Regards,<br />Human Resources Department.";
                    $mail->clear_all();
                    $mail->AddReplyTo($this->f3->get('hr_email'));
                    $mail->AddAddress($applicant_email);
                    $mail->Subject = $subject;
                    $mail->Body = $css . "\n\n" . $msg;
                    $mail->queue_message();
                }
            }

//      $alloedPermission = '2458,2456,2455,2454,'
//      $sql = "select users.id from users inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id in (111, 112, 504, 403, 114, 115, 534, 2039)
//      and lookup_answers.table_assoc = 'users' and users.id = ".$_SESSION['user_id'].";";
//      echo $sql;
//      die;
//      if($result = $this->dbi->query($sql)) $is_manager = ($result->num_rows ? 1 : 0);

            $is_manager = 1;
            $str .= '
      <input type="hidden" name="hdnChangeItem" id="hdnChangeItem" />
      <input type="hidden" name="hdnChangeStatus" id="hdnChangeStatus" />
      <script type="text/javascript">
        function customAlert(msg,duration) {
          var styler = document.createElement("span");
          styler.setAttribute("style","position: absolute !important; width: 100%; top: 0; left: 0px; border: solid 2px green;background-color:#FFFFDD;color:red;text-align: center; padding: 50px; font-size: 16px;");
          styler.innerHTML = "<h1>"+msg+"</h1>";
          setTimeout(function() {
            styler.parentNode.removeChild(styler);
          },duration);
          document.body.appendChild(styler);
        }
        function caller(msg) {
          customAlert(msg,"3500");
        }
        function display_hide(itm) {
          if(document.getElementById(itm).style.display == "block") {
            document.getElementById(itm).style.display = "none";
            document.getElementById("btnShowHide").value = "Show Image Attachments";
          } else {
            document.getElementById(itm).style.display = "block";
            document.getElementById("btnShowHide").value = "Hide Image Attachments";
          }
        }
      </script>
      ';
            $report_edit_id = (isset($_GET['report_edit_id']) ? $_GET['report_edit_id'] : null);
            $report_view_id = (isset($_GET['report_view_id']) ? $_GET['report_view_id'] : null);

            if ($report_edit_id || $report_view_id) {

                if ($report_edit_id) {
                    $str .= '<script>
          function goodbye(e) {
            if(!e) e = window.event;
            e.cancelBubble = true; //e.cancelBubble is supported by IE - this will kill the bubbling process.
            e.returnValue = "Are you sure you want to leave this page?"; //This is displayed on the dialog
            if (e.stopPropagation) { //e.stopPropagation works in Firefox.
              e.stopPropagation();
              e.preventDefault();
            }
          }
          window.onbeforeunload=goodbye;
          
          </script>';
                }

                $vid = ($report_edit_id ? $report_edit_id : $report_view_id);
                $sql = "select compliance_checks.id as `result` from compliance_checks
                left join compliance on compliance.id = compliance_checks.compliance_id
                where compliance_checks.id = $vid and compliance.allow_all_access = 1;";
                $all_access = $this->get_sql_result($sql);

                $compliance_category = "";
                $this->get_sql_result("select lookup_fields.item_name as `result` from compliance_checks
                inner join compliance on compliance.id = compliance_checks.compliance_id
                inner join lookup_fields on lookup_fields.id = compliance.category_id
                where compliance_checks.id = $vid;");

                if ($compliance_category == "Survey") {
                    $is_survey = 1;
                } else if ($compliance_category == "Forms") {
                    $is_form = 1;
                } else if ($compliance_category == "Induction") {
                    $is_induction = 1;
                } else if ($compliance_category == "Request Forms") {
                    $is_request = 1;
                } else {
                    $is_survey = 0;
                    $is_form = 0;
                    $is_request = 0;
                    $is_survey = 0;
                }

                if ($is_manager || $all_access) {
                    $mgr_sql = " union select foreign_id from lookup_answers where table_assoc = 'compliance' and foreign_id in (select compliance_id from compliance_checks where id = $vid);";
                }
//                    $allow_access = $this->num_results("select child_user_id from associations where association_type_id = 1 and child_user_id = $user_id and parent_user_id = {$_SESSION['user_id']} ");


                $sql = "select id from compliance_auditors where compliance_id = (SELECT compliance.id FROM `compliance_checks`
                left join compliance on compliance.id = compliance_checks.compliance_id
                WHERE compliance_checks.id = $vid LIMIT 1) and (user_id = " . $_SESSION['user_id'] . "
                or compliance_checks.subject_id in (select child_user_id from associations where association_type_id = 1 and parent_user_id = " . $_SESSION['user_id'] . "))
                $mgr_sql";
                //return "A: $all_access  $sql";
                if ($report_view_id)
                    $own_access = ($this->f3->get('is_site') || $this->f3->get('is_client') ? ($this->get_sql_result("select subject_id as `result` from `compliance_checks` where id = $vid;") == $_SESSION['user_id'] ? 1 : 0) : 0);


                if ($result = $this->dbi->query($sql)) {
                    if (!$result->num_rows && !$all_access && !$own_access) {
                        $report_edit_id = 0;
                        $report_view_id = 0;
                        exit;
                    }
                }
            }

            $new_report = (isset($_GET['new_report']) ? $_GET['new_report'] : null);
            $report_id = (isset($_GET['report_id']) ? $_GET['report_id'] : null);
            $site_id = (isset($_GET['site_id']) ? $_GET['site_id'] : null);
            if ($new_report) {
                $compliance_category = $this->get_sql_result("select lookup_fields.item_name as `result` from compliance
                inner join lookup_fields on lookup_fields.id = compliance.category_id
                where compliance.id = $report_id;");

                //$str .= "CC: $compliance_category - reid: $report_edit_id - rvid: $report_view_id";
                if ($compliance_category == "Forms" && $new_report) {
                    //echo $this->redirect($this->f3->get('main_folder').'Compliance?form_message=1&report_id='.$report_id.'&site_id='.$_SESSION['user_id']);
                }
            }
            $subject_id = (isset($_GET['subject_id']) ? $_GET['subject_id'] : null);
            $auditor_id = (isset($_GET['auditor_id']) ? $_GET['auditor_id'] : null);
            if ($report_id || $subject_id || $auditor_id) {
                $hdnReportFilter = (isset($_REQUEST['hdnReportFilter']) ? $_REQUEST['hdnReportFilter'] : null);
                if ($hdnReportFilter) {

                    $chkPending = (isset($_GET['chkPending']) ? $_GET['chkPending'] : null);
                    $chkCompleted = (isset($_GET['chkCompleted']) ? $_GET['chkCompleted'] : null);
                    $chkCancelled = (isset($_GET['chkCancelled']) ? $_GET['chkCancelled'] : null);
                    if ($chkCompleted && $chkPending && $chkCancelled) {
                        $filter .= " and (compliance_checks.status_id = $chkPending or compliance_checks.status_id = $chkCompleted or compliance_checks.status_id = $chkCancelled) ";
                        $chkPending = "checked";
                        $chkCompleted = "checked";
                        $chkCancelled = "checked";
                    } else if ($chkCompleted && $chkPending) {
                        $filter .= " and (compliance_checks.status_id = $chkPending or compliance_checks.status_id = $chkCompleted) ";
                        $chkPending = "checked";
                        $chkCompleted = "checked";
                    } else if ($chkCompleted && $chkCancelled) {
                        $filter .= " and (compliance_checks.status_id = $chkCancelled or compliance_checks.status_id = $chkCompleted) ";
                        $chkCancelled = "checked";
                        $chkCompleted = "checked";
                    } else if ($chkCancelled && $chkPending) {
                        $filter .= " and (compliance_checks.status_id = $chkPending or compliance_checks.status_id = $chkCancelled) ";
                        $chkPending = "checked";
                        $chkCancelled = "checked";
                    } else if ($chkPending) {
                        $filter .= " and compliance_checks.status_id = $chkPending ";
                        $chkPending = "checked";
                    } else if ($chkCompleted) {
                        $filter .= " and compliance_checks.status_id = $chkCompleted ";
                        $chkCompleted = "checked";
                    } else if ($chkCancelled) {
                        $filter .= " and compliance_checks.status_id = $chkCancelled ";
                        $chkCancelled = "checked";
                    }
                } else {
                    $filter = " and compliance_checks.status_id != 525" . ($is_client ? " and compliance_checks.status_id != 522" : "");
                    $chkPending = ($is_client ? "" : "checked");
                    $chkCompleted = "checked";
                }

                if (!$new_report && !$site_id) {
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
            <input type="hidden" name="report_id" id="report_id" value="' . $report_id . '">
            <input type="hidden" name="site_id" id="site_id" value="' . $site_id . '">
            <input type="hidden" name="new_report" id="new_report" value="' . $new_report . '">
            <input type="hidden" name="report_edit_id" id="report_edit_id" value="' . $report_edit_id . '">
            <input type="hidden" name="report_view_id" id="report_view_id" value="' . $report_view_id . '">
            <input type="hidden" name="subject_id" id="subject_id" value="' . $subject_id . '">
            <input type="hidden" name="auditor_id" id="auditor_id" value="' . $auditor_id . '">
            <div class="form-wrapper">
            <div class="form-header">Filter</div>
            <div  style="padding: 10px;">
            ' . $nav->month_year(2010) . '
            <input value="522" type="checkbox" name="chkPending" ' . $chkPending . ' id="chkPending"  /> Pending
            <input value="524" type="checkbox" name="chkCompleted" ' . $chkCompleted . ' id="chkCompleted"  /> Completed
            <input value="525" type="checkbox" name="chkCancelled" ' . $chkCancelled . ' id="chkCancelled"  /> Cancelled
            <input type="text" name="txtComplianceFilter" id="txtComplianceFilter" value="' . $txtComplianceFilter . '" placeholder="Optional filter by text within answers..." style="width: 300px;" /><input onClick="report_filter()" type="button" value="Go" />
            </div>
            </div>
            </form>
          ';
                    if ($hdnReportFilter) {
                        $month_select = (isset($_REQUEST['selDateMonth']) ? $_REQUEST['selDateMonth'] : null);
                        $year_select = (isset($_REQUEST['selDateYear']) ? $_REQUEST['selDateYear'] : null);
                    } else {
                        $str .= '
                  <script language="JavaScript">
                    change_selDate()
                  </script>
            ';
                        $month_select = date('m');
                        $year_select = date('Y');
                    }
                    if ($month_select > 0) {
                        $filter .= " and MONTH(compliance_checks.check_date_time) = $month_select ";
                    }
                    if ($year_select > 0) {
                        $filter .= " and YEAR(compliance_checks.check_date_time) = $year_select ";
                    }
                }
            }



            if ($hdnSubmitReport) {
                $sql = "delete from compliance_check_answers where compliance_check_id = $report_edit_id;";
                $result = $this->dbi->query($sql);
                foreach ($_POST as $param_name => $param_val) {
                    $bits = explode("_", $param_name);
                    $ptype = $bits[0];
                    $param_id = $bits[1];
                    //echo "<h3>$ptype -- $param_id</h3>";
                    $param_num = ($bits[2] ? $bits[2] : 0);
                    $param_seq = $bits[2];
                    if ($ptype != "hdn" && $param_val) {
                        $fields = explode(";*;", $param_val);
                        if ($ptype == "opt" || $ptype == "chk") {
                            $fid = $fields[0];
                            $additional_text = $fields[1];
                            if ($ptype == "opt") {
                                $question = $this->mesc($fields[2]);
                                $answer = $this->mesc($fields[3]);
                                $val = $fields[4];
                                $answer_id = $fields[5];
                                $answer_colour = $fields[6];
                                $sql = "select max(convert(choice_value, SIGNED)) as `max_val` from compliance_question_choices where compliance_question_id = $param_id";
                                //echo $sql;
                                if ($result_c = $this->dbi->query($sql)) {
                                    if ($myrow_c = $result_c->fetch_assoc()) {
                                        $p_out_of = $myrow_c['max_val'];
                                    } else {
                                        $p_out_of = 0;
                                    }
                                }
                                //echo "<h3>$sql</h3>";
                            } else {
                                $question = $this->mesc($fields[1]);
                                $answer = $this->mesc($fields[2]);
                                $val = $fields[3];
                                $answer_id = $fields[4];
                                $answer_colour = $fields[5];
                                $sql = "select choice_value from compliance_question_choices where id = $answer_id;";
                                //$str .= $sql;
                                if ($result_c = $this->dbi->query($sql)) {
                                    if ($myrow_c = $result_c->fetch_assoc()) {
                                        $p_out_of = abs($myrow_c['choice_value']);
                                    } else {
                                        $p_out_of = 0;
                                    }
                                }
                            }
                            $sql = "insert into compliance_check_answers(question_id, compliance_check_id, question, answer, value, answer_id, out_of, answer_colour) values ($param_id, $report_edit_id, '$question', '$answer', '$val', '$answer_id', '$p_out_of', '$answer_colour')";
                            //echo "<h1>$sql</h1>";
                            if ($result = $this->dbi->query($sql))
                                $last_idtmp[$param_num] = $this->dbi->insert_id;
                        } else {
                            if ($param_id == $old_param_id) {
                                $last_id = ($old_ptype == "opt" ? $last_idtmp[0] : $last_idtmp[$param_num]);
                                $sql = "select id from compliance_question_choices where compliance_question_id = '$param_id';";

                                if ($result = $this->dbi->query($sql)) {
                                    if ($myrow = $result->fetch_assoc()) {
                                        if ($myrow['id']) {
                                            $sql = "update compliance_check_answers set additional_text = '$param_val' where id = $last_id;";
                                            $result = $this->dbi->query($sql);
                                        }
                                    }
                                }
                            } else {
                                $param_val = $this->mesc($param_val);
                                //          echo "<h3>$param_val</h3>";
                                $sql = "select question_title from compliance_questions where id = $param_id";
                                if ($result = $this->dbi->query($sql)) {
                                    if ($myrow = $result->fetch_assoc()) {
                                        $question = $this->mesc($myrow['question_title']);
                                    }
                                }
                                $sql = "insert into compliance_check_answers(question_id, compliance_check_id, question, answer) values ($param_id, $report_edit_id, '$question', '$param_val')";
                                $result = $this->dbi->query($sql);
                            }
                        }
                        $old_param_id = $param_id;
                        $old_ptype = $ptype;
                        $old_additional_text = $additional_text;
                    }
                }
                $sql = "select sum(tvalues) as total_value from
                (select max(CONVERT(choice_value, SIGNED)) as tvalues from compliance_question_choices where compliance_question_id in
                (select id from compliance_questions where compliance_id in (select compliance_id from compliance_checks where id = $report_edit_id) and question_type = 'opt')
                group by compliance_question_id)
                as subquery";
                if ($result = $this->dbi->query($sql)) {
                    if ($myrow = $result->fetch_assoc()) {
                        $total_out_of = $myrow['total_value'];
                    }
                }
                $sql = "select sum(choice_value) as total_value from compliance_question_choices where compliance_question_id in
        (select id from compliance_questions where compliance_id in (select compliance_id from compliance_checks where id = $report_edit_id) and question_type = 'chl')";
                if ($result = $this->dbi->query($sql)) {
                    if ($myrow = $result->fetch_assoc()) {
                        $total_out_of += $myrow['total_value'];
                    }
                }
                $sql = "select round((select count(distinct(question_id)) as num_answers from compliance_check_answers where compliance_check_id = $report_edit_id) /
        (select count(id) as `num_questions` from compliance_questions where compliance_id in (select compliance_id from compliance_checks where id = $report_edit_id)) * 100) as `percent_complete`";
                if ($result = $this->dbi->query($sql)) {
                    if ($myrow = $result->fetch_assoc()) {
                        $percent_complete = $myrow['percent_complete'];
                    }
                }
                $sql = "update compliance_checks set percent_complete = '$percent_complete', total_out_of = '$total_out_of', last_modified = '$curr_date_time' where id = $report_edit_id";
                $this->dbi->query($sql);
                if ($_POST['hdnCompleteReport']) {
                    $sql = "update compliance_checks set status_id = 524 where id = " . $report_edit_id;
                    $result = $this->dbi->query($sql);
                    $redirect_str = $this->f3->get('main_folder') . 'Compliance?report_view_id=' . $report_edit_id . ($is_form || $is_survey || $is_request ? "&form_message=1" : "");
                    $redirect_str .= ($job_application_id ? "&job_application_id=$job_application_id" : ($job_application2_id ? "&job_application2_id=$job_application2_id" : ($request_id ? "&request_id=$request_id" : "")));

                    $sql = "select 
                    compliance_checks.subject_id,
                    compliance_checks.id as `compliance_check_id`,
                    compliance_email_to.site_id as `site_id_in`,
                    CONCAT(users.name, ' ', users.surname) as `send_to`,
                    CONCAT(users2.name, ' ', users2.surname) as `assessor`,
                    CONCAT(users3.name, ' ', users3.surname) as `subject`,
                    compliance.title, DATE_FORMAT(compliance_checks.check_date_time, '%d-%b %H:%i') as `date`,
                    users.email as `email_send`
                    FROM compliance_checks
                    inner join users2 on users2.id = compliance_checks.assessor_id
                    inner join users3 on users3.id = compliance_checks.subject_id
                    inner join compliance on compliance.id = compliance_checks.compliance_id
                    inner join compliance_email_to on compliance_email_to.compliance_id = compliance.id
                    inner join users on users.id = compliance_email_to.staff_id
                    where compliance_checks.id = $report_edit_id";
                    //$str .= $this->ta($sql) . "<br/>";
                    if ($result = $this->dbi->query($sql)) {
                        $mail = new email_q($this->dbi);
                        while ($myrow = $result->fetch_assoc()) {
                            $subject_id = $myrow['subject_id'];
                            $site_id_in = $myrow['site_id_in'];
                            if (!$site_id_in || $site_id_in == $subject_id) {
                                $mail->clear_all();
                                $send_to = $myrow['send_to'];
                                $compliance_check_id = $myrow['compliance_check_id'];
                                $email_send = $myrow['email_send'];
                                $assessor = $myrow['assessor'];
                                $subject = $myrow['subject'];
                                $title = $myrow['title'];
                                $date = $myrow['date'];
                                $msg = "Hello $send_to,<br /><br />";
                                $compliance_obj2 = new compliance;
                                $compliance_obj2->dbi = $this->dbi;
                                $compliance_obj2->compliance_check_id = $report_edit_id;
                                $compliance_obj2->display_results();
                                $msg .= "<h3>The following report or form was just added to " . $this->f3->get('software_name') . ":</h3>
                <h3>$title</h3> <b>Performed on</b> $date <b>by</b> $assessor " . ($assessor != $subject ? "<b>on</b> $subject" : "");
                                $msg .= $compliance_obj2->str;
                                $msg .= "<br/><a href=\"{$this->f3->get('full_url')}Compliance?report_edit_id=$compliance_check_id\">Edit Report</a> | ";
                                $msg .= "<a href=\"{$this->f3->get('full_url')}Compliance?report_view_id=$compliance_check_id\">View Report</a> | ";
                                $msg .= "<a href=\"{$this->f3->get('full_url')}CompliancePDF/$compliance_check_id\">Download PDF</a><br/>";
                                $msg .= "<br /><br />Regards,<br />" . $this->f3->get('company_name');
                                $str .= $msg;
                                //$mail->AddReplyTo($assessor_email);
                                $mail->AddAddress($email_send);
                                $mail->Subject = "Report or Form Created on " . $this->f3->get('software_name');
                                $mail->Body = $css . "\n\n" . $msg;
                                $mail->queue_message();
                                //  $str .= $msg;
                            }
                        }
                    }
                    //return $str;
                    $str .= $this->redirect($redirect_str);
                }
                $str .= "<script type=\"text/javascript\">
                  caller('Report Saved');
                  </script>";
            }

            $detect = new Mobile_Detect;
            /* if($detect->isTablet()){
              $m_pfix = "mobile_";
              $standard_font = "22pt";
              $m_font = "mobile_font";
              $question_font = "font-size: 18pt;    padding-bottom: 10px;";
              $q_lbl_font = "font-size: 18pt; padding-top: 10px; padding-bottom: 5px;";
              $button_font = "    padding-top: 15px !important;    padding-bottom: 15px !important;    font-size: 16pt !important;";
              $height_multiplier = "41";
              $text_box_font = "font-size: 26pt !important;    width: 99%;";
              $compliance_auditor_answer_font = "font-size: 16pt; padding: 12px;";
              $answer_button_style = "width: 50px; height: 50px; padding-bottom: 0px;";
              $question_area_padding = "padding-bottom: 0px;  padding-top: 15px;";
              } else if($detect->isMobile()){
              $m_pfix = "mobile_";
              $standard_font = "26pt";
              $m_font = "mobile_font";
              $question_font = "font-size: 25pt;    padding-bottom: 15px;";
              $q_lbl_font = "font-size: 25pt; padding-top: 15px; padding-bottom: 10px;";
              $button_font = "    padding-top: 20px !important;    padding-bottom: 20px !important;    font-size: 22pt !important;     background-size: auto 48px;";
              $height_multiplier = "34";
              $text_box_font = "font-size: 22pt !important;    width: 99%;";
              $compliance_auditor_answer_font = "font-size: 18pt; padding: 15px;";
              $answer_button_style = "width: 70px; height: 70px; padding-bottom: 10px;";
              $question_area_padding = "padding-bottom: 0px;  padding-top: 15px;";
              } else { */
            $standard_font = "11pt";
            $question_font = "font-size: 12pt;    padding-bottom: 5px;";
            $q_lbl_font = "font-size: 12pt; padding-top: 10px; padding-bottom: 5px;";
            $button_font = "    padding-top: 7px !important;    padding-bottom: 7px !important;    font-size: 12pt !important;     background-size: auto 24px;";
            $height_multiplier = "17";
            $text_box_font = "font-size: 11pt !important;    width: 99%;";
            $compliance_auditor_answer_font = "font-size: 11pt; padding: 10px;";
            $answer_button_style = "width: 30px; height: 30px; padding-bottom: 0px;";
            $question_area_padding = "padding-bottom: 0px; padding-left: 0px; padding-right: 0px; padding-top: 15px;";
            //}


            $button_col = "#C9C9C9";
            $str .= '
      <style type="text/css">
       .question_area {
          margin-bottom: 15px;
          margin-top: 25px;
          padding-left: 10px;
          ' . $question_area_padding . '
          border-bottom: 1px solid #BBBBBB;
          /*background-color: #DDDDDD;
          border-bottom: 2px solid #BBBBBB;
          border-top: 2px solid white;*/
        }
         .q_lbl {
            ' . $q_lbl_font . '
            padding-left: 10px;
            margin-top: 20px;
            border-bottom: 2px solid red; background-color: #FFFFCC; font-weight: bold;
         }
         .instructions {
            ' . $q_lbl_font . '
            padding: 15px;
            margin-top: 10px;
            margin-bottom: 10px;
            border: 1px solid #006600; background-color: #FFFFEE;
         }
        .compliance_question {
          ' . $question_font . '
          float: left;
        }
       .save_button {
          ' . $button_font . ';
          width: 49% !important;
          border-radius: 6px 6px 6px 6px;
          -moz-border-radius: 6px 6px 6px 6px;
          -webkit-border-radius: 6px 6px 6px 6px;
          margin-right: 3px;
          margin-top: 10px;
          border: none !important;
        }
        /*.save {
          background-image: url(' . $this->f3->get('img_folder') . 'save.gif);
        }
        .complete {
          background-image: url(' . $this->f3->get('img_folder') . 'save_complete.gif);
        }*/
        .cb {
          text-align: center;
          border: 1px solid #BBBBBB !important;
          background-color: ' . $button_col . ';
          ' . $button_font . ';
          color: black;
          margin: 1px;
          float: left;
          border-radius: 6px 6px 6px 6px;
          -moz-border-radius: 6px 6px 6px 6px;
          -webkit-border-radius: 6px 6px 6px 6px;
          border: 0px solid #000000;border: 0px solid #000000;
          border: 0px solid #000000;
          /*border: 0px solid #000000;    */
        }
        /*.c1 { width: 99%; }
        .c2 { width: 48.7%; }
        .c3 { width: 32%; }
        .c4 { width: 24%; }
        .c5 { width: 19%; }
        .c6 { width: 15.8%; }
        .c7 { width: 13.5%; }
        .c8 { width: 11.8%; }
        .c9 { width: 10.5%; }
        .c10 { width: 9.4%; }
        .c11 { width: 8.55%; }
        .c12 { width: 7.8%; }*/
        
        .c1 { width: 99%; }
        .c2 { width: 47.7%; }
        .c3 { width: 31%; }
        .c4 { width: 23%; }
        .c5 { width: 19%; }
        .c6 { width: 15.4%; }
        .c7 { width: 13.5%; }
        .c8 { width: 11.8%; }
        .c9 { width: 10.5%; }
        .c10 { width: 9.4%; }
        .c11 { width: 8.55%; }
        .c12 { width: 7.8%; }
        
        
        .compliance_answer_button {
          background-size: cover; 
          float: right;
          ' . $answer_button_style . '
          background-image: url(' . $this->f3->get('img_folder') . 'show.gif);
        }
        .img_btn {
          background-size: cover; 
          float: right;
          ' . $answer_button_style . '
          background-image: url(' . $this->f3->get('img_folder') . 'image_show.gif);
        }
        .compliance_add_image {
          background-size: cover; 
          float: right;
          ' . $answer_button_style . '
          background-image: url(' . $this->f3->get('img_folder') . 'show.gif);
        }
        .d_image {
          padding-right: 5px;
          max-width: 255px;
        }
        .compliance_auditor_answer {
          ' . $compliance_auditor_answer_font . '
          background-color: #FFFFEE;
          border: 1px solid #660000;
          margin-right: 10px;
        }
        .compliance_mini_answer {
          display: block;
          ' . $compliance_auditor_answer_font . '
          background-color: #FFFFEE;
          border: 1px solid #660000;
          margin-top: 8px;
          margin-bottom: 8px;
        }
        .img_uploader {
          display: none;
          padding: 0px;
          border: none;
        }
        .compliance_text_box {
          background-color: white;
          color: #222222;
          border: 1px solid #CCCCCC;
          ' . $text_box_font . '
        }
        .compliance_text_box:focus {
          -webkit-text-size-adjust: 100%;
        }
        .time_box {
          width: 300px;
          font-size: 36pt;
        }
        .date_box {
          width: 100%;
          font-size: 36pt;
        }
        .additional_box {
          display: none;
        }
        .opt {
          display: none;
        }
        .time_click_mobile {
          cursor: hand; background-color: #CCCCCC;
          padding-left: 60px !important; padding-right: 60px !important;
          padding-top: 5px; padding-bottom: 5px;
        }
        table.mobile_grid {
          border-width: 1px;
          border-spacing: 0px;
          border-style: outset;
          border-color: #DDDDDD;
          border-collapse: collapse;
          font: 24pt arial;
        }
        table.mobile_grid th {
          border-width: 1px;
          padding: 8px;
          border-style: solid;
          border-color: #DDDDDD;
          background-color: white;
          font: 24pt arial;
          font-weight: bold;
        }
        table.mobile_grid td {
          border-width: 1px;
          padding: 8px;
          border-style: solid;
          border-color: #DDDDDD;
          font: 24pt arial;
          padding-top: 25px;
          padding-bottom: 25px;
        }
        table.mobile_grid tr:nth-child(even) {background-color: #F9F9F9;}
        table.mobile_grid tr:nth-child(odd) {background-color: #FFFFFF;}
        table.mobile_grid td a {
          font: 24pt arial;
        }
        .status_button_mobile {
          padding-top: 42px !important;
          padding-bottom: 42px !important;
          padding-left: 32px !important;
          padding-right: 32px !important;
          border: none !important;
          font-size: 22pt !important;
        }
        .status_button_mobile:hover {
        }
        #file_upload {
          display: none;
        }
        .subject_a {
          font-size: 20px;
          display: block;
          padding: 20px;
          margin-bottom: 8px;
          margin-left: -8px;
          width: 100%;
          background-color: #E9E9E9;
          border-top: 1px solid white;
          border-left: 1px solid white;
          border-bottom: 1px solid #AAAAAA;
          border-right: 1px solid #AAAAAA;
        }
        .subject_a:hover {
          text-decoration: none !important;
          background-color: #DDEEEE;
          border-bottom: 1px solid #9999AA;
          border-right: 1px solid #9999AA;
        }
        .cell_wrap {
          float: left;
          display: inline-block;
          padding: 0px;
          border: 1px solid #DDDDDD;
        }
        .cell_head {
          padding: 6px;
          background-color: white;
        }
        .cell_foot {
          padding: 6px;
          background-color: #F9F9F9;
          border-top: 1px solid #DDDDDD;
        }
      </style> 
      <script type="text/javascript">
        function hostReachable() {
          return true;
    // Handle IE and more capable browsers
          var xhr = new ( window.ActiveXObject || XMLHttpRequest )( "Microsoft.XMLHTTP" );
          var status;
    // Open new request as a HEAD to the root hostname with a random param to bust the cache
          xhr.open( "HEAD", "//" + window.location.hostname + "/?rand=" + Math.floor((1 + Math.random()) * 0x10000), false );
    // Issue request and handle response
          try {
            xhr.send();
            return ( xhr.status >= 200 && (xhr.status < 300 || xhr.status === 304) );
          } catch (error) {
            return false;
          }
        }
        var off_col = "' . $button_col . '"
        var x, bg_col
        var hexDigits = new Array("0","1","2","3","4","5","6","7","8","9","A","B","C","D","E","F");
        function hex(x) {
          return isNaN(x) ? "00" : hexDigits[(x - x % 16) / 16] + hexDigits[x % 16];
        }
        function rgb2hex(rgb) {
         rgb = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
         return "#" + hex(rgb[1]) + hex(rgb[2]) + hex(rgb[3]);
        }
        function show_hide_answer(id) {
          if(document.getElementById("auditor_answer"+id).style.display == "block") {
            document.getElementById("auditor_answer"+id).style.display = "none";
            document.getElementById("compliance_answer_button"+id).style.backgroundImage = "url(' . $this->f3->get('img_folder') . 'show.gif)";
    //compliance_answer_button
          } else {
            document.getElementById("auditor_answer"+id).style.display = "block";
            document.getElementById("compliance_answer_button"+id).style.backgroundImage = "url(' . $this->f3->get('img_folder') . 'hide.gif)";
          }
        }
        function show_hide_img(id,target_dir) {
          if(document.getElementById("img_uploader"+id).style.display == "block") {
            document.getElementById("img_uploader"+id).style.display = "none";
            document.getElementById("img_btn"+id).style.backgroundImage = "url(' . $this->f3->get('img_folder') . 'image_show.gif)";
          } else {
            document.getElementById("img_uploader"+id).style.display = "block";
            document.getElementById("img_btn"+id).style.backgroundImage = "url(' . $this->f3->get('img_folder') . 'image_hide.gif)";
            if(!document.getElementById("photo_upload"+id).src) document.getElementById("photo_upload"+id).src  = "' . $this->f3->get('main_folder') . 'Fileman?show_min=1&target_dir="+target_dir+"&target_subdir="+id;
          }
        }
        function toggle_opt(item_id, num, num_of, sel_col, additional_text_required, choice, first, from_further) {
          if(first === undefined) first = 0;
          if(from_further === undefined) from_further = 0;
          //alert(from_further)
          var reg = new RegExp(\'^\\\\d+$\');
          var is_follow = (reg.test(additional_text_required) === true ? 1 : 0)
          var allow = 1
          //if(is_follow) {
          //  alert(item_id)
            for(x = 1; x < groups.length; x++) {
              if(first) allow = groups[x] == groups[additional_text_required]
              if(allow) {
                if(x == additional_text_required) {
                  e = document.getElementById("child" + additional_text_required)
                  cs = document.defaultView.getComputedStyle(e,null);
                  disp = cs.getPropertyValue("display");
                  e.style.display = (disp == "block" ? "none" : "block");
                  if(e.style.display == "block") {
                    ch = document.getElementById("child_header" + additional_text_required)
                    e.style.borderColor = sel_col
                    e.style.borderBottomColor = sel_col
                    e.style.borderLeftColor = "#EEEEEE"
                    e.style.borderRightColor = "#EEEEEE"
                    ch.style.borderColor = sel_col
                    ch.innerHTML = "Further Questions/Instructions for the Answer " + choice
                  }
                } else {
                  if(from_further) {
                    e = document.getElementById("child" + x)
                    e.style.display = "none";
                  }
                }
              }
            }
          //}
          bg_col = sel_col;
          for(x = 1; x <= num_of; x++) {
            if(document.getElementById("cont" + item_id + x) && !is_follow) {
              document.getElementById("cont" + item_id + x).style.display = "none";
            }
            if(x == num) {
              e = document.getElementById("div" + item_id + x)
              cs = document.defaultView.getComputedStyle(e,null);
              bg = rgb2hex(cs.getPropertyValue("background-color"));
              if(bg == sel_col) sel_col = off_col;
              bg_col = sel_col;
              if(additional_text_required && sel_col != off_col && !is_follow) {
                document.getElementById("cont" + item_id + num).style.display = "block";
              }
              if(sel_col != off_col) {
    //alert(item_id + " " + num)
    //alert(document.getElementsByName("hdnChoices["+x+"]").value)
                  document.getElementById("opt_"+item_id+"_"+num).checked = true;
                  fg_col = "white";
              } else {
                  document.getElementById("opt_"+item_id+"_"+num).checked = false;
                  fg_col = "black";
              }
            } else {
              bg_col = off_col;
              fg_col = "black";
            }
            document.getElementById("div" + item_id + x).style.backgroundColor = bg_col;
            document.getElementById("div" + item_id + x).style.color = fg_col;
          }
        }
        function toggle_chl(item_id, num, num_of, sel_col, additional_text_required, choice, first) {
          if(first === undefined) first = 0;
          var reg = new RegExp(\'^\\\\d+$\');
          var is_follow = (reg.test(additional_text_required) === true ? 1 : 0)
          if(is_follow) {
            e = document.getElementById("child" + additional_text_required)
            cs = document.defaultView.getComputedStyle(e,null);
            disp = cs.getPropertyValue("display");
            e.style.display = (disp == "block" ? "none" : "block");
            ch = document.getElementById("child_header" + additional_text_required)
            e.style.borderColor = sel_col
            ch.style.borderColor = sel_col
            ch.innerHTML = choice + " Further Questions"
          }
          e = document.getElementById("div" + item_id + num)
          cs = document.defaultView.getComputedStyle(e,null);
          bg = rgb2hex(cs.getPropertyValue("background-color"));
          if(bg == sel_col) {
            sel_col = off_col;
            document.getElementById("chk_"+item_id+"_"+num).checked = false;
            if(additional_text_required && !is_follow) {
              document.getElementById("cont" + item_id + num).style.display = "none";
            } 
            fg_col = "black";
          } else {
            document.getElementById("chk_"+item_id+"_"+num).checked = true;
            if(additional_text_required && !is_follow) {
              document.getElementById("cont" + item_id + num).style.display = "block";
            } 
            fg_col = "white";
          }
          document.getElementById("div" + item_id + num).style.backgroundColor = sel_col;
          document.getElementById("div" + item_id + num).style.color = fg_col;
        }
        function submit_report(x) {
         window.onbeforeunload = "";
          if(!navigator.onLine) {
            document.getElementById("hdnCompleteReport").value = 0
            alert("You are currently not online.\nThe form HAS NOT been submitted.\nPlease check your connection and try again...")
          } else {
            for(x = 1; x < groups.length; x++) {
              e = document.getElementById("child" + x)
              cs = document.defaultView.getComputedStyle(e,null);
              disp = cs.getPropertyValue("display");
              var srch = e.getElementsByTagName("*");
              if(disp == "none") {
                for(var i = 0; i < srch.length; i++) {
                  if(srch[i].type == "textarea" && srch[i].value != "") srch[i].value = ""
                  if(srch[i].tagName == "INPUT") {
                    if(srch[i].getAttribute("type") == "radio") {
                      srch[i].checked = false
                    } else if(srch[i].getAttribute("type") == "text") {
                      srch[i].value = ""
                    }
                  } else {
                    //if(srch[i].getAttribute("type") != null) alert (srch[i].getAttribute("type"))
                  }
                }
              }
            }
    //Saving Signature (if there is one)
          if(typeof save_canvas !== "undefined"){
            save_canvas();
            setTimeout(function() {document.getElementById("hdnSubmitReport").value = 1, document.frmEdit.submit()},2000);          
          }else{
            document.getElementById("hdnSubmitReport").value = 1
            document.frmEdit.submit()
            
          }
          
            if(typeof save_canvas !== "undefined")   save_canvas();
                
                document.getElementById("hdnSubmitReport").value = 1
                document.frmEdit.submit()
         }       
        }
        function complete_report(x) {
          confirmation = "' . ($job_application_id || $job_application2_id || $request_id ? "Do you wish to save all of your details now?" : ($is_induction ? "Save Induction Quiz?" : ($is_request || $is_form ? "Save and Complete this Form Now?" : ($is_survey ? "Save this Survey Now?" : "Are you sure about completing this report?\\n\\nWARNING: THIS CANNOT BE REVERSED!")))) . '";
          if(confirm(confirmation)) {
            document.getElementById("hdnCompleteReport").value = 1
            submit_report(x)
          }
        }
        function change_status(change_itm, status_in, status_txt) {
          var confirmation;
          confirmation = "Are you sure about changing the status this record to "+status_txt+"?";
          if (confirm(confirmation)) {
            document.getElementById("hdnChangeItem").value = change_itm
            document.getElementById("hdnChangeStatus").value = status_in
            document.frmEdit.submit()
          }
        }
      </script>
      ';
            $view_details = new data_list;
            $view_details->show_num_records = 1;

            $view_details->dbi = $this->dbi;
            //$view_details->table_class = $m_pfix."grid";
            //$str .= "$report_edit_id || $report_view_id && !$is_induction";


            if ($report_edit_id || $report_view_id && !$is_induction) {
                $rid = ($report_edit_id ? $report_edit_id : $report_view_id);
                $sql = "select compliance.id as compliance_id, compliance.title, compliance_checks.id as `compliance_check_id`, compliance.show_answers,
                  CONCAT('<nobr>', DATE_FORMAT(compliance_checks.check_date_time, '%d-%b-%Y %H:%i'), '</nobr>') as `date`,
                  CONCAT('<nobr>', DATE_FORMAT(compliance_checks.last_modified, '%d-%b-%Y %H:%i'), '</nobr>') as `last_modified`,
                  CONCAT(if(users.employee_id != '', users.employee_id, ''), ' ', if(users.client_id != '', users.client_id, ''), ' ', users.name, ' ', users.surname) as `assessor`, CONCAT(users.name, ' ', users.surname) as `assessor_fullname`, users.phone, CONCAT(users.address, ' ', users.suburb, ' ', states.item_name, ' ', users.postcode) as `address`,
                  CONCAT(if(users2.employee_id != '', users2.employee_id, ''), ' ', if(users2.client_id != '', users2.client_id, ''), ' ', users2.name, ' ', users2.surname) as `subject`, " . ($job_application_id || $job_application2_id || $request_id || $is_survey || $is_induction || $z || !$this->f3->get('is_staff') ? "" : "
                  CONCAT('<a target=\"_blank\" uk-tooltip=\"title: View Online\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_view_id=', compliance_checks.id, '\">View</a>') as `view`,
                  CONCAT('<a target=\"_blank\" uk-tooltip=\"title: View as PDF\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "CompliancePDF/', compliance_checks.id, '\">PDF</a>', '<a target=\"_blank\" uk-tooltip=\"title: Upload/View Attachemts\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Resources?current_dir=compliance&current_subdir=', compliance_checks.id, '\">Attachments...</a>',
                  '<a target=\"_blank\" uk-tooltip=\"title: Add Notes\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Page/ComplianceCheckNotes?compliance_check_id=', compliance_checks.id, '\">Notes...</a>',
                  '<a target=\"_blank\" uk-tooltip=\"title: Resolve Issues\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "ComplianceFollowup?compliance_check_id=', compliance_checks.id, '\">Resolve Issues...</a>'
                  ) as `pdf`,
                  CONCAT('<a uk-tooltip=\"title: Edit Checklist\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_edit_id=', compliance_checks.id, '\">Edit</a>')	as `edit`,") . "
                  " . ($request_id ? "concat('<a target=\"_blank\" uk-tooltip=\"title: Upload/View Attachemts\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Resources?current_dir=compliance&current_subdir=', compliance_checks.id, '\">Attachments...</a>') as `attachments`, " : "") . "
                  lookup_fields1.item_name as `status`,
                  compliance_checks.total_out_of as `out_of`
                  FROM compliance_checks
                  left join lookup_fields1 on lookup_fields1.id = compliance_checks.status_id
                  left join compliance on compliance.id = compliance_checks.compliance_id
                  left join users on users.id = compliance_checks.assessor_id
                  left join users2 on users2.id = compliance_checks.subject_id
                  left join states on states.id = users.state
                  where compliance_checks.id = $rid";
                //$str .= "<textarea>{$sql}</textarea>";
                if ($result = $this->dbi->query($sql)) {
                    while ($myrow = $result->fetch_assoc()) {
                        $compliance_id = $myrow['compliance_id'];
                        $compliance_title = $myrow['title'];
                        $show_answers = $myrow['show_answers'];
                        $status = $myrow['status'];
                        $assessor_address = $myrow['address'];
                        $assessor_phone = $myrow['phone'];
                        $date = $myrow['date'];
                        $last_modified = $myrow['last_modified'];
                        $subject = $myrow['subject'];
                        $assessor = $myrow['assessor'];
                        $assessor_fullname = $myrow['assessor_fullname'];
                        if (!$this->f3->get('is_mobile') && !$this->f3->get('is_tablet')) {
                            $vxtra = (($report_edit_id || $status == 'Pending') && !$is_survey && !$is_induction && !$is_request && !$is_form && $this->f3->get('is_staff') ? '<div class="cell_wrap"><div class="cell_head">**</div><div class="cell_foot">' . $myrow[($report_edit_id ? 'view' : 'edit')] . $myrow['pdf'] . '</div></div>' : '');
                        }
                        if ($job_application_id || $job_application2_id || $request_id || $form_id) {
                            $vxtra = "";
                            $st_xtra = "";
                            if ($job_application_id) {
                                $str .= '<div class="q_lbl" style="margin-bottom: 20px;">' . ($report_view_id ? "<h1>Step 3 of 3, Review and Complete.</h1><h3>Please review your answers below and click \"Complete Application\" when finished.<br /><br />* Don't forget to upload your resume, cover letter and approprite licances/certificates by clicking the link below...</h3>" : "<h1>Step 2 of 3, More Information.</h1><h3>Please answer <u>ALL</u> of the questions below and save them to complete your job application.</h3>") . "</div>";
                            } else {
                                $str .= '<div class="q_lbl" style="margin-bottom: 20px;">' . ($report_view_id ? "<h1>Review and Complete.</h1><h3>Please review the answers below...</h3>" : ($request_id ? "<h1>Request Form.</h1><h3>Please answer the questions below and save the form.</h3>" : "<h1>Interview Checklist.</h1><h3>Please go through the checklist below and save it.</h3>")) . "</div>";
                            }
                            if (($report_view_id || $report_edit_id) && ($job_application_id || $job_application2_id)) {
                                $tmp_id = ($report_view_id ? $report_view_id : $report_edit_id);
                                $sql2 = "update job_applications set compliance_check_id_interviewe" . ($job_application_id ? "e" : "r") . " = $tmp_id where id = " . ($job_application_id ? $job_application_id : $job_application2_id);
                                $result2 = $this->dbi->query($sql2);
                            }
                            if ($job_application_id)
                                $str .= '<a target="_blank" class="list_a" href' . $this->f3->get('main_folder') . 'Resources?job_application_id=' . $job_application_id . '&current_dir=compliance&current_subdir=' . ($report_view_id ? $report_view_id : $report_edit_id) . '">Click Here to Attach Your Resume, Cover Letter and Appropriate Licences</a><br /><br />';
                            if ($request_id && !$report_edit_id) {
                                $str .= "<p><a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_edit_id=$report_view_id&" . ($job_application_id ? "job_application_id" : ($request_id ? "request_id" : "job_application2_id")) . "=" .
                                        ($job_application_id ? $job_application_id : ($request_id ? $request_id : $job_application2_id)) . "\"><< Go Back to Answering Questions</a>      " .
                                        ($job_application_id || $request_id ? (!$complete_application ? "<a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_view_id=$report_view_id&job_application_id=$job_application_id&request_id=$request_id&complete_application=1\">Complete Application >></a></p>" : "") : "<a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "JobApplications?more_info=$job_application2_id\">View Outcome >></a></p>");
                            }
                        } else {

                            $str .= "<h3>" . ($is_survey ? "Survey Details" : ($is_induction ? "Induction Questions" : ($is_request || $is_form ? "" : "Details"))) . "</h3>";
                            $vxtra = '
                ' . ($assessor != $subject ? '
                <div class="cell_wrap"><div class="cell_head">Assessor</div><div class="cell_foot">' . $assessor . '</div></div>
                <div class="cell_wrap"><div class="cell_head">Subject</div><div class="cell_foot">' . $subject . '</div></div>
                ' : '<div class="cell_wrap"><div class="cell_head">By</div><div class="cell_foot">' . $assessor . '</div></div>') . $vxtra .
                                    ($is_survey || $is_induction || !$this->f3->get('is_staff') || $is_induction || $is_request || $is_form || $this->f3->get('is_mobile') || $this->f3->get('is_tablet') ? '' : '<div class="cell_wrap"><div class="cell_head">&lt;&lt;</div><div class="cell_foot">' . "<a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_id=$compliance_id\">Back</a></div>" . '</div>');
                        }
                        $vxtra .= ($request_id ? '<div class="cell_wrap"><div class="cell_head">Attachments</div><div class="cell_foot">' . $myrow['attachments'] . '</div></div>' : '');
                        if ($compliance_title && !(($is_induction || $is_request || $is_form) && $form_message)) {
                            if (!($request_id || $is_induction || $is_request || $is_form))
                                $str .= '<div class="cell_wrap"><div class="cell_head">Status</div><div class="cell_foot">' . $status . '</div></div>';
                            $str .= '
              <div class="cell_wrap"><div class="cell_head">Date</div><div class="cell_foot">' . $date . '</div></div>
              <div class="cell_wrap"><div class="cell_head">Last Modified</div><div class="cell_foot">' . $last_modified . '</div></div>
              <div class="cell_wrap"><div class="cell_head">Title</div><div class="cell_foot">' . $compliance_title . '</div></div>
              ' . $vxtra;
                        }
                        $str .= '<div class="cl"></div>';
                        if ($job_application_id && !$report_view_id) {
                            $str .= "<br /><br /><h3>Please answer <u>ALL</u> of the following questions as accurately as possible to be considered for the role.</h3>";
                        }
                    }
                }
            }
            $reloadLocation = "";
            if ($report_id || $subject_id || $auditor_id) {
                if ($report_id && $report_id != "ALL") {


                    $for_by = " for ";
                    $sql = "select title, show_all_entities, description,division_id from compliance where id = $report_id";
                    if ($result = $this->dbi->query($sql)) {
                        if ($myrow = $result->fetch_assoc()) {
                            $title = $myrow['title'];
                            $show_all_entities = $myrow['show_all_entities'];
                            $search_desc = $myrow['description'];
                            $division_id = $myrow['division_id'];
                        }
                    }
                } else if ($subject_id) {
                    $xtra = "2";
                    $xtra2 = "";
                    $for_by = " for ";
                    $is_about = "Assessor";
                } else {
                    $subject_id = $auditor_id;
                    $xtra = "";
                    $xtra2 = "2";
                    $for_by = " by ";
                    $is_about = "Subject";
                }
                if ($subject_id || $auditor_id) {
                    $sql = "select employee_id, client_id, name, surname from users where id = $subject_id";
                    if ($result = $this->dbi->query($sql)) {
                        if ($myrow = $result->fetch_assoc()) {
                            $title = $myrow['employee_id'] . " " . $myrow['client_id'] . " " . $myrow['name'] . " " . $myrow['surname'];
                        }
                    }
                    $where_cond = " where users$xtra.id = $subject_id ";
                } else {

                    $where_cond = " where " . ($report_id == "ALL" ? "1" : "compliance_checks.compliance_id = $report_id");
                }
                if ($report_id && $subject_id) {
                    $where_cond = " where users2.id = $subject_id and compliance_checks.compliance_id = $report_id ";
                }
                if (!$is_manager) {
                    $sql = "select id from compliance where id = $report_id and allow_all_access = 1;";
                    if ($result = $this->dbi->query($sql))
                        $all_access = $result->num_rows;
                }
                if ($new_report) {
                    if (!$show_all_entities)
                        $str .= "<h3 class=\"mobile_font\">$title</h3><br />";
                    $search_str = trim((isset($_GET['compliance_search']) ? $_GET['compliance_search'] : ($search_desc ? $search_desc : null)));
                    if ($show_all_entities && !$_GET['compliance_search'])
                        $search_str = "ALL";
                    if ($search_str) {
                        if ($search_str != 'ALL') {
                            $filter = " and (users.name LIKE '%$search_str%' or users.surname LIKE '%$search_str%' or users.email LIKE '%$search_str%' or users.employee_id LIKE '%$search_str%' or users.client_id LIKE '%$search_str%'
              or CONCAT(users.name, ' ', users.surname) LIKE '%$search_str%')";
                        } else {
                            $filter = "";
                        }
                        $subqueryDivision = ",(SELECT GROUP_CONCAT(' ',com.id) FROM lookup_answers la 
LEFT JOIN companies com ON com.id = la.lookup_field_id
WHERE la.foreign_id = users.id AND com.id IS NOT NULL) as `division_ids`";
                        $userType = 384;
                        $usertype_xtra = ($userType ? "inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = $userType and lookup_answers.table_assoc = 'users'" : "");

                        //company division query
                        $filter .= ($company_ids ? " and users.id in (select user_id from users_user_groups where user_group_id in ($company_ids)) " : "");

                        $sql = "select * from (
                      select users.id as `user_id`, users.name, users.surname as sname, users.employee_id, users.client_id
                      $subqueryDivision
                      from compliance_auditors_subjects
                      left join users on users.id = compliance_auditors_subjects.user_id
                     
                      where compliance_auditors_subjects.compliance_auditor_id = (select id from compliance_auditors where user_id = " . $_SESSION['user_id'] . " and compliance_id = $report_id ) $filter
                      ";
                        if ($is_manager || $all_access) {

                            $sql = "$sql union distinct select users.id as `user_id`, users.name, users.surname as sname, users.employee_id, users.client_id
                  $subqueryDivision
                        from users
                        
                      where id in (
                        select users.id from users inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id
                        in (select lookup_field_id from lookup_answers where table_assoc = 'compliance' and foreign_id = $report_id)
                        and lookup_answers.table_assoc = 'users' and users.user_status_id = (select id from user_status where item_name = 'ACTIVE') $filter
                      )";
                        }
                        $sql .= ") a order by sname";

//           echo $sql;
//           die;
                        if ($result = $this->dbi->query($sql))
                            $num_items = $result->num_rows;
                        //$num_items = $this->num_results($sql);
                    } else {
                        $num_items = 0;
                    }

                    if (!$num_items) {
                        if ($this->num_results("SELECT id FROM lookup_fields where value = 'SITE' and id = (select lookup_field_id from `lookup_answers` where foreign_id = $report_id and table_assoc = 'compliance')")) {

                            $sql = "select users.id as `user_id`, users.name, users.surname as sname, users.employee_id, users.client_id
                   $subqueryDivision
                                from users                                
                                left join associations on associations.parent_user_id = users.id
                                where associations.association_type_id = (select id from association_types where name = 'site_staff') and associations.child_user_id = " . $_SESSION['user_id'];
                            if ($result = $this->dbi->query($sql))
                                $num_items = $result->num_rows;
                        }
                    }

                    $subject_type = $this->get_sql_result("SELECT lookup_fields.item_name as `result` FROM `lookup_answers`
          left join lookup_fields on lookup_fields.id = lookup_answers.lookup_field_id
          where table_assoc = 'compliance' and lookup_answers.foreign_id = $report_id");
                    $subject_type = ($subject_type ? $subject_type : "Subject");

                    $str .= '
                    </form>
                    <script type="text/javascript">
                        function show_all() {
                            document.getElementById("compliance_search").value = "ALL";
                            document.frmFollowSearch.submit();
                        }
                    </script>
                    <form method="get" name="frmFollowSearch">
                        <input type="hidden" name="report_id" value="' . $report_id . '" />
                        <input type="hidden" name="new_report" value="' . $new_report . '" />
                        <div class="fl">
                ';

    
                    $str .= '<input maxlength="50" placeholder="" name="compliance_search" id="compliance_search" type="text" class="search_box" value="' . $search_str . '" />';
       
                
                $str .= '
                        <input type="submit" name="cmdFollowSearch" value="Search" class="search_button" />
                        <input type="button" onClick="show_all()" name="cmdFollowSearch" value="Show All" class="search_button" style="margin-left: 2px;" />
                    </div>
                    <div class="cl"></div><br /><br />
                    <br /><br />
                    </form>
                ';
                

                

                    /* Vestigial code from the KPI system
                      if($num_items) $str .= "<div style=\"font-weight: bold; font-size: 20px;\">Select a $subject_type for $title:</div><br />";
                      $sql = "SELECT check_date_time,
                      concat(users.name, ' ', users.surname) as `subject`, concat(users2.name, ' ', users2.surname) as `assessor`,
                      compliance.category_id as `ccat`
                      FROM `compliance_checks`
                      left join users on users.id = compliance_checks.subject_id
                      left join users2 on users2.id = compliance_checks.assessor_id
                      left join compliance on compliance.id = compliance_checks.compliance_id
                      where compliance_id = $report_id and compliance_checks.status_id != 525
                      order by check_date_time DESC LIMIT 1
                      ";
                      if($result2 = $this->dbi->query($sql)) {
                      if($myrow2 = $result2->fetch_assoc()) {
                      if(Date("m-Y", strtotime($myrow2['check_date_time'])) == Date("m-Y") && $myrow2['ccat'] == 2010) $str .= "<h3 style=\"color: red;\">NOTE: A KPI for " . $myrow2['subject'] . " has already been performed this month.</h3><br />";
                      }
                      }
                     */




                    if ($num_items) {
                        $str .= "<div style=\"font-weight: bold; font-size: 20px;\">Select a $subject_type for $title:</div><br />";
                        while ($myrow = $result->fetch_assoc()) {
                            $user_id = $myrow['user_id'];
                            $name = $myrow['name'];
                            $client_id = $myrow['client_id'];
                            $employee_id = $myrow['employee_id'];
                            $surname = $myrow['sname'];

                            $userDivisionId = $myrow['division_ids'];
                            $complianceDivisionId = $this->getComplianceDivisions($report_id);

                            $validDivisionId = $this->checkValidComplianceUserDivisionId($complianceDivisionId, $userDivisionId);

                            if ($user_id && $validDivisionId) {
                                $sql = "SELECT check_date_time, concat(users.name, ' ', users.surname) as `assessor`
                        FROM `compliance_checks`
                        left join users on users.id = compliance_checks.assessor_id
                        where compliance_id = $report_id and compliance_checks.status_id != 525 and compliance_checks.subject_id = '$user_id'
                        order by check_date_time DESC LIMIT 1
                ";
                                if ($result2 = $this->dbi->query($sql)) {
                                    if ($myrow2 = $result2->fetch_assoc()) {
                                        $perform_xtra = "<span style=\"font-size: 10pt;\">(Last Performed By " . $myrow2['assessor'] . ": " . Date("d-M-Y", strtotime($myrow2['check_date_time'])) . ")</span>";
                                        if (Date("m-Y", strtotime($myrow2['check_date_time'])) == Date("m-Y") && $myrow2['ccat'] == 2010)
                                            $str .= "<h3 style=\"color: red;\">NOTE: A KPI for " . $myrow2['subject'] . " has already been performed this month.</h3><br />";
                                    } else {
                                        $perform_xtra = "";
                                    }
                                }
                                $str .= "<a class=\"subject_a\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_id=$report_id&site_id=$user_id\">$employee_id $name $surname $perform_xtra</a>";
                            }
                        }
                    }
                } else {
                    if ($site_id) {
                        $sql = "insert into compliance_checks (compliance_id, assessor_id, subject_id, check_date_time, last_modified, status_id) values ($report_id, " . $_SESSION['user_id'] . ", $site_id, '$curr_date_time', '$curr_date_time', 522)";
                        //$str .= "<h3>$sql</h3>";
                        $result = $this->dbi->query($sql);
                        //print_r($this->dbi->error);
                        $check_id = $this->dbi->insert_id;
                        $redirect_str = $this->f3->get('main_folder') . 'Compliance?report_edit_id=' . $check_id . ($prefill ? "&prefill=$prefill" : "");
                        $redirect_str .= ($job_application_id ? "&job_application_id=$job_application_id" : ($job_application2_id ? "&job_application2_id=$job_application2_id" : ($request_id ? "&request_id=$request_id" : "")));
                        //$str .= $redirect_str;
                        $str .= $this->redirect($redirect_str);
                    } else {
                        if ($report_id && $report_id != "ALL" && !($subject_id))
                            $str .= "<div style=\"margin-top: 50px; margin-bottom: 50px;\"><a class=\"mobile_font\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_id=$report_id&new_report=1\">Start a New Report for $title</a></div>";
                        if ($_POST['hdnChangeItem']) {
                            $sql = "update compliance_checks set status_id = " . $_POST['hdnChangeStatus'] . " where id = " . $_POST['hdnChangeItem'];
                            $result = $this->dbi->query($sql);
                        }
                        $sql = "select id, sort_order, value, item_name from lookup_fields where lookup_id in (select id from lookups where item_name = 'compliance_status') order by sort_order";
                        if ($result = $this->dbi->query($sql)) {
                            $stat_xtra = "";
                            $stat_xtra2 = "";
                            while ($myrow = $result->fetch_assoc()) {
                                $str_tmp = "CONCAT('<input type=\"button\" class=\"cs_button\" style=\"font-size: $standard_font !important;\" value=\"" . $myrow['value'] . "\" onClick=\"change_status(', compliance_checks.id, ', " . $myrow['id'] . ", \'" . $myrow['item_name'] . "\')\" />')";
                                //if($myrow['sort_order'] != 20) $stat_str = $str_tmp;
                                if ($myrow['sort_order'] != 20) {
                                    $stat_str .= "$stat_xtra $str_tmp";
                                    $stat_xtra = ", ";
                                }
                                if ($myrow['sort_order'] != 10) {
                                    $stat_str2 .= "$stat_xtra2 $str_tmp";
                                    $stat_xtra2 = ", ";
                                }
                            }
                        }
                        $stat_str = "
                          if(lookup_fields1.item_name = 'Pending',
                          CONCAT($stat_str2),
                          CONCAT($stat_str))
                          as `Change Status`,
            ";
                        if ($report_id == "ALL") {

                            $view_details->title = "Checklists Overview";
                        } else {
                            $view_details->title = "Checklists $for_by $title";
                        }
                        if ($subject_id || $auditor_id) {
                            $title_str = " compliance.title as `Title`, ";
                        }
                        if ($is_manager || $all_access) {
                            $mgr_xtra = "or compliance.id in (select foreign_id from lookup_answers where table_assoc = 'compliance')";
                        }
                        if ($report_id == "ALL") {
                            $title_xtra = "compliance.title as `Title`, ";
                            $filter .= " and compliance_checks.percent_complete != 0 ";
                            $reloadLocation = "<script>setTimeout(function () {
            location.reload();
        }, 30000);</script>";
                        }
                        if ($txtComplianceFilter)
                            $where_cond .= " and (compliance_check_answers.answer LIKE '%$txtComplianceFilter%' or compliance_check_answers.additional_text LIKE '%$txtComplianceFilter%' or users2.name like '%$txtComplianceFilter%') ";
                        $where_cond .= " and compliance.title not like '%job application%' ";

                        //company division query
                        $where_cond .= ($company_ids ? " and users2.id in (select user_id from users_user_groups where user_group_id in ($company_ids)) " : "");

                        if ($is_client) {
                            $view_details->sql = "
                select 
                compliance_checks.id as idin,
                CONCAT('<a target=\"_blank\" uk-tooltip=\"title: View as PDF\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "CompliancePDF/', compliance_checks.id, '\">PDF</a>') as `***`,
                CONCAT('<a uk-tooltip=\"title: View This Report Online\" class=\"list_a\" target=\"_blank\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_view_id=', compliance_checks.id, '\">View Online</a>') as `View`,
                CONCAT(if(users.employee_id != '', users.employee_id, ''), ' ', if(users.client_id != '', users.client_id, ''), ' ', users.name, ' ', users.surname) as `Assessor`,
                $title_xtra
                CONCAT(users2.name, ' ', users2.surname) as `Site`,
                CONCAT('<nobr>', DATE_FORMAT(compliance_checks.check_date_time, '%d-%b-%Y'), '</nobr>') as `Date`, $title_str lookup_fields1.item_name as `Status`,
                if(compliance_checks.total_out_of = 0, 'N/A', CONCAT((select sum(value) from compliance_check_answers where compliance_check_id = compliance_checks.id), '/', compliance_checks.total_out_of)) as `Score`
                FROM compliance_checks
                left join lookup_fields1 on lookup_fields1.id = compliance_checks.status_id
                left join compliance on compliance.id = compliance_checks.compliance_id
                left join users on users.id = compliance_checks.assessor_id
                left join users2 on users2.id = compliance_checks.subject_id
                left join compliance_check_answers on compliance_check_answers.compliance_check_id = compliance_checks.id
                where users2.id = $subject_id and compliance.min_user_level_id <= " . $_SESSION['u_level'] . " and compliance.is_active = 1 and compliance.category_id not in (select id from lookup_fields where item_name LIKE '%request%')
                $filter
                " . ($txtComplianceFilter ? " and (compliance_check_answers.answer LIKE '%$txtComplianceFilter%' or compliance_check_answers.additional_text LIKE '%$txtComplianceFilter%' or users2.name like '%$txtComplianceFilter%') " : "") . "
                or users2.id in (select child_user_id from associations where association_type_id = 1 and parent_user_id = $subject_id)
                $filter
                
                group by compliance_checks.id
                order by check_date_time desc
              ";
                            //return "<textarea>{$view_details->sql}</textarea>";
                        } else {
                            $view_details->sql = "
                select 
                compliance_checks.id as idin,
                CONCAT('<a uk-tooltip=\"title: View Online\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_view_id=', compliance_checks.id, '\">View</a>', '<a uk-tooltip=\"title: View as PDF\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "CompliancePDF/', compliance_checks.id, '\">PDF</a><br /><a uk-tooltip=\"title: Upload/View Attachemts\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Resources?current_dir=compliance&current_subdir=', compliance_checks.id, '\">Attachments...</a>
                <br /><a uk-tooltip=\"title: Add Notes\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Page/ComplianceCheckNotes?compliance_check_id=', compliance_checks.id, '\">Notes...</a>',
                '<a uk-tooltip=\"title: Add Notes\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "ComplianceFollowup?compliance_check_id=', compliance_checks.id, '\">Resolve Issues...</a>') as `***`,
                if((lookup_fields1.item_name = 'Pending' and users.id = " . $_SESSION['user_id'] . ") or (lookup_fields1.item_name = 'Pending' and compliance.allow_sharing = 1),
                CONCAT('<a uk-tooltip=\"title: Edit Checklist\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_edit_id=', compliance_checks.id, '\">Edit</a>'),
                '<span class=\"list_a\"><strike>Edit</strike></span>')
                as `**`,
                CONCAT(if(users.employee_id != '', users.employee_id, ''), ' ', if(users.client_id != '', users.client_id, ''), '<br />', users.name, ' ', users.surname) as `Assessor`,                CONCAT(if(users2.employee_id != '', users2.employee_id, ''), ' ', if(users2.client_id != '', users2.client_id, ''), '<br />', users2.name, ' ', users2.surname) as `Subject`, $title_xtra
                CONCAT('<nobr>', DATE_FORMAT(compliance_checks.check_date_time, '%d-%b-%Y'), '</nobr>') as `Date`, $title_str concat(lookup_fields1.item_name, '<br />', compliance_checks.percent_complete, '% Done') as `Status`, $stat_str
                compliance_checks.id as `ID`,
                if((lookup_fields1.item_name = 'Pending' and users.id = " . $_SESSION['user_id'] . ") or (lookup_fields1.item_name = 'Pending' and compliance.allow_sharing = 1),
                CONCAT('<a uk-tooltip=\"title: Edit Checklist\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_edit_id=', compliance_checks.id, '\">Edit</a>'),
                '<span class=\"list_a\"><strike>Edit</strike></span>')
                as `** `,
                if(compliance_checks.total_out_of = 0, 'N/A', CONCAT((select sum(value) from compliance_check_answers where compliance_check_id = compliance_checks.id), '/', compliance_checks.total_out_of)) as `Score`
                FROM compliance_checks
                left join lookup_fields1 on lookup_fields1.id = compliance_checks.status_id
                left join compliance on compliance.id = compliance_checks.compliance_id
                left join users on users.id = compliance_checks.assessor_id
                left join users2 on users2.id = compliance_checks.subject_id
                left join compliance_check_answers on compliance_check_answers.compliance_check_id = compliance_checks.id
                $where_cond
                $filter
                and compliance.min_user_level_id <= " . $_SESSION['u_level'] . " and compliance.is_active = 1
                and (compliance_checks.subject_id in (select user_id from compliance_auditors_subjects where compliance_auditor_id in (select id from compliance_auditors where user_id = " . $_SESSION['user_id'] . "))
                $mgr_xtra)
                
                group by compliance_checks.id
                order by check_date_time desc
              ";
                        }
                    }
                }
            } else if ($report_edit_id) {
                $txt_itm = new input_item;
                $str .= $txt_itm->setup_ti2();
                //if($detect->isTablet() || $detect->isMobile()) {
                $str .= $txt_itm->setup_clm();
                //} else {
//          $str .= $txt_itm->setup_cal();
                //}
                $str .= '
        <input type="hidden" id="hdnSubmitReport" name="hdnSubmitReport" />
        <input type="hidden" id="hdnCompleteReport" name="hdnCompleteReport" />
        ';
                //$str .= $this->f3->get('download_folder') . "compliance/$compliance_id";
                if (file_exists($this->f3->get('download_folder') . "compliance/$compliance_id") && !$is_induction) {
                    $str .= '<iframe width="100%" height="180" src="' . $this->f3->get('main_folder') . 'Resources?show_min=1&current_dir=compliance&current_subdir=downloads' . urlencode('/') . $compliance_id . '" frameborder="0"></iframe>';
                }
                if (!$is_survey && !$is_induction && !$is_request && !$is_form) {
                    $str .= ((!$job_application_id && !$job_application2_id && !$request_id && !$is_induction && !$is_survey && !$is_request && !$is_form) ? '<input onClick="submit_report(2)" class="save_button save" type="button" value="Save For Later" />' : "");
                    $str .= '<input onClick="complete_report(2)" class="save_button complete" type="button" value="' . ($job_application_id || $job_application2_id || $request_id || $is_induction || $is_survey || $is_request || $is_form ? 'Save and Review >>' : 'Save and Complete') . '"/>';
                }
                $x = 0;
                $prefix = 0;
                $postfix = 0;
                $js_arr = "var groups = [0";
                $hidden_started = 0;

                $preitems['[fullname]'] = $assessor_fullname;
                $preitems['[phone]'] = $assessor_phone;
                $preitems['[address]'] = $assessor_address;
                $preitems['[today]'] = date("d-M-Y");

                $sql = "select id, question_title, question_type, answer, choices_per_row, parent from compliance_questions
                where compliance_id = (select compliance_id from compliance_checks where id = $report_edit_id)
                order by sort_order";
                if ($result = $this->dbi->query($sql)) {
                    $txt_count = 0;
                    while ($myrow = $result->fetch_assoc()) {
                        $x++;
                        $qid = $myrow['id'];
                        $question_title = nl2br($myrow['question_title']);
                        $question_type = $myrow['question_type'];
                        $question_answer = nl2br($myrow['answer']);
                        $parent = $myrow['parent'];
                        if ($old_parent && $old_parent != $parent) {
                            $str .= "</div>";
                            $hidden_started = 0;
                        }
                        if ($parent && $old_parent != $parent) {
                            $hidden_started = 1;
                            $str .= '<div style="display: none; border: 3px solid !important;" name="child' . $parent . '" id="child' . $parent . '"><div id="child_header' . $parent . '" style="border-bottom: 3px solid; background-color: #FFFFEE; padding: 5px;"></div>';
                        }
                        $choices_per_row = $myrow['choices_per_row'];
                        if (!$choices_per_row) {
                            $choices_per_row = 2;
                        }
                        $text_height = ($choices_per_row * $height_multiplier) . "px";
                        if ($question_type == "lbl" || $question_type == "ins") {
                            if ($question_type == "lbl") {
                                if ($x > 1)
                                    $prefix++;
                                $postfix = 0;
                                $str .= "<input type=\"hidden\" name=\"lbl_$qid\" value=\"LBL\" /><div class=\"q_lbl\">$prefix.0 $question_title</div>";
                            } else {
                                $str .= "<div class=\"instructions\">$question_title</div>";
                            }
                        } else {
                            $postfix++;
                            $str .= '<div class="question_area">';
                            $str .= "<div class=\"compliance_question\">$prefix.$postfix. $question_title</div>";
                            if ($question_answer && substr($question_answer, 0, 1) != '[') {
                                if (!$show_answers)
                                    $str .= '<div onClick="show_hide_answer(' . $qid . ');" class="compliance_answer_button" id="compliance_answer_button' . $qid . '"></div>';
                                $str .= '<div class="cl"></div>
                  <div ' . ($show_answers ? "" : 'style="display: none;"') . ' class="compliance_auditor_answer" id="auditor_answer' . $qid . '">' . $question_answer . '</div>';
                            }
                            $str .= '<div class="cl"></div>';
                        }
                        if ($question_type == "txt" || $question_type == "cal" || $question_type == "tim" || $question_type == "clm") {
                            if ($question_type == "txt") {
                                $txt_count++;
                            }
                            $sql = "select answer from compliance_check_answers where compliance_check_id = $report_edit_id and question_id = $qid;";
                            $result3 = $this->dbi->query($sql);
                            if ($myrow2 = $result3->fetch_assoc()) {
                                $tmp_answer = $myrow2['answer'];
                            } else {
                                $tmp_answer = ($x == 1 && $prefill ? $prefill : "");
                            }
                            if (substr($question_answer, 0, 1) == '[') {
                                //$tmp_answer = $preitems[substr($question_answer, 1, strlen($question_answer) - 2)];
                                $tmp_answer = $preitems[$question_answer];
                            }


                            if ($question_type == "txt") {
                                $str .= ($choices_per_row > 1 ? "<textarea style=\"height: $text_height;\" name=\"txt_$qid\" class=\"compliance_text_box\">$tmp_answer</textarea>" : "<input type=\"text\" style=\"height: $text_height;\" name=\"txt_$qid\" class=\"compliance_text_box\" value=\"$tmp_answer\" />");
                            } else if ($question_type == "tim") {
                                $str .= $txt_itm->ti2("txt_$qid", "", ' class="compliance_text_box" value="' . $tmp_answer . '" ', "", "", "");
                            } else if ($question_type == "cal") {
                                $str .= $txt_itm->cal("txt_$qid", "$tmp_answer", ' class="compliance_text_box" ', "", "", "");
                            } else if ($question_type == "clm") {
                                $str .= $txt_itm->clm("txt_$qid", "$tmp_answer", ' class="compliance_text_box" ', "", "", "");
                            }
                        } else if ($question_type == "opt" || $question_type == "chl") {
                            $y = 0;
                            $z = 0;
                            $done = 0;
                            $sql = "select compliance_question_choices.id as `choice_id`, compliance_question_choices.choice_value, compliance_question_choices.choice, compliance_question_choices.additional_text_required, lookup_fields1.value as `colour`
                      from compliance_question_choices
                      left join lookup_fields1 on lookup_fields1.id = compliance_question_choices.colour_scheme_id
                      where compliance_question_choices.compliance_question_id = $qid
                      order by compliance_question_choices.sort_order
                     ";

                            $from_further = 0;
                            $sql_test_further = "select additional_text_required from compliance_question_choices where compliance_question_id = $qid and additional_text_required REGEXP '^-?[0-9]+$'";
                            if ($result_further = $this->dbi->query($sql_test_further)) {
                                if ($result_further->num_rows)
                                    $from_further = 1;
                            }


                            if ($result2 = $this->dbi->query($sql)) {
                                $num_of = $result2->num_rows;
                                while ($myrow2 = $result2->fetch_assoc()) {
                                    $y++;
                                    $choice = $myrow2['choice'];
                                    $choice_id = $myrow2['choice_id'];
                                    $choice_value = $myrow2['choice_value'];
                                    if (!$choice_value)
                                        $choice_value = 0;
                                    $additional_text_required = $myrow2['additional_text_required'];
                                    if (is_numeric($additional_text_required)) {
                                        if (!$done)
                                            $group++;
                                        $js_arr .= ", $group";
                                        $done = 1;
                                    }
                                    $colour = $myrow2['colour'];
                                    if ($question_type == "opt") {
                                        $str .= '<input class="opt" type="radio" id="opt_' . $qid . '_' . $y . '" name="opt_' . $qid . '" value="' . $qid . ';*;' . $additional_text_required . ';*;' . $question_title . ';*;' . $choice . ';*;' . $choice_value . ';*;' . $choice_id . ';*;' . $colour . '" />';
                                        $update_txt = ($additional_text_required ? "update_txt(" . $qid . "," . $y . ",ys" . $qid . ");" : "");
                                    } else {
                                        $str .= '<input class="opt" type="checkbox" id="chk_' . $qid . '_' . $y . '" name="chk_' . $qid . '_' . $y . '" value="' . $qid . ';*;' . $question_title . ';*;' . $choice . ';*;' . $choice_value . ';*;' . $choice_id . ';*;' . $colour . '" />';
                                        $update_txt = "";
                                    }
                                    $str .= "\n\n<div id=\"div$qid$y\" onClick=\"$update_txt toggle_$question_type('$qid',$y,$num_of,'$colour','$additional_text_required','$choice'" . ($question_type == 'opt' && $from_further ? ", 0, 1" : "") . ");\" class=\"cb c$choices_per_row\">$choice</div>";
                                    $sql = "select answer, additional_text from compliance_check_answers where compliance_check_id = $report_edit_id and question_id = $qid and answer_id = $choice_id;";
                                    if ($result3 = $this->dbi->query($sql)) {
                                        if ($myrow3 = $result3->fetch_assoc()) {
                                            $js_str .= "\ntoggle_$question_type('$qid', $y, $num_of, '$colour', '$additional_text_required', '$choice', 1);";
                                            $text_additional = ($myrow3['additional_text'] ? $myrow3['additional_text'] : "");
                                        } else {
                                            $text_additional = "";
                                        }
                                    }
                                    if ($additional_text_required) {
                                        if (strpos($additional_text_required, "::") !== false) {
                                            $components = explode("::", $additional_text_required);
                                            $additional_text_required = $components[0];
                                            if (is_numeric($components[1])) {
                                                $additional_height = $components[1] * 18;
                                                $itm_type = "txa";
                                            } else {
                                                $itm_type = $components[1];
                                                $additional_height = 25;
                                            }
                                        } else {
                                            $itm_type = "txa";
                                            $additional_height = 100;
                                        }
                                        $z++;
                                        if ($z == 1) {
                                            $js_text .= "\nys$qid = [];";
                                        }
                                        $js_text .= "\nys$qid" . "[" . ($z - 1) . "]=$y;";
                                        $add_text .= "<div class=\"additional_box\" id=\"cont$qid$y\"><div class=\"cl\"></div><span class=\"compliance_question\">$additional_text_required</span><br />";
                                        $add_text .= $itm->$itm_type("txt_$qid" . "_$y", $text_additional, '  style="height: ' . $additional_height . 'px;" class="compliance_text_box" ', "", "", "", "") . "</div>";
                                    }
                                }
                            }
                            if ($add_text) {
                                $str .= $add_text;
                                $add_text = "";
                            }
                        } else if ($question_type == "sig") {

                            $sig = new draw();
                            $sig->width = 800;
                            $sig->height = 240;
                            $sig->target_dir = "images/compliance/$report_edit_id";
                            $target_dir = $this->f3->get('download_folder') . "images/compliance/$report_edit_id";
                            $sig->target_dir2 = $target_dir;

                            if (!file_exists($target_dir)) {
                                mkdir($target_dir, 0777, true);
                                // $myfile = fopen($target_dir."/sig.png", "w");
                                // mkdir($target_dir."sig.png",0755,true);
                                //chmod($target_dir, 0755);
                            }

                            $sig->file_name = "sig.png";

                            $str .= $sig->display_canvas();
//              echo $str;
//              die;
                            $str .= "<input type=\"hidden\" value=\"AS1gna7ure1ma6e\" name=\"sig_$qid\" />";
                        }
                        $str .= "<div class=\"cl\"></div>";
                        $target_dir = $this->f3->get('download_folder') . "images/compliance/$report_edit_id";
                        if ($question_type != "lbl" && $question_type != "ins") {
                            if ($job_application_id || $is_survey || $is_induction) {
                                $str .= "<br />";
                            } else {
                                $str .= '<div onClick="show_hide_img(' . $qid . ',\'' . $target_dir . '\');" class="img_btn" id="img_btn' . $qid . '"></div><div class="cl"></div><div class="img_uploader" id="img_uploader' . $qid . '">
            <iframe id="photo_upload' . $qid . '" frameborder="0" width="100%" height="500px;"></iframe>
            </div>';
                                if (count(glob("$target_dir/$qid/*"))) {
                                    $str .= '<script>show_hide_img(' . $qid . ',\'' . $target_dir . '\');</script>';
                                }
                            }
                            $str .= '<div class="cl"></div>';
                            $str .= "</div>";
                        }
                        $old_parent = $parent;
                    }
                }
                if ($hidden_started)
                    $str .= "</div>";
                $js_arr .= "];\n";
                $str .= '
              <script>
                ' . $js_arr . '
                ' . $js_text . '
                function update_txt(qid,y,ys) {
                  if(ys.length > 1) {
                    var change_to = ""
                    for(x = 0; x < ys.length; x++) {
                      tst = x + 1
                      if(document.getElementsByName("txt_"+qid+"_"+tst)[0].value) {
                        change_to = document.getElementsByName("txt_"+qid+"_"+tst)[0].value;
                      }
                      if(tst != y) {
                        document.getElementsByName("txt_"+qid+"_"+tst)[0].value = "";
                      }
                    }
                    for(x = 0; x < ys.length; x++) {
                      tst = x + 1
                      if(tst == y) {
                        document.getElementsByName("txt_"+qid+"_"+tst)[0].value = change_to;
                      } else {
                        document.getElementsByName("txt_"+qid+"_"+tst)[0].value = "";
                      }
                    }
                  }
                }
              </script>
        ';
                $str .= ((!$job_application_id && !$job_application2_id && !$request_id && !$is_survey && !$is_induction && !$is_request && !$is_form) ? '<input onClick="submit_report(2)" class="save_button save" type="button" value="Save For Later" />' : "");
                $str .= '<input onClick="complete_report(2)" class="save_button complete" type="button" value="' . ($job_application_id || $job_application2_id || $request_id ? 'Save and Review >>' : ($is_induction ? 'Complete Induction' : ($is_request || $is_form ? 'Save Form' : 'Save and Complete'))) . '"/>';
                $target_dir = "uploads/compliance/$report_edit_id";
            } else if ($report_view_id) {

                $compliance_obj = new compliance;

                if ($is_induction) {
                    $induction_obj = new UserController();
                    $compliance_obj->title = " ";
                    $str .= $induction_obj->induction(1);
                    $str .= "<h3>Induction Results</h3>";
                }

                if ($job_application_id || $job_application2_id || $request_id || $form_id)
                    $compliance_obj->title = "Please review your answers below and click \"Complete Application\" when finished...";
                $compliance_obj->dbi = $this->dbi;

                $compliance_obj->title = ($form_message ? ($is_survey ? "Survey Results" : ($is_induction ? "Induction Results" : ($is_request || $is_form ? "Form Completed..." : ""))) : "");
                $compliance_obj->compliance_check_id = $report_view_id;
                $compliance_obj->display_results();
                $str .= $compliance_obj->str;
                if (($is_request || $is_form || $is_survey || $is_prestart) && $form_message) {
                    $str .= '<div class="inline_message">';
                    if ($is_request) {
                        $time_in15 = date("g:ia", strtotime(date("Y-m-d H:i:s") . " +15 minutes"));
                        $str .= 'The request will be sent at ' . $time_in15 . ' (15 minutes after the request was saved).';
                    } else if ($is_form) {
                        $str .= "The form has been submitted...";
                    } else {
                        $str .= "The survey has been submitted...";
                    }

                    $str .= '<br /><br />Are any changes needed to this form? <br /><br />
          <a class="list_a" href="' . $this->f3->get('main_folder') . 'Compliance?report_edit_id=' . $report_view_id . '">Click Here to Make Changes</a> 
          <a class="list_a" target=\"_blank\" href="' . $this->f3->get('main_folder') . 'CompliancePDF/' . $report_view_id . '">View PDF</a><br/><br/>
          <a class="list_a" href="' . $this->f3->get('main_folder') . '">&lt;&lt; Back to Dashboard</a></div>';

                    //curr_date_time
                }
                if ($job_application_id || $job_application2_id || $request_id || $form_id) {
                    $str .= "<p><a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_edit_id=$report_view_id&" . ($job_application_id ? "job_application_id" : ($request_id ? "request_id" : "job_application2_id")) . "=" .
                            ($job_application_id ? $job_application_id : ($request_id ? $request_id : $job_application2_id)) . "\"><< Go Back to Answering Questions</a>      " .
                            ($job_application_id || $request_id ? (!$complete_application ? "<a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_view_id=$report_view_id&job_application_id=$job_application_id&request_id=$request_id&complete_application=1\">Complete Application >></a></p>" : "") : "<a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "JobApplications?more_info=$job_application2_id\">View Outcome >></a></p>");
                }
            } else if ($survey_mode) {
                $view_details->title = "Surveys";
                $view_details->show_num_records = 0;
                $view_details->sql = "
              select compliance.id as idin, compliance.title as `Title`, closing_date as `Closing Date`,
              if(compliance.id in (select compliance_id from compliance_checks where subject_id = " . $_SESSION['user_id'] . "),
              CONCAT('<a target=\"_top\" class=\"list_a\" uk-tooltip=\"title: Continue Answering the Survey Questions\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_edit_id=', compliance_checks.id, '\">Continue Survey...</a>'),
              CONCAT('<a target=\"_top\" class=\"list_a\" uk-tooltip=\"title: Start Answering the Survey\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_id=', compliance.id, '&site_id=" . $_SESSION['user_id'] . "\">Start Survey...</a>')
              ) as `***`
              FROM compliance
              left join compliance_checks on compliance_checks.compliance_id = compliance.id 
              where
              if(compliance.id in (select compliance_id from compliance_checks where subject_id = " . $_SESSION['user_id'] . "),
              subject_id = " . $_SESSION['user_id'] . ", '1 = 1')
              and
              compliance.category_id in (select id from lookup_fields where item_name = 'Survey') and closing_date >= now()
              group by compliance_checks.compliance_id
              order by title
          ";
            } else if ($survey_results) {
                $survey_summary = (isset($_GET['survey_summary']) ? $_GET['survey_summary'] : null);
                $view_details->title = "Survey Results";
                $view_details->show_num_records = 0;
                if ($survey_results == 1) {
                    $sql = "select id FROM compliance where category_id in (select id from lookup_fields where item_name = 'Survey')";
                    $result = $this->dbi->query($sql);
                    if (!$result->num_rows)
                        $str .= "<h3>There are currently no surveys.</h3>";

                    $view_details->sql = "
                select compliance.id as idin, compliance.title as `Title`, closing_date as `Closing Date`,
                CONCAT('<a target=\"_top\" class=\"list_a\" uk-tooltip=\"title: Show the general results of this survey\" href=\"" . $this->f3->get('main_folder') . "Compliance?survey_results=', compliance.id, '\">Overall Results</a>',
                '<a target=\"_top\" class=\"list_a\" uk-tooltip=\"title: Show the results of this survey by individual question\" href=\"" . $this->f3->get('main_folder') . "Compliance?survey_summary=1&survey_results=', compliance.id, '\">Results By Question</a>
                <a target=\"_top\" class=\"list_a\" uk-tooltip=\"title: Show the results of this survey by individual question\" href=\"" . $this->f3->get('main_folder') . "Compliance?survey_comments=1&survey_results=', compliance.id, '\">Answers to Questions</a>')
                as `***`
                FROM compliance
                left join compliance_checks on compliance_checks.compliance_id = compliance.id 
                where
                compliance.category_id in (select id from lookup_fields where item_name = 'Survey')
                group by compliance.id
                order by title
            ";
                } else {
                    if ($survey_summary) {
                        $view_details->sql = "
              select compliance_check_answers.question as `Question`, CONCAT(round((sum(compliance_check_answers.value)/sum(compliance_check_answers.out_of)) * 100), '%') as `Average Score`
                    from compliance_check_answers
                    left join compliance_checks on compliance_checks.id = compliance_check_answers.compliance_check_id
                    left join compliance on compliance.id = compliance_checks.compliance_id
                    left join compliance_questions on compliance_questions.id = compliance_check_answers.question_id
                    where compliance.id = $survey_results
                    and compliance_checks.status_id != 525
                    and compliance_questions.question_type != 'lbl' and compliance_questions.question_type != 'txt'
                    group by compliance_check_answers.question
            ";
                    } else if ($survey_comments) {
                        $view_details->sql = "
              select compliance_check_answers.question as `Question`, REPLACE(compliance_check_answers.additional_text, '\n', '<br />') as `Comment`, REPLACE(compliance_check_answers.answer, '\n', '<br />') as `Answer`
                    from compliance_check_answers
                    left join compliance_checks on compliance_checks.id = compliance_check_answers.compliance_check_id
                    left join compliance on compliance.id = compliance_checks.compliance_id
                    left join compliance_questions on compliance_questions.id = compliance_check_answers.question_id
                    where compliance.id = $survey_results
                    and compliance_checks.status_id != 525
                    and compliance_questions.question_type != 'lbl'
                    and (compliance_check_answers.answer not in (select distinct(choice) from compliance_question_choices where compliance_question_id in (select id from compliance_questions where compliance_id = $survey_results))
                    or compliance_check_answers.additional_text != '')
            ";
                    } else {
                        $sql = "
              select compliance_checks.id, compliance.title, CONCAT(users.name, ' ', users.surname) as `subject`, states.item_name as `state_name`,
                    users.id as `subject_id`, compliance_checks.total_out_of, compliance_checks.check_date_time, compliance_checks.percent_complete
                    from compliance_checks
                    left join users on users.id = compliance_checks.subject_id
                    left join compliance on compliance.id = compliance_checks.compliance_id
                    left join states on states.id = users.state
                    where compliance.id = $survey_results
                    and compliance_checks.status_id != 525
                    order by compliance_checks.check_date_time DESC
            ";
                        if ($result = $this->dbi->query($sql)) {
                            while ($myrow = $result->fetch_assoc()) {
                                $scount++;
                                $ccid = $myrow['id'];
                                $subject = $myrow['subject'];
                                $subject_id = $myrow['subject_id'];
                                $assessor = $myrow['assessor'];
                                $title = $myrow['title'];
                                $state_name = $myrow['state_name'];
                                $check_date = Date("d-M-Y", strtotime($myrow['check_date_time']));
                                $out_of = $myrow['total_out_of'];
                                $percent_complete = $myrow['percent_complete'];
                                $sql = "select (sum(value)/$out_of)*100 as `score` from compliance_check_answers where compliance_check_id = $ccid";
                                if ($result2 = $this->dbi->query($sql)) {
                                    if ($myrow2 = $result2->fetch_assoc()) {
                                        $score = round($myrow2['score']) . "%";
                                    }
                                    $total_score += $score;
                                    if ($percent_complete > 10)
                                        $ccount++;
                                    if ($scount == 1) {
                                        $str .= "<h3>Results for $title</h3>";
                                        $str .= "<table class=\"grid\"><tr><th>Date</th><th>% Done</th><th>By</th><th>State</th><th>Score</th><th>View</th></tr>";
                                    }
                                    $str .= "<tr><td>$check_date</td><td>$percent_complete</td>
                  <td>$subject</td><td>$state_name</td><td>$score</td><td><a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_view_id=$ccid\">View</a></td></tr>";
                                }
                                $avg = round($total_score / $ccount);
                                $str .= "<tr><th colspan=\"4\" style=\"text-align: right !important;\">Average Score: </th><th colspan=\"2\">$avg%</td></tr>";
                                $str .= "</table>";
                            }
                        } else {
                            //$str .= "<h3>There are currently no survey results.</h3>";
                        }
                    }
                }
            } else {
                $search_str = (isset($_GET['compliance_search']) ? $_GET['compliance_search'] : null);
                $category_id = (isset($_GET['category_id']) ? $_GET['category_id'] : null);

                if ($_SESSION['u_level'] >= 300) {

                    /* For  get category  */
                    $sql = "select lookup_fields.id as idin, lookup_fields.item_name, lookup_fields.value from lookup_fields where lookup_fields.item_name NOT LIKE '%request%' and lookup_fields.item_name NOT LIKE '%survey%'  and lookup_fields.item_name NOT LIKE '%induction%' and lookup_id = 100 and id in (select category_id from compliance) order by sort_order, lookup_fields.item_name";

                    $str .= '<a class="list_a" ' . ($category_id ? '' : 'style="color: white !important; background-color: #242E48 !important;"') . ' href="Compliance">All Categories</a> ';

                    if ($result = $this->dbi->query($sql)) {
                        while ($myrow = $result->fetch_assoc()) {
                            $cat_id = $myrow['idin'];
                            if ($cat_id == $category_id) {
                                $style_xtra = "style=\"color: white !important; background-color: #242E48 !important;\"";
                                $heading = $myrow['item_name'];
                            } else {
                                $style_xtra = "";
                            }
                            $item_name = $myrow['item_name'];
                            $value = $myrow['value'];
                            $str .= '<a class="list_a" ' . $style_xtra . ' href="?category_id=' . $cat_id . '">' . $item_name . '</a> ';
                        }
                    }

                    if ($category_id || $search_str) {
                        if ($search_str) {
                            $filter_xtra = " and (compliance.title LIKE '%$search_str%' || compliance.description LIKE '%$search_str%') ";
                            $search_msg = "Filtered By: $search_str";
                        } else {
                            //compliance.title LIKE '%kpi%' || 
                            $filter_xtra = " and NOT(compliance.title LIKE '%performance appraisal%' || compliance.title LIKE '%request%' || compliance.title LIKE '%job app%') ";
                        }
                        if ($category_id) {
                            $filter_xtra .= " and compliance.category_id = $category_id";
                        }
                    }



                    $str .= '
          </form>
          <hr />
          <style>
            .selected_search {
              background-color: #FFFFEE;
            }
          </style>
          <form method="get" name="frmFollowSearch">

          <p class="fl input-group custom-search-form"  style="padding-right: 8px;"><input autocomplete="off" placeholder="Search for reports..." maxlength="70" name="compliance_search" id="search" type="text" class="form-control off-search"  style="padding: 2px !important; height: 32px !important;"  /><button onClick="this.form.submit()" name="cmdFollowSearch" value="Search" class="button_a" style="height: 38px;" /><span class="fa fa-search"></span></button></p>
          

          <!-- <span data-uk-search-icon class="search-icon"></span><input class="uk-search-input search-field" type="text" maxlength="70" placeholder="Search for reports..."  name="compliance_search" id="search" value="' . $search_str . '"> -->
                  
          ';

                    $str .= "  <b>Quick Search: </b>";
                    $sql = "select id, item_name, value from lookup_fields where lookup_id in (select id from lookups where item_name = 'compliance_keywords') order by sort_order, item_name";
                    if ($result = $this->dbi->query($sql)) {
                        while ($myrow = $result->fetch_assoc()) {
                            $keyword = $myrow['item_name'];
                            $value = $myrow['value'];
                            $str .= '<a class="list_a ' . ($keyword == $_GET['compliance_search'] ? 'selected_search' : '') . '" href="?compliance_search=' . $keyword . '">' . $value . '</a>';
                        }
                    }
                } else {
                    $str .= '<h3>Reporting</h3>';
                }

                /* $str .= '
                  <div class="fr"><a class="download_link" href="'.$this->f3->get('main_folder').'downloadFile?fl=' . urlencode($this->encrypt("resources/Guides")) . '&f=' . urlencode($this->encrypt(($search_str == "kpi" ? "KPI" : "Compliance System" . ".pdf"))) . '">Download User Guide</a></div>';


                  if($search_str != "kpi") {
                  $str .= '<div class="fr"><a class="download_link" href="'.$this->f3->get('main_folder').'downloadFile?fl=1FwKortBoY8ocRpVL9brjwvEG0p5pdtIJZ9Uf27ppZF1ahXTogvnd5A3wHLuvHnF%2BFeeaRZucaJhvkfqXlxde9&f=9Ndny5XKieFLoKeoyW9iUAE1pppVI7vSdNdZ7GDeAktLgbCzSfoaxDwxKGFA4nXNdKlT9dnIaHAOXGMde5VHqZ5Tb8NWqa%2F1QG%2Bfn1LiOur0ne1DjOTKLoo%2BW6iai5TRk%3D">Download Meeting Minutes Form</a></div>';
                  } */
                $str .= '<br /><br /></form><div class="cl"></div>';

                if ($search_str == "kpi") {
                    $view_details->title = "KPI";
                    $str .= "<b>Please Note:</b> The current month is " . date('F') . "; so KPI scores added now are for last month (" . date('F', strtotime("-1 month")) . ").<br /><br />";
                } else {
                    $view_details->title = $heading;
                }
                $is_admin = $this->f3->get('is_admin');

                $mgr_xtra = ($is_manager ? "or compliance.id in (select foreign_id from lookup_answers where table_assoc = 'compliance')" : "");
                $view_details->sql = "
              select compliance.id as idin,
              CONCAT('<a class=\"list_a\" uk-tooltip=\"title: Show Reports\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_id=', compliance.id, '\">Reports</a>') as `Reports`,
              CONCAT('<a class=\"list_a\" uk-tooltip=\"title: Start a New " . ($search_str == "kpi" ? "KPI" : "Report") . "\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_id=', compliance.id, '&new_report=1\">New " . ($search_str == "kpi" ? "KPI" : "Report") . "</a>') as `New`,
              CONCAT('<span style=\"color: ', IF(compliance.title = 'Vicinity KPI', 'blue', 'black'), '\">', compliance.title, '</span>') as `Title`
              
              " . ($this->f3->get('is_mobile') || $this->f3->get('is_tablet') || !$is_admin ? "" : "
              , lookup_fields2.item_name as `Category`,
              
              
              CONCAT('<a class=\"list_a\" uk-tooltip=\"title: Download the Template file in Excel here.\" style=\"width: 80px;\" href=\"" . $this->f3->get('main_folder') . "Compliance?template_id=', compliance.id, '&template_name=', compliance.title, '\">Template</a><a style=\"width: 80px;\" class=\"list_a\" uk-tooltip=\"title: View Summary\" href=\"" . $this->f3->get('main_folder') . "ComplianceResults?report_id=', compliance.id, '\">Summary</a><a style=\"width: 90px;\" class=\"list_a\" uk-tooltip=\"title: View Summary\" href=\"" . $this->f3->get('main_folder') . "Reporting/Results?cid=', compliance.id, '\">Results Table</a>') as `More`") . "
              FROM compliance
              left join lookup_fields2 on lookup_fields2.id = compliance.category_id
              where
              compliance.min_user_level_id <= " . $_SESSION['u_level'] . " and compliance.is_active = 1 and 
              ((compliance.id in (select compliance_id from compliance_auditors where user_id = " . $_SESSION['user_id'] . ")
              $mgr_xtra) or (compliance.allow_all_access = 1 and compliance.id in (select foreign_id from lookup_answers where table_assoc = 'compliance')))

              $filter_xtra
              
              order by title
        ";
            }
//      echo $view_details->sql;
//      die;
//                    //$str .= $this->ta($view_details->sql);
            die;
            $str .= $reloadLocation;
            $str .= ($view_details->sql ? $view_details->draw_list() : "");
            if ($survey_results != 1 && $survey_results) {
                $str .= "<a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Compliance?survey_results=1\"><< Back to Survey Results</a>";
                if ($survey_summary || $survey_comments)
                    $str .= "<a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Compliance?survey_results=$survey_results\">Overall Scores</a>";
                if (!$survey_summary)
                    $str .= "<a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Compliance?survey_results=$survey_results&survey_summary=1\">View By Question</a>";
                if (!$survey_comments)
                    $str .= "<a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Compliance?survey_results=$survey_results&survey_comments=1\">Answers to Question</a>";
            }
            if ($js_str)
                $str .= "<script type=\"text/javascript\">\n$js_str\n</script>";
        }
        return $str;
    }

    function ComplianceResults() {
        $report_id = (isset($_GET['report_id']) ? $_GET['report_id'] : null);
        $report_comments = (isset($_GET['report_comments']) ? $_GET['report_comments'] : null);
        $nav_month = (isset($_GET['selDateMonth']) ? $_GET['selDateMonth'] : null);
        $nav_year = (isset($_GET['selDateYear']) ? $_GET['selDateYear'] : null);
        $state = (isset($_GET['state']) ? $_GET['state'] : null);
        if ($report_id) {
            $report_summary = (isset($_GET['report_summary']) ? $_GET['report_summary'] : null);
            $this->list_obj->title = "Results";
            $this->list_obj->show_num_records = 0;
            $this->list_obj->sql = "";
            $nav = new navbar;
            if (!$nav_month) {
                $def_month = 1;
                $nav_month = date("m");
                $nav_year = date("Y");
            }
            $str .= '
          <script language="JavaScript">
            function report_filter() {
              document.getElementById("hdnReportFilter").value=1
              document.frmFilter.submit()
            }
          </script>
          </form>
          <form method="GET" name="frmFilter">
          <input type="hidden" name="hdnReportFilter" id="hdnReportFilter">
          <input type="hidden" name="report_id" id="edit_date" value="' . $report_id . '">
          <div class="form-wrapper">
          <div class="form-header">Filter</div>
          <div  style="padding: 10px;">
          ' . $nav->month_year(2015) . '
          <input onClick="report_filter()" type="button" value="Go" /> 
          </div>
          </div>
          </form>
          <style>
            .state_item {
              float: left;
              border: 2px solid black;
              padding: 0px;
              display: inline-block;
              margin-right: 10px;
              margin-top: 15px;
            }
            .state_item:hover {
              border-color: blue !important;
            }
            .state_a {
              padding-left: 12px;
              padding-right: 12px;
              padding-top: 6px;
              padding-bottom: 6px;
              display: inline-block;
            }
            .state_a:hover {
              background-color: #FFFFCC !important;
            }
            .state_selected {
              border: 2px solid red;
              background-color: #FFFFEE;
            }
          </style>
      ';
            if ($def_month) {
                $str .= '
          <script language="JavaScript">
            change_selDate()
          </script>
        ';
            }
            if ($nav_month > 0) {
                $nav1 = "and MONTH(compliance_checks.check_date_time) = $nav_month";
            } else {
                $nav_month = "ALL Months";
            }
            if ($nav_year > 0) {
                $nav2 = "and YEAR(compliance_checks.check_date_time) = $nav_year";
            } else {
                $nav_year = "ALL Years";
            }
            $qry = ($nav_month || $nav_year ? "&selDateMonth=$nav_month&selDateYear$nav_year" : "");
            $qry .= ($_GET['hdnReportFilter'] ? "&hdnReportFilter=" . $_GET['hdnReportFilter'] : "");
            $sql = "select id, item_name from states;";
            if ($result = $this->dbi->query($sql)) {
                $str .= '<div class="state_item' . (!$state ? ' state_selected' : "") . '"><a class="state_a" href="employment.php">ALL STATES</a></div>';
                $current_state = "";
                while ($myrow = $result->fetch_assoc()) {
                    $state_id = $myrow['id'];
                    $state_name = $myrow['item_name'];
                    $str .= '<div class="state_item' . ($state_id == $state ? ' state_selected' : "") . '"><a class="state_a" href="?report_id=' . $report_id . $qry . '&state=' . $state_id . '">' . $state_name . '</a></div>';
                    if ($state_id == $state)
                        $current_state = $state_name;
                }
                if (!$current_state)
                    $current_state = "ALL States";
            }
            $str .= '<div style="clear: both;"></div>';
            if ($report_summary) {
                $this->list_obj->sql = "
          select compliance_check_answers.question as `Question`, CONCAT(round((sum(compliance_check_answers.value)/sum(compliance_check_answers.out_of)) * 100), '%') as `Average Score`
                from compliance_check_answers
                left join compliance_checks on compliance_checks.id = compliance_check_answers.compliance_check_id
                left join compliance on compliance.id = compliance_checks.compliance_id
                left join compliance_questions on compliance_questions.id = compliance_check_answers.question_id
                where compliance.id = $report_id
                and compliance_checks.status_id != 525
                and compliance_questions.question_type != 'lbl' and compliance_questions.question_type != 'txt'
                group by compliance_check_answers.question
        ";
            } else if ($report_comments) {
                $this->list_obj->sql = "
          select compliance_check_answers.question as `Question`, REPLACE(compliance_check_answers.additional_text, '\n', '<br />') as `Comment`, REPLACE(compliance_check_answers.answer, '\n', '<br />') as `Answer`
                from compliance_check_answers
                left join compliance_checks on compliance_checks.id = compliance_check_answers.compliance_check_id
                left join compliance on compliance.id = compliance_checks.compliance_id
                left join compliance_questions on compliance_questions.id = compliance_check_answers.question_id
                where compliance.id = $report_id
                $nav1 $nav2
                and compliance_checks.status_id != 525
                and compliance_questions.question_type != 'lbl'
                and (compliance_check_answers.answer not in (select distinct(choice) from compliance_question_choices where compliance_question_id in (select id from compliance_questions where compliance_id = $report_id))
                or compliance_check_answers.additional_text != '')
        ";
            } else {
                $sql = "
          select compliance_checks.id, compliance.title, CONCAT(users.name, ' ', users.surname) as `subject`,
          CONCAT(if(users2.employee_id != '', users2.employee_id, ''), ' ', if(users2.client_id != '', users2.client_id, ''), ' ', users2.name, ' ', users2.surname) as `assessor`,
          states.item_name as `state_name`,
                users.id as `subject_id`, compliance_checks.total_out_of, compliance_checks.check_date_time, compliance_checks.percent_complete, compliance.hide_score, CONCAT('<a uk-tooltip=\"title: View as PDF\" class=\"list_a\" target=\"_blank\" href=\"" . $this->f3->get('main_folder') . "CompliancePDF/', compliance_checks.id, '\">PDF</a>') as `pdf`
                from compliance_checks
                left join users on users.id = compliance_checks.subject_id
                left join users2 on users2.id = compliance_checks.assessor_id
                left join compliance on compliance.id = compliance_checks.compliance_id
                left join states on states.id = users.state
                where compliance.id = $report_id
                $nav1 $nav2
                " . ($state ? " and users.state = '$state' " : "") . "
                and compliance_checks.status_id != 525
                order by compliance_checks.check_date_time DESC
        ";
                if ($result = $this->dbi->query($sql)) {
                    while ($myrow = $result->fetch_assoc()) {
                        $ccid = $myrow['id'];
                        $subject = $myrow['subject'];
                        $subject_id = $myrow['subject_id'];
                        $hide_score = $myrow['hide_score'];
                        $pdf = $myrow['pdf'];
                        $assessor = $myrow['assessor'];
                        $title = $myrow['title'];
                        $state_name = $myrow['state_name'];
                        $check_date = Date("d-M-Y", strtotime($myrow['check_date_time']));
                        $out_of = $myrow['total_out_of'];
                        $percent_complete = $myrow['percent_complete'];
                        $sql = "select (sum(value)/$out_of)*100 as `score` from compliance_check_answers where compliance_check_id = $ccid";
                        if ($result2 = $this->dbi->query($sql)) {
                            if ($myrow2 = $result2->fetch_assoc()) {
                                $score = round($myrow2['score']) . "%";
                            }
                        }
                        $total_score += $score;
                        if ($percent_complete > 10) {
                            $ccount++;
                            if ($ccount == 1) {
                                $str .= "<h3>Results for $title</h3>";
                                $str .= "<table class=\"grid\"><tr><th>Date</th><th>% Done</th><th>By</th><th>Subject</th><th>State</th>" . (!$hide_score ? "<th>Score</th>" : "") . "<th colspan=\"1\">View</th></tr>";
                            }
                            $str .= "<tr><td>$check_date</td><td>$percent_complete</td>
              <td>$assessor</td><td>$subject</td><td>$state_name</td>" . (!$hide_score ? "<td>$score</td>" : "") . "<td><a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_view_id=$ccid\">View</a>$pdf</td></tr>";
                        }
                    }
                    if ($ccount)
                        if ($avg = round($total_score / $ccount))
                            $str .= "<tr><th colspan=\"4\" style=\"text-align: right !important;\">Average Score: </th><th colspan=\"2\">$avg%</td></tr>";
                    $str .= "</table>";
                }
            }
            if ($this->list_obj->sql) {
                $str .= $this->list_obj->draw_list();
                $str .= "<br /><br /><a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "compliance_results.php?report_id=$report_id\">&lt;&lt; Back to Main Results</a>";
            }
        }
        return $str;
    }

    function add_notes($resolve_id, $compliance_check_id, $heading) {
        if (isset($_POST['chkAddToNotes'])) {
            $sql = "select question, answer from compliance_check_answers where id = $resolve_id";
            $result = $this->dbi->query($sql);
            if ($myrow = $result->fetch_assoc()) {
                $question = $myrow['question'];
                $answer = $myrow['answer'];
            }
            $res_notes = $heading . ':<br /><table class="uk-table-condensed uk-table-hover uk-table-divider uk-table-middle uk-table-striped uk-table-small"><tr><th>Question</th><th>Answer</th><th>Notes</th></tr>' .
                    "<tr><td>$question</td><td>$answer</td><td>$notes</td></tr></table>";
            $sql = "insert into compliance_check_notes (user_id, compliance_check_id, date, description) values('" . $_SESSION['user_id'] . "', '$compliance_check_id', now(), '$res_notes')";
            $result = $this->dbi->query($sql);
        }
    }

    function ComplianceFollowup() {
        $nav_month = (isset($_GET['selDateMonth']) ? $_GET['selDateMonth'] : null);
        $nav_year = (isset($_GET['selDateYear']) ? $_GET['selDateYear'] : null);
        $hdnReportFilter = (isset($_GET['hdnReportFilter']) ? $_GET['hdnReportFilter'] : null);
        $resolve_id = (isset($_GET['resolve_id']) ? $_GET['resolve_id'] : null);
        $edit = (isset($_GET['edit']) ? $_GET['edit'] : null);
        $unresolve_id = (isset($_GET['unresolve_id']) ? $_GET['unresolve_id'] : null);
        $kpi = (isset($_GET['kpi']) ? $_GET['kpi'] : null);
        $compliance_check_id = (isset($_GET['compliance_check_id']) ? $_GET['compliance_check_id'] : null);
        $nav_str = "ComplianceFollowup?i=1";
        if ($compliance_check_id)
            $nav_str .= "&compliance_check_id=$compliance_check_id";
        if ($hdnReportFilter)
            $nav_str .= "&hdnReportFilter=$hdnReportFilter";
        if ($nav_month)
            $nav_str .= "&selDateMonth=$nav_month";
        if ($nav_year)
            $nav_str .= "&selDateYear=$nav_year";
        if ($kpi)
            $nav_str .= "&kpi=$kpi";
        if ($resolve_id) {
            if ($_POST['hdnSaveResolution']) {
                $save_date = date('Y-m-d', strtotime($_POST['calDate']));
                $notes = $this->dbi->real_escape_string($_POST['txtNotes']);
                if ($edit) {
                    $this->add_notes($resolve_id, $compliance_check_id, "Item Resolution Amended");
                    $sql = "update compliance_followup set resolution_date = '$save_date', notes = '$notes' where compliance_answer_id = $resolve_id";
                    $str .= "<div class=\"message\"><h3>Item Saved...</h3><a style=\"width: 150px; \" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "$nav_str\">Close</a><br /></div><br />";
                } else {
                    $this->add_notes($resolve_id, $compliance_check_id, "Item Resolved");
                    $sql = "insert into compliance_followup (compliance_answer_id, resolution_date, resolved_by, notes) values ($resolve_id, '$save_date', " . $_SESSION['user_id'] . ", '$notes')";
                    $str .= "<div class=\"message\"><h3>Item Resolved</h3><a style=\"width: 150px; \" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "$nav_str&unresolve_id=$resolve_id\">Undo...</a><br /><br /><a style=\"width: 150px; \" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "$nav_str&resolve_id=$resolve_id&edit=1\">Edit Notes</a><br /><br /><a style=\"width: 150px; \" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "$nav_str\">Close</a><br /><br /></div>";
                }
                $result = $this->dbi->query($sql);
            }
            $this->list_obj->title = ($edit ? "Item to Edit" : "Item to Resolve");
            $this->list_obj->sql = "
            SELECT compliance_checks.id as idin, compliance.title as `Title`, CONCAT(users.name, ' ',  users.surname) as `Assessor`, CONCAT(users2.name, ' ',  users2.surname) as `Subject`,
            compliance_check_answers.question as `Question`, compliance_check_answers.answer as `Answer`, compliance_check_answers.additional_text as `Comment`, compliance_checks.check_date_time as `Check Date`, states.item_name as `State`
            " . ($edit ? ", compliance_followup.notes as `Notes`, compliance_followup.resolution_date as `Resolution Date`" : "") . "
            FROM `compliance_check_answers`
            left join compliance_checks on compliance_checks.id = compliance_check_answers.compliance_check_id
            left join compliance on compliance.id = compliance_checks.compliance_id
            left join users on users.id = compliance_checks.assessor_id
            left join users2 on users2.id = compliance_checks.subject_id
            left join states on states.id = users2.state
            " . ($edit ? "left join compliance_followup on compliance_followup.compliance_answer_id = compliance_check_answers.id" : "") . "
            where compliance_check_answers.id = $resolve_id
      ";
            if ($edit) {
                $sql = "select notes, resolution_date from compliance_followup where compliance_answer_id = $resolve_id";
                $result = $this->dbi->query($sql);
                if ($myrow = $result->fetch_assoc()) {
                    $notes = $myrow['notes'];
                    $resolution_date = date('d-M-Y', strtotime($myrow['resolution_date']));
                }
            }
            $str .= $this->list_obj->draw_list() . "<br />";
            if ($edit || !$_POST['hdnSaveResolution']) {
                $input_obj = new input_item;
                $str .= $input_obj->setup_cal();
                $curr_date_time = date('Y-m-d');
                $curr_date_show = ($edit ? date('d-M-Y', strtotime($resolution_date)) : date('d-M-Y'));
                $str .= '
          ' . $input_obj->hdn("hdnSaveResolution", 1, '', '', '', '') . '
          <table class="standard_form">
          <tr><td class="form_header">Resolution Details</td></tr>
          <tr>
          <td>
          <div class="fl">
          Resolution Date*<br />
          ' . $input_obj->cal("calDate", $curr_date_show, 'style="width: 190px;"', '', '', '') . '<br /><br />
          ' . $input_obj->chk("chkAddToNotes", 'Add to Notes', 'checked', '', '', '') . '</div>
          <div class="fl">
          Notes<br />
          <textarea name="txtNotes" id="txtNotes" style="width: 800px; height: 100px;">' . $notes . '</textarea></div>
          <div class="fl"><br /><input class="list_a" type="submit" name="cmdSave" value="Save"><a href="' . $nav_str . '" class="list_a button_a">Cancel</a></div>'
                        . ($edit ? '<div class="fl"><br /> &nbsp; &nbsp;<a href="' . $nav_str . '&unresolve_id=' . $resolve_id . '" class="list_a button_a">Unresolve (Delete)</a></div>' : "")
                        . '<br />
          </td>
          </tr>
          </table>
          <br /><br />
        ';
            }
        } else if ($unresolve_id) {
            $sql = "delete from compliance_followup where compliance_answer_id = $unresolve_id";
            $result = $this->dbi->query($sql);
            $str .= '<div class="message"><h3>Resolve Undone</h3></div>';
        }
        if ($compliance_check_id) {
            $bottom_sql = " and compliance_checks.id = $compliance_check_id ";
        } else {
            if (!$nav_month) {
                $def_month = 1;
                $nav_month = date("m");
                $nav_year = date("Y");
            }
            $nav = new navbar;
            $str .= '
          <script language="JavaScript">
            function report_filter() {
              document.getElementById("hdnReportFilter").value=1
              document.frmFilter.submit()
            }
          </script>
          </form>
          <form method="GET" name="frmFilter">
          <input type="hidden" name="hdnReportFilter" id="hdnReportFilter">
          <input type="hidden" name="kpi" id="kpi" value="' . $kpi . '">
          <div class="form-wrapper">
          <div class="form-header">Filter</div>
          <div  style="padding: 10px;">
          ' . $nav->month_year(2015) . '
          <input onClick="report_filter()" type="button" value="Go" /> 
          </div>
          </div>
          </form>
      ';
            if ($def_month) {
                $str .= '
          <script language="JavaScript">
            change_selDate()
          </script>
        ';
            }
            if ($nav_month > 0) {
                $nav1 = "and MONTH(compliance_checks.check_date_time) = $nav_month";
            } else {
                $nav_month = "ALL Months";
            }
            if ($nav_year > 0) {
                $nav2 = "and YEAR(compliance_checks.check_date_time) = $nav_year";
            } else {
                $nav_year = "ALL Years";
            }
            $kpi_for_month = $nav_month - 1;
            $kpi_for_year = $nav_year;
            if (!$kpi_for_month) {
                $kpi_for_month = 12;
                $kpi_for_year--;
            }
            $own_assessment = 1;
            $bottom_sql = "$nav1 $nav2
                " . ($state_manager ? " and users2.state = $users_state " : ($national_manager ? "" : " and users.id = " . $_SESSION['user_id'])) . " and compliance.title " . ($kpi ? "" : "not") . " LIKE '%kpi%'
                order by compliance_checks.check_date_time";
        }
        if (!$kpi) {
            $sql = "select lookup_fields.id, lookup_fields.item_name, lookup_fields.value, users.state from lookup_fields
            left join lookup_answers on lookup_answers.lookup_field_id = lookup_fields.id
            left join users on users.id = lookup_answers.foreign_id
            where lookup_answers.foreign_id = '" . $_SESSION['user_id'] . "' and lookup_answers.table_assoc = 'users' and lookup_fields.value = 'MANAGEMENT'
            ";
            $result = $this->dbi->query($sql);
            $is_manager = $result->num_rows;
            if ($is_manager) {
                while ($myrow = $result->fetch_assoc()) {
                    if ($myrow['item_name'] == 'State Manager' || $myrow['item_name'] == 'State Ops & Safety Supervisor') {
                        $state_manager = 1;
                        $users_state = $myrow['state'];
                        $own_assessment = 0;
                    }
                    if ($myrow['item_name'] == 'National Manager') {
                        $national_manager = 1;
                        $own_assessment = 0;
                    }
                }
            }
        }
        if ($compliance_check_id) {
            $this->list_obj->title = "";
            $this->list_obj->sql = "
            SELECT compliance_checks.id as idin, compliance.title as `Title`, CONCAT(users.name, ' ',  users.surname) as `Assessor`, CONCAT(users2.name, ' ',  users2.surname) as `Subject`, compliance_checks.check_date_time as `Check Date`, states.item_name as `State`, CONCAT('<a uk-tooltip=\"title: View Online\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_view_id=', compliance_checks.id, '\">View</a>', '<a uk-tooltip=\"title: View as PDF\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "CompliancePDF/', compliance_checks.id, '\">PDF</a><a uk-tooltip=\"title: Upload/View Attachemts\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Resources?current_dir=compliance&current_subdir=', compliance_checks.id, '\">Attachments...</a>
                  ') as `***`
            FROM `compliance_checks`
            left join compliance on compliance.id = compliance_checks.compliance_id
            left join users on users.id = compliance_checks.assessor_id
            left join users2 on users2.id = compliance_checks.subject_id
            left join states on states.id = users2.state
            where compliance_checks.id = $compliance_check_id
      ";
        }
        for ($x = 0; $x < 2; $x++) {
            $sql = "
              SELECT compliance_checks.id as `ccid`, compliance_check_answers.id as `ccaid`, compliance.title, CONCAT(users.name, ' ',  users.surname) as `assessor`, CONCAT(users2.name, ' ',  users2.surname) as `subject`,
              compliance_check_answers.question as `question`, compliance_check_answers.answer as `answer`, compliance_check_answers.additional_text as `comment`, compliance_checks.check_date_time, states.item_name as `state`
              " . ($x ? ", compliance_followup.notes, compliance_followup.resolution_date" : "") . "
              FROM `compliance_check_answers`
              left join compliance_checks on compliance_checks.id = compliance_check_answers.compliance_check_id
              left join compliance on compliance.id = compliance_checks.compliance_id
              left join users on users.id = compliance_checks.assessor_id
              left join users2 on users2.id = compliance_checks.subject_id
              left join states on states.id = users2.state
              " . ($x ? "left join compliance_followup on compliance_followup.compliance_answer_id = compliance_check_answers.id" : "") . "
              WHERE compliance_check_answers.answer_colour = '#8C0000'
              and compliance_check_answers.id " . ($x ? "in" : "not in") . " (select compliance_answer_id from compliance_followup)
              $bottom_sql
      ";
            $result = $this->dbi->query($sql);
            $num_results = $result->num_rows;
            if ($nav_month != "ALL Months")
                $for_months = "(for $kpi_for_month / $kpi_for_year)";
            $title_xtra = ($compliance_check_id ? "" : "from " . ($kpi ? "KPI's" : "Compliance Checks") . " Performed During $nav_month / $nav_year");
            $str .= "<h3>" . ($x ? "Resolved" : "Followup") . " Items $title_xtra <a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "" . ($kpi ? "ComplianceFollowup\">Go to Compliance Check" : "ComplianceFollowup?kpi=1\">Go to KPI") . " Followup</a></h3>";
            if (!$x && $compliance_check_id)
                $str .= $this->list_obj->draw_list() . "<br />";
            $str .= "<h6>$num_results Items</h6>";
            if ($num_results) {
                $str .= "<table class=\"grid2\"><tr>
        " . ($compliance_check_id ? "" : "<th>Date</th><th>Title</th>" . ($own_assessment ? "" : "<th>Assessor</th>") . "<th>Subject</th><th>State</th>") . "
        <th>Question</th><th>Answer</th><th>Comment</th>
              " . (!$x ? "" : "<th>Resolution Date</th><th>Notes</th>") . "<th>&gt;&gt;</th></tr>";
                $bg = "white";
                while ($myrow = $result->fetch_assoc()) {
                    $ccid = $myrow['ccid'];
                    $ccaid = $myrow['ccaid'];
                    $question = $myrow['question'];
                    $answer = $myrow['answer'];
                    $comment = $myrow['comment'];
                    if ($x) {
                        $resolution_date = Date("d-M-Y", strtotime($myrow['resolution_date']));
                        $notes = $myrow['notes'];
                    }
                    if ($oldccid != $ccid) {
                        $assessor = "<nobr>" . $myrow['assessor'] . "</nobr>";
                        $subject = "<nobr>" . $myrow['subject'] . "</nobr>";
                        $title = $myrow['title'];
                        $state = $myrow['state'];
                        $check_date = "<nobr>" . Date("d-M-Y", strtotime($myrow['check_date_time'])) . "</nobr>";
                        $tmp_str = ($compliance_check_id ? "" : "<td valign=\"top\">$check_date</td><td valign=\"top\"><a href=\"" . $this->f3->get('main_folder') . "?compliance_check_id=$ccid\">$title</a></td>" . ($own_assessment ? "" : "<td valign=\"top\">$assessor</td>") . "<td valign=\"top\">$subject</td><td valign=\"top\">$state</td>");
                        $bg = ($bg == "white" ? "#EEEEEE" : "white");
                    } else {
                        if (!$compliance_check_id)
                            $tmp_str = '<td colspan="' . ($own_assessment ? "4" : "5") . '">&nbsp;</td>';
                    }
                    $str .= '<tr style="background-color: ' . $bg . ' !important;">' . $tmp_str;
                    $str .= "<td valign=\"top\">$question</td><td valign=\"top\">$answer</td><td valign=\"top\">$comment</td>
          " . (!$x ? "<td valign=\"top\"><a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "$nav_str&resolve_id=$ccaid\">Resolve</a></td>" : "<td valign=\"top\">$resolution_date</td><td valign=\"top\">$notes</td><td valign=\"top\"><a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "$nav_str&resolve_id=$ccaid&edit=1\">Edit</a></td>") .
                            "</tr>";
                    $oldccid = $ccid;
                }
                $str .= "</table>";
            }
        }
        return $str;
    }

}

?>