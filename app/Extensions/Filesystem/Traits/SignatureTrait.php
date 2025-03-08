<?php

namespace App\Extensions\Filesystem\Traits;

use DateTime;
use DateTimeZone;

trait SignatureTrait
{
    public function gmt_iso8601($time): string
    {
        return (new DateTime('', new DateTimeZone('UTC')))->setTimestamp($time)->format('Y-m-d\TH:i:s\Z');
    }
}
