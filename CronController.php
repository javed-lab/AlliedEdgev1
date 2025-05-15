<?php

class CronController extends Controller {

    protected $f3;
    var $img_folder;

    function __construct($f3) {
        date_default_timezone_set('Australia/Sydney');
        $this->db_init();
        $this->f3 = $f3;
    }

    function notActiveUserDeactivate() {
        $updateStatus = $this->notActiveUserList(14);
        if ($updateStatus == 1) {
            echo "Completed";
            die;
        }
    }

    function notActiveUserArchive() {
        $updateStatus = $this->notActiveArchiveUserList(180);
        if ($updateStatus == 1) {
            echo "Completed";
            die;
        }
    }

    function reminderSignOnRoasterToUser() {
        $this->reminderRosterAfterSignOnMinuteToUser(5);
    }     

    function reminderSignOnRoaster() {
        $this->reminderRosterSignOnMinute(30);
        $this->reminderRosterSignOnMinute(20);
        $this->reminderRosterSignOnMinute(10);
        //$this->reminderRosterSignOnPerDate();
        //$this->reminderRosterSignInHour();
    }

    function reminderAfterSignOnTimeRoaster() {
        $this->reminderRosterAfterSignOnMinute(15);
        //$this->reminderRosterSignOnMinute(10);
        //$this->reminderRosterSignOnMinute(5);
        //$this->reminderRosterSignOnPerDate();
        //$this->reminderRosterSignInHour();
    }

    function sendOccuranceLogNotification() {
        $days = -1;
        $getSites = $this->getSiteReceivedOccuranceLog($days);
        $date_by = date("Y-m-d", strtotime("$days days"));
        //prd($getSites);
        echo "1";
        foreach ($getSites as $sites) {
            $siteName = $sites['site_name'];
            $managerEmail1 = $sites['site_contact_email1'];
            $managerEmail2 = $sites['site_contact_email2'];
            $managerEmail3 = $sites['site_contact_email3'];

            $email_send = array($managerEmail1, $managerEmail2, $managerEmail3);
            //  print_r($sites);
            //  print_r($email_send);

            $uniqueEmailSend = array_unique(array_values(array_filter($email_send)));

            $siteId = $sites['site_id'];
            //$managerEmail1 = "mahavir.jain@dotsquares.com";

            if(trim($managerEmail1) || trim($managerEmail2) || trim($managerEmail2)) {
                $occurancePdf = $this->createSiteOccurancePdf($siteId, $days);
            }
            // die("pdf created");

            echo "2";
            if (!empty($uniqueEmailSend)) {
                foreach ($uniqueEmailSend as $toEmail) {
                    //echo "mm".$rosterTimeStaffId;
                    echo "3";
                    if ($mail) {
                        $mail->clear_all();
                    }
                    $mail = new email_q($this->dbi);
                    //$mail->AddAddress("mahavir.jain@dotsquares.com");
                    $mail->AddAddress($toEmail);
                    $mail->Subject = ' Occurane Log (' . $date_by . ') of site ' . $siteName;

                    $mail->Body = "Dear,<br><br> Please find attached the <b>Occurrence Log</b> Report for the past 24 hours at <b> " . $siteName . " </b> <br><br> This report contains detailed information regarding incidents or events that have occurred during this period and was posted by our team.
<br> Additionally, we would like to remind you that the Occurrence Log is readily accessible on the Dashboard, including the Whiteboard feature. Please make sure to review if needed. 
<br><br> If you have any questions or need further assistance regarding the Occurrence Log or any related matter, please contact us directly.
<br><br> Thank you for your attention.<br><br>

                        Thank you <br>
                        Allied EDGE ';";

                    $file_path_name = $this->f3->get('base_folder') . '/edge/downloads/occurance_log/pdf_files/occurancelog_' . $date_by . "_" . $siteId . '.pdf';

                    $mail->AddAttachment($file_path_name);
                    if ($mail->send()) {
                        echo "Mail Send Done";
                    }
                }
            }
        }
    }

    function trainingReminder() {

        $notiTrainingUsers = $this->notiTrainingUser();

        foreach ($notiTrainingUsers as $key => $myrow) {

            //$rosterList[] = $myrow;
            // $rosterStartTime = $myrow['roster_start'];
            //$rosterLocation = $myrow['roster_location'];
            $userName = $myrow['name'];
            $userEmail = $myrow['email'];

            //$uniqueEmailSend = array_unique(array_values(array_filter($userEmail)));
//pr($userEmail);
            // $startTimeRo = $minute;

            /* email send */
            $email_send = array($userEmail);

            $email_msg = 'Dear ' . $userName . '<br><br> We are pleased to inform you that you have been assigned some trainings. <br><br>
                    This training is designed to help you develop new skills and knowledge that will benefit you in your role.</br><br><br>
                    You can access the training by below link.</br><br><br>' . $this->f3->get('base_url') . '/AlliedMyTraining/Pending     </br><br><br>

We recommend that you complete the training as soon as possible to ensure you have enough time to review the materials and complete the assignments.</br><br><br>

If you have any questions or concerns about the training, please do not hesitate to contact us </br><br><br>

We wish you the best of luck in completing the training and look forward to your success.</br><br><br>

Thank you <br>
Allied EDGE ';

            if (!empty($userEmail)) {
                //pr($uniqueEmailSend);
                foreach ($email_send as $toEmail) {
                    //echo "mm".$rosterTimeStaffId;
                    // $toEmail = "mahavir.jain@dotsquares.com";
                    if ($mail) {
                        $mail->clear_all();
                    }
                    $mail = new email_q($this->dbi);
                    $mail->AddAddress($toEmail);
                    $mail->Subject = "Training Assignment By Allied Team ";

                    $mail->Body = $email_msg;
                    //$mail->queue_message();
                    //$mail->AddAttachment($file_path_name);

                    if ($toEmail) {
//                            if($mail->send()){
//                                echo "Mail Send Done";
//                                die;
//                             }
                        $mail->queue_message();
                        $this->notiTrainingUserSendStatus($myrow['user_id']);
                    }
                }
            }
        }
    }

