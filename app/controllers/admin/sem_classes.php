<?php
/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 * 
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */
require_once 'app/controllers/authenticated_controller.php';

class Admin_SemClassesController extends AuthenticatedController
{
    function before_filter (&$action, &$args)
    {
        parent::before_filter($action, $args);
        if (!$GLOBALS['perm']->have_perm("root")) {
            throw new AccessDeniedException(_("Kein Zugriff"));
        }
        PageLayout::setHelpKeyword("Admins.SemClasses");
        PageLayout::setTitle("Veranstaltungskategorien");
    }

    public function overview_action()
    {
        Navigation::activateItem("/admin/config/sem_classes");
        if (count($_POST) && Request::get("delete_sem_class")) {
            $sem_class = $GLOBALS['SEM_CLASS'][Request::get("delete_sem_class")];
            if ($sem_class->delete()) {
                PageLayout::postMessage(MessageBox::success(_("Veranstaltungskategorie wurde gelöscht.")));
                $GLOBALS['SEM_CLASS'] = SemClass::refreshClasses();
            }
        }
        if (count($_POST) && Request::get("add_name")) {
            $statement = DBManager::get()->prepare(
                "INSERT INTO sem_classes SET name = :name, mkdate = UNIX_TIMESTAMP(), chdate = UNIX_TIMESTAMP() " .
            "");
            $statement->execute(array('name' => Request::get("add_name")));
            $id = DBManager::get()->lastInsertId();
            if (Request::get("add_like")) {
                $sem_class = clone $GLOBALS['SEM_CLASS'][Request::get("add_like")];
                $sem_class->set('name', Request::get("add_name"));
                $sem_class->set('id', $id);
                $sem_class->store();
            }
            $this->redirect(URLHelper::getURL($this->url_for('admin/sem_classes/details'), array('id' => $id)));
            PageLayout::postMessage(MessageBox::success(_("Veranstaltungskategorie wurde erstellt.")));
            $GLOBALS['SEM_CLASS'] = SemClass::refreshClasses();
        }
    }
    
    public function details_action() 
    {
        Navigation::activateItem("/admin/config/sem_classes");
        $modules = array(
            'CoreOverview' => array('id' => "CoreOverview", 'name' => _("Kern-Übersicht"), 'enabled' => true),
            'CoreAdmin' => array('id' => "CoreAdmin", 'name' => _("Kern-Verwaltung"), 'enabled' => true),
            'CoreForum' => array('id' => "CoreForum", 'name' => _("Kern-Forum"), 'enabled' => true),
            'CoreStudygroupAdmin' => array('id' => "CoreStudygroupAdmin", 'name' => _("Studiengruppen-Verwaltung"), 'enabled' => true),
            'CoreDocuments' => array('id' => "CoreDocuments", 'name' => _("Kern-Dateibereich"), 'enabled' => true),
            'CoreSchedule' => array('id' => "CoreSchedule", 'name' => _("Kern-Termine"), 'enabled' => true),
            'CoreParticipants' => array('id' => "CoreParticipants", 'name' => _("Kern-Teilnehmer"), 'enabled' => true),
            'CoreStudygroupParticipants' => array('id' => "CoreStudygroupParticipants", 'name' => _("Kern-Studiengruppen-Teilnehmer"), 'enabled' => true),
            'CoreLiterature' => array('id' => "CoreLiterature", 'name' => _("Kern-Literatur"), 'enabled' => true),
            'CoreScm' => array('id' => "CoreScm", 'name' => _("Kern-Freie-Informationen"), 'enabled' => true),
            'CoreWiki' => array('id' => "CoreWiki", 'name' => _("Kern-Wiki"), 'enabled' => true),
            'CoreResources' => array('id' => "CoreResources", 'name' => _("Kern-Ressourcen"), 'enabled' => true),
            'CoreCalendar' => array('id' => "CoreCalendar", 'name' => _("Kern-Kalender"), 'enabled' => true),
            'CoreElearningInterface' => array('id' => "CoreElearningInterface", 'name' => _("Kern-Lernmodule"), 'enabled' => true)
        );

        $plugin_infos = PluginManager::getInstance()->getPluginInfos("StandardPlugin");

        foreach ($plugin_infos as $plugin_info) {
            if (!$plugin_info['core']) {
                $modules[$plugin_info['class']] = $plugin_info;
            }
        }

        $this->modules = $modules;
        $this->sem_class = $GLOBALS['SEM_CLASS'][Request::get("id")];
        $this->overview_url = $this->url_for("admin/sem_classes/overview");
    }
    
