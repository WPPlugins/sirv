<?php

class Sirv_Gallery
{
    private $params;
    private $items;
    private $captions;
    private $initialized = false;
    private $inline_css = array();

    public function __construct($params=array(),$items=array(),$captions=array())
    {

        $this->params = array(
            'width'     => 'auto',
            'height'    => 'auto',
            'is_gallery'=> false,
            'profile'   => false,
            'link_image'=> false,
            'show_caption'=> true,
            'thumbnails_height' => 70,
            'apply_zoom'=> false,
            'gallery_styles' => '',
            'gallery_align' => ''
        );

        foreach($params as $name=>$value) {
            $this->params[$name] = $value;
        }

        if (empty($this->params['id'])) {
            $this->params['id'] = substr(uniqid(rand(),true),0,10);
        }

        $this->params['id'] = 'sirv-gallery-'.$this->params['id'];

        $this->items = $items;
        $this->captions = $captions;

        return true;
    }

    public function addCss($rule) {
        $this->inline_css[] = $rule;
    }
    
    public function getInlineCss() {
        return join("\r\n",$this->inline_css);
    }

    public function render() {

        if (count($this->items)==0) return '';

        $styles = array();
        if ($this->params['width']!='auto') {
            $this->addCss('#'.$this->params['id'].' { width: '.((preg_match('/%/',$this->params['width']))?$this->params['width']:$this->params['width'].'px;').' }');
        }
        /*
        if ($this->params['height']!='auto') {
            $this->addCss('#'.$this->params['id'].' { height: '.((preg_match('/%/',$this->params['height']))?$this->params['height']:$this->params['height'].'px;').' }');
        }
        */

        $this->addCss('.sirv-hidden { display:none; }');
        $this->addCss('#'.$this->params['id'].'.sirv-thumbs-box, #'.$this->params['id'].' .sirv-zoom-thumbnails.thumbs-horizontal { height: '.($this->params['thumbnails_height']).'px !important; }');
        $html[] = '<div class="sirv-gallery '.$this->params['gallery_align'].' '.(($this->params['is_gallery'])?' is-sirv-gallery':' no-gallery').((!$this->params['apply_zoom'])?' no-sirv-zoom':'').' '.$this->params['gallery_styles'].'" id="'.$this->params['id'].'">';

        $profile = ($this->params['profile'])?'?profile='.$this->params['profile']:'';

        $spins = $spins_html = $images = array();

        foreach($this->items as $i=>$item) {
            $caption = htmlspecialchars($this->captions[$i]);
            $caption_tag = ($this->params['show_caption'] && !$this->params['apply_zoom'])?'<div class="sirv-caption">'.$caption.'</div>':'';

            if (preg_match('/\.spin/is',$item)) {
                $spins_html[$i] = '<div data-item-id="'.$i.'" class="sirv-gallery-item'.($this->params['apply_zoom'] || ($this->params['is_gallery']&&$i>0)?' sirv-hidden':'').'"><div class="Sirv" data-src="'.$item.$profile.'"></div>'.$caption_tag.'</div>';
                $spins[$i] = $item.$profile;
                $image_captions[] = '';
            } else {
                
                $image_captions[] = $this->captions[$i];

                if (!isset($defaultCaption)) {
                    $defaultCaption = $this->captions[$i];
                }

                $open_tag = ($this->params['link_image'])?'<a href="'.$item.$profile.'">':'';
                $close_tag = ($this->params['link_image'])?'</a>':'';

                if ($this->params['apply_zoom']) {
                    $images[$i] = '<img data-title="'.$caption.'" class="Sirv" data-src="'.$item.$profile.'"/>';
                } else {
                    $images[$i] = '<div data-item-id="'.$i.'" class="sirv-gallery-item'.(($i>0&&$this->params['is_gallery'])?' sirv-hidden':'').'">'.$open_tag.'<img data-title="'.$caption.'" class="Sirv" data-src="'.$item.$profile.'"/>'.$close_tag.$caption_tag.'</div>';
                }
            }
        }

        if ($this->params['is_gallery']) {
            if ($this->params['apply_zoom']) {
                $html[] = '<div data-item-id="sirv-zoom" class="sirv-gallery-item"><div class="Sirv" data-id="'.$this->params['id'].'" data-effect="zoom" data-options="thumbnails: #sirv-thumbs-box-'.$this->params['id'].'">';
            }
            
            $html[] = join('',$images);
            
            if ($this->params['apply_zoom']) {
                $html[] = '</div></div>';
            }

            $html[] = join("\r\n",$spins_html);

            if ($this->params['apply_zoom'] && $this->params['show_caption']) {
                if (empty($defaultCaption)) {
                    $defaultCaption = '';
                }
                $html[] = '<div class="sirv-caption sirv-zoom-caption">'.$defaultCaption.'</div>';
            }
$html[] = '<div class="sirv-thumbs-box" id="sirv-thumbs-box-'.$this->params['id'].'">';
            if ($this->params['is_gallery'] && !$this->params['apply_zoom'] && count($this->items) > 1) {
                $html[] = '<ul>';
                foreach($this->items as $i=>$item) {
                    $caption = htmlspecialchars($this->captions[$i]);
                    $html[] = '<li'.(($i==0)?' class="sirv-thumb-selected"':'').'><img title="'.$caption.'" alt="'.$caption.'" data-item-id="'.$i.'" src="'.$item.$profile.((empty($profile))?'?': '&').'thumbnail='.$this->params['thumbnails_height'].((preg_match('/.*\.spin$/is',$item))?'&image=true&scale.option=noup':'').'"/></li>';
                }
                $html[] = '</ul>';
                
            }
            $html[] = '</div>';

        } else {
            $images = $spins_html+$images;
            ksort($images);

            foreach($images as $image) {
                $html[] = $image;
            }           
        }

        $html[] = '</div>';

        $spins = ($this->params['is_gallery'] && $this->params['apply_zoom'] && count($spins))?json_encode($spins):'[]';
        
        $image_captions = json_encode($image_captions,JSON_HEX_APOS);
        $captions = json_encode($this->captions,JSON_HEX_APOS);
        

        $html = join("\r\n",$html);

        $html = preg_replace('/<div/i','<div data-spins=\''.$spins.'\' data-captions=\''.$captions.'\' data-image-captions=\''.$image_captions.'\' data-thumbnails-height="'.$this->params['thumbnails_height'].'"',$html,1);
        // $html = preg_replace('/<div/i','<div data-spins=\''.$spins.'\' data-captions="'.$captions.'" data-image-captions="'.$image_captions.'" data-thumbnails-height="'.$this->params['thumbnails_height'].'"',$html,1);

        return $html.'<style type="text/css">'.$this->getInlineCss().'</style>';
    
    }
}

?>