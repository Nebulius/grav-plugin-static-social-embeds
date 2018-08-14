<?php

namespace Grav\Plugin\Shortcodes;


class InstagramShortcode extends SSEShortcode
{
    /**
     * The shortcode name (e.g. "tweet"), used for the shortcode of course,
     * but also for the template name (in this example, "partials/static-social-embeds/tweet.html.twig").
     *
     * @return string The shortcode name
     */
    protected function getShortcodeName()
    {
        return 'instagram';
    }

    /**
     * The network name (e.g. “twitter”), used to retrieve related configuration.
     *
     * @return string The network name
     */
    protected function getNetworkName()
    {
        return 'instagram';
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
        // There is no Instagram API to retrieve content.
        // We scrap a JSON placed into the page containing raw data and use that.
        // Source: InstagramBridge from RSSBridge, maintained by pauder, in public domain.

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_TIMEOUT        => 3600,
            CURLOPT_URL            => $url,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $raw_instagram_html = curl_exec($ch);

        $error_code = curl_errno($ch);
        $error = $error_code != 0 ? (': #' . $error_code . ' - ' . curl_error($ch)) : '';

        curl_close($ch);

        if (!$raw_instagram_html)
            return ['errors' => [['code' => 0, 'message' => 'Unable to retrieve instagram post' . $error]], 'url' => $url];

        preg_match('/window\._sharedData = (.*);<\/script>/', $raw_instagram_html, $matches, PREG_OFFSET_CAPTURE, 0);

        $post = json_decode($matches[1][0], true);

        if (!$post
            || !isset($post['entry_data'])
            || !isset($post['entry_data']['PostPage'])
            || !isset($post['entry_data']['PostPage'][0])
            || !isset($post['entry_data']['PostPage'][0]['graphql'])
            || !isset($post['entry_data']['PostPage'][0]['graphql']['shortcode_media']))
        {
            return ['errors' => [['code' => 0, 'message' => 'Unable to retrieve instagram post']], 'url' => $url];
        }

        // Instagram Post or Inner Post (as you like)
        $ipost = $post['entry_data']['PostPage'][0]['graphql']['shortcode_media'];

        $processed_post = [];

        // Post author

        if (isset($ipost['owner']))
        {
            $processed_post['author'] = [
                'name'         => $ipost['owner']['username'],
                'display_name' => $ipost['owner']['full_name'],
                'link'         => 'https://www.instagram.com/' . $ipost['owner']['username'] . '/',
                'private'      => $ipost['owner']['is_private'],
                'verified'     => $ipost['owner']['is_verified'],
                'avatar'       => $this->fetchImage($ipost['owner']['profile_pic_url'])
            ];
        }

        // Post link

        if (isset($ipost['shortcode']))
        {
            $processed_post['link'] = 'https://www.instagram.com/p/' . $ipost['shortcode'] . '/';
        }

        // Post date

        if (isset($ipost['taken_at_timestamp']))
        {
            $processed_post['date'] = $ipost['taken_at_timestamp'];
        }

        // Post location

        if (isset($ipost['location']))
        {
            $processed_post['location'] = $ipost['location'];
        }

        // Post caption

        if (isset($ipost['edge_media_to_caption'])
            && isset($ipost['edge_media_to_caption']['edges'])
            && isset($ipost['edge_media_to_caption']['edges'][0])
            && isset($ipost['edge_media_to_caption']['edges'][0]['node'])
            && isset($ipost['edge_media_to_caption']['edges'][0]['node']['text']))
        {
            $caption = $ipost['edge_media_to_caption']['edges'][0]['node']['text'];

            $processed_post['caption_raw'] = $caption;

            $caption = preg_replace('/#([^#\s]+)/', '<a href="https://www.instagram.com/explore/tags/$1/">$0</a>', $caption);
            $caption = preg_replace('/@([a-zA-Z0-9_]+)/', '<a href="https://www.instagram.com/$1/">$0</a>', $caption);

            $processed_post['caption'] = $caption;
        }

        // Likes & comments

        $processed_post['stats'] = [];

        if (isset($ipost['edge_media_to_comment']) && isset($ipost['edge_media_to_comment']['count']))
        {
            $processed_post['stats']['comments'] = $ipost['edge_media_to_comment']['count'];
        }

        if (isset($ipost['edge_media_preview_like']) && isset($ipost['edge_media_preview_like']['count']))
        {
            $processed_post['stats']['likes'] = $ipost['edge_media_preview_like']['count'];
        }

        // Medias

        // Either there is only one media in the post, and it is at the root of the internal port,
        // or there are multiple medias, and they are in edge_sidecar_to_children.edge.x.node

        $processed_post['medias'] = [];

        if (isset($ipost['edge_sidecar_to_children'])
            && isset($ipost['edge_sidecar_to_children']['edges']))
        {
            foreach ($ipost['edge_sidecar_to_children']['edges'] as $edge)
            {
                if (!isset($edge['node'])) continue;

                $processed_media = $this->processMedia($edge['node']);
                if ($processed_media) $processed_post['medias'][] = $processed_media;
            }
        }
        else
        {
            $processed_media = $this->processMedia($ipost);
            if ($processed_media) $processed_post['medias'][] = $processed_media;
        }

        return $processed_post;
    }

    /**
     * Processes a media, extracts informations about it and saves it locally if needed, then returns a
     * description in an array.
     *
     * @param $imedia array An Instagram media object
     * @return array|bool Media description or false if invalid.
     */
    private function processMedia($imedia)
    {
        $processed_media = [];

        // First get the type

        if (!isset($imedia['__typename'])) return false;

        switch(strtolower($imedia['__typename']))
        {
            case 'graphsidecar':
            case 'graphimage':
                $processed_media['type'] = 'image';
                break;

            case 'graphvideo':
                $processed_media['type'] = 'video';
                break;

            default:
                return false;
        }

        if (isset($imedia['display_resources']))
        {
            $best_src = null;
            $best_width = 0;
            $best_height = 0;

            foreach ($imedia['display_resources'] as $resource)
            {
                if (!isset($resource['config_height']) || !isset($resource['config_width']) || !isset($resource['src'])) continue;
                if ($resource['config_width'] <= $best_width) continue;

                $best_src = $resource['src'];
                $best_width = $resource['config_width'];
                $best_height = $resource['config_height'];
            }

            if ($best_src != null)
            {
                $processed_media['src'] = $this->fetchImage($best_src); // This is either the image or the video preview: always an image.
                $processed_media['dimensions'] = [
                    'width'  => $best_width,
                    'height' => $best_height
                ];
            }
        }
        else if (isset($imedia['display_url']) && !empty($imedia['display_url']))
        {
            $processed_media['src'] = $this->fetchImage($imedia['display_url']);
            $processed_media['dimensions'] = isset($imedia['dimensions']) ? $imedia['dimensions'] : null;
        }

        if (($processed_media['type'] === 'video' || $imedia['is_video']) && isset($imedia['video_url']))
        {
            $processed_media['src_video'] = $this->fetchVideo($imedia['video_url']);

            if (isset($imedia['video_view_count']))
            {
                $processed_media['views'] = $imedia['video_view_count'];
            }
        }

        return $processed_media;
    }
}
