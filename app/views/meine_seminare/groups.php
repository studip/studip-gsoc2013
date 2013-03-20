<? 
use Studip\Button;

$groupables = array(
    'sem_number'  => _('Semester'),
    'sem_tree_id' => _('Studienbereich'),
    'sem_status'  => _('Typ'),
    'gruppe'      => _('Farbgruppen'),
    'dozent_id'   => _('Dozenten'),
)
?>

<form method="post" action="<?= URLHelper::getLink('meine_seminare.php') ?>">
    <?= CSRFProtection::tokenTag() ?>

    <p>
        <label>
            <?= _('Kategorie zur Gliederung:') ?>
            <select name="select_group_field">
            <? if ($no_grouping_allowed): ?>
                <option value="not_grouped" <? if ($group_field == 'not_grouped') echo 'selected'; ?>>
                    <?= _('keine Gliederung') ?>
                </option>
            <? endif; ?>
            <? foreach ($groupables as $key => $label): ?>
                <option value="<?= $key ?>" <? if ($group_field == $key) echo 'selected'; ?>>
                    <?= $label ?>
                </option>
            <? endforeach; ?>
            </select>
        </label>
        <?= Button::createAccept(_('Speichern')) ?>
    </p>
    
<? foreach ($groups as $group_id => $group_members): ?>
    <table class="default zebra-hover" style="margin-bottom: 1em;">
        <colgroup>
            <col>
        <? for ($i = 0; $i < 9; $i++): ?>
            <col width="28px">
        <? endfor; ?>
        </colgroup>

    <? if ($group_field != 'not_grouped'): ?>
        <tr>
            <td class="table_header" valign="middle" height="20" colspan="10">
            <? if (isset($_my_sem_open[$group_id])): ?>
                <a class="tree" style="font-weight:bold" name="<?= $group_id ?>" href="<?= $controller->url_for('meine_seminare/groups?close_my_sem=' . $group_id . '#' .$group_id) ?>">
                    <?= Assets::img('icons/16/blue/arr_1down', tooltip2(_('Gruppierung schließen'))) ?>
            <? else: ?>
                <a class="tree" name="<?= $group_id ?>" href="<?= $controller->url_for('meine_seminare/groups?open_my_sem=' . $group_id . '#' .$group_id ) ?>">
                     <?= Assets::img('icons/16/blue/arr_1right', tooltip2(_('Gruppierung öffnen'))) ?>
            <? endif; ?>
            <? if (is_array($group_names[$group_id])): ?>
                <?= htmlReady(my_substr($group_names[$group_id][1] . ' > ' . $group_names[$group_id][0], 0, 70)) ?>
            <? else: ?>
                <?= htmlReady(my_substr($group_names[$group_id], 0, 70)) ?>
            <? endif; ?>
                 </a>
            </td>
        </tr>
    <? endif; ?>
<? if (isset($_my_sem_open[$group_id])): ?>
    <? foreach ($group_members as $member): ?>
        <tr>
            <td>
                <a href="<?= URLHelper::getLink('seminar_main.php?auswahl=' . $member['seminar_id']) ?>">
                    <?= htmlReady(my_substr($my_sem[$member['seminar_id']]['name'], 0, 70)) ?>
                </a>
            <? if (!$my_sem[$member['seminar_id']]['visible']): ?>
                <?= _('(versteckt)') ?>
            <? endif; ?>
            </td>
        <? for ($i = 0; $i < 9; $i++): ?>
            <td class="gruppe<?= $i ?>">
                <input type="radio" name="gruppe[<?= $member['seminar_id'] ?>]" value="<?= $i ?>" aria-label="<?=_('Zugeordnet zu Gruppe ') . $i ?>"
                       <? if ($my_sem[$member['seminar_id']]['gruppe'] == $i) echo 'checked'; ?>>
            </td>
        <? endfor; ?>
        </tr>
    <? endforeach; ?>
<? endif; ?>
    </table>
<? endforeach; ?>

    <p style="text-align: center;">
        <?= Button::createAccept(_('Speichern')) ?>
        <input type="hidden" name="gruppesent" value="1">
    </p>
</form>
