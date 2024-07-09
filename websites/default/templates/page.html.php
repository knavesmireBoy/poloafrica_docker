<?php
include_once 'funcs.php';

foreach ($composite as $article) {
    if (!empty($article->getItem())) {
        $articles = $article->getItem();
        include '_multi_sections.html.php';
    } else {
        include '_single_section.html.php';
    }
}
