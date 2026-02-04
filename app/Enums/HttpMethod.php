<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum HttpMethod: string implements HasLabel, HasColor
{
    case GET = 'GET';

    case POST = 'POST';

    case PUT = 'PUT';

    case PATCH = 'PATCH';

    case DELETE = 'DELETE';

    case OPTIONS = 'OPTIONS';

    case HEAD = 'HEAD';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::GET => 'GET',
            self::POST => 'POST',
            self::PUT => 'PUT',
            self::PATCH => 'PATCH',
            self::DELETE => 'DELETE',
            self::OPTIONS => 'OPTIONS',
            self::HEAD => 'HEAD',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::GET => 'success',
            self::POST => 'primary',
            self::PUT, self::PATCH => 'warning',
            self::DELETE => 'danger',
            self::OPTIONS, self::HEAD => 'secondary',
        };
    }
}