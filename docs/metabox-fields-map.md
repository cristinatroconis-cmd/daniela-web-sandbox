# Mapa de campos de metaboxes

> Edita los valores de `help` vacíos (`help:`) y dime cuáles quieres aplicar.  
> Los campos `media`, `color` y `checkbox` no usan `placeholder`.

---

## 1. Secciones del single tipo landing
**Metabox:** `dm_editorial_sections` · **Post types:** `dm_recurso`, `dm_escuela`, `dm_servicio`  
**Archivo:** `inc/helpers-cpt.php` → `dm_cpt_editorial_fields_config()`

---

### `_dm_single_hero_image_url`
- **label:** Imagen hero del single
- **type:** media
- **help:** Selecciona una imagen desde medios. Imagen vertical `1200x500px` o similar.

---

### `_dm_editorial_hero_kicker`
- **label:** Texto superior del hero
- **type:** text
- **placeholder:** Aprende a regular tu mente y tu cuerpo desde la raíz
- **help:** Escribe texto corto llamando a la acción

---

### `_dm_editorial_hero_intro`
- **label:** Bajada del hero
- **type:** textarea
- **placeholder:** Deja de luchar con...
- **help:** Texto breve de 1 línea para explicar el beneficio principal.

---

### `_dm_editorial_hero_button_label`
- **label:** Texto botón hero
- **type:** text
- **placeholder:** Agregar al carrito
- **help:** Escribe el texto del botón.

---

### `_dm_editorial_fit_title`
- **label:** Título primera sección
- **type:** text
- **placeholder:** Es para ti si...
- **help:** Escribe título descriptivo de los items a colocar

---

### `_dm_editorial_fit_title_image`
- **label:** Imagen título primera sección 
- **type:** media
- **help:** Opcional. Elige media de título editorial. Imagen tamaños `450x100px` o `900x200px` para pantallas retina.

---

### `_dm_editorial_fit_items`
- **label:** Items de primera sección (uno por línea)
- **type:** textarea
- **placeholder:** Sabes que tus pensamientos...\nVives en estado de alerta...
- **help:** Escribe un item por línea. Ideal 6–14 palabras por item.

---

### `_dm_editorial_learn_title`
- **label:** Título segunda sección
- **type:** text
- **placeholder:** Qué vas a aprender
- **help:** Escribe título descriptivo de los items a colocar

---

### `_dm_editorial_learn_title_image`
- **label:** Imagen título segunda sección
- **type:** media
- **help:** Opcional. Elige media de título editorial. Imagen tamaños `450x100px` o `900x200px` para pantallas retina.

---

### `_dm_editorial_learn_intro`
- **label:** Texto corto segunda sección
- **type:** textarea
- **placeholder:** No es solo otro curso...
- **help:** Texto breve para introducir la lista de aprendizajes (1–2 líneas).

---

### `_dm_editorial_learn_items`
- **label:** Lista (uno por línea)
- **type:** textarea
- **placeholder:** A entender qué te pasa...\nA calmar tu cuerpo...
- **help:** Escribe un item por línea. Ideal 6–14 palabras por item.

---

### `_dm_editorial_learn_image`
- **label:** Imagen sección aprendizaje
- **type:** media
- **help:** Imagen vertical `1200x500px` o similar.

---

### `_dm_editorial_learn_button_label`
- **label:** Texto botón sección aprendizaje
- **type:** text
- **placeholder:** Agregar al carrito
- **help:** Escribe el texto del botón.

---

### `_dm_editorial_diff_title`
- **label:** Título sección "Qué hace diferente..."
- **type:** text
- **placeholder:** ¿Qué hace diferente a este proceso?
- **help:** Escribe título descriptivo de los items a colocar

---

### `_dm_editorial_diff_title_image`
- **label:** Imagen título sección diferenciadores
- **type:** media
- **help:** Opcional. Si eliges una imagen, reemplaza al título escrito en el frontend.

---

### `_dm_editorial_diff_items`
- **label:** Lista diferenciadores (uno por línea)
- **type:** textarea
- **placeholder:** Te explico solo lo necesario...\nDiseñado para pocos minutos...
- **help:** Un diferenciador por línea. Enfócate en beneficios claros y concretos.

