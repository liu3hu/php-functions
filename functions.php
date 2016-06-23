<?php
    /**
     * 获取客户端IP地址
     * @param   integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
     * @param   boolean $adv  是否进行高级模式获取（有可能被伪装） 
     * @return  mixed
     */
    function get_client_ip($type = 0,$adv=false) {
        $type       =  $type ? 1 : 0;
        static $ip  =   NULL;
        if ($ip !== NULL) return $ip[$type];
        if($adv){
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $pos    =   array_search('unknown',$arr);
                if(false !== $pos) unset($arr[$pos]);
                $ip     =   trim($arr[0]);
            }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip     =   $_SERVER['HTTP_CLIENT_IP'];
            }elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $ip     =   $_SERVER['REMOTE_ADDR'];
            }
        }elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip     =   $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u",ip2long($ip));
        $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
        return $ip[$type];
    }


    /**
     * 判断是否SSL协议
     * @return boolean
     */
    function is_ssl() {
        if(isset($_SERVER['HTTPS']) && ('1' == $_SERVER['HTTPS'] || 'on' == strtolower($_SERVER['HTTPS']))){
            return true;
        }elseif(isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'] )) {
            return true;
        }
        return false;
    }


    /**
     * 发送HTTP状态
     * @param  integer $code 状态码
     * @return void
     */
    function send_http_status($code) {
        static $_status = array(
            // Informational 1xx
            100 => 'Continue',
            101 => 'Switching Protocols',
            // Success 2xx
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            // Redirection 3xx
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Moved Temporarily ', // 1.1
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            // 306 is deprecated but reserved
            307 => 'Temporary Redirect',
            // Client Error 4xx
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            // Server Error 5xx
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            509 => 'Bandwidth Limit Exceeded'
        );
        if(isset($_status[$code])) {
            header('HTTP/1.1 '.$code.' '.$_status[$code]);
            // 确保FastCGI模式下正常
            header('Status:'.$code.' '.$_status[$code]);
        }
    }


    /**
     * 获取当前时间毫秒
     * @return string
     */
    function get_microtime(){
        list($usec, $sec) = explode(' ', microtime());
        $usec2msec = $usec * 1000;    //计算微秒部分的毫秒数(微秒部分并不是微秒,这部分的单位是秒)
        $sec2msec = $sec * 1000;    //计算秒部分的毫秒数
        $usec2msec2float = (float)$usec2msec;    
        $sec2msec2float = (float)$sec2msec;    
        $msec = $usec2msec2float + $sec2msec2float; //加起来就对了
        $arrMsc = explode('.', $msec);
        return $arrMsc[0];
    }


    /**
     * 功能：字节格式化 把字节数格式为 B K M G T 描述的大小
     * @param  string $size 字节数
     * @param  string $dec  小数点后保留的位数
     * @return string
     */
    function byte_format($size, $dec=2) {
        $a = array("B", "KB", "MB", "GB", "TB", "PB");
        $pos = 0;
        while ($size >= 1024) {
             $size /= 1024;
               $pos++;
        }
        return round($size,$dec)." ".$a[$pos];
    }


    /**
     * 检查字符串是否是UTF8编码
     * @param  string  $string 字符串
     * @return boolean
     */
    function is_utf8($string) {
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


    /**
     * 根据PHP各种类型变量生成唯一标识号
     * @param  mixed  $mix 变量
     * @return string
     */
    function to_guid_string($mix) {
        if (is_object($mix) && function_exists('spl_object_hash')) {
            return spl_object_hash($mix);
        } elseif (is_resource($mix)) {
            $mix = get_resource_type($mix) . strval($mix);
        } else {
            $mix = serialize($mix);
        }
        return md5($mix);
    }


    /**
     * 功能：解决nginx不支持getallheaders的情况
     * @return array
     */
    if (!function_exists('getallheaders')) {
        function getallheaders() {
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
            return $headers;
        }
    }


    /**
     * 优化的require_once
     * @param  string  $filename 文件地址
     * @return boolean
     */
    function require_cache($filename) {
        static $_importFiles = array();
        if (!isset($_importFiles[$filename])) {
            if (file_exists_case($filename)) {
                require $filename;
                $_importFiles[$filename] = true;
            } else {
                $_importFiles[$filename] = false;
            }
        }
        return $_importFiles[$filename];
    }


    /**
     * 功能：加密解密字符串
     * @param  string  $string    明文 或 密文
     * @param  string  $operation DECODE表示解密,其它表示加密
     * @param  string  $key       密匙
     * @param  integer $expiry    密文有效期
     * @return string             返回加密后的字符串
     */
    function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
        // 动态密匙长度，相同的明文会生成不同密文就是依靠动态密匙
        $ckey_length = 4;

        // 密匙
        $key = $key?md5($key):'';

        // 密匙a会参与加解密
        $keya = md5(substr($key, 0, 16));
        // 密匙b会用来做数据完整性验证
        $keyb = md5(substr($key, 16, 16));
        // 密匙c用于变化生成的密文
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';
        // 参与运算的密匙
        $cryptkey = $keya.md5($keya.$keyc);
        $key_length = strlen($cryptkey);
        // 明文，前10位用来保存时间戳，解密时验证数据有效性，10到26位用来保存$keyb(密匙b)，解密时会通过这个密匙验证数据完整性
        // 如果是解码的话，会从第$ckey_length位开始，因为密文前$ckey_length位保存 动态密匙，以保证解密正确
        $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
        $string_length = strlen($string);
        $result = '';
        $box = range(0, 255);
        $rndkey = array();
        // 产生密匙簿
        for($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }
        // 用固定的算法，打乱密匙簿，增加随机性，好像很复杂，实际上对并不会增加密文的强度
        for($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        // 核心加解密部分
        for($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            // 从密匙簿得出密匙进行异或，再转成字符
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }
        if($operation == 'DECODE') {
            // substr($result, 0, 10) == 0 验证数据有效性
            // substr($result, 0, 10) - time() > 0 验证数据有效性
            // substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16) 验证数据完整性
            // 验证数据有效性，请看未加密明文的格式
            if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            // 把动态密匙保存在密文里，这也是为什么同样的明文，生产不同密文后能解密的原因
            // 因为加密后的密文可能是一些特殊字符，复制过程可能会丢失，所以用base64编码
            return $keyc.str_replace('=', '', base64_encode($result));
        }
    }


    /**
     * escape编码  同JS的escape
     * @param  string $str 待编码字符串
     * @return string
     */
    function escape($str){
        preg_match_all("/[\xc2-\xdf][\x80-\xbf]+|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}|[\x01-\x7f]+/e",$str,$r);
        //匹配utf-8字符，
        $str = $r[0];
        $l = count($str);
        for($i=0; $i <$l; $i++){
            $value = ord($str[$i][0]);
            if($value < 223){
                $str[$i] = rawurlencode(utf8_decode($str[$i]));
                //先将utf8编码转换为ISO-8859-1编码的单字节字符，urlencode单字节字符.
                //utf8_decode()的作用相当于iconv("UTF-8","CP1252",$v)。
            }else{
                $str[$i] = "%u".strtoupper(bin2hex(iconv("UTF-8","UCS-2",$str[$i])));
            }
        }
        return join("",$str);
    }


    /**
     * unescape编码  同JS的unescape
     * @param  string $str 待解码字符串
     * @return string
     */
    function unescape($str){
        $ret = '';
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++){
            if ($str[$i] == '%' && $str[$i+1] == 'u'){
                $val = hexdec(substr($str, $i+2, 4));
                if ($val < 0x7f) $ret .= chr($val);
                else if($val < 0x800) $ret .= chr(0xc0|($val>>6)).chr(0x80|($val&0x3f));
                else $ret .= chr(0xe0|($val>>12)).chr(0x80|(($val>>6)&0x3f)).chr(0x80|($val&0x3f));

                $i += 5;
            }else if ($str[$i] == '%'){
                $ret .= urldecode(substr($str, $i, 3));
                $i += 2;
            }
            else $ret .= $str[$i];
        }
        //$ret=iconv('utf-8', 'gb2312', $ret);
        return $ret;
    }


    /**
     * 自动转换字符集 支持数组转换
     * @param  mixed  $fContents 待转换字符串或数组
     * @param  string $from      输入字符
     * @param  string $to        输出字符
     * @return mixed
     */
    function auto_charset($fContents, $from='gbk', $to='utf-8') {
        $from = strtoupper($from) == 'UTF8' ? 'utf-8' : $from;
        $to = strtoupper($to) == 'UTF8' ? 'utf-8' : $to;
        if (strtoupper($from) === strtoupper($to) || empty($fContents) || (is_scalar($fContents) && !is_string($fContents))) {
            //如果编码相同或者非字符串标量则不转换
            return $fContents;
        }
        if (is_string($fContents)) {
            if (function_exists('mb_convert_encoding')) {
                return mb_convert_encoding($fContents, $to, $from);
            } elseif (function_exists('iconv')) {
                return iconv($from, $to, $fContents);
            } else {
                return $fContents;
            }
        } elseif (is_array($fContents)) {
            foreach ($fContents as $key => $val) {
                $_key = auto_charset($key, $from, $to);
                $fContents[$_key] = auto_charset($val, $from, $to);
                if ($key != $_key)
                    unset($fContents[$key]);
            }
            return $fContents;
        }
        else {
            return $fContents;
        }
    }


    /**
     * 过滤跨站攻击字符串
     * @param  string $val 待过滤字符串
     * @return string
     */
    function remove_xss($val) {
        // remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed
        // this prevents some character re-spacing such as <java\0script>
        // note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs
        $val = preg_replace('/([\x00-\x08,\x0b-\x0c,\x0e-\x19])/', '', $val);

        // straight replacements, the user should never need these since they're normal characters
        // this prevents like <IMG SRC=@avascript:alert('XSS')>
        $search = 'abcdefghijklmnopqrstuvwxyz';
        $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $search .= '1234567890!@#$%^&*()';
        $search .= '~`";:?+/={}[]-_|\'\\';
        for ($i = 0; $i < strlen($search); $i++) {
            // ;? matches the ;, which is optional
            // 0{0,7} matches any padded zeros, which are optional and go up to 8 chars

            // @ @ search for the hex values
            $val = preg_replace('/(&#[xX]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $val); // with a ;
            // @ @ 0{0,7} matches '0' zero to seven times
            $val = preg_replace('/(&#0{0,8}'.ord($search[$i]).';?)/', $search[$i], $val); // with a ;
        }

        // now the only remaining whitespace attacks are \t, \n, and \r
        $ra1 = array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base');
        $ra2 = array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
        $ra = array_merge($ra1, $ra2);

        $found = true; // keep replacing as long as the previous round replaced something
        while ($found == true) {
            $val_before = $val;
            for ($i = 0; $i < sizeof($ra); $i++) {
                $pattern = '/';
                for ($j = 0; $j < strlen($ra[$i]); $j++) {
                    if ($j > 0) {
                        $pattern .= '(';
                        $pattern .= '(&#[xX]0{0,8}([9ab]);)';
                        $pattern .= '|';
                        $pattern .= '|(&#0{0,8}([9|10|13]);)';
                        $pattern .= ')*';
                    }
                    $pattern .= $ra[$i][$j];
                }
                $pattern .= '/i';
                $replacement = substr($ra[$i], 0, 2).'<x>'.substr($ra[$i], 2); // add in <> to nerf the tag
                $val = preg_replace($pattern, $replacement, $val); // filter out the hex tags
                if ($val_before == $val) {
                    // no replacements were made, so exit the loop
                    $found = false;
                }
            }
        }
        return $val;
    }


    /**
     * 代码加亮
     * @param  string  $str 要高亮显示的字符串 或者 文件名
     * @param  boolean $show 是否输出
     * @return string
     */
    function highlight_code($str,$show=false) {
        if(file_exists($str)) {
            $str    =   file_get_contents($str);
        }
        $str  =  stripslashes(trim($str));
        // The highlight string function encodes and highlights
        // brackets so we need them to start raw
        $str = str_replace(array('&lt;', '&gt;'), array('<', '>'), $str);

        // Replace any existing PHP tags to temporary markers so they don't accidentally
        // break the string out of PHP, and thus, thwart the highlighting.

        $str = str_replace(array('&lt;?php', '?&gt;',  '\\'), array('phptagopen', 'phptagclose', 'backslashtmp'), $str);

        // The highlight_string function requires that the text be surrounded
        // by PHP tags.  Since we don't know if A) the submitted text has PHP tags,
        // or B) whether the PHP tags enclose the entire string, we will add our
        // own PHP tags around the string along with some markers to make replacement easier later

        $str = '<?php //tempstart'."\n".$str.'//tempend ?>'; // <?

        // All the magic happens here, baby!
        $str = highlight_string($str, TRUE);

        // Prior to PHP 5, the highlight function used icky font tags
        // so we'll replace them with span tags.
        if (abs(phpversion()) < 5) {
            $str = str_replace(array('<font ', '</font>'), array('<span ', '</span>'), $str);
            $str = preg_replace('#color="(.*?)"#', 'style="color: \\1"', $str);
        }

        // Remove our artificially added PHP
        $str = preg_replace("#\<code\>.+?//tempstart\<br />\</span\>#is", "<code>\n", $str);
        $str = preg_replace("#\<code\>.+?//tempstart\<br />#is", "<code>\n", $str);
        $str = preg_replace("#//tempend.+#is", "</span>\n</code>", $str);

        // Replace our markers back to PHP tags.
        $str = str_replace(array('phptagopen', 'phptagclose', 'backslashtmp'), array('&lt;?php', '?&gt;', '\\'), $str); //<?
        $line   =   explode("<br />", rtrim(ltrim($str,'<code>'),'</code>'));
        $result =   '<div class="code"><ol>';
        foreach($line as $key=>$val) {
            $result .=  '<li>'.$val.'</li>';
        }
        $result .=  '</ol></div>';
        $result = str_replace("\n", "", $result);
        if( $show!== false) {
            echo($result);
        }else {
            return $result;
        }
    }


    /**
     * 输出安全的html
     * @param  string $text 待过滤的字符串
     * @param  string $tags 允许的HTML标签
     * @return string
     */
    function h($text, $tags = null) {
        $text   =   trim($text);
        //完全过滤注释
        $text   =   preg_replace('/<!--?.*-->/','',$text);
        //完全过滤动态代码
        $text   =   preg_replace('/<\?|\?'.'>/','',$text);
        //完全过滤js
        $text   =   preg_replace('/<script?.*\/script>/','',$text);

        $text   =   str_replace('[','&#091;',$text);
        $text   =   str_replace(']','&#093;',$text);
        $text   =   str_replace('|','&#124;',$text);
        //过滤换行符
        $text   =   preg_replace('/\r?\n/','',$text);
        //br
        $text   =   preg_replace('/<br(\s\/)?'.'>/i','[br]',$text);
        $text   =   preg_replace('/<p(\s\/)?'.'>/i','[br]',$text);
        $text   =   preg_replace('/(\[br\]\s*){10,}/i','[br]',$text);
        //过滤危险的属性，如：过滤on事件lang js
        while(preg_match('/(<[^><]+)( lang|on|action|background|codebase|dynsrc|lowsrc)[^><]+/i',$text,$mat)){
            $text=str_replace($mat[0],$mat[1],$text);
        }
        while(preg_match('/(<[^><]+)(window\.|javascript:|js:|about:|file:|document\.|vbs:|cookie)([^><]*)/i',$text,$mat)){
            $text=str_replace($mat[0],$mat[1].$mat[3],$text);
        }
        if(empty($tags)) {
            $tags = 'table|td|th|tr|i|b|u|strong|img|p|br|div|strong|em|ul|ol|li|dl|dd|dt|a';
        }
        //允许的HTML标签
        $text   =   preg_replace('/<('.$tags.')( [^><\[\]]*)>/i','[\1\2]',$text);
        $text = preg_replace('/<\/('.$tags.')>/Ui','[/\1]',$text);
        //过滤多余html
        $text   =   preg_replace('/<\/?(html|head|meta|link|base|basefont|body|bgsound|title|style|script|form|iframe|frame|frameset|applet|id|ilayer|layer|name|script|style|xml)[^><]*>/i','',$text);
        //过滤合法的html标签
        while(preg_match('/<([a-z]+)[^><\[\]]*>[^><]*<\/\1>/i',$text,$mat)){
            $text=str_replace($mat[0],str_replace('>',']',str_replace('<','[',$mat[0])),$text);
        }
        //转换引号
        while(preg_match('/(\[[^\[\]]*=\s*)(\"|\')([^\2=\[\]]+)\2([^\[\]]*\])/i',$text,$mat)){
            $text=str_replace($mat[0],$mat[1].'|'.$mat[3].'|'.$mat[4],$text);
        }
        //过滤错误的单个引号
        while(preg_match('/\[[^\[\]]*(\"|\')[^\[\]]*\]/i',$text,$mat)){
            $text=str_replace($mat[0],str_replace($mat[1],'',$mat[0]),$text);
        }
        //转换其它所有不合法的 < >
        $text   =   str_replace('<','&lt;',$text);
        $text   =   str_replace('>','&gt;',$text);
        $text   =   str_replace('"','&quot;',$text);
         //反转换
        $text   =   str_replace('[','<',$text);
        $text   =   str_replace(']','>',$text);
        $text   =   str_replace('|','"',$text);
        //过滤多余空格
        $text   =   str_replace('  ',' ',$text);
        return $text;
    }


    /**
     * 功能：字符串截取，支持中文和其他编码
     * @param  string $str 需要转换的字符串
     * @param  string $start 开始位置
     * @param  string $length 截取长度
     * @param  string $charset 编码格式
     * @param  string $suffix 截断显示字符
     * @return string
     */
    function msubstr($str, $start=0, $length, $charset="utf-8", $suffix='') {
        if(function_exists("mb_substr"))
            $slice = mb_substr($str, $start, $length, $charset);
        elseif(function_exists('iconv_substr')) {
            $slice = iconv_substr($str,$start,$length,$charset);
            if(false === $slice) {
                $slice = '';
            }
        }else{
            $re['utf-8']   = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
            $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
            $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
            $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
            preg_match_all($re[$charset], $str, $match);
            $slice = join("",array_slice($match[0], $start, $length));
        }
        return $suffix ? $slice.$suffix : $slice;
    }


    /**
     * 功能：字符串截取指定长度 支持中文
     * @param  string  $string 待截取的字符串
     * @param  integer $len    截取的长度
     * @param  integer $start  从第几个字符开始截取
     * @param  boolean $suffix 是否在截取后的字符串后跟上省略号
     * @return string          返回截取后的字符串
     */
    function cutstr($str, $len = 100, $start = 0, $suffix = 1) {
        $str = strip_tags(trim(strip_tags($str)));
        $str = str_replace(array("\n", "\t"), "", $str);
        $strlen = mb_strlen($str);
        while ($strlen) {
            $array[] = mb_substr($str, 0, 1, "utf8");
            $str = mb_substr($str, 1, $strlen, "utf8");
            $strlen = mb_strlen($str);
        }
        $end = $len + $start;
        $str = '';
        for ($i = $start; $i < $end; $i++) {
            $str.=$array[$i];
        }
        return count($array) > $len ? ($suffix == 1 ? $str . "&hellip;" : $str) : $str;
    }


    /**
     * 功能：产生随机字串，可用来自动生成密码 默认长度6位 字母和数字混合
     * @param  string $len      长度
     * @param  string $type     字串类型 ， 0 字母 1 数字 其它 混合
     * @param  string $addChars 额外字符
     * @return string
     */
    function rand_string($len=6,$type='',$addChars='') {
        $str ='';
        switch($type) {
            case 0:
                $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'.$addChars;
                break;
            case 1:
                $chars= str_repeat('0123456789',3);
                break;
            case 2:
                $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZ'.$addChars;
                break;
            case 3:
                $chars='abcdefghijklmnopqrstuvwxyz'.$addChars;
                break;
            case 4:
                $chars = "们以我到他会作时要动国产的一是工就年阶义发成部民可出能方进在了不和有大这主中人上为来分生对于学下级地个用同行面说种过命度革而多子后自社加小机也经力线本电高量长党得实家定深法表着水理化争现所二起政三好十战无农使性前等反体合斗路图把结第里正新开论之物从当两些还天资事队批点育重其思与间内去因件日利相由压员气业代全组数果期导平各基或月毛然如应形想制心样干都向变关问比展那它最及外没看治提五解系林者米群头意只明四道马认次文通但条较克又公孔领军流入接席位情运器并飞原油放立题质指建区验活众很教决特此常石强极土少已根共直团统式转别造切九你取西持总料连任志观调七么山程百报更见必真保热委手改管处己将修支识病象几先老光专什六型具示复安带每东增则完风回南广劳轮科北打积车计给节做务被整联步类集号列温装即毫知轴研单色坚据速防史拉世设达尔场织历花受求传口断况采精金界品判参层止边清至万确究书术状厂须离再目海交权且儿青才证低越际八试规斯近注办布门铁需走议县兵固除般引齿千胜细影济白格效置推空配刀叶率述今选养德话查差半敌始片施响收华觉备名红续均药标记难存测士身紧液派准斤角降维板许破述技消底床田势端感往神便贺村构照容非搞亚磨族火段算适讲按值美态黄易彪服早班麦削信排台声该击素张密害侯草何树肥继右属市严径螺检左页抗苏显苦英快称坏移约巴材省黑武培著河帝仅针怎植京助升王眼她抓含苗副杂普谈围食射源例致酸旧却充足短划剂宣环落首尺波承粉践府鱼随考刻靠够满夫失包住促枝局菌杆周护岩师举曲春元超负砂封换太模贫减阳扬江析亩木言球朝医校古呢稻宋听唯输滑站另卫字鼓刚写刘微略范供阿块某功套友限项余倒卷创律雨让骨远帮初皮播优占死毒圈伟季训控激找叫云互跟裂粮粒母练塞钢顶策双留误础吸阻故寸盾晚丝女散焊功株亲院冷彻弹错散商视艺灭版烈零室轻血倍缺厘泵察绝富城冲喷壤简否柱李望盘磁雄似困巩益洲脱投送奴侧润盖挥距触星松送获兴独官混纪依未突架宽冬章湿偏纹吃执阀矿寨责熟稳夺硬价努翻奇甲预职评读背协损棉侵灰虽矛厚罗泥辟告卵箱掌氧恩爱停曾溶营终纲孟钱待尽俄缩沙退陈讨奋械载胞幼哪剥迫旋征槽倒握担仍呀鲜吧卡粗介钻逐弱脚怕盐末阴丰雾冠丙街莱贝辐肠付吉渗瑞惊顿挤秒悬姆烂森糖圣凹陶词迟蚕亿矩康遵牧遭幅园腔订香肉弟屋敏恢忘编印蜂急拿扩伤飞露核缘游振操央伍域甚迅辉异序免纸夜乡久隶缸夹念兰映沟乙吗儒杀汽磷艰晶插埃燃欢铁补咱芽永瓦倾阵碳演威附牙芽永瓦斜灌欧献顺猪洋腐请透司危括脉宜笑若尾束壮暴企菜穗楚汉愈绿拖牛份染既秋遍锻玉夏疗尖殖井费州访吹荣铜沿替滚客召旱悟刺脑措贯藏敢令隙炉壳硫煤迎铸粘探临薄旬善福纵择礼愿伏残雷延烟句纯渐耕跑泽慢栽鲁赤繁境潮横掉锥希池败船假亮谓托伙哲怀割摆贡呈劲财仪沉炼麻罪祖息车穿货销齐鼠抽画饲龙库守筑房歌寒喜哥洗蚀废纳腹乎录镜妇恶脂庄擦险赞钟摇典柄辩竹谷卖乱虚桥奥伯赶垂途额壁网截野遗静谋弄挂课镇妄盛耐援扎虑键归符庆聚绕摩忙舞遇索顾胶羊湖钉仁音迹碎伸灯避泛亡答勇频皇柳哈揭甘诺概宪浓岛袭谁洪谢炮浇斑讯懂灵蛋闭孩释乳巨徒私银伊景坦累匀霉杜乐勒隔弯绩招绍胡呼痛峰零柴簧午跳居尚丁秦稍追梁折耗碱殊岗挖氏刃剧堆赫荷胸衡勤膜篇登驻案刊秧缓凸役剪川雪链渔啦脸户洛孢勃盟买杨宗焦赛旗滤硅炭股坐蒸凝竟陷枪黎救冒暗洞犯筒您宋弧爆谬涂味津臂障褐陆啊健尊豆拔莫抵桑坡缝警挑污冰柬嘴啥饭塑寄赵喊垫丹渡耳刨虎笔稀昆浪萨茶滴浅拥穴覆伦娘吨浸袖珠雌妈紫戏塔锤震岁貌洁剖牢锋疑霸闪埔猛诉刷狠忽灾闹乔唐漏闻沈熔氯荒茎男凡抢像浆旁玻亦忠唱蒙予纷捕锁尤乘乌智淡允叛畜俘摸锈扫毕璃宝芯爷鉴秘净蒋钙肩腾枯抛轨堂拌爸循诱祝励肯酒绳穷塘燥泡袋朗喂铝软渠颗惯贸粪综墙趋彼届墨碍启逆卸航衣孙龄岭骗休借".$addChars;
                break;
            default :
                // 默认去掉了容易混淆的字符oOLl和数字01，要添加请使用addChars参数
                $chars='ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789'.$addChars;
                break;
        }
        if($len>10 ) {//位数过长重复字符串一定次数
            $chars= $type==1? str_repeat($chars,$len) : str_repeat($chars,5);
        }
        if($type!=4) {
            $chars   =   str_shuffle($chars);
            $str     =   substr($chars,0,$len);
        }else{
            // 中文随机字
            for($i=0;$i<$len;$i++){
              $str.= msubstr($chars, floor(mt_rand(0,mb_strlen($chars,'utf-8')-1)),1);
            }
        }
        return $str;
    }


    /**
     * 将数字转换为大写金额
     * @param  mix    $ns 数字金额
     * @return string     转换后的大写金额
     */
    function cny($ns) {
        static $cnums = array("零","壹","贰","叁","肆","伍","陆","柒","捌","玖"), 
        $cnyunits = array("圆","角","分"), 
        $grees = array("拾","佰","仟","万","拾","佰","仟","亿"); 
        list($ns1,$ns2) = explode(".",$ns,2); 
        $ns2 = array_filter(array($ns2[1],$ns2[0])); 

        $arr=array(array(str_split($ns1),$grees),array('',$cnyunits));
        foreach ($arr as $k => $v_arr) {
            if($k==1)$v_arr[0]=$ret;
            $ul = count($v_arr[1]); 
            $xs = array(); 
            foreach (array_reverse($v_arr[0]) as $x) { 
                $l = count($xs); 
                if($x!="0" || !($l%4)) {
                    $n=($x=='0'?'':$x).($v_arr[1][($l-1)%$ul]); 
                }
                else{
                    $n=is_numeric($xs[0][0]) ? $x : ''; 
                }
                array_unshift($xs, $n); 
            } 
            if($k==0){
                $ret = array_merge($ns2,array(implode("", $xs), "")); 
            }
        }
        $ret = implode("",array_reverse($xs)); 
        $r=str_replace(array_keys($cnums), $cnums,$ret); 

        preg_match_all("/./u", $r, $r_arr);
        $rr='';
        $prev_letter='';
        foreach ($r_arr[0] as $k1 => $v1) {
            if(!($v1==$prev_letter && $prev_letter=='零')){
                $rr.=$v1;
            }
            $prev_letter=$v1;
        }
        return $rr;
    }


    /**
     * 将一个字符串部分字符用*替代隐藏  支持中文
     * @param  string  $string  待转换的字符串
     * @param  integer $bengin  起始位置，从0开始计数
     * @param  integer $len     需要转换成*的字符个数，当$type=4时，表示右侧保留长度
     * @param  integer $hidestr 替代字符
     * @param  integer $type    转换类型：0，在整个字符串中 从左向右隐藏；
     *                                    1，在整个字符串中 从右向左隐藏；
     *                                    2，在分隔符前的字符串中 由右向左隐藏；
     *                                    3，在分隔符后的字符串中 由左向右隐藏；
     *                                    4，从指定位置开始保留最右侧指定长度，其余用*代替
     * @param  string  $glue    分割符
     * @return string           处理后的字符串
     */
    function hide_str($string, $bengin = 0, $len = 4,$hidestr='*', $type = 0, $glue = "@") {
        if (empty($string))
            return false;
        $array = array();
        if ($type == 0 || $type == 1 || $type == 4) {
            $strlen = $length = mb_strlen($string);
            while ($strlen) {
                $array[] = mb_substr($string, 0, 1, "utf8");
                $string = mb_substr($string, 1, $strlen, "utf8");
                $strlen = mb_strlen($string);
            }
        }
        switch ($type) {
            case 1:
                $array = array_reverse($array);
                for ($i = $bengin; $i < ($bengin + $len); $i++) {
                    if (isset($array[$i]))
                        $array[$i] = "$hidestr";
                }
                $string = implode("", array_reverse($array));
                break;
            case 2:
                $array = explode($glue, $string);
                $array[0] = hide_str($array[0], $bengin, $len, 1);
                $string = implode($glue, $array);
                break;
            case 3:
                $array = explode($glue, $string);
                $array[1] = hide_str($array[1], $bengin, $len, 0);
                $string = implode($glue, $array);
                break;
            case 4:
                $left = $bengin;
                $right = $len;
                $tem = array();
                for ($i = 0; $i < ($length - $right); $i++) {
                    if (isset($array[$i]))
                        $tem[] = $i >= $left ? "$hidestr" : $array[$i];
                }
                $array = array_chunk(array_reverse($array), $right);
                $array = array_reverse($array[0]);
                for ($i = 0; $i < $right; $i++) {
                    $tem[] = $array[$i];
                }
                $string = implode("", $tem);
                break;
            default:
                for ($i = $bengin; $i < ($bengin + $len); $i++) {
                    if (isset($array[$i]))
                        $array[$i] = "$hidestr";
                }
                $string = implode("", $array);
                break;
        }
        return $string;
    }


    /**
     * 将一个字符串转换成数组，支持中文
     * @param  string $string 待转换成数组的字符串
     * @return string         转换后的数组
     */
    function str_to_array($string) {
        $strlen = mb_strlen($string);
        while ($strlen) {
            $array[] = mb_substr($string, 0, 1, "utf8");
            $string = mb_substr($string, 1, $strlen, "utf8");
            $strlen = mb_strlen($string);
        }
        return $array;
    }


    /**
     * 数据XML编码
     * @param  mixed  $data 数据
     * @param  string $item 数字索引时的节点名称
     * @param  string $id   数字索引key转换为的属性名
     * @return string
     */
    function data_to_xml($data, $item='item', $id='id') {
        $xml = $attr = '';
        foreach ($data as $key => $val) {
            if(is_numeric($key)){
                $id && $attr = " {$id}=\"{$key}\"";
                $key  = $item;
            }
            $xml    .=  "<{$key}{$attr}>";
            $xml    .=  (is_array($val) || is_object($val)) ? data_to_xml($val, $item, $id) : $val;
            $xml    .=  "</{$key}>";
        }
        return $xml;
    }


    /**
     * XML编码
     * @param  mixed  $data     数据
     * @param  string $root     根节点名
     * @param  string $item     数字索引的子节点名
     * @param  string $attr     根节点属性
     * @param  string $id       数字索引子节点key转换的属性名
     * @param  string $encoding 数据编码
     * @return string
     */
    function xml_encode($data, $root='think', $item='item', $attr='', $id='id', $encoding='utf-8') {
        if(is_array($attr)){
            $_attr = array();
            foreach ($attr as $key => $value) {
                $_attr[] = "{$key}=\"{$value}\"";
            }
            $attr = implode(' ', $_attr);
        }
        $attr   = trim($attr);
        $attr   = empty($attr) ? '' : " {$attr}";
        $xml    = "<?xml version=\"1.0\" encoding=\"{$encoding}\"?>";
        $xml   .= "<{$root}{$attr}>";
        $xml   .= data_to_xml($data, $item, $id);
        $xml   .= "</{$root}>";
        return $xml;
    }


    /**
     * 获取无限分类的树形结构数据
     * @param  integer $id   当前ID
     * @param  integer $data 待处理数据
     * @param  string  $type tree 获取带树结构的二维数组，ids 获取id集合
     * @param  integer $self 是否包含自己 1是，0否
     * @param  string  $son  1 仅获取下一级，2 获取全部子级
     * @return array           
     * $data 格式：
     * array(
     *  array('id'=>1,'pid'=>0,'name'=>'aaa',...),
     *  array('id'=>2,'pid'=>1,'name'=>'bbb',...)
     *  ...
     * )
     * 返回的数据格式
     * array(
     *  array('id'=>1,'pid'=>0,'name'=>'aaa','fullname'=>'aaa',...),
     *  array('id'=>2,'pid'=>1,'name'=>'bbb','fullname'=>'└ bbb'...)
     *  ...
     * )
     * 或
     * array(1,2,3,4)
     */
    function get_tree($datas=array(),$id=0,$type='tree',$self=1,$son=2){
        static $res=array();

        $arg_num=func_num_args();
        if($arg_num!=6){
            $level=$self==1 && $id!=0?1:0;

            if($self==1 && $id!=0){
                foreach($datas as $v){
                    if($v['id']==$id){
                        $res[]=$v;
                        break;
                    }
                }
            }
        }else{
            $level=func_get_arg(5);
        }
        $level++;

        $filter_datas=array();
        foreach($datas as $v){
            if($v['pid']==$id){
                $filter_datas[]=$v;
            }
        }

        foreach($filter_datas as $v){
            $v['level']=$level;
            $res[]=$v;
            if($son==2){
                get_tree($datas,$v['id'],$type,$self,$son,$level);
            }
        }

        $resx=array();
        if(!empty($res)){
            foreach($res as $k=>$v){
                if($type=='ids'){
                    $resx[]=$v['id'];
                    continue;
                }
                if(isset($res[$k+1]['level'])){
                    if($v['level']>=2){
                        $separate2=$v['level']>$res[$k+1]['level']?"&nbsp;&nbsp;&nbsp;&nbsp;└":"&nbsp;&nbsp;&nbsp;&nbsp;├";
                    }else{
                        $separate2='';
                    }
                    if($v['level']>=3){
                        if($v['level']>$res[$k+1]['level']){
                            $separate1=str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;│", $res[$k+1]['level']-1).str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;└", $v['level']-$res[$k+1]['level']-1);
                        }else{
                            $separate1=str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;│", $v['level']-2);
                        }
                    }else{
                        $separate1='';
                    }
                }else{
                    $separate1='';
                    $separate2=str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;└", $v['level']-1);
                }
                $v['fullname']=$separate1.$separate2.' '.$v['name'];
                $resx[]=$v;
            }
        }
        unset($res);
        return $resx;
    }


    /**
     * 把返回的数据集转换成Tree
     * @param  array  $list  要转换的数据集
     * @param  string $pk    id标记字段
     * @param  string $pid   parent标记字段
     * @param  array  $child 子数据集字段
     * @param  string $root  根id值
     * @return array
     */
    function list_to_tree($list, $pk='id',$pid = 'pid',$child = '_child',$root=0) {
        // 创建Tree
        $tree = array();
        if(is_array($list)) {
            // 创建基于主键的数组引用
            $refer = array();
            foreach ($list as $key => $data) {
                $refer[$data[$pk]] =& $list[$key];
            }
            foreach ($list as $key => $data) {
                // 判断是否存在parent
                $parentId = $data[$pid];
                if ($root == $parentId) {
                    $tree[] =& $list[$key];
                }else{
                    if (isset($refer[$parentId])) {
                        $parent =& $refer[$parentId];
                        $parent[$child][] =& $list[$key];
                    }
                }
            }
        }
        return $tree;
    }


    /**
     * 对查询结果集进行排序
     * @param   array  $list   查询结果
     * @param   string $field  排序的字段名
     * @param   array  $sortby 排序类型 asc正向排序 desc逆向排序 nat自然排序
     * @return  array
     */
    function list_sort_by($list,$field, $sortby='asc') {
       if(is_array($list)){
           $refer = $resultSet = array();
           foreach ($list as $i => $data)
               $refer[$i] = &$data[$field];
           switch ($sortby) {
               case 'asc': // 正向排序
                    asort($refer);
                    break;
               case 'desc':// 逆向排序
                    arsort($refer);
                    break;
               case 'nat': // 自然排序
                    natcasesort($refer);
                    break;
           }
           foreach ( $refer as $key=> $val)
               $resultSet[] = &$list[$key];
           return $resultSet;
       }
       return false;
    }


    /**
     * 对数组中的每个成员应用用户函数
     * @param  string  $filter 用户函数
     * @param  string  $data   待处理数组
     * @return array
     */
    function array_map_recursive($filter, $data) {
         $result = array();
         foreach ($data as $key => $val) {
             $result[$key] = is_array($val)? array_map_recursive($filter, $val): call_user_func($filter, $val);
         }
         return $result;
    }