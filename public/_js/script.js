
/*
 * Copyright by Mariusz Połchowski.
 * All rights reserved.
 * 
 * Contact via e-mail:
 *   mariusz.polchowski@gmail.com
 */

function confirmPassword(value, constraints) {
    var isValid = false;

    if (constraints && constraints.other)  {
        var otherInput = dijit.byId(constraints.other);
        if (otherInput) {
            var otherValue = otherInput.value;
            isValid = (value == otherValue);
        }
    }

    return isValid;
}

 function resizeCheck(nodeId) {
    dojo.require('dojo.window');

    var position = 'fixed';
    if (dojo.window && dojo.window.getBox().w < dojo.style(nodeId, 'minWidth')) {
        position = 'absolute';
    }
    dojo.style(nodeId, 'position', position);
}

function resizeObject(obj, height, width) {
    if (height) {
        height = dojo.window.getBox().h - height + 2;
        if (height > 0) {
            ojo.style(obj.domNode, 'height', height + 'px');
        }
    }
    
    if (width) {
        width = dojo.window.getBox().w - width + 2;
        if (width > 0) {
            dojo.style(obj.domNode, 'width', width + 'px');
        }
    }
}
    
function resizeGrid(obj, height, width) {
    height = dojo.window.getBox().h - height + 2;
    width = dojo.window.getBox().w - width + 2;

    if (height > 0 && width > 0) {
        dojo.style(obj.domNode, 'height', height + 'px');
        dojo.style(obj.domNode, 'width', width + 'px');
        
        if (typeof obj.pagination != 'undefined') {
            obj.pagination.plugin.gh = height + 2;
        }
    }

    obj.resize();
    obj.update();
}

dojo.ready(function() {
    dojo.fadeOut({node: 'page_loader', duration: 500, onEnd: function() {
        dojo.style('page_loader', 'display', 'none');
    }}).play();
});

function format_money(value) {
    return number_format(value, 2, ',', ' ');
}

function number_format(number, decimals, dec_point, thousands_sep) {
    number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
    var n = !isFinite(+number) ? 0 : +number,
    prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
    sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
    dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
    s = '',
    toFixedFix = function (n, prec) {
        var k = Math.pow(10, prec);
        return '' + Math.round(n * k) / k;
    };
    // Fix for IE parseFloat(0.55).toFixed(0) = 0;
    s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
    if (s[0].length > 3) {
        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
    }
    if ((s[1] || '').length < prec) {
        s[1] = s[1] || '';
        s[1] += new Array(prec - s[1].length + 1).join('0');
    }
    return s.join(dec);
}
    
function parseDate(value) {
    var year = value.substr(0, 2);
    var month = parseInt(value.substr(2, 2));

    if (month > 80) {
        year = '18' + year;
        month -= 80;
    } else if (month > 60) {
        year = '22' + year;
        month -=600;
    } else if (month > 40) {
        year = '21' + year;
        month -= 40;
    } else if (month > 20) {
        year = '20' + year;
        month -= 20;
    } else {
        year = '19' + year;
    }

    return new Date(year, month - 1, value.substr(4, 2));
}

function validateForm(form, text) {
    if (form.validate()) {
        return true;
    } else {
        alert(text);
    }
    
    return false;
}

function parseURIparams(uri, params, zend) {
    for (var key in params) {
        uri += (zend ? '/' : (uri.indexOf('?') > 0 ? '&' : '?')) + key + (zend ? '/' : '=') + encodeURIComponent(params[key]);
    }
    
    return uri;
}