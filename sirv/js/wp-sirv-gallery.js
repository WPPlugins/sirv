SirvOptions = {};

SirvOptions['zoom'] = {};
SirvOptions['captions'] = {};
SirvOptions['image_captions'] = {};

SirvOptions['zoom']['onchange'] = function() { };

SirvOptions['zoom']['onready'] = function(instance) {
    var $id = instance.placeholder.getAttribute('data-id');
    SirvOptions['captions'][$id]  = jQuery.parseJSON( jQuery('#'+$id).attr('data-captions') );
    SirvOptions['image_captions'][$id]  = jQuery.parseJSON( jQuery('#'+$id).attr('data-image-captions') );

    var $spins = jQuery.parseJSON( jQuery('#'+$id).attr('data-spins') );

    for(var $i in $spins) {
        var img = document.createElement('img');
        img.src = $spins[$i]+'?thumbnail='+jQuery('#'+$id).attr('data-thumbnails-height')+'&image=true&scale.option=noup';
        img.setAttribute('data-item-id',$i);
        var thumb = instance.thumbnails.addItem(img);
        thumb.setAttribute('data-item','spin');
    }
    initSirvGallerySelectors($id);
};

function initSirvGallerySelectors($id) {
    SirvOptions['captions'][$id]  = jQuery.parseJSON( jQuery('#'+$id).attr('data-captions') );
    SirvOptions['image_captions'][$id]  = jQuery.parseJSON( jQuery('#'+$id).attr('data-image-captions') );
    jQuery('#'+$id+' #sirv-thumbs-box-'+$id+' img').on('click',function(){
        if (typeof(jQuery(this).attr('data-item-id'))=='undefined') {
            jQuery(this).attr('data-item-id','sirv-zoom');
            jQuery(this).attr('data-caption-id',jQuery(this).closest('li').index());
        } 
        jQuery('#'+$id+' .sirv-gallery-item').addClass('sirv-hidden');
        jQuery('#'+$id+' .sirv-thumbs-box li').removeClass('sirv-thumb-selected');
        jQuery('#'+$id+' .sirv-gallery-item[data-item-id='+jQuery(this).attr('data-item-id')+']').removeClass('sirv-hidden');

        if (jQuery(this).attr('data-caption-id')!=null) {
            jQuery('#'+$id+' .sirv-caption.sirv-zoom-caption').html(SirvOptions['image_captions'][$id][jQuery(this).attr('data-caption-id')]);
        } else {
            jQuery('#'+$id+' .sirv-caption.sirv-zoom-caption').html(SirvOptions['captions'][$id][jQuery(this).attr('data-item-id')]);
        }

        jQuery(this).closest('li').addClass('sirv-thumb-selected');
    });
};

jQuery(document).ready(function(){
    jQuery('.sirv-gallery.no-sirv-zoom').each(function(){
        initSirvGallerySelectors(jQuery(this).attr('id'));
    });
});