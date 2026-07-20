<?php

return [
    'provider' => env('MAGAZINE_AI_PROVIDER', env('AI_TEXT_PROVIDER', 'gemini')),
    'text_model' => env('MAGAZINE_AI_TEXT_MODEL', 'gemini-2.5-flash'),
    'image_model' => env('MAGAZINE_AI_IMAGE_MODEL', 'gemini-2.5-flash-image'),
    'primary_locale' => env('MAGAZINE_AI_PRIMARY_LOCALE', 'de'),
    'allow_fallback_publication' => env('MAGAZINE_AI_ALLOW_FALLBACK_PUBLICATION', false),
    'news_research_timeout' => (int) env('MAGAZINE_AI_NEWS_RESEARCH_TIMEOUT', 180),
];
