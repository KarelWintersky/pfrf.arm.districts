<?php
/*
KarelWintersky's Template simple engine ver 1.5
*/

/**
 * Попытка сделать возможность парсить вложенные override-переменные, например
 * в override-массив попадает конструкция array
 * ('val1' => 'data1', 'val2' => array( 'val3' => 'n') ) )
 * мы хотим такую конструкцию корректно распарсить так, чтобы можно было
 * подставить n по соотв ключу, например 'val2/val3' as 'n' или как-то похоже.
 * Внимание, вопрос - как лучше строить такой ключ?
 * @param $array
 * @param string $prefix
 * @param string $suffix
 * @return array|null
 */
function flatten($array, $prefix = '', $suffix = '/')
{
    $result = array();
    if (!is_array($array)) return null; // exit if array is empty
    foreach ($array as $key => $value)
    {
        if (is_array($value))
            $result = array_merge($result, flatten($value, $prefix . $key . $suffix, $suffix));
        else
            $result[$prefix . $key] = $value;
    }
    return $result;
}


/**
 *
 */
class kwt
{
    private $file;
    private $tag_open = '{%';
    private $tag_close = '%}';
    private $overrides = array();
    private $content;

    /**
     * Инклюдит файл с шаблоном
     * @param $filename
     * @return null|string
     */
    private function get_include_contents($filename)
    {
        if ( is_file($filename ) ) {
            ob_start();
            include $filename;
            return ob_get_clean();
        }
        return null;
    }

    /**
     * @param $array
     * @param $key
     * @return string
     */
    private function buildKey($array, $key)
    {
        $value = $array [ $key ];
        $rkey = '';
        if (!is_array($value) ) {
            $rkey = $key;
        } else {
            foreach ( $value as $kkey => $kvalue ) {
                $rkey .= $key.'/'.$this->buildKey( $kvalue, $kkey );
            }
        }
        return $rkey;
    }

    //
    /**
     * Функция-обработчик. Заменяет переменные в файле согласно массиву overrides
     * @param $buffer
     * @return mixed
     */
    private function kwt_callback(&$buffer)
    {
        $buf = $buffer;
        foreach ($this->overrides as $key => $value)
        {
            $skey = $this->tag_open.$key.$this->tag_close;
            $buf = str_replace($skey, $value, $buf);
        }
        return $buf;
    }

    /**
     * Создает экземпляр класса kwt.
     * @param $file         -- путь к файлу шаблона
     * @param string $open  -- открывающий тег, по умолчанию {%
     * @param string $close -- закрывающий тег, по умолчанию %}
     */
    public function __construct($file, $open = '{%', $close = '%}' /*, $default_template = '' */)
    {
        $file_with_path = dirname($_SERVER['SCRIPT_FILENAME']).'/';
        $file_with_path .= substr($file, 1, 1) == '/'
            ? substr($file, 1)
            : $file;
        // $this->file = dirname($_SERVER['SCRIPT_FILENAME']).'/'.$file;
        $this->file = $file_with_path;
        $this->tag_open = $open;
        $this->tag_close = $close;
        /*
        Если мы выносим этот блок в другую функцию, тут надо будет оставить проверку на
        существование файла и вставку дефолтного темплейта. Впрочем эта проблема решается просто
        возвратом null (по крайней мере пока).
        */
        $this->content = $this->get_include_contents($this->file);
    }

    /**
     * Cоздает (или дополняет) массив замещаемых переменных в шаблоне.
     * @param $arr
     */
/*     public function override($arr)
    {
        if (!empty($arr)) {
            foreach ($arr as $ki => $kv) {
                if (!array_key_exists(strtolower($ki), $this->overrides)) $this->overrides[strtolower($ki)] = $kv;
            }
        } else {
            $this->overrides = array_merge($this->overrides,$arr);
        }
    }
*/
    public function override($arr)
    {
        if (!empty($arr)) {
            foreach ($arr as $ki => $kv) {
                if (!array_key_exists(strtolower($ki), $this->overrides))
                    $this->overrides[strtolower($ki)] = $kv;
            }
        } else {
            $this->overrides = array_merge($this->overrides, $arr);
        }
        /* вообще-то надо обрабатывать массив вложенных ключей тут */
        $this->overrides = flatten( $this->overrides );
    }

    /**
     * Возвращает обработанный шаблон в переменную (для использования в шаблонах верхнего уровня)
     * @return mixed -- html-код шаблона с замененными переменными
     */
    public function get()
    {
        /* Интересно, если перенести сюда
        $this->content = $this->get_include_contents($this->file);
        будет ли работать? А в конструкторе возвращать true если файл с шаблоном найден
        Это позволит задать в конфиге файл с шаблоном по умолчанию на случай, если
        нужный файл не найден.
        */
        $return = $this->kwt_callback($this->content);
        return $return;
    }

    /**
     * выводит шаблон в буфер вывода, то есть в stdout (эквивалент функции flush() )
     */
    public function out()
    {
        print $this->kwt_callback($this->content);
    }

    /**
     * Переопределяет параметры экранирования заменяемых переменных, принимает строки
     * @param $start    -- открывающий тег
     * @param $end      -- закрывающий тег
     */
    public function config($start, $end)
    {
        $this->tag_open = $start;
        $this->tag_close = $end;
    }

    /**
     *
     * проверяет значение ключа в массиве kwt::overrides[] и возвращает его значение
     * Заготовка-костыль нормального парсера условных операций в шаблоне, смотри конец файла
     * Пример применения, в шаблоне:
     * <?php if ( $this->key('estaff_honorary_editor') != '') { ?>
    <h2>Почесний редактор</h2>
    <ul class="no-marker">
    {%estaff_honorary_editor%}
    </ul>
    <?php } ?>
     */
    public function key($key)
    {
        return isset($key) ? $this->overrides[ $key ] : null;
    }

    /* ====================================================================== */
    /* функции-обертки (deprecated) */

    /**
     * вывод в stdout
     */
    public function flush()
    {
        $this->out();
    }

    /**
     * вывод в переменную
     * @return mixed
     */
    public function getcontent()
    {
        return $this->get();
    }


}