<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RegistrationCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly array $data,
        public readonly string $locale,
        public readonly array $record,
    ) {}
}
