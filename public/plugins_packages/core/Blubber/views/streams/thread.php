<?php

/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 * 
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */

$last_visit = object_get_visit($_SESSION['SessionSeminar'], "forum");
BlubberPosting::$course_hashes = ($thread['context_type'] === "course" ? $thread['Seminar_id'] : false);
$related_users = $thread['context_type'] === "private" ? $thread->getRelatedUsers() : array();
$author = $thread->getUser();
$author_name = $author->getName();
$author_url = $author->getURL();
$commentable = $GLOBALS['perm']->have_perm("autor") ? true : (bool) $commentable;
?>
<? if (@$single_thread): ?>
<input type="hidden" id="base_url" value="plugins.php/blubber/streams/">
<input type="hidden" id="context_id" value="<?= htmlReady($thread->getId()) ?>">
<input type="hidden" id="stream" value="thread">
<input type="hidden" id="last_check" value="<?= time() ?>">
<input type="hidden" id="user_id" value="<?= htmlReady($GLOBALS['user']->id) ?>">
<input type="hidden" id="stream_time" value="<?= time() ?>">
<input type="hidden" id="browser_start_time" value="">
<script>jQuery(function () { jQuery("#browser_start_time").val(Math.floor(new Date().getTime() / 1000)); });</script>
<div id="editing_question" style="display: none;"><?= _("Wollen Sie den Beitrag wirklich bearbeiten?") ?></div>
<p>
    <? switch ($thread['context_type']) {
        case "course":
            $overview_url = URLHelper::getURL("plugins.php/blubber/streams/forum", array('cid' => $thread['Seminar_id']));
            break;
        case "public":
            $overview_url = URLHelper::getURL("plugins.php/blubber/streams/profile", array('user_id' => $thread['user_id'], 'extern' => $thread['external_contact'] ? $thread['external_contact'] : null));
            break;
        default: 
            $overview_url = URLHelper::getURL("plugins.php/blubber/streams/global");
    } ?> 
    <a href="<?= URLHelper::getLink($overview_url) ?>">
        <?= Assets::img('icons/16/blue/arr_1left', array('class' => 'text-top')) ?>
        <?= _('Zur�ck zur �bersicht') ?>
    </a>
</p>

<ul id="forum_threads" class="coursestream singlethread" aria-live="polite" aria-relevant="additions">
<? endif; ?>
<li id="posting_<?= htmlReady($thread->getId()) ?>" mkdate="<?= htmlReady($thread['discussion_time']) ?>" class="thread posting<?= $last_visit < $thread['mkdate'] ? " new" : "" ?>" data-autor="<?= htmlReady($thread['user_id']) ?>">
    <div class="hiddeninfo">
        <input type="hidden" name="context" value="<?= htmlReady($thread['Seminar_id']) ?>">
        <input type="hidden" name="context_type" value="<?= $thread['Seminar_id'] === $thread['user_id'] ? "public" : "course" ?>">
    </div>
    <? if ($thread['context_type'] === "course") : ?>
    <a href="<?= URLHelper::getLink("plugins.php/blubber/streams/forum", array('cid' => $thread['Seminar_id'])) ?>"
       <? $title = get_object_name($thread['Seminar_id'], "sem") ?>
       title="<?= _("Veranstaltung")." ".htmlReady($title['name']) ?>"
       class="contextinfo"
       style="background-image: url('<?= CourseAvatar::getAvatar($thread['Seminar_id'])->getURL(Avatar::NORMAL) ?>');">
    </a>
    <? elseif($thread['context_type'] === "private") : ?>
    <? 
        if (count($related_users) > 20) {
            $title = _("Privat: ").sprintf(_("%s Personen"), count($related_users));
        } else {
            $title = _("Privat: ");
            foreach ($related_users as $key => $user_id) {
                if ($key > 0) {
                    $title .= ", ";
                }
                $title .= get_fullname($user_id);
            }
        }
    ?>
    <div class="contextinfo" title="<?= htmlReady($title) ?>" style="background-image: url('<?= $GLOBALS['ABSOLUTE_URI_STUDIP'] ?>/plugins_packages/core/Blubber/assets/images/private.png');">
    </div>
    <div class="related_users"></div>
    <? else : ?>
    <div class="contextinfo" title="<?= _("�ffentlich") ?>" style="background-image: url('<?= $GLOBALS['ABSOLUTE_URI_STUDIP'] ?>/plugins_packages/core/Blubber/assets/images/public.png');">
    </div>
    <? endif ?>
    <div class="avatar_column">
        <div class="avatar">
            <? if ($author_url) : ?>
            <a href="<?= URLHelper::getLink($author_url, array(), true) ?>">
            <? endif ?>
                <div style="background-image: url('<?= $author->getAvatar()->getURL(Avatar::MEDIUM)?>');" class="avatar_image"<?= $author->isNew() ? ' title="'._("Nicht registrierter Nutzer").'"' : "" ?>></div>
            <? if ($author_url) : ?>
            </a>
            <? endif ?>
        </div>
    </div>
    <div class="content_column">
        <div class="timer">
            <a href="<?= URLHelper::getLink('plugins.php/blubber/streams/thread/' . $thread->getId(), array('cid' => $thread['Seminar_id'])) ?>" class="permalink" title="<?= _("Permalink") ?>" style="background-image: url('<?= Assets::image_path("icons/16/grey/group") ?>');">
                <span class="time" data-timestamp="<?= (int) $thread['mkdate'] ?>">
                    <?= (date("j.n.Y", $thread['mkdate']) == date("j.n.Y")) ? sprintf(_("%s Uhr"), date("G:i", $thread['mkdate'])) : date("j.n.Y", $thread['mkdate']) ?>
                </span>
            </a>
            <? if (($thread['Seminar_id'] !== $thread['user_id'] && $GLOBALS['perm']->have_studip_perm("tutor", $thread['Seminar_id']))
                    or ($thread['user_id'] === $GLOBALS['user']->id)) : ?>
            <a href="#" class="edit icon" alt="<?= _("Bearbeiten") ?>" title="<?= _("Bearbeiten") ?>" onClick="return false;" style="background-image: url('<?= Assets::image_path("icons/16/grey/tools") ?>');"></a>
            <? endif ?>
        </div>
        <div class="name">
            <? if ($author_url) : ?>
            <a href="<?= URLHelper::getLink($author_url, array(), true) ?>">
            <? endif ?>
                <?= htmlReady($author_name) ?>
            <? if ($author_url) : ?>
            </a>
            <? endif ?>
        </div>
        <div class="content">
            <? 
            $content = $thread['description'];
            if ($thread['name'] && strpos($thread['description'], $thread['name']) === false) {
                $content = $thread['name']."\n".$content;
            }
            ?>
            <?= BlubberPosting::format($content) ?>
        </div>
    </div>
    <ul class="comments">
    <? $postings = $thread->getChildren() ?>
    <? if ($postings) : ?>
        <? if (count($postings) > 3) : ?>
        <li class="more">
            <?= sprintf(ngettext('%u weiterer Kommentar anzeigen', '%u weitere Kommentare anzeigen', count($postings) - 3), count($postings) - 3)?>
        </li>
        <? endif; ?>
        <? foreach (array_slice($postings, -3) as $posting) : ?>
        <?= $this->render_partial("streams/comment.php", array('posting' => $posting, 'last_visit' => $last_visit)) ?>
        <? endforeach ?>
    <? endif ?>
    </ul>
    <? if ($commentable) : ?>
    <div class="writer">
        <textarea placeholder="<?= _("Kommentiere dies") ?>" aria-label="<?= _("Kommentiere dies") ?>" id="writer_<?= md5(uniqid()) ?>"></textarea>
    </div>
    <? endif ?>
</li>

<? if (@$single_thread): ?>
</ul>
<? endif; ?>