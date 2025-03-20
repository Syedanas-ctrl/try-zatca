<?php

namespace App\Services\Zatca\Tags;

use App\Services\Zatca\Tag;

class TaxNumber extends Tag
{
    public function __construct($value)
    {
        parent::__construct(2, $value);
    }
}
