<?php

add_action('wp_head', 'functions');
add_action('wp_ajax_client_change', 'client_change_callback' );
add_action('wp_ajax_sort_table', 'sort_table_callback');
add_action('wp_ajax_task_assign', 'task_assign_callback');
add_action('wp_ajax_td_task_assign', 'td_task_assign_callback');
add_action('wp_ajax_update_hours', 'update_hours_callback');
add_action('wp_ajax_mysql_timestamp', 'mysql_timestamp');
add_action('wp_ajax_admin_request', 'admin_request_callback');
add_action('wp_ajax_update_notes', 'update_notes_callback');
add_action('wp_ajax_search_users', 'search_users');
add_action('wp_ajax_update_status', 'update_status');
add_action('wp_ajax_email_file_delete', 'email_file_delete');
add_action('wp_ajax_duplicate_task', 'duplicate_task');
add_action('wp_ajax_group_status', 'group_status_update');

# ----------------------------------------------
#
# Dashboard Update Hours
#
# ----------------------------------------------

wp_enqueue_script('jquery');

function update_hours_callback() {
    global $wpdb;
    $id = $_POST['id'];
    $new_hours = $_POST['new_hours'];
    
    $data_update = array('hours'=>$new_hours);
    $data_where = array('display_name'=>$name);
    
    //if ($wpdb->update('va_users', $data_update, $data_where)) {
    if ($wpdb->query($wpdb->prepare("UPDATE `va_users` SET hours = '$new_hours' WHERE ID = '$id'"))) {
        echo true;
    }
    else {
        echo wp_send_json(array('where' => $data_where, 'update' => $data_update));
    };
    
    wp_die();
};

# ----------------------------------------------
# Give Timestamp
# ----------------------------------------------

function mysql_timestamp() {
    echo current_time('mysql');
    
    wp_die();
};

# ----------------------------------------------
#
# SELECT CLIENT
#
# ----------------------------------------------

