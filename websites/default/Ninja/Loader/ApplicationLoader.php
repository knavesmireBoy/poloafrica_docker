<?php

namespace Ninja\Loader;

use \PoloAfrica\Controllers\Uploader;
use \Ninja\Image;


class ApplicationLoader extends VideoLoader
{
    protected $extensions = ['pdf'];

    public function __construct(protected Uploader $controller, protected string $local, protected string $thumbs = '', protected $ratio = 0, protected $pp = '')
    {
    }

    private function seekPage($str, $path)
    {
        $x = strpos($str, $path);
        if (!$x) return '';
        $x -= 2;
        $y = $x;
        while (substr($str, $y, 1) !== '/') {
            $y--;
        }
        return substr($str, $y + 1, $x - $y);
    }

    private function validateSelection($str, $selection)
    {
        //allows for: "my [selection] is here"
        $pass = strpos($str, $selection);
        //if fail try bracketed text
        $tmp = preg_replace('/[\[\]]/', '', $selection);
        $i = $pass ? $pass : strpos($str, $tmp);

        if ($i) {
            $test = preg_replace('/\[[\w\s]+\]\[\d+\]/', '', $str); //exclude previous links
            $pass = strpos($test, $selection);
            $tmp = preg_replace('/[\[\]]/', '', $selection);
            $i = $pass ? $pass : strpos($test, $tmp);
            if (!$i) {
                return 'prelink';
            }
        }
        return intval($i);
    }

    private function resolvePath($pp, $path)
    {
        if (str_contains($this->dir, $pp)) {
            $mypath = $this->dir . $path;
        } else {
            $mypath = $this->dir . $pp . '/' . $path;
        }
        return $mypath;
    }
    protected function validateLink($values, $arg)
    {
        $first = preg_match('/upload/', $arg) && !empty($values['attr_id']);
        return $first || preg_match('/uploaded/', $arg);
    }

    protected function clearLink($linktext)
    {
        //deal with the 'n' first [my link text][n] ONLY numbers FOLLOWED by ]
        $linktext = preg_replace('/\d+(?=\])/', '', $linktext);
        return preg_replace('/[\[\]]/', '', $linktext); //then the square brackets
    }

    protected function getLinkIndex($str, $pathtofile)
    {
        $reg = "|(\d+)\]:\s*$pathtofile|";
        preg_match($reg, $str, $matches);
        return empty($matches) ? 0 : $matches[1];
    }
    protected function getLinkRefDetails($str, $pathtofile)
    {
        $x = strpos($str, $pathtofile);
        $reg = "|(\[\d+\]:\s*)$pathtofile|";
        preg_match($reg, $str, $matches);
        if (!empty($matches)) {
            $x -= strlen($matches[1]);
            return [$x, strlen($matches[0])];
        }
    }

    protected function findMatch($reg, $str)
    {
        preg_match($reg, $str, $matches);
        return empty($matches) ? '' : $matches[0];
    }

    protected function checkAttrs($str)
    {
        $title = $this->findMatch('/\s*"[^"]+"\s*/', $str);
        $reg = $title ? '/\{target=_blank\}[\n\r]*/' : '/\s*\{target=_blank\}[\n\r]*/';
        $tgt = $this->findMatch($reg, $str);
        list($a, $b) = array_map('strlen', [$title, $tgt]);
        return $a + $b;
    }

    protected function removeLinkHref($str, $pathtofile)
    {
        list($offset, $len) = $this->getLinkRefDetails($str, $pathtofile);
        $len += $this->checkAttrs(substr($str, $offset));
        if ($offset) {
            $linktofile = substr($str, $offset, $len);
            return str_replace($linktofile, '', $str);
        }
        return $str;
    }
    //breakLink CAN BE a stand alone request and may not have $this->dir or $this->pp set
    //whereas handleAsset and makeLink are part of the same request
    public function breakLink($str, $pp, $path, $attr_id)
    {
        //GIVEN a LIST item we could delete the item on removal of link, but let us leave that to the discretion of the editor
        $pathtofile = $this->pp ? $this->dir . $path : $this->local . $pp . '/' . $path;
        $i = $this->getLinkIndex($str, $pathtofile); //[n]
        if (!$i) {
            $pathtofile = "/$pathtofile";
        }
        $i = $this->getLinkIndex($str, $pathtofile);
     
        if ($i) {
            $str = $this->removeLinkHref($str, $pathtofile);
           
            $index = "][$i]";
            $j = strpos($str, $index);
            
            //not guaranteed to have link text [my link][3] even though there may be a reference to it:
            // [3]: path/to/file.pdf
            if ($j && $attr_id) {
                //could use regex below to find linktext but need access to $j anyway
                //preg_match("/\[[\w\s]+\]\[$i\]/", $str, $matches);
                $k = $j;
                while (substr($str, $j, 1) !== '[') {
                    $j--;
                }
                $i = ($k - $j) + strlen($index);
                $linktext = substr($str, $j, $i); //eg [selected text][11]
                $pre = substr($str, 0, $j);
                $post = substr($str, $j + $i);
                //deal with the 'n' first [my link text][n]
                $orig = $this->clearLink($linktext);
                return $pre . $orig . rtrim($post);
            }
        }
        return $str;
    }

