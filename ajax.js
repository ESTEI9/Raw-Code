jQuery(document).ready(function($){

/*------------------------------------------------
#
#   DASHBOARD FUNCTIONS
#
-------------------------------------------------*/ 
    
    /* ---- HOUR UPDATES ---- */
    
    $('.userhours button').click(function(){
        $(this).after('<img id="loadinggif" style="width: 25px; display: inline-block" src="/wp-content/plugins/VirtualAssistant1.0/images/loading.gif" />');
        var key;
        ($('#name').length != 0) ? key = $('#name td:eq(1)').text() : key = $(this).parents('.userhours').find('label').text();
        var newhours = $(this).siblings('input').val();
        var data = {
            action: 'update_hours',
            key: key,
            new_hours: newhours
        };
        $.post(ajaxurl, data, function(results){
            $('#loadinggif').remove();
        });
    });

/*------------------------------------------------
#
#   TASK REQUESTS SELECT USER
#
-------------------------------------------------*/
    
    $('#filteruser').change(function() {
        var client = $(this).val();
        var referer = window.location.pathname;
        $('#selectAll input').attr('checked',false);
        $(this).append('<img id="loadinggif" style="margin: 3em auto; width: 30px; display: block;" src="/wp-content/plugins/VirtualAssistant1.0/images/loading.gif" />');
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
            $('#loadinggif').remove();
       });
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
        $('#task_assign').after('<img id="loadinggif" style="width: 20px; vertical-align: middle; margin-left: 5px;" src="/wp-content/plugins/VirtualAssistant1.0/images/loading.gif" />');
        var assign_to = $(this).val();
        var checks = $('[type="checkbox"]:checked');
        var values = [];
        for (var i=0; i<checks.length; i++) {
            values.push($(checks[i]).attr('value'));
        }
        var data = {
            'action': 'task_assign',
            'assign': assign_to,
            'checks': values
        }

        $.post(ajaxurl, data, function(results) {
            $('#loadinggif').remove();
            if (results) {
                for (var i=0; i<values.length; i++) {
                    $('[data-row="'+values[i]+'"]').find('.assistant').html(assign_to);
                }
                $('#message').attr('class','success').html('&nbsp;&nbsp;Task(s) successfully assigned!').stop().fadeIn(200).delay(3000).fadeOut(400);
            }
            else {
                $('#message').attr('class','error').html('&nbsp;&nbsp;Error - task(s) not assigned.').stop().fadeIn(200).delay(3000).fadeOut(400);
            } 
        });
    });

/*------------------------
#   Task-Details
------------------------*/

    $('#tdAssign select').change(function() {
        if ($(this).val() != '') {
            $(this).after('<img id="loadinggif" style="width: 20px; vertical-align: middle; margin-left: 5px;" src="/wp-content/plugins/VirtualAssistant1.0/images/loading.gif" />');
            var assign = $(this).val();
            var id = window.location.search;
            id = id.split('=');
            id = id[1];
                var data = {
                'action': 'td_task_assign',
                'assign': assign,
                'entry-id': id
                }
            $.post(ajaxurl, data, function(results){
                $('#loadinggif').remove();
                if (results) {
                    $('#assigned_to').html(assign);
                    $('#message').attr('class','success').html('&nbsp;&nbsp;Task successfully assigned!').stop().fadeIn(200).delay(3000).fadeOut(400);
                }
                else {
                    $('#message').attr('class','error').html('&nbsp;&nbsp;Error - task not assigned').stop().fadeIn(200).delay(3000).fadeOut(400);  
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {
                $('#loadinggif').remove();
                console.log('HTTP Status: '+jqXHR.status+', Error Message: '+textStatus+' - '+errorThrown);
            });
        }
    });

    $('.emaildelete').on('click', function(){
        var file = $(this);
        $(this).siblings().after('<img id="loadinggif" style="width: 20px; vertical-align: middle; margin-left: 5px; float:right;" src="/wp-content/plugins/VirtualAssistant1.0/images/loading.gif" />');
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
        })
    })
    
/*------------------------------------------------
#
#   Admin Request Form Client Email Prepopulation
#
-------------------------------------------------*/
    
    $('.clients-dropdown').change(function(){
        var client = $('.clients-dropdown').find('select').val();
            var data = {
                'action': 'admin_request',
                'client' : client
            }
            $.post(ajaxurl, data, function(results) {
                $('.client-email input').attr('value', results);
            })
    });

/*------------------------------------------------
#
#   Task-Details Notes Update
#
-------------------------------------------------*/
    $('[value="Update Notes"]').click(function(){
        $(this).after('<img id="loadinggif" style="width: 25px; display: inline-block" src="/wp-content/plugins/VirtualAssistant1.0/images/loading.gif" />');
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
            $('#loadinggif').remove();
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
            $(this).after('<img id="loadinggif" style="width: 25px; display: inline-block" src="/wp-content/plugins/VirtualAssistant1.0/images/loading.gif" />');
            var status = $(this).val();
            var id = window.location.search;
            id = id.split('=');
            id = id[1];
            var taskname = $('#name td:eq(1)').text();

            var date = new Date();
            var hours = date.getHours();
            var minutes = date.getMinutes();
            if (hours > 12) {
                hours -= 12;
            }
            if (minutes < 10) {
                minutes = '0'+minutes;
            }
            var timestamp = (date.getMonth()+1)+"/"+date.getDate()+"/"+date.getFullYear()+", "+hours+":"+minutes+" PM";

            var data = {
                action:'update_status',
                id: id,
                status: status,
                taskname: taskname
            }
            $.post(ajaxurl, data, function(sqlhours) {
                $('#loadinggif').remove();
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
                }
            });
        }
    });
    
});