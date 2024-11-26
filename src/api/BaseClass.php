<?php

namespace Magnit\Api;

use Magnit\MagnitClient;

class BaseClass
{

    private $client;

    public function __construct(MagnitClient $client)
    {
        $this->client = $client;
    }
}
