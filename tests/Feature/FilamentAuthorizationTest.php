<?php

use App\Models\User;
use Filament\Facades\Filament;

test('only admin and editor users can access the filament panel', function () {
    $panel = Filament::getPanel('admin');

    $user = User::factory()->create();
    $editor = User::factory()->editor()->create();
    $admin = User::factory()->admin()->create();

    expect($user->canAccessPanel($panel))->toBeFalse()
        ->and($editor->canAccessPanel($panel))->toBeTrue()
        ->and($admin->canAccessPanel($panel))->toBeTrue();
});