---

### `_dm_editorial_diff_image`
- **label:** Imagen sección diferenciadores
- **type:** media
- **help:** Selecciona la imagen desde la biblioteca de medios.

---

### `_dm_editorial_diff_button_label`
- **label:** Texto botón sección diferenciadores
- **type:** text
- **placeholder:** Agregar al carrito
- **help:** Escribe el texto del botón.

---

### `_dm_editorial_include_title`
- **label:** Título sección "Incluye"
- **type:** text
- **placeholder:** 4 módulos
- **help:** Escribe título descriptivo de los items a colocar

---

### `_dm_editorial_include_title_image`
- **label:** Imagen título sección "Incluye"
- **type:** media
- **help:** Opcional. Si eliges una imagen, reemplaza al título escrito en el frontend.

---

### `_dm_editorial_include_items`
- **label:** Lista "Incluye" (uno por línea)
- **type:** textarea
- **placeholder:** Clases en video...\nRecursos descargables...
- **help:** Un item por línea (qué recibe la persona: videos, audios, PDFs, soporte, etc.).

---

### `_dm_editorial_final_title`
- **label:** Título CTA final
- **type:** text
- **placeholder:** Si esto resonó contigo...
- **help:** Texto corto emotivo

---

### `_dm_editorial_final_title_image`
- **label:** Imagen título CTA final
- **type:** media
- **help:** Opcional.Imagen tamaños `450x100px` o `900x200px` para pantallas retina.

---

### `_dm_editorial_final_text`
- **label:** Texto CTA final
- **type:** textarea
- **placeholder:** Estás a un solo paso...
- **help:** Texto de 1–3 líneas para cerrar y reforzar el beneficio con claridad.

---

### `_dm_editorial_final_button_label`
- **label:** Texto botón CTA final
- **type:** text
- **placeholder:** Agregar al carrito
- **help:** Escribe el texto del botón.

---

## 2. Producto WooCommerce relacionado
**Metabox:** `dm_wc_product` · **Post types:** `dm_recurso`, `dm_escuela`, `dm_servicio`  
**Archivo:** `inc/helpers-cpt.php` → `dm_cpt_wc_metabox_html()` (HTML inline, no array config)

### `dm_wc_product_id`
- **label:** ID del producto en WooCommerce
- **type:** number
- **placeholder:** Ej: 123
- **help:** _(nota fija en HTML: "Los temas del CPT se sincronizan automáticamente desde los tags de este producto…")_

---

## 3. Curso Tutor (URL)
**Metabox:** `dm_tutor_course_url` · **Post type:** solo `dm_escuela`  
**Archivo:** `inc/helpers-cpt.php` → `add_action('add_meta_boxes', ...)` anónimo (HTML inline)

### `dm_tutor_course_url_field`
- **label:** Pega el path del curso
- **type:** text
- **placeholder:** /courses/tumenteencalma/
- **help:** 

---

## 4. Admin de Productos WooCommerce
**Post type:** `product`  
**Archivo:** `inc/helpers-cpt.php` → `dm_render_product_catalog_excerpt_metabox()` (HTML inline)

> Los metaboxes nativos de WooCommerce (Product data, memberships, categorías, tags) no están en el tema hijo.

### Excerpt que se muestra en el catálogo (`post_excerpt`)
- **type:** textarea
- **placeholder:** _(vacío actualmente)_
- **help:** Texto de 2-3 líneas. Este texto se usa en las tarjetas de catálogo y archives del sitio. _(ya fijo en HTML)_


### Imagen que se muestra en el catálogo (`_thumbnail_id`)
- **type:** media
- **help:** Imagen horizontal `1600x900px` o `1280x720px`.

---

## 5. Home — sección "¿Qué necesitas?" (página de inicio)
**Metabox:** `dm_home_necesitas_content` · **Post type:** `page` (solo la Home)  
**Archivo:** `inc/home-necesitas-admin.php` → `dm_home_necesitas_front_fields_config()`

---

### `_dm_home_necesitas_kicker`
- **label:** Texto pequeño superior
- **type:** text
- **placeholder:** Un espacio para ubicarte con calma
- **help:** 

