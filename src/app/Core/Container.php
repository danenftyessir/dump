<?php

namespace Core;

use Exception;

class Container
{
    protected $bindings = [];
    protected $instances = [];

    // set binding untuk dependency
    public function set($key, $callback) {
        $this->bindings[$key] = $callback;
    }

    // get instance dari dependency
    public function get($key) {
        // cek apakah sudah ada instance (singleton)
        if (isset($this->instances[$key])) {
            return $this->instances[$key];
        }

        // cek apakah ada binding untuk key ini
        if (!isset($this->bindings[$key])) {
            throw new Exception("No binding found for key: {$key}");
        }

        // eksekusi callback untuk membuat instance
        $callback = $this->bindings[$key];
        $newInstance = $callback($this);

        // simpan instance untuk singleton pattern
        $this->instances[$key] = $newInstance;

        return $newInstance;
    }

    // check apakah binding ada
    public function has($key) {
        return isset($this->bindings[$key]);
    }

    // hapus binding
    public function remove($key) {
        unset($this->bindings[$key]);
        unset($this->instances[$key]);
    }

    // hapus semua binding
    public function clear() {
        $this->bindings = [];
        $this->instances = [];
    }
}