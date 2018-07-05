<?php
/**
 * Created by PhpStorm.
 * User: amaury
 * Date: 04/07/18
 * Time: 22:11
 */

namespace Grav\Plugin\Shortcodes;


class MastodonShortcode extends SSEShortcode
{

    /**
     * The shortcode name (e.g. "tweet"), used for the shortcode of course,
     * but also for the template name (in this example, "partials/static-social-embeds/tweet.html.twig").
     *
     * @return string The shortcode name
     */
    protected function getShortcodeName()
    {
        return 'toot';
    }

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
    protected function getData($url)
    {
        $purl = parse_url($url);

        if ($purl === false)
            return ['errors' => [['code' => 0, 'message' => 'Malformed toot URL']], 'url' => $url];

        $path_parts = explode('/', $purl['path']);
        if (count($path_parts) <= 2)
            return ['errors' => [['code' => 0, 'message' => 'Malformed toot URL']], 'url' => $url];

        // From: https://mamot.fr/@ploum/100244825435451499
        // To:   https://mamot.fr/api/v1/statuses/100244825435451499
        $api_endpoint = $purl['scheme'] . '://' . $purl['host'] . (isset($purl['port']) && $purl['port'] != 80 ? ':' . $purl['port'] : '');
        $api_endpoint .= '/api/v1/statuses/' . $path_parts[2];

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_TIMEOUT        => 3600,
            CURLOPT_URL            => $api_endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $toot = json_decode(curl_exec($ch), true);

        curl_close($ch);

        if ($toot == null)
            return ['errors' => [['code' => 0, 'message' => 'Unable to retrieve toot']], 'url' => $url];

        if (isset($toot['error']))
            return ['errors' => [['code' => 0, 'message' => $toot['error']]], 'url' => $url];


        // We use the JSON almost directly; the only updated thing is the images
        // to store them locally (if enabled).

        $toot['account']['avatar'] = $this->fetchImage($toot['account']['avatar']);
        $toot['account']['fully_qualified_name'] = $toot['account']['username'] . '@' . $purl['host'] . (isset($purl['port']) && $purl['port'] != 80 ? ':' . $purl['port'] : '');

        // Process & store medias

        if (isset($toot['media_attachments']))
        {
            foreach ($toot['media_attachments'] as $id => $media)
            {
                switch ($media['type'])
                {
                    case 'image':
                        $media['url'] = $this->fetchImage($media['url']);
                        $media['preview_url'] = $media['url'];
                        break;

                    case 'video':
                    case 'gifv':
                        $media['preview_url'] = $this->fetchImage($media['preview_url']);
                        $media['url'] = $this->fetchVideo($media['url']);

                        if (isset($media['meta']) && isset($media['meta']['duration']))
                            $media['meta']['duration_human'] = $this->formatMilliseconds($media['meta']['duration'] * 1000);

                        break;

                    case 'unknown':
                        // Normalize to image
                        $media['url'] = $this->fetchImage($media['remote_url']);
                        $media['preview_url'] = $media['url'];
                        break;
                }
            }
        }

        // Process & store emojis

        if (isset($toot['emojis']))
        {
            foreach ($toot['emojis'] as $emoji)
            {
                $toot['content'] = str_replace(
                    ':' . $emoji['shortcode'] . ':',
                    '<img src="' . $this->fetchImage($emoji['url']) . '" alt="' . $emoji['shortcode'] . '" class="sse-mastodon-custom-emoji" />',
                    $toot['content']
                );
            }
        }

        return $toot;
    }
}