    function toolboxReminder() {

        $notiTrainingUsers = $this->notiToolboxUser();
        //  prd($notiTrainingUsers);
        //prd($notiTrainingUsers);
        foreach ($notiTrainingUsers as $key => $myrow) {

            //$rosterList[] = $myrow;
            // $rosterStartTime = $myrow['roster_start'];
            //$rosterLocation = $myrow['roster_location'];
            $userName = $myrow['name'];
            $userEmail = $myrow['email'];
            $toolboxTitle = $myrow['title'];
            $toolboxDescription = $myrow['description'];

            //$uniqueEmailSend = array_unique(array_values(array_filter($userEmail)));
//pr($userEmail);
            // $startTimeRo = $minute;

            /* email send */
            $email_send = array($userEmail);

            $email_msg = 'Dear ' . $userName . '<br><br> We are pleased to inform you that you have been received toolbox.' . $toolboxTitle . ' <br><br>
                    Detail of Toolbox .</br><br><br>' . $toolboxDescription . '</br><br><br>
                    You can access the toolbox by below link.</br><br><br>' . $this->f3->get('base_url') . '/AlliedMyToolbox/index     </br><br><br>

We recomended that you complete the acknowledge as soon as possible to ensure you have enough time to review the materials and complete the acknowledge.</br><br><br>

If you have any questions or concerns about the toolbox, please do not hesitate to contact us </br><br><br>

Thank you <br>
Allied EDGE ';

            if (!empty($userEmail)) {
                //pr($uniqueEmailSend);
                foreach ($email_send as $toEmail) {
                    //echo "mm".$rosterTimeStaffId;
                    // $toEmail = "mahavir.jain@dotsquares.com";
                    if ($mail) {
                        $mail->clear_all();
                    }
                    $mail = new email_q($this->dbi);
                    $toEmail = "mahavir.jain@dotsquares.com";
                    $mail->AddAddress($toEmail);
                    $mail->Subject = "Training Assignment By Allied Team ";

                    $mail->Body = $email_msg;
                    //$mail->queue_message();
                    //$mail->AddAttachment($file_path_name);

                    if ($toEmail) {
                        if ($mail->send()) {
                            echo "Mail Send Done";
                            die;
                        }
                        $mail->queue_message();
                        $this->notiToolboxUserSendStatus($myrow['user_id']);
                    }
                }
            }
        }
    }

    function testEmail() {

        $mail = new email_q($this->dbi);
        // $rosterStaffEmail = "mahavir.jain@dotsquares.com";
        // $mail->AddAddress("mahavir.jain@dotsquares.com");
        $mail->AddAddress("javed.a@alliedmanagement.com.au");
        $mail->Subject = 'testing sending email';
        $mail->Body = 'testing sending email';
        ;
        if ($mail->send()) {
            echo "Mail Send Done";
        }
    }

    function reminderReportScheduler() {


        $time_date = date('Y-m-d H:i:s');

        $sql = " select compliance_schedules.id as `compliance_schedules_id`,csc.id as compliance_schedules_cron_id,compliance.title as `report`,
            compliance_schedules.frequency_id as `frequency`, compliance_schedules.start_date, 
            compliance_schedules.send_time as `send_time`, CONCAT('', users.name, ' ', users.surname, '') as `staff_member`, 
            CONCAT(users2.name, ' ', users2.surname) as `location`,`subject`, if(compliance_schedules.is_email = '', 'No', 'Yes') as `email`, 
            if(compliance_schedules.is_sms = '', 'No', 'Yes') as `sms`, if(compliance_schedules.avoid_public_holidays = '', 'No', 'Yes') as `avoid_holidays`,
            users.email staff_member_email,users.phone phone,users.phone2 phone2
             from compliance_schedules 
             inner join compliance_schedules_cron csc on csc.com_sch_id = compliance_schedules.id and csc.status = 0 
             left join compliance on compliance.id = compliance_schedules.compliance_id left join users on users.id = compliance_schedules.staff_id 
             left join users2 on users2.id = compliance_schedules.site_id left join lookup_fields on lookup_fields.id = compliance_schedules.frequency_id              
      where '$time_date' >= csc.crondatetime                  
      group by csc.com_sch_id
      order by compliance.title";
        // prd($sql);
        if ($result = $this->dbi->query($sql)) {
            // prd($result);
            while ($myrow = $result->fetch_assoc()) {
                // pr($myrow);
                $rosterList[] = $myrow;
                $schedulerId = $myrow['compliance_schedules_id'];
                $schedulerCronId = $myrow['compliance_schedules_cron_id'];
                $report = $myrow['report'];
                $frequency = $myrow['frequency'];
                $rosterStartDate = $myrow['start_date'];
                $rosterSendTime = $myrow['send_time'];
                $rosterStaffMember = $myrow['staff_member'];
                $rosterSite = $myrow['location'];
                $rosterSubject = $myrow['subject'];
                $rosterSms = $myrow['sms'];
                $rosterEmail = $myrow['email'];
                $phone = $myrow['phone'];
                $phone2 = $myrow['phone2'];
                $rosterAvoidHoliday = $myrow['avoidholiday'];

                // $startTimeRo = $minute;
                if ($rosterSms) {
                    $sms = new sms($this->dbi);
                    if ($phone = $sms->process_phones($phone, $phone2)) {
                        //$phone = "919252034506";
                        $message = 'This is reminder. you have a task ' . $rosterSubject . "(" . $report . ")" . '  at ' . $rosterSite . ' on ' . $rosterStartDate . ' ' . $rosterSendTime;

                        //. $this->f3->get('full_url') . "MyPatrols";
                        $sms->send_message($phone, $message);
                        // $msg = "Notification Sent";
                    }
                }

                if ($rosterEmail) {
                    $email_msg = 'Hi ' . $rosterStaffMember . ',<br><br>This is a courtesy message to remind that you have task ' . $rosterSubject . " (" . $report . ")" . '  at ' . $rosterSite . ' on ' . $rosterStartDate . ' ' . $rosterSendTime . '.  
                        <br><br>If you are experiencing any difficulty, please contact your manager asap.<br><br>Thank you <br>
                        Allied EDGE ';
                    $rosterStaffEmail = $myrow['staff_member_email'];
                    if ($rosterStaffEmail) {
                        $mail = new email_q($this->dbi);
                        // $rosterStaffEmail = "mahavir.jain@dotsquares.com";
                        $mail->AddAddress($rosterStaffEmail);
                        $mail->Subject = 'You have task ' . $report . '  at ' . $rosterSite . ' on ' . $rosterStartDate . ' ' . $rosterSendTime;
                        $mail->Body = $email_msg;
                        //$mail->AddAttachment($file_path_name);
                        if ($rosterStaffEmail != "") {
                            if ($mail->send()) {
                                $mail->queue_message_sent();
                            }
                        }
                    }
                }

                if ($schedulerCronId) {

                    $updateQuery = "update compliance_schedules_cron set status = '1' where id = '" . $schedulerCronId . "'";

                    $result1 = $this->dbi->query($updateQuery);
                    // $deleteQ = "delete from associations where child_user_id = '" . $user_id . "'";
                    $newCronDateTime = $this->newSubscriptionDateTime($frequency, $rosterStartDate, $rosterSendTime);

                    if ($newCronDateTime) {
                        $newCronDateTime = date('Y-m-d H:i:s', strtotime($newCronDateTime . ' - 4 hours'));

                        if ($newCronDateTime) {
                            $insertQuery = "insert into compliance_schedules_cron (com_sch_id,crondatetime) values ('" . $schedulerId . "','" . $newCronDateTime . "')";
                            $result2 = $this->dbi->query($insertQuery);
                        }
                    }

//                        echo $insertQuery;
//                        die;
                }


                // $this->schedulerNewTaskUpdate();
            }
        }
    }
    
