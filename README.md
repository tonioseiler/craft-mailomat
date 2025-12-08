<p align="center"><img src="./src/icon.svg" width="100" height="100" alt="Mailomat for Craft CMS icon"></p>

<h1 align="center">Mailomat for Craft CMS</h1>

This plugin provides a [Mailomat](https://mailomat.swiss/) integration for [Craft CMS](https://craftcms.com/).

## Requirements

This plugin requires Craft CMS 4.0.0+ or 5.0.0+.

You can install this plugin from the Plugin Store or with Composer.

#### From the Plugin Store

Go to the Plugin Store in your project’s Control Panel and search for “Mailomat”. Then click on the “Install” button in its modal window.

#### With Composer

Open your terminal and run the following commands:

```bash
# go to the project directory
cd /path/to/my-project.test

# tell Composer to load the plugin
composer require furbo/craft-mailomat

# tell Craft to install the plugin
./craft install/plugin craft-mailomat
```

## Setup

Once Mailomat is installed, go to Settings → Email, and change the “Transport Type” setting to “Mailomat”. Enter your API Key (which you can get from your [domain overview](https://app.mailomat.swiss/) page) and click Save.

> **Tip:** The Domain, API Key, and Endpoint settings can be set to environment variables. See [Environmental Configuration](https://docs.craftcms.com/v3/config/environments.html) in the Craft docs to learn more about that.