    protected function checkAvailableIndex($str, $max, $inc = 0)
    {
        $range = range(1, $max, 1);
        preg_match_all('/(?<=\[)\d+(?=\]:)/', $str, $refs);
        $refs = $refs[0] ?? [];
        $refs = array_map('intval', $refs);
        $i = 0;
        $pass = true;
        sort($refs); //crucial
        while ($pass) {
            if (!empty($refs[$i]) && !empty($range[$i])) {
                if (($refs[$i] !== $range[$i])) {
                    $pass = false;
                    break;
                }
                $i++;
            } else {
                break;
            }
        }
        if (empty($range[$i])) {
            return $max + $inc;
        } else {
            $i = $range[$i];
            preg_match("/\[[^\]]+\]\[$i\]/", $str, $ref);
            $ref = !empty($ref) ? $ref[0] : null;
            //check if we don't have a redundant link
            if ($ref) {
                return [$i, "/\[[^\]]+\]\[$i\]/", $ref];
            }
            return $i;
        }
    }
    /*
    priority for NEW index
    a) unused index [redundant link][6]
    b) availableIndex (current indexes 1,2,3,6) = 4
    c) lastIndex (1,2,3,4,5) = 5
*/
    private function getIndex($str, $flag = false)
    {
        if ($flag) {
            preg_match_all('/\[(\d+)\]\s?:/', $str, $match); //get int
            $res = array_map(fn ($o) => $o, $match)[1];
            return max(array_map('intval', $res));
        }
        //pos look(behind/ahead)
        preg_match_all('/(?<=\]\[)\d+(?=\])/', $str, $copy);
        preg_match_all('/(?<=\[)\d+(?=\]:)/', $str, $refs);
        $res = array_diff($copy[0], $refs[0]);
        if (!empty($res)) {
            return end($res);
        }
        return null;
    }

    private function qualifyingSelection($str, $selection)
    {
        $res = preg_match('/\s/', $selection);
        $res = $res ? $res : preg_match('/\-\s[\w\s]+(?!-)/', $selection);
        if (!$res) {
            //allow for list item but not bracketed copy..
            // - itemA
            // - itemB
            //The three female characters — the wife, the nun, and the jockey — are the incarnation of excellence.
            $k = strpos($str, $selection);
            $l = strlen($selection);
            //MASSIVE ASSUMPTION that all items in a list conform to hypen single space "- Hello"
            $tmp = substr($str, $k - 2, $l + 2);
            $res = preg_match('/(?<=-)\s\w+\s*(?!-)/', $tmp);
            if ($res) {
                return substr($tmp, 2) === $selection;
            }
        }
        return $res;
    }

    public function makeLink($str, $path, $title = '', $selection = '')
    {
        $j = 0;
        $pp = $this->pp ? $this->pp : $this->seekPage($str, $path);
        $pathtofile = $this->resolvePath($pp, $path);
        if ($this->checkMimeType($pathtofile)) {
            $availableIndex = $this->getIndex($str);
            $max = $this->getIndex($str, true);
            $index = $availableIndex ?? $max; //$flag get max
            $linkref = strpos($str, $pathtofile);
            $linkIndex = $linkref ? $this->getLinkIndex($str, $pathtofile) : null;
            $j = $this->validateSelection($str, $selection);

            $updateCopy = $selection && is_integer($j);
            $updateRef = !$linkref && empty($selection);

            if ($updateRef && !$availableIndex) {
                return [$str, 'pdf'];
            }
            //attempt to UPDATE the link text for the SAME NAMED FILE
            if ($linkref || $updateCopy || $updateRef) {
                $str = $this->breakLink($str, $pp, $path, $selection);
                $index = $linkIndex ?? $index;
            }
            //$availableIndex ONLY used when no copy provided, ie replacing the FILE the LINK refers to

            if ($updateCopy) { //new body copy
                if (!$linkIndex) {
                    $res = $this->checkAvailableIndex($str, $max, 1);
                    //clear copy of EXISTING link
                    //where we have [6]: /path/to/file, BUT "this was [my link][6] innit" so clearLink "this was my link innit"
                    if (!is_numeric($res)) {
                        $index = $res[0];
                        $orig = $this->clearLink($res[2]);
                        $str = preg_replace($res[1], $orig, $str);
                    } else {
                        $index = $res;
                    }
                } else {
                    $index = $linkIndex;
                }

                if ($this->qualifyingSelection($str, $selection)) {
                    if (preg_match('/\-\s[\w\s]+(?!-)/', $selection)) {
                        $str = preg_replace('|\n(?=<\/nav>)|', "\n$selection\n", $str);
                        $selection = substr($selection, 2);
                    }
                    if (preg_match('/(\[\w+\])/', $selection)) {
                        $myindex = "[$index]";
                        $selection = preg_replace('/(\[\w+\])/', "$1$myindex", $selection);
                        $orig = preg_replace('/[\[\]]/', '', $_POST['attr_id']);
                        $str = str_replace($orig, $selection, $str);
                    } else {
                        $myselection = "[$selection]";
                        $myselection .= "[$index]";
                        $str = str_replace($selection, $myselection, $str);
                    }
                } //qualify
            }

            $t = $title ? '"' . $title . '"' : '';
            if ($index && $this->getIndex($str)) {
                $ref = "[$index]: $pathtofile $t{target=_blank}";
                $a = "$str$ref";
                $b = "$str\n$ref";
                $n = substr($str, -1) == "\n" ? $a : $b;
                return [$n, $j];
            }
        }
        return [$str, $j];
    }

    public function exit($id, $record, $flag = true)
    {
        if ($flag) {
            $record['attr_id'] = '';
            $record['id'] = $id; //make sure we UPDATE
            return $record;
        }
        return null;
    }
}
