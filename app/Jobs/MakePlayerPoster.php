<?php

namespace App\Jobs;

use App\Factories\PosterFactory;
use App\Models\Player;
use App\Models\Poster;

class MakePlayerPoster extends BaseJob
{
    public string $queue = 'poster';

    public function __construct(protected Player $player, protected Poster $poster)
    {
    }

    public function handle(): void
    {
        $picture = new PosterFactory($this->player);
        $image = $picture->resource();
        $path = sprintf('%s/%s.%s', date('Y/m/d'), md5($image), 'jpg');

        $storage = $this->player->application->domainPool->storage();
        $storage->put($path, $image->toString());

        $this->poster->update([
            'cover' => $path,
        ]);
    }
}
