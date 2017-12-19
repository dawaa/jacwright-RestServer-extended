<?php
namespace DAwaa\Core\RestServer;

use \Jacwright\RestServer\RestServer;
use \Jacwright\RestServer\RestFormat;
use \DAwaa\Core\Modules\Url;
use \DAwaa\Core\Modules\DocParser;

/**
 * RestServerExtended
 *
 * We extend the RestServer because its public method getPath()
 * was not removing trailing slash, ending in not finding routes f.e.
 *
 * it found:
 * /v1/users
 *
 * but didn't find:
 * /v1/users/
 *
 * This resolves that, and a few other things as well.
 */
class RestServerExtended extends RestServer {

    public $vars;

    /**
     * We do a check to see if we have namespaced the REST API under
     * a domain name and is not under its own separate domain name
     *
     * @return void
     */
    public function __construct( $Helpers, Resolver $Resolver ) {
        parent::__construct();

        $this->Helpers  = $Helpers;
        $this->Resolver = $Resolver;
        $this->Expander = new Expander( $this->Resolver );

        $uriSegments = Url::getSegments();

        // Parent constructor uses DOCUMENT_ROOT and SCRIPT_FILENAME.. so
        // we better override that
        $this->root = "/" . $uriSegments[ 0 ] . "/";

        $root = $this->Helpers->String->omitForwardSlashes( $this->root );
        if ( $uriSegments[ 0 ] !== $root && $uriSegments[ 1 ] === $root ) {
            $this->root = '/' . $uriSegments[ 0 ] . $this->root;
        }
    }

    /**
     * Original method had issues when visiting the f.e. teachers and teachers/
     * It would see them as two different routes, so we remove using
     * rtrim() before we return
     *
     * @return string
     */
    public function getPath() {
        $path = preg_replace( '/\?.*$/', '', $_SERVER[ 'REQUEST_URI' ] );

        // Remove root from path
        if ( $this->root ) {
            $path = preg_replace(
                '/^' . preg_quote( $this->root, '/' ) . '/',
                '',
                $path
            );
        }

        // Remove trailing format definition like:
        // => /controller/action.json -> /controller/action
        $path = preg_replace( '/\.(\w+)$/i', '', $path );

        // Remove root path from path, like:
        // => /root/path/api -> /api
        if ( $this->rootPath ) {
            $path = str_replace( $this->rootPath, '', $path );
        }

        // Remove trailing slash
        $path = rtrim( $path, '/' );

        return $path;
    }

    /**
     * Using the public instance variable 'vars' we use argument unpacking
     * when instantiating the new object
     *
     * @return void
     */
    public function handle() {
        $this->url    = $this->getPath();
        $this->method = $this->getMethod();
        $this->format = $this->getFormat();

        // @TODO
        //  Doesnt seem to work the way you'd expect it to
        if ( $this->method == 'PUT'
            || $this->method == 'POST'
            || $this->method == 'PATCH' ) {
            $this->data = $this->getData();
        }

        list(
            $ControllerName,
            $method,
            $params,
            $this->params,
            $noAuth
        ) = $this->findUrl();

        $Controller = $this->Resolver->getController(
            $ControllerName,
            ...$this->vars
        );

        // Set the controllers model
        $Controller->model = $this->Resolver->getModel(
            $Controller,
            ...$this->vars
        );

        // Tell Expander which method we are targetting
        $this->Expander->setMethod( $method );
        // Tell Expander which instance of a Controller we will be using
        $this->Expander->setClass( $Controller );


        /**
         * We try to initialize a method called `init()` in
         * both the Controller and its Model.
         *
         * Later we try running a method of the Controller
         * called `authorize()`.
         *
         * Then we finally call the method respective to
         * the route which a function is bound to.
         *
         * Lastly deliver something to the user depending
         * on what we received from the method.
         * Otherwise we run $this->handleError() with errors
         * passed to it.
         */
        try {
            $this->methodCallExists( $Controller, 'init' );
            $this->methodCallExists( $Controller->model, 'init' );

            if ( ! $noAuth && method_exists( $Controller, 'authorize' ) ) {
                if ( ! $Controller->authorize() ) {
                    // @TODO
                    //  Unauthorized returns void
                    $this->sendData( $this->unauthorized( true ) );
                    die;
                }
            }

            // Calls the method in our controller corresponding
            // to the given route
            $result = call_user_func_array(
                array(
                    $Controller,
                    $method
                ),
                $params
            );

            // $expands = $this->Resolver->getExpandables( $method );

            // I guess this is for debugging purposes, avoiding
            // any fatal errors etc..
            if ( $result === false ) {
                return;
            }

            // If result is null, meaning we didn't return anything
            // from the controller we will let the user know about it
            if ( $result === null ) {
                $className = get_class( $Controller );
                $errorResponse = [
                    'status'     => 500, // maybe 502? Bad Gateway
                    'route'      => $this->url,
                    'message'    => "$className#$method() didn't return with a valid response",
                    'devMessage' => "Controller must return with \$this->respondWith( \$response );"
                ];

                $this->sendData( $errorResponse );
                die;
            }

            // This holds our POST arguments..
            $params = isset( $params[0] ) ? $params[0] : $params;


            /**
             * Expandable section where we add expandable options
             * and also call them if so requested by the user.
             */

            // Add expandable options to the result
            $result = $this->Expander->addExpandableOptions( $result );
            // Call methods if any respective methods are defined
            $result = $this->Expander->callMethods( $result );

            if ( $result !== null ) {
                $this->sendData( $result );
            }

        } catch ( RestException $e) {
            $this->handleError( $e->getCode(), $e->getMessage() );
        }
    }

