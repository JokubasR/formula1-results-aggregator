$(function() {
    $('#sidebar-wrapper').slimscroll({
        height: '100%',
        width: '500px',
        position: 'right',
        alwaysVisible: true,
        color: '#73715e'
    });

    $('.event-list>li').click(function(e){
        $('.background-image').css('background-image', "url('" + $(this).data('background') + "')");
    });
});