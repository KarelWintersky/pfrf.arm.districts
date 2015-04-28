/**
 * Аналог функции strpos()
 * @param haystack  - стог сена
 * @param needle    - иголка
 * @param offset    - смещение начала поиска
 * @return {Boolean}- -1 ИЛИ позицию
 */
function strpos (haystack, needle, offset) {
    var i = (haystack+'').indexOf(needle, (offset || 0));
    return i === -1 ? false : i;
}

/**
 * strpos() через прототип
 * @param needle    - иголка
 * @param offset    - смещение начала поиска
 */
String.prototype.strpos = String.prototype.strpos || function(needle, offset) {
    var i = (this+'').indexOf(needle, (offset || 0));
    return i === -1 ? false : i;
};

/* прототипные функции trim */
String.prototype.trim = String.prototype.trim || function(){return this.replace(/^\s+|\s+$/g, ''); };
String.prototype.ltrim = String.prototype.ltrim || function(){return this.replace(/^\s+/,''); };
String.prototype.rtrim = String.prototype.rtrim || function(){return this.replace(/\s+$/,''); };
String.prototype.fulltrim = String.prototype.fulltrim || function(){return this.replace(/(?:(?:^|\n)\s+|\s+(?:$|\n))/g,'').replace(/\s+/g,' ');};

/**
 * Разделить строку по параметрам ( © http://a2x.ru/?p=140 )
 * возвращает массив вида 'valuename' => 'valuedata'
 * @param queryString
 * @param limiter
 * @return {Array}
 */
function getQuery( queryString , limiter)
{
    var vars = queryString.split((limiter || '&')); //делим строку по & - parama1=1
    var arr = [];
    for (var i=0 , vl = vars.length; i < vl; i++)
    {
        var pair = vars[i].split("="); //делим параметр со значением по =, и пишем в ассоциативный массив arr['param1'] = 1
        arr[pair[0]] = pair[1];
    }
    return arr;
}

/**
 * установка URL-хэша на основе нескольких селектов (выпадающих списков), имеющих
 * определенный class (по умолчанию ".search_selector") и name (которое превращается
 * в элемент hash-строки: для <select name="letter" class="search_selector">
 * соответствующая запись в URL будет: /...#letter={select_value}..[&something=value]
 *
 * @param search_selector
 */
function setHashBySelectors(search_selector)
{
    var $selector = $(search_selector) || $(".search_selector");
    var hashstr = '';
    var val, name, $data;
    $.each( $selector , function(id, data) {
        $data = $(data);
        val = $data.val();
        name = $data.attr('name');
        if (val != '0')
            hashstr += name + "=" + val + "&";
    } );
    if (hashstr.length > 2) {
        window.location.hash = hashstr.substring(0, hashstr.length-1)
    } else {
        if ('pushState' in history) {
            history.pushState('', window.title, window.location.pathname + window.location.search);
        } else {
            window.location.hash = '';
        }
    }
}

/**
 * устанавливает селекторы с указанным классом на основе URL-хэша
 * @param target
 */
function setSelectorsByHash(target)
{
    var sel_name;
    var sel_value;
    var hashes_arr = getQuery((window.location.hash).substr(1));

    $.each( $(target), function(id, data) {
        sel_name = $(data).attr('name'); // selector's name attribute
        sel_value = hashes_arr[sel_name] != 'undefined' ? hashes_arr[sel_name] : 0;
        $(target+"[name="+sel_name+"] option[value="+sel_value+"]").prop("selected",true);
    } );
}

/**
 * Получает значение куки с указанным именем
 * @param name
 * @return {*}
 */
function getCookie(name){
    var pattern = new RegExp(name + "=.[^;]*");
    var matched = document.cookie.match(pattern);
    if(matched){
        var cookie = matched[0].split('=');
        return cookie[1]
    }
    return false
}

/**
 * Устанавливает куку
 * @param name
 * @param value
 * @param expires
 * @param path
 * @param domain
 * @param secure
 */
function setCookie (name, value, expires, path, domain, secure) {
    document.cookie = name + "=" + escape(value) +
        ((expires) ? "; expires=" + expires : "") +
        ((path) ? "; path=" + path : "") +
        ((domain) ? "; domain=" + domain : "") +
        ((secure) ? "; secure" : "");
}

/**
 * Unslash string
 * © http://proger.i-forge.net/Add_slashes-strip_slashes_in_JavaScript/ekj
 * @param str
 * @return {String}
 */
function unslash(str) {
    var regexp = /\\(([abfnrtv])|o?([0-7]{1,3})|x([\da-fA-F]{1,2})|.)/g;
    var symbols = {a: '\7', b: '\10', f: '\14', n: '\n', r: '\r', t: '\t', v: '\13'};
    var ret = '';
    if (typeof str != 'undefined') {
        ret = str.replace(regexp, function (full, asis, seq, oct, hex) {
            if (seq) {
                return symbols[seq] || seq;
            } else if (oct || hex) {
                return String.fromCharCode(parseInt(oct, oct ? 8 : 18));
            } else {
                return asis;
            }
        });
    }
    return ret;
}

/**
 * Форматирует параметр типа Date() в формат d.m.Y (29.12.2014)
 * @param date
 * @return {String}
 */
function dateToDMY(date)
{
    var d = date.getDate();
    var m = date.getMonth() + 1;
    var y = date.getFullYear();
    return '' + (d <= 9 ? '0' + d : d) + '.' + (m<=9 ? '0' + m : m) + '.' + y;
}

/**
 * Возвращает сегодняшнюю дату в формате d.m.Y
 * @return {String}
 */
function getCurrentDate()
{
    var _date = new Date();
    return dateToDMY(_date);
    // var d = _date.getDay(), m = _date.getMonth()+ 1, y = _date.getFullYear();
    // return '' + y + '-' + (m<=9 ? '0' + m : m) + '-' + (d <= 9 ? '0' + d : d);
    // return (_date_day + '.' + _date_month + '.' + _date_year);
}

function clearHash()
{
    if ('pushState' in history) { window.
        history.pushState('', window.title, window.location.pathname + window.location.search);
    } else {
        window.location.hash = '';
    }
}

function reloadDocumentWithAjax()
{
    $.ajax({
        url: "",
        context: document.body,
        success: function(s,x){
            $(this).html(s);
        }});
}