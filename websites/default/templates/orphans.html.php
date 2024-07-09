<?php include_once 'funcs.php'; ?>
<h3>Manage Orphaned Pics</h3>
<?php

if (isset($group[0]) && isset($action)) { ?>

    <form action="<?= $action ?>/manage/<?= $id ?>" method="post" class="orphans">
        <ul class="adminlist">
            <?php
            foreach ($group as $item) {
                $path = $dir . $item->path;
                if (preg_match('/pdf$/', $item->path)) {
                    $path = doScanDir(ASSETS, $item->path);
                }
                if (file_exists($path)) :
            ?>
                    <li>
                        <input type="checkbox" name="pics[]" id=<?= $item->id ?> value=<?= $item->id ?>>
                        <label for=<?= $item->id ?> class="thumb" >
                            <img src="/<?= $path ?>" alt="" title="<?= $item->path; ?>">
                        </label>
                    </li>
            <?php endif;
            } ?>
            <ul>
                <li class="all trash"><label for="all">check all</label>
                    <input type="checkbox" name="all" id="all">
                </li>
                <li class="backup trash"><label for="backup">backup</label>
                    <input type="checkbox" name="backup" id="backup" title="check to remove BACKUP as well as LOCAL files">
                </li>
            </ul>
            <li class="submit"><input type="submit"></li>
        </ul>
    </form>
    <p class="replace">
        <?php if (empty($exit)) { ?>
            <a href="<?= BADMINTON ?>">Back to Admin</a>
        <?php } else { ?>
            <a href="<?= $exit['href'] ?>"><?= $exit['txt'] ?></a>
        <?php } ?>
    </p>

<?php } ?>