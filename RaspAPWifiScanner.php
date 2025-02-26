<?php

/**
 * RaspAPWifiScanner
 *
 * A WiFi scanning plugin for RaspAP
 *
 * @description A WiFi scanning plugin to extend RaspAP's functionality
 * @author      borisbu
 */

namespace RaspAP\Plugins\RaspAPWifiScanner;

use RaspAP\Plugins\PluginInterface;
use RaspAP\UI\Sidebar;

class RaspAPWifiScanner implements PluginInterface
{

    private string $pluginPath;
    private string $pluginName;
    private string $templateMain;
    private array $scanResults = [];
    private string $selectedInterface;
    private array $availableInterfaces = [];

    public function __construct(string $pluginPath, string $pluginName)
    {
        $this->pluginPath = $pluginPath;
        $this->pluginName = $pluginName;
        $this->templateMain = 'main';
        $this->detectInterfaces();

        if ($loaded = self::loadData()) {

        }
    }

    private function detectInterfaces()
    {
        $interfaces = [];
        $ap_iface = '';

        // Get AP interface from RaspAP configuration
        if (file_exists('/etc/raspap/hostapd.ini')) {
            $ap_config = parse_ini_file('/etc/raspap/hostapd.ini');
            $ap_iface = $ap_config['interface'] ?? '';
        }

        // Get all wireless interfaces using iw
        exec('iw dev | grep Interface | cut -f 2 -s -d" "', $iw_output);
        foreach ($iw_output as $iface) {
            if ($iface !== $ap_iface) {
                $interfaces[] = $iface;
            }
        }

        // Fallback to iwconfig if iw didn't find interfaces
        if (empty($interfaces)) {
            exec('iwconfig 2>/dev/null | grep -o "^wlan[0-9]*"', $iwconfig_output);
            foreach ($iwconfig_output as $iface) {
                if ($iface !== $ap_iface) {
                    $interfaces[] = $iface;
                }
            }
        }

        // Final fallback to system network interfaces
        if (empty($interfaces)) {
            if (is_dir('/sys/class/net')) {
                $netDevices = scandir('/sys/class/net');
                foreach ($netDevices as $device) {
                    if (strpos($device, 'wlan') === 0 && $device !== $ap_iface) {
                        $interfaces[] = $device;
                    }
                }
            }
        }

        $this->availableInterfaces = array_unique($interfaces);

        // Select first available interface as default
        if (!empty($this->availableInterfaces)) {
            $this->selectedInterface = $this->availableInterfaces[0];
        }

        // Debug logging
        error_log('RaspAPWifiScanner - Available interfaces: ' . print_r($this->availableInterfaces, true));
        error_log('RaspAPWifiScanner - Selected interface: ' . ($this->selectedInterface ?? 'none'));
        error_log('RaspAPWifiScanner - AP interface: ' . $ap_iface);
    }

    private function getInterfaceInfo()
    {
        $interfaceInfo = [];
        
        foreach ($this->availableInterfaces as $iface) {
            $info = [
                'name' => $iface,
                'status' => 'down',
                'ssid' => '',
                'frequency' => '',
                'bitrate' => '',
                'signal' => ''
            ];

            // Get interface status using iw
            exec("iw dev $iface link 2>/dev/null", $iw_output);
            if (!empty($iw_output)) {
                foreach ($iw_output as $line) {
                    if (strpos($line, 'Connected to') !== false) {
                        $info['status'] = 'up';
                    } elseif (strpos($line, 'SSID:') !== false) {
                        $info['ssid'] = trim(substr($line, strpos($line, ':') + 1));
                    } elseif (strpos($line, 'freq:') !== false) {
                        preg_match('/freq: (\d+)/', $line, $matches);
                        if (isset($matches[1])) {
                            $info['frequency'] = $matches[1] . ' MHz';
                        }
                    } elseif (strpos($line, 'signal:') !== false) {
                        preg_match('/signal: ([-\d]+)/', $line, $matches);
                        if (isset($matches[1])) {
                            $info['signal'] = $matches[1] . ' dBm';
                        }
                    } elseif (strpos($line, 'tx bitrate:') !== false) {
                        preg_match('/tx bitrate: ([\d.]+)/', $line, $matches);
                        if (isset($matches[1])) {
                            $info['bitrate'] = $matches[1] . ' MBit/s';
                        }
                    }
                }
            }

            // Fallback to iwconfig if iw didn't provide info
            if ($info['status'] === 'down') {
                exec("iwconfig $iface 2>/dev/null", $iwconfig_output);
                foreach ($iwconfig_output as $line) {
                    if (strpos($line, 'ESSID:') !== false) {
                        preg_match('/ESSID:"([^"]*)"/', $line, $matches);
                        if (isset($matches[1]) && !empty($matches[1])) {
                            $info['status'] = 'up';
                            $info['ssid'] = $matches[1];
                        }
                    }
                    if (strpos($line, 'Bit Rate=') !== false) {
                        preg_match('/Bit Rate=(\d+)/', $line, $matches);
                        if (isset($matches[1])) {
                            $info['bitrate'] = $matches[1] . ' Mb/s';
                        }
                    }
                    if (strpos($line, 'Frequency:') !== false) {
                        preg_match('/Frequency:([\d.]+)/', $line, $matches);
                        if (isset($matches[1])) {
                            $info['frequency'] = $matches[1] . ' GHz';
                        }
                    }
                }
            }

            $interfaceInfo[$iface] = $info;
        }

        return $interfaceInfo;
    }

