<?php

namespace Grav\Plugin\Shortcodes;

use Grav\Common\Cache;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;


abstract class SSEShortcode extends Shortcode
{
    const CACHE_PREFIX = 'sse';
    const TEMPLATES_DIRECTORY = 'partials/static-social-embeds/';

    /**
     * The shortcode name (e.g. "tweet"), used for the shortcode of course,
     * but also for the template name (in this example, "partials/static-social-embeds/tweet.html.twig").
     *
     * @return string The shortcode name
     */
    abstract protected function getShortcodeName();

    /**
     * From the URL, retrieves and returns data transferred to the template.
     *
     * Images & videos can be retrieved using the downloadMedia method.
     *
     * The result of this method is cached against the URL, so it will be called once per URL (except if
     * the cache is emptied).
     *
     * @param $url string The base URL given by the user.
     * @return array The data related to the given content, that will become the template's context.
     */
    abstract protected function getData($url);

    private function getDataCached($url)
    {
        /** @var $cache Cache */
        $cache = $this->grav['cache'];
        $cache_id = self::CACHE_PREFIX . '-' . $this->getShortcodeName() . '-' . $url;

        if ($cache->contains($cache_id))
        {
            return $cache->fetch($cache_id);
        }
        else
        {
            $data = $this->getData($url);
            $cache->save($cache_id, $data);
            return $data;
        }
    }

    public function init()
    {
        $this->shortcode->getHandlers()->add($this->getShortcodeName(), function(ShortcodeInterface $sc)
        {
            $template_context = array_merge($this->getDataCached(trim($sc->getBbCode())), [
                'sse_theme' => $this->config->get('plugins.static-social-embeds.theme')
            ]);

            return $this->grav['twig']->processTemplate(
                self::TEMPLATES_DIRECTORY . $this->getShortcodeName() . '.html.twig',
                $template_context
            );
        });
    }
}
