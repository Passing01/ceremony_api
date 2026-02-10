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
                    'rendering' => [
                        'title' => ['x' => 540, 'y' => 800, 'font' => 'Playfair Display', 'size' => 48, 'color' => '#856404'],
                        'date' => ['x' => 540, 'y' => 950, 'font' => 'Playfair Display', 'size' => 24, 'color' => '#000000'],
                        'location' => ['x' => 540, 'y' => 1050, 'font' => 'Playfair Display', 'size' => 20, 'color' => '#000000'],
                    ]
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
                    'rendering' => [
                        'title' => ['x' => 100, 'y' => 300, 'font' => 'Bungee', 'size' => 60, 'color' => '#FF0000'],
                        'date' => ['x' => 100, 'y' => 450, 'font' => 'Roboto', 'size' => 24, 'color' => '#FFFFFF'],
                    ]
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
                    'rendering' => [
                        'title' => ['x' => 540, 'y' => 400, 'font' => 'Great Vibes', 'size' => 50, 'color' => '#3498db'],
                        'date' => ['x' => 540, 'y' => 600, 'font' => 'Roboto', 'size' => 24, 'color' => '#333333'],
                    ]
                ],
                'is_active' => true,
            ]
        );
    }
}
