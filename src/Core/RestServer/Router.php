<?php
namespace DAwaa\Core\RestServer;

class Router {

    public function getRoute() {
        list(
            $version,
            $resource,
            $method
        ) = array_pad( \DAwaa\Core\Modules\Url::getSegments(), 3, null );

        if ( $resource === null ) {
            $this->noResourceGiven();
            return;
        }

        $Resource = $this->getResourcePath( $resource );

        if ( file_exists( $Resource[ 'path' ] ) ) {
            require_once $Resource[ 'path' ];
        } else {
            return $this->noResourceFound( $resource );
        }

        return array(
            'resource'  => $resource,
            'namespace' => $Resource[ 'namespace' ]
        );
    }

    public function noResourceGiven() {
        header( 'Content-Type: application/json; charset=utf-8' );
        echo json_encode([
            'meta'    => array(
                'href' => \DAwaa\Core\Modules\Url::getUrl( true )
            ),
            'status'  => 404,
            'message' => 'No resource given'
        ], JSON_PRETTY_PRINT);
        die;
    }

    public function noResourceFound(string $resource) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'meta' => array(
                'href' => \DAwaa\Core\Modules\Url::getUrl( true )
            ),
            'status' => 404,
            'message' => "Couldn\'t find an endpoint called /$resource"

        ], JSON_PRETTY_PRINT);
        die;
    }

    public function getResourcePath(string $resource) {
        $Resource  = ucfirst( $resource );
        $path      = __RESOURCES__ . DS . "$Resource/$Resource.php";
        $namespace = "\\Resources\\$Resource\\$Resource";

        return array(
            'path'      => $path,
            'namespace' => $namespace
        );
    }
}
