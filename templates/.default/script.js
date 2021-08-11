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

        let strHtml = "<h2 style='text-align: center;'>Спасибо, Ваше обращение принято.</h2>";

        let $iType = $('select#i_TYPE').val();

        if (parseInt($iType) === 37) {
            let $iEmail = $('input#i_EMAIL').val().trim();
            strHtml += '<h3 style="text-align: center; text-decoration: underline;">На указанную эл.почту ' + $iEmail + ' отправлена инструкция по процедуре замены эл.ключа</h3>';
        }

        obFormSubmitter.$form
            .css('min-height', 640)
            .html(strHtml);
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
                if (n['name'].indexOf('[]') > 0) {

                    let _name = n['name'].replace(/\]|\[/g, '');
                    if (!(_name in arFormDataNested)) {
                        arFormDataNested[_name] = [];
                    }
                    arFormDataNested[_name].push(n['value']);
                } else {
                    arFormDataNested[n['name']] = n['value'];
                }
            });

            obForm.send(arFormDataNested);
        }
    })
});