    /* 
     * unapprove /candel reminder
     */

    function reminderRosterNotConfirmedIn4Hour() {

        $time_date = date('Y-m-d H:i:s');
//         echo $time_date;
//         die;

        $startTime = 250;
        $endTime = 230;

        $sql = "select users2.name roster_location,users.name,users.phone,users.phone2,users.email,roster_times_staff.id, roster_times.start_time_date as `roster_start`, roster_times.finish_time_date as `roster_finish`, roster_times_staff.start_time_date, roster_times_staff.finish_time_date, roster_times_staff.staff_comment, users2.id as `site_id`, users2.name as `site`,
            users2.site_contact_email1,
                    users2.site_contact_email2,
                    users2.site_contact_email3,
                    users2.civil_manager_email,
                    users2.facilities_manager_email,
                    users2.pest_manager_email,
                    users2.security_manager_email,
                    users2.traffic_manager_email,
                    users2.civil_manager_email2,
                    users2.facilities_manager_email2,
                    users2.pest_manager_email2,
                    users2.security_manager_email2,
                    users2.traffic_manager_email2, 
                    users2.civil_manager_email3,
                    users2.facilities_manager_email3,
                    users2.pest_manager_email3,
                    users2.security_manager_email3,
                    users2.traffic_manager_email3,
                    users2.civil_manager_mobile,
                    users2.facilities_manager_mobile,
                    users2.pest_manager_mobile,
                    users2.security_manager_mobile,
                    users2.traffic_manager_mobile,
                    users2.civil_manager_mobile2,
                    users2.facilities_manager_mobile2,
                    users2.pest_manager_mobile2,
                    users2.security_manager_mobile2,
                    users2.traffic_manager_mobile2,                    
                    users2.civil_manager_mobile3,
                    users2.facilities_manager_mobile3,
                    users2.pest_manager_mobile3,
                    users2.security_manager_mobile3,
                    users2.traffic_manager_mobile3
                    
                  from roster_times_staff  
                  left join users on users.id = roster_times_staff.staff_id
                  left join roster_times on roster_times.id = roster_times_staff.roster_time_id
                  left join rosters on rosters.id = roster_times.roster_id
                  left join users as users2 on users2.id = rosters.site_id
                  where roster_times_staff.status in (1,3) and ('$time_date' > DATE_ADD(roster_times.start_time_date, INTERVAL - $startTime MINUTE)
                  and '$time_date' <= DATE_ADD(roster_times.start_time_date, INTERVAL - $endTime MINUTE)) "
                . "and roster_times_staff.start_time_date = '0000-00-00 00:00:00'"
                . " order by roster_times_staff.id ";
        
        //prd($sql);

        if ($result = $this->dbi->query($sql)) {
//prd($result);
            while ($myrow = $result->fetch_assoc()) {
                //  die("test");
                $rosterList[] = $myrow;
                $rosterTimeStaffId = $myrow['id'];
                $rosterStartTime = $myrow['roster_start'];
                $rosterLocation = $myrow['roster_location'];
                $rosterName = $myrow['name'];
                $rosterEmail = $myrow['email'];
                $startTimeRo = $minute;

                /* email send */
                $email_send = array(
                    $myrow['civil_manager_email'], $myrow['facilities_manager_email'], $myrow['pest_manager_email'], $myrow['security_manager_email'], $myrow['traffic_manager_email'],
                    $myrow['civil_manager_email2'], $myrow['facilities_manager_email2'], $myrow['pest_manager_email2'], $myrow['security_manager_email2'], $myrow['traffic_manager_email2'],
                    $myrow['civil_manager_email3'], $myrow['facilities_manager_email3'], $myrow['pest_manager_email3'], $myrow['security_manager_email3'], $myrow['traffic_manager_email3']);

                $uniqueEmailSend = array_unique(array_values(array_filter($email_send)));
                
                 $email_msg = 'Dear Manager<br><br>This is a reminder email that you have a shift unconfirmed/Cancelled for the following<br><br> '
                        . 'Site: ' . $rosterLocation . '<br>Name: ' . $rosterName . '<br>Start Time: ' . $rosterStartTime . '</br><br><br>
Thank you <br>
Allied EDGE ';
                
                  if (!empty($uniqueEmailSend)) {
                    foreach ($uniqueEmailSend as $toEmail) {
                        //echo "mm".$rosterTimeStaffId;

                        if ($mail) {
                            $mail->clear_all();
                        }
                        $mail = new email_q($this->dbi);
                        $mail->AddAddress($toEmail);
                        $mail->Subject = "This is reminder. User have a unApproved/Cancelled shift starting in 4 Hour from now";

                        $mail->Body = $email_msg;
                        //$mail->queue_message();
                        //$mail->AddAttachment($file_path_name);

                        if ($toEmail) {
//                            echo "mmm".$rosterTimeStaffId;
//                            die;
                            $detail = "Before 4 hour notification ";
                           $this->rosterLog(14, NULL, NULL, $rosterTimeStaffId, $detail);
                            // if ($mail->send()) {
                            $mail->queue_message();
                            //}
                        }
                    }
                }
                
               
                
//$uniqueEmailSend = array('mahavir.jain@dotsquares.com');
                //  prd($uniqueEmailSend);
                $mobile_msg = array(
                    $myrow['civil_manager_mobile'], $myrow['facilities_manager_mobile'], $myrow['pest_manager_mobile'], $myrow['security_manager_mobile'], $myrow['traffic_manager_mobile'],
                    $myrow['civil_manager_mobile2'], $myrow['facilities_manager_mobile2'], $myrow['pest_manager_mobile2'], $myrow['security_manager_mobile2'], $myrow['traffic_manager_mobile2'],
                    $myrow['civil_manager_mobile3'], $myrow['facilities_manager_mobile3'], $myrow['pest_manager_mobile3'], $myrow['security_manager_mobile3'], $myrow['traffic_manager_mobile3']);

                $uniqueMsgSend = array_unique(array_values(array_filter($mobile_msg)));

                
                $mobile_msg = "Dear user, please be advised you have a shift uncovered or cancelled by an employee that requires covering asap before 4 hours";

              

                if (!empty($uniqueMsgSend)) {
                    foreach ($uniqueMsgSend as $toPhone) {
                        $sms = new sms($this->dbi);
                        if($phone = $sms->process_phones($toPhone, $toPhone)) {
                            //. $this->f3->get('full_url') . "MyPatrols";
                            $sms->send_message($phone, $mobile_msg);
                            // $msg = "Notification Sent";
                        }
                    }
                }


//                $phone = $myrow['phone'];
//                $phone2 = $myrow['phone2'];
//                $sms = new sms($this->dbi);
//                if ($phone = $sms->process_phones($phone, $phone2)) {                 
//                    $message = "This is reminder. you have a shift starting in 90 minutes from now ";
//                    //. $this->f3->get('full_url') . "MyPatrols";
//                    $sms->send_message($phone, $message);
//                   // $msg = "Notification Sent";
//                } 
//                
//                $mail = new email_q($this->dbi);
//                $mail->AddAddress($rosterEmail);
//                $mail->Subject = "This is reminder. you have a shift starting in 90 minutes from now";
//                $mail->Body = $email_msg;
//                //$mail->queue_message();
//                //$mail->AddAttachment($file_path_name);
//                if ($myrow['email']) {
//                   // if ($mail->send()) {
//                        $mail->queue_message();
//                   // }
//                }
            }
        }
    }
    
