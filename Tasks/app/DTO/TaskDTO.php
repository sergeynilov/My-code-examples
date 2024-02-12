<?php

namespace App\DTO;

use DateTime;
use WendellAdriel\ValidatedDTO\Casting\CarbonCast;
use WendellAdriel\ValidatedDTO\Casting\StringCast;
use WendellAdriel\ValidatedDTO\SimpleDTO;

class TaskDTO extends SimpleDTO
{
    public ?int $user_id=null; // TODO
    public string $title;
    public string $priority;
    public string $status;
    public string $description;
    public ?datetime $completed_at = null;
    public ?datetime $created_at = null;

    /**
     * @return array<string, string>
     */
    protected function defaults(): array
    {
        return [];
    }

    /**
     * @return array
     */
    protected function casts(): array
    {
        return [
            'priority' => new StringCast,
            'completed_at' => new CarbonCast,
            'created_at' => new CarbonCast
        ];
    }
}
