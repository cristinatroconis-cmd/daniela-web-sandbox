# Recursos Importer — Guía de uso

Este documento describe el flujo de importación de recursos PDF (handouts) desde la Media Library de WordPress hacia WooCommerce + CPT `dm_recurso`, el sistema de entrega de freebies tokenizados y cómo crear bundles.

---

## 1. Comando WP-CLI

### Archivos

| Archivo | Propósito |
|---|---|
| `wp-content/themes/daniela-child/inc/cli-import-recursos.php` | Importer principal (cargado vía `functions.php` → `plugins_loaded`) |
| `wp-content/themes/daniela-child/inc/cli-import.php` | Implementación alternativa standalone; ejecutar directamente con `wp eval-file` si se prefiere |

### Uso

```bash
# Importar todos los adjuntos PDF/MP3/M4A de la Media Library
wp dm import-recursos

# Simulación (no escribe nada, solo informa qué haría)
wp dm import-recursos --dry-run

# Forzar actualización de productos/CPTs ya importados
wp dm import-recursos --force-update
```

### Qué hace por cada attachment elegible

1. Deriva un **título limpio** a partir del título del attachment (o del nombre de archivo si el título está vacío).
2. Detecta si el recurso es **gratuito** (ver sección 2).
3. Clasifica el recurso en **temas** (`dm_tema` + `product_tag`) a partir de palabras clave del título.
4. **Crea o actualiza** un producto WooCommerce (simple, descargable):
   - `_dm_source_attachment_id` → ID del attachment (clave de idempotencia).
   - Archivo descargable apunta a la URL del attachment.
   - Precio según regla free/paid.
   - `product_cat` asignada: `recursos`.
   - `product_tag` asignadas: temas detectados por palabras clave (ver sección 3).
5. **Crea o actualiza** un CPT `dm_recurso`:
   - Título y excerpt sincronizados con el producto.
   - `_dm_wc_product_id` → ID del producto WC vinculado.
   - `_dm_source_attachment_id` → misma clave de idempotencia.
   - Términos `dm_tema` asignados.

### Idempotencia

El importer es **idempotente**: si ya existe un producto o CPT con el mismo `_dm_source_attachment_id`, **no se duplica**; se salta (o actualiza con `--force-update`).

Ejecutarlo múltiples veces sobre los mismos archivos produce el mismo resultado.

---

## 2. Regla free vs paid

> **Única regla:** si el título del recurso contiene la palabra **"gratuito"** (insensible a mayúsculas/minúsculas) → precio **$0**.
> En cualquier otro caso → precio **$5** (o $9 para bundles, ver sección 4).

### Ejemplos

| Título del attachment | Precio |
|---|---|
| `Diario de emociones gratuito` | $0 |
| `DIARIO DE EMOCIONES GRATUITO` | $0 |
| `Ficha de autorregistro` | $5 |
| `Afirmaciones para la autoestima — Pack completo` | $9 (bundle) |
| `Guía gratis de respiración` | $5 ← "gratis" no activa la regla; solo "gratuito" |

**Nota:** las palabras "gratis", "free", "gratuita" **no** activan la regla. Solo "gratuito".

Para marcar un recurso como gratuito, incluir la palabra "gratuito" en el título del attachment en la Media Library (o en el nombre del archivo PDF antes de subirlo).

---

## 3. Clasificación de temas (`dm_tema` + `product_tag`)

El importer detecta los temas automáticamente a partir de palabras clave en el título del attachment.

| Palabra clave en título | Tag / dm_tema asignado |
|---|---|
| autoestima | `autoestima` |
| ansiedad | `ansiedad` |
| respiraci… (respiración…) | `respiracion` |
| meditaci… | `meditacion` |
| afirmaci… (afirmación…) | `afirmaciones` |
| mindfulness | `mindfulness` |
| depresi… | `depresion` |
| duelo | `duelo` |
| trauma | `trauma` |
| relajaci… | `relajacion` |
| emociones / emocion | `emociones` |
| critica (autocrítica) | `autocritica` |
| estres (estrés) | `estres` |
| pareja | `pareja` |
| limites (límites) | `limites` |
| habitos (hábitos) | `habitos` |

Los términos `dm_tema` y `product_tag` se crean automáticamente si no existen.

---

## 4. Bundles

### Detección automática

Un attachment cuyos título o nombre de archivo contenga la palabra **"afirmaciones"** (insensible a mayúsculas) se considera parte de la **familia bundle "Afirmaciones"**.

Cuando hay **2 o más** attachments en esa familia, el importer los agrupa en un **único producto bundle** con todos los archivos como descargas:

- Título: `Afirmaciones — Pack completo`
- Precio: **$9** (editable en WP Admin)
- Tags: `bundle` + los temas detectados en los miembros del pack

### Crear un bundle manualmente (WP Admin)