    /*
     * 90 minute reminder
     */

    function reminderRosterSignInHour() {


        $time_date = date('Y-m-d H:i:s');
//         echo $time_date;
//         die;


        $startTime = 100;
        $endTime = 80;

        $sql = "select users2.name roster_location,users.name,users.phone,users.phone2,users.email,roster_times_staff.id, roster_times.start_time_date as `roster_start`, roster_times.finish_time_date as `roster_finish`, roster_times_staff.start_time_date, roster_times_staff.finish_time_date, roster_times_staff.staff_comment, users2.id as `site_id`, users2.name as `site`

                  from roster_times_staff
                  left join users on users.id = roster_times_staff.staff_id
                  left join roster_times on roster_times.id = roster_times_staff.roster_time_id
                  left join rosters on rosters.id = roster_times.roster_id
                  left join users2 on users2.id = rosters.site_id
                  where roster_times_staff.status = 2 and ('$time_date' > DATE_ADD(roster_times.start_time_date, INTERVAL - $startTime MINUTE)
                  and '$time_date' <= DATE_ADD(roster_times.start_time_date, INTERVAL - $endTime MINUTE)) "
                . "and roster_times_staff.start_time_date = '0000-00-00 00:00:00'"
                . " order by roster_times_staff.id ";

//         echo $sql;
//         die;

        if ($result = $this->dbi->query($sql)) {

            while ($myrow = $result->fetch_assoc()) {

                //  die("test");
                $rosterList[] = $myrow;
                $rosterTimeStaffId = $myrow['id'];
                $rosterStartTime = $myrow['roster_start'];
                $rosterLocation = $myrow['roster_location'];
                $rosterName = $myrow['name'];
                $rosterEmail = $myrow['email'];
                $startTimeRo = $minute;

//                $email_msg = 'Hi ' . $rosterName . ',<br> <br>This is a courtesy message to remind that you have a shift starting in 90 mins from now.<br>'
//                        . 'If you are unable to attend to the shift or believe you will be late, kindly contact your Site Supervisor.<br><br>
//Alternatively you can always contact your rostering coordinator.<br><br>
//Thank you <br>
//Allied EDGE ';
                $email_msg = 'Hi ' . $rosterName . ',<br> <br>Reminder: Your shift starts in 90 mins. If unable to attend or running late, please contact Supervisor/Manager/National Operations Centre.<br><br>
Thank you <br>
Allied EDGE ';

                $phone = $myrow['phone'];
                $phone2 = $myrow['phone2'];

                $sms = new sms($this->dbi);
                if ($phone = $sms->process_phones($phone, $phone2)) {
                    $message = "'Hi ' . $rosterName . ',<br>Reminder: Your shift starts in 90 mins. If unable to attend or running late, please contact Supervisor/Manager/National Operations Centre";
                    //. $this->f3->get('full_url') . "MyPatrols";
                    $sms->send_message($phone, $message);
                    // $msg = "Notification Sent";
                }





                $mail = new email_q($this->dbi);
                $mail->AddAddress($rosterEmail);
                $mail->Subject = "Reminder: Your shift starts in 90 mins. If unable to attend or running late, please contact Supervisor/Manager/National Operations Centre";
                $mail->Body = $email_msg;
                //$mail->queue_message();
                //$mail->AddAttachment($file_path_name);
//                   echo $myrow['email'];
//                    die;
//                $myrow['email'] = "mahavir.jain@dotsquares";
                if ($myrow['email']) {
                    $detail = "90 Minute notification reminder";
                    $this->rosterLog(18, NULL, NULL, $rosterTimeStaffId, $detail);
                    if ($mail->send()) {
                        $mail->queue_message_sent();
                    }
                }
            }
        }
    }
    
    /*
     * 10,20,30 minute signon reminder
     */
    
    

