class FormSubmitter {
    constructor($form, arOptions) {

        this.$form = $($form);
        this.signer = this.$form.find('input[name="ajax_signed"]').val();
        this.method = 'addResult';

        this.arEvents = {
            error: 'formsubmitterError',
            success: 'formsubmitterSuccess',
            sent: 'formsubmitterSent'
        };

        if (arOptions) {
            if (arOptions.hasOwnProperty('method')) {
                this.method = arOptions.method;
            }
        }
    }

    send(arFields) {
        let $this = this;
        BX.ajax.runComponentAction('fei:back.form',
            this.method, { // Вызывается без постфикса Action
                mode: 'class',
                signedParameters: this.signer,
                data: {
                    arFields: arFields
                }
            })
            .then(function (response) {
                BX.onCustomEvent($this, $this.arEvents.sent, [response, $this]);

            })
            .catch(function (response) {
                BX.onCustomEvent($this, $this.arEvents.error, [response, $this]);
            });
    }
}