# PollaMundial — CLAUDE.md

Quinela web para el Mundial FIFA 2026. Laravel 11 + React + MySQL + Reverb.

---

## Entorno de desarrollo

**Docker Desktop + Laravel Sail.** No hay PHP, Composer ni MySQL instalados localmente en WSL2.

Docker Desktop debe estar corriendo antes de usar cualquier comando Sail.

```bash
./vendor/bin/sail up -d        # Levantar contenedores en background
./vendor/bin/sail down         # Bajar contenedores
./vendor/bin/sail shell        # Shell dentro del contenedor PHP
```

---

## Comandos frecuentes

### PHP / Laravel

```bash
./vendor/bin/sail artisan migrate                        # Correr migraciones
./vendor/bin/sail artisan migrate:fresh --seed           # Reset BD + seeders
./vendor/bin/sail artisan db:seed                        # Solo seeders
./vendor/bin/sail artisan make:model Foo -mf             # Modelo + migración + factory
./vendor/bin/sail artisan make:migration create_x_table
./vendor/bin/sail artisan make:event FooEvent
./vendor/bin/sail artisan make:listener FooListener
./vendor/bin/sail artisan make:middleware FooMiddleware
./vendor/bin/sail artisan tinker
./vendor/bin/sail artisan points:recalculate --round=1   # Recalcular puntos (futuro)
```

### Testing

```bash
./vendor/bin/sail test                          # Todos los tests
./vendor/bin/sail test --filter NombreTest      # Un test específico
./vendor/bin/sail test tests/Unit/              # Solo unit tests
./vendor/bin/sail test tests/Feature/           # Solo feature tests
```

Framework de tests: **Pest v3** (no PHPUnit directamente).
Los tests de Feature que renderizan Inertia/React necesitan `$this->withoutVite()`.

### JavaScript / Frontend

```bash
./vendor/bin/sail pnpm install     # Instalar dependencias
./vendor/bin/sail pnpm run dev     # Vite dev server (hot reload)
./vendor/bin/sail pnpm run build   # Build producción
./vendor/bin/sail pnpm add foo     # Agregar paquete
```

**Siempre pnpm, nunca npm.**

### Composer

```bash
./vendor/bin/sail composer require foo/bar
./vendor/bin/sail composer require foo/bar --dev
```

---

## Stack técnico

| Capa | Tecnología |
|---|---|
| Backend | Laravel 11 |
| Frontend | React 18 + Inertia.js v2 |
| Auth scaffold | Laravel Breeze (React/Inertia) |
| Base de datos | MySQL 8.4 (contenedor Sail) |
| Real-time | Laravel Reverb (WebSockets) |
| JS package manager | pnpm |
| Tests | Pest v3 |
| Dev environment | Docker Desktop + Laravel Sail |

---

## Convenciones clave

### Modelo Fixture (tabla `matches`)

El modelo para partidos se llama `Fixture` (no `Match`) porque `match` es palabra reservada en PHP 8.

```php
// app/Models/Fixture.php
protected $table = 'matches';
```

Las FK hacia esta tabla deben declararse explícitamente:

```php
// En migrations:
$table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();

// En Prediction::fixture():
return $this->belongsTo(Fixture::class, 'match_id');

// En Fixture::predictions():
return $this->hasMany(Prediction::class, 'match_id');
```

### Registro de middleware (Laravel 11)

No existe `Http/Kernel.php`. Los aliases se registran en `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
    ]);
})
```

### Usuarios: is_active vs is_activated

- `is_active` → puede iniciar sesión (acceso al sistema)
- `is_activated` → aportó 50 coins al pozo del torneo

### Rondas: puntos por ronda

| Campo | R1 | R2 | R3 | R4 |
|---|---|---|---|---|
| points_exact | 3 | 5 | 8 | 13 |
| points_result | 1 | 2 | 3 | 5 |
| points_classifier | 2 | 4 | 0 | 0 |

Los puntos de clasificados solo aplican en R1 y R2.

### Partidos de eliminación (knockout scoring)

- `home_score` / `away_score` = marcador a 90 minutos siempre
- `winner_team_id` = ganador real (puede diferir si hubo ET/penales)
- Exacto se compara contra score de 90 min
- Resultado correcto = acertar el `winner_team_id`

### Seeders — idempotencia

Todos los seeders usan `firstOrCreate` sobre clave natural para poder ejecutarse múltiples veces sin duplicar.

---

## Estructura de la BD (resumen)

