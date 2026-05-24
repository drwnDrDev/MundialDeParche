# PollaMundial — Diseño del Sistema (MVP)

**Fecha:** 2026-05-23
**Stack:** Laravel · React · MySQL · Reverb
**Alcance:** MVP — quinela global única, usuarios administrados manualmente

---

## 1. Contexto y Objetivo

Aplicación web de quinela para el Mundial FIFA 2026. Los usuarios registrados predicen marcadores de partidos organizados en 4 rondas de predicción. El sistema calcula puntos automáticamente con escala progresiva, actualiza el ranking en tiempo real vía WebSockets, y ofrece un chat grupal. Un módulo admin permite gestionar el torneo completo, el live scoring y los usuarios.

---

## 2. Estructura del Torneo

**Mundial 2026:**
- 48 equipos, 12 grupos de 4 (A–L)
- 104 partidos en total
- Clasificación grupo: top 2 por grupo + 8 mejores terceros = 32 clasificados

**4 Rondas de Predicción:**

| Ronda | Contenido | Partidos |
|---|---|---|
| R1 | Fase de Grupos | 72 (12 grupos × 6) |
| R2 | Round of 32 + Round of 16 | 24 |
| R3 | Cuartos de Final + Semifinales | 6 |
| R4 | 3er Puesto + Final | 2 |

---

## 3. Sistema de Puntos

### Puntos por partido

| Acierto | R1 | R2 | R3 | R4 |
|---|---|---|---|---|
| Marcador exacto | 3 | 5 | 8 | 13 |
| Resultado correcto (1/X/2) | 1 | 2 | 3 | 5 |
| Clasificado a siguiente ronda | 2 | 4 | — | — |

### Puntos máximos teóricos

| Ronda | Partidos | Max pts (exacto + clasificados) |
|---|---|---|
| R1 | 72 matches + 32 classifiers | 280 pts |
| R2 | 24 matches + 8 classifiers | 152 pts |
| R3 | 6 matches | 48 pts |
| R4 | 2 matches | 26 pts |
| **Total partidos** | | **~506 pts** |

### Predicciones especiales (pre-torneo)

Se ingresan una sola vez antes del primer partido. Quedan bloqueadas al iniciar el torneo.

| Predicción | Puntos |
|---|---|
| Campeón | 30 |
| Goleador | 15 |
| Sub-campeón | 10 |

**Total máximo general: ~561 pts**

### Lógica de clasificados

**R1 (Grupos):**
- El sistema simula la tabla de posiciones de cada grupo a partir de los marcadores predichos (criterios FIFA: puntos → diferencia de goles → goles a favor).
- Se identifican los top 2 predichos por grupo (24 equipos) y el tercero de cada grupo (12 terceros).
- Los 8 mejores terceros reales se comparan con los 8 mejores terceros predichos usando el mismo criterio FIFA.
- 2 pts por cada clasificado predicho que coincida con el clasificado real.

**R2 (R32 + R16):**
- Se evalúan los 8 equipos que el usuario predijo llegando a Cuartos de Final (ganadores de sus partidos R16 predichos).
- Se comparan con los 8 equipos reales en Cuartos.
- 4 pts por cada coincidencia en el set de 8.

---

## 4. Flujo de Predicciones

### Ciclo de vida de una ronda

```
Admin abre ronda (is_open = true)
  → usuario ve partidos con equipos reales asignados
  → usuario ingresa marcadores → "Guardar progreso" (draft)
  → usuario puede cerrar, volver y editar mientras is_locked = false
  → usuario confirma definitivamente → submission.status = submitted
Admin cierra ronda (is_locked = true)
  → submission.status = locked, edición imposible
Admin carga resultados partido a partido
  → eventos calculan puntos → ranking actualizado en tiempo real
Admin finaliza ronda → se calculan pts_classifier
```

### Reglas de completitud

**R1:** El botón "Confirmar" solo se habilita cuando los 12 grupos tienen las 6 predicciones cada uno (72 predicciones totales). El usuario avanza grupo a grupo pero no puede confirmar hasta completar todos.

**R2–R4:** El usuario debe completar todos los partidos de la ronda antes de poder confirmar.

### Borrador parcial

Los marcadores se guardan automáticamente como borrador (`draft`) al pulsar "Guardar progreso". El usuario puede interrumpir y continuar en cualquier momento. Solo al "Confirmar" el `submitted_at` se registra y el status pasa a `submitted`.

### Validación de marcadores en rondas de eliminación

En R2, R3 y R4 no pueden existir empates. El frontend debe validar que `predicted_home != predicted_away` antes de guardar. Si el usuario ingresa un marcador de empate en una ronda eliminatoria, se muestra error: "En rondas de eliminación debe haber un ganador".

