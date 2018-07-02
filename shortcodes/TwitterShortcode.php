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

        if (!$tweet_id) return [];

        $tweet_data = $this->callTwitter('https://api.twitter.com/1.1/statuses/show/' . $tweet_id . '.json?tweet_mode=extended');

        error_log(json_encode($tweet_data));

        $data = $this->processTweet($tweet_data);

        if (isset($tweet_data['quoted_status']))
        {
            $data['quoted_tweet'] = $this->processTweet($tweet_data['quoted_status']);
        }

        return $data;
    }

    private function processTweet($tweet_data)
    {
        $tweet_html = Tweet::make(
            $tweet_data['full_text'],
            $tweet_data['entities']['urls'],
            $tweet_data['entities']['user_mentions'],
            $tweet_data['entities']['hashtags']
        )->linkify();

        // We want to be able to stylise the @ & #.
        $tweet_html = str_replace('@<a', '<span class="sse-twitter-handle-at">@</span><a', $tweet_html);
        $tweet_html = str_replace('#<a', '<span class="sse-twitter-hashtag-hash">#</span><a', $tweet_html);

        $processed_tweet = [
            'author' => [
                'name'         => $tweet_data['user']['screen_name'],
                'display_name' => $tweet_data['user']['name'],
                'link'         => 'https://twitter.com/' . $tweet_data['user']['screen_name'],
                'avatar'       => $tweet_data['user']['profile_image_url_https'],
                'verified'     => $tweet_data['user']['verified'],
                'protected'    => $tweet_data['user']['protected']
            ],
            'tweet' => [
                'raw'    => $tweet_data['full_text'],
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

        if (isset($tweet_data['entities']) && isset($tweet_data['entities']['media']))
        {
            $processed_tweet['tweet']['medias'] = [];
            foreach ($tweet_data['entities']['media'] as $media)
            {
                $processed_media = [
                    'src'       => $media['media_url_https'],
                    'src_small' => $media['media_url_https'],
                    'link'      => $media['expanded_url'],
                    'type'      => $media['type']
                ];

                if (isset($media['sizes']['small']))
                {
                    $processed_media['src_small'] .= ':small';
                }

                if (isset($media['video_info']))
                {
                    $processed_media['video'] = [
                        'src'          => $media['video_info']['variants'][0]['url'],
                        'content_type' => $media['video_info']['variants'][0]['content_type'],
                        'aspect_ratio' => $media['video_info']['aspect_ratio']
                    ];
                }

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
