<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Relation::morphMap([
            'invoice' => \App\Models\Invoice::class,
            'expense' => \App\Models\Expense::class,
            'contact' => \App\Models\Contact::class,
            'quote'   => \App\Models\Quote::class,
        ]);
    }
}
