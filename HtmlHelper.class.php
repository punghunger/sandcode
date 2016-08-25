<?php

/**
 * html标签输出助手
 * @date   2015-10-10
 * @author Li Zongheng
 */
class htmlHelper {

    public static $noEndtag = array(
        'area' => 1,
        'base' => 1,
        'br' => 1,
        'col' => 1,
        'command' => 1,
        'embed' => 1,
        'hr' => 1,
        'img' => 1,
        'input' => 1,
        'keygen' => 1,
        'link' => 1,
        'meta' => 1,
        'param' => 1,
        'source' => 1,
        'track' => 1,
        'wbr' => 1,
    );

    function __construct() {
        
    }

    /**
     * chcekbox多选框html
     * @param string $name      属性name值
     * @param mixed $checked    选中值，可为数组或者以,隔开的字符串
     * @param array $items      选项列表
     * @param array $attributes 标签属性
     * @return string
     */
    static function checkboxList($name, $checked = null, $items = array(), $attributes = array()) {
        $attributes['type'] = 'checkbox';
        if (!in_array($checked)) {
            $checked = explode(',', (string) $checked);
        }
        return self::boxList($name, $checked, $items, $attributes);
    }

    /**
     * radio单选框html
     * @param string $name      属性name值
     * @param mixed $checked    选中值，string|int|bool
     * @param array $items      选项列表
     * @param array $attributes 标签属性
     * @return string
     */
    static function radioList($name, $checked = null, $items = array(), $attributes = array()) {
        $attributes['type'] = 'radio';
        return self::boxList($name, $checked, $items, $attributes);
    }

    private static function boxList($name, $checked = null, $items = array(), $attributes = array()) {
        $html = '';
        $attributes['name'] = $name;
        $id = isset($attributes['id']) ? $attributes['id'] : $name;
        $type = $attributes['type'];
        $index = 1;
        foreach ($items as $key => $value) {
            $id_ = $id . '_' . $index;
            $attributes['id'] = $id_;
            $attributes['value'] = $key;
            // 选中值是否一致
            self::setBoxChecked($type, $key, $checked, $attributes);
            $label = self::tag('label', $value, array('for' => $id_));
            $html .= self::tag('input', null, $attributes) . $label;
            $index++;
        }
        return $html;
    }

    /**
     * 设置选中属性
     * @param string $type      box类型
     * @param mixed $value      值
     * @param mixed $checked    选中值
     * @param array $attributes 属性
     */
    private static function setBoxChecked($type, $value, $checked, &$attributes) {
        if (
                ($type == 'radio' && $checked === $value) ||
                ($type == 'checkbox' && in_array($value, $checked))
        ) {
            $attributes['checked'] = 'checked';
        } else {
            unset($attributes['checked']);
        }
    }

    /**
     * 根据指定的键名，重组数组
     * @param array $list   数组
     * @param string $kk      对应键的键名
     * @param string $vk      对应值的键名
     * @return array
     */
    static function listData($list, $kk, $vk) {
        $data = array();
        foreach ($list as $value) {
            $k = $value[$kk];
            $data[$k] = $value[$vk];
        }
        return $data;
    }

    /**
     * 下拉框html
     * @param type $name
     * @param type $selected
     * @param type $items
     * @param array $attributes
     * @return type
     */
    static function dropDownList($name, $selected, $items = array(), $attributes = array()) {
        $attributes['name'] = $name;
        $option = '';
        foreach ($items as $key => $value) {
            $checked = $selected === $key ? ' selected="selected"' : '';
            $option .= "<option value=\"{$key}\"{$checked}>{$value}</option>";
        }
        return self::tag('select', $option, $attributes);
    }

    static function input($type, $name) {
        
    }

    /**
     * 设置对应的html标签
     * @param type $name
     * @param type $content
     * @param type $attributes
     * @return type
     */
    static function tag($name, $content = '', $attributes = array()) {
        $html = "<{$name} " . self::joinAttributes($attributes) . '>';
        return isset(self::$noEndtag[strtolower($name)]) ? $html : "{$html}{$content}</{$name}>";
    }

    /**
     * 拼接属性
     * @param type $attrs
     * @return type
     */
    private static function joinAttributes($attrs) {
        $attr = $space = '';
        foreach ($attrs as $key => $value) {
            $attr .= "{$space}{$key}=\"{$value}\"";
            $space = ' ';
        }
        return $attr;
    }

}

?>
