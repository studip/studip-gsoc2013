<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* ExternModuleRangelecturetree.class.php
* 
* 
* 
*
* @author       Peter Thienel <pthienel@web.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup  extern
* @module       ExternModuleRangelecturetree
* @package  studip_extern
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// ExternModuleRangelecturetree.class.php
// 
// Copyright (C) 2003 Peter Thienel <pthienel@web.de>,
// Suchi & Berg GmbH <info@data-quest.de>
// +---------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +---------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +---------------------------------------------------------------------------+


require_once($GLOBALS["RELATIVE_PATH_EXTERN"]."/lib/ExternModule.class.php");
require_once($GLOBALS["RELATIVE_PATH_EXTERN"]."/views/extern_html_templates.inc.php");

class ExternModuleRangeLectureTree extends ExternModule {

    /**
    *
    */
    function ExternModuleRangeLectureTree ($range_id, $module_name, $config_id = NULL, $set_config = NULL, $global_id = NULL) {
        $this->registered_elements = array(
                'Body', 'TreePath', 'RangeTreeLevelName', 'RangeTreeLevelContent',
                'TreeKids', 'TreeBackLink'
        );
        $this->args = array('sem', 'start_item_id');
        parent::ExternModule($range_id, $module_name, $config_id, $set_config, $global_id);
    }
    
    function printout ($args) {
        
        if ($this->config->getValue("Main", "wholesite"))
            echo html_header($this->config);
        
        require_once($GLOBALS["RELATIVE_PATH_EXTERN"]
                . "/modules/views/ExternRangeLectureTree.class.php");
        
        $tree = new ExternRangeLectureTree($this->config, $args["start_item_id"]);
        $tree->showSemRangeTree();
        
        if ($this->config->getValue("Main", "wholesite"))
            echo html_footer();
    }
    
    function printoutPreview ($args) {
        
        if ($this->config->getValue("Main", "wholesite"))
            echo html_header($this->config);
        
        require_once($GLOBALS["RELATIVE_PATH_EXTERN"]
                . "/modules/views/ExternRangeLectureTree.class.php");
        
        $tree = new ExternRangeLectureTree($this->config, $args["start_item_id"]);
        $tree->showSemRangeTree();
        
        if ($this->config->getValue("Main", "wholesite"))
            echo html_footer();
    }
    
}

?>
