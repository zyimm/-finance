<?php 
use Think\Crypt;

/**
 * 判断是否utf8
 * 
 * @param string $string
 * @return number
 * @author 周阳阳 2017年3月14日 下午12:37:49
 */
function is_utf8($string = '') {
	return preg_match('%^(?:
		 [\x09\x0A\x0D\x20-\x7E]            # ASCII
	   | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
	   |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
	   | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
	   |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
	   |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
	   | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
	   |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
   )*$%xs', $string);
}
if (!function_exists('array_column')) {
    function array_column($input, $column_key, $index_key = null)
    {
        $result = array();
        foreach ($input as $subArray) {
            if (!is_array($subArray)) {
                continue;
            } elseif (is_null($index_key) && array_key_exists($column_key, $subArray)) {
                $result[] = $subArray[$column_key];
            } elseif (array_key_exists($index_key, $subArray)) {
                if (is_null($column_key)) {
                    $result[$subArray[$index_key]] = $subArray;
                } elseif (array_key_exists($column_key, $subArray)) {
                    $result[$subArray[$index_key]] = $subArray[$column_key];
                }
            } elseif(is_null($column_key)) {
                $result[] = $subArray;
            }
        }
        return $result;
    }
}

/**
 * 删除文件夹并重建文件夹
 * 
 * @param string $dirname            
 * @return mixed
 * @author 周阳阳 2017年4月19日 下午4:00:52
 */
function rmdirr($dirname = '')
{
    if (! file_exists($dirname)) {
        return false;
    }
    if (is_file($dirname) || is_link($dirname)) {
        return unlink($dirname);
    }
    $dir = dir($dirname);
    while (false !== $entry = $dir->read()) {
        if ($entry == '.' || $entry == '..') {
            continue;
        }
        rmdirr($dirname . DIRECTORY_SEPARATOR . $entry);
    }
    $dir->close();
    return rmdir($dirname);
}


/**
 * 标种小图标展示
 * 
 * @param number $type
 * @return string
 * @author 周阳阳 2017年5月5日 下午3:15:14
 */
function getIco($map= [])
{
    $icon = [];
    $icon_path = "/Statics/H/Images/icon/";
    if ($map['borrow_type']== 2) {
        $icon .= "<img src='{$icon_path}d.gif' align='absmiddle'>";
    } elseif ($map['borrow_type']== 3) {
        $icon .= "<img src='{$icon_path}m.gif' align='absmiddle'>";
    } elseif ($map['borrow_type']== 4) {
        $icon .= "<img src='{$icon_path}jing.gif' align='absmiddle'>";
    } elseif ($map['borrow_type']== 1) {
        $icon .= "<img src='{$icon_path}xin.gif' align='absmiddle'>";
    } elseif ($map['borrow_type']== 5) {
        $icon .= "<img src='{$icon_path}ya.gif' align='absmiddle'>";
    } elseif ($map['borrow_type']== 6) {
        $icon .= "<img src='{$icon_path}lbt.gif' align='absmiddle'>";
    }
    if ($map['repayment_type'] == 1) {
        $icon .= "<img src='{$icon_path}t.gif' align='absmiddle'>";
    }
    if (! empty($map['password'])) {
        $icon .= "<img src='{$icon_path}passw.gif' align='absmiddle' >";
    }
    if ($map['is_tuijian'] == 1) {
        $icon .= "<img src='{$icon_path}tuijian.gif' align='absmiddle'>";
    }
    if ($map['reward_type'] > 0 && ($map['reward_num'] > 0 || $map['reward_money'] > 0)) {
        $icon .= "<img src='{$icon_path}j.gif' align='absmiddle'>";
    }
    return $icon . '&nbsp;&nbsp;';
}

function get_invest_url($id = 0)
{
    $id = Crypt::encrypt($id,C('CRYPT_KEY'));
    return U('/Front/Invest/view/'.$id);
    
}