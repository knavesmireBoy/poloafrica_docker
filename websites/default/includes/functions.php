<?php
function invoke($func)
{
    return function ($arg) use ($func) {
        return $func($arg);
    };
}

function lazyRest($func, $b)
{
    return function ($a, $c) use ($func, $b) {
        if (is_callable($func)) {
            call_user_func($func, $a, $b, $c);
        }
    };
}


function lazyMiddle($func, $a, $c)
{
    return function ($b) use ($func, $a, $c) {
        if (is_callable($func)) {
            call_user_func($func, $a, $b, $c);
        }
    };
}

function curry2($fun)
{
    return function ($arg2) use ($fun) {
        return function ($arg1) use ($fun, $arg2) {
            return $fun($arg1, $arg2);
        };
    };
}

function curry2L($fun)
{
    return function ($arg1) use ($fun) {
        return function ($arg2) use ($fun, $arg1) {
            return $fun($arg1, $arg2);
        };
    };
}


//https://eddmann.com/posts/using-partial-application-in-php/
function partial( /* $func, $args... */)
{
    $args = func_get_args();
    $func = array_shift($args);
    return function () use ($func, $args) {
        return call_user_func_array($func, array_merge($args, func_get_args()));
    };
}

function deco($fn)
{
    return function () use ($fn) {
        return call_user_func_array($fn, func_get_args());
    };
}
function dump($a)
{
    var_dump($a);
    exit;
}

function doEcho($arg)
{
    echo $arg;
}

function greaterThan($a, $b)
{
    return $a > $b;
}

function lesserThan($a, $b)
{
    return $a < $b;
}

function divideBy($divisor)
{
    return function ($dividend) use ($divisor) {
        return $dividend / $divisor;
    };
}

function multiplyBy($multiplier)
{
    return function ($multiplicand) use ($multiplier) {
        return $multiplicand * $multiplier;
    };
}

function divide($dividend, $divisor)
{
    return $dividend / $divisor;
}

function minus($a)
{
    return function ($b) use ($a) {
        return $a - $b;
    };
}

function add($a)
{
    return function ($b) use ($a) {
        return $a + $b;
    };
}
function equals($a, $b)
{
    return $a === $b;
}

function toObject($o, $arg = false)
{
    return json_decode(json_encode($o), $arg);
}

function doEncode($str)
{
    return urlencode(strtolower($str));
}

function prepID($str)
{
    return strtolower(str_replace(' ', '_', $str));
}

function prepPoloTitles($str = 'home')
{
    $list = ['admin', 'home', 'polo in africa', 'trust', 'scholars', 'place', 'stay', 'polo', 'medley', 'enquiries', 'photos'];
    $res = explode(' ', strtolower($str));
    $home = isset($res[2]) && $res[2] === 'africa';
    if ($home) {
        return 'home';
    }
    $fail = isset($res[2]) ? true : false;
    $res = $res[1] ?? $res[0];
    // "Your Stay","The Place" => stay, place
    $t = $fail ? 'lost' : $res;
    if (in_array($res, $list)) {
        return $t;
    }
    return $res;
}

function prepTitle($str)
{
    return ucwords(str_replace('_', ' ', $str));
}

function checkLower($str)
{
    return $str === strtolower($str);
}

function beautify($txt)
{
    return ucwords(strtolower(str_replace('_', ' ', $txt)));
}

function exclaim($msg, $char = '!')
{
    $msg = urldecode($msg);
    if (substr($msg, 0, 1) === $char) {
        $msg = ucfirst(substr($msg, 1));
    } else {
        $msg = '';
    }
    return is_numeric($msg) ? '' : $msg;
}

function abbr($name)
{
    preg_match_all('/\b\w/u', $name, $abbreviatedName);
    return implode("", $abbreviatedName[0]);
}


function beautify2($txt)
{
    $txt = strtolower(str_replace('_', ' ', $txt));
    $txt = $txt !== 'id' ? ucwords($txt) : $txt;
    if (strpos($txt, "B")) {
        $txt = abbr($txt);
    }
    return $txt;
}

