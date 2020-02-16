<?php

namespace Grav\Plugin;

use Grav\Common\Assets;
use Grav\Common\Plugin;


/**
 * Class StaticSocialEmbedsPlugin
 * @package Grav\Plugin
 */
class StaticSocialEmbedsPlugin extends Plugin
{
    /**
     * @return array
     *
     * The getSubscribedEvents() gives the core a list of events
     *     that the plugin wants to listen to. The key of each
     *     array section is the event that the plugin listens to
     *     and the value (in the form of an array) contains the
     *     callable (or function) as well as the priority. The
     *     higher the number the higher the priority.
     */
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => [
                ['autoload', 100001],
                ['onPluginsInitialized', 0]
            ],
        ];
    }

    /**
     * [onPluginsInitialized:100000] Composer autoload.
     *
     * @return ClassLoader
     */
    public function autoload()
    {
        return require __DIR__ . '/vendor/autoload.php';
    }

    /**
     * Initialize the plugin
     */
    public function onPluginsInitialized()
    {
        if ($this->isAdmin()) {
            // Don't proceed if we are in the admin plugin
            return;
        }

        require_once __DIR__ . '/classes/plugin/SSEShortcode.php';

        // Enable the main event we are interested in
        $this->enable([
            'onShortcodeHandlers' => ['onShortcodeHandlers', 0],
            'onTwigTemplatePaths' => ['onTwigTemplatePaths', 0],
            'onAssetsInitialized' => ['onAssetsInitialized', 0]
        ]);
    }

    /**
     * Add current directory to twig lookup paths.
     */
    public function onTwigTemplatePaths()
    {
        $this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
    }

    /**
     * Adds assets.
     */
    public function onAssetsInitialized()
    {
        /** @var $assets Assets */
        $assets = $this->grav['assets'];

        if ($this->config->get('plugins.static-social-embeds.include_font_awesome_5', true)) {
            $assets->add('https://use.fontawesome.com/releases/v5.1.0/css/all.css');
        }

        if ($this->config->get('plugins.static-social-embeds.built_in_css', true)) {
            $assets->add('plugin://static-social-embeds/assets/css-compiled/sse.min.css', 4);
        }

        if ($this->config->get('plugins.static-social-embeds.built_in_js', true)) {
            $assets->addJs('plugin://static-social-embeds/assets/js/sse.js', 4, true, 'defer');
        }
    }

    /**
     * Registers shortcodes
     */
    public function onShortcodeHandlers()
    {
        $this->grav['shortcode']->registerAllShortcodes(__DIR__ . '/classes/shortcodes');
    }
}
