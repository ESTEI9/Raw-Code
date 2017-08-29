<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');

Global $user_role, $display_name;

$mysqli = new mysqli('localhost','****', '********', '**');

if ($mysqli->connect_errno) {
    echo "data: Errno: {$mysqli->connect->errno}\n";
    echo "data: Error: {$mysqli->connect_error}\n\n";
    
    exit;
};

switch ($user_role) {
    case 'super_admin':
        $results = $mysqli->query("SELECT id, name, title, status, assigned_to, complete_by, complete_time, frequency 
            FROM va_job_requests WHERE status NOT LIKE 'C%' ORDER BY id DESC");
        $columns = $qmysqli->query("SELECT COLUMN_NAME, DATA_TYPE
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_NAME='va_job_requests' 
            AND COLUMN_NAME NOT IN ('user_id','email','category','description','file','recurring','task_entered','task_start','task_complete','notes','email_file_1','email_file_2','email_file_3','email_file_4','email_file_5')");
    break;
    default:
        $results = $mysqli->query("SELECT id, name, title, status, complete_by, complete_time, frequency 
            FROM va_job_requests WHERE status NOT LIKE 'C%' AND assigned_to IN ('$display_name','Not Assigned') ORDER BY id DESC");
        $columns = $mysqli->query("SELECT COLUMN_NAME, DATA_TYPE
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_NAME='va_job_requests' 
            AND COLUMN_NAME NOT IN ('user_id','email','category','description','file','recurring','assigned_to','task_entered','task_start','task_complete','notes','email_file_1','email_file_2','email_file_3','email_file_4','email_file_5')");
};

if (!$results) {
    echo "data: Errno: {$mysqli->errno}\n";
    echo "data: Error: {$mysqli->error}\n\n";

}
else {
    while ($row = mysqli_fetch_assoc($results)) {
        $table[] = $row;
    };
    $str = json_encode($table);
    echo "data: {$str}\n\n";
    flush();
};


?>