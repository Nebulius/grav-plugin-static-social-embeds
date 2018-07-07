<?php
namespace Grav\Plugin;

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
        require_once __DIR__ . '/vendor/autoload.php';
        require_once __DIR__ . '/classes/SSEShortcode.php';

        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0]
        ];
    }

    /**
     * Initialize the plugin
     */
    public function onPluginsInitialized()
    {
        // Don't proceed if we are in the admin plugin
        if ($this->isAdmin()) {
            return;
        }

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
        if ($this->config->get('plugins.static-social-embeds.include_font_awesome_5', true))
        {
            $this->grav['assets']->add('https://use.fontawesome.com/releases/v5.1.0/css/all.css');
        }

        if ($this->config->get('plugins.static-social-embeds.use_built_in_css', true))
        {
            $this->grav['assets']->add('plugin://static-social-embeds/assets/css-compiled/sse.min.css', 4);
        }

        if ($this->config->get('plugins.static-social-embeds.use_built_in_js', true))
        {
            $this->grav['assets']->add('plugin://static-social-embeds/assets/js/sse.js', 4);
        }
    }

    /**
     * Registers shortcodes
     */
    public function onShortcodeHandlers()
    {
        $this->grav['shortcode']->registerAllShortcodes(__DIR__.'/shortcodes');
    }
}
