# Home Screen — Spec (Paso futuro)

**Pantalla:** `ScreenHomeA` del handoff  
**Ruta destino:** `/` o `/home` (actualmente `/dashboard` es placeholder)  
**Controlador a crear:** `HomeController`

---

## Datos que necesita la pantalla

### 1. Usuario autenticado
- `name` — para saludo "QUÉ MÁS, {name}"
- `total_points` — para PtsBadge
- `position` — posición en ranking (ORDER BY total_points DESC, igual que RankingController)
- `is_activated` — para badge "✓ ENTRADA 50K PAGA"
- `avatar_color` — determinístico: `['yel','teal','red','cream'][user->id % 4]`

### 2. Partido destacado (LiveMatchCard / UpcomingMatchCard)
El partido más relevante: si hay alguno `status = 'in_progress'` mostrarlo LIVE; si no, el próximo `scheduled` más cercano.

```php
$featured = Fixture::where('status', 'in_progress')
    ->with(['homeTeam', 'awayTeam', 'group'])
    ->first()
    ?? Fixture::where('status', 'scheduled')
       ->where('match_date', '>=', now())
       ->with(['homeTeam', 'awayTeam', 'group'])
       ->orderBy('match_date')
       ->first();

// Predicción del usuario para ese partido
$myPrediction = Prediction::where('user_id', auth()->id())
    ->where('match_id', $featured?->id)
    ->first();
```

Props necesarias para el componente:
- `status` → `live` / `upcoming` (estandarizado)
- `teamA`, `teamB`, `flagUrlA`, `flagUrlB`
- `scoreA`, `scoreB`, `minute` (si live)
- `group` (letra del grupo), `venue` (si existe)
- `myPick` → `"{predicted_home}-{predicted_away}"` o null
- `myPts` → suma pts_result + pts_exact de esa predicción (si FT)
- `isWinnerCorrect` → para chip "VAS X EL GANADOR" (si live)

### 3. StatCards (3 tarjetas)
| Card | Dato | Query |
|---|---|---|
| POSICIÓN | `#N / total` | posición calculada + COUNT usuarios activos |
| ACERTADOS | número | COUNT predictions WHERE pts_result > 0 AND user_id = auth |
| _(RACHA omitida)_ | ver spec de Rachas | — |

**Stat RACHA**: ver `2026-05-26-streaks-spec.md` — omitida hasta implementar lógica.

### 4. Banner de fase
La ronda actualmente abierta (`is_open = true`):
- `round->name` → "GRUPOS"
- Partidos sin predicción: `fixtures->count() - predictions_del_usuario->count()`
- Cierre: `round->fixtures->max('match_date')` o campo separado si se agrega

### 5. BetCards "TUS PRÓXIMOS"
Próximas 5 predicciones del usuario para partidos futuros o en curso:
```php
Prediction::where('user_id', auth()->id())
    ->whereHas('fixture', fn($q) => $q->whereIn('status', ['scheduled','in_progress'])->orderBy('match_date'))
    ->with('fixture.homeTeam', 'fixture.awayTeam')
    ->limit(5)
    ->get()
```

Props de BetCard: `teamA`, `teamB`, `flagUrlA`, `flagUrlB`, `pick`, `pts` (posibles = round->points_exact), `time` (diff humano: "EN 2H", "MAÑ 16H", etc.), `hot` (is_live)

---

## Decisiones pendientes al implementar
- ¿`/dashboard` se reemplaza o creamos ruta `/home` separada?
- El "ticker" de texto scrollable puede ser hardcoded en Fase 1 (mensajes estáticos) o conectado a eventos de Reverb en el futuro.
- La StatCard de RACHA se conecta cuando se implementa `2026-05-26-streaks-spec.md`.
