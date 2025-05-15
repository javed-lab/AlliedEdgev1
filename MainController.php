<?php

class MainController extends Controller {

    function beforeroute() {

        session_start();
        date_default_timezone_set('Australia/Sydney');
        $this->f3->set('css', 'main.css?v=2');
        $this->f3->set('year', Date('Y'));
        //$_SESSION['page_access'] = 1;

        if (!empty($_SESSION['timestamp']) && time() - $_SESSION['timestamp'] > 14400) {
            unset($_SESSION['lids']);
            unset($_SESSION['timestamp']);
            unset($_SESSION['user_last_log_id']);
            unset($_SESSION['user_last_log_check_date']);
            $this->f3->reroute('/Logout');
        } else {
            $_SESSION['timestamp'] = time();
            if ($_SESSION['user_id']) {
                if ($_SESSION['user_last_log_check_date'] != date('Y-m-d') || $_SESSION['user_last_log_id'] != $_SESSION['user_id']) {
                    $userLastLoginLog = $this->userLastLogUpdate($_SESSION['user_id']);
                }
                $this->userLastLoginUpdate($_SESSION['user_id']);
            }
        }

        $hchkim = (isset($_GET['hchkim']) ? $_GET['hchkim'] : null);
        $itm = new input_item;
        $this->f3->set('chkim', $itm->chk("chkim", "User Manager", ($hchkim == "on" ? "checked" : ""), "1", '', ''));

        if (!$_SESSION['u_level'])
            $_SESSION['u_level'] = 10;
        if ($_SESSION['lids'])
            $sql_xtra = "and main_pages2.id in (select foreign_id from lookup_answers where table_assoc = 'main_pages2' and lookup_field_id in (" . implode(",", $_SESSION['lids']) . "))";
        $page_sql = "
            select main_pages2.id as `id`, main_pages2.title as `item_name` from main_pages2
            left join page_types on page_types.id = main_pages2.page_type_id
            where parent_id != 0 and user_level_id <= " . $_SESSION['u_level'] . " and max_user_level_id >= " . $_SESSION['u_level'] . " and (page_types.item_name = 'SIDE') and url != '#' 
                and parent_id in (
                select id from main_pages2
                where page_type_id != 0 and page_type_id != 10000 and parent_id = 0 and user_level_id <= " . $_SESSION['u_level'] . " and max_user_level_id >= " . $_SESSION['u_level'] . " and (page_types.item_name = 'SIDE') $sql_xtra) 
            $sql_xtra
           ";

        //$this->f3->set('page_nav', $itm->cmb("cmbPageSelect", "", "placeholder=\"Select Page to Jump to\" class=\"uk-search-input search-field\" onChange=\"select_page()\"  ", "", $this->dbi, $page_sql, ""));
        //echo $page_sql;
        //die;
//prd($_SESSION['full_name']);

        if (!$_SESSION['user_id']) {
            $test = $_COOKIE['AnInescapableFact'];
            if ($test) {
                $user_logins = new Crud($this->db, 'user_logins');
                $login = new UserController;
                $user_logins->getByField('str', $test);
                if (!$user_logins->dry())
                    $login->authenticate($user_logins->user_id, 0);
            } else {
                $this->f3->reroute('/Login?page_from=' . urlencode($_SERVER["REQUEST_URI"]));
                exit;
            }
        }

        $loginUserId = $_SESSION['user_id'];

        $allow_access = 1;
        $this->f3->set('lids', $_SESSION['lids']);
        if ($_SESSION['lids']) {
            //prd($_SESSION['lids']);
            //$this->onLoginSetUserType($_SESSION['lids'],$_SESSION['user_id']);
            $this->f3->set('is_staff', (array_search(107, $this->f3->get('lids')) !== false) ? 1 : 0);
            $this->f3->set('is_security', (array_search(108, $this->f3->get('lids')) !== false) ? 1 : 0);
            $this->f3->set('hr_user', (array_search(461, $this->f3->get('lids')) !== false) ? 1 : 0);
            $this->f3->set('is_admin', (array_search(114, $this->f3->get('lids')) !== false) ? 1 : 0);
            $this->f3->set('is_client', (array_search(104, $this->f3->get('lids')) !== false) ? 1 : 0);
            $this->f3->set('is_site', (array_search(384, $this->f3->get('lids')) !== false) ? 1 : 0);
            $this->f3->set('is_client_staff', (array_search(504, $this->f3->get('lids')) !== false) ? 1 : 0);
            //$this->f3->set('is_', (array_search(2150, $this->f3->get('lids')) !== false) ? 1 : 0);
            //if(array_search(2150, $this->f3->get('lids')) !== false) { $this->f3->set('is_client', 1); $this->f3->set('is_site', 1); }
        } else {
            $this->f3->reroute('/Logout');
        }
        $tmp_str = $_SERVER["REQUEST_URI"];

        if ($tmp_str == '/Page/OccurrenceLog' || $tmp_str == '/Page/OccurrenceLog?show_min=') {
            
        } else {
            //  die("hello");
            unset($_SESSION['sticky_timestamp']);
        }



        $tmp_url = $tmp_str;
        if (strrpos($tmp_str, "?")) {
            $tmp_str = substr($tmp_str, 0, strrpos($tmp_str, "?"));
        }
        $curr_page = $tmp_str;
        if (!$curr_page)
            $curr_page = "./";
        $this->f3->set('tmp_url', $tmp_url);
        $this->f3->set('curr_page', $curr_page);

        $this->f3->set('curr_method', $this->f3->get('PARAMS.p1'));

        $page_name = substr($curr_page, strlen($this->f3->get('main_folder')));


        $sqlLogin = "select ID from users where (term_condition_accept = '0' or term_condition_accept = '') and ID = '$loginUserId';";

       

        if ($result = $this->dbi->query($sqlLogin)) {

            
            if ($result->num_rows > 0 && $page_name != 'TermConditionAccept') {
                echo $this->redirect($this->f3->get('main_folder') . "TermConditionAccept");
            }
            if ($result->num_rows == 0 && $page_name == 'TermConditionAccept') {

                echo $this->redirect($this->f3->get('main_folder'));
            }
        }


        $page_str_len = strlen($page_name);

        $detect = new Mobile_Detect;
        $this->f3->set('is_mobile', ($detect->isMobile() ? 1 : 0));
        $this->f3->set('is_tablet', ($detect->isTablet() ? 1 : 0));

        //$_SESSION['side_menu'] = "";

        if ($page_name != 'MyAccount' && $page_name != 'AccountDetails') {
            $sql = "select id from users where id = " . $_SESSION['user_id'] . " and pw = '" . $this->ed_crypt($_SESSION['username'], $_SESSION['user_id']) . "';";
//            if ($result = $this->dbi->query($sql)) {
//                if ($result->num_rows == 1) {
//                    echo $this->redirect($this->f3->get('main_folder') . "MyAccount?show_warning=1");
//                }
//            }
            /*
              $sql = "select main_site_id from users where id = " . $_SESSION['user_id'];
              if($result = $this->dbi->query($sql)) {
              if($myrow = $result->fetch_assoc()) {
              $main_site_id = $myrow['main_site_id'];
              }
              if(!$main_site_id) echo $this->redirect($this->f3->get('main_folder') . "MyAccount?select_site=1");
              }
             */
            //if(!$_SESSION['site_id'] && !($this->f3->get('is_client') || $this->f3->get('is_client_staff') || $this->f3->get('is_site'))) echo $this->redirect($this->f3->get('main_folder') . "MyAccount?select_site=1");
        }
        if ($page_name != 'Resources' && $page_name != 'Induction' && $page_name != 'PandemicResponse' && $page_name != 'Licencing/MyLicences' && $page_name != 'MyAccount' && $this->f3->get('is_security') && !($this->f3->get('is_client') || $this->f3->get('is_site') || $this->f3->get('is_client_staff'))) {
            $sql = "select id from licences where user_id = " . $_SESSION['user_id'] . ";";

            if ($result = $this->dbi->query($sql)) {
                $has_licence = $result->num_rows;
            }
            if (!$has_licence)
                echo $this->redirect($this->f3->get('main_folder') . "Licencing/MyLicences?show_warning=1 $page_name");
        }
        $accessUserRoleName = $this->userRoleNameByUid($_SESSION['user_id']);

        $noInductionRole = array('CLIENT SITE MANAGER', 'CLIENTS STATE MANAGER', 'CLIENTS NATIONAL OPERATIONS', 'SUPER ADMIN');
        
        //$this->f3->get('is_staff') &&
        if (!in_array($accessUserRoleName, $noInductionRole) && $_SESSION['user_id'] != 1 && $_SESSION['user_id'] != 5 && $page_name != 'TermConditionAccept' && $page_name != 'DownloadFile' && $page_name != 'Licencing/MyLicences' && $page_name != 'Compliance' && $page_name != 'Induction' && $page_name != 'Resources' && $page_name != 'PandemicResponse' && $page_name != 'MyAccount' && !($this->f3->get('is_client') || $this->f3->get('is_client_staff') || $this->f3->get('is_site'))) {

            $induction_obj = new UserController();
            $induction_res = $induction_obj->induction(1, 1);

            if ($induction_res != '1') {
                echo $this->redirect($this->f3->get('main_folder') . "Induction");
                die;
            }
        }

        
        
         
         /*  check Allied Mandatory training pending or  normal training date cross */
        
          $trainingBeforeAccess = $this->trainingBeforeAccess($_SESSION['user_id']);
          $firstRoute = explode("/",$page_name);
                  
          if($firstRoute[0] != "AlliedMyTraining" && $trainingBeforeAccess && $_SESSION['user_id'] != "1" && $_SESSION['user_id'] != "5" ){
               //echo $this->redirect($this->f3->get('main_folder') . "AlliedMyTraining/Pending");
          }

          // need to uncomment - Training Module - start
        if (strpos($page_name, 'AlliedMyTraining') === false) {
            if ($loginUserId == '15256') {
                $incompleteTraining = $this->hasIncompleteTraining($loginUserId);
                if (isset($incompleteTraining) && $incompleteTraining > 0) {
                    // prd($page_name);
                    $this->f3->reroute("/AlliedMyTraining/Pending");
                }
            }
        }
        // need to uncomment - Training Module - end
         
          
           if($page_name != 'PandemicResponse' && !($page_name == 'Compliance' && ($_GET['report_id'] || $_GET['report_edit_id'])) && $this->f3->get('is_staff') && !($this->f3->get('is_client') || $this->f3->get('is_client_staff') || $this->f3->get('is_site'))) {
            $pandemic_obj = new UserController();
            $pandemic_res = $pandemic_obj->pandemic_questions();
            if ($pandemic_res == 'failed') {
                echo $this->redirect($this->f3->get('main_folder') . "PandemicResponse");
            } else if ($pandemic_res == 'incomplete') {

                echo $this->redirect($this->f3->get('main_folder') . "Compliance?report_id=47&new_report=1");
            }
        }
          
        

        if($page_name != 'PandemicResponse' && !($page_name == 'Compliance' && ($_GET['report_id'] || $_GET['report_edit_id'])) && $this->f3->get('is_staff') && !($this->f3->get('is_client') || $this->f3->get('is_client_staff') || $this->f3->get('is_site'))) {
            $pandemic_obj = new UserController();
            $pandemic_res = $pandemic_obj->pandemic_questions();
            if ($pandemic_res == 'failed') {
                echo $this->redirect($this->f3->get('main_folder') . "PandemicResponse");
            } else if ($pandemic_res == 'incomplete') {

                echo $this->redirect($this->f3->get('main_folder') . "Compliance?report_id=47&new_report=1");
            }
        }

        $sql = "select ID, user_level_id, max_user_level_id from main_pages2 where url = '$page_name';";

        //echo "<div style=\"position: absolute; top: 300px; left: 300px;\">$sql</div>";
        if ($result2 = $this->dbi->query($sql)) {
            $allow_access = 0;
            while ($myrow2 = $result2->fetch_assoc()) {
                if ($allow_access == 0) {
                    $page_id = $myrow2['ID'];
                    $user_level_id = $myrow2['user_level_id'];
                    $max_user_level_id = $myrow2['max_user_level_id'];

                    if (isset($page_id)) {
                        if ($_SESSION['u_level'] < $user_level_id || $_SESSION['u_level'] > $max_user_level_id) {
                            $allow_access = 0;
                        } else {

                            $sql = "select foreign_id from lookup_answers where table_assoc = 'main_pages2' and foreign_id = $page_id and lookup_field_id in (" . implode(",", $_SESSION['lids']) . ")";
//                            echo $sql;
//                            die;
                            if ($result = $this->dbi->query($sql)) {
                                $allow_access = ($result->num_rows ? 1 : 0);
                            } else {
                                $allow_access = 0;
                            }
                        }
                    } else {
                        $allow_access = 0;
                    }
                }
            }
        }

        


        //echo "if({$_SESSION['u_level']} < $user_level_id || {$_SESSION['u_level']} > $max_user_level_id) {";

        $sql = "select MAX(access_level) as `access_lvl` from page_access where group_id in (" . implode(",", $_SESSION['lids']) . ") and page_id in (select ID from main_pages2 where substring(url, 1, $page_str_len) = '$page_name');";
        if ($result2 = $this->dbi->query($sql)) {
            if ($myrow2 = $result2->fetch_assoc()) {
                $_SESSION['page_access'] = $myrow2['access_lvl'];
            }
        }
        if (!isset($_SESSION['page_access']))
            $_SESSION['page_access'] = 1;

        if ($_SESSION['lids'])
            $sql_xtra = "and main_pages2.id in (select foreign_id from lookup_answers where table_assoc = 'main_pages2' and lookup_field_id in (" . implode(",", $_SESSION['lids']) . "))";
        if (!$_SESSION['u_level'])
            $_SESSION['u_level'] = 10;

        $sql_xtra .= "and site_id in (select 0
    union select main_site_id from users where id = " . $_SESSION['user_id'] . "
    union select users.id from users
                        left join associations on associations.parent_user_id = users.id 
                        where associations.association_type_id = (select id from association_types where name = '" . ($this->f3->get('is_client_staff') ? "site_client_staff" : "site_staff") . "') and associations.child_user_id = " . $_SESSION['user_id'] . ")";

                   

        if ($_SESSION['side_menu']) {
            $side_menu = $_SESSION['side_menu'];
            $side_menu = str_replace("uk-open", "", $side_menu);
            $custom_menu = $_SESSION['custom_menu'];

            $sql = "select parent_id from main_pages2 where url = '" . substr($curr_page, strlen($this->f3->get('main_folder'))) . "' order by sort_order DESC LIMIT 1;";
//          echo $sql;
            //        exit;

            
            if ($result2 = $this->dbi->query($sql)) {
                if ($myrow2 = $result2->fetch_assoc()) {
                    $parent_id = $myrow2['parent_id'];
                    $side_menu = str_replace("uk2replace$parent_id", "uk-open", $side_menu);

                
                }
            }
        } else {
            $sql = "
            SELECT main_pages2.*, page_types.item_name AS `position`, main_pages2.title 
            FROM main_pages2
            LEFT JOIN page_types ON page_types.id = main_pages2.page_type_id
            WHERE ((parent_id = 0 
                AND user_level_id <= " . $_SESSION['u_level'] . " 
                AND max_user_level_id >= " . $_SESSION['u_level'] . " 
                AND (page_types.item_name != 'INVISIBLE') 
                $sql_xtra)
                OR (main_pages2.ID IN (557, 556, 558, 560, 559, 518, 223, 491) AND " . $_SESSION['u_level'] . " >= 1000))  -- Including IDs 557, 556, 558, 560, 559, 518, 223, 491 for u_level 1000 and above
                AND " . $_SESSION['u_level'] . " != 3000  -- Excluding u_level 3000
            ORDER BY main_pages2.sort_order;
        ";
            
        //user levels add new pages to menu
        
        
        #continue here
            //echo "<div style=\"position: absolute; top: 300px; left: 300px;\"><textarea>$sql</textarea></div>";
            //        and (page_types.item_name != 'INVISIBLE')
            //FIX for lookup updates
            //insert ignore into lookup_answers (foreign_id, lookup_field_id, table_assoc) select foreign_id, lookup_field_id, table_assoc from lookup_answers2 where table_assoc = 'main_pages2';
            //select id from main_pages2 where id not in (select foreign_id from lookup_answers where table_assoc = 'main_pages2');
            //echo $sql;
            //die;
            $custom_menu = '<div class="custom_menu_bar">';

            $result = $this->dbi->query($sql);
            $x = 0;
            $y = 0;
            while ($myrow = $result->fetch_assoc()) {
                $flder = (substr(strtoupper($myrow['url']), 0, 11) == 'JAVASCRIPT:' ? "" : $this->f3->get('main_folder'));
                $url = ($myrow['url'] == "#" ? $myrow['url'] : $flder . $myrow['url']);
                if(!($url == $this->f3->get('main_folder') . "Page/LoginAs" && $_SESSION['user_id'] != 2)) {
                    $id = $myrow['ID'];
                    $item_name = $myrow['title'];
                    $parent_id = $myrow['parent_id'];
                    $position = ($myrow['position'] ? $myrow['position'] : "");
                    $class = $myrow['class'];

                    if ($position == 'TOP' || $position == 'SIDE-TOP') {
                        $custom_menu .= '<a href="' . $url . '">' . $item_name . '</a>';
                    }

                    //echo $details_str;
                    $sql = "
              select main_pages2.*, page_types.item_name as `position` from main_pages2
              left join page_types on page_types.id = main_pages2.page_type_id
              where parent_id = $id and user_level_id <= " . $_SESSION['u_level'] . " and max_user_level_id >= " . $_SESSION['u_level'] . " and (page_types.item_name != 'INVISIBLE')
              $sql_xtra
              order by main_pages2.sort_order
             ";
//             echo "<h3>$sql</h3>";          
//          echo $sql;
//          die;

                    $result2 = $this->dbi->query($sql);

                    $menu_text = "\r\n" . '<li' . ($result2->num_rows ? ' class="uk-parent uk2replace' . $id . '"' : '') . '><a class="main-menu-a" ' . $class . ' href="' . $url . '">' . $item_name . '</a>';
                    if ($result2->num_rows) {
                        $menu_text .= "\r\n" . '<ul class="uk-nav-sub">';
                        while ($myrow2 = $result2->fetch_assoc()) {
                            $id2 = $myrow2['ID'];
                            $item_name2 = $myrow2['title'];
                            $item_name = $myrow2['title'];
                            $position = ($myrow2['position'] ? $myrow2['position'] : "");
                            $class = $myrow2['class'];

                            $flder = (substr(strtoupper($myrow2['url']), 0, 11) == 'JAVASCRIPT:' ? "" : $this->f3->get('main_folder'));

                            $url2 = ($myrow2['url'] == "#" ? $myrow2['url'] : $flder . $myrow2['url']);
                            //echo "<h3>test $item_name2</h3>";
                            if ($position == 'TOP' || $position == 'SIDE-TOP') {
                                $custom_menu .= '<a href="' . $url2 . '">' . $item_name . '</a>';
                            }
                            if ($url2 == $curr_page || $url2 == $tmp_url) {
                                $menu_text = str_replace("uk2replace$id", "uk-open", $menu_text);
                            }


                            $sql = "
                  select main_pages2.*, page_types.item_name as `position` from main_pages2
                  left join page_types on page_types.id = main_pages2.page_type_id
                  where parent_id = $id2 and user_level_id <= " . $_SESSION['u_level'] . " and max_user_level_id >= " . $_SESSION['u_level'] . " and (page_types.item_name != 'INVISIBLE')
                  $sql_xtra
                  order by main_pages2.sort_order
                 ";

                 
                            //echo "<h3>$sql</h3>";
                            $result3 = $this->dbi->query($sql);

                            $menu_text .= "\r\n" . '<li class="uk-parent' . ($result3->num_rows ? ' uk2replace' : '') . '"><a class="sub-menu-a" ' . $class . ' href="' . $url2 . '"><span data-uk-icon="icon: chevron-right; ratio: 0.6"></span>' . $item_name2 . ($result3->num_rows ? '<span class="fa arrow"></span>' : '') . '</a>';
                            //$menu_text .= "\r\n".'<li '.($result3->num_rows ? 'class="uk-parent uk2replace"' : '').'"><a class="main-menu-a" '.$class.' href="'.$url.'">'.$item_name.'</a>';

                            if ($result3->num_rows) {
                                $menu_text .= "\r\n" . '<ul class="uk-nav-sub">';
                                while ($myrow3 = $result3->fetch_assoc()) {
                                    $class = $myrow3['class'];
                                    $position = ($myrow3['position'] ? $myrow3['position'] : "");
                                    $item_name = $myrow3['title'];
                                    $item_name3 = $myrow3['title'];
                                    $flder = (substr(strtoupper($myrow3['url']), 0, 11) == 'JAVASCRIPT:' ? "" : $this->f3->get('main_folder'));
                                    $url3 = ($myrow3['url'] == "#" ? $myrow3['url'] : $flder . $myrow3['url']);
                                    //$menu_text .= "\r\n".'<li><a '.$class.' href="'.$url3.'">'.$item_name3.'</a></li>';
                                    //echo "<h3>test $item_name3</h3>";
                                    if ($position == 'TOP' || $position == 'SIDE-TOP') {
                                        $custom_menu .= '<a href="' . $url3 . '">' . $item_name . '</a>';
                                    }
                                    if ($url3 == $curr_page || $url3 == $tmp_url) {
                                        //$menu_text = str_replace("uk2replace", "uk-open", $menu_text);
                                    }
                                    $menu_text .= "\r\n" . '<li><a class="sub-menu-a" ' . $class . ' href="' . $url3 . '"><span data-uk-icon="icon: chevron-right; ratio: 0.6"></span>' . $item_name3 . '</a></li>';
                                }
                                $menu_text .= '</li>';
                                $menu_text .= "\r\n</ul>";
                            }
                        }
                        if ($item_name == "Operations") {
                            $menu_text .= "\r\n<li><a href=\"Compliance?auditor_id=" . $_SESSION['user_id'] . "\">My Checklists</a></li>";
                        }
                        $menu_text .= "\r\n</ul>";
                    }
                    $menu_text .= "\r\n" . '</li>';
                } else {
                    $menu_text = "";
                }
                if ($position == 'SIDE' || $position == 'SIDE-TOP')
                    $side_menu .= $menu_text;

                //$str .= "<h3>$position -- " . strip_tags($menu_text) . "</h3>";
                //echo "<h3 style=\"text-align: right;\">$position -- " . strip_tags($menu_text) . "</h3>";

                if ($curr_page) {
                    $page_match = 1;
                }
            }


            $sql = "
        select my_links.id as idin, main_pages2.title, main_pages2.url
        from my_links
        left join main_pages2 on main_pages2.id = my_links.page_id
        where user_id = " . $_SESSION['user_id'] . "
        order by my_links.sort_order
        ";

            if ($result2 = $this->dbi->query($sql)) {
                $num_custom = $result2->num_rows;
                $menu_count = 0;
                while ($myrow2 = $result2->fetch_assoc()) {
                    $menu_count++;
                    $url = $myrow2['url'];
                    $title = $myrow2['title'];
                    if ($menu_count == 1) {
                        
                    }
                    $custom_menu .= '<a href="' . $this->f3->get('main_folder') . $url . '">' . $title . '</a>';
                }
            }
            $custom_menu .= ($this->f3->get('is_client') || $this->f3->get('is_site') || $this->f3->get('is_client_staff') ? '' : '<a href="' . $this->f3->get('main_folder') . 'Edit/MyLinks">Customise Links</a>');
            $custom_menu .= '</div>';

            $_SESSION['custom_menu'] = $custom_menu;
            $_SESSION['side_menu'] = $side_menu;
        }
        $this->f3->set('custom_menu', $custom_menu);

//    if($meta_title) $this->f3->set('meta_title', strip_tags($meta_title));
        $this->f3->set('meta_title', strip_tags($this->get_sql_result("select title as `result` from main_pages2 where url = '" . substr($curr_page, strlen($this->f3->get('main_folder'))) . "'")));
        $this->f3->set('side_menu', $side_menu);

