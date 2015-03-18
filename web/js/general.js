$(function() {
    $('#sidebar-wrapper').slimscroll({
        height: '100%',
        width: '500px',
        position: 'right',
        alwaysVisible: true,
        color: '#73715e'
    });

    $('[data-toggle="tooltip"]').tooltip();

    $('select')
        .select2({
            placeholder: "Select a state",
            allowClear: true,
            width: '170px'
        })
        .change(function(e){
            $target = $(e.target);
            $img = $target.parents('.row:first').find('img');

            $img.attr('src', $target.children('option:selected').data('photo'));
        });

    $('.event-list>li').click(function(e){
        $('.background-image').css('background-image', "url('" + $(this).data('background') + "')");
        $('#race-title').html($(this).data('title'));
    });
});