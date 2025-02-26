<?php

namespace RaspAP\Plugins\RaspAPWifiClient;

use RaspAP\Plugins\PluginInterface;
use RaspAP\UI\Sidebar;

class RaspAPWifiClient implements PluginInterface
{
    private string $pluginPath;
    private string $pluginName;
    private string $templateMain;
    private array $networks = [];
    private array $currentNetwork = [];
    private string $interface;
    private array $availableInterfaces = [];

    public function __construct(string $pluginPath, string $pluginName)
    {
        $this->pluginPath = $pluginPath;
        $this->pluginName = $pluginName;
        $this->templateMain = 'main';
        $this->detectInterfaces();
        $this->loadCurrentConfig();
    }

    private function detectInterfaces()
    {
        // Get all wireless interfaces
        exec('iwconfig 2>/dev/null | grep IEEE | cut -d" " -f1', $interfaces);
        $this->availableInterfaces = array_filter($interfaces, function($iface) {
            return strpos($iface, 'wlan') === 0;
        });

        // Get the AP interface from RaspAP configuration
        $ap_iface = '';
        if (file_exists('/etc/raspap/hostapd.ini')) {
            $ap_config = parse_ini_file('/etc/raspap/hostapd.ini');
            $ap_iface = $ap_config['interface'] ?? '';
        }

        // Select the first non-AP interface as default
        foreach ($this->availableInterfaces as $iface) {
            if ($iface !== $ap_iface) {
                $this->interface = $iface;
                break;
            }
        }

        // Fallback to first available interface if no non-AP interface found
        if (empty($this->interface) && !empty($this->availableInterfaces)) {
            $this->interface = $this->availableInterfaces[0];
        }
    }

    public function initialize(Sidebar $sidebar): void
    {
        $label = _('WiFi Client');
        $icon = 'fas fa-laptop';
        $action = 'plugin__'.$this->getName();
        $priority = 20;

        $sidebar->addItem($label, $icon, $action, $priority);
    }

    public function handlePageAction(string $page): bool
    {
        if (str_starts_with($page, "/plugin__" . $this->getName())) {
            $status = new \RaspAP\Messages\StatusMessage;

            if (!RASPI_MONITOR_ENABLED) {
                if (isset($_POST['connect'])) {
                    $this->handleConnect($status);
                } elseif (isset($_POST['disconnect'])) {
                    $this->handleDisconnect($status);
                } elseif (isset($_POST['scan'])) {
                    $this->scanNetworks($status);
                }
            }

            // Always scan networks if none are available
            if (empty($this->networks)) {
                $this->scanNetworks($status);
            }

            $__template_data = [
                'title' => _('WiFi Client'),
                'description' => _('Connect to WiFi networks'),
                'icon' => 'fas fa-laptop',
                'action' => 'plugin__'.$this->getName(),
                'pluginName' => $this->getName(),
                'networks' => $this->networks,
                'currentNetwork' => $this->currentNetwork,
                'interface' => $this->interface,
                'availableInterfaces' => $this->availableInterfaces
            ];

            echo $this->renderTemplate($this->templateMain, compact(
                "status",
                "__template_data"
            ));
            return true;
        }
        return false;
    }

    private function handleConnect($status)
    {
        $ssid = $_POST['ssid'] ?? '';
        $psk = $_POST['psk'] ?? '';
        
        if (empty($ssid)) {
            $status->addMessage(_('SSID cannot be empty'), 'danger');
            return;
        }

        $config = [
            'ssid' => $ssid,
            'psk' => $psk,
            'key_mgmt' => empty($psk) ? 'NONE' : 'WPA-PSK'
        ];

        if ($this->writeWPAConfig($config)) {
            exec("sudo wpa_cli -i {$this->interface} reconfigure", $output, $return);
            if ($return === 0) {
                $status->addMessage(_('Successfully connected to network'), 'success');
                $this->currentNetwork = $config;
            } else {
                $status->addMessage(_('Failed to connect to network'), 'danger');
            }
        } else {
            $status->addMessage(_('Failed to write configuration'), 'danger');
        }
    }

    private function handleDisconnect($status)
    {
        exec("sudo wpa_cli -i {$this->interface} disconnect", $output, $return);
        if ($return === 0) {
            $status->addMessage(_('Successfully disconnected from network'), 'success');
            $this->currentNetwork = [];
        } else {
            $status->addMessage(_('Failed to disconnect from network'), 'danger');
        }
    }

    private function scanNetworks($status)
    {
        exec("sudo iwlist {$this->interface} scan", $output, $return);
        
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
            
            $this->networks = $networks;
            $status->addMessage(_('Network scan completed'), 'success');
        } else {
            $status->addMessage(_('Failed to scan networks'), 'danger');
        }
    }

    private function loadCurrentConfig()
    {
        $config = '/etc/wpa_supplicant/wpa_supplicant.conf';
        if (file_exists($config)) {
            $content = file_get_contents($config);
            if (preg_match('/network={\s*ssid="([^"]+)"/', $content, $matches)) {
                $this->currentNetwork['ssid'] = $matches[1];
                if (preg_match('/psk="([^"]+)"/', $content, $matches)) {
                    $this->currentNetwork['psk'] = $matches[1];
                    $this->currentNetwork['key_mgmt'] = 'WPA-PSK';
                } else {
                    $this->currentNetwork['key_mgmt'] = 'NONE';
                }
            }
        }
    }

    private function writeWPAConfig($network)
    {
        $config = "ctrl_interface=DIR=/var/run/wpa_supplicant GROUP=netdev\n";
        $config .= "update_config=1\n\n";
        $config .= "network={\n";
        $config .= "    ssid=\"{$network['ssid']}\"\n";
        if (!empty($network['psk'])) {
            $config .= "    psk=\"{$network['psk']}\"\n";
        }
        $config .= "    key_mgmt={$network['key_mgmt']}\n";
        $config .= "}\n";

        return file_put_contents('/tmp/wpa_supplicant.conf', $config) &&
               exec('sudo cp /tmp/wpa_supplicant.conf /etc/wpa_supplicant/wpa_supplicant.conf') === 0;
    }

    public function renderTemplate(string $templateName, array $__data = []): string
    {
        $templateFile = "{$this->pluginPath}/templates/{$templateName}.php";

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

    public static function getName(): string
    {
        return basename(str_replace('\\', '/', static::class));
    }
} 