<!-- markdownlint-disable-next-line MD033 MD041 -->
<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<!-- markdownlint-disable MD033 -->
<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>
<!-- markdownlint-enable MD033 -->

## 📖 API documentation

Start the Laravel server with `php artisan serve`, then open Swagger UI at
`http://127.0.0.1:8000/swagger.html`. The OpenAPI 3.0 specification is available
at `http://127.0.0.1:8000/openapi.json`. The generated spec covers all
registered Laravel API routes, derives request schemas from Laravel validation
rules, documents controller response structures and authentication middleware,
and uses matching Postman requests as examples.

Regenerate it after changing routes or Postman collections:

```bash
python scripts/generate_openapi.py
```

### Populate a local demo dashboard

On a local or testing database, add realistic dashboard sample records without
resetting existing data:

```bash
php artisan db:seed --class=DemoDashboardSeeder
```

The opt-in seeder is idempotent and only creates clearly marked demo records.
It is not included in the default `DatabaseSeeder` and refuses to run in
production.

Demo mobile login accounts (password: `Demo12345!`):

- Resident: `demo.warga01@example.test`
- Collector: `demo.kolektor01@example.test`

The seeder resets these demo accounts to the documented password each time it
runs. Use only against a local or testing database.

## 📱 Mobile API quick start

The `/api/v1/mobile` namespace exposes dedicated endpoints for the Smart Environment mobile app:

- `GET /api/v1/mobile/dashboard` – aggregated home data: outstanding invoices, next pickup, recent activities.
- `GET /api/v1/mobile/areas` – paginated area lookup with optional `level` and `q` filters (e.g. kelurahan search).
- `GET /api/v1/mobile/service-accounts` – list the authenticated user's service accounts with schedule and billing stats.
- `POST /api/v1/mobile/service-accounts` – create a new service account (requires `name`, `area_id`, `address`, coordinates).
- `GET /api/v1/mobile/service-accounts/{id}` – fetch a specific service account owned by the user.
- `PATCH /api/v1/mobile/service-accounts/{id}` – update contact details, address, or status.
- `DELETE /api/v1/mobile/service-accounts/{id}` – soft-delete a service account.

All mobile endpoints require Sanctum bearer tokens (`Authorization: Bearer {token}`) obtained through the existing `/api/v1/auth/login` flow.

### Testing the mobile API locally

```bash
php artisan migrate:fresh --seed
php artisan test --filter=Mobile
```

The targeted test suite covers dashboard aggregation and service-account CRUD, ensuring safe iteration before handing builds to the mobile team.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
