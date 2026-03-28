# Daniela Montes Psicóloga — Project Status (Sandbox)

**Última actualización:** 2026-03-28  
**Repo:** `cristinatroconis-cmd/daniela-web-sandbox`  
**Producción (referencia):** https://danielamontespsic.com/ (rocket.net)  
**Modo de trabajo:** sandbox / aislado — NO tocar producción directo.

---

## 1) Contexto / objetivo de negocio
Negocio terapéutico con foco en:
1. **Recursos** (entrada económica — PDFs, guías)
2. **Escuela** (cursos / talleres / programas — ticket medio)
3. **Servicios** (sesiones / membresías — último nivel / premium)

Objetivo: plataforma de formación psicológica escalable con conversión optimizada (CTAs claros → checkout WooCommerce → acceso Tutor LMS).

---

## 2) Stack técnico activo
- **CMS:** WordPress con tema hijo `daniela-child` (parent: Shoptimizer)
- **Ecommerce:** WooCommerce + Memberships + Subscriptions
- **LMS:** Tutor LMS (cursos activos — no retirar sin migración)
- **Hosting producción:** Rocket.net (no migrar aún)
- **Entorno local:** LocalWP (`dani-backup`) con symlink del theme al repo

---

## 3) Estado actual — Implementado ✅

### 3.1 CPTs (Custom Post Types)
Registrados en `wp-content/themes/daniela-child/inc/cpt.php`:

| CPT | URL | Estado |
|---|---|---|
| `dm_escuela` | `/escuela/` | ✅ Implementado |
| `dm_recurso` | `/recursos/` | ✅ Implementado |
| `dm_servicio` | `/servicios/` | ✅ Implementado |

Con taxonomías internas: `dm_tipo_escuela`, `dm_tipo_recurso`, `dm_tipo_servicio` (**legacy** en `/servicios/`), `dm_tema`.

### 3.2 Templates
Todos en `wp-content/themes/daniela-child/`:

| Archivo | Estado |
|---|---|
| `archive-dm_escuela.php` | ✅ Chips WooCommerce (Ruta A) + grid |
| `archive-dm_recurso.php` | ✅ Chips taxonomía + grid |
| `archive-dm_servicio.php` | ✅ Chips WooCommerce (Ruta A, estricto) + grid |
| `single-dm_escuela.php` | ✅ Imagen + tipo + contenido + CTA Woo |
| `single-dm_recurso.php` | ✅ Implementado |
| `single-dm_servicio.php` | ✅ Implementado |

### 3.3 Helpers (`wp-content/themes/daniela-child/inc/helpers-cpt.php`)
- ✅ `dm_cpt_render_cta()` — botón "Agregar al carrito" con precio
- ✅ `dm_cpt_render_grid()` — tarjetas con lógica dual de enlaces y footer de CTAs
- ✅ `dm_cpt_render_taxonomy_chips()` — chips genéricos para CPTs
- ✅ `dm_escuela_render_woo_chips()` — chips de `/escuela/` basados en categorías WC
- ✅ `dm_escuela_query_args_by_woo_cat()` — filtrado por categoría WC del producto vinculado
- ✅ `dm_servicios_render_woo_chips()` — chips de `/servicios/` basados en categorías WC (`servicios/*`)
- ✅ `dm_servicios_query_args_by_woo_cat_strict()` — filtrado estricto: solo muestra ítems con producto en `servicios/*`

### 3.4 Metaboxes
- ✅ `_dm_wc_product_id` — vincula CPT a producto WooCommerce (en los 3 CPTs)
- ✅ `_dm_tutor_course_url` — URL del curso Tutor (solo `dm_escuela`)

### 3.5 Comportamiento `/escuela/`
- Chips: Todos / Cursos / Talleres / Programas (categorías WooCommerce)
- Tarjeta: imagen + título enlazan a Tutor si existe URL; si no, al single CPT
- Footer de tarjeta: "Ver curso" (Tutor, nueva pestaña) + "Agregar al carrito" (WooCommerce)
- Sin CTAs → footer no se renderiza

### 3.5b Comportamiento `/servicios/` — Ruta A (estricto)
- Chips: Todos / Sesiones / Paquetes / Membresías / Supervisiones (categorías WooCommerce hijas de `servicios`)
- Modo **estricto**: solo aparecen ítems `dm_servicio` cuyo producto vinculado esté en `product_cat servicios/*`
- Sin producto en `servicios/*` → el ítem no aparece en el archive (aunque esté publicado)
- Querystring: `?tipo=<slug>` (ej. `/servicios/?tipo=sesiones`)
- Nota: taxonomía interna `dm_tipo_servicio` es **legacy** (existe en cpt.php, no se usa en UX/chips)

### 3.6 Entorno local
- ✅ Symlink: `$DM_WP/wp-content/themes/daniela-child` → `$DM_REPO/wp-content/themes/daniela-child`
- ✅ Variables en `~/.zshrc`: `DM_REPO` y `DM_WP`
- Flujo: `git pull` en `$DM_REPO` → refrescar navegador (sin rsync)

