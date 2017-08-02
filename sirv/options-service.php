<?php
class getValue{

    public static function getOption($optionName){
        if($optionName == 'AWS_HOST'){
            return 'http://' . get_option($optionName);
        }else return get_option($optionName);

    }

}
?>