    /**
     * Initializes RaspAPWifiScanner and creates a custom sidebar item. This is the entry point
     * for creating a custom user plugin; the PluginManager will autoload the plugin code.
     * @param Sidebar $sidebar an instance of the Sidebar
     * @see src/RaspAP/UI/Sidebar.php
     * @see https://fontawesome.com/icons
     */
    public function initialize(Sidebar $sidebar): void
    {

        $label = _('WiFi Scanner');
        $icon = 'fas fa-wifi';
        $action = 'plugin__'.$this->getName();
        $priority = 65;

        $sidebar->addItem($label, $icon, $action, $priority);
    }

    /**
     * Handles a page action by processing inputs and rendering a plugin template.
     *
     * @param string $page the current page route
     */
    public function handlePageAction(string $page): bool
    {
        // Verify that this plugin should handle the page
        if (str_starts_with($page, "/plugin__" . $this->getName())) {

            // Instantiate a StatusMessage object
            $status = new \RaspAP\Messages\StatusMessage;

            if (!RASPI_MONITOR_ENABLED) {
                if (isset($_POST['scan'])) {
                    $this->scanNetworks($status);
                } elseif (isset($_POST['interface']) && in_array($_POST['interface'], $this->availableInterfaces)) {
                    $this->selectedInterface = $_POST['interface'];
                    $this->scanNetworks($status);
                }
            }

            // Populate template data
            $__template_data = [
                'title' => _('WiFi Scanner'),
                'description' => _('Scan for nearby WiFi networks'),
                'icon' => 'fas fa-wifi',
                'action' => 'plugin__'.$this->getName(),
                'pluginName' => $this->getName(),
                'scanResults' => $this->scanResults,
                'selectedInterface' => $this->selectedInterface,
                'availableInterfaces' => $this->availableInterfaces,
                'interfaceInfo' => $this->getInterfaceInfo()
            ];

            echo $this->renderTemplate($this->templateMain, compact(
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
    }    /* An example method to persist plugin data
    *
    * This writes to the volatile /tmp directory which is cleared
    * on each system boot, so should not be considered as a robust
    * method of data persistence; it's used here for demo purposes only.
    *
    * @note Plugins should avoid use of $_SESSION vars as these are
    * super globals that may conflict with other user plugins.
    */
   public function persistData()
   {
       $serialized = serialize($this);
       file_put_contents("/tmp/plugin__{$this->getName()}.data", $serialized);
   }

   // Static method to load persisted data
   public static function loadData(): ?self
   {
       $filePath = "/tmp/plugin__".self::getName() .".data";
       if (file_exists($filePath)) {
           $data = file_get_contents($filePath);
           return unserialize($data);
       }
       return null;
   }
    // Returns an abbreviated class name
    public static function getName(): string
    {
        return basename(str_replace('\\', '/', static::class));
    }

    private function scanNetworks($status)
    {
        if (empty($this->selectedInterface)) {
            $status->addMessage(_('No interface selected'), 'danger');
            return;
        }

        exec("sudo iwlist {$this->selectedInterface} scan", $output, $return);
        
        if ($return === 0) {
            $networks = [];
            $current = null;
            
            foreach ($output as $line) {
                $line = trim($line);
                
                if (strpos($line, 'Cell') === 0) {
                    if ($current) {
                        $networks[] = $current;
                    }
                    $current = [
                        'ssid' => '',
                        'signal' => '',
                        'quality' => '',
                        'encryption' => 'none',
                        'frequency' => '',
                        'channel' => ''
                    ];
                }
                
                if ($current) {
                    if (strpos($line, 'ESSID:') !== false) {
                        $current['ssid'] = trim(str_replace(['ESSID:', '"'], '', $line));
                    }
                    if (strpos($line, 'Signal level=') !== false) {
                        preg_match('/Signal level=(.+?) dBm/', $line, $matches);
                        $current['signal'] = $matches[1] ?? '';
                    }
                    if (strpos($line, 'Quality=') !== false) {
                        preg_match('/Quality=(\d+)\/70/', $line, $matches);
                        $current['quality'] = ($matches[1] ?? 0) / 70 * 100;
                    }
                    if (strpos($line, 'Encryption key:on') !== false) {
                        $current['encryption'] = 'WPA2';
                    }
                    if (strpos($line, 'Frequency:') !== false) {
                        preg_match('/Frequency:(.+?) GHz/', $line, $matches);
                        $current['frequency'] = $matches[1] ?? '';
                        preg_match('/\(Channel (\d+)\)/', $line, $matches);
                        $current['channel'] = $matches[1] ?? '';
                    }
                }
            }
            
            if ($current) {
                $networks[] = $current;
            }
            
            $this->scanResults = $networks;
            $status->addMessage(_('Network scan completed'), 'success');
        } else {
            $status->addMessage(_('Failed to scan networks'), 'danger');
        }
    }

}

