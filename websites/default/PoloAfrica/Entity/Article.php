<?php

namespace PoloAfrica\Entity;

use \Ninja\Composite\Leaf;

class Article extends Leaf
{
    public $id;
    public $pubDate;
    public $title;
    public $summary;
    public $attr_id;
    public $page;
    public $content;
    public $mdcontent;
    public $assets = [];
    private $image = array(
        '.gif',
        '.jpg',
        '.jpeg',
        '.pjpeg',
        '.png',
        '.x-png'
    );
    private $video = array(
        '.mp4',
        '.avi'
    );

    public function __construct(private \Ninja\DatabaseTable $assetTable, private \Ninja\DatabaseTable $slotTable)
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

    private function validatepath($needle, $haystack)
    {
        return in_array(strrchr($needle, '.'), $haystack);
    }

    public function getAssets($article_id = 0, $cb = null)
    {
        $assetids = array_map(fn ($article) => $article->id, $this->assetTable->find('article_id', $article_id));
        $myassets = [];
        foreach ($assetids as $id) {
            $myassets[] = $this->fetch('assetTable', 'id', $id);
        }
        //filter out pdf's which are for reference not display
        if (!$cb) {
            $cb =  fn ($item) => $this->validatepath($item->path, $this->image) || $this->validatepath($item->path, $this->video);
        }
        $ret = array_values(array_filter($myassets, $cb));
        return $ret;
    }

    private function getPaths($paths)
    {
        $pp = $this->page;
        $lib = [
            'pdf' => "resources/assets/$pp/",
            'video' => "resources/video/$pp",
            'img' => "resources/images/articles/fullsize"
        ];
    }

    public function archiveAssets($article_id, $cb = null)
    {
        $assetIds = array_map(fn ($o) => $o->id, $this->getAssets($article_id, $cb));
        foreach ($assetIds as $id) {
            $record = $this->assetTable->find('id', $id, null, 0, 0, \PDO::FETCH_ASSOC);
            if (!empty($record[0])) {
                $record = $record[0];
                $record['article_id'] = NULL;
                $this->assetTable->save($record);
            }
        }
    }

    public function delete($id, $cb = null)
    {
        $res = $this->assetTable->find('id', $id);
        $assets = $this->getAssets($id, $cb);
        $paths = [];
        foreach ($assets as $asset) {
            $paths[] = $asset->path;
            $this->assetTable->delete('id', $asset->id);
        }
        return $paths;
    }

    public function isSingleAsset($article_id)
    {
        $assets = $this->assetTable->find('article_id', $article_id, null, 0, 0, \PDO::FETCH_ASSOC);
        $multi = count($assets) > 1;
        return $multi ? null : $assets[0]['id'] ?? null;
    }

    public function getArchived()
    {
        return $this->assetTable->find('article_id', null, 'path', 0, 0, \PDO::FETCH_ASSOC, ' IS NULL');
    }


    public function getOddEven($title)
    {
        $record = $this->slotTable->find('title', $title);
        if (isset($record)) {
            return  $record[0]->id % 2 ? 'odd' : 'even';
        }
        return '';
    }

    public function getSlotEntity()
    {
        return $this->slotTable->getEntity();
    }

    public function findAll(...$args)
    {
        return $this->slotTable->findAll(...$args);
    }

    public function setName($name)
    {
        return $this->slotTable->setName(strtolower($name));
    }

    public function getName()
    {
        return $this->slotTable->getName();
    }

    public function repop(...$args)
    {
        $slot = $this->getSlotEntity();
        $slot->repop(...$args);
    }
    public function swap(...$args)
    {
        $slot = $this->getSlotEntity();
        return $slot->swap(...$args);
    }

    public function shuffle(...$args)
    {
        $slot = $this->getSlotEntity();
        return $slot->shuffle(...$args);
    }

    public function trigger(...$args)
    {
        $slot = $this->getSlotEntity();
        return $slot->trigger(...$args);
    }
}
