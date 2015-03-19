if (!String.prototype.format) {
    String.prototype.format = function () {
        var args = arguments;
        return this.replace(/{(\d+)}/g, function (match, number) {
            return typeof args[number] != 'undefined'
                ? args[number]
                : match
                ;
        });
    };
}

toggle_button_loading_text = function($element){
    var btnText = $element.data('loading-text');
    $element
        .data('loading-text', $element.text())
        .text(btnText)
    ;
};

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
            var $target = $(e.target);
            var $img = $target.parents('.row:first').find('img');

            $img.attr('src', $target.children('option:selected').data('photo'));
        });

    $('.event-list>li').click(function(e){
        var $target = $(this);

        $('.background-image').css('background-image', "url('" + $target.data('background') + "')");
        $('#race-title').html($target.data('title'));
        $('input[name="stage"]').val($target.data('slug'));
    });

    $('*[data-event="ajaxRequest"]').click(function(e){
        e.preventDefault();

        var $target = $(e.target);
        var $container = $($target.data('container'));
        var $resultContainer = $($target.data('result-container'));
        var $form = $($target).parents('form:first');

        $.ajax({
            async: true,
            type: $form.attr('method'),
            url: decodeURI($form.attr('action')).format($('input[name="stage"]').val()),
            data: $form.serialize(),
            beforeSend: function(){
                toggle_button_loading_text($target);
            },
            success: function(data){
                if (data.status === "ok") {
                    $container.show();
                    $resultContainer.html(data.view);

                    toggle_button_loading_text($target);
                }
            }
        });
    });
});