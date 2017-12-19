<?php
namespace DAwaa\Core\RestServer;

use \DAwaa\Core\Modules\Url;

class Expander {

    /**
     * @var array $payload
     *  Holds our current payload.
     */
    private $payload;

    /**
     * @var object $class
     *  Sets which current controller instance we will be calling methods upon.
     */
    private $class;

    /**
     * @var string $method
     *  Sets which current method we are calling.
     */
    private $method;

    /**
     * @var boolean $isCollection
     *  Whether the result contains an array of a resource, e.g.
     *  /Users/all would be a Collection meanwhile /Users/1
     *  would refer to a Single user.
     */
    private $isCollection = false;

    public function __construct(Resolver $Resolver) {
        $this->Resolver = $Resolver;
    }

    /**
     * Adds expandable options to the result returned from the
     * route-method.
     *
     * @param array $result
     *
     * @return array
     */
    public function addExpandableOptions(array $payload) {
        $payload = $this->prependBaseMeta( $payload );

        $this->payload = $payload;

        if ( $this->hasCollection() ) {
            $collection = $this->getCollection();
            $collection = $this->appendMetaToCollection( $collection );
            if ( $this->hasExpendables() ) {
                $collection = $this->appendExpandables( $collection, true );
            }

            // Overrides old `items` with our modified `collection`
            $this->payload[ 'items' ] = $collection;
        } else {
            if ( $this->hasExpendables() ) {
                $this->payload = $this->appendExpandables( $this->payload );
            }
        }

        return $this->payload;
    }

    public function callMethods() {
        $expandables = $this->getExpandables();
        $requests    = $this->getRequestedExpandables();

        // If no requests were made, bail early with what we have
        if ( sizeof( $requests ) === 0 ) {
            return $this->payload;
        }


        if ( $this->hasCollection() ) {
            $collection = $this->getCollection();
            foreach ( $collection as $k => & $collectionItem ) {
                $uId = $collectionItem[ $this->getUnique() ];

                foreach ( $requests as $request ) {
                    if ( array_key_exists( $request, $expandables ) ) {
                        $method     = $expandables[ $request ];

                        $ControllerMethod = method_exists( $this->class, $method );
                        $ModelMethod      = method_exists( $this->class->model, $method );


                        if ( $ControllerMethod ) {
                            $expandPayload = $this->class->$method( $uId );
                            if ( is_array( $expandPayload ) ) {
                                $collectionItem[ $request ] += $expandPayload;
                            }
                        } else if ( $ModelMethod ) {
                            $expandPayload = $this->class->model->$method( $uId );
                            if ( is_array( $expandPayload ) ) {
                                $collectionItem[ $request ] += $expandPayload;
                            }
                        }
                    }
                }
            }

            $this->payload[ 'items' ] = $collection;
        } else {

        }

        return $this->payload;
    }

    /**
     * @return void
     */
    public function setMethod(string $method): void {
        $this->method = $method;
    }

    public function setClass($Class): void {
        $this->class = $Class;
    }

    private function validExpandPayload($payload): bool {
        return \DAwaa\Core\Helpers\ArrayHelper::isAssoc( $payload );
    }

    /**
     * Prepends meta info to the base of the payload we received
     * from the route-method.
     *
     * @param array $payload
     *
     * @return array
     */
    private function prependBaseMeta(array $payload, $uId = ""): array {
        return array(
            'meta' => array(
                'href' => Url::getUrl( true ) . $uId
            )
        ) + $payload;
    }

    private function appendMetaToCollection(array $collection) {
        $baseUrl = Url::getUrl( true );

        foreach ( $collection as $k => & $item ) {
            $key = $this->hasUnique() ? $this->getUnique() : $k;
            $uId = $item[ $key ]; // Unique identifier

            $item = $this->prependBaseMeta( $item, $uId );
        }

        return $collection;
    }

    private function appendExpandables(array $payload, bool $isCollection = false): array {
        $expandables = $this->getExpandables( $this->method );

        foreach ( $expandables as $metaKey => $method ) {
            $metaUrl = Url::getUrl( true ) . "?expand=$metaKey";

            if ( $isCollection ) {
                foreach ( $payload as & $collectionItem ) {
                    $collectionItem[ $metaKey ] = array(
                        'meta' => array(
                            'href' => $metaUrl
                        )
                    );
                }
            } else {
                $payload[ $metaKey ] = array(
                    'meta' => array(
                        'href' => $metaUrl
                    )
                );
            }
        }

        return $payload;
    }

    private function hasUnique(): bool {
        return sizeof($this->Resolver->getUniqueKey( $this->method )) === 0
            ? false
            : true;
    }

    private function getUnique(): string {
        return $this->Resolver->getUniqueKey( $this->method );
    }

    private function hasCollection(): bool {
        return array_key_exists( 'items', $this->payload );
    }

    private function getCollection(): array {
        return $this->hasCollection() ? $this->payload[ 'items' ] : array();
    }

    private function hasExpendables(): bool {
        return sizeof( $this->getExpandables( $this->method ) ) === 0
            ? false
            : true;
    }

    private function getExpandables() {
        $expandables = $this->Resolver->getExpandables( $this->method );

        if ( isset( $expandables[ 'expand' ] ) ) {
            return $expandables[ 'expand' ];
        }

        // if ( is_string( $expandables ) ) {
        //     return array( $expandables );
        // }

        return $expandables;
    }

    /**
     * Returns an array of requested expandables to be expanded.
     *
     * @return array
     */
    private function getRequestedExpandables(): array {
        // Bail early
        if ( !isset( $_GET[ 'expand' ] ) )
            return array();

        $expand = $_GET[ 'expand' ];
        $expand = explode( ',', $expand );

        return $expand;
    }

    private function populateResultWithMetaTags(array $result) {
        $populatedResult = [];
        $expands = false;
        $isCollection = false;
        $hasUnique = sizeof( $uniqueKey ) === 0 ? false : true;

        if ( isset( $expandables['expand'] ) )
            $expands = $expandables['expand'];

        if ( array_key_exists( 'items', $result ) )
            $isCollection = true;


        $rootUrl = rtrim( Root::getUrl(true), '/' ) . '/';

        // Add root meta
        $rootMeta = [
            'meta' => [
                'href' => $rootUrl
            ]
        ];
        $result = $rootMeta + $result;

        // Add meta to items
        if ( $isCollection && $hasUnique ) {
            $items = $result['items'];
            foreach ( $items as $i => $item ) {
                if ( ! isset( $uniqueKey ) ) {
                    $uniqueKey = $i;
                }

                $id = $item[ $uniqueKey ];

                $itemMeta = [
                    'meta' => [
                        'href' => $rootUrl . $id
                    ]
                ];

                $item = $itemMeta + $item;
                $result['items'][$i] = $item;
            }
        }

        // Add meta to expand tags
        if ( $expands !== false ) {
            foreach ( $expands as $metaKey => $method ) {
                $metaHref = $rootUrl . '?expand=' . $metaKey;

                if ( $isCollection ) {

                    // Iterate through each item in our items array and
                    // give their expand property its meta tag
                    foreach ( $result['items'] as $i => $item ) {
                        $item[ $metaKey ] = [
                            'meta' => [
                                'href' => $metaHref
                            ]
                        ];

                        // Append the item to its index position with a
                        // new meta property added to the item
                        $result['items'][$i] = $item;
                    }
                }
                else if ( $isCollection === false ) {
                    $meta = [
                        $metaKey => [
                            'meta' => [
                                'href' => $metaHref
                            ]
                        ]
                    ];

                    $result = $result + $meta;
                }
            }
        }

        return $result;
    }
}
