$(function () {
    let obForm = new FormSubmitter('#backform');

    BX.addCustomEvent(obForm, obForm.arEvents.error, function (res, obFormSubmitter) {
        obFormSubmitter.$form.find('button[type="submit"]')
            .removeClass('sending')
            .attr('disabled', false);

        window['_backform_sending'] = false;
        alert('Ошибка отправки формы, попробуйте еще раз');
    });

    BX.addCustomEvent(obForm, obForm.arEvents.sent, function (res, obFormSubmitter) {
        obFormSubmitter.$form
            .css('min-height', 640)
            .html("<h2>Спасибо, Ваше обращение принято.</h2>");
    });

    $(obForm.$form).on('submit', function (e) {

        e.preventDefault();
        e.stopPropagation();

        let $form = $(this);

        $form.find('button[type="submit"]')
            .addClass('sending')
            .attr('disabled', true);

        if (!window['_backform_sending']) {

            window['_backform_sending'] = true;

            let arFormData = $form.serializeArray(),
                arFormDataNested = {};

            $.map(arFormData, function (n, i) {
                arFormDataNested[n['name']] = n['value'];
            });

            obForm.send(arFormDataNested);
        }
    })
});