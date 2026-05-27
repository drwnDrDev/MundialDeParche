# User Predictions UI — Spec

**Fecha:** 2026-05-27
**Alcance:** Plan 6a — Phases Index + Phase Receipt + scoring audit de pantallas existentes
**Stack:** React + Inertia.js + MobileShell + design system pop-art existente

---

## Contexto

El frontend de usuario tiene las pantallas de predicción por fase (`Predictions/Round.jsx`) funcionando, pero:

1. `Predictions/Index.jsx` usa `AuthenticatedLayout` (generic Blade) en lugar de `MobileShell`, y solo muestra un listado plano sin información de progreso ni puntos. Las fases que no están abiertas son invisibles para el usuario.
2. No existe una pantalla de comprobante ("receipt") donde el usuario pueda ver sus predicciones vs resultados reales + desglose de puntos.
3. `HowTo.jsx` muestra scoring plano (+5 / +2 / +3) que no refleja la escala progresiva real por ronda.

---

## Pantallas a construir / modificar

| Pantalla | Archivo | Acción |
|---|---|---|
| Phases Index | `Predictions/Index.jsx` | Reescribir completo |
| Phase Receipt | `Predictions/Receipt.jsx` | Nuevo |
| HowTo scoring | `HowTo.jsx` | Actualizar sección "La Puntuación" |
| Rules scoring | `Rules.jsx` | Verificar y corregir si aplica |
| Round.jsx hints | `Predictions/Round.jsx` | Verificar hints de puntos hardcodeados |
| Matches.jsx pts | `Matches.jsx` | Confirmar sin refs a pts_classifier por partido |

---

## 1. Phases Index — `Predictions/Index.jsx`

### Ruta
`GET /predictions` → `PredictionController@index`

### Props del controller (actualizar)

```php
// PredictionController@index — payload actualizado
[
  'rounds' => Round::orderBy('order')->withCount('fixtures')->get(),
  'submissions' => [...],  // keyed by round_id, incluye pts_classifier
  'phasePts' => [          // NUEVO: puntos por ronda del usuario
    $roundId => [
      'pts_exact'      => int,   // SUM(predictions.pts_exact) para fixtures de esta ronda
      'pts_result'     => int,   // SUM(predictions.pts_result)
      'pts_classifier' => int,   // prediction_submissions.pts_classifier
      'total'          => int,
      'prediction_count' => int, // cuántas predicciones ha guardado el usuario
    ]
  ],
]
```

La query de `phasePts` se construye así para cada ronda:
```php
$fixtureIds = Fixture::where('round_id', $round->id)->pluck('id');
$pts = Prediction::where('user_id', Auth::id())
    ->whereIn('match_id', $fixtureIds)
    ->selectRaw('SUM(pts_exact) as pts_exact, SUM(pts_result) as pts_result, COUNT(*) as prediction_count')
    ->first();
```

### Layout

`MobileShell` como wrapper. Sin `AuthenticatedLayout`.

**Header:**
```
←  MIS FASES          [badge: 128 PTS]
```
El badge total es `users.total_points` (incluye special pts).

**Progress bar del torneo:**
4 nodos horizontales conectados por línea, uno por fase en orden:
- Nodo pasado (finalizado): relleno ink, checkmark
- Nodo activo (open/submitted/locked): relleno red, pulsante
- Nodo futuro (upcoming): outline ink, candado

Labels debajo de cada nodo: "GRUPOS", "R32+R16", "8vos+4tos", "FINAL".

**Stack de 4 Phase Cards** (componente `PhaseCard`):

### Estados de PhaseCard

Determinado en el controller o derivado en el frontend desde `round` + `submission`:

| Estado | Condición |
|---|---|
| `upcoming` | `!round.is_open && !round.is_locked` |
| `open` | `round.is_open && !submission` |
| `draft` | `round.is_open && submission.status === 'draft'` |
| `submitted` | `round.is_open && submission.status === 'submitted'` |
| `locked` | `round.is_locked && !submission` |
| `finalized` | `round.is_locked && submission` |

> Nota: en cuanto `round.is_locked = true` la ronda se considera finalizada a efectos de UI. Los puntos se muestran tal como están (pueden ser parciales si aún hay partidos en curso — el live scoring los va actualizando). El admin bloquea la ronda cuando empieza el primer partido de la siguiente fase.

### Diseño de PhaseCard por estado

