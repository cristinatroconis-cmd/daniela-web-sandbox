# Proyecto Web — Daniela Montes Psicóloga

Repositorio de trabajo para la optimización técnica, UX y conversión de la web (sandbox/local):

`https://danielamontespsic.com/`

El proyecto **NO** busca reconstruir el sitio desde cero, sino **mejorar la estructura existente**, optimizar la experiencia de usuario y aumentar la conversión hacia productos digitales y programas terapéuticos.

---

## Alcance del repositorio (importante)

Este repositorio versiona **solo código propio** para mantener PRs limpios y evitar incluir WordPress core/plugins/uploads.

Se versiona:
- `wp-content/themes/daniela-child/` (tema-hijo / código principal)
- (opcional) `wp-content/mu-plugins/`

No se versiona:
- WordPress core (`wp-admin`, `wp-includes`)
- uploads (`wp-content/uploads`)
- plugins de terceros
- base de datos

---

## Objetivo del proyecto

Transformar la web en una **plataforma de recursos psicológicos y formación**, donde el foco principal sea:

1. Venta de recursos descargables
2. Venta de cursos
3. Talleres
4. Programas terapéuticos
5. (Secundario) sesiones psicológicas

La arquitectura debe facilitar:
- navegación clara
- CTAs visibles
- flujo rápido hacia checkout
- recurrencia de compra

---

## Stack técnico

- CMS: WordPress  
- Ecommerce: WooCommerce  
- Servidor actual: Rocket.net  

Plugins principales:
- WooCommerce
- WooCommerce Membership
- WooCommerce Subscriptions
- Tutor LMS
- Elementor / Elementor Pro (solo en secciones existentes)
- MailerLite Integration
- CommerceKit

---

## Filosofía de desarrollo

Este proyecto sigue estas reglas:

### 1 — No romper lo existente
El theme actual funciona y **no se debe reconstruir completamente**.

Las mejoras deben ser:
- progresivas
- modulares
- compatibles con el theme actual

### 2 — Evitar dependencia de Elementor
Las nuevas secciones deben desarrollarse preferentemente con:
- PHP templates
- HTML semántico
- CSS ligero
- JS mínimo

### 3 — Arquitectura modular
Las secciones de la home deben cargarse con `get_template_part()`.

Ejemplo:
```php
get_template_part('template-parts/home/section', 'necesitas');
```

Esto permite:
- modularidad
- mantenimiento fácil
- eliminación futura de Elementor

### 4 — UX primero
Cada cambio debe mejorar al menos uno de estos puntos:
- claridad de oferta
- reducción de fricción en compra
- acceso rápido a productos
- escalabilidad del catálogo

---

## Arquitectura actual de la HOME (dirección deseada)

HOME

- Meet Dani (se mantiene igual)
- ¿Qué necesitas? (nueva sección de orientación)
  - PDF Handouts
  - Cursos
  - Talleres
  - Programas
- Reviews (se mantiene igual)
- Newsletter (se mantiene igual)

---

## Nueva sección: ¿Qué necesitas?

Objetivo: guiar rápidamente al usuario al tipo de recurso que busca.

Se implementará como un **grid de 4 tarjetas con CTA**.

Cada tarjeta contendrá:
- título
- descripción corta
- botón CTA
- enlace a categoría WooCommerce

---

## Buenas prácticas

Siempre que se agregue código:
1. Explicar qué problema resuelve
2. Indicar en qué archivo se coloca
3. Mantener comentarios en el código
4. Evitar duplicación de lógica
5. Priorizar rendimiento

---

## Estilo visual

La estética del proyecto debe ser:
- limpia
- calmada
- cercana
- profesional

Evitar:
- elementos pesados
- animaciones innecesarias
- exceso de JS

---

## Roadmap técnico

### Fase 1 — Auditoría técnica completa
- theme
- plugins
- estructura WooCommerce
- LMS

### Fase 2 — Optimización HOME
- nueva sección “¿Qué necesitas?”
- jerarquía visual
- CTAs claros

### Fase 3 — Optimización conversión
- checkout simplificado
- bundles
- productos destacados
- automatizaciones email

### Fase 4 — Optimización de escuela online
- estructura cursos
- acceso usuarios
- navegación LMS

---

## Objetivo final

Convertir la web en una **plataforma de recursos psicológicos escalable**, donde el usuario pueda:
1. descubrir contenido
2. comprar recursos
3. profundizar con cursos
4. avanzar a programas terapéuticos

---

## Recursos gratuitos descargables (PDFs)

### Cómo crear un recurso gratuito (PDF)

1. WP Admin → **Productos → Añadir nuevo**
2. **Título**: nombre del recurso (ej. "Baja el Ruido Mental")
3. En "Datos del producto":
   - Tipo: **Producto simple**
   - Marca: **Descargable** (y opcionalmente **Virtual**)
   - En "Archivos descargables" → **Añadir archivo** → sube el PDF
   - **Precio normal**: `0`
4. En la columna derecha:
   - **Categoría**: `Recursos (Gratis)` (`recursos-gratis`) para que aparezca en `/recursos/gratis/`
   - **Etiquetas (Temas)**: ej. `ansiedad`, `estrés`, `autoestima`
5. **Publicar**

> WooCommerce enviará automáticamente un email con el enlace de descarga al completar el pedido.

### Categorías de producto

| Slug               | Descripción                                  | URL frontend          |
|--------------------|----------------------------------------------|-----------------------|
| `recursos-gratis`  | PDFs / recursos gratuitos (checkout $0)      | `/recursos/gratis/`   |
| `recursos-pagos`   | PDFs / recursos de pago                      | `/recursos/pagos/`    |
| `cursos`           | Cursos online                                | `/escuela/cursos/`    |
| `talleres`         | Talleres                                     | `/escuela/talleres/`  |

### Flujo de checkout para recursos gratuitos

Cuando un carrito contiene únicamente productos **virtuales o descargables** con total **$0**, el checkout se simplifica automáticamente mostrando solo:

- Nombre
- Apellidos
- Email

Se eliminan los campos de dirección, teléfono, empresa, etc. para reducir la fricción.  
Los carritos mixtos (productos físicos o de pago) mantienen el checkout completo.

### Mensaje de "Gracias" en la página de confirmación

Después de completar un pedido gratuito, se muestra un mensaje personalizado informando al usuario que recibirá el recurso por correo.

**Para editar el mensaje** (sin tocar código):

1. WP Admin → **WooCommerce → Ajustes → Avanzado → Mensaje Recursos Gratis**
2. Edita el texto en el campo "Mensaje" (se acepta HTML básico)
3. Guarda

El mensaje solo se muestra en pedidos con total $0 que contengan al menos un producto descargable o virtual.