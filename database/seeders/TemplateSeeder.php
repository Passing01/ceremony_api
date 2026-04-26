<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Template;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        Template::where('config_schema->type', '!=', 'html')->delete();

        $templates = [
            [
                'name' => 'Invitation Classique',
                'category' => 'Mariage',
                'price_per_pack' => 15.00,
                'preview_image' => 'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?w=600&h=400&fit=crop',
                'file' => 'invitation1.html',
                'sections' => [
                    [
                        'id' => 'intro',
                        'label' => 'Introduction',
                        'fields' => [
                            ['id' => 'names', 'label' => 'Noms des mariés', 'type' => 'text', 'placeholder' => 'Sarah & Tom'],
                        ]
                    ],
                    [
                        'id' => 'ch1',
                        'label' => 'Notre Histoire',
                        'fields' => [
                            ['id' => 'title', 'label' => 'Titre', 'type' => 'text', 'placeholder' => 'Le premier regard'],
                            ['id' => 'text', 'label' => 'Texte', 'type' => 'textarea', 'placeholder' => 'Racontez votre rencontre...'],
                            ['id' => 'media', 'label' => 'Photo ou Vidéo', 'type' => 'media'],
                        ]
                    ],
                    [
                        'id' => 'ceremony',
                        'label' => 'La Cérémonie',
                        'fields' => [
                            ['id' => 'date', 'label' => 'Date et Heure', 'type' => 'datetime'],
                            ['id' => 'location', 'label' => 'Lieu', 'type' => 'text', 'placeholder' => 'Adresse de la mairie...'],
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Story Moderne',
                'category' => 'Anniversaire',
                'price_per_pack' => 12.00,
                'preview_image' => 'https://images.unsplash.com/photo-1530103043960-ef38714abb15?w=600&h=400&fit=crop',
                'file' => 'invitation3.html',
                'sections' => [
                    [
                        'id' => 'main',
                        'label' => 'Informations',
                        'fields' => [
                            ['id' => 'title', 'label' => 'Titre', 'type' => 'text', 'placeholder' => 'Mes 20 ans !'],
                            ['id' => 'message', 'label' => 'Message', 'type' => 'textarea'],
                            ['id' => 'media', 'label' => 'Photo de couverture', 'type' => 'media'],
                        ]
                    ]
                ]
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
                        'file' => $t['file'],
                        'sections' => $t['sections']
                    ],
                    'is_active' => true,
                ]
            );
        }
    }
}
