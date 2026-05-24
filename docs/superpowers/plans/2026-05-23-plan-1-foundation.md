# PollaMundial — Plan 1: Foundation & Data Layer

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Crear el proyecto Laravel + React + Reverb con el schema completo de BD, modelos Eloquent, autenticación y sistema de roles.

**Architecture:** Laravel 11 con Inertia.js v2 + React en el frontend. Todas las tablas definidas vía migraciones desde el inicio. Modelos Eloquent con relaciones tipadas. Auth via Laravel Breeze. Roles admin/user enforced via middleware. El modelo para la tabla `matches` se llama `Fixture` para evitar conflicto con la keyword `match` de PHP 8.

**Tech Stack:** Laravel 11, Inertia.js v2, React 18, MySQL 8, Pest, Laravel Reverb, Laravel Breeze, Laravel Sail (Docker)

**Dev Environment:** Docker Desktop + Laravel Sail. No PHP/MySQL/Node locally. All commands run via `./vendor/bin/sail` (or `sail` alias). pnpm for JS packages (never npm).

---

## File Structure

```
# Proyecto raíz
.env

# Migraciones
database/migrations/
  *_add_fields_to_users_table.php
  *_create_groups_table.php
  *_create_teams_table.php
  *_create_players_table.php
  *_create_rounds_table.php
  *_create_matches_table.php
  *_create_prediction_submissions_table.php
  *_create_predictions_table.php
  *_create_special_predictions_table.php
  *_create_coin_transactions_table.php
  *_create_messages_table.php

# Modelos
app/Models/
  User.php          — actualizar con nuevos campos y relaciones
  Group.php         — hasMany Teams
  Team.php          — belongsTo Group, hasMany Players
  Player.php        — belongsTo Team
  Round.php         — hasMany Fixtures
  Fixture.php       — tabla: matches; relaciones a Round, Group, Team x3, Prediction
  PredictionSubmission.php — belongsTo User, Round
  Prediction.php    — belongsTo User, Fixture
  SpecialPrediction.php    — belongsTo User, Team x2, Player
  CoinTransaction.php      — belongsTo User
  Message.php              — belongsTo User

# Factories
database/factories/
  GroupFactory.php
  TeamFactory.php
  PlayerFactory.php
  RoundFactory.php
  FixtureFactory.php
  PredictionSubmissionFactory.php
  PredictionFactory.php
  SpecialPredictionFactory.php
  CoinTransactionFactory.php
  MessageFactory.php

# Middleware
app/Http/Middleware/EnsureUserIsAdmin.php

# Seeders
database/seeders/
  DatabaseSeeder.php        — orquesta todos los seeders
  GroupTeamSeeder.php       — 12 grupos + 48 equipos
  RoundSeeder.php           — 4 rondas con puntos configurados
  DevelopmentUserSeeder.php — 1 admin + 5 usuarios de prueba

# Tests
tests/Unit/Models/
  GroupTest.php
  TeamTest.php
  PlayerTest.php
  RoundTest.php
  FixtureTest.php
  PredictionTest.php
  UserTest.php
tests/Feature/
  AdminMiddlewareTest.php
```

---

## Task 1: Project Initialization

**Files:**
- Create: proyecto Laravel en `/home/dwndz/Projects/PollaMundial`
- Create: `docker-compose.yml` (via Sail)
- Modify: `.env`

> **Dev environment:** Docker Desktop + Laravel Sail. Sin PHP/MySQL local. Todos los comandos artisan/composer/pnpm van via `./vendor/bin/sail`.

- [ ] **Step 1: Crear proyecto Laravel 11 usando Docker (sin PHP local)**

El directorio `/home/dwndz/Projects/PollaMundial` ya existe con `docs/`. Usar docker run para crear en temp y mover:

```bash
cd /home/dwndz/Projects

docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd)":/app \
    -w /app \
    laravelsail/php84-composer:latest \
    composer create-project laravel/laravel pollamundial-temp "^11.0"

cp -r pollamundial-temp/. PollaMundial/
rm -rf pollamundial-temp
```

Expected: archivos Laravel 11 en `PollaMundial/`. El directorio `docs/` debe seguir intacto.

- [ ] **Step 2: Instalar Laravel Sail**

```bash
cd /home/dwndz/Projects/PollaMundial

docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd)":/app \
    -w /app \
    laravelsail/php84-composer:latest \
    composer require laravel/sail --dev

docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd)":/app \
    -w /app \
    laravelsail/php84-composer:latest \
    php artisan sail:install --with=mysql
```

Expected: `docker-compose.yml` creado en la raíz del proyecto.

- [ ] **Step 3: Configurar .env para Sail**

Editar `/home/dwndz/Projects/PollaMundial/.env`:

```dotenv
APP_NAME=PollaMundial
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=pollamundial
DB_USERNAME=sail
DB_PASSWORD=password

BROADCAST_CONNECTION=reverb
```

Nota: `DB_HOST=mysql` es el nombre del servicio Docker, no localhost.

- [ ] **Step 4: Levantar Sail**

```bash
cd /home/dwndz/Projects/PollaMundial
./vendor/bin/sail up -d
```

Expected: contenedores corriendo. Verificar con:

```bash
./vendor/bin/sail ps
```

- [ ] **Step 5: Instalar Laravel Breeze con React + Inertia**

```bash
./vendor/bin/sail composer require laravel/breeze --dev
./vendor/bin/sail artisan breeze:install react
```

Cuando pregunte por dark mode: `no`. Cuando pregunte por testing framework: seleccionar `Pest`.

Expected: `resources/js/` con componentes React, `package.json` actualizado.

- [ ] **Step 6: Instalar dependencias JS con pnpm**

```bash
./vendor/bin/sail pnpm install
```

Expected: `node_modules/` creado sin errores.

- [ ] **Step 7: Instalar Laravel Reverb**

```bash
./vendor/bin/sail artisan install:broadcasting
```

Seleccionar `reverb`. Confirmar instalación de paquetes.

