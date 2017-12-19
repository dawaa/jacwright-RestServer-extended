<?php
namespace DAwaa\Core\Modules;

class Url {
    /**
     * Helps us with getting the current URL with ease, it also looks to if
     * we have https available, if not fallback to good ol' http.
     *
     * @see self::getUrlScheme()      To determine what to use, http or https
     * @see self::stripQueryFromUrl() Strips away query parameters from URL
     *
     * @param bool $stripped If we should strip off query params or not
     *
     * @return string $url Current URL
     */
    public static function getUrl($stripQuery = false): string {
        $url = self::getUrlScheme() .
            '://' .
            "{$_SERVER['HTTP_HOST']}/" .
            ltrim( $_SERVER['REQUEST_URI'], '/' );

        if ( $stripQuery === true ) {
            $url = self::stripQueryFromUrl( $url );
        }

        // Make sure we only have one trailing slash
        $url = rtrim( $url, '/' );
        if ( strpos( $url, '?' ) !== false ) {
        } else {
            $url .= '/';
        }

        return $url;
    }

    /**
     * Retrieves all segments in an array from the SERVER object.
     *
     * @return array
     */
    public static function getSegments(): array {
        return array_values(array_filter(
            explode(
                '/',
                parse_url(
                    $_SERVER[ 'REQUEST_URI' ],
                    PHP_URL_PATH
                )
            )
        ));
    }

    /**
     * Determines if the server can serve using https, otherwise
     * fallback to https:
     *
     * @return string 'http' or 'https'
     */
    private static function getUrlScheme(): string {
        return 'http' . (isset($_SERVER['HTTPS']) ? 's' : '');
    }

    /**
     * Strips everything (including) after ? in the url
     *
     * @param string $url Current URL
     *
     * @return string Current URL but stripped
     */
    protected static function stripQueryFromUrl($url): string {
        return strtok( $url, '?' );
    }
}