---

### `_dm_home_necesitas_title`
- **label:** Título principal
- **type:** text
- **placeholder:** ¿Dónde estás parada hoy?
- **help:** 

---

### `_dm_home_necesitas_title_image`
- **label:** Imagen del título principal
- **type:** media
- **help:** Opcional. Si eliges una imagen desde Medios, reemplaza el título escrito en el frontend. Si la dejas vacía, se usa el título de texto.

---

### `_dm_home_necesitas_lead`
- **label:** Bajada / descripción
- **type:** textarea
- **placeholder:** Elige el camino que mejor encaja con lo que estás viviendo ahora mismo.
- **help:** 

---

### `_dm_home_necesitas_note`
- **label:** Texto destacado inferior
- **type:** textarea
- **placeholder:** Opcional. Una frase corta que refuerce el mensaje de la izquierda.
- **help:** 

---

### `_dm_home_necesitas_image`
- **label:** Imagen decorativa opcional de la izquierda
- **type:** media
- **help:** Opcional. Puedes elegir una ilustración o foto desde Medios para reforzar visualmente la columna izquierda.

---

### `_dm_home_card_recursos_title`
- **label:** Título tarjeta Recursos
- **type:** text
- **placeholder:** Quiero herramientas prácticas
- **help:** 

---

### `_dm_home_card_recursos_text`
- **label:** Texto corto tarjeta Recursos
- **type:** textarea
- **placeholder:** PDFs, guías y registros para trabajar hoy mismo, a tu ritmo.
- **help:** 

---

### `_dm_home_card_recursos_image`
- **label:** Imagen de la tarjeta Recursos
- **type:** media
- **help:** Selecciona una imagen desde Medios para esta tarjeta.

---

### `_dm_home_card_recursos_bg`
- **label:** Color de fondo tarjeta Recursos
- **type:** color
- **default:** #ead2ac
- **help:** Elige el color de fondo para esta tarjeta.

---

### `_dm_home_card_escuela_title`
- **label:** Título tarjeta Escuela
- **type:** text
- **placeholder:** Quiero aprender de forma guiada
- **help:** 

---

### `_dm_home_card_escuela_text`
- **label:** Texto corto tarjeta Escuela
- **type:** textarea
- **placeholder:** Formación online a tu ritmo o en vivo, en comunidad.
- **help:** 

---

### `_dm_home_card_escuela_image`
- **label:** Imagen de la tarjeta Escuela
- **type:** media
- **help:** Selecciona una imagen desde Medios para esta tarjeta.

---

### `_dm_home_card_escuela_bg`
- **label:** Color de fondo tarjeta Escuela
- **type:** color
- **default:** #ad8fb7
- **help:** Elige el color de fondo para esta tarjeta.

---

### `_dm_home_card_servicios_title`
- **label:** Título tarjeta Servicios
- **type:** text
- **placeholder:** Quiero acompañamiento profesional
- **help:** 

---

### `_dm_home_card_servicios_text`
- **label:** Texto corto tarjeta Servicios
- **type:** textarea
- **placeholder:** Te ofrezco mis servicios de terapia.
- **help:** 

---

### `_dm_home_card_servicios_image`
- **label:** Imagen de la tarjeta Servicios
- **type:** media
- **help:** Selecciona una imagen desde Medios para esta tarjeta.

---

### `_dm_home_card_servicios_bg`
- **label:** Color de fondo tarjeta Servicios
- **type:** color
- **default:** #eaefbd
- **help:** Elige el color de fondo para esta tarjeta.

---

### `_dm_home_card_temas_title`
- **label:** Título tarjeta Temas
- **type:** text
- **placeholder:** No sé bien qué necesito
- **help:** 

---

### `_dm_home_card_temas_text`
- **label:** Texto corto tarjeta Temas
- **type:** textarea
- **placeholder:** Cuéntame qué estás sintiendo y encuentra lo que mejor encaja.
- **help:** 

---

### `_dm_home_card_temas_image`
- **label:** Imagen de la tarjeta Temas
- **type:** media
- **help:** Selecciona una imagen desde Medios para esta tarjeta.

---

