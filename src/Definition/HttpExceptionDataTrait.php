<?php

namespace Rapture\Http\Definition;

/**
 * HttpExceptionDataTrait
 *
 * @package Rapture\Http
 * @author  Iulian N. <rapture@iuliann.ro>
 * @license LICENSE MIT
 */
trait HttpExceptionDataTrait
{
    protected $data;

    /**
     * @param mixed $data Exception data
     *
     * @return void
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }
}