1. Sube los PDFs del bundle a la Media Library con títulos que contengan "afirmaciones".
2. Ejecuta `wp dm import-recursos` (o usa el fallback admin: `/wp-admin/?dm_import_recursos=1`).
3. El importer detecta la familia y crea el bundle.
4. En **WooCommerce → Productos**, busca el bundle creado y ajusta:
   - Precio (si quieres cambiar el default de $9).
   - Imagen destacada.
   - Descripción larga.
5. El CPT `dm_recurso` vinculado se crea automáticamente con el `_dm_wc_product_id` correcto.

### Fallback admin (sin WP-CLI)

Si el servidor no tiene WP-CLI disponible, visita como administrador:

```
/wp-admin/?dm_import_recursos=1
/wp-admin/?dm_import_recursos=1&dry_run=1       ← solo simula
/wp-admin/?dm_import_recursos=1&force_update=1  ← fuerza actualización
```

Los resultados aparecen como un aviso de administrador en el dashboard.

---

## 5. Entrega de freebies (link tokenizado)

### Flujo

1. El visitante llega al single de un `dm_recurso` cuyo producto vinculado tiene precio $0.
2. Ve el shortcode `[dm_freebie_form product_id="X"]` renderizado automáticamente por `single-dm_recurso.php`.
3. Introduce su email + checkbox de opt-in newsletter (GDPR: no pre-marcado).
4. Se genera un **token hex de 64 caracteres** único para `(email, product_id)`.
5. Se envía un email con el link `?dm_freebie_token=<token>`.
6. Al hacer clic, el token se valida y se entrega el archivo. El contador de descargas se incrementa.
7. Al llegar a **10 descargas**, el link queda bloqueado y se muestra un mensaje para solicitar uno nuevo.

### Tabla de base de datos

La tabla `{prefix}dm_freebie_tokens` se crea automáticamente en `init`:

| Columna | Tipo | Descripción |
|---|---|---|
| `token` | VARCHAR(64) | Token hex (clave primaria) |
| `email` | VARCHAR(200) | Email del solicitante |
| `product_id` | BIGINT | ID del producto WC |
| `created_at` | DATETIME | Fecha de creación |
| `expires_at` | DATETIME | Expiración (NULL = sin límite) |
| `download_count` | INT | Descargas realizadas |
| `max_downloads` | INT DEFAULT 10 | Límite de descargas |
| `newsletter_optin` | TINYINT | Consentimiento newsletter (1/0) |

### Shortcode manual

```
[dm_freebie_form product_id="123"]
[dm_freebie_form product_id="123" title="Recíbelo gratis" button_text="Enviarme el PDF"]
```

Útil para colocar el formulario en cualquier página o post, no solo en el single del CPT.

---

## 6. Probar localmente (LocalWP)

### Variables de entorno

Añade a `~/.zshrc` (una sola vez):

```bash
export DM_REPO="/Users/cristinatroconis/Desktop/daniela-web-sandbox"
export DM_WP="/Users/cristinatroconis/Local Sites/dani-backup/app/public"
```

El theme `daniela-child` en LocalWP es un **symlink** al directorio del repo, por lo que cualquier cambio en `$DM_REPO/wp-content/themes/daniela-child/` se refleja inmediatamente en LocalWP sin necesidad de sincronizar.

### Flujo de prueba local

```bash
# 1. Actualizar código
cd "$DM_REPO"
git pull --no-rebase origin main

# 2. Verificar que el symlink sigue activo
ls -la "$DM_WP/wp-content/themes" | grep daniela-child

# 3. Ejecutar el importer en modo simulación
wp --path="$DM_WP" dm import-recursos --dry-run

# 4. Ejecutar el importer real
wp --path="$DM_WP" dm import-recursos

# 5. Comprobar los productos creados
wp --path="$DM_WP" post list --post_type=product --fields=ID,post_title,post_status

# 6. Comprobar los CPTs creados
wp --path="$DM_WP" post list --post_type=dm_recurso --fields=ID,post_title,post_status
```

### Subir PDFs de prueba

1. En LocalWP abre WP Admin → Medios → Añadir nuevo.
2. Sube un PDF con título como `Diario de respiración gratuito.pdf` (o renómbralo en la Media Library).
3. Ejecuta `wp dm import-recursos --dry-run` y verifica la salida.
4. Ejecuta sin `--dry-run` y revisa WooCommerce → Productos y Recursos CPT.

### Probar la entrega de freebies

1. Asegúrate de que `WP_DEBUG` está activo en `wp-config.php` de LocalWP para ver errores.
2. Navega a `/recursos/` y selecciona un recurso gratuito.
3. Introduce un email en el formulario y envía.
4. Revisa el log de emails de LocalWP (pestaña **MailHog** en la app LocalWP) para ver el link tokenizado.
5. Haz clic en el link y comprueba que el archivo se descarga.
6. Repite hasta 10 veces con el mismo link y comprueba que al 11º intento aparece el mensaje de límite alcanzado.