    function reminderRosterSignOnMinute($minute) {

        $singOnLink = " Please sign on by this link " . $this->f3->get('full_url') . "Clock2";

        $time_date = date('Y-m-d H:i:s');
//         echo $time_date;
//         die;


        $startTime = $minute + 5;
        $endTime = $minute - 4;

        $sql = "select users2.name roster_location,users.name,users.phone,users.phone2,users.email,roster_times_staff.id, roster_times.start_time_date as `roster_start`, roster_times.finish_time_date as `roster_finish`, roster_times_staff.start_time_date, roster_times_staff.finish_time_date, roster_times_staff.staff_comment, users2.id as `site_id`, users2.name as `site`
                  from roster_times_staff
                  left join users on users.id = roster_times_staff.staff_id
                  left join roster_times on roster_times.id = roster_times_staff.roster_time_id
                  left join rosters on rosters.id = roster_times.roster_id
                  left join users2 on users2.id = rosters.site_id
                  where roster_times_staff.status = 2 and ('$time_date' > DATE_ADD(roster_times.start_time_date, INTERVAL - $startTime MINUTE)
                  and '$time_date' <= DATE_ADD(roster_times.start_time_date, INTERVAL - $endTime MINUTE)) "
                . "and roster_times_staff.start_time_date = '0000-00-00 00:00:00'"
                . " order by roster_times_staff.id ";

//        echo $sql;
//        die;

        if ($result = $this->dbi->query($sql)) {


//                      while($myrow = $result->fetch_assoc()) {
//                       //  die("test");
//                        $rosterList[] = $myrow;                        
//                        //$rosterStartTime = $myrow['roster_start'];
//                       // $rosterLocation = $myrow['roster_location'];
//                        //$rosterName = $myrow['name'];
//                        $rosterEmail[0] = "mahavir.jain@dotsquares.com"; //$myrow['email'];
//                        
//                        
//                        
//                        
//                    }
//                    $rosterEmail[0] = "mahavir.jain@dotsquares.com";
//                    $rosterEmail[1] = "mahavirjainsbr@gmail.com";
//                    
//                        $startTime = $minute;
//                        $email_msg = 'Hello ,<br> You have roster in '.$startTime.' minute please signon <br><br>Thank You<br>';
//                        $mail = new email_q($this->dbi);
//                        $mail->AddAddress("mahavir.jain@dotsquares.com");
//                        $mail->addBCC($rosterEmail);
//                        $mail->Subject = "You have  roster in ".$startTime.' minute please signon';
//                        $mail->Body = $email_msg;
//                        //$mail->queue_message();
//                        //$mail->AddAttachment($file_path_name);
//                        $mail->send();
            // prd($result);

            while ($myrow = $result->fetch_assoc()) {
                //  die("test");
                $rosterList[] = $myrow;
                $rosterTimeStaffId = $myrow['id'];
                $rosterStartTime = $myrow['roster_start'];
                $rosterLocation = $myrow['roster_location'];
                $rosterName = $myrow['name'];
                $rosterEmail = $myrow['email'];
                $startTimeRo = $minute;

//                if ($startTimeRo == 30) {
//
////                    $email_msg = 'Hi ' . $rosterName . ',<br><br>This is a courtesy message to remind that you have ' . $startTimeRo . ' mins to sign on to your shift.
////If you are experiencing any difficulty, please contact your manager asap.<br><br>Thank you <br>
////                        Allied EDGE ';
//                } else if ($startTimeRo == 20 || $startTimeRo == 10) {
//
//
//                    $email_msg = 'Hi ' . $rosterName . ',<br><br Its seems like you haven’t signed on to the shift yet.<br>
//This is a courtesy message to remind that you have ' . $startTimeRo . ' mins to sign on to your shift.<br> If you are experiencing any difficulty, please contact your manager asap.
//<br><br>Thank you <br>
//                        Allied EDGE ';
//                }
                
               $messageReminder = "Reminder: ' . $startTimeRo . ' mins left to sign on for your shift. If experiencing difficulties, contact your manager ASAP. Sign on using this link '. $this->f3->get('full_url') . 'Clock2";
                
               $email_msg = 'Hi ' . $rosterName . ','.$messageReminder.'<br><br> Thank you <br> Allied EDGE ';

                $phone = $myrow['phone'];
                $phone2 = $myrow['phone2'];

                $sms = new sms($this->dbi);

                if ($phone = $sms->process_phones($phone, $phone2)) {
                    $message = 'Hi ' . $rosterName . ','.$messageReminder;
                    //. $this->f3->get('full_url') . "MyPatrols";
                    $sms->send_message($phone, $message);
                    // $msg = "Notification Sent";
                }




                if ($email_msg) {
                    $mail = new email_q($this->dbi);
                    $mail->AddAddress($rosterEmail);
                    $mail->Subject = "You have  roster in " . $startTimeRo . ' minute please signon';
                    $mail->Body = $email_msg;
                    //$mail->AddAttachment($file_path_name);

                    if ($myrow['email']) {
                        if ($startTimeRo == 30) {
                            $detail = " 30 minute notification";
                            $this->rosterLog(15, NULL, NULL, $rosterTimeStaffId, $detail);
                        } elseif ($startTimeRo == 20) {
                            $detail = " 20 minute notification";
                            $this->rosterLog(16, NULL, NULL, $rosterTimeStaffId, $detail);
                        } else if ($startTimeRo == 10) {
                            $detail = " 10 minute notification";
                            $this->rosterLog(17, NULL, NULL, $rosterTimeStaffId, $detail);
                        }
                        if ($mail->send()) {
                            $mail->queue_message_sent();
                        }
                    }
                }
            }
        }
    }
    
    
    /*
     * after 5 minute send to notification to User
     */
    
