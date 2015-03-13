<?php

/**
 * FindAndReplace
 * 
 * @copyright Copyright © 2015 Richard Doe
 * @license http://opensource.org/licenses/MIT MIT
 */

/**
 * The FindAndReplace plugin.
 * 
 * @package Omeka\Plugins\FindAndReplace
 */
class FindAndReplacePlugin extends Omeka_Plugin_AbstractPlugin
{
   protected $_filters = array(
        'admin_navigation_main',
    );
    
    public function filterAdminNavigationMain($nav)
    {
        $nav[] = array(
            'label' => __('Find And Replace'),
            'uri' => url('find-and-replace'),
        );
        return $nav;
    }
}
