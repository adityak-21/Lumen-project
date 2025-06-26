<?php

namespace App\Providers;
use App\Events\UserRegistered;
use App\Listeners\SendRegisteredEmail;
use App\Events\TaskRegistered;
use App\Listeners\SendTaskAssignedEmail;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        // \App\Events\ExampleEvent::class => [
        //     \App\Listeners\ExampleListener::class,
        // ],

        UserRegistered::class => [
            SendRegisteredEmail::class,
        ],

        TaskRegistered::class => [
            SendTaskAssignedEmail::class,
        ],
    ];

    
}
