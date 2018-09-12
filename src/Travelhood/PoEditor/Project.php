<?php

namespace Travelhood\PoEditor;

class Project extends Entity
{
    /** @var int */
    public $id;

    /** @var string */
    public $name;

    /** @var string */
    public $description = "";

    /** @var int */
    public $public = 0;

    /** @var int */
    public $open = 0;

    /** @var string */
    public $reference_language = "";

    /** @var int */
    public $terms = 0;

    /** @var string */
    public $created;
}