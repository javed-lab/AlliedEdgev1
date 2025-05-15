<?php

class Controller {

    protected $f3;
    protected $db, $dbi;
    protected $notification;

    function beforeroute() {
        session_start();
    }

    function afterroute() {
        //echo Template::instance()->render('layout.htm');	
    }

    function divisionWithColor($divisionName) {

        return "<div class='" . $divisionName . "'><b>" . $divisionName . "</b></div>";
    }

    function rosterShiftStatus($status) {

        if ($status == 1) {
            return "<span style='color:orange'><b>Pending</b></span>";
            ;
        } else if ($status == 2 || $status == 4 || $status == 5) {
            return "<span style='color:green'><b>Confirm</b></span>";
        } else if ($status == 3) {
            return "<span style='color:red'><b>Cancelled</b></span>";
        } else if ($status == 4) {
            return "<span style='color:red'><b>Cancelled</b></span>";
        } else {
            return "<span><b>Tentative</b></span>";
        }
    }

    function rosStartingIn($minute) {
        if ($minute > 59) {
            $hour = ceil($minute / 60);
            $minute = $minute % 60;
            return $hour . " Hour " . $minute . " Minute";
        } else {
            return $minute . " Minute";
        }
    }
    

    function validate_email($email) {
//    $email2 = filter_var($email, FILTER_SANITIZE_EMAIL);
        //  return (filter_var($email2, FILTER_VALIDATE_EMAIL) === false || $email != $email2 ? 0 : 1);
        return (filter_var($email, FILTER_VALIDATE_EMAIL) === false ? 0 : 1);
    }

    function message($msg, $duration = 5000, $style = "") {
        $str = '
      <script type="text/javascript">
        customAlert("' . $msg . '", ' . $duration . ' ' . ($style ? ", \"$style\"" : "") . ')
      </script>
    ';
        return $str;
    }

    function userLastLogUpdate($userId) {

        $user = $this->getUserDetail($userId);
        // prd($user);
        if ($user) {
            $userName = $user['username'];
            $userId = $user['ID'];
            $curr_date_time = date('Y-m-d H:i:s');
            $sql = "insert into log_login (user_id, username, attempt_date_time, ip_address, is_successful,byscript) values ('{$userId}', '{$userName}', '$curr_date_time', '" . $_SERVER['REMOTE_ADDR'] . "', 1,1)";
            //echo $sql;
            // die;
            $this->dbi->query($sql);
            $_SESSION['user_last_log_id'] = $_SESSION['user_id'];
            $_SESSION['user_last_log_check_date'] = date('Y-m-d');
        }
    }
    // function userLastLoginUpdate($userId) {

    //     $user = $this->getUserDetail($userId);
    //     // prd($user);
    //     if ($user) {
    //         $userName = $user['username'];
    //         $userId = $user['ID'];
    //         $curr_date_time = date('Y-m-d H:i:s');
    //         $sql = "insert into last_logged_in (user_id, username, attempt_date_time, ip_address, is_successful) values ('{$userId}', '{$userName}', '$curr_date_time', '" . $_SERVER['REMOTE_ADDR'] . "', 1)";
    //         //echo $sql;
    //         // die;
    //         $this->dbi->query($sql);
    //         $_SESSION['user_last_log_id'] = $_SESSION['user_id'];
    //         $_SESSION['user_last_log_check_date'] = date('Y-m-d');
    //     }
    // }

    function userLastLoginUpdate($userId) {
        // Get user details
        $user = $this->getUserDetail($userId);

        if ($user) {
            $userName = $user['username'];
            $userId = $user['ID'];
            $curr_date_time = date('Y-m-d H:i:s');
            $ipAddress = $_SERVER['REMOTE_ADDR'];

            // Check if the user_id already exists in the last_logged_in table
            $checkQuery = "SELECT COUNT(*) AS count FROM last_logged_in WHERE user_id = '{$userId}'";
            $result = $this->dbi->query($checkQuery);
            $row = $result->fetch_assoc(); // Fetch the result

            // If user_id exists, update the record; otherwise, insert a new record
            if ($row['count'] > 0) {
                // Update existing record
                $updateSql = "UPDATE last_logged_in 
                            SET username = '{$userName}', 
                                attempt_date_time = '$curr_date_time', 
                                ip_address = '{$ipAddress}', 
                                is_successful = 1 
                            WHERE user_id = '{$userId}'";
                $this->dbi->query($updateSql);
            } else {
                // Insert new record
                $insertSql = "INSERT INTO last_logged_in (user_id, username, attempt_date_time, ip_address, is_successful) 
                            VALUES ('{$userId}', '{$userName}', '$curr_date_time', '{$ipAddress}', 1)";
                $this->dbi->query($insertSql);
            }
        }
    }


    /*
     * check user type
     * if user type site then create auto folder if not exist;
     */

    function createDefaultSiteFolder($userId) {

        $userType = $this->getUserType($userId);
        if ($userType == 384) {
            $folderPath = $this->f3->get('download_folder') . "user_files/" . $userId;
            $defaultFolder = [''];
            //'Edge Manual', 'Safety & WHS', 'Site SOP - Security', 'Site SOP - Facilities', 'Site SOP - Traffic', 'Site SOP - CIVIL', 'Site SOP – Pest', 'Other'
            foreach ($defaultFolder as $folderName) {
                $siteFolderPath = $folderPath . "/" . $folderName;
                if (!file_exists($siteFolderPath)) {
                    mkdir($siteFolderPath, 0755, true);
                    //chmod($folder, 0755);
                }
            }
        }

        return $userType;
    }

    function sendRosterAlterMessage($userId, $action, $roster_time_id) {
        //roster_times_staff.status = 2 and ('$time_date' > DATE_ADD(roster_times.start_time_date, INTERVAL - $startTime MINUTE)
        //        and '$time_date' <= DATE_ADD(roster_times.start_time_date, INTERVAL - $endTime MINUTE)) "
        //      . "and roster_times_staff.start_time_date = '0000-00-00 00:00:00'"        



        if ($action == "added" || $action == "deleted" || $action == "cancelled" || $action == "pending" || $action == "confirmed") {

            $sql = "select users2.name roster_location,users.name,users.phone,users.phone2,users.email,roster_times_staff.id, 
              roster_times.start_time_date as `roster_start`, roster_times.finish_time_date as `roster_finish`, 
              roster_times_staff.start_time_date, roster_times_staff.finish_time_date, roster_times_staff.staff_comment,
              users2.id as `site_id`, users2.name as `site`
                  from roster_times_staff
                  left join roster_times on roster_times.id = roster_times_staff.roster_time_id
                  left join users on users.id = roster_times_staff.staff_id              
                  left join rosters on rosters.id = roster_times.roster_id
                  left join users2 on users2.id = rosters.site_id
                  where rosters.is_published = 1 and roster_times_staff.id = $roster_time_id ";

            if ($action == "deleted") {
                $detail = "Staff removed from shift";
                $this->rosterLog(6, NULL, NULL, $roster_time_id, $detail);
            } else if ($action == "cancelled") {
                $detail = "Shift Canceled for Staff";
                $this->rosterLog(10, NULL, NULL, $roster_time_id, $detail);
            } else if ($action == "pending") {
                $detail = "Roster Shift set Pending for Staff";
                $this->rosterLog(9, NULL, NULL, $roster_time_id, $detail);
            } else if ($action == "confirmed") {
                $detail = "Roster Shift set Confirmed for Staff";
                $this->rosterLog(8, NULL, NULL, $roster_time_id, $detail);
            } else if ($action == "added") {
                $detail = "Staff added in Shift";
                $this->rosterLog(4, NULL, NULL, $roster_time_id, $detail);
            } else {
                
            }
        } else if ($action == "deletedAll" || $action == "changeTime") {
            $sql = "select users2.name roster_location,users.name,users.phone,users.phone2,users.email,roster_times_staff.id, 
              roster_times.start_time_date as `roster_start`, roster_times.finish_time_date as `roster_finish`, 
              roster_times_staff.start_time_date, roster_times_staff.finish_time_date, roster_times_staff.staff_comment,
              users2.id as `site_id`, users2.name as `site`
                  from roster_times_staff
                  left join roster_times on roster_times.id = roster_times_staff.roster_time_id
                  left join users on users.id = roster_times_staff.staff_id              
                  left join rosters on rosters.id = roster_times.roster_id
                  left join users2 on users2.id = rosters.site_id
                  where rosters.is_published = 1 and roster_times.id = $roster_time_id ";

            if ($action == "changeTime") {
                $detail = "Roster Shift time has been changed";
                $this->rosterLog(11, NULL, $roster_time_id, NULL, $detail);
            } else if ($action == "deletedAll") {
                $detail = "Roster Shift has been deleted";
                $this->rosterLog(5, NULL, $roster_time_id, NULL, $detail);
            }
        } else if ($action == 'publish' || $action == 'unpublish') {
            $sql = "select users2.name roster_location,users.name,users.phone,users.phone2,users.email,roster_times_staff.id, 
              roster_times.start_time_date as `roster_start`, roster_times.finish_time_date as `roster_finish`, 
              roster_times_staff.start_time_date, roster_times_staff.finish_time_date, roster_times_staff.staff_comment,
              users2.id as `site_id`, users2.name as `site`
                  from roster_times_staff
                  left join roster_times on roster_times.id = roster_times_staff.roster_time_id
                  left join users on users.id = roster_times_staff.staff_id              
                  left join rosters on rosters.id = roster_times.roster_id
                  left join users2 on users2.id = rosters.site_id
                  where rosters.id = $roster_time_id ";
            if ($action == "publish") {
                $detail = "Roster has been publish";
                $this->rosterLog(7, NULL, $roster_time_id, NULL, $detail);
            } else if ($action == "unpublish") {
                $detail = "Roster has been unpublish";
                $this->rosterLog(13, NULL, $roster_time_id, NULL, $detail);
            }
        } else {
            $sql = "select users2.name roster_location,users.name,users.phone,users.phone2,users.email,roster_times_staff.id, 
              roster_times.start_time_date as `roster_start`, roster_times.finish_time_date as `roster_finish`, 
              roster_times_staff.start_time_date, roster_times_staff.finish_time_date, roster_times_staff.staff_comment,
              users2.id as `site_id`, users2.name as `site`
                  from roster_times_staff
                  left join roster_times on roster_times.id = roster_times_staff.roster_time_id
                  left join users on users.id = roster_times_staff.staff_id              
                  left join rosters on rosters.id = roster_times.roster_id
                  left join users2 on users2.id = rosters.site_id
                  where rosters.is_published = 1 and users.id = $userId and roster_times.id = $roster_time_id ";
//            echo $sql;
//            die;
        }

        if ($result = $this->dbi->query($sql)) {
            while ($myrow = $result->fetch_assoc()) {

                if ($myrow['site_id'] == 16140 || $myrow['site_id'] == 16141) {
                    continue; // Skip the current iteration
                }

                $rosterList[] = $myrow;
                $rosterStartTime = $myrow['roster_start'];
                $rosterEndTime = $myrow['roster_finish'];
                $rosterLocation = $myrow['roster_location'];
                $rosterName = $myrow['name'];
                $rosterEmail = $myrow['email'];
                $startTimeRo = $minute;

//                if ($startTimeRo == 15) {
//
//                    $email_msg = 'Hi ' . $rosterName . ',<br><br>This is a courtesy message to remind that you have ' . $startTimeRo . ' mins to sign on to your shift.
//If you are experiencing any difficulty, please contact your manager asap.<br><br>Thank you <br>
//                        Allied EDGE ';
//                } else if ($startTimeRo == 10 || $startTimeRo == 5) {
//
//                    $email_msg = 'Hi ' . $rosterName . ',<br><br Its seems like you haven’t signed on to the shift yet.<br>
//This is a courtesy message to remind that you have ' . $startTimeRo . ' mins to sign on to your shift.<br> If you are experiencing any difficulty, please contact your manager asap.
//<br><br>Thank you <br>
//                        Allied EDGE ';
//                }

                $phone = $myrow['phone'];
                $phone2 = $myrow['phone2'];

                $sms = new sms($this->dbi);
                $message = "";
                //$phone = "919252034506";

                if ($phone = $sms->process_phones($phone, $phone2)) {
                    $message = "Hi $rosterName,";
                    if ($action == "added") {
                        //$message = "You has been added to roster for location " . $rosterLocation . "  Start time: " . $rosterStartTime . " End Time: " . $rosterEndTime;

                        $message .= " You have been allocated a shift at " . $rosterLocation;

                        //  echo $message;
                        //  die;
                    } elseif ($action == "publish") {
                        //$message = "Some changes in your roster for location " . $rosterLocation . "  Start time: " . $rosterStartTime . " End Time: " . $rosterEndTime;
                        $message .= " Your shift status has changed at " . $rosterLocation;
                    } elseif ($action == "unpublish") {
                        $message .= " Your shift status has changed at " . $rosterLocation;
                    } elseif ($action == "changeTime") {
                        $message .= " Your shift status has changed at " . $rosterLocation;
                    } elseif ($action == "deleted") {
                        //$message = "Your assigned roster has been deleted for  location " . $rosterLocation . "  Start time: " . $rosterStartTime . " End Time: " . $rosterEndTime;
                        $message .= " Your shift at " . $rosterLocation . " has been deleted ";
                    } elseif ($action == "cancelled") {
                        //$message = "Your assigned roster has been cancelled  for location " . $rosterLocation . "  Start time: " . $rosterStartTime . " End Time: " . $rosterEndTime;
                        $message .= " Your shift at " . $rosterLocation . " has been cancelled ";
                    } elseif ($action == "confirmed") {
                        //$message = "Your assigned roster  status has beend confirmed for location " . $rosterLocation . "  Start time: " . $rosterStartTime . " End Time: " . $rosterEndTime;
                        $message .= " Your shift at " . $rosterLocation . " is now confirmed ";
                    } elseif ($action == "pending") {
                        //$message = "Your assigned roster staus has been  pending  for location " . $rosterLocation . "  Start time: " . $rosterStartTime . " End Time: " . $rosterEndTime;
                        $message .= " Your shift at " . $rosterLocation . " is now pending ";
                    } elseif ($action == "deletedAll") {
                        //$message = "Your assigned roster slot has been deleted for location " . $rosterLocation . "  Start time: " . $rosterStartTime . " End Time: " . $rosterEndTime;
                        $message .= " Your shift at " . $rosterLocation . " has been deleted ";
                    } 
                    
                    $fullUrl = "https://alliededge.com.au/MyRoster";
                    $message .= "\nStart: " . $rosterStartTime . "\nEnd: " . $rosterEndTime . "\n" . $fullUrl;
                    
                    if ($message) {
                        // Assuming $sms object is properly instantiated
                        $sms->send_message($phone, $message);
                    }
                    
                    

                }
            }
        }
    }

    function rosterStaffList($roster_time_id) {

        $sql = "select roster_times_staff.id,CONCAT(users.name, ' ', users.surname) userfullname,rlt.leave_title "
                . " from roster_times_staff "
                . " LEFT JOIN  roster_leave_types rlt on rlt.id = roster_times_staff.leave_id"
                . " left join users  on users.id = roster_times_staff.staff_id"
                . " where roster_times_staff.roster_time_id = '" . $roster_time_id . "'";

//        echo $sql;
//        die;
        $rosterStaffList = array();
        if ($result = $this->dbi->query($sql)) {
            while ($myrow = $result->fetch_assoc()) {
                $rosterStaffList[] = $myrow;
            }
        }
        return $rosterStaffList;
    }

    //Escaping characters before database insertion.

    function __construct() {
        date_default_timezone_set('Australia/Sydney');
        $this->db_init();
        $this->curr_date_time = date('Y-m-d H:i:s');
//        echo $this->curr_date_time;
//        die;
    }

    function db_init() {
        $f3 = Base::instance();
        $this->f3 = $f3;
        $db = new DB\SQL(
                $f3->get('devdb'),
                $f3->get('devdbusername'),
                $f3->get('devdbpassword'),
                array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION)
        );
        $this->db = $db;
        if (null != $f3->get('devhost')) {
            $devHost = $f3->get('devhost');
        } else {
            $devHost = "localhost";
        }