**`upcoming`**
```
┌─────────────────────────────────────────┐  ink border, pop-sm shadow
│  [candado]  FASE DE GRUPOS              │  bg: cream, opacity 0.6
│             72 partidos · Junio 2026    │  text apagado
└─────────────────────────────────────────┘
```

**`open`**
```
┌─────────────────────────────────────────┐  ink border, pop-md shadow
│  [punto verde]  FASE DE GRUPOS    ABIERTA│  bg: cream
│  ▓▓▓▓▓░░░░░  16/72 partidos            │  progress bar teal
│                                         │
│  [CTA: METER GOLES →]                   │  btn red full-width
└─────────────────────────────────────────┘
```

**`draft`**
```
┌─────────────────────────────────────────┐
│  [punto amarillo]  FASE DE GRUPOS        │
│  ▓▓▓▓▓▓▓▓░░  56/72 partidos            │
│                                         │
│  [CTA: CONTINUAR →]                     │  btn yellow
└─────────────────────────────────────────┘
```

**`submitted`**
```
┌─────────────────────────────────────────┐
│  [✓ teal]  FASE DE GRUPOS  [CONFIRMADA] │  badge teal
│  Confirmadas el 15 jun · 72/72          │
│                                         │
│  [CTA: VER COMPROBANTE →]               │  btn ghost/outline
└─────────────────────────────────────────┘
```

**`locked`**
```
┌─────────────────────────────────────────┐
│  [● rojo]  FASE DE GRUPOS  [EN JUEGO]   │  badge rojo pulsante
│  Los puntos se calculan al finalizar    │  texto gris
│                                         │
│  [CTA: VER COMPROBANTE →]               │  btn ghost
└─────────────────────────────────────────┘
```

**`finalized`**
```
┌─────────────────────────────────────────┐  bg: navy, texto cream
│  [✓]  FASE DE GRUPOS          [+48 PTS] │  PtsBadge yel
│  exacto ×5 · resultado ×12 · clasif ×4  │  chips pequeños
│                                         │
│  [CTA: VER COMPROBANTE →]               │  btn cream/ghost
└─────────────────────────────────────────┘
```

**Bloque de Predicciones Especiales** (al fondo, separado del stack):
Card individual con estado propio: editable / enviadas / bloqueadas / calculadas (con puntos especiales).

---

## 2. Phase Receipt — `Predictions/Receipt.jsx`

### Ruta
`GET /predictions/{round}/receipt` → `PredictionController@receipt` (nueva acción)

Accesible desde PhaseCard cuando `submission` existe (estado `submitted`, `locked`, o `finalized`).
Redirige a `predictions.index` si el usuario no tiene submission para esa ronda.

### Props del controller

```php
[
  'round'      => Round,
  'fixtures'   => Fixture[] con homeTeam, awayTeam, group (eager loaded)
  'predictions' => Prediction[] keyed por match_id
                   (campos: predicted_home, predicted_away, pts_exact, pts_result)
  'submission' => PredictionSubmission (status, pts_classifier, submitted_at)
  'isFinalized' => bool  // round.is_locked (puntos visibles; pueden ser parciales si hay partidos en curso)
]
```

### Layout

`MobileShell`. Sin TabBar (vista de detalle, no tab principal).

**Header:**
```
←  FASE DE GRUPOS
   FASE DE GRUPOS · 72 PARTIDOS
   [badge estado]
```

**Banner de puntos** (solo si `isFinalized`):
```
┌────────────────────────────────────────┐  bg: navy
│  EXACTO +15  ·  RESULTADO +8  ·  CLASIF +6   TOTAL: +29 PTS  │
└────────────────────────────────────────┘
```
Si `!isFinalized` y `submitted`:
```
┌────────────────────────────────────────┐  bg: yel
│  ⏳  Los puntos se calculan cuando finalicen los partidos      │
└────────────────────────────────────────┘
```

**Lista de partidos** (ordenada por `match_number`):

Cada row:
```
[flag] ESP  2–1  FRA [flag]    predijiste  2–0    [+5] [+2]
```
- Resultado real a la izquierda
- Predicción a la derecha (separadas por `predijiste`)
- Chips de puntos: verde para `pts_exact`, teal para `pts_result`, gris para 0
- Si `!isFinalized`: resultado real puede ser `—` si el partido no tiene score; chips de puntos ocultos

**Partidos TBD (knockout):**
Equipos no asignados al momento de predecir muestran el placeholder (`Ganador M73`) en lugar de bandera + código.

