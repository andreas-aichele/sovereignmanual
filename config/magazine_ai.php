<?php

return [
    'provider' => env('MAGAZINE_AI_PROVIDER', env('AI_TEXT_PROVIDER', 'gemini')),
    'text_model' => env('MAGAZINE_AI_TEXT_MODEL', 'gemini-2.5-flash'),
    'image_model' => env('MAGAZINE_AI_IMAGE_MODEL', 'gemini-2.5-flash-image'),
];
