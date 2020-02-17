<?php

namespace FluentForm\App\Services\FormBuilder;

use FluentForm\App\Services\Browser\Browser;
use FluentForm\Request;

class EditorShortcodeParser
{
    /**
     * Available dynamic short codes
     * @var null
     */
    private static $dynamicShortcodes = null;

    /**
     * mappings of methods to parse the shortcode
     * @var array
     */
    private static $handlers = [
        'ip'         => 'parseIp',
        'date.m/d/Y' => 'parseDate',
        'date.d/m/Y' => 'parseDate',

        'embed_post.ID'         => 'parsePostProperties',
        'embed_post.post_title' => 'parsePostProperties',
        'embed_post.permalink'  => 'parsePostProperties',
        'http_referer'          => 'parseWPProperties',

        'wp.admin_email' => 'parseWPProperties',
        'wp.site_url'    => 'parseWPProperties',
        'wp.site_title'  => 'parseWPProperties',

        'user.ID'           => 'parseUserProperties',
        'user.display_name' => 'parseUserProperties',
        'user.first_name'   => 'parseUserProperties',
        'user.last_name'    => 'parseUserProperties',
        'user.user_email'   => 'parseUserProperties',
        'user.user_login'   => 'parseUserProperties',

        'browser.name'     => 'parseBrowserProperties',
        'browser.platform' => 'parseBrowserProperties',

        'get.param_name' => 'parseQueryParam'
    ];

    /**
     * Filter dynamic shortcodes in input value
     * @param  string $value
     * @return string
     */
    public static function filter($value, $form)
    {
        if (is_null(static::$dynamicShortcodes)) {
            static::$dynamicShortcodes = fluentFormEditorShortCodes();
        }

        $filteredValue = '';
        foreach (static::parseValue($value) as $handler) {
            if (isset(static::$handlers[$handler])) {
                $filteredValue .= call_user_func_array(
                    [__CLASS__, static::$handlers[$handler]],
                    ['{' . $handler . '}', $form]
                );
            } elseif (strpos($handler, 'get.') !== false) {
                $filteredValue .= static::parseQueryParam($handler);
            } else if (strpos($handler, 'user.') !== false) {
                $value = self::parseUserProperties($handler);
                if (is_array($value) || is_object($value)) {
                    return '';
                }
                return $value;
            } else if (strpos($handler, 'date.') !== false) {
                return self::parseDate($handler);
            } else if (strpos($handler, 'embed_post.meta.') !== false) {
                $key = substr(str_replace(['{', '}'], '', $value), 16);
                global $post;
                if ($post) {
                    $value = get_post_meta(53, $key, true);
                    if (!is_array($value) && !is_object($value)) {
                        return $value;
                    }
                }
                return '';
            } else if (strpos($handler, 'embed_post.') !== false) {
                return self::parsePostProperties($handler, $form);
            } else {
                $handlerValue = apply_filters('fluentform_editor_shortcode_callback_' . $handler, '{' . $handler . '}', $form);
                // In not found then return the original please
                $filteredValue .= $handlerValue;
            }
        }

        return $filteredValue;
    }

    /**
     * Parse the curly braced shortcode into array
     * @param  string $value
     * @return mixed
     */
    private static function parseValue($value)
    {
        if (!is_array($value)) {
            return preg_split(
                '/{(.*?)}/',
                $value,
                null,
                PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
            );
        }

        return $value;
    }

    /**
     * Declare all parsers and must be [private] static methods
     */

    /**
     * Parse loggedin user properties
     * @param  string $value
     * @return string
     */
    private static function parseUserProperties($value, $form = null)
    {
        if ($user = wp_get_current_user()) {
            $prop = substr(str_replace(['{', '}'], '', $value), 5);
            return $user->{$prop};
        }

        return '';
    }

    /**
     * Parse loggedin user properties
     * @param  string $value
     * @return string
     */
    private static function parsePostProperties($value, $form = null)
    {
        global $post;
        if (!$post) {
            return '';
        }

        $prop = substr(str_replace(['{', '}'], '', $value), 11);
        if ($prop == 'permalink') {
            return htmlspecialchars(site_url(wp_unslash($_SERVER['REQUEST_URI'])));
        }
        if (property_exists($post, $prop)) {
            return $post->{$prop};
        }
        return '';
    }


    /**
     * Parse WP Properties
     * @param  string $value
     * @return string
     */
    private static function parseWPProperties($value, $form = null)
    {
        if ($value == '{wp.admin_email}') {
            return get_option('admin_email');
        }
        if ($value == '{wp.site_url}') {
            return site_url();
        }
        if ($value == '{wp.site_title}') {
            return get_option('blogname');
        }
        if ($value == 'http_referer') {
            return wp_get_referer();
        }

        return '';
    }

    /**
     * Parse browser/user-agent properties
     * @param  string $value
     * @return string
     */
    private static function parseBrowserProperties($value, $form = null)
    {
        $browser = new Browser;
        if ($value == '{browser.name}') {
            return $browser->getBrowser();
        } elseif ($value == '{browser.platform}') {
            return $browser->getPlatform();
        }

        return '';
    }

    /**
     * Parse ip shortcode
     * @param  string $value
     * @return string
     */
    private static function parseIp($value, $form = null)
    {
        $ip = Request::getIp();
        return $ip ? $ip : $value;
    }

    /**
     * Parse date shortcode
     * @param  string $value
     * @return string
     */
    private static function parseDate($value, $form = null)
    {
        $format = substr(str_replace(['}', '{'], '', $value), 5);
        $date = date($format);
        return $date ? $date : '';
    }

    /**
     * Parse request query param.
     *
     * @param  string $value
     * @param  \stdClass $form
     * @return string
     */
    public static function parseQueryParam($value)
    {
        $exploded = explode('.', $value);
        $param = array_pop($exploded);

        return isset($_REQUEST[$param]) ? wp_kses_post($_REQUEST[$param]) : '';
    }
}
