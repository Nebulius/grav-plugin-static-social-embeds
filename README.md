# Static Social Embeds Plugin

The **Static Social Embeds** Plugin is for [Grav CMS](http://github.com/getgrav/grav). It embeds social status (like tweets, instagram posts, toots, etc.) in articles without using their embed iframe, but rather statically without any dependency to the service.

## Installation

Installing the Static Social Embeds plugin can be done in one of two ways. The GPM (Grav Package Manager) installation method enables you to quickly and easily install the plugin with a simple terminal command, while the manual method enables you to do so via a zip file.

### GPM Installation (Preferred)

The simplest way to install this plugin is via the [Grav Package Manager (GPM)](http://learn.getgrav.org/advanced/grav-gpm) through your system's terminal (also called the command line).  From the root of your Grav install type:

    bin/gpm install static-social-embeds

This will install the Static Social Embeds plugin into your `/user/plugins` directory within Grav. Its files can be found under `/your/site/grav/user/plugins/static-social-embeds`.

### Manual Installation

To install this plugin, just download the zip version of this repository and unzip it under `/your/site/grav/user/plugins`. Then, rename the folder to `static-social-embeds`. You can find these files on [GitHub](https://github.com/amaury-carrade/grav-plugin-static-social-embeds) or via [GetGrav.org](http://getgrav.org/downloads/plugins#extras).

You should now have all the plugin files under

    /your/site/grav/user/plugins/static-social-embeds
	
> NOTE: This plugin is a modular component for Grav which requires [Grav](http://github.com/getgrav/grav) and the [Error](https://github.com/getgrav/grav-plugin-error) and [Problems](https://github.com/getgrav/grav-plugin-problems) to operate.

### Admin Plugin

If you use the admin plugin, you can install directly through the admin plugin by browsing the `Plugins` tab and clicking on the `Add` button.

## Configuration

Before configuring this plugin, you should copy the `user/plugins/static-social-embeds/static-social-embeds.yaml` to `user/config/plugins/static-social-embeds.yaml` and only edit that copy.

Here is the default configuration and an explanation of available options:

```yaml
enabled: true
```

Note that if you use the admin plugin, a file with your configuration, and named static-social-embeds.yaml will be saved in the `user/config/plugins/` folder once the configuration is saved in the admin.

## Usage

**Describe how to use the plugin.**

## To Do

- [ ] Future plans, if any
