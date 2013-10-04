// JavaScript Document

$.widget('ui.form', {

    _init: function(){
        var self = this;
        var form = this.element;
        var inputs = form.find('input, select, textarea');

        $.each(inputs, function(){
            $(this).addClass('ui-widget ui-state-default ui-corner-all');


            if($(this).is(':checkbox')) {

                self.checkboxes(this);

            } else if($(this).is('input[type="text"]')||$(this).is("textarea")||$(this).is('input[type="password"]')) {

                self.textelements(this);

            } else if($(this).is(':radio')) {

                self.radios(this);

            }

            if ($(this).is(':disabled')) {
                $(this).addClass('fieldDisabled');
            }

            if($(this).hasClass('date'))
            {
                $(this).datepicker();
            }
        });
    },

    textelements: function(element){

        $(element).on('focusin', function() {

            $(this).addClass('ui-state-focus');

         }).on('focusout', function() {

             $(this).removeClass('ui-state-focus');

         });
    },

    checkboxes: function(element) {
        var parent = $('<span />');
        var $input = $(element);

        $input.after(parent);
        $input.addClass('ui-helper-hidden');
        parent.css({width:15, height:15, display:'block'});
        parent.wrap('<span class="ui-state-default ui-corner-all selectable" />');

        if ($input.is(':checked')) {
            parent.parent('span').addClass('ui-state-active');
            parent.addClass('ui-icon ui-icon-check');
        }

        parent.parent().on('click', function(event, manual){

            parent.toggleClass('ui-icon ui-icon-check');

            if (parent.hasClass('ui-icon')) {

                $input.prop('checked', true);

            } else {

                $input.prop('checked', false);

            }

            if (manual) {
                return;
            }

            $input.trigger('change');
        });


        $input.on('toggleValue',function() {
            parent.parent().trigger('click', true);
        });

        $input.on('forceValue',function(e, targetValue) {
            if (targetValue == null) {
                return;
            }

            if (targetValue != $(this).prop('checked')) {
                parent.parent().trigger('click', true);
            }
        });
    },

    radios: function(element){
        var parent = $('<span />');
        var $input = $(element);

        $input.after(parent);
        $input.addClass('ui-helper-hidden');
        parent.css({width:15, height:15, display:'block'});
        parent.wrap('<span rel="' + $input.attr('id') + '" class="ui-state-default ui-corner-all ui-state-active selectable" style="float: left" />');

        if ($input.is(':checked')) {
            parent.addClass('ui-icon ui-icon-bullet');
        }

        parent.parent().on('click', function(event){
            parent.parents('ul').find('.ui-icon-bullet').removeClass('ui-icon ui-icon-check');

            parent.addClass('ui-icon ui-icon-bullet');
            $input.prop('checked', true);

            $input.trigger('change');
        });
    }
});