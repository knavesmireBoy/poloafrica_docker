<ul class="adminlist dual">
  <li><a href="<?= ASSET_ADD . $article_id ?>">Add Asset</a></li>
  <?php if (isset($files[0]->id)) {
    foreach ($files as $file) :
  ?>
      <li>
        <a href="<?= ASSET_EDIT ?><?= $file->id ?>" title="click to edit"><?= $file->path; ?></a>
        <form action="<?= $action .  $file->id . '/archive'; ?>" method="post">
          <input type="hidden" name="id" value="<?= $file->id; ?>">
          <input type="submit" value="archive">
        </form>
      </li>
      <li><a class="thumb pdf_file" href="<?= ASSET_EDIT ?><?= $file->id ?>" title="<?= $file->path; ?>">
          <img src="/<?= PDF_FILE; ?>">
        </a>
        <a class="trash" title="delete" href="<?= $routes['action'] .  $file->id . '/delete'; ?>">delete</a>
      </li>

  <?php endforeach;
  } ?>
  <li><a href="<?= $exit ?>">Exit</a></li>
</ul>