jQuery(document).ready(function($) {
    // Check if we are on the post edit screen and the metabox exists
    if (!$('#usc_audit_box').length) {
        return;
    }

    // Function to run the audit (called on events)
    function runAudit() {
        var postID = usc_ajax_obj.post_id;
        var focusKeyword = $('#usc_focus_keyword').val().trim();
        
        // Get content from the Classic Editor (TinyMCE)
        var content = '';
        if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
            content = tinymce.get('content').getContent();
        } else {
            // Fallback for HTML view
            content = $('#content').val();
        }

        // Bail if no keyword is entered
        if (focusKeyword.length < 3) {
            $('#usc_score').text('--');
            $('#usc_audit_checklist').html('<p style="color: red; margin: 0;">Please enter a focus keyword to run the audit.</p>');
            return;
        }
        
        // Show loading state
        $('#usc_score').text('...');
        $('#usc_audit_checklist').html('<p style="margin: 0;">Running checks, please wait...</p>');


        // AJAX call to the PHP Scoring Engine
        $.post(usc_ajax_obj.ajax_url, {
            action: 'usc_run_audit', // Matches the wp_ajax hook
            post_id: postID,
            keyword: focusKeyword,
            content: content
        }, function(response) {
            if (response.success) {
                var score = response.data.score;
                var results = response.data.results;

                // 1. Update Score and Background Color
                $('#usc_score').text(score);
                $('#usc_audit_score_display').css({
                    'background-color': score >= 85 ? '#d4edda' : (score >= 60 ? '#fff3cd' : '#f8d7da'), // Green, Yellow, Red color logic
                    'border-color': score >= 85 ? '#c3e6cb' : (score >= 60 ? '#ffeeba' : '#f5c6cb')
                });

                // 2. Build Checklist HTML
                var checklistHtml = '<ul style="list-style: none; margin: 0; padding: 0;">';
                $.each(results, function(key, check) {
                    var icon = '';
                    var color = '';
                    if (check.status === 'green') {
                        icon = '✅';
                        color = 'green';
                    } else if (check.status === 'orange') {
                        icon = '🟡';
                        color = 'orange';
                    } else {
                        icon = '🔴';
                        color = 'red';
                    }

                    checklistHtml += '<li>' + icon + ' <span style="color:' + color + ';">' + check.label + '</span></li>';
                });
                checklistHtml += '</ul>';
                
                $('#usc_audit_checklist').html(checklistHtml);

            } else {
                $('#usc_audit_checklist').html('<p style="color: red; margin: 0;">Error running audit.</p>');
            }
        });
    }
    
    // Simple Debounce function to limit AJAX calls (critical for performance)
    function debounce(func, delay) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                func.apply(context, args);
            }, delay);
        };
    }

    // --- Real-Time Trigger Setup ---

    // Trigger 1: When the focus keyword changes
    $('#usc_focus_keyword').on('keyup', debounce(runAudit, 500));

    // Trigger 2: When the editor content changes (TinyMCE editor events)
    if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
        tinymce.get('content').on('change keyup', debounce(runAudit, 1000));
    } else {
        // Fallback for when the editor is in HTML mode
        $('#content').on('keyup', debounce(runAudit, 1000));
    }
    
    // Run initial audit on load
    runAudit(); 
});
