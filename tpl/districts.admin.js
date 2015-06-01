/* */

function ReloadContent(target, region)
{
    var s_region = region ? '&region='+region : '';
    $(target).empty().load(ajax_handler + "?action=list" + s_region);
}

function loadEditableFields() {
    var ret = {};
    $.get(ajax_handler +'?action=get-comments').done(function(data){
        var result = $.parseJSON(data);
        ret = (result['state'] != 'error') ? result['data'] : {};
    });
    return ret;
}

function ShowErrorMessage(message)
{
    alert(message);
}

/**
 * @param template_set ОБЯЗАТЕЛЕН: массив ИМЕН полей данных =
 * @param $form форма опроса ($jQuery)
 * @param request_type кто спрашивает: add | edit
 * @param id    айдишник, для UPDATE запроса (id кнопки)
 * @return array
 */
function getDataSet(template_set, $form, request_type, id )
{
    var ret = {
        id: id,
        password: $("#master_password").val()
    };
    $.each(template_set , function(key, value){
        ret [ key ] = $form.find("input[name='" + request_type + "_" + key + "']").val()
    });
    return ret;
}

/**
 * @param template_set ОБЯЗАТЕЛЕН: массив ИМЕН полей данных
 * @param dataset массив данных, которые нужно вставить в поля, перечисленные в template_set
 * @param $form форма опроса ($jQuery)
 * @param request_type кто спрашивает: add | edit
 * @param id    айдишник, для UPDATE запроса (id кнопки)
 * @return array
 */
function setDataSet(template_set, dataset, $form, request_type /* always is 'edit' */ )
{
    $.each(template_set , function(key, value){
        $form.find("input[name='" + request_type + "_" + key + "']").val(dataset[ key ]);
    });
}

/**
 * @param field_labels массив подписей к полям данных ( LoadFieldComments() )
 * @param fields_type тип полей: add | edit
 * @param target контейнер-получатель
 * @return null
 */
function initInputFields( field_labels , fields_type, target )
{
    if (typeof field_labels == 'undefined') return false;

    var content = '';
    $.each( field_labels , function(key, value) {
        // <label for="add_district_title" id="add_label_district_title">Район (строка на русском):</label>
        // <input type="text" name="add_district_title" id="add_district_title" class="text ui-widget-content ui-corner-all">
        content += '<label for="' +
            fields_type +
            '_' +
            key +
            '" id="' +
            fields_type +
            '_label_' +
            key +
            '">' +
            value +
            ':</label>';
        content += '<input type="text" name="' +
            fields_type +
            '_' +
            key +
            '" id="' +
            fields_type +
            '_' +
            key +
            '" class="text ui-widget-content ui-corner-all">';

        content += "\r\n";
    });
    $(target).append(content);
}

/* Работа с кусочками данных (CRUD) */

function LoadItem(target, id)
{
    var getting = $.get(ajax_handler + '?action=load', {
        id: id
    });
    var $form = $(target).find('form');
    getting.done(function(data){
        var result = $.parseJSON(data);
        if (result['error'] == 0) {
            setDataSet( fields_list, result['data'], $form, 'edit' );
        } else {
            // ошибка загрузки
        }
    });
}

function AddItem(source, id)
{
    var $form = $(source).find('form');
    var url = $form.attr("action");

    var dataset = getDataSet( fields_list , $form, 'add')

    var getting = $.get( ajax_handler + url, dataset );
    getting.done(function(data){
        var result = $.parseJSON(data);
        if (result['error']==0) {
            ReloadContent("#ref_list");
            $( source ).dialog( "close" );
        } else {
            ShowErrorMessage(result['message']);
            $( source ).dialog( "close" );
        }
    });
}

function UpdateItem(source, id)
{
    var $form = $(source).find('form');
    var url = $form.attr("action");

    var dataset = getDataSet( fields_list , $form, 'edit', id);
    var getting = $.get( ajax_handler + url, dataset );

    getting.done(function(data){
        var result = $.parseJSON(data);
        if (result['error']==0) {
            ReloadContent("#ref_list");
            $( source ).dialog( "close" );
        } else {
            ShowErrorMessage(result['message']);
            $( source ).dialog( "close" );
        }
    });
}

function RemoveItem(target, id)
{
    var getting = $.get(ajax_handler + '?action=remove', {
        id: id,
        password: $("#master_password").val()
    });
    getting.done(function(data){
        result = $.parseJSON(data);
        if (result['error'] == 0) {
            ReloadContent("#ref_list");
            $( target ).dialog( "close" );
        } else {
            ShowErrorMessage(result['message']);
            $( target ).dialog( "close" );
        }
    });
}

