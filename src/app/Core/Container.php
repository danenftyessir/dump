<?php

namespace Core;

use Exception;

class Container
{
    protected $bindings = [];
    protected $instances = [];

    public function set($key, $callback) {
        $this->bindings[$key] = $callback;
    }

    public function get($key) {
        if (isset($this->instances[$key])) {
            return $this->instances[$key];
        }

        if (!isset($this->bindings[$key])) {
            throw new Exception("No binding found for key: {$key}");
        }

        $callback = $this->bindings[$key];
        $newInstance = $callback($this);

        $this->instances[$key] = $newInstance;

        return $newInstance;
    }
}