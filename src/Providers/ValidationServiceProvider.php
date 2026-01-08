<?php

namespace Jtech\Providers;

use Jtech\ServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Illuminate\Database\Capsule\Manager as Capsule;

class ValidationServiceProvider
{
  public static function register($app, Capsule $db)
  {
    $loader = new FileLoader(
      new Filesystem,
      BASEPATH . "/lang" // folder lang lo
    );

    $translator = new Translator($loader, 'en');

    $validatorFactory = new Factory($translator);
    $validatorFactory->setPresenceVerifier(
      new \Illuminate\Validation\DatabasePresenceVerifier(
        $db->getDatabaseManager()
      )
    );
    $app->bind('validator', fn() => $validatorFactory);
  }
}
