<?php
namespace DAwaa\Tests\Modules;

class DocParserTest extends \DAwaa\Tests\PHPUnitWrapper {

    public function testShouldThrowExceptionOnNonExistantNamespace() {
        $this->expectException( \Exception::class );
        new \DAwaa\Core\Modules\DocParser( 'Not\An\Existing\Class' );
    }

    /**
     * @dataProvider getAnnotationClass
     */
    public function testShouldThrowExceptionOnUndefinedMethod($instance, $parser) {
        $this->expectException( \ReflectionException::class );
        $unique = $parser->getAnnotations( 'notAMethodOfThisClass', 'unique' );
    }

    /**
     * @dataProvider getAnnotationMethods
     */
    public function testShouldOnlyFindExpandsWithExistingRespectiveMethod($instance, $parser) {
        $expands = $parser->getAnnotations( 'fetchUsers', 'expand' );
        $this->assertArrayStructure(
            $expands,
            array(
                'expand' => array(
                    'detailed' => 'showDetailedInformation'
                )
            )
        );
    }

    /**
     * @dataProvider getAnnotationMethods
     */
    public function testShouldGetUniqueAsString($instance, $parser) {
        $unique = $parser->getAnnotations( 'fetchUsers', 'unique' );
        $this->assertSame( $unique, 'userId' );
    }

    /**
     * @dataProvider getAnnotationMethods
     */
    public function testShouldReturnNullIfAnnotationNotFound($instance, $parser) {
        $missing = $parser->getAnnotations( 'fetchUsers', 'cantFindMe' );
        $this->assertSame( $missing, null );
    }

    /**
     * @dataProvider getAnnotationMethods
     */
    public function testShouldReturnMultipleAnnotations($instance, $parser) {
        $expands = $parser->getAnnotations( 'fetchUser', 'expand' );
        $this->assertArrayStructure(
            $expands,
            array(
                'expand' => array(
                    'detailed' => 'showDetailedInformation',
                    'avatar'   => 'getAvatar',
                    'related'  => 'showRelatedTopics'
                )
            )
        );
    }

    /**
     * @dataProvider getAnnotationMethods
     */
    public function testShouldReturnNullIfNoClassAnnotation($instance, $parser) {
        $model = $parser->getAnnotations( 'model' );
        $this->assertSame( null, $model );
    }

    /**
     * @dataProvider getAnnotationClass
     */
    public function testShouldReturnClassAnnotationModel($instance, $parser) {
        $model = $parser->getAnnotations( 'model' );
        $this->assertSame( $model, 'This\Class\Does\Exist\Yay' );
    }

    public function getAnnotationMethods() {
        $instance  = new Fixtures\AnnotationMethods();
        $DocParser = new \DAwaa\Core\Modules\DocParser( $instance );

        return [
            [ $instance, $DocParser ]
        ];
    }

    public function getAnnotationClass() {
        $instance  = new Fixtures\AnnotationClass();
        $DocParser = new \DAwaa\Core\Modules\DocParser( $instance );

        return [
            [ $instance, $DocParser ]
        ];
    }

}
