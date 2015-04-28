/*
* KW Option Selectors -- работа с селектами
* version 1.4
* now BuildSelector() ALWAYS required extended format (see function description)
* */
function preloadOptionsList(url, request_params, request_type) // Загружает данные (кэширование в переменную)
{
    var ret = false;
    var ajax_request_type = request_type || 'GET';
    var ajax_request_params = request_params || {}
    $.ajax({
        url: url,
        async: false,
        cache: false,
        data: request_params,
        type: ajax_request_type,
        success: function(data){
            ret = $.parseJSON(data);
        }
    });
    return ret;
}

/*
 * target            =       name нужного селекта
 * data              =       json-объект со значениями
 * default_option    =       false или объект со значениями по умолчанию (добавляется в начало списка опций!)
 *                           {
 *                              text: текст опции по умолчанию
 *                              value: возвращаемый value для опции по умолчанию
 *                           }
 * selected_value    =       false или значение (value) опции, которую мы выбираем после
 *                           загрузки списка ( 825229640 is a magic number ;-)
 *                           не помню, что оно означает, но живет с версии 1.2 )
 * Example:
 * BuildSelector('charlist', $.parseJSON(data), { text: 'Select', 'value' : 0 }, 825229640);
 * */
function BuildSelector(target_name, selector_data, default_option, value_of_selected_option )
{
    /* формирует SELECTOR/OPTIONS list с текущим элементом равным [currentid]
     data format:
     {
        state: ok, (@todo: default is 'extended'|'ok' , для простого списка - 'simple')
        error: 0,
        data:
            {
            n:  {
                    type:   group | option
                    value:  item id in reference/selector
                    text:   group title | option text
                    comment: comment (useless?)
                }
            }
     } */
    var not_a_first_option_group = 0;
    var ret = '', last_group = '';

    var curr_id = value_of_selected_option || 0;

    var _target = "select[name='" + target_name + "']";

    if (default_option) {
        var dos = (default_option['text'] == '') ? 'Выбрать!' : default_option['text'];
        var dov = (default_option['value'] == '') ? '0' : default_option['value'];
        ret = '<option value="' + dov +'" data-group="*">'+ dos +'</option>';
    }

    var data = (typeof selector_data == "string") ? $.parseJSON(selector_data) : selector_data;

    if (data['error'] == 0) {
        $.each(data['data'] , function(id, value){
            if (value['type'] == 'group') {
                // add optiongroup
                if (last_group != value['text']) {
                    last_group = value['text'];
                    if (not_a_first_option_group) ret += '</optgroup>';
                    ret += '<optgroup label="'+ value['text'] +'">';
                    not_a_first_option_group++;
                }
            }

            if (value['type'] == 'option') {
                // add option
                ret += '<option value="'+value['value']+'" data-group="'+ last_group +'">'+value['text']+'</option>';
            }
        });
        if (not_a_first_option_group > 0) {
            ret += '</optiongroup>';
        }
        $(_target).empty().append ( ret );
        if (value_of_selected_option !== false) Selector_SetOption(target_name, curr_id);
        $("select[name="+target_name+"]").prop('disabled',false);
    }
    else {
        $("select[name="+target_name+"]").prop('disabled',true);
    }
}

function BuildSelectorClear(target_name, default_option)
{
    var dos, dov, ret='';
    if (default_option) {
        dos = (default_option['text'] == '') ? 'Выбрать!' : default_option['text'];
        dov = default_option['value'] || 0;
        ret = '<option value="' + dov +'" data-group="*">'+ dos +'</option>';
    }
    var _target = "select[name='" + target_name + "']";
    $(_target).empty().append ( ret );
}

function Selector_SetOption(name, option_value)
{
    var cid = option_value || 0;
    $("select[name="+name+"] option[value="+ cid +"]").prop("selected",true);
}

/*
 target         : target form (ID attr or jquery object)
 select_name    : имя селекта
 value_for_undefined : возвращаемое значение если нет такой опции
 * */
function getSelectedOptionValue(targetform, selector_name, value_for_undefined)
{
    var t;
    var vou = value_for_undefined || 0;
    if (typeof targetform === 'string') {
        t = $("#"+targetform);
    } else if (typeof targetform === 'object') {
        t = targetform;
    } else {
        return false;
    }
    var v = t.find("select[name='"+selector_name+"'] option:selected").val();
    if (typeof v === 'undefined') {
        v = vou
    }
    return v;
}

function getSelectedOptionText(target, selector_name)
{
    var t;
    if (typeof target === 'string') {
        t = $("#"+target);
    } else if (typeof target === 'object') {
        t = target;
    } else {
        return false;
    }
    return t.find("select[name='"+selector_name+"'] option:selected").html();
}

