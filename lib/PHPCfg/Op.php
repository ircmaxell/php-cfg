<?php

namespace PHPCfg;

abstract class Op {
    
    protected $attributes = array();

    public function __construct(array $attributes = array()) {
        $this->attributes = $attributes;
    }

    public function getType() {
        return strtr(substr(rtrim(get_class($this), '_'), strlen(__CLASS__)), '\\', '_');
    }

    public function getLine() {
        return $this->getAttribute('startLine', -1);
    }

    public function &getAttribute($key, $default = null) {
        if (!$this->hasAttribute($key)) {
            return $default;
        }
        return $this->attributes[$key];
    }

    public function hasAttribute($key) {
        return array_key_exists($key, $this->attributes);
    }

    abstract public function getVariableNames();

    abstract public function getSubBlocks();

}