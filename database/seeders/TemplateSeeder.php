<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Template;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Mariage
        Template::updateOrCreate(
            ['name' => 'Royal Wedding'],
            [
                'category' => 'Mariage',
                'price_per_pack' => 9.99,
                'preview_image' => null,
                'config_schema' => [
                    'fields' => [
                        'event_type' => ['type' => 'string', 'label' => 'Type de cérémonie'],
                        'groom_photo' => ['type' => 'image', 'label' => 'Photo du marié', 'optional' => true],
                        'bride_photo' => ['type' => 'image', 'label' => 'Photo de la mariée', 'optional' => true],
                        'dress_code' => ['type' => 'string', 'label' => 'Dress code', 'optional' => true],
                        'locations' => ['type' => 'array', 'label' => 'Lieux', 'optional' => true],
                    ],
                ],
                'is_active' => true,
            ]
        );

        // Anniversaire
        Template::updateOrCreate(
            ['name' => 'Birthday Bash'],
            [
                'category' => 'Anniversaire',
                'price_per_pack' => 4.99,
                'preview_image' => null,
                'config_schema' => [
                    'fields' => [
                        'event_type' => ['type' => 'string'],
                        'dress_code' => ['type' => 'string', 'optional' => true],
                        'locations' => ['type' => 'array', 'optional' => true],
                        'theme' => ['type' => 'string', 'optional' => true],
                    ],
                ],
                'is_active' => true,
            ]
        );

        // Baptême
        Template::updateOrCreate(
            ['name' => 'Blessing Day'],
            [
                'category' => 'Baptême',
                'price_per_pack' => 6.99,
                'preview_image' => null,
                'config_schema' => [
                    'fields' => [
                        'event_type' => ['type' => 'string'],
                        'godparents' => ['type' => 'array', 'optional' => true],
                        'locations' => ['type' => 'array', 'optional' => true],
                    ],
                ],
                'is_active' => true,
            ]
        );
    }
}