function split($data, $not)
{
    $ret = [];
    $sale = [];
    $alp = [];
    $pair = [];
    foreach ($data as $d) {
        foreach ($d as $k => $v) {
            if ($k === strtolower($k)) {
                $sale[$k] = $v;
            } else {
                $alp[$k] = $v;
            }
        }
        $pair[] = $sale;
        $pair[] = $alp;
        //$tmp = $pair;
        $ret[] = $pair;
    }
    return $ret;
}

function html($str)
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function htmlout($str)
{
    echo html($str);
}

function toHtml($str)
{
    // convert $this->string to HTML
    $text = htmlspecialchars($str, ENT_QUOTES, 'UTF-8');

    // strong (bold)
    $text = preg_replace('/__(.+?)__/s', '<strong>$1</strong>', $text);
    $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text);

    // emphasis (italic)
    $text = preg_replace('/_([^_]+)_/', '<em>$1</em>', $text);
    $text = preg_replace('/\*([^\*]+)\*/', '<em>$1</em>', $text);

    // Convert Windows (\r\n) to Unix (\n)
    $text = str_replace("\r\n", "\n", $text);
    // Convert Macintosh (\r) to Unix (\n)
    $text = str_replace("\r", "\n", $text);
    /*
        // Paragraphs
        $text = '<p>' . str_replace("\n\n", '</p><p>', $text) . '</p>';
        // Line breaks
        $text = str_replace("\n", '<br>', $text);
*/
    // [linked text](link URL)

    $text = preg_replace('/\[([^\]]+)]\(([-a-z0-9._~:\/?#@!$&\'()*+,;=%]+)\)/i', '<a href="$2">$1</a>',  $text);
    return $text;
}

function single_space($value)
{
    return preg_match('/^\s$/', $value);
}
function identity($arg)
{
    return $arg;
}

function spam_scrubber($value)
{
    //usage: $scrubbed = array_map('spam_scrubber', $data($_POST));
    // List of very bad values:
    $very_bad = array(
        'to:',
        'cc:',
        'bcc:',
        'content-type:',
        'mime-version:',
        'multipart-mixed:',
        'content-transfer-encoding:'
    );
    if (is_array($value)) {
        foreach ($value as $v) {
            return spam_scrubber($v);
        }
    }
    // If any of the very bad strings are in
    // the submitted value, return an empty string:
    foreach ($very_bad as $v) {
        if (stripos($value, $v) !== false) {
            return ' ';
        }
    }

    // Replace any newline characters with spaces:
    $value = str_replace(array(
        "\r",
        "\n",
        "%0a",
        "%0d",
        "\t",
        "%08",
        "%09"
    ), ' ', $value);
    //$value = str_replace(array('Fucking', 'fucking', 'fuck', 'wank', 'cunt'), '***', $value);
    // Return the value:
    return trim($value);
} // End of spam_scrubber() function.

function buildMessage($k, $v, $flag)
{
    $ret = $flag ? "" : "\r\n\r\n"; //!MUST BE DOUBLE QUOTES!
    $str = ucfirst($k) . ': ' . $v;
    return $str . $ret;
}

function stringMin($v)
{
    return strlen($v) > 15;
}

function stringMax($v)
{
    return strlen($v) < 1000;
}

function getDiff(array $a, array $b)
{
    if (count($a) >= count($b)) {
        return array_diff($a, $b);
    }
    return array_filter(array_diff($b, $a), fn ($o) => $o);
}

function inMyArray($needle, $haystack) {

    $cb = function($n) {
        return function($agg, $cur) use($n){
            return $agg ? $agg : preg_match("/^$n$/i", $cur);
        };
    };
    return array_reduce($haystack, $cb($needle));
}

function pluck($haystack, $n = 0){

    if(!$n){
        $n = reset($haystack);
        return [$n];
    }
    if(is_bool($n)){
        $n = end($haystack);
        return [$n];
    }
    return $haystack[$n] ?? null;

}

function preconditions()
{
    $checkers = func_get_args();
    return function ($strategy, $value) use ($checkers) {
        $errors = array_reduce(array_map(
            function ($checker) use ($strategy, $value) {
                return $checker->validate($value) ? array() : array(
                    $checker->message
                );
            },
            $checkers
        ), 'array_merge', array());
        if (!empty($errors)) {
            return $errors;
        }
        //return $strategy->algorithm($value);
        return $strategy($value);
    };
}

