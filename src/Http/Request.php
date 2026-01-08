<?php

namespace Jtech\Http;

use Illuminate\Session\SessionManager;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request as IlluminateRequest;

class Request extends IlluminateRequest
{
  public function validate($request, array $rules, array $messages = [])
  {
    $validator = app('validator')->make($this->all(), $rules, $messages);

    if ($validator->fails()) {
      // Ini magic-nya: Throw exception biar ditangkep sama ErrorHandler
      throw new ValidationException($validator);
    }

    return $validator->validated();
  }
}
