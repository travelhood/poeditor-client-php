<?php

namespace Travelhood\PoEditor;

class Term extends Entity
{
    /** @var string */
    public $term;

    /** @var string */
    public $context;

    /** @var string */
    public $plural;

    /** @var string */
    public $created;

    /** @var string */
    public $updated;

    /** @var array */
    public $translation;

    /** @var string */
    public $reference;

    /** @var array */
    public $tags;

    /** @var string */
    public $comment;
}