    function reminderRosterAfterSignOnMinuteToUser($minute) {

        $singOnLink = " Please sign on by this link " . $this->f3->get('full_url') . "Clock2";

        $time_date = date('Y-m-d H:i:s');
//         echo $time_date;
//         die;


        $startTime = $minute + 2;
        $endTime = $minute - 2;

        $sql = "select users2.name roster_location,users.name,users.phone,users.phone2,users.email,roster_times_staff.id, roster_times.start_time_date as `roster_start`, roster_times.finish_time_date as `roster_finish`, roster_times_staff.start_time_date, roster_times_staff.finish_time_date, roster_times_staff.staff_comment, users2.id as `site_id`, users2.name as `site`
                  from roster_times_staff
                  left join users on users.id = roster_times_staff.staff_id
                  left join roster_times on roster_times.id = roster_times_staff.roster_time_id
                  left join rosters on rosters.id = roster_times.roster_id
                  left join users2 on users2.id = rosters.site_id
                  where roster_times_staff.status = 2 and ('$time_date' > DATE_ADD(roster_times.start_time_date, INTERVAL + $startTime MINUTE)
                  and '$time_date' <= DATE_ADD(roster_times.start_time_date, INTERVAL + $endTime MINUTE)) "
                . "and roster_times_staff.start_time_date = '0000-00-00 00:00:00'"
                . " order by roster_times_staff.id ";

//        echo $sql;
//        die;

        if ($result = $this->dbi->query($sql)) {


//                      while($myrow = $result->fetch_assoc()) {
//                       //  die("test");
//                        $rosterList[] = $myrow;                        
//                        //$rosterStartTime = $myrow['roster_start'];
//                       // $rosterLocation = $myrow['roster_location'];
//                        //$rosterName = $myrow['name'];
//                        $rosterEmail[0] = "mahavir.jain@dotsquares.com"; //$myrow['email'];
//                        
//                        
//                        
//                        
//                    }
//                    $rosterEmail[0] = "mahavir.jain@dotsquares.com";
//                    $rosterEmail[1] = "mahavirjainsbr@gmail.com";
//                    
//                        $startTime = $minute;
//                        $email_msg = 'Hello ,<br> You have roster in '.$startTime.' minute please signon <br><br>Thank You<br>';
//                        $mail = new email_q($this->dbi);
//                        $mail->AddAddress("mahavir.jain@dotsquares.com");
//                        $mail->addBCC($rosterEmail);
//                        $mail->Subject = "You have  roster in ".$startTime.' minute please signon';
//                        $mail->Body = $email_msg;
//                        //$mail->queue_message();
//                        //$mail->AddAttachment($file_path_name);
//                        $mail->send();
            // prd($result);

            while ($myrow = $result->fetch_assoc()) {
                //  die("test");
                $rosterList[] = $myrow;
                $rosterTimeStaffId = $myrow['id'];
                $rosterStartTime = $myrow['roster_start'];
                $rosterLocation = $myrow['roster_location'];
                $rosterName = $myrow['name'];
                $rosterEmail = $myrow['email'];
                $startTimeRo = $minute;

                //if ($startTimeRo == 5) {

                    $email_msg = 'Hi ' . $rosterName . ',<br><br>This is a courtesy message to remind that you have a shift starting ' . $startTimeRo .' minutes ago from now.
If you are experiencing any difficulty, please contact your manager asap.<br><br>Thank you <br>
                        Allied EDGE ';
               // }

                $phone = $myrow['phone'];
                $phone2 = $myrow['phone2'];

                $sms = new sms($this->dbi);

                if ($phone = $sms->process_phones($phone, $phone2)) {
                    $message = "This is reminder. you have a shift starting " . $startTimeRo . " minutes ago from now. " . $singOnLink;
                    //. $this->f3->get('full_url') . "MyPatrols";
                    $sms->send_message($phone, $message);
                    // $msg = "Notification Sent";
                }




                if ($email_msg) {
                    $mail = new email_q($this->dbi);
                    $mail->AddAddress($rosterEmail);
                    $mail->Subject = "You have roster " . $startTimeRo . ' minute ago please signon';
                    $mail->Body = $email_msg;
                    //$mail->AddAttachment($file_path_name);

                    if ($myrow['email']) {
                        if ($startTimeRo == 5) {
                            $detail = " 5 minute After Roster notification";
                            $this->rosterLog(20, NULL, NULL, $rosterTimeStaffId, $detail);
                        } 
//                        elseif ($startTimeRo == 20) {
//                            $detail = " 10 minute notification";
//                            $this->rosterLog(16, NULL, NULL, $rosterTimeStaffId, $detail);
//                        } else if ($startTimeRo == 10) {
//                            $detail = " 5 minute notification";
//                            $this->rosterLog(17, NULL, NULL, $rosterTimeStaffId, $detail);
//                        }
                        if ($mail->send()) {
                            $mail->queue_message_sent();
                        }
                    }
                }
            }
        }
    }
    
    
     /*
     * after not Signoff 10 minute after send cron
     */    

   function reminderRosterAfterSignOffMinuteToUser() {
       //die("hello");
       $minute = 5;
        $singOnLink = " Please sign Off by this link " . $this->f3->get('full_url') . "Clock2";

        $time_date = date('Y-m-d H:i:s');
//         echo $time_date;
//         die;


        $startTime = $minute - 2;
        $endTime = $minute + 2;

        $sql = "select users2.name roster_location,users.name,users.phone,users.phone2,users.email,roster_times_staff.id, roster_times.start_time_date as `roster_start`, roster_times.finish_time_date as `roster_finish`, roster_times_staff.start_time_date, roster_times_staff.finish_time_date, roster_times_staff.staff_comment, users2.id as `site_id`, users2.name as `site`
                  from roster_times_staff
                  left join users on users.id = roster_times_staff.staff_id
                  left join roster_times on roster_times.id = roster_times_staff.roster_time_id
                  left join rosters on rosters.id = roster_times.roster_id
                  left join users2 on users2.id = rosters.site_id
                  where roster_times_staff.status = 4 and ('$time_date' > DATE_ADD(roster_times.finish_time_date, INTERVAL + $startTime MINUTE)
                  and '$time_date' <= DATE_ADD(roster_times.finish_time_date, INTERVAL + $endTime MINUTE)) "
                . "and roster_times_staff.start_time_date != '0000-00-00 00:00:00' and roster_times_staff.finish_time_date = '0000-00-00 00:00:00'"
                . " order by roster_times_staff.id ";

        //echo $sql;
        

        if ($result = $this->dbi->query($sql)) {


          while ($myrow = $result->fetch_assoc()) {
                //  die("test");
                $rosterList[] = $myrow;
                $rosterTimeStaffId = $myrow['id'];
                $rosterStartTime = $myrow['roster_start'];
                $rosterFinishTime = $myrow['roster_finish'];
                $rosterLocation = $myrow['roster_location'];
                $rosterName = $myrow['name'];
                $rosterEmail = $myrow['email'];
                $startTimeRo = $minute;

                //if ($startTimeRo == 5) {

                    $email_msg = 'Hi ' . $rosterName . ',<br><br>This is a courtesy message to remind that you have a shift Off ' . $startTimeRo .' minutes ago from now.
If you are experiencing any difficulty, please contact your manager asap.<br><br>Thank you <br>
                        Allied EDGE ';
               // }

                $phone = $myrow['phone'];
                $phone2 = $myrow['phone2'];

                $sms = new sms($this->dbi);

                if ($phone = $sms->process_phones($phone, $phone2)) {
                    $message = "This is reminder. you have a shift Off " . $startTimeRo . " minutes ago from now. " . $singOnLink;
                    //. $this->f3->get('full_url') . "MyPatrols";
                    $sms->send_message($phone, $message);
                    // $msg = "Notification Sent";
                }




                if ($email_msg) {
                    $mail = new email_q($this->dbi);
                    $mail->AddAddress($rosterEmail);
                    $mail->Subject = "You have roster sign off " . $startTimeRo . ' minute ago please sign Off';
                    $mail->Body = "mahavir.jain@dotsquares.com"; //$email_msg;
                    //$mail->AddAttachment($file_path_name);

                    if($myrow['email']) {
                        //die("hello");
                        //if ($startTimeRo == 5) {
                            $detail = $startTimeRo." minute ago Roster SignOff time notification";
                            $this->rosterLog(20, NULL, NULL, $rosterTimeStaffId, $detail);
                        //}
                        if ($mail->send()) {
                            $mail->queue_message_sent();
                        }
                    }
                }
            }
        }
    }
    
    
    /*
     * after 15 minute  send cron
     */    