        $this->dbi = new mysqli($devHost, $f3->get('devdbusername'), $f3->get('devdbpassword'), $f3->get('mysqlidb'));
    }

    function ed_crypt($str, $id) {
        $salt = '$1$' . md5("$id") . '$';
        $hash = crypt($str, $salt);
        for ($x = 0; $x < 50; $x++) {
            $salt_val = $id + $x;
            $salt = '$1$' . md5("$salt_val") . '$';
            $hash = crypt($hash, $salt);
        }
        $hash = substr($hash, 12);
        return $hash;
    }

    function mesc($value) {
        $search = array("\\", "\x00", "\n", "\r", "'", '"', "\x1a");
        $replace = array("\\\\", "\\0", "\\n", "\\r", "\'", '\"', "\\Z");
        return str_replace($search, $replace, $value);
    }

    function allowedComplianceCategory($cat_id, $loginUserDivisionsArray) {

//                prd($loginUserDivisionsArray);

        if ($cat_id == 2101 and in_array(108, $loginUserDivisionsArray)) {
            $allowedCat = 1;
        } else if ($cat_id == 2007 and in_array(2100, $loginUserDivisionsArray)) {
            $allowedCat = 1;
        } else if ($cat_id == 2008 and in_array(2104, $loginUserDivisionsArray)) {
            $allowedCat = 1;
        } else if ($cat_id == 2009 and in_array(2103, $loginUserDivisionsArray)) {
            $allowedCat = 1;
        } else if ($cat_id == 2010 and in_array(2102, $loginUserDivisionsArray)) {
            $allowedCat = 1;
        } else {
            $allowedCat = 0;
        }
        return $allowedCat;
    }

    function encrypt($plaintext, $password = "bmfrn1c8r", $salt = '!kQm*fF3pXe1Kbm%9') {
        /*
          $key = hash('SHA256', $salt . $password, true);
          srand(); $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC), MCRYPT_RAND);
          if (strlen($iv_base64 = rtrim(base64_encode($iv), '=')) != 22) return false;
          $ciphertext = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $decrypted . md5($decrypted), MCRYPT_MODE_CBC, $iv));
          return $iv_base64 . $ciphertext;
         */
        //$key = hash('SHA256', $salt . $password, true);

        $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($plaintext, $cipher, $key, $options = OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary = true);
        $ciphertext = base64_encode($iv . $hmac . $ciphertext_raw);
        return $ciphertext;
    }

    function decrypt($ciphertext, $password = "bmfrn1c8r", $salt = '!kQm*fF3pXe1Kbm%9') {
        /*
          $key = hash('SHA256', $salt . $password, true);
          $iv = base64_decode(substr($ciphertext, 0, 22) . '==');
          $ciphertext = substr($ciphertext, 22);
          $decrypted = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, base64_decode($ciphertext), MCRYPT_MODE_CBC, $iv), "\0\4");
          $hash = substr($decrypted, -32);
          $decrypted = substr($decrypted, 0, -32);
          if (md5($decrypted) != $hash) return false;
          return $decrypted;
         */
        //$key = hash('SHA256', $salt . $password, true);
        $c = base64_decode($ciphertext);
        $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
        $iv = substr($c, 0, $ivlen);
        $hmac = substr($c, $ivlen, $sha2len = 32);
        $ciphertext_raw = substr($c, $ivlen + $sha2len);
        $original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, $key, $options = OPENSSL_RAW_DATA, $iv);
        $calcmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary = true);
        if (hash_equals($hmac, $calcmac)) {//PHP 5.6+ timing attack safe comparison
            return $original_plaintext . "\n";
        }
    }

    function divisionClass($divisionName) {
        if ($divisionName == 'Security') {
            return " ASM";
        } else if ($divisionName == 'Facilities') {
            return "AFM";
        } else if ($divisionName == 'Traffic') {
            return "ATM";
        } else if ($divisionName == 'Civil') {
            return "ACM";
        } else if (trim($divisionName) == 'Pest') {
            return "APM";
        } else {
            return "division";
        }
    }

    function shorten($txt, $by) {
        if (strlen($txt) > $by)
            $txt = substr($txt, 0, $by) . "...";
        return $txt;
    }

    function get_lookup($lookup_name, $pfix = "") {
        return "select * from (select 0 as id, -10000 as `sort_order`, '--- Select ---' as item_name union all select id, sort_order, item_name from $pfix" . "lookup_fields where lookup_id in (select id from $pfix" . "lookups where item_name = '$lookup_name')) a  order by sort_order, item_name";
    }

    function get_blank_lookup($lookup_name, $pfix = "") {
        return "select id, sort_order, item_name from $pfix" . "lookup_fields where lookup_id in (select id from $pfix" . "lookups where item_name = '$lookup_name') order by sort_order, item_name";
    }

    function get_cmb_lookup($lookup_name) {
        return "select id as `idin`, sort_order, item_name as `result` from lookup_fields where lookup_id in (select id from lookups where item_name = '$lookup_name') order by sort_order, item_name";
    }

    function lookup_list($lookup_name) {
        return $this->get_sql_result("select group_concat(item_name) as `result` from lookup_fields where lookup_id in (select id from lookups where item_name = '$lookup_name') order by sort_order, item_name");
    }

    function get_simple_lookup($lookup_name, $top_text = 'Select') {
        return "select 0 as id, '--- $top_text ---' as item_name union all select id, item_name from $lookup_name order by id, item_name";
    }

    function get_simple_lookup_access_level($lookup_name, $top_text = 'Select') {
        $condition = $_SESSION['user_id'] > 0 ? ' and id != 10000' : "";
        return "select 0 as id, '--- $top_text ---' as item_name union all select id, item_name from $lookup_name where status = 1 $condition order by id, item_name";
    }

    function get_chl($lookup_name) {
        return "select id, item_name from lookup_fields where lookup_id in (select id from lookups where item_name = '$lookup_name') order by sort_order, item_name";
    }

    function standard_date($date_in) {
        if ($date_in)
            return Date("d-M-Y", strtotime($date_in));
    }

    function user_dropdown($group1, $group2 = 0, $group3 = 0, $group4 = 0, $group5 = 0, $is_or = 0, $only_active = 1) {
        $str = "SELECT users.id as `idin`, CONCAT(users.name, ' ', users.surname, if(users.employee_id != '', CONCAT(' (', users.employee_id, ')'), if(users.client_id != '', CONCAT(' (', users.client_id, ')'), ''))) as `item_name` FROM users
    inner join lookup_answers on lookup_answers.foreign_id = users.id and (lookup_answers.lookup_field_id = $group1 " . ($is_or ? "or lookup_answers.lookup_field_id = $group2 or lookup_answers.lookup_field_id = $group3 or lookup_answers.lookup_field_id = $group4 or lookup_answers.lookup_field_id = $group5" : "") . ") and lookup_answers.table_assoc = 'users'";
        if (!$is_or) {
            $str .= ($group2 ? " inner join lookup_answers2 on lookup_answers2.foreign_id = users.id and lookup_answers2.lookup_field_id = $group2 and lookup_answers2.table_assoc = 'users' " : "") .
                    ($group3 ? " inner join lookup_answers3 on lookup_answers3.foreign_id = users.id and lookup_answers3.lookup_field_id = $group3 and lookup_answers3.table_assoc = 'users' " : "") .
                    ($group4 ? " inner join lookup_answers4 on lookup_answers4.foreign_id = users.id and lookup_answers4.lookup_field_id = $group4 and lookup_answers4.table_assoc = 'users' " : "") .
                    ($group5 ? " inner join lookup_answers5 on lookup_answers5.foreign_id = users.id and lookup_answers5.lookup_field_id = $group5 and lookup_answers5.table_assoc = 'users' " : "");
        }
        $str .= ($only_active ? "where users.user_status_id = 30" : "") . " order by users.name, users.surname";
        return $str;
    }

    function userRoleByLevel($user_level_id) {
        $lfId = 103;
        $sql = "select lf.id from lookup_fields lf"
                . " left join user_level ul on (lf.lookup_id = 21 and ul.item_name = lf.item_name)"
                . " where ul.id IS NOT NULL and ul.id =  '" . $user_level_id . "'";
//          echo $sql;
//          die;


        $result = $this->dbi->query($sql);
        if ($myrow = $result->fetch_assoc()) {
            $lfId = $myrow['id'];
        }
        return $lfId;
    }

    function userRoleNameByUid($user_id) {
        //$lfId = 103;

        $sql = "select * from lookup_answers la "
                . "left join lookup_fields lf on (lf.lookup_id = 21 and la.lookup_field_id = lf.id and la.foreign_id = '" . $user_id . "' and la.table_assoc = 'users')"
                . " WHERE lf.id IS NOT NULL AND lookup_id in (select id from lookups where item_name = 'user_group')";

//          $sql = "select lf.id from lookup_fields lf"
//                  . " left join user_level ul on (lf.lookup_id = 21 and ul.item_name = lf.item_name)"
//                  . " where ul.id IS NOT NULL and ul.id =  '".$user_level_id."'";
        //echo $sql;


        $result = $this->dbi->query($sql);
        if ($result && $myrow = $result->fetch_assoc()) {
            $lfId = $myrow['item_name'];
        } else {
            $lfId = "---";
        }

        return $lfId;
    }

    function getReportDetail($editReportid) {
        $sql = "select *,rts.start_time_date from compliance_checks cc left join roster_times_staff rts on rts.id = cc.subject_id where cc.id = " . $editReportid;

        $result = $this->dbi->query($sql);
        if ($result && $myrow = $result->fetch_assoc()) {
            return $myrow;
        } else {
            return false;
        }
    }

    function getAllUser() {
        $sql = "SELECT users.id as `id`, CONCAT(users.employee_id, ' - ', users.name, ' ', users.surname,'') as `item_name`
               FROM users        
               inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = 107 and lookup_answers.table_assoc = 'users'               
               where users.user_status_id = 30 AND (users.employee_id IS NOT NULL  and users.employee_id != '')
               order by users.name asc";

        $userList = array();
        if ($result = $this->dbi->query($sql)) {
            while ($myrow = $result->fetch_assoc()) {
                $userList[] = $myrow;
            }
        }

        return $userList;
    }

    function updateUserRoleByLevel($userId, $user_level_id) {

        $userRoleId = $this->userRoleByLevel($user_level_id);

        $updateQuery = "update users  set user_mainrole = '" . $userRoleId . "' where id = '" . $userId . "'";
        //echo $updateQuery;

        $result = $this->dbi->query($updateQuery);

        $query = "select * from  lookup_answers where foreign_id = '" . $userId . "' and lookup_field_id = '" . $userRoleId . "' and table_assoc = 'users'";

        // echo $query;
        //die;

        $result = $this->dbi->query($query);
        if ($result && $result->fetch_assoc()) {
            
        } else {

            $ins_str .= "($userId, $userRoleId, 'users'),";
            $ins_str = substr($ins_str, 0, strlen($ins_str) - 1);
            $getAllRoleQuery = $this->get_chl('user_group');
            //echo $getAllRoleQuery;
            //die;
            //$deleteSql = "delete from lookup_answers where foreign_id = '".$userId."' and lookup_field_id in ($getAllRoleQuery)and table_assoc = 'users';";
            //$this->dbi->query($deleteSql);

            $sql = " insert into lookup_answers (foreign_id, lookup_field_id, table_assoc) values $ins_str;";

            $this->dbi->query($sql);
//               print_r($this->dbi->error);
            // die;
        }
    }

    function updateUserType($userId, $user_type) {

        //$userRoleId = $this->userRoleByLevel($user_level_id);

        $query = "select * from  lookup_answers where foreign_id = '" . $userId . "' and lookup_field_id = '" . $user_type . "' and table_assoc = 'users'";

        // echo $query;
        //die;

        $result = $this->dbi->query($query);
        if ($result && $result->fetch_assoc()) {
            
        } else {

            $ins_str .= "($userId, $user_type, 'users'),";
            $ins_str = substr($ins_str, 0, strlen($ins_str) - 1);
            //$getAllRoleQuery = $this->get_chl('user_group');
            //echo $getAllRoleQuery;
            //die;
            //$deleteSql = "delete from lookup_answers where foreign_id = '".$userId."' and lookup_field_id in ($getAllRoleQuery)and table_assoc = 'users';";
            //$this->dbi->query($deleteSql);

            $sql = " insert into lookup_answers (foreign_id, lookup_field_id, table_assoc) values $ins_str;";

            $this->dbi->query($sql);
//               print_r($this->dbi->error);
            // die;
        }
    }

    function roosterDetail($rosterId) {

//        select users2.name roster_location,users.name,users.phone,users.phone2,users.email,roster_times_staff.id, 
//              roster_times.start_time_date as `roster_start`, roster_times.finish_time_date as `roster_finish`, 
//              roster_times_staff.start_time_date, roster_times_staff.finish_time_date, roster_times_staff.staff_comment,
//              users2.id as `site_id`, users2.name as `site`
//                  from roster_times_staff
//                  left join roster_times on roster_times.id = roster_times_staff.roster_time_id
//                  left join users on users.id = roster_times_staff.staff_id              
//                  left join rosters on rosters.id = roster_times.roster_id
//                  left join users2 on users2.id = rosters.site_id
//                  where rosters.is_published = 1 and users.id = $userId and roster_times.id = $roster_time_id ";

        $query = "select rosters.id roster_id,rosters.site_id site_id,rosters.division_id,lookup_fields.item_name division_name,rosters.start_date roster_start_date,users2.name as `site_name`  "
                . " from rosters"
                . " left join users2 on users2.id = rosters.site_id"
                . " left join lookup_fields on (lookup_fields.id = rosters.division_id and `lookup_fields`.`lookup_id` = 136) and (`lookup_fields`.`value` = 'COMPANY')"
                . " where rosters.id = '" . $rosterId . "'";

        $result = $this->dbi->query($query);
        if ($result && $myrow = $result->fetch_assoc()) {
            return $myrow;
        } else {
            return false;
        }
    }

    function rosterShiftEmpDetail($roster_shift_staff_id) {

        $query = "select emp.name employee_name,rt.start_time_date shift_start_date_time, rt.finish_time_date shift_end_date_time, rt.team_group_name team_name, rosters.id roster_id,rosters.site_id site_id,rosters.division_id,lookup_fields.item_name division_name,rosters.start_date roster_start_date,users2.name as `site_name`  "
                . " from roster_times_staff rts"
                . " left join users2 emp on emp.id = rts.staff_id"
                . " left join  roster_times rt on rt.id = rts.roster_time_id "
                . "  left join rosters on rosters.id = rt.roster_id"
                . " left join users2 on users2.id = rosters.site_id"
                . " left join lookup_fields on (lookup_fields.id = rosters.division_id and `lookup_fields`.`lookup_id` = 136) and (`lookup_fields`.`value` = 'COMPANY')"
                . " where rts.id = '" . $roster_shift_staff_id . "'";

        $result = $this->dbi->query($query);
//          echo $query;
//        die;
        if ($result && $myrow = $result->fetch_assoc()) {
            return $myrow;
        } else {
            return false;
        }
    }

    function rosterShiftDetail($roster_shiftid) {

        $query = "select rt.start_time_date shift_start_date_time, rt.finish_time_date shift_end_date_time, rt.team_group_name team_name, rosters.id roster_id,rosters.site_id site_id,rosters.division_id,lookup_fields.item_name division_name,rosters.start_date roster_start_date,users2.name as `site_name`  "
                . " from roster_times rt "
                . "  left join rosters on rosters.id = rt.roster_id"
                . " left join users2 on users2.id = rosters.site_id"
                . " left join lookup_fields on (lookup_fields.id = rosters.division_id and `lookup_fields`.`lookup_id` = 136) and (`lookup_fields`.`value` = 'COMPANY')"
                . " where rt.id = '" . $roster_shiftid . "'";

//      echo $query;
//      die;

        $result = $this->dbi->query($query);
        if ($result && $myrow = $result->fetch_assoc()) {
            // prd($myrow);
            return $myrow;
        } else {
            return false;
        }
    }

    function rosterLog($rosterLogTypeId, $rosterId, $rosterShiftId, $roster_shift_staff_id, $detail) {



        $rosterDetail = false;
        $sql = false;
        if ($rosterId) {
            $rosterDetail = $this->roosterDetail($rosterId);
        } else if ($rosterShiftId) {

            $rosterDetail = $this->rosterShiftDetail($rosterShiftId);
//                prd($rosterDetail);
//                die;
        } else if ($roster_shift_staff_id) {
            $rosterDetail = $this->rosterShiftEmpDetail($roster_shift_staff_id);
        }


        //prd($rosterDetail);
        $rosterNewId = $rosterDetail['roster_id'];
        $siteId = $rosterDetail['site_id'];
        $divisionId = $rosterDetail['division_id'];
        $divisionName = $rosterDetail['division_name'];
        $rosterStartDate = $rosterDetail['roster_start_date'];
        $siteName = $rosterDetail['site_name'];

        if ($rosterId) {
            // die("2");
            $sql = " insert into roster_logs "
                    . " (roster_logtypes_id,roster_id,site_id,division_id,division_name,site_name,roster_start_date,detail,created_by) "
                    . " values ('{$rosterLogTypeId}','{$rosterNewId}','{$siteId}','{$divisionId}','{$divisionName}','{$siteName}'"
                    . ",'{$rosterStartDate}','{$detail}','{$_SESSION['user_id']}');";
        } else if ($rosterShiftId) {
            //die("1");
            $shift_start_date_time = $rosterDetail['shift_start_date_time'];
            $shift_end_date_time = $rosterDetail['shift_end_date_time'];
            $team_name = $rosterDetail['team_name'];

            $sql = " insert into roster_logs "
                    . " (roster_logtypes_id,roster_id,site_id,division_id,division_name,site_name,roster_start_date,detail,created_by,shift_start_date_time,shift_end_date_time,team_name) "
                    . " values ('{$rosterLogTypeId}','{$rosterNewId}','{$siteId}','{$divisionId}','{$divisionName}','{$siteName}'"
                    . ",'{$rosterStartDate}','{$detail}','{$_SESSION['user_id']}','{$shift_start_date_time}','{$shift_end_date_time}','{$team_name}');";
        } else if ($roster_shift_staff_id) {

            $shift_start_date_time = $rosterDetail['shift_start_date_time'];
            $shift_end_date_time = $rosterDetail['shift_end_date_time'];
            $team_name = $rosterDetail['team_name'];
            $employee_name = $rosterDetail['employee_name'];

            $sql = " insert into roster_logs "
                    . " (roster_logtypes_id,roster_id,site_id,division_id,division_name,site_name,roster_start_date,detail,created_by,shift_start_date_time,shift_end_date_time,team_name,employee_name) "
                    . " values ('{$rosterLogTypeId}','{$rosterNewId}','{$siteId}','{$divisionId}','{$divisionName}','{$siteName}'"
                    . ",'{$rosterStartDate}','{$detail}','{$_SESSION['user_id']}','{$shift_start_date_time}','{$shift_end_date_time}','{$team_name}','{$employee_name}');";
        }


        if ($sql) {
            $this->dbi->query($sql);
        }
    }

    function user_select_dropdown_old($group1 = 0, $group2 = 0, $group3 = 0, $group4 = 0, $group5 = 0, $is_or = 0, $only_active = 1) {


        $str = "select '0' as id, '--- Select ---' `item_name` union all (SELECT users.id as `id`, CONCAT(users.name, ' ', users.surname, if(users.employee_id != '', CONCAT(' (', users.employee_id, ')'), if(users.client_id != '', CONCAT(' (', users.client_id, ')'), ''))) as `item_name` FROM users
    inner join lookup_answers on lookup_answers.foreign_id = users.id and (lookup_answers.lookup_field_id = $group1 " . ($is_or ? "or lookup_answers.lookup_field_id = $group2 or lookup_answers.lookup_field_id = $group3 or lookup_answers.lookup_field_id = $group4 or lookup_answers.lookup_field_id = $group5" : "") . ") and lookup_answers.table_assoc = 'users'";
        if (!$is_or) {
            $str .= ($group2 ? " inner join lookup_answers2 on lookup_answers2.foreign_id = users.id and lookup_answers2.lookup_field_id = $group2 and lookup_answers2.table_assoc = 'users' " : "") .
                    ($group3 ? " inner join lookup_answers3 on lookup_answers3.foreign_id = users.id and lookup_answers3.lookup_field_id = $group3 and lookup_answers3.table_assoc = 'users' " : "") .
                    ($group4 ? " inner join lookup_answers lookup_answers4 on lookup_answers4.foreign_id = users.id and lookup_answers4.lookup_field_id = $group4 and lookup_answers4.table_assoc = 'users' " : "") .
                    ($group5 ? " inner join lookup_answers lookup_answers5 on lookup_answers5.foreign_id = users.id and lookup_answers5.lookup_field_id = $group5 and lookup_answers5.table_assoc = 'users' " : "");
        }
        $str .= ($only_active ? "where users.user_status_id = 30" : "") . " order by users.name, users.surname)";
        //echo $str;
        //die;
        return $str;
    }

    function user_select_dropdown($group1 = 0, $group2 = 0, $group3 = 0, $group4 = 0, $group5 = 0, $is_or = 1, $only_active = 1) {

        $str = "select '0' as id, '--- Select ---' `item_name` union all (SELECT distinct(users.id) as `id`, CONCAT(users.name, ' ', users.surname, if(users.employee_id != '', CONCAT(' (', users.employee_id, ')'), if(users.client_id != '', CONCAT(' (', users.client_id, ')'), ''))) as `item_name` FROM users
    inner join lookup_answers on lookup_answers.foreign_id = users.id and (lookup_answers.lookup_field_id = $group1 )and lookup_answers.table_assoc = 'users'";
        if (!$is_or) {
            $str .= ($group2 ? " inner join lookup_answers2 on lookup_answers2.foreign_id = users.id and lookup_answers2.lookup_field_id = $group2 and lookup_answers2.table_assoc = 'users' " : "") .
                    ($group3 ? " inner join lookup_answers3 on lookup_answers3.foreign_id = users.id and lookup_answers3.lookup_field_id = $group3 and lookup_answers3.table_assoc = 'users' " : "") .
                    ($group4 ? " inner join lookup_answers lookup_answers4 on lookup_answers4.foreign_id = users.id and lookup_answers4.lookup_field_id = $group4 and lookup_answers4.table_assoc = 'users' " : "") .
                    ($group5 ? " inner join lookup_answers lookup_answers5 on lookup_answers5.foreign_id = users.id and lookup_answers5.lookup_field_id = $group5 and lookup_answers5.table_assoc = 'users' " : "");
        } else {
            if ($group1 == 105) {
                $str .= " inner join lookup_answers as lookup_answersc on lookup_answersc.foreign_id = lookup_answers.foreign_id and (lookup_answersc.lookup_field_id = $group1 or lookup_answersc.lookup_field_id = $group2 or lookup_answersc.lookup_field_id = $group3 or lookup_answersc.lookup_field_id = $group4 or lookup_answersc.lookup_field_id = $group5) and lookup_answersc.table_assoc = 'users'";
            } else {
                $str .= " inner join lookup_answers as lookup_answersc on lookup_answersc.foreign_id = lookup_answers.foreign_id and (lookup_answersc.lookup_field_id = $group2 or lookup_answersc.lookup_field_id = $group3 or lookup_answersc.lookup_field_id = $group4 or lookup_answersc.lookup_field_id = $group5) and lookup_answersc.table_assoc = 'users'";
            }
        }
        $str .= ($only_active ? "where users.user_status_id = 30" : "") . " order by users.name, users.surname)";
//            if ($group1 == 105) {
//                echo $str;
//                die;
//            }


        return $str;
    }

    function supplierCategory() {

        $jobs_category = $this->db->exec("SELECT id,category_name FROM jobs_category WHERE category_status=1 ORDER BY category_name ASC");
        return $jobs_category;
    }

    function supplierCategorySelected($userId) {

//         echo "SELECT job_category_id from supplier_jobcategory WHERE user_id =".$userId;
//         die;

        $supplier_category = $this->db->exec("SELECT job_category_id from supplier_jobcategory WHERE user_id =$userId");
        $ssCategory = array();
        if ($supplier_category) {
            foreach ($supplier_category as $value) {
                $ssCategory[] = $value['job_category_id'];
            }
        }
//         echo $ssCategory;
//         die;
        return $ssCategory;
    }

    function updateSupplierCategory($userId, $supplierCategories) {

        if (!empty($supplierCategories)) {
            $jobs_category = $this->db->exec("delete from supplier_jobcategory WHERE user_id =$userId");
            foreach ($supplierCategories as $scValue) {
                if ($scValue) {
                    $this->db->exec("insert into supplier_jobcategory (user_id,job_category_id) value ($userId,$scValue)");
                }
            }
            return $jobs_category;
        }
    }

    function distance($lat1, $lon1, $lat2, $lon2) {
        $lat1 = ($lat1 ? $lat1 : 0);
        $lat2 = ($lat2 ? $lat2 : 0);
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist) * 111189.57696;

        return $dist;
    }

    function getDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $unit = 'K') {
        // Google API key
//    $apiKey = 'Your_Google_API_Key';
//    
//    // Change address format
//    $formattedAddrFrom    = str_replace(' ', '+', $addressFrom);
//    $formattedAddrTo     = str_replace(' ', '+', $addressTo);
//    
//    // Geocoding API request with start address
//    $geocodeFrom = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?address='.$formattedAddrFrom.'&sensor=false&key='.$apiKey);
//    $outputFrom = json_decode($geocodeFrom);
//    if(!empty($outputFrom->error_message)){
//        return $outputFrom->error_message;
//    }
//    
//    // Geocoding API request with end address
//    $geocodeTo = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?address='.$formattedAddrTo.'&sensor=false&key='.$apiKey);
//    $outputTo = json_decode($geocodeTo);
//    if(!empty($outputTo->error_message)){
//        return $outputTo->error_message;
//    }
        // Get latitude and longitude from the geodata
//    $latitudeFrom    = $outputFrom->results[0]->geometry->location->lat;
//    $longitudeFrom    = $outputFrom->results[0]->geometry->location->lng;
//    $latitudeTo        = $outputTo->results[0]->geometry->location->lat;
//    $longitudeTo    = $outputTo->results[0]->geometry->location->lng;
        // Calculate distance between latitude and longitude
        $theta = $longitudeFrom - $longitudeTo;
        $dist = sin(deg2rad($latitudeFrom)) * sin(deg2rad($latitudeTo)) + cos(deg2rad($latitudeFrom)) * cos(deg2rad($latitudeTo)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;

        // Convert unit and return distance
        $unit = strtoupper($unit);
        if ($unit == "K") {
            return round($miles * 1.609344, 2) . ' km';
        } elseif ($unit == "M") {
            return round($miles * 1609.344, 2) . ' meters';
        } else {
            return round($miles, 2) . ' miles';
        }
    }

    function redirect($page) {
        $str = '
      <script type="text/javascript">
        window.location = "' . $page . '";
      </script>
    ';
        return $str;
    }

    function alter_brightness($colourstr, $steps) {
        $colourstr = str_replace('#', '', $colourstr);
        $rhex = substr($colourstr, 0, 2);
        $ghex = substr($colourstr, 2, 2);
        $bhex = substr($colourstr, 4, 2);
        $r = hexdec($rhex);
        $g = hexdec($ghex);
        $b = hexdec($bhex);
        $r = dechex(max(0, min(255, $r + $steps)));
        $g = dechex(max(0, min(255, $g + $steps)));
        $b = dechex(max(0, min(255, $b + $steps)));
        $r = str_pad($r, 2, "0", STR_PAD_LEFT);
        $g = str_pad($g, 2, "0", STR_PAD_LEFT);
        $b = str_pad($b, 2, "0", STR_PAD_LEFT);
        $cor = '#' . $r . $g . $b;
        return $cor;
    }

    function hex2RGB($color) {
        $color = str_replace('#', '', $color);
        if (strlen($color) != 6) {
            return array(0, 0, 0);
        }
        $rgb = array();
        for ($x = 0; $x < 3; $x++) {
            $rgb[$x] = hexdec(substr($color, (2 * $x), 2));
        }
        return $rgb;
    }

    function get_site_id() {
        $site_id = (isset($_SESSION['site_id']) ? $_SESSION['site_id'] : 0);

        if (!$site_id) {
            $sql = "select users.id as `idin` from users
                        left join associations on associations.parent_user_id = users.id 
                        where associations.association_type_id = (select id from association_types where name = '" . ($this->f3->get('is_client_staff') ? "site_client_staff" : "site_staff") . "') and associations.child_user_id = " . $_SESSION['user_id'];

            $result = $this->dbi->query($sql);
            if ($myrow = $result->fetch_assoc()) {
                $site_id = $myrow['idin'];
            }
        }
        return $site_id;
    }

    function getUserDetail($userId) {
        $sql = "select * from users where users.id = '" . $userId . "'";
        $result = $this->dbi->query($sql);

        if ($userDetail = $result->fetch_assoc()) {
            return $userDetail;
        }
        return "0";
    }

    function userDataAccessType($userId) {

        $userDetail = $this->getUserDetail($userId);

        if ($userDetail) {
            if ($userDetail['user_level_id'] >= 7000) {
                $userDataAccessType = 1;
            } else if ($userDetail['user_level_id'] >= 600) {
                $userDataAccessType = 2;
            } else {
                $userDataAccessType = 3;
            }
            return $userDataAccessType;
        }

        return 0;
    }

    function onLoginSetUserType($lids, $userId) {
        if ($lids && $userId) {
            $userType = $this->getUserType($userId);
            $lids[] = $userType;
//            prd($lids);
//            die;

            $this->f3->set('is_staff', (array_search(107, $lids) !== false) ? 1 : 0);
            $this->f3->set('is_security', (array_search(108, $lids) !== false) ? 1 : 0);
            $this->f3->set('hr_user', (array_search(461, $lids) !== false) ? 1 : 0);
            $this->f3->set('is_admin', (array_search(114, $lids) !== false) ? 1 : 0);
            $this->f3->set('is_client', (array_search(104, $lids) !== false) ? 1 : 0);
            $this->f3->set('is_site', (array_search(384, $lids) !== false) ? 1 : 0);
            $this->f3->set('is_client_staff', (array_search(504, $lids) !== false) ? 1 : 0);
        }
    }

    function getUserType($userId) {
        $sql = "select lookup_answers.lookup_field_id from users
                        inner join lookup_answers on lookup_answers.foreign_id = users.id 
                        where users.id  = '" . $userId . "' and lookup_answers.lookup_field_id in (104,107,384,105)";

        $result = $this->dbi->query($sql);
        $userTypes = "";
        if ($myrow = $result->fetch_assoc()) {
            $userTypes = $myrow['lookup_field_id'];
        }
        return $userTypes;
    }

    function getUserId($userId) {
        $userIds = "";
        $sql = "select CONCAT(if(employee_id IS NOT NULL && employee_id != '', CONCAT(employee_id, '<br />'), ''), if(client_id  IS NOT NULL && client_id != '', CONCAT(client_id, '<br />'), ''), if(supplier_id IS NOT NULL && supplier_id != '', CONCAT(supplier_id, '<br />'), '')) as `user_id`
          from users where users.id  = '" . $userId . "'";

        $result = $this->dbi->query($sql);
        if ($myrow = $result->fetch_assoc()) {
            $userIds = $myrow['user_id'];
        }
        return $userIds;
    }

    function rosteringStateIds($userId) {
        $userIds = "0";
        $sql = "select `state`
          from users where users.id  = '" . $userId . "'";

        $result = $this->dbi->query($sql);
        if ($myrow = $result->fetch_assoc()) {
            $userIds = $myrow['state'];
        }
        return $userIds;
    }

    function getUserName($userId) {
        $userIds = "";
        $sql = "select  username
          from users where users.id  = '" . $userId . "'";

        $result = $this->dbi->query($sql);
        if ($myrow = $result->fetch_assoc()) {
            $userIds = $myrow['username'];
        }
        return $userIds;
    }

    function getUserRole($userId) {
        $lfId = "";
        $sql = "select lf.item_name from lookup_fields lf"
                . " left join user_level ul on (lf.lookup_id = 21 and ul.item_name = lf.item_name)"
                . " where ul.id IS NOT NULL and ul.id =  '" . $userId . "'";

        $result = $this->dbi->query($sql);
        if ($myrow = $result->fetch_assoc()) {
            $lfId = $myrow['item_name'];
        }
        return $lfId;
    }

    /*
     * output get State Ids
     */

    function getStateIds() {
        $sql = "select id, item_name from states order by item_name";
        $stateList = array();
        if ($result = $this->dbi->query($sql)) {
            while ($myrow = $result->fetch_assoc()) {
                $stateList[] = $myrow;
            }
        }

        return $stateList;
    }

    /*
     * output get records for dropdown
     */

    function getRecordIds($sql) {

        $recordList = array();
        if ($result = $this->dbi->query($sql)) {
            while ($myrow = $result->fetch_assoc()) {
                $recordList[] = $myrow;
            }
        }
        return $recordList;
    }

    /*
     * output get State Ids
     */

    function getStickyNotes($site_id) {
        $sql = "select * from sticky_notes where site_id = '" . $site_id . "' order by updated_at desc";

        $stickyNotesList = array();
        if ($result = $this->dbi->query($sql)) {
            while ($myrow = $result->fetch_assoc()) {
                $stickyNotesList[] = $myrow;
            }
        }

        return $stickyNotesList;
    }

    /*
     * 
     */

    function getStickyNotesContent($site_id) {
        $stickyNotesContent = $this->getStickyNotes($site_id);
//        pr($stickyNotesContent);
//        prd("hello");
        $stickyNotesStr = "";
        //<div class="fl comment_head">Amer Awad (APM) replied on 03-Sep-2022 at 23:34 </div><div class="fr"><a class="list_a" href="JavaScript:delete_comment(2204)">Remove Comment</a></div><div class="cl"></div>
        foreach ($stickyNotesContent as $value) {
            //comment
            $stickyNotesStr .= '<div class="log_entry"><div class="fl" style="width:950px;">' . $value['notes'] . ' </div>';
            //die($value['added_by']);
            if ($value['added_by'] == $_SESSION['user_id']) {
                $stickyNotesStr .= '<div class="fl"><a href="javascript:void(0))" onclick="deleteStickyNotes(' . $value["id"] . ',' . $site_id . ')" >X</a></div>';
            }
            $stickyNotesStr .= '<div style="clear:both"></div></div>';
        }
        return $stickyNotesStr;
    }

    function addStrickyNotes($siteId, $strickyNotes, $loginUserId) {
        $sql = " insert into sticky_notes (site_id,notes, added_by) values ($siteId,'$strickyNotes',$loginUserId);";

        $this->dbi->query($sql);
    }

    function deleteStrickyNotes($stickyNotesId) {
        $sql = "delete from sticky_notes where id = '" . $stickyNotesId . "'";
//         echo $sql;
//         die;
        $this->dbi->query($sql);
    }

    /*
     * assigned StatetIds 
     */

    function getAssignedStateIds($userId) {
        $sql = "select sta.id, sta.item_name from states sta "
                . " left join user_assign_states uas on uas.state_id = sta.id"
                . " where uas.user_id = '" . $userId . "'"
                . " order by item_name";

        $stateList = array();
        if ($result = $this->dbi->query($sql)) {
            while ($myrow = $result->fetch_assoc()) {
                $stateList[] = $myrow;
            }
        }

        return $stateList;
    }

    /*
     *  get Division Ids
     */

    function getDivisionIds($id = 0) {
        $sql = "select id, item_name from companies ";

        if ($id == 'all') {
            
        } else if ($id) {
            $sql .= ' where id = "' . $id . '"';
        } else {
            $sql .= ' where id = "0"';
        }
        $sql .= " order by item_name";

//        $sql = "select sta.id, sta.item_name from states sta "
//                . " left join user_assign_states uas on uas.state_id = sta.id"
//                . " where uas.user_id = '" . $userId . "'"
//                . " order by item_name";

        $divisionList = array();
        if ($result = $this->dbi->query($sql)) {
            while ($myrow = $result->fetch_assoc()) {
                $divisionList[] = $myrow;
            }
        }

        return $divisionList;
    }

    function updateComplianceDivisions($lookup_id, $complianceDivisions) {
        $updateSql = "update compliance set division_id = '" . $complianceDivisions . "' where id = '" . $lookup_id . "'";
        $result = $this->dbi->query($updateSql);
    }

    function getComplianceDivisions($lookup_id) {
        $selectSql = "select division_id from compliance where id = '" . $lookup_id . "'";
        if ($result = $this->dbi->query($selectSql)) {
            if ($myrow = $result->fetch_assoc()) {
                return $myrow["division_id"];
            }
        }
        return "";
    }

    function checkValidComplianceUserDivisionId($complianceDivisionIdStr, $userDivisionIdStr) {
        if ($complianceDivisionIdStr) {
            $complianceDivisionIdArray = explode(',', $complianceDivisionIdStr);
            $userDivisionIdArray = explode(',', $userDivisionIdStr);
            foreach ($complianceDivisionIdArray as $comDivisionId) {
                if (in_array($comDivisionId, $userDivisionIdArray)) {
                    return true;
                    break;
                }
            }
            return false;
        } else {
            return true;
        }
    }

    function sendHandOverReport($reportId) {


        $query = "  select cca.id ccaid from compliance_check_answers cca"
                . " inner join compliance_checks cc on cc.id = cca.compliance_check_id and cc.id = '" . $reportId . "'"
                . " where cca.question = 'Are there any issues that require follow up?' and cca.answer = 'yes' "
                . " and cca.additional_text != '' and cc.id = '" . $reportId . "' LIMIT 1";

        //echo $query;

        $result = $this->dbi->query($query);
        //var_dump($result);
        if ($result) {
            
            if ($myrow1 = $result->fetch_assoc()) {
                //die("test3");
                $query1 = "  select CONCAT(emp.name, ' ', emp.surname) emp_name,CONCAT(location.name, ' ', location.surname) site_name,"
                        . " clt.email clientEmail,"
                        . " location.site_contact_email1,
                    location.site_contact_email2,
                    location.site_contact_email3,
                    location.civil_manager_email,
                    location.facilities_manager_email,
                    location.pest_manager_email,
                    location.security_manager_email,
                    location.traffic_manager_email,
                    location.civil_manager_email2,
                    location.facilities_manager_email2,
                    location.pest_manager_email2,
                    location.security_manager_email2,
                    location.traffic_manager_email2, 
                    location.civil_manager_email3,
                    location.facilities_manager_email3,
                    location.pest_manager_email3,
                    location.security_manager_email3,
                    location.traffic_manager_email3,
                    location.civil_manager_mobile,
                    location.facilities_manager_mobile,
                    location.pest_manager_mobile,
                    location.security_manager_mobile,
                    location.traffic_manager_mobile,
                    location.civil_manager_mobile2,
                    location.facilities_manager_mobile2,
                    location.pest_manager_mobile2,
                    location.security_manager_mobile2,
                    location.traffic_manager_mobile2,                    
                    location.civil_manager_mobile3,
                    location.facilities_manager_mobile3,
                    location.pest_manager_mobile3,
                    location.security_manager_mobile3,
                    location.traffic_manager_mobile3
                    from compliance_check_answers cca"
                        . " inner join compliance_checks cc on cc.id = cca.compliance_check_id and cc.id = '" . $reportId . "'"
                        . " left join roster_times_staff rts on rts.id = cc.subject_id"
                        . " left join roster_times rt on rt.id = rts.roster_time_id"
                        . " left join rosters rst on rst.id = rt.roster_id"
                        . " left join users as emp on emp.id = rts.staff_id"
                        . " left join users as location on location.id = rst.site_id"
                        . " left join associations ass1 ON ass1.child_user_id = location.ID AND ass1.association_type_id = 1 "
                        . " left join users AS clt ON clt.ID = ass1.parent_user_id"
                        . " where cca.question = 'Are there any issues that require follow up?' and cca.answer = 'yes' "
                        . " and cca.additional_text != ''  and cc.id = '" . $reportId . "' LIMIT 1";

//        $query = "  select reportuser.email from compliance_checks cc"
//                . " left join users reportuser on reportuser.id = cc.assessor_id"
//                . " where cc.id = '" . $reportId . "' ORDER BY cc.id desc LIMIT 1";



                $result1 = $this->dbi->query($query1);

                if ($result1) {
                    if ($myrow = $result1->fetch_assoc()) {
                        $email_send = array(
                            $myrow['clientEmail'],
                            $myrow['site_contact_email1'], $myrow['site_contact_email2'], $myrow['site_contact_email3'],
                            $myrow['civil_manager_email'], $myrow['facilities_manager_email'], $myrow['pest_manager_email'], $myrow['security_manager_email'], $myrow['traffic_manager_email'],
                            $myrow['civil_manager_email2'], $myrow['facilities_manager_email2'], $myrow['pest_manager_email2'], $myrow['security_manager_email2'], $myrow['traffic_manager_email2'],
                            $myrow['civil_manager_email3'], $myrow['facilities_manager_email3'], $myrow['pest_manager_email3'], $myrow['security_manager_email3'], $myrow['traffic_manager_email3']);

                        $uniqueEmailSend = array_unique(array_values(array_filter($email_send)));
                       // print_r($uniqueEmailSend);

                        if (!empty($uniqueEmailSend)) {
                            foreach ($uniqueEmailSend as $toEmail) {
                                //echo "mm".$rosterTimeStaffId;

                                if ($mail) {
                                    $mail->clear_all();
                                }
                                //$to = "mahavir.jain@dotsquares.com"; // $myrow['email'];
                                $to = $toEmail;
                                // $rosterStaffEmail = "mahavir.jain@dotsquares.com";
                                $mail = new email_q($this->dbi);
                                $mail->AddAddress($to);
                                $mail->Subject = "Shift Handover Report";
//                                $mail->Body = "Hi Allied Management ".$myrow['emp_name'].",<br><br> Please see the attached handover reporth Signoff Handover Report of site ".$myrow['site_name']." <br><br>"
//                                        . "<a href='" . $this->f3->get('base_url') . "/CompliancePDF/" . $reportId . "?show_min=1'>Click Here For Download</a>"
//                                        . "</br><br><br>
//
//                            Thank you <br>
//                            Allied EDGE ";
                                
                                
                                $mail->Body = "Hi Allied Management,<br><br> Please see the attached handover report.<br><br>"
                                        . "<a href='" . $this->f3->get('base_url') . "/CompliancePDF/" . $reportId . "?show_min=1'>Click Here For Download</a>"
                                        . "</br><br>
                                            This report was sent to you as ".$myrow['emp_name']." has indicated while signing off from their shift at ".$myrow['site_name']." that an important information must be passed on.<br><br>
                            Thank you <br>
                            Allied EDGE ";

                                //$mail->AddAttachment($file_path_name);                     
//                          if ($mail->send()) {
//                                $mail->queue_message_sent();
//                            } 
                                $mail->queue_message();
                            }
                        }
                    }
                }
            } 
            
            /*else {
                //echo "test4";
                $query1 = "  select clt.email clientEmail,"
                        . " location.site_contact_email1,
                    location.site_contact_email2,
                    location.site_contact_email3,
                    location.civil_manager_email,
                    location.facilities_manager_email,
                    location.pest_manager_email,
                    location.security_manager_email,
                    location.traffic_manager_email,
                    location.civil_manager_email2,
                    location.facilities_manager_email2,
                    location.pest_manager_email2,
                    location.security_manager_email2,
                    location.traffic_manager_email2, 
                    location.civil_manager_email3,
                    location.facilities_manager_email3,
                    location.pest_manager_email3,
                    location.security_manager_email3,
                    location.traffic_manager_email3,
                    location.civil_manager_mobile,
                    location.facilities_manager_mobile,
                    location.pest_manager_mobile,
                    location.security_manager_mobile,
                    location.traffic_manager_mobile,
                    location.civil_manager_mobile2,
                    location.facilities_manager_mobile2,
                    location.pest_manager_mobile2,
                    location.security_manager_mobile2,
                    location.traffic_manager_mobile2,                    
                    location.civil_manager_mobile3,
                    location.facilities_manager_mobile3,
                    location.pest_manager_mobile3,
                    location.security_manager_mobile3,
                    location.traffic_manager_mobile3
                    from compliance_checks cc "
                        . " inner join roster_times_staff rts on rts.id = cc.subject_id"
                        . " left join roster_times rt on rt.id = rts.roster_time_id"
                        . " left join rosters rst on rst.id = rt.roster_id"
                        . " left join users as location on location.id = rst.site_id"
                        . " left join associations ass1 ON ass1.child_user_id = location.ID AND ass1.association_type_id = 1 "
                        . " left join users AS clt ON clt.ID = ass1.parent_user_id"
                        . " where cc.id = '" . $reportId . "' LIMIT 1";

//        $query = "  select reportuser.email from compliance_checks cc"
//                . " left join users reportuser on reportuser.id = cc.assessor_id"
//                . " where cc.id = '" . $reportId . "' ORDER BY cc.id desc LIMIT 1";



                $result1 = $this->dbi->query($query1);

                if ($result1) {
                    if ($myrow = $result1->fetch_assoc()) {
                        $email_send = array(
                            $myrow['civil_manager_email'], $myrow['facilities_manager_email'], $myrow['pest_manager_email'], $myrow['security_manager_email'], $myrow['traffic_manager_email'],
                            $myrow['civil_manager_email2'], $myrow['facilities_manager_email2'], $myrow['pest_manager_email2'], $myrow['security_manager_email2'], $myrow['traffic_manager_email2'],
                            $myrow['civil_manager_email3'], $myrow['facilities_manager_email3'], $myrow['pest_manager_email3'], $myrow['security_manager_email3'], $myrow['traffic_manager_email3']);

                        $uniqueEmailSend = array_unique(array_values(array_filter($email_send)));
//                        print_r($uniqueEmailSend);
//                        die;
                        //die('test2');
                        if (!empty($uniqueEmailSend)) {
                            foreach ($uniqueEmailSend as $toEmail) {
                                //echo "mm".$rosterTimeStaffId;

                                if ($mail) {
                                    $mail->clear_all();
                                }
                                //$to = "mahavir.jain@dotsquares.com"; // $myrow['email'];
                                $to = $toEmail;
                                // $rosterStaffEmail = "mahavir.jain@dotsquares.com";
                                $mail = new email_q($this->dbi);
                                $mail->AddAddress($to);
                                $mail->Subject = "Roster Handover Report";
                                $mail->Body = "Hi,<br><br> Find enclosed here with Signoff Handover Report <br><br>"
                                        . "<a href='" . $this->f3->get('base_url') . "/CompliancePDF/" . $reportId . "?show_min=1'>Click Here For Download</a>"
                                        . "</br><br><br>

                            Thank you <br>
                            Allied EDGE ";

                                //$mail->AddAttachment($file_path_name);                     
//                          if ($mail->send()) {
//                                $mail->queue_message_sent();
//                            } 
                                $mail->queue_message();
                            }
                        }
                    }
                }
            }*/
        }
    }

    function checkPreStartReportExist($reportId, $shiftId) {

        $query = "select * from compliance_checks cc where cc.compliance_id = '" . $reportId . "' and subject_id = '" . $shiftId . "' and assessor_id = '" . $_SESSION['user_id'] . "' ORDER BY cc.id desc LIMIT 1";
//       echo $query;
//       die;
        $result = $this->dbi->query($query);

        if ($result) {
            if ($myrow = $result->fetch_assoc()) {
                return $myrow['id'];
            } else {
                return false;
            }
        } else {
            return false;
        }

        if ($complianceDivisionIdStr) {
            $complianceDivisionIdArray = explode(',', $complianceDivisionIdStr);
            $userDivisionIdArray = explode(',', $userDivisionIdStr);
            foreach ($complianceDivisionIdArray as $comDivisionId) {
                if (in_array($comDivisionId, $userDivisionIdArray)) {
                    return true;
                    break;
                }
            }
            return false;
        } else {
            return true;
        }
    }

    function checkPreStartReportCompleted($reportId) {

        $query = "select * from compliance_checks cc where cc.id = '" . $reportId . "' ORDER BY cc.id desc LIMIT 1";
//       echo $query;
//       die;
        $result = $this->dbi->query($query);

        if ($result) {
            if ($myrow = $result->fetch_assoc()) {
                if ($myrow['status_id'] == 524) {
                    return $myrow['percent_complete'];
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }




//        if ($complianceDivisionIdStr) {
//            $complianceDivisionIdArray = explode(',', $complianceDivisionIdStr);
//            $userDivisionIdArray = explode(',', $userDivisionIdStr);
//            foreach ($complianceDivisionIdArray as $comDivisionId) {
//                if (in_array($comDivisionId, $userDivisionIdArray)) {
//                    return true;
//                    break;
//                }
//            }
//            return false;
//        } else {
//            return true;
//        }
    }

    function divisionUserList($site_id, $hdnDivisionID, $notIncludeSelf = 1) {
//        $sql = "select users.id as `user_id`, users.name, users.surname, users.email, users.email2 as `email2`, users.preferred_name as `email3`, users2.name as `site` 
//          from associations
//          left join users on users.id = associations.child_user_id
//          left join users2 on users2.id = associations.parent_user_id
//          where associations.association_type_id in (4,12)";
//          
//          $curr_divs_user = $this->get_divisions($_SESSION['user_id'], 0, 1);
//          $curr_divs_site = $this->get_divisions($site_id, 0, 1);
//          
//          //$sql .= ($hdnDivisionID ? " and associations.child_user_id in (select user_id from users_user_division_groups where user_group_id = $hdnDivisionID)) " : "");
//              
//          $sql .= ($hdnDivisionID ? " and associations.child_user_id in (select user_id from users_user_division_groups where user_group_id = $hdnDivisionID) and (user_group_id in ($curr_divs_user) and user_group_id in ($curr_divs_site))) " : " and associations.child_user_id in (select user_id from users_user_division_groups where (user_group_id in ($curr_divs_user) and user_group_id in ($curr_divs_site)))");
//          
//          $sql .= " and associations.parent_user_id = $site_id  and users.name NOT LIKE '%generic%' and users.name NOT LIKE '%guard%' and users.user_status_id = 30
//          and associations.child_user_id != " . $_SESSION['user_id'];

        $sql = "select users.id as `user_id`, users.name, users.surname, users.email, users.email2 as `email2`, users.preferred_name as `email3`, users2.name as `site` 
          from associations
          left join users on users.id = associations.child_user_id
          left join users2 on users2.id = associations.parent_user_id
          where associations.association_type_id in (4,12)";

        $curr_divs_user = $this->get_divisions($_SESSION['user_id'], 0, 1);
        $curr_divs_site = $this->get_divisions($site_id, 0, 1);

        //$sql .= ($hdnDivisionID ? " and associations.child_user_id in (select user_id from users_user_division_groups where user_group_id = $hdnDivisionID)) " : "");

        $sql .= (($hdnDivisionID and $hdnDivisionID != "ALL") ? " and associations.child_user_id in (select user_id from users_user_division_groups where user_group_id = $hdnDivisionID and (user_group_id in ($curr_divs_user) and user_group_id in ($curr_divs_site))) " : " and associations.child_user_id in (select user_id from users_user_division_groups where (user_group_id in ($curr_divs_user) and user_group_id in ($curr_divs_site)))");

        $sql .= " and associations.parent_user_id = $site_id  and users.name NOT LIKE '%generic%' and users.name NOT LIKE '%guard%' and users.user_status_id = 30 ";

        if ($notIncludeSelf) {
            $sql .= " and associations.child_user_id != " . $_SESSION['user_id'];
        }



//            echo $hdnDivisionID;
//         
//          echo $sql;
//          die;


        $result = $this->dbi->query($sql);
        $userList = array();
        if ($result) {
            $i = 0;
            while ($myrow = $result->fetch_assoc()) {
                $userList[$i] = $myrow;
                $i++;
            }
        }
        return $userList;
    }

    function notActiveUserList($days) {
        $sql = "select llog.attempt_date_time,TIMESTAMPDIFF(DAY,llog.attempt_date_time,NOW()) loginfrom,
            TIMESTAMPDIFF(DAY,users.created_at,NOW()) createdFrom,llog.id,users.ID userId
            from users
            inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = 107 
				AND users.user_status_id = 30
            LEFT JOIN log_login llog ON llog.is_successful = 1 AND llog.user_id = users.ID
            LEFT JOIN log_login llog2 ON llog2.is_successful = 1 AND llog2.user_id = llog.user_id and llog2.id > llog.id            
            where lookup_answers.lookup_field_id = 107 AND llog2.id IS NULL 
            AND (TIMESTAMPDIFF(DAY,llog.attempt_date_time,NOW()) > '" . $days . "'
    OR (llog.attempt_date_time IS NULL and TIMESTAMPDIFF(DAY,users.created_at,NOW()) > '" . $days . "'))
            GROUP BY users.id ORDER BY users.id";

        $result = $this->dbi->query($sql);

        if ($result) {
            while ($userRecord = $result->fetch_assoc()) {

                //prd($userRecord);]
                if ($userRecord['userId'] != 1 && $userRecord['userId'] != 5) {


                    if ($userRecord['loginfrom'] > $days) {
                        $sql = "update users SET users.cron_inactivate_date = NOW(),user_status_id = 40 WHERE users.id = " . $userRecord['userId'];
                        $updateResult = $this->dbi->query($sql);
                    } elseif (is_null($userRecord['loginfrom']) and $userRecord['createdFrom'] > $days) {
                        $sql = "update users SET users.cron_inactivate_date = NOW(),user_status_id = 40 WHERE users.id = " . $userRecord['userId'];
                        $updateResult = $this->dbi->query($sql);
                    }
                }
            }
            return "1";
        }
    }
    
    
    function notActiveArchiveUserList($days) {
        $sql = "select llog.attempt_date_time,TIMESTAMPDIFF(DAY,llog.attempt_date_time,NOW()) loginfrom,
            TIMESTAMPDIFF(DAY,users.created_at,NOW()) createdFrom,llog.id,users.ID userId
            from users
            inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = 107 
				AND users.user_status_id = 30
            LEFT JOIN log_login llog ON llog.is_successful = 1 AND llog.user_id = users.ID
            LEFT JOIN log_login llog2 ON llog2.is_successful = 1 AND llog2.user_id = llog.user_id and llog2.id > llog.id            
            where lookup_answers.lookup_field_id = 107 AND llog2.id IS NULL 
            AND (TIMESTAMPDIFF(DAY,llog.attempt_date_time,NOW()) > '" . $days . "'
    OR (llog.attempt_date_time IS NULL and TIMESTAMPDIFF(DAY,users.created_at,NOW()) > '" . $days . "'))
            GROUP BY users.id ORDER BY users.id";

        $result = $this->dbi->query($sql);

        if ($result) {
            while ($userRecord = $result->fetch_assoc()) {

                //prd($userRecord);]
                if ($userRecord['userId'] != 1 && $userRecord['userId'] != 5) {


                    if ($userRecord['loginfrom'] > $days) {
                        $sql = "update users SET users.cron_inactivate_date = NOW(),user_status_id = 40 WHERE users.id = " . $userRecord['userId'];
                        $updateResult = $this->dbi->query($sql);
                    } elseif (is_null($userRecord['loginfrom']) and $userRecord['createdFrom'] > $days) {
                        $sql = "update users SET users.cron_inactivate_date = NOW(),user_status_id = 40 WHERE users.id = " . $userRecord['userId'];
                        $updateResult = $this->dbi->query($sql);
                    }
                }
            }
            return "1";
        }
    }

    function inActivateUserByDay($day_from , $day_to) {
        $sql = "SELECT
            u.id AS userId,
            u.username,
            u.email,
            u.currrently_active,
            u.user_status_id,
            MAX(llog.attempt_date_time) AS lastLogin,
            TIMESTAMPDIFF(DAY, COALESCE(MAX(llog.attempt_date_time), '2024-01-01'), NOW()) AS daysSinceLastLogin,
            TIMESTAMPDIFF(DAY, u.created_at, NOW()) AS createdFrom
            FROM users u
            INNER JOIN lookup_answers la ON la.foreign_id = u.id AND la.lookup_field_id = 107
            LEFT JOIN log_login llog ON llog.user_id = u.id AND llog.is_successful = 1
            WHERE u.user_status_id = 30
            GROUP BY u.id
            -- HAVING daysSinceLastLogin < 28
            HAVING daysSinceLastLogin > $day_from AND daysSinceLastLogin <= $day_to
            ORDER BY u.id
        ";

        $result = $this->dbi->query($sql);

        

        if ($result) {
            $userRecord = $result->fetch_assoc();
            while ($userRecord = $result->fetch_assoc()) {
                $data[] = $userRecord;

                if ($userRecord['userId'] != 1 && $userRecord['userId'] != 5) {

                    // $sql = "update users SET users.cron_inactivate_date = NOW(),user_status_id = 40 WHERE users.id = " . $userRecord['userId'];
                    // $updateResult = $this->dbi->query($sql);
                }
            }
        }

        prd($data);
    }

    function archiveUserByDay($day_from) {
        $sql = "SELECT
            u.id AS userId,
            u.username,
            u.email,
            u.currrently_active,
            u.user_status_id,
            MAX(llog.attempt_date_time) AS lastLogin,
            TIMESTAMPDIFF(DAY, COALESCE(MAX(llog.attempt_date_time), '2024-01-01'), NOW()) AS daysSinceLastLogin,
            TIMESTAMPDIFF(DAY, u.created_at, NOW()) AS createdFrom
            FROM users u
            INNER JOIN lookup_answers la ON la.foreign_id = u.id AND la.lookup_field_id = 107
            LEFT JOIN log_login llog ON llog.user_id = u.id AND llog.is_successful = 1
            WHERE u.user_status_id = 30
            GROUP BY u.id
            -- HAVING daysSinceLastLogin < 28
            HAVING daysSinceLastLogin > $day_from
            ORDER BY u.id
        ";

        $result = $this->dbi->query($sql);

        

        if ($result) {
            $userRecord = $result->fetch_assoc();
            while ($userRecord = $result->fetch_assoc()) {
                $data[] = $userRecord;

                if ($userRecord['userId'] != 1 && $userRecord['userId'] != 5) {

                    // $sql = "update users SET users.cron_inactivate_date = NOW(),user_status_id = 40 WHERE users.id = " . $userRecord['userId'];
                    // $updateResult = $this->dbi->query($sql);
                }
            }
        }

        prd($data);
    }

    function divisionCommentUserList($table, $site_id, $hdnDivisionID, $notIncludeSelf = 1) {
//        $sql = "select users.id as `user_id`, users.name, users.surname, users.email, users.email2 as `email2`, users.preferred_name as `email3`, users2.name as `site` 
//          from associations
//          left join users on users.id = associations.child_user_id
//          left join users2 on users2.id = associations.parent_user_id
//          where associations.association_type_id in (4,12)";
//          
//          $curr_divs_user = $this->get_divisions($_SESSION['user_id'], 0, 1);
//          $curr_divs_site = $this->get_divisions($site_id, 0, 1);
//          
//          //$sql .= ($hdnDivisionID ? " and associations.child_user_id in (select user_id from users_user_division_groups where user_group_id = $hdnDivisionID)) " : "");
//              
//          $sql .= ($hdnDivisionID ? " and associations.child_user_id in (select user_id from users_user_division_groups where user_group_id = $hdnDivisionID) and (user_group_id in ($curr_divs_user) and user_group_id in ($curr_divs_site))) " : " and associations.child_user_id in (select user_id from users_user_division_groups where (user_group_id in ($curr_divs_user) and user_group_id in ($curr_divs_site)))");
//          
//          $sql .= " and associations.parent_user_id = $site_id  and users.name NOT LIKE '%generic%' and users.name NOT LIKE '%guard%' and users.user_status_id = 30
//          and associations.child_user_id != " . $_SESSION['user_id'];

        $sql = "select distinct(users.id) as `user_id`, users.name, users.surname, users.email, users.email2 as `email2`, users.preferred_name as `email3`, users2.name as `site` 
          from associations
          left join users on users.id = associations.child_user_id
          left join users2 on users2.id = associations.parent_user_id
          left join $table on $table" . ".user_id = users.id and $table" . ".site_id = users2.id
          where associations.association_type_id in (4,12) and $table" . ".user_id IS NOT NULL";

        $curr_divs_user = $this->get_divisions($_SESSION['user_id'], 0, 1);
        $curr_divs_site = $this->get_divisions($site_id, 0, 1);

        //$sql .= ($hdnDivisionID ? " and associations.child_user_id in (select user_id from users_user_division_groups where user_group_id = $hdnDivisionID)) " : "");

        $sql .= (($hdnDivisionID and $hdnDivisionID != "ALL") ? " and associations.child_user_id in (select user_id from users_user_division_groups where user_group_id = $hdnDivisionID and (user_group_id in ($curr_divs_user) and user_group_id in ($curr_divs_site))) " : " and associations.child_user_id in (select user_id from users_user_division_groups where (user_group_id in ($curr_divs_user) and user_group_id in ($curr_divs_site)))");

        $sql .= " and associations.parent_user_id = $site_id  and users.name NOT LIKE '%generic%' and users.name NOT LIKE '%guard%' and users.user_status_id = 30 ";

        if ($notIncludeSelf) {
            $sql .= " and associations.child_user_id != " . $_SESSION['user_id'];
        }



//            echo $hdnDivisionID;
//      
        if ($_SESSION['user_id'] == '11203') {
            // echo $sql;
            //die;
        }


        $result = $this->dbi->query($sql);
        $userList = array();
        if ($result) {
            $i = 0;
            while ($myrow = $result->fetch_assoc()) {
                $userList[$i] = $myrow;
                $i++;
            }
        }
        return $userList;
    }

    /*
     * input State Id
     * return location depend of state id
     */

    function getLocationOfState($state_id) {

//       $sql = "SELECT distinct(users.id) as `id`, 
//           CONCAT(users.name, ' ', users.surname, 
//           if(users.employee_id != '', CONCAT(' (', users.employee_id, ')'), if(users.client_id != '', CONCAT(' (', users.client_id, ')'), ''))) as `item_name` 
//FROM users 
//inner join lookup_answers on lookup_answers.foreign_id = users.id and (lookup_answers.lookup_field_id = 384 )and lookup_answers.table_assoc = 'users'
//
//where users.user_status_id = 30 AND users.state = '".$state_id."' order by users.name, users.surname";  


        $sql = "SELECT distinct(users.id) as `id`, 
CONCAT(users.name, ' ', users.surname, if(users.employee_id != '', CONCAT(' (', users.employee_id, ')'), 
if(users.client_id != '', CONCAT(' (', users.client_id, ')'), '')))
 as `item_name`,(
 SELECT GROUP_CONCAT(usersp.name) FROM users usersp LEFT JOIN  associations ass1 ON ass1.parent_user_id = usersp.ID
 WHERE ass1.child_user_id IS NOT NULL and ass1.child_user_id = users.id AND ass1.association_type_id = 1) AS client_name 
FROM users 
inner join lookup_answers on lookup_answers.foreign_id = users.id and (lookup_answers.lookup_field_id = 384 )and lookup_answers.table_assoc = 'users'
where users.user_status_id = 30 AND users.state = '" . $state_id . "' order by users.name, users.surname";

        $locationList = array();
        if ($result = $this->dbi->query($sql)) {
            while ($myrow = $result->fetch_assoc()) {
                $locationList[] = $myrow;
            }
        }

        return $locationList;
    }

    function getUserSiteIdsByUserId($user_id) {
        $userTypeId = $this->userDataAccessType($user_id);

        $parentSites = "all";

        if ($userTypeId == 2) {
            $assignedStateData = $this->getAssignedStateIds($user_id);
            if (is_array($assignedStateData)) {
                $stateAssignedDataArray = array_column($assignedStateData, 'id');
            } else {
                $stateAssignedDataArray = array();
            }

            $stateAssignedDataStr = implode(',', $stateAssignedDataArray);

            $parentSites = $this->getUserSiteIdsByStates($stateAssignedDataStr, $user_id);
        } else if ($userTypeId == 3) {

            $parentSites = 0;
            //$stateList = $this->getStateIds();
            $parentSites = $this->getUserSiteIds($user_id);
//                $assignedSiteArray = array();
//                if($parentSites != "") {
//                   //$assignedSiteArray = explode(',', $parentSites);                }
//                    $where_cond .= ' and users2.id in ('.$parentSites.') ';  
//                }else{
//                     $where_cond .= ' and users2.id in ('.$parentSites.') ';
//                }
//                
//                $userLocationPermission= "";
//                
//                $userLocationPermission = ' AND IF (sla2.table_assoc = \'users\' and sla2.lookup_field_id IN (384), users.id IN ('.$parentSites.'),1)';
//               $userLocationPermission .= " AND IF(sla3.table_assoc = 'users' and sla3.lookup_field_id IN (107),
//IF(users.user_level_id >= 600,0,(select group_concat(associations11.parent_user_id) as result from users as user11 
//left join associations associations11 on associations11.parent_user_id = user11.id 
//  where associations11.association_type_id = (select id from association_types association_types11 where name = 'site_staff') and associations11.child_user_id = users.id 
//  AND user11.id  IN (".$parentSites."))),1)";                
        }



        return $parentSites;
    }

    function rosStaffDetail($rosStaffId) {
        $rosQuery = "select id,green_called,green_called_who,ros_comment_internal,roster_send_message "
                . " from roster_times_staff where id = '" . $rosStaffId . "'";
        //echo $rosQuery;
        $result = $this->dbi->query($rosQuery);
        $rosStaffRec = $result->fetch_assoc();
        //pr("m".$rosStaffRec);
        //prd("okg");

        return $rosStaffRec;
    }

    function checkNotAllowedRoster($rid) {


        $query = "select site_id as result from rosters where id = " . $rid;

        $siteid = $this->get_sql_result($query);

        $allowedSites = $this->getUserSiteIdsByUserId($_SESSION['user_id']);

        if ($allowedSites == "all") {
            return 0;
        } else {

            if ($allowedSites && $siteid) {
                $allowedSitesArray = explode(',', $allowedSites);
                if (in_array($siteid, $allowedSitesArray)) {
                    return 0;
                } else {
                    return 1;
                }
            } else {
                return 1;
            }
        }

        //$rosterSiteId = 
    }

    function getUserSiteIdsByStates($stateids, $userIds) {
        $locationIds = $this->getAllSites();

        $sql = "select group_concat(users.id) as result from users
            left join states locationState ON locationState.id = users.state
            where users.id in (" . $locationIds . ") and locationState.id in (" . $stateids . ")";

        $parentSites = $this->get_sql_result($sql);

        return $parentSites;
    }

    function getAllSites() {


        $sql = "SELECT group_concat(distinct(users.id)) as result
FROM users 
inner join lookup_answers on lookup_answers.foreign_id = users.id and (lookup_answers.lookup_field_id = 384 )and lookup_answers.table_assoc = 'users'
where users.user_status_id = 30 order by users.name, users.surname";

        $parentSites = $this->get_sql_result($sql);
        return $parentSites;
    }

    function getUserSiteIds($userId) {
        $sql = "select group_concat(associations.parent_user_id) as result from users
                        left join associations on associations.parent_user_id = users.id 
                        where associations.association_type_id = (select id from association_types where name = '" . ($this->f3->get('is_client_staff') ? "site_client_staff" : "site_staff") . "') and associations.child_user_id = " . $userId;

        $parentSites = $this->get_sql_result($sql);
        return $parentSites;
        //prd($parentSites);
    }

    function getUserAccessOfSiteIds($siteIds) {
        $sql = "select group_concat(associations.parent_user_id) as result from users
                        left join associations on associations.parent_user_id = users.id 
                        where associations.association_type_id = (select id from association_types where name = '" . ($this->f3->get('is_client_staff') ? "site_client_staff" : "site_staff") . "') and associations.child_user_id = " . $userId;

        $userIds = $this->get_sql_result($sql);
        return $userIds;
    }

    function get_by_id($table, $field, $id) {
        $sql = "select $field from $table where id = $id";
        if ($result = $this->dbi->query($sql)) {
            if ($myrow = $result->fetch_assoc()) {
                return $myrow["$field"];
            }
        }
    }

    function process_phone($phone) {
        $phone = trim(preg_replace("/[^0-9 ]/", "", $phone));  //Removing all of the characters except numbers and spaces
        if (strlen($phone) == 10) {
            $left2 = substr($phone, 0, 2);
            if ($left2 == "04" || $left2 == "13") {
                $phone = substr($phone, 0, 4) . " " . substr($phone, 4, 3) . " " . substr($phone, 7, 3);
            } else {
                $phone = substr($phone, 0, 2) . " " . substr($phone, 2, 4) . " " . substr($phone, 6, 4);
            }
        }
        return $phone;
    }

    function get_sql_result($sql) {
        if ($result = $this->dbi->query($sql)) {
            if ($myrow = $result->fetch_assoc()) {
                return $myrow["result"];
            }
        }
        return 0;
    }

    function emp_id_to_staff_id($emp_id) {
        if ($result = $this->dbi->query("select id from users where employee_id = '$emp_id'")) {
            if ($myrow = $result->fetch_assoc()) {
                return $myrow["id"];
            }
        }
    }

    function get_divisions($user_id, $longname = 0, $ids = 0) {
        $sql = "select companies.id, companies.item_name from users_user_division_groups inner join companies on companies.id = users_user_division_groups .user_group_id where users_user_division_groups.user_id = $user_id";
//        echo $sql;
//        die;
        $str = "";
        if ($result = $this->dbi->query($sql)) {
            while ($myrow = $result->fetch_assoc()) {
                $item_name = ($longname ? $myrow['item_name'] : ($ids ? $myrow['id'] : substr($myrow['item_name'], 0, 3)));
                $str .= ($str ? ", " : "") . $item_name;
            }
        }
        return $str;
    }

    function get_divisionsIds($user_id, $longname = 0, $ids = 0) {
        $sql = "select companies.id, companies.item_name from users_user_division_groups inner join companies on companies.id = users_user_division_groups .user_group_id where users_user_division_groups .user_id = $user_id";

        $str = "";
        if ($result = $this->dbi->query($sql)) {
            while ($myrow = $result->fetch_assoc()) {
                $item_name = ($longname ? $myrow['item_name'] : ($ids ? $myrow['id'] : substr($myrow['item_name'], 0, 3)));
                $str .= ($str ? ", " : "") . $item_name;
            }
        }
        return $str;
    }

    function division_nav($division_id, $page, $shorten = 0, $lookup_id = 0, $hide_all = 0, $url_xtra = '') {
//    if($division_id == 'ALL') $division_id = 0;
        $division_str = "
    <style>
    .ASM { color: #009DE0; }
    .AFM { color: #64B446; }
    .ACM { color: #E9C229; }
    .ATM { color: #F18D2B; }
    .APM { color: #DF3330; }

    .ASM_selected { background-color: #009DE0; color: white; }
    .AFM_selected { background-color: #64B446; color: white; }
    .ACM_selected { background-color: #E9C229; color: white; }
    .ATM_selected { background-color: #F18D2B; color: white; }
    .APM_selected { background-color: #DF3330; color: white; }

    .ASM:hover { background-color: #009DE0; color: white; }
    .AFM:hover { background-color: #64B446; color: white; }
    .ACM:hover { background-color: #E9C229; color: white; }
    .ATM:hover { background-color: #F18D2B; color: white; }
    .APM:hover { background-color: #DF3330; color: white; }
    </style>
    ";
        $show_min = (isset($_GET['show_min']) ? $_GET['show_min'] : null);
        $curr_divs = $this->get_divisions($_SESSION['user_id'], 0, 1);

        if ($curr_divs && (strrpos($curr_divs, ',') !== false || strlen($curr_divs) > 0)) {

            $sql = ($hide_all ? "" : "select 'ALL' as id, 'ALL' as item_name UNION ") . "select id, item_name from companies where id in ($curr_divs)";

            if ($result = $this->dbi->query($sql)) {
                $division_str .= '<div class="divisionlink">';
                while ($myrow = $result->fetch_assoc()) {
                    $division_name = $myrow['item_name'];
                    $div_idin = $myrow['id'];
                    $title_str = ($shorten ? "uk-tooltip=\"title: $division_name\"" : "");
                    $short_version = strtoupper(substr($division_name, 0, 3));
                    if ($shorten)
                        $division_name = $short_version;

                    //if(!$division_id) $division_id = $div_idin;
                    if ($div_idin == $division_id)
                        $division_show = $division_name;

                    $division_str .= '<a ' . $title_str . ' class="division_nav_item ' . ($div_idin == $division_id ? ($division_id == 'ALL' ? "division_nav_selected" : "{$short_version}_selected") : "") . " $short_version" . '" href="' . $this->f3->get('main_folder') . $page . '?division_id=' . $div_idin . ($lookup_id ? "&lookup_id=$lookup_id" : "") . ($show_min ? "&show_min=1" : "") . $url_xtra . '">' . $division_name . '</a>';
                }
                $division_str .= '</div>';
            }
        }
        return $division_str;
    }

    function division_site_user($site_id, $division_id) {
//    if($division_id == 'ALL') $division_id = 0;       
        $division_str = array();
        $show_min = (isset($_GET['show_min']) ? $_GET['show_min'] : null);
        $curr_divs_user = $this->get_divisions($_SESSION['user_id'], 0, 1);
        $curr_divs_site = $this->get_divisions($site_id, 0, 1);
        // prd($curr_divs_user);
        $division_str = array();
        if (strrpos($curr_divs_user, ',') !== false || $curr_divs_user) {
            $sql = ($hide_all ? "" : "select 'ALL' as id, 'ALL' as item_name UNION ") . "select id, item_name from companies where id in ($curr_divs_user) and id in ($curr_divs_site)";

            if ($result = $this->dbi->query($sql)) {

                while ($myrow = $result->fetch_assoc()) {
                    $totRow = mysqli_num_rows($result);

                    $division_name = $myrow['item_name'];
                    $div_idin = $myrow['id'];

                    $division_str[] = $div_idin;
                }
            }
        }
        return $division_str;
    }

    function division_nav_site_user($site_id, $division_id, $page, $shorten = 0, $lookup_id = 0, $hide_all = 0, $url_xtra = '') {
//    if($division_id == 'ALL') $division_id = 0;
        $division_str = "
    <style>
    .ASM { color: #009DE0; }
    .AFM { color: #64B446; }
    .ACM { color: #E9C229; }
    .ATM { color: #F18D2B; }
    .APM { color: #DF3330; }

    .ASM_selected { background-color: #009DE0; color: white; }
    .AFM_selected { background-color: #64B446; color: white; }
    .ACM_selected { background-color: #E9C229; color: white; }
    .ATM_selected { background-color: #F18D2B; color: white; }
    .APM_selected { background-color: #DF3330; color: white; }

    .ASM:hover { background-color: #009DE0; color: white; }
    .AFM:hover { background-color: #64B446; color: white; }
    .ACM:hover { background-color: #E9C229; color: white; }
    .ATM:hover { background-color: #F18D2B; color: white; }
    .APM:hover { background-color: #DF3330; color: white; }
    </style>
    ";
        $show_min = (isset($_GET['show_min']) ? $_GET['show_min'] : null);
        $curr_divs_user = $this->get_divisions($_SESSION['user_id'], 0, 1);
        $curr_divs_site = $this->get_divisions($site_id, 0, 1);
        // prd($curr_divs_user);
        if (strrpos($curr_divs_user, ',') !== false || $curr_divs_user) {
            $sql = ($hide_all ? "" : "select 'ALL' as id, 'ALL' as item_name UNION ") . "select id, item_name from companies where id in ($curr_divs_user) and id in ($curr_divs_site)";

            if ($result = $this->dbi->query($sql)) {
                $division_str .= '<div class="divisionlink">';
                while ($myrow = $result->fetch_assoc()) {
                    $totRow = mysqli_num_rows($result);

                    $division_name = $myrow['item_name'];
                    $div_idin = $myrow['id'];
                    $title_str = ($shorten ? "uk-tooltip=\"title: $division_name\"" : "");
                    $short_version = strtoupper(substr($division_name, 0, 3));
                    if ($shorten)
                        $division_name = $short_version;

                    //if(!$division_id) $division_id = $div_idin;
                    if ($div_idin == $division_id)
                        $division_show = $division_name;

                    if ($totRow == 2 && $div_idin == 'ALL') {
                        
                    } else {
                        $division_str .= '<a ' . $title_str . ' class="division_nav_item ' . ($div_idin == $division_id ? ($division_id == 'ALL' ? "division_nav_selected" : "{$short_version}_selected") : "") . " $short_version" . '" href="' . $this->f3->get('main_folder') . $page . '?division_id=' . $div_idin . ($lookup_id ? "&lookup_id=$lookup_id" : "") . ($show_min ? "&show_min=1" : "") . $url_xtra . '">' . $division_name . '</a>';
                    }
                }
                $division_str .= '</div>';
            }
        }
        return $division_str;
    }

    function num_results($sql) {
        if ($result = $this->dbi->query($sql)) {
            $r = ($result->num_rows ? $result->num_rows : 0);
        } else {
            $r = 0;
        }
        return $r;
    }

    function pluralise($count) {
        return ($count == 1 ? "" : "s");
    }

    function js_wrap($str) {
        return '<script type="text/javascript">' . $str . '</script>';
    }

    function css_wrap($str) {
        return '<style>' . $str . '</style>';
    }

    //random 4 digit number not divisible by 100
    //Also, no 3 digits the same
    function get_4dig_random() {
        $nums = array();
        while (!$finished) {
            $finished = 0;
            $rnd = mt_rand(1012, 9988);
            if ($rnd % 100) {
                $finished = 1;
                for ($x = 0; $x < 10; $x++) {
                    $nums[$x] = 0;
                }
                $str = (string) $rnd;
                if (strpos($str, "0") === false) {
                    if (strpos($str, "123") === false && strpos($str, "321") === false && strpos($str, "234") === false && strpos($str, "345") === false && strpos($str, "456") === false && strpos($str, "567") === false && strpos($str, "678") === false && strpos($str, "789") === false) {
                        for ($x = 0; $x < strlen($str); $x++) {
                            $ch = substr($str, $x, 1);
                            $nums[$ch] += 1;
                        }
                        for ($x = 0; $x < 10; $x++) {
                            if ($nums[$x] > 2)
                                $finished = 0;
                        }
                    } else {
                        $finished = 0;
                    }
                } else {
                    $finished = 0;
                }
            }
        }
        return $rnd;
    }

    function pdf_header($pdf, $heading) {
        $pdf->AddPage();
        $pdf->SetFont('Arial', '', 11);
        $pdf->Image($this->f3->get('base_img_folder') . "logo-print.png", 10, 9, 55);
        $col = $this->hex2RGB("#CCCCCC");
        $pdf->SetDrawColor($col[0], $col[1], $col[2]);
        $pdf->SetFont('Arial', '', 11);
        $pdf->SetXY(72, 7);  //$pdf->Cell(110,6,$heading);
        $pdf->MultiCell(110, 6, $heading, 0);
        $pdf->SetTextColor(30, 30, 30);
    }

    function pdf_footer($pdf) {
        $pdf->SetFont('Arial', '', 8.2);
        $pdf->SetXY(10, 288);
        $pdf->MultiCell(190, 6, "info@alliedmanagement.com.au | 1300 003 456 | M/L NSW: 000101885 ACT: 17502515 WA: CA57168, Sa57168 QLD: 4184168 VIC: 94076950S", 0);
        $pdf->SetFont('Arial', '', 11);
    }

    //This function creates a textarea for displaying SQL queries etc.
    function ta($str) {
        return '
    <textarea id="testtxt" style="height: 300px; width:90%;" readonly="readonly">' . $str . '</textarea>
    <script>
    var el = document.getElementById("testtxt")
    el.focus();
    el.select()
    el.setSelectionRange(0, 99999);
    document.execCommand("copy");
    </script>
    ';
    }

    function br2nl($str) {
        return preg_replace('/<br(\s+)?\/?>/i', "\r\n", $str);
    }

    /* right menu management */

    function assignAllRoleToSuperAdmin() {

        $superAdmin = 115;
        $sql = "SELECT laf.foreign_id FROM           
(select foreign_id 
from lookup_answers  la
where table_assoc = 'main_pages2' 
GROUP BY la.foreign_id) laf
LEFT JOIN 
(select foreign_id 
from lookup_answers  la
where table_assoc = 'main_pages2' AND la.lookup_field_id = " . $superAdmin . ") las ON las.foreign_id = laf.foreign_id 
WHERE las.foreign_id IS NULL";

        if ($result = $this->dbi->query($sql)) {
            while ($myrow = $result->fetch_assoc()) {

                //print_r($myrow);
                $foreign_id = $myrow['foreign_id'];

                $fieldH = array('foreign_id', 'lookup_field_id', 'table_assoc');
                $valueA = array($foreign_id, $superAdmin, '"main_pages2"');
                $fieldHS = implode(',', $fieldH);
                $valueAS = implode(',', $valueA);
                $sql = "insert into lookup_answers ($fieldHS) "
                        . "values ($valueAS)";

                $this->dbi->query($sql);
//       print_r($this->dbi->error);
//       die;
                // $last_id = $this->dbi->insert_id;
            }
        }
    }

    function assignAllRoleToSuperAdminByPages() {

        $superAdmin = 115;
        $sql = "SELECT * FROM main_pages2 mp2 order by mp2.ID asc";

        if ($result = $this->dbi->query($sql)) {
            while ($myrow = $result->fetch_assoc()) {

                // print_r($myrow);
                $foreign_id = $myrow['ID'];

                $fieldH = array('foreign_id', 'lookup_field_id', 'table_assoc');
                $valueA = array($foreign_id, $superAdmin, '"main_pages2"');
                $fieldHS = implode(',', $fieldH);
                $valueAS = implode(',', $valueA);
                $sql = "insert ignore into lookup_answers ($fieldHS) "
                        . "values ($valueAS)";

//                echo $sql;
//                die;
                $this->dbi->query($sql);
//       print_r($this->dbi->error);
//       die;
                // $last_id = $this->dbi->insert_id;
            }
        }
    }

    function hiddenMainMenuWithSubMenu() {

        $mainsql = "select mp.id,mp.title,mp.url,mp.parent_id,mp.sort_order,mp.page_type_id,la.id laid
from lookup_answers la
LEFT JOIN main_pages2 mp ON mp.ID = la.foreign_id
where la.table_assoc = 'main_pages2'  AND mp.parent_id = 0
GROUP BY foreign_id
ORDER BY sort_order ASC 
";
        if ($mainresult = $this->dbi->query($mainsql)) {
            while ($mainMenuRow = $mainresult->fetch_assoc()) {
                //print_r($mainMenuRow);
                echo "</br></br>-----------------------------Main Menu  ------------------------</br></br>";
                echo $mainMenuRow['id'] . " -- " . $mainMenuRow['title'] . " -- " . $mainMenuRow['url'] . " -- " . $mainMenuRow['page_type_id'] . "</br>";
                /*   sub Menu */
                $subMenusql = "select mp.id,mp.title,mp.url,mp.parent_id,mp.sort_order,mp.page_type_id,la.id laid 
from lookup_answers la
LEFT JOIN main_pages2 mp ON mp.ID = la.foreign_id
where la.table_assoc = 'main_pages2'  AND mp.parent_id = '" . $mainMenuRow['id'] . "' 
GROUP BY foreign_id
ORDER BY sort_order ASC";

                if ($subresult = $this->dbi->query($subMenusql)) {
                    if ($subresult->num_rows > 0) {
                        //echo $subresult->num_rows;
//                            if($mainMenuRow['id'] == "149" && $subresult->num_rows > 0){
//                            echo "mmm".$subresult->num_rows;
//                            die;
//                            }
                        echo "</br></br>-----------------------------sub Menu Start ------------------------</br></br>";
                        while ($subMenuRow = $subresult->fetch_assoc()) {

                            echo " ---------  " . $subMenuRow['id'] . " -- " . $subMenuRow['title'] . " -- " . $subMenuRow['url'] . " -- " . $subMenuRow['page_type_id'] . "</br>";
                            /*   sub Menu */

                            $subSubMenusql = "select mp.id,mp.title,mp.url,mp.parent_id,mp.sort_order,mp.page_type_id,la.id laid 
from lookup_answers la
LEFT JOIN main_pages2 mp ON mp.ID = la.foreign_id
where la.table_assoc = 'main_pages2'  AND mp.parent_id = '" . $subMenuRow['id'] . "'
GROUP BY foreign_id
ORDER BY sort_order ASC";

                            if ($subSubresult = $this->dbi->query($subSubMenusql)) {
                                if ($subSubresult->num_rows > 0) {
                                    //echo $subresult->num_rows;
//                            if($mainMenuRow['id'] == "149" && $subresult->num_rows > 0){
//                            echo "mmm".$subresult->num_rows;
//                            die;
//                            }
                                    echo "</br></br>-----------level2------------------sub sub  Menu Start ------------------------</br></br>";
                                    while ($subSubMenuRow = $subSubresult->fetch_assoc()) {

                                        echo " -----------------------------------  " . $subSubMenuRow['id'] . " -- " . $subSubMenuRow['title'] . " -- " . $subSubMenuRow['url'] . " -- " . $subSubMenuRow['page_type_id'] . "</br>";
                                    }
                                    echo "</br></br>-------------level2----------------sub sub Menu Menu End ------------------------</br></br>";
                                }
                            }
                        }
                        echo "</br></br>-----------------------------sub Menu Menu End ------------------------</br></br>";
                    }
                }
            }
            echo "</br></br>-----------------------------Main Menu End ------------------------</br></br>";
            die;
        }
    }

    function mainMenuWithSubMenu() {

        $mainsql = "select mp.id,mp.title,mp.url,mp.parent_id,mp.sort_order,mp.page_type_id,la.id laid
from lookup_answers la
LEFT JOIN main_pages2 mp ON mp.ID = la.foreign_id
where la.table_assoc = 'main_pages2'  AND mp.parent_id = 0 AND (mp.page_type_id = 250 OR mp.page_type_id = 100)
GROUP BY foreign_id
ORDER BY sort_order ASC 
";
        if ($mainresult = $this->dbi->query($mainsql)) {
            while ($mainMenuRow = $mainresult->fetch_assoc()) {
                //print_r($mainMenuRow);
                echo "</br></br>-----------------------------Main Menu  ------------------------</br></br>";
                echo $mainMenuRow['id'] . " -- " . $mainMenuRow['title'] . "</br>";
                /*   sub Menu */
                $subMenusql = "select mp.id,mp.title,mp.url,mp.parent_id,mp.sort_order,mp.page_type_id,la.id laid 
from lookup_answers la
LEFT JOIN main_pages2 mp ON mp.ID = la.foreign_id
where la.table_assoc = 'main_pages2'  AND mp.parent_id = '" . $mainMenuRow['id'] . "' AND (mp.page_type_id = 250 OR mp.page_type_id = 100)
GROUP BY foreign_id
ORDER BY sort_order ASC";

                if ($subresult = $this->dbi->query($subMenusql)) {
                    if ($subresult->num_rows > 0) {
                        //echo $subresult->num_rows;
//                            if($mainMenuRow['id'] == "149" && $subresult->num_rows > 0){
//                            echo "mmm".$subresult->num_rows;
//                            die;
//                            }
                        echo "</br></br>-----------------------------sub Menu Start ------------------------</br></br>";
                        while ($subMenuRow = $subresult->fetch_assoc()) {

                            echo " ---------  " . $subMenuRow['id'] . " -- " . $subMenuRow['title'] . "</br>";

                            $subSubMenusql = "select mp.id,mp.title,mp.url,mp.parent_id,mp.sort_order,mp.page_type_id,la.id laid 
from lookup_answers la
LEFT JOIN main_pages2 mp ON mp.ID = la.foreign_id
where la.table_assoc = 'main_pages2'  AND mp.parent_id = '" . $subMenuRow['id'] . "' AND (mp.page_type_id = 250 OR mp.page_type_id = 100)
GROUP BY foreign_id
ORDER BY sort_order ASC";

                            if ($subSubresult = $this->dbi->query($subSubMenusql)) {
                                if ($subSubresult->num_rows > 0) {
                                    //echo $subresult->num_rows;
//                            if($mainMenuRow['id'] == "149" && $subresult->num_rows > 0){
//                            echo "mmm".$subresult->num_rows;
//                            die;
//                            }
                                    echo "</br></br>-----------level2------------------sub sub  Menu Start ------------------------</br></br>";
                                    while ($subSubMenuRow = $subSubresult->fetch_assoc()) {

                                        echo " -----------------------------------  " . $subSubMenuRow['id'] . " -- " . $subSubMenuRow['title'] . "</br>";
                                    }
                                    echo "</br></br>-------------level2----------------sub sub Menu Menu End ------------------------</br></br>";
                                }
                            }
                        }
                        echo "</br></br>-----------------------------sub Menu Menu End ------------------------</br></br>";
                    }
                }
            }
            echo "</br></br>-----------------------------Main Menu End ------------------------</br></br>";
            die;
        }
    }

    function parentMenuDetail($pid) {
        $mainsql = "select * from main_pages2 where id = '" . $pid . "'";
        if ($mainresult = $this->dbi->query($mainsql)) {
            if ($mainMenuRow = $mainresult->fetch_assoc()) {
                // print_r($mainMenuRow);
                return $mainMenuRow['parent_id'];
            }
        }
        return 0;
    }

    function assignPagesToRole($roleId, $pagesArray) {

        $delete_access = " delete from page_access where group_id = '" . $roleId . "';";
        $this->dbi->query($delete_access);
        $str_xtra_delete = " delete from lookup_answers where lookup_field_id = " . $roleId . " and table_assoc = 'main_pages2'";
        $this->dbi->query($str_xtra_delete);
        //die;
        $str_xtra = "";
        $ins_str_access = "";
        $delete_access = "";
        $ins_str = "";
        $vals = "";
        foreach ($pagesArray as $pageId) {
            $ins_str .= "($pageId, $roleId, 'main_pages2'),";
            if ($roleId == 103) {
                $ins_str_access .= "($pageId, $roleId, 1),";
            } else {
                $ins_str_access .= "($pageId, $roleId, 4),";
            }
        }

        $ins_str = substr($ins_str, 0, strlen($ins_str) - 1);
        $ins_str_access = substr($ins_str_access, 0, strlen($ins_str_access) - 1);

        $str_xtra = " insert ignore into lookup_answers (foreign_id, lookup_field_id, table_assoc) values $ins_str;";
        $this->dbi->query($str_xtra);

        if ($ins_str_access != "") {
            $str_xtra_access = " insert ignore into page_access (page_id, group_id, access_level) values $ins_str_access;";
            $this->dbi->query($str_xtra_access);
            //print_r($this->dbi->error);
        }
    }

    function getStateSelectHtml($user_id, $userDataAccessType, $stateDataArray, $state_id = 0) {
        $stateList = $this->getStateIds();
        $str = "";
        if ($userDataAccessType == 1) {
            $str .= "<form method=\"POST\" name=\"frmEdit\">";
            $selState = "<select name='userDataState' id ='userDataState'><option value=\"\"> Select State </option>";
            foreach ($stateList as $key => $svalue) {
                if ($state_id == $svalue['id']) {
                    $selected = "selected";
                } else {
                    $selected = "";
                }
                $selState .= '<option value="' . $svalue['id'] . '" ' . $selected . '>' . $svalue['item_name'] . '</option>';
            }
            $selState .= '</select>';

            $str .= $selState;
        } else if ($userDataAccessType == 2) {
            $str .= "<form method=\"POST\" name=\"frmEdit\">";
            $selState = "<select name='userDataState' id ='userDataState'><option value=\"\"> Select State </option>";
            foreach ($stateList as $key => $svalue) {
                if (in_array($svalue['id'], $stateDataArray)) {
                    if ($state_id == $svalue['id']) {
                        $selected = "selected";
                    } else {
                        $selected = "";
                    }

                    $selState .= '<option value="' . $svalue['id'] . '" ' . $selected . '>' . $svalue['item_name'] . '</option>';
                }
            }
            $selState .= '</select>';

            $str .= $selState;
        }
        return $str;
    }

    function getSiteStateSelectHtml($user_id, $userDataAccessType, $stateDataArray, $state_id = 0) {
        $stateList = $this->getSitesList();
        $str = "";
        if ($userDataAccessType == 1) {
            $str .= "<form method=\"POST\" name=\"frmEdit\">";
            $selState = "<select name='userDataState' id ='userDataState'><option value=\"\"> Select State </option>";
            foreach ($stateList as $key => $svalue) {
                if ($state_id == $svalue['id']) {
                    $selected = "selected";
                } else {
                    $selected = "";
                }
                $selState .= '<option value="' . $svalue['id'] . '" ' . $selected . '>' . $svalue['item_name'] . '</option>';
            }
            $selState .= '</select>';

            $str .= $selState;
        } else if ($userDataAccessType == 2) {
            $str .= "<form method=\"POST\" name=\"frmEdit\">";
            $selState = "<select name='userDataState' id ='userDataState'><option value=\"\"> Select State </option>";
            foreach ($stateList as $key => $svalue) {
                if (in_array($svalue['id'], $stateDataArray)) {
                    if ($state_id == $svalue['id']) {
                        $selected = "selected";
                    } else {
                        $selected = "";
                    }

                    $selState .= '<option value="' . $svalue['id'] . '" ' . $selected . '>' . $svalue['item_name'] . '</option>';
                }
            }
            $selState .= '</select>';

            $str .= $selState;
        }
        return $str;
    }

    function assignStates($user_id, $userDataStates) {
        $sql = "update users set main_site_id = 0 where id = " . $user_id;
        $result = $this->dbi->query($sql);
        if (is_array($userDataStates)) {
            $deleteQ = "delete from user_assign_states where user_id = '" . $user_id . "'";
            $this->dbi->query($deleteQ);
            foreach ($userDataStates as $key => $state) {
                $insertQ = 'insert into user_assign_states (user_id,state_id,added_by) values (' . $user_id . ',' . $state . ',' . $_SESSION["user_id"] . ')';

                $this->dbi->query($insertQ);
            }
        } else {
            $deleteQ = "delete from user_assign_states where user_id = '" . $user_id . "'";
            $this->dbi->query($deleteQ);
            if ($userDataStates) {
                $insertQ = 'insert into user_assign_states (user_id,state_id,added_by) values (' . $user_id . ',' . $userDataStates . ',' . $_SESSION["user_id"] . ')';

                $this->dbi->query($insertQ);
            }
        }
    }

    function removeLocation($user_id, $userLocations) {
        $sql = "update users set main_site_id = 0 where id = " . $user_id;

        $result = $this->dbi->query($sql);

        $deleteQ = "delete from associations where child_user_id = '" . $user_id . "'";

        $this->dbi->query($deleteQ);
    }

    function assignLocations($user_id, $userLocations) {

        // prd($userLocations);
        if (is_array($userLocations)) {
            $sql = "update users set main_site_id = 0 where id = " . $user_id;

            $result = $this->dbi->query($sql);
            $deleteQ = "delete from associations where child_user_id = '" . $user_id . "'";

            $this->dbi->query($deleteQ);
            foreach ($userLocations as $skey => $svalue) {

                foreach ($svalue as $lkey => $lvalue) {
                    if ($lkey == 0) {
                        $sqlm = "update users set main_site_id = '" . $lvalue . "' where id = " . $user_id;
                        $resultm = $this->dbi->query($sqlm);
                    }
                    $insertQ = 'insert into associations (association_type_id,parent_user_id,child_user_id,added_by_id) values (4,' . $lvalue . ',' . $user_id . ',' . $_SESSION["user_id"] . ')';
                    $this->dbi->query($insertQ);

                    // Get client_id using $lvalue
                    $clientIdQuery = "SELECT parent_user_id FROM associations WHERE child_user_id = '$lvalue' AND association_type_id = 1";
                    $clientIdResult = $this->dbi->query($clientIdQuery)->fetchAll(PDO::FETCH_ASSOC);
                    $clientId = $clientIdResult[0]['parent_user_id']; // Assuming fetchColumn returns the single value

                     if ($clientId) {
                        // Check for trainings associated with the client and site
                        $trainingQuery = "SELECT id as training_rule_id, start_date, end_date 
                                        FROM training_allied_rules 
                                        WHERE client_id = $clientId 
                                        AND (site_id = $lvalue OR site_id = 0)
                                        AND (end_date IS NULL OR end_date >= CURDATE())";
                        $trainings = $this->dbi->query($trainingQuery)->fetchAll(PDO::FETCH_ASSOC);

                        if (!empty($trainings)) {
                            $insertRec = [];
                            foreach ($trainings as $training) {
                                $training_rule_id = $training['training_rule_id'];
                                $start_date = $training['start_date'];
                                $end_date = $training['end_date'];

                                // Check if the user already has this training
                                $checkUserQuery = "SELECT COUNT(*) 
                                                FROM training_allied_training_user 
                                                WHERE training_rule_id = $training_rule_id 
                                                AND user_id = $user_id";
                                $userExists = $this->dbi->query($checkUserQuery)->fetchColumn();

                                if (!$userExists) {
                                    $insertRec[] = "('$training_rule_id', '0', '$user_id', '$start_date', '$end_date')";
                                }
                            }

                            // Insert records if there are new ones
                            if (!empty($insertRec)) {
                                $insertRecString = implode(',', $insertRec);
                                $insertQuery = "INSERT IGNORE INTO `training_allied_training_user` 
                                                (`training_rule_id`, `lesson_id`, `user_id`, `start_date`, `end_date`) 
                                                VALUES $insertRecString";
                                $this->dbi->exec($insertQuery);
                            }
                        }
                    }
                
                }
            }
        }
    }

    function isUserLocationType($userId) {

        $query = 'select users.id'
                . ' from users '
                . ' inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = "384"  and lookup_answers.table_assoc = "users"'
                . ' where lookup_answers.lookup_field_id = "384" and users.id = "' . $userId . '"';

        if ($mainresult = $this->dbi->query($query)) {
            if ($result = $mainresult->fetch_assoc()) {
                // print_r($mainMenuRow);
                return 1; //$result['id'];
            }
        }
        return 0;
    }

    function getRoleIdByName($name) {
        $name = trim($name);

        $query = "select id from lookup_fields where lookup_id = '21' and item_name = '" . $name . "' and value = 'ROLE'";

        //$mainresult = $this->dbi->query($query);

        if ($mainresult = $this->dbi->query($query)) {
            if ($mainRole = $mainresult->fetch_assoc()) {
                // print_r($mainMenuRow);
                return $mainRole['id'];
            }
        }
        return 0;
    }

    function trainingRuleRolesDetail($training_id) {


        $sql = "select tarr.* from training_allied_rules_roles tarr where training_rule_id = $training_id  order by id desc limit 1";
        $result = $this->dbi->query($sql);

        if ($tarrDetail = $result->fetch_assoc()) {
            //prd($tarDetail);
            return $tarrDetail;
        }
        return "0";
    }

    function assignPageToRoleWise() {

        // return true;
        if ($_SESSION['user_id'] == 1) {
            //die("test");

            $assignRolesArray = array('CEO', 'EXECUTIVE', 'DOO', 'STATE OPERATIONS MANAGER',
                'OPERATIONS MANAGER', 'BUSINESS SERVICES MANAGER', 'CLIENT SERVICES MANAGER', 'AREA SERVICES MANAGER',
                'COMPLIANCE', 'HR', 'ADMIN', 'FINANCE', 'BD', 'SITE MANAGER', 'EMPLOYEE', 'CLIENTS NATIONAL OPERATIONS', 'CLIENTS STATE MANAGER',
                'CLIENT SITE MANAGER', 'PROVIDER', 'VISITOR');
            // $roleAssignArray = array('CEOPages' => '2439', );
            $assignRoles = array('CEO', 'EXECUTIVE', 'DOO', 'STATE OPERATIONS MANAGER',
                'OPERATIONS MANAGER', 'BUSINESS SERVICES MANAGER', 'CLIENT SERVICES MANAGER', 'AREA SERVICES MANAGER',
                'COMPLIANCE', 'HR', 'ADMIN', 'FINANCE', 'BD', 'SITE MANAGER', 'EMPLOYEE', 'CLIENTS NATIONAL OPERATIONS', 'CLIENTS STATE MANAGER',
                'CLIENT SITE MANAGER', 'PROVIDER', 'VISITOR');

            foreach ($assignRoles as $type) {
                $role = $this->getRoleIdByName($type);
                //echo $type." ".$role."</br>";
                if ($type == "CEO") {
                    $totalPageArray = $this->CEOPages();
                } else if ($type == "EXECUTIVE") {
                    $totalPageArray = $this->EXECUTIVEPages();
                } else if ($type == "DOO") {
                    $totalPageArray = $this->DOOPages();
                } else if ($type == "STATE OPERATIONS MANAGER") {
                    $totalPageArray = $this->StateOperationManagerPages();
                } else if ($type == "OPERATIONS MANAGER") {
                    $totalPageArray = $this->OperationManagerPages();
                } else if ($type == "BUSINESS SERVICES MANAGER") {
                    $totalPageArray = $this->BussinessServiceManagerPages();
                } else if ($type == "CLIENT SERVICES MANAGER") {
                    $totalPageArray = $this->ClientServiceManagerPages();
                } else if ($type == "AREA SERVICES MANAGER") {
                    $totalPageArray = $this->AreaServiceManagerPages();
                } else if ($type == "COMPLIANCE") {
                    $totalPageArray = $this->CompliancePages();
                } else if ($type == "HR") {
                    $totalPageArray = $this->HrPages();
                } else if ($type == "ADMIN") {
                    $totalPageArray = $this->adminPages();
                } else if ($type == "FINANCE") {
                    $totalPageArray = $this->financePages();
                } else if ($type == "BD") {
                    $totalPageArray = $this->bdPages();
                } else if ($type == "SITE MANAGER") {
                    $totalPageArray = $this->SiteMangerPages();
                } else if ($type == "EMPLOYEE") {
                    $totalPageArray = $this->employeePages();
                } else if ($type == "CLIENTS NATIONAL OPERATIONS") {
                    $totalPageArray = $this->nationalOperationPages();
                } else if ($type == "CLIENTS STATE MANAGER") {
                    $totalPageArray = $this->stateManagerPages();
                } else if ($type == "CLIENT SITE MANAGER") {
                    $totalPageArray = $this->clientManagerPages();
                } else if ($type == "PROVIDER") {
                    $totalPageArray = $this->providerPages();
                } else if ($type == "VISITOR") {
                    $totalPageArray = $this->visitorPages();
                }

                if (!empty($totalPageArray) && $role != 0) {

                    $this->assignPagesToRole($role, $totalPageArray);
                }
            }
            // die;
        }
    }

    function CEOPages() {
        $pageRoot = array('52', 526, 382, 79, 419,
            422, 218, 421, 485, 449, 203, 512, 515, 414, 410, 420, 147, 201, 150, 448, 496, 337,
            '407', '374', '375', '455', '153', '161', '418', '508', 525, 443, 518, 525);

        /* admin sub menu */

        $signOnOffModule = array(407, 523, 524);

        $jobModule = array(519, 520, 521, 522);

        $adminSubMenu = array(149, 474, 150, 482, 478, 157, 486, 475, 477, 238, 459, 427, 472, 206, 175, 258, 354, 355, 431);

        $myPatrol = array(516, 453, 457);

        $trainingSubMenu = array(153, 91, 502, 501, 503, 530, 531, 533, 534, 541, 542, 549, 548, 550);

        $hrSubMenu = array(161, 476, 510, 517, 416, 240, 214, 450, 456, 451, 266, 275, 492, 493, 494, 495, 452, 13, 221, 352, 458,
            244, 194, 406, 320, 253, 224, 284, 556);

        

        $bdSubMenu = array(368, 391, 390, 529, 467, 468);

        $reportingMenu = array(527, 553, 369, 338, 357, 362, 337, 208, 182, 490, 488, 507, 202, 243, 377, 376, 178, 193, 245, 287, 460, 471, 463, 464, 466, 470, 527);

        $mettingsMenu = array(358, 348, 349, 347);

        $operations = array(168, 313, 424, 499, 392, 491, 169, 288, 394, 504, 498, 469);

        $rostering = array(551, 552, 528, 170, 409, 411, 425, 396, 400, 423, 402, 403, 400, 423, 402, 403, 412, 466, 433, 305, 434, 435, 500, 528);

        $timeSheet = array(437, 438, 439, 440, 441);
        $myEdge = array(47, 404, 393, 399, 246, 283, 215, 158, 306, 317, 239, 353, 367, 318, 290);

        $alliedTraining = array(530, 531, 532, 533, 534, 535, 536, 537, 538, 539, 540, 541, 545);

        $myAlliedTraining = array(542, 543, 544.549);

        $extraPages = array(158);


        $totalPageArray = array_merge($pageRoot, $jobModule, $signOnOffModule, $adminSubMenu, $myPatrol, $trainingSubMenu, $hrSubMenu, $bdSubMenu, $reportingMenu, $mettingsMenu, $operations,
                $rostering, $timeSheet, $myEdge, $alliedTraining, $myAlliedTraining, $extraPages);

            
                return $totalPageArray;


    }

    function EXECUTIVEPages() {
        $pageRoot = array('52', 526, 382, 79, 419,
            422, 218, 421, 485, 449, 203, 512, 515, 414, 410, 420, 147, 201, 150, 448, 496, 337, '407', '374', '375', '455', '153', '161',
            '418', '508', 443);

        /* admin sub menu */
        $signOnOffModule = array(407, 523, 524);
      

        $jobModule = array(519, 520, 521, 522);

        $adminSubMenu = array(149, 238, 459, 472, 206, 175, 258, 354, 355, 431);

        $myPatrol = array(516, 453, 457);

        $trainingSubMenu = array(153, 91, 502, 501, 503);

        $hrSubMenu = array(161, 476, 510, 416, 240, 214, 450, 456, 451, 266, 275, 492, 493, 494, 495, 452, 13, 221, 352, 458,
            244, 194, 406, 320, 253, 224, 284);

        

        $bdSubMenu = array(368, 391, 390, 529, 467, 529, 468);

        $reportingMenu = array(527, 553, 369, 338, 357, 362, 337, 182, 208, 490, 488, 507, 202, 243, 377, 376, 178, 193, 245, 287, 460, 471, 463, 464, 466, 470, 527);

        $mettingsMenu = array(358, 348, 349, 347);

        $operations = array(168, 313, 424, 499, 392, 491, 169, 288, 394, 504, 498, 469);

        $rostering = array(551, 552, 528, 170, 409, 411, 425, 396, 400, 423, 402, 403, 400, 423, 402, 403, 412, 466, 433, 305, 434, 435, 500);

        $timeSheet = array(437, 438, 439, 440, 441);
        $myEdge = array(47, 404, 393, 399, 246, 283, 215, 158, 306, 317, 239, 353, 367, 318, 290);

        $extraPages = array(158);

        $myAlliedTraining = array(542, 543, 544.549);

        $totalPageArray = array_merge($pageRoot, $jobModule, $signOnOffModule, $adminSubMenu, $myPatrol, $trainingSubMenu, $hrSubMenu, $bdSubMenu, $reportingMenu, $mettingsMenu, $operations,
                $rostering, $timeSheet, $myEdge, $extraPages, $myAlliedTraining);

        return $totalPageArray;
    }

    function DOOPages() {
        $pageRoot = array('52', 526, '407', '374', '375', '455', '161',
            '418', '508', 443, 515, 527);

        $signOnOffModule = array(407, 523, 524);
        $jobModule = array(519, 520, 521, 522);

        /* admin sub menu */

        $adminSubMenu = array(149, 238, 427, 472, 354, 431);

        //$adminSubMenu = array();

        $myPatrol = array(516, 453, 457);

        $trainingSubMenu = array();

        $hrSubMenu = array(161, 476, 510, 416, 240, 214, 450, 456, 451, 266, 275, 492, 493, 494, 495, 458,
            244, 194, 406, 320, 253, 224, 284);
        
        

        $bdSubMenu = array(368, 391, 390, 529, 467, 529, 468);

        $reportingMenu = array(527, 553, 369, 338, 357, 362, 337, 182, 208, 490, 488, 507, 202, 243, 377, 376, 178, 193, 245, 287, 460, 471, 463, 464, 466, 470, 527);

        $mettingsMenu = array(358, 348, 349, 347);

        $operations = array(168, 313, 424, 499, 496, 392, 491, 169, 288, 394, 504, 498, 469);

        $rostering = array(551, 552, 528, 170, 409, 411, 425, 396, 400, 423, 402, 403, 400, 423, 402, 403, 412, 466, 433, 305, 434, 435, 500);

        $timeSheet = array(437, 438, 439, 440, 441);
        $myEdge = array();

        $extraPages = array(158);

        $myAlliedTraining = array(542, 543, 544.549);

        $totalPageArray = array_merge($pageRoot, $jobModule, $signOnOffModule, $adminSubMenu, $myPatrol, $trainingSubMenu, $hrSubMenu, $bdSubMenu, $reportingMenu, $mettingsMenu, $operations,
                $rostering, $timeSheet, $myEdge, $extraPages, $myAlliedTraining);

        return $totalPageArray;
    }

    function StateOperationManagerPages() {
        $pageRoot = array('52', 526, 382, 79, 419,
            422, 218, 421, 485, 449, 203, 512, 515, 414, 410, 420, 147, 201, 150, 448, 496, 337,
            '374', '375', '455', '153', '161', '418', 44);

        $signOnOffModule = array(407, 523, 524);
        $jobModule = array(519, 520, 521, 522);

        /* admin sub menu */

        $adminSubMenu = array(149, 238, 427, 472, 206, 175, 258, 354, 355, 431);

        $myPatrol = array(516, 453, 457);

        $trainingSubMenu = array(508, 153, 91, 502, 501, 503);

        $hrSubMenu = array(161, 476, 510, 214, 450, 456, 451, 266, 275, 492, 493, 494, 495, 13, 352, 458,
            244, 194, 406, 320, 253, 224, 284);
        
        

        //$bdSubMenu = array(368,391,390,467,468);
        $bdSubMenu = array();

        $reportingMenu = array(527, 553, 369, 338, 357, 362, 337, 208, 182, 490, 488, 507, 202, 243, 377, 376, 178, 193, 245, 287, 460, 471, 463, 464, 466, 470);

        $mettingsMenu = array(358, 348, 349, 347);

        $operations = array(168, 313, 424, 499, 392, 491, 169, 288, 394, 504, 498, 469);

        $rostering = array(551, 552, 528, 170, 409, 411, 425, 396, 400, 423, 402, 403, 400, 423, 402, 403, 412, 466, 305, 434);

        $timeSheet = array(437, 438, 439, 440, 441);
        $myEdge = array();

        $extraPages = array(158);

        $myAlliedTraining = array(542, 543, 544.549);

        $totalPageArray = array_merge($pageRoot, $jobModule, $signOnOffModule, $adminSubMenu, $myPatrol, $trainingSubMenu, $hrSubMenu, $bdSubMenu, $reportingMenu, $mettingsMenu, $operations,
                $rostering, $timeSheet, $myEdge, $extraPages, $myAlliedTraining);

        return $totalPageArray;
    }

    function OperationManagerPages() {
        $pageRoot = array('52', 526, '374', '375', '455',
            '153', '161', '418', 443, 515, 556);

        $signOnOffModule = array(407, 523, 524);
        $jobModule = array(519, 520, 521, 522);

        /* admin sub menu */

        $adminSubMenu = array(149, 238, 427, 472, 206, 175, 258, 354, 355, 431);

        $myPatrol = array(516, 453, 457);

        $trainingSubMenu = array(508, 153, 91, 502, 501, 503);

        $hrSubMenu = array(161, 476, 510, 214, 450, 456, 451, 266, 275, 492, 493, 494, 495, 352, 458,
            244, 194, 406, 320, 253, 224, 556);

        

        //$bdSubMenu = array(368,391,390,467,468);
        $bdSubMenu = array();

        $reportingMenu = array(527, 553, 369, 338, 357, 362, 337, 208, 182, 490, 488, 507, 202, 243, 377, 376, 193, 245, 287, 460, 471, 463, 464, 466, 470);

        $mettingsMenu = array(358, 348, 349, 347);

        $operations = array(168, 313, 424, 499, 392, 491, 169, 288, 394, 504, 469);

        $rostering = array(551, 552, 528, 170, 409, 411, 425, 396, 400, 423, 402, 403, 400, 423, 402, 403, 412, 466, 305, 434);

        $timeSheet = array(437, 438, 439, 440, 441);

        $myEdge = array();
        $extraPages = array(158);

        $myAlliedTraining = array(542, 543, 544.549);

        $totalPageArray = array_merge($pageRoot, $jobModule, $signOnOffModule, $adminSubMenu, $myPatrol, $trainingSubMenu, $hrSubMenu, $bdSubMenu, $reportingMenu, $mettingsMenu, $operations,
                $rostering, $timeSheet, $myEdge, $extraPages, $myAlliedTraining);

        return $totalPageArray;
    }

    function BussinessServiceManagerPages() {
        $pageRoot = array('52', 526, '374', '375', '455',
            '153', '161', '418', 443, 515, 556);

        $signOnOffModule = array(407, 523, 524);
        $jobModule = array(519, 520, 521, 522);

        /* admin sub menu */

        $adminSubMenu = array(149, 238, 427, 472, 206, 175, 258, 354, 355, 431);

        $myPatrol = array(516, 453, 457);

        $trainingSubMenu = array(508, 153, 91, 502, 501, 503);

        $hrSubMenu = array(161, 476, 510, 214, 450, 456, 451, 266, 275, 492, 493, 494, 495, 352, 458,
            244, 194, 406, 320, 253, 224, 556);
        
        

        //$bdSubMenu = array(368,391,390,467,468);
        $bdSubMenu = array();

        $reportingMenu = array(527, 553, 369, 338, 357, 362, 337, 208, 182, 490, 488, 507, 202, 243, 377, 376, 193, 245, 287, 460, 471, 463, 464, 466, 470);

        $mettingsMenu = array(358, 348, 349, 347);

        $operations = array(168, 313, 424, 499, 392, 491, 169, 288, 394, 504, 469);

        $rostering = array(551, 552, 528, 170, 409, 411, 425, 396, 400, 423, 402, 403, 400, 423, 402, 403, 412, 466, 305, 434);

        $timeSheet = array(437, 438, 439, 440, 441);

        $myEdge = array();

        $extraPages = array(158);

        $myAlliedTraining = array(542, 543, 544.549);

        $totalPageArray = array_merge($pageRoot, $jobModule, $signOnOffModule, $adminSubMenu, $myPatrol, $trainingSubMenu, $hrSubMenu, $bdSubMenu, $reportingMenu, $mettingsMenu, $operations,
                $rostering, $timeSheet, $myEdge, $extraPages, $myAlliedTraining);
    }

    function ClientServiceManagerPages() {
        $pageRoot = array('52', 526, '374', '375', '455',
            '153', '161', '418');

        $signOnOffModule = array(407, 523, 524);
        $jobModule = array();

        /* admin sub menu */

        $adminSubMenu = array(149, 238, 427, 472, 354, 355, 431);

        // $adminSubMenu = array(149,238,427,472,206,175,258,354,355,431);
        $myPatrol = array(516, 453, 457);

        $trainingSubMenu = array(508, 153, 91, 502, 501, 503);

        $hrSubMenu = array(161, 476, 510, 214, 450, 456, 451, 266, 275, 492, 493, 494, 495, 458,
            244, 194, 406, 320, 253);

        

        //$bdSubMenu = array(368,391,390,467,468);
        $bdSubMenu = array();

        $reportingMenu = array(527, 553, 369, 338, 357, 362, 337, 208, 182, 490, 202, 243, 377, 376, 460, 463, 464, 466, 470);

        $mettingsMenu = array(358, 348, 349, 347);

        $operations = array(168, 313, 424, 499, 392, 169);

        $rostering = array(551, 552, 528, 170, 409, 411, 425, 396, 400, 423, 402, 403, 400, 423, 402, 403, 412, 466, 434);

        $timeSheet = array();

        $myEdge = array();
        $extraPages = array(158);

        $myAlliedTraining = array(542, 543, 544.549);

        $totalPageArray = array_merge($pageRoot, $jobModule, $signOnOffModule, $adminSubMenu, $myPatrol, $trainingSubMenu, $hrSubMenu, $bdSubMenu, $reportingMenu, $mettingsMenu, $operations,
                $rostering, $timeSheet, $myEdge, $extraPages, $myAlliedTraining);
        return $totalPageArray;
    }

    function AreaServiceManagerPages() {
        $pageRoot = array('52', 526, '374', '375', '455',
            '153', '161', '418');

        $signOnOffModule = array(407, 523, 524);
        $jobModule = array();

        /* admin sub menu */

        $adminSubMenu = array(149, 238, 427, 472, 354, 431);

        $myPatrol = array(516, 453, 457);

        $trainingSubMenu = array(508, 153, 91, 502, 501, 503);

        $hrSubMenu = array(161, 214, 450, 456, 451, 266, 275, 492, 493, 494, 495, 458,
            194, 406, 253);

            


        //$bdSubMenu = array(368,391,390,467,468);
        $bdSubMenu = array();

        $reportingMenu = array(527, 553, 369, 338, 357, 362, 337, 182, 208, 490, 202, 243, 377, 376, 463, 464, 466, 470);

        $mettingsMenu = array(358, 348, 349, 347);

        $operations = array(168, 313, 424, 499, 392, 169);

        $rostering = array(551, 552, 528, 170, 409, 411, 425, 396, 400, 423, 402, 403, 400, 423, 402, 403, 412, 466, 434);

        $timeSheet = array();

        $myEdge = array();

        $extraPages = array(158);

        $myAlliedTraining = array(542, 543, 544.549);

        $totalPageArray = array_merge($pageRoot, $jobModule, $signOnOffModule, $adminSubMenu, $myPatrol, $trainingSubMenu, $hrSubMenu, $bdSubMenu, $reportingMenu, $mettingsMenu, $operations,
                $rostering, $timeSheet, $myEdge, $extraPages, $myAlliedTraining);
        return $totalPageArray;
    }

    function CompliancePages() {
        $pageRoot = array('52', 526, '374', '375', '455',
            '153', '161', '418', '556');

        $signOnOffModule = array(407, 523, 524);
        $jobModule = array(519, 520, 521, 522);

        /* admin sub menu */

        $adminSubMenu = array(149, 238, 427, 472, 354, 431);

        $trainingSubMenu = array(508, 153, 91, 502, 501, 503);

        $hrSubMenu = array(161, 214, 450, 456, 451, 266, 275, 492, 493, 494, 495,
            194, 406, 253, 556);

        

        //$bdSubMenu = array(368,391,390,467,468);
        $bdSubMenu = array();

        $reportingMenu = array(527, 553, 369, 338, 357, 362, 337, 208, 182, 490, 202, 243, 377, 376, 463, 464, 466, 470);

        $mettingsMenu = array(358, 348, 349, 347);

        $operations = array(168, 313, 424, 499, 392, 394, 504, 169);

        $rostering = array(551, 552, 170, 409, 411, 425, 412, 466, 434);

        $timeSheet = array();

        $myPatrol = array();

        $myEdge = array();

        $extraPages = array(158);

        $myAlliedTraining = array(542, 543, 544.549);

        $totalPageArray = array_merge($pageRoot, $jobModule, $signOnOffModule, $adminSubMenu, $myPatrol, $trainingSubMenu, $hrSubMenu, $bdSubMenu, $reportingMenu, $mettingsMenu, $operations,
                $rostering, $timeSheet, $myEdge, $extraPages, $myAlliedTraining);
        return $totalPageArray;
    }

    function HrPages() {


        $pageRoot = array('52', 526, '374', '375', '455',
            '153', '161', '418', '508', '382', 443, '556');

        $signOnOffModule = array(407, 523, 524);
        $jobModule = array(519, 520, 521, 522);

        /* admin sub menu */

        $adminSubMenu = array(149, 238, 459, 427, 472, 354, 431);

        $trainingSubMenu = array(153, 91, 502, 501, 503);

        $hrSubMenu = array(161, 476, 510, 416, 240, 214, 450, 456, 451, 266, 275, 492, 493, 494, 495, 452, 13, 221, 352, 458,
            244, 194, 406, 320, 253, 224, 284, 556);

        

        $bdSubMenu = array();

        $reportingMenu = array(527, 553, 369, 338, 357, 362, 337, 208, 182, 490, 202, 243, 377, 376, 460, 463, 464, 466, 470);

        $mettingsMenu = array(358, 348, 349, 347);

        $operations = array(168, 313, 424, 499, 392, 169);

        $rostering = array(551, 552, 170, 409, 411, 425, 412, 466, 433, 434, 435);

        $timeSheet = array(437, 438, 439, 440, 441);
        $myEdge = array();
        $myPatrol = array();

        $extraPages = array(158);

        $myAlliedTraining = array(542, 543, 544.549);

        $totalPageArray = array_merge($pageRoot, $jobModule, $signOnOffModule, $adminSubMenu, $myPatrol, $trainingSubMenu, $hrSubMenu, $bdSubMenu, $reportingMenu, $mettingsMenu, $operations,
                $rostering, $timeSheet, $myEdge, $extraPages, $myAlliedTraining);

        return $totalPageArray;
    }

    function adminPages() {
        $pageRoot = array('52', 526, '153',
            '418', '508', '556');

        $signOnOffModule = array(407, 523, 524);
        $jobModule = array(519, 520, 521, 522);
        /* admin sub menu */

        $adminSubMenu = array(149, 474, 150, 482, 478, 157, 486, 475, 238, 427, 472, 206, 175, 258, 354, 355, 431);

        $trainingSubMenu = array(153, 91, 502, 501, 503);

        $hrSubMenu = array(161, 476, 510, 416, 240, 214, 450, 456, 451, 266, 275, 492, 493, 494, 495, 452, 13, 221, 352, 458,
            244, 194, 406, 320, 253, 224, 284, 556);

        

        $bdSubMenu = array();

        $reportingMenu = array(527, 553, 369, 338, 357, 362, 337, 208, 182, 490, 202, 243, 377, 376, 193, 245, 287, 460, 471, 463, 464, 466, 470);

        $mettingsMenu = array(358, 348, 349, 347);

        $operations = array(168, 313, 424, 499, 392, 169);

        $rostering = array(551, 552, 170, 409, 411, 425, 412, 466, 433, 434, 435);

        $timeSheet = array();
        $myEdge = array();
        $myPatrol = array();

        $extraPages = array(158);

        $myAlliedTraining = array(542, 543, 544.549);

        $totalPageArray = array_merge($pageRoot, $jobModule, $signOnOffModule, $adminSubMenu, $myPatrol, $trainingSubMenu, $hrSubMenu, $bdSubMenu, $reportingMenu, $mettingsMenu, $operations,
                $rostering, $timeSheet, $myEdge, $extraPages, $myAlliedTraining);

        return $totalPageArray;
    }

    function financePages() {
        $pageRoot = array('52', 526, '153', 443);

        $signOnOffModule = array(407, 523, 524);
      $jobModule = array(519, 520, 521, 522);
        /* admin sub menu */

        $adminSubMenu = array(149, 238, 472, 354);

        $trainingSubMenu = array(153, 91, 502, 501, 503);

        $hrSubMenu = array(161, 266, 275, 492, 493, 494, 495, 352, 458);
        


        $bdSubMenu = array();

        $reportingMenu = array();

        $mettingsMenu = array(358, 348, 349, 347);

        $operations = array();

        $rostering = array(551, 552, 170, 409, 411, 425, 412, 466, 433, 434, 435);

        $timeSheet = array(437, 439, 440, 441);
        $myEdge = array();
        $myPatrol = array();

        $extraPages = array(158);

        $myAlliedTraining = array(542, 543, 544.549);

        $totalPageArray = array_merge($pageRoot, $jobModule, $signOnOffModule, $adminSubMenu, $myPatrol, $trainingSubMenu, $hrSubMenu, $bdSubMenu, $reportingMenu, $mettingsMenu, $operations,
                $rostering, $timeSheet, $myEdge, $extraPages, $myAlliedTraining);

        return $totalPageArray;
    }

    function bdPages() {
        $pageRoot = array('52', 526, 382, 79, 419,
            422, 218, 421, 485, 449, 203, 512, 414, 410, 420, 147, 201, 150, 448, 496, 337, '153');

        $signOnOffModule = array(407, 523, 524);
        $jobModule = array();
        /* admin sub menu */

        $adminSubMenu = array(149, 238, 472, 354);

        $trainingSubMenu = array(153, 91, 502, 501, 503);

        $hrSubMenu = array(161, 266, 275, 492, 493, 494, 495, 352, 458);
        


        $bdSubMenu = array();

        $reportingMenu = array(527, 553, 369, 338, 357, 362, 337, 208, 182, 490, 193, 245, 287, 460, 471, 463, 464, 466);

        $mettingsMenu = array(358, 348, 349, 347);

        $operations = array(168, 313, 424, 499, 392);

        $rostering = array(551, 552, 170, 409, 411, 425, 412, 466, 433, 434, 435);

        $timeSheet = array();
        $myEdge = array();

        $extraPages = array(158);
        $myPatrol = array();

        $myAlliedTraining = array(542, 543, 544.549);

        $totalPageArray = array_merge($pageRoot, $jobModule, $signOnOffModule, $adminSubMenu, $myPatrol, $trainingSubMenu, $hrSubMenu, $bdSubMenu, $reportingMenu, $mettingsMenu, $operations,
                $rostering, $timeSheet, $myEdge, $extraPages, $myAlliedTraining);

        return $totalPageArray;
    }

    function SiteMangerPages() {
        $pageRoot = array('52', 526, '407', '374', '375', '153');

        $signOnOffModule = array(407, 523, 524);
        $jobModule = array();

        /* admin sub menu */

        $adminSubMenu = array(149, 238, 472, 354);

        $trainingSubMenu = array();

        $hrSubMenu = array();

        


        $bdSubMenu = array();

        $reportingMenu = array(527, 553, 369, 338, 357, 362, 337, 208, 182, 193, 245);

        $mettingsMenu = array(358, 348, 351, 349, 347);

        $operations = array(168, 313, 424, 498);

        $rostering = array(551, 552, 170, 409, 411, 396, 400, 423, 402, 403, 412);

        $timeSheet = array();
        $myEdge = array(47, 404, 393, 399, 246, 283, 215, 158, 306, 317, 239, 353, 367, 318, 290);

        $extraPages = array(158);
        $myPatrol = array();

        $myAlliedTraining = array(542, 543, 544.549);

        $totalPageArray = array_merge($pageRoot, $jobModule, $signOnOffModule, $adminSubMenu, $myPatrol, $trainingSubMenu, $hrSubMenu, $bdSubMenu, $reportingMenu, $mettingsMenu, $operations,
                $rostering, $timeSheet, $myEdge, $extraPages, $myAlliedTraining);

        return $totalPageArray;
    }

    function employeePages() {
        $pageRoot = array('52', 526, '407', '374', '375', '153');
        $signOnOffModule = array(407, 523, 524);

        $jobModule = array();

        /* admin sub menu */

        $adminSubMenu = array(149, 238, 459, 472, 354);

        $trainingSubMenu = array(153, 91, 502, 501, 503);

        $hrSubMenu = array();
        

        $bdSubMenu = array();

        $reportingMenu = array(369, 338, 357, 362, 337, 182);

        $mettingsMenu = array(358, 348, 351, 349, 347);

        $operations = array(168, 313, 424, 498);

        // $rostering = array(170, 409, 411, 425, 412);

        $rostering = array();

        $timeSheet = array();
        $myEdge = array(47, 404, 393, 399, 246, 283, 215, 158, 306, 317, 239, 353, 367, 318, 290);

        $extraPages = array(158);
        $myPatrol = array();

        $myAlliedTraining = array(542, 543, 544.549);

        $totalPageArray = array_merge($pageRoot, $jobModule, $signOnOffModule, $adminSubMenu, $myPatrol, $trainingSubMenu, $hrSubMenu, $bdSubMenu, $reportingMenu, $mettingsMenu, $operations,
                $rostering, $timeSheet, $myEdge, $extraPages, $myAlliedTraining);

        return $totalPageArray;
    }

    function nationalOperationPages() {
        $pageRoot = array('52', 526, 422, 419);

        $signOnOffModule = array(407, 523, 524);
        $jobModule = array();

        /* admin sub menu */

        $adminSubMenu = array(149, 238, 427, 472, 354);

        $trainingSubMenu = array();

        //$hrSubMenu = array(161, 510);
        $hrSubMenu = array();
        


        $bdSubMenu = array();

        $reportingMenu = array(369, 338, 357, 362, 337, 208, 513, 202);

        // $reportingMenu = array(202);

        $mettingsMenu = array();

        $operations = array(168, 313, 424, 499);

        $rostering = array(551, 552, 170, 409, 411, 425, 412);

        $timeSheet = array();
        $myEdge = array();

        $extraPages = array(158);

        $myPatrol = array();

        $myAlliedTraining = array(542, 543, 544.549);

        $totalPageArray = array_merge($pageRoot, $jobModule, $signOnOffModule, $adminSubMenu, $myPatrol, $trainingSubMenu, $hrSubMenu, $bdSubMenu, $reportingMenu, $mettingsMenu, $operations,
                $rostering, $timeSheet, $myEdge, $extraPages, $myAlliedTraining);

        return $totalPageArray;
    }

    function stateManagerPages() {
        $pageRoot = array('52', 526, 422, 419);

        $signOnOffModule = array(407, 523, 524);
        $jobModule = array();

        /* admin sub menu */

        $adminSubMenu = array(149, 238, 427, 472, 354);

        $trainingSubMenu = array();

        //$hrSubMenu = array(161, 510);
        $hrSubMenu = array();
        

        $bdSubMenu = array();

        $reportingMenu = array(369, 338, 357, 362, 337, 208, 513, 202);

        $mettingsMenu = array();

        $operations = array(168, 313, 424, 499);

        $rostering = array(551, 552, 170, 409, 411, 425, 412);

        $timeSheet = array();
        $myEdge = array();

        $extraPages = array(158);

        $myPatrol = array();

        $totalPageArray = array_merge($pageRoot, $jobModule, $signOnOffModule, $adminSubMenu, $myPatrol, $trainingSubMenu, $hrSubMenu, $bdSubMenu, $reportingMenu, $mettingsMenu, $operations,
                $rostering, $timeSheet, $myEdge, $extraPages);

        $myAlliedTraining = array(542, 543, 544.549);

        $totalPageArray = array_merge($pageRoot, $jobModule, $signOnOffModule, $adminSubMenu, $myPatrol, $trainingSubMenu, $hrSubMenu, $bdSubMenu, $reportingMenu, $mettingsMenu, $operations,
                $rostering, $timeSheet, $myEdge, $extraPages, $myAlliedTraining);
    }

    function clientManagerPages() {
        $pageRoot = array('52', 526, 422, 419);

        $signOnOffModule = array(407, 523, 524);
        $jobModule = array();

        /* admin sub menu */

        $adminSubMenu = array(149, 238, 427, 472, 354);

        $trainingSubMenu = array();

        // $hrSubMenu = array(161, 510);
        $hrSubMenu = array();
        


        $bdSubMenu = array();

        $reportingMenu = array(369, 338, 357, 362, 337, 208, 513, 202);

        $mettingsMenu = array();

        $operations = array(168, 313, 424, 499);

        $rostering = array(551, 552, 170, 409, 411, 425, 412);

        $timeSheet = array();
        $myEdge = array();

        $extraPages = array(158);

        $myPatrol = array();

        $myAlliedTraining = array(542, 543, 544.549);

        $totalPageArray = array_merge($pageRoot, $jobModule, $signOnOffModule, $adminSubMenu, $myPatrol, $trainingSubMenu, $hrSubMenu, $bdSubMenu, $reportingMenu, $mettingsMenu, $operations,
                $rostering, $timeSheet, $myEdge, $extraPages, $myAlliedTraining);

        return $totalPageArray;
    }

    function providerPages() {
        $pageRoot = array('52', 526, '407');

        $signOnOffModule = array(407, 523, 524);
        $jobModule = array();

        /* admin sub menu */

        $adminSubMenu = array();

        $trainingSubMenu = array();

        $hrSubMenu = array();
        


        $bdSubMenu = array();

        $reportingMenu = array(369, 338, 357, 362, 337, 208, 182);

        $mettingsMenu = array();

        $operations = array();

        $rostering = array();

        $timeSheet = array();
        $myEdge = array(47, 404, 393, 215);

        $extraPages = array(158);

        $myPatrol = array();

        $myAlliedTraining = array(542, 543, 544.549);

        $totalPageArray = array_merge($pageRoot, $jobModule, $signOnOffModule, $adminSubMenu, $myPatrol, $trainingSubMenu, $hrSubMenu, $bdSubMenu, $reportingMenu, $mettingsMenu, $operations,
                $rostering, $timeSheet, $myEdge, $extraPages, $myAlliedTraining);

        return $totalPageArray;
    }

    function visitorPages() {
        $pageRoot = array('52', 526);

        $signOnOffModule = array(407, 523, 524);
        $jobModule = array();

        /* admin sub menu */

        $adminSubMenu = array();

        $trainingSubMenu = array();

        $hrSubMenu = array();
        


        $bdSubMenu = array();

        $reportingMenu = array();

        $mettingsMenu = array();

        $operations = array();

        $rostering = array();

        $timeSheet = array();
        $myEdge = array(47, 404, 393, 215);
        $extraPages = array(158);
        $myPatrol = array();

        $totalPageArray = array_merge($pageRoot, $jobModule, $signOnOffModule, $adminSubMenu, $myPatrol, $trainingSubMenu, $hrSubMenu, $bdSubMenu, $reportingMenu, $mettingsMenu, $operations,
                $rostering, $timeSheet, $myEdge, $extraPages);

        return $totalPageArray;
    }

    function deleteAccessLevelOthers() {

        /* delete other roles access only super admin rights keep record by  heid */
        $sql = "delete FROM page_access WHERE group_id != 115";

        /* TRUNCATE TABLE  log_login;
          TRUNCATE TABLE  log_page_access;
          TRUNCATE TABLE  marketing_campaign_log;
          TRUNCATE TABLE  marketing_notes;
          TRUNCATE TABLE  marketing_notes;
          TRUNCATE TABLE  wfc;
          TRUNCATE TABLE user_update_log;
          TRUNCATE TABLE user_notes;
          TRUNCATE TABLE user_logins
          TRUNCATE TABLE user_logins
         * 


         */
    }

    function rosterSendMessage($rosterStafffid, $sendMessage) {

        //  $msg .= "Password Reset Link Sent.<br /><br /><br />Please check your email for password reset instructions...";
        $email_msg = $sendMessage;
        $sms_msg = $sendMessage;

        $rosterQuery = "select staff.email,staff.phone,staff.phone2 "
                . " from roster_times_staff rst "
                . " left join users staff on staff.id = rst.staff_id"
                . " where rst.id = '" . $rosterStafffid . "'";
        // $result = $this->dbi->query($rosterQuery);

        if ($result = $this->dbi->query($rosterQuery)) {
            // prd($result);
            while ($myrow = $result->fetch_assoc()) {
                $rosterEmail = $myrow['email'];
                $phone = $myrow['phone'];
                $phone2 = $myrow['phone2'];
                $sendSms = 1;
                $sendEmail = 0;
                if ($sendSms) {
                    $sms = new sms($this->dbi);
                    if ($phone = $sms->process_phones($phone, $phone2)) {
                        //$phone = "919694933386";
                        $sms->send_message($phone, $sms_msg);
                        // die("hello");
                    }
                }
                //$rosterEmail =  "mahavir.jain@dotsquares.com";
                if ($sendEmail && $rosterEmail) {
                    $mail = new email_q($this->dbi);
                    // $rosterStaffEmail = "mahavir.jain@dotsquares.com";
                    $mail->AddAddress($rosterEmail);
                    $mail->Subject = "Urgent-Roster Notification";
                    $mail->Body = $email_msg;
                    //$mail->AddAttachment($file_path_name);
                    if ($rosterEmail != "") {
                        if ($mail->send()) {
                            $mail->queue_message_sent();
                        }
                    }
                }
            }
        }
    }

    /* email */

    function sendRegistrationEmail($name, $email, $username, $password) {
        $send_url = $this->f3->get('full_url') . "login";
        //  $msg .= "Password Reset Link Sent.<br /><br /><br />Please check your email for password reset instructions...";
        $email_msg = "<p>Hello {$name},</p>
                   Your account has been added on allied </p>
                   <p>You can access the site by below detail</p>
                    <p> Username: {$username} </p>
                    <p> Password: {$password}</p>
                    <p></p>
                    <p></p>
                   Please click the below link to access site:</p>
                   <p><a href=\"$send_url\">$send_url</a></p>                   
                   <p>Regards,<br />" . $this->f3->get('company_name') . "</p>
                ";

        //prd($this->dbi);
        $mail = new email_q($this->dbi);
        $mail->AddAddress($email);
        //$mail->AddAddress("mahavir.jain@dotsquares.com");
        //$mail->AddAddress("mahavir1.jain@dotsquares.com");
        //$mail->AddReplyTo("mahavir2.jain@dotsquares.com");
        $mail->Subject = "Your account has been added on allied";
        $mail->Body = $email_msg;
        //$mail->queue_message();

        $mail->send();
    }

    function sendLicenseEmail($query, $email, $user_id) {
//        prd($query);
//        die;

        $body1 = '<table id="customers">	
					<thead>
						<tr>
							<th>#</th>
							<th>Licence Holder</th>						
							<th>Licence Type</th>
							<th>Licence Number</th>
							<th>Class/Compliance</th>
							<th>Expired</th>
							<th>State Issued</th>
							<th>Verified By</th>
							<th>photo1</th>	
                                                        <th>photo2</th>	
						</tr>
						</thead>
  		     	<tbody>';

        if ($result = $this->dbi->query($query)) {
            // prd($result);
            foreach ($result as $key => $val) {
                $holderName = $val['Licence Holder'];
//                             prd($res);
//                            if ($val= $res->fetch_assoc()) {            
//                            prd($val);

                $body1 .= "<tr>  		     		                    
               <td>" . ++$key . "</td>
               <td>" . $val['Licence Holder'] . "</td>               
               <td>" . $val['Licence Type'] . "</td>
               <td>" . $val['Licence Number'] . "</td>
               <td>" . $val['Class/Compliance'] . "</td>
               <td>" . $val['Expired'] . "</td>
               <td>" . $val['State Issued'] . "</td>
               <td>" . $val['Verified By'] . "</td>
               <td>" . $val['Photo 1'] . "</td>
               <td>" . $val['Photo 2'] . "</td></tr> ";
            }
            $body1 .= "	</tbody></table>";
        }
        $body = " Hi <br/> Please find License Detail  of " . $holderName . " </br> " . $body1 . "<br/><br/>";
        $body .= " Kind Regards ";

        if ($result && $holderName != "") {
            $mail = new email_q($this->dbi);
            $mail->AddAddress($email);
            //$mail->AddAddress("mahavir.jain@dotsquares.com");
            //$mail->AddAddress("mahavir1.jain@dotsquares.com");
            //$mail->AddReplyTo("mahavir2.jain@dotsquares.com");
            $mail->Subject = "License Detail of " . $holderName;
            $mail->Body = $body;

            $mail->send();

            //$mail->queue_message();
        }
    }

    /* email */

    function sendPasswordChangeEmail($name, $email, $username, $password) {
        $send_url = $this->f3->get('full_url') . "login";
        //  $msg .= "Password Reset Link Sent.<br /><br /><br />Please check your email for password reset instructions...";
        $email_msg = "<p>Hello {$name},</p>
                   Your account password been reset on allied </p>
                   <p>You can access the site by below detail</p>
                    <p> Username: {$username} </p>
                    <p> Password: {$password}</p>
                    <p></p>
                    <p></p>
                   Please click the below link to access site:</p>
                   <p><a href=\"$send_url\">$send_url</a></p>                   
                   <p>Regards,<br />" . $this->f3->get('company_name') . "</p>
                ";

        //prd($this->dbi);
        $mail = new email_q($this->dbi);
        $mail->AddAddress($email);
        //$mail->AddAddress("mahavir.jain@dotsquares.com");
        //$mail->AddAddress("mahavir1.jain@dotsquares.com");
        //$mail->AddReplyTo("mahavir2.jain@dotsquares.com");
        $mail->Subject = "Your account password has been reset on allied";
        $mail->Body = $email_msg;
        $mail->queue_message();

        //$mail->send();
    }

    /* check User type  by level */

    function isClientUserLevel($level) {

        if ($level >= 100 and $level <= 300) {
            return true;
        } else {
            return false;
        }
    }

    /*
     * change array
     */

    function trainingStatusGraph($trainingStatusDetails) {
        // prd($trainingStatusDetail);
        $userArray = [];
        $courseIdArray = [];
        $empTrainingStatus = [];
        $empTrainingStatusRow = [];
        $empTrainingStatusCourse = [];
        $i;
        //prd($trainingStatusDetails);
        foreach ($trainingStatusDetails as $trainingStatusDetail) {
            //  pr($trainingStatusDetail);
            if (in_array($trainingStatusDetail['user_id'], $userArray)) {
                if (!in_array($trainingStatusDetail['course_id'], $courseIdArray)) {
                    array_push($courseIdArray, $trainingStatusDetail['course_id']);
                    array_push($empTrainingStatusCourse, $trainingStatusDetail['course_title']);
                }
                $percentage['pass_total'] = $trainingStatusDetail['pass_total'];
                $percentage['assign_total'] = $trainingStatusDetail['assign_total'];
                $percentage['percentage'] = $trainingStatusDetail['assign_total'] > 0 ? ($trainingStatusDetail['pass_total'] / $trainingStatusDetail['assign_total']) : "1000";
                $percentage['show_text'] = $trainingStatusDetail['assign_total'] > 0 ? $percentage['percentage'] == 1 ? "Yes (" . $trainingStatusDetail['pass_total'] . " / " . $trainingStatusDetail['assign_total'] . ")" : "No (" . $trainingStatusDetail['pass_total'] . " / " . $trainingStatusDetail['assign_total'] . ")" : "N/A";
                $percentage['show_class'] = $percentage['percentage'] == 1000 || ($percentage['percentage'] == 1) ? "colorYes" : "colorNo";
                $percentage['detail_link'] = "javascript:view_chart_detail('" . $trainingStatusDetail['user_id'] . "','" . $trainingStatusDetail['course_id'] . "')";
                array_push($empTrainingStatusRow, $percentage);
            } else {

                if (!empty($empTrainingStatusRow)) {
                    if (!empty($empTrainingStatus)) {
                        $empTrainingStatus[0] = $empTrainingStatusCourse;
                    }

                    $empTrainingStatus[$i] = $empTrainingStatusRow;
                    // prd($empTrainingStatus);
                    $empTrainingStatusRow = [];
                }
                $i++;
                if (!in_array($trainingStatusDetail['course_id'], $courseIdArray)) {
                    array_push($empTrainingStatusCourse, "");
                    array_push($courseIdArray, $trainingStatusDetail['course_id']);
                    array_push($empTrainingStatusCourse, $trainingStatusDetail['course_title']);
                }
                array_push($userArray, $trainingStatusDetail['user_id']);
                array_push($empTrainingStatusRow, $trainingStatusDetail['employee_name']);
                // $percentage = $trainingStatusDetail['assign_total'] ? ($trainingStatusDetail['pass_total'] / $trainingStatusDetail['assign_total']):0;
                //$percentage[] = $trainingStatusDetail['assign_total'] ? ($trainingStatusDetail['pass_total'] / $trainingStatusDetail['assign_total']):0;
                $percentage['pass_total'] = $trainingStatusDetail['pass_total'];
                $percentage['assign_total'] = $trainingStatusDetail['assign_total'];
                $percentage['percentage'] = $trainingStatusDetail['assign_total'] > 0 ? ($trainingStatusDetail['pass_total'] / $trainingStatusDetail['assign_total']) : "1000";
                $percentage['show_text'] = $trainingStatusDetail['assign_total'] > 0 ? $percentage['percentage'] == 1 ? "Yes (" . $trainingStatusDetail['pass_total'] . " / " . $trainingStatusDetail['assign_total'] . ")" : "No (" . $trainingStatusDetail['pass_total'] . " / " . $trainingStatusDetail['assign_total'] . ")" : "N/A";
                $percentage['show_class'] = $percentage['percentage'] == 1000 || ($percentage['percentage'] == 1) ? "colorYes" : "colorNo";
                $percentage['detail_link'] = "javascript:view_chart_detail('" . $trainingStatusDetail['user_id'] . "','" . $trainingStatusDetail['course_id'] . "')";
                array_push($empTrainingStatusRow, $percentage);
                // array_push($empTrainingStatusRow,$rainingStatusDetail['assign_total'] ? (percentage); 
            }
        }
        return $empTrainingStatus;
//        pr($userArray);
//        pr($courseIdArray);
//        pr($empTrainingStatusCourse);
//        prd($empTrainingStatus);
    }

    function clientLocationDropdown($divisionId = 0, $clientId, $siteId = 0) {

        $locationData = $this->getLocationOfClient($clientId, $divisionId);
        //prd($siteId);
        $str = '<option value="0"> All Site </option>';
        if (!empty($locationData)) {

            foreach ($locationData as $location) {

                if ($siteId == $location['id']) {
                    $selected = "selected";
                } else {
                    $selected = "";
                }

                $str .= '<option value="' . $location['id'] . '" ' . $selected . '>' . $location['item_name'] . '</option>';
            }
        }

        return $str;
    }

    /*
     * return Facilities location
     * 
     */

    function getLocationOfJobs($clientId = 0, $id = 0) {

        //$divisionFilterSql = " left join lookup_answers sla on sla.foreign_id = users.id and sla.lookup_field_id in (".$loginUserDivisions.") and sla.lookup_field_id = '".$srchUserDivision."'";

        if ($id > 0) {
            $idsql = 'or users.ID = ' . $id;
        }

        $sql = "SELECT distinct(users.id) as `id`, 
CONCAT(users.name, ' ', users.surname, if(users.employee_id != '', CONCAT(' (', users.employee_id, ')'), 
if(users.client_id != '', CONCAT(' (', users.client_id, ')'), '')))
 as `item_name`,(
 SELECT GROUP_CONCAT(usersp.name) FROM users usersp LEFT JOIN  associations ass1 ON ass1.parent_user_id = usersp.ID
 WHERE ass1.child_user_id IS NOT NULL and ass1.child_user_id = users.id AND ass1.association_type_id = 1) AS client_name 
FROM users 
inner join lookup_answers on lookup_answers.foreign_id = users.id and (lookup_answers.lookup_field_id = 384 )and lookup_answers.table_assoc = 'users'
inner join associations ass on ass.association_type_id = 1 and ass.child_user_id = users.id and ass.parent_user_id = $clientId
inner join lookup_answers sla on sla.foreign_id = users.id and sla.lookup_field_id = '2100'   
where users.user_status_id = 30 $idsql order by users.name, users.surname";

        $locationList = array();
        if ($result = $this->dbi->query($sql)) {
            while ($myrow = $result->fetch_assoc()) {
                $locationList[] = $myrow;
            }
        }

        return $locationList;
    }

    function getLocationOfClient($clientId = 0, $divisionId = 0, $id = 0) {

        //$divisionFilterSql = " left join lookup_answers sla on sla.foreign_id = users.id and sla.lookup_field_id in (".$loginUserDivisions.") and sla.lookup_field_id = '".$srchUserDivision."'";

        if ($id > 0) {
            $idsql = 'or users.ID = ' . $id;
        }

        if ($divisionId != 0) {
            $divisionJoin = " inner join lookup_answers sla on sla.foreign_id = users.id and sla.lookup_field_id = '$divisionId' ";
        } else {
            $divisionJoin = "";
        }

        $sql = "SELECT distinct(users.id) as `id`, 
CONCAT(users.name, ' ', users.surname, if(users.employee_id != '', CONCAT(' (', users.employee_id, ')'), 
if(users.client_id != '', CONCAT(' (', users.client_id, ')'), '')))
 as `item_name`,(
 SELECT GROUP_CONCAT(usersp.name) FROM users usersp LEFT JOIN  associations ass1 ON ass1.parent_user_id = usersp.ID
 WHERE ass1.child_user_id IS NOT NULL and ass1.child_user_id = users.id AND ass1.association_type_id = 1) AS client_name 
FROM users 
inner join lookup_answers on lookup_answers.foreign_id = users.id and (lookup_answers.lookup_field_id = 384 )and lookup_answers.table_assoc = 'users'
inner join associations ass on ass.association_type_id = 1 and ass.child_user_id = users.id and ass.parent_user_id = $clientId 
$divisionJoin    
where users.user_status_id = 30 $idsql order by users.name, users.surname";

        //        
//echo $sql;
//die;
        $locationList = array();
        if ($result = $this->dbi->query($sql)) {
            while ($myrow = $result->fetch_assoc()) {
                $locationList[] = $myrow;
            }
        }

        return $locationList;
    }

    function getLocation($divisionId = 0) {

        //$divisionFilterSql = " left join lookup_answers sla on sla.foreign_id = users.id and sla.lookup_field_id in (".$loginUserDivisions.") and sla.lookup_field_id = '".$srchUserDivision."'";



        if ($divisionId != 0) {
            $divisionJoin = " inner join lookup_answers sla on sla.foreign_id = users.id and sla.lookup_field_id = '$divisionId' ";
        } else {
            $divisionJoin = "";
        }

        $sql = "SELECT distinct(users.id) as `id`, 
CONCAT(users.name, ' ', users.surname, if(users.employee_id != '', CONCAT(' (', users.employee_id, ')'), 
if(users.client_id != '', CONCAT(' (', users.client_id, ')'), '')))
 as `item_name`,(
 SELECT GROUP_CONCAT(usersp.name) FROM users usersp LEFT JOIN  associations ass1 ON ass1.parent_user_id = usersp.ID
 WHERE ass1.child_user_id IS NOT NULL and ass1.child_user_id = users.id AND ass1.association_type_id = 1) AS client_name 
FROM users 
inner join lookup_answers on lookup_answers.foreign_id = users.id and (lookup_answers.lookup_field_id = 384 )and lookup_answers.table_assoc = 'users'
$divisionJoin    
where users.user_status_id = 30 order by users.name, users.surname";

        //        
//echo $sql;
//die;
        $locationList = array();
        if ($result = $this->dbi->query($sql)) {
            while ($myrow = $result->fetch_assoc()) {
                $locationList[] = $myrow;
            }
        }

        return $locationList;
    }

    function getClientsData($id = 0) {
        if ($id > 0) {
            $idsql = 'or users.ID = ' . $id;
        }
        $sql = "SELECT distinct(users.id) as `id`, 
CONCAT(users.name, ' ', users.surname) item_name
FROM users 
inner join lookup_answers on lookup_answers.foreign_id = users.id and (lookup_answers.lookup_field_id = 104 )and lookup_answers.table_assoc = 'users'
where users.user_status_id = 30 $idsql order by users.name, users.surname";

        $clientList = array();
        if ($result = $this->dbi->query($sql)) {
            while ($myrow = $result->fetch_assoc()) {
                $clientList[] = $myrow;
            }
        }
        return $clientList;
    }

    function getTrainingClientsData($id = 0) {
        if ($id > 0) {
            $idsql = 'or users.ID = ' . $id;
        }
        $sql = "SELECT distinct(users.id) as `id`, 
CONCAT(users.name, ' ', users.surname) item_name
FROM users 
inner join lookup_answers on lookup_answers.foreign_id = users.id and (lookup_answers.lookup_field_id = 104 )and lookup_answers.table_assoc = 'users'
where users.user_status_id = 30 $idsql order by users.name, users.surname";

        $clientList = array();
        if ($result = $this->dbi->query($sql)) {
            while ($myrow = $result->fetch_assoc()) {
                $clientList[] = $myrow;
            }
        }
        return $clientList;
    }

    /*
     * return client for job
     */

    function getClientsOfJobs($id = 0) {
        if ($id > 0) {
            $idsql = 'or users.ID = ' . $id;
        }

        $sql = "SELECT distinct(users.id) as `id`, 
CONCAT(users.name, ' ', users.surname, if(users.employee_id != '', CONCAT(' (', users.employee_id, ')'), 
if(users.client_id != '', CONCAT(' (', users.client_id, ')'), '')))
 as `item_name`,(
 SELECT GROUP_CONCAT(usersp.name) FROM users usersp LEFT JOIN  associations ass1 ON ass1.parent_user_id = usersp.ID
 WHERE ass1.child_user_id IS NOT NULL and ass1.child_user_id = users.id AND ass1.association_type_id = 1) AS client_name 
FROM users 
inner join lookup_answers on lookup_answers.foreign_id = users.id and (lookup_answers.lookup_field_id = 104 )and lookup_answers.table_assoc = 'users'
where users.user_status_id = 30 $idsql order by users.name, users.surname";

        $locationList = array();
        if ($result = $this->dbi->query($sql)) {
            while ($myrow = $result->fetch_assoc()) {
                $locationList[] = $myrow;
            }
        }

        return $locationList;
    }

    function changedInRoster($rid, $changedArray) {

        if (isset($changedArray['publish'])) {

//            if($changedArray['publish'] == 2)
//            $message = " Your roster has been"
        }

        //$userList = 
    }

    /*
     * return client for job
     */

    function getUserLevel() {

        $sql = $this->get_simple_lookup('user_level');
        $levelList = array();
        if ($result = $this->dbi->query($sql)) {
            while ($myrow = $result->fetch_assoc()) {
                $levelList[] = $myrow;
            }
        }
        return $levelList;
    }

    /*
     * return client for job
     */

    function getSupplierOfJobs($jobcategory_id, $location_id, $id = 0) {
        if ($id > 0) {
            $idsql = 'or users.ID = ' . $id;
        }
        $innerJobCategory = '';

        if ($jobcategory_id) {
            $innerJobCategory = "inner join supplier_jobcategory sjob on sjob.user_id = users.id and sjob.job_category_id = '" . $jobcategory_id . "'";
        }

        $sql = "SELECT distinct(users.id) as `id`, 
CONCAT(users.name, ' ', users.surname, if(users.employee_id != '', CONCAT(' (', users.employee_id, ')'), 
if(users.client_id != '', CONCAT(' (', users.client_id, ')'), '')))
 as `item_name`,(
 SELECT GROUP_CONCAT(usersp.name) FROM users usersp LEFT JOIN  associations ass1 ON ass1.parent_user_id = usersp.ID
 WHERE ass1.child_user_id IS NOT NULL and ass1.child_user_id = users.id AND ass1.association_type_id = 1) AS client_name 
FROM users 
inner join lookup_answers on lookup_answers.foreign_id = users.id and (lookup_answers.lookup_field_id = 105 )and lookup_answers.table_assoc = 'users'
inner join lookup_answers sla on sla.foreign_id = users.id and sla.lookup_field_id = '2100'
$innerJobCategory
where users.user_status_id = 30 $idsql order by users.name, users.surname";

        //prd($sql);

        $locationList = array();
        if ($result = $this->dbi->query($sql)) {
            while ($myrow = $result->fetch_assoc()) {
                $locationList[] = $myrow;
            }
        }

        return $locationList;
    }

    /*
     * license User
     */

    function userLicenseList($user_id) {
        $subqueryDivision = "(SELECT GROUP_CONCAT(' ',com.item_name) FROM lookup_answers la 
LEFT JOIN companies com ON com.id = la.lookup_field_id
WHERE la.foreign_id = users.id AND com.id IS NOT NULL) as `Licence_Division`";

        $sql = " select CONCAT(users.name, ' ', users.surname,'', CASE WHEN users.phone IS NULL then  '' else users.phone END) as `Licence_Holder`,"
                . "$subqueryDivision,"
                . " licence_types.item_name as `Licence_Type`,"
                . " licences.licence_number as `Licence_Number`,"
                . " CONCAT(licences.licence_class, if(licence_compliance_id = '', '', CONCAT('<br/>', lookup_fields.item_name))) as `Class_Compliance` ,"
                . " licences.expiry_date Licence_Expired,"
                . " states.item_name as `Licence_Issued`,"
                . " licences.*"
                . " from licences "
                . " left join users on users.id = licences.user_id"
                . " left join licence_types on licence_types.id = licences.licence_type_id"
                . "  left join states on states.id = licences.state_id"
                . " left join lookup_fields on lookup_fields.id = licences.licence_compliance_id"
                . " where user_id = '" . $user_id . "' ";

        //prd($sql);
        $licenseList = array();
        if ($result = $this->dbi->query($sql)) {
            while ($myrow = $result->fetch_assoc()) {
                $licenseList[] = $myrow;
            }
        }



        return $licenseList;
    }

    function complianceSchedulesCronUpdate($save_id) {

        $sql = "select * from compliance_schedules cs where cs.id = '" . $save_id . "' ";

        if ($result = $this->dbi->query($sql)) {

            if ($schedulesRecord = $result->fetch_assoc()) {

                $cronDateTime = $schedulesRecord['start_date'] . " " . $schedulesRecord['send_time'];
                $cronDateTime = date('Y-m-d H:i:s', strtotime($cronDateTime . ' - 4 hours'));

                $sql = "select * from compliance_schedules_cron csc where csc.com_sch_id = '" . $save_id . "' and csc.status = 0 order by csc.id desc limit 1";

                if ($result1 = $this->dbi->query($sql)) {
                    $myrow = $result1->fetch_assoc();

                    if ($myrow) {

                        $updateQuery = "update compliance_schedules_cron set crondatetime = '" . $cronDateTime . "' where id = '" . $myrow['id'] . "'";

                        $result = $this->dbi->query($updateQuery);
                        // $deleteQ = "delete from associations where child_user_id = '" . $user_id . "'";
                    } else {

                        $insertQuery = "insert into compliance_schedules_cron (com_sch_id,crondatetime) values ('" . $save_id . "','" . $cronDateTime . "')";
//                        echo $insertQuery;
//                        die;
                        $result = $this->dbi->query($insertQuery);
                    }
                } else {

                    $insertQuery = "insert into compliance_schedules_cron (com_sch_id,crondatetime) values ('" . $save_id . "','" . $cronDateTime . "')";
                    $result = $this->dbi->query($insertQuery);
                }

                // $deleteQ = "delete from associations where child_user_id = '" . $user_id . "'";
            }

            //$complianceSchedulesCron = 
        }
    }

    function newSubscriptionDateTime($frequency, $rosterStartDate, $rosterSendTime) {
        while ($rosterStartDate && $rosterStartDate <= date("Y-m-d")) {
            if ($frequency == '2227') {
                $dateIncrement = "+1 week";
            } else if ($frequency == '2228') {
                $dateIncrement = "+1 month";
            } else if ($frequency == '2230') {
                $dateIncrement = "+3 month";
            } else if ($frequency == '2231') {
                $dateIncrement = "+6 month";
            } else if ($frequency == '2232') {
                $dateIncrement = "+4 month";
            } else if ($frequency == '2386') {
                $dateIncrement = "+1 year";
            } else {
                $dateIncrement = 0;
            }

            if ($dateIncrement) {
                $rosterStartDate = date('Y-m-d', strtotime($rosterStartDate . $dateIncrement));
            } else {
                $rosterStartDate = 0;
            }
            // echo $rosterStartDate."</br>";
        }

        if ($rosterStartDate) {
            return $rosterStartDate . " " . $rosterSendTime;
        } else {
            return $rosterStartDate;
        }
    }

    function divisionWiseReportLogo($division_id = 0) {
        if ($division_id == "108") {
            $default_img = "logo-print-security.png";
        } else if ($division_id == "2100") {
            $default_img = "logo-print-facilities.png";
        } elseif ($division_id == "2102") {
            $default_img = "logo-print-Pest.png";
        } elseif ($division_id == "2103") {
            $default_img = "logo-print-Civil.png";
        } elseif ($division_id == "2104") {
            $default_img = "logo-print-Traffic.png";
        } else {
            $default_img = "logo-print.png";
        }

        return $default_img;
    }

    function licenceUser($user_id = 0) {

        if (!$user_id) {
            return "select 0 as id, '--- $top_text ---' as item_name union all (SELECT distinct(users.id) as `id`, 
CONCAT(users.name, ' ', users.surname, if(users.employee_id != '', CONCAT(' (', users.employee_id, ')'), 
if(users.client_id != '', CONCAT(' (', users.client_id, ')'), '')))
 as `item_name`
FROM users 
inner join lookup_answers on lookup_answers.foreign_id = users.id and (lookup_answers.lookup_field_id = 107 or lookup_answers.lookup_field_id = 105 )and lookup_answers.table_assoc = 'users'
where users.user_status_id = 30 order by users.name, users.surname)";
        } else {
            $sql = "select distinct(users.id) as `id`, 
CONCAT(users.name, ' ', users.surname, if(users.employee_id != '', CONCAT(' (', users.employee_id, ')'), 
if(users.client_id != '', CONCAT(' (', users.client_id, ')'), '')))
 as `item_name`
FROM users 
inner join lookup_answers on lookup_answers.foreign_id = users.id and (lookup_answers.lookup_field_id = 107  or lookup_answers.lookup_field_id = 105)and lookup_answers.table_assoc = 'users'
where users.user_status_id = 30 and users.id = '" . $user_id . "' order by users.name, users.surname ";

            return $sql;
        }

//        
//
//        $locationList = array();
//        if ($result = $this->dbi->query($sql)) {
//            while ($myrow = $result->fetch_assoc()) {
//                $locationList[] = $myrow;
//            }
//        }
//
//        return $locationList;
    }

    function lessonList($courseId) {

        $where_cond = " 1 ";

        $where_cond .= " and ta_lessons.status != 2 ";

        if ($courseId) {
            $where_cond .= " and ta_lessons.course_id = '" . $courseId . "'";
        }

        $select_records = "SELECT ta_lessons.`id`, ta_lessons.`created_at`,ta_lessons.`course_id`,ta_lessons.`title`,ta_lessons.`document1`,ta_lessons.`video1`,ta_lessons.`description`,ta_lessons.`status`,division.`item_name` division_name,ta_courses.title course_title,ta_frequency.`title` freq_title";
        $select_num_rows = "SELECT count(ta_lessons.id) as total_records";
        $afterFrom = " FROM training_allied_lessons ta_lessons "
                . " LEFT JOIN training_allied_courses ta_courses ON ta_courses.id=ta_lessons.course_id  "
                . " LEFT JOIN training_allied_frequency ta_frequency ON ta_frequency.id=ta_courses.id  "
                . " LEFT JOIN companies division ON division.id=ta_courses.division_id "
                . " WHERE $where_cond";

        $lessonList = array();

        $selectQuery = $select_records . " " . $afterFrom;

        if ($result = $this->dbi->query($selectQuery)) {
            while ($myrow = $result->fetch_assoc()) {
                //prd($myrow);
                $lessonList[] = $myrow;
                //prd($userList);
            }
        }
        return $lessonList;
    }

    function toolboxLessonList($courseId) {

        $where_cond = " 1 ";

        $where_cond .= " and ta_lessons.status != 2 ";

        if ($courseId) {
            $where_cond .= " and ta_lessons.course_id = '" . $courseId . "'";
        }

        $select_records = "SELECT ta_lessons.`id`, ta_lessons.`created_at`,ta_lessons.`course_id`,ta_lessons.`title`,ta_lessons.`document1`,ta_lessons.`video1`,ta_lessons.`description`,ta_lessons.`status`,division.`item_name` division_name,ta_courses.title course_title,ta_frequency.`title` freq_title";
        $select_num_rows = "SELECT count(ta_lessons.id) as total_records";
        $afterFrom = " FROM toolbox_allied_lessons ta_lessons "
                . " LEFT JOIN toolbox_allied_courses ta_courses ON ta_courses.id=ta_lessons.course_id  "
                . " LEFT JOIN training_allied_frequency ta_frequency ON ta_frequency.id=ta_courses.id  "
                . " LEFT JOIN companies division ON division.id=ta_courses.division_id "
                . " WHERE $where_cond";

        $lessonList = array();

        $selectQuery = $select_records . " " . $afterFrom;

        if ($result = $this->dbi->query($selectQuery)) {
            while ($myrow = $result->fetch_assoc()) {
                //prd($myrow);
                $lessonList[] = $myrow;
                //prd($userList);
            }
        }
        return $lessonList;
    }

    function trainingAssignUser($trainingRuleId, $minLevel, $maxLevel) {
        $whereCond = 1;

        $whereCond .= " and (users.user_level_id >= '" . $minLevel . "' and users.user_level_id <= '" . $maxLevel . "')";
        $whereCond .= " and (sla.lookup_field_id IS NOT NULL)";

        $trainingRuleDetail = $this->trainingRuleDetail($trainingRuleId);

        $division_id = $trainingRuleDetail['division_id'];

        //prd($trainingRuleDetail);

        if (strtotime($trainingRuleDetail['start_date']) > strtotime(date("Y-m-d"))) {
            $deleteUserQuery = "delete  from training_allied_training_user where training_rule_id = '" . $trainingRuleId . "'";
            // echo $deleteUserQuery;
            // die("hello1");
            $this->db->exec($deleteUserQuery);
        }
        //die($trainingRuleDetail['start_date']." ".date("Y-m-d"));
        // prd($trainingRuleDetail);
//        if($clientId != 0){
//            $whereCond .= " clt.ID IS NOT NULL and clt.ID = '$clientId'";
//        }
//        
//        if($siteId != 0){
//            $whereCond .= " site.ID IS NOT NULL and site.ID = '$siteId'";
//        }        
        //and sla.lookup_field_id in (108, 2100, 2102, 2103, 2104) 


        if ($trainingRuleDetail['client_id']) {
            $whereCond .= " and (clt.ID in (" . $trainingRuleDetail['client_id'] . ")  )";
        }

        if ($trainingRuleDetail['site_id']) {
            $whereCond .= " and (site.ID in (" . $trainingRuleDetail['site_id'] . ")  )";
        }




        $selectQuery = "SELECT clt.ID clientId,clt.name clientName,site.ID siteId,site.name siteName,users.Id userId,users.name userName 
FROM users
INNER JOIN lookup_answers ON lookup_answers.foreign_id = users.id AND lookup_answers.lookup_field_id in (107) AND lookup_answers.table_assoc = 'users'
INNER JOIN lookup_answers sla on sla.foreign_id = users.id and sla.lookup_field_id = '$division_id'
LEFT JOIN associations siteass ON siteass.child_user_id = users.id AND siteass.association_type_id = 4
LEFT JOIN associations cltass ON cltass.child_user_id = siteass.parent_user_id AND cltass.association_type_id = 1
LEFT JOIN users site ON site.ID = siteass.parent_user_id
LEFT JOIN users clt ON clt.ID = cltass.parent_user_id
WHERE $whereCond
GROUP BY users.id";

//    echo $selectQuery;
//    die;
        //prd($selectQuery);

        $userList = array();
        if ($result = $this->dbi->query($selectQuery)) {
            while ($myrow = $result->fetch_assoc()) {
                //prd($myrow);
                $userList[] = $myrow;
                //prd($userList);
            }
        }
        return $userList;
    }

    function trainingRuleDetail($id) {
        $sql = "select tac.division_id,tar.* "
                . " from training_allied_rules tar"
                . " left join training_allied_courses tac on tac.id = tar.course_id "
                . " where tar.id = $id";

        $result = $this->dbi->query($sql);

        if ($tarDetail = $result->fetch_assoc()) {
            //prd($tarDetail);
            return $tarDetail;
        }
        return "0";
    }

    function trainingRuleDetailWithName($id) {
        $sql = "SELECT tar.id,clt.id clientId,concat(clt.name,'',clt.surname) clt_name,site.id siteId,concat(site.name,'',site.surname) site_name,tar.training_title,ta_courses.`id` course_id, ta_courses.`title` course_name,division.`item_name` division_name,tar.start_date,tar.end_date";

        $sql .= " FROM training_allied_rules tar"
                . " left join users clt on clt.id = tar.client_id"
                . " left join users site on site.id = tar.site_id"
                . " LEFT JOIN training_allied_courses ta_courses ON ta_courses.id=tar.course_id  "
                . " LEFT JOIN training_allied_frequency ta_frequency ON ta_frequency.id=ta_courses.id  "
                . " LEFT JOIN companies division ON division.id=ta_courses.division_id "
                . " WHERE tar.id = '" . $id . "'";

        $result = $this->dbi->query($sql);

        if ($tarDetail = $result->fetch_assoc()) {
            //prd($tarDetail);
            return $tarDetail;
        }
        return "0";
    }

    function trainingBeforeAccess($userId) {

        $query = "SELECT usr.ID AS user_id,CONCAT(usr.name, ' ', usr.surname) employee_name,tatu.id trainingId, tatu.start_date startDate,tatu.end_date endDate,tac.id course_id,division.`item_name` division_name,tar.training_title trainingTitle,tal.id lessonId,tal.title lesson_title,tac.id courseId, tac.title course_title,tatu.is_result,tatu.total_question,tatu.correct_answer,IF(tatu.total_question = 0,0,IF(tatu.total_question = tatu.correct_answer,2,1)) AS test_status
FROM users usr
INNER JOIN training_allied_training_user tatu ON tatu.user_id = usr.ID and tatu.is_result != 1
INNER JOIN training_allied_rules tar ON tar.id = tatu.training_rule_id 
LEFT JOIN training_allied_lessons tal ON tal.id = tatu.lesson_id 
LEFT JOIN training_allied_courses tac ON tac.id = tal.course_id 
LEFT JOIN companies division ON division.id=tac.division_id
WHERE  usr.id = '$userId' and tatu.is_result != 1 and tac.status = 1  AND tal.status = 1 AND ((tac.is_required = 1 and tar.start_date <= CURDATE()) or   tar.end_date <= CURDATE())";
//        echo $query;
//        die;
        $result = $this->dbi->query($query);

        if ($tarDetail = $result->fetch_assoc()) {
            //prd($tarDetail);
            return 1;
        }
        return "0";
    }

    /*
     * 
     */

    function getRosterLeaveTypes() {
        $sql = "select id,leave_title from roster_leave_types where status = 1";
        $leaveTypeList = array();
        if ($result = $this->dbi->query($sql)) {
            while ($myrow = $result->fetch_assoc()) {
                $leaveTypeList[$myrow['id']] = $myrow['leave_title'];
            }
        }
        return $leaveTypeList;
    }

    function notiTrainingUser() {
        $query = "select users.id user_id,users.name,users.phone,users.phone2,users.email
                  from training_allied_training_user tatu
                  left join users on users.id = tatu.user_id 
                  where tatu.notification_send = 0 and tatu.start_date <= CURDATE() group by users.id";
//        echo $query;
//        die;
        $result = $this->dbi->query($query);
        $userDetail = array();
        while ($tarDetail = $result->fetch_assoc()) {
            //prd($tarDetail);
            $userDetail[] = $tarDetail;
        }
        return $userDetail;
    }

    function notiToolboxUser() {
        $query = "select users.id user_id,users.name,users.phone,users.phone2,users.email,tac.title,tal.description
                  from toolbox_allied_user tau
                  left join users on users.id = tau.user_id 
                  left join toolbox_allied_courses tac on tac.id = tau.course_id
                  left join toolbox_allied_lessons tal on tal.id = tau.lesson_id                  
                  where tau.notification_send = 0 and tac.start_date <= CURDATE() and users.email is not null group by tau.id";

        $result = $this->dbi->query($query);
        $userDetail = array();
        while ($tarDetail = $result->fetch_assoc()) {
            //prd($tarDetail);
            $userDetail[] = $tarDetail;
        }
        return $userDetail;
    }

    function userDetail($userId) {
        $query = "select *,states.item_name userstate 
                  from users
                  left join states on states.id = users.state
                  where users.id = '$userId'";
        $result = $this->dbi->query($query);
        $userDetail = 0;
        while ($tarDetail = $result->fetch_assoc()) {
            $userDetail = $tarDetail;
        }
        return $userDetail;
    }

    function reportSiteDetail($ccheckId) {
        $query = "select users.*,states.item_name userstate 
                  from compliance_checks ccheck
                  left join users on users.id = ccheck.subject_id
                  left join states on states.id = users.state
                  where ccheck.id = '$ccheckId'";
        $result = $this->dbi->query($query);
        $userDetail = 0;
        while ($tarDetail = $result->fetch_assoc()) {
            $userDetail = $tarDetail;
        }
        return $userDetail;
    }

    function whiteBoardSiteDetail($siteNotesId) {
        $query = "select users.*,states.item_name userstate 
                  from site_notes
                  left join users on users.id = site_notes.site_id
                  left join states on states.id = users.state
                  where site_notes.id = '$siteNotesId'";
        $result = $this->dbi->query($query);
        $userDetail = 0;
        while ($tarDetail = $result->fetch_assoc()) {
            $userDetail = $tarDetail;
        }
        return $userDetail;
    }

    function occuranceLogSiteDetail($siteNotesId) {
        $query = "select users.*,states.item_name userstate 
                  from occurrence_log
                  left join users on users.id = occurrence_log.site_id
                  left join states on states.id = users.state
                  where occurrence_log.id = '$siteNotesId'";
        $result = $this->dbi->query($query);
        $userDetail = 0;
        while ($tarDetail = $result->fetch_assoc()) {
            $userDetail = $tarDetail;
        }
        return $userDetail;
    }

    function notiTrainingUserSendStatus($user_id) {
        $updateQuery = " update training_allied_training_user "
                . " set notification_send = '1' "
                . " where notification_send = 0 and start_date <= CURDATE() and user_id = '" . $user_id . "'";

        $result = $this->dbi->query($updateQuery);
    }

    function notiToolboxUserSendStatus($toolBoxId) {
        $updateQuery = " update toolbox_allied_user "
                . " set notification_send = '1' "
                . " where id = '" . $toolBoxId . "' and notification_send = 0 and start_date <= CURDATE() ";

        $result = $this->dbi->query($updateQuery);
    }

    /* for Toolbox user */

    function toolboxCourseDetail($course_id) {
        $listQuery = "SELECT division_id FROM toolbox_allied_courses ta_courses WHERE ta_courses.id = " . $course_id;
        $record_list = $this->db->exec($listQuery);
        return $record_list[0];
    }

    function assignedToolboxUser($course_id) {
        $courseDetailed = $this->toolboxCourseDetail($course_id);
        $divisionId = $courseDetailed['division_id'];
        /*
         * 
          LEFT JOIN associations siteass ON siteass.child_user_id = users.id AND siteass.association_type_id = 4
          LEFT JOIN associations cltass ON cltass.child_user_id = siteass.parent_user_id AND cltass.association_type_id = 1
          LEFT JOIN users site ON site.ID = siteass.parent_user_id
          LEFT JOIN users clt ON clt.ID = cltass.parent_user_id
         */

        $notAssignedUser = "SELECT users.ID
FROM users
INNER JOIN lookup_answers ON lookup_answers.foreign_id = users.id AND lookup_answers.lookup_field_id in (107) AND lookup_answers.table_assoc = 'users'
INNER JOIN lookup_answers sla on sla.foreign_id = users.id and sla.lookup_field_id = '$divisionId'
LEFT JOIN toolbox_allied_user tau on tau.course_id = $course_id and tau.user_id = users.id  
WHERE tau.id IS NULL AND users.user_status_id = 30
GROUP BY users.id";

        $userLists = $this->db->exec($notAssignedUser);

        $lessonList = $this->toolboxLessonList($course_id);

        // prd($userLists);

        foreach ($lessonList as $lesson) {


            $insertRec = "";
            foreach ($userLists as $key => $userList) {
                // prd($key);
                if ($key == 0) {
                    $commaAdd = " ";
                } else {
                    $commaAdd = ",";
                }
                $insertRec .= $commaAdd . '("' . $course_id . '","' . $lesson['id'] . '","' . $userList['ID'] . '")';
            }
            if ($userLists) {
                $insertQuery = "INSERT IGNORE INTO toolbox_allied_user 
                    (course_id, lesson_id, user_id) 
                    VALUES 
                    $insertRec";
            }
            $this->db->exec($insertQuery);
        }


        //$userNotAssigned = 
    }

    /*
     * 
     */

    function getSiteReceivedOccuranceLog($days = 1) {
        $query = "select occurrence_log.site_id,CONCAT(users.name, ' ', users.surname) site_name, users.site_contact_name1,users.site_contact_email1,users.site_contact_name2,users.site_contact_email2,users.site_contact_name3,users.site_contact_email3
from occurrence_log 
left join users on users.id = occurrence_log.site_id 
where DATEDIFF(DATE(occurrence_log.date),CURDATE()) = $days 
GROUP BY occurrence_log.site_id
order by occurrence_log.date";

        $result = $this->dbi->query($query);
        $userDetail = array();
        while ($tarDetail = $result->fetch_assoc()) {
            //prd($tarDetail);
            $userDetail[] = $tarDetail;
        }
        return $userDetail;
    }

    function createSiteOccurancePdf($siteId, $days) {


        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->setAutoPageBreak(false);
        //$days = 1;
        $date_by = date("Y-m-d", strtotime("$days days"));
        $date_by_show = date("l, d/M/Y", strtotime("$days days"));

        // $srch =  " and DATE(occurrence_log.date) = '$date_by'";


        $sql = "select CONCAT(users2.name, ' ', users2.surname) as `result` from occurrence_log left join users2 on users2.id = occurrence_log.site_id"
                . " where occurrence_log.site_id = " . $siteId . " and DATEDIFF(DATE(occurrence_log.date),CURDATE()) = " . $days;

//            echo $sql;
//            die;
        //$date_by_show = date("$days, d/M/Y");
        // echo $date_by_show;
        //die;

        $heading = "Occurrence Log \r\n" . $this->get_sql_result($sql) . "\r\n" . $date_by_show;

        $this->pdf_header($pdf, $heading);

        $line_height = 5.5;
        $cell_width = 190;
        $startx = 10;
        $starty = 30;

        $pic_right = 95;

        $break_point = 285;

        $startx_indent = 15;
        $cell_width_indent = $cell_width - ($startx_indent - $startx);
        $image_height = 50;

        $sql = "select occurrence_log.id as `site_id`, CONCAT(DATE_FORMAT(occurrence_log.date, '%a %d-%b-%Y at %H:%i')) as `date`, occurrence_log.date as `test_date`,
                  occurrence_log.division_id,
                   CONCAT(users2.name, ' ', users2.surname) as `site`, users2.id as `location_id`, occurrence_log.user_id,
                   CONCAT(users.name, ' ', users.surname) as `added_by`, users.id as `added_by_id`,
                   occurrence_log.description as `description`
                   from occurrence_log
                   left join users on users.id = occurrence_log.user_id
                   left join users2 on users2.id = occurrence_log.site_id
                   where occurrence_log.site_id = $siteId and DATEDIFF(DATE(occurrence_log.date),CURDATE()) = " . $days . " order by occurrence_log.date";

//            echo $sql;
//            die;
//. " or $table." . $this->table_id_name . " = 0"
        //$pdf->SetXY(130, 30); 	$pdf->MultiCell(50,6, $sql,0);

        $oldy = $pdf->GetY();
        $pdf->SetXY($startx, $starty);
        $pdf->MultiCell($cell_width, 0, "", 0);

        $result = $this->dbi->query($sql);

        while ($myrow = $result->fetch_assoc()) {
            $site = $myrow['site'];
            $idin = $myrow['site_id'];
            $added_by_id = $myrow['added_by_id'];
            $site_id = $myrow['location_id'];
            $date = $myrow['date'];
            $test_date = $myrow['test_date'];
            $added_by = $myrow['added_by'];
            $site = $myrow['site'];
            $description = $myrow['description'];
            $edit = $myrow['edit'];
            $added_user_id = $myrow['user_id'];

            $div_id = $myrow['division_id'];
            if ($div_id == 0) {
                if ($div_str = $this->get_divisions($added_user_id))
                    $div_str = " ($div_str) ";
            } else {
                $div_str = " (" . $this->get_sql_result("select SUBSTRING(item_name, 1, 3) as `result` from companies where id = $div_id") . ")";
            }

            $added_by = $added_by . $div_str . ' wrote on ' . $date;

            $yl = $pdf->GetY() + $height_add;
            if ($yl > $break_point) {
                $this->pdf_header($pdf, $heading);
                $yl = $starty;
            }
            $pdf->SetTextColor(0, 90, 0);
            $pdf->SetXY($startx, $yl);
            $pdf->MultiCell($cell_width, 6, $added_by, 0);
            $pdf->SetTextColor(30, 30, 30);

            $items = explode("\r\n", $description);
            foreach ($items as $item) {
                if (trim($item)) {
                    $yl = $pdf->GetY();
                    if ($yl > $break_point) {
                        $this->pdf_header($pdf, $heading);
                        $yl = $starty;
                    }
                    $pdf->SetXY($startx, $yl);
                    $pdf->MultiCell($cell_width, 6, utf8_decode($item), 0);
                }
            }

            //$str .=  '<div class="cl"></div>'.$description.'</div>';

            $sql = "
                select
                occurrence_log_comments.id as idin,
                occurrence_log_comments.date_added as `added_on`,
                CONCAT(users.name, ' ', users.surname) as `added_by`, users.id as `added_by_id`,
                occurrence_log_comments.description as `comment`
                from occurrence_log_comments
                inner join users on users.id = occurrence_log_comments.user_id
                where occurrence_log_id = $idin
                order by occurrence_log_comments.date_added";

            $yl = $pdf->GetY();

            //$pdf->SetXY($startx, $yl); 	$pdf->MultiCell($cell_width,6, "test: " . $this->table_id_name, 0);


            if ($result2 = $this->dbi->query($sql)) {
                while ($myrow2 = $result2->fetch_assoc()) {
                    $id_added = $myrow2['idin'];
                    $added_by_id = $myrow2['added_by_id'];
                    $added_on = Date("d-M-Y", strtotime($myrow2['added_on']));
                    $added_time = Date("H:i", strtotime($myrow2['added_on']));
                    $added_by = $myrow2['added_by'];
                    $comment = $myrow2['comment'];

                    $added_by = "$added_by replied on $added_on at $added_time";
                    $yl = $pdf->GetY();
                    if ($yl > $break_point) {
                        $this->pdf_header($pdf, $heading);
                        $yl = $starty;
                    }
                    $pdf->SetTextColor(0, 90, 0);
                    $pdf->SetXY($startx_indent, $yl);
                    $pdf->MultiCell($cell_width_indent, 6, $added_by, 0);
                    $pdf->SetTextColor(30, 30, 30);
                    $yl = $pdf->GetY();
                    if ($yl > $break_point) {
                        $this->pdf_header($pdf, $heading);
                        $yl = $starty;
                    }
                    $pdf->SetXY($startx_indent, $yl);
                    $pdf->MultiCell($cell_width_indent, 6, utf8_decode($comment), 0);

                    //$str .=  "<div class=\"log_entry comment\"><div class=\"fl comment_head\">$added_by wrote on $added_on at $added_time</div>";
                    //$str .=  "<div class=\"cl\"></div>$comment</div>";
                }
            }
            $target_dir = $this->f3->get('download_folder') . "occurrence_log_images";

            /* $str .= ($this->show_cam ? '<div class=\"cl\"></div><div onClick="show_hide_img('.$idin.',\''.$target_dir.'\');" class="img_button" id="img_button'.$idin.'"></div><div class="img_label">Upload Images &gt;&gt;</div><div class="cl"></div><div class="img_uploader" id="img_uploader'.$idin.'">
              <iframe id="photo_upload'.$idin.'" frameborder="0" width="100%" height="500px;"></iframe>
              </div><div class="cl"></div>' : '');
             */
            //$str .= "<p>$target_dir/$idin/*</p>";
            $height_add = 0;
            if (count(glob("$target_dir/$idin/*"))) {
                $dir = new DirectoryIterator("$target_dir/$idin/");
                $x = 0;
                $file_list = Array();
                foreach ($dir as $fileinfo) {
                    if (!$fileinfo->isDir() && !$fileinfo->isDot()) {
                        $x++;
                        $yl = $pdf->GetY() + $height_add;

                        if ($yl > $break_point - $image_height) {
                            $this->pdf_header($pdf, $heading);
                            $yl = $starty;
                        }

                        $pdf->SetXY($startx, $yl);
                        //die("test");
                        //$pdf->MultiCell(70,6, $x, 0);
                        $pdf->Image("$target_dir/$idin/" . $fileinfo->getFilename(), $startx + (!($x % 2) ? $pic_right : 0), $yl, 0, $image_height);
                        //$this->waterMarkImageText("$target_dir/$idin/" . $fileinfo->getFilename());
                        //$yl += $image_height;
                        $height_add = + (($x % 2) ? 0 : $image_height);

                        //if($x == 1) $str .= "<p style=\"font-weight: bold;\">Image Attachments</p>";
                        //$str .= '<img style="width:49%;" src="' . $this->f3->get('full_url') . 'Image?i=' . urlencode($this->encrypt($fileinfo->getFilename())) . '&alt_flder=' . urlencode("$target_dir/$idin/") . '" />';
                    }
                }//
                $x++;
                $height_add += (($x % 2) ? 0 : $image_height);
            }
        }
        $base_url = $this->f3->get('base_folder') . '/edge/downloads/occurance_log/pdf_files/';
        // mkdir
        if (!file_exists($base_url)) {
            mkdir($base_url, 0777, true);
        }
        $fileName = $base_url . 'occurancelog_' . $date_by . "_" . $siteId . '.pdf';
        $pdf->Output($fileName, 'F');

        //$file_path_name = $base_url . $job_pdf_filename;
    }

    function waterMarkImage() {


        $originalImagePath = 'D:\wamp64\www\laravel\alliedge_laravel_dev\app\images\heavy_rain.jpg';
        $watermarkImagePath = 'D:\wamp64\www\laravel\alliedge_laravel_dev\app\images\logo.png';

// Load the original image
        $originalImage = imagecreatefromjpeg($originalImagePath);

// Load the watermark image with transparency
        $watermarkImage = imagecreatefrompng($watermarkImagePath);

// Get the dimensions of the original image and watermark image
        $originalWidth = imagesx($originalImage);
        $originalHeight = imagesy($originalImage);
        $watermarkWidth = imagesx($watermarkImage);
        $watermarkHeight = imagesy($watermarkImage);

        // Define the watermark size as a percentage of the original image size (e.g., 20%)
        $watermarkPercentage = 80; // Adjust this percentage as needed
        // Calculate the new dimensions for the watermark
        $newWatermarkWidth = $watermarkWidth; //($originalWidth * $watermarkPercentage) / 100;
        $newWatermarkHeight = $watermarkHeight; //($originalHeight * $watermarkPercentage) / 100;
// Calculate the position to place the watermark (e.g., bottom-right corner)
        //$positionX = $originalWidth - $watermarkWidth - 10; // Adjust the values as needed
        //$positionY = $originalHeight - $watermarkHeight - 10; // Adjust the values as needed

        $positionX = ($originalWidth - $newWatermarkWidth) / 2; // Adjust the values as needed
        $positionY = ($originalHeight - $newWatermarkHeight) / 2; // Adjust the values as needed
        // Resize the watermark image to the new dimensions
        $resizedWatermark = imagecreatetruecolor($newWatermarkWidth, $newWatermarkHeight);
        imagecopyresampled($resizedWatermark, $watermarkImage, 0, 0, 0, 0, $newWatermarkWidth, $newWatermarkHeight, $watermarkWidth, $watermarkHeight);

        $opacity = 100; // Adjust this value for the desired opacity level (0 to 100)
// Merge the watermark with the original image using the specified opacity
        imagecopymerge($originalImage, $resizedWatermark, $positionX, $positionY, 0, 0, $newWatermarkWidth, $newWatermarkHeight, $opacity);

// Save or output the watermarked image (e.g., display on the web)
        header('Content-Type: image/jpeg'); // Change the content type if you're saving to a file
        imagejpeg($originalImage);

// Clean up resources
        imagedestroy($originalImage);
        imagedestroy($resizedWatermark);
        imagedestroy($watermarkImage);
    }

    function waterMarkImageText($originalImagePath = "", $text1 = "", $text2 = "", $text3 = "", $text4 = "", $text5 = "") {
        if ($originalImagePath == "") {
            $originalImagePath = $this->f3->get('base_folder') . 'app\images\cpr-training-test.jpg';
        }
//        echo "mm".$originalImagePath;
//        die;
        $fontFile = $this->f3->get('base_folder') . 'app/font/fabrik.ttf';

// Path to your original image
// Load the original image
        $originalImage = imagecreatefromjpeg($originalImagePath);

// Define the font settings for the watermark
        // $fontColor = imagecolorallocate($originalImage, 0, 0, 0); // Text color (white in this example)

        $fontColor = imagecolorallocate($originalImage, 255, 255, 255); // Text color (white in this example)
        $borderColor = imagecolorallocate($originalImage, 0, 0, 0); // Border color (black)
// Define the watermark text
        // $watermarkText = 'Your Watermark Text'; // Replace with your watermark text
// Get the dimensions of the original image
        $originalWidth = imagesx($originalImage);
        $originalHeight = imagesy($originalImage);

        if ($originalWidth > 300) {
            $fontSize = 24; // Font size
        } else if ($originalWidth > 200) {
            $fontSize = 14; // Font size
        } else {

            $fontSize = 9;
        }
        // Calculate the width of each character
        $charWidth = $fontSize / 1.5; // Adjust as needed
        //echo $originalWidth."</br>";
        //echo $originalHeight;
        //die;
// Split the watermark text into lines
        //$lines = explode("\n", $watermarkText);
        $lines[] = $this->stateWiseDateTime($text3); //('d M Y H:i:s');
        if ($text1 != "") {
            $lines[] = $text1;
        }
        if ($text2 != "") {
            $lines[] = $text2;
        }
        if ($text3 != "") {
            $lines[] = $text3;
        }
        $lines[] = 'Australia';
        if ($text4 != "") {
            $lines[] = $text4;
        }
        $positionY = 0;
// Loop through each line and add it as a watermark
        $shadowColor = imagecolorallocate($originalImage, 0, 0, 0); // Shadow color (black)
        foreach ($lines as $line) {
            // Calculate the position to place the text in the top-right corner
            $textBoundingBox = imagettfbbox($fontSize, 0, $fontFile, $line);
            $textWidth = $textBoundingBox[2] - $textBoundingBox[0];
            $positionX = $originalWidth - $textWidth - 10; // Adjust the margin as needed
            $positionY += $fontSize + 1; // Adjust spacing as needed
            // Add the text watermark to the original image
            imagettftext($originalImage, $fontSize, 0, $positionX + 3, $positionY + 3, $shadowColor, $fontFile, $line);
            imagettftext($originalImage, $fontSize, 0, $positionX, $positionY, $fontColor, $fontFile, $line);

            // Adjust the Y position for the next line
        }

        /* image add watermark */

        $watermarkImagePath = $this->f3->get('base_folder') . 'app/images/logo.png';

// Load the original image
// Load the watermark image with transparency
        $watermarkImage = imagecreatefrompng($watermarkImagePath);

// Get the dimensions of the original image and watermark image
        $originalWidth = imagesx($originalImage);
        $originalHeight = imagesy($originalImage);

        $watermarkWidth = imagesx($watermarkImage);
        $watermarkHeight = imagesy($watermarkImage);
        $watermarkImagePath = $this->f3->get('base_folder') . 'app/images/logo.png';
        if ($originalWidth > 400) {
            $newWatermarkWidth = ($watermarkWidth * 99) / 100;
            $newWatermarkHeight = ($watermarkHeight * 99) / 100;
            $positionX = $originalWidth - $watermarkWidth - 10; // Adjust the values as needed
            $positionY = $originalHeight - $watermarkHeight - 20;
        } else if ($originalWidth > 300) {
            $newWatermarkWidth = ($watermarkWidth * 70) / 100;
            $newWatermarkHeight = ($watermarkHeight * 70) / 100; // Adjust this percentage as needed

            $positionX = $originalWidth - $watermarkWidth + 80; // Adjust the values as needed
            $positionY = $originalHeight - $watermarkHeight + 20;
        } else if ($originalWidth > 200) {
            $newWatermarkWidth = ($watermarkWidth * 50) / 100;
            $newWatermarkHeight = ($watermarkHeight * 50) / 100; // Adjust this percentage as needed

            $positionX = $originalWidth - $watermarkWidth + 134; // Adjust the values as needed
            $positionY = $originalHeight - $watermarkHeight + 37;
        } else {

            $newWatermarkWidth = ($watermarkWidth * 30) / 100;
            $newWatermarkHeight = ($watermarkHeight * 30) / 100;

            // $newWatermarkWidth = $watermarkWidth; //($originalWidth * $watermarkPercentage) / 100;
            //$newWatermarkHeight = $watermarkHeight;


            $positionX = $originalWidth - $watermarkWidth + 190; // Adjust the values as needed
            $positionY = $originalHeight - $watermarkHeight + 51;
        }



// Load the original image
// Load the watermark image with transparency
        $watermarkImage = imagecreatefrompng($watermarkImagePath);

        // Define the watermark size as a percentage of the original image size (e.g., 20%)
        // $watermarkPercentage = 80; // Adjust this percentage as needed
        // Calculate the new dimensions for the watermark
        // $newWatermarkWidth = $watermarkWidth; //($originalWidth * $watermarkPercentage) / 100;
        // $newWatermarkHeight = $watermarkHeight; //($originalHeight * $watermarkPercentage) / 100;
// Calculate the position to place the watermark (e.g., bottom-right corner)
        // Adjust the values as needed
        //$positionX = ($originalWidth - $newWatermarkWidth) / 2; // Adjust the values as needed
        //$positionY = ($originalHeight - $newWatermarkHeight) / 2; // Adjust the values as needed
        // Resize the watermark image to the new dimensions
        $resizedWatermark = imagecreatetruecolor($newWatermarkWidth, $newWatermarkHeight);
        $transparentColor = imagecolorallocatealpha($resizedWatermark, 0, 0, 0, 127);
        imagealphablending($resizedWatermark, false);

// Set the alpha flag to enable saving as a PNG with transparency
        imagesavealpha($resizedWatermark, true);

// Fill the image with the transparent color
        imagefill($resizedWatermark, 0, 0, $transparentColor);

        imagecopyresampled($resizedWatermark, $watermarkImage, 0, 0, 0, 0, $newWatermarkWidth, $newWatermarkHeight, $watermarkWidth, $watermarkHeight);

        $opacity = 100; // Adjust this value for the desired opacity level (0 to 100)
// Merge the watermark with the original image using the specified opacity
        imagecopy($originalImage, $resizedWatermark, $positionX, $positionY, 0, 0, $newWatermarkWidth, $newWatermarkHeight);

// Save or output the watermarked image (e.g., display on the web)
        //header('Content-Type: image/jpeg'); // Change the content type if you're saving to a file
        imagejpeg($originalImage, $originalImagePath);
        //imagejpeg($originalImage);
// Clean up resources
        @imagedestroy($originalImage);
        @imagedestroy($resizedWatermark);
        @imagedestroy($watermarkImage);

// Save or output the watermarked image (e.g., display on the web)
//        header('Content-Type: image/jpeg'); // Change the content type if you're saving to a file
//        imagejpeg($originalImage);
//
//// Clean up resources
        //imagedestroy($originalImage);
    }

    function stateWiseDateTime($text3) {

        switch ($text3) {
            case 'ACT':
                $stateTimeZone = "Australia/Sydney";
                break;
            case 'NSW':
                $stateTimeZone = "Australia/Sydney";
                break;
            case 'NT':
                $stateTimeZone = "Australia/Darwin";
                break;
            case 'QLD':
                $stateTimeZone = "Australia/Brisbane";
                break;
            case 'SA':
                $stateTimeZone = "Australia/Adelaide";
                break;
            case 'TAS':
                $stateTimeZone = "Australia/Hobart";
                break;
            case 'VIC':
                $stateTimeZone = "Australia/Melbourne";
                break;
            case 'WA':
                $stateTimeZone = "Australia/Perth";
                break;
            case 'NZ':
                $stateTimeZone = "Pacific/Auckland";
                break;
            default :
                $stateTimeZone = "Australia/Sydney";
                break;
        }
        $timezone = new DateTimeZone($stateTimeZone); // NSW time zone
        // Create a DateTime object with the specified time zone
        $date = new DateTime('now', $timezone);

        // Format the date and time as per your requirements
        $formatted_date = $date->format('d M Y H:i:s');
        return $formatted_date;
        //('d M Y H:i:s');
    }

    function waterMarkText($positiion = 0, $text1 = "", $text2 = "", $text3 = "", $text4 = "", $text5 = "") {
        $originalImagePath = 'D:\wamp64\www\laravel\alliedge_laravel_dev\app\images\integrated.jpg';
        $fontFile = 'D:\wamp64\www\laravel\alliedge_laravel_dev\app\font/fabrik.ttf';

// Path to your original image
// Load the original image
        $originalImage = imagecreatefromjpeg($originalImagePath);

// Define the font settings for the watermark

        $fontSize = 24; // Font size
        $fontColor = imagecolorallocate($originalImage, 0, 0, 0); // Text color (white in this example)
// Define the watermark text
        $watermarkText = 'Your Watermark Text'; // Replace with your watermark text
// Get the dimensions of the original image
        $originalWidth = imagesx($originalImage);
        $originalHeight = imagesy($originalImage);

// Split the watermark text into lines
        $lines = explode("\n", $watermarkText);
        $lines = [
            'Line 1 of Watermark',
            '2 of Watermark',
            'of Watermark',
        ];
        $positionY = 0;
// Loop through each line and add it as a watermark
        foreach ($lines as $line) {
            // Calculate the position to place the text in the top-right corner
            $textBoundingBox = imagettfbbox($fontSize, 0, $fontFile, $line);
            $textWidth = $textBoundingBox[2] - $textBoundingBox[0];
            $positionX = $originalWidth - $textWidth - 10; // Adjust the margin as needed
            $positionY += $fontSize + 5; // Adjust spacing as needed
            // Add the text watermark to the original image
            imagettftext($originalImage, $fontSize, 0, $positionX, $positionY, $fontColor, $fontFile, $line);

            // Adjust the Y position for the next line
        }

// Save or output the watermarked image (e.g., display on the web)
        // header('Content-Type: image/jpeg'); // Change the content type if you're saving to a file
        imagejpeg($originalImage, $originalImagePath);

// Clean up resources
        imagedestroy($originalImage);
    }

    function waterMarkText1($positiion = 0, $text1 = "", $text2 = "", $text3 = "", $text4 = "", $text5 = "") {
        $originalImagePath = 'D:\wamp64\www\laravel\alliedge_laravel_dev\app\images\integrated.jpg';
// Load the original image
        $originalImage = imagecreatefromjpeg($originalImagePath);
//
//// Define the text and font settings for the watermark
//        $watermarkText = 'Your Watermark Text'; // Replace with your watermark text
//        $fontFile = 'D:\wamp64\www\laravel\alliedge_laravel_dev\app\font/fabrik.ttf'; // Replace with the path to a TrueType font file
//        //$fontSize = 24; // Font size
//// Set the color of the text (white in this example)
//        // Load the original image
//        $originalImage = imagecreatefromjpeg($originalImagePath);
// Define the text and font settings for the watermark
        $watermarkText = 'Your Watermark Text'
                . ' adfad afasd a'; // Replace with your watermark text

        $lines = [
            'Line 1 of Watermark',
            '2 of Watermark',
            'of Watermark',
        ];
        $fontFile = 'D:\wamp64\www\laravel\alliedge_laravel_dev\app\font/fabrik.ttf';
        $textPercentage = 10; // Adjust this percentage for the desired text size relative to the image width
// Set the color of the text (white in this example)
// $fontColor = imagecolorallocate($originalImage, 241, 240, 234);
        $fontColor = imagecolorallocate($originalImage, 0, 0, 0);

/// Get the dimensions of the original image
        $originalWidth = imagesx($originalImage);
        $originalHeight = imagesy($originalImage);

// Calculate the font size relative to the image width
        $fontSize = 16; //($originalWidth * $textPercentage) / 50;
// Calculate the position to place the text in the top-right corner
//        $textBoundingBox = imagettfbbox($fontSize, 0, $fontFile, $watermarkText);
//        $textWidth = $textBoundingBox[2] - $textBoundingBox[0];
//        $textHeight = $textBoundingBox[1] - $textBoundingBox[7];
//        $positionX = $originalWidth - $textWidth + 100; // Adjust the margin as needed
//        $positionY = $textHeight+30; // Adjust the margin as needed
// Add the text watermark to the original image

        foreach ($lines as $line) {
//imagettftext($originalImage, $fontSize, 0, $positionX, $positionY, $fontColor, $fontFile, $line);

            $textBoundingBox = imagettfbbox($fontSize, 0, $fontFile, $watermarkText);
            $textWidth = $textBoundingBox[2] - $textBoundingBox[0];
            $textHeight = $textBoundingBox[1] - $textBoundingBox[7];
            $positionX = $originalWidth - $textWidth + 100; // Adjust the margin as needed
            $positionY += $fontSize + 5;

// Add the text watermark to the original image
            imagettftext($originalImage, $fontSize, 0, $positionX, $positionY, $fontColor, $fontFile, $line);

// Adjust the Y position for the next line
//$positionY += $fontSize + 20; // Adjust spacing as needed
// Adjust the Y position for the next line
//$positionY += $fontSize + 5; // Adjust spacing as needed
        }




// imagettftext($originalImage, $fontSize, 0, $positionX, $positionY, $fontColor, $fontFile, $watermarkText);
// Save or output the watermarked image (e.g., display on the web)
        header('Content-Type: image/jpeg'); // Change the content type if you're saving to a file
        imagejpeg($originalImage);

// Clean up resources
        imagedestroy($originalImage);
    }

    function getAssetVehicleList($rostertTimeId) {
        $sql = 'SELECT asst.id,concat(asst.description,"(",asst.label_no,")") item_name
FROM assets asst
LEFT JOIN roster_times rtv on rtv.id = "' . $rostertTimeId . '"
LEFT JOIN rosters rst on rst.id = rtv.roster_id
WHERE asst.category_id = 135 and asst.division_id = rst.division_id
and asst.id NOT IN (SELECT distinct(rt.asset_vehicle_id) FROM roster_times rt 
LEFT JOIN roster_times rtself ON rtself.id = ' . $rostertTimeId . ' WHERE !rtself.asset_vehicle_id and (rt.id != "' . $rostertTimeId . '" and rt.asset_vehicle_id IS NOT NULL
AND ((rt.start_time_date >= rtv.start_time_date and rt.start_time_date <= rtv.finish_time_date) or (rt.finish_time_date >= rtv.start_time_date and rt.finish_time_date <= rtv.finish_time_date)))) order by asst.description';
//        echo $sql;
//        die;

        $vehicleList = array();
        if ($result = $this->dbi->query($sql)) {
            while ($myrow = $result->fetch_assoc()) {
                $vehicleList[] = $myrow;
            }
        }

        return $vehicleList;
    }

    /*
     * input roster time id     
     * Available and assign vehicli List
     * 
     */

    function assetVehicleDropDown($rostertTimeId, $rosterVehicleId) {
        $vehicleList = $this->getAssetVehicleList($rostertTimeId);
//prd($vehicleList);


        $str = "";

        $selVehicle = "<select name='roster_vehicle" . $rostertTimeId . "' id ='roster_vehicle" . $rostertTimeId . "'><option value=\"\"> Select Vehicle </option>";
        foreach ($vehicleList as $key => $svalue) {
            if ($rosterVehicleId == $svalue['id']) {
                $selected = "selected";
            } else {
                $selected = "";
            }
            $selVehicle .= '<option value="' . $svalue['id'] . '" ' . $selected . '>' . $svalue['item_name'] . '</option>';
        }
        $selVehicle .= '</select>';

        $str .= $selVehicle;

        return $str;
    }

    function rosterTimeDetail($rosterTimeId) {

        $sql = "select * "
                . " from roster_times rt"
                . " where rt.id = $rosterTimeId";

        $result = $this->dbi->query($sql);

        if ($rtrDetail = $result->fetch_assoc()) {
//prd($tarDetail);
            return $rtrDetail;
        }
        return 0;
    }

    function rosterTimeDetailByTimeStaffid($rosterTimeStaffId) {

        $sql = "select rt.roster_time_notes,rt.roster_time_notes_image "
                . " from roster_times rt"
                . " left join roster_times_staff rts on rts.roster_time_id = rt.id"
                . " where rts.id = $rosterTimeStaffId";

        $result = $this->dbi->query($sql);

        if ($rtrDetail = $result->fetch_assoc()) {
//prd($tarDetail);
            return $rtrDetail;
        }
        return 0;
    }

    function checkVehicleExistInOtherSlot($roster_time_id, $vehicleId, $rosterDate, $newStartDateTime, $newFinishDateTime) {



        $sql = "select count(rt.id) result from roster_times rt where id != '" . $roster_time_id . "' and asset_vehicle_id = '" . $vehicleId . "' "
                . " AND ((rt.start_time_date >= '" . $newStartDateTime . "' and rt.start_time_date <= '" . $newFinishDateTime . "') or (rt.finish_time_date >= '" . $newStartDateTime . "' and rt.finish_time_date <= '" . $newFinishDateTime . "'))";

        $result = $this->get_sql_result($sql);
        return $result;
    }

    function divisionManagerList($divisionId) {
        $query = "SELECT users.id as `id`,
CONCAT(users.id,'___',users.name,if(users.preferred_name != '', CONCAT(' (', users.preferred_name, ')'), ''), ' ', if(users.surname,users.surname,''),'___',if(users.email != '',users.email,''),'___',if(users.phone != '',users.phone,''),'') as `full_name`,CONCAT(users.name,if(users.preferred_name != '', CONCAT(' (', users.preferred_name, ')'), ''), ' ', if(users.surname,users.surname,'')) as `item_name`
FROM users 
left join states on states.id = users.state 
left join user_status on user_status.id = users.user_status_id 
LEFT JOIN lookup_answers sla2 ON sla2.foreign_id = users.id AND sla2.lookup_field_id = 384 AND sla2.table_assoc = 'users' 
LEFT JOIN lookup_answers sla3 ON sla3.foreign_id = users.id AND sla3.lookup_field_id = 107 AND sla3.table_assoc = 'users' 
inner join lookup_answers sla on sla.foreign_id = users.id and sla.lookup_field_id in (108, 2100, 2102, 2103, 2104) and sla.lookup_field_id = '" . $divisionId . "' 
inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id in (107) and lookup_answers.table_assoc = 'users'
left join lookup_answers slarole on slarole.foreign_id = users.id AND slarole.table_assoc = 'users'
inner JOIN lookup_fields lf ON (slarole.lookup_field_id = lf.id AND lf.lookup_id = 21 AND lf.sort_order >= 2000 AND lf.sort_order <= 10000)
where 1 and users.user_status_id = (select id from user_status where item_name = 'ACTIVE') 
and ((lookup_answers.lookup_field_id IN (384,107,105) AND sla.id IS NOT NULL)) 
and !(users.id in(1,2)) group by users.id order by users.name ;";
//return $query;
        $managerList = array();
        if ($result = $this->dbi->query($query)) {
            while ($myrow = $result->fetch_assoc()) {
                $managerList[] = $myrow;
            }
        }
        return $managerList;
    }

}

?>
