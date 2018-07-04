<?php
namespace Grav\Plugin\Shortcodes;

use Jedkirby\TweetEntityLinker\Tweet;


class TwitterShortcode extends SSEShortcode
{

    /**
     * The shortcode name (e.g. "tweet"), used for the shortcode of course,
     * but also for the template name (in this example, "partials/static-social-embeds/tweet.html.twig").
     *
     * @return string The shortcode name
     */
    protected function getShortcodeName()
    {
        return "tweet";
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
        $tweet_id = null;

        if (preg_match('#https?://twitter\.com/(?:\#!/)?(\w+)/status(?:es)?/(\d+)#is', $url, $match))
        {
            $tweet_id = $match[2];
        }

        if (!$tweet_id) return ['errors' => [['code' => 0, 'message' => 'Not a tweet']], 'url' => $url];

        $tweet_data = $this->callTwitter('https://api.twitter.com/1.1/statuses/show/' . $tweet_id . '.json?tweet_mode=extended&include_ext_alt_text=true');

        // Request failed?
        if (isset($tweet_data['errors']))
        {
            return array_merge($tweet_data, ['url' => $url]);
        }

        $data = $this->processTweet($tweet_data);

        if (isset($tweet_data['quoted_status']))
        {
            $data['quoted_tweet'] = $this->processTweet($tweet_data['quoted_status'], true);
        }

        return $data;
    }

    private function processTweet($tweet_data, $is_quoted_tweet = false)
    {
        $tweet_raw = $tweet_data['full_text'];

        $tweet_html = Tweet::make(
            $tweet_data['full_text'],
            $tweet_data['entities']['urls'],
            $tweet_data['entities']['user_mentions'],
            $tweet_data['entities']['hashtags']
        )->linkify();

        // We want to be able to stylise the @ & #.
        $tweet_html = str_replace('@<a', '<span class="sse-twitter-handle-at">@</span><a', $tweet_html);
        $tweet_html = str_replace('#<a', '<span class="sse-twitter-hashtag-hash">#</span><a', $tweet_html);

        // If there are images, an un-linkified link remains
        if (isset($tweet_data['extended_entities']) && isset($tweet_data['extended_entities']['media']))
        {
            $tweet_raw = $this->removeLastTCOLink($tweet_raw);
            $tweet_html = $this->removeLastTCOLink($tweet_html);
        }

        $processed_tweet = [
            'author' => [
                'name'         => $tweet_data['user']['screen_name'],
                'display_name' => $tweet_data['user']['name'],
                'link'         => 'https://twitter.com/' . $tweet_data['user']['screen_name'],
                'avatar'       => $this->fetchImage($tweet_data['user']['profile_image_url_https']),
                'verified'     => $tweet_data['user']['verified'],
                'protected'    => $tweet_data['user']['protected']
            ],
            'tweet' => [
                'raw'    => $tweet_raw,
                'html'   => $tweet_html,
                'link'   => 'https://twitter.com/' . $tweet_data['user']['screen_name'] . '/status/' . $tweet_data['id_str'],
                'date'   => strtotime($tweet_data['created_at']),
                'source' => $tweet_data['source'],
                'stats'  => [
                    'retweets' => $tweet_data['retweet_count'],
                    'likes' => $tweet_data['favorite_count']
                ],
            ]
        ];

        if (isset($tweet_data['extended_entities']) && isset($tweet_data['extended_entities']['media']))
        {
            $processed_tweet['tweet']['medias'] = [];
            foreach ($tweet_data['extended_entities']['media'] as $media)
            {
                $processed_media = [
                    'src'       => $this->fetchImage($media['media_url_https']),
                    'src_small' => $media['media_url_https'],
                    'link'      => $media['expanded_url'],
                    'alt'       => $media['ext_alt_text'],
                    'type'      => $media['type']
                ];

                if (isset($media['sizes']['small']))
                {
                    $processed_media['src_small'] .= ':small';
                }

                $processed_media['src_small'] = $this->fetchImage($processed_media['src_small']);

                if (isset($media['video_info']) && !$is_quoted_tweet)
                {
                    // for each variant we keep the highest bitrate
                    $video_variants = [];

                    foreach ($media['video_info']['variants'] as $variant)
                    {
                        if (!isset($video_variants[$variant['content_type']]) || !isset($variant['bitrate']) || $variant['bitrate'] >= $video_variants[$variant['content_type']]['bitrate'])
                        {
                            $video_variants[$variant['content_type']] = [
                                'src'          => $variant['url'],
                                'bitrate'      => isset($variant['bitrate']) ? $variant['bitrate'] : 0
                            ];
                        }
                    }

                    // Only fetch actually selected video files
                    foreach ($video_variants as $content_type => $variant)
                    {
                        $video_variants[$content_type]['src'] = $this->fetchVideo($variant['src']);
                    }

                    $processed_media['video'] = [
                        'aspect_ratio'   => $media['video_info']['aspect_ratio'],
                        'duration'       => isset($media['video_info']['duration_millis']) ? $media['video_info']['duration_millis'] : 0,
                        'duration_human' => isset($media['video_info']['duration_millis']) ? $this->formatMilliseconds($media['video_info']['duration_millis']) : '',
                        'variants'       => $video_variants
                    ];
                }

                error_log("\n\nMEDIA: \n" . json_encode($processed_media));

                $processed_tweet['tweet']['medias'][] = $processed_media;
            }
        }

        if ($tweet_data['in_reply_to_status_id_str'] !== null)
        {
            $processed_tweet['tweet']['in_reply_to'] = [
                'link' => 'https://twitter.com/' . $tweet_data['in_reply_to_screen_name'] . '/status/' . $tweet_data['in_reply_to_status_id_str'],
                'name' => $tweet_data['in_reply_to_screen_name']
            ];
        }

        return $processed_tweet;
    }

