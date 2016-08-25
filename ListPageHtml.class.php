<?php

class ListPageHtml {

    public $listTableData = array();
    public $showBatchTable = true;

    function __construct() {
        
    }

    // 列表标题
    function getTableTitle() {
        $labs = $this->listTableData['libs'];
        $html = '<tr>';
        // 表格title
        foreach ($labs as $value) {
            $title = is_array($value) ? $value[0] : $value;
            $html .= "<td class=\"css_outer22\" align=\"center\">{$title}</td>";
        }
        if ($this->listTableData['buttons']) {
            $html .= '<td class="css_outer22" align="center">操作</td>';
        }
        $html .= '</tr>';
        return $html;
    }

    // 列表内容
    function getTableContent() {
        $labs = $this->listTableData['libs'];
        $list_data = $this->listTableData['list_data'];
        $html = '';
        if (is_array($list_data) && $list_data) {
            foreach ($list_data as $data) {
                $html .= '<tr>';
                $extra_html = '';
                if ($this->listTableData['extra_function']) {
                    $func = $this->listTableData['extra_function'];
                    $func_p = $func[1] ? $func[1] : array();
                    $extra_html = call_user_func_array($func[0], array($data, $func_p));
                }
                $i = 0;
                foreach ($labs as $key => $lab) {
                    $content = $this->parseData($data, $key, $lab, $i);
                    $i == 0 && $content .= $extra_html;
                    $html .= "<td class=\"css_outer2\" align=\"center\">{$content}</td>";
                    $i++;
                }
                $html .= $this->setOptionButton($data);
                $html .= '</tr>';
            }
        } else {
            $colspan = count($labs) + 1;
            $html = '<tr><td class="css_outer2 tdEmpty" colspan="' . $colspan . '">暂无内容!</td></tr>';
        }
        return $html;
    }

    // 设置操作button
    function setOptionButton($data) {
        $buttons = $this->listTableData['buttons'];
        $button = '';
        if (!$buttons) {
            return $button;
        }
        $button_html = '<td class="css_outer2 option_tr" align="center">';
        if ($buttons) {
            $button_count = count($buttons);
            foreach ($buttons as $key => $value) {
                // 方法
                if (isset($value['function'])) {
                    $func = $value['function'];
                    $func_p = $func[1] ? $func[1] : array();
                    $button .= call_user_func_array($func[0], array($data, $func_p));
                    continue;
                }
                // 获取属性及内容
                $attr = $value['attr'];
                $button_tpl = '<a ';
                array_walk($attr, array($this, 'joinAttr'));
                $button_tpl .= implode(' ', $attr);
                $name = $value['img'] ? "<img src='{$value['img']}' />" : $value['name'];
                $button_tpl .= '>' . $name . '</a>' . ($button_count - 1 == $key ? '' : ' ');
                eval("\$button .= \"{$button_tpl}\";");
            }
        }
        $button_html .= $button;
        $button_html .= '</td>';
        return $button_html;
    }

    function joinAttr(&$attr, $key) {
        $attr = "{$key}=\\\"{$attr}\\\"";
    }

    function setjoinAttr($button) {
        $attr = $space = '';
        foreach ($button as $key => $value) {
            $attr .= "{$space}{$key}=\"{$value}\"";
            $space = ' ';
        }
        return $attr;
    }

    // 设置显示内容
    function parseData($data, $key, $lab, $index) {
        $content = $ckInput = '';
        if ($this->showBatchTable && $index == 0) {
            $ckInput = "<input type=\"checkbox\" name=\"data_id[]\" value=\"{$data['id']}\" />";
        }
        if (is_string($lab)) {
            $content = $data[$key];
            if ($content) {
                if (strpos($key, 'time') !== false) {
                    $content = date_("Y-m-d H:i", $content);
                } else if (strpos($key, 'show_image') !== false) {
                    $content = "<a target='_blank' href='/{$content}'><img class='show_image' src='/{$content}' /></a>";
                }
            }
        } else {
            $temp_type = $lab[1];
            if (is_array($temp_type)) {      // 数组
                $content = $temp_type[$data[$key]];
            } else if (function_exists($temp_type)) {    // 方法
                $content = $temp_type($data[$key], $data);
            }
        }
        is_null($content) && $content = '-';
        return $ckInput . $content;
    }

    function getBatchTable() {
        // 是否显示批量操作
        if ($this->showBatchTable === false) {
            return '';
        }
        $buttons = $this->listTableData['batch_buttons'];
        $button_html = '';
        foreach ($buttons as $value) {
            $attr = $this->setjoinAttr($value);
            $button_html .= "<input type='button' {$attr} />";
        }
        $html = <<<ETO
    <table width="100%" style="margin-top:8px;" cellpadding="0" cellspacing="0" >
        <tr>
            <td>
                <input type="button" value="全选" onclick="checkAll('data_id[]')" />
                <input type="button" value="取消" onclick="uncheckAll('data_id[]')"/>
                {$button_html}
            </td>
        </tr>
    </table>
ETO;
        return $html;
    }

    // 创建数据列表表格
    function createListTable() {
        $search = array('TABLE_TITLE', 'TABLE_CONTENT', 'BATCH_TABLE');
        $replace = array();
        $tpl = $this->listTableTpl();
        $replace[] = $this->getTableTitle();
        $replace[] = $this->getTableContent();
        $replace[] = $this->getBatchTable();
        $html = str_replace($search, $replace, $tpl);
        return $html;
    }

    // 设置表格模板
    function listTableTpl() {
        $html = <<<ETO
<table width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td class="css_outer01" height="2px"></td>
    </tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" class="css_outer">
    TABLE_TITLE
    TABLE_CONTENT
</table>
BATCH_TABLE
ETO;
        return $html;
    }

}

?>
