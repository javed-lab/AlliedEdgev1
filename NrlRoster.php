<?php
class NrlRoster extends Controller {
  function SaveNrlItem() {
    $col = (isset($_GET['col']) ? $_GET['col'] : null);
    $item_id = (isset($_GET['item_id']) ? $_GET['item_id'] : null);
    $txt = (isset($_GET['txt']) ? trim(mysqli_real_escape_string($this->dbi, $_GET['txt'])) : null);
    $chk = (isset($_GET['chk']) ? $_GET['chk'] : null);
    $equip = (isset($_GET['equip']) ? $_GET['equip'] : null);
    $game_id = (isset($_GET['game_id']) ? $_GET['game_id'] : null);


    if($chk) {
      $sql = "update nrl_rosters set ".strtolower(str_replace("X", "_", substr($chk, 3)))." = ".($txt == "true" ? 1 : 0)." where id = $item_id;";
    } else {
      $cols = ($equip ? array("radio_number", "equipment_comment") : array("position", "zone_position", "call_sign", "start_time", "finish_time", "sub_contractor", "staff_name", "security_licence_number", "licence_type", "licence_expiry", "actual_start_time", "actual_finish_time", "comment"));
      if($col > 5 && $col <= 8 && !$equip) {
        $sql = "select position, sort_order from nrl_rosters where id = $item_id;";
        $result = $this->dbi->query($sql);
        if($myrow = $result->fetch_assoc()) { $pos = $myrow['position']; $sort_order = $myrow['sort_order']; }
        $sql_xtra = ($pos ? "position = $pos and game_id = $game_id and sort_order >= $sort_order" : "id = $item_id");
      } else {
        $sql_xtra = "id = $item_id";
      }
      $sql = "update nrl_rosters set ".$cols[$col-1]." = '$txt' where $sql_xtra;";
    }
    $result = $this->dbi->query($sql);
  }
  function bgCellColour($objPHPExcel,$cells,$colour) {
    $objPHPExcel->getActiveSheet()->getStyle($cells)->getFill()->applyFromArray(array(
      'type' => PHPExcel_Style_Fill::FILL_SOLID,
      'startcolor' => array(
      'rgb' => $colour
      )
    ));
  }
  function display_totals($objPHPExcel, &$quoted_sub_total, &$actual_sub_total, $grand_total_tag, &$quoted_total, &$actual_total, &$row_count, $invoice, &$quoted_hours_sub, &$actual_hours_sub, &$quoted_hours_total,
    &$actual_hours_total) {
    $this->bgCellColour($objPHPExcel, "A$row_count:".($invoice ? "N" : "G")."$row_count", "F4FFF4");
    $objPHPExcel->getActiveSheet()->SetCellValue("A$row_count", "Sub Totals:");
    $objPHPExcel->getActiveSheet()->SetCellValue("F$row_count", $quoted_hours_sub);
    $objPHPExcel->getActiveSheet()->SetCellValue("G$row_count", $quoted_sub_total);
    $objPHPExcel->getActiveSheet()->getStyle("A$row_count")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
    $objPHPExcel->getActiveSheet()->getStyle("A$row_count")->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->getStyle("G$row_count")->getNumberFormat()->setFormatCode("$#,##0.00");
    $sub_total = 0;    $quoted_sub_total = 0;    $hours_sub = 0;    $quoted_hours_sub = 0;
    $objPHPExcel->getActiveSheet()->mergeCells("A$row_count:E$row_count");
    if($invoice) {
      $objPHPExcel->getActiveSheet()->SetCellValue("H$row_count", "Sub Totals:");
      $objPHPExcel->getActiveSheet()->SetCellValue("L$row_count", $actual_hours_sub);
      $objPHPExcel->getActiveSheet()->SetCellValue("M$row_count", $actual_sub_total);
      $objPHPExcel->getActiveSheet()->getStyle("H$row_count")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
      $objPHPExcel->getActiveSheet()->getStyle("H$row_count")->getFont()->setBold(true);
      $objPHPExcel->getActiveSheet()->getStyle("M$row_count")->getNumberFormat()->setFormatCode("$#,##0.00");
      $actual_sub_total = 0;
      $actual_hours_sub = 0;
      $objPHPExcel->getActiveSheet()->mergeCells("H$row_count:K$row_count");
    }
    if($grand_total_tag) {
      $row_count++;
      $this->bgCellColour($objPHPExcel, "A$row_count:".($invoice ? "N" : "G")."$row_count", "F4FFF4");
      $objPHPExcel->getActiveSheet()->SetCellValue("A$row_count", $grand_total_tag);
      $objPHPExcel->getActiveSheet()->SetCellValue("F$row_count", $quoted_hours_total);
      $objPHPExcel->getActiveSheet()->SetCellValue("G$row_count", $quoted_total);
      $objPHPExcel->getActiveSheet()->getStyle("A$row_count")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
      $objPHPExcel->getActiveSheet()->getStyle("A$row_count")->getFont()->setBold(true);
      $objPHPExcel->getActiveSheet()->getStyle("G$row_count")->getNumberFormat()->setFormatCode("$#,##0.00");
      $objPHPExcel->getActiveSheet()->mergeCells("A$row_count:E$row_count");
      $quoted_total = 0;
      $quoted_hours_total = 0;
      if($invoice) {
        $objPHPExcel->getActiveSheet()->SetCellValue("H$row_count", $grand_total_tag);
        $objPHPExcel->getActiveSheet()->SetCellValue("L$row_count", $actual_hours_total);
        $objPHPExcel->getActiveSheet()->SetCellValue("M$row_count", $actual_total);
        $objPHPExcel->getActiveSheet()->getStyle("H$row_count")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $objPHPExcel->getActiveSheet()->getStyle("H$row_count")->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle("M$row_count")->getNumberFormat()->setFormatCode("$#,##0.00");
        $objPHPExcel->getActiveSheet()->mergeCells("H$row_count:K$row_count");
        $actual_total = 0;
        $actual_hours_total = 0;
      }
      if($grand_total_tag == "STADIUM TOTALS:") {
        $objPHPExcel->getActiveSheet()->SetCellValue("F$row_count", $objPHPExcel->getActiveSheet()->getCell("F$row_count")->getValue() - $objPHPExcel->getActiveSheet()->getCell("F" . ($row_count - 1))->getValue());
        $objPHPExcel->getActiveSheet()->SetCellValue("G$row_count", $objPHPExcel->getActiveSheet()->getCell("G$row_count")->getValue() - $objPHPExcel->getActiveSheet()->getCell("G" . ($row_count - 1))->getValue());
        if($invoice) {
          $objPHPExcel->getActiveSheet()->SetCellValue("L$row_count", $objPHPExcel->getActiveSheet()->getCell("L$row_count")->getValue() - $objPHPExcel->getActiveSheet()->getCell("L" . ($row_count - 1))->getValue());
          $objPHPExcel->getActiveSheet()->SetCellValue("M$row_count", $objPHPExcel->getActiveSheet()->getCell("M$row_count")->getValue() - $objPHPExcel->getActiveSheet()->getCell("M" . ($row_count - 1))->getValue());
        }
        $objPHPExcel->getActiveSheet()->removeRow($row_count - 1);
      }
    }
  }
  function Show() {
    $this->list_obj = new data_list;
    $this->editor_obj = new data_editor;
    $download_xl = (isset($_GET['download_xl']) ? $_GET['download_xl'] : null);
    $download_equipment_xl = (isset($_GET['download_equipment_xl']) ? $_GET['download_equipment_xl'] : null);
    if($download_xl || $download_equipment_xl) {
      //include('Classes/PHPExcel.php');
      $objPHPExcel = new PHPExcel();
      $objPHPExcel->setActiveSheetIndex(0);
      $sql = "select title, DAYOFWEEK(date) as `day`, DATE_FORMAT(date, '%a, %d-%b-%Y') as `game_date`, DATE_FORMAT(game_start_time, '%H:%i') as `game_start_time`, DATE_FORMAT(gate_opening_time, '%H:%i') as `gate_opening_time`, is_public_holiday from nrl_games where id = " .($download_xl ? $download_xl : $download_equipment_xl);
      $result = $this->dbi->query($sql);
      if($myrow = $result->fetch_assoc()) {
        $game_title = $myrow['title'];
        $game_day = $myrow['day'];
        $game_date = $myrow['game_date'];
        $game_start_time = $myrow['game_start_time'];
        $gate_opening_time = $myrow['gate_opening_time'];
        $is_public_holiday = $myrow['is_public_holiday'];
        $game_title = "$game_title on $game_date ".($is_public_holiday ? "(Public Holiday)" : "")." | GATES: $gate_opening_time | KICK OFF: $game_start_time";
      }
      $row_count = 1;
      $objPHPExcel->getActiveSheet()->SetCellValue("A$row_count", $game_title);
      $objPHPExcel->getActiveSheet()->mergeCells("A$row_count:" . ($invoice ? "N" : "G") . $row_count);
    }
    if($download_xl) {
      $invoice = (isset($_GET['invoice']) ? $_GET['invoice'] : null);
      $objPHPExcel->getActiveSheet()->setTitle("Stadium");
      $sql = "
        select nrl_rosters.zone as `zone`, nrl_rosters.position `position`, nrl_rosters.zone_position `zone_position`, nrl_rosters.call_sign `call_sign`, nrl_rosters.sub_contractor `sub_contractor`,
        DATE_FORMAT(nrl_rosters.start_time, '%H:%i') as `start`, DATE_FORMAT(nrl_rosters.finish_time, '%H:%i') as `finish`,
        nrl_rosters.staff_name as `staff_name`,
        DATE_FORMAT(nrl_rosters.actual_start_time, '%H:%i') as `started`,
        DATE_FORMAT(nrl_rosters.actual_finish_time, '%H:%i') as `finished`,
        TIME_FORMAT(TIMEDIFF(CONCAT('2010-01-0', if(TIME_TO_SEC(nrl_rosters.finish_time) - TIME_TO_SEC(nrl_rosters.start_time) < 0, '2', '1'), ' ', nrl_rosters.finish_time), 
                              CONCAT('2010-01-01 ', nrl_rosters.start_time)), '%H:%i') as `rostered_hours`,
        TIME_FORMAT(TIMEDIFF(CONCAT('2010-01-0', if(TIME_TO_SEC(nrl_rosters.actual_finish_time) - TIME_TO_SEC(nrl_rosters.actual_start_time) < 0, '2', '1'), ' ', nrl_rosters.actual_finish_time), 
                              CONCAT('2010-01-01 ', nrl_rosters.actual_start_time)), '%H:%i') as `hours`,
        nrl_rosters.hourly_rate as `rate`,
        nrl_rosters.comment as `comment`
        from nrl_rosters
        left join nrl_games on nrl_games.id = nrl_rosters.game_id
        where nrl_rosters.game_id = $download_xl
        order by nrl_rosters.sort_order
      ";
      $result = $this->dbi->query($sql);
      $zone_count = 0;
      $page_fac = 3;
      while($myrow = $result->fetch_assoc()) {
        $row_count++;
        $zone = $myrow['zone'];
        $zone_position = $myrow['zone_position'];
        $position = $myrow['position'];
        $call_sign = $myrow['call_sign'];
        $start = $myrow['start'];
        $finish = $myrow['finish'];
        $rate = $myrow['rate'];
        if($old_zone != $zone && $row_count != 2) {
          $this->display_totals($objPHPExcel, $quoted_sub_total, $actual_sub_total, "", $quoted_total, $actual_total, $row_count, $invoice, $quoted_hours_sub, $actual_hours_sub, $quoted_hours_total, $actual_hours_total);
          $row_count++;
        }
        $rostered_hours = substr($myrow['rostered_hours'], 0, 2) + (substr($myrow['rostered_hours'], 3, 2) != "00" ? (substr($myrow['rostered_hours'], 3, 2) / 60) : "");
        $quoted_cost = $rate * $rostered_hours;
        $quoted_sub_total += $quoted_cost;
        $quoted_total += $quoted_cost;
        $quoted_hours_sub += $rostered_hours;
        $quoted_hours_total += $rostered_hours;
        if($invoice) {
          $sub_contractor = $myrow['sub_contractor'];
          $staff_name = $myrow['staff_name'];
          $started = $myrow['started'];
          $finished = $myrow['finished'];
          $hours = substr($myrow['hours'], 0, 2) + (substr($myrow['hours'], 3, 2) != "00" ? (substr($myrow['hours'], 3, 2) / 60) : "");
          $hours = round($hours, 2);
          $comment = $myrow['comment'];
          $actual_cost = $rate * $hours;
          $actual_total += $actual_cost;
          $actual_sub_total += $actual_cost;
          $actual_hours_sub += $hours;
          $actual_hours_total += $hours;
        }
        if($old_zone != $zone) {
          $zone_count++;
          if($zone_count == 6) {
            $this->display_totals($objPHPExcel, $quoted_sub_total, $actual_sub_total, "STADIUM TOTALS:", $quoted_total, $actual_total, $row_count, $invoice, $quoted_hours_sub, $actual_hours_sub, $quoted_hours_total, $actual_hours_total);
            if($invoice) {
              $grand_actual_total += $actual_total;
              $grand_actual_hours_total += $actual_hours_total;
            }
            $grand_quoted_total += $quoted_total;
            $grand_quoted_hours_total += $quoted_hours_total;
            $myWorkSheet = new PHPExcel_Worksheet($objPHPExcel, 'Club');
            $rostered_hours = substr($myrow['rostered_hours'], 0, 2) + (substr($myrow['rostered_hours'], 3, 2) != "00" ? (substr($myrow['rostered_hours'], 3, 2) / 60) : "");
            $quoted_cost = $rate * $rostered_hours;
            $quoted_sub_total += $quoted_cost;
            $quoted_total += $quoted_cost;
            $quoted_hours_sub += $rostered_hours;
            $quoted_hours_total += $rostered_hours;
            if($invoice) {
              $sub_contractor = $myrow['sub_contractor'];
              $staff_name = $myrow['staff_name'];
              $started = $myrow['started'];
              $finished = $myrow['finished'];
              $hours = substr($myrow['hours'], 0, 2) + (substr($myrow['hours'], 3, 2) != "00" ? (substr($myrow['hours'], 3, 2) / 60) : "");
              $hours = round($hours, 2);
              $comment = $myrow['comment'];
              $actual_cost = $rate * $hours;
              $actual_total += $actual_cost;
              $actual_sub_total += $actual_cost;
              $actual_hours_sub += $hours;
              $actual_hours_total += $hours;
            }
            $objPHPExcel->addSheet($myWorkSheet, 1);
            $objPHPExcel->setActiveSheetIndex(1);
            $row_count = 1;
            $objPHPExcel->getActiveSheet()->SetCellValue("A$row_count", $game_title);
            $objPHPExcel->getActiveSheet()->mergeCells("A$row_count:" . ($invoice ? "N" : "G") . $row_count);
            $row_count = 2;
            $page_fac = 2;
          }
          $objPHPExcel->getActiveSheet()->SetCellValue("A$row_count", $zone);
          $objPHPExcel->getActiveSheet()->getStyle("A$row_count")->getFont()->setBold(true)
                                                                            ->setSize(16)
                                                                            ->getColor()->setRGB('0000AA');
          $objPHPExcel->getActiveSheet()->mergeCells("A$row_count:C$row_count");
          $this->bgCellColour($objPHPExcel, "A$row_count:".($invoice ? "N" : "G")."$row_count", "DDDDDD");
          $objPHPExcel->getActiveSheet()->SetCellValue("D$row_count", "Rostered");
          $objPHPExcel->getActiveSheet()->getStyle("D$row_count")->getFont()->setBold(true)  ->getColor()->setRGB('0000AA');
          $objPHPExcel->getActiveSheet()->mergeCells("D$row_count:G$row_count");
          $objPHPExcel->getActiveSheet()->getStyle("D$row_count:G$row_count")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
          if($invoice) {
            $objPHPExcel->getActiveSheet()->SetCellValue("J$row_count", "Actual");
            $objPHPExcel->getActiveSheet()->getStyle("J$row_count")->getFont()->setBold(true)  ->getColor()->setRGB('0000AA');
            $objPHPExcel->getActiveSheet()->mergeCells("J$row_count:M$row_count");
            $objPHPExcel->getActiveSheet()->getStyle("J$row_count:M$row_count")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
          }
          $objPHPExcel->getActiveSheet()->mergeCells("H$row_count:I$row_count");
          $row_count++;
          $this->bgCellColour($objPHPExcel, "A$row_count:".($invoice ? "N" : "G")."$row_count", "DDDDDD");
          $objPHPExcel->getActiveSheet()->getStyle("A$row_count:".($invoice ? "N" : "G")."$row_count")->getFont()->setBold(true)
                                                                            ->setSize(13);
          $objPHPExcel->getActiveSheet()->SetCellValue("A$row_count", "#");
          $objPHPExcel->getActiveSheet()->SetCellValue("B$row_count", "Position");
          $objPHPExcel->getActiveSheet()->SetCellValue("C$row_count", "Call Sign");
          $objPHPExcel->getActiveSheet()->SetCellValue("D$row_count", "Start");
          $objPHPExcel->getActiveSheet()->SetCellValue("E$row_count", "Finish");
          $objPHPExcel->getActiveSheet()->SetCellValue("F$row_count", "Hours");
          $objPHPExcel->getActiveSheet()->SetCellValue("G$row_count", "Cost");
          $objPHPExcel->getActiveSheet()->getStyle("A$row_count:G$row_count")->getFont()->setBold(true);
          if($invoice) {
            $objPHPExcel->getActiveSheet()->SetCellValue("H$row_count", "Sub Contractor");
            $objPHPExcel->getActiveSheet()->SetCellValue("I$row_count", "Staff Name");
            $objPHPExcel->getActiveSheet()->SetCellValue("J$row_count", "Start");
            $objPHPExcel->getActiveSheet()->SetCellValue("K$row_count", "Finish");
            $objPHPExcel->getActiveSheet()->SetCellValue("L$row_count", "Hours");
            $objPHPExcel->getActiveSheet()->SetCellValue("M$row_count", "Cost");
            $objPHPExcel->getActiveSheet()->SetCellValue("N$row_count", "Comment");
            $objPHPExcel->getActiveSheet()->getStyle("H$row_count:N$row_count")->getFont()->setBold(true);
          }
          $row_count++;
        }
        $pos_count++;
        $objPHPExcel->getActiveSheet()->SetCellValue("A$row_count", $position);
        $objPHPExcel->getActiveSheet()->getStyle("A$row_count")->getFont()->setBold(true)
                                                                          ->getColor()->setRGB('AA0000');
        $objPHPExcel->getActiveSheet()->SetCellValue("B$row_count", $zone_position);
        $objPHPExcel->getActiveSheet()->SetCellValue("C$row_count", $call_sign);
        $objPHPExcel->getActiveSheet()->SetCellValue("D$row_count", $start);  
        $objPHPExcel->getActiveSheet()->SetCellValue("E$row_count", $finish);
        $objPHPExcel->getActiveSheet()->SetCellValue("F$row_count", $rostered_hours);
        $objPHPExcel->getActiveSheet()->SetCellValue("G$row_count", $quoted_cost);
        $objPHPExcel->getActiveSheet()->getStyle('G' . $row_count)->getNumberFormat()->setFormatCode("$#,##0.00");
        if($pos_count % 2) $this->bgCellColour($objPHPExcel, "A$row_count:".($invoice ? "N" : "G")."$row_count", "F3F3F4");
        if($invoice) {
          $objPHPExcel->getActiveSheet()->SetCellValue("H$row_count", $sub_contractor);
          $objPHPExcel->getActiveSheet()->SetCellValue("I$row_count", $staff_name);
          $objPHPExcel->getActiveSheet()->SetCellValue("J$row_count", $started);
          $objPHPExcel->getActiveSheet()->SetCellValue("K$row_count", $finished);
          $objPHPExcel->getActiveSheet()->SetCellValue("L$row_count", $hours);
          $objPHPExcel->getActiveSheet()->SetCellValue("M$row_count", $actual_cost);
          $objPHPExcel->getActiveSheet()->SetCellValue("N$row_count", $comment);
          $objPHPExcel->getActiveSheet()->getStyle('M' . $row_count)->getNumberFormat()->setFormatCode("$#,##0.00");
        }
        $old_zone = $zone;
        $objPHPExcel->getActiveSheet()->getStyle("A1:" . ($invoice ? "N" : "G") . ($row_count + $page_fac))->applyFromArray(array(
          'borders' => array(
            'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN,
                'color' => array('rgb' => 'AAAAAA')
            )
          )
        ));
        for ($i = 'A'; $i != 'O'; $i++) {
          $objPHPExcel->getActiveSheet()->getColumnDimension($i)->setAutoSize(true);
        }
        $objPHPExcel->getActiveSheet()->calculateColumnWidths();
        for ($i = 'A'; $i != 'P'; $i++) {
          $curr_width = $objPHPExcel->getActiveSheet()->getColumnDimension($i)->getWidth();
          $objPHPExcel->getActiveSheet()->getColumnDimension($i)->setAutoSize(false)->setWidth($curr_width * 0.86);
        }
      }
      $row_count++;
      $grand_quoted_total += $quoted_total;
      $grand_actual_total += $actual_total;
      $grand_quoted_hours_total += $quoted_hours_total;
      $grand_actual_hours_total += $actual_hours_total;
      $this->display_totals($objPHPExcel, $quoted_sub_total, $actual_sub_total, "CLUB TOTALS:", $quoted_total, $actual_total, $row_count, $invoice, $quoted_hours_sub, $actual_hours_sub, $quoted_hours_total, $actual_hours_total);
    } else if ($download_equipment_xl) {
      $objPHPExcel->getActiveSheet()->setTitle("Equipment Issue");
      $sql = "
        select nrl_rosters.zone as `zone`, nrl_rosters.position `position`,
        nrl_rosters.staff_name as `staff_name`, nrl_rosters.radio_number,
        concat(if(nrl_rosters.equipment_issued, 'X', '')) as `equipment_issued`, concat(if(nrl_rosters.radio_returned, 'X', '')) as `radio_returned`, concat(if(nrl_rosters.equipment_returned, 'X', '')) as `equipment_returned`,
        nrl_rosters.equipment_comment as `equipment_comment`
        from nrl_rosters
        left join nrl_games on nrl_games.id = nrl_rosters.game_id
        where nrl_rosters.game_id = ".($download_xl ? $download_xl : $download_equipment_xl)."
        order by nrl_rosters.sort_order
      ";
      $result = $this->dbi->query($sql);
      $zone_count = 0;
      while($myrow = $result->fetch_assoc()) {
        $row_count++;
        $zone = $myrow['zone'];
        if($old_zone != $zone) {
          $objPHPExcel->getActiveSheet()->SetCellValue("A$row_count", $zone);
          $objPHPExcel->getActiveSheet()->SetCellValue("C$row_count", "Issued");
          $objPHPExcel->getActiveSheet()->SetCellValue("E$row_count", "Returned");
          $objPHPExcel->getActiveSheet()->getStyle("A$row_count")->getFont()->setBold(true)
                                                                            ->setSize(12)
                                                                            ->getColor()->setRGB('0000AA');
          $objPHPExcel->getActiveSheet()->getStyle("C$row_count:G$row_count")->getFont()->setBold(true);
          $this->bgCellColour($objPHPExcel, "A$row_count:".($invoice ? "N" : "G")."$row_count", "DDDDDD");
          $objPHPExcel->getActiveSheet()->mergeCells("A$row_count:B$row_count");
          $objPHPExcel->getActiveSheet()->mergeCells("C$row_count:D$row_count");
          $objPHPExcel->getActiveSheet()->mergeCells("E$row_count:F$row_count");
          $objPHPExcel->getActiveSheet()->getStyle("C$row_count:D$row_count")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
          $objPHPExcel->getActiveSheet()->getStyle("E$row_count:F$row_count")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
          $row_count++;
          $objPHPExcel->getActiveSheet()->getStyle("A$row_count:G$row_count")->getFont()->setBold(true)
                                                                            ->setSize(10);
          $this->bgCellColour($objPHPExcel, "A$row_count:".($invoice ? "N" : "G")."$row_count", "DDDDDD");
          $objPHPExcel->getActiveSheet()->SetCellValue("A$row_count", "#");
          $objPHPExcel->getActiveSheet()->SetCellValue("B$row_count", "Staff Name");
          $objPHPExcel->getActiveSheet()->SetCellValue("C$row_count", "Equip");
          $objPHPExcel->getActiveSheet()->SetCellValue("D$row_count", "Radio #");
          $objPHPExcel->getActiveSheet()->SetCellValue("E$row_count", "Equip");
          $objPHPExcel->getActiveSheet()->SetCellValue("F$row_count", "Radio");
          $objPHPExcel->getActiveSheet()->SetCellValue("G$row_count", "Comment");
          $row_count++;
        }
        $objPHPExcel->getActiveSheet()->SetCellValue("A$row_count", $myrow['position']);
        $objPHPExcel->getActiveSheet()->SetCellValue("B$row_count", $myrow['staff_name']);
        $objPHPExcel->getActiveSheet()->SetCellValue("C$row_count", $myrow['equipment_issued']);
        $objPHPExcel->getActiveSheet()->SetCellValue("D$row_count", $myrow['radio_number']);
        $objPHPExcel->getActiveSheet()->SetCellValue("E$row_count", $myrow['equipment_returned']);
        $objPHPExcel->getActiveSheet()->SetCellValue("F$row_count", $myrow['radio_returned']);
        $objPHPExcel->getActiveSheet()->SetCellValue("G$row_count", $myrow['equipment_comment']);
        $old_zone = $zone;
      }
      $objPHPExcel->getActiveSheet()->getStyle("A1:G$row_count")->applyFromArray(array(
        'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => 'AAAAAA')))
      ));
      $objPHPExcel->getActiveSheet()->getStyle("C1:D$row_count")->applyFromArray(array(
        'borders' => array('outline' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => '333333')))
      ));
      $objPHPExcel->getActiveSheet()->getStyle("F1:F$row_count")->applyFromArray(array(
        'borders' => array('right' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => '333333')))
      ));
      $objPHPExcel->getActiveSheet()->getStyle("A1:G$row_count")->applyFromArray(array(
        'borders' => array('outline' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => '000000')))
      ));
    }
    if($download_xl || $download_equipment_xl) {
      header('Content-type: application/vnd.ms-excel');
      header('Content-Disposition: attachment; filename="nrl_' . ($download_xl ? ($invoice ? "invoice" : "quote") : "equipment_issue") . '.xlsx"');
      $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel); 
      $objWriter->save('php://output');
      exit;
    }
    $show_min = (isset($_GET['show_min']) ? $_GET['show_min'] : '');
    //require_once(($show_min ? "top_min" : "top") . ".php");
    $zone_edit = (isset($_GET['zone_edit']) ? $_GET['zone_edit'] : null);
    $rate_edit = (isset($_GET['rate_edit']) ? $_GET['rate_edit'] : null);
    $position_edit = (isset($_GET['position_edit']) ? $_GET['position_edit'] : null);
    $roster_edit = (isset($_GET['roster_edit']) ? $_GET['roster_edit'] : null);
    $equipment_issue_edit = (isset($_GET['equipment_issue_edit']) ? $_GET['equipment_issue_edit'] : null);
    $process = (isset($_GET['process']) ? $_GET['process'] : null);
    $tid = (isset($_GET['tid']) ? $_GET['tid'] : '');
    $rid = (isset($_GET['rid']) ? $_GET['rid'] : '');
    $game_edit = (isset($_GET['game_edit']) ? $_GET['game_edit'] : (empty($_GET) ? 1 : null));
    $adjust_positions = (isset($_GET['adjust_positions']) ? $_GET['adjust_positions'] : null);
    if($game_edit) {
      $this->list_obj->title = "NRL Games";
      $this->list_obj->sql = "
        select id as idin, title as `Title`, date as `Date`, gate_opening_time as `Gate Opening Time`, game_start_time as `Game Start Time`, if(is_public_holiday = 1, 'Yes', 'No') as `P/H`,
        CONCAT('<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."NrlRoster?roster_edit=', id, '\">Edit Roster</a>') as `Edit Roster`,
        CONCAT('<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."NrlRoster?equipment_issue_edit=', id, '\">Equipment Issue</a>') as `Equipment Issue`,
        'Edit' as `*`, 'Delete' as `!` from nrl_games
        where 1 $filter_string
        order by YEAR(date) DESC, date ASC
        ";
      $this->editor_obj->table = "nrl_games";
      $style = 'style="width: 420px;"';
      $style_small = 'style="width: 160px;"';
      $this->editor_obj->form_attributes = array(
               array("txtTitle", "calDate", "ti2GateOpeningTime", "ti2GameStartTime", "chkPublicHoliday"),
               array("Title", "Date", "Gate Opening Time", "Game Start Time", "Public Holiday"),
               array("title", "date", "gate_opening_time", "game_start_time", "is_public_holiday"),
               array("", "", "", "", ""),
               array($style, $style_small, $style_small, $style_small, ""),
               array("D", "D", "D", "D", "n")
      );
        $this->editor_obj->button_attributes = array(
        array("Add New", "Save", "Reset"),
        array("cmdAdd", "cmdSave", "cmdReset", "cmdFilter"),
        array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();", "filter()"),
        array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit", "js_function_filter")
      );
      $this->editor_obj->form_template = '
                <div class="fl"><nobr>ttxtTitle</nobr><br />txtTitle</div>
                <div class="fl"><nobr>tcalDate</nobr><br />calDate</div>
                <div class="fl"><nobr>tti2GateOpeningTime</nobr><br />ti2GateOpeningTime</div>
                <div class="fl"><nobr>tti2GameStartTime</nobr><br />ti2GameStartTime</div>
                <div class="cl"></div>
                <div class="fr">chkPublicHoliday tchkPublicHoliday</div>
                <div class="cl"></div>
                '.$this->editor_obj->button_list();
      $this->editor_obj->editor_template = '
                  <div class="form-wrapper">
                  <div class="form-header">NRL Games</div>
                  <div class="form-content">
                  editor_form
                  </div>
                  </div>
                  <div class="cl"></div>
                  editor_list
      ';
      $str .= $this->editor_obj->draw_data_editor($this->list_obj);
    } else if($zone_edit) {
      $str .= '<a href="NrlRoster?adjust_positions=1" class="list_a">Adjust Position Orders</a><div class="cl"></div><br /><br />';
      $this->list_obj->title = "NRL Zones";
      $this->list_obj->sql = "
        select id as idin, sort_order as `Sort Order`, name as `Name`, supervisor_call_sign as `Call Sign`,
        CONCAT('<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."NrlRoster?position_edit=', id, '\">Edit Positions</a>') as `Edit Positions`,
        CONCAT('<a class=\"list_a\" href=\"nrl_roster.php?update_rate_id=', id, '\">Update Rates</a>') as `Update Rates`,
        'Edit' as `*`, 'Delete' as `!` from nrl_zones
        where 1 $filter_string
        order by sort_order
        ";
      $this->editor_obj->table = "nrl_zones";
      $style = 'style="width: 280px;"';
      $style_small = 'style="width: 140px;"';
      $this->editor_obj->form_attributes = array(
               array("txtSortOrder", "txtName", "txtSupervisorCallSign"),
               array("Sort Order", "Name", "Call Sign"),
               array("sort_order", "name", "supervisor_call_sign"),
               array("", "", ""),
               array($style_small, $style, $style),
               array("D", "D", "n")
      );
      $this->editor_obj->button_attributes = array(
        array("Add New", "Save", "Reset"),
        array("cmdAdd", "cmdSave", "cmdReset"),
        array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
        array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
      );
      $this->editor_obj->form_template = '
                <div class="fl"><nobr>ttxtSortOrder</nobr><br />txtSortOrder</div>
                <div class="fl"><nobr>ttxtName</nobr><br />txtName</div>
                <div class="fl"><nobr>ttxtSupervisorCallSign</nobr><br />txtSupervisorCallSign</div>
                <div class="cl"></div>
                '.$this->editor_obj->button_list();
      $this->editor_obj->editor_template = '
                  <div class="form-wrapper">
                  <div class="form-header">NRL Zones</div>
                  <div class="form-content">
                  editor_form
                  </div>
                  </div>
                  <div class="cl"></div>
                  editor_list
      ';
      $str .= $this->editor_obj->draw_data_editor($this->list_obj);
    } else if($rate_edit) {
      $this->list_obj->title = "NRL Rates";
      $this->list_obj->sql = "
        select id as idin, name as `Name`, weekday as `Weekday Rate`, weekend as `Weekend Rate`, public_holiday as `P/H Rate`,
        'Edit' as `*`, 'Delete' as `!` from nrl_rates
        where 1
        order by name
        ";
      $this->editor_obj->table = "nrl_rates";
      $style = 'style="width: 280px;"';
      $style_small = 'style="width: 140px;"';
      $this->editor_obj->form_attributes = array(
               array("txtName", "txtWeekday", "txtWeekend", "txtPublicHoliday"),
               array("Name", "Weekday", "Weekend Rate", "P/H Rate"),
               array("name", "weekday", "weekend", "public_holiday"),
               array("", "", "", ""),
               array($style, $style_small, $style_small, $style_small),
               array("D", "D", "n", "n")
      );
      $this->editor_obj->button_attributes = array(
        array("Add New", "Save", "Reset"),
        array("cmdAdd", "cmdSave", "cmdReset"),
        array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
        array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
      );
      $this->editor_obj->form_template = '
                <div class="fl"><nobr>ttxtName</nobr><br />txtName</div>
                <div class="fl"><nobr>ttxtWeekday</nobr><br />txtWeekday</div>
                <div class="fl"><nobr>ttxtWeekend</nobr><br />txtWeekend</div>
                <div class="fl"><nobr>ttxtPublicHoliday</nobr><br />txtPublicHoliday</div>
                <div class="cl"></div>
                '.$this->editor_obj->button_list();
      $this->editor_obj->editor_template = '
                  <div class="form-wrapper">
                  <div class="form-header">NRL Rates</div>
                  <div class="form-content">
                  editor_form
                  </div>
                  </div>
                  <div class="cl"></div>
                  editor_list
      ';
      $str .= $this->editor_obj->draw_data_editor($this->list_obj);
    } else if($position_edit) {
      $sql = "select id, name from nrl_zones";
      $result = $this->dbi->query($sql);
      while($myrow = $result->fetch_assoc()) {
        $zone_id = $myrow['id'];
        $zone_name_tmp = $myrow['name'];
        if($zone_id == $position_edit) {
          $zone_name = $zone_name_tmp;
          $zone_list .= '<b class="list_a">'.$zone_name_tmp.'</b>    ';
        } else {
          $zone_list .= '<a class="list_a" href="?position_edit='.$zone_id.'">'.$zone_name_tmp.'</a>    ';
        }
      }
      $this->list_obj->call_sign = "Positions for $zone_name";
      $this->list_obj->sql = "
            select nrl_zone_positions.id as idin, nrl_zone_positions.sort_order as `Srt`, nrl_zone_positions.position as `Pos`, nrl_zone_positions.name as `Name`,
            nrl_zone_positions.call_sign as `Call Sign`, nrl_zone_positions.common_start_minutes as `Common<br/>Start<br/>(Minutes)`, nrl_zone_positions.common_finish_minutes as `Common<br/>Finish<br/>(Minutes)`, nrl_rates.name as `Rate`, nrl_rates.weekday as `Weekday`, nrl_rates.weekend as `Weekend`,
            'Edit' as `*`, 'Delete' as `!`
            FROM nrl_zone_positions
            left join nrl_rates on nrl_rates.id = nrl_zone_positions.rate_id
            where zone_id = $position_edit
            order by nrl_zone_positions.sort_order
            $filter_string
        ";
      $tmp_sql = "select 0 as id, '--- Select ---' as item_name union all select nrl_rates.id, name as `item_name` from nrl_rates";
      $this->editor_obj->xtra_id_name = "zone_id";
      $this->editor_obj->xtra_id = $position_edit;
      $list_top = "select * from (select 0 as id, -10000 as `sort_order`, '--- Select ---' as item_name union all";
      $this->editor_obj->table = "nrl_zone_positions";
        $style_small = 'style="width: 100px;"';
        $style = 'style="width: 190px;"';
        $style_big = 'style="width: 300px;"';
        $this->editor_obj->form_attributes = array(
                 array("chlTemplate", "txtSortOrder", "txtPosition", "txtName", "txtCallSign", "txtCommonStartMinutes", "txtCommonFinishMinutes", "selCommonRate"),
                 array("Template", "Sort Order", "Position", "Name", "Call Sign", "Common Start (Minutes)", "Common Finish (Minutes)", "Rate"),
                 array("sort_order", "sort_order", "position", "name", "call_sign", "common_start_minutes", "common_finish_minutes", "rate_id"),
                 array("select id, item_name from lookup_fields where lookup_id in (select id from lookups where item_name = 'nrl_templates') order by sort_order, item_name", "", "", "", "", "", "", "$tmp_sql"),
                 array("", $style_small, $style_small, $style_big, $style, $style . ' title="Number of minutes to start before/after the start time; use a negative number for before (e.g. -60)."', $style . ' title="Number of minutes to finish before/after the start time; use a negative number for before (e.g. -60)."', $style_big),
                 array("", "D", "D", "D", "D", "n", "n", "n"),
                 array("nrl_templates", "", "", "", "", "", "", "")
        );
      $this->editor_obj->button_attributes = array(
        array("Add New", "Save", "Reset"),
        array("cmdAdd", "cmdSave", "cmdReset"),
        array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
        array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
      );
      $this->editor_obj->form_template = '
                <div class="fl"><nobr>ttxtSortOrder</nobr><br />txtSortOrder</div>
                <div class="fl"><nobr>ttxtPosition</nobr><br />txtPosition</div>
                <div class="fl"><nobr>ttxtName</nobr><br />txtName</div>
                <div class="fl"><nobr>ttxtCallSign</nobr><br />txtCallSign</div>
                <div class="fl"><nobr>ttxtCommonStartMinutes</nobr><br />txtCommonStartMinutes</div>
                <div class="fl"><nobr>ttxtCommonFinishMinutes</nobr><br />txtCommonFinishMinutes</div>
                <div class="fl"><nobr>tselCommonRate</nobr><br />selCommonRate</div>
                <div class="cl"></div>
                <br />tchlTemplate<br />chlTemplate
                <div class="cl"></div><br />
                '.$this->editor_obj->button_list();
      $this->editor_obj->editor_template = '
                  <p>'.$zone_list.'</p>
                  <div class="form-wrapper">
                  <div class="form-header">Positions for '.$zone_name.'</div>
                  <div class="form-content">
                  editor_form
                  </div>
                  </div>
                  <div class="cl"></div>
                  editor_list
      ';
      return $this->editor_obj->draw_data_editor($this->list_obj);
      $str .= '<p><a class="list_a" href="NrlRoster?zone_edit=1"><< Back to Zones</a></p>';
    } else if($roster_edit || $equipment_issue_edit) {
      $num_cols = ($equipment_issue_edit ? 2 : 13);
      $sql = "select count(id) as `num_rows` from nrl_rosters where game_id = " . ($roster_edit ? $roster_edit : $equipment_issue_edit);
      $result = $this->dbi->query($sql);
      if($myrow = $result->fetch_assoc()) $num_rows = $myrow['num_rows'];
      if(!$num_rows) $num_rows = '0';
      if($_POST['hdnSave']) {
        $cols = ($equipment_issue_edit ? array("radio_number", "equipment_comment") : array("position", "zone_position", "call_sign", "start_time", "finish_time", "sub_contractor", "staff_name", "security_licence_number", "licence_type", "licence_expiry", "actual_start_time", "actual_finish_time", "comment"));
        $sql = "";
        $cnt = 0;
        $cnt_chk = 0;
        foreach ($_POST as $key => $value) {
          if(strpos($key, '_') !== false && strpos($key, 'chk') === false && strpos($key, 'hidden') === false) {
            $tmp = explode('_', $key);
            $rcount = $tmp[1];
            $rid = $tmp[2];
            $sql .= $cols[$cnt] . " = " . (strstr($cols[$cnt], 'time') && !$value ? "null" : "'" . mysqli_real_access_modetring($this->dbi, $value) . "'") . ($cnt == ($num_cols - 1) ? "" : ", ");
            $cnt = ($cnt == ($num_cols - 1) ? 0 : $cnt + 1);
            if(!$cnt && $_POST["hdn$rcount"]) {
              $sql = "update nrl_rosters set $sql where id = $rid;";
              $result = $this->dbi->query($sql);
            }
            if(!$cnt) $sql = "";
          } else if(strpos($key, 'hidden') !== false) {
            $tmp = explode('_', $key);
            $rcount = $tmp[1];
            $rid = $tmp[2];
            if($_POST["hdn$rcount"]) {
              $sql_chk = "update nrl_rosters set ".strtolower(str_replace("X", "_", substr($tmp[0], 6)))." = ".($value == "yes" ? "1" : "0")." where id = $rid;";
              $result = $this->dbi->query($sql_chk);
            }
          }
        }
        $msg = "Saved...";
      }
      $str .= '
      <div id="message"></div>
      <style>
        .hints_button {
          padding: 2px 10px 2px 10px !important;
        }
        .instructions {
          display: none;
          margin-top: 5px;
          margin-bottom: 10px;
          border: 1px solid #660000;
          background-color: #FFFFEE;
          padding: 5px;
        }
        #message {
          position: fixed;
          left: 0px; top: ' . ($roster_edit ? "10" : "27") . 'px;
          display: none;
          margin-top: 30px;
          margin-bottom: 10px;
          border: 1px solid white;
          background-color: red;
          color: white;
          padding: 10px;
          font-weight: bold;
          font-size: 24pt;
        }
        .nav_bar {
          position: fixed;
          left: 0px; top: 0px;
          display: block;
          border: 1px solid white;
          background-color: #000066;
          font-weight: bold;
          color: #FFFFEE;
          font-size: 16pt;
        }
        .nav_item {
          padding-top: 15px;
          padding-bottom: 15px;
          display: inline-block;
          border-left: 1px solid #FFFFEE;
        }
        .nav_img {
          padding-top: 15px;
          padding-left: 8px;
          padding-right: 8px;
          height: 20px;
        }
        .nav_item a {
          color: #FFFFEE;
          font-size: 16pt;
          padding-left: 25px;
          padding-right: 25px;
        }
        .nav_item a:hover {
          text-decoration: none;
        }
        table.xl_grid {
          border-width: 1px;
          border-spacing: 0px;
          border-style: outset;
          border-color: #CCCCCC;
          border-collapse: collapse;
          background-color: white !important;
        }
        table.xl_grid th {
          border-width: 1px;
          padding: 4px;
          border-style: solid;
          border: 1px solid #CCCCCC;
          background-color: #DDDDDD;
          font-weight: bold;
          text-align: left !important;
          vertical-align: bottom; 
        }
        table.xl_grid td {
          border-width: 1px;
          padding: 0px;
          border-style: solid;
          border-color: #CCCCCC;
          background-color: white !important;
        }
        input[type=text] {
          border: none !important;
        }

        table.xl_grid tr:nth-child(even) {background-color: #F9F9F9;}
        table.xl_grid tr:nth-child(odd) {background-color: #FFFFFF;}
        .xl_cell { border: 1px solid transparent !important; font-size: 12pt !important;}
        .xl_cell:focus { outline: 2px solid #999999 !important; }
        .xl_cell:hover { background-color: #F9FFF3; }
        .tiny_text_box {  width: 25px;  }
        .small_medium_text_box {  width: 100px;  }
        .small_text_box {  width: 55px;  }
        .medium_text_box {  width: 160px;  }
        .medium_large_text_box {  width: 280px;  }
        .large_text_box {  width: 360px;  }
        .sel_itm input[type=checkbox] {
          width: ' . ($roster_edit ? 30 : 45) . 'px !important;
          height: ' . ($roster_edit ? 30 : 45) . 'px !important;
        }
        .sel_itm {
          display: block;
          padding-top: 2px !important;      padding-bottom: 0px !important;
          padding-left: 5px !important;      padding-right: 5px !important;
        }
        .sel_itm:hover {
          background-color: #F9FFF3;
        }
      </style>
      <script>
        var current_item;
        var offline_shown = 0;
      function check_connection() {
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
      function save_list() {
        if(!check_connection()) {
          alert("Currently NOT online.\n\nThe form HAS NOT been submitted.\n\nPlease check your connection and try again...")
        } else {
          document.getElementById("hdnSave").value = 1;
          document.frmEdit.submit();
        }
      }
      function show_hide(id) {
        if(document.getElementById(id).style.display == \'block\') {
          document.getElementById(id).style.display = \'none\';
          document.getElementById(\'cmdHints\').value = \'Show Instructions\';
        } else {
          document.getElementById(id).style.display = \'block\';
          document.getElementById(\'cmdHints\').value = \'Hide Instructions\';
        }
      }
      function move_focus(e, col, row, curr_id) {
        var ctl = document.getElementById(\'txt\' + col + \'_\' + row);
        var up = 38, down = 40, left = 37, right = 39, pgup = 33, pgdown = 34;
        var keynum;
        var page_step = 75;
        keynum = (window.event ? e.keyCode : keynum = e.which);
        //alert(ctl.value)
        if(keynum == down) {
          document.getElementById(\'txt\' + col + \'_\' + (row == num_rows ? 1 : parseInt(row+1))).focus();
        } else if(keynum == up) {
          document.getElementById(\'txt\' + col + \'_\' + (row == 1 ? num_rows : parseInt(row-1))).focus();
        } else if(keynum == left || keynum == right) {
          var startPos = ctl.selectionStart;
          var endPos = ctl.selectionEnd;
          var len = ctl.value.length
          if(keynum == right) {
            if(len == endPos) {
              document.getElementById(\'txt\' + (col == num_cols ? 1 : parseInt(col+1)) + \'_\' + row).focus();
              document.getElementById(\'txt\' + (col == num_cols ? 1 : parseInt(col+1)) + \'_\' + row).selectionEnd = document.getElementById(\'txt\' + (col == num_cols ? 1 : parseInt(col+1)) + \'_\' + row).value.length;
            }
          } else {
            if(!startPos) {
              document.getElementById(\'txt\' + (col == 1 ? num_cols : parseInt(col-1)) + \'_\' + row).focus();
              document.getElementById(\'txt\' + (col == 1 ? num_cols : parseInt(col-1)) + \'_\' + row).selectionStart = 0;
            }
          }
        } else if(keynum == pgdown) {
          document.getElementById(\'txt\' + col + \'_\' + ((row + page_step) > num_rows ? parseInt(num_rows) : parseInt(row + page_step))).focus();
        } else if(keynum == pgup) {
          document.getElementById(\'txt\' + col + \'_\' + ((row - page_step < 1) ? 1 : parseInt(row - page_step))).focus();
        } else {
          document.getElementById(\'hdn\' + row).value = curr_id;
        }
        (function() {
          var _old_alert = window.alert;
          window.alert = function() {
            document.body.innerHTML += ""; // run some code when the alert pops up 
            _old_alert.apply(window,arguments);
            document.getElementById(\'txt\' + col + \'_\' + row).focus(); // run some code after the alert
          };
        })();
      }
      function change_item(row, curr_id) {
        document.getElementById(\'hdn\' + row).value = curr_id;
      }
      function save_item(col, row, curr_id) {
        if(!check_connection()) {
          document.getElementById("message").innerHTML = \'Currently NOT online. Please save the ' . ($roster_edit ? "roster" : "equipment issue form") . ' when the connection is restored...\'
          document.getElementById("message").style.display = \'block\';
          offline_shown = 1;
        } else {
          if(offline_shown) {
            document.getElementById("message").innerHTML = \'BACK ONLINE - PLEASE SAVE THE ' . ($roster_edit ? "ROSTER" : "EQUIPMENT ISSUE FORM") . ' NOW.\'
          }
          var test
          var ctl = document.getElementById(\'txt\' + col + \'_\' + row);
          var pos = document.getElementById(\'txt1_\' + row);
          if (window.XMLHttpRequest) {  xmlhttp = new XMLHttpRequest(); } else { xmlhttp = new ActiveXObject("Microsoft.XMLHTTP"); }
          xmlhttp.open("GET","'.$this->f3->get('main_folder').'SaveNrlItem?item_id="+curr_id+"&col="+col+"&txt="+encodeURIComponent(ctl.value)' . ($equipment_issue_edit ? '\'+"&equip=1"\'' : "") . '+"&game_id=' . $roster_edit . '",true);
          xmlhttp.send();
      //xmlhttp.onreadystatechange=function() { if (this.readyState==4 && this.status==200) { document.getElementById("message").innerHTML=this.responseText; document.getElementById("message").style.display = "block"; } }  
          if(col > 5 || col <= 8) {
            num_rows = ' . $num_rows . ';
            for(x = (row + 1); x <= num_rows; x++) {
              test = document.getElementById(\'txt1\' + \'_\' + x)
              if(test.value == pos.value) {
                document.getElementById(\'txt\' + col + \'_\' + x).value = ctl.value;
              }
            }
          }
//              document.getElementById("message").innerHTML = col;
  //          document.getElementById("message").style.display = \'block\';
        }
      }
      function toggle_itm(item_id, row, curr_id) {
        change_item(row, curr_id)
        var ctl = document.getElementById(item_id + "_" + row);
        var htl = document.getElementById("hidden" + item_id.substring(3) + "_" + row);
        htl.value = (ctl.checked ? "yes" : "no");
        if(!check_connection()) {
          document.getElementById("message").innerHTML = \'Currently NOT online. Please save the ' . ($roster_edit ? "roster" : "equipment issue form") . ' when the connection is restored...\'
          document.getElementById("message").style.display = \'block\';
          offline_shown = 1;
        } else {
          if(offline_shown) {
            document.getElementById("message").innerHTML = \'BACK ONLINE - THE ' . ($roster_edit ? "ROSTER" : "EQUIPMENT ISSUE FORM") . ' NEEDS TO BE SAVED...\'
          }
          if (window.XMLHttpRequest) {  xmlhttp = new XMLHttpRequest(); } else { xmlhttp = new ActiveXObject("Microsoft.XMLHTTP"); }
          xmlhttp.open("GET","'.$this->f3->get('main_folder').'SaveNrlItem?item_id="+curr_id+"&txt="+ctl.checked+"&chk="+item_id,true);
          xmlhttp.send();
        }
      }
      </script>
      <input type="hidden" name="hdnSave" id="hdnSave">
      ';
      $sql = "select title, DAYOFWEEK(date) as `day`, DATE_FORMAT(date, '%a, %d-%b-%Y') as `game_date`, DATE_FORMAT(game_start_time, '%H:%i') as `game_start_time`,
      DATE_FORMAT(gate_opening_time, '%H:%i') as `gate_opening_time`, is_public_holiday from nrl_games where id = " . ($roster_edit ? $roster_edit : $equipment_issue_edit);
      $result = $this->dbi->query($sql);
      if($myrow = $result->fetch_assoc()) {
        $game_title = $myrow['title'];
        $game_day = $myrow['day'];
        $game_date = $myrow['game_date'];
        $game_start_time = $myrow['game_start_time'];
        $gate_opening_time = $myrow['gate_opening_time'];
        $is_public_holiday = $myrow['is_public_holiday'];
      }
      $str .= "<div class=\"fl\"><h3>".'<input class="hints_button" type="button" id="cmdHints" name="cmdHints" id="cmdHints" onClick="show_hide(\'instructions\')" value="Show Instructions" />    '."$game_title on $game_date ".($is_public_holiday ? "(Public Holiday)" : "")." | GATES: $gate_opening_time | KICK OFF: $game_start_time</h3></div>";
    }
    if($roster_edit) {
      $str .= '<div class="fr"><a class="list_a" href="?download_xl='.$roster_edit.'">Download Excel Quote</a><a class="list_a" href="?download_xl='.$roster_edit.'&invoice=1">Download Excel Invoice</a></div><div class="cl"></div>';
      $str .= '<div class="instructions" id="instructions"><h3>Instructions</h3><ol>
      <li><b>Each item is now automatically saved as it is entered.</b></li>
      <li>If the Internet access drops out; a message show. You may still proceed entering data; however, click [Save] when the access is restored.</li>
      <li>Use the arrow keys to navigate.</li>
      <li>Clicking [Copy] will make a copy of the row.</li>
      <li>clicking [X] will remove the row.</li>
      <li>To make the Started/Finished times the same as the Start/Finish times, double-click on them.</li>
      <li>USE 24 HOUR TIMES ONLY. Entering times are as easy as typing 23 for 23:00 or 2330 for 23:30 for example.</li>
      </ol>
      <p><a class="list_a" href="https://Edge.scgs.com.au/download_file.php?fl=VwzvYfHjy8nJNa8RECPvWw8uXRFFSHJlAS7wlk8yqMCEjUl%2Fwve%2B8RuEb7Gc6UsgkblsDfY2%2BigasaJXrKWAkw&f=A0nS5a%2Fg9xvyOR3iX3uTtg1dlRtQdhagfSjCgoSjSl49kJ0%2FDKF3f2svxNg9RNwMLIRcDCC4GUVrWm4GUSi5PhJAMiYOEyRHUYlzpMclgDKw%3D%3D">Download User Guide...</a></p>
      </div>';
      $sql = "select users.name from users inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = 2024 and lookup_answers.table_assoc = 'users'
              and users.user_status_id = (select id from user_status where item_name = 'ACTIVE') order by users.name";
      $result = $this->dbi->query($sql);
      while($myrow = $result->fetch_assoc()) $dl_items .= "<option value=\"".$myrow['name']."\"/>";
    //}
    $str .= '
      <script>
        var num_cols = ' . $num_cols. '
        var num_rows = ' . $num_rows. '
        function load_roster(num_items) {
          var go = 0;
          if(num_items) {
            var confirmation;
            confirmation = \'This will remove all current roster records from this game; are you sure?\';
            if (confirm(confirmation)) {
              go = 1
            }
          } else {
            go = 1
          }
          if (go) {
            document.getElementById("hdnLoadRoster").value = 1;
            document.frmEdit.submit();
          }
        }
        function copy_roster() {
          var confirmation;
          confirmation = \'This will remove all current roster records from this game; are you sure?\';
          if (confirm(confirmation)) {
            document.getElementById("hdnCopyRoster").value = 1;
            document.frmEdit.submit();
          }
        }
        function clone_item(itm) {
          if(!check_connection()) {
            alert("Currently NOT online.\n\nThe item HAS NOT been copied.\n\nPlease check your connection and try again...")
          } else {
            document.getElementById("hdnClone").value = itm;
            document.frmEdit.submit();
          }
        }
        function delete_item(itm) {
          if(!check_connection()) {
            alert("Currently NOT online.\n\nThe item HAS NOT been deleted.\n\nPlease check your connection and try again...")
          } else {
            var confirmation;
            confirmation = \'Are you sure about deleting this record?\';
            if (confirm(confirmation)) {
              document.getElementById("hdnDelete").value = itm;
              document.frmEdit.submit();
            }
          }
        }
        function time_change(col, row, curr_id) {
          document.getElementById(\'txt\' + col + \'_\' + row).value = document.getElementById(\'txt\' + parseInt(col-5) + \'_\' + row).value;
          change_item(row, curr_id);
          save_item(col, row, curr_id);
        }
      </script>
      ';
      if($_POST['hdnLoadRoster']) {
        $this->dbi->query("delete from nrl_rosters where game_id = $roster_edit;");
        $rate_str = "if(nrl_rates.weekend, nrl_rates." . ($game_day == 1 || $game_day == 7 ? "weekend" : "weekday") . ", nrl_rates.weekday)";
        if($is_public_holiday) $rate_str = "if(nrl_rates.public_holiday, nrl_rates.public_holiday, $rate_str)";
        $sql = "insert into nrl_rosters (game_id, sort_order, position, zone, zone_position, call_sign, start_time, finish_time, hourly_rate) (
          select '$roster_edit', nrl_zone_positions.sort_order, nrl_zone_positions.position, nrl_zones.name, nrl_zone_positions.name, nrl_zone_positions.call_sign,
          DATE_FORMAT(DATE_ADD((select CONCAT(date, ' ', gate_opening_time) from nrl_games where id = $roster_edit), INTERVAL nrl_zone_positions.common_start_minutes MINUTE), '%H:%i'),
          DATE_FORMAT(DATE_ADD((select CONCAT(date, ' ', gate_opening_time) from nrl_games where id = $roster_edit), INTERVAL nrl_zone_positions.common_finish_minutes MINUTE), '%H:%i'),
          $rate_str
          from nrl_zone_positions
          left join nrl_rates on nrl_rates.id = nrl_zone_positions.rate_id
          left join nrl_zones on nrl_zones.id = nrl_zone_positions.zone_id
          where nrl_zone_positions.id in (select foreign_id from lookup_answers where table_assoc = 'nrl_zone_positions' and lookup_field_id = ".$_POST['selTemplate'].")
          order by nrl_zones.sort_order, nrl_zone_positions.sort_order
          );
        ";
        $msg = "Roster Loaded...";
        $result = $this->dbi->query($sql);
      }
      if($_POST['hdnCopyRoster']) {
        $rate_str = "if(nrl_rates.weekend, nrl_rates." . ($game_day == 1 || $game_day == 7 ? "weekend" : "weekday") . ", nrl_rates.weekday)";
        if($is_public_holiday) $rate_str = "if(nrl_rates.public_holiday, nrl_rates.public_holiday, $rate_str)";
        $sql = "insert into nrl_rosters (game_id, sort_order, position, zone, zone_position, call_sign, start_time, finish_time, hourly_rate) 
          select '$roster_edit', sort_order, position, zone, zone_position, call_sign, start_time, finish_time, hourly_rate from nrl_rosters
          where game_id = ".$_POST['selGameCopy']."
          order by sort_order;
        ";
        $msg = "Roster Copied...";
        $result = $this->dbi->query($sql);
      }
      if($_POST['hdnClone']) {
        $change_id = $_POST['hdnClone'];
        $result = $this->dbi->query("select sort_order from nrl_rosters where id = $change_id;");
        if($myrow = $result->fetch_assoc()) $so = $myrow['sort_order'];
        $sql = "update nrl_rosters set sort_order = sort_order + 1 where sort_order > $so and game_id = $roster_edit order by sort_order";
        $result = $this->dbi->query($sql);
        $sql = "insert into nrl_rosters (game_id, sort_order, zone, zone_position, call_sign, start_time, finish_time, hourly_rate) (
          select game_id, (sort_order + 1), zone, zone_position, call_sign, start_time, finish_time, hourly_rate from nrl_rosters where id = $change_id
          );
        ";
        $result = $this->dbi->query($sql);
        $msg = "Item Copied...";
      }
      if($_POST['hdnDelete']) {
        $del_id = $_POST['hdnDelete'];
        $result = $this->dbi->query("select sort_order from nrl_rosters where id = $del_id;");
        if($myrow = $result->fetch_assoc()) $so = $myrow['sort_order'];
        $sql = "update nrl_rosters set sort_order = sort_order - 1 where sort_order > $so and game_id = $roster_edit order by sort_order";
        $result = $this->dbi->query($sql);
        $sql = "delete from nrl_rosters where id = $del_id";
        $result = $this->dbi->query($sql);
        $msg = "Item Deleted...";
      }
      $sql = "
        select nrl_rosters.id as `roster_id`, nrl_rosters.zone, nrl_rosters.zone_position, nrl_rosters.position, nrl_rosters.call_sign, nrl_rosters.sub_contractor,
        DATE_FORMAT(nrl_rosters.start_time, '%H:%i') as `start_time`, DATE_FORMAT(nrl_rosters.finish_time, '%H:%i') as `finish_time`,
        nrl_rosters.staff_name,
        DATE_FORMAT(nrl_rosters.actual_start_time, '%H:%i') as `actual_start_time`,
        DATE_FORMAT(nrl_rosters.actual_finish_time, '%H:%i') as `actual_finish_time`,
        nrl_rosters.hourly_rate, nrl_rosters.comment, nrl_rosters.security_licence_number, nrl_rosters.licence_type, nrl_rosters.licence_expiry
        from nrl_rosters
        left join nrl_games on nrl_games.id = nrl_rosters.game_id
        where nrl_rosters.game_id = $roster_edit
        order by nrl_rosters.sort_order
      ";
      $itm = new input_item;
      $itm->hide_filter = 1;
      $str .= $itm->setup_ti2();
//      $str .= $itm->setup_cal();
      $result = $this->dbi->query($sql);
      $header_row = "<tr><th style=\"width: 15px;\">#</th><th>Position</th><th>Call Sign</th><th>Start</th><th>Finish</th><th>Sub Contractor</th><th>Staff Name</th><th>Security Licence #</th><th>Type</th><th>Expiry</th><th>Start</th><th>Finish</th><th>Comment</th><th> </th></tr>";
      $str .= '<table class="xl_grid">';
      while($myrow = $result->fetch_assoc()) {
        $row++;
        $roster_id = $myrow['roster_id']; 
        $zone = $myrow['zone'];
        $row_str = $row . "_" . $roster_id;
        $position = $myrow['position'];
        $position = $itm->txt("txt1_$row|1_$row_str", "", ' onblur="save_item(1, '.$row.', '.$roster_id.')" onkeydown="move_focus(event, 1, '.$row.', '.$roster_id.')" class="tiny_text_box xl_cell" style="color: red !important; font-weight: bold;" value="'.$myrow['position'].'" ', "", "", "");
        $zone_position = $itm->txt("txt2_$row|2_$row_str", "", ' onblur="save_item(2, '.$row.', '.$roster_id.')" onkeydown="move_focus(event, 2, '.$row.', '.$roster_id.')" class="large_text_box xl_cell" value="'.$myrow['zone_position'].'" ', "", "", "");
        $call_sign = $itm->txt("txt3_$row|3_$row_str", "", ' onblur="save_item(3, '.$row.', '.$roster_id.')" onkeydown="move_focus(event, 3, '.$row.', '.$roster_id.')" class="medium_text_box xl_cell" value="'.$myrow['call_sign'].'" ', "", "", "");
        $itm->blur_xtra = 'save_item(4, '.$row.', '.$roster_id.');';
        $start_time = $itm->ti2("txt4_$row|4_$row_str", "", ' onkeydown="move_focus(event, 4, '.$row.', '.$roster_id.')" class="small_text_box xl_cell" value="'.$myrow['start_time'].'" ', "", "", "");
        $itm->blur_xtra = 'save_item(5, '.$row.', '.$roster_id.');';
        $finish_time = $itm->ti2("txt5_$row|5_$row_str", "", ' onkeydown="move_focus(event, 5, '.$row.', '.$roster_id.')" class="small_text_box xl_cell" value="'.$myrow['finish_time'].'" ', "", "", "");
        $sub_contractor = $itm->txt("txt6_$row|6_$row_str", "", ' oninput="change_item('.$row.', '.$roster_id.')" onblur="save_item(6, '.$row.', '.$roster_id.')" onkeydown="move_focus(event, 6, '.$row.', '.$roster_id.')" class="medium_text_box xl_cell" value="'.$myrow['sub_contractor'].'" list="l'.$row_str.'"', "", "", "");
        $sub_contractor .= '<datalist id="l'.$row_str.'">'.$dl_items.'</datalist>';
        $staff_name = $itm->txt("txt7_$row|7_$row_str", "", ' onblur="save_item(7, '.$row.', '.$roster_id.')" onkeydown="move_focus(event, 7, '.$row.', '.$roster_id.')" class="medium_text_box xl_cell" value="'.$myrow['staff_name'].'" ', "", "", "");
        $security_licence_number = $itm->txt("txt8_$row|8_$row_str", "", ' onblur="save_item(8, '.$row.', '.$roster_id.');" onkeydown="move_focus(event, 8, '.$row.', '.$roster_id.');" class="medium_text_box xl_cell" value="'.$myrow['security_licence_number'].'" ', "", "", "");
        $licence_type = $itm->txt("txt9_$row|9_$row_str", "", ' onblur="save_item(9, '.$row.', '.$roster_id.')" onkeydown="move_focus(event, 9, '.$row.', '.$roster_id.')" class="small_text_box xl_cell" value="'.$myrow['licence_type'].'" ', "", "", "");
        $licence_expiry = $itm->txt("txt10_$row|10_$row_str", "", ' onblur="save_item(10, '.$row.', '.$roster_id.')" onkeydown="move_focus(event, 10, '.$row.', '.$roster_id.')" class="small_medium_text_box xl_cell" value="'.$myrow['licence_expiry'].'" ', "", "", "");
        $itm->blur_xtra = 'save_item(11, '.$row.', '.$roster_id.');';
        $actual_start_time = $itm->ti2("txt11_$row|11_$row_str", "", ' ondblclick="time_change(11, '.$row.', '.$roster_id.');" onblur="save_item(11, '.$row.', '.$roster_id.');" onkeydown="move_focus(event, 11, '.$row.', '.$roster_id.');" class="small_text_box xl_cell" value="'.$myrow['actual_start_time'].'" ', "", "", "");
        $itm->blur_xtra = 'save_item(12, '.$row.', '.$roster_id.');';
        $actual_finish_time = $itm->ti2("txt12_$row|12_$row_str", "", ' ondblclick="time_change(12, '.$row.', '.$roster_id.');" onblur="save_item(12, '.$row.', '.$roster_id.')" onkeydown="move_focus(event, 12, '.$row.', '.$roster_id.')" class="small_text_box xl_cell" value="'.$myrow['actual_finish_time'].'" ', "", "", "");
        $comment = $itm->txt("txt13_$row|13_$row_str", "", ' onblur="save_item(13, '.$row.', '.$roster_id.')" onkeydown="move_focus(event, 13, '.$row.', '.$roster_id.')" class="medium_large_text_box xl_cell" value="'.$myrow['comment'].'" ', "", "", "");


        $str .= $itm->hdn("hdn$row", "", "", "", "", "");
        $btns = "<nobr><input type=\"button\" onClick=\"clone_item('$roster_id')\" value=\"Copy\"><input type=\"button\" onClick=\"delete_item('$roster_id')\" value=\"X\"></nobr>";
        if($old_zone != $zone) $str .= "<tr><th colspan=\"3\"><h3>$zone</h3></th><th style=\"text-align: center !important;\" colspan=\"2\">Rostered</th><th colspan=\"2\"> </th><th style=\"text-align: center !important;\" colspan=\"3\">Licence Details</th><th style=\"text-align: center !important;\" colspan=\"2\">Actual</th><th>&nbsp;</th><th><input title=\"Save Roster\" onClick=\"save_list()\" type=\"button\" value=\"Save\"></th></tr>$header_row";
        $str .= "<tr><td style=\"color: red !important; font-weight: bold;\">$position</td><td>$zone_position</td><td>$call_sign</td><td>$start_time</td><td>$finish_time</td><td>$sub_contractor</td><td>$staff_name</td><td>$security_licence_number</td><td>$licence_type</td><td>$licence_expiry</td><td>$actual_start_time</td><td>$actual_finish_time</td><td>$comment</td><td>$btns</td></tr>";
        $old_zone = $zone;
      }
      $str .= "</table>";
      $game_sql = "select id, concat(title, ' (', date_format(date, '%a %D of %b, %Y'), ')') as `item_name` from nrl_games where id != $roster_edit order by date DESC";
      $game_sel = $itm->sel("selGameCopy", "", "", "", $this->dbi, $game_sql, "");
      $template_sql = "select id, item_name from lookup_fields where lookup_id in (select id from lookups where item_name = 'nrl_templates') order by sort_order, item_name";
      $template_sel = $itm->sel("selTemplate", "", "", "", $this->dbi, $template_sql, "");
      $str .= '
        <input type="hidden" name="hdnClone" id="hdnClone">
        <input type="hidden" name="hdnDelete" id="hdnDelete">
      ';
      if(!$row) {
        $str .= '
        <input type="hidden" name="hdnLoadRoster" id="hdnLoadRoster">
        <input type="hidden" name="hdnCopyRoster" id="hdnCopyRoster">
        <br /><br /><br />Template: '.$template_sel.'<input type="button" onClick="load_roster('.$row.')" value="Load Pre-existing Roster Values" />
        Game: '.$game_sel.'<input type="button" onClick="copy_roster('.$row.')" value="Load From Existing Game" />
        ';
      }
      if($msg) $str .= $this->message($msg, 2000);
  } else if($equipment_issue_edit) {
    $str .= '<div class="nav_bar"><a href="./"><img class="nav_img" src="images/home.png"></a><div class="nav_item">  Go To >>  </div><div class="nav_item"><a href="#top">Top</a></div>';
    for($x = 10; $x <= 80; $x+=10) $str .= "<div class=\"nav_item\"><a href=\"".$this->f3->get('main_folder')."#$x\">$x</a></div>";
    $str .= '<div class="nav_item"><a href="#bottom">Bottom</a></div></div>';
      $str .= '<script>
          var num_cols = ' . $num_cols. '
          var num_rows = ' . $num_rows. '
      </script>';
      $str .= '<div class="fr"><a class="list_a" href="?download_equipment_xl='.$equipment_issue_edit.'">Download Excel Equipment Issue</a></div><div class="cl"></div>';
      $str .= '<div class="instructions" id="instructions"><h3>Instructions</h3><ol>
      <li><b>Each item is now automatically saved as it is entered.</b></li>
      <li>If the Internet access drops out; a message show. You may still proceed entering data; however, click [Save] when the access is restored.</li>
      <li>Click each item to mark as issued or returned.</li>
      </ol>
      <p><a class="list_a" href="https://Edge.scgs.com.au/download_file.php?fl=VwzvYfHjy8nJNa8RECPvWw8uXRFFSHJlAS7wlk8yqMCEjUl%2Fwve%2B8RuEb7Gc6UsgkblsDfY2%2BigasaJXrKWAkw&f=A0nS5a%2Fg9xvyOR3iX3uTtg1dlRtQdhagfSjCgoSjSl49kJ0%2FDKF3f2svxNg9RNwMLIRcDCC4GUVrWm4GUSi5PhJAMiYOEyRHUYlzpMclgDKw%3D%3D">Download User Guide...</a></p>
      </div>';
      $sql = "
        select nrl_rosters.id as `roster_id`, nrl_rosters.zone, nrl_rosters.position, nrl_rosters.radio_number, nrl_rosters.equipment_issued, nrl_rosters.radio_returned, nrl_rosters.equipment_returned, nrl_rosters.equipment_comment, nrl_rosters.staff_name
        from nrl_rosters
        where nrl_rosters.game_id = $equipment_issue_edit
        order by nrl_rosters.sort_order
      ";
      $itm = new input_item;
      $itm->hide_filter = 1;
      $str .= $itm->setup_ti2();
      $result = $this->dbi->query($sql);
      $header_row = "<tr><th style=\"width: 15px;\">#</th><th>Staff Name</th><th>Equip</th><th>Radio #</th><th>Equip</th><th>Radio</th><th>Comment</th></tr>";
      $str .= '<table class="xl_grid">';
      while($myrow = $result->fetch_assoc()) {
        $row++;
        $roster_id = $myrow['roster_id']; 
        $zone = $myrow['zone'];
        $row_str = $row . "_" . $roster_id;
        $position = $myrow['position'];
        $staff_name = $myrow['staff_name'];
        $row_str = $row . "_" . $roster_id;
        $radio_number = $itm->txt("txt1_$row|1_$row_str", "", ' onblur="save_item(1, '.$row.', '.$roster_id.')" onkeydown="move_focus(event, 1, '.$row.', '.$roster_id.')" class="medium_text_box xl_cell" value="'.$myrow['radio_number'].'" ', "", "", "");
        $comment = $itm->txt("txt2_$row|2_$row_str", "", ' onblur="save_item(2, '.$row.', '.$roster_id.')" onkeydown="move_focus(event, 2, '.$row.', '.$roster_id.')" class="medium_large_text_box xl_cell" value="'.$myrow['equipment_comment'].'" ', "", "", "");
        $equipment_issued = "<span class=\"sel_itm\"><input onClick=\"toggle_itm('chkEquipmentXIssued', $row, $roster_id)\" type=\"checkbox\" " . ($myrow['equipment_issued'] ? "checked" : "") . " id=\"chkEquipmentXIssued_$row\" name=\"chkEquipmentXIssued_$row_str\" value=\"1\" /></span>";
        $equipment_issued .= "<input type=\"hidden\" id=\"hiddenEquipmentXIssued_$row\" name=\"hiddenEquipmentXIssued_$row_str\" value=\"" . ($myrow['equipment_issued'] ? "yes" : "no") . "\" />";
        $radio_returned = "<span class=\"sel_itm\"><input onClick=\"toggle_itm('chkRadioXReturned', $row, $roster_id)\" type=\"checkbox\" " . ($myrow['radio_returned'] ? "checked" : "") . " id=\"chkRadioXReturned_$row\" name=\"chkRadioReturned_$row_str\" value=\"1\" /></span>";
        $radio_returned .= "<input type=\"hidden\" id=\"hiddenRadioXReturned_$row\" name=\"hiddenRadioXReturned_$row_str\" value=\"" . ($myrow['radio_returned'] ? "yes" : "no") . "\" />";
        $equipment_returned = "<span class=\"sel_itm\"><input onClick=\"toggle_itm('chkEquipmentXReturned', $row, $roster_id)\" type=\"checkbox\" " . ($myrow['equipment_returned'] ? "checked" : "") . " id=\"chkEquipmentXReturned_$row\" name=\"chkEquipmentXReturned_$row_str\" value=\"1\" /></span>";
        $equipment_returned .= "<input type=\"hidden\" id=\"hiddenEquipmentXReturned_$row\" name=\"hiddenEquipmentXReturned_$row_str\" value=\"" . ($myrow['equipment_returned'] ? "yes" : "no") . "\" />";
        $str .= $itm->hdn("hdn$row", "", "", "", "", "");
        if($old_zone != $zone) $str .= "<tr><th colspan=\"2\"><b>$zone</b></th><th style=\"text-align: center !important;\" colspan=\"2\">Issued</th><th style=\"text-align: center !important;\" colspan=\"2\">Returned</th><th style=\"text-align: right !important;\"><input title=\"Save Equipment Issue\" onClick=\"save_list()\" type=\"button\" value=\"Save\"></th></tr>$header_row";
        $str .= "<tr><td style=\"color: red !important; font-weight: bold;\">".(!(($row+1)%10) ? '<a name="'.($row+1).'"></a>' : "")."$position</td><td>$staff_name</td><td>$equipment_issued</td><td>$radio_number</td><td>$equipment_returned</td><td>$radio_returned</td><td>$comment</td></tr>";
        $old_zone = $zone;
      }
      $str .= '</table><a name="bottom"></a>';
      $template_sql = "select id, item_name from lookup_fields where lookup_id in (select id from lookups where item_name = 'nrl_templates') order by sort_order, item_name";
      $template_sel = $itm->sel("selTemplate", "", "", "", $this->dbi, $template_sql, "");
      if($msg) $str .= $this->message($msg, 2000);
    } else if($adjust_positions) {
      $sql = "select nrl_zone_positions.id from nrl_zone_positions
        left join nrl_zones on nrl_zones.id = nrl_zone_positions.zone_id
        order by nrl_zones.sort_order, nrl_zone_positions.sort_order";
      $result = $this->dbi->query($sql);
      while($myrow = $result->fetch_assoc()) {
        $c++;
        $d += 10;
        $id = $myrow['id'];
        $sql = "update nrl_zone_positions set position = $c, sort_order = $d where id = $id";
        $this->dbi->query($sql);
      }
      $str .= "Done...";
    }
    return $str;
  }
}
    
?>