```
users           — role(admin|user), is_active, is_activated, coins_balance, total_points
groups          — name (A-L)
teams           — group_id, name, fifa_code, flag_url
players         — team_id, name  (para predicción de goleador)
rounds          — name, slug, order, is_open, is_locked, points_exact/result/classifier
matches         — round_id, group_id?, home/away_team_id?, winner_team_id?, scores, status
predictions     — user_id, match_id, predicted_home/away, pts_exact/result/classifier
prediction_submissions — user_id, round_id, status(draft|submitted|locked)
special_predictions    — user_id, champion/runner_up/top_scorer FKs, pts_*
coin_transactions      — user_id, type(credit|debit), amount, concept
messages               — user_id, content
```

---

## Canales Reverb (real-time)

```
presence-quinela       → ranking, live scores, chat, alertas globales
private-user.{userId}  → puntos del usuario, bloqueo de predicción, ronda abierta
```

---

## Arquitectura de puntos (event-driven)

```
Admin actualiza score → MatchScoreUpdated
  → CalculateMatchPoints (listener)
    → actualiza predictions.pts_*
    → recalcula users.total_points
    → dispara PointsUpdated → broadcast ranking

Admin finaliza ronda → RoundFinalized
  → CalculateClassifierPoints

Admin cierra torneo → TournamentFinalized
  → CalculateSpecialPredictions
```

Comando de corrección: `./vendor/bin/sail artisan points:recalculate {--round=} {--match=}`

---

## Progreso del proyecto

### Plan 1: Foundation & Data Layer — COMPLETADO ✓

Commit final: `7e45279`

Incluye: scaffold Laravel, entorno Sail, todos los modelos y migraciones, factories, middleware admin, seeders completos (12 grupos, 48 equipos, 4 rondas, usuarios dev).

Tests: 59 pasando.

### Plan 2: Tournament Admin — COMPLETADO ✓

Commit final: `891cc8f`

Incluye: 6 controladores admin (Dashboard, Round, Team, Fixture, Player, User), AdminLayout + páginas React para todas las secciones, CRUD completo de rondas/equipos/partidos/jugadores/usuarios, 35 feature tests.

Tests: 94 pasando.

### Plan 3: Predictions Engine — COMPLETADO ✓

Commit final: `fd69d78`

Incluye: PredictionController (index/show/save/submit), SpecialPredictionController (show/save), páginas React Predictions/Index + Round + Special, rutas bajo `/predictions`, 18 feature tests.

Tests: 112 pasando.

### Plan 4: Points Engine — COMPLETADO ✓

Commit final: `6ff13e1`

Incluye: CalculateMatchPoints + CalculateClassifierPoints (R1 group stage + 8 best thirds + R2 R16) + CalculateSpecialPredictions listeners, TournamentController + Tournament.jsx, dispatch desde FixtureController y RoundController, Artisan command `points:recalculate --match= --round=`, integer casts en SpecialPrediction FKs.

Tests: 145 pasando.

### Plan 5: Real-time & Chat — COMPLETADO ✓

Commit final: `9e64bd6`

Incluye: `routes/channels.php` (presence-quinela + private-user), 6 broadcast events (LiveScoreUpdated, PointsUpdated, RoundOpened, RoundLocked, MessageSent, ExactScoreAlert), ChatController (GET/POST), RankingController, Chat.jsx + Ranking.jsx.

Tests: 175 pasando.

### Paso 3: Biblioteca de Íconos SVG — COMPLETADO ✓

Commit final: `9f27c9c`

Incluye: 12 íconos de fútbol en `resources/js/Components/icons/football/` (Trophy, SoccerBall, Jersey, Boot, Whistle, Stadium, GoalNet, Mark26, HostStrip, FlagSmall, Pennant, PitchSwoosh), barrel export en `index.js`, 4 íconos de NavBar en `NavIcons.jsx` (NavStadium, NavVS, NavTrophy, NavFire con prop `active`). Todos usan CSS variables como defaults.

### Paso 4: Componentes Compuestos — COMPLETADO ✓

Commit final: `ea10615`

12 componentes presentacionales en `resources/js/Components/composed/`: ScoreBox, StatCard, PtsBadge, BetCard, MatchPredRow, MatchCard, TabBar, PodiumStep, RankRow, PozoCard, ChatBubble, GroupStandingCard.

### Paso 5: Screens/Views — COMPLETADO ✓

Commit final: `a5c2e2f`

8 pantallas completas con design system pop-art. Commits principales: `a8b56d4` (MobileShell+TabBar), `3a10ecb` (MatchesController), `76b35aa` (RankingController), `67fd79b` (Chat), `5397615` (Splash+ruta), `efc9453` (Login), `2116eb0` (Ranking), `bf7872b` (Locked), `493455f` (Round+cleanup).

