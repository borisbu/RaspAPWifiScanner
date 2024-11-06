<?php

/**
 * SamplePlugin
 *
 * @description A sample user plugin to extend RaspAP's functionality
 * @author      Bill Zimmerman <billzimmerman@gmail.com>
 *              Special thanks to GitHub user @assachs 
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 */

namespace RaspAP\Plugins\SamplePlugin;

use RaspAP\Plugins\PluginInterface;
use RaspAP\UI\Sidebar;

class SamplePlugin implements PluginInterface {
    private string $pluginPath;
    private string $pluginName;

    public function __construct(string $pluginPath, string $pluginName) {
        $this->pluginPath = $pluginPath;
        $this->pluginName = $pluginName;
    }

    /**
     * Initializes SamplePlugin and creates a custom sidebar item. This is the entry point
     * for creating a custom user plugin; the Plugin Manager will autoload the plugin code.
     *
     * Replace 'Sample Plugin' below with the label you wish to use in the sidebar.
     * You may specify any icon in the Font Awesome 6.6 free library for the sidebar item.
     * The page action is handled by a namespaced function provided by the plugin's custom code.
     * The priority value sets the position of the item in the sidebar (lower values = higher priority).
     *
     * @param Sidebar $sidebar an instance of the Sidebar
     * @see src/RaspAP/UI/Sidebar.php
     * @see https://fontawesome.com/icons
     */
    public function initialize(Sidebar $sidebar): void {

        $label = _('Sample Plugin');
        $icon = 'fas fa-plug';
        $action = 'plugin__'.$this->getName();
        $priority = 65;

        $sidebar->addItem($label, $icon, $action, $priority);
    }

    public function getName() {
        return getClassName(get_class($this));
    }

}

