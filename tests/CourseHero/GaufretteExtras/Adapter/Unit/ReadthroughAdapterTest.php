<?php
namespace CourseHero\GaufretteExtras\Adapter\Unit;

use Gaufrette\Adapter\AmazonS3;
use CourseHero\GaufretteExtras\Adapter\ReadthroughAdapter;
use Gaufrette\Filesystem;

class ReadthroughAdapterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $primary;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $fallback;

    /**
     * @var ReadthroughAdapaterTester
     */
    protected $readthroughAdapter;

    public function setUp()
    {

        $this->primary = $this->getMockBuilder('Gaufrette\Adapter\AmazonS3')->disableOriginalConstructor()->getMock();
        $this->fallback = $this->getMockBuilder('Gaufrette\Adapter\AmazonS3')->disableOriginalConstructor()->getMock();
        $this->readthroughAdapter = new ReadthroughAdapter($this->primary, $this->fallback);
    }

    /**
     * @dataProvider shouldGetMetadataData
     */
    public function testShouldGetMetadataFromFallback($primaryMetadata, $fallbackMetadata, $expectedMetadata, $message){
        $key = "test-file";

        if ($fallbackMetadata) {
            $this->fallback->expects($this->any())
                ->method('getMetadata')
                ->with($key)
                ->willReturn($fallbackMetadata);
        }

        if ($primaryMetadata){
            $this->primary->expects($this->any())
                          ->method('exists')
                          ->with($key)
                          ->willReturn(true);

            $this->primary->expects($this->any())
                          ->method('getMetadata')
                          ->with($key)
                          ->willReturn($primaryMetadata);
        }

        $result = $this->readthroughAdapter->getMetadata($key);

        $this->assertEquals($expectedMetadata, $result, $message);
    }

    public function shouldGetMetadataData(){
        $primaryMetadata = array("data" => "primary");
        $fallbackMetadata = array("data" => "fallback");

        return array(
            //primaryMetadata, fallbackMetadata, expectedMetadata, message
            array(null, $fallbackMetadata, $fallbackMetadata, "should get fallback metadata with no primary"),
            array($primaryMetadata, $fallbackMetadata, $primaryMetadata, "should get primary metadata over fallback"),
            array($primaryMetadata, null, $primaryMetadata, "should get primary metadata with no fallback"),
            array(null, null, null, "should have no metadata if nothing has metadata")
        );
    }

    public function testShouldGetFalseForMetadataIfNotProvider(){
        $key = "test-file";

        $this->primary = $this->getMockBuilder('Gaufrette\Adapter\InMemory')->disableOriginalConstructor()->getMock();
        $this->fallback = $this->getMockBuilder('Gaufrette\Adapter\AmazonS3')->disableOriginalConstructor()->getMock();
        $this->readthroughAdapter = new ReadthroughAdapter($this->primary, $this->fallback);

        $this->fallback->expects($this->never())
                       ->method('getMetadata');

        $this->primary->expects($this->never())
                      ->method('getMetadata');

        $result = $this->readthroughAdapter->getMetadata($key);

        $this->assertFalse($result);
    }

    public function testShouldSetMetadataInPrimary(){
        $key = "test-file";
        $metadata = array("data" => "something");

        $this->primary->expects($this->atLeastOnce())
                      ->method('exists')
                      ->with($key)
                      ->willReturn(true);

        $this->primary->expects($this->once())
                      ->method('setMetadata')
                      ->with($key, $metadata);

        $this->readthroughAdapter->setMetadata($key, $metadata);
    }

    public function testShouldCopyDataOnMetadataSetInPrimary(){
        $key = "test-file";
        $content = "content";
        $metadata = array("data" => "something");

        $this->primary->expects($this->atLeastOnce())
            ->method('exists')
            ->with($key)
            ->willReturn(false);

        $this->fallback->expects($this->atLeastOnce())
                        ->method('exists')
                        ->with($key)
                        ->willReturn(true);

        $this->fallback->expects($this->once())
                       ->method('read')
                       ->with($key)
                       ->willReturn($content);

        $this->primary->expects($this->once())
                      ->method('write')
                      ->with($key, $content);

        $this->primary->expects($this->once())
                      ->method('setMetadata')
                      ->with($key, $metadata);

        $this->readthroughAdapter->setMetadata($key, $metadata);
    }

    /**
     * @dataProvider shouldDetermineIfDirectoryData
     */
    public function testShouldDetermineIfDirectory($isPrimary, $isFallback, $expected, $message){
        $key = "test-file";

        $this->primary->expects($this->any())
                      ->method('isDirectory')
                      ->with($key)
                      ->willReturn($isPrimary);

        $this->fallback->expects($this->any())
                        ->method('isDirectory')
                        ->with($key)
                        ->willReturn($isFallback);

        $result = $this->readthroughAdapter->isDirectory($key);

        $this->assertEquals($expected, $result, $message);
    }

    public function shouldDetermineIfDirectoryData(){
        return array(
            //isPrimary, isFallback, expected, message
            array(false, false, false, 'should not be a directory if not a directory anywhere'),
            array(true, false, true, 'should be a directory if primary is a directory'),
            array(false, true, true, 'should be a directory if fallback is a directory'),
            array(true, true, true, 'should be a directory if everywhere is a directory')
        );
    }
}