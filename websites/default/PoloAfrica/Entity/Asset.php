<?php

namespace PoloAfrica\Entity;

class Asset
{
  public $attr_id;
  public $id;
  public $alt;
  public $path;
  public $article_id;
  public $date;

  public function __construct(private \Ninja\DatabaseTable $table, private \Ninja\DatabaseTable $articleTable)
  {
  }

  protected function fetch($t, $prop, $val, ...$rest)
  {
    $ret = [];
    if ($val) { //safeguard against missing values
      $ret = $this->{$t}->find($prop, $val, ...$rest);
    }
    return empty($ret) ? null : $ret[0];
  }
  public function getArticle($id = 0, $prop = null)
  {
    $article = $this->fetch('table', 'id', $id);
    $id = $article ? $article->article_id : null;
    if ($id) {
      $article = $this->fetch('articleTable', 'id', $id);
      if (!empty($article)) {
        return $prop ? $article->{$prop} : $article;
      }
      return null;
    }
  }
  public function getArticleDirect($id = 0, $prop = null)
  {
    $article = $this->fetch('articleTable', 'id', $id);
    if (!empty($article)) {
      return $prop ? $article->{$prop} : $article;
    }
  }


  public function validate($articleId, $assetId, $regX, $flag = false)
  {
    $res = $this->getArticleDirect($articleId, 'page');
    $articles = $this->articleTable->find('page', $res);
    $articles = array_filter($articles, fn ($o) => preg_match($regX, $o->attr_id));
    $articleIds = array_map(fn ($o) => $o->id, $articles);
    $ret = [];
    foreach ($articleIds as $i) {
      $res = $this->fetch('table', 'article_id', $i);
      if ($res && !$flag) {
        $ret[] = $res->id;
      } else if ($res && $flag && $res->id != $assetId) {
        $ret[] = $res->id;
      }
    }
    return empty($ret) || in_array($assetId, $ret);
  }

  public function setContent($str)
  {
    if (isset($str)) {
      $id = $this->getArticle($this->id, 'id');
      //!! path to file (PDF) needs a leading slash for ROUTING purposes BUT MAY fail the file_exists() test
      //fix it when saving the article; ensure leading slash does not already exist NOTE USING # for delimiter not / to avoid confusion
      $str = preg_replace('#(?<!\/)resources\/assets#', '/resources/assets', $str);
      $values = ['id' => $id, 'content' => trim($str)];
      $this->articleTable->save($values);
    }
  }

  public function dumpy()
  {
    dump('dumpy');
  }
}
