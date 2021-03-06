<?php
/*
 * Forum.class.php - Forum
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Till Gl�ggler <till.gloeggler@elan-ev.de>
 * @copyright   2011 ELAN e.V. <http://www.elan-ev.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

require_once 'app/models/smiley.php';
require_once 'lib/plugins/core/ForumModule.class.php';
require_once 'vendor/trails/trails.php';
require_once 'lib/classes/exportdocument/ExportPDF.class.php';

require_once 'models/ForumEntry.php';
require_once 'models/ForumHelpers.php';
require_once 'models/ForumIssue.php';
require_once 'models/ForumPerm.php';
require_once 'models/ForumVisit.php';

// Notifications
NotificationCenter::addObserver('CoreForum', 'overviewDidClear', "OverviewDidClear");

class CoreForum extends StudipPlugin implements ForumModule
{

    /**
     * Initialize a new instance of the plugin.
     */
    function __construct()
    {
        parent::__construct();

        // Add JS and StyleSheet to header
        PageLayout::addScript($this->getPluginURL() . '/javascript/forum.js');
        PageLayout::addStylesheet($this->getPluginURL() . '/stylesheets/forum.css');
        
        // JQuery-Tutor JoyRide JS and CSS
        PageLayout::addScript($this->getPluginURL() . '/javascript/jquery.joyride.js');
        PageLayout::addStylesheet($this->getPluginURL() . '/stylesheets/joyride.css');
        
        // Set helpkeyword for Stud.IP's user-documentation
        PageLayout::setHelpKeyword('Basis.Forum');
    }

    /**
     * This method dispatches all actions.
     *
     * @param string $unconsumed_path  part of the dispatch path that was not consumed
     */
    function perform($unconsumed_path)
    {
        $trails_root = $this->getPluginPath();
        $dispatcher = new Trails_Dispatcher($trails_root, PluginEngine::getUrl($this, array(), 'index'), 'index');
        $dispatcher->dispatch($unconsumed_path);

    }
    
    /* interface method */
    public function getTabNavigation($course_id)
    {
        if (!$this->isActivated($course_id)) {
            return;
        }

        $navigation = new Navigation(_('Forum'), PluginEngine::getLink($this, array(), 'index'));
        $navigation->setImage('icons/16/white/forum.png');

        // add main third-level navigation-item
        $navigation->addSubNavigation('index', new Navigation(_('�bersicht'), PluginEngine::getLink($this, array(), 'index')));
        
        if (ForumPerm::has('fav_entry', $course_id)) {
            $navigation->addSubNavigation('newest', new Navigation(_("Neue Beitr�ge"), PluginEngine::getLink($this, array(), 'index/newest')));
            $navigation->addSubNavigation('latest', new Navigation(_("Letzte Beitr�ge"), PluginEngine::getLink($this, array(), 'index/latest')));
            $navigation->addSubNavigation('favorites', new Navigation(_('Gemerkte Beitr�ge'), PluginEngine::getLink($this, array(), 'index/favorites')));
            
            // mass-administrate the forum
            if (ForumPerm::has('admin', $course_id)) {
                $navigation->addSubNavigation('admin', new Navigation(_('Administration'), PluginEngine::getLink($this, array(), 'index/admin')));
            }
        }

        return array('forum2' => $navigation);
    }

    /* interface method */
    function getIconNavigation($course_id, $last_visit, $user_id = null)
    {
        if (!$this->isActivated($course_id)) {
            return;
        }

        if ($GLOBALS['perm']->have_studip_perm('user', $course_id)) {
            $num_entries = ForumVisit::getCount($course_id, ForumVisit::getVisit($course_id));
            $text = ForumHelpers::getVisitText($num_entries, $course_id);
        } else {
            $num_entries = 0;
            $text = 'Forum';
        }
        
        $navigation = new Navigation('forum', PluginEngine::getLink($this, array(), 'index/enter_seminar'));
        #$navigation->setBadgeNumber($num_entries);

        if ($num_entries > 0) {
            $navigation->setImage('icons/16/red/new/forum.png', array('title' => $text));
        } else {
            $navigation->setImage('icons/16/grey/forum.png', array('title' => $text));
        }

        return $navigation;
    }

    /* interface method */
    function getNotificationObjects($course_id, $since, $user_id)
    {
        return array();
    }

 
    /**
     * This method is called, whenever an user clicked to clear the visit timestamps
     * and set everything as visited
     * 
     * @param object $notification
     * @param string $user_id
     */
    function overviewDidClear($notification, $user_id)
    {
        $stmt = DBManager::get()->prepare("UPDATE forum_visits 
            SET visitdate = UNIX_TIMESTAMP(), last_visitdate = UNIX_TIMESTAMP()
            WHERE user_id = ?");
        $stmt->execute(array($user_id));
    }
    
    function getInfoTemplate($course_id)
    {
        return null;
    }
    
    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    /* * IMPLEMENTATION OF METHODS FROM FORUMMODULE-INTERFACE  * */
    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    function getLinkToThread($issue_id)
    {
        if ($topic_id = ForumIssue::getThreadIdForIssue($issue_id)) {
            return PluginEngine::getLink($this, array(), '/index/index/' . $topic_id);
        }
        
        return false;
    }
    
    function setThreadForIssue($issue_id, $title, $content)
    {
        ForumIssue::setThreadForIssue($GLOBALS['SessSemName'][1], $issue_id, $title, $content);
    }
    
    function getNumberOfPostingsForUser($user_id)
    {
        return ForumEntry::countUserEntries($user_id);
    }
    
    function getNumberOfPostingsForIssue($issue_id)
    {
        $topic_id = ForumIssue::getThreadIdForIssue($issue_id);

        return ForumEntry::countEntries($topic_id);
    }
    
    function getNumberOfPostingsForSeminar($seminar_id)
    {
        return ForumEntry::countEntries($seminar_id);
    }
    
    function getNumberOfPostings()
    {
        return ForumEntry::countAllEntries();
    }
    
    function getEntryTableInfo()
    {
        return array(
            'table'      => 'forum_entries',
            'content'    => 'content',
            'chdate'     => 'chdate',
            'seminar_id' => 'seminar_id',
            'user_id'    => 'user_id'
        );
    }
    
    function getTopTenSeminars()
    {
        return ForumEntry::getTopTenSeminars();
    }
    
    function migrateUser($user_from, $user_to)
    {
        return ForumEntry::migrateUser($user_from, $user_to);
    }
    
    function deleteContents($seminar_id)
    {
        return ForumEntry::delete($seminar_id);
    }
    
    function getDump($seminar_id)
    {
        ForumEntry::getDump($seminar_id);
    }
}
