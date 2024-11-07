<?php

/**
 * SamplePlugin
 *
 * @description A sample user plugin to extend RaspAP's functionality
 * @author      Bill Zimmerman <billzimmerman@gmail.com>
 *              Special thanks to GitHub user @assachs 
 * @license     https://github.com/RaspAP/SamplePlugin/blob/master/LICENSE
 */

namespace RaspAP\Plugins\SamplePlugin;

use RaspAP\Plugins\PluginInterface;
use RaspAP\UI\Sidebar;

class SamplePlugin implements PluginInterface
{

    private string $pluginPath;
    private string $pluginName;

    public function __construct(string $pluginPath, string $pluginName)
    {
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
    public function initialize(Sidebar $sidebar): void
    {

        $label = _('Sample Plugin');
        $icon = 'fas fa-plug';
        $action = 'plugin__'.$this->getName();
        $priority = 65;

        $sidebar->addItem($label, $icon, $action, $priority);
    }

    /**
     * Handles a page action by rendering a plugin template
     *
     * @param string $page the current page route
     * @param PluginManager $pluginManager an instance of the PluginManager
     */
    public function handlePageAction(string $page): bool
    {
        // Verify that this plugin should handle the page
        if (str_starts_with($page, "/plugin__" . $this->getName())) {

            // Instantiate StatusMessage object
            $status = new \RaspAP\Messages\StatusMessage;

            /**
             * Examples of common plugin actions are handled here:
             * 1. saveSettings
             * 2. startSampleService
             * 3. stopSampleService
             *
             * Other page actions and custom functions may be added as required.
             */
            if (!RASPI_MONITOR_ENABLED) {
                if (isset($_POST['saveSettings'])) {
                    if (isset($_POST['txtapikey'])) {
                        // Validate user data
                        $apiKey = trim($_POST['txtapikey']);
                        if (strlen($apiKey) == 0) {
                            $status->addMessage('Please enter a valid API key', 'danger');
                        } else {
                            $return = $this->saveSampleSettings($status, $apiKey);
                            $status->addMessage('Restarting sample.service', 'info');
                            // Here you could restart a service, for example:
                            // exec('sudo /bin/systemctl restart sample.service', $return);
                            // Note: the www-user must have execute permissions in raspap.sudoers
                        }
                    }
                } elseif (isset($_POST['startSampleService'])) {
                    $status->addMessage('Attempting to start sample.service', 'info');
                    // Example of starting a service with exec():
                    // exec('sudo /bin/systemctl start sample.service', $return);
                    // Note: the www-user must have execute permissions in raspap.sudoers
                    $_SESSION['serviceStatus'] = 'up';
                    foreach ($return as $line) { // collect any returned values and add them to the StatusMessage object
                        $status->addMessage($line, 'info');
                    }
                } elseif (isset($_POST['stopSampleService'])) {
                    $status->addMessage('Attempting to stop sample.service', 'info');
                    // Example of stopping a a service with exec():
                    // exec('sudo /bin/systemctl stop sample.service', $return);
                    // Note: the www-user must have execute permissions in raspap.sudoers
                    $_SESSION['serviceStatus'] = 'down';
                    foreach ($return as $line) {
                        $status->addMessage($line, 'info'); // collect any returned values and add them to the StatusMessage object
                    }
                }
            }

            // Populate template data
            $__template_data = [
                'title' => _('Sample Plugin'),
                'description' => _('A sample user plugin to extend RaspAP'),
                'author' => _('A. Plugin Author'),
                'icon' => 'fas fa-plug', // icon should be the same used for Sidebar
                'serviceStatus' => $this->getServiceStatus(), // plugin may optionally return a service status
                'serviceName' => 'sample.service', // an optional service name
                'action' => 'plugin__'.$this->getName(), // expected by Plugin Manager; do not edit
                'pluginName' => $this->getName(), // required for template rendering; do not edit
                // content may be passed in template data or used directly in the parent template and/or child tabs
                'content' => _('This is content generated by the SamplePlugin.'),
                // example service log output. this could be replaced with an actual status result such as:
                // exec("sudo systemctl status sample.service", $output, $return);
                'serviceLog' => "● sample.service - raspap-sample\n    Loaded: loaded (/lib/systemd/system/sample.service; enabled;)\n    Active: active (running)"
            ];

            // pass session var to template data after processing page actions
            $__template_data['apiKey'] = $_SESSION['apiKey'];

            echo $this->renderTemplate('sample', compact(
                "status",
                "__template_data"
            ));
            return true;
        }
        return false;
    }

    /**
     * Renders a template from inside a plugin directory
     * @param string $templateName
     * @param array $__data
     */
    public function renderTemplate(string $templateName, array $__data = []): string
    {
        $templateFile = "{$this->pluginPath}/{$this->getName()}/templates/{$templateName}.php";

        if (!file_exists($templateFile)) {
            return "Template file {$templateFile} not found.";
        }
        if (!empty($__data)) {
            extract($__data);
        }

        ob_start();
        include $templateFile;
        return ob_get_clean();
    }

    /**
     * Saves SamplePlugin settings
     *
     * @param object status
     * @param string $apiKey
     */
    public function saveSampleSettings($status, $apiKey)
    {
        $status->addMessage('Saving Sample API key', 'info');
        // do something with the API key, save to session for demo purposes
        $_SESSION['apiKey'] = $apiKey;
        return $status;
    }

    /**
     * Returns a hypothetical service status
     * @return string $status
     */
    public function getServiceStatus(): string
    {
        /* A dummy value is used here for demo purposes.
         *
         * An example of fetching a process or service status is:
         * exec('pidof some_process | wc -l', $return);
         * $state = ($return[0] > 0);
         * $status = $state ? "up" : "down";
         *
         * @note "up" and "down" correspond to the .service-status-* CSS classes
         */
        $status = $_SESSION['serviceStatus'] ?? "up";
        return $status;
    }

    public function getName(): string
    {
        return basename(str_replace('\\', '/', static::class));
    } 

}

