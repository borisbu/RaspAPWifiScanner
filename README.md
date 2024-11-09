# SamplePlugin
This repo provides a starting point to create custom user plugins for RaspAP.

<img width="400" alt="sample" src="https://github.com/user-attachments/assets/cf9a8cf2-7cc7-49e5-9cbe-521ef7d97a4d">

The `SamplePlugin` implements a `PluginInterface` and is automatically loaded by RaspAP's `PluginManager`. Several common plugin functions are included, as well as an example method for persisting data in plugin instances. Each plugin has its own namespace, meaning that classes and functions are organized to avoid naming conflicts. Plugins are self-contained and render their own templates from inside the `/templates` directory.

## Contents
 - [Usage](#usage)
 - [Scope of functionality](#scope-of-functionality)
 - [Customizing](#customizing)
 - [Multiple instances](#multiple-instances)
 - [Publishing your plugin](#publishing-your-plugin)

## Usage
The `SamplePlugin` requires an installation of [RaspAP](https://github.com/RaspAP/raspap-webgui), either via the [Quick install](https://docs.raspap.com/quick/) method or with a [Docker container](https://docs.raspap.com/docker/). The default application path `/var/www/html` is used here. If you've chosen a different install location, substitute this in the steps below.

1. Begin by creating a fork of this repository.
2. SSH into the device hosting RaspAP, change to the install location and create a `/plugins` directory.
   ```
   cd /var/www/html
   sudo mkdir plugins
   ```
3. Change to the `/plugins` directory, clone your `SamplePlugin` fork and change to it:
   ```
   cd plugins
   sudo git clone https://github.com/[your-username]/SamplePlugin
   cd SamplePlugin
   ```
4. The `PluginManager` will autoload the plugin; a new 'Sample Plugin' item will appear in the sidebar.

## Scope of functionality
The `SamplePlugin` implements the server-side methods needed to support basic plugin functionality. It initalizes a `Sidebar` object and adds a custom navigation item. User input is processed with `handlePageAction()` and several common operations are performed, including:

1. Saving plugin settings
2. Starting a sample service
3. Stopping a sample service

Template data is then collected in `$__template_data` and rendered by the `main.php` template file located in `/templates`. Property get/set methods are demonstrated with `apiKey` and `serviceStatus` values. A method is then used in `persistData()` to save the `SamplePlugin` object data. Importantly, `SamplePlugin` does _not_ use the PHP `$_SESSION` object as this super global can lead to conflicts with other user plugins.

On the front-end, Bootstrap's [form validation](https://getbootstrap.com/docs/5.3/forms/validation/) is used to validate user input. A custom JavaScript function is used to generate a random `apiKey` value. The `sample.service` LED indicator is functional, as are the service stop/start form buttons.

## Customizing
The `SamplePlugin` demonstrates basic plugin functions without being overly complex. It's designed with best practices in mind and made to be extended and customized by developers.

### Unique plugin names
Most plugin authors will probably begin by renaming `SamplePlugin` to something unique. The `PluginManager` expects the plugin folder, file, namespace and class to follow the same naming convention. When renaming the `SamplePlugin` ensure that each of the following entities uses the same plugin name:


|  Entity                                          |   Type     |
|--------------------------------------------------|------------|
| `plugins/SamplePlugin`                           | folder     |
| `plugins/SamplePlugin/SamplePlugin.php`          | file       |
| `namespace RaspAP\Plugins\SamplePlugin`          | namespace  |
| `class SamplePlugin implements PluginInterface`  | class      |

That is, replace each occurrence of `SamplePlugin` with your plugin name in each of these entities.

### Plugin logic and templates
Plugin classes and functions are contained in `SamplePlugin.php`. The parent template `main.php` and child tab templates are used to render template data. 

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

You may wish to omit, modify or create new tabs. This is done by editing `main.php` and modifying the contents of the `/tabs` directory.

### Sidebar item
The `PluginInterface` exposes an `initalize()` method that is used to create a unique sidebar item. The properties below can be customized for your plugin:

```
$label = _('Sample Plugin');
$icon = 'fas fa-plug';
$action = 'plugin__'.$this->getName();
$priority = 65;
```

You may specify any icon in the [Font Awesome 6.6 free library](https://fontawesome.com/icons) for the sidebar item. The priority value sets the position of the item in the sidebar (lower values = a higher priority).

### Permissions
For security reasons, the `www-data` user which the `lighttpd` web service runs under is not allowed to start or stop daemons or execute commands. RaspAP's installer adds the `www-data` user to [sudoers](https://www.sudo.ws/about/intro/), but with restrictions on what commands the user can run. If your plugin requires execute permissions on a Linux binary not present in RaspAP's [sudoers file](https://github.com/RaspAP/raspap-webgui/blob/master/installers/raspap.sudoers), you will need to add this yourself. To edit this file, the `visudo` command should be used. This tool safely edits `sudoers` and performs basic validity checks before installing the edited file. Execute `visudo` and edit RaspAP's sudoers file like so:

```
sudo visudo /etc/sudoers.d/090_raspap
```

An example of adding entries to support a plugin's service is shown below:

```
www-data ALL=(ALL) NOPASSWD:/bin/systemctl start sample.service
www-data ALL=(ALL) NOPASSWD:/bin/systemctl stop sample.service
www-data ALL=(ALL) NOPASSWD:/bin/systemctl status sample.service
```

Wildcards ('`*`') and regular expressions are supported by `sudoers` but [care should be taken when using them](https://www.sudo.ws/posts/2022/03/sudo-1.9.10-using-regular-expressions-in-the-sudoers-file/).

## Multiple instances
The `PluginManager` is a managerial class responsible for locating, instantiating and coordinating plugins. Through the use of namespaces and object data persistence in `SamplePlugin`, any number of user plugins may be installed to `/plugins` and run concurrently.

<img width="619" alt="multiple-highlight" src="https://github.com/user-attachments/assets/2d156efe-8cfc-49e7-b682-219d2db4eeee">

As noted, developers should avoid using PHP's `$_SESSION` object in their plugins to prevent conflicts with other plugin instances. 

## Publishing your plugin
The `SamplePlugin` contains an 'About' tab where you may provide author information, a description and link to your project. If you've authored a plugin you feel would be useful to the RaspAP community, you're encouraged to share it in this repo's [Discussions](https://github.com/RaspAP/SamplePlugin/discussions). 
