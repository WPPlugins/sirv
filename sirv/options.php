<script>
    jQuery(function($){
        $(document).ready(function(){

            $('.nav-tab-wrapper > a').on('click',function(e){
                $('.sirv-tab-content').removeClass('sirv-tab-content-active');
                $('.nav-tab-wrapper > a').removeClass('nav-tab-active');
                $('.sirv-tab-content'+$(this).attr('href')).addClass('sirv-tab-content-active');
                $(this).addClass('nav-tab-active').blur();
                $('#active_tab').val($(this).attr('href'));
                e.preventDefault();
            })

            $('.test-connect').on('click', function(){
                var host = $('input[name=AWS_HOST]').val(),
                    bucket = $('input[name=AWS_BUCKET]').val(),
                    key = $('input[name=AWS_KEY]').val(),
                    secret_key = $('input[name=AWS_SECRET_KEY]').val();
                    
                $('.show-result').text("Testing connection...").show();

                $.post(ajaxurl, {
                action: 'sirv_check_connection',
                host: host,
                bucket: bucket,
                key: key,
                secret_key: secret_key
                }).done(function(data){
                    //debug
                    //console.log(data);
                    $('.show-result').text(data);

                }).fail(function(){
                    $('.show-result').text("Failed ajax request to check sirv login details!");
                });
            })

            //ajax request to get cached count and size of images
            $.post(ajaxurl,{
                action: 'sirv_get_cached_data',
                get_cached_data: true
            }).done(function(data){
                    //debug
                    //console.log(data);
                    var json_obj = $.parseJSON(data);
                    //disable clean cache button if empty cache
                    if(json_obj[0].count == '0'){
                        $('input[name=clear-cache]').prop('disabled', true);
                    }else{
                        $('input[name=clear-cache]').prop('disabled', false);
                    }
                    
                    sum_size = json_obj[0].sum_size/1000000;
                    $('.cache-img-num').text(json_obj[0].count);
                    $('.cache-size').text( sum_size.toFixed(2) +' MB');

                }).fail(function(){
                    console.log("Failed ajax request to get cached image data!");
            });


            //ajax erquest to clean up cache
            $('.clear-cache').on('click', function(){
                var cleanType = $('input[name=cache-clean-type]:checked').val();

                $.post(ajaxurl,{
                    action: 'sirv_clear_cache',
                    clean_cache_type: cleanType
                }).done(function(data){
                    //debug
                    //console.log(data);
                    $('.cache-img-num').text('0');
                    $('.cache-size').text( '0.00 MB');
                    $('.clear-cache-result').text('Successfuly cleaned up');
                    $('input[name=clear-cache]').prop('disabled', true);

                }).fail(function(){
                    //console.log("Failed ajax request to clean up cache");
                    $('.clear-cache-result').text('Something went wrong!');
                });

            });


            //send the message from plugin to support@sirv.com
            $('#send-email-to-magictoolbox').on('click', function(){
              //fields
              var name = $('#sirv-writer-name').val();
              var contactEmail = $('#sirv-writer-contact-email').val();
              var priority = $('#sirv-priority').val();
              var summary = $('#sirv-summary').val();
              var messageText = $('#sirv-text').val();

              //messages;
              var proccessingSendMessage = 'Sending message. This may take some time...';
              var messageSent = 'Your message has been sent.';
              var ajaxError = 'Error during AJAX request. Please try to send the message again or use the <a target="_blank" href="https://sirv.com/contact/">Sirv contact form here</a> Error: <br/>';
              var sendingError = 'Something went wrong. The most likely reason is that Sendmail is not installed/configured. Please try to send the message again or use the <a target="_blank" href="https://sirv.com/contact/">Sirv contact form here</a>';
              //form messages
              var emptyFields = '<span style="color: red;">Please fill form fields.</span>';
              var incorrectEmail = '<span style="color: red;">Email is incorrect. Please check email field.</span>';

              var generatedViaWP = '\n\n\n---\nThis message was sent from the Sirv WordPress plugin form at ' + document.location.hostname;


              var formMessages = '';

              if(summary == '' || messageText == '' || name == '' || contactEmail == ''){
                formMessages += emptyFields + '<br />';
              }

              if(contactEmail.match(/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,3}$/i) == null && contactEmail != ''){
                formMessages += incorrectEmail + '<br />';
              }

              if(formMessages != ''){
                $('.sirv-show-result').html(formMessages);
                return false;
              }

              $.post(ajaxurl,{
                action: 'sirv_send_message',
                name: name,
                emailFrom: contactEmail,
                priority: priority + ' (via WP)',
                summary: summary,
                text: 'Contact name: ' + name +'\n' + 'Contact Email: ' + contactEmail +'\n\n' + messageText + generatedViaWP,
                beforeSend: function(){
                    $('.sirv-show-result').text(proccessingSendMessage);
                }
              }).done(function(data){
                    //debug
                    //console.log(data);

                    if(data == '1'){
                      $('.sirv-show-result').text(messageSent);                      
                    }else{
                      $('.sirv-show-result').html(sendingError);
                    }

                }).fail(function(jqXHR, status, error){
                    console.log("Error during ajax request: " + error);
                    $('.sirv-show-result').html(ajaxError + error);
                });

            });

        });//domready end
    });    
