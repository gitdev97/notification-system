<?php

namespace App\Providers;

use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Repositories\NotificationRepository;
use App\Repositories\TaskRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Binds repository interfaces to their Eloquent implementations in the service container.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    private array $repositories = [
        TaskRepositoryInterface::class => TaskRepository::class,
        NotificationRepositoryInterface::class => NotificationRepository::class,
    ];

    /**
     * Register repository bindings.
     */
    public function register(): void
    {
        foreach ($this->repositories as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }
}