### Partidos con equipos TBD

Los partidos de R2, R3 y R4 tienen `home_team_id` / `away_team_id` nullable hasta que el admin asigna los equipos reales clasificados. El usuario solo puede predecir una ronda cuando todos sus partidos tienen equipos asignados.

### Reapertura por admin

El admin puede resetear el `prediction_submissions.status` de un usuario específico a `draft` para permitirle corregir. Solo debe hacerse antes de que cualquier partido de esa ronda esté `in_progress` o `finished`.

---

## 5. Modelo de Datos

```sql
teams
  id, name, flag_url, fifa_code, group_id

groups
  id, name  -- A, B, C ... L

players
  id, team_id, name  -- solo para predicción de goleador

rounds
  id, name, slug, order
  is_open, is_locked
  points_exact, points_result, points_classifier

matches
  id, round_id, group_id (nullable)
  match_number, match_date
  home_team_id (nullable), away_team_id (nullable)
  home_placeholder, away_placeholder  -- "Ganador Grupo A"
  home_score (nullable), away_score (nullable)  -- marcador a 90 min siempre
  winner_team_id (nullable)  -- el ganador real (puede ser por ET o penales)
  went_to_extra_time (boolean, default false)
  status: scheduled | in_progress | finished

predictions
  id, user_id, match_id
  predicted_home, predicted_away
  pts_exact, pts_result, pts_classifier
  total_points, calculated_at

prediction_submissions
  id, user_id, round_id
  status: draft | submitted | locked
  submitted_at

special_predictions
  id, user_id
  champion_team_id, runner_up_team_id, top_scorer_player_id
  is_locked
  pts_champion, pts_runner_up, pts_top_scorer
  calculated_at

users
  id, name, email, password, avatar
  role: admin | user
  is_active       -- acceso al sistema
  is_activated    -- aportó al pozo de coins
  coins_balance
  total_points    -- cache actualizado por eventos

coin_transactions
  id, user_id, type: credit | debit
  amount, concept, created_at

messages
  id, user_id, content, created_at
```

---

## 6. Arquitectura de Puntos — Event-Driven

### Eventos y Listeners

| Evento | Trigger | Listener |
|---|---|---|
| `MatchScoreUpdated` | Admin actualiza score (in_progress o finished) | `CalculateMatchPoints` |
| `RoundFinalized` | Admin finaliza ronda | `CalculateClassifierPoints` |
| `TournamentFinalized` | Admin cierra torneo | `CalculateSpecialPredictions` |
| `PointsUpdated` | Post-cálculo | Broadcast vía Reverb |

### Lógica de cálculo por partido

```
Para cada prediction del match donde submission.status IN (submitted, locked):

  -- Fase de grupos (pueden terminar en empate)
  pts_exact  = (predicted_home == home_score AND predicted_away == away_score)
               ? round.points_exact : 0
  pts_result = (signo(predicted_home - predicted_away) == signo(home_score - away_score))
               ? round.points_result : 0

  -- Rondas de eliminación (siempre hay ganador)
  -- home_score / away_score = marcador a 90 min
  -- winner_team_id = ganador real (puede ser por ET o penales)
  pts_exact  = (predicted_home == home_score AND predicted_away == away_score)
               ? round.points_exact : 0
               -- acierto de marcador exacto solo contra el resultado a 90 min
  pts_result = (equipo ganador de la prediction == winner_team_id)
               ? round.points_result : 0
               -- acierto de resultado = acertar al ganador real (independiente de ET/penales)

  pts_classifier = calculado separado al finalizar ronda

Actualizar prediction.total_points
Recalcular users.total_points = SUM(predictions.total_points) + special_predictions total
```

**Ejemplo knockout:** Partido termina 1-1 a 90 min, Brasil gana por penales.
- Usuario predijo 1-0 Brasil → pts_exact = 0, pts_result = ✅ (acertó ganador)
- Usuario predijo 1-1 → pts_exact = ✅ (marcador 90 min exacto), pts_result = 0 (no indicó ganador — un marcador exacto empatado en eliminatoria no implica ganador correcto)
- Usuario predijo 2-0 Brasil → pts_exact = 0, pts_result = ✅

### Live scoring

Durante `match.status = in_progress`, cada actualización de score dispara `MatchScoreUpdated`. Los puntos se recalculan y el ranking se actualiza en tiempo real. Los puntos se marcan como **provisionales** en el frontend (indicador 🔴 EN VIVO). Al pasar a `finished`, los puntos se consolidan como definitivos.