### 3.7 Sistema UI — Catálogo uniforme (DECISIÓN CERRADA ✅)
- ✅ Se usa **un solo sistema** de cards + grids para todos los catálogos (no hay variantes por CPT).
- ✅ Archives CPT (escuela, recursos, servicios) → `.dm-grid` + `.dm-card` via `dm_cpt_render_grid()`.
- ✅ Grids de producto WooCommerce → `.dm-products-grid` via `inc/dm-products.php`.
- ✅ Estilos centralizados en `style.css`; PHP renderers no llevan layout inline.
- ✅ CTA neutro: "Ver detalles" (excepción: "Ver curso" en `/escuela/` con `_dm_tutor_course_url`).
- ✅ Regla: 3 columnas en desktop (≥1024px), responsivo en tablet/mobile.
- **Motivo:** proyecto low budget — consistencia visual mejora conversión; sistema único reduce mantenimiento.
- **Referencia:** `ARCHITECTURE.md` § 18 + `docs/ARCHITECTURE_NOTES.md` § 3c.

---

## 4) Backlog inmediato 🔲

### Prioridad alta
- [ ] **Sanitizar excerpt en el grid** (`dm_cpt_render_grid` en `inc/helpers-cpt.php`)  
  Algunos excerpts traen HTML/CTAs antiguos. Usar `wp_strip_all_tags()` antes de `wp_trim_words()`.

### Prioridad media
- [ ] **Subitems hover en menú principal**  
  Agregar subitems en WP Admin → Apariencia → Menús:
  - Escuela → Cursos (`/escuela/?tipo=cursos`) / Talleres / Programas
  - Recursos → Gratis (`/recursos/?tipo=gratis`) / Pagos
  - Servicios → Sesiones / Paquetes / Membresías / Supervisiones (Woo categories hijas de `servicios`)
  
- [ ] **Replicar Ruta A en Recursos (transversal)** — próximo paso sugerido  
  Estructurar categorías Woo padres `recursos` y `temas` para que `/recursos/` filtre igual que `/servicios/` (Ruta A o variante flexible).  
  Decidir slugs de padres y hijas antes de tocar código.
  
- [ ] **Auditar gating de acceso** (Tutor vs Memberships/Subscriptions)  
  Confirmar quién controla el acceso post-compra y documentarlo.  
  Decidir si se agrega "Ir al curso" en la thank-you page de WooCommerce.

### Prioridad baja / futuro
- [ ] **UX checkout** — reducir fricción en `/checkout/`: deshabilitar scripts innecesarios (Elementor, Slider Revolution, etc.) vía hooks en `functions.php`
- [ ] **Sección "¿Qué necesitas?" en HOME** — grid de 4 tarjetas de orientación al usuario
- [ ] **Email automation** — integración MailerLite post-compra
- [ ] **Flush de rewrite rules** tras activar CPTs en entorno nuevo  
  `WP Admin → Ajustes → Enlaces permanentes → Guardar cambios` (o `wp rewrite flush` con WP-CLI)

---

## 5) Riesgos activos

| Riesgo | Estado |
|---|---|
| Página estática con slug `/escuela/` o `/recursos/` | ⚠️ Verificar que no existe como Page (bloquea el archive CPT) |
| Doble gating Tutor + Memberships | ⚠️ Pendiente auditoría |
| Excerpts con HTML antiguo | ⚠️ Pendiente sanitización (ver backlog 4.1) |
| WP File Manager instalado | ⚠️ Revisar necesidad/riesgo de seguridad |
| Symlink `daniela-child` en LocalWP | ✅ Activo — si se pierde, recrear con `ln -s` |

---

## 6) Flujo de trabajo estándar

### Actualizar código desde GitHub
```bash
# A) En el repo
cd "$DM_REPO"
git checkout main
git pull --no-rebase origin main

# B) Ver en LocalWP
# Solo refrescar navegador (Cmd+Shift+R)
# El symlink hace que LocalWP ya use el código actualizado.
```

### Si el archive CPT da 404 después de cambiar slugs
```bash
# Opción 1: WP Admin → Ajustes → Enlaces permanentes → Guardar cambios
# Opción 2: WP-CLI
cd "$DM_WP" && wp rewrite flush
```

### Verificar que el symlink existe
```bash
ls -la "$DM_WP/wp-content/themes" | grep daniela-child
```

---

## 7) Archivos del child theme (mapa rápido)

```
wp-content/themes/daniela-child/
├── functions.php                   # Bootstrap: carga inc/cpt.php, inc/helpers-cpt.php, etc.
├── style.css                       # Declaración del child theme + estilos dm-card/dm-btn/dm-chips
├── inc/
│   ├── cpt.php                     # Registra CPTs y taxonomías
│   ├── helpers-cpt.php             # Metaboxes, CTA, chips, grid
│   ├── shortcodes-escuela.php      # Shortcodes WooCommerce para páginas de Escuela
│   ├── shortcodes-servicios.php    # Shortcodes para páginas de Servicios
│   └── ...                         # Otros módulos (assets, header, checkout, etc.)
├── archive-dm_escuela.php          # Template archive /escuela/
├── archive-dm_recurso.php          # Template archive /recursos/
├── archive-dm_servicio.php         # Template archive /servicios/
├── single-dm_escuela.php           # Template single /escuela/<slug>/
├── single-dm_recurso.php           # Template single /recursos/<slug>/
└── single-dm_servicio.php          # Template single /servicios/<slug>/
```
