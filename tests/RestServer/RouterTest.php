<?php
namespace DAwaa\Tests\RestServer;

class RouterTest extends \DAwaa\Tests\PHPUnitWrapper {

    private $mock = null;

    public function setUp() {
        $this->mock = $this
            ->getMockBuilder( '\DAwaa\Core\RestServer\Router' )
            ->setMethods([
                'noResourceGiven',
                'noResourceFound',
                'getResourcePath'
            ])
            ->getMock();
    }

    public function testShouldReturnNoResourceGiven() {
        $this->setUrl( 'http://api.local/v1' );

        $this->mock
            ->expects( $this->once() )
            ->method( 'noResourceGiven' );

        $this->mock->getRoute();
    }

    public function testShouldReturnNoFoundResource() {
        $this->setUrl( 'http://api.local/v1/not-a-resource' );

        $this->mock
            ->expects( $this->once() )
            ->method( 'noResourceFound' );

        $this->mock->getRoute();
    }

    public function testShouldReturnValidResource() {
        $this->setUrl( 'http://api.local/v1/users' );

        $this->mock
            ->method( 'getResourcePath' )
            ->willReturn(array(
                'path'      => TESTS_ROOT . '/RestServer/Fixtures/Resources/Users/Users.php',
                'namespace' => '\DAwaa\Tests\RestServer\Fixtures\Resources\Users\Users'
            ));

        list(
            'resource'  => $resource,
            'namespace' => $namespace
        ) = $this->mock->getRoute();


        $this->assertSame( 'users', $resource );
        $this->assertSame(
            '\DAwaa\Tests\RestServer\Fixtures\Resources\Users\Users',
            $namespace
        );
    }

    public function testShouldReturnValidResourceEvenWithQueryString() {
        $this->setUrl( 'http://api.local/v1/users?expand=options,detailed' );

        $this->mock
            ->method( 'getResourcePath' )
            ->willReturn(array(
                'path'      => TESTS_ROOT . '/RestServer/Fixtures/Resources/Users/Users.php',
                'namespace' => '\DAwaa\Tests\RestServer\Fixtures\Resources\Users\Users'
            ));

        list(
            'resource'  => $resource,
            'namespace' => $namespace
        ) = $this->mock->getRoute();


        $this->assertSame( 'users', $resource );
        $this->assertSame(
            '\DAwaa\Tests\RestServer\Fixtures\Resources\Users\Users',
            $namespace
        );
    }

    private function setURL($url) {
        $parsedUrl = parse_url( $url );

        $_SERVER[ 'HTTP_HOST' ]   = $parsedUrl[ 'host' ];
        $_SERVER[ 'REQUEST_URI' ] = $parsedUrl[ 'path' ];
    }

    public function tearDown() {
        $this->mock               = null;
        $_SERVER[ 'HTTP_HOST' ]   = null;
        $_SERVER[ 'REQUEST_URI' ] = null;
    }

}
