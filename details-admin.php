<?php

$title = $results[0]->title;
$timestart = $results[0]->task_start;
$status = $results[0]->status;

?>
<a href="<?= $referer ?>"><button id='back-to-tasks'>Back to <?= $reference ?></button></a>
<div class='panel-group'>
    <div class='panel panel-default'>
        <div class='panel-heading' data-toggle='collapse' data-target='.admin'>
            <span class='panel-title'>Admin Data</span>
        </div>
        <div class='admin panel-collapse collapse'>
            <div class='panel-body'>
                <table id='admin-details' class='table table-condensed data'><thead></thead><tbody>
                <?php
                foreach ($results as $task) {
                    $status = $task->status;
                    foreach ($columns as $column) {
                        $adminvar = $column->COLUMN_NAME;
                        $coltype = $column->DATA_TYPE;
                        if (in_array($adminvar, $asidevars)) {
                            $colfriendly = ucwords(str_replace('_',' ',$adminvar));
                            $output = $task->$adminvar;
                            //Formatting
                            switch ($adminvar) {
                                case 'status':
                                    $output = "<strong>$output</strong>";
                                    break;
                                case 'email_file_1':
                                case 'email_file_2':
                                case 'email_file_3':
                                case 'email_file_4':
                                case 'email_file_5':
                                    $file = array();
                                    $href = $output;
                                    preg_match("/(?=[\w.\-_ ]+\.\w{3,4}$).+/", $output, $file);
                                    if (!empty($file[0])) {
                                        $output = "<a href='$href'>$file[0]</a>";
                                        if ($status != 'Complete' && $status != 'Canceled') {
                                            $output .= "<button class='emaildelete'>&times;</button>";
                                        }
                                    }
                                    break;
                            }
                            if ($coltype == 'timestamp') {
                                $output = ($output == '0000-00-00 00:00:00') ? '' : date('m/d/Y, g:i A', strtotime($output));
                            }
                        ?>
                            <tr><td><label><?= $colfriendly ?>:</label></td><td id='<?= $adminvar ?>'><?= $output ?></td></tr>
                        <?php 
                        }
                    }
                }
                ?>
                </table>
                <?php
                if ($status != 'Complete' && $status != 'Canceled') {
                ?>
                <div id='forms'>
                    <div id='tdAssign'><label>Assign</label><select name='assign_to'>
                            <option value=''>---</option>
                            <?php
                            foreach ($vas as $va) {
                                echo "<option value='".$va->name."'>".$va->name."</option>";
                            }
                            ?>
                        </select>
                    </div>
                        <input type='hidden' name='title' value='<?=$title ?>'><input type='hidden' name='formname' value='task-details'><label>Status</label><select name='status'>
                            <option>---</option>
                            <option value='Not Started'>Not Started</option>
                            <option value='In Progress'>In Progress</option>
                            <option id='completeopt' value='Complete'>Complete</option>
                            <option value='Canceled'>Canceled</option>
                        </select>
                    <div class='userhours'>
                        <label>Hours</label> <input type='text' name='new_hours' value='<?= $hours ?>' size='5' /> <button>Update</button>
                    </div>
                    <?php gravity_form( 'Admin Upload', false, false, false, '', true); ?>
                </div>
                <?php } ?>
            </div></div>
    </div>
</div>
<div class='task-details'>
<?php
    if ($results) {
        echo "<table class='table table-bordered table-condensed table-striped'><thead></thead><tbody>";
        foreach ($columns as $column) {
            $coltitle = $column->COLUMN_NAME;
            $coltype = $column->DATA_TYPE;
            $colfriendly = ucwords(str_replace('_',' ',$coltitle));
            foreach ($results as $task) {
                $output = $task->$coltitle;
                $emailtitle = str_replace(array('\'','"'),array("'", '*'),$task->title);
                if ($output) {

                    # ------------------------------------
                    # Formatting Functions
                    # ------------------------------------

                    if ($coltitle == 'email' && $status != 'Complete' && $status != 'Canceled') {
                        $output = "<a href='mailto:$output?subject=".$emailtitle."'>$output</a>";
                    }
                    if ($coltitle == 'file') {
                        $output = "<a target='_blank' href='$output'><button>View/Download</button></a>";
                    }
                    if ($coltitle == 'notes') {
                        if ($status != 'Complete' && $status != 'Canceled') {
                            $notes = stripslashes($output);
                            $output = "<div><div id='notescontent' contenteditable='true'>$notes</div><input type='submit' value='Update Notes' /></div>";
                        }
                        else {
                            $notes = stripslashes($output);
                            $output = "<div id='notes'>$notes</div>";
                        }
                    }

                    # ------------------------------------
                    # Build Task UI
                    # ------------------------------------
       
                    if (!in_array($coltitle, $asidevars)) { ?>
                        <tr id='<?= $coltitle ?>'>
                            <td><label><?= $colfriendly ?></label></td><td><?= $output ?></td>
                        </tr>
                    <?php
                    }
                }
            }
        }
        if (empty($results[0]->notes)) {
            if ($status != 'Complete' && $status != 'Canceled') {
                echo "<tr id='notes'><td><label>Notes</label></td><td><div><div id='notescontent' contenteditable='true'></div><input type='submit' value='Update Notes' /></div></div></td></tr>";
            }
            else {
                echo "<tr id='notes'><td><label>Notes</label></td><td></td></tr>";
            }
        }
        echo "</table>";
    }
    else {
        echo $message;
    }
    ?>
</div>    
<div id='message'></div>
    