# v1.1.6
## 26-07-2020

1. [](#bugfix)
    * Fixed “ProblemChecker class not found” due to bad classes autoload ([#5](https://github.com/Nebulius/grav-plugin-static-social-embeds/pull/5), thanks to funilrys!).

# v1.1.5
## 22-04-2019

1. [](#bugfix)
    * Fixed Matodon medias not correctly downloaded (they were downloaded, but the local copy was not used).
    * Fixed Mastodon's custom emojis not correclty supported (they are now correctly displayed in spoiler texts and display names).
1. [](#improved)
    * Improved some embeds stylings to match their models.
    * Improved compatibility with some themes.
    * Improved embeds accessibility a little bit.

# v1.1.4
## 21-04-2019

1. [](#bugfix)
    * Fixed plugin admin rendering error in English, because the blueprint referred translation files with an inconsistent format (#1).
    * Miscellaneous minor fixes and improvements.
1. [](#improved)
    * Improved styles and in-flow integration (margins…).
    * Improved video playing:
      * the video will stop if the user switches to the next image;
      * the controls are not under the navigation links.
    * Updated documentation for new Twitter developer accounts.

# v1.1.3
## 23-08-2018

1. [](#bugfix)
    * Fixed error while embedding non-existant Instagram post.

# v1.1.2
## 14-08-2018

1. [](#bugfix)
    * Fixed Instagram posts failing to be retrieved in some cases.

# v1.1.1
## 14-08-2018

1. [](#bugfix)
    * Fixed a regression where download options were no longer working since the per-network download options (oh the irony).

# v1.1.0
## 11-07-2018

1. [](#new)
    * Added [Pleroma](https://pleroma.social) support (using the same `toot` shortcode as Mastodon).
1. [](#improved)
    * Download options can be configured per-network.
    * Better explanations regarding Firefox privacy shield and Instagram changing URLs.
1. [](#bugfix)
    * Fixed some Mastodon URLs wrongly parsed.

# v1.0.1
## 07-07-2018

1. [](#bugfix)
    * Fixes broken icons if the template does not provide Font Awesome 5.1.0+.
    * Small CSS fixes on the default theme (and every theme, for that matter).

# v1.0.0
##  07-07-2018

1. [](#new)
    * First version
    * Support for Twitter, Mastodon and Instagram, with light or dark embeds, using simple shortcodes.
    * Support for texts, images, videos, GIF, multiple medias, Mastodon's CW.
    * Images and videos can be downloaded locally for enhanced independence.
