jQuery(document).ready(function($) {
    //has doesn't seem to work properly
    $(".folder-has-children > .wpcl-control-container").html('<a class="wpcl-control" href="#"><i class="fa fa-plus"></i><i class="fa fa-minus"></i></a>');
    $(".folder-has-children .wpcl-checkbox:checked").parent().parents(".folder-has-children").addClass("opened");
    $("a.wpcl-control").on('click', function(e){
        var folder = $(this).parent().parent();
        if ( folder.hasClass('opened') ) {
            folder.removeClass('opened');
        }
        else {
            folder.addClass('opened');   
        }
        e.preventDefault();
   }); 
});