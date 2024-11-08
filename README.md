# SamplePlugin
This repo provides a starting point to create custom user plugins for RaspAP.

<img width="400" alt="sample" src="https://github.com/user-attachments/assets/cf9a8cf2-7cc7-49e5-9cbe-521ef7d97a4d">

The `SamplePlugin` implements a `PluginInterface` and is automatically loaded by RaspAP's `PluginManager`. Several common plugin functions are included, as well as an example method for persisting data in plugin instances. Each plugin has its own namespace, meaning that classes and functions are organized to avoid naming conflicts. Plugins are self-contained and render their own templates from inside the `/templates` directory.

## Contents
 - [Usage](#usage)
 - [Scope of functionality](#scope-of-functionality)
 - [Customizing](#customizing)
 - [Publishing your plugin](#publishing-your-plugin)

## Usage
The `SamplePlugin` requires an installation of [RaspAP](https://github.com/RaspAP/raspap-webgui), either via the [Quick install](https://docs.raspap.com/quick/) method or with a [Docker container](https://docs.raspap.com/docker/). The default application path `/var/www/html` is used here. If you've chosen a different install location, substitute this in the steps below:

1. Begin by creating a fork of this repository.
2. SSH into the device hosting RaspAP, change to the install location and create a `/plugins` directory.
   ```
   cd /var/www/html
   sudo mkdir plugins
   ```
3. Change to the `/plugins` directory, clone your SamplePlugin fork and change to it:
   ```
   cd plugins
   sudo git clone https://github.com/[your-username]/SamplePlugin
   cd SamplePlugin
   ```
4. The PluginManager will autoload the plugin; a new 'Sample Plugin' item will appear in the sidebar.

## Scope of functionality
The `SamplePlugin` implements the methods needed for basic plugin functionality. It initalizes a `Sidebar` object and adds a custom navigation item. User input is processed with `handlePageAction()` and some common functions are handled, including:

1. Saving plugin settings
2. Starting a sample service
3. Stopping a sample service

Template data is then collected in `$__template_data` and rendered by the `main.php` template file located in `/templates`. Property get/set methods are demonstrated with `apiKey` and `serviceStatus` values. A method is then used in `persistData()` to save the `SamplePlugin` object data. Importantly, `SamplePlugin` does _not_ use the PHP `$_SESSION` object as this super global can lead to conflicts with other user plugins.

## Customizing
The `SamplePlugin` demonstrates basic plugin functions without being overly complex. It's designed with best practices in mind and made to be extended and customized by developers.

### Unique plugin names
Most plugin authors will probably begin by renaming `SamplePlugin` to something unique. The `PluginManager` expects the plugin folder, file, namespace and class to follow the same naming convention. When renaming the `SamplePlugin` ensure that each of the following entities uses the same plugin name:


|  Entity                                        |   Type     |
|------------------------------------------------|------------|
| plugins/SamplePlugin                           | folder     |
| plugins/SamplePlugin/SamplePlugin.php          | file       |
| namespace RaspAP\Plugins\SamplePlugin          | namespace  |
| class SamplePlugin implements PluginInterface  | class      |

That is, replace each occurance of `SamplePlugin` with your plugin name in each of these entities.

### Plugin logic and templates
Plugin classes and functions are contained in `SamplePlugin.php`. The parent template (`main.php`) and child tab templates are used to render template data. 

```
├── SamplePlugin/
│   ├── SamplePlugin.php
│   └── templates/
│       ├── main.php
│       └── tabs/
│           ├── about.php
│           ├── basic.php
│           └── status.php
```

You may wish to omit, modify or create new tabs. This is done by editing `main.php` and modifying the contents of the `tabs/` directory.

### Sidebar item
The `PluginInterface` exposes an `initalize()` method that is used to create a unique sidebar item. The properties below can be customized for your plugin:

```
$label = _('Sample Plugin');
$icon = 'fas fa-plug';
$action = 'plugin__'.$this->getName();
$priority = 65;
```

You may specify any icon in the [Font Awesome 6.6 free library](https://fontawesome.com/icons) for the sidebar item. The priority value sets the position of the item in the sidebar (lower values = a higher priority).

## Publishing your plugin
The `SamplePlugin` contains an 'About' tab where you may provide author information, a description and link to your project. If you've authored a plugin you feel would be useful to the RaspAP community, you're encouraged to share it in this repo's [discussions](https://github.com/RaspAP/SamplePlugin/discussions). 