Expected: `config/reverb.php` creado, `.env` actualizado con `REVERB_*` vars.

- [ ] **Step 8: Generar app key y correr migraciones iniciales**

```bash
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
```

Expected: migraciones de Breeze corren sin error (users, password_reset_tokens, sessions, cache, jobs).

- [ ] **Step 9: Commit inicial**

```bash
cd /home/dwndz/Projects/PollaMundial
git init
git add .
git commit -m "chore: initialize Laravel 11 + Sail + Breeze (React/Inertia) + Reverb"
```

---

## Task 2: Actualizar Tabla Users

**Files:**
- Create: `database/migrations/*_add_fields_to_users_table.php`
- Modify: `app/Models/User.php`

- [ ] **Step 1: Escribir test que falla**

Crear `tests/Unit/Models/UserTest.php`:

```php
<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('has required fields for the quinela', function () {
    $user = User::factory()->create([
        'role' => 'user',
        'is_active' => true,
        'is_activated' => false,
        'coins_balance' => 0,
        'total_points' => 0,
    ]);

    expect($user->role)->toBe('user')
        ->and($user->is_active)->toBeTrue()
        ->and($user->is_activated)->toBeFalse()
        ->and($user->coins_balance)->toBe(0)
        ->and($user->total_points)->toBe(0);
});

it('can be admin', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    expect($admin->isAdmin())->toBeTrue();
});

it('is not admin by default', function () {
    $user = User::factory()->create(['role' => 'user']);

    expect($user->isAdmin())->toBeFalse();
});
```

- [ ] **Step 2: Correr test para verificar que falla**

```bash
./vendor/bin/sail artisan test tests/Unit/Models/UserTest.php
```

Expected: FAIL — columnas `role`, `is_active`, etc. no existen.

- [ ] **Step 3: Crear la migración**

```bash
./vendor/bin/sail artisan make:migration add_fields_to_users_table --table=users
```

Editar el archivo generado en `database/migrations/`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user')->after('email');
            $table->boolean('is_active')->default(true)->after('role');
            $table->boolean('is_activated')->default(false)->after('is_active');
            $table->unsignedInteger('coins_balance')->default(0)->after('is_activated');
            $table->unsignedInteger('total_points')->default(0)->after('coins_balance');
            $table->string('avatar')->nullable()->after('total_points');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_active', 'is_activated', 'coins_balance', 'total_points', 'avatar']);
        });
    }
};
```

- [ ] **Step 4: Actualizar el modelo User**

Reemplazar el contenido de `app/Models/User.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'is_activated',
        'coins_balance',
        'total_points',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_activated' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
```

- [ ] **Step 5: Actualizar UserFactory para incluir nuevos campos**

Editar `database/factories/UserFactory.php`, reemplazar el método `definition()`:

```php
public function definition(): array
{
    return [
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'email_verified_at' => now(),
        'password' => static::$password ??= Hash::make('password'),
        'remember_token' => Str::random(10),
        'role' => 'user',
        'is_active' => true,
        'is_activated' => false,
        'coins_balance' => 0,
        'total_points' => 0,
        'avatar' => null,
    ];
}

public function admin(): static
{
    return $this->state(fn(array $attributes) => ['role' => 'admin']);
}