function client_change_callback() {
    global $wpdb;
    $client = $_POST['client'];
    $referer = $_POST['referer'];
    $current_user = wp_get_current_user();
    $admin = $current_user->display_name;
    if ($referer != '/archives/') {
        if ($admin == 'April Dodson') {
            $columns = $wpdb->get_results("SELECT COLUMN_NAME, DATA_TYPE, COLUMN_COMMENT 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_NAME='va_job_requests' 
                AND COLUMN_NAME NOT IN ('email','category','description','task_entered','task_start','task_complete','recurring','file','notes','email_file_1','email_file_2','email_file_3','email_file_4','email_file_5')"); 
            if (!empty($client)) {
                $results = $wpdb->get_results("SELECT id, name, title, status, assigned_to, complete_by, frequency
                    FROM va_job_requests 
                    WHERE name='$client'
                    AND status NOT LIKE 'C%'
                    ORDER BY task_entered DESC");
            }
            else {
                $results = $wpdb->get_results("(SELECT id, name, category, title, status, assigned_to, complete_by, frequency
                    FROM va_job_requests WHERE status = 'Complete' ORDER BY task_complete DESC LIMIT 5)
                    UNION 
                    (SELECT id, name, category, title, status, assigned_to, complete_by, frequency 
                    FROM va_job_requests WHERE status NOT LIKE 'C%' ORDER BY id DESC)");
            }
        }
        else {
            $columns = $wpdb->get_results("SELECT COLUMN_NAME, DATA_TYPE, COLUMN_COMMENT 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_NAME='va_job_requests' 
                AND COLUMN_NAME NOT IN ('email','category','description','assigned_to','task_entered','task_start','task_complete','recurring','file','notes','email_file_1','email_file_2','email_file_3','email_file_4','email_file_5')");

            if (!empty($client)) {
               $results = $wpdb->get_results("SELECT id, name, title, status, complete_by, frequency
                    FROM va_job_requests 
                    WHERE name='$client'
                    AND status NOT LIKE 'C%'
                    AND assigned_to IN ('$admin','Not Assigned')
                    ORDER BY task_entered DESC"); 
            }
            else {
                $results = $wpdb->get_results("SELECT id, name, category, title, status, complete_by, frequency 
                    FROM va_job_requests 
                    WHERE status NOT LIKE 'C%' 
                    AND assigned_to IN ('$admin', 'Not Assigned')
                    ORDER BY id DESC");
            }
        }
    }
    else {
        $columns = $wpdb->get_results("SELECT COLUMN_NAME, DATA_TYPE, COLUMN_COMMENT 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_NAME='va_job_requests' 
            AND COLUMN_NAME NOT IN ('email','category','description','status','task_entered','complete_by','recurring','frequency','file','notes','email_file_1','email_file_2','email_file_3','email_file_4','email_file_5')");
        if (!empty($client)) {   
            $results = $wpdb->get_results("SELECT id, name, title, assigned_to, task_start, task_complete
                FROM va_job_requests 
                WHERE name='$client'
                AND status LIKE 'C%' 
                ORDER BY task_entered DESC");
        }
        else {
            $results = $wpdb->get_results("SELECT id, name, title, assigned_to, task_start, task_complete
                FROM va_job_requests 
                WHERE status LIKE 'C%' 
                ORDER BY task_entered DESC");
        }
    };
    
    foreach ($results as $result) {
        echo "<tr>";
        if (is_admin() && $referer != '/archives/') {
                echo "<td class='check_box'><input type='checkbox' name='update_task' value='$result->id' /></td>";
        }
        foreach ($columns as $column) {
            $coltitle = $column->COLUMN_NAME;
            if ($coltitle != 'id') {
                $coltype = $column->DATA_TYPE;
                $colcomment = $column->COLUMN_COMMENT;
                $output = $result->$coltitle;
                if ($coltitle == 'title') {
                    $output = "<a href='task-details?id=$result->id'>$output</a>";
                }
                if ($coltype == 'timestamp') {
                        $output = ($output == '0000-00-00 00:00:00') ? '' : date('m/d/Y, g:i A', strtotime($output));
                }
                if ($coltitle != 'notes') {
                    echo "<td>$output</td>";
                }
            }
        }
        echo "</tr>";
    }    
    
    wp_die();
}

# ----------------------------------------------
#
# Task-Requests Update Status (Group)
#
# ----------------------------------------------

function group_status_update() {
    global $wpdb;
    $tasks = $_POST['tasks'];
    $status = $_POST['status'];
    $results = $invalids = array();
    
    foreach ($tasks as $taskid) {
        $current_status = $wpdb->get_results("SELECT status FROM va_job_requests WHERE id = $taskid")[0]->status;
        $timestamp = current_time('mysql');
        switch($status) {
            case 'Complete':
                ($current_status == 'Not Started') ? $invalids[] = $taskid : task_complete($taskid);
                break;
            case 'In Progress':
                $results['message'] = task_in_progress($taskid);
                break;
            case 'Canceled':
                $wpdb->update('va_job_requests', array('status'=>$status, 'task_start'=>$timestamp, 'task_complete'=>$timestamp), array('id'=>$taskid));
                break;
            case 'Not Started':
                $wpdb->update('va_job_requests', array('status'=>$status, 'task_start'=>'', 'task_complete'=>''), array('id'=>$taskid));
                break;
        }; 
        $results['invalids'] = $invalids;
    };
    
    echo json_encode($results);

    wp_die();
}

# ----------------------------------------------
#
# Task-Requests Task Assign
#
# ----------------------------------------------

function task_assign_callback() {
    global $wpdb;
    $checks = $_POST['checks'];
    $assign = $_POST['assign'];
    $assign_email = $wpdb->get_results("SELECT email FROM va_assistants WHERE name = '$assign'");
    $message = "Hi $assign,
    
    At least one new task has been assigned to you. Please check your dashboard for any newly assigned tasks.
    
    - Bidslot VA";
    
    $adminmessage = "Hi April,
    
    A new task has been assigned to $assign. Please check the dashboard for any newly assigned tasks.
    
    -Bidslot VA";
    
    foreach ($checks as $check) {
        if ($wpdb->update('va_job_requests', array('assigned_to'=>$assign), array('id' => $check))) {
            echo true;
        }
    }
    wp_mail($assign_email, 'Bidslot VA - New Task Assigned', $message);
    wp_mail('april@bidslotmarketingcorp.com', 'Bidslot VA - New Task Assigned to '.$assign, $adminmessage);
    
    wp_die();
}

# ----------------------------------------------
#
# Task-Details Task Assign
#
# ----------------------------------------------

function td_task_assign_callback() {
    global $wpdb;
    $assign = $_POST['assign'];
    $id = $_POST['entry-id'];
    $assign_email = $wpdb->get_results("SELECT email FROM va_assistants WHERE name = '$assign'")[0]->email;
    
    if($wpdb->update('va_job_requests', array('assigned_to'=>$assign), array('id'=>$id))) {
        echo true;
    }
    
    $message = "Hi $assign,
    
    A new task has been assigned to you. Please check your dashboard for any newly assigned tasks.
    
    - Bidslot VA";
    
    $adminmessage = "Hi April,
    
    A new task has been assigned to $assign. Please check the dashboard for any newly assigned tasks.
    
    -Bidslot VA";
    
    wp_mail($assign_email, 'Bidslot VA - New Task Assigned', $message);
    wp_mail('april@bidslotmarketingcorp.com', 'Bidslot VA - New Task Assigned to '.$assign, $adminmessage);
    
    wp_die();
}

# ----------------------------------------------
#
# Task-Details Update Notes
#
# ----------------------------------------------

function update_notes_callback() {
    global $wpdb;
    $id = $_POST['id'];
    $notes = $_POST['notes'];

    if ($wpdb->update('va_job_requests', array('notes'=>$notes), array('id'=>$id))) {
        echo true;
    }
    
    wp_die();
}

# ----------------------------------------------
#
# Task-Details Duplicate Task
#
# ----------------------------------------------

function duplicate_task() {
    global $wpdb;
    $id = $_POST['id'];
    $title = $_POST['title'];
    $complete = $_POST['complete'];
    $complete_time = $_POST['complete_time'];
    if (!empty($complete)) {
        $complete = strtotime($complete);
        $complete = date('Y-m-d', $complete);
    };
    
    $curtask = $wpdb->get_results("SELECT * FROM `va_job_requests` WHERE id = $id")[0];
    $curtitle = $newtitle = $curtask->title;
    
    $newid = $wpdb->get_results("SELECT id FROM `va_job_requests` ORDER BY id DESC LIMIT 1")[0]->id;
    $newid += 1;
    $timestamp = current_time('mysql');  
    
    $tasktitles = $wpdb->get_results("SELECT title FROM `va_job_requests` ORDER BY id DESC");
    $titlesarray = array();
    
    for ($i=0; $i<count($tasktitles); $i++) {
        $titlesarray[$i] = $tasktitles[$i]->title;
    };
    
    //If any duplicate of $curtask exists, output it to an array.
    if (empty($title)) {
        preg_match("/.+(?=_[0-9]+)/", $curtitle, $basetitle);
        if (empty($basetitle)) {
            $matches = array();
        }
        else {
            $matches = preg_grep("/(?<=$basetitle[0]\_)[0-9]/", $titlesarray);
        };

        //If that array is empty...
        if (empty($matches)) {
            $newtitle .= '_1';
        }
        else {
            $titlesarray = arsort($matches);
            $index = array();
            preg_match("/(?<=_)[0-9]+/", $matches[0], $index);
            $newindex = $index[0] + 1;
            $newtitle = $basetitle[0].'_'.$newindex;
        };
    }
    else {
        $newtitle = $title;
    };

    $wpdb->insert('va_job_requests', 
        array(
            'user_id'       => $curtask->user_id,
            'name'          => $curtask->name,
            'email'         => $curtask->email,
            'category'      => $curtask->category,
            'title'         => $newtitle,
            'description'   => $curtask->description,
            'task_entered'  => $timestamp,
            'file'          => $curtask->file,
            'notes'         => $curtask->notes,
            'complete_by'   => $complete,
            'complete_time' => $complete_time,
            'recurring'     => $curtask->recurring,
            'frequency'     => $curtask->frequency
        )
    );
    
    echo "/task-details/?id=$newid";
    
    wp_die();
}

# ----------------------------------------------
#
# Admin Request Form Client Email Population
#
# ----------------------------------------------

function admin_request_callback() {
    global $wpdb;
    $client_id = $_POST['client_id'];
    $results = array();
    if ($client == '---') {
        echo '';
    }
    else {
        $results['email'] = $wpdb->get_results("SELECT user_email FROM va_users WHERE ID = '$client_id'")[0]->user_email;
        $results['user_id'] = $client_id;
    }
    
    echo json_encode($results);
    
    wp_die();
}


# ----------------------------------------------
#
# SEARCH USERS
#
# ----------------------------------------------

//Array Search & Return - using ~ as delimiter
/*$name = preg_quote($name,'~');
$name = preg_grep("~(?i)$name~",$names);*/

# ----------------------------
# TASK DETAILS STATUS UPDATE
# ----------------------------

function update_status() {
    global $wpdb;
    $id = $_POST['id'];
    $manual_diff = $_POST['manualDiff'];
    $status = $_POST['status'];
    $timestamp = current_time('mysql');

    switch($status) {
        case 'Complete':
            echo task_complete($id, $manual_diff); //remaining hours
            break;
        case 'In Progress':
            echo task_in_progress($id); //remaining hours
            break;
        case 'Canceled':
            $wpdb->update('va_job_requests', array('status'=>'Canceled','task_start'=>$timestamp, 'task_complete'=>$timestamp), array('id'=>$id));
            break;
        case 'Not Started':
            $wpdb->update('va_job_requests', array('status'=>'Not Started','task_start'=>'', 'task_complete'=>''), array('id'=>$id));
            break;
    };
    
    wp_die();
}

# -------------------------------
# Task Details - Remove Email Files
# -------------------------------

function email_file_delete() {
    global $wpdb;
    $id = $_POST['id'];
    $slot = $_POST['slot'];

    if ($wpdb->update('va_job_requests', array("$slot"=>''), array('id'=>$id))) {
        echo true;
    };
}

# ------------------------------
#
# TASK COMPLETE FUNCTION
#
# ------------------------------

function task_complete($taskid, $manual_diff) {
    global $wpdb;
    $task = $wpdb->get_results("SELECT * FROM va_job_requests WHERE id = $taskid")[0]; 
    $attachments = array();
    $timestamp = current_time('mysql');
    $notes = $task->notes; 
    $category = $task->category;
    $title = $task->title;        
    $email = $task->email;
    
    if (!empty($task->notes)) {
        $notes = stripslashes($task->notes);
        $notes = str_replace("$","\\$", $notes);
    } 
    else {
        $notes = 'No notes.';
    };
    
    $va = ($task->assigned_to != 'Not Assigned' ? $task->assigned_to : 'April Dodson');
    $va_email = $wpdb->get_results("SELECT email from va_assistants WHERE name = '$va'")[0]->email;
    $headers[] = "Content-type: text/html";
    
    # --------------------------------------------
    # Calculate Time Difference & Update
    # --------------------------------------------

    $user_id = $task->user_id;

    //Check if user is WITHIN a corporate account
    //$corpgroupid = get_field('corporate_group',"user_$user_id");
   
    $taskstart = $task->task_start;
    
    if ($manual_diff > 0) { //Manual difference for tasks not worked within VA. Calculated based on div & input.
        $remaininghours = $wpdb->get_results("SELECT hours FROM `va_users` WHERE ID = '$user_id'")[0]->hours;
        $prehours = $remaininghours + $manual_diff;
        $taskhours = $manual_diff;
    }
    else { //Auto difference between timestamps
        $prehours = $wpdb->get_results("SELECT hours FROM `va_users` WHERE ID = '$user_id'")[0]->hours;
        $preminutes = $prehours * 60;
        $difference = round((strtotime($timestamp) - strtotime($taskstart)) / 60, 0); //in minutes
        $taskhours = round($difference / 60, 2);
        $remaininghours = round(($preminutes - $difference) / 60,2);
    };
    //Corporate User
    //$corp_prehours = $corp_remaininghours = $corp_display_name = '';


    /*if (!empty($corpgroupid)) {
        $corp_query =  $wpdb->get_results("SELECT display_name, hours from `va_users` WHERE ID = $corpgroupid")[0];
        $corp_prehours = $corp_query->hours;
        $corp_display_name = $corp_query->display_name;
        $corp_preminutes = $corp_prehours * 60;
        $corp_remaininghours = round(($corp_preminutes - $difference) / 60,2);

        $wpdb->update('va_users', array('hours'=>$corp_remaininghours), array('ID'=>$corpgroupid));
    };*/

    $wpdb->update('va_users', array('hours'=>$remaininghours), array('ID'=>$user_id));
    $wpdb->update('va_job_requests', array('status'=>'Complete', 'task_complete'=>$timestamp), array('id'=>$taskid));
    
    # --------------------------------------------
    # Send out Email
    # --------------------------------------------

    //preg_match("/(?=[\w.\-_ ]+\.\w{3,4}$).+/", $taskfileurl, $taskfile);
    $starttime = $wpdb->get_results("SELECT DATE_FORMAT(task_start, '%c/%d/%Y - %l:%i %p') TIMEONLY FROM va_job_requests WHERE id = '$taskid'")[0]->TIMEONLY;

    for ($i=0; $i<5; $i++) {
        $file = "email_file_$i";
        if ($task->$file){$attachments[] = $task->$file;}
    };

    $curdate = current_time("m/d/Y");
    $curtime = current_time("g:i A");
    $message = "
    <h2>Plan Update</h2>
    <hr size='2' width='100%' align='center'>
    <table border='0' cellpadding='0' width='100%' style='width:100.0%; table-layout:fixed;'>
        <tbody>
            <tr>
                <td style='padding:.75pt'><strong><span style=\"font-family:'Calibri', sans-serif;\">Request Type:</span></strong></td>
                <td style='padding:.75pt'>$category</td>
            </tr>
            <tr>
                <td style='padding:.75pt'><strong><span style=\"font-family:'Calibri',sans-serif;\">Job Title:</span></strong></td>
                <td style='padding:.75pt'>$title</td>
            </tr>
            <tr>
                <td style='padding:.75pt'><strong><span style=\"font-family:'Calibri',sans-serif;\">Date Completed:</span></strong></td>
                <td style='padding:.75pt'>$curdate - $curtime</td>
            </tr>
            <tr>
                <td style='padding:.75pt'><strong><span style=\"font-family:'Calibri',sans-serif;\">User Hours Before Task Completion:</span></strong></td>
                <td style='padding:.75pt'>$prehours</td>
            </tr>";
            if (!empty($corpgroupid)) {
                $message .= "<tr>
                    <td style='padding:.75pt'><strong><span style=\"font-family:'Calibri',sans-serif;\">$corp_display_name Hours Before Task Completion:</span></strong></td>
                    <td style='padding:.75pt'>$corp_prehours</td>
                </tr>";
            };
            $message .= "<tr>
                <td style='padding:.75pt'><strong><span style=\"font-family:'Calibri',sans-serif;\">Hours Used for This Task:</span></strong></td>
                <td style='padding:.75pt'><b>$taskhours</b></td>
            </tr>
            <tr>
                <td style='padding:.75pt'><strong><span style=\"font-family:'Calibri,sans-serif;\">User Hours Remaining This Subscription Period:</span></strong></td>
                <td style='padding:.75pt'><b>$remaininghours</b></td>
            </tr>";
            if (!empty($corpgroupid)) {
                $message .= "
                </tr>
                    <td style='padding:.75pt'><strong><span style=\"font-family:'Calibri',sans-serif;\">$corp_display_name Hours Remaining This Subscription Period:</span></strong></td>
                    <td style='padding:.75pt'><b>$corp_remaininghours</b></td>
                </tr>";
            };
        $message .= "</tbody>
    </table>
    <br/><br/>
    <a href='https://va.bidslotprogram.com/wp-login.php?action=login&redirect_to=https://va.bidslotprogram.com/task-details/?id=$taskid'>Login To View Request</a>
    <br/><br/><ul>";
            for ($i=0; $i<count($attachments); $i++) {
                $j = $i+1;
                $message .= "<li><a target='_blank' href='$attachments[$i]'>File $j</a></li>";
            };
        $message .= "</ul>
        <br/>
        Additional Notes:
        <blockquote style='background-color:#eee; padding:10px; font-style:italic;'>$notes</blockquote>

        <div style='display:block; text-align:center; font-style:italic;'>If you have any further questions, feel free to contact $va regarding this task at $va_email. Thanks!</div>";

    wp_mail($email, 'Bidslot VA - Task Complete', $message, $headers);
    wp_mail('april@bidslotmarketingcorp.com', 'Bidslot VA - Task Complete', $message, $headers);
    
    return $remaininghours;
}

function task_in_progress ($taskid) {
    global $wpdb;
    $task = $wpdb->get_results("SELECT * FROM va_job_requests WHERE id = $taskid")[0];
    $timestamp = current_time('mysql');
    $startdate = current_time('m/d/Y');
    $starttime = current_time('g:i A');

    if($wpdb->update('va_job_requests', array('status'=>'In Progress','task_start'=>$timestamp, 'task_complete'=>''), array('id'=>$taskid))) {
        $taskname = $task->name;
        $email = $task->email;
        $title = $task->title;

        if (!empty($task->notes)) {
            $notes = stripslashes($task->notes);
            $notes = str_replace("$","\\$", $notes);
        } 
        else {
            $notes = 'No notes.';
        }
        $va = ($task->assigned_to != 'Not Assigned' ? $task->assigned_to : 'April Dodson');
        $va_email = $wpdb->get_results("SELECT email from va_assistants WHERE name = '$va'")[0]->email;
        $headers[] = "Content-type: text/html";
        
        $message = "<div style='font-size:1.2em; padding:10px; font-family:sans-serif;'>
        <div style='width:100%; background-color:#eee; padding:1em; text-align:center; margin-bottom:1em;'>THE FOLLOWING IS FOR YOUR RECORDS</div>

            $taskname,

            <b>$title</b> was started on <b>$startdate at $starttime<b>. You will receive another email containing additional information when the task is completed.

            <blockquote style='background-color:#eee; padding:10px; font-style:italic;'>$notes</blockquote>

            <div style='display:block; text-align:center; font-style:italic;'>If you have any further questions, feel free to contact $va regarding this task at $va_email. Thanks!</div>
        </div>";
        
        return true;
        wp_mail($email, 'Bidslot VA - Task In Progress', $message, $headers);
        wp_mail('april@bidslotmarketingcorp.com', 'Bidslot VA - Task In Progress', $message, $headers);
    }
    else {
        return 'Having trouble updating the status to "In Progress".';
    };
}

?>