Incluye:
- `MobileShell.jsx` — shell compartido reemplazando AuthenticatedLayout
- `TabBar.jsx` — navegación Inertia con `router.visit()`
- `MatchesController` + ruta `/matches` + feature tests
- `RankingController` actualizado: dense rank, avatarColor, pozo
- `ChatController` actualizado: liveMatch prop
- `PredictionController` actualizado: redirect a Locked cuando `!is_open`
- Páginas nuevas: `Splash.jsx`, `Matches.jsx`, `Predictions/Locked.jsx`
- Páginas refactorizadas: `Home.jsx` (ya estaba), `Ranking.jsx`, `Chat.jsx`, `Auth/Login.jsx`, `Predictions/Round.jsx`
- Ruta `/` → Splash para guests, redirect dashboard para autenticados

Tests: 207 pasando.

### Plan 6a: User Predictions UI — COMPLETADO ✓

Commit final: `0415265`

Incluye: auditoría del motor de puntos (4 bugs identificados/resueltos), nuevo Predictions/Index con MobileShell + PhaseCard + TournamentProgress, nueva pantalla Predictions/Receipt (comprobante por fase), correcciones de scoring hardcodeado en Round.jsx + HowTo.jsx + Rules.jsx.

Tests: 217 pasando (217 total, todos deprecated por PDO::MYSQL_ATTR_SSL_CA — warning del entorno, no fallos reales).

**Bugs del motor de puntos resueltos en esta sesión:**
- `predictions.pts_classifier` era columna muerta → eliminada por migración
- `CalculateClassifierPoints` R2 usaba `slice(16)` frágil → reemplazado con `whereBetween('match_number', [89, 96])`
- Validación de empates en knockout solo existía en `submit()` → agregada también a `save()`
- `MatchesController` sumaba `pts_classifier` muerto → corregido a `pts_exact + pts_result`

**Componentes nuevos:**
- `Components/ui/PtsChip.jsx` — chip de puntos coloreado por tipo
- `Components/composed/ReceiptMatchRow.jsx` — fila partido en comprobante
- `Components/composed/TournamentProgress.jsx` — barra 4 nodos del torneo
- `Components/composed/PhaseCard.jsx` — card de fase con 6 estados (upcoming/open/draft/submitted/locked/finalized)

**Páginas nuevas/reescritas:**
- `Predictions/Index.jsx` — reescrito con MobileShell + PhaseCard + TournamentProgress
- `Predictions/Receipt.jsx` — nueva pantalla comprobante: predicción vs resultado + chips de puntos

**Ruta nueva:**
- `GET /predictions/{round}/receipt` → `predictions.receipt`

**PredictionController actualizado:**
- `index()` incluye `phasePts` (desglose pts_exact/result/classifier/total por ronda) y `fixtures_count`
- `receipt()` nueva acción: carga fixtures + predictions + submission + isFinalized

### Planes pendientes

| Plan | Alcance | Estado |
|---|---|---|
| Plan 6b | Admin Panel UI (React frontend admin) | Pendiente |
| Plan 7 | User Frontend: mejoras avanzadas (especiales, streaks) | Pendiente |

---

## Archivos de referencia

- Diseño del sistema: `docs/superpowers/specs/2026-05-23-pollamundial-design.md`
- Plan 1: `docs/superpowers/plans/2026-05-23-plan-1-foundation.md`
- Plan 2: `docs/superpowers/plans/2026-05-24-plan-2-tournament-admin.md`
- Plan 3: `docs/superpowers/plans/2026-05-24-plan-3-predictions-engine.md`
- Plan 4: `docs/superpowers/plans/2026-05-24-plan-4-points-engine.md`
- Spec íconos (Paso 3): `docs/superpowers/specs/2026-05-25-icons-design.md`
- Spec compuestos (Paso 4): `docs/superpowers/specs/2026-05-25-composites-design.md`
- Plan compuestos (Paso 4): `docs/superpowers/plans/2026-05-25-plan-composites.md`
- Auditoría motor de puntos: `docs/superpowers/audits/2026-05-27-points-engine-audit.md`
- Spec User Predictions UI: `docs/superpowers/specs/2026-05-27-user-predictions-ui-design.md`
- Plan User Predictions UI: `docs/superpowers/plans/2026-05-27-user-predictions-ui.md`
- Design handoff: `/mnt/c/Users/dwndz/OneDrive/Escritorio/Mundial de parche_/design_handoff_mundial_parche/`
  - `README.md` — sistema de diseño completo, 20 pantallas, lista de componentes
  - `football-graphics.jsx` — 12 íconos SVG de fútbol
  - `bits.jsx` — TabBar, PtsBadge, HalftoneCorner, KasBadge
  - `screen-home.jsx`, `screen-ranking.jsx`, `screen-matches.jsx`, `screen-predict.jsx`, `screen-chat.jsx`
