<?php

namespace AppBundle\Exception;


use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class BadRestRequestHttpException extends HttpException
{
    private $errors;
    public function __construct($errors = [], $code = 0, Throwable $previous = null)
    {
        $this->errors = $errors;
        parent::__construct(400, "Bad request", $previous, array(), $code);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function setErrors(array $errors)
    {
        $this->errors = $errors;
    }
}