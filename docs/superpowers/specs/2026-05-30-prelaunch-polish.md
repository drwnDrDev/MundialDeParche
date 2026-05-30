# Spec: Pre-launch Polish — 5 ítems

**Fecha:** 2026-05-30  
**Estado:** Aprobado para implementación

---

## Ítem 1: Bloqueo de predicciones especiales

### Problema
`SpecialPrediction.is_locked` existe en el modelo y `save()` lo respeta, pero `RoundController::lock()` nunca activa ese campo. Las especiales quedan editables indefinidamente.

### Solución

**Backend — `RoundController::lock()`**  
Al bloquear una ronda, si su slug es `grupos`, hacer update masivo:
```php
if ($round->slug === 'grupos') {
    \App\Models\SpecialPrediction::query()->update(['is_locked' => true]);
}
```

**Backend — `SpecialPredictionController::save()`**  
Agregar segundo guard para usuarios que aún no tienen registro (nunca guardaron):
```php
$gruposRound = Round::where('slug', 'grupos')->first();
if ($gruposRound?->is_locked) {
    return back()->with('status', 'Las predicciones especiales están bloqueadas.');
}
```
Este guard va antes del `updateOrCreate`, después del guard de `$special->is_locked`.

**Frontend — `Special.jsx`**  
No cambia. Ya maneja `isLocked` correctamente mostrando read-only.

### Especiales en Receipt de R1

**`PredictionController::receipt()`**  
Si `$round->slug === 'grupos'`, cargar la `SpecialPrediction` del usuario visualizado:
```php
$specialPrediction = null;
if ($round->slug === 'grupos') {
    $specialPrediction = \App\Models\SpecialPrediction::with(['champion', 'runnerUp', 'topScorer.team'])
        ->where('user_id', $viewedUserId)
        ->first();
}
```
Pasar como prop `specialPrediction`.

**`Receipt.jsx`**  
Al final del contenido (antes del `pb-10`), si `specialPrediction` existe, renderizar sección:
- Header "PREDICCIONES ESPECIALES"
- 3 filas: Campeón / Sub-campeón / Goleador con nombre e imagen de bandera/equipo
- Si `isFinalized`, mostrar `PtsChip` con los puntos obtenidos en cada una

---

## Ítem 2: Comprobante público con selector de usuario

### URL y acceso
`GET /predictions/{round}/receipt` — parámetro opcional `?user_id=X`

- Si la ronda **no está bloqueada**: ignora `user_id`, muestra solo el usuario autenticado (comportamiento actual)
- Si la ronda **está bloqueada**: acepta cualquier `user_id` válido de usuario activo con submission en esa ronda

### Backend — `PredictionController::receipt()`

```php
$viewedUserId = Auth::id();
if ($round->is_locked && $request->filled('user_id')) {
    $requestedId = (int) $request->query('user_id');
    // Verificar que existe submission para ese usuario+ronda
    $exists = PredictionSubmission::where('user_id', $requestedId)
        ->where('round_id', $round->id)->exists();
    if ($exists) {
        $viewedUserId = $requestedId;
    }
}
```

Cuando la ronda está bloqueada, cargar lista de usuarios con submission:
```php
$usersWithSubmission = null;
if ($round->is_locked) {
    $submittedUserIds = PredictionSubmission::where('round_id', $round->id)
        ->pluck('user_id');
    $usersWithSubmission = \App\Models\User::whereIn('id', $submittedUserIds)
        ->where('is_active', true)
        ->orderBy('name')
        ->select(['id', 'name'])
        ->get();
}
```

Props nuevas: `viewedUserId`, `usersWithSubmission` (null cuando no bloqueada), `authUserId`.

### Frontend — `Receipt.jsx`

Header: cuando `usersWithSubmission` existe, mostrar `<select>` con la lista de usuarios. El valor seleccionado es `viewedUserId`. Al cambiar:
```js
router.visit(route('predictions.receipt', { round: round.slug }), {
    data: { user_id: selectedId },
    preserveScroll: false,
});
```

Si el usuario visualizado no es el autenticado, mostrar badge "VER: [NOMBRE]" en el header para dejar claro que es el comprobante de otro jugador.

---

## Ítem 3: Premio 70% / 20% (comisión implícita)

### Backend — `RankingController`
```php
'prize1' => $fmt((int) ($total * 0.70)),
'prize2' => $fmt((int) ($total * 0.20)),
// prize3 (10%) no se expone en la API
'amountPerPlayer' => $fmt(50000),
```

### Frontend — `PozoCard.jsx`
- Mantener 2 slots: `1° / 70%` y `2° / 20%`
- Eliminar referencia al 30% actual y cambiar a 20%
- Grid permanece en 2 columnas

### Frontend — `Rules.jsx`
Agregar en la sección de premiación:
> "El 10% restante del pozo se destina a los costos de operación de la plataforma."

---

## Ítem 4: Bug real-time de marcadores

### Diagnóstico
Dos problemas independientes:

1. **Frontend (`Matches.jsx`)**: No tiene ningún listener de Echo. Los eventos `LiveScoreUpdated` se emiten pero nadie los escucha en la vista de partidos.

2. **Backend (`FixtureController::update()`)**: Solo dispara `MatchScoreUpdated` (para cálculo de puntos), pero **no** `LiveScoreUpdated` (para broadcast de UI). El admin puede editar scores desde dos rutas:
   - `/admin/score-entry` → `ScoreEntryController` ✅ sí dispara `LiveScoreUpdated`
   - `/admin/fixtures/{id}/edit` → `FixtureController` ❌ no dispara `LiveScoreUpdated`

