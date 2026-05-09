<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class MenuItemFactory extends Factory
{
    protected $model = MenuItem::class;

    public function definition(): array
    {
        return [
            'titulo'   => $this->faker->word(),
            'link'     => $this->faker->slug(2).'.html',
            'icone'    => null,
            'page_key' => null,
            'pai_id'   => null,
            'ordem'    => rand(1, 99),
            'visivel'  => true,
            'externo'  => false,
        ];
    }
}