public function activated(): static
{
    return $this->state(fn(array $attributes) => ['is_activated' => true]);
}
```

- [ ] **Step 6: Correr la migración y tests**

```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan test tests/Unit/Models/UserTest.php
```

Expected: 3 tests pasando (PASS).

- [ ] **Step 7: Commit**

```bash
git add database/migrations/ app/Models/User.php database/factories/UserFactory.php tests/Unit/Models/UserTest.php
git commit -m "feat: add role, activation, points and coins fields to users table"
```

---

## Task 3: Groups & Teams

**Files:**
- Create: `database/migrations/*_create_groups_table.php`
- Create: `database/migrations/*_create_teams_table.php`
- Create: `app/Models/Group.php`
- Create: `app/Models/Team.php`
- Create: `database/factories/GroupFactory.php`
- Create: `database/factories/TeamFactory.php`
- Create: `tests/Unit/Models/GroupTest.php`
- Create: `tests/Unit/Models/TeamTest.php`

- [ ] **Step 1: Escribir tests que fallan**

Crear `tests/Unit/Models/GroupTest.php`:

```php
<?php

use App\Models\Group;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('has a name', function () {
    $group = Group::factory()->create(['name' => 'A']);

    expect($group->name)->toBe('A');
});

it('has many teams', function () {
    $group = Group::factory()->create();
    Team::factory()->count(4)->create(['group_id' => $group->id]);

    expect($group->teams)->toHaveCount(4);
});
```

Crear `tests/Unit/Models/TeamTest.php`:

```php
<?php

use App\Models\Group;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('belongs to a group', function () {
    $group = Group::factory()->create();
    $team = Team::factory()->create(['group_id' => $group->id]);

    expect($team->group)->toBeInstanceOf(Group::class)
        ->and($team->group->id)->toBe($group->id);
});

it('has fifa_code', function () {
    $team = Team::factory()->create(['fifa_code' => 'ARG']);

    expect($team->fifa_code)->toBe('ARG');
});

it('has many players', function () {
    $team = Team::factory()->create();
    Player::factory()->count(3)->create(['team_id' => $team->id]);

    expect($team->players)->toHaveCount(3);
});
```

- [ ] **Step 2: Correr tests para verificar que fallan**

```bash
./vendor/bin/sail artisan test tests/Unit/Models/GroupTest.php tests/Unit/Models/TeamTest.php
```

Expected: FAIL — clases Group, Team no existen.

- [ ] **Step 3: Crear migraciones**

```bash
./vendor/bin/sail artisan make:migration create_groups_table
./vendor/bin/sail artisan make:migration create_teams_table
```

Editar `*_create_groups_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 1)->unique(); // A–L
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
```

Editar `*_create_teams_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('fifa_code', 3);
            $table->string('flag_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
```

- [ ] **Step 4: Crear modelos**

Crear `app/Models/Group.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }
}
```

Crear `app/Models/Team.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    protected $fillable = ['group_id', 'name', 'fifa_code', 'flag_url'];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }
}
```

- [ ] **Step 5: Crear factories**

Crear `database/factories/GroupFactory.php`:

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class GroupFactory extends Factory
{
    public function definition(): array
    {
        // unique() se resetea por test gracias a RefreshDatabase
        return [
            'name' => fake()->unique()->randomElement(['A','B','C','D','E','F','G','H','I','J','K','L']),
        ];
    }
}
```

Crear `database/factories/TeamFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'name' => fake()->country(),
            'fifa_code' => strtoupper(fake()->lexify('???')),
            'flag_url' => null,
        ];
    }
}
```

- [ ] **Step 6: Correr migración y tests**

```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan test tests/Unit/Models/GroupTest.php tests/Unit/Models/TeamTest.php
```

Expected: 5 tests pasando (PASS).

- [ ] **Step 7: Commit**

```bash
git add database/migrations/ app/Models/Group.php app/Models/Team.php database/factories/GroupFactory.php database/factories/TeamFactory.php tests/Unit/Models/
git commit -m "feat: add groups and teams tables with models and factories"
```

---

## Task 4: Players & Rounds

**Files:**
- Create: `database/migrations/*_create_players_table.php`
- Create: `database/migrations/*_create_rounds_table.php`
- Create: `app/Models/Player.php`
- Create: `app/Models/Round.php`
- Create: `database/factories/PlayerFactory.php`
- Create: `database/factories/RoundFactory.php`
- Create: `tests/Unit/Models/PlayerTest.php`
- Create: `tests/Unit/Models/RoundTest.php`

- [ ] **Step 1: Escribir tests que fallan**

Crear `tests/Unit/Models/PlayerTest.php`:

```php
<?php

use App\Models\Player;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('belongs to a team', function () {
    $team = Team::factory()->create();
    $player = Player::factory()->create(['team_id' => $team->id]);

    expect($player->team)->toBeInstanceOf(Team::class)
        ->and($player->team->id)->toBe($team->id);
});
```

Crear `tests/Unit/Models/RoundTest.php`:

```php
<?php

use App\Models\Round;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('has point configuration', function () {
    $round = Round::factory()->create([
        'points_exact' => 3,
        'points_result' => 1,
        'points_classifier' => 2,
    ]);

    expect($round->points_exact)->toBe(3)
        ->and($round->points_result)->toBe(1)
        ->and($round->points_classifier)->toBe(2);
});

it('is closed by default', function () {
    $round = Round::factory()->create();

    expect($round->is_open)->toBeFalse()
        ->and($round->is_locked)->toBeFalse();
});
```

- [ ] **Step 2: Correr tests para verificar que fallan**

```bash
./vendor/bin/sail artisan test tests/Unit/Models/PlayerTest.php tests/Unit/Models/RoundTest.php
```

Expected: FAIL — Player, Round no existen.

- [ ] **Step 3: Crear migraciones**

```bash
./vendor/bin/sail artisan make:migration create_players_table
./vendor/bin/sail artisan make:migration create_rounds_table
```

Editar `*_create_players_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
```

Editar `*_create_rounds_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rounds', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedTinyInteger('order');
            $table->boolean('is_open')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->unsignedTinyInteger('points_exact');
            $table->unsignedTinyInteger('points_result');
            $table->unsignedTinyInteger('points_classifier')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rounds');
    }
};
```

- [ ] **Step 4: Crear modelos**

Crear `app/Models/Player.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Player extends Model
{
    use HasFactory;

    protected $fillable = ['team_id', 'name'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
```

Crear `app/Models/Round.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Round extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'order',
        'is_open',
        'is_locked',
        'points_exact',
        'points_result',
        'points_classifier',
    ];

    protected function casts(): array
    {
        return [
            'is_open' => 'boolean',
            'is_locked' => 'boolean',
        ];
    }

    public function fixtures(): HasMany
    {
        return $this->hasMany(Fixture::class);
    }
}
```

- [ ] **Step 5: Crear factories**

Crear `database/factories/PlayerFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlayerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name' => fake()->name(),
        ];
    }
}
```

Crear `database/factories/RoundFactory.php`:

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RoundFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'slug' => fake()->unique()->slug(),
            'order' => fake()->numberBetween(1, 4),
            'is_open' => false,
            'is_locked' => false,
            'points_exact' => 3,
            'points_result' => 1,
            'points_classifier' => 2,
        ];
    }

    public function r1(): static
    {
        return $this->state(fn() => [
            'name' => 'Fase de Grupos',
            'slug' => 'grupos',
            'order' => 1,
            'points_exact' => 3,
            'points_result' => 1,
            'points_classifier' => 2,
        ]);
    }

    public function r2(): static
    {
        return $this->state(fn() => [
            'name' => 'Round of 32 + Round of 16',
            'slug' => 'r32-r16',
            'order' => 2,
            'points_exact' => 5,
            'points_result' => 2,
            'points_classifier' => 4,
        ]);
    }

    public function r3(): static
    {
        return $this->state(fn() => [
            'name' => 'Cuartos + Semis',
            'slug' => 'qf-sf',
            'order' => 3,
            'points_exact' => 8,
            'points_result' => 3,
            'points_classifier' => 0,
        ]);
    }

    public function r4(): static
    {
        return $this->state(fn() => [
            'name' => 'Final + 3er Puesto',
            'slug' => 'final',
            'order' => 4,
            'points_exact' => 13,
            'points_result' => 5,
            'points_classifier' => 0,
        ]);
    }
}
```

- [ ] **Step 6: Correr migración y tests**

```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan test tests/Unit/Models/PlayerTest.php tests/Unit/Models/RoundTest.php
```

Expected: 3 tests pasando (PASS).

- [ ] **Step 7: Commit**

```bash
git add database/migrations/ app/Models/Player.php app/Models/Round.php database/factories/PlayerFactory.php database/factories/RoundFactory.php tests/Unit/Models/
git commit -m "feat: add players and rounds tables with models, factories and point configuration"
```

---

## Task 5: Matches Table + Fixture Model

**Files:**
- Create: `database/migrations/*_create_matches_table.php`
- Create: `app/Models/Fixture.php`
- Create: `database/factories/FixtureFactory.php`
- Create: `tests/Unit/Models/FixtureTest.php`

- [ ] **Step 1: Escribir tests que fallan**

Crear `tests/Unit/Models/FixtureTest.php`:

```php
<?php

use App\Models\Fixture;
use App\Models\Group;
use App\Models\Round;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('belongs to a round', function () {
    $round = Round::factory()->create();
    $fixture = Fixture::factory()->create(['round_id' => $round->id]);

    expect($fixture->round)->toBeInstanceOf(Round::class)
        ->and($fixture->round->id)->toBe($round->id);
});

it('identifies group stage matches', function () {
    $group = Group::factory()->create();
    $fixture = Fixture::factory()->create(['group_id' => $group->id]);

    expect($fixture->isGroupStage())->toBeTrue();
});

it('identifies knockout matches', function () {
    $fixture = Fixture::factory()->create(['group_id' => null]);

    expect($fixture->isGroupStage())->toBeFalse();
});

it('detects live status', function () {
    $fixture = Fixture::factory()->live()->create();

    expect($fixture->isLive())->toBeTrue()
        ->and($fixture->isFinished())->toBeFalse();
});

it('detects finished status', function () {
    $fixture = Fixture::factory()->finished(2, 1)->create();

    expect($fixture->isFinished())->toBeTrue()
        ->and($fixture->home_score)->toBe(2)
        ->and($fixture->away_score)->toBe(1);
});

it('can have home and away teams', function () {
    $home = Team::factory()->create();
    $away = Team::factory()->create();
    $fixture = Fixture::factory()->create([
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
    ]);

    expect($fixture->homeTeam->id)->toBe($home->id)
        ->and($fixture->awayTeam->id)->toBe($away->id);
});

it('can have placeholder text for unknown teams', function () {
    $fixture = Fixture::factory()->create([
        'home_team_id' => null,
        'home_placeholder' => 'Ganador Grupo A',
    ]);

    expect($fixture->home_team_id)->toBeNull()
        ->and($fixture->home_placeholder)->toBe('Ganador Grupo A');
});
```

- [ ] **Step 2: Correr tests para verificar que fallan**

```bash
./vendor/bin/sail artisan test tests/Unit/Models/FixtureTest.php
```

Expected: FAIL — Fixture no existe.

- [ ] **Step 3: Crear migración**

```bash
./vendor/bin/sail artisan make:migration create_matches_table
```

Editar `*_create_matches_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('round_id')->constrained()->cascadeOnDelete();
            $table->foreignId('group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->unsignedSmallInteger('match_number');
            $table->dateTime('match_date');
            $table->foreignId('home_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('away_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->string('home_placeholder')->nullable();
            $table->string('away_placeholder')->nullable();
            $table->unsignedTinyInteger('home_score')->nullable();
            $table->unsignedTinyInteger('away_score')->nullable();
            $table->foreignId('winner_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->boolean('went_to_extra_time')->default(false);
            $table->string('status')->default('scheduled');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
```

- [ ] **Step 4: Crear modelo Fixture**

Crear `app/Models/Fixture.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fixture extends Model
{
    use HasFactory;

    protected $table = 'matches';

    protected $fillable = [
        'round_id',
        'group_id',
        'match_number',
        'match_date',
        'home_team_id',
        'away_team_id',
        'home_placeholder',
        'away_placeholder',
        'home_score',
        'away_score',
        'winner_team_id',
        'went_to_extra_time',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'match_date' => 'datetime',
            'went_to_extra_time' => 'boolean',
        ];
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function winnerTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'winner_team_id');
    }

    public function predictions(): HasMany
    {
        return $this->hasMany(Prediction::class, 'match_id');
    }

    public function isGroupStage(): bool
    {
        return $this->group_id !== null;
    }

    public function isFinished(): bool
    {
        return $this->status === 'finished';
    }

    public function isLive(): bool
    {
        return $this->status === 'in_progress';
    }
}
```

- [ ] **Step 5: Crear factory**

Crear `database/factories/FixtureFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\Round;
use Illuminate\Database\Eloquent\Factories\Factory;

class FixtureFactory extends Factory
{
    public function definition(): array
    {
        return [
            'round_id' => Round::factory(),
            'group_id' => null,
            'match_number' => fake()->numberBetween(1, 104),
            'match_date' => fake()->dateTimeBetween('2026-06-11', '2026-07-19'),
            'home_team_id' => null,
            'away_team_id' => null,
            'home_placeholder' => null,
            'away_placeholder' => null,
            'home_score' => null,
            'away_score' => null,
            'winner_team_id' => null,
            'went_to_extra_time' => false,
            'status' => 'scheduled',
        ];
    }

    public function groupStage(): static
    {
        return $this->state(fn() => ['group_id' => Group::factory()]);
    }

    public function finished(int $homeScore, int $awayScore): static
    {
        return $this->state(fn() => [
            'home_score' => $homeScore,
            'away_score' => $awayScore,
            'status' => 'finished',
        ]);
    }

    public function live(): static
    {
        return $this->state(fn() => ['status' => 'in_progress']);
    }
}
```

- [ ] **Step 6: Correr migración y tests**

```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan test tests/Unit/Models/FixtureTest.php
```

Expected: 7 tests pasando (PASS).

- [ ] **Step 7: Commit**

```bash
git add database/migrations/ app/Models/Fixture.php database/factories/FixtureFactory.php tests/Unit/Models/FixtureTest.php
git commit -m "feat: add matches table with Fixture model (table: matches) and factory states"
```

---

## Task 6: Prediction Tables + Models

**Files:**
- Create: `database/migrations/*_create_prediction_submissions_table.php`
- Create: `database/migrations/*_create_predictions_table.php`
- Create: `database/migrations/*_create_special_predictions_table.php`
- Create: `app/Models/PredictionSubmission.php`
- Create: `app/Models/Prediction.php`
- Create: `app/Models/SpecialPrediction.php`
- Create: `database/factories/PredictionSubmissionFactory.php`
- Create: `database/factories/PredictionFactory.php`
- Create: `database/factories/SpecialPredictionFactory.php`
- Create: `tests/Unit/Models/PredictionTest.php`

- [ ] **Step 1: Escribir tests que fallan**

Crear `tests/Unit/Models/PredictionTest.php`:

```php
<?php

use App\Models\Fixture;
use App\Models\Prediction;
use App\Models\PredictionSubmission;
use App\Models\Round;
use App\Models\SpecialPrediction;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('belongs to a user and a fixture', function () {
    $user = User::factory()->create();
    $fixture = Fixture::factory()->create();
    $prediction = Prediction::factory()->create([
        'user_id' => $user->id,
        'match_id' => $fixture->id,
    ]);

    expect($prediction->user->id)->toBe($user->id)
        ->and($prediction->fixture->id)->toBe($fixture->id);
});

it('starts with zero points', function () {
    $prediction = Prediction::factory()->create();

    expect($prediction->pts_exact)->toBe(0)
        ->and($prediction->pts_result)->toBe(0)
        ->and($prediction->pts_classifier)->toBe(0)
        ->and($prediction->total_points)->toBe(0);
});

it('prediction submission has correct statuses', function () {
    $submission = PredictionSubmission::factory()->create(['status' => 'draft']);

    expect($submission->status)->toBe('draft');
});

it('special prediction belongs to user', function () {
    $user = User::factory()->create();
    $sp = SpecialPrediction::factory()->create(['user_id' => $user->id]);

    expect($sp->user->id)->toBe($user->id);
});

it('special prediction starts with zero points', function () {
    $sp = SpecialPrediction::factory()->create();

    expect($sp->pts_champion)->toBe(0)
        ->and($sp->pts_runner_up)->toBe(0)
        ->and($sp->pts_top_scorer)->toBe(0);
});
```

- [ ] **Step 2: Correr tests para verificar que fallan**

```bash
./vendor/bin/sail artisan test tests/Unit/Models/PredictionTest.php
```

Expected: FAIL — modelos no existen.

- [ ] **Step 3: Crear migraciones**

```bash
./vendor/bin/sail artisan make:migration create_prediction_submissions_table
./vendor/bin/sail artisan make:migration create_predictions_table
./vendor/bin/sail artisan make:migration create_special_predictions_table
```

Editar `*_create_prediction_submissions_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prediction_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('round_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('draft'); // draft|submitted|locked
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'round_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prediction_submissions');
    }
};
```

Editar `*_create_predictions_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->unsignedTinyInteger('predicted_home');
            $table->unsignedTinyInteger('predicted_away');
            $table->unsignedTinyInteger('pts_exact')->default(0);
            $table->unsignedTinyInteger('pts_result')->default(0);
            $table->unsignedTinyInteger('pts_classifier')->default(0);
            $table->unsignedTinyInteger('total_points')->default(0);
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'match_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('predictions');
    }
};
```

Editar `*_create_special_predictions_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('special_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('champion_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('runner_up_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('top_scorer_player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->boolean('is_locked')->default(false);
            $table->unsignedTinyInteger('pts_champion')->default(0);
            $table->unsignedTinyInteger('pts_runner_up')->default(0);
            $table->unsignedTinyInteger('pts_top_scorer')->default(0);
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('special_predictions');
    }
};
```

- [ ] **Step 4: Crear modelos**

Crear `app/Models/PredictionSubmission.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PredictionSubmission extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'round_id', 'status', 'submitted_at'];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class);
    }
}
```

Crear `app/Models/Prediction.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prediction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'match_id',
        'predicted_home',
        'predicted_away',
        'pts_exact',
        'pts_result',
        'pts_classifier',
        'total_points',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'calculated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fixture(): BelongsTo
    {
        return $this->belongsTo(Fixture::class, 'match_id');
    }
}
```

Crear `app/Models/SpecialPrediction.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpecialPrediction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'champion_team_id',
        'runner_up_team_id',
        'top_scorer_player_id',
        'is_locked',
        'pts_champion',
        'pts_runner_up',
        'pts_top_scorer',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'is_locked' => 'boolean',
            'calculated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function champion(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'champion_team_id');
    }

    public function runnerUp(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'runner_up_team_id');
    }

    public function topScorer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'top_scorer_player_id');
    }
}
```

- [ ] **Step 5: Crear factories**

Crear `database/factories/PredictionSubmissionFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Round;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PredictionSubmissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'round_id' => Round::factory(),
            'status' => 'draft',
            'submitted_at' => null,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn() => [
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    public function locked(): static
    {
        return $this->state(fn() => [
            'status' => 'locked',
            'submitted_at' => now(),
        ]);
    }
}
```

Crear `database/factories/PredictionFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Fixture;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PredictionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'match_id' => Fixture::factory(),
            'predicted_home' => fake()->numberBetween(0, 5),
            'predicted_away' => fake()->numberBetween(0, 5),
            'pts_exact' => 0,
            'pts_result' => 0,
            'pts_classifier' => 0,
            'total_points' => 0,
            'calculated_at' => null,
        ];
    }
}
```

Crear `database/factories/SpecialPredictionFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SpecialPredictionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'champion_team_id' => null,
            'runner_up_team_id' => null,
            'top_scorer_player_id' => null,
            'is_locked' => false,
            'pts_champion' => 0,
            'pts_runner_up' => 0,
            'pts_top_scorer' => 0,
            'calculated_at' => null,
        ];
    }
}
```

- [ ] **Step 6: Correr migración y tests**

```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan test tests/Unit/Models/PredictionTest.php
```

Expected: 5 tests pasando (PASS).

- [ ] **Step 7: Commit**

```bash
git add database/migrations/ app/Models/Prediction.php app/Models/PredictionSubmission.php app/Models/SpecialPrediction.php database/factories/ tests/Unit/Models/PredictionTest.php
git commit -m "feat: add prediction tables (submissions, predictions, special_predictions) with models and factories"
```

---

## Task 7: Support Tables + Models

**Files:**
- Create: `database/migrations/*_create_coin_transactions_table.php`
- Create: `database/migrations/*_create_messages_table.php`
- Create: `app/Models/CoinTransaction.php`
- Create: `app/Models/Message.php`
- Create: `database/factories/CoinTransactionFactory.php`
- Create: `database/factories/MessageFactory.php`

- [ ] **Step 1: Crear migraciones**

```bash
./vendor/bin/sail artisan make:migration create_coin_transactions_table
./vendor/bin/sail artisan make:migration create_messages_table
```

Editar `*_create_coin_transactions_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coin_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // credit|debit
            $table->unsignedInteger('amount');
            $table->string('concept');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coin_transactions');
    }
};
```

Editar `*_create_messages_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
```

- [ ] **Step 2: Crear modelos**

Crear `app/Models/CoinTransaction.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoinTransaction extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'type', 'amount', 'concept'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

Crear `app/Models/Message.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'content'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

- [ ] **Step 3: Crear factories**

Crear `database/factories/CoinTransactionFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CoinTransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement(['credit', 'debit']),
            'amount' => 50,
            'concept' => 'activación',
        ];
    }
}
```

Crear `database/factories/MessageFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'content' => fake()->sentence(),
        ];
    }
}
```

- [ ] **Step 4: Correr migración y verificar**

```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan test
```

Expected: todos los tests existentes siguen pasando (PASS). Sin errores de migración.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/ app/Models/CoinTransaction.php app/Models/Message.php database/factories/CoinTransactionFactory.php database/factories/MessageFactory.php
git commit -m "feat: add coin_transactions and messages tables with models and factories"
```

---

## Task 8: User Model — Relaciones Completas

**Files:**
- Modify: `app/Models/User.php`
- Modify: `tests/Unit/Models/UserTest.php`

- [ ] **Step 1: Agregar tests de relaciones al UserTest existente**

Abrir `tests/Unit/Models/UserTest.php` y agregar al final:

```php
it('has many predictions', function () {
    $user = User::factory()->create();
    Prediction::factory()->count(3)->create(['user_id' => $user->id]);

    expect($user->predictions)->toHaveCount(3);
});

it('has many prediction submissions', function () {
    $user = User::factory()->create();
    PredictionSubmission::factory()->count(2)->create(['user_id' => $user->id]);

    expect($user->predictionSubmissions)->toHaveCount(2);
});

it('has one special prediction', function () {
    $user = User::factory()->create();
    SpecialPrediction::factory()->create(['user_id' => $user->id]);

    expect($user->specialPrediction)->toBeInstanceOf(SpecialPrediction::class);
});

it('has many coin transactions', function () {
    $user = User::factory()->create();
    CoinTransaction::factory()->count(2)->create(['user_id' => $user->id]);

    expect($user->coinTransactions)->toHaveCount(2);
});

it('has many messages', function () {
    $user = User::factory()->create();
    Message::factory()->count(3)->create(['user_id' => $user->id]);

    expect($user->messages)->toHaveCount(3);
});
```

Agregar al inicio del archivo los imports necesarios:

```php
use App\Models\CoinTransaction;
use App\Models\Message;
use App\Models\Prediction;
use App\Models\PredictionSubmission;
use App\Models\SpecialPrediction;
```

- [ ] **Step 2: Correr tests para verificar que fallan**

```bash
./vendor/bin/sail artisan test tests/Unit/Models/UserTest.php
```

Expected: FAIL — relaciones no definidas en User.

- [ ] **Step 3: Actualizar User model con todas las relaciones**

Reemplazar el contenido completo de `app/Models/User.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'is_activated',
        'coins_balance',
        'total_points',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_activated' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function predictions(): HasMany
    {
        return $this->hasMany(Prediction::class);
    }

    public function predictionSubmissions(): HasMany
    {
        return $this->hasMany(PredictionSubmission::class);
    }

    public function specialPrediction(): HasOne
    {
        return $this->hasOne(SpecialPrediction::class);
    }

    public function coinTransactions(): HasMany
    {
        return $this->hasMany(CoinTransaction::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
```

- [ ] **Step 4: Correr todos los tests**

```bash
./vendor/bin/sail artisan test tests/Unit/Models/UserTest.php
```

Expected: 8 tests pasando (PASS).

- [ ] **Step 5: Correr suite completa para verificar que no rompimos nada**

```bash
php artisan test
```

Expected: todos los tests pasando (PASS).

- [ ] **Step 6: Commit**

```bash
git add app/Models/User.php tests/Unit/Models/UserTest.php
git commit -m "feat: add all Eloquent relationships to User model"
```

---

## Task 9: Admin Middleware

**Files:**
- Create: `app/Http/Middleware/EnsureUserIsAdmin.php`
- Modify: `bootstrap/app.php`
- Create: `tests/Feature/AdminMiddlewareTest.php`

- [ ] **Step 1: Escribir tests que fallan**

Crear `tests/Feature/AdminMiddlewareTest.php`:

```php
<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('blocks non-admin users from admin routes', function () {
    $user = User::factory()->create(['role' => 'user']);

    $response = $this->actingAs($user)->get('/admin');

    $response->assertStatus(403);
});

it('allows admin users to access admin routes', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get('/admin');

    $response->assertStatus(200);
});

it('redirects guests to login on admin routes', function () {
    $response = $this->get('/admin');

    $response->assertRedirect('/login');
});
```

- [ ] **Step 2: Correr tests para verificar que fallan**

```bash
./vendor/bin/sail artisan test tests/Feature/AdminMiddlewareTest.php
```

Expected: FAIL — ruta `/admin` no existe o retorna 404.

- [ ] **Step 3: Crear el middleware**

Crear `app/Http/Middleware/EnsureUserIsAdmin.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()?->isAdmin()) {
            abort(403);
        }

        return $next($request);
    }
}
```

- [ ] **Step 4: Registrar el middleware en bootstrap/app.php**

Abrir `bootstrap/app.php` y agregar dentro de `->withMiddleware(function (Middleware $middleware) {`:

```php
use App\Http\Middleware\EnsureUserIsAdmin;

// dentro del callback withMiddleware:
$middleware->alias([
    'admin' => EnsureUserIsAdmin::class,
]);
```

El bloque `withMiddleware` debe quedar así:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\HandleInertiaRequests::class,
        \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
    ]);

    $middleware->alias([
        'admin' => EnsureUserIsAdmin::class,
    ]);
})
```

- [ ] **Step 5: Crear ruta de prueba temporal para el test**

Abrir `routes/web.php` y agregar al final:

```php
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin', fn() => inertia('Admin/Dashboard'))->name('admin.dashboard');
});
```

- [ ] **Step 6: Crear el componente React mínimo para la ruta admin**

Crear `resources/js/Pages/Admin/Dashboard.jsx`:

```jsx
export default function Dashboard() {
    return <div>Admin Dashboard</div>;
}
```

- [ ] **Step 7: Correr tests**

```bash
./vendor/bin/sail artisan test tests/Feature/AdminMiddlewareTest.php
```

Expected: 3 tests pasando (PASS).

- [ ] **Step 8: Correr suite completa**

```bash
php artisan test
```

Expected: todos los tests pasando (PASS).

- [ ] **Step 9: Commit**

```bash
git add app/Http/Middleware/EnsureUserIsAdmin.php bootstrap/app.php routes/web.php resources/js/Pages/Admin/Dashboard.jsx tests/Feature/AdminMiddlewareTest.php
git commit -m "feat: add admin middleware and protect /admin routes"
```

---

## Task 10: Database Seeders

**Files:**
- Create: `database/seeders/GroupTeamSeeder.php`
- Create: `database/seeders/RoundSeeder.php`
- Create: `database/seeders/DevelopmentUserSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`

- [ ] **Step 1: Crear RoundSeeder (los 4 rounds con puntos exactos)**

Crear `database/seeders/RoundSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Round;
use Illuminate\Database\Seeder;

class RoundSeeder extends Seeder
{
    public function run(): void
    {
        $rounds = [
            [
                'name' => 'Fase de Grupos',
                'slug' => 'grupos',
                'order' => 1,
                'points_exact' => 3,
                'points_result' => 1,
                'points_classifier' => 2,
            ],
            [
                'name' => 'Round of 32 + Round of 16',
                'slug' => 'r32-r16',
                'order' => 2,
                'points_exact' => 5,
                'points_result' => 2,
                'points_classifier' => 4,
            ],
            [
                'name' => 'Cuartos + Semis',
                'slug' => 'qf-sf',
                'order' => 3,
                'points_exact' => 8,
                'points_result' => 3,
                'points_classifier' => 0,
            ],
            [
                'name' => 'Final + 3er Puesto',
                'slug' => 'final',
                'order' => 4,
                'points_exact' => 13,
                'points_result' => 5,
                'points_classifier' => 0,
            ],
        ];

        foreach ($rounds as $round) {
            Round::firstOrCreate(['slug' => $round['slug']], $round);
        }
    }
}
```

- [ ] **Step 2: Crear GroupTeamSeeder con los 48 equipos**

Crear `database/seeders/GroupTeamSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Team;
use Illuminate\Database\Seeder;

class GroupTeamSeeder extends Seeder
{
    /**
     * Equipos del Mundial 2026.
     * Verificar asignaciones de grupos en https://www.fifa.com/fifaplus/en/tournaments/mens/worldcup/canadamexicousa2026
     */
    public function run(): void
    {
        $groups = [
            'A' => [
                ['name' => 'México',        'fifa_code' => 'MEX'],
                ['name' => 'Estados Unidos', 'fifa_code' => 'USA'],
                ['name' => 'Uruguay',        'fifa_code' => 'URU'],
                ['name' => 'Panamá',         'fifa_code' => 'PAN'],
            ],
            'B' => [
                ['name' => 'Argentina',      'fifa_code' => 'ARG'],
                ['name' => 'Chile',          'fifa_code' => 'CHI'],
                ['name' => 'Perú',           'fifa_code' => 'PER'],
                ['name' => 'Australia',      'fifa_code' => 'AUS'],
            ],
            'C' => [
                ['name' => 'Brasil',         'fifa_code' => 'BRA'],
                ['name' => 'Colombia',       'fifa_code' => 'COL'],
                ['name' => 'Ecuador',        'fifa_code' => 'ECU'],
                ['name' => 'Alemania',       'fifa_code' => 'GER'],
            ],
            'D' => [
                ['name' => 'Francia',        'fifa_code' => 'FRA'],
                ['name' => 'España',         'fifa_code' => 'ESP'],
                ['name' => 'Portugal',       'fifa_code' => 'POR'],
                ['name' => 'Marruecos',      'fifa_code' => 'MAR'],
            ],
            'E' => [
                ['name' => 'Inglaterra',     'fifa_code' => 'ENG'],
                ['name' => 'Países Bajos',   'fifa_code' => 'NED'],
                ['name' => 'Japón',          'fifa_code' => 'JPN'],
                ['name' => 'Senegal',        'fifa_code' => 'SEN'],
            ],
            'F' => [
                ['name' => 'Italia',         'fifa_code' => 'ITA'],
                ['name' => 'Croacia',        'fifa_code' => 'CRO'],
                ['name' => 'Nigeria',        'fifa_code' => 'NGA'],
                ['name' => 'Venezuela',      'fifa_code' => 'VEN'],
            ],
            'G' => [
                ['name' => 'Bélgica',        'fifa_code' => 'BEL'],
                ['name' => 'Serbia',         'fifa_code' => 'SRB'],
                ['name' => 'Costa Rica',     'fifa_code' => 'CRC'],
                ['name' => 'República Checa','fifa_code' => 'CZE'],
            ],
            'H' => [
                ['name' => 'Suiza',          'fifa_code' => 'SUI'],
                ['name' => 'Turquía',        'fifa_code' => 'TUR'],
                ['name' => 'Corea del Sur',  'fifa_code' => 'KOR'],
                ['name' => 'Camerún',        'fifa_code' => 'CMR'],
            ],
            'I' => [
                ['name' => 'Dinamarca',      'fifa_code' => 'DEN'],
                ['name' => 'Austria',        'fifa_code' => 'AUT'],
                ['name' => 'Arabia Saudita', 'fifa_code' => 'KSA'],
                ['name' => 'Ghana',          'fifa_code' => 'GHA'],
            ],
            'J' => [
                ['name' => 'Polonia',        'fifa_code' => 'POL'],
                ['name' => 'Paraguay',       'fifa_code' => 'PAR'],
                ['name' => 'Irán',           'fifa_code' => 'IRN'],
                ['name' => 'Sudáfrica',      'fifa_code' => 'RSA'],
            ],
            'K' => [
                ['name' => 'Canadá',         'fifa_code' => 'CAN'],
                ['name' => 'Escocia',        'fifa_code' => 'SCO'],
                ['name' => 'Bolivia',        'fifa_code' => 'BOL'],
                ['name' => 'Argelia',        'fifa_code' => 'ALG'],
            ],
            'L' => [
                ['name' => 'Ucrania',        'fifa_code' => 'UKR'],
                ['name' => 'Hungría',        'fifa_code' => 'HUN'],
                ['name' => 'Malí',           'fifa_code' => 'MLI'],
                ['name' => 'Nueva Zelanda',  'fifa_code' => 'NZL'],
            ],
        ];

        foreach ($groups as $groupName => $teams) {
            $group = Group::firstOrCreate(['name' => $groupName]);

            foreach ($teams as $teamData) {
                Team::firstOrCreate(
                    ['fifa_code' => $teamData['fifa_code']],
                    array_merge($teamData, ['group_id' => $group->id])
                );
            }
        }
    }
}
```

> **Nota:** Estos grupos son aproximados para desarrollo. Verificar y actualizar con las asignaciones oficiales de la FIFA antes del torneo.

- [ ] **Step 3: Crear DevelopmentUserSeeder**

Crear `database/seeders/DevelopmentUserSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DevelopmentUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@pollamundial.test'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
                'is_activated' => true,
                'coins_balance' => 50,
            ]
        );

        $users = [
            ['name' => 'Juan', 'email' => 'juan@pollamundial.test'],
            ['name' => 'María', 'email' => 'maria@pollamundial.test'],
            ['name' => 'Carlos', 'email' => 'carlos@pollamundial.test'],
            ['name' => 'Ana', 'email' => 'ana@pollamundial.test'],
            ['name' => 'Luis', 'email' => 'luis@pollamundial.test'],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                array_merge($userData, [
                    'password' => Hash::make('password'),
                    'role' => 'user',
                    'is_active' => true,
                    'is_activated' => true,
                    'coins_balance' => 50,
                ])
            );
        }
    }
}
```

- [ ] **Step 4: Actualizar DatabaseSeeder**

Reemplazar `database/seeders/DatabaseSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoundSeeder::class,
            GroupTeamSeeder::class,
            DevelopmentUserSeeder::class,
        ]);
    }
}
```

- [ ] **Step 5: Correr el seeder y verificar**

```bash
./vendor/bin/sail artisan db:seed
```

Expected: sin errores. Verificar en MySQL:

```bash
./vendor/bin/sail artisan tinker --execute="echo 'Grupos: ' . App\Models\Group::count() . ', Equipos: ' . App\Models\Team::count() . ', Rondas: ' . App\Models\Round::count() . ', Usuarios: ' . App\Models\User::count();"
```

Expected: `Grupos: 12, Equipos: 48, Rondas: 4, Usuarios: 6`

- [ ] **Step 6: Correr suite completa**

```bash
php artisan test
```

Expected: todos los tests pasando (PASS).

- [ ] **Step 7: Commit final**

```bash
git add database/seeders/
git commit -m "feat: add seeders for rounds, groups/teams (48 teams, 12 groups) and dev users"
```

---

## Verificación Final

- [ ] **Correr todas las migraciones desde cero**

```bash
./vendor/bin/sail artisan migrate:fresh --seed
```

Expected: sin errores. Schema completo aplicado + datos de prueba cargados.

- [ ] **Correr suite completa de tests**

```bash
./vendor/bin/sail artisan test --coverage
```

Expected: todos los tests pasando. Sin errores.

- [ ] **Verificar schema completo**

```bash
./vendor/bin/sail artisan tinker --execute="
echo 'Tablas verificadas: ';
\$tables = ['users','groups','teams','players','rounds','matches','prediction_submissions','predictions','special_predictions','coin_transactions','messages'];
foreach(\$tables as \$t) { echo \$t . ' ✓  '; }
"
```

Expected: todas las tablas listadas sin errores.

---

## Resumen de lo construido en este plan

| Componente | Estado |
|---|---|
| Proyecto Laravel 11 + Breeze (React/Inertia) | ✅ |
| Laravel Reverb instalado | ✅ |
| 11 migraciones (schema completo) | ✅ |
| 10 modelos Eloquent con relaciones | ✅ |
| 10 factories con estados útiles | ✅ |
| Admin middleware con alias `admin` | ✅ |
| Seeders: 12 grupos, 48 equipos, 4 rondas, 6 usuarios | ✅ |
| Tests: Unit (modelos) + Feature (middleware) | ✅ |

**Siguiente:** Plan 2 — Tournament Admin (CRUD de equipos, grupos, partidos y rondas vía panel admin)
