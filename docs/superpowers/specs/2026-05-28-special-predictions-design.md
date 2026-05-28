# Spec: Special Predictions UI Redesign

**Fecha:** 2026-05-28
**Alcance:** Rediseño completo de `Predictions/Special.jsx` al design system pop-art + campo goleador custom + backend para jugador libre.

---

## Contexto

`Special.jsx` es la única pantalla de predicciones que aún usa `AuthenticatedLayout` con Tailwind genérico. Necesita portar al design system pop-art igual que el resto de las pantallas (`MobileShell`, `TabBar`, tokens pop-art). Adicionalmente, se agrega la feature #5 (Sub-proyecto C): goleador custom si el jugador no está en la lista.

---

## Layout general

- `MobileShell` + `TabBar active="home"`
- Header idéntico al de otras pantallas de predicciones:
  ```
  MUNDIAL 2026          [XX PTS TOTALES]
  MIS ESPECIALES
  ```
- Cromo navy debajo del header (igual patrón a `Round.jsx`) con:
  - Label: `PREDICCIONES ESPECIALES`
  - Sub: `Campeón · Sub-campeón · Goleador`
  - Badge de estado: `🔒 BLOQUEADO` (teal) o `ABIERTO`
  - Tres chips de puntos: `+30 CAMPEÓN`, `+10 SUB`, `+15 GOLEADOR`

---

## Sección 1 & 2: Picker de equipos (Campeón y Sub-campeón)

Dos secciones idénticas en estructura, separadas por divisor.

### Header de sección
```
CAMPEÓN DEL MUNDO                    [+30 PTS]
Elige el equipo que ganará el torneo
```
(Subcampeón: misma estructura con label y puntos propios)

### TeamCard — grid 4 columnas
Cada tarjeta muestra:
- Imagen de bandera (`flag_url`) cuadrada, full-width dentro de la card
- Nombre del equipo en `font-display text-[10px]` centrado debajo
- Border `2px solid ink`, sombra `pop-sm`

**Estados:**
- **Normal:** fondo cream, borde ink
- **Seleccionado:** borde amarillo grueso (`border-pop-yel border-[3px]`), overlay checkmark negro centrado sobre la bandera
- **Deshabilitado** (el equipo ya elegido en la otra sección): opacidad 40%, `cursor-not-allowed`, no clickeable

### Modo locked
- Solo el equipo elegido se muestra a full opacidad; los demás a opacidad 15%
- `PtsChip` al lado del nombre mostrando puntos ganados (`pts_champion` / `pts_runner_up`)

---

## Sección 3: Picker de goleador

### Header de sección
```
GOLEADOR DEL TORNEO                  [+15 PTS]
Elige el jugador que más goles meta
```

### Dropdown estilizado
- `<select>` con clases pop-art: borde ink 2px, fondo cream, `font-display`, flecha custom
- Opciones agrupadas por equipo: `<optgroup label="ARGENTINA">` con los jugadores del equipo
- Última opción: `➕ Otro jugador...` (value `"__custom__"`)

### Campo custom (aparece cuando se selecciona "Otro jugador…")
Dos inputs inline que aparecen debajo del dropdown:
1. **Nombre del jugador** — `<input type="text">` con placeholder `"Nombre del jugador"`, estilo pop-art
2. **Equipo** — `<select>` con la lista de 48 equipos, placeholder `"— Equipo —"`, mismo estilo pop-art

Ambos campos son requeridos si se activa el modo custom. Al guardar, el backend hace `Player::firstOrCreate(['name' => $name, 'team_id' => $teamId])` y usa el ID resultante como `top_scorer_player_id` normal — el jugador queda registrado en la tabla `players` y disponible para todos.

### Modo locked
- Muestra nombre del jugador (de la lista o custom) + equipo en cromo pequeño
- `PtsChip` con puntos ganados (`pts_top_scorer`)

---

## Sección 4: CTA sticky y resumen locked

### Modo editable — barra sticky al fondo
- Si todos los campos llenos: botón rojo `GUARDAR MIS ESPECIALES →` activo
- Si falta alguno: botón deshabilitado + mensaje `"Faltan X predicciones"`
- Post-save: flash `✓ GUARDADO` en teal (via `usePage().props.flash`)

### Modo locked — cromo resumen al final de la página (no sticky)
```
PUNTOS ESPECIALES
CAMPEÓN:      [PtsChip]
SUB-CAMPEÓN:  [PtsChip]
GOLEADOR:     [PtsChip]
TOTAL:        XX PTS
```

---

## Cambios de backend

### Sin migración necesaria
No se agregan columnas a `special_predictions`. El jugador custom se convierte en un `Player` real mediante `firstOrCreate`.

### `SpecialPredictionController@save` — validación actualizada
- Request acepta: `top_scorer_player_id` (ID existente) **OR** (`top_scorer_custom_name` + `top_scorer_custom_team_id`) para el path custom
- Si viene path custom: `Player::firstOrCreate(['name' => $name, 'team_id' => $teamId])` → usar el ID resultante como `top_scorer_player_id`
- Validación: exactamente uno de los dos paths requerido; `top_scorer_custom_team_id` debe existir en `teams`

### `SpecialPredictionController@show`
- Pasar `special` con `top_scorer_player_id` cargado con `player.name` y `player.team.name` al frontend (sin cambios adicionales)

### `CalculateSpecialPredictions` — sin cambio
- Lógica existente funciona sin modificación: compara `top_scorer_player_id` directamente. El jugador custom ya tiene ID real.

---

## Componentes nuevos

| Componente | Ubicación | Descripción |
|---|---|---|
| `TeamPickerGrid` | inline en `Special.jsx` | Grid 4-col de TeamCards con estado selected/disabled |
| `TeamCard` | inline en `Special.jsx` | Tarjeta individual de equipo con bandera + nombre |
| `GoalScorerPicker` | inline en `Special.jsx` | Dropdown agrupado + lógica custom |

Todos son componentes internos de `Special.jsx` (no extraídos a `composed/`) ya que son específicos de esta pantalla.

---

## No incluido en este plan

- Deduplicación avanzada de jugadores custom (ej. variantes de nombre — queda en manos del admin via Plan 6b)
- Validación de que campeón ≠ subcampeón en el frontend (ya existe en backend; agregar validación visual)
- Animaciones de selección
