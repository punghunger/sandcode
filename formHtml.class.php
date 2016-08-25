<?php

class FormHtml {

    protected $elements = array();
    protected $hiddenElements = array();
    protected $curElementName = null;
    protected $curElementValue = null;
    public $formButton = array();
    public $formAttr = array();
    public $formData = array();
    public $openValid = false;
    public $textModel = false;

    function __construct($elements, $hiddenElements) {
        $elements && $this->elements = $elements;
        $hiddenElements && $this->hiddenElements = $hiddenElements;
    }

    function setHiddenElement() {
        $html = '';
        foreach ($this->hiddenElements as $key => $value) {
            $html .= "<input type=\"hidden\" name=\"{$key}\" value=\"{$value}\" />";
        }
        return $html;
    }

    // 设置表单元素
    function setElement() {
        $html = '';
        foreach ($this->elements as $element) {
            $this->curElementName = $element['attr']['name'];
            $input = $this->parseElement($element);
            $starHtml = $this->setStarHtml($element['validator']);
            // 额外html
            $extraHtml = $element['extra_html'] ? $element['extra_html'] : '';
            // tr属性
            $attr = $this->joinAttr($element['tr_attr']);
            $html .= <<<HTML
            <tr {$attr}>
                <td class="title">{$starHtml}{$element['title']}</td>
                <td class="content">{$input}{$extraHtml}</td>
                <td class="remark"></td>
            </tr>
HTML;
        }
        return $html;
    }

    /**
     * array('类型','标题','input名称','对值设置','attr','options','validator','extra_html')
     */
    function setElementByData() {
        if ($this->elements || !$this->formData) {
            return false;
        }
        $list_data = $this->formData['data_info'];
        foreach ($this->formData['libs'] as $key => $lab) {
            list($type, $title, $name, $parse_value, $attr, $options, $validator, $extra_html) = $lab;
            // 名称
            $name = $name ? $name : 'add_data';
            $attr = $attr ? $attr : array();
            $attr['name'] = "{$name}[{$key}]";
            // 表单元素
            $element = array(
                'title' => $title,
                'type' => $type,
                'attr' => $attr,
                'validator' => (array) $validator,
                'extra_html' => (string) $extra_html,
            );
            if ($type == 'text') {
                $element['attr']['value'] = $list_data[$key];
                $element['attr']['class'] = 'inputText';
            } else if ($type == 'textarea') {
                $element['value'] = $list_data[$key];
            } else if ($type == 'html') {
                // 文字显示不提示
                $content = $this->parseData($list_data, $key, $lab, $i);
                $element['html'] = $content;
                // 文本查看模式
                if ($this->textModel) {
                    $element['validator'] = array('ignore' => 'ignore');
                }
            }
            // 数据集选项
            if ($options) {
                $value = $list_data[$key];
                if ($parse_value && function_exists($parse_value)) {    // 方法
                    $content = call_user_func_array($parse_value, array($value));
                }
                $options['value'] = $value;
                $element['options'] = $options;
            }
            $this->elements[] = $element;
        }
        return true;
    }

    // 设置显示内容
    function parseData($data, $key, $lab, $index = 0) {
        $content = '';
        $temp_type = $lab[3];
        if ($temp_type == '') {
            $content = $data[$key];
            if ($content) {
                if (strpos($key, 'time') !== false) {
                    $content = date_("Y-m-d H:i", $content);
                } else if (strpos($key, 'show_image') !== false) {
                    $content = "<a target='_blank' href='/{$content}'><img class='show_image' src='/{$content}' /></a>";
                }
            }
        } else if (is_array($temp_type)) {      // 数组
            $content = $temp_type[$data[$key]];
        } else if (function_exists($temp_type)) {    // 方法
            $content = call_user_func_array($temp_type, array($data));
        }
        is_null($content) && $content = '-';
        return $content;
    }

    // 设置必填星号显示
    function setStarHtml($validator) {
        $starHtml = '';
        if ($this->openValid && $validator['ignore'] != 'ignore') {
            $starHtml = '<span class="need_star">*</span>';
        }
        return $starHtml;
    }

    // 设置验证属性
    function setValidator($title, $validator) {
        $attr = '';
        if ($this->openValid) {
            $validator || $validator = array('datatype' => '*', 'nullmsg' => "{$title}不为空!");
            if ($validator['ignore'] != 'ignore') {
                $attr = ' ' . $this->joinAttr($validator);
            }
        }
        return $attr;
    }

    function setElementValue($element) {
        
    }

    // 解析内容，设置显示相应的表单元素
    function parseElement($element) {
        $html = '';
        $attr = $this->joinAttr($element['attr']);
        // 表单验证属性
        $attr .= $this->setValidator($element['title'], $element['validator']);
        if ($element['type'] == 'text') {
            $html = "<input type=\"text\" _ATTR_ />";
        } else if ($element['type'] == 'textarea') {
            $html = "<textarea _ATTR_>{$element['value']}</textarea>";
        } else if ($element['type'] == 'select') {
            $html = $this->setSelectHtml($element['options']);
        } else if ($element['type'] == 'file') {
            $html = "<input type=\"file\" _ATTR_ />";
        } else if ($element['type'] == 'swfupload') {
            $html = $this->setSwfuploadHtml($element);
        } else if ($element['type'] == 'checkbox') {
            $html = $this->setCheckBoxHtml($element['options']);
        } else if ($element['type'] == 'radio') {
            $html = $this->setRadioHtml($element['options']);
        } else if ($element['type'] == 'html') {
            $html = $element['html'];
        }
        $r_search = array('_ATTR_');
        $r_replace = array($attr);
        // 替换对应数据
        $html = str_replace($r_search, $r_replace, $html);
        return $html;
    }