    private function removeLastTCOLink($tweet_content)
    {
        return preg_replace('/https:\/\/t\.co\/([a-zA-Z0-9]{9,16})$/', '', $tweet_content);
    }

    private function buildBaseInfo($method, $endpoint, $params)
    {
        $r = [];
        ksort($params);

        foreach($params as $key=>$value)
        {
            $r[] = "$key=" . rawurlencode($value);
        }

        return $method . "&" . rawurlencode($endpoint) . '&' . rawurlencode(implode('&', $r));
    }

    private function buildAuthorizationHeader($oauth)
    {
        $r = 'Authorization: OAuth ';
        $values = array();

        foreach($oauth as $key => $value)
            $values[] = "$key=\"" . rawurlencode($value) . "\"";

        $r .= implode(', ', $values);

        return $r;
    }

    private function callTwitter($url)
    {
        $url = ltrim($url);

        $consumer_key = $this->config->get('plugins.static-social-embeds.twitter.consumer_key');
        $consumer_secret = $this->config->get('plugins.static-social-embeds.twitter.consumer_secret');
        $oauth_access_token = $this->config->get('plugins.static-social-embeds.twitter.access_token');
        $oauth_access_token_secret = $this->config->get('plugins.static-social-embeds.twitter.access_token_secret');

        $oauth = [
            'oauth_consumer_key' => $consumer_key,
            'oauth_nonce' => time(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_token' => $oauth_access_token,
            'oauth_timestamp' => time(),
            'oauth_version' => '1.0'
        ];

        $url_parts = explode('?', $url);
        $base_url = $url_parts[0];
        $extra_params = [];

        if (count($url_parts) > 1)
        {
            array_shift($url_parts);
            $query_string = implode('?', $url_parts);

            foreach (explode('&', $query_string) as $part)
            {
                $p = explode('=', $part);
                if (count($p) != 2) continue;
                $extra_params[$p[0]] = $p[1];
            }
        }

        $all_params = array_merge($oauth, $extra_params);

        $base_info = $this->buildBaseInfo('GET', $base_url, $all_params);

        $composite_key = rawurlencode($consumer_secret) . '&' . rawurlencode($oauth_access_token_secret);
        $oauth_signature = base64_encode(hash_hmac('sha1', $base_info, $composite_key, true));
        $oauth['oauth_signature'] = $oauth_signature;

        $header = [$this->buildAuthorizationHeader($oauth), 'Expect:'];
        $options = [
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_HEADER => false,
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false
        ];

        $curl_handle = curl_init();
        curl_setopt_array($curl_handle, $options);
        $json = curl_exec($curl_handle);
        curl_close($curl_handle);

        return json_decode($json, true);
    }
}