    function reminderRosterAfterSignOnMinute($minute) {

        $singOnLink = " Please sign on by this link " . $this->f3->get('full_url') . "Clock2";

        $time_date = date('Y-m-d H:i:s');
//         echo $time_date;
//         die;


        $startTime = $minute + 7;
        $endTime = $minute - 7;

        $sql = "select 
                    users2.site_contact_email1,
                    users2.site_contact_email2,
                    users2.site_contact_email3,
                    users2.civil_manager_email,
                    users2.facilities_manager_email,
                    users2.pest_manager_email,
                    users2.security_manager_email,
                    users2.traffic_manager_email,
                    users2.civil_manager_email2,
                    users2.facilities_manager_email2,
                    users2.pest_manager_email2,
                    users2.security_manager_email2,
                    users2.traffic_manager_email2,
                    users2.civil_manager_email3,
                    users2.facilities_manager_email3,
                    users2.pest_manager_email3,
                    users2.security_manager_email3,
                    users2.traffic_manager_email3,
                    
                    users2.civil_manager_mobile,
                    users2.facilities_manager_mobile,
                    users2.pest_manager_mobile,
                    users2.security_manager_mobile,
                    users2.traffic_manager_mobile,
                    users2.civil_manager_mobile2,
                    users2.facilities_manager_mobile2,
                    users2.pest_manager_mobile2,
                    users2.security_manager_mobile2,
                    users2.traffic_manager_mobile2,                    
                    users2.civil_manager_mobile3,
                    users2.facilities_manager_mobile3,
                    users2.pest_manager_mobile3,
                    users2.security_manager_mobile3,
                    users2.traffic_manager_mobile3,
                    roster_times_staff.status rosterStatus,
                    users2.name roster_location,users.name,users.phone,users.phone2,users.email,roster_times_staff.id, roster_times.start_time_date as `roster_start`, roster_times.finish_time_date as `roster_finish`, roster_times_staff.start_time_date, roster_times_staff.finish_time_date, roster_times_staff.staff_comment, users2.id as `site_id`, users2.name as `site`
                  from roster_times_staff
                  left join users on users.id = roster_times_staff.staff_id
                  left join roster_times on roster_times.id = roster_times_staff.roster_time_id
                  left join rosters on rosters.id = roster_times.roster_id
                  left join users2 on users2.id = rosters.site_id
                  where roster_times_staff.status in (1,2) and ('$time_date' > DATE_ADD(roster_times.start_time_date, INTERVAL + $startTime MINUTE)
                  and '$time_date' <= DATE_ADD(roster_times.start_time_date, INTERVAL + $endTime MINUTE)) "
                . "and roster_times_staff.start_time_date = '0000-00-00 00:00:00'"
                . " order by roster_times_staff.id ";

//        echo $sql;
//        die;

        if ($result = $this->dbi->query($sql)) {
            /* email send */
            $email_send = array(
                $myrow['civil_manager_email'], $myrow['facilities_manager_email'], $myrow['pest_manager_email'], $myrow['security_manager_email'], $myrow['traffic_manager_email'],
                $myrow['civil_manager_email2'], $myrow['facilities_manager_email2'], $myrow['pest_manager_email2'], $myrow['security_manager_email2'], $myrow['traffic_manager_email2'],
                $myrow['civil_manager_email3'], $myrow['facilities_manager_email3'], $myrow['pest_manager_email3'], $myrow['security_manager_email3'], $myrow['traffic_manager_email3']);

            $uniqueEmailSend = array_unique(array_values(array_filter($email_send)));

            $msg_send = array(
                $myrow['civil_manager_mobile'], $myrow['facilities_manager_mobile'], $myrow['pest_manager_mobile'], $myrow['security_manager_mobile'], $myrow['traffic_manager_mobile'],
                $myrow['civil_manager_mobile2'], $myrow['facilities_manager_mobile2'], $myrow['pest_manager_mobile2'], $myrow['security_manager_mobile2'], $myrow['traffic_manager_mobile2'],
                $myrow['civil_manager_mobile3'], $myrow['facilities_manager_mobile3'], $myrow['pest_manager_mobile3'], $myrow['security_manager_mobile3'], $myrow['traffic_manager_mobile3']);

            $uniqueMsgSend = array_unique(array_values(array_filter($msg_send)));

            //$uniqueEmailSend = array('mahavir.jain@dotsquares.com');
            //  prd($uniqueEmailSend);
            if ($myrow['rosterStatus'] == 2) {
                $rosterShiftStatus = "Approved";
            } else {
                $rosterShiftStatus = "unApproved";
            }


            $email_msg = 'Dear Manager<br><br>This is a reminder email that you have a shift ' . $rosterShiftStatus . ' for the following<br><br> '
                    . 'Site: ' . $rosterLocation . '<br>Name: ' . $rosterName . '<br>Start Time: ' . $rosterStartTime . ' user not signed on after ' . $minute . ' Please taka action</br><br><br>
                Thank you <br>
                Allied EDGE ';

            $email_sub = "Edge Rreminder. User have a " . $rosterShiftStatus . " shift Start Time: ' . $rosterStartTime . ' That user not signed on from now";

            $mobile_msg = 'Edge Rreminder. You have a  shift of Site: ' . $rosterLocation . ' and user not signed on after ' . $minute . '<';

            if (!empty($uniqueEmailSend)) {
                foreach ($uniqueEmailSend as $toEmail) {
                    //echo "mm".$rosterTimeStaffId;
                    if ($mail) {
                        $mail->clear_all();
                    }
                    $mail = new email_q($this->dbi);
                    $mail->AddAddress($toEmail);
                    $mail->Subject = "Edge Rreminder. User have a " . $rosterShiftStatus . " shift Start Time: ' . $rosterStartTime . ' That user not signed on from now";

                    $mail->Body = $email_msg;
                    //$mail->queue_message();
                    //$mail->AddAttachment($file_path_name);

                    if ($toEmail) {
//                            echo "mmm".$rosterTimeStaffId;
//                            die;
                        $detail = "User not signon after 15 minute Notificaton";
                        $this->rosterLog(14, NULL, NULL, $rosterTimeStaffId, $detail);
                        // if ($mail->send()) {
                        $mail->queue_message();
                        //}
                    }
                }
            }

            if (!empty($uniqueMsgSend)) {
                foreach ($uniqueMsgSend as $toPhone) {
                    $sms = new sms($this->dbi);
                    if ($phone = $sms->process_phones($toPhone, $toPhone)) {
                        //. $this->f3->get('full_url') . "MyPatrols";
                        $sms->send_message($toPhone, $mobile_msg);
                        // $msg = "Notification Sent";
                    }
                }
            }
        }
        die("done");
    }

