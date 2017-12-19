<?php
namespace DAwaa\Tests\Modules;

use \DAwaa\Core\Modules\Url;

class UrlTest extends \DAwaa\Tests\PHPUnitWrapper {

    public function setUp() {
        $_SERVER[ 'HTTP_HOST' ]   = 'api.local';
        $_SERVER[ 'REQUEST_URI' ] = '/v1/users?shouldbegone=true';
    }

    public function testShouldGetSegments() {
        $this->assertArrayStructure(
            Url::getSegments(),
            array(
                'v1',
                'users'
            )
        );
    }

    public function testShouldGetFullUrl() {
        $this->assertSame(
            Url::getUrl(),
            'http://api.local/v1/users?shouldbegone=true'
        );
    }

    public function testShouldGetStrippedUrl() {
        $this->assertSame(
            Url::getUrl( true ),
            'http://api.local/v1/users/'
        );
    }

}