### Fix Backend
En `FixtureController::update()`, después del `$fixture->update($data)`, agregar:
```php
$fresh = $fixture->fresh();
if ($fresh->home_score !== null && $fresh->away_score !== null) {
    MatchScoreUpdated::dispatch($fresh);
    if (in_array($fresh->status, ['in_progress', 'finished'])) {
        LiveScoreUpdated::dispatch(
            $fresh->id,
            $fresh->home_score,
            $fresh->away_score,
            $fresh->isLive(),
        );
    }
}
```
(Reemplaza el bloque existente que solo hace `MatchScoreUpdated::dispatch`.)

### Fix Frontend
En `Matches.jsx`, agregar `useEffect` para suscribirse al canal:
```js
useEffect(() => {
    const channel = window.Echo.join('quinela');
    channel.listen('.LiveScoreUpdated', (event) => {
        setMatchDays(prev => prev.map(day => ({
            ...day,
            matches: day.matches.map(m =>
                m.id === event.match_id
                    ? { ...m, home_score: event.home_score, away_score: event.away_score, is_live: event.is_live }
                    : m
            ),
        })));
    });
    return () => window.Echo.leave('quinela');
}, []);
```
Requiere que el estado de `matchDays` sea `useState(initialMatchDays)` en lugar de valor fijo.

---

## Ítem 5: Suite de simulación en 2 capas

### Capa 1 — Pest (lógica de estado)

Archivo: `tests/Feature/Simulation/TournamentFlowTest.php`

Escenario cubierto (en orden cronológico):
1. Usuarios no pueden predecir antes de que la ronda esté abierta → error
2. Admin abre R1 → usuarios predicen → submission guardada
3. Admin bloquea R1 → predicciones especiales quedan bloqueadas → no se puede editar
4. Admin carga marcadores → `MatchScoreUpdated` dispara → puntos calculados
5. Admin finaliza R1 → `RoundFinalized` → puntos de clasificados calculados
6. Admin abre R2 → usuarios R2 predicen, usuarios sin predicción en R1 no tienen puntos
7. Ranking final refleja orden correcto

Fixtures de test: 5 usuarios, 2 partidos por ronda (para agilidad), predicciones variadas para verificar exactos, resultados correctos e incorrectos.

### Capa 2 — Sub-agentes Claude (flujo real contra servidor)

Precondición: `./vendor/bin/sail up -d` corriendo con datos sembrados (`migrate:fresh --seed`).

**Sub-agentes definidos:**

| Agente | Rol | Acciones |
|--------|-----|---------|
| `admin-agent` | Administrador | Abre R1, carga marcadores, bloquea R1, abre R2, carga marcadores R2, finaliza torneo |
| `user-1-agent` | Jugador 1 (predice todo) | Predice R1 completo, predice especiales, predice R2 |
| `user-2-agent` | Jugador 2 (predice parcial) | Predice solo algunos partidos de R1, no predice especiales |
| `user-3-agent` | Jugador 3 (tarda) | Predice R1 justo antes del bloqueo |
| `observer-agent` | Verificador | Después de cada paso del admin, verifica ranking, puntos, estado de rondas via HTTP |

**Flujo cronológico del orchestrador:**

```
[T+0]  admin-agent: abre R1
[T+0]  user-1, user-2, user-3: predicen R1 en paralelo
[T+1]  admin-agent: bloquea R1
[T+1]  user-1: intenta editar R1 → debe fallar (verificar 403/redirect)
[T+1]  user-1: predice especiales
[T+2]  admin-agent: carga 3 marcadores de R1
[T+2]  observer-agent: verifica puntos y ranking via GET /ranking
[T+3]  admin-agent: finaliza R1
[T+3]  observer-agent: verifica pts_classifier en submissions
[T+4]  admin-agent: abre R2
[T+4]  user-1, user-2: predicen R2
[T+5]  admin-agent: carga marcadores R2, finaliza R2
[T+5]  observer-agent: verifica ranking final
```

**Implementación técnica:**
- Script de arranque: `docs/superpowers/simulations/run-simulation.md` — instrucciones para lanzar el orchestrador
- Los agentes usan `curl` con cookies de sesión (login por POST a `/login` primero)
- Credenciales de test sembradas por `DatabaseSeeder` con flag `--sim` o en `SimulationSeeder`
- El orchestrador es un agente Claude principal que lanza sub-agentes con `superpowers:dispatching-parallel-agents`

---

## Tests a agregar (Capa 1)

- `TournamentFlowTest` — flujo completo como se describe arriba
- `SpecialPredictionLockTest` — verifica que el bloqueo de R1 bloquea especiales
- `PublicReceiptTest` — verifica acceso a comprobante de otro usuario solo cuando bloqueado
- Tests existentes no se modifican

---

## Resumen de archivos afectados

| Archivo | Cambio |
|---------|--------|
| `RoundController.php` | Lock R1 → bloquear todas las special_predictions |
| `SpecialPredictionController.php` | Guard adicional: rechazar si ronda grupos está bloqueada |
| `FixtureController.php` | Dispatch `LiveScoreUpdated` en update |
| `PredictionController.php` | `receipt()` acepta `user_id`, carga `specialPrediction`, carga `usersWithSubmission` |
| `RankingController.php` | Calcular 70%/20%, agregar `amountPerPlayer` |
| `PozoCard.jsx` | Cambiar 30% → 20% |
| `Receipt.jsx` | Selector de usuario, sección de especiales para R1 |
| `Matches.jsx` | Listener Echo `LiveScoreUpdated`, `useState` para matchDays |
| `Rules.jsx` | Explicar 10% de comisión |
| `tests/Feature/Simulation/TournamentFlowTest.php` | Nuevo |
| `docs/superpowers/simulations/run-simulation.md` | Nuevo (instrucciones capa 2) |
