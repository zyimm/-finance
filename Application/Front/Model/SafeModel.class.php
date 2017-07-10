<?php
namespace Front\Model;

class SafeModel
{

    /**
     * 输出纯文本
     *
     * @param string $text            
     * @param string $parseBr            
     * @param string $nr            
     * @return string
     */
    public static function text($text = '', $parseBr = false, $nr = false)
    {
        $text = htmlspecialchars_decode($text);
        $text = self::safe($text, 'text');
        if (! $parseBr && $nr) {
            $text = str_ireplace(array(
                "\r",
                "\n",
                "\t",
                "&nbsp;"
            ), '', $text);
            $text = htmlspecialchars($text, ENT_QUOTES);
        } elseif (! $nr) {
            $text = htmlspecialchars($text, ENT_QUOTES);
        } else {
            $text = htmlspecialchars($text, ENT_QUOTES);
            $text = nl2br($text);
        }
        $text = trim($text);
        return $text;
    }

    public static function safe($text = '', $type = 'html', $tagsMethod = true, $attrMethod = true, $xssAuto = 1, $tags = array(), $attr = array(), $tagsBlack = array(), $attrBlack = array())
    {
        // 无标签格式
        $text_tags = '';
        // 只存在字体样式
        $font_tags = '<i><b><u><s><em><strong><font><big><small><sup><sub><bdo><h1><h2><h3><h4><h5><h6>';
        // 标题摘要基本格式
        $base_tags = $font_tags . '<p><br><hr><a><img><map><area><pre><code><q><blockquote><acronym><cite><ins><del><center><strike>';
        // 兼容Form格式
        $form_tags = $base_tags . '<form><input><textarea><button><select><optgroup><option><label><fieldset><legend>';
        // 内容等允许HTML的格式
        $html_tags = $base_tags . '<ul><ol><li><dl><dd><dt><table><caption><td><th><tr><thead><tbody><tfoot><col><colgroup><div><span><object><embed>';
        // 专题等全HTML格式
        $all_tags = $form_tags . $html_tags . '<!DOCTYPE><html><head><title><body><base><basefont><script><noscript><applet><object><param><style><frame><frameset><noframes><iframe>';
        // 过滤标签
        $text = strip_tags($text, ${$type . '_tags'});
        // 过滤攻击代码
        if ($type != 'all') {
            // 过滤危险的属性，如：过滤on事件lang js
            while (preg_match('/(<[^><]+) (onclick|onload|unload|onmouseover|onmouseup|onmouseout|onmousedown|onkeydown|onkeypress|onkeyup|onblur|onchange|onfocus|action|background|codebase|dynsrc|lowsrc)([^><]*)/i', $text, $mat)) {
                $text = str_ireplace($mat[0], $mat[1] . $mat[3], $text);
            }
            while (preg_match('/(<[^><]+)(window\.|javascript:|js:|about:|file:|document\.|vbs:|cookie)([^><]*)/i', $text, $mat)) {
                $text = str_ireplace($mat[0], $mat[1] . $mat[3], $text);
            }
        }
        return $text;
    }

    /**
     * 在前台显示时去掉反斜线,传入数组，最多二维
     * 
     * @param array $arr            
     * @return string|string[]
     */
    public static function removeSlash($arr = array())
    {
        $data = array();
        if (is_array($arr)) {
            foreach ($arr as $key => $v) {
                if (is_array($v)) {
                    foreach ($v as $skey => $sv) {
                        if (!is_array($sv)) {
                            $v[$skey] = stripslashes($sv);
                        }
                    }
                    $data[$key] = $v;
                } else {
                    $data[$key] = stripslashes($v);
                }
            }
        } else {
            $data = stripslashes($arr);
        }
        return $data;
    }
}

