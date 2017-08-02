jQuery(function($){

    $(document).ready(function(){

        var contentData = {},
            prev = -1;


        function render_view(json_obj){

            function alphanumCase(a, b) {
              function chunkify(t) {
                var tz = new Array();
                var x = 0, y = -1, n = 0, i, j;

                while (i = (j = t.charAt(x++)).charCodeAt(0)) {
                  var m = (i == 46 || (i >=48 && i <= 57));
                  if (m !== n) {
                    tz[++y] = "";
                    n = m;
                  }
                  tz[y] += j;
                }
                return tz;
              }

              var aa = chunkify(a.toLowerCase());
              var bb = chunkify(b.toLowerCase());

              for (x = 0; aa[x] && bb[x]; x++) {
                if (aa[x] !== bb[x]) {
                  var c = Number(aa[x]), d = Number(bb[x]);
                  if (c == aa[x] && d == bb[x]) {
                    return c - d;
                  } else return (aa[x] > bb[x]) ? 1 : -1;
                }
              }
              return aa.length - bb.length;
            }

            var bucket = json_obj.bucket;
            var dirs = json_obj.dirs;
            var images = json_obj.contents;

            if(dirs !== null){
                var documentFragment = $(document.createDocumentFragment());
                for(var i = 0; i < dirs.length; i++){
                    var dir = dirs[i].Prefix.split("/");
                    //from prefix like test/example1 create folder name example1
                    dir = dir[dir.length-2];
                    if(dir[0] != '.'){
                        var elemBlock = $('<li><a class="sirv-link" href="#" data-link="'+dirs[i].Prefix+'">'+
                            '<img src="'+ ajax_object.assets_path +'/ico-folder.png" />'+
                            '<span>'+dir+'</span></a></li>\n');
                        documentFragment.append(elemBlock);
                    }
                }
                $('#dirs').append(documentFragment);
            }

            if(images !== null){
                var documentFragment = $(document.createDocumentFragment());
                for(var i = 0; i < images.length; i++){
                    var valid_images_ext = ["jpg", "png", "gif", "bmp","jpeg","PNG","JPG","JPEG","GIF","BMP"];
                    var image = images[i].Key;

                    var fileName = image.replace(/.*\/(.*)/gm,'$1');

                    var image_ext = image.substr((~-image.lastIndexOf(".") >>> 0) + 2)
                    if( image_ext != ''){
                        if(valid_images_ext.indexOf(image_ext) != -1){
                            var elemBlock = $('<li class="sirv-image-container"><a href="#" title="'+fileName+'"><img class="sirv-image" src="https://'+bucket+'.sirv.com/'+image+'?thumbnail=100"'+
                                ' data-id="'+ md5('//'+bucket+'.sirv.com/'+image) +'" data-type="image" data-original="https://'+bucket+'.sirv.com/'+image+'" /><span>'+fileName+'</span></a></li>\n');
                            documentFragment.append(elemBlock);
                        }else if(image_ext == 'spin'){
                            //https://obdellus.sirv.com/test/test.spin?thumbnail=132&image=true
                            $('<li class="sirv-spin-container"><a href="#" title="'+fileName+'"><img class="sirv-image" data-id="'+ md5('//'+bucket+'.sirv.com/'+image) +
                                '" data-original="https://'+bucket+'.sirv.com/'+image+'" data-type="spin" '+
                                'src="https://'+bucket+'.sirv.com/'+image+'?thumbnail=100&image=true" /><span>'+fileName+'</span></a></li>\n').appendTo('#spins');
                        }
                    }
                }
                $('#images').append(documentFragment);
            }
        }


        function erase_view(){

            unbindEvents();
            $('#dirs').empty();
            $('#images').empty();
            $('#spins').empty();
            $('.breadcrumb').empty();
        }


        function render_breadcramb(current_dir){

            if(current_dir != "/"){
                $('<li><span class="breadcrumb-text">You are here: </span><a href="#" class="sirv-link" data-link="/">Home</a></li>').appendTo('.breadcrumb');
                var dirs = current_dir.split("/");
                var temp_dir = "";
                for(var i=0; i < dirs.length - 1; i++){
                    temp_dir += dirs[i] + "/";
                    $('<li><a href="#" class="sirv-link" data-link="'+ temp_dir +'">'+ dirs[i] +'</a></li>').appendTo('.breadcrumb');
                }
            }else{
                $('<li><span class="breadcrumb-text">You are here: </span>Home</li>').appendTo('.breadcrumb')
            }
        }


        function set_current_dir(current_dir){

            $('#filesToUpload').attr('data-current-folder', current_dir);
        }


        //custome event for enter key
        $('#sirv-search-field').keyup(function(e){
            if(e.keyCode == 13)
            {
                $(this).trigger("enterKey");
            }
        });


        function bindEvents(){

            $('.sirv-link').bind('click', getContentFromSirv);
            $('.sirv-image').bind('click', function(event){selectImages(event, $(this))});
            $('.insert').bind('click', insert);
            $('#sirv-search-field').bind('enterKey', searchImages);
            $('#sirv-search-field-btn').bind('click', searchImages);
            $('.sirv-search').bind('click', searchImages);
            $('.create-gallery').bind('click', createGallery);
            $('.clear-selection').bind('click', clearSelection);
            $('.delete-selected-images').bind('click', deleteSelectedImages);
            $('.create-folder').bind('click', createFolder);
            $('#filesToUpload').bind('change', function(event){uploadImages(event.target.files);});
            $('#gallery-flag').bind('click', checkGalleryFlag);
            $('#gallery-zoom-flag').bind('click', checkGalleryZoomFlag);
            $('.sirv-gallery-type').bind('change', checkEmbededAsStates);
            $('.set-featured-image').bind('click', setFeaturedImage);
        };


        function unbindEvents(){

            $('.insert').unbind('click');
            $('.sirv-search').unbind('click');
            $('#sirv-search-field').unbind('enterKey');
            $('#sirv-search-field-btn').unbind('click');
            $('.create-gallery').unbind('click');
            $('.sirv-link').unbind('click');
            $('.sirv-image').unbind('click');
            $('.clear-selection').unbind('click');
            $('.delete-selected-images').unbind('click');
            $('.create-folder').unbind('click');
            $('#filesToUpload').unbind('change');
            $('#gallery-flag').unbind('click');
            $('#gallery-zoom-flag').unbind('click');
            $('.sirv-gallery-type').unbind('change');
            $('.set-featured-image').unbind('click');
        }


        window['getContentFromSirv'] = function(pth){
            var path;

            if(!pth || typeof(pth) == 'object' || pth == undefined){
                try {
                    path = $(this).attr('data-link');
                    if(path == undefined){
                        path = '';
                    }
                }catch(err) {
                    path = '';
                }
            }else{
                path = pth;
            }

            var data = {}
            data['action'] = 'sirv_get_aws_object';
            data['path'] = path;

            $.ajax({
                url: ajax_object.ajaxurl,
                data: data,
                type: 'POST',
                beforeSend: function(){
                    $('.loading-ajax').show();
                }

            }).done(function(data){       
                //debug
                //console.log(data);
                $('.loading-ajax').hide();

                var json_obj = $.parseJSON(data);
                contentData = json_obj;

                erase_view();
                render_breadcramb(json_obj.current_dir);
                set_current_dir(json_obj.current_dir);
                render_view(json_obj);
                restoreSelections(false);
                bindEvents();
                patchMediaBar();

            }).fail(function( jqXHR, textStatus ) {
                    $('.loading-ajax').hide();
                    console.log( "Request failed: " + textStatus );
            });

        }


        function patchMediaBar(){

            if($('#chrome_fix', top.document).length <= 0){
                $('head', top.document).append($('<style id="chrome_fix">.media-frame.hide-toolbar .media-frame-toolbar {display: none;}</style>'));
            }
        }


        //create folder
         function createFolder(){
            var newFolderName = window.prompt("Enter folder name:");
            if(newFolderName != null || newFolderName != ''){
                if(!newFolderName){
                    //some code here
                }

                var data = {}

                data['action'] = 'sirv_add_folder';
                data['current_dir'] = $('#filesToUpload').attr('data-current-folder');
                data['new_dir'] = newFolderName;

                $.ajax({
                    url: ajax_object.ajaxurl,
                    type: 'POST',
                    data: data,
                    beforeSend: function(){
                        $('.loading-ajax').show();
                    }
                }).done(function(response){
                    //show error message
                    //console.log(response);
                    $('.loading-ajax').hide();

                    getContentFromSirv(data.current_dir);
                });
            }
        }


        //upload images
        window['uploadImages'] = function(files){

            var current_dir = $('#filesToUpload').attr('data-current-folder');
            //var files = event.target.files;
            var data = new FormData();

            data.append('action', 'sirv_upload_files');
            data.append('current_dir', current_dir);

            $.each(files, function(key, value)
            {
                data.append(key, value);
            });

            $.ajax({
                //progress status
                xhr: function()
                {
                var xhr = new window.XMLHttpRequest();
                //Upload progress
                xhr.upload.addEventListener("progress", function(evt) {
                if (evt.lengthComputable) {
                var percentComplete = evt.loaded / evt.total;
                //Do something with upload progress
                // console.log(percentComplete);
                //console.log(percentComplete * 100 + '%');
                }
                }, false);

                //Download progress

                xhr.addEventListener("progress", function(evt) {
                if (evt.lengthComputable) {
                var percentComplete = Math.round(evt.loaded * 100 / evt.total);
                //Do something with download progress
                //console.log(percentComplete + '%');
                }

                }, false);
                return xhr;
                },
                url: ajax_object.ajaxurl,
                type: 'POST',
                contentType: false,
                processData: false,
                data: data,
                //timeout: 500,
                beforeSend: function(){
                    $('.loading-ajax').show();
                }
            }).done(function(response){
                $('.loading-ajax').hide();
                console.log(response);

                //clear list of files
                var input = $("#filesToUpload");
                input.replaceWith(input.val('').clone(true));

                getContentFromSirv(current_dir);
                
            }).fail(function( jqXHR, textStatus ) {
                    $('.loading-ajax').hide();
                    console.log( "Request failed: " + textStatus );
            });
        }


        function searchImages(){
            var querySearch = $('#sirv-search-field').val();
            var data = JSON.parse(JSON.stringify(contentData));
            console.log(contentData);
            if (!querySearch){
                //return;
            }
            if (data.contents.length > 0){
                var imgNamesArray = [];
                for(var i=0; i<data.contents.length; i++){
                    if (data.contents[i].Key.toLowerCase().indexOf(querySearch.toLowerCase()) != -1){
                        imgNamesArray.push({"Key" : data.contents[i].Key});
                    }
                }

                data.contents = imgNamesArray;
                
                erase_view();
                render_breadcramb(data.current_dir);
                set_current_dir(data.current_dir);
                render_view(data);
                restoreSelections(false);
                bindEvents();
                patchMediaBar();

            }
        }

        function basename(path) {
            return path.split('/').reverse()[0];
        }


        function deleteSelectedImages(){
            var current_dir = $('#filesToUpload').attr('data-current-folder');
            var filenamesArray = [];
            var data = {}
            var selectedImages = $('.selected-miniature-img');
            $.each(selectedImages, function(index, value){
                filenamesArray.push($(value).attr('data-dir') + basename($(value).attr('data-original')));
           });

            data['action'] = 'sirv_delete_files';
            data['filenames'] = filenamesArray;
            $.ajax({
                url: ajax_object.ajaxurl,
                type: 'POST',
                data: data,
                beforeSend: function(){
                    $('.loading-ajax').show();
                }
            }).done(function(response){
                $('.loading-ajax').hide();
                getContentFromSirv(current_dir);
                clearSelection();
            });
        }

        function deleteAllImages(isRecursive){
            //pass
        }



        function selectImages(event, $object){

            function addMiniatures(object){
                $('.selected-miniatures-container').append('<li class="selected-miniature"><img class="selected-miniature-img" data-id="'+ object.attr('data-id') +
                    '" data-original="'+ object.attr('data-original') +'" data-type="'+ object.attr('data-type') +
                    '" data-dir="'+ $('#filesToUpload').attr('data-current-folder') +'"'+
                    ' data-caption="" src="'+ object.attr('data-original') +'?thumbnail=40&image=true"' +' /></li>\n');
            }

            function removeMiniatures(object){
                $($('img[data-id='+ object.attr('data-id')+ ']').closest('li.selected-miniature')).remove();
            }

            var curr = -1;

            if(event.ctrlKey){
                event.preventDefault();
            }

            if(event.shiftKey){
                event.preventDefault();

                curr = $('.sirv-image').index($object);
                if(prev > -1){
                        var miniaturesArray= [];
                        $('.selected-miniature-img').each(function(){
                            miniaturesArray.push($(this).attr('data-id'));
                        });
                    $('.sirv-image').slice(Math.min(prev, curr), 1 + Math.max(prev, curr)).each(function(){
                        if ($.inArray($(this).attr('data-id'), miniaturesArray) == -1){
                            $(this).addClass('selected');
                            $(this).closest('li').addClass('selected');
                            addMiniatures($(this));
                        }
                    });
                }
            }else{
                curr = prev = $('.sirv-image').index($object);

                if($object.hasClass('selected')){
                    $object.removeClass('selected');
                    $object.closest('li').removeClass('selected');
                    removeMiniatures($object);

                } else{
                    $object.addClass('selected');
                    $object.closest('li').addClass('selected');
                    addMiniatures($object);
                }
            } 

            if ($('.selected-miniature-img').length > 0){
                $('.selection-content').addClass('items-selected');
                $('.count').text($('.selected-miniature-img').length + " selected");
            } else $('.selection-content').removeClass('items-selected');
        };



        function restoreSelections(isAddImages){

            $('.selected').removeClass('selected');

            if(isAddImages){
                $('.selected-miniatures-container').empty();

                if($('.gallery-img').length > 0){
                    var galleryItems = $('.gallery-img');

                    $.each(galleryItems, function(index, value){
                        $('.selected-miniatures-container').append('<li class="selected-miniature"><img class="selected-miniature-img" data-id="'+ $(this).attr('data-id') +
                            '" data-original="'+ $(this).attr('data-original') +'" data-type="'+ $(this).attr('data-type') + '"'+
                            '  data-caption="'+ $(this).parent().siblings('span').children().val() +'"'+
                            '  src="'+ $(this).attr('data-original') +'?thumbnail=40&image=true"' +' /></li>\n');
                    });
                }
            }

            if($('.selected-miniature-img').length > 0){
                $('.count').text($('.selected-miniature-img').length + " selected");
                
                if($('.selection-content').not('.items-selected')){
                    $('.selection-content').addClass('items-selected');
                }

                var selectedImages = $('.selected-miniature-img');

                $.each(selectedImages, function(index, value){
                    $('.sirv-image[data-id="' + $(value).attr('data-id') +'"]').closest('li').addClass('selected');
                    $('.sirv-image[data-id="' + $(value).attr('data-id') +'"]').addClass('selected');
                });
            }else{
                $('.selection-content').removeClass('items-selected');
            }
        }


        function clearSelection(){

            $(".selected-miniatures-container").empty();
            $('.selected').removeClass('selected');
            $('.selection-content').removeClass('items-selected');
            $('.count').text($('.selected-miniature-img').length + " selected");
        }


        function insert(){

                var html = '';
                var $gallery = $('.sirv-gallery-type[value=gallery-flag]'),
                    $zoom = $('.sirv-gallery-type[value=gallery-zoom-flag]'),
                    $spin = $('.sirv-gallery-type[value=360-spin]');

                if($gallery.is(':checked') || $zoom.is(':checked') || $spin.is(':checked')){
                    if($('.insert').hasClass('edit-gallery')){
        
                        save_shorcode_to_db('sirv_update_sc', window.top.sirv_sc_id);
                        //hack to show visualisation of shortcode
                        window.parent.switchEditors.go("content", "html");
                        window.parent.switchEditors.go("content", "tmce");

                    }else{

                        var id = save_shorcode_to_db('sirv_save_shortcode_in_db');
                        html = '[sirv-gallery id='+ id +']';
                    }

                }else{

                var links = $('.gallery-img');

                var galleryAlign = $('#gallery-align').val();
                galleryAlign = galleryAlign == '' ? '' : 'class="align' + galleryAlign.replace('sirv-', '')+'"';

                var profile = $('#gallery-profile').val();
                profile = profile == false ? '' : '?profile='+profile;

                $.each(links, function(index, value){
                    var $imgSrc = $(value).attr('data-original');
                    var $width = Number($('#gallery-width').val());
                    if (!isNaN($width)) {
                        $imgSrc += '?scale.width='+$width;
                        $width = 'width="'+$width+'"';
                    } else {
                        $width = '';
                    }
                    if ($('#gallery-link-img').is(":checked")) {
                        html += '<a href="' + $(value).attr('data-original') + '">';
                    }
                    html += '<img '+$width+' '+ galleryAlign +' src="' + $imgSrc + profile + '" alt="'+ $(this).parent().siblings('span').children().val() +'">';
                    if ($('#gallery-link-img').is(":checked")) {
                        html += '</a>';
                    }
                });
                }
                //some strange issue with firefox. If return empty string, than shortcode html block will broken. So return string only if not empty.
                if(html != ''){
                    window.parent.send_to_editor(html);
                }
                //window.parent.tb_remove();
                //window.location.reload();
                bPopup.close();
        }


        function setFeaturedImage(){
            if($('.selected-miniature-img').length > 0){
                var selectedImage = $('.selected-miniature-img');
                var inputAnchor = $('#sirv-add-featured-image').attr('data-input-anchor');

                $(inputAnchor).val($(selectedImage).attr('data-original'));

                bPopup.close();
            }
        }


        function createGallery(){

            $('.selection-content').hide();
            $('.gallery-creation-content').show();
            imageSortable();

            if($('.selected-miniature-img').length > 0){
                var selectedImages = $('.selected-miniature-img');
                var documentFragment = $(document.createDocumentFragment());
                $.each(selectedImages, function(index, value){
                    var elemBlock = $('<li class="gallery-item"><div><div><a class="delete-image delete-image-icon" href="#" title="Remove"></a>'+
                                                        '<img class="gallery-img" src="'+ $(value).attr('data-original') +'?thumbnail=150&image=true"'+
                                                        ' data-id="'+ $(value).attr('data-id') +'"'+
                                                        'data-order="'+ index +'"'+
                                                        'data-original="'+ $(value).attr('data-original') +
                                                        '" data-type="'+ $(value).attr('data-type') +'" alt=""></div>'+
                                                        '<span><input type="text" placeholder="Text caption.."'+
                                                        ' data-setting="caption" class="image-caption" value="'+ $(value).attr('data-caption') +'" /></span></div></li>\n');
                    documentFragment.append(elemBlock);
                });

                $('.gallery-container').append(documentFragment);


                //bind events
                $('.delete-image').bind('click', removeFromGalleryView);
                $('.select-images').bind('click', function(){selectMoreImages(false)});
            }

            checkSpinState();

        }


        function removeFromGalleryView(){
            $(this).closest('li.gallery-item').remove();
            checkSpinState();
        }

        function clearGalleryView(){
            $('.gallery-container').empty();
        }


        function selectMoreImages(isEditGallery){
            $('.create-gallery>span').text('Add images');
            $('.gallery-creation-content').hide();
            $('.selection-content').show();
            restoreSelections(true);
            if(isEditGallery){
                //getData();
                getContentFromSirv();
            }
            clearGalleryView();
            $('.delete-image').unbind('click');
            $('.select-images').unbind('click');
        }

        function imageSortable(){

            function reCalcOrder(){
                $('.gallery-img').each(function(index){
                    $(this).attr('data-order', index);
                });
            }

            $( ".gallery-container" ).sortable({
                revert: true,
                cursor: "move",
                scroll: false,
                stop: function( event, ui ) {
                    reCalcOrder();
                }
            });
        }



        function getShortcodeData(){

            function getEmbededAsValue(value){
                var $gallery = $('.sirv-gallery-type[value=gallery-flag]'),
                    $zoom = $('.sirv-gallery-type[value=gallery-zoom-flag]'),
                    $spin = $('.sirv-gallery-type[value=360-spin]');
                switch(value){
                    case 'gallery-flag':
                        return ($gallery.is(':checked') || $zoom.is(':checked') || $spin.is(':checked')) ? true : false;
                        break;
                    case 'gallery-zoom-flag': 
                        return $zoom.is(':checked') ? true : false;
                        break;
                }
            }

            var shortcode_data = {}
            shortcode_data['width'] = $('#gallery-width').val();
            shortcode_data['thumbs_height'] = $('#gallery-thumbs-height').val();
            shortcode_data['gallery_styles'] = $('#gallery-styles').val();
            shortcode_data['align'] = $('#gallery-align').val();
            shortcode_data['profile'] = $('#gallery-profile').val();
            shortcode_data['use_as_gallery'] = getEmbededAsValue('gallery-flag');
            shortcode_data['use_sirv_zoom'] = getEmbededAsValue('gallery-zoom-flag');
            shortcode_data['link_image'] = $('#gallery-link-img').is(":checked");
            shortcode_data['show_caption'] = $('#gallery-show-caption').is(":checked");

            var images = []
            $('.gallery-img').each(function(){
                var tmp = {};
                var tmp_url = $(this).attr('data-original'); 
                tmp['url'] = tmp_url.replace(/http(?:s)*:/, '');
                tmp['order'] = $(this).attr('data-order');
                tmp['caption'] = $(this).parent().siblings('span').children().val() ;
                tmp['type'] = $(this).attr('data-type');

                images.push(tmp);
            });

            shortcode_data['images'] = images;

            return shortcode_data;
        }


        function save_shorcode_to_db(action, row_id){

            row_id = row_id || -1;
            var id,
                data = {}

            data['action'] = action;
            data['shortcode_data'] = getShortcodeData();
            if (row_id != -1) {
                data['row_id'] = row_id;
            };

            $.ajax({
                url: ajax_object.ajaxurl,
                type: 'POST',
                async: false,
                data: data,
                beforeSend: function(){
                    $('.loading-ajax').show();
                }
            }).done(function(response){
                $('.loading-ajax').hide();
                id = response;
            });

            return id;
        }


        window['sirvEditGallery'] = function(){
            $('.selection-content').hide();
            $('.gallery-creation-content').show();
            $('.edit-gallery>span').text('Save');
            $('.insert>span').text('Update');
            $('.select-images>span').text('Add images');
            $('.sirv-gallery-type[value=plain-image]').attr('disabled', true);
            imageSortable();

            var id = window.top.sirv_sc_id;

            var data = {}
            data['action'] = 'sirv_get_row_by_id';
            data['row_id'] = id;

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data,
                async: false,
                dataType: 'json',
                beforeSend: function(){
                    $('.loading-ajax').show();
                }
            }).done(function(response){
                $('.loading-ajax').hide();
                $('#gallery-width').val(response['width']);
                $('#gallery-thumbs-height').val(response['thumbs_height']);
                $('#gallery-styles').val(response['gallery_styles']);
                $("#gallery-align").val(response['align']);
                $("#gallery-profile").val(response['profile']);
                if($.parseJSON(response['use_sirv_zoom']) == true){
                    $('.sirv-gallery-type[value=gallery-zoom-flag]').attr('checked', true);
                }else{
                    $('.sirv-gallery-type[value=gallery-flag]').attr('checked', true);
                }
                $('#gallery-flag').prop('checked', $.parseJSON(response['use_as_gallery']));
                $('#gallery-zoom-flag').prop('checked', $.parseJSON(response['use_sirv_zoom']));
                $('#gallery-link-img').prop('checked', $.parseJSON(response['link_image']));
                $('#gallery-show-caption').prop('checked', $.parseJSON(response['show_caption']));

                checkFlagStates();

                function stripslashes(str) {
                    str=str.replace(/\\'/g,'\'');
                    str=str.replace(/\\"/g,'&quot;');
                    str=str.replace(/\\0/g,'\0');
                    str=str.replace(/\\\\/g,'\\');
                    return str;
                    }
   
                var images = response['images'];
                var documentFragment = $(document.createDocumentFragment());
                for(var i = 0; i < images.length; i++){
                    var caption = stripslashes(images[i]['caption']);

                    var elemBlock = $('<li class="gallery-item"><div><div><a class="delete-image delete-image-icon" href="#" title="Remove"></a>'+
                                                        '<img class="gallery-img" src="https:'+ images[i]['url'] +'?thumbnail=150&image=true"'+
                                                        ' data-id="'+ md5(images[i]['url']) +'"'+
                                                        'data-order="'+ images[i]['order'] +'"'+
                                                        'data-original="https:'+ images[i]['url'] +
                                                        '" data-type="'+ images[i]['type'] +'" alt=""></div>'+
                                                        '<span><input type="text" placeholder="Text caption..."'+
                                                        ' data-setting="caption" class="image-caption" value="'+ caption +'" /></span></div></li>\n');
                    documentFragment.append(elemBlock);                    
                }

                function checkFlagStates(){
                    if($('#gallery-flag').is(':checked')){
                        $('#gallery-zoom-flag').removeAttr("disabled");
                        $('#gallery-styles').removeAttr("disabled");
                        $('#gallery-thumbs-height').removeAttr("disabled"); 
                        $('#gallery-show-caption').removeAttr("disabled"); 
                    }
                    if(!$('#gallery-zoom-flag').not(':checked')){
                        $('#gallery-link-img').removeAttr("disabled"); 
                    }
                }

                $('.gallery-container').append(documentFragment);

                checkSpinState();

                //bind events
                $('.delete-image').bind('click', removeFromGalleryView);
                $('.select-images').bind('click', function(){selectMoreImages(true)});
                $('.insert').bind('click', insert);
            });
         };

        //$('#gallery-flag').click(
        function checkGalleryFlag() {
            if($(this).is(":checked")){
                $('#gallery-zoom-flag').removeAttr("disabled");
                $('#gallery-styles').removeAttr("disabled");
                $('#gallery-thumbs-height').removeAttr("disabled");
                $('#gallery-link-img').removeAttr("disabled"); 
                $('#gallery-show-caption').removeAttr("disabled");                 
            }else{
                $('#gallery-zoom-flag').attr('disabled', true)
                $('#gallery-zoom-flag').attr('checked', false);
                $('#gallery-link-img').attr('disabled', true)
                $('#gallery-link-img').attr('checked', false);
                $('#gallery-show-caption').attr('disabled', true)
                $('#gallery-show-caption').attr('checked', false);
                $('#gallery-styles').attr('disabled', true);
                $('#gallery-thumbs-height').attr('disabled', true);
            }
        }

        //$('#gallery-zoom-flag').click(
        function checkGalleryZoomFlag() {
            if($(this).is(":checked")){
                $('#gallery-link-img').attr("disabled", true);
                $('#gallery-link-img').attr("checked", false);                
            }else{
                $('#gallery-link-img').attr("disabled", false);
                $('#gallery-link-img').attr("checked", false);

            }
        }

        function checkEmbededAsStates(){
            if($(this).val() == 'gallery-flag'){
                $('#gallery-styles').removeAttr("disabled");
                $('#gallery-thumbs-height').removeAttr("disabled");
                $('#gallery-link-img').removeAttr("disabled"); 
                $('#gallery-show-caption').removeAttr("disabled");

            }else if($(this).val() == 'gallery-zoom-flag'){
                $('#gallery-link-img').attr("disabled", true);
                $('#gallery-link-img').attr("checked", false);
                $('#gallery-styles').removeAttr("disabled");
                $('#gallery-thumbs-height').removeAttr("disabled");
                $('#gallery-show-caption').removeAttr("disabled");

            }else if($(this).val() == 'plain-image'){
                $('#gallery-zoom-flag').attr('disabled', true)
                $('#gallery-zoom-flag').attr('checked', false);
                
                $('#gallery-link-img').attr('disabled', false)
                //$('#gallery-link-img').attr('checked', false);
                
                $('#gallery-show-caption').attr('disabled', true)
                $('#gallery-show-caption').attr('checked', false);
                $('#gallery-styles').attr('disabled', true);
                $('#gallery-thumbs-height').attr('disabled', true);
            }
        }

        function checkSpinState(){
            if($('.gallery-img').length == 1 && $('.gallery-img').attr('data-type') == 'spin'){
                $('.sirv-gallery-type').attr('disabled', true);
                $('.sirv-gallery-type[value=360-spin]').attr('disabled', false);
                $('.sirv-gallery-type[value=360-spin]').attr('checked', true);
                $('#gallery-zoom-flag').attr('disabled', true)
                $('#gallery-zoom-flag').attr('checked', false);
                $('#gallery-link-img').attr('disabled', true)
                $('#gallery-link-img').attr('checked', false);
                $('#gallery-show-caption').attr('disabled', true)
                $('#gallery-show-caption').attr('checked', false);
                $('#gallery-styles').attr('disabled', true);
                $('#gallery-thumbs-height').attr('disabled', true);
            }else{
                if($('.sirv-gallery-type[value=360-spin]').is(':checked')){
                    $('.sirv-gallery-type').attr('disabled', false);
                    $('.sirv-gallery-type[value=360-spin]').attr('disabled', true);
                    $('.sirv-gallery-type[value=gallery-zoom-flag]').attr('checked', true);
                    if($('.insert').hasClass('edit-gallery')){
                        $('.sirv-gallery-type[value=plain-image]').attr('disabled', true);
                    }
                }
            }
        }

        function setProfiles(){
            var data = {}
            data['action'] = 'sirv_get_profiles';

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data,
                async: false,
                beforeSend: function(){
                    $('.loading-ajax').show();
                }
            }).done(function(response){
                $('.loading-ajax').hide();
                $('#gallery-profile').empty();
                $('#gallery-profile').append($(response));
            });
        }

        // Initialization
        patchMediaBar();
        
        //check if run shortcode edition
        if(window.top.sirv_edit_flag !== undefined && window.top.sirv_edit_flag == true){
            window.top.sirv_edit_flag = false;
            $('.insert').addClass('edit-gallery');
            editGallery();

        }else{
            getContentFromSirv();
        }

    });
});