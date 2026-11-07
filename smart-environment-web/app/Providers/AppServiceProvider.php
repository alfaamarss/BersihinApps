<?php

namespace App\Providers;

use App\Models\Menu;
use App\Models\User;
use App\Models\AppSetting;
use App\Models\ServiceAccount;
use App\Models\Invoice;
use App\Observers\ServiceAccountObserver;
use App\Observers\InvoiceObserver;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

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
        $baseUrl = $this->resolveBaseAppUrl();
        config(['app.url' => $baseUrl]);

        ResetPassword::createUrlUsing(function (object $notifiable, string $token) use ($baseUrl) {
            $email = urlencode($notifiable->getEmailForPasswordReset());
            return $baseUrl . '/reset-password/' . $token . '?email=' . $email;
        });

        // Register observers for auto-generating waste deliveries
        ServiceAccount::observe(ServiceAccountObserver::class);
        Invoice::observe(InvoiceObserver::class);

        // Force HTTPS in production
        // if (config('app.env') === 'production' || str_contains(config('app.url'), 'https://')) {
        //     URL::forceScheme('https');
        // }
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }


        View::composer(['layouts.sidebar', 'layouts.master'], function ($view) {
            $user = Auth::user();
            $menuTree = [];
            if ($user) {
                $roleIds = DB::table('model_has_roles')
                    ->where('model_type', User::class)
                    ->where('model_id', $user->id)
                    ->pluck('role_id');
                $allowedMenuIds = DB::table('role_has_menus')
                    ->whereIn('role_id', $roleIds)
                    ->pluck('menu_id')
                    ->unique();

                if ($allowedMenuIds->isNotEmpty()) {
                    $allMenus = Menu::whereIn('id', $allowedMenuIds)
                        ->orderByRaw("CASE WHEN parent_id IS NULL OR parent_id = '0' THEN id ELSE parent_id END")
                        ->orderBy('urutan')
                        ->get();

                    $byParent = $allMenus->groupBy(function ($m) {
                        return (string)($m->parent_id ?? '0');
                    });

                    $tops = collect()
                        ->merge($byParent->get('0', collect()))
                        ->merge($byParent->get('', collect()));

                    $tree = [];
                    foreach ($tops as $top) {
                        $level1 = $byParent->get((string)$top->id, collect());
                        $children = [];
                        foreach ($level1 as $child) {
                            $grand = $byParent->get((string)$child->id, collect());
                            $children[] = [
                                'menu' => $child,
                                'children' => $grand,
                            ];
                        }
                        $tree[] = [
                            'menu' => $top,
                            'children' => $children,
                        ];
                    }
                    $menuTree = $tree;
                }
            }

            // Get app settings
            $appSettings = AppSetting::first();
            if (!$appSettings) {
                $appSettings = (object) [
                    'app_name' => config('app.name', 'Bersihin'),
                    'app_logo_url' => null
                ];
            }

            $view->with([
                'menuTree' => $menuTree,
                'appSettings' => $appSettings
            ]);
        });
    }

    private function resolveBaseAppUrl(): string
    {
        $configuredUrl = rtrim((string) config('app.url'), '/');

        if (!app()->runningInConsole()) {
            $requestUrl = rtrim(url('/'), '/');
            if (!$this->isPlaceholderUrl($requestUrl)) {
                return $requestUrl;
            }
        }

        if ($this->isPlaceholderUrl($configuredUrl)) {
            return rtrim(config('app.production_url', $configuredUrl), '/');
        }

        return $configuredUrl;
    }

    private function isPlaceholderUrl(?string $url): bool
    {
        if (!$url) {
            return true;
        }

        return str_contains($url, 'localhost') || str_contains($url, '127.0.0.1');
    }
}