//https://stackoverflow.com/questions/25105796/php-add-value-to-a-existing-query-string
function setQueryString($url, $key, $val)
{
    $pUrl = parse_url($url);
    if (isset($pUrl['query'])) parse_str($pUrl['query'], $pUrl['query']);
    else $pUrl['query'] = [];
    $pUrl['query'][$key] = $val;

    $scheme = isset($pUrl['scheme']) ? $pUrl['scheme'] . '://' : '';
    $host = isset($pUrl['host']) ? $pUrl['host'] : '';
    $path = isset($pUrl['path']) ? $pUrl['path'] : '';
    $path = count($pUrl['query']) > 0 ? $path . '?' : $path;
    return $scheme . $host . $path . http_build_query($pUrl['query']);
}


function path($f, $d = '/templates/')
{
    $path = strrchr(__DIR__, '/');
    include __DIR__ . "../../../websites$path$d$f";
}

function loadTemplate($templateFileName, $variables)
{
    extract($variables);
    ob_start();
    include TEMPLATE . $templateFileName;
    return ob_get_clean();
}

function checkUri($uri)
{
    if ($uri != strtolower($uri)) {
        http_response_code(301);
        header('Location: ' . strtolower($uri));
    }
}

function startSession()
{
    if (!isset($_SESSION)) {
        session_start();
    }
}

function findfile($dir, $path, $i)
{
    $ret = file_exists($dir . $path);
    if (!$ret && $i && preg_match('/\.jpe?g$/i', $path)) {
        $ext = stristr($path, '.');
        $pth = strstr($path, '.', true);
        if ((strlen($ext) === 4) && $i) {
            return findfile($dir, $pth . '.jpeg', $i -= 1);
            // $ret = file_exists($dir . $pth . '.jpeg');
        } else if ($i) {
            // $ret = file_exists($dir . $pth . '.jpg');
            return findfile($dir, $pth . '.jpg', $i -= 1);
        }
    } else if ($ret) {
        return $path;
    }
    return null;
}

function findMatch($reg, $str, $flag = false)
{
    if ($flag) {
        preg_match_all($reg, $str, $matches);
    } else {
        preg_match($reg, $str, $matches);
    }
    return empty($matches) ? '' : $matches[0];
}

function validate_extension($needle, $haystack)
{
    $n = strtolower(strrchr($needle, '.'));
    $h = array_map('strtolower', $haystack);
    return in_array($n, $h);
}

function swapExtension($path, $ext, $sep = '') {

    if($sep){
        $path = explode('/', $path);
        $path = end($path);
    }
    return preg_replace('|\.\w+$|', $ext, $path);
}