        $curr_date_time = date('Y-m-d H:i:s');
        $sql = "insert into log_page_access (user_id, attempt_date_time, page, is_successful) values ('" . $_SESSION['user_id'] . "', '$curr_date_time', '$curr_page', '$allow_access')";
        $this->dbi->query($sql);

        if($allow_access || $page_name = 'TermConditionAccept') {
            /* if($this->f3->get('is_client_staff')) {
              $_SESSION['user_id'] = $this->get_sql_result("select parent_id as `result` from associations where child_user_id = $user_id and association_type_id = (select id from assocition_types where name = 'site_client_staff');");
              } */
        } else {

            $this->f3->set('content', "<h3>Page Access Denied...</h3>");
            $template = new Template;
            echo $template->render('layout.htm');
            exit;
        }
        
        
    }

    function hasIncompleteTraining($user_id) {
        // Get the main site ID for the logged-in user
        $userQuery = "SELECT main_site_id, user_level_id FROM users WHERE id = ?";
        $userResult = $this->db->exec($userQuery, $user_id);
        $user_level_id = $userResult[0]['user_level_id'];
        if($user_level_id != 400) {
            return false;
        }
        $main_site_id = $userResult[0]['main_site_id'];

        // Get all associated site IDs for the user from the associations table
        $siteQuery = "SELECT parent_user_id FROM associations WHERE child_user_id = ? AND association_type_id = 4";
        $siteResult = $this->db->exec($siteQuery, $user_id);

        // Collect associated site IDs into an array
        $siteIds = array_column($siteResult, 'parent_user_id');
        
        // Get all associated division IDs for the user from the users_user_division_groups table
        $divisionQuery = "SELECT user_group_id FROM users_user_division_groups WHERE user_id = ?";
        $divisionResult = $this->db->exec($divisionQuery, $user_id);

        // Collect associated division IDs into an array
        $divisionIds = array_column($divisionResult, 'user_group_id');

        $siteIds[] = 0;
        $divisionIds[] = 111;

        // Convert the site IDs array to a comma-separated string for the WHERE clause
        $siteIdsStr = implode(',', $siteIds);
        $divisionIdsStr = implode(',', $divisionIds);


        // prd($main_site_id);

        // Define the WHERE condition for checking incomplete training, now including main_site_id
        
        // Query to check if the user has completed the training
        // $checkCompletedQuery = "SELECT ta_training_user.training_rule_id
        //                         FROM training_allied_client_site_training tatc
        //                         INNER JOIN training_allied_rules tar ON tatc.training_id = tar.id
        //                         LEFT JOIN training_allied_training_user ta_training_user 
        //                             ON ta_training_user.training_rule_id = tar.id 
        //                             AND ta_training_user.user_id = $user_id
        //                         WHERE $where_cond
        //                         AND ta_training_user.is_result = 1";

        // // Execute the completed training query
        // $completedResult = $this->db->query($checkCompletedQuery);


        // // If a completed record is found, return false as the training is complete
        // if ($completedResult && $completedResult->fetch(PDO::FETCH_ASSOC)) {
        //     return false;
        // }

        // Define the WHERE condition for checking incomplete training
        $where_cond = " tar.start_date <= CURDATE() "; // Training must have started
        $where_cond .= " AND tar.end_date >= CURDATE() "; // Training must not have ended
        $where_cond .= " AND tatc.site_id IN ($siteIdsStr) AND tatc.division_id IN ($divisionIdsStr) AND tar.status = 1"; // Filter by main site ID

        // Calculate the due date dynamically and check if it's remaining or expired
        $where_cond .= " AND (DATE_ADD(tatc.updated_at, INTERVAL tar.due_date_in DAY) >= CURDATE())"; // Due date must be remaining
        $where_cond .= " AND (ta_training_user.is_result IS NULL OR ta_training_user.is_result = '') "; // Check if training is incomplete
        $where_cond .= " AND (ta_training_user.user_id IS NULL OR ta_training_user.user_id = $user_id) "; // Check for user ID or null (no record)

        // Query to check if there are any incomplete trainings
        // $checkIncompleteQuery = "SELECT COUNT(*) as total_incomplete_training
        //                         FROM training_allied_client_site_training tatc
        //                         INNER JOIN training_allied_rules tar ON tatc.training_id = tar.id
        //                         LEFT JOIN training_allied_training_user ta_training_user 
        //                             ON ta_training_user.training_rule_id = tar.id
        //                         WHERE $where_cond";
        $checkIncompleteQuery = "SELECT tar.id AS trainingId, tar.training_title AS trainingTitle, tar.due_date_in AS dueDateIn, tar.immediate_start AS immediateStart, 
                                    tar.start_date AS startDate, tar.end_date AS endDate, tatc.updated_at AS assignedDate,
                                    CASE 
                                        WHEN ta_training_user.is_result = 1 THEN 'Completed'
                                        ELSE 'Incomplete'
                                    END AS completion_status,
                                    DATE_ADD(tatc.updated_at, INTERVAL tar.due_date_in DAY) AS dueDate, 
                                    GREATEST(DATEDIFF(DATE_ADD(tatc.updated_at, INTERVAL tar.due_date_in DAY), CURDATE()), 0) AS remainingDays
                                FROM training_allied_client_site_training tatc
                                INNER JOIN training_allied_rules tar ON tatc.training_id = tar.id
                                LEFT JOIN training_allied_training_user ta_training_user 
                                    ON ta_training_user.training_rule_id = tar.id
                                WHERE $where_cond
                                HAVING remainingDays <= 0";

        // Execute the incomplete training query
        $arr = array();
        $incompleteResult = $this->db->query($checkIncompleteQuery);
        while($row = $incompleteResult->fetch(PDO::FETCH_ASSOC)) {
            $arr[] = $row;
        }

        // prd(count($arr));

        // Check if there are incomplete trainings
        if (count($arr) > 0) {
            return count($arr);
        } else {
            return false; // Return false if query failed
        }
    }


    function Placeholder() {
        session_start();
    }

    function Editor($f3, $params) {

        $header = ($params['p2'] ? $params['p2'] : $params['p1']);
        //$meta_title = ($params['p3'] ? $params['p3'] : $header);
        $EditController = new EditController($f3);
        $this->f3->set('content', $EditController->{$params['p1']}());
        $this->f3->set('header', $header);
        //$this->f3->set('meta_title', $meta_title);
        $template = new Template;
        $this->f3->set('pagejs', 'htmjs/stakeholder.htm');
        echo $template->render('layout.htm');
    }

    function Assets($f3, $params) {
        //$meta_title = ($params['p3'] ? $params['p3'] : $header);
        $AssetController = new AssetController($f3);
        $this->f3->set('content', $AssetController->{$params['p1']}());
        $this->f3->set('header', $header);
        //$this->f3->set('meta_title', $meta_title);
        $template = new Template;
        echo $template->render('layout.htm');
    }

    function Tasking($f3, $params) {
        //$meta_title = ($params['p3'] ? $params['p3'] : $header);
        $TaskController = new TaskController($f3);
        $this->f3->set('content', $TaskController->{$params['p1']}());
        $this->f3->set('header', $header);
        //$this->f3->set('meta_title', $meta_title);
        $template = new Template;
        echo $template->render('layout.htm');
    }

    function Reporting($f3, $params) {
        $ReportsController = new ReportsController($f3);
        if ($params['p1'] == "ExcelResults" || $params['p1'] == "AllExcel") {
            echo $ReportsController->{$params['p1']}();
        } else {
            $header = ($params['p2'] ? $params['p2'] : $params['p1']);
            //$meta_title = ($params['p3'] ? $params['p3'] : $header);
            $this->f3->set('content', $ReportsController->{$params['p1']}());
            $this->f3->set('header', $header);
            //$this->f3->set('meta_title', $meta_title);
            $template = new Template;
            echo $template->render('layout.htm');
        }
    }

    function Licencing($f3, $params) {
        // $header = ($params['p2'] ? $params['p2'] : $params['p1']);
        // $LicenceController = new LicenceController($f3);
        // // $this->f3->set('content', $LicenceController->{$params['p1']}());
        // // $this->f3->set('header', $header);
        // $template = new Template;
        // // echo $template->render('layout.htm');
        // $Controller->manageLicenses();  //Call Course edit func

        $Controller = new LicenceController($f3); //create an object of Course controller
        $Controller->manageLicenses();  //Call Course edit func
    }
    function MyLicences($f3, $params) {
        // $header = ($params['p2'] ? $params['p2'] : $params['p1']);
        // $LicenceController = new LicenceController($f3);
        // // $this->f3->set('content', $LicenceController->{$params['p1']}());
        // // $this->f3->set('header', $header);
        // $template = new Template;
        // // echo $template->render('layout.htm');
        // $Controller->manageLicenses();  //Call Course edit func

        $Controller = new LicenceController($f3); //create an object of Course controller
        $Controller->manageMyLicenses();  //Call Course edit func
    }

    // function Licencing($f3, $params) {
    //     $header = ($params['p2'] ? $params['p2'] : $params['p1']);
    //     $LicenceController = new LicenceController($f3);
    //     $this->f3->set('content', $LicenceController->{$params['p1']}());
    //     $this->f3->set('header', $header);
    //     $template = new Template;
    //     echo $template->render('layout.htm');
    // }

    function Training($f3, $params) {
        $header = ($params['p2'] ? $params['p2'] : $params['p1']);
        $TrainingController = new TrainingController($f3);
        $this->f3->set('content', $TrainingController->{$params['p1']}());
        $this->f3->set('header', $header);
        $template = new Template;
        echo $template->render('layout.htm');
    }

    
    function FlagUser($f3, $params) {
        $header = ($params['p2'] ? $params['p2'] : $params['p1']);
        $uv = new UserController();
        $this->f3->set('content', $uv->FlagUser());
        $this->f3->set('header', $header);
        $template = new Template;
        echo $template->render('layout.htm');
    }

    function Patrols($f3, $params) {
        $header = ($params['p2'] ? $params['p2'] : $params['p1']);
        $PatrolController = new PatrolController($f3);
        $this->f3->set('content', $PatrolController->{$params['p1']}());
        $this->f3->set('header', $header);
        $template = new Template;
        echo $template->render('layout.htm');
    }

    function Marketing($f3, $params) {
        $header = ($params['p2'] ? $params['p2'] : $params['p1']);
        $MarketingController = new MarketingController($f3);
        $this->f3->set('content', $MarketingController->{$params['p1']}());
        $this->f3->set('header', $header);
        $template = new Template;
        echo $template->render('layout.htm');
    }

    function Rostering($f3, $params) {
        $header = ($params['p2'] ? $params['p2'] : $params['p1']);
	// $header = $params['p2'] ?? $params['p1'];
        $RosteringController = new RosteringController($f3);
        $this->f3->set('content', $RosteringController->{$params['p1']}());
        $this->f3->set('header', $header);
        $this->f3->set('css_xtra', 'rostering.css?v=3');
        $this->f3->set('js_xtra', 'rostering.js?v=3');
        $template = new Template;
        echo $template->render('layout.htm');
    }

    function Rostering1($f3, $params) {
        // echo "here"; die;
        $header = ($params['p2'] ? $params['p2'] : $params['p1']);
	    // $header = $params['p2'] ?? $params['p1'];
        $RosteringController = new Rostering1Controller($f3);
        // var_dump($RosteringController->{$params['p1']}()); die;
        $this->f3->set('content', $RosteringController->{$params['p1']}());
        $this->f3->set('header', $header);
        $this->f3->set('css_xtra', 'rostering.css?v=3');
        $this->f3->set('js_xtra', 'rostering.js?v=3');
        $template = new Template;
        echo $template->render('layout.htm');
    }

    function MyRoster($f3) {
        $RosteringController = new RosteringController($f3);
        $this->f3->set('content', $RosteringController->MyRoster());
        $this->f3->set('header', $header);
        $this->f3->set('css_xtra', 'rostering.css?v=2');
        $this->f3->set('js_xtra', 'rostering.js?v=2');
        $template = new Template;
        echo $template->render('layout.htm');
    }

    function MyCard($f3) {
        $uv = new UserController();
        $card_id = ($this->f3->get('is_client_staff') ? $this->get_site_id() : $_SESSION['user_id']);
        $this->f3->set('content', $uv->ViewCard($card_id));
        $this->f3->set('header', $header);
        //$this->f3->set('view', 'user_card.htm');
        $this->f3->set('css_xtra', 'resources.css?v=3');
        $template = new Template;
        echo $template->render('layout.htm');
    }

    function UserCard($f3) {
        $uv = new UserController();
        $this->f3->set('content', $uv->UserCard());
        $this->f3->set('header', $header);
        $this->f3->set('view', 'user_card.htm');
        $this->f3->set('css_xtra', 'resources.css?v=3');
        //$this->f3->set('js_xtra', 'rostering.js?v=2');
        $template = new Template;
        echo $template->render('layout.htm');
    }

    function ViewCard($f3) {
        $uv = new UserController();
        $this->f3->set('content', $uv->ViewCard());
        $this->f3->set('header', $header);
        //$this->f3->set('view', 'user_card.htm');
        $this->f3->set('css_xtra', 'resources.css?v=3');
        //$this->f3->set('js_xtra', 'rostering.js?v=2');
        $template = new Template;
        echo $template->render('layout.htm');
    }

    function MeetingMinutes($f3, $params) {
        $header = ($params['p2'] ? $params['p2'] : $params['p1']);
        $MeetingMinutes = new MeetingMinutes($f3);
        $this->f3->set('content', $MeetingMinutes->{$params['p1']}());
        $this->f3->set('header', $header);
        $template = new Template;
        echo $template->render('layout.htm');
    }

    function Page($f3, $params) {
        $header = ($params['p2'] ? $params['p2'] : $params['p1']);
        //$meta_title = ($params['p3'] ? $params['p3'] : $header);
        $PageController = new PageController($f3);
        $this->f3->set('content', $PageController->{$params['p1']}());
        $this->f3->set('header', $header);
        //$this->f3->set('meta_title', $meta_title);
        $template = new Template;
        echo $template->render('layout.htm');
    }

    /* function Page($f3, $params) {
      $download_xl = (isset($_GET['download_xl']) ? $_GET['download_xl'] : null);
      $header = ($params['p2'] ? $params['p2'] : $params['p1']);
      //$meta_title = ($params['p3'] ? $params['p3'] : $header);
      $PageController = new PageController($f3);
      if($download_xl) {
      $PageController->{$params['p1']}();
      } else {
      $this->f3->set('content', $PageController->{$params['p1']}());
      $this->f3->set('header', $header);
      //$this->f3->set('meta_title', $meta_title);
      $template = new Template;
      echo $template->render('layout.htm');
      }
      } */

    function Employment() {
        $Emp = new Employment($f3);
        $this->f3->set('content', $Emp->Show());
        $this->f3->set('title', "Apply For Work");
        $template = new Template;
        echo $template->render(($this->f3->get('is_staff') ? '' : 'employment_') . 'layout.htm');
    }

    function KPI($f3, $params) {
        $KPIResultsController = new KPIResultsController($f3);
        $this->f3->set('content', $KPIResultsController->{$params['p1']}());
        $this->f3->set('header', $header);
        $template = new Template;
        echo $template->render('layout.htm');
    }

    function UserDetails() {
        $p = new UserController();
        $this->f3->set('content', $p->UserDetails());
        $template = new Template;
        echo $template->render('layout.htm');
    }

    function StaffPositions() {
        $p = new UserController();
        $this->f3->set('content', $p->StaffPositions());
        $template = new Template;
        echo $template->render('layout.htm');
    }

    function AccountDetails() {
        $p = new UserController();
        $this->f3->set('content', $p->AccountDetails());
        $template = new Template;
        echo $template->render('layout.htm');
    }

    function StaffLicences() {
        $p = new UserController();
        $this->f3->set('content', $p->StaffLicences());
        $template = new Template;
        echo $template->render('layout.htm');
    }

    function LeaveRequests() {
        $p = new UserController();
        $this->f3->set('content', $p->LeaveRequests());
        $template = new Template;
        echo $template->render('layout.htm');
    }


    function MyLeaveRequests() {
        $this->LeaveRequests();
    }

    function Resources($f3) {
        $header = "Resources";
        $meta_title = $header;
        $ResourceController = new ResourceController($f3);
        $this->f3->set('content', $ResourceController->Show());
        $this->f3->set('header', $header);
        $this->f3->set('meta_title', $meta_title);
        $this->f3->set('css_xtra', 'resources.css?v=3');
        $this->f3->set('js_xtra', 'resources.js?v=2');

        $template = new Template;
        echo $template->render('layout.htm');
    }

    function ClockEditor() {
        $header = "Clock Editor";
        $meta_title = $header;
        $test = "ClockController";
        $ClockController = new $test();
        $this->f3->set('content', $ClockController->ClockEditor());
        $this->f3->set('header', $header);
        $this->f3->set('meta_title', $meta_title);
//		$this->f3->set('css_xtra', 'clock.css?v=2');
//		$this->f3->set('js_xtra', 'clock.js?v=2');

        $template = new Template;
        echo $template->render('layout.htm');
    }

    function Clock() {
        $header = "Clock";
        $meta_title = $header;
        $test = "ClockController";
        $ClockController = new $test();
        $this->f3->set('content', $ClockController->Show());
        $this->f3->set('header', $header);
        $this->f3->set('meta_title', $meta_title);
        $this->f3->set('css_xtra', 'clock.css?v=2');
        $this->f3->set('js_xtra', 'clock.js?v=2');

        $template = new Template;
        echo $template->render('layout.htm');
    }

    function Clock2() {
        $header = "Clock";
        $meta_title = $header;
        $RosteringController = new RosteringController($f3);
        $this->f3->set('content', $RosteringController->Clock());
        $this->f3->set('header', $header);
        $this->f3->set('meta_title', $meta_title);
        $this->f3->set('css_xtra', 'clock.css?v=2');
        $this->f3->set('js_xtra', 'rostering.js?v=2');

        $template = new Template;
        echo $template->render('layout.htm');
    }

    function TermConditionAccept() {

        $header = "Term & Condition";
        $meta_title = $header;
        $UserController = new UserController($f3);
        $this->f3->set('content', $UserController->termCondition());
        $this->f3->set('header', $header);
        $this->f3->set('meta_title', $meta_title);
        $this->f3->set('css_xtra', 'clock.css?v=2');
        $this->f3->set('js_xtra', 'rostering.js?v=2');
        $template = new Template;
        echo $template->render('layout.htm');
    }

    function MyPatrols() {
        $header = "My Patrols";
        $meta_title = $header;
        $PatrolController = new PatrolController($f3);
        $this->f3->set('content', $PatrolController->MyPatrols());
        $this->f3->set('header', $header);
        $this->f3->set('meta_title', $meta_title);
        $this->f3->set('css_xtra', 'clock.css?v=2');
        $this->f3->set('js_xtra', 'rostering.js?v=2');

        $template = new Template;
        echo $template->render('layout.htm');
    }

    function MyJobApplications() {
        $tmp = new JobApplications();
        $this->f3->set('content', $tmp->MyJobApplications());
        $template = new Template;
        echo $template->render('layout.htm');
    }

    function GenericPage($name, $hide_side = 0, $func = "Show") {
        $this->f3->set('hide_side', $hide_side);
        $Generic = new $name();
        $this->f3->set('content', $Generic->$func());
        $template = new Template;
        echo $template->render('layout.htm');
    }

