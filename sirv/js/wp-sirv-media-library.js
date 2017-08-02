jQuery(function($){

    $(document).ready(function(){ 

        function sirv_update_window_dimensions() {      
            var footer = $('#footer').size() > 0 ? $('#footer') : $('#wpfooter');
            var body_height = $('body').height() - $(footer).outerHeight(true);
            $('#wpcontent').css('margin-left', '156px');
            $('#wpbody').css('height', body_height).css('overflow', 'hidden');
            $('#wpbody-content .content').css('height', body_height);               
        }


        //code for drag area
        var obj = $("#drug-upload-area");
        obj.on('dragenter', function (e) 
        {
            e.stopPropagation();
            e.preventDefault();
            $(this).css('border', '5px solid #797373');
        });
        obj.on('dragover', function (e) 
        {
             e.stopPropagation();
             e.preventDefault();
        });
        obj.on('drop', function (e) 
        {
         
             $(this).css('border', '5px dashed #797373');
             e.preventDefault();
             var files = e.originalEvent.dataTransfer.files;
         
             //We need to send dropped files to Server
             uploadImages(files);
        });

        $(document).on('dragenter', function (e) 
        {
            e.stopPropagation();
            e.preventDefault();
        });
        $(document).on('dragover', function (e) 
        {
          e.stopPropagation();
          e.preventDefault();
          obj.css('border', '5px dashed #797373');
        });
        $(document).on('drop', function (e) 
        {
            e.stopPropagation();
            e.preventDefault();
        });



        //Initialization
        sirv_update_window_dimensions()
        $(window).resize(sirv_update_window_dimensions);

    });
});