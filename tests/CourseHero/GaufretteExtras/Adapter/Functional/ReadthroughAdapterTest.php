<?php
namespace CourseHero\GaufretteExtras\Adapter\Functional;

use Gaufrette\Adapter\InMemory;
use CourseHero\GaufretteExtras\Adapter\ReadthroughAdapter;
use Gaufrette\Filesystem;

class ReadthroughAdapterTest extends FunctionalTestCase
{
    /**
     * @var InMemory
     */
    protected $primary;

    /**
     * @var InMemory
     */
    protected $fallback;

    /**
     * @var ReadthroughAdapter
     */
    protected $readthroughAdapter;

    public function setUp()
    {
        $this->primary = new InMemory();
        $this->fallback = new InMemory();
        $this->readthroughAdapter = new ReadthroughAdapter($this->primary, $this->fallback);

        $this->filesystem = new Filesystem($this->readthroughAdapter);
    }

    /**
     * @test
     */
    public function shouldReadFromFallback(){
        $key = "test-file";
        $contents = "abc123";

        $this->fallback->write($key, $contents);

        $result = $this->filesystem->read($key);

        $this->assertEquals($contents, $result, "should return contents from fallback");
    }
}