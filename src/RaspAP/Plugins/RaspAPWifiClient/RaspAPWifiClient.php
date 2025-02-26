<?php

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
        // Get all network interfaces
        $interfaces = [];
        
        // Method 1: Using /sys/class/net
        if (is_dir('/sys/class/net')) {
            $netDevices = scandir('/sys/class/net');
            foreach ($netDevices as $device) {
                if (strpos($device, 'wlan') === 0) {
                    $interfaces[] = $device;
                }
            }
        }
        
        // Method 2: Using ip link (backup method)
        if (empty($interfaces)) {
            exec('ip link show | grep -o "wlan[0-9]*"', $ipOutput);
            $interfaces = array_unique($ipOutput);
        }
        
        // Method 3: Using iwconfig (final backup)
        if (empty($interfaces)) {
            exec('iwconfig 2>/dev/null | grep -o "wlan[0-9]*"', $iwOutput);
            $interfaces = array_unique($iwOutput);
        }

        $this->availableInterfaces = $interfaces;

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

        // Debug logging
        error_log('Available interfaces: ' . print_r($this->availableInterfaces, true));
        error_log('Selected interface: ' . ($this->interface ?? 'none'));
    }

    // Remove country-related methods
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

    // ... rest of the existing code ...
} 