</script>
<style type="text/css">
.optiontable.form-table input[type="text"] {max-width: 100%; min-width: 360px;}
.show-result, .sirv-show-result{font-weight: 600; line-height: 1.3; text-align: left;}
nav.nav-tab-wrapper {
    margin: 1.5em 0 1em;
    border-bottom: 1px solid #ccc;
}
.sirv-tab-content { display:none; }
.sirv-tab-content.sirv-tab-content-active { display: block; }
.sirv-tab-content .show-result { margin:10px 0 0 0; }
.sirv-tab-content .form-table th, 
.sirv-tab-content .form-table td { padding: 10px; }
.sirv-tab-content label { margin-bottom: 5px; display: block; }
.sirv-tab-content p, .sirv-tab-content li { font-size:14px; }

.sirv-optiontable-holder { padding: 10px;  border: 1px solid #ddd; background: #f9f9f9; display: inline-block; margin-bottom:10px; }

.sirv-optiontable-holder { }
.sirv-optiontable-holder table { min-width: 300px; width: 700px; }
.sirv-optiontable-holder table input[type="text"] { width: 100%; }

.form-table th { width: 100px; }

.required::after{
  content: '*';
  color: red;
}


/* ============================================================
  COMMON
============================================================ */
.cmn-toggle {
  position: absolute;
  margin-left: -9999px;
  visibility: hidden;
}
.cmn-toggle + label {
  display: block;
  position: relative;
  cursor: pointer;
  outline: none;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
}

/* ============================================================
  SWITCH 1 - ROUND
============================================================ */
input.cmn-toggle-round + label {
  padding: 2px;
  width: 30px;
  height: 15px;
  background-color: #dddddd;
  -webkit-border-radius: 30px;
  -moz-border-radius: 30px;
  -ms-border-radius: 30px;
  -o-border-radius: 30px;
  border-radius: 30px;
  display: inline-block;
}
input.cmn-toggle-round + label:before, input.cmn-toggle-round + label:after {
  display: block;
  position: absolute;
  top: 1px;
  left: 1px;
  bottom: 1px;
  content: "";
}
input.cmn-toggle-round + label:before {
  right: 1px;
  background-color: #f1f1f1;
  -webkit-border-radius: 30px;
  -moz-border-radius: 30px;
  -ms-border-radius: 30px;
  -o-border-radius: 30px;
  border-radius: 30px;
  -webkit-transition: background 0.4s;
  -moz-transition: background 0.4s;
  -o-transition: background 0.4s;
  transition: background 0.4s;
}
input.cmn-toggle-round + label:after {
  width: 17px;
  background-color: #fff;
  -webkit-border-radius: 100%;
  -moz-border-radius: 100%;
  -ms-border-radius: 100%;
  -o-border-radius: 100%;
  border-radius: 100%;
  -webkit-box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
  -moz-box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
  -webkit-transition: margin 0.4s;
  -moz-transition: margin 0.4s;
  -o-transition: margin 0.4s;
  transition: margin 0.4s;
}
input.cmn-toggle-round:checked + label:before {
  background-color: #8ce196;
}
input.cmn-toggle-round:checked + label:after {
  margin-left: 15px;
}

input.cmn-toggle-round + label + span:before { content: 'Disabled'; color:red; margin-left:5px; top:-2px; font-weight: bold; } 
input.cmn-toggle-round:checked + label + span:before { content: 'Enabled'; color:green; } 

.s3access-image { margin:0 0 10px 20px; max-width: 100%; }

@media only screen and (max-width: 1199px) {
  .s3access-image { float: none; }
}


.sirv-show-result{padding-top: 8px;}

</style>

<div class="wrap">

    <h2>Sirv settings</h2>
    
    <form action="options.php" method="post" id="">
        
        <?php wp_nonce_field('update-options'); ?>

        <?php
            $active_tab = (isset($_POST['active_tab']))? $_POST['active_tab'] : '#sirv-account';
        ?>

        <nav class="nav-tab-wrapper">        
            <a class="nav-tab <?php echo ( $active_tab=='#sirv-account' )?'nav-tab-active':'' ?>" href="#sirv-account">Account &amp; CDN</a>
            <?php if (get_option('WP_USE_SIRV_CDN')) { ?>
            <a class="nav-tab <?php echo ( $active_tab=='#sirv-cache' )?'nav-tab-active':'' ?>" href="#sirv-cache">Cache</a>
            <?php } ?>
            <a class="nav-tab <?php echo ( $active_tab=='#sirv-instructions' )?'nav-tab-active':'' ?>" href="#sirv-instructions">Instructions</a>
            <a class="nav-tab <?php echo ( $active_tab=='#help-feedback' )?'nav-tab-active':'' ?>" href="#help-feedback">Help &amp; Feedback</a>
        </nav>
        
        <div class="sirv-tab-content sirv-tab-content-active" id="sirv-account">
            <?php if (!sirv_test_connection('http://'.get_option('AWS_HOST'),get_option('AWS_BUCKET'),get_option('AWS_KEY'),get_option('AWS_SECRET_KEY'))) { /* ?>
            <p>Enter your Sirv S3 settings, then you can embed images into your WordPress pages and posts.<br />
                <ol>
                    <li><a target="_blank" href="https://my.sirv.com/#/signup">Create Sirv account</a> or <a target="_blank" href="https://my.sirv.com/#/signin">login to your Sirv account</a></li>
                    <li>Copy and paste <a href="https://my.sirv.com/#/account/">your S3 settings</a> into the fields below.</li>
                </ol>
            </p>
            <?php */ } ?>


            <h2>Connect your Sirv account</h2>

            <p>Enter your S3 credentials from your <a target="_blank" href="https://my.sirv.com/#/account/">Sirv account page</a>.</p>

            <table style="width:100%"><tr>

            <td style="vertical-align: top">
  
            <div class="sirv-optiontable-holder" style="float:left;">
                <table class="optiontable form-table">            
                    <tr>
                        <th><label>S3 Endpoint: </label></th>
                        <td><input type="text" name="AWS_HOST" value="<?php echo get_option('AWS_HOST'); ?>" readonly></td>
                    </tr>
                    <tr>
                        <th><label>S3 Bucket: </label></th>
                        <td><input type="text" name="AWS_BUCKET" value="<?php echo get_option('AWS_BUCKET'); ?>"></td>
                    </tr>
                    <tr>
                        <th><label>S3 Key: </label></th>
                        <td><input type="text" name="AWS_KEY" value="<?php echo get_option('AWS_KEY'); ?>"></td>
                    </tr>
                    <tr>
                        <th><label>S3 Secret: </label></th>
                        <td><input type="text" name="AWS_SECRET_KEY" value="<?php echo get_option('AWS_SECRET_KEY'); ?>"></td>
                    </tr>
                    <tr>
                        <th></th>
                        <td>
                            <input type="button" class="button-primary test-connect" value="Test connection">
                            <input type="submit" name="submit" class="button-primary" value="<?php _e('Save Settings') ?>" />
                            <div class="show-result"></div>
                        </td>
                    </tr>
                </table>
            </div>

            </td>

            <td style="vertical-align: top; ">

            <img src="<?php echo plugin_dir_url( __FILE__ ) . "assets/s3access.png" ?>" class="s3access-image" />

            </td></tr>

            </table>

            <h2>Enable the Sirv CDN</h2>

            <p>
                Your images will be automatically copied to Sirv, optimized, resized and delivered fast by Sirv. Includes featured images, gallery images, WooCommerce & Jigoshop images, plus any other image added to your WordPress media library via a plugin or script.
            </p>

            <div class="sirv-optiontable-holder">
                <table class="optiontable form-table">
                    <tr><th>
                            <label>Network status:</label>
                        </th>
                        <td>
                            <input type="checkbox" class="cmn-toggle cmn-toggle-round" name="WP_USE_SIRV_CDN" id="WP_USE_SIRV_CDN" value='1' "<?php checked('1', get_option('WP_USE_SIRV_CDN')); ?>">
                            <label for="WP_USE_SIRV_CDN"></label>
                            <span></span>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label>Network: </label>
                        </th>
                        <td>
                            <label><input type="radio" name="WP_SIRV_NETWORK" value='1' "<?php checked(1, get_option('WP_SIRV_NETWORK'), true); ?>"><b>CDN</b> - deliver images rapidly from our global server network (24-hour cache delay).</label>
                            <label><input type="radio" name="WP_SIRV_NETWORK" value='2' "<?php checked(2, get_option('WP_SIRV_NETWORK'), true); ?>"><b>DIRECT</b> - deliver images from the primary Sirv datacentre (no cache delay).</label>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label>Folder name on Sirv: </label>
                        </th>
                        <td><input type="text" name="WP_FOLDER_ON_SIRV" value="<?php echo get_option('WP_FOLDER_ON_SIRV'); ?>"></td>
                    </tr>
                    <tr>
                        <th>
                        </th>
                        <td><input type="submit" name="submit" class="button-primary" value="<?php _e('Save Settings') ?>" /></td>
                    </tr>
                </table>
            </div>       

            <input type="hidden" name="active_tab" id="active_tab" value="#sirv-account" />
            <input type="hidden" name="action" value="update" />
            <input type="hidden" name="page_options" value="AWS_KEY,AWS_SECRET_KEY,AWS_HOST, AWS_BUCKET, WP_FOLDER_ON_SIRV, WP_USE_SIRV_CDN, WP_SIRV_NETWORK" />

<!--             <p><input type="submit" name="submit" class="button-primary" value="<?php _e('Save Settings') ?>" /></p>
 -->        
        </div>

        <?php if (get_option('WP_USE_SIRV_CDN')) { ?>
        <div class="sirv-tab-content" id="sirv-cache">
            <div class="sirv-optiontable-holder">
                <table class="optiontable form-table">
                    <tr>
                        <th><label style="white-space:nowrap;">Number of cached images: </label></th>
                        <td><span class="cache-img-num"></span></td>
                    </tr>
                    <tr>
                        <th><label>Cache size: </label></th>
                        <td><span class="cache-size"></span></td>
                    </tr>
                    <tr>
                        <th><label></label></th>
                        <td>
                            <label><input type="radio" id="radio_quick" name="cache-clean-type" value="quick" checked> <b>Quick</b> - Clean up local cache table only</label>
                            <label><input type="radio" id="radio_full" name="cache-clean-type" value="full"> <b>Full</b> - Clean up local cache table + delete images on Sirv</label>
                            <input type="button" name="clear-cache" class="button-primary clear-cache" value="Clean up cache" />
                            <div style="display: inline; margin-left: 5px; font-weight: bold;" class="clear-cache-result"></div>  
                        </td>
                    </tr>
                </table>
            </div>
        </div>            
        <?php } ?>


        <div class="sirv-tab-content" id="sirv-instructions">        
            <p><h2>Upload and embed images</h2></p>
            <ol>
            <li>Click the Add Sirv Media button on a page or post:</li><br />
            <img src="<?php echo plugin_dir_url( __FILE__ ) . "assets/wordpress-plugin-instructions-1.png" ?>" /><br /><br />

            <li>Choose the images you wish to embed:</li><br />
            <img src="<?php echo plugin_dir_url( __FILE__ ) . "assets/wordpress-plugin-instructions-2.png" ?>" /><br /><br />

            <li>Choose your options and click the Insert into page:</li><br />
            <img src="<?php echo plugin_dir_url( __FILE__ ) . "assets/wordpress-plugin-instructions-3.png" ?>" /><br /><br />

            <li>You will see your images embedded in your page as a gallery. You can edit it with the settings icon and delete it with the X icon.</li><br />
            <img src="<?php echo plugin_dir_url( __FILE__ ) . "assets/wordpress-plugin-instructions-4.png" ?>" /><br /><br />

            <li>Save your page and enjoy the images in your post:</li><br />
            <img src="<?php echo plugin_dir_url( __FILE__ ) . "assets/wordpress-plugin-instructions-5.png" ?>" /><br /><br />

            </ol>

            <p>In need of inspiration? <a href='https://sirv.com/demos/'>Check out these 11 demos</a> of zoom, responsive and 360 spin images using Sirv.</p>

            <p>Contact <a href="https://sirv.com/contact/">Sirv support</a> if you need help.</p> 
        </div>

        <div class="sirv-tab-content" id="help-feedback">        
            <!-- <h2>How can we help?</h2> -->
            <p>
                Search our <a target="_blank" href="https://sirv.com/help">help section</a>, for tutorials that help you get the best out of Sirv.<br /><br />
                <!--<a href="mailto:support@sirv.com">support@sirv.com</a>-->
                Popular articles:
            </p>
            <ul style="list-style-type: circle; padding-left: 2%;">
                <li><a href="https://sirv.com/help/resources/dynamic-imaging">Dynamic imaging guide</a> - for resizing, watermarking, optimizing and all other dynamic options</li>
                <li><a href="https://sirv.com/help/resources/responsive-imaging/">Responsive imaging guide</a> - for serving images to perfectly fit each users screen</li>
                <li><a href="https://sirv.com/help/resources/sirv-zoom/">Zoom guide</a> - for customizing your deep image zooms</li>
                <li><a href="https://sirv.com/help/resources/sirv-spin/">360 guide</a> - for customizing your 360 spins</li>
            </ul>
            <br />
            <p><h2>Contact us</h2>
            <div class="sirv-optiontable-holder">
                <table class="optiontable form-table">   
                    <tr>
                      <td>
                        <label class='required'><b>Your name:</b></label>
                        <input type="text" name="name" id="sirv-writer-name">
                      </td>
                    </tr>
                    <tr>
                      <td>
                        <label class='required'><b>Your email:</b></label>
                        <input type="text" name="contact-email" id="sirv-writer-contact-email">
                      </td>
                    </tr>
                    <tr>
                        <td>
                            <label>Priority:</label>
                            <select id="sirv-priority" name="priority">
                                <option label="Low" value="Low">Low</option>
                                <option label="Normal" value="Normal" selected="selected">Normal</option>
                                <option label="High" value="High">High</option>
                                <option label="Urgent" value="Urgent">Urgent</option>
                            </select>
                        </td>
                    </tr>
                    <tr>                        
                        <td>
                            <label class='required'><b>Summary:</b></label>
                            <input type="text" name="summary" id="sirv-summary">
                        </td>
                    </tr>
                    <tr>                        
                        <td>
                            <label class='required'><b>Describe your issue or share your ideas:</b></label>
                            <textarea style="width:100%;height:200px;" name="text" id="sirv-text"></textarea>
                        </td>
                    </tr>


                    <tr>
                        <td>
                            <input id="send-email-to-magictoolbox" type="button" class="button-primary test-connect" value="Send message">
                            <div class="sirv-show-result"></div>
                        </td>
                    </tr>
                </table>
            </div>

        </div>




    </form>
</div>