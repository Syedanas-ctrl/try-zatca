<?php

namespace App\Services\Zatca\Tags;

use App\Services\Zatca\Tag;

class InvoiceDate extends Tag
{
    public function __construct($value)
    {
        parent::__construct(3, $value);
    }
}