//https://www.php.net/manual/en/function.getimagesize.php
// Retrieve JPEG width and height without downloading/reading entire image.
function getjpegsize($img_loc)
{
    $img_loc = trim($img_loc);
    if (file_exists($img_loc)) {
        $handle = fopen($img_loc, "rb");
        $new_block = NULL;
        if (!feof($handle)) {
            $new_block = fread($handle, 32);
            $i = 0;
            if ($new_block[$i] == "\xFF" && $new_block[$i + 1] == "\xD8" && $new_block[$i + 2] == "\xFF" && $new_block[$i + 3] == "\xE0") {
                $i += 4;
                if ($new_block[$i + 2] == "\x4A" && $new_block[$i + 3] == "\x46" && $new_block[$i + 4] == "\x49" && $new_block[$i + 5] == "\x46" && $new_block[$i + 6] == "\x00") {
                    // Read block size and skip ahead to begin cycling through blocks in search of SOF marker
                    $block_size = unpack("H*", $new_block[$i] . $new_block[$i + 1]);
                    $block_size = hexdec($block_size[1]);
                    while (!feof($handle)) {
                        $i += $block_size;
                        $new_block .= fread($handle, $block_size);
                        if ($new_block[$i] == "\xFF") {
                            // New block detected, check for SOF marker
                            $sof_marker = array("\xC0", "\xC1", "\xC2", "\xC3", "\xC5", "\xC6", "\xC7", "\xC8", "\xC9", "\xCA", "\xCB", "\xCD", "\xCE", "\xCF");
                            if (in_array($new_block[$i + 1], $sof_marker)) {
                                // SOF marker detected. Width and height information is contained in bytes 4-7 after this byte.
                                $size_data = $new_block[$i + 2] . $new_block[$i + 3] . $new_block[$i + 4] . $new_block[$i + 5] . $new_block[$i + 6] . $new_block[$i + 7] . $new_block[$i + 8];
                                $unpacked = unpack("H*", $size_data);
                                $unpacked = $unpacked[1];
                                $height = hexdec($unpacked[6] . $unpacked[7] . $unpacked[8] . $unpacked[9]);
                                $width = hexdec($unpacked[10] . $unpacked[11] . $unpacked[12] . $unpacked[13]);
                                return array($width, $height);
                            } else {
                                // Skip block marker and read block size
                                $i += 2;
                                $block_size = unpack("H*", $new_block[$i] . $new_block[$i + 1]);
                                $block_size = hexdec($block_size[1]);
                            }
                        } else {
                            return FALSE;
                        }
                    }
                }
            }
        }
    } //fof
    return FALSE;
}
// Retrieve PNG width and height without downloading/reading entire image.
function getpngsize($img_loc)
{
    $handle = fopen($img_loc, "rb") or die("Invalid file stream.");

    if (!feof($handle)) {
        $new_block = fread($handle, 24);
        if (
            $new_block[0] == "\x89" &&
            $new_block[1] == "\x50" &&
            $new_block[2] == "\x4E" &&
            $new_block[3] == "\x47" &&
            $new_block[4] == "\x0D" &&
            $new_block[5] == "\x0A" &&
            $new_block[6] == "\x1A" &&
            $new_block[7] == "\x0A"
        ) {
            if ($new_block[12] . $new_block[13] . $new_block[14] . $new_block[15] === "\x49\x48\x44\x52") {
                $width  = unpack('H*', $new_block[16] . $new_block[17] . $new_block[18] . $new_block[19]);
                $width  = hexdec($width[1]);
                $height = unpack('H*', $new_block[20] . $new_block[21] . $new_block[22] . $new_block[23]);
                $height  = hexdec($height[1]);
                return array($width, $height);
            }
        }
    }
    return false;
}

//makes some assumptions, a warning is in an array, a string is ok
function flushMsg($missing, $data, $klas = 'warning')
{
    return function ($k, $flag = false, $ret = false) use ($missing, $data, $klas) {
        $output = isset($missing[$k]) && is_array($missing[$k]) ? $missing[$k][0] : null;
        if (isset($output)) {
            //we can be adding to value field of input, or class of label
            //assumes no other class present
            if (!$ret) { //default is echo
                echo $flag ? $output : " class=$klas";
            } //but echo may be delegated so just return
            else {
                return $flag ? $output : " class=$klas";
            }
        } else {
            htmlout(trim(strip_tags($data[$k]))); //outputs

        }
    };
}

function flushMsgCb($missing, $data, $klas = 'warning')
{
    return function ($k, $flag = false) use ($missing, $data, $klas) {
        $output = isset($missing[$k]) && is_array($missing[$k]) ? $missing[$k][0] : null;
        if (isset($output)) {
            return $flag ? $output : " class=$klas";
        } else {
            htmlout(trim(strip_tags($data[$k]))); //outputs

        }
    };
}


function splitOn($str, $seps = [' ', '/'])
{
    $L = count($seps);
    $res = null;
    $ret = null;
    while ($L--) {
        $res = explode($seps[$L], $str);
        if (isset($res[1])) {  
            $ret = array_map(function($o){
               return !is_numeric($o) ? intval($o) : $o;
            }, $res);
            break;
        }
    }
    return $ret;
}


function getAccess($i)
{
    $lib = [null, 'Content Editors', 'Photo Editors'];
    return isset($lib[$i]) ? $lib[$i] : 'Account Administrators';
}

function getPeers($p, $b = 64)
{
    $test = ($p % $b) === $p;
    if ($test) {
        $b /= 2;
        return getPeers($p, $b);
    } else {
        return $b;
    }
}

function trimToLower($str)
{
    return strtolower(trim($str));
}

function reLocate($path, $prefix = '')
{
    header('Location: ' . $path);
    exit;
}

function retour()
{
    header('Location: /');
    exit;
}

