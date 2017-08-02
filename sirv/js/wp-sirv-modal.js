jQuery(function($){

    $(document).ready(function(){

        function checkEmptyOptions(){
            var data = {}
            data['action'] = 'sirv_check_empty_options';

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data,
                async: true,
            }).done(function(response){
                if(response == '1'){
                    $('.sirv-modal-click').click(function(){
                        window['bPopup'] = $('.sirv-modal').bPopup({
                            position: ['auto', 'auto'],
                            contentContainer:'.modal-content',
                            loadUrl: modal_object.media_add_url,
                        });
                        getContentFromSirv();
                    });

                    $('.sirv-add-image-modal-click').click(function(){
                        window['bPopup'] = $('.sirv-modal').bPopup({
                            position: ['auto', 'auto'],
                            contentContainer:'.modal-content',
                            loadUrl: modal_object.featured_image_url,
                        });
                        getContentFromSirv();
                    });                    
                }else{
                    //var warning = $('<div class="sirv-warning"><a href="admin.php?page=sirv/sirv/options.php">Enter your Sirv S3 settings</a> to view your images on Sirv.</div>');
                    //$('.sirv-modal').append(warning);
                    $('.sirv-modal-click').click(function(){
                        //$('.sirv-modal').bPopup();
                        window['bPopup'] = $('.sirv-modal').bPopup({
                            position: ['auto', 'auto'],
                            contentContainer:'.modal-content',
                            loadUrl: modal_object.login_error_url,
                        });
                    });
                }
            });
        }

        //-------------------------------------------initialization-----------------------------------------------------------------
        checkEmptyOptions();
    }); 
});