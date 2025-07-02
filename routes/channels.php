<?php
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('private-user.{id}', function ($user, $id) {
    \Log::info('Broadcast auth haha', [
        'user' => $user,
        'id' => $id
    ]);
    return (int) $user->id === (int) $id;
});