//Generic Page Functions
    function Diary() {
        $this->GenericPage("Diary");
    }

    function Appraisals() {
        $this->GenericPage("Appraisals");
    }

    function NrlRoster() {
        $this->GenericPage("NrlRoster", ($_GET['roster_edit'] ? 1 : 0));
    }

    function Applications() {
        $this->GenericPage("Applications");
    }

    function ConvertF3() {
        $this->GenericPage("ConvertF3");
    }

    function Logs() {
        $this->GenericPage("Logs");
    }

    function JobApplications() {
        $this->GenericPage("JobApplications");
    }

    function SiteNotes() {
        $this->GenericPage("SiteNotes");
    }

    function StaffList() {
        $this->GenericPage("StaffList");
    }

    function StaffListNew() {
        $this->GenericPage("StaffList", 'show1');
    }

    function SiteList() {
        $this->GenericPage("SiteList");
    }

    function EquipmentIssue() {
        $this->GenericPage("EquipmentIssue");
    }

    function UserManager() {
        $this->GenericPage("UserController", 0, 'UserManager');
    }

    function UserAction() {
        $p = new UserController();
        $p->UserAction();
    }

    //function Employment() { $this->GenericPage("Employment"); }

    /* function Employment() {
      $Emp = new Employment();
      $this->f3->set('content', $Emp->Show());
      $template = new Template;
      echo $template->render('bare_layout.htm');
      } */
    function IncidentReport() {
        $tmp = new ComplianceController($this->f3);
        $this->f3->set('content', $tmp->incident_report());
        $template = new Template;
        echo $template->render('layout.htm');
    }

    function SocialMedia() {
        $this->f3->set('content', '');
        $this->f3->set('view', 'social-media.htm');
        $template = new Template;
        echo $template->render('layout.htm');
    }

    function Induction() {
        $tmp = new UserController($this->f3);
        $this->f3->set('content', $tmp->induction());
        $template = new Template;
        echo $template->render('layout.htm');
    }

    function PandemicResponse() {
        $str = '
    <h3>Please self isolate as soon as possible and call your manager.</h3>

    <h3>How to Self Isolate</h3>

    <table border="0" width="100%">
      <tbody>
        <tr>
          <td width="75"><img src="/app/images/notice_board/image1.gif" /></td>
          <td>Monitor for symptoms: a fever, cough, shortness of breath or difficulty breathing, chills, body aches, sore throat, headache and runny nose, muscle pain or diarrhoea.</td>
        </tr>
        <tr>
          <td width="75"><img src="/app/images/notice_board/image2.gif" /></td>
          <td>Separate yourself from the other people in your home. Avoid sharing household items, wear a surgical mask when you are in the same room as someone else or are using communal spaces and use a separate bathroom if possible.</td>
        </tr>
        <tr>
          <td width="75"><img src="/app/images/notice_board/image3.gif" /></td>
          <td>Wash your hands carefully.</td>
        </tr>
        <tr>
          <td width="75"><img src="/app/images/notice_board/image4.gif" /></td>
          <td>Exercise regularly at home.</td>
        </tr>
        <tr>
          <td width="75"><img src="/app/images/notice_board/image5.gif" /></td>
          <td>Keep in touch with family and friends via social media, email or the phone.</td>
        </tr>
        <tr>
          <td width="75"><img src="/app/images/notice_board/image6.gif" /></td>
          <td>Try to maintain a normal daily routine.</td>
        </tr>
      </tbody>
    </table>

    ';
        $this->f3->set('content', $str);
        $template = new Template;
        echo $template->render('layout.htm');
    }

    function InductionReport() {
        $tmp = new UserController($this->f3);
        $this->f3->set('content', $tmp->induction_report());
        $template = new Template;
        echo $template->render('layout.htm');
    }

    function PrestartChecklistReport() {
        $tmp = new UserController($this->f3);
        $this->f3->set('content', $tmp->prestart_checklist_report());
        $template = new Template;
        echo $template->render('layout.htm');
    }
    
    function ShiftEndingClientHandoverReport() {
        $tmp = new UserController($this->f3);
        $this->f3->set('content', $tmp->shift_ending_client_handover_report());
        $template = new Template;
        echo $template->render('layout.htm');
    }

    function MyReports() {
        $tmp = new ComplianceController($this->f3);
        $this->f3->set('content', $tmp->MyReports());
        $template = new Template;
        echo $template->render('layout.htm');
    }

    function Requests() {
        $tmp = new ComplianceController($this->f3);
        $this->f3->set('content', $tmp->Requests());
        $template = new Template;
        echo $template->render('layout.htm');
    }

    function ReportSummary() {
        $tmp = new ComplianceController($this->f3);
        $this->f3->set('content', $tmp->ReportSummary());
        $template = new Template;
        echo $template->render('layout.htm');
    }

    function Compliance($name) {
        $tmp = new ComplianceController($this->f3);
        //if($_GET['report_edit_id']) $this->f3->set('hide_side', 1);
        // var_dump($_GET['report_edit_id']); die;

        $template_id = (isset($_GET['template_id']) ? $_GET['template_id'] : null);
        if ($template_id) {
            echo $tmp->Show();
        } else {
            $this->f3->set('content', $tmp->Show());
            $template = new Template;
            echo $template->render('layout.htm');
        }
    }

    function ComplianceTest($name) {
        // $f3 = Base::instance();
        // $db = new DB\SQL('mysql:host=localhost;dbname=tnsmwdztaz', 'tnsmwdztaz', 'vzZ3mFxE2E');
        // $template = new Template;
        // $tmp = new ComplianceTestController($this->f3);
        // echo $tmp->Show(); die;


        // echo $template->render('compliance/reportlist.htm');
        $tmp = new ComplianceTestController($this->f3);
        $this->f3->set('content', $tmp->Show());
        $template = new Template;
        echo $template->render('layout.htm');
    }

    function ComplianceResults() {
        $tmp = new ComplianceController($this->f3);
        $this->f3->set('content', $tmp->ComplianceResults());
        $template = new Template;
        echo $template->render('layout.htm');
    }

    function ComplianceFollowup() {
        $tmp = new ComplianceController($this->f3);
        $this->f3->set('content', $tmp->ComplianceFollowup());
        $template = new Template;
        echo $template->render('layout.htm');
    }

    function MyAccount() {
        $Acct = new UserController();
        $this->f3->set('content', $Acct->MyAccount());
        $template = new Template;
        echo $template->render('layout.htm');
    }

    function ManageSiteManager() {
        $itm = new UserController();
        $this->f3->set('content', $itm->ManageSiteManager());
        $template = new Template;
        echo $template->render('layout.htm');
    }

    function AssignSiteManager() {
        $itm = new UserController();
        $this->f3->set('content', $itm->AssignSiteManager());
        $template = new Template;
        echo $template->render('layout.htm');
    }

    function UserAdder() {
        $tmp = new UserController();
        $this->f3->set('content', $tmp->UserAdder());
        $template = new Template;

        $this->f3->set('pagejs', 'htmjs/stakeholder.htm');
        echo $template->render('layout.htm');
    }

    function UserAdderNew() {

        $tmp = new UserController();
        $this->f3->set('content', $tmp->UserAdderNew());
        $template = new Template;
        $this->f3->set('pagejs', 'htmjs/stakeholder.htm');
        echo $template->render('layout.htm');
    }

    function MediaManager() {
        if (isset($_GET['target_dir'])) {
            $target_dir = urlencode($_GET['target_dir']);      
//            echo $target_dir.'</br>';
//            die;
//            echo urlencode($target_dir).'</br>';
//             echo urldecode($target_dir).'</br>';
//            die;
            $this->f3->set('content', '<iframe width="100%" height="1000" src="' . $this->f3->get('main_folder') . 'Fileman?show_min=1&target_dir=' . urlencode($target_dir) . '&norename=1&fileUseConst=base_img_folder"></iframe>');
            $template = new Template;
            echo $template->render('layout.htm');
        }
    }

    function Fileman() {
        $this->f3->set('show_min', 1);
        //die;
        $this->GenericPage("Fileman");
    }

    function FileManager() {
        $tmp = new FileManager($this->f3);
        $this->f3->set('content', $tmp->Show());
        $template = new Template;
        echo $template->render('layout.htm');
    }

    function showDashboard() {

        $user_id = $_SESSION['user_id'];
         $accessUserRoleName = $this->userRoleNameByUid($_SESSION['user_id']);
//         echo $user_id.$accessUserRoleName;
//         die;

        $sql = "select meta_value from usermeta where user_id = $user_id and meta_key = (select id from lookups where item_name = 'is_signon_off');";
        $result = $this->dbi->query($sql);
        $this->f3->set('wfc_xtra', "&wfc=1");
        if ($myrow = $result->fetch_assoc()) {
            if ($myrow['meta_value'] == 1)
                $this->f3->set('wfc_xtra', "&signon_off=1");
        }
        //echo "$sql" . $this->f3->get('wfc_xtra');
        $ticker_xtra = Array();
        $sql = "select public_holidays.title, public_holidays.date
    from public_holidays
    where date >= now() and public_holidays.state_id = 2 order by date LIMIT 1";
        if ($result = $this->dbi->query($sql)) {
            if ($myrow = $result->fetch_assoc()) {
                $title = $myrow['title'];
                $date = $myrow['date'];
                $date = date("l, d/M/Y", strtotime($date));
                $public_holiday_message = "<b>$title</b> on <b>$date</b>";
                $ticker_xtra[] = $public_holiday_message;
            }
        }
        if ($public_holiday_message)
            $this->f3->set('public_holiday_message', $public_holiday_message);

        $sql = "select CONCAT('Happy Birthday ', name, ' ', surname) as `message` from users where month(dob) = month(now()) and day(dob) = day(now()) and user_status_id = 30 and user_level_id >= 300";
        if ($result = $this->dbi->query($sql)) {
            while ($myrow = $result->fetch_assoc()) {
                $ticker_xtra[] = $myrow['message'];
            }
        }

        $detect = new Mobile_Detect;
        $this->f3->set('is_mobile', ($detect->isMobile() ? 1 : 0));
        $this->f3->set('is_tablet', ($detect->isTablet() ? 1 : 0));

        $left_pane = "";
        // Any mobile device (phones or tablets).
        if ($this->f3->get('is_mobile') || $this->f3->get('is_tablet')) {
            $tmp = new EditController($this->f3);
            $my_tasks .= $tmp->MyTasks(2);

            $this->list_obj = new data_list;
            $this->list_obj->sql = "
                    select
                    title as `Job Title`,
                    CONCAT('<a class=\"list_a\" href=\"Employment?more_info=', job_ads.id, '\">More Info</a>') as `&nbsp;`,
                    CONCAT('<div style=\"float: right;\"><a class=\"list_a\" href=\"Employment?apply_now=1&job_id=', job_ads.id, '\">Apply Now</a></div>') as `&nbsp;&nbsp;&nbsp;`
                    FROM job_ads
                    where closing_date = '0000-00-00' or closing_date >= now()
                    order by sort_order
            ";
            $job_vacancies = $this->list_obj->draw_list();

            $this->f3->set('cl', "s_mobile");
            $this->f3->set('fr', "1500");
            if ($site_id = $this->get_site_id()) {
                $sql = "select concat(name, ' ', surname) as `site_name`, CONCAT(users.address, ' ', users.suburb, ' ', users.postcode) as `site_address` from users where users.id = " . $site_id;
                $result = $this->dbi->query($sql);
                if ($myrow = $result->fetch_assoc()) {
                    $this->f3->set('site_name', $myrow['site_name']);
                    $this->f3->set('site_address', urlencode(trim($myrow['site_address'])));
                }
            }
            if ($this->f3->get('is_client')) {
                $view_details = new data_list;
                $view_details->dbi = $this->dbi;
                if ($this->f3->get('is_mobile')) {
                    $sql = "SELECT users.id, CONCAT('<b>', users.name, ' ', users.surname, '</b><br />', if(users.email != '', CONCAT(users.email, '<br />'), ''), if(users.email != '', CONCAT('<nobr>', users.phone, '</nobr>'), ''), '<br /><a class=\"list_a\" href=\"ViewCard?uid=', users.id ,'\">Site Card</a><a class=\"list_a\" href=\"Page/GetSiteNotes?lookup_id=', users.id, '\">Whiteboard</a><br /><a class=\"list_a\" href=\"Page/OccurrenceLog?lookup_id=', users.id, '\">Occurrence Log</a>') as `&nbsp;`
            from associations
            inner join users on users.id = associations.child_user_id
            left join states on states.id = users.state
            where associations.parent_user_id = " . $user_id . " and associations.association_type_id = 1 and users.user_status_id = 30";
                } else {
                    $sql = "SELECT users.id, CONCAT('<b>', users.name, ' ', users.surname, '</b><br />', if(users.email != '', CONCAT(users.email, '<br />'), ''), if(users.email != '', CONCAT('<nobr>', users.phone, '</nobr>'), '')) as `Details`,
            CONCAT(users.address, '<br />', users.suburb, '<br />', states.item_name, ' ', users.postcode) as `Address`, CONCAT('<a class=\"list_a\" href=\"ViewCard?uid=', users.id ,'\">Site Card</a><br /><a class=\"list_a\" href=\"Page/GetSiteNotes?lookup_id=', users.id, '\">Whiteboard</a><br /><a class=\"list_a\" href=\"Page/OccurrenceLog?lookup_id=', users.id, '\">Occurrence Log</a>') as `Operations`
            from associations
            inner join users on users.id = associations.child_user_id
            left join states on states.id = users.state
            where associations.parent_user_id = " . $user_id . " and associations.association_type_id = 1 and users.user_status_id = 30";
                }
                $view_details->sql = $sql;
                $this->f3->set('left_pane', $view_details->draw_list());
            }
        } else {
            $this->f3->set('cl', "s_pc");
            $this->f3->set('fr', "500");
            if ($this->f3->get('is_client')) {
                $view_details = new data_list;
                $view_details->dbi = $this->dbi;
                //$view_details->title = "Sites";
                $sql = "SELECT users.id, CONCAT('<b>', users.name, ' ', users.surname, '</b><br />', if(users.email != '', CONCAT(users.email, '<br />'), ''), if(users.email != '', CONCAT('<nobr>', users.phone, '</nobr>'), '')) as `Details`,
          CONCAT(users.address, '<br />', users.suburb, '<br />', states.item_name, ' ', users.postcode) as `Address`, CONCAT('<a class=\"list_a\" href=\"ViewCard?uid=', users.id ,'\">Site Card</a><br /><a class=\"list_a\" href=\"Page/GetSiteNotes?lookup_id=', users.id, '\">Whiteboard</a><br /><a class=\"list_a\" href=\"Page/OccurrenceLog?lookup_id=', users.id, '\">Occurrence Log</a>') as `Operations`
          from associations
          inner join users on users.id = associations.child_user_id
          left join states on states.id = users.state
          where associations.parent_user_id = " . $user_id . " and associations.association_type_id = 1 and users.user_status_id = 30";

                //

                $view_details->sql = $sql;
                $left_pane .= $view_details->draw_list();
            } else {
                if ($site_id = $this->get_site_id()) {
                    $sql = "select concat(name, ' ', surname) as `site_name` from users where users.id = " . $site_id;
                    $result = $this->dbi->query($sql);
                    if ($myrow = $result->fetch_assoc()) {
                        $site_name = $myrow['site_name'];
                        $main_site_id = $myrow['main_site_name'];
                    }

                    $notes = new notes;
                    $notes->use_comments = 1;
                    $notes->hide_enter_comments = 1;
                    $notes->num_per_page = 10;
                    $notes->table = "site_notes";
                    $notes->table_id_name = "site_id";
                    $notes->show_cam = 0;
                    $notes->id = $site_id;
                    //$notes->notes_heading = '<div class="cl"></div><h3 class="fl">Whiteboard for '.$site_name.'</h3><div class="cl"></div>';
                    $left_pane .= $notes->display_notes();
                }
            }

            //    $notes->id = $site_id;


            /*
              }
              if($download_xl) {
              $notes->excel_file = 'site_notes.xlsx';
              $notes->download_xl();
              } else {
              if($site_id) {
              $notes->show_delete = 1;
              $notes->use_comments = 1;
              $str .= $notes->display_add();
              $str .= $notes->display_notes();
              } else {
              $str .= '<h3>You are not yet assigned to a site<br /><br /></h3><a class="list_a" href="'.$this->f3->get('main_folder').'MyAccount?select_site=Page/SiteNotes'.($_GET['show_min'] ? "&show_min=1" : "").'">Select a Site &gt;&gt;</a>';
              }
              } */

            //" . (isset($site_state) ? $site_state : 2) . "

            $tmp = new PageController($this->f3);
            $right_pane .= $tmp->NoticeBoard($ticker_xtra);

            $tmp = new EditController($this->f3);
            $my_tasks .= $tmp->MyTasks(2);

            $this->list_obj = new data_list;
            $this->list_obj->sql = "
            select
            title as `Job Title`,
            CONCAT('<a class=\"list_a\" href=\"Employment?more_info=', job_ads.id, '\">More Info</a>') as `&nbsp;`,
            CONCAT('<div style=\"float: right;\"><a class=\"list_a\" href=\"Employment?apply_now=1&job_id=', job_ads.id, '\">Apply Now</a></div>') as `&nbsp;&nbsp;&nbsp;`
            FROM job_ads
            where closing_date = '0000-00-00' or closing_date >= now()
            order by sort_order
      ";
            $job_vacancies = $this->list_obj->draw_list();

            $loginUserDivisions = $this->get_divisions($_SESSION['user_id'], 0, 1);
            $loginUserDivisionsArray = explode(',', $loginUserDivisions);
            $loginUserDivisionsArray = array_map('trim', $loginUserDivisionsArray);
            $loginUserDivisionsStr = implode(',', $loginUserDivisionsArray);
            $allowedcat = [2101, 2007, 2008, 2009, 2010];

            $dataStat .= '<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.3/Chart.js"></script>';

            $loginUserDivisions = $this->get_divisions($_SESSION['user_id'], 0, 1);
            $loginUserDivisionsArray = explode(',', $loginUserDivisions);
            $loginUserDivisionsArray = array_map('trim', $loginUserDivisionsArray);
            $loginUserDivisionsStr = implode(',', $loginUserDivisionsArray);
            $allowedcat = [2101, 2007, 2008, 2009, 2010];

            $userAllowedSiteIds = $this->getUserSiteIdsByUserId($_SESSION['user_id']);

            if ($userAllowedSiteIds != "all") {
                $filterAllowedSiteIds = "and cc.subject_id in (" . $userAllowedSiteIds . ")";
            } else {
                $filterAllowedSiteIds = "";
            }

          $statSql = "
    SELECT 
        CONCAT(
            '<li class=\"dropdown\"><button type=\"button\" class=\"btn-link dropdown-toggle\" data-toggle=\"dropdown\"><i class=\"fa fa-ellipsis-v\"></i></button>',
            '<ul class=\"dropdown-menu\">',
            GROUP_CONCAT(
                CONCAT('<li><a href=\"/Reporting/ShowStats?stat_id=', compliance.id, '\">', compliance.title, '</a></li>')
                SEPARATOR ''
            ),
            '</ul></li>'
        ) AS `link`
    FROM compliance
    LEFT JOIN lookup_fields2 ON lookup_fields2.id = compliance.category_id
    WHERE compliance.category_id IN (" . implode(",", $allowedcat) . ") 
        AND (compliance.division_id = '' OR compliance.division_id IN (" . $loginUserDivisionsStr . ")) 
        AND compliance.min_user_level_id <= " . $_SESSION['u_level'] . " 
        AND compliance.is_active = 1 
        AND (
            (compliance.id IN (
                SELECT compliance_id FROM compliance_auditors WHERE user_id = " . $_SESSION['user_id'] . "
            )) 
            OR (
                compliance.allow_all_access = 1 
                AND compliance.id IN (
                    SELECT foreign_id FROM lookup_answers WHERE table_assoc = 'compliance'
                )
            )
        )
    ORDER BY title";

            $statresult = $this->dbi->query($statSql);
            while ($myrowStat = $statresult->fetch_assoc()) {
                $dataStat .= $myrowStat['link'];
            }
            $stat_id = 34;
            if ($stat_id) {

                $sql = "SELECT concat(MONTHNAME(cc.check_date_time),' ',YEAR(cc.check_date_time)) label,COUNT(cc.id) value, com.title 
FROM compliance_checks cc
LEFT JOIN compliance com ON com.id = cc.compliance_id
WHERE com.category_id in (" . implode(",", $allowedcat) . ") and (com.division_id = '' or com.division_id in (" . $loginUserDivisionsStr . ")) and cc.compliance_id = " . $stat_id . " AND cc.status_id = 524 " . $filterAllowedSiteIds . "
    and datediff(curdate(),cc.check_date_time) < 549
GROUP BY YEAR(cc.check_date_time) ,MONTH(cc.check_date_time) 
ORDER BY YEAR(cc.check_date_time) ASC,MONTH(cc.check_date_time) ASC";
                
                $result = $this->dbi->query($sql);
                if(isset($result) && $result){
                    while ($myrow = $result->fetch_assoc()) {
                        $title = $myrow['title'];
                        $label = $myrow['label'];
                        $value = $myrow['value'];
                        if ($label) {
                            $labels .= "'$label',";
                            $data_items .= "$value,";
                        }
                    }
                }
                
                $labels = substr($labels, 0, strlen($labels) - 1);
                $data_items = substr($data_items, 0, strlen($data_items) - 1);

                $this->list_obj->title = "Results Table";
                   
               
                $this->list_obj->sql = "SELECT concat(MONTH(cc.check_date_time),'-',YEAR(cc.check_date_time)) Label,COUNT(cc.id) Result, com.title Title
FROM compliance_checks cc
LEFT JOIN compliance com ON com.id = cc.compliance_id
WHERE com.category_id in (" . implode(",", $allowedcat) . ") and (com.division_id = '' or com.division_id in (" . $loginUserDivisionsStr . ")) and cc.compliance_id = " . $stat_id . " AND cc.status_id = 524 " . $filterAllowedSiteIds . "
    and datediff(curdate(),cc.check_date_time) < 549
GROUP BY YEAR(cc.check_date_time) ,MONTH(cc.check_date_time) 
ORDER BY YEAR(cc.check_date_time) ASC,MONTH(cc.check_date_time) ASC";
             

                $dataStat .= '<div style="margin-top: 25px;"></div><div class="hide" style="padding-right: 30px;">';
                $dataStat .= $this->list_obj->draw_list();
                if ($title != "") {
                    $dataStat .= '</div><br><br><div class="fl"><h5>Graph of Results for ' . $title . '</h5>';
                    $dataStat .= $this->draw_graph("cha" . preg_replace('/[^a-z0-9]+/i', '', $title), $title, $labels, $data_items);
                } else {
                    $str .= '</div><div class="fl"><h4> Record Not Found </h4>';
                }
                $dataStat .= '</div><div class="fl"></div>';
            }

            $site_name = ($site_name ? "Whiteboard for $site_name" : '<a href="MyAccount?select_site=Page/SiteNotes" class="list_a">Click Here to select your site and view the whiteboard...</a>');
            $this->f3->set('left_pane', $left_pane);
            $this->f3->set('right_pane', $right_pane);
            $this->f3->set('main_site_id', $site_id);
            
            $this->f3->set('role', $accessUserRoleName);
            $this->f3->set('my_stat', $dataStat);
            $this->f3->set('left_pane_title', ($this->f3->get('is_client') ? "Sites" : $site_name));
            $this->f3->set('right_pane_title', 'News');
        }
        $this->f3->set('job_vacancies', $job_vacancies);
        $this->f3->set('my_tasks', $my_tasks);
        $this->f3->set('meta_title', 'Edge Dashboard');
        $this->f3->set('view', 'dashboard.htm');
        $this->f3->set('css', 'main.css?v=2');
        $template = new Template;
        echo $template->render('layout.htm');
    }

    function draw_graph($id, $title, $labels, $data_items) {
        $str .= '
      <div style="width:500px; text-align: centre;"><canvas id="' . $id . '"></canvas></div>
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
                      \'rgba(153, 102, 255, 1)\'
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
                      \'rgba(153, 102, 255, 1)\'
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

    function displayMessages() {
        //$messages = new crud($this->db, 'nrl_games');
//    $this->f3->set('table', $messages->all());
        $this->f3->set('column_headers', array('Title', 'Date'));
        //$this->f3->set('table', $messages->limit_columns('title, date'));
//    $this->f3->set('table',$this->db->exec('SELECT title, date from nrl_games'));
        $this->f3->set('table', $this->db->exec('SELECT title, url from main_pages2 order by sort_order'));

        $this->f3->set('header', 'NRL Games');
        $this->f3->set('table_class', 'grid');
        $this->f3->set('meta_title', 'NRL Games');
        $this->f3->set('view', 'data_table.htm');
        $this->f3->set('css', 'main.css?v=2');
        $template = new Template;
        echo $template->render('layout.htm');
    }

    function DownloadCV() {
        if (isset($_SESSION['user_id'])) {
            $fl = (isset($_GET['fl']) ? $this->decrypt($_GET['fl']) : NULL);
            $file = $this->f3->get('upload_folder') . "cv/" . $_SESSION['user_id'] . "/$fl";
            if (file_exists($file)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                //      header('Content-Type: application/' . mime_content_type(basename($file)));
                header('Content-Disposition: attachment; filename=' . basename($file));
                header('Content-Transfer-Encoding: binary');
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
                header('Content-Length: ' . filesize($file));
                ob_clean();
                flush();
                readfile($file);
                exit;
            } else {
                echo "File Not Found";
            }
        }
    }

    function DownloadFile() {
        if (isset($_SESSION['user_id'])) {

            $f = (isset($_GET['f']) ? $this->decrypt($_GET['f']) : NULL);
            $fl = (isset($_GET['fl']) ? $this->decrypt($_GET['fl']) : NULL);
            $folder = (substr($fl, 0, 1) == "/" ? "" : $this->f3->get('download_folder')) . $fl;
            $file = trim(trim($folder) . "/" . $f);

            if (file_exists($file)) {
                $sql = "insert into downloads (file_name, date, downloaded_by) values ('" . $fl . "/" . $f . "', now(), " . $_SESSION['user_id'] . ")";
                $this->dbi->query($sql);
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                //      header('Content-Type: application/' . mime_content_type(basename($file)));
                header('Content-Disposition: attachment; filename=' . basename($file));
                header('Content-Transfer-Encoding: binary');
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
                header('Content-Length: ' . filesize($file));
                ob_clean();
                flush();
                readfile($file);
                exit;
            } else {
                echo "File Not Found";
            }
        }
    }

    function Image() {

        $flder = (isset($_GET['alt_flder']) ? $_GET['alt_flder'] : $this->f3->get('download_folder'));

        $no_crypt = (isset($_GET['no_crypt']) ? $_GET['no_crypt'] : null);
        $re_encrypt = (isset($_GET['re_encrypt']) ? $_GET['re_encrypt'] : null);
        if ($no_crypt && isset($_GET['i']) && $re_encrypt) {
            die;
            echo $this->redirect($this->f3->get('full_url') . "Image?i=" . urlencode($this->encrypt($flder . $_GET['i'])));
            exit;
        }


        $image = (isset($_GET['i']) ? ($no_crypt ? $flder . $alt_flder . $_GET['i'] : $flder . $alt_flder . $this->decrypt($_GET['i'])) : null);
        //$image = (isset($_GET['i']) ? ($no_crypt ? $flder . $_GET['i'] : $this->decrypt($_GET['i'])) : null);
        $image = trim($image);
        $ext = trim(strtolower(substr($image, (strlen($image) - 4), 4)));

        if (isset($_SESSION['user_id']) && $image && ($ext == '.jpg' || $ext == 'jpeg' || $ext == '.gif' || $ext == '.png')) {
            $type = ($ext == '.jpg' || $ext == 'jpeg' ? 'jpeg' : substr($ext, 1));
            $file = htmlspecialchars($image);
            //echo "$file  $type";
            //exit;
            $imginfo = getimagesize($file);
            header("Content-type: {$imginfo['mime']}");
            header("Content-Type: image/$type");
            readfile($file);
        }
    }

    function ShowImage() {

        echo $this->redirect($this->f3->get('full_url') . "Image?i=" . urlencode($this->encrypt($flder . $_GET['i'])));

        $flder = $this->f3->get('download_folder');
        $no_crypt = (isset($_GET['no_crypt']) ? $_GET['no_crypt'] : null);
        //echo $_GET['i'];
        //exit;
        if ($no_crypt) {
            echo $this->redirect($this->f3->get('full_url') . "ShowImage?i=" . urlencode($this->encrypt($_GET['i'])));
        } else {
            $this->f3->set('content', '<img src="Image?i=' . $_GET['i'] . '">');
            $template = new Template;
            echo $template->render('simple.htm');
        }
    }
    function ShowImagetest() {
        echo $this->redirect($this->f3->get('full_url') . "Image?i=" . urlencode($this->encrypt($flder . $_GET['i'])));

        $flder = $this->f3->get('download_folder');
        $no_crypt = (isset($_GET['no_crypt']) ? $_GET['no_crypt'] : null);
        //echo $_GET['i'];
        //exit;
        if ($no_crypt) {
            echo $this->redirect($this->f3->get('full_url') . "ShowImage?i=" . urlencode($this->encrypt($_GET['i'])));
        } else {
            $this->f3->set('content', '<img src="Image?i=' . $_GET['i'] . '">');
            $template = new Template;
            echo $template->render('simple.htm');
        }
    }

    function SaveImage() {
        $img = $_POST['img'];
        $file_name = $_POST['file_name'];
        $target_dir = $_POST['target_dir'];

        $img = str_replace('data:image/png;base64,', '', $img);
        $img = str_replace(' ', '+', $img);
        $file_data = base64_decode($img);
        file_put_contents("$target_dir/$file_name", $file_data);
    }

    function DrawOz() {
        $img = (isset($_GET['img']) ? $this->f3->get('base_url') . $_GET['img'] : null);

        $image = ImageCreateFromGIF($img);
        $green = imagecolorallocate($image, 200, 255, 200);
        $blue = imagecolorallocate($image, 210, 255, 255);
        $red = imagecolorallocate($image, 255, 200, 200);
        $yellow = imagecolorallocate($image, 251, 188, 5);
        if (isset($_GET['WA'])) {
            if ($_GET['WA'] == 'r') {
                $c = $red;
            } else if ($_GET['WA'] == 'g') {
                $c = $green;
            } else if ($_GET['WA'] == 'y') {
                $c = $yellow;
            } else {
                $c = $blue;
            }
            imagefill($image, 150, 150, $c);
        }
        if (isset($_GET['SA'])) {
            if ($_GET['SA'] == 'r') {
                $c = $red;
            } else if ($_GET['SA'] == 'g') {
                $c = $green;
            } else if ($_GET['SA'] == 'y') {
                $c = $yellow;
            } else {
                $c = $blue;
            }
            imagefill($image, 250, 250, $c);
        }
        if (isset($_GET['QLD'])) {
            if ($_GET['QLD'] == 'r') {
                $c = $red;
            } else if ($_GET['QLD'] == 'g') {
                $c = $green;
            } else if ($_GET['QLD'] == 'y') {
                $c = $yellow;
            } else {
                $c = $blue;
            }
            imagefill($image, 350, 150, $c);
        }
        if (isset($_GET['NSW'])) {
            if ($_GET['NSW'] == 'r') {
                $c = $red;
            } else if ($_GET['NSW'] == 'g') {
                $c = $green;
            } else if ($_GET['NSW'] == 'y') {
                $c = $yellow;
            } else {
                $c = $blue;
            }
            imagefill($image, 380, 300, $c);
        }
        if (isset($_GET['VIC'])) {
            if ($_GET['VIC'] == 'r') {
                $c = $red;
            } else if ($_GET['VIC'] == 'g') {
                $c = $green;
            } else if ($_GET['VIC'] == 'y') {
                $c = $yellow;
            } else {
                $c = $blue;
            }
            imagefill($image, 350, 350, $c);
        }
        if (isset($_GET['TAS'])) {
            if ($_GET['TAS'] == 'r') {
                $c = $red;
            } else if ($_GET['TAS'] == 'g') {
                $c = $green;
            } else if ($_GET['TAS'] == 'y') {
                $c = $yellow;
            } else {
                $c = $blue;
            }
            imagefill($image, 375, 420, $c);
        }
        header('Content-type: image/png');
        imagegif($image);
        imagedestroy($image);
    }

    function Test() {
        $test = "Feck Orf";
        $this->f3->set('content', $test);
        $template = new Template;
        echo $template->render('layout.htm');
    }

    function Support() {
        $p = new UserController();
        $this->f3->set('content', $p->Support());
        $template = new Template;
        echo $template->render('layout.htm');
    }

    function PushNotifications() {
        $p = new UserController();
        $this->f3->set('content', $p->PushNotifications());
        $template = new Template;
        echo $template->render('layout.htm');
    }
        
        function ManualTimesheet() {
            $p = new UserController();
            $this->f3->set('content', $p->ManualTimesheet());
            $template = new Template;
            echo $template->render('layout.htm');
        }

        function FleetManager() {
            $p = new UserController();
            $this->f3->set('content', $p->FleetManager());
            $template = new Template;
            echo $template->render('layout.htm');
        }

        function Contractors() {
            $p = new ContractorController();
            $this->f3->set('content', $p->Contractors());
            $template = new Template;
            echo $template->render('layout.htm');
        }

        function Employees(){
            $p = new EmployeeController();
            $this->f3->set('content', $p->Employees());
            $template = new Template;
            echo $template->render('layout.htm');
        }

            
        function ContractHours() {
            $p = new UserController();
            $this->f3->set('content', $p->ContractHours());
            $template = new Template;
            echo $template->render('layout.htm');
        }

        function BookService() {
            $p = new UserController();
            $this->f3->set('meta_title', 'Booking Service');
            $this->f3->set('content', $p->BookService());
            $template = new Template;
            echo $template->render('layout.htm');
        }
        
        function PoliciesSignoff() {
            $p = new UserController();
            $this->f3->set('content', $p->PoliciesSignoff($_GET['uid']));
            $template = new Template;
            echo $template->render('layout.htm');
        }

        function MaxHours() {
            $p = new UserController();
            $this->f3->set('content', $p->MaxHours());
            $template = new Template;
            echo $template->render('layout.htm');
        }

        function PestPeriodicals() {
            $p = new UserController();
            $this->f3->set('content', $p->PestPeriodicals());
            $template = new Template;
            echo $template->render('layout.htm');
        }

        function FacilitiesPeriodicals() {
            $p = new UserController();
            $this->f3->set('content', $p->FacilitiesPeriodicals());
            $template = new Template;
            echo $template->render('layout.htm');
        }
    
        function ManualTimesheets() {
            $p = new UserController();
            $this->f3->set('content', $p->ManualTimesheets());
            $template = new Template;
            echo $template->render('layout.htm');
        }

         
        function allPeriodicals() {
            $p = new UserController();
            $this->f3->set('content', $p->allPeriodicals());
            $template = new Template;
            echo $template->render('layout.htm');
        }

        
        function FinesRegister() {
            $servername = "localhost";
            $username = "tnsmwdztaz";
            $password = "vzZ3mFxE2E";
            $dbname = "tnsmwdztaz";
            // Establish the database connection
            $connect = mysqli_connect($servername, $username, $password, $dbname);
            // Check the connection
            if (!$connect) {
                die("Connection failed: " . mysqli_connect_error());
            }
            // Pass the database connection as an argument to FinesController
            $finesController = new FinesController($connect);
            $finesController->FinesRegister(); // Assuming this method exists within FinesController
            $template = new Template;
            echo $template->render('fines.htm');
            // Close the database connection
            mysqli_close($connect);
        }

        function BookingRegister() {
            $servername = "localhost";
            $username = "tnsmwdztaz";
            $password = "vzZ3mFxE2E";
            $dbname = "tnsmwdztaz";
            // Establish the database connection
            $connect = mysqli_connect($servername, $username, $password, $dbname);
            // Check the connection
            if (!$connect) {
                die("Connection failed: " . mysqli_connect_error());
            }
            // Pass the database connection as an argument to FinesController
            $bookingController = new BookingController($connect);
            $bookingController->BookingRegister(); // Assuming this method exists within BookingRegister
            $template = new Template;
            $this->f3->set('meta_title', 'Booking Dashboard');
            echo $template->render('booking.htm');
            // Close the database connection
            mysqli_close($connect);
        }

        
        function dataCentre1() {
            $servername = "localhost";
            $username = "tnsmwdztaz";
            $password = "vzZ3mFxE2E";
            $dbname = "tnsmwdztaz";
            // Establish the database connection
            $connect = mysqli_connect($servername, $username, $password, $dbname);
            // Check the connection
            if (!$connect) {
                die("Connection failed: " . mysqli_connect_error());
            }
            // Pass the database connection as an argument to FinesController
            $SwslhdDataCenterController = new SwslhdDataCenterController($connect);
            $SwslhdDataCenterController->dataCentre(); // Assuming this method exists within BookingRegister
            $template = new Template;
            echo $template->render('swslhdPanel.htm');
            // Close the database connection
            mysqli_close($connect);
        }

        function payrollDataCenter() {
            $div_id = $_COOKIE["RosteringDivisionId"];
            $division_id = (isset($_GET['division_id']) ? $_GET['division_id'] : ($div_id ? $div_id : 0));
            if ($division_id){
                setcookie("RosteringDivisionId", $division_id, 2147483647);
            }
                
            $this->f3->set('meta_title', 'Data Center');
            $division_str = $this->division_nav($division_id, 'TimesheetReport/dataCentretest', 0, 0, 1);
            // Pass division_str to the template
            $this->f3->set('division_str', $division_str);
            if(isset($_REQUEST['csv_export']) && $_REQUEST['csv_export'] = 'CSV Booking Report')
            {
                $result = $this->getCSVReportData();
               
            
            }
            if(isset($_REQUEST['detailed_csv_export']) && $_REQUEST['detailed_csv_export'] = 'CSV Actual Report')
            {
                $result = $this->getDetailedCSVReportWithContractorData($division_id);
               
            
            }
            $servername = "localhost";
            $username = "tnsmwdztaz";
            $password = "vzZ3mFxE2E";
            $dbname = "tnsmwdztaz";
            // Establish the database connection
            $connect = mysqli_connect($servername, $username, $password, $dbname);
            // Check the connection
            if (!$connect) {
                die("Connection failed: " . mysqli_connect_error());
            }
            // Pass the database connection as an argument to FinesController
            $SwslhdDataCenterController = new SwslhdDataCenterController($connect);
            $SwslhdDataCenterController->dataCentre(); // Assuming this method exists within BookingRegister
            $template = new Template;

            // $div_id = $_COOKIE["RosteringDivisionId"];
            // $division_id = (isset($_GET['division_id']) ? $_GET['division_id'] : ($div_id ? $div_id : 0));
            // if ($division_id){
            //     setcookie("RosteringDivisionId", $division_id, 2147483647);
            // }
                
            // $this->f3->set('meta_title', 'Data Center');
            // $division_str = $this->division_nav($division_id, 'TimesheetReport/dataCentretest', 0, 0, 1);
            // // Pass division_str to the template
            // $this->f3->set('division_str', $division_str);
            $loginUserDivisions = $this->get_divisions($_SESSION['user_id'], 0, 1);
            $loginUserDivisionsArray = explode(',', $loginUserDivisions);
            $loginUserDivisionsArray = array_map('trim', $loginUserDivisionsArray);
            $loginUserDivisionsStr = implode(',', $loginUserDivisionsArray);
            if (!$loginUserDivisionsStr) {
                $loginUserDivisionsStr = 0;
            }
            $clt_sql = "SELECT clt.id as `id`,CONCAT('', clt.name, ' ', clt.surname, '') as 'item_name'
                        FROM rosters rst
                        left join users as location on location.id = rst.site_id
                        LEFT JOIN associations ass1 ON ass1.child_user_id = location.ID AND ass1.association_type_id = 1
                        LEFT JOIN users AS clt ON clt.ID = ass1.parent_user_id
                        WHERE clt.id IS NOT NULL AND rst.division_id in (" . $division_id . ") and rst.division_id in (" . $loginUserDivisionsStr . ")
                            group by clt.id
                        ORDER BY CONCAT('', clt.name, ' ',TRIM(clt.surname), '') asc";

            $cltRecords = $this->getRecordIds($clt_sql);


            $selClt = "<div class='fl' style='padding-right:10px'> "
                        . "<select class='large_textbox' onChange='form.submit()' name='userClt' id='userClt'><option value=''> --Select-- </option>";
            $client_name = '';
            $idList = array();
            foreach ($cltRecords as $key=>$client) {
                $idList[] = $client['id'];
                $selected = "";
                if ($client['id'] == $_REQUEST['userClt']) {
                    $selected = "selected";
                    $client_name = $client['item_name'];
                }
                if($key == 0) 
                {
                    if($_REQUEST['userClt'] == 'all')
                    {
                        $all_selected = "selected";
                    }
                    $selClt .= '<option value="all" ' . $all_selected . '>Select All</option>';
                }

                $clientNameSelect = $client['item_name'];

                $selClt .= '<option value="' . $client['id'] . '" ' . $selected . '>' . $clientNameSelect . '</option>';
            }
            $selClt .= '</select></div>';
            $this->f3->set('selClt', $selClt);
            $this->f3->set('client_name', $client_name);

            // var_dump($_REQUEST['userClt']); die;


            if ($_REQUEST['userClt']) {
                $reportUserClt = $_REQUEST['userClt'];
            } else {
                $reportUserClt = 0;
            }
            if ($_REQUEST['calReportStartDate']) {
                $reportcalReportStartDate = $_REQUEST['calReportStartDate'];
            } else {
                $reportcalReportStartDate = '';
            }
            if ($_REQUEST['calReportEndDate']) {
                $reportcalReportEndDate = $_REQUEST['calReportEndDate'];
            } else {
                $reportcalReportEndDate = '';
            }
            if ($_REQUEST['ti2StartTime']) {
                $reportti2StartTime = $_REQUEST['ti2StartTime'];
            } else {
                $reportti2StartTime = '';
            }
            if ($_REQUEST['ti2FinishTime']) {
                $reportti2FinishTime = $_REQUEST['ti2FinishTime'];
            } else {
                $reportti2FinishTime = '';
            }
            $this->f3->set('calReportStartDate', $reportcalReportStartDate);
            $this->f3->set('calReportEndDate', $reportcalReportEndDate);
            $this->f3->set('ti2StartTime', $reportti2StartTime);
            $this->f3->set('ti2FinishTime', $reportti2FinishTime);


            // var_dump($reportUserClt);die;

            // $site_sql = "SELECT users.id as `idin`, CONCAT(users.name, ' ', users.surname) as `item_name` FROM users
            //             inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = 384 and lookup_answers.table_assoc = 'users' 
            //             inner join users_user_division_groups on users_user_division_groups.user_group_id = '" . $division_id . "' and users_user_division_groups.user_id = lookup_answers.foreign_id
            //             where users_user_division_groups.user_group_id in (" . $loginUserDivisionsStr . ") $userSiteAllowedconditon group by users.id";

            // Determine the condition based on $reportUserClt
            if ($reportUserClt == 'all') {
                $condition = "AND ass1.parent_user_id IN (" . implode(",", $idList) . ")";
            } else {
                $condition = "AND ass1.parent_user_id = '" . $reportUserClt . "'";
            }

            // $site_sql_new = "SELECT users.id as `id`, CONCAT(users.name, ' ', users.surname) as `item_name` 
            //                 FROM users
            //                 inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = 384 and lookup_answers.table_assoc = 'users' 
            //                 inner join users_user_division_groups on users_user_division_groups.user_group_id = '" . $division_id . "' and users_user_division_groups.user_id = lookup_answers.foreign_id
            //                 LEFT JOIN associations ass1 ON ass1.child_user_id = users.ID AND ass1.association_type_id = 1
            //                 where ass1.parent_user_id = '" . $reportUserClt . "' group by users.id";
            // Construct the SQL query
            $site_sql_new = "SELECT users.id AS `id`, CONCAT(users.name, ' ', users.surname) AS `item_name` 
                            FROM users
                            INNER JOIN lookup_answers ON lookup_answers.foreign_id = users.id 
                                AND lookup_answers.lookup_field_id = 384 
                                AND lookup_answers.table_assoc = 'users' 
                            INNER JOIN users_user_division_groups ON users_user_division_groups.user_group_id = '" . $division_id . "' 
                                AND users_user_division_groups.user_id = lookup_answers.foreign_id
                            LEFT JOIN associations ass1 ON ass1.child_user_id = users.ID 
                                AND ass1.association_type_id = 1
                            WHERE 1 = 1 " . $condition . " 
                            GROUP BY users.id";

            $locationRecords = $this->getRecordIds($site_sql_new);
            $selLocation = "<div class='fl' style='padding-right:10px'> "
                            . "<select class='large_textbox multi-select' name='userLocation[]' id ='userLocation'  multiple>";

            // var_dump($userLocation);die;


            foreach ($locationRecords as $location) {
                $selected = "";
                $locationNameSelect = $location['item_name'];
                $selLocation .= '<option value="' . $location['id'] . '" ' . $selected . '>' . $locationNameSelect . '</option>';
            }
            $selLocation .= '</select></div>';
            $this->f3->set('selLocation', $selLocation);
            //var_dump($division_str);die;
            echo $template->render('swslhdPaneltest.htm');
            // Close the database connection
            mysqli_close($connect);
        }

        function dataCentre() {
            $div_id = $_COOKIE["RosteringDivisionId"];
            $division_id = (isset($_GET['division_id']) ? $_GET['division_id'] : ($div_id ? $div_id : 0));
            if ($division_id){
                setcookie("RosteringDivisionId", $division_id, 2147483647);
            }
                
            $this->f3->set('meta_title', 'Data Center');
            $division_str = $this->division_nav($division_id, 'Report/dataCentre', 0, 0, 1);
            // Pass division_str to the template
            $this->f3->set('division_str', $division_str);

            if(isset($_REQUEST['csv_export']) && $_REQUEST['csv_export'] = 'CSV Booking Report')
            {
                $result = $this->getCSVReportData();
            }
            if(isset($_REQUEST['detailed_csv_export']) && $_REQUEST['detailed_csv_export'] = 'CSV Actual Report')
            {
                $result = $this->getDetailedCSVReportData($division_id);
            }

            $servername = "localhost";
            $username = "tnsmwdztaz";
            $password = "vzZ3mFxE2E";
            $dbname = "tnsmwdztaz";
            // Establish the database connection
            $connect = mysqli_connect($servername, $username, $password, $dbname);
            // Check the connection
            if (!$connect) {
                die("Connection failed: " . mysqli_connect_error());
            }
            // Pass the database connection as an argument to FinesController
            $SwslhdDataCenterController = new SwslhdDataCenterController($connect);
            $SwslhdDataCenterController->dataCentre(); // Assuming this method exists within BookingRegister
            $template = new Template;

            
            $loginUserDivisions = $this->get_divisions($_SESSION['user_id'], 0, 1);
            // var_dump($_SESSION['user_id']); die;

            // var_dump($loginUserDivisions); die;
            $loginUserDivisionsArray = explode(',', $loginUserDivisions);
            $loginUserDivisionsArray = array_map('trim', $loginUserDivisionsArray);
            $loginUserDivisionsStr = implode(',', $loginUserDivisionsArray);
            if (!$loginUserDivisionsStr) {
                $loginUserDivisionsStr = 0;
            }

            if($_SESSION['user_id']) {
                
                if($_SESSION['u_level'] >= '6000') {
                if($_SESSION['user_id'] == '14758000') {
                        $my_client_id = "SELECT parent_user_id 
                                FROM associations 
                                WHERE child_user_id IN (
                                    SELECT parent_user_id 
                                    FROM associations 
                                    WHERE child_user_id = ".$_SESSION['user_id']."
                                )
                                AND parent_user_id NOT IN (
                                    SELECT child_user_id 
                                    FROM associations 
                                    WHERE parent_user_id IN (
                                        SELECT parent_user_id 
                                        FROM associations 
                                        WHERE child_user_id = ".$_SESSION['user_id']."
                                    )
                                ) group by parent_user_id";

                        $my_client_idRecords = $this->getRecordIds($my_client_id);
                        $my_client_idRecords = array_column($my_client_idRecords, 'parent_user_id');

                        // $my_client_idRecordsArray = explode(',', $my_client_idRecords);
                        $my_client_idRecordsArray = array_map('trim', $my_client_idRecords);
                        $my_client_idRecordsStr = implode(',', $my_client_idRecordsArray);

                        $clt_sql = "SELECT clt.id as `id`,CONCAT('', clt.name, ' ', clt.surname, '') as 'item_name'
                                FROM rosters rst
                                left join users as location on location.ID = rst.site_id
                                LEFT JOIN associations ass1 ON ass1.child_user_id = location.ID AND ass1.association_type_id = 1
                                LEFT JOIN users AS clt ON clt.ID = ass1.parent_user_id
                                WHERE clt.ID IS NOT NULL AND rst.division_id in (" . $division_id . ") and rst.division_id in (" . $loginUserDivisionsStr . ")
                                and clt.ID in (".$my_client_idRecordsStr.") group by clt.ID
                                ORDER BY CONCAT('', clt.name, ' ',TRIM(clt.surname), '') asc";
                        

                    }
                    else{
                    // var_dump($_SESSION['u_level']); die;
                    $clt_sql = "SELECT clt.id as `id`,CONCAT('', clt.name, ' ', clt.surname, '') as 'item_name'
                        FROM rosters rst
                        left join users as location on location.ID = rst.site_id
                        LEFT JOIN associations ass1 ON ass1.child_user_id = location.ID AND ass1.association_type_id = 1
                        LEFT JOIN users AS clt ON clt.ID = ass1.parent_user_id
                        WHERE clt.ID IS NOT NULL AND rst.division_id in (" . $division_id . ") and rst.division_id in (" . $loginUserDivisionsStr . ")
                        group by clt.ID
                        ORDER BY CONCAT('', clt.name, ' ',TRIM(clt.surname), '') asc";
                    }
                    
                }
                else
                {
                    $my_client_id = "SELECT parent_user_id 
                            FROM associations 
                            WHERE child_user_id IN (
                                SELECT parent_user_id 
                                FROM associations 
                                WHERE child_user_id = ".$_SESSION['user_id']."
                            )
                            AND parent_user_id NOT IN (
                                SELECT child_user_id 
                                FROM associations 
                                WHERE parent_user_id IN (
                                    SELECT parent_user_id 
                                    FROM associations 
                                    WHERE child_user_id = ".$_SESSION['user_id']."
                                )
                            ) group by parent_user_id";

                    $my_client_idRecords = $this->getRecordIds($my_client_id);
                    $my_client_idRecords = array_column($my_client_idRecords, 'parent_user_id');

                    // $my_client_idRecordsArray = explode(',', $my_client_idRecords);
                    $my_client_idRecordsArray = array_map('trim', $my_client_idRecords);
                    $my_client_idRecordsStr = implode(',', $my_client_idRecordsArray);
                    // var_dump($my_client_idRecordsStr); die;

                    $clt_sql = "SELECT clt.id as `id`,CONCAT('', clt.name, ' ', clt.surname, '') as 'item_name'
                            FROM rosters rst
                            left join users as location on location.ID = rst.site_id
                            LEFT JOIN associations ass1 ON ass1.child_user_id = location.ID AND ass1.association_type_id = 1
                            LEFT JOIN users AS clt ON clt.ID = ass1.parent_user_id
                            WHERE clt.ID IS NOT NULL AND rst.division_id in (" . $division_id . ") and rst.division_id in (" . $loginUserDivisionsStr . ")
                            and clt.ID in (".$my_client_idRecordsStr.") group by clt.ID
                            ORDER BY CONCAT('', clt.name, ' ',TRIM(clt.surname), '') asc";
                }
            }
            

            $cltRecords = $this->getRecordIds($clt_sql);

            $selClt = "<div class='fl' style='padding-right:10px'> "
                        . "<select class='large_textbox' onChange='form.submit()' name='userClt' id ='userClt'><option value=\"\"> Select Client </option>";
            $client_name = '';
            foreach ($cltRecords as $client) {
                if ($client['id'] == $_REQUEST['userClt']) {
                    $selected = "selected";
                    $client_name = $client['item_name'];
                } else {
                    $selected = "";
                }

                $clientNameSelect = $client['item_name'];

                $selClt .= '<option value="' . $client['id'] . '" ' . $selected . '>' . $clientNameSelect . '</option>';
            }
            $selClt .= '</select></div>';
            $this->f3->set('selClt', $selClt);
            $this->f3->set('client_name', $client_name);


            if ($_REQUEST['userClt']) {
                $reportUserClt = $_REQUEST['userClt'];
            } else {
                $reportUserClt = 0;
            }
            if ($_REQUEST['calReportStartDate']) {
                $reportcalReportStartDate = $_REQUEST['calReportStartDate'];
            } else {
                $reportcalReportStartDate = '';
            }
            if ($_REQUEST['calReportEndDate']) {
                $reportcalReportEndDate = $_REQUEST['calReportEndDate'];
            } else {
                $reportcalReportEndDate = '';
            }
            if ($_REQUEST['ti2StartTime']) {
                $reportti2StartTime = $_REQUEST['ti2StartTime'];
            } else {
                $reportti2StartTime = '';
            }
            if ($_REQUEST['ti2FinishTime']) {
                $reportti2FinishTime = $_REQUEST['ti2FinishTime'];
            } else {
                $reportti2FinishTime = '';
            }
            $this->f3->set('calReportStartDate', $reportcalReportStartDate);
            $this->f3->set('calReportEndDate', $reportcalReportEndDate);
            $this->f3->set('ti2StartTime', $reportti2StartTime);
            $this->f3->set('ti2FinishTime', $reportti2FinishTime);


            // var_dump($calReportStartDate);die;

            // $site_sql = "SELECT users.id as `idin`, CONCAT(users.name, ' ', users.surname) as `item_name` FROM users
            //             inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = 384 and lookup_answers.table_assoc = 'users' 
            //             inner join users_user_division_groups on users_user_division_groups.user_group_id = '" . $division_id . "' and users_user_division_groups.user_id = lookup_answers.foreign_id
            //             where users_user_division_groups.user_group_id in (" . $loginUserDivisionsStr . ") $userSiteAllowedconditon group by users.id";

            if($_SESSION['u_level'] >= '6000') {
                if($_SESSION['user_id'] == '14758000') {
                    $my_location_id = "SELECT parent_user_id 
                                FROM associations 
                                WHERE child_user_id = ".$_SESSION['user_id']." ";
                        
                        $my_location_idRecords = $this->getRecordIds($my_location_id);
                        $my_location_idRecords = array_column($my_location_idRecords, 'parent_user_id');

                        // $my_location_idRecordsArray = explode(',', $my_location_idRecords);
                        $my_location_idRecordsArray = array_map('trim', $my_location_idRecords);
                        $my_location_idRecordsStr = implode(',', $my_location_idRecordsArray);
                        // var_dump($my_location_idRecordsStr); die;   
                        $site_sql_new = "SELECT users.id as `id`, CONCAT(users.name, ' ', users.surname) as `item_name` 
                            FROM users
                            inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = 384 and lookup_answers.table_assoc = 'users' 
                            inner join users_user_division_groups on users_user_division_groups.user_group_id = '" . $division_id . "' and users_user_division_groups.user_id = lookup_answers.foreign_id
                            LEFT JOIN associations ass1 ON ass1.child_user_id = users.ID AND ass1.association_type_id = 1
                            where ass1.parent_user_id = '" . $reportUserClt . "' 
                            and users.ID in (".$my_location_idRecordsStr.") group by users.id";
                }
                else
                {
                    $site_sql_new = "SELECT users.id as `id`, CONCAT(users.name, ' ', users.surname) as `item_name` 
                        FROM users
                        inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = 384 and lookup_answers.table_assoc = 'users' 
                        inner join users_user_division_groups on users_user_division_groups.user_group_id = '" . $division_id . "' and users_user_division_groups.user_id = lookup_answers.foreign_id
                        LEFT JOIN associations ass1 ON ass1.child_user_id = users.ID AND ass1.association_type_id = 1
                        where ass1.parent_user_id = '" . $reportUserClt . "' group by users.id";   
                }
            }
            else
            {
                $my_location_id = "SELECT parent_user_id 
                        FROM associations 
                        WHERE child_user_id = ".$_SESSION['user_id']." ";
                
                $my_location_idRecords = $this->getRecordIds($my_location_id);
                $my_location_idRecords = array_column($my_location_idRecords, 'parent_user_id');

                // $my_location_idRecordsArray = explode(',', $my_location_idRecords);
                $my_location_idRecordsArray = array_map('trim', $my_location_idRecords);
                $my_location_idRecordsStr = implode(',', $my_location_idRecordsArray);
                // var_dump($my_location_idRecordsStr); die;   
                $site_sql_new = "SELECT users.id as `id`, CONCAT(users.name, ' ', users.surname) as `item_name` 
                    FROM users
                    inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = 384 and lookup_answers.table_assoc = 'users' 
                    inner join users_user_division_groups on users_user_division_groups.user_group_id = '" . $division_id . "' and users_user_division_groups.user_id = lookup_answers.foreign_id
                    LEFT JOIN associations ass1 ON ass1.child_user_id = users.ID AND ass1.association_type_id = 1
                    where ass1.parent_user_id = '" . $reportUserClt . "' 
                    and users.ID in (".$my_location_idRecordsStr.") group by users.id";
            }

            

            $locationRecords = $this->getRecordIds($site_sql_new);
            $selLocation = "<div class='fl' style='padding-right:10px'> "
                            . "<select class='large_textbox multi-select' name='userLocation[]' id ='userLocation'  multiple><option value=\"\"> Select location </option>";

            // var_dump($userLocation);die;


            foreach ($locationRecords as $location) {
                $selected = "";
                $locationNameSelect = $location['item_name'];
                $selLocation .= '<option value="' . $location['id'] . '" ' . $selected . '>' . $locationNameSelect . '</option>';
            }
            $selLocation .= '</select></div>';
            $this->f3->set('selLocation', $selLocation);
            //var_dump($division_str);die;
            echo $template->render('swslhdPanel.htm');
            // Close the database connection
            mysqli_close($connect);
        }
        

        // Helper function to sanitize date input
        function sanitizeDate($date) {
            return date("d-m-Y", strtotime($date));
        }

        function getCSVReportData() {
            $servername = "localhost";
            $username = "tnsmwdztaz";
            $password = "vzZ3mFxE2E";
            $dbname = "tnsmwdztaz";
            // Establish the database connection
            $connect = mysqli_connect($servername, $username, $password, $dbname);
            // Check the connection
            if (!$connect) {
                die("Connection failed: " . mysqli_connect_error());
            }
            // Array of specific staff IDs received from the Ajax request
            $userLocation = isset($_REQUEST['userLocation']) ? $_REQUEST['userLocation'] : array();

            // Sanitize and prepare staff IDs for the SQL query
            $escaped_userLocation = array_map('intval', $userLocation);

            $id_list = implode(',', $escaped_userLocation);
            

            $whereClauses = [];

            if (!empty($_REQUEST['calReportStartDate'])) {
                $start_date_mysql = $this->sanitizeDate($_REQUEST['calReportStartDate']);
                $whereClauses[] = "STR_TO_DATE(SUBSTRING_INDEX(timestart, ' ', 1), '%d-%m-%Y') >= STR_TO_DATE('$start_date_mysql', '%d-%m-%Y')";
            }

            if (!empty($_REQUEST['calReportEndDate'])) {
                $end_date_mysql = $this->sanitizeDate($_REQUEST['calReportEndDate']);
                $whereClauses[] = "STR_TO_DATE(SUBSTRING_INDEX(timefinish, ' ', 1), '%d-%m-%Y') <= STR_TO_DATE('$end_date_mysql', '%d-%m-%Y')";
            }

            if (!empty($_REQUEST['ti2StartTime'])) {
                $start_time_mysql = $_REQUEST['ti2StartTime'];
                $whereClauses[] = "SUBSTRING_INDEX(timestart, ' ', -1) >= '$start_time_mysql'";
            }

            if (!empty($_REQUEST['ti2FinishTime'])) {
                $end_time_mysql = $_REQUEST['ti2FinishTime'];
                $whereClauses[] = "SUBSTRING_INDEX(timefinish, ' ', -1) <= '$end_time_mysql'";
            }

            $whereClause = implode(' AND ', $whereClauses);

            $sql = "SELECT timestart, staff_id, amountOfGuards, timefinish 
                    FROM bookservice 
                    WHERE staff_id IN ($id_list)";

            if (!empty($whereClause)) {
                $sql .= " AND $whereClause";
            }

            $sql .= " ORDER BY timestart DESC";


            $result = mysqli_query($connect, $sql);


            if ($result) {
                $data = array();

                while ($row = mysqli_fetch_assoc($result)) {
                    $data[] = $row;
                }

            } else {
                echo "Error: " . $sql . "<br>" . mysqli_error($connect);
            }


            // Prepare CSV data
            $csvData = "DATE/TIME OF BOOKING,DAY,NIGHT,SATURDAY,SUNDAY,PUBLIC HOLIDAY,FINISH TIME OF BOOKING,LOCATION/COST CENTRE,NUMBER OF GUARDS,COST PER DAY\n";
            // var_dump($data);

            if (is_array($data)) {
                
                foreach ($data as $row) {
                    $row['amountOfGuards'] = isset($row['amountOfGuards']) && $row['amountOfGuards'] !== '' ? $row['amountOfGuards'] : 0;
                    // Assuming getUserDetails and calculate functions are defined elsewhere in your PHP code
                    $staffFullName = $this->getUserDetails($row['staff_id']);
                    $dayNighthift = $this->calculateDayNightShift($row['timestart'], $row['timefinish'], $row['amountOfGuards']);
                    // var_dump($dayNighthift); die;


                    $csvData .= "{$row['timestart']},{$dayNighthift['weekday_day_shift']},{$dayNighthift['weekday_night_shift']},{$dayNighthift['weekend_saturday_shift']},{$dayNighthift['weekend_sunday_shift']},0,{$row['timefinish']},{$staffFullName},{$row['amountOfGuards']},{$dayNighthift['total_cost']}\n";

                }
            } else {
                $staffFullName = $this->getUserDetails($row['staff_id']);
                $dayNighthift = $this->calculateDayNightShift($row['timestart'], $row['timefinish'], $row['amountOfGuards']);

                $csvData .= "{$row['timestart']},{$dayNighthift['weekday_day_shift']},{$dayNighthift['weekday_night_shift']},{$dayNighthift['weekend_saturday_shift']},{$dayNighthift['weekend_sunday_shift']},0,{$row['timefinish']},{$staffFullName},{$row['amountOfGuards']},{$dayNighthift['total_cost']}\n";

            }

            // Set headers for CSV file download
            header("Content-type: text/csv");
            header("Content-Disposition: attachment; filename=Booking Report.csv");
            header("Pragma: no-cache");
            header("Expires: 0");
        
            // Output CSV data
            echo $csvData;
            exit();
            mysqli_close($connect);


            // return $data;
        }

        function formatDateTime($dateTimeString) {
            // Create a DateTime object from the original string
            $dateTime = new DateTime($dateTimeString);
        
            // Convert the datetime to the desired format
            $formattedDateTime = $dateTime->format('d-m-Y \T\i\m\e: H:i');
        
            return $formattedDateTime;
        }

        function getDetailedCSVReportData($division_id = 0) {
            
            $servername = "localhost";
            $username = "tnsmwdztaz";
            $password = "vzZ3mFxE2E";
            $dbname = "tnsmwdztaz";
            // Establish the database connection
            $connect = mysqli_connect($servername, $username, $password, $dbname);
            // Check the connection
            if (!$connect) {
                die("Connection failed: " . mysqli_connect_error());
            }

            // Array of specific staff IDs received from the Ajax request
            $userLocation = isset($_REQUEST['userLocation']) ? $_REQUEST['userLocation'] : array();

            // Sanitize and prepare staff IDs for the SQL query
            $escaped_userLocation = array_map('intval', $userLocation);

            $id_list = implode(',', $escaped_userLocation);
            

            $whereClauses = [];

            $report_start_date = strtotime($_REQUEST['calReportStartDate']);
            $report_start_time = strtotime($_REQUEST['ti2StartTime']);
            $report_search_start_date = date('Y-m-d', $report_start_date);
            $report_search_start_time = $report_start_time;

            if (!$report_search_start_time) {
                $report_search_start_time = "00:00:00";
            }

            $report_search_start_cmp_time = $report_search_start_date . " " . $report_search_start_time;

            $report_end_date = strtotime($_REQUEST['calReportEndDate']);
            $report_end_time = strtotime($_REQUEST['ti2FinishTime']);
            $report_search_end_date = date('Y-m-d', $report_end_date);
            $report_search_end_time = $report_end_time;

            if (!$report_search_end_time) {
                $report_search_end_time = "00:00:00";
            }
        
            $report_search_end_cmp_time = $report_search_end_date . " " . $report_search_end_time;

            if (!empty($_REQUEST['calReportStartDate'])) {
                $start_date_mysql = $this->sanitizeDate($_REQUEST['calReportStartDate']);
                // $whereClauses[] = "STR_TO_DATE(SUBSTRING_INDEX(rt.start_time_date, ' ', 1), '%d-%m-%Y') >= STR_TO_DATE('$start_date_mysql', '%d-%m-%Y')";
                $whereClauses[] = "rt.start_time_date  >= '$report_search_start_cmp_time'";

            }

            if (!empty($_REQUEST['calReportEndDate'])) {
                $end_date_mysql = $this->sanitizeDate($_REQUEST['calReportEndDate']);
                $whereClauses[] = "rt.start_time_date  <= '$report_search_end_cmp_time'";
            }


            $whereClause = implode(' AND ', $whereClauses);

            $sql = "SELECT DATE(rt.start_time_date) AS DATE, CONCAT('', location.name, ' ', location.surname, '') as 'Location', location.state AS location_state,
                    CONCAT('', emp.name, ' ', emp.surname, '') as 'Employee',
                    CONCAT('', clt.name, ' ', clt.surname, '') as 'Client',
                    (ABS(ROUND(TIME_TO_SEC(TIMEDIFF(rt.start_time_date, rt.finish_time_date)) / 3600, 2))) assigned_hours,
                    rts.leave_id AS leave_id,
                    rts.start_time_date,rts.finish_time_date,IF(rts.start_time_date != '0000-00-00 00:00:00' AND rts.finish_time_date != '0000-00-00 00:00:00',(ABS(ROUND(TIME_TO_SEC(TIMEDIFF(rts.start_time_date, rts.finish_time_date)) / 3600, 2))),0) completed_hours
                    FROM roster_times_staff rts
                    LEFT JOIN users AS emp ON emp.ID = rts.staff_id
                    LEFT Join roster_times rt ON rt.id = rts.roster_time_id 
                    LEFT JOIN rosters rst ON rst.id = rt.roster_id
                    left join users as location on location.id = rst.site_id
                    LEFT JOIN associations ass1 ON ass1.child_user_id = location.ID AND ass1.association_type_id = 1
                    LEFT JOIN users AS clt ON clt.ID = ass1.parent_user_id
                    LEFT JOIN users_user_division_groups ud ON ud.user_id = emp.ID

                    WHERE location.id IN ($id_list)
                    AND ud.user_group_id IN ($division_id)";
                                        

            if (!empty($whereClause)) {
                $sql .= " AND $whereClause";
            }

            $sql .= " AND emp.ID IS NOT NULL AND location.ID IS NOT NULL AND rst.division_id in (108,2100,2102,2103,2104) ORDER BY rt.start_time_date desc";

            // echo $sql; die;


            $result = mysqli_query($connect, $sql);

            // var_dump($result); die;


            if ($result) {
                $data = array();

                while ($row = mysqli_fetch_assoc($result)) {
                    // Format the datetime string using the function
                    $start_time_date = $this->formatDateTime($row['start_time_date']);
                    $row['timestart'] = $start_time_date;
                    
                    $finish_time_date = $this->formatDateTime($row['finish_time_date']);
                    $row['timefinish'] = $finish_time_date;
                    $row['leave'] = 'On Site';
                    if(isset($row['leave_id']) && $row['leave_id'] != null)
                    {
                        if($row['leave_id'] == 1) {
                            $row['leave'] = 'Sick Leave';
                        }
                        elseif($row['leave_id'] == 2) {
                            $row['leave'] = 'Annual Leave';
                        }
                        elseif($row['leave_id'] == 3) {
                            $row['leave'] = 'Public Holiday Not Worked';
                        }
                        elseif($row['leave_id'] == 4) {
                            $row['leave'] = 'Leave Without Pay';
                        }
                    }

                    $data[] = $row;
                }

            } else {
                echo "Error: " . $sql . "<br>" . mysqli_error($connect);
            }

            // var_dump($data); die;
            // echo '<pre>' . json_encode($data, JSON_PRETTY_PRINT) . '</pre>'; die;

            // Prepare CSV data
            $csvData = "Date,Location,Employee,Client,Assigned Hours,Start Day,Start Time,End Day,End Time,Completed Hours,DAY,NIGHT,SATURDAY,SUNDAY,PUBLIC HOLIDAY,DAY RATE,NIGHT RATE,SAT RATE,SUN RATE,PH RATE,COST PER DAY,STATUS\n";

           $day_rate = 33.55;
           $night_rate = 40.77;
           $saturday_rate = 50.21;
           $sunday_rate = 66.85;
           $ph_rate = 83.51;


            if (is_array($data)) {
                
                foreach ($data as $row) {
                    $row['amountOfGuards'] = 1;
                    // Assuming getUserDetails and calculate functions are defined elsewhere in your PHP code
                    $dayNighthift = $this->calculateDayNightShift($row['timestart'], $row['timefinish'], $row['amountOfGuards'], $row['location_state'], $_REQUEST['userClt']);

                    $row['start_Day'] = date("l", strtotime($row['start_time_date']));  
                    $row['end_Day'] = date("l", strtotime($row['finish_time_date']));  
                    if (strpos($row['Location'], ',') !== false) {
                        $row['Location'] = str_replace(',', ' -', $row['Location']);
                    }
                    
                    $csvData .= "{$row['DATE']},{$row['Location']},{$row['Employee']},{$row['Client']},{$row['assigned_hours']},{$row['start_Day']},{$row['start_time_date']},{$row['end_Day']},{$row['finish_time_date']},{$row['completed_hours']},{$dayNighthift['weekday_day_shift']},{$dayNighthift['weekday_night_shift']},{$dayNighthift['weekend_saturday_shift']},{$dayNighthift['weekend_sunday_shift']},{$dayNighthift['weekend_ph_shift']},{$dayNighthift['day_cost']},{$dayNighthift['night_cost']},{$dayNighthift['saturday_cost']},{$dayNighthift['sunday_cost']},{$dayNighthift['ph_cost']},{$dayNighthift['total_cost']},{$row['leave']}\n";

                }
            } else {
                $row['amountOfGuards'] = 1;
                // Assuming getUserDetails and calculate functions are defined elsewhere in your PHP code
                $dayNighthift = $this->calculateDayNightShift($row['timestart'], $row['timefinish'], $row['amountOfGuards'], $row['location_state']);
                $row['start_Day'] = date("l", strtotime($row['start_time_date']));  
                $row['end_Day'] = date("l", strtotime($row['finish_time_date']));  
                if (strpos($row['Location'], ',') !== false) {
                    $row['Location'] = str_replace(',', ' -', $row['Location']);
                }
                
                $csvData .= "{$row['DATE']},{$row['Location']},{$row['Employee']},{$row['Client']},{$row['assigned_hours']},{$row['start_Day']},{$row['start_time_date']},{$row['end_Day']},{$row['finish_time_date']},{$row['completed_hours']},{$dayNighthift['weekday_day_shift']},{$dayNighthift['weekday_night_shift']},{$dayNighthift['weekend_saturday_shift']},{$dayNighthift['weekend_sunday_shift']},{$dayNighthift['weekend_ph_shift']},{$dayNighthift['day_cost']},{$dayNighthift['night_cost']},{$dayNighthift['saturday_cost']},{$dayNighthift['sunday_cost']},{$dayNighthift['ph_cost']},{$dayNighthift['total_cost']},{$row['leave']}\n";

            }

            // Set headers for CSV file download
            header("Content-type: text/csv");
            header("Content-Disposition: attachment; filename=Actual Report.csv");
            header("Pragma: no-cache");
            header("Expires: 0");

            // Output CSV data
            echo $csvData;
            exit();

            mysqli_close($connect);
        }
        
        function getDetailedCSVReportWithContractorData($division_id = 0) {
            $servername = "localhost";
            $username = "tnsmwdztaz";
            $password = "vzZ3mFxE2E";
            $dbname = "tnsmwdztaz";

            // $division_id = (isset($_GET['division_id']) ? $_GET['division_id'] : ($div_id ? $div_id : 0));

            // var_dump($division_id); die;

            // Establish the database connection
            $connect = mysqli_connect($servername, $username, $password, $dbname);
            // Check the connection
            if (!$connect) {
                die("Connection failed: " . mysqli_connect_error());
            }

            // Array of specific staff IDs received from the Ajax request
            $userLocation = isset($_REQUEST['userLocation']) ? $_REQUEST['userLocation'] : array();

            // prd($userLocation);

            if(empty($userLocation)){
                return  "Please select the location to generate the report."; die;
            }

            // Sanitize and prepare staff IDs for the SQL query
            $escaped_userLocation = array_map('intval', $userLocation);

            $id_list = implode(',', $escaped_userLocation);
            

            $whereClauses = [];

            $report_start_date = strtotime($_REQUEST['calReportStartDate']);
            $report_start_time = strtotime($_REQUEST['ti2StartTime']);
            $report_search_start_date = date('Y-m-d', $report_start_date);
            $report_search_start_time = $report_start_time;

            if (!$report_search_start_time) {
                $report_search_start_time = "00:00:00";
            }

            $report_search_start_cmp_time = $report_search_start_date . " " . $report_search_start_time;

            $report_end_date = strtotime($_REQUEST['calReportEndDate']);
            $report_end_time = strtotime($_REQUEST['ti2FinishTime']);
            $report_search_end_date = date('Y-m-d', $report_end_date);
            $report_search_end_time = $report_end_time;

            if (!$report_search_end_time) {
                $report_search_end_time = "00:00:00";
            }
        
            $report_search_end_cmp_time = $report_search_end_date . " " . $report_search_end_time;

            if (!empty($_REQUEST['calReportStartDate'])) {
                $start_date_mysql = $this->sanitizeDate($_REQUEST['calReportStartDate']);
                // $whereClauses[] = "STR_TO_DATE(SUBSTRING_INDEX(rt.start_time_date, ' ', 1), '%d-%m-%Y') >= STR_TO_DATE('$start_date_mysql', '%d-%m-%Y')";
                $whereClauses[] = "rt.start_time_date  >= '$report_search_start_cmp_time'";

            }

            if (!empty($_REQUEST['calReportEndDate'])) {
                $end_date_mysql = $this->sanitizeDate($_REQUEST['calReportEndDate']);
                $whereClauses[] = "rt.start_time_date  <= '$report_search_end_cmp_time'";
            }


            $whereClause = implode(' AND ', $whereClauses);

            // $sql = "SELECT DATE(rt.start_time_date) AS DATE, CONCAT('', location.name, ' ', location.surname, '') as 'Location', location.state AS location_state,
            //         CONCAT('', emp.name, ' ', emp.surname, '') as 'Employee',
            //         CONCAT('', clt.name, ' ', clt.surname, '') as 'Client',
            //         -- IF(provider.id,CONCAT(provider.name, ' ', provider.surname),'Allied') AS `providername`,
            //         (ABS(ROUND(TIME_TO_SEC(TIMEDIFF(rt.start_time_date, rt.finish_time_date)) / 3600, 2))) assigned_hours,
            //         rts.leave_id AS leave_id,
            //         rst.id AS check_roster_id,
            //         rt.id AS check_roster_time_id,
            //         rts.id AS check_roster_time_staff_id,
            //         rts.start_time_date,rts.finish_time_date,IF(rts.start_time_date != '0000-00-00 00:00:00' AND rts.finish_time_date != '0000-00-00 00:00:00',(ABS(ROUND(TIME_TO_SEC(TIMEDIFF(rts.start_time_date, rts.finish_time_date)) / 3600, 2))),0) completed_hours
            //         FROM roster_times_staff rts
            //         LEFT JOIN users AS emp ON emp.ID = rts.staff_id
            //         -- LEFT JOIN users as provider ON provider.ID = users.provider_id
            //         LEFT Join roster_times rt ON rt.id = rts.roster_time_id 
            //         LEFT JOIN rosters rst ON rst.id = rt.roster_id
            //         left join users as location on location.id = rst.site_id
            //         LEFT JOIN associations ass1 ON ass1.child_user_id = location.ID AND ass1.association_type_id = 1
            //         LEFT JOIN users AS clt ON clt.ID = ass1.parent_user_id
            //         WHERE location.id IN ($id_list)";
                    

            // working fine
            $sql = "SELECT 
                        DATE(rt.start_time_date) AS DATE, 
                        CONCAT('', location.name, ' ', location.surname, '') AS 'Location', 
                        location.state AS location_state,
                        CONCAT('', emp.name, ' ', emp.surname, '') AS 'Employee',
                        CONCAT('', clt.name, ' ', clt.surname, '') AS 'Client',
                        (ABS(ROUND(TIME_TO_SEC(TIMEDIFF(rt.start_time_date, rt.finish_time_date)) / 3600, 2))) AS assigned_hours,
                        rts.leave_id AS leave_id,
                        emp.provider_id AS provider_id,
                        rts.start_time_date,
                        rts.finish_time_date,
                        IF(rts.start_time_date != '0000-00-00 00:00:00' AND rts.finish_time_date != '0000-00-00 00:00:00',
                        (ABS(ROUND(TIME_TO_SEC(TIMEDIFF(rts.start_time_date, rts.finish_time_date)) / 3600, 2))), 0) AS completed_hours,
                        CASE 
                            WHEN emp.provider_id = '' OR emp.provider_id = 'N' OR emp.provider_id = '0' THEN 'Allied'
                            ELSE CONCAT('', user.name, ' ', user.surname, '')
                        END AS providername
                    FROM 
                        roster_times_staff rts
                        LEFT JOIN users AS emp ON emp.ID = rts.staff_id
                        LEFT JOIN roster_times rt ON rt.id = rts.roster_time_id 
                        LEFT JOIN rosters rst ON rst.id = rt.roster_id
                        LEFT JOIN users AS location ON location.id = rst.site_id
                        LEFT JOIN associations ass1 ON ass1.child_user_id = location.ID AND ass1.association_type_id = 1
                        LEFT JOIN users AS clt ON clt.ID = ass1.parent_user_id
                        LEFT JOIN users AS user ON user.ID = emp.provider_id
                        LEFT JOIN users_user_division_groups ud ON ud.user_id = emp.ID

                    WHERE 
                        location.id IN ($id_list)
                        AND ud.user_group_id IN ($division_id)
                    ";

            // $sql = "SELECT roster_times_staff.id AS idin, users.id AS `staff_id`, users.employee_id, CONCAT(users.name, ' ', users.surname) AS `Staff`,IF(provider.id,CONCAT(provider.name, ' ', provider.surname),'Allied') AS `providername`, users2.id AS `site_id`, CONCAT(users2.name, ' ', users2.surname) AS `Site`, users2.state, states.item_name location_state, 
            //             userstates.item_name employee_state, 
            //             roster_times.start_time_date AS `rostered_start_time_date`, roster_times.finish_time_date AS `rostered_finish_time_date`, rlt.leave_title, roster_times_staff.start_time_date, roster_times_staff.finish_time_date, roster_times_staff.staff_comment, roster_times_staff.controller_comment, roster_times.minutes_unpaid
            //             FROM rosters
            //             INNER JOIN roster_times ON roster_times.roster_id = rosters.id
            //             INNER JOIN roster_times_staff ON roster_times_staff.roster_time_id = roster_times.id
            //             INNER JOIN users ON users.id = roster_times_staff.staff_id 
            //             left JOIN users provider ON provider.ID = users.provider_id
            //             left JOIN roster_leave_types rlt ON rlt.id = roster_times_staff.leave_id 
            //             left JOIN users2 ON users2.id = rosters.site_id 
            //             LEFT JOIN states ON users2.state = states.id 
            //             LEFT JOIN states userstates ON users.state = userstates.id
            //                 where YEARWEEK(roster_times.start_time_date, 7) = YEARWEEK(CURDATE() - INTERVAL -$weeks WEEK, 7)
            //                 and rosters.division_id = $division_id
            //                 order by users2.name, users.name      
            //                 ";

            // $sql = "SELECT 
            //             DATE(rt.start_time_date) AS DATE, 
            //             CONCAT('', location.name, ' ', location.surname, '') AS 'Location', 
            //             location.state AS location_state,
            //             CONCAT('', emp.name, ' ', emp.surname, '') AS 'Employee',
            //             -- CONCAT('', clt.name, ' ', clt.surname, '') AS 'Client',
            //             (ABS(ROUND(TIME_TO_SEC(TIMEDIFF(rt.start_time_date, rt.finish_time_date)) / 3600, 2))) AS assigned_hours,
            //             rts.leave_id AS leave_id,
            //             -- emp.provider_id AS provider_id,
            //             rts.start_time_date,
            //             rts.finish_time_date,
            //             IF(rts.start_time_date != '0000-00-00 00:00:00' AND rts.finish_time_date != '0000-00-00 00:00:00',
            //             (ABS(ROUND(TIME_TO_SEC(TIMEDIFF(rts.start_time_date, rts.finish_time_date)) / 3600, 2))), 0) AS completed_hours,
            //             -- CASE 
            //             --     WHEN emp.provider_id = '' OR emp.provider_id = 'N' OR emp.provider_id = '0' THEN 'Allied'
            //             --     ELSE CONCAT('', user.name, ' ', user.surname, '')
            //             -- END AS providername
            //         FROM 
            //             roster_times_staff rts
            //             LEFT JOIN users AS emp ON emp.ID = rts.staff_id
            //             LEFT JOIN roster_times rt ON rt.id = rts.roster_time_id 
            //             LEFT JOIN rosters rst ON rst.id = rt.roster_id
            //             LEFT JOIN users AS location ON location.id = rst.site_id
            //             LEFT JOIN associations ass1 ON ass1.child_user_id = location.ID AND ass1.association_type_id = 1
            //             LEFT JOIN users AS clt ON clt.ID = ass1.parent_user_id
            //             -- LEFT JOIN users AS user ON user.ID = emp.provider_id
            //         WHERE 
            //             location.id IN ($id_list)
            //         ";

            // $sql = "SELECT DATE(rt.start_time_date) AS DATE, CONCAT('', location.name, ' ', location.surname, '') as 'Location', location.state AS location_state,
            //         CONCAT('', emp.name, ' ', emp.surname, '') as 'Employee',
            //         -- CONCAT('', clt.name, ' ', clt.surname, '') as 'Client',
            //         -- IF(provider.id,CONCAT(provider.name, ' ', provider.surname),'Allied') AS `providername`,
            //         (ABS(ROUND(TIME_TO_SEC(TIMEDIFF(rt.start_time_date, rt.finish_time_date)) / 3600, 2))) assigned_hours,
            //         rts.leave_id AS leave_id,
            //         rst.id AS check_roster_id,
            //         rt.id AS check_roster_time_id,
            //         rts.id AS check_roster_time_staff_id,
            //         emp.id AS employee_idd,
            //         location.client_id AS client_id,
            //         -- ass1.child_user_id AS child_user_id,
            //         rts.start_time_date,rts.finish_time_date,IF(rts.start_time_date != '0000-00-00 00:00:00' AND rts.finish_time_date != '0000-00-00 00:00:00',(ABS(ROUND(TIME_TO_SEC(TIMEDIFF(rts.start_time_date, rts.finish_time_date)) / 3600, 2))),0) completed_hours
            //         FROM roster_times_staff rts
            //         LEFT JOIN users AS emp ON emp.ID = rts.staff_id
            //         -- LEFT JOIN users as provider ON provider.ID = users.provider_id
            //         LEFT Join roster_times rt ON rt.id = rts.roster_time_id 
            //         LEFT JOIN rosters rst ON rst.id = rt.roster_id
            //         left join users as location on location.ID = rst.site_id
            //         -- LEFT JOIN associations ass1 ON ass1.child_user_id = location.ID AND ass1.association_type_id = 1
            //         -- LEFT JOIN users AS clt ON clt.ID = ass1.parent_user_id
            //         WHERE location.id IN ($id_list)";
                                        

            if (!empty($whereClause)) {
                $sql .= " AND $whereClause";
            }

            $sql .= " AND emp.ID IS NOT NULL AND location.ID IS NOT NULL AND rst.division_id in (108,2100,2102,2103,2104) ORDER BY rt.start_time_date desc";
            // $sql .= " AND emp.ID IS NOT NULL AND emp.employee_id = 'U619'  AND location.ID IS NOT NULL AND rst.division_id in (108,2100,2102,2103,2104) ORDER BY rt.start_time_date desc";

            // echo $sql; die;


            $result = mysqli_query($connect, $sql);

            // while ($row = mysqli_fetch_assoc($result)) {
            //     $data[] = $row;
            // }

            // var_dump($data); die;
            // echo '<pre>' . json_encode($data, JSON_PRETTY_PRINT) . '</pre>'; die;

            if ($result) {
                $data = array();

                while ($row = mysqli_fetch_assoc($result)) {
                    // Format the datetime string using the function
                    $start_time_date = $this->formatDateTime($row['start_time_date']);
                    $row['timestart'] = $start_time_date;
                    
                    $finish_time_date = $this->formatDateTime($row['finish_time_date']);
                    $row['timefinish'] = $finish_time_date;
                    $row['leave'] = 'On Site';
                    if(isset($row['leave_id']) && $row['leave_id'] != null)
                    {
                        if($row['leave_id'] == 1) {
                            $row['leave'] = 'Sick Leave';
                        }
                        elseif($row['leave_id'] == 2) {
                            $row['leave'] = 'Annual Leave';
                        }
                        elseif($row['leave_id'] == 3) {
                            $row['leave'] = 'Public Holiday Not Worked';
                        }
                        elseif($row['leave_id'] == 4) {
                            $row['leave'] = 'Leave Without Pay';
                        }
                    }

                    $data[] = $row;
                }

            } else {
                echo "Error: " . $sql . "<br>" . mysqli_error($connect);
            }

            // var_dump($data); die;
            // echo '<pre>' . json_encode($data, JSON_PRETTY_PRINT) . '</pre>'; die;

            // Prepare CSV data
            // $csvData = "Date,Location,Employee,Provider Name,Client,Assigned Hours,Start Day,Start Time,End Day,End Time,Completed Hours,DAY,NIGHT,SATURDAY,SUNDAY,PUBLIC HOLIDAY,DAY RATE,NIGHT RATE,SAT RATE,SUN RATE,PH RATE,COST PER DAY,STATUS\n";
            $csvData = "Date,Location,Employee,Provider Name,Client,Assigned Hours,Start Day,Start Time,End Day,End Time,Completed Hours,DAY,NIGHT,SATURDAY,SUNDAY,PUBLIC HOLIDAY,DAY RATE,NIGHT RATE,SAT RATE,SUN RATE,PH RATE,COST PER DAY,STATUS\n";

           $day_rate = 33.55;
           $night_rate = 40.77;
           $saturday_rate = 50.21;
           $sunday_rate = 66.85;
           $ph_rate = 83.51;

            if (is_array($data)) {
                
                foreach ($data as $row) {
                    $row['amountOfGuards'] = 1;
                    // Assuming getUserDetails and calculate functions are defined elsewhere in your PHP code
                    $dayNighthift = $this->calculateDayNightShift($row['timestart'], $row['timefinish'], $row['amountOfGuards'], $row['location_state']);

                    $row['start_Day'] = date("l", strtotime($row['start_time_date']));  
                    $row['end_Day'] = date("l", strtotime($row['finish_time_date'])); 
                    if (strpos($row['Location'], ',') !== false) {
                        $row['Location'] = str_replace(',', ' -', $row['Location']);
                    }
 
                    
                    // $csvData .= "{$row['DATE']},{$row['Location']},{$row['Employee']},{$row['providername']},{$row['Client']},{$row['assigned_hours']},{$row['start_Day']},{$row['start_time_date']},{$row['end_Day']},{$row['finish_time_date']},{$row['completed_hours']},{$dayNighthift['weekday_day_shift']},{$dayNighthift['weekday_night_shift']},{$dayNighthift['weekend_saturday_shift']},{$dayNighthift['weekend_sunday_shift']},{$dayNighthift['weekend_ph_shift']},{$dayNighthift['day_cost']},{$dayNighthift['night_cost']},{$dayNighthift['saturday_cost']},{$dayNighthift['sunday_cost']},{$dayNighthift['ph_cost']},{$dayNighthift['total_cost']},{$row['leave']}\n";
                    // $csvData .= "{$row['DATE']},{$row['Location']},{$row['Employee']},{$row['providername']},{$row['assigned_hours']},{$row['start_Day']},{$row['start_time_date']},{$row['end_Day']},{$row['finish_time_date']},{$row['completed_hours']},{$dayNighthift['weekday_day_shift']},{$dayNighthift['weekday_night_shift']},{$dayNighthift['weekend_saturday_shift']},{$dayNighthift['weekend_sunday_shift']},{$dayNighthift['weekend_ph_shift']},0,0,0,0,0,0,{$row['leave']}\n";
                    $csvData .= "{$row['DATE']},{$row['Location']},{$row['Employee']},{$row['providername']},{$row['Client']},{$row['assigned_hours']},{$row['start_Day']},{$row['start_time_date']},{$row['end_Day']},{$row['finish_time_date']},{$row['completed_hours']},{$dayNighthift['weekday_day_shift']},{$dayNighthift['weekday_night_shift']},{$dayNighthift['weekend_saturday_shift']},{$dayNighthift['weekend_sunday_shift']},{$dayNighthift['weekend_ph_shift']},0,0,0,0,0,0,{$row['leave']}\n";

                }
            } else {
                $row['amountOfGuards'] = 1;
                // Assuming getUserDetails and calculate functions are defined elsewhere in your PHP code
                $dayNighthift = $this->calculateDayNightShift($row['timestart'], $row['timefinish'], $row['amountOfGuards'], $row['location_state']);
                $row['start_Day'] = date("l", strtotime($row['start_time_date']));  
                $row['end_Day'] = date("l", strtotime($row['finish_time_date'])); 
                if (strpos($row['Location'], ',') !== false) {
                    $row['Location'] = str_replace(',', '-', $row['Location']);
                }
                
                // $csvData .= "{$row['DATE']},{$row['Location']},{$row['Employee']},{$row['providername']},{$row['Client']},{$row['assigned_hours']},{$row['start_Day']},{$row['start_time_date']},{$row['end_Day']},{$row['finish_time_date']},{$row['completed_hours']},{$dayNighthift['weekday_day_shift']},{$dayNighthift['weekday_night_shift']},{$dayNighthift['weekend_saturday_shift']},{$dayNighthift['weekend_sunday_shift']},{$dayNighthift['weekend_ph_shift']},{$dayNighthift['day_cost']},{$dayNighthift['night_cost']},{$dayNighthift['saturday_cost']},{$dayNighthift['sunday_cost']},{$dayNighthift['ph_cost']},{$dayNighthift['total_cost']},{$row['leave']}\n";
                // $csvData .= "{$row['DATE']},{$row['Location']},{$row['Employee']},{$row['providername']},{$row['assigned_hours']},{$row['start_Day']},{$row['start_time_date']},{$row['end_Day']},{$row['finish_time_date']},{$row['completed_hours']},{$dayNighthift['weekday_day_shift']},{$dayNighthift['weekday_night_shift']},{$dayNighthift['weekend_saturday_shift']},{$dayNighthift['weekend_sunday_shift']},{$dayNighthift['weekend_ph_shift']},0,0,0,0,0,0,{$row['leave']}\n";
                $csvData .= "{$row['DATE']},{$row['Location']},{$row['Employee']},{$row['providername']},{$row['assigned_hours']},{$row['start_Day']},{$row['start_time_date']},{$row['end_Day']},{$row['finish_time_date']},{$row['completed_hours']},{$dayNighthift['weekday_day_shift']},{$dayNighthift['weekday_night_shift']},{$dayNighthift['weekend_saturday_shift']},{$dayNighthift['weekend_sunday_shift']},{$dayNighthift['weekend_ph_shift']},0,0,0,0,0,0,{$row['leave']}\n";

            }
            // Create a footer row that contains the footer message spanning all columns
            $footer = ",,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,\n"; // Add enough commas for the number of columns
            $footer .= "All records are accurate and verified in accordance with the hours signed on and off. All information contained in this report is the property of Allied EDGE. If you are not the intended recipient or this information does not belong to you then please notify our management immediately at 1300 00 3456.\n\n";
            $footer .= "NSW M/ L: 000101885 | ACT M/L #: 17502515 | WA M/L #: CA 571688 ; SA57168 | QLD M/L #: 4184168 | VIC M/ L #: 940 769 50S | NT M/L #: SFL098 | SA M/L #: ISL 298 888 | TAS M/L #: ISL 809678840\n";

            // Append the footer to the CSV data
            $csvData .= $footer;

            // Set headers for CSV file download
            header("Content-type: text/csv");
            header("Content-Disposition: attachment; filename=Actual Report.csv");
            header("Pragma: no-cache");
            header("Expires: 0");

            // Output CSV data
            echo $csvData;
            exit();

            mysqli_close($connect);
        }

        function calculateDayNightShift($timestart, $timefinish, $amountOfGuards, $state = 0, $client_id = 0) {
            // var_dump($client_id); die;
            if($amountOfGuards == '')
            {
                // Return an array containing the durations of the day and night shifts
                return [
                    'weekday_day_shift' => 0,
                    'weekday_night_shift' => 0,
                    'weekend_saturday_shift' => 0,
                    'weekend_sunday_shift' => 0,
                    'weekend_ph_shift' => 0,
                    'total_cost' => 0,
                    'day_cost' => 0,
                    'night_cost' => 0,
                    'saturday_cost' => 0,
                    'sunday_cost' => 0,
                    'ph_cost' => 0,
                ];
            }
            // Parse the timestamps to DateTime objects
            $startDate = $this->parseDate($timestart);
            // $startDate = $this->parseDate('30-06-2024 Time: 23:30');
            
            $endDate = $this->parseDate($timefinish);
            // $endDate = $this->parseDate('01-07-2024 Time: 07:15');

            $durationInSeconds = $endDate->getTimestamp() - $startDate->getTimestamp();

            // Convert seconds to hours
            $durationInHours = $durationInSeconds / (60 * 60);

            // var_dump($durationInHours); die;

            // Determine the rate based on the day and time
            $rate = 0;
            $startday = $startDate->format('w');
            if($this->AusPublicHolidays($startDate->format('Y-m-d') , $state)) {
                $startday = 7;
            }
            $endday = $endDate->format('w');
            if($this->AusPublicHolidays($endDate->format('Y-m-d') , $state)) {
                $endday = 7;
            }
            $startHour = $startDate->format('H');
            $startMin = $startDate->format('i');
            $startHour = $startHour + ($startMin / 60);

            $endHour = $endDate->format('H');
            $endMin = $endDate->format('i');
            $endHour = $endHour + ($endMin / 60);

            // var_dump($startday);
            // var_dump($endday);
            // var_dump($startHour);
            // var_dump($endHour);
        
            $weekday_day_shift = 0;
            $weekday_night_shift = 0;
            $weekend_saturday_shift = 0;
            $weekend_sunday_shift = 0;
            $weekend_ph_shift = 0;
        
            if ($startday != $endday) {
                if ($startday >= 1 && $startday <= 5) { // check if the start day is not weekend
                    if ($startHour < 6) {
                        $weekday_night_shift += (6 - $startHour);
                        $weekday_day_shift += (18 - 6);
                        $weekday_night_shift += (24 - 18);
                    } elseif ($startHour >= 6 && $startHour <= 18) {
                        $weekday_day_shift += (18 - $startHour);
                        $weekday_night_shift += (24 - 18);
                    } elseif ($startHour > 18) {
                        $weekday_night_shift += (24 - $startHour);
                    }
                } elseif ($startday == 0) { // check if the start day is weekend (Sunday)
                    $weekend_sunday_shift += (24 - $startHour);
                } elseif ($startday == 6) { // check if the start day is weekend (Saturday)
                    $weekend_saturday_shift += (24 - $startHour);
                } elseif ($startday == 7) { // check if the start day is holiday (Public Holiday)
                    $weekend_ph_shift += (24 - $startHour);
                }
        
                if ($endday >= 1 && $endday <= 5) { // check if the end day is not weekend
                    if ($endHour < 6) {
                        $weekday_night_shift += $endHour;
                    } elseif ($endHour >= 6 && $endHour <= 18) {
                        $weekday_night_shift += (6 - 0);
                        $weekday_day_shift += $endHour - 6;
                    } elseif ($endHour > 18) {
                        $weekday_night_shift += (6 - 0);
                        $weekday_day_shift += (18 - 6);
                        $weekday_night_shift += $endHour - 18;
                    }
                } elseif ($endday == 0) { // check if the end day is weekend (Sunday)
                    $weekend_sunday_shift += $endHour;
                } elseif ($endday == 6) { // check if the end day is weekend (Saturday)
                    $weekend_saturday_shift += $endHour;
                } elseif ($endday == 7) { // check if the end day is holiday (Public Holiday)
                    $weekend_ph_shift += $endHour;
                }
            } else { // Same day
                if ($startday >= 1 && $startday <= 5) {
                    if (($startHour >= 6 && $startHour <= 18) && ($endHour >= 6 && $endHour <= 18)) {
                        $weekday_day_shift += $durationInHours;
                    } elseif ($startHour < 6 && $endHour < 6) {
                        $weekday_night_shift += $durationInHours;
                    } elseif ($startHour > 18 && $endHour > 18) {
                        $weekday_night_shift += $durationInHours;
                    } elseif ($startHour < 6 && ($endHour >= 6 && $endHour <= 18)) {
                        $weekday_night_shift += (6 - $startHour);
                        $weekday_day_shift += $endHour - 6;
                    } elseif (($startHour >= 6 && $startHour <= 18) && $endHour > 18) {
                        $weekday_day_shift += (18 - $startHour);
                        $weekday_night_shift += $endHour - 18;
                    } elseif ($startHour < 6 && $endHour > 18) {
                        $weekday_night_shift += (6 - $startHour);
                        $weekday_day_shift += (18 - 6);
                        $weekday_night_shift += $endHour - 18;
                    }
                } elseif ($startday == 0) { // check if the start day is weekend (Sunday)
                    $weekend_sunday_shift += $durationInHours;
                } elseif ($startday == 6) { // check if the start day is weekend (Saturday)
                    $weekend_saturday_shift += $durationInHours;
                } elseif ($startday == 7) { // check if the start day is holiday (Public Holiday)
                    $weekend_ph_shift += $durationInHours;
                }
            }

            $total_cost = 0;

            $day_cost = $weekday_day_shift * 34.98;
            $night_cost = $weekday_night_shift * 42.50;
            $saturday_cost = $weekend_saturday_shift * 52.34;
            $sunday_cost = $weekend_sunday_shift * 69.69;
            $ph_cost = $weekend_ph_shift * 87.06;
            $total_cost = ($day_cost + $night_cost + $saturday_cost + $sunday_cost + $ph_cost) * $amountOfGuards;

            $compareDate = new DateTime("2024-07-01", new DateTimeZone("Australia/Sydney"));

            $compareElectionDate = new DateTime("2024-07-29", new DateTimeZone("Australia/Sydney"));

            // if ($startDate >= $compareDate && $endDate >= $compareDate) {
            //     $day_cost = $weekday_day_shift * 34.98;
            //     $night_cost = $weekday_night_shift * 42.50;
            //     $saturday_cost = $weekend_saturday_shift * 52.34;
            //     $sunday_cost = $weekend_sunday_shift * 69.69;
            //     $ph_cost = $weekend_ph_shift * 87.06;
            //     $total_cost = ($day_cost + $night_cost + $saturday_cost + $sunday_cost + $ph_cost) * $amountOfGuards;
            //     // new rate for NSW election commision
                
            // }
            
            if($client_id == '1279') {
                if ($startDate >= $compareDate && $endDate >= $compareDate) {
                    $day_cost = $weekday_day_shift * 34.98;
                    $night_cost = $weekday_night_shift * 42.50;
                    $saturday_cost = $weekend_saturday_shift * 52.34;
                    $sunday_cost = $weekend_sunday_shift * 69.69;
                    $ph_cost = $weekend_ph_shift * 87.06;
                    $total_cost = ($day_cost + $night_cost + $saturday_cost + $sunday_cost + $ph_cost) * $amountOfGuards;
                }
            }
            else if($client_id == '12371') {
                if ($startDate >= $compareElectionDate && $endDate >= $compareElectionDate) { 
                    $day_cost = $weekday_day_shift * 41.29;
                    $night_cost = $weekday_night_shift * 48.43;
                    $saturday_cost = $weekend_saturday_shift * 57.74;
                    $sunday_cost = $weekend_sunday_shift * 74.19;
                    $ph_cost = $weekend_ph_shift * 90.64;
                    $total_cost = ($day_cost + $night_cost + $saturday_cost + $sunday_cost + $ph_cost) * $amountOfGuards;
                }
            }
            else{
                $day_cost = $weekday_day_shift * 0;
                $night_cost = $weekday_night_shift * 0;
                $saturday_cost = $weekend_saturday_shift * 0;
                $sunday_cost = $weekend_sunday_shift * 0;
                $ph_cost = $weekend_ph_shift * 0;
                $total_cost = ($day_cost + $night_cost + $saturday_cost + $sunday_cost + $ph_cost) * $amountOfGuards;
            }
            
            // var_dump($day_cost);
            // var_dump($night_cost);
            // var_dump($saturday_cost);
            // var_dump($sunday_cost);
            // var_dump($ph_cost);
            // var_dump($total_cost); die;
            
            $total_cost = '"' . '$' . number_format($total_cost, 3) . '"';
            $day_cost = '"' . '$' . number_format($day_cost, 3) . '"';
            $night_cost = '"' . '$' . number_format($night_cost, 3) . '"';
            $saturday_cost = '"' . '$' . number_format($saturday_cost, 3) . '"';
            $sunday_cost = '"' . '$' . number_format($sunday_cost, 3) . '"';
            $ph_cost = '"' . '$' . number_format($ph_cost, 3) . '"';

        
            // Return an array containing the durations of the day and night shifts
            return [
                'weekday_day_shift' => $weekday_day_shift,
                'weekday_night_shift' => $weekday_night_shift,
                'weekend_saturday_shift' => $weekend_saturday_shift,
                'weekend_sunday_shift' => $weekend_sunday_shift,
                'weekend_ph_shift' => $weekend_ph_shift,
                'total_cost' => $total_cost,
                'day_cost' => $day_cost,
                'night_cost' => $night_cost,
                'saturday_cost' => $saturday_cost,
                'sunday_cost' => $sunday_cost,
                'ph_cost' => $ph_cost,
            ];
        }

        function AusPublicHolidays($date_check, $state = 0) {

            if($state == 1) {
                $state_name = "Australian Capital Territory";
            }
            elseif($state == 2) {
                $state_name = "New South Wales";
            }
            elseif($state == 3) {
                $state_name = "Northern Territory";
            }
            elseif($state == 4) {
                $state_name = "Queensland";
            }
            elseif($state == 5) {
                $state_name = "South Australia";
            }
            elseif($state == 6) {
                $state_name = "Tasmania";
            }
            elseif($state == 7) {
                $state_name = "Victoria";
            }
            elseif($state == 8) {
                $state_name = "Western Australia";
            }

            $public_holidays = [
                "Australian Capital Territory" => [
                    "New Year's Day" => "2024-01-01",
                    "Australia Day" => "2024-01-26",
                    "Canberra Day" => "2024-03-11",
                    "Good Friday" => "2024-03-29",
                    "Easter Saturday" => "2024-03-30",
                    "Easter Sunday" => "2024-03-31",
                    "Easter Monday" => "2024-04-01",
                    "Anzac Day" => "2024-04-25",
                    "Reconciliation Day" => "2024-05-27",
                    "King’s Birthday" => "2024-06-10",
                    "Labour Day" => "2024-10-07",
                    "Christmas Day" => "2024-12-25",
                    "Boxing Day" => "2024-12-26",
                    "New Year's Day - 2025" => "2025-01-01",
                    "Australia Day - 2025" => "2025-01-27",
                    "Canberra Day - 2025" => "2025-03-10",
                    "Good Friday - 2025" => "2025-04-18",
                    "Easter Saturday - 2025" => "2025-04-19",
                    "Easter Sunday - 2025" => "2025-04-20",
                    "Easter Monday - 2025" => "2025-04-21",
                    "Anzac Day - 2025" => "2025-04-25",
                    "Reconciliation Day - 2025" => "2025-06-02",
                    "King’s Birthday - 2025" => "2025-06-09",
                    "Labour Day - 2025" => "2025-10-06",
                    "Christmas Day - 2025" => "2025-12-25",
                    "Boxing Day - 2025" => "2025-12-26"
                ],
                "New South Wales" => [
                    "New Year's Day" => "2024-01-01",
                    "Australia Day" => "2024-01-26",
                    "Good Friday" => "2024-03-29",
                    "Easter Saturday" => "2024-03-30",
                    "Easter Sunday" => "2024-03-31",
                    "Easter Monday" => "2024-04-01",
                    "Anzac Day" => "2024-04-25",
                    "King's Birthday" => "2024-06-10",
                    "Labour Day" => "2024-10-07",
                    "Christmas Day" => "2024-12-25",
                    "Boxing Day" => "2024-12-26",
                    "New Year's Day" => "2025-01-01",
                    "Australia Day - 2025" => "2025-01-27",
                    "Good Friday - 2025" => "2025-04-18",
                    "Easter Saturday - 2025" => "2025-04-19",
                    "Easter Sunday - 2025" => "2025-04-20",
                    "Easter Monday - 2025" => "2025-04-21",
                    "Anzac Day - 2025" => "2025-04-25",
                    "King's Birthday - 2025" => "2025-06-09",
                    "Labour Day - 2025" => "2025-10-06",
                    "Christmas Day - 2025" => "2025-12-25",
                    "Boxing Day - 2025" => "2025-12-26"
                ],
                "Northern Territory" => [
                    "New Year's Day" => "2024-01-01",
                    "Australia Day" => "2024-01-26",
                    "Good Friday" => "2024-03-29",
                    "Easter Saturday" => "2024-03-30",
                    "Easter Sunday" => "2024-03-31",
                    "Easter Monday" => "2024-04-01",
                    "Anzac Day" => "2024-04-25",
                    "May Day" => "2024-05-06",
                    "King's Birthday" => "2024-06-10",
                    "Picnic Day" => "2024-08-05",
                    "Christmas Eve" => "2024-12-24 (from 7pm to midnight)",
                    "Christmas Day" => "2024-12-25",
                    "Boxing Day" => "2024-12-26",
                    "New Year's Eve" => "2024-12-31 (from 7pm to midnight)",
                    "New Year's Day" => "2025-01-01",
                    "Australia Day - 2025" => "2025-01-27",
                    "Good Friday - 2025" => "2025-04-18",
                    "Easter Saturday - 2025" => "2025-04-19",
                    "Easter Sunday - 2025" => "2025-04-20",
                    "Easter Monday - 2025" => "2025-04-21",
                    "Anzac Day - 2025" => "2025-04-25",
                    "May Day - 2025" => "2025-05-05",
                    "King's Birthday - 2025" => "2025-06-09",
                    "Picnic Day - 2025" => "2025-08-04",
                    "Christmas Eve - 2025" => "2025-12-24 (from 7pm to midnight)",
                    "Christmas Day - 2025" => "2025-12-25",
                    "Boxing Day - 2025" => "2025-12-26",
                    "New Year's Eve - 2025" => "2025-12-31 (from 7pm to midnight)"
                ],
                "Queensland" => [
                    "New Year's Day" => "2024-01-01",
                    "Australia Day" => "2024-01-26",
                    "Good Friday" => "2024-03-29",
                    "The day after Good Friday" => "2024-03-30",
                    "Easter Sunday" => "2024-03-31",
                    "Easter Monday" => "2024-04-01",
                    "Anzac Day" => "2024-04-25",
                    "Labour Day" => "2024-05-06",
                    "Royal Queensland Show (Brisbane area only)" => "2024-08-14",
                    "King’s Birthday" => "2024-10-07",
                    "Christmas Eve" => "2024-12-24 (from 6pm to midnight)",
                    "Christmas Day" => "2024-12-25",
                    "Boxing Day" => "2024-12-26",
                    "New Year's Day - 2025" => "2025-01-01",
                    "Australia Day - 2025" => "2025-01-27",
                    "Good Friday - 2025" => "2025-04-18",
                    "The day after Good Friday - 2025" => "2025-04-19",
                    "Easter Sunday - 2025" => "2025-04-20",
                    "Easter Monday - 2025" => "2025-04-21",
                    "Anzac Day - 2025" => "2025-04-25",
                    "Labour Day - 2025" => "2025-05-05",
                    "Royal Queensland Show (Brisbane area only) - 2025" => "2025-08-13",
                    "King’s Birthday - 2025" => "2025-10-06",
                    "Christmas Eve - 2025" => "2025-12-24 (from 6pm to midnight)",
                    "Christmas Day - 2025" => "2025-12-25",
                    "Boxing Day - 2025" => "2025-12-26"
                ],
                "South Australia" => [
                    "New Year's Day" => "2024-01-01",
                    "Australia Day" => "2024-01-26",
                    "Adelaide Cup Day" => "2024-03-11",
                    "Good Friday" => "2024-03-29",
                    "Easter Saturday" => "2024-03-30",
                    "Easter Sunday" => "2024-03-31",
                    "Easter Monday" => "2024-04-01",
                    "Anzac Day" => "2024-04-25",
                    "King's Birthday" => "2024-06-10",
                    "Labour Day" => "2024-10-07",
                    "Christmas Eve" => "2024-12-24 (from 7pm to midnight)",
                    "Christmas Day" => "2024-12-25",
                    "Proclamation Day public holiday / Boxing Day" => "2024-12-26",
                    "New Year's Eve" => "2024-12-31 (from 7pm to midnight)",
                    "New Year's Day - 2025" => "2025-01-01",
                    "Australia Day - 2025" => "2025-01-27",
                    "Adelaide Cup Day - 2025" => "2025-03-10",
                    "Good Friday - 2025" => "2025-04-18",
                    "Easter Saturday - 2025" => "2025-04-19",
                    "Easter Sunday - 2025" => "2025-04-20",
                    "Easter Monday - 2025" => "2025-04-21",
                    "Anzac Day - 2025" => "2025-04-25",
                    "King's Birthday - 2025" => "2025-06-09",
                    "Labour Day - 2025" => "2025-10-06",
                    "Christmas Eve - 2025" => "2025-12-24 (from 7pm to midnight)",
                    "Christmas Day - 2025" => "2025-12-25",
                    "Proclamation Day public holiday / Boxing Day - 2025" => "2025-12-26",
                    "New Year's Eve - 2025" => "2025-12-31 (from 7pm to midnight)"
                ],
                "Tasmania" => [
                    "New Year's Day" => "2024-01-01",
                    "Australia Day" => "2024-01-26",
                    "Royal Hobart Regatta (only observed in certain areas of the state)" => "2024-02-12",
                    "Eight Hours Day" => "2024-03-11",
                    "Good Friday" => "2024-03-29",
                    "Easter Monday" => "2024-04-01",
                    "Easter Tuesday (generally Tasmanian Public Service only)" => "2024-04-02",
                    "Anzac Day" => "2024-04-25",
                    "King's Birthday" => "2024-06-10",
                    "Recreation Day (areas of the state that don’t observe Royal Hobart Regatta)" => "2024-11-04",
                    "Christmas Day" => "2024-12-25",
                    "Boxing Day" => "2024-12-26",
                    "New Year's Day - 2025" => "2025-01-01",
                    "Australia Day - 2025" => "2025-01-27",
                    "Royal Hobart Regatta (only observed in certain areas of the state) - 2025" => "2025-02-10",
                    "Eight Hours Day - 2025" => "2025-03-10",
                    "Good Friday - 2025" => "2025-04-18",
                    "Easter Monday - 2025" => "2025-04-21",
                    "Easter Tuesday (generally Tasmanian Public Service only) - 2025" => "2025-04-22",
                    "Anzac Day - 2025" => "2025-04-25",
                    "King's Birthday - 2025" => "2025-06-09",
                    "Recreation Day (areas of the state that don’t observe Royal Hobart Regatta) - 2025" => "2025-11-03",
                    "Christmas Day - 2025" => "2025-12-25",
                    "Boxing Day - 2025" => "2025-12-26"
                ],
                "Victoria" => [
                    "New Year's Day" => "2024-01-01",
                    "Australia Day" => "2024-01-26",
                    "Labour Day" => "2024-03-11",
                    "Good Friday" => "2024-03-29",
                    "Saturday before Easter Sunday" => "2024-03-30",
                    "Easter Sunday" => "2024-03-31",
                    "Easter Monday" => "2024-04-01",
                    "Anzac Day" => "2024-04-25",
                    "King's Birthday" => "2024-06-10",
                    "Friday before AFL Grand Final" => "2024-09-27",
                    "Melbourne Cup" => "2024-11-05",
                    "Christmas Day" => "2024-12-25",
                    "Boxing Day" => "2024-12-26",
                    "New Year's Day - 2025" => "2025-01-01",
                    "Australia Day - 2025" => "2025-01-27",
                    "Labour Day - 2025" => "2025-03-10",
                    "Good Friday - 2025" => "2025-04-18",
                    "Saturday before Easter Sunday - 2025" => "2025-04-19",
                    "Easter Sunday - 2025" => "2025-04-20",
                    "Easter Monday - 2025" => "2025-04-21",
                    "Anzac Day - 2025" => "2025-04-25",
                    "King's Birthday - 2025" => "2025-06-09",
                    "Friday before the AFL Grand Final - 2025" => "2025-09-26", // Date subject to AFL schedule
                    "Melbourne Cup - 2025" => "2025-11-04",
                    "Christmas Day - 2025" => "2025-12-25",
                    "Boxing Day - 2025" => "2025-12-26"
                ],
                "Western Australia" => [
                    "New Year's Day" => "2024-01-01",
                    "Australia Day" => "2024-01-26",
                    "Labour Day" => "2024-03-04",
                    "Good Friday" => "2024-03-29",
                    "Easter Sunday" => "2024-03-31",
                    "Easter Monday" => "2024-04-01",
                    "Anzac Day" => "2024-04-25",
                    "Western Australia Day" => "2024-06-03",
                    "King's Birthday (some regional areas in WA hold the King's Birthday public holiday on a different date)" => "2024-09-23",
                    "Christmas Day" => "2024-12-25",
                    "Boxing Day" => "2024-12-26",
                    "New Year's Day - 2025" => "2025-01-01",
                    "Australia Day - 2025" => "2025-01-27",
                    "Labour Day - 2025" => "2025-03-03",
                    "Good Friday - 2025" => "2025-04-18",
                    "Easter Sunday - 2025" => "2025-04-20",
                    "Easter Monday - 2025" => "2025-04-21",
                    "Anzac Day - 2025" => "2025-04-25",
                    "Western Australia Day - 2025" => "2025-06-02",
                    "King's Birthday - 2025" => "2025-09-29", // Some regional areas might observe on a different date
                    "Christmas Day - 2025" => "2025-12-25",
                    "Boxing Day - 2025" => "2025-12-26"
                ]
            ];
            

            foreach ($public_holidays as $st => $dates) {
                if (in_array($date_check, $public_holidays[$state_name])) {
                    return true;
                }
            }
            return false;

        }


        function parseDate($dateString) {
            // Extract date and time parts from the string
            $parts = explode(' ', $dateString);
            $datePart = $parts[0];
            $timePart = $parts[2];
        
            // Extract day, month, and year from the date part
            $dateParts = explode('-', $datePart);
            $day = (int)$dateParts[0];
            $month = (int)$dateParts[1];
            $year = (int)$dateParts[2];
        
            // Extract hours and minutes from the time part
            $timeParts = explode(':', $timePart);
            $hours = (int)$timeParts[0];
            $minutes = (int)$timeParts[1];
            // $minutes_in_hours = $minutes / 60;


            // $hours = (float)($hours + $minutes_in_hours);
        
            // Return a new DateTime object
            return new DateTime(sprintf('%04d-%02d-%02d %02d:%02d', $year, $month, $day, $hours, $minutes));
        }
        

        function getUserDetails($staff_id) {

            $servername = "localhost";
            $username = "tnsmwdztaz";
            $password = "vzZ3mFxE2E";
            $dbname = "tnsmwdztaz";

            $connection = mysqli_connect($servername, $username, $password, $dbname);

            if (!$connection) {
                die("Connection failed: " . mysqli_connect_error());
            }

            if (isset($staff_id)) {
                $query = "SELECT name, surname FROM users WHERE id = $staff_id";

                $result = mysqli_query($connection, $query);

                if ($result && mysqli_num_rows($result) > 0) {
                    $row = mysqli_fetch_assoc($result);
                    // Constructing response in the same format as expected by JavaScript function
                    $data = [
                        [
                            'name' => $row['name'],
                            'surname' => $row['surname']
                        ]
                    ];
                    if ($data && isset($data[0]['name'], $data[0]['surname'])) {
                        $userName = $data[0]['name'] . ' ' . $data[0]['surname'];
                    } else {
                        $userName = '';
                    }
                
                    return $userName;
                } 
            } 

            mysqli_close($connection);
        }
        
        


        function OnboardingPanel() {
            $servername = "localhost";
            $username = "tnsmwdztaz";
            $password = "vzZ3mFxE2E";
            $dbname = "tnsmwdztaz";
            // Establish the database connection
            $connect = mysqli_connect($servername, $username, $password, $dbname);
            // Check the connection
            if (!$connect) {
                die("Connection failed: " . mysqli_connect_error());
            }
            // Pass the database connection as an argument to FinesController
            $onboardingController = new OnboardingController($connect);
            $onboardingController->OnboardingPanel(); // Assuming this method exists within FinesController
            $template = new Template;
            echo $template->render('onboardingPanel.htm');
            // Close the database connection
            mysqli_close($connect);
        }
        
        function SupplierOnboardingPanel() {
            $servername = "localhost";
            $username = "tnsmwdztaz";
            $password = "vzZ3mFxE2E";
            $dbname = "tnsmwdztaz";
            // Establish the database connection
            $connect = mysqli_connect($servername, $username, $password, $dbname);
            // Check the connection
            if (!$connect) {
                die("Connection failed: " . mysqli_connect_error());
            }
            // Pass the database connection as an argument to FinesController
            $onboardingController = new SupplierOnboardingController($connect);
            $onboardingController->OnboardingPanel(); // Assuming this method exists within FinesController
            $template = new Template;
            $this->f3->set('meta_title', "Supplier's Onboarding Panel");
            echo $template->render('supplieronboardingPanel.htm');
            // Close the database connection
            mysqli_close($connect);
        }

 /*       
 function OnboardingDetails() {
            $servername = "localhost";
            $username = "tnsmwdztaz";
            $password = "vzZ3mFxE2E";
            $dbname = "tnsmwdztaz";
            // Establish the database connection
            $connect = mysqli_connect($servername, $username, $password, $dbname);
            // Check the connection
            if (!$connect) {
                die("Connection failed: " . mysqli_connect_error());
            }
            // Pass the database connection as an argument to FinesController
            $onboardingDetails = new OnboardingController($connect);
            $onboardingDetails->OnboardingDetails(); // Assuming this method exists within FinesController
            $template = new Template;
            echo $template->render('onboardingDetails.htm');
            // Close the database connection
            mysqli_close($connect);
        }

*/
        function Ticket() {
            $p = new UserController();
            $this->f3->set('content', $p->Ticket());
            $template = new Template;
            echo $template->render('layout.htm');
        }

        function PerformanceReview() {
            $p = new UserController();
            $this->f3->set('content', $p->PerformanceReview());
            $template = new Template;
            echo $template->render('layout.htm');
        }
        
        
    function getSpecificPeriodical() {
        $p = new UserController();
        $this->f3->set('content', $p->getSpecificPeriodical());
        $template = new Template;
        echo $template->render('layout.htm');
    }

    //Job module function start
    function JobList($f3, $params) {
        $Jobs = new Jobs($f3); //create an object of Job controller
        $Jobs->index();  //Call job list func
    }
    
    //RosterByDay module function start
    function RosterByDay($f3, $params) {
        $RosterByDay = new RosterByDay($f3); //create an object of Job controller
        $RosterByDay->index();  //Call job list func
    }
    
    //RosterByDay module function start
    function ManageRosterByDay($f3, $params) {
        $RosterByDay = new RosterByDay($f3); //create an object of Job controller
        $RosterByDay->indexManage();  //Call job list func
    }

    function CreateJob($f3, $params) {
        $Jobs = new Jobs($f3); //create an object of Job controller
        $Jobs->create();  //Call create job func   
    }

    function EditJob($f3, $params) {
        $job_id = $_GET['job-id'];
        $Jobs = new Jobs($f3); //create an object of Job controller
        $Jobs->edit($job_id);  //Call job edit func
    }

    function DeleteJob($f3, $params) {
        $job_id = $_GET['id'];
        $Jobs = new Jobs($f3); //create an object of Job controller
        $Jobs->delete($job_id);  //Call job edit func
    }

    function jobAllocation($f3, $params) {
        $job_id = $_GET['job-id'];
        $Jobs = new Jobs($f3); //create an object of Job controller
        $Jobs->allocation($job_id);  //Call job edit func
    }

	//RosterLogs module function start
    function RosterList($f3, $params) {
        $RosterLogs = new RosterLogs($f3); //create an object of Job controller
        $RosterLogs->index();  //Call job list func
    }
    /* --------------------------Toolbox Training -----------------------------   */
    
    function ToolboxCourseIndex($f3, $params) {
        $Controller = new ToolboxAlliedCoursesController($f3); //create an object of Job controller
        $Controller->index();  //Call job list func
    }
    
    function ToolboxCourseAdd($f3, $params) {
        $Controller = new ToolboxAlliedCoursesController($f3); //create an object of Course controller
        $Controller->create();  //Call Course Add func
    }
    
    function ToolboxCourseEdit($f3, $params) {
        $course_id = $_GET['course-id'];
        $Controller = new ToolboxAlliedCoursesController($f3); //create an object of Course controller
        $Controller->edit($course_id);  //Call Course edit func
    }
    
    function ToolboxLessonIndex($f3, $params) {  
        $Controller = new ToolboxAlliedLessonsController($f3); //create an object of Job controller
        $Controller->index();  //Call job list func
    }
    function ToolboxLessonsAdd($f3, $params) {
        $Controller = new ToolboxAlliedLessonsController($f3); //create an object of Lessons controller
        $Controller->create();  //Call Lessons Add func
    }
    function ToolboxLessonsEdit($f3, $params) {
        $course_id = $_GET['course_id'];
        $id = $_GET['id'];
        $Controller = new ToolboxAlliedLessonsController($f3); //create an object of Lessons controller
        $Controller->edit($course_id,$id);  //Call Course edit func
    }
    
    function ToolboxQuestionIndex($f3, $params) {  
       
        $Controller = new ToolboxAlliedQuestionsController($f3); //create an object of Questions controller      
        $Controller->index();  //Call job list func
    }
    
    function ToolboxQuestionAdd($f3, $params) {
        $Controller = new ToolboxAlliedQuestionsController($f3); //create an object of Questions controller
        $Controller->create();  //Call Lessons Add func
    }
    
    function ToolboxQuestionEdit($f3, $params) {
        $lesson_id = $_GET['lesson_id'];
        $id = $_GET['id'];
        $Controller = new ToolboxAlliedQuestionsController($f3); //create an object of Questions controller
        $Controller->edit($lesson_id,$id);  //Call Course edit func
    }
    
    function ToolboxUser($f3, $params) {  
        
        $Controller = new ToolboxAlliedAssignController($f3); //create an object of Questions controller
        $Controller->toolbox_user_index();  //Call job list func
    }
    
    
    function AlliedMyToolbox(){
        $_SESSION['toolbox'] = "user";
        $Controller = new ToolboxAlliedController($f3); //create an object of Job controller
        $Controller->index();  //Call job list func
    }
    
    function AlliedUserToolbox(){
        $_SESSION['toolbox'] = "alluser";
        $Controller = new ToolboxAlliedController($f3); //create an object of Job controller
        $Controller->index();  //Call job list func
    }
    
    function AlliedUserToolboxView($f3, $params) {  
          $_SESSION['toolbox'] = "alluser";
        $Controller = new ToolboxAlliedController($f3); //create an object of Questions controller
        $Controller->view();  //Call job list func
    }
    
    
    function AlliedToolboxView($f3, $params) {  
        $_SESSION['toolbox'] = "oneuser";
        $Controller = new ToolboxAlliedController($f3); //create an object of Questions controller
        $Controller->view();  //Call job list func
    }
    
    function AlliedUserToolBoxTest($f3, $params) {  
          $_SESSION['toolbox'] = "alluser";
        $Controller = new ToolboxAlliedController($f3); //create an object of Questions controller
        $Controller->trainingTest();  //Call job list func
    }
    
    
    function AlliedToolBoxTest($f3, $params) {  
         $_SESSION['toolbox'] = "oneuser";
        $Controller = new ToolboxAlliedController($f3); //create an object of Questions controller
        $Controller->trainingTest();  //Call job list func
    }
    
    
    /* --------------------------- Training Module ------------------------------ */
    
    /* alleid training Module */
    function CourseIndex($f3, $params) {
        $Controller = new TrainingAlliedCoursesController($f3); //create an object of Job controller
        $Controller->index();  //Call job list func
    }
    function CourseAdd($f3, $params) {
        $Controller = new TrainingAlliedCoursesController($f3); //create an object of Course controller
        $Controller->create();  //Call Course Add func
    }
    function CourseEdit($f3, $params) {
        $course_id = $_GET['course-id'];
        $Controller = new TrainingAlliedCoursesController($f3); //create an object of Course controller
        $Controller->edit($course_id);  //Call Course edit func
    }
    function LessonIndex($f3, $params) {  
        $Controller = new TrainingAlliedLessonsController($f3); //create an object of Job controller
        $Controller->index();  //Call job list func
    }
    function LessonsAdd($f3, $params) {
        $Controller = new TrainingAlliedLessonsController($f3); //create an object of Lessons controller
        $Controller->create();  //Call Lessons Add func
    }
    function LessonsEdit($f3, $params) {
        $course_id = $_GET['course_id'];
        $id = $_GET['id'];
        $Controller = new TrainingAlliedLessonsController($f3); //create an object of Lessons controller
        $Controller->edit($course_id,$id);  //Call Course edit func
    }
    
     function QuestionIndex($f3, $params) {  
        $Controller = new TrainingAlliedQuestionsController($f3); //create an object of Questions controller
        $Controller->index();  //Call job list func
    }
    function QuestionAdd($f3, $params) {
        $Controller = new TrainingAlliedQuestionsController($f3); //create an object of Questions controller
        $Controller->create();  //Call Lessons Add func
    }
    
    function QuestionEdit($f3, $params) {
        $lesson_id = $_GET['lesson_id'];
        $id = $_GET['id'];
        $Controller = new TrainingAlliedQuestionsController($f3); //create an object of Questions controller
        $Controller->edit($lesson_id,$id);  //Call Course edit func
    } 
    
    function AlliedTrainingIndex(){
        $Controller = new TrainingAlliedController($f3); //create an object of Job controller
        $Controller->index();  //Call job list func
    }
    
    function AlliedTrainingChart(){
        $Controller = new TrainingAlliedController($f3); //create an object of Job controller
        $Controller->trainingChart();  //Call job list func
    }
    
    
    function AlliedMyTrainingPending(){
        $Controller = new TrainingAlliedController($f3); //create an object of Job controller
        $Controller->myTrainingPending();  //Call job list func
    }
    
    function AssignTraining(){
        
        $Controller = new TrainingAlliedAssignController($f3); //create an object of Job controller
        $Controller->index();  //Call job list func
    }
    
     function UserAssign($f3, $params) {  
        $Controller = new TrainingAlliedAssignController($f3); //create an object of Questions controller
        $Controller->userAssign();  //Call job list func
    }
    
     function trainingUser($f3, $params) {  
        
        $Controller = new TrainingAlliedAssignController($f3); //create an object of Questions controller
        $Controller->training_user_index();  //Call job list func
    }
    
    function AddTraining(){        
        $Controller = new TrainingAlliedAssignController($f3); //create an object of Job controller
        $Controller->create();  //Call job list func
    }
    
     
    function EditTraining(){        
        $Controller = new TrainingAlliedAssignController($f3); //create an object of Job controller
        $Controller->edit();  //Call job list func
    }
    
    function trainingTest($f3, $params) {
        
        $Controller = new TrainingAlliedController($f3); //create an object of Questions controller
        $Controller->trainingTest();  //Call job list func
    }
    
     function AlliedTrainingView($f3, $params) {  
        $Controller = new TrainingAlliedController($f3); //create an object of Questions controller
        $Controller->view();  //Call job list func
    }

    function Newsletters($f3, $params){
        $Controller = new NewsLettersAlliedController($f3); //create an object of Job controller
        $Controller->index($params['p1']);  //Call job list func
    }
}
