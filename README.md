<p><img src="./src/icon.svg" width="100" height="100" alt="QuickPay for Craft Commerce icon"></p>

# Webshipper for Craft Commerce

This plugin provides an [Webshipper](https://webshipper.dk/) integration for [Craft Commerce](https://craftcms.com/commerce).

## Requirements

This plugin requires Craft CMS 3.5.0 and Craft Commerce 3.2.0 or later.

## Installation

You can install this plugin from the Plugin Store or with Composer.

#### From the Plugin Store

Go to the Plugin Store in your project’s Control Panel and search for “Webshipper”. Then click on the “Install” button in its modal window.

#### With Composer

Open your terminal and run the following commands:

```bash
# go to the project directory
cd /path/to/my-project.test

# tell Composer to load the plugin
composer require quantity-digital/commerce-webshipper

# tell Craft to install the plugin
./craft install/plugin quantity-digital/commerce-webshipper
```

## Setup

This plugin will, after you have filled in the required settings on the settings page, add a new field on Craft's default shipping methods, where you can connect a shipping method in Craft to the shipping method in Webshipper.

All syncronizations between Craft and Webshipper is handled automaticly using Craft Queue.

> **Tip:** The Account name, Secret token and webhook token can be set to environment variables. See [Environmental Configuration](https://craftcms.com/docs/3.x/config/#environmental-configuration) in the Craft docs for more information.

## Usage

To get droppoints on the front, you can call the endpoint `/craftapi/webshipper/droppoint/locator`, after the shipping address has been added, to receive available droppoints based on the selected shipping address.

## Webhook support

This plugin currently supports the following webhooks:

* shipments/created

