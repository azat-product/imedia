<?php

/**
 * Файл конфигурации Sphinx
 * @param $path string
 * @param $prefix string
 * @param $sources array
 * @param $indexes array
 * @param $indexer array
 * @param $searchd array
 */

$params = function($section, $data) use ($prefix, $path) {
    if (isset($data[':extends'])) {
        unset($data[':extends']);
    }
    foreach ($data as $k=>$v) {
    if (is_array($v)) { foreach ($v as $vv) { ?>
    <?= $k ?> = <?= $vv ?>

<?php } } else { ?>
    <?= $k ?> = <?= $v ?>

<?php } }
};
?>

# SOURCES

<?php
foreach ($sources as $sourceName => $source)
{
?>
source <?= $prefix.$sourceName ?><?php if (!empty($source[':extends'])) { ?> : <?= $prefix.$source[':extends'] ?><?php } ?>

{
<?php $params('source', $source); ?>
}

<?php
}

?>

# INDEXES

<?php
foreach ($indexes as $indexName => $index)
{
?>
index <?= $prefix.$indexName ?><?php if (!empty($index[':extends'])) { ?> : <?= $prefix.$index[':extends'] ?><?php } ?>

{
<?php
    $index['source'] = $prefix.$index['source'];
    if ( ! isset($index['path'])) {
        $index['path'] = $path.$index['source'];
    }
    $params('index', $index);
?>
}

<?php
}

?>
<?php if (!empty($indexer)) { ?>

# INDEXER

indexer
{
<?php $params('indexer', $indexer); ?>
}
<?php } ?>
<?php if (!empty($searchd)) { ?>

# SEARCHD

searchd
{
<?php $params('searchd', $searchd); ?>
}
<?php }