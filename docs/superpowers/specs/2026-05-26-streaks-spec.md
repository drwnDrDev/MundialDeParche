# Rachas y Perfect Matches — Spec (Paso futuro)

**Contexto:** El usuario quiere trackear rachas de predicciones correctas consecutivas y/o "perfect matches" (exactos). Actualmente la lógica de puntos existe (pts_exact, pts_result) pero no se almacena un contador de racha.

---

## Tipos de racha propuestos

### 1. Racha de ganadores (`winner_streak`)
Partidos consecutivos (ordenados por match_date) donde `pts_result > 0` (acertó el ganador, puede o no ser exacto).

### 2. Racha de exactos (`exact_streak`)
Partidos consecutivos donde `pts_exact > 0` (marcador exacto). Más difícil — podría ser una racha "perfect".

### 3. Perfect Matches (`exact_count` acumulado)
Total histórico de marcadores exactos del usuario. Más simple, sin lógica de consecutividad.

---

## Implementación propuesta

### Opción A: Computar on-the-fly (MVP)
Consultar predicciones del usuario ordenadas por `match_date DESC`, iterar hasta encontrar una sin pts_result > 0.

**Pro:** Sin migración. **Con:** N+1 si no se optimiza; costoso para usuarios con muchas predicciones.

### Opción B: Columnas en `users` (recomendado)
Agregar a `users`:
- `winner_streak` int default 0
- `exact_count` int default 0

Actualizar en `CalculateMatchPoints` listener después de guardar pts:
```php
// Al procesar cada predicción con pts_result > 0 → incrementar winner_streak
// Al procesar pts_result = 0 → resetear winner_streak a 0
// Al procesar pts_exact > 0 → incrementar exact_count
```

**Pro:** O(1) lectura en Home. **Con:** Requiere migración + modificar listener.

---

## Display en Home (StatCard)
```jsx
<StatCard label="RACHA" value={`🔥${winnerStreak}`} sub="ganadores" color="yel" icon="boot" />
```
O alternativamente:
```jsx
<StatCard label="EXACTOS" value={exactCount} sub="marcadores" color="teal" icon="ball" />
```

---

## Prerequisitos para implementar
1. Decidir Opción A o B
2. Si B: migración `add_streak_columns_to_users_table`
3. Modificar `CalculateMatchPoints` listener
4. Agregar al `HomeController` al construir los props
5. Actualizar la StatCard correspondiente en `ScreenHome`
