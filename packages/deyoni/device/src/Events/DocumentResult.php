<?php

namespace Deyoni\Device\Events;

class DocumentResult
{
    public function __construct(public bool $success, public string $path, public string $action) {}
}