### `_dm_home_card_temas_bg`
- **label:** Color de fondo tarjeta Temas
- **type:** color
- **default:** #c97f72
- **help:** Elige el color de fondo para esta tarjeta.

---

## 6. Home — sección "¿Qué necesitas?" (páginas archivo: recursos, escuela, servicios, temas)
**Metabox:** `dm_home_necesitas_content` · **Post type:** `page` (páginas archivo)  
**Archivo:** `inc/home-necesitas-admin.php` → `dm_home_necesitas_card_fields_config()`

---

### `_dm_home_card_kicker`
- **label:** Texto pequeño de la tarjeta
- **type:** text
- **placeholder:** Opcional
- **help:** 

---

### `_dm_home_card_title`
- **label:** Título de la tarjeta en Home
- **type:** text
- **placeholder:** Quiero herramientas prácticas
- **help:** 

---

### `_dm_home_card_text`
- **label:** Texto corto de la tarjeta en Home
- **type:** textarea
- **placeholder:** Describe en 1–2 líneas qué encuentra la persona en esta sección.
- **help:** 

---

### `_dm_home_card_image`
- **label:** Imagen de la tarjeta en Home
- **type:** media
- **help:** Opcional. Si eliges una imagen aquí, reemplaza la imagen por defecto en la tarjeta de Home.

---

### `_dm_home_card_bg`
- **label:** Color de fondo de la tarjeta en Home
- **type:** color
- **default:** #f4f0eb
- **help:** Opcional. Si eliges un color aquí, reemplaza el color de fondo por defecto de la tarjeta.

---

## 7. DM Newsletter (página de ajustes WooCommerce)
**Archivo:** `inc/admin-settings.php` → `DM_Settings_Page::get_settings()`  
> Estos campos usan la Settings API de WC. El campo de ayuda se llama `desc` (no `help`). Con `desc_tip: true` se convierte en tooltip (?).

---

### `dm_newsletter_optin_label`
- **title:** Texto del checkbox
- **type:** textarea
- **default:** Acepto recibir recursos y novedades de Daniela Montes Psicóloga por email. Puedo darme de baja en cualquier momento.
- **desc:** Mensaje de consentimiento mostrado al cliente en el checkout.

---

### `dm_downloads_email_button_text`
- **title:** Texto del botón
- **type:** text
- **placeholder:** Descargar recurso
- **default:** Descargar recurso
- **desc:** Etiqueta principal de descarga mostrada en el email Pedido completado.

---

### `dm_downloads_email_cta_title`
- **title:** Título CTA en correos WooCommerce
- **type:** text
- **placeholder:** ⬇️ Accede a tu descarga
- **default:** ⬇️ Accede a tu descarga
- **desc:** Texto mostrado encima de los botones de descarga en el email Pedido completado.

---

### `dm_downloads_email_cta_note`
- **title:** Nota CTA en correos WooCommerce
- **type:** text
- **placeholder:** Los enlaces de descarga tienen un límite de usos y tiempo de validez.
- **default:** Los enlaces de descarga tienen un límite de usos y tiempo de validez.
- **desc:** Texto pequeño mostrado debajo de los botones de descarga.

---

### `dm_mailerlite_fallback_enabled`
- **title:** Activar API fallback
- **type:** checkbox
- **default:** no
- **desc:** Habilitar integración directa con la API de MailerLite desde el tema hijo.

---

### `dm_mailerlite_api_key`
- **title:** API Key de MailerLite
- **type:** password
- **desc:** Se guarda encriptada. No compartas esta clave.

---

### `dm_mailerlite_group_id`
- **title:** ID del grupo por defecto
- **type:** text
- **desc:** ID numérico del grupo de MailerLite donde se suscribirán los compradores.

---

### `dm_mailerlite_tag_buyer`
- **title:** Tag: buyer
- **type:** text
- **desc:** ID de grupo para todos los compradores.

---

### `dm_mailerlite_tag_resource_buyer`
- **title:** Tag: resource-buyer
- **type:** text
- **desc:** ID de grupo para compradores de recursos.

---

### `dm_mailerlite_tag_course_buyer`
- **title:** Tag: course-buyer
- **type:** text
- **desc:** ID de grupo para compradores de cursos/talleres.
