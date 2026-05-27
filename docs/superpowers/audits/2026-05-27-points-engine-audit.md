# Auditoría del Motor de Puntos

**Fecha:** 2026-05-27
**Archivos revisados:**
- `app/Listeners/CalculateMatchPoints.php`
- `app/Listeners/CalculateClassifierPoints.php`
- `app/Listeners/CalculateSpecialPredictions.php`
- `app/Models/User.php` (`recalculateTotalPoints`)
- `app/Models/Prediction.php` + migración
- `app/Models/PredictionSubmission.php`
- `app/Models/Fixture.php` (`isGroupStage`)

---

## Resumen de estado

La lógica core de cálculo de puntos es **correcta en sus fundamentos** pero tiene
4 issues que deben resolverse antes de integrar con la UI real:

| # | Severidad | Área | Descripción | Estado |
|---|---|---|---|---|
| 1 | 🔴 Bug de esquema | `predictions.pts_classifier` | Columna muerta — nunca se escribe | ✅ RESUELTO |
| 2 | 🟡 Frágil | `CalculateClassifierPoints` R2 | `slice(16)` asume orden en BD | ✅ RESUELTO |
| 3 | 🟡 Gap de validación | Knockout draws | Solo frontend valida; backend no rechaza | ✅ RESUELTO |
| 4 | 🟢 Riesgo bajo | `array_intersect` con duplicados | Posible overcount en datos corruptos | Pendiente |

---

## 1. ✅ `predictions.pts_classifier` — columna muerta (RESUELTO 2026-05-27)

**Problema:**
La columna `pts_classifier` existe en la tabla `predictions` (y en `Prediction::$fillable`)
pero **nunca se escribe**. `CalculateMatchPoints` solo actualiza `pts_exact`, `pts_result`
y `total_points = pts_exact + pts_result`.

Los puntos de clasificados se almacenan en `prediction_submissions.pts_classifier`
(nivel ronda, no nivel partido), lo cual es correcto arquitectónicamente porque
los clasificados se evalúan al final de la ronda, no partido a partido.

`User::recalculateTotalPoints` lo suma correctamente:
```php
$matchPts      = Prediction::where('user_id', $userId)->sum('total_points');       // pts_exact + pts_result
$classifierPts = PredictionSubmission::where('user_id', $userId)->sum('pts_classifier'); // puntos de clasificados
$specialPts    = ...;
```

**Consecuencias:**
- `predictions.pts_classifier` siempre será 0 — columna fantasma.
- `predictions.total_points` = pts_exact + pts_result únicamente (sin clasificados).
- Si la UI intenta leer `prediction.pts_classifier` para mostrar el desglose por partido, siempre mostrará 0.

**Solución aplicada:**
- Migración `2026_05_27_100000_drop_pts_classifier_from_predictions_table.php` elimina la columna.
- Eliminado de `Prediction::$fillable` y `PredictionFactory`.
- `MatchesController::formatFixture` corregido: `$pts = pts_exact + pts_result` (sin classifier).
- Test `PredictionTest` actualizado para no referenciar el campo.
- 207 tests pasan.

---

## 2. 🟡 `CalculateClassifierPoints` R2 — `slice(16)` es frágil

**Código:**
```php
$r2Fixtures = Fixture::where('round_id', $round->id)->orderBy('match_number')->get();
$r16Fixtures = $r2Fixtures->slice(16)->values();
```

**Problema:**
Asume que los primeros 16 fixtures de R2 (por `match_number`) son los partidos de R32
y los siguientes 8 son los de R16. Esto funciona si los 24 partidos están cargados en
orden y `match_number` los numera correctamente, pero:

- Si el admin carga los partidos fuera de orden.
- Si `match_number` no sigue la convención esperada.
- Si se agregan fixtures de prueba u otros.

...los últimos 8 en el slice serán erróneos y los clasificados a QF se calcularán mal.

**Acciones requeridas:**
- [ ] Verificar que el seeder de fixtures de R2 asigna `match_number` en el orden
      correcto: R32 primero (1-16), R16 después (17-24).
- [ ] Considerar marcar los partidos R16 explícitamente (campo `stage` o `match_number`
      conocido y fijo) en lugar de depender de slicing.
- [ ] Agregar un test que simule R2 completo y verifique que `calculateR2` selecciona
      los partidos correctos.

---

## 3. 🟡 Validación de empates en knockout — solo frontend

**Spec:**
> "En R2, R3 y R4 no pueden existir empates. El frontend debe validar que
> `predicted_home != predicted_away` antes de guardar."

**Código actual en `CalculateMatchPoints` (knockout):**
```php
if ($fixture->winner_team_id !== null && $prediction->predicted_home !== $prediction->predicted_away) {
    // asigna pts_result
}
```

Si llega una predicción con empate (1-1) en un partido knockout:
- `pts_result = 0` (el guard la ignora silenciosamente)
- `pts_exact = 3/5/8/13` si el marcador a 90min fue efectivamente empate (caso real: penales)

Esto es **correcto por spec** (empate predicho en knockout no acredita resultado), pero
la validación existe solo en el frontend. El `PredictionController` no rechaza marcadores
empatados en rondas de eliminación.

