<?php

return [
    'alternate_locale' => 'de',
    'routes' => [
        'index' => 'magazine.index',
        'show' => 'magazine.show',
    ],
    'meta' => [
        'title' => 'Sovereign Manual Magazine',
        'description' => 'Bitcoin, financial intelligence, and sovereign independence.',
    ],
    'index' => [
        'eyebrow' => 'Magazine // Analysis',
        'heading' => 'Sovereign Manual Magazine',
        'featured' => 'Latest article',
        'read' => 'Read article',
        'empty' => 'No published articles yet.',
    ],
    'show' => [
        'alternate' => 'Read in German',
        'breadcrumb_label' => 'Breadcrumb',
        'category' => 'Category',
        'details' => 'Article details',
        'language' => 'Language',
        'magazine' => 'Magazine',
    ],
    'categories' => [
        'bitcoin' => 'Bitcoin',
        'financial-independence' => 'Financial independence',
        'self-custody' => 'Self custody',
    ],
];