    function reminderRosterSignOnPerDate() {

        $singOnLink = " Please sign on by this link " . $this->f3->get('full_url') . "Clock2";

        $time_date = date('Y-m-d H:i:s');
        $todaydate = date('Y-m-d');

        $sqlProvider = "select provider.id,provider.name,provider.phone,provider.phone2,provider.email
                  from roster_times_staff
                  left join users on users.id = roster_times_staff.staff_id
                  inner join users provider on provider.id = users.provider_id
                  left join roster_times on roster_times.id = roster_times_staff.roster_time_id
                  left join rosters on rosters.id = roster_times.roster_id
                  left join users2 on users2.id = rosters.site_id
                  where users.working_for_provider = 'Y' and users.provider_id > 0 and roster_times_staff.status = 2  and ('$todaydate' = DATE(roster_times.start_time_date)) "
                . " and ('$time_date' < roster_times.start_time_date) "
                . " and roster_times_staff.start_time_date = '0000-00-00 00:00:00'"
                . " group by users.provider_id ";

//        echo $sqlProvider;
//        die;

        if ($result = $this->dbi->query($sqlProvider)) {
            while ($myrow = $result->fetch_assoc()) {

                $providerId = $myrow['id'];
                $providerName = $myrow['name'];
                $providerPhone = $myrow['phone'];
                $providerPhone = $myrow['phone2'];
                $providerEmail = $myrow['email'];
                $sqlRoster = "select users2.name roster_location,users.name,users.phone,users.phone2,users.email,roster_times_staff.id, roster_times.start_time_date as `roster_start`, roster_times.finish_time_date as `roster_finish`, roster_times_staff.start_time_date, roster_times_staff.finish_time_date, roster_times_staff.staff_comment, users2.id as `site_id`, users2.name as `site`
                  from roster_times_staff
                  left join users on users.id = roster_times_staff.staff_id
                  inner join users provider on provider.id = users.provider_id
                  left join roster_times on roster_times.id = roster_times_staff.roster_time_id
                  left join rosters on rosters.id = roster_times.roster_id
                  left join users2 on users2.id = rosters.site_id
                  where users.working_for_provider = 'Y' and users.provider_id = '" . $providerId . "' and roster_times_staff.status = 2  and ('$todaydate' = DATE(roster_times.start_time_date)) "
                        . " and ('$time_date' < roster_times.start_time_date) "
                        . " and roster_times_staff.start_time_date = '0000-00-00 00:00:00'"
                        . " order by users.provider_id ";

                $mailContent = "Hi " . $providerName . "</br> you have some upcoming roster today</b>";
                if ($resultRoster = $this->dbi->query($sqlRoster)) {
                    while ($myRoster = $resultRoster->fetch_assoc()) {
                        $rosterStartTime = $myRoster['roster_start'];
                        $rosterEndTime = $myRoster['roster_finish'];
                        $rosterLocation = $myRoster['roster_location'];
                        $rosterUserName = $myRoster['name'];
                        $rosterUserEmail = $myrow['email'];
                        $rosterUserPhone = $myrow['phone'];
                        $rosterUserPhone2 = $myrow['phone2'];
                        $mailContent .= " <br><br><b>Location:</b> " . $rosterLocation . ", <b>Start Time</b> " . $rosterStartTime . ", <b>User </b>" . $rosterUserName . "(" . $rosterUserEmail . ")";
                    }

                    if ($mailContent && $providerEmail) {
                        $mail = new email_q($this->dbi);
                        $mail->AddAddress($providerEmail);
                        $mail->Subject = "you have some upcoming roster today " . $todaydate;
                        $mail->Body = $mailContent;
                        //$mail->AddAttachment($file_path_name);

                        if ($providerEmail) {
                            if ($mail->send()) {
                                $mail->queue_message_sent();
                            }
                        }
                    }
                }
            }
        }
    }

    function sendEmailQueue() {
        // die("test");
        $mail = new email_q($this->dbi);
        //$mail->AddAddress($_POST['txtEmail']);
        //$mail->AddAddress("mahavir.jain@dotsquares.com");
        $mailObjs = $mail->get_q_message();

        if ($mailObjs) {
            foreach ($mailObjs as $mailObj) {
                $mailNew = array();
                $mailNew = new email_q($this->dbi);
                $mailNew->clear_all();
                $mailNew->Subject = $mailObj['email_subject'];
                $mailNew->Body = $mailObj['email_body'];

                foreach ($mailObj['email_to'] as $emailto) {
                    if ($emailto[0] != "") {
                        $mailNew->AddAddress($emailto[0]);
                        //$mailNew->AddAddress("mahavir.jain@dotsquares.com");
                    }
                }

                foreach ($mailObj['email_reply_to'] as $emailToReply) {
                    if (isset($emailToReply[0]) && $emailToReply[0] != "") {
                        $mailNew->AddReplyTo($emailToReply[0]);
                    }
                }


                if ($mailNew->send()) {
                    $mailNew->update_q_message($mailObj['id']);
                }

//                if (!$mail->Send()) {
//                        $str .= "Mailer Error: " . $mail->ErrorInfo;
//                    } else {
//                        $str .= "<h3>Message Sent to: $name &lt;$email&gt;</h3>";
//                    }
//                    
            }
        }

//        $mail->Subject = $this->f3->get('company_name') . " Password' Reset";
//        $mail->Body = $email_msg;
//        $mail->AddAddress("mahavir.jain@dotsquares.com");
//                $mail->AddAddress("mahavir1.jain@dotsquares.com");
//                $mail->AddReplyTo("mahavir2.jain@dotsquares.com");
//                
//                $mail->queue_message();
        //$mailObj->send();
    }

}

?>