    public function save_action() 
    {
        if (count($_POST) === 0) {
            throw new Exception("Kein Zugriff über GET");
        }
        $sem_class = $GLOBALS['SEM_CLASS'][Request::int("sem_class_id")];
        foreach (Request::getArray("core_module_slots") as $slot => $module) {
            $sem_class->setSlotModule($slot, studip_utf8decode($module));
        }
        $sem_class->setModules(Request::getArray("modules"));
        $sem_class->set('name', Request::get("sem_class_name"));
        $sem_class->set('title_dozent', Request::get("title_dozent") ? studip_utf8decode(Request::get("title_dozent")) : null);
        $sem_class->set('title_dozent_plural', Request::get("title_dozent_plural") ? studip_utf8decode(Request::get("title_dozent_plural")) : null);
        $sem_class->set('title_tutor', Request::get("title_tutor") ? studip_utf8decode(Request::get("title_tutor")) : null);
        $sem_class->set('title_tutor_plural', Request::get("title_tutor_plural") ? studip_utf8decode(Request::get("title_tutor_plural")) : null);
        $sem_class->set('title_autor', Request::get("title_autor") ? studip_utf8decode(Request::get("title_autor")) : null);
        $sem_class->set('title_autor_plural', Request::get("title_autor_plural") ? studip_utf8decode(Request::get("title_autor_plural")) : null);
        $sem_class->set('compact_mode', Request::int("compact_mode"));
        $sem_class->set('workgroup_mode', Request::int("workgroup_mode"));
        $sem_class->set('only_inst_user', Request::int("only_inst_user"));
        $sem_class->set('turnus_default', Request::int("turnus_default"));
        $sem_class->set('default_read_level', Request::int("default_read_level"));
        $sem_class->set('default_write_level', Request::int("default_write_level"));
        $sem_class->set('bereiche', Request::int("bereiche"));
        $sem_class->set('show_browse', Request::int("show_browse"));
        $sem_class->set('write_access_nobody', Request::int("write_access_nobody"));
        $sem_class->set('topic_create_autor', Request::int("topic_create_autor"));
        $sem_class->set('visible', Request::int("visible"));
        $sem_class->set('course_creation_forbidden', Request::int("course_creation_forbidden"));
        $sem_class->set('admission_prelim_default', Request::int("admission_prelim_default"));
        $sem_class->set('admission_type_default', Request::int("admission_type_default"));
        $sem_class->store();
        $output = array(
            'html' => studip_utf8encode((string) MessageBox::success(_("Änderungen wurden gespeichert."." ".'<a href="'.URLHelper::getLink("dispatch.php/admin/sem_classes/overview").'">'._("Zurück zur Übersichtsseite.").'</a>')))
        );
        echo json_encode($output);
        $this->render_nothing();
    }

    public function add_sem_type_action() {
        if (Request::get('name') && Request::get("sem_class") && count($_POST)) {
            $name = studip_utf8decode(Request::get('name'));
            $statement = DBManager::get()->prepare(
                "INSERT INTO sem_types " .
                "SET name = :name, " .
                    "class = :sem_class, " .
                    "mkdate = UNIX_TIMESTAMP(), " .
                    "chdate = UNIX_TIMESTAMP() " .
            "");
            $statement->execute(array(
                'name' => $name,
                'sem_class' => Request::get("sem_class")
            ));
            $id = DBManager::get()->lastInsertId();
            $GLOBALS['SEM_TYPE'] = SemType::refreshTypes();
            $this->sem_type = $GLOBALS['SEM_TYPE'][$id];

            $this->render_template(
                "admin/sem_classes/_sem_type.php"
            );
        }
    }

    public function rename_sem_type_action() {
        $sem_type = $GLOBALS['SEM_TYPE'][Request::get("sem_type")];
        if ($sem_type) {
            $sem_type->set('name', studip_utf8decode(Request::get("name")));
            $sem_type->store();
        }
        $this->render_nothing();
    }

    public function delete_sem_type_action() {
        if (count($_POST)) {
            $sem_type = $GLOBALS['SEM_TYPE'][Request::int("sem_type")];
            if (!$sem_type->delete()) {
                throw new Exception("Could not delete sem_type because it' still in use.");
            }
        }
        $this->render_nothing();
    }

}