    // 多选按钮
    function setCheckBoxHtml($option) {
        $selected = $option['value'];
        // 获取选中值
        if (!in_array($selected)) {
            $selected = $selected ? explode(',', (string) $selected) : array();
        }
        $option['value'] = $selected;
        $html = $this->parseArrayData($option, 'checkbox');
        return $html;
    }

    // 单选按钮
    function setRadioHtml($option) {
        $html = $this->parseArrayData($option, 'radio');
        return $html;
    }

    // 下拉框
    function setSelectHtml($option) {
        $html = "<select _ATTR_>";
        $html .= "<option value=''>请选择</option>";
        $html .= $this->parseArrayData($option, 'select');
        $html .= '</select>';
        return $html;
    }

    // 解析数组的值
    function parseArrayData($option, $type) {
        $html = '';
        $kk = $option['kk'];
        $vk = $option['vk'];
        // 选中值
        $checked_value = $option['value'] !== null ? $option['value'] : '-520';
        foreach ($option['list'] as $key => $value) {
            $kv = $kk && isset($value[$kk]) ? $value[$kk] : $key;
            $vv = $vk && isset($value[$vk]) ? $value[$vk] : $value;
            if ($type == 'select') {
                $checked = $checked_value == $kv ? 'selected="selected"' : '';
                $html .= "<option value='{$kv}' {$checked}>{$vv}</option>";
            } else if ($type == 'radio') {
                $checked = $checked_value == $kv ? 'checked="checked"' : '';
                $html .= "<label><input type=\"radio\" _ATTR_ {$checked} data-text=\"{$vv}\" value=\"{$kv}\">{$vv}</label>";
            } else if ($type == 'checkbox') {
                $checked = in_array($kv, $checked_value) ? 'checked="checked"' : '';
                $html .= "<label><input type=\"checkbox\" _ATTR_ {$checked} data-text=\"{$vv}\" value=\"{$kv}\">{$vv}</label>";
            }
        }
        return $html;
    }

    // 上传文件
    function setSwfuploadHtml($element) {
        $list_upload_file = $this->listUploadFile($element);
        $html = <<<HTML
        <div id="btnUpload"></div>
        <div class="fieldset flash" id="fsUploadProgress"></div>
        <div id="divStatus">0 个文件已上传</div>
        <input type="button" class="uploadBtn" value="开始上传" onclick="uploadImage.startUpload();"/>&nbsp;&nbsp;
        <input id="btnCancel" class="uploadBtn" type="button" value="取消上传" onclick="uploadImage.cancelQueue();"/>&nbsp;&nbsp;
        <ul class="listImage">{$list_upload_file}</ul>
HTML;
        return $html;
    }

    function listUploadFile($element) {
        $uploaded_file = $element['uploaded_file'];
        $image_size = $element['preview_image_size'];
        $width = $image_size['width'] ? $image_size['width'] : 100;
        $height = $image_size['height'] ? $image_size['height'] : 80;
        $upload_image = '';
        if ($uploaded_file) {
            foreach ($uploaded_file as $value) {
                $upload_image .= <<<ETO
                <li>
                    <img width="{$width}" height="{$height}" src="/{$value}" />
                    <a class="linkDelImage" href="javascript:;" title="删除">x</a>
                    <input type="hidden" _ATTR_ value="{$value}" />
                </li>
ETO;
            }
        }
        return $upload_image;
    }

    function parseContent($content) {
        $html = '';
        if ($element['type'] == 'text') {
            $html = "<input type=\"text\" class=\"{$element['class']}\" name=\"{{$element['name']}}\" value=\"\" />";
        }
        return $html;
    }

    function setFormButton() {
        $html = '<tr><td></td><td colspan="2" class="submit">';
        foreach ($this->formButton as $btn) {
            $attr = $this->joinAttr($btn);
            $html .= "<input {$attr} />&nbsp;&nbsp;&nbsp;";
        }
        $html .= '</td></tr>';
        return $html;
    }

    function joinAttr($button) {
        $attr = $space = '';
        foreach ($button as $key => $value) {
            $attr .= "{$space}{$key}=\"{$value}\"";
            $space = ' ';
        }
        return $attr;
    }

    function setFormAttr() {
        $formAttr = $this->formAttr;
        isset($formAttr['method']) || $formAttr['method'] = 'POST';
        $attr = $this->joinAttr($formAttr);
        return $attr;
    }

    function create() {
        $attr = $this->setFormAttr();
        $html = "<form {$attr}>";
        $html .= $this->setHiddenElement();
        $html .= '<table class="add_data_table" width="100%" cellpadding="0" cellspacing="0" >';
        $this->setElementByData();
        $html .= $this->setElement();
        $html .= $this->setFormButton();
        $html .= '</table></form>';
        return $html;
    }

}

?>
