<?php

namespace Rapture\Http;

use Psr\Http\Message\StreamInterface;

/**
 * PSR7 compliant Stream
 *
 * @package Rapture\Http
 * @author  Iulian N. <rapture@iuliann.ro>
 * @license LICENSE MIT
 * @credits Guzzle\Psr7\Stream
 */
class Stream implements StreamInterface
{
    protected $stream;

    protected $meta = [];

    /**
     * Stream constructor.
     *
     * @param mixed $stream Stream
     * @param array $meta   Extra meta info
     */
    public function __construct($stream, array $meta = [])
    {
        if (!is_resource($stream)) {
            throw new \InvalidArgumentException('Stream is not a resource');
        }

        $this->stream = $stream;
        $this->meta = $meta + stream_get_meta_data($this->stream);
    }

    /**
     * Closes the stream when the destructed
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Reads all data from the stream into a string, from the beginning to end.
     *
     * This method MUST attempt to seek to the beginning of the stream before
     * reading data and read the stream until the end is reached.
     *
     * Warning: This could attempt to load a large amount of data into memory.
     *
     * This method MUST NOT raise an exception in order to conform with PHP's
     * string casting operations.
     *
     * @see http://php.net/manual/en/language.oop5.magic.php#object.tostring
     * @return string
     */
    public function __toString()
    {
        try {
            $this->seek(0);

            return (string)stream_get_contents($this->stream);
        }
        catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Closes the stream and any underlying resources.
     *
     * @return void
     */
    public function close()
    {
        if (isset($this->stream)) {
            if (is_resource($this->stream)) {
                fclose($this->stream);
            }
            $this->detach();
        }
    }

    /**
     * Separates any underlying resources from the stream.
     *
     * After the stream has been detached, the stream is in an unusable state.
     *
     * @return resource|null Underlying PHP stream, if any
     */
    public function detach()
    {
        $result = $this->stream;
        unset($this->stream);
        $this->meta = [];

        return $result;
    }

    /**
     * Get the size of the stream if known.
     *
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    public function getSize()
    {
        if (!isset($this->stream)) {
            return null;
        }

        if ($this->getMetadata('size') !== null) {
            return $this->getMetadata('size');
        }

        // Clear the stat cache if the stream has a URI
        if ($this->getMetadata('uri') !== null) {
            clearstatcache(true, $this->getMetadata('uri'));
        }

        $stats = fstat($this->stream);
        if (isset($stats['size'])) {
            $this->meta['size'] = $stats['size'];
        }

        return $this->getMetadata('size');
    }

    /**
     * Returns the current position of the file read/write pointer
     *
     * @return int Position of the file pointer
     * @throws \RuntimeException on error.
     */
    public function tell()
    {
        $result = ftell($this->stream);

        if ($result === false) {
            throw new \RuntimeException('Cannot determine stream position');
        }

        return $result;
    }

    /**
     * Returns true if the stream is at the end of the stream.
     *
     * @return bool true if the file pointer is at EOF or an error occurs
     */
    public function eof()
    {
        return !$this->stream || feof($this->stream);
    }

    /**
     * Returns whether or not the stream is seekable.
     *
     * @return bool
     */
    public function isSeekable()
    {
        return (bool)$this->getMetadata('seekable');
    }

    /**
     * Seek to a position in the stream.
     *
     * @link http://www.php.net/manual/en/function.fseek.php
     *
     * @param int $offset Stream offset
     * @param int $whence Specifies how the cursor position will be calculated
     *                    based on the seek offset. Valid values are identical to the built-in
     *                    PHP $whence values for `fseek()`. SEEK_SET: Set position equal to
     *                    offset bytes SEEK_CUR: Set position to current location plus offset
     *                    SEEK_END: Set position to end-of-stream plus offset.
     *
     * @throws \RuntimeException on failure.
     * @return int Upon success, returns 0; otherwise, returns -1. Note that seeking
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if (!$this->getMetadata('seekable')) {
            throw new \RuntimeException('Stream is not seekable');
        } elseif ($seek = fseek($this->stream, $offset, $whence) === -1) {
            throw new \RuntimeException('Unable to seek to stream position `' . $offset . '` with whence ' . var_export($whence, true));
        }

        return $seek;
    }

    /**
     * Seek to the beginning of the stream.
     *
     * If the stream is not seekable, this method will raise an exception;
     * otherwise, it will perform a seek(0).
     *
     * @see  seek()
     * @link http://www.php.net/manual/en/function.fseek.php
     *
     * @throws \RuntimeException on failure.
     *
     * @return int
     */
    public function rewind()
    {
        return $this->seek(0);
    }

    /**
     * Returns whether or not the stream is writable.
     *
     * @return bool
     */
    public function isWritable()
    {
        return in_array($this->getMetadata('mode'), [
            'w', 'w+', 'rw', 'r+', 'x+', 'c+', 'wb', 'w+b', 'r+b', 'x+b', 'c+b', 'w+t', 'r+t', 'x+t', 'c+t', 'a', 'a+',
        ]);
    }

    /**
     * Write data to the stream.
     *
     * @param string $string The string that is to be written.
     *
     * @return int Returns the number of bytes written to the stream.
     * @throws \RuntimeException on failure.
     */
    public function write($string)
    {
        if (!$this->isWritable()) {
            throw new \RuntimeException('Stream is not writable');
        }

        $this->meta['size'] = null;

        $result = fwrite($this->stream, $string);

        if ($result === false) {
            throw new \RuntimeException('Unable to write to stream');
        }

        return $result;
    }

    /**
     * Returns whether or not the stream is readable.
     *
     * @return bool
     */
    public function isReadable()
    {
        return in_array($this->getMetadata('mode'), [
            'r', 'w+', 'r+', 'x+', 'c+', 'rb', 'w+b', 'r+b', 'x+b', 'c+b', 'rt', 'w+t', 'r+t', 'x+t', 'c+t', 'a+',
        ]);
    }

    /**
     * Read data from the stream.
     *
     * @param int $length Read up to $length bytes from the object and return
     *                    them. Fewer than $length bytes may be returned if underlying stream
     *                    call returns fewer bytes.
     *
     * @return string Returns the data read from the stream, or an empty string
     * if no bytes are available.
     * @throws \RuntimeException if an error occurs.
     */
    public function read($length)
    {
        if (!$this->isReadable()) {
            throw new \RuntimeException('Stream is not readable');
        }

        return fread($this->stream, $length);
    }

    /**
     * Returns the remaining contents in a string
     *
     * @return string
     * @throws \RuntimeException if unable to read or an error occurs while
     * reading.
     */
    public function getContents()
    {
        $contents = stream_get_contents($this->stream);

        if ($contents === false) {
            throw new \RuntimeException('Cannot read stream contents');
        }

        return $contents;
    }

    /**
     * Get stream metadata as an associative array or retrieve a specific key.
     *
     * The keys returned are identical to the keys returned from PHP's
     * stream_get_meta_data() function.
     *
     * @link http://php.net/manual/en/function.stream-get-meta-data.php
     *
     * @param string $key Specific metadata to retrieve.
     *
     * @return array|mixed|null Returns an associative array if no key is
     * provided. Returns a specific key value if a key is provided and the
     * value is found, or null if the key is not found.
     */
    public function getMetadata($key = null)
    {
        return $this->meta[$key] ?? null;
    }
}
