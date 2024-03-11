jQuery(function($) {
    $(document.body).on('click', 'input.plus, input.minus', function() {
        var $qty = $(this).closest('.quantity').find('.qty'),
            currentVal = parseFloat($qty.val()),
            max = parseFloat($qty.attr('max')),
            min = parseFloat($qty.attr('min')),
            step = $qty.attr('step');
        // Asegúrate de que este número tenga decimales si el paso tiene decimales
        if (String(step).indexOf('.') > 0) {
            var stepDecimals = step.slice(step.indexOf('.') + 1).length;
            currentVal = currentVal.toFixed(stepDecimals);
        }
        // Comprueba si es el botón + o -
        if ($(this).is('.plus')) {
            if (max && (max == currentVal || currentVal > max)) {
                $qty.val(max);
            } else {
                $qty.val(currentVal + parseFloat(step));
            }
        } else {
            if (min && (min == currentVal || currentVal < min)) {
                $qty.val(min);
            } else if (currentVal > 0) {
                $qty.val(currentVal - parseFloat(step));
            }
        }
        // Dispara el evento change en el campo de cantidad
        $qty.trigger('change');
    });
});
