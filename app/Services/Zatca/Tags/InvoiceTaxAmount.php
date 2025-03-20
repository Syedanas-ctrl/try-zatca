<?php

namespace App\Services\Zatca\Tags;

use App\Services\Zatca\Tag;

class InvoiceTaxAmount extends Tag
{
    public function __construct($value)
    {
        parent::__construct(5, $value);
    }
}