**Acciones requeridas:**
- [ ] Agregar validación en `PredictionController@save`: si la ronda no es grupo stage
      y `predicted_home == predicted_away`, retornar error 422.
- [ ] Verificar que `Round.jsx` muestra el mensaje de error especificado en la spec:
      "En rondas de eliminación debe haber un ganador".
- [ ] Agregar test: POST a `/predictions/{round}/save` con marcador empatado en R2
      debe retornar 422.

---

## 4a. 🟢 Edge case aceptado — desempate de 8 mejores terceros

**Situación:**
La cadena de desempate actual es `pts → gd → gf`. Si dos terceros empatan en los tres
criterios, `usort` deja el orden no determinístico.

FIFA 2026 resuelve empates posteriores con puntos disciplinarios y ranking FIFA — datos
que los usuarios no predicen y que no podemos simular.

**Decisión:** Edge case aceptado para MVP.
- En los **clasificados reales**: FIFA ya resuelve el empate; el admin carga los scores
  correctos y el sistema deriva el conjunto correcto.
- En los **clasificados predichos**: empate pts/gd/gf entre dos terceros distintos es
  estadísticamente muy improbable; se acepta la indeterminación del sort.

**TODO UI — Pantalla Reglas/HowTo:**
- [ ] Explicar que los pts de clasificados de fase de grupos se calculan simulando tablas
      de posiciones a partir de los marcadores predichos. En el extremo caso de empate
      exacto en pts/gd/gf entre terceros, el sistema puede diferir del criterio oficial FIFA.

---

## 4b. 🟢 `array_intersect` con equipos duplicados — riesgo bajo

**Código:**
```php
$correct = count(array_intersect($predictedClassifiers, $realClassifiers));
```

**Problema:**
`array_intersect` en PHP no hace deduplicación del primer array. Si por corrupción de datos
un equipo apareciera dos veces en `$predictedClassifiers` y una vez en `$realClassifiers`,
contaría como 2 aciertos en lugar de 1.

En la práctica esto no debería ocurrir porque:
- La combinación `(user_id, match_id)` es `UNIQUE` en `predictions`.
- Cada partido tiene equipos distintos.

**Acciones requeridas:**
- [ ] No es urgente. Considerar `array_unique($predictedClassifiers)` antes del intersect
      como medida defensiva si se quiere evitar el riesgo.

---

## Lo que está correcto ✅

### `CalculateMatchPoints`
- **Exacto (90 min):** `predicted_home === home_score && predicted_away === away_score` ✅
- **Resultado grupo (1/X/2):** Comparación de signo con spaceship operator ✅
- **Resultado knockout:** Compara `predictedWinnerId` contra `winner_team_id` real ✅
  - Caso penales: Brasil gana 1-1 → `winner_team_id = brasil_id`. Usuario predijo 1-0 Brasil
    → `predictedWinnerId = brasil_id` → pts_result ✅. Usuario predijo 1-1 → guard de empate
    → pts_result = 0 ✅ (per spec).
- **Solo usuarios submitted/locked:** `whereIn('status', ['submitted', 'locked'])` ✅
- **Recálculo de ranking post-cálculo:** `User::recalculateTotalPoints` + broadcast ✅
- **ExactScoreAlert:** Dispara alertas a usuarios que acertaron exacto ✅

### `CalculateClassifierPoints` R1
- **Simulación FIFA completa:** Builds tabla con pts/gd/gf por marcadores predichos ✅
- **32 clasificados:** Top 2 por grupo (24) + 8 mejores terceros ✅
- **Tiebreaker terceros:** pts → gd → gf (criterio FIFA simplificado) ✅
- **Solo submissions válidos:** `whereIn('status', ['submitted', 'locked'])` ✅

### `CalculateSpecialPredictions`
- **Puntos correctos:** 30 (campeón), 10 (subcampeón), 15 (goleador) ✅
- **Bloqueo automático:** `is_locked = true` al calcular ✅
- **Recálculo de usuario:** `User::recalculateTotalPoints` ✅

### `User::recalculateTotalPoints`
- **Suma correcta:** `predictions.total_points` + `submission.pts_classifier` + special pts ✅
- **Sobreescribe (no acumula):** Diseño correcto para correcciones retroactivas ✅

---

## Pendientes de verificar (requieren prueba manual o tests)

- [ ] **UI vs lógica knockout:** La pantalla `Round.jsx` muestra el desglose de puntos.
      Confirmar que no intenta mostrar `prediction.pts_classifier` por partido.
- [ ] **UI vs clasificados:** Confirmar que la pantalla de resultados muestra los puntos
      de clasificados desde `submission.pts_classifier`, no desde `prediction`.
- [ ] **Flujo RoundFinalized:** Verificar que el admin puede finalizar una ronda
      (disparar `RoundFinalized`) y que el evento llega al listener correctamente.
- [ ] **Orden de partidos R2 en seeder:** Revisar que el fixture seeder carga R32 primero
      y R16 después para que `slice(16)` funcione.
- [ ] **`points:recalculate` command:** Confirmar que el comando llama a los mismos
      listeners o equivalentes, incluyendo pts_classifier cuando `--round` está presente.
