<div style="float: left" id="tutorBreadcrumb">
    <?= _('Sie befinden sich hier:') ?>
    <span style="font-weight: bold">
    <? if ($section == 'search') : ?>
        <a href="<?= PluginEngine::getURL('coreforum/index/goto_page/'. $topic_id .'/'. $section 
            .'/1/?searchfor=' . $searchfor . (!empty($options) ? '&'. http_build_query($options) : '' )) ?>">
            <?= _('Suchergebnisse') ?>
        </a>
    <? elseif ($section == 'newest') : ?>
        <a href="<?= PluginEngine::getURL('coreforum/index/newest') ?>">
            <?= _('Neuste Beiträge') ?>
        </a>
    <? elseif ($section == 'latest') : ?>
        <a href="<?= PluginEngine::getURL('coreforum/index/latest') ?>">
            <?= _('Letzte Beiträge') ?>
        </a>
    <? elseif ($section == 'favorites') : ?>
        <a href="<?= PluginEngine::getURL('coreforum/index/favorites') ?>">
            <?= _('Gemerkte Beiträge') ?>
        </a>        
    <? else: ?>

        <? $first = true ?>
        <? foreach (ForumEntry::getPathToPosting($topic_id) as $path_part) : ?>
            <? if (!$first) : ?> &gt;&gt; <? endif ?>
            <a href="<?= PluginEngine::getLink('coreforum/index/index/' . $path_part['id']) ?>">
                <?= htmlReady(ForumEntry::killFormat($path_part['name'])) ?>
            </a>
            <? $first = false ?>
        <? endforeach ?>
    <? endif ?>
    </span>        
</div>
