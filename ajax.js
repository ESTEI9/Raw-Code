jQuery(document).ready(function($){

/*------------------------------------------------
#
#   DASHBOARD FUNCTIONS
#
-------------------------------------------------*/ 
    
    /* ---- HOUR UPDATES (DASHBOARD AND TASK DETAILS) ---- */
    
    $(document).on('click', '.userhours button', function(){
        let button = this;
        $(this).addClass('loading');
        let id = '';
        if ($('tr#user_id').length > 0) { //task details
            id = $('tr#user_id').find('td:eq(1)').text();
        }
        else { //dashboard
            id = $(this).parents('.userhours').attr('data-id');
        };
        let newhours = $(this).siblings('input').val();
        let data = {
            action: 'update_hours',
            id: id,
            new_hours: newhours
        };
        $.post(ajaxurl, data, function(results){
            $(button).removeClass('loading');
        });
    });
    
    /* ---- QUICK JOB REQUEST (ADD TIMESTAMP) ---- */
    
    /*if (window.location.pathname == '/quick-job-request/') {
        $('#field_8_5, #field_8_6, #field_8_7').css({'display':'none'});
        $('.addJobRequest .modal-title').text('Quick Job Request');
        let data = {
            action: 'mysql_timestamp'
        };
        $.post(ajaxurl, data, function(timestamp){
            $('#input_8_13').val(timestamp);
        });
    };*/
    
    $('#quickJobRequest').click(function(e){
        e.preventDefault();
        let data = {
           action: 'mysql_timestamp',
        };
        $.post(ajaxurl, data, function(date){
           window.open("/quick-job-request?sttime="+date, "_blank", "width=600,height=800,resizable,dependent");
        });
    });
/*------------------------------------------------
#
#   TASK REQUESTS SELECT USER
#
-------------------------------------------------*/
    
    $('#filteruser,#archfilter').change(function() {
        var client = $(this).val();
        var referer = window.location.pathname;
        $('#selectAll input').attr('checked',false);
        $(this).after('<div class="loading"></div>');
        var data = {
            action: 'client_change',
            client: client,
            referer: referer
        }
        $.post(ajaxurl, data, function(newtable){
            if (newtable) {
                if ($('#tasks').length != 0){
                    $('#tasks tbody').find('tr').remove();
                    $('#tasks tbody').append(newtable);
                }
                else {
                    $('#archives table').dataTable().fnDestroy();
                    $('#archives tbody').find('tr').remove();
                    $('#archives tbody').append(newtable);
                    $('#archives table').dataTable({
                        'order': [[4, "desc"]]
                    });
                }
            }
            else {
                if (referer == '/archives/') {
                    $('#message').attr('class','error').html('&nbsp;&nbsp;No completed tasks for selected user.').stop().fadeIn(200).delay(2000).fadeOut(200);
                }
                else {
                    $('#message').attr('class','error').html('&nbsp;&nbsp;No incomplete tasks for selected user.').stop().fadeIn(200).delay(2000).fadeOut(200);
                }     
            }
            $('.loading').remove();
       });
    });
    
/*------------------------------------------------
#
#   TASK REQUESTS CHANGE STATUS (GROUP)
#
-------------------------------------------------*/  
    
    $('#group_status select').change(function(){
        $('#group_status select').addClass('loading');
        let status = $(this).val();
        if (status != '---')  {
            let checks = $('[type="checkbox"]:checked');
            let tasks = [];
            for (let i=0; i<checks.length; i++) {
                tasks.push($(checks[i]).attr('value'));
            };
            let data = {
                action: 'group_status',
                status: status,
                tasks: tasks
            };

            $.post(ajaxurl, data, function(results){
                $('#group_status select').removeClass('loading');
                let invalids = results.invalids;
                for (let i=0; i<tasks.length; i++) {
                    if (invalids.indexOf(tasks[i]) == -1) {
                        let record = $('tr[data-row='+tasks[i]+']');
                        let oBgColor= $(record).css('background-color');
                        $(record).find('.current-status').html(status);
                        $(record).find('[type=checkbox]').attr('checked',false);
                        $(record).addClass('temp-success-task');
                        setTimeout(function(){$(record).removeClass('temp-success-task');}, 1000);
                    }
                    else {
                        $('tr[data-row='+tasks[i]+']').css({'background': 'rgba(51, 122, 183, 0.3)'});
                    };
                };
                if (invalids.length) {
                    alert('One or more of the selected tasks has not been started and therefore cannot be marked Complete. The tasks are marked out for you.');
                }
                else {
                    
                };
            }, "json");
        };
    });

/*------------------------------------------------
#
#   Task Assign
#
-------------------------------------------------*/
    
/*------------------------
#   Task-Requests
------------------------*/
    
     $('#selectAll input').click(function(){
        if ($(this).is(':checked')) {
            $('.check_box input').attr('checked', true);
        }
        else {
            $('.check_box input').attr('checked',false);
        }
    });
    
    $('#task_assign select').change(function() {
        let assign_to = $(this).val();
        if (assign_to != '---') {
            $('#task_assign').after('<div class="loading"></div>');
            let checks = $('[type="checkbox"]:checked');
            let tasks = [];
            for (var i=0; i<checks.length; i++) {
                tasks.push($(checks[i]).attr('value'));
            };
            let data = {
                'action': 'task_assign',
                'assign': assign_to,
                'checks': tasks
            }
            $.post(ajaxurl, data, function(results) {
                $('#loading').remove();
                if (results) {
                    for (var i=0; i<tasks.length; i++) {
                        let record = $('[data-row="'+tasks[i]+'"]');
                        $(record).find('.assistant').html(assign_to);
                        $(record).find('[type=checkbox]').attr('checked',false);
                        $(record).addClass('temp-success-task');
                        setTimeout(function(){$(record).removeClass('temp-success-task');}, 1000);

                    };
                }
                else {
                    $('#message').attr('class','error').html('&nbsp;&nbsp;Error - task(s) not assigned.').stop().fadeIn(200).delay(3000).fadeOut(400);
                }; 
            });
        };
    });

/*------------------------
#   Task-Details
------------------------*/

    $('#tdAssign select').change(function() {
        if ($(this).val() != '') {
            let button = $(this);
            $(button).addClass('loading');
            var assign = $(this).val();
            var id = window.location.search;
            id = id.split('=');
            id = id[1];
            let data = {
                'action': 'td_task_assign',
                'assign': assign,
                'entry-id': id
            };
            $.post(ajaxurl, data, function(results){
                $(button).removeClass('loading');
                if (results) {
                    $('#assigned_to').html(assign);
                }
                else {
                    $('#message').attr('class','error').html('&nbsp;&nbsp;Error - task not assigned').stop().fadeIn(200).delay(3000).fadeOut(400);  
                };
            }).fail(function(jqXHR, textStatus, errorThrown) {
                $(button).removeClass('loading');
                console.log('HTTP Status: '+jqXHR.status+', Error Message: '+textStatus+' - '+errorThrown);
            });
        }
    });

    $('.emaildelete').on('click', function(){
        var file = $(this);
        $(this).siblings().after('<img id="loading" style="width: 20px; vertical-align: middle; margin-left: 5px; float:right;" src="/wp-content/plugins/VirtualAssistant1.0/images/loading.svg" />');
        var slot= $(this).parent().attr('id');
        $(this).css({'display':'none'});
        var id = window.location.search;
        id = id.split('=');
        id = id[1];
        var data = {
            action: 'email_file_delete',
            slot: slot,
            id:id
        }
        $.post(ajaxurl, data, function(results){
            $(file).parent().empty();
        });
    });
    
    /*------------------------
    #   Duplicate Task
    ------------------------*/
    
    $('#duplicateTask').on('click', function(){
    
        //Popup Modal, which asks for complete by date, then button for submit
        //https://www.gravityhelp.com/documentation/article/gform_field_value_parameter_name/
        //Populate multiple fields using one function - this on ajaxfns.php
        
        let id = window.location.search;
        id = id.split('=');
        id = id[1];
        let title = $('#newTitle').val();
        let completeBy = $('#newCompleteBy').val();
        let hour = $('#newHour').val();
        let minute = $('#newMinutes').val();
        let ampm = $('#AMPM').val();
        let completeTime = '';
        if (hour.length > 0) {
            completeTime = hour+":"+minute+" "+ampm;
        };
        
        let data = {
            action: 'duplicate_task',
            id: id,
            title: title,
            complete: completeBy,
            complete_time: completeTime
        };
        
        $(this).addClass('loading');
        
        $.post(ajaxurl, data, function(results){
            $('#duplicateTask').removeClass('loading').addClass('completed');
            window.location.href = 'https://va.bidslotprogram.com'+results;
        });
        
    });
    
/*------------------------------------------------
#
#   Admin Request Form Client Email/Id Prepopulation
#
-------------------------------------------------*/
    
    $('.clients-dropdown').on('change', function(){
        let clientId = $(this).find('select').val();
        let data = {
            'action': 'admin_request',
            'client_id' : clientId
        };
        $.post(ajaxurl, data, function(results) {
            $('.client-email input').attr('value', results.email);
            $('.user-id input').attr('value',results.user_id);
        }, 'json');
    });

/*------------------------------------------------
#
#   Task-Details Notes Update
#
-------------------------------------------------*/
    $('[value="Update Notes"]').click(function(){
        let button = $(this);
        $(button).addClass('loading');
        var notes = $('#notescontent').html();
        var id = window.location.search;
        id = id.split('=');
        id = id[1];
        var data = {
            'action': 'update_notes',
            'id' : id,
            'notes' : notes
        }
        $.post(ajaxurl, data, function(results){
            $(button).removeClass('loading');
            $('#notescontent').html(notes);
        })
    });

/*------------------------------------------------
#
#   Task-Details Status Update
#
-------------------------------------------------*/
    $("[name='status']").change(function(){
        if ($(this).val() != '—') {
            let button = $(this);
            $(button).addClass('loading');
            let status = $(this).val();
            let id = window.location.search;
            id = id.split('=');
            id = id[1];
            let taskname = $('#name td:eq(1)').text();
            let oldhours = $('[name="new_hours"]').val();

            let date = new Date();
            let hours = date.getHours();
            let minutes = date.getMinutes();
            let ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12; // the hour '0' should be '12'           
            minutes = minutes < 10 ? '0'+minutes : minutes;
            
            let timestamp = (date.getMonth()+1)+"/"+date.getDate()+"/"+date.getFullYear()+", "+hours+":"+minutes+" "+ampm;
            
            let manualDiff = $('#oldHours').text() - oldhours; //for tasks not started or completed in VA. Hours updated in task before submission.
            
            let data = {
                action:'update_status',
                id: id,
                status: status,
                taskname: taskname,
                oldhours: oldhours,
                manualDiff: manualDiff
            };
            console.log(data);
            $.post(ajaxurl, data, function(sqlhours) {
                $(button).removeClass('loading');
                $('#status').html('<strong>'+status+'</strong>');
                switch (status) {
                    case "In Progress":
                        $('#task_start').html(timestamp);
                        $('#task_complete').empty();
                        $('#completeopt').css({'display':'block'});
                        break;
                    case "Complete":
                        $('#task_complete').html(timestamp);
                        $("[name='new_hours']").attr('value', sqlhours);
                        break;
                    case "Canceled":
                        $('#task_start, #task_complete').html(timestamp);
                        break;
                    case "Not Started":
                        $('#task_start, #task_complete').empty();
                        $('#completeopt').css({'display':'none'});
                        break;
                };
            });
        };
    });
    
});