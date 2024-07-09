
<?php
include '_accordion.html.php';
//$article->summary contains RAW html
$myhead = preg_match('/^<h\d>.+/', $article->summary);
/*!!
TV coverage is the ONLY section that has a COMMON heading, the only one that supresses the output of the individual article title headings AND the only one that requires a COMMON trigger for NO-JS mobile scenario.
Is it a candidate for further ENCAPSULATION?.
It is essentially a LAYOUT issue and the individual articles should be ignorant of it. 
SO the summary field was inherited from a tutorial and we use it here as META information 
IDEALLY a seperate table mapping article id to header id would be required in the database,
OR a class of header may be on the asset->attr_id .header But then that would only be a clue to include a header template, so it's better we include the header in the database.
If the order of articles were to change or new articles added we would have to MANUALLY update
Given this is a one-off we resort to a bit of a kludge
*/

foreach ($article->assets as $k => $a) {
    if ($myhead) {
        echo $article->summary;
    }
}
include '_video_article.html.php';