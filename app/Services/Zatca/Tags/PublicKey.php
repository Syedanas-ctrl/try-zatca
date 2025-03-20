<?php

namespace App\Services\Zatca\Tags;

use App\Services\Zatca\Tag;

class PublicKey extends Tag
{
    public function __construct($value)
    {
        parent::__construct(8, $value);
    }
}
