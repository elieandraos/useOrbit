<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Carrier;
use App\Models\Client;
use App\Models\Document;
use App\Models\Note;
use App\Models\Tag;
use App\Models\User;
use App\Policies\CarrierPolicy;
use App\Policies\ClientPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\NotePolicy;
use App\Policies\TagPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Inertia\ExceptionResponse;
use Inertia\Inertia;

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
        $this->configureDefaults();
        $this->registerPolicies();
        $this->registerMorphMap();
        $this->configureExceptionHandling();
    }

    protected function registerPolicies(): void
    {
        Gate::policy(Carrier::class, CarrierPolicy::class);
        Gate::policy(Client::class, ClientPolicy::class);
        Gate::policy(Document::class, DocumentPolicy::class);
        Gate::policy(Note::class, NotePolicy::class);
        Gate::policy(Tag::class, TagPolicy::class);
    }

    protected function registerMorphMap(): void
    {
        Relation::enforceMorphMap([
            'clients' => Client::class,
            'documents' => Document::class,
            'users' => User::class,
        ]);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        JsonResource::withoutWrapping();

        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Render an in-app Inertia error page for unhandled 4xx/5xx responses instead of falling back
     * to Laravel's default HTML error page.
     */
    protected function configureExceptionHandling(): void
    {
        Inertia::handleExceptionsUsing(function (ExceptionResponse $response) {
            if (config('app.debug')) {
                return null;
            }

            if (in_array($response->statusCode(), [403, 404, 500, 503], true)) {
                return $response->render('ErrorPage', [
                    'status' => $response->statusCode(),
                ])->withSharedData();
            }

            return null;
        });
    }
}
