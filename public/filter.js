

$(document).ready( () => {

    const go = (elem) => ($(elem).closest('form').submit);

    $('.form-control').blur(function (e) {
        go(e);
    });

    $('.form-control').keypress(function (e) {
        if(e.which === 13) {
            go(e);
        }
    })
});

