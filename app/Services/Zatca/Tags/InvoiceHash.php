<?php

namespace App\Services\Zatca\Tags;

use App\Services\Zatca\Tag;

class InvoiceHash extends Tag
{
    public function __construct($value)
    {
        parent::__construct(6, $value);
    }
}