**Bloque de clasificados** (solo en R1 y R2, al final):
```
┌────────────────────────────────────────┐
│  CLASIFICADOS  Acertaste 18 de 32  +36 PTS  │
└────────────────────────────────────────┘
```
Si `!isFinalized`: no se muestra este bloque.

---

## 3. Auditoría de pantallas existentes

### 3a. HowTo.jsx — scoring incorrecto

**Problema:** La sección "LA PUNTUACIÓN" muestra valores planos:
```
+5  MARCADOR EXACTO
+2  GANADOR
+3  CLASIFICA A LA SIGUIENTE
```

**Valores reales** (progresivos por ronda):
| Ronda | Exacto | Resultado | Clasifica |
|---|---|---|---|
| Fase de Grupos | +3 | +1 | +2 |
| R32 + R16 | +5 | +2 | +4 |
| Cuartos + Semis | +8 | +3 | — |
| Final + 3er Puesto | +13 | +5 | — |

**Fix:** Reemplazar los 3 `ScoreLine` estáticos por una tabla por ronda. Diseño: sección colapsable o tabla scrollable horizontal con 4 columnas (una por ronda). Mantener el estilo pop-art del resto de la pantalla.

Texto actualizado del subtítulo: "Los puntos aumentan en cada fase — vale más acertar en la final."

### 3b. Rules.jsx — verificar

Leer y confirmar si tiene la misma tabla de scoring. Si replica los valores del handoff, aplicar el mismo fix.

### 3c. Predictions/Round.jsx — hints de puntos

Verificar si el componente muestra hints de puntos por partido (ej. "Exacto: +5") hardcodeados o derivados del prop `round.points_exact`. Si están hardcodeados, corregirlos para usar `round.points_exact` y `round.points_result` del prop ya disponible.

### 3d. Matches.jsx — pts_classifier

Confirmar que `myPts` por partido es solo `pts_exact + pts_result` (ya corregido en MatchesController). No requiere cambios si el bug del controller ya está resuelto (está ✅).

---

## 4. Rutas nuevas a agregar

```php
// En el grupo predictions:
Route::get('/{round}/receipt', [PredictionController::class, 'receipt'])->name('receipt');
```

---

## 5. Tests

### PredictionController@receipt
- `GET /predictions/{round}/receipt` con submission existente → 200, Inertia render `Predictions/Receipt`
- Sin submission para esa ronda → redirect a `predictions.index`
- Ronda no finalizada → `isFinalized = false` en props
- Ronda finalizada → `isFinalized = true`, puntos en props

### PredictionController@index (actualizado)
- Props incluyen `phasePts` con las sumas correctas por ronda
- Ronda sin predicciones del usuario → `phasePts[$roundId]` con valores en 0

---

## 6. Componentes nuevos

| Componente | Archivo | Descripción |
|---|---|---|
| `PhaseCard` | `Components/composed/PhaseCard.jsx` | Card de fase con sus 6 estados |
| `TournamentProgress` | `Components/composed/TournamentProgress.jsx` | Barra de nodos de progreso del torneo |
| `ReceiptMatchRow` | `Components/composed/ReceiptMatchRow.jsx` | Row de partido en el comprobante |
| `PtsChip` | `Components/ui/PtsChip.jsx` | Chip de puntos (+N, coloreado por tipo) |

`PhaseCard` y `TournamentProgress` se usan solo en `Predictions/Index.jsx`.
`ReceiptMatchRow` y `PtsChip` se usan solo en `Predictions/Receipt.jsx`.

Si alguno resulta ser un one-liner, puede vivir inline en su página.

---

## 7. Orden de implementación

1. Actualizar `PredictionController@index` — nuevo payload con `phasePts`
2. Agregar ruta + `PredictionController@receipt`
3. Tests del controller (receipt + index actualizado)
4. `Predictions/Index.jsx` — reescribir con MobileShell + PhaseCard + TournamentProgress
5. `Predictions/Receipt.jsx` — nuevo
6. Auditoría y fix de `HowTo.jsx`
7. Verificar y corregir `Rules.jsx` y `Round.jsx`

---

## Fuera de alcance

- Detalle de clasificados por equipo en el comprobante (quiénes clasificaron, no solo cuántos)
- Comparación con otros usuarios en el comprobante
- Animaciones de puntos en tiempo real dentro del comprobante
- Push notifications / email cuando se calculan los puntos
