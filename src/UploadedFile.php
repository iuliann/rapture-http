<?php

namespace Rapture\Http;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Class UploadedFile
 *
 * @package Rapture\Http
 * @author  Iulian N. <rapture@iuliann.ro>
 * @license LICENSE MIT
 */
class UploadedFile implements UploadedFileInterface
{
    /** @var array */
    protected $file = [
        'tmp_name' => null,
        'name' => null,
        'size' => null,
        'type' => null,
        'error' => 0,
    ];

    /** @var bool */
    protected $hasMoved = false;

    /**
     * UploadedFile constructor.
     *
     * @param array $file File upload data
     */
    public function __construct(array $file)
    {
        $this->file = $file + $this->file;
    }

    /**
     * Retrieve a stream representing the uploaded file.
     *
     * This method MUST return a StreamInterface instance, representing the
     * uploaded file. The purpose of this method is to allow utilizing native PHP
     * stream functionality to manipulate the file upload, such as
     * stream_copy_to_stream() (though the result will need to be decorated in a
     * native PHP stream wrapper to work with such functions).
     *
     * If the moveTo() method has been called previously, this method MUST raise
     * an exception.
     *
     * @return StreamInterface Stream representation of the uploaded file.
     * @throws \RuntimeException in cases when no stream is available or can be
     * created.
     */
    public function getStream()
    {
        if ($this->hasMoved()) {
            throw new \LogicException('File has been already moved');
        }

        return new Stream(fopen($this->file['tmp_name'], 'r+'));
    }

    /**
     * Move the uploaded file to a new location.
     *
     * @param string $targetPath Path to which to move the uploaded file.
     *
     * @throws \InvalidArgumentException if the $path specified is invalid.
     *
     * @return bool
     */
    public function moveTo($targetPath):bool
    {
        if (!file_exists($targetPath)) {
            throw new \InvalidArgumentException('Invalid path:' . $targetPath);
        }

        $hasMoved = false;
        if (is_uploaded_file($this->getServerFilename())) {
            $hasMoved = move_uploaded_file($this->getServerFilename(), $targetPath . $this->getClientFilename());
        }

        $this->hasMoved = $hasMoved;

        return $hasMoved;
    }

    /**
     * Retrieve the file size.
     *
     * Implementations SHOULD return the value stored in the "size" key of
     * the file in the $_FILES array if available, as PHP calculates this based
     * on the actual size transmitted.
     *
     * @return int|null The file size in bytes or null if unknown.
     */
    public function getSize()
    {
        return $this->file['size'];
    }

    /**
     * Retrieve the error associated with the uploaded file.
     *
     * The return value MUST be one of PHP's UPLOAD_ERR_XXX constants.
     *
     * If the file was uploaded successfully, this method MUST return
     * UPLOAD_ERR_OK.
     *
     * Implementations SHOULD return the value stored in the "error" key of
     * the file in the $_FILES array.
     *
     * @see http://php.net/manual/en/features.file-upload.errors.php
     * @return int One of PHP's UPLOAD_ERR_XXX constants.
     */
    public function getError()
    {
        return $this->file['error'];
    }

    /**
     * Retrieve the filename sent by the client.
     *
     * Do not trust the value returned by this method. A client could send
     * a malicious filename with the intention to corrupt or hack your
     * application.
     *
     * Implementations SHOULD return the value stored in the "name" key of
     * the file in the $_FILES array.
     *
     * @return string|null The filename sent by the client or null if none
     * was provided.
     */
    public function getClientFilename()
    {
        return $this->file['name'];
    }

    /**
     * getServerFilename
     *
     * @return string
     */
    public function getServerFilename()
    {
        return $this->file['tmp_name'];
    }

    /**
     * Retrieve the media type sent by the client.
     *
     * Do not trust the value returned by this method. A client could send
     * a malicious media type with the intention to corrupt or hack your
     * application.
     *
     * Implementations SHOULD return the value stored in the "type" key of
     * the file in the $_FILES array.
     *
     * @return string|null The media type sent by the client or null if none
     * was provided.
     */
    public function getClientMediaType()
    {
        return $this->file['type'];
    }

    /**
     * hasMoved
     *
     * @return bool
     */
    public function hasMoved():bool
    {
        return $this->hasMoved;
    }
}
