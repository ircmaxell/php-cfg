<?php

namespace PHPCfg;

abstract class Op {
    
    protected $attributes = array();
    protected $writeVariables = [];

    public function __construct(array $attributes = array()) {
        $this->attributes = $attributes;
    }

    public function getType() {
    	return strtr(substr(rtrim(get_class($this), '_'), strlen(__CLASS__) + 1), '\\', '_');
    }

    public function getLine() {
        return $this->getAttribute('startLine', -1);
    }

    public function getFile() {
    	return $this->getAttribute("filename", "unknown");
    }

    public function &getAttribute($key, $default = null) {
        if (!$this->hasAttribute($key)) {
            return $default;
        }
        return $this->attributes[$key];
    }

    public function setAttribute($key, &$value) {
        $this->attributes[$key] = $value;
    }

    public function hasAttribute($key) {
        return array_key_exists($key, $this->attributes);
    }

    public function getAttributes() {
        return $this->attributes;
    }

    abstract public function getVariableNames();

    abstract public function getSubBlocks();

    public function isWriteVariable($name) {
        return in_array($name, $this->writeVariables);
    }

}