### Comando de corrección

```bash
php artisan points:recalculate {--round=} {--match=}
```

Sobreescribe puntos (no acumula). Usado cuando el admin corrige un resultado.

---

## 7. Real-time — Reverb

### Canales

```
private-user.{userId}
  → tus puntos cambiaron
  → tu predicción fue bloqueada
  → ronda abierta para predecir

presence-quinela
  → ranking actualizado (top 10 + posición personal)
  → score en vivo de partido en curso
  → nuevo mensaje de chat
  → alerta de marcador exacto ("¡Juan acertó 2-1!")
  → ronda abierta / cerrada
```

### Chat grupal

Canal único global. Historial: últimos 50 mensajes al cargar (paginado). Nuevos mensajes llegan por broadcast en tiempo real. Sin threads, sin reacciones, sin edición — MVP.

### Broadcasts emitidos

| Broadcast | Canal | Payload |
|---|---|---|
| `MatchScoreUpdated` | presence-quinela | match_id, score, is_live |
| `PointsUpdated` | presence-quinela + private-user | user_id, total_points, position |
| `RoundOpened` | presence-quinela | round name |
| `RoundLocked` | presence-quinela | round name |
| `MessageSent` | presence-quinela | user, content, timestamp |
| `ExactScoreAlert` | presence-quinela | username, match, score |

---

## 8. Módulo Admin

### Setup inicial (una sola vez)

- Cargar 48 equipos con grupo asignado
- Cargar fixture completo (104 partidos con fechas y números)
- Cargar plantillas de jugadores (para predicción de goleador)
- Configurar ventana de predicciones especiales

### Control de rondas

- Abrir ronda → `is_open = true`
- Cerrar ronda → `is_locked = true`
- Asignar equipos reales a partidos TBD (R2, R3, R4)
- Finalizar ronda → dispara `RoundFinalized` → calcula clasificados
- Finalizar torneo → dispara `TournamentFinalized` → calcula predicciones especiales

### Live scoring

- Iniciar partido → `status = in_progress` → broadcast
- Actualizar score → dispara `MatchScoreUpdated` → ranking live
- Finalizar partido → `status = finished` → puntos consolidados

### Gestión de usuarios

- Crear usuario
- Activar acceso → `is_active = true`
- Desactivar acceso → `is_active = false` (sesiones invalidadas)
- Restablecer contraseña → link de reset (Laravel built-in)
- Activar al pozo → `is_activated = true` → +50 coins registrado en `coin_transactions`
- Desactivar del pozo → `is_activated = false` → −50 coins
- Reabrir predicciones → resetear `prediction_submissions.status = draft` por usuario y ronda

### Correcciones

- Editar resultado de partido → ejecutar `points:recalculate --match={id}`
- Recalcular ronda completa → `points:recalculate --round={id}`

---

## 9. Sistema de Coins (base MVP)

- Cada usuario con `is_activated = true` aporta **50 coins** al pozo
- Pozo total: `SELECT COUNT(*) * 50 FROM users WHERE is_activated = true`
- Todos los movimientos quedan registrados en `coin_transactions`
- `users.coins_balance` refleja el saldo individual

**Distribución del pozo:** lógica de premiación y distribución a definir en iteración futura.

---

## 10. Roles y Permisos

| Acción | admin | user |
|---|---|---|
| Live scoring | ✅ | ❌ |
| Gestionar rondas y torneo | ✅ | ❌ |
| Gestionar usuarios | ✅ | ❌ |
| Ver ranking | ✅ | ✅ |
| Predecir | ❌ | ✅ |
| Chat grupal | ✅ | ✅ |
| Ver partidos y resultados | ✅ | ✅ |

---

## 11. Fuera de Scope (MVP)

- Grupos/ligas privadas (posible futura iteración si hay adopción)
- API externa de resultados (se puede agregar scraping como mejora)
- Lógica de distribución del pozo de coins
- Reacciones, threads o edición en el chat
- Notificaciones push / email
- App móvil

---

## Decisiones Técnicas Clave

| Decisión | Elección | Razón |
|---|---|---|
| Cálculo de puntos | Event-driven | Ranking instantáneo, compatible con Reverb live |
| Puntos mid-match | Provisionales en vivo | Engagement sin comprometer datos finales |
| Clasificados R2 | Set de 8 equipos a QF | Más justo que por partido individual |
| Predicciones | Draft + submit explícito | 72 marcadores en R1 requieren guardado parcial |
| Resultados | Admin manual | Sin presupuesto para API externa en MVP |
| Quinela | Global única | MVP simple, grupos/ligas en futura iteración |
