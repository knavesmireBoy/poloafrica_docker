<?php
$parent = '';
$multi = false;
$section_attrs = '';
//dump($article->assets);

if (isset($article->assets) & isset($article->assets[0])) {
    if (count($article->assets) > 1) {
        $parent = classify('.multi');
        $multi = true;
    }
    if ($article->assets[0]->attr_id) {
        $parent = classify($article->assets[0]->attr_id, false);
        if ($multi && $parent) {
            $parent = preg_replace('/(=\w+)/', '$1&nbsp;multi', $parent);
        }
    }
}
if (!empty($article->attr_id)) {

    if (!empty($parent)) {
        //true returns id only ASSUMES child class USURPS any parent class
        //thinking portait/landscape; mobile/desktop
        $id = classify($article->attr_id, true);
        $section_attrs = "$id $parent";
    } else {
        $section_attrs = $article->attr_id;
    }
} else {
    $section_attrs = empty($section_attrs) ? $parent : $section_attrs;
}