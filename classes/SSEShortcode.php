<?php

namespace Grav\Plugin\Shortcodes;

use Doctrine\Common\Cache\FilesystemCache;
use Grav\Common\Cache;
use RocketTheme\Toolbox\ResourceLocator\ResourceLocatorInterface;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;


abstract class SSEShortcode extends Shortcode
{
    const CACHE_PREFIX = 'sse';
    const TEMPLATES_DIRECTORY = 'partials/static-social-embeds/';

    /** @var string The cache directory for API data  */
    protected $cache_dir;

    /** @var string The cache directory for downloaded images/videos */
    protected $images_dir;

    /** @var string The public path to the cache directory for downloaded images/videos */
    protected $images_path;

    /** @var string The temporary directory for downloaded medias */
    protected $tmp_dir;

    /** @var FilesystemCache The cache driver for API data */
    protected $cache;


    /**
     * SSEShortcode constructor.
     */
    public function __construct()
    {
        parent::__construct();

        /** @var $locator ResourceLocatorInterface */
        $locator = $this->grav['locator'];

        $this->cache_dir   = $locator->findResource('cache://static-social-embeds', true, true);
        $this->images_dir  = $locator->findResource('cache://images/static-social-embeds', true, true);
        $this->images_path = $locator->findResource('cache://images/static-social-embeds', false, true);
        $this->tmp_dir     = $locator->findResource('tmp://static-social-embeds', true, true);

        $this->cache = new FilesystemCache($this->cache_dir, '.sse.data');
    }

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

    /**
     * Loads an URL but caches the result if it was already fetched, using the URL as key.
     *
     * Calls the shortcode's underlying getData method and cache the data returned by this method.
     *
     * @param $url string The URL.
     * @return array Fetched data.
     */
    private function getDataCached($url)
    {
        /** @var $cache Cache */
        $cache_id = self::CACHE_PREFIX . '-' . $this->getShortcodeName() . '-' . $url;

        if ($this->cache->contains($cache_id))
        {
            return (array) $this->cache->fetch($cache_id);
        }
        else
        {
            // If the call ended in error, we cache for one minute to allow us to retry,
            // while avoiding rate limits if there are lots of visitors.
            $data = $this->getData($url);
            $this->cache->save($cache_id, $data, isset($data['errors']) ? 60 : 0);
            return $data;
        }
    }

    /**
     * Registers the shortcode.
     */
    public function init()
    {
        $this->shortcode->getHandlers()->add($this->getShortcodeName(), function(ShortcodeInterface $sc)
        {
            $template_context = array_merge($this->getDataCached(trim($sc->getBbCode())), [
                'config' => $this->config->get('plugins.static-social-embeds')
            ]);

            return $this->grav['twig']->processTemplate(
                self::TEMPLATES_DIRECTORY . $this->getShortcodeName() . '.html.twig',
                $template_context
            );
        });
    }

    /**
     * Fetches a media and either downloads it and returns the local URL, or returns the URL directly, according
     * to the configuration.
     *
     * @param $url string The downloaded URL
     * @param string $format The format (either “image”, “video” or “auto” to determine the type according to the extension.
     * @return string The URL to use (either locale or remote).
     */
    protected function fetchMedia($url, $format = 'auto')
    {
        $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        $extension = explode(':', $extension)[0]; // Twitter uses a .ext:image-size format (e.g. .png:small)

        if ($format == 'auto')
        {
            switch ($extension)
            {
                case 'mp4':
                case 'mkv':
                case 'mov':
                case 'm4a':
                    $format = 'video';
                    break;

                case 'png':
                case 'jpg':
                case 'jpeg':
                case 'jpe':
                case 'gif': // Animated GIF are fetched as MP4 on nearly all social networks
                case 'tiff':
                default:
                    $format = 'image';
                    break;
            }
        }


        // Are we allowed to cache images?
        if ($format == 'image' && !$this->config->get('plugins.static-social-embeds.downloaded_content.images'))
        {
            return $url;
        }

        // Or videos?
        if ($format == 'video' && !$this->config->get('plugins.static-social-embeds.downloaded_content.videos'))
        {
            return $url;
        }

        $ch = curl_init();
        $tmp_file_path = $this->tmp_dir . '/' . sha1($url) . '.' . $extension;
        $tmp_dir_name = dirname($tmp_file_path);

        if (!is_dir($tmp_dir_name)) mkdir($tmp_dir_name, $this->config->get('system.images.cache_perms', '0755'), true);

        $tmp_file = fopen($tmp_file_path, 'w');

        curl_setopt_array($ch, [
            CURLOPT_FILE => $tmp_file,
            CURLOPT_TIMEOUT => 3600,
            CURLOPT_URL => $url
        ]);

        curl_exec($ch);
        fclose($tmp_file);

        $storage_file_name = hash_file('sha256', $tmp_file_path) . '.' . $extension;
        $storage_file_path = [];

        // Getting the length of the filename before the extension
        $base_name_length = strlen(explode('.', $storage_file_name)[0]);

        for ($i = 0; $i < min($base_name_length, 5); $i++)
        {
            $storage_file_path[] = $storage_file_name[$i];
        }

        $storage_file_path = '/' . implode('/', $storage_file_path) . '/' . $storage_file_name;
        $storage_file_dir = dirname($this->images_dir . $storage_file_path);

        if (!is_dir($storage_file_dir)) mkdir($storage_file_dir, $this->config->get('system.images.cache_perms', '0755'), true);

        rename($tmp_file_path, $this->images_dir . $storage_file_path);

        return $this->grav['base_url'] . '/' . $this->images_path . $storage_file_path;
    }

    /**
     * Fetches an image and either downloads it and returns the local URL, or returns the URL directly, according
     * to the configuration.
     *
     * @param $url string The downloaded URL
     * @return string The URL to use (either locale or remote).
     */
    protected function fetchImage($url)
    {
        return $this->fetchMedia($url, 'image');
    }

    /**
     * Fetches a video and either downloads it and returns the local URL, or returns the URL directly, according
     * to the configuration.
     *
     * @param $url string The downloaded URL
     * @return string The URL to use (either locale or remote).
     */
    protected function fetchVideo($url)
    {
        return $this->fetchMedia($url, 'video');
    }

    /**
     * From a number of milliseconds, returns a formatted version [HH:]mm:ss.
     *
     * @param $milliseconds integer The milliseconds.
     * @return string The formatted version.
     */
    protected function formatMilliseconds($milliseconds)
    {
        $seconds = round($milliseconds / 1000) % 60;
        $minutes = floor($seconds / 60) % 60;
        $hours = floor($minutes / 60);

        if ($hours > 0)
        {
            return sprintf('%u:%02u:%02u', $hours, $minutes, $seconds);
        }
        else
        {
            return sprintf('%02u:%02u', $minutes, $seconds);
        }
    }
}
