<?php

namespace Sribna\Licensee;

use LogicException;

/**
 * Class Key
 * @package Sribna\Licensee
 */
class Key
{
    /**
     * @var string Absolute path to the key storage
     */
    private $storage;

    /**
     * Returns the absolute path to the key storage
     * @return string
     */
    public function getStorage(): string
    {
        if (!$this->storage || !is_dir($this->storage)) {
            throw new LogicException("Invalid key storage path");
        }

        return $this->storage;
    }

    /**
     * Sets the absolute path to the key storage
     * @param string $path
     * @return $this
     */
    public function setStorage(string $path)
    {
        $this->storage = $path;
        return $this;
    }

    /**
     * Returns the absolute path to the key file
     * @param string $filename
     * @return string
     */
    public function getPath(string $filename)
    {
        return $this->getStorage() . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * Returns the content of a key file
     * @param string $filename
     * @return string|false
     */
    public function get(string $filename = 'public')
    {
        $file = $this->getPath($filename);
        return is_file($file) ? file_get_contents($file) : false;
    }

    /**
     * Returns the content of the private key
     * @return string|false
     */
    public function getPrivate()
    {
        return $this->get('private');
    }

    /**
     * Sets the content to a key file
     * @param string $key
     * @param string $filename
     * @return false|int
     */
    public function set(string $key, string $filename = 'public')
    {
        return file_put_contents($this->getPath($filename), $key);
    }

    /**
     * Sets the content to the private key
     * @param string $key
     * @return false|int
     */
    public function setPrivate(string $key)
    {
        return $this->set($key, 'private');
    }

    /**
     * Deletes a key file
     * @param string $filename
     * @return bool
     */
    public function delete(string $filename = 'public')
    {
        $file = $this->getPath($filename);
        return is_file($file) && unlink($file);
    }

    /**
     * Deletes a private key file
     * @return bool
     */
    public function deletePrivate()
    {
        return $this->delete('private');
    }

}
