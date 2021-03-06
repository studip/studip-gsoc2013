<table id="news_box" role="article" class="index_box" <? if ($width): ?> style="width: <?= $width ?>;"<? endif; ?>>
    <tr>
        <td class="table_header_bold">
            <img src="<?= Assets::image_path('icons/16/white/breaking-news.png') ?>"
                 <?= tooltip(_('Newsticker. Klicken Sie rechts auf die Zahnräder, '
                              .'um neue News in diesen Bereich einzustellen. Klicken '
                              .'Sie auf die Pfeile am linken Rand, um den ganzen '
                              .'Nachrichtentext zu lesen.')) ?>>
            <b><?= _('Ankündigungen') ?></b>
        </td>
        <td align="right" class="table_header_bold">
            <a href="<?= URLHelper::getLink('admin_news.php?' . $admin_link . '&cmd=new_entry') ?>">
                <img src="<?= Assets::image_path('icons/16/white/admin.png') ?>"
                     <?= tooltip(_('Ankündigungen einstellen')) ?>>
            </a>
        </td>
    </tr>
    <tr>
        <td class="table_row_even" colspan="2">
            <p class="info">
                <?= _('Es sind keine aktuellen Ankündigungen vorhanden. '
                     .'Um neue Ankündigungen zu erstellen, klicken Sie rechts auf die Zahnräder.') ?>
            </p>
        </td>
    </tr>
</table>
