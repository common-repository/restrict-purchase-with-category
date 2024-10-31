jQuery(document).ready( function($) {
    $("#catForm").submit(function() {
        $('.msg').remove();
        var templateUrl = the_ajax_script.admin_url;
        var ajaxloaderHtml = '<div class="ajaxLoader" style="text-align: center;"><img src="'+templateUrl+'/images/spinner-2x.gif" /></div>';
        $('#settingcontainer').prepend(ajaxloaderHtml);
        var text = $('textarea#msgNote').val();

        var data = {
            action: 'process_response',
            post_var: text
        };

        // the_ajax_script.ajaxurl is a variable that will contain the url to the ajax processing file
        $.post(the_ajax_script.ajaxurl, data, function(response) {
            $('.ajaxLoader').remove();
            if(response == 1){
                $msgHtml = '<div class="msg" style="color: green; font-size: 14px; margin-bottom: 5px;">Details Updated</div>';

            }
            else{
                $msgHtml = '<div class="msg" style="color: red; font-size: 14px; margin-bottom: 5px;">Error in updating!</div>';
            }

            $('#settingcontainer').prepend($msgHtml);

        });
        return false;
    });
});