    /**
     * Added splat operator ...$vars to the method. So we can pass
     * in arguments to the Controllers __constructor
     * We assign it to the public instance variable 'vars'
     *
     * @param string $class
     *  Absolute namespace path to class
     *
     * @param string $basePath
     *  We take the URI string and make sure we only return like the
     *  pattern below.
     *  /v1/users/ => users/
     *
     * @param array  ...$vars
     *  Holds any rest parameters
     *   [
     *       $db,
     *       $server
     *   ]
     *
     * @return void
     */
    public function addClass($class, $basePath = '', ...$vars)
    {
        $this->loadCache();

        // What $basePath actually represents
        $resource = $basePath;

        if ( ! $this->cached ) {
            if ( $this->validClass( $class ) ) {
                if ( $this->Helpers->String->isFirstChar( $resource, '/' ) ) {
                    // Subtract first char
                    $resource = substr( $resource, 1 );
                }

                if ( ! $this->Helpers->String->isLastChar( $resource, '/' ) ) {
                    $resource .= '/';
                }

                $this->generateMap( $class, $resource );
            }
        }

        $this->vars = $vars;
    }

    /**
     * Generates a map of methods found within a given namespace path
     * to a class.
     *
     * @param string $class
     *  Absolut namespace path to class
     *
     * @param string $basePath
     *  If we are looking even deeper into a route, e.g.
     *  /v1/users/user/1
     *
     * @return void
     */
    protected function generateMap($class, $basePath) {
        $reflection = $this->getReflection( $class );

        // @TODO $reflection might not be instantiated
        $methods = $reflection->getMethods( \ReflectionMethod::IS_PUBLIC );
        $rgx = '/@url[ \t]+(GET|POST|PUT|PATCH|DELETE|HEAD|OPTIONS)[ \t]+\/?(\S*)/s';

        foreach ( $methods as $method ) {
            $doc    = $method->getDocComment();
            $noAuth = strpos( $doc, '@noAuth' ) !== false;
            if ( preg_match_all( $rgx, $doc, $matches, PREG_SET_ORDER ) ) {
                $params = $method->getParameters();

                foreach ( $matches as $match ) {
                    $httpMethod = $match[ 1 ];
                    $url        = $basePath . $match[ 2 ];

                    if ( $this->Helpers->String->isLastChar( $url, '/' ) ) {
                        // Remove last char
                        $url = substr( $url, 0, -1 );
                    }

                    $call = array( $class, $method->getName() );
                    $args = array();
                    foreach ( $params as $param ) {
                        $args[ $param->getName() ] = $param->getPosition();
                    }

                    $call[] = $args;
                    $call[] = null;
                    $call[] = $noAuth;

                    $this->map[ $httpMethod ][ $url ] = $call;
                }
            }
        }
    }

    /**
     * @return void
     */
    public function sendData($data) {
        header( "Cache-Control: no-cache, must-revalidate" );
        header( "Expires: 0" );
        // header('Content-Type: ' . $this->format);
        header( 'Content-Type: application/json; charset=utf-8' );

        if ( $this->format == RestFormat::XML ) {

            if ( is_object( $data ) && method_exists( $data, '__keepOut' ) ) {
                $data = clone $data;
                foreach ( $data->__keepOut() as $prop ) {
                    unset( $data->$prop );
                }
            }
            $this->xml_encode( $data );

        } else {
            if ( is_object( $data ) && method_exists( $data, '__keepOut' ) ) {
                $data = clone $data;
                foreach ( $data->__keepOut() as $prop ) {
                    unset( $data->$prop );
                }
            }

            $options = 0;

            if ( $this->mode == 'debug' && defined( 'JSON_PRETTY_PRINT' ) ) {
                $options = JSON_PRETTY_PRINT;
            }

            if ( defined( 'JSON_UNESCAPED_UNICODE' ) ) {
                $options = $options | JSON_UNESCAPED_UNICODE;
            }

            echo json_encode( $data, $options );
        }
    }

    /**
     * @return void
     */
    protected function findUrl() {
        $urls = $this->map[ $this->method ];

        // Bail if no urls were found
        if ( ! $urls ) return null;

        /**
         * We check if we actually get a direct match and not something
         * similar.
         */
        $directMatch   = false;
        $directMatches = [];
        foreach ( $urls as $url => $call ) {
            if ( strpos( $this->url, $url ) !== false ) {
                $directMatch           = true;
                $directMatches[ $url ] = $call;
            }
        }

        if ( sizeof( $directMatches ) > 0 ) {
            $urls = $directMatches;
        }

        foreach ( $urls as $url => $call ) {
            $args = $call[ 2 ];

            if ( ! strstr( $url, '$' ) ) {
                if ( $url == $this->url ) {
                    if ( isset( $args[ 'data' ] ) ) {
                        $params = array_fill( 0, $args[ 'data' ] + 1, null );

                        // @TODO
                        //  Data is not a property of this class
                        $params[ $args[ 'data' ] ] = $this->data;
                        $call[ 2 ] = $params;
                    } else {
                        $call[ 2 ] = array();
                    }

                    return $call;
                }
            } else {
                $regex = preg_replace(
                    '/\\\\\$([\w\d]+)\.\.\./',
                    '(?P<$1>.+)',
                    str_replace( '\.\.\.', '...', preg_quote( $url ) )
                );

                // Overrides above regex
                $regex = preg_replace(
                    '/\\\\\$([\w\d]+)/',
                    '(?P<$1>[^\/]+)',
                    $regex
                );

                // if (preg_match(":^$regex$:", urldecode($this->url), $matches)) {
                if ( preg_match( ":^$regex$:", urldecode( $this->url ), $matches ) ) {
                    $params   = array();
                    $paramMap = array();

                    if ( isset( $args[ 'data' ] ) ) {
                        $params[ $args[ 'data' ] ] = $this->data;
                    }

                    foreach ( $matches as $arg => $match ) {
                        if ( is_numeric( $arg ) ) continue;

                        $paramMap[ $arg ] = $match;

                        if ( isset( $args[ $arg ] ) ) {
                            $params[ $args[ $arg ] ] = $match;
                        }
                    }

                    ksort( $params );

                    // Make sure we have all the params we need
                    end( $params );

                    $max = key( $params );
                    for ( $i = 0; $i < $max; $i++ ) {
                        if ( ! array_key_exists( $i, $params ) ) {
                            $params[ $i ] = null;
                        }
                    }

                    ksort( $params );

                    $call[ 2 ] = $params;
                    $call[ 3 ] = $paramMap;

                    return $call;
                }
            }
        }
    }

    /**
     * If method exists in instance, call it.
     *
     * @param object $instance
     * @param string $method
     *
     * @return void
     */
    private function methodCallExists($instance, $method): void {
        if ( method_exists( $instance, $method ) ) {
            $instance->$method();
        }
    }

    /**
     * Helps fetching a reflection instance of a class.
     *
     * @param object|string $class
     *
     * @return object
     */
    private function getReflection($class) {
        if ( is_object( $class ) ) {
            $reflection = new \ReflectionObject( $class );
        } elseif ( class_exists( $class ) ) {
            $reflection = new \ReflectionClass( $class );
        }

        return $reflection;
    }

    /**
     * @param string|object $class
     */
    private function validClass($class) {
        if ( is_string( $class )
            && ! class_exists( $class ) ) {
            throw new \Exception( 'Invalid method or class' );

        } elseif ( ! is_string( $class )
            && ! is_object( $class ) ) {

            throw new \Exception(
                'Invalid method or class; must be a classname or object'
            );
        }

        return true;
    }
}
