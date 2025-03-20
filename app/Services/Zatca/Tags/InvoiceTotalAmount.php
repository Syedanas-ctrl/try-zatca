<?php

namespace App\Services\Zatca\Tags;

use App\Services\Zatca\Tag;

class InvoiceTotalAmount extends Tag
{
    public function __construct($value)
    {
        parent::__construct(4, $value);
    }
}
