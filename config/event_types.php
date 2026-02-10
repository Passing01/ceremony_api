<?php

return [
    'wedding' => [
        'name' => 'Mariage',
        'fields' => [
            [
                'name' => 'groom_name',
                'type' => 'text',
                'label' => 'Nom du marié',
                'required' => true,
            ],
            [
                'name' => 'bride_name',
                'type' => 'text',
                'label' => 'Nom de la mariée',
                'required' => true,
            ],
            [
                'name' => 'groom_photo',
                'type' => 'image',
                'label' => 'Photo du marié',
                'required' => false,
            ],
            [
                'name' => 'bride_photo',
                'type' => 'image',
                'label' => 'Photo de la mariée',
                'required' => false,
            ],
            [
                'name' => 'dress_code',
                'type' => 'text',
                'label' => 'Code vestimentaire',
                'required' => false,
            ],
            [
                'name' => 'ceremony_time',
                'type' => 'time',
                'label' => 'Heure de la cérémonie',
                'required' => false,
            ],
            [
                'name' => 'reception_time',
                'type' => 'time',
                'label' => 'Heure de la réception',
                'required' => false,
            ],
        ],
    ],

    'birthday' => [
        'name' => 'Anniversaire',
        'fields' => [
            [
                'name' => 'celebrant_name',
                'type' => 'text',
                'label' => 'Nom du célébrant',
                'required' => true,
            ],
            [
                'name' => 'celebrant_photo',
                'type' => 'image',
                'label' => 'Photo du célébrant',
                'required' => false,
            ],
            [
                'name' => 'age',
                'type' => 'number',
                'label' => 'Âge',
                'required' => false,
            ],
            [
                'name' => 'theme',
                'type' => 'text',
                'label' => 'Thème de la fête',
                'required' => false,
            ],
            [
                'name' => 'dress_code',
                'type' => 'text',
                'label' => 'Code vestimentaire',
                'required' => false,
            ],
        ],
    ],

    'baptism' => [
        'name' => 'Baptême',
        'fields' => [
            [
                'name' => 'child_name',
                'type' => 'text',
                'label' => 'Nom de l\'enfant',
                'required' => true,
            ],
            [
                'name' => 'child_photo',
                'type' => 'image',
                'label' => 'Photo de l\'enfant',
                'required' => false,
            ],
            [
                'name' => 'parents_names',
                'type' => 'text',
                'label' => 'Noms des parents',
                'required' => false,
            ],
            [
                'name' => 'godparents_names',
                'type' => 'text',
                'label' => 'Noms des parrains/marraines',
                'required' => false,
            ],
            [
                'name' => 'church_name',
                'type' => 'text',
                'label' => 'Nom de l\'église',
                'required' => false,
            ],
        ],
    ],

    'corporate' => [
        'name' => 'Événement d\'entreprise',
        'fields' => [
            [
                'name' => 'company_name',
                'type' => 'text',
                'label' => 'Nom de l\'entreprise',
                'required' => true,
            ],
            [
                'name' => 'company_logo',
                'type' => 'image',
                'label' => 'Logo de l\'entreprise',
                'required' => false,
            ],
            [
                'name' => 'event_purpose',
                'type' => 'text',
                'label' => 'Objectif de l\'événement',
                'required' => false,
            ],
            [
                'name' => 'dress_code',
                'type' => 'text',
                'label' => 'Code vestimentaire',
                'required' => false,
            ],
            [
                'name' => 'agenda',
                'type' => 'textarea',
                'label' => 'Ordre du jour',
                'required' => false,
            ],
        ],
    ],

    'graduation' => [
        'name' => 'Remise de diplôme',
        'fields' => [
            [
                'name' => 'graduate_name',
                'type' => 'text',
                'label' => 'Nom du diplômé',
                'required' => true,
            ],
            [
                'name' => 'graduate_photo',
                'type' => 'image',
                'label' => 'Photo du diplômé',
                'required' => false,
            ],
            [
                'name' => 'degree',
                'type' => 'text',
                'label' => 'Diplôme obtenu',
                'required' => false,
            ],
            [
                'name' => 'institution',
                'type' => 'text',
                'label' => 'Établissement',
                'required' => false,
            ],
            [
                'name' => 'year',
                'type' => 'number',
                'label' => 'Année',
                'required' => false,
            ],
        ],
    ],

    'other' => [
        'name' => 'Autre événement',
        'fields' => [
            [
                'name' => 'event_description',
                'type' => 'textarea',
                'label' => 'Description de l\'événement',
                'required' => false,
            ],
            [
                'name' => 'main_photo',
                'type' => 'image',
                'label' => 'Photo principale',
                'required' => false,
            ],
            [
                'name' => 'special_notes',
                'type' => 'textarea',
                'label' => 'Notes spéciales',
                'required' => false,
            ],
        ],
    ],
];