function fixUri()
{
    $uri = strtok(ltrim($_SERVER['REQUEST_URI'], '/'), '?');
    $route = explode('/', $uri);
    array_unique($route);
    return $route;
}

function doWhen($predicate, $action)
{
    return function (...$args) use ($predicate, $action) {
        if ($predicate(...$args)) {
            return $action(...$args);
        }
    };
}
function classify($str, $flag = null)
{
    $res = explode('.', $str);
    $ret = 'id=%s class=%s';
    $false = !$flag && is_bool($flag);
    //taking of the piss '.' yields ["",""];
    if (empty($res[0]) && (empty($res[1]) || $flag)) {
        return '';
    }

    if (empty($res[0]) || $false) { //class NO id
        $k = sprintf('class=%s', $res[1] ?? '');
        return preg_match('/=\w+/', $k) ? $k : '';
    }

    if (empty($res[1]) || $flag) {
        $id = sprintf('id=%s', $res[0] ?? '');
        return preg_match('/=\w+/', $id) ? $id : '';
    }
    return sprintf($ret, $res[0], $res[1]);
}

function drive($end, &$van, &$places, $i)
{
    //$i === 'destination';
    //$places [a,b,c,d...]
    //$van [a||b||c||d...]; firstLabel
    if (!count($van)) return;
    $nextLabel = $places[$i];
    if ($end) {
        $places[$i] = array_shift($van);
        return drive($end, $van, $places, $i - 1);
    } else if (empty($places[$i + 1])) {
        $places[$i + 1] = $nextLabel;
        $end = true;
        return drive($end, $van, $places, $i);
    } else {
        array_unshift($van, $nextLabel);
        $places[$i] = null;
        return drive($end, $van, $places, $i + 1);
    }
}

function negate($predicate)
{
    return function ($arg) use ($predicate) {
        return !$predicate($arg);
    };
}
function alternate($predicate)
{
    return function ($arg) use (&$predicate) {
        $i = $predicate($arg);
        if (!$i) {
            $predicate = negate($predicate);
        }
        return $i;
    };
}

//https://medium.com/@assertchris/function-composition-c8094ae9be63
$reduce = function ($result, $item) {
    return !is_array($result) ? call_user_func_array($item, [$result]) : call_user_func_array($item, $result);
};

$compose = function () use ($reduce) {
    $callbacks = func_get_args();
    return function () use ($callbacks, $reduce) {
        return array_reduce($callbacks, $reduce, func_get_args());
    };
};


function myReducer($agg, $f)
{
    $agg = !is_array($agg) ? array($agg) : $agg;
    return call_user_func_array($f, $agg);
}

function compose($reducer)
{
    return function () use ($reducer) {
        $callbacks = func_get_args();
        return function () use ($callbacks, $reducer) {
            return array_reduce($callbacks, $reducer, func_get_args());
        };
    };
}

function unsetCookie($str)
{
    unset($_COOKIE[$str]);
    setcookie($str, '', -1, '/');
}

function doInclude($content, $template = '_image_article.html.php')
{
    if (preg_match('/\w+\.html\.php$/', $content)) {
        return $content;
    }
    return $template;
}

function formatError($e)
{
    $ret = implode(preg_split('/SQLSTATE\[[^:]+:/', $e));
    return implode(preg_split('/:\s\d+/', $ret));
}

function doPreparedQuery($st, $values = [], $msg = '')
{
    try {
        $bool = $st->execute($values);
        // dump(is_bool($bool));
        return $bool && is_bool($bool) ? $st : null;
    } catch (PDOException $e) {
        $error = $msg . ' ' . $e->getMessage();
        $error = formatError($error);
        return $error;
    }
}


function orderByList($data, $list, $a, $b)
{
    function foo(&$coll, $key)
    {
        return function ($o) use (&$coll, $key) {
            return $coll[$o[$key]] = $o;
        };
    }

    $assoc = [];
    $foo = foo($assoc, $a);
    foreach ($data as $d) {
        $foo($d);
    }
    $coll = [];
    foreach ($list as $k) {
        //var_dump($k);
       // if (isset($coll[$k])) {
            $coll[$k] = $assoc[$k][$b];
       // }
    }
    return $coll;
}
