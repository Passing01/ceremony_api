<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Template;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        // On nettoie les anciens templates qui n'utilisent pas le nouveau format HTML
        Template::where('config_schema->type', '!=', 'html')->delete();

        // Vos 3 templates actuels basés sur vos fichiers HTML
        $templates = [
            [
                'name' => 'Invitation Classique',
                'category' => 'Mariage',
                'price_per_pack' => 15.00,
                'preview_image' => 'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?w=600&h=400&fit=crop',
                'file' => 'invitation1.html'
            ],
            [
                'name' => 'Invitation Interactive',
                'category' => 'Mariage',
                'price_per_pack' => 20.00,
                'preview_image' => 'https://images.unsplash.com/photo-1519225421980-715cb0215aed?w=600&h=400&fit=crop',
                'file' => 'invitation2.html'
            ],
            [
                'name' => 'Story Moderne',
                'category' => 'Anniversaire',
                'price_per_pack' => 12.00,
                'preview_image' => 'https://images.unsplash.com/photo-1530103043960-ef38714abb15?w=600&h=400&fit=crop',
                'file' => 'invitation3.html'
            ],
        ];

        foreach ($templates as $t) {
            Template::updateOrCreate(
                ['name' => $t['name']],
                [
                    'category' => $t['category'],
                    'price_per_pack' => $t['price_per_pack'],
                    'preview_image' => $t['preview_image'],
                    'config_schema' => [
                        'type' => 'html',
                        'file' => $t['file']
                    ],
                    'is_active' => true,
                ]
            );
        }
    }
}
