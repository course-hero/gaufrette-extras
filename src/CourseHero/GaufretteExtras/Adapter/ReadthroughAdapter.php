<?php
namespace CourseHero\GaufretteExtras\Adapter;
use Gaufrette\File;
use Gaufrette\Adapter;
use Gaufrette\Adapter\InMemory as InMemoryAdapter;

/**
 * Adds a readthrough adapater.
 * The readthrough adapater accepts two other adapters, a primary and a fallback.
 *
 * All writes will happen against the primary.
 *
 * All reads will attempt to read against the primary, if the key is not found
 * the read will then happen against the fallback.
 *
 * @package CourseHero\GaufretteExtras
 * @author  Jason Wentworth <wentwj@gmail.com>
 */
class ReadthroughAdapter implements Adapter,
    Adapter\MetadataSupporter
{
    /**
     * @var Adapter
     */
    protected $fallback;
    /**
     * @var Adapter
     */
    protected $primary;
    /**
     * Constructor
     *
     * @param Adapter $primary          The adapter to attempt to use first, all writes use this adapater
     * @param Adapter $fallback         The adapter to use as a fallback if data isn't present in the primary adapter
     */
    public function __construct(Adapter $primary, Adapter $fallback)
    {
        $this->fallback = $fallback;
        $this->primary = $primary;
    }
    /**
     * {@InheritDoc}
     */
    public function read($key)
    {
        if ($this->primary->exists($key)){
            $contents = $this->primary->read($key);
        } else{
            $contents = $this->fallback->read($key);
        }
        return $contents;
    }
    /**
     * {@inheritDoc}
     */
    public function rename($key, $new)
    {
        return $this->primary->rename($key, $new);
    }
    /**
     * {@inheritDoc}
     */
    public function write($key, $content, array $metadata = null)
    {
        return $this->primary->write($key, $content);
    }
    /**
     * {@inheritDoc}
     */
    public function exists($key)
    {
        return $this->primary->exists($key) || $this->fallback->exists($key);
    }
    /**
     * {@inheritDoc}
     */
    public function keys()
    {
        return array_merge($this->primary->keys(), $this->fallback->keys());
    }
    /**
     * {@inheritDoc}
     */
    public function delete($key)
    {
        return $this->primary->delete($key);
    }
    /**
     * {@inheritDoc}
     */
    public function isDirectory($key)
    {
        return $this->primary->isDirectory($key) || $this->fallback->isDirectory($key);
    }
    /**
     * {@inheritDoc}
     */
    public function setMetadata($key, $metadata)
    {
        if ($this->primary instanceof Adapter\MetadataSupporter) {
            if (!$this->primary->exists($key) && $this->fallback->exists($key)){
                $this->primary->write($key, $this->fallback->read($key));
            }

            $this->primary->setMetadata($key, $metadata);
        }
    }
    /**
     * {@inheritDoc}
     */
    public function getMetadata($key)
    {
        $result = false;
        if ($this->primary instanceof Adapter\MetadataSupporter) {
            if ($this->primary->exists($key)) {
                $result = $this->primary->getMetadata($key);
            } else if ($this->fallback instanceof Adapter\MetadataSupporter){
                $result = $this->fallback->getMetadata($key);
            }
        }
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function mtime($key)
    {
        return $this->primary->mtime($key);
    }
}