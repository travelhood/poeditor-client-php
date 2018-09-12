<?php

namespace Travelhood\PoEditor;

abstract class Entity
{
    public function __construct($object)
    {
        $this->copyFrom($object);
    }

    public function copyFrom($object)
    {
        if (is_array($object) || is_object($object)) {
            foreach ($object as $key => $value) {
                $this->$key = $value;
            }
        }
    }
}