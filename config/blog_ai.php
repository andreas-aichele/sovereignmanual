<?php

return [
    'provider' => env('BLOG_AI_PROVIDER', env('AI_TEXT_PROVIDER', 'gemini')),
    'text_model' => env('BLOG_AI_TEXT_MODEL', 'gemini-2.5-flash'),
    'image_model' => env('BLOG_AI_IMAGE_MODEL', 'gemini-2.5-flash-image'),
    'auto_publish_minimum_score' => (int) env('BLOG_AI_AUTO_PUBLISH_MINIMUM_SCORE', 85),
    'default_review_interval_days' => (int) env('BLOG_AI_DEFAULT_REVIEW_INTERVAL_DAYS', 365),
];
