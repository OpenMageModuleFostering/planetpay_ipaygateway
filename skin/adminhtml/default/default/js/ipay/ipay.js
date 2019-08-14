/**
 * IPAY Admin JS.
 */
ipay = {};
ipay.Payment = Class.create({
    initialize: function (container, dropdown, radio, url) {
        this.container = $(container);
        if (!this.container) {
            return;
        }
        this.dropdown = $(dropdown);
        this.dropdown.observe('change', this.toggleForm.bind(this));
        var obj = this;
        this.url = url;
        $$(".ipay-payment").each(function(e){
            Event.observe(e, 'change', obj.checkPaymentMethod.bind(obj))
        });
        this.radio = $(radio);
        this.radio.observe('click', this.toggleForm.bind(this));
        this.form = this.container.down('ul');
        /**
		 * Kind of ghetto, but wait a bit until AdminOrder.setPaymentMethod() fires because that enables
		 * all the payment form fields.
		 */
        setTimeout(function () { 
            this.toggleForm();
        }.bind(this), 500);
    },
	
    toggleForm: function (evt) {
        if (this.dropdown.value) {
            this.form.hide();
            Form.disable(this.form.identify());
        //console.log('disabled');
        }
        else {
            this.form.show();
            Form.enable(this.form.identify());
        //console.log('enabled');
        }
    }, 
        
    checkPaymentMethod: function (evt) {
        $("loading-mask").show();
        new Ajax.Request(this.url, {
            parameters: $('edit_form').serialize(true),
            method:'post',
            onSuccess: function(transport) {
                var response = transport.responseText.evalJSON();
                if(response.update_section) {
                    if(response.error){
                        if(response.message){
                            Element.update($(response.update_section.name), "<font color='red'>"+response.message+"</font>");
                        }
                    } else {
                        Element.update($(response.update_section.name), response.update_section.html);
                    }
                }
            }.bind(this)
        });
    }
});
