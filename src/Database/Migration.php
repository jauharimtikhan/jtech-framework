<?php

namespace Jtech\Database;

use Illuminate\Database\ConnectionInterface;

abstract class Migration
{
  protected ConnectionInterface $db;

  public function __construct()
  {
    $this->db = app()->make('db')->connection();
  }

  abstract public function up(): void;
  abstract public function down(): void;

  public function getConnection(): ConnectionInterface
  {
    return $this->db;
  }
}
