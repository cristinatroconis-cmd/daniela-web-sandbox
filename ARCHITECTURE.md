# Arquitectura del Producto Digital
Proyecto: Daniela Montes Psicóloga

Este documento define la arquitectura del negocio digital dentro del sitio web.

El objetivo no es solo tener una web informativa, sino una **plataforma de recursos psicológicos escalable**.

---

# 1. Modelo de Producto

La oferta se organiza en una **escalera de valor (Value Ladder)**.

Esto permite:

- aumentar conversión
- aumentar ticket promedio
- generar recurrencia
- preparar a los usuarios para procesos más profundos

La estructura es la siguiente:

Nivel 1 — Recursos
Nivel 2 — Cursos
Nivel 3 — Talleres
Nivel 4 — Programas
Nivel 5 — Sesiones

---

# 2. Tipos de Producto

## 2.1 PDF Handouts

Tipo de producto:
WooCommerce Simple Product

Formato:

- PDF descargable
- ejercicios terapéuticos
- guías prácticas

Objetivo:

- entrada económica al ecosistema
- captación de nuevos usuarios


---

## 2.2 Cursos

Tipo:

- WooCommerce product
- vinculado a Tutor LMS

Contenido:

- módulos
- videos
- ejercicios
- material descargable

Objetivo:

profundización individual.


---

## 2.3 Talleres

Tipo:
producto WooCommerce

Formato:

- evento en vivo
- online

Incluye:

- sesión grupal
- material adicional

Objetivo:

experiencia en comunidad.


---

## 2.4 Programas

Producto premium.

Formato:

- proceso terapéutico estructurado
- varias sesiones
- material adicional
- posible acceso a curso

Objetivo:

transformación profunda.


---

## 2.5 Sesiones individuales

Servicio profesional.

Debe mostrarse como:

último nivel de intervención.

Objetivo:

no saturar agenda con clientes que aún no están preparados.

---

# 3. Estructura WooCommerce recomendada

Categorías principales:
Recursos
Cursos
Talleres
Programas
Sesiones


Cada producto debe pertenecer a una de estas categorías.

---

# 4. Navegación principal

La navegación debe reflejar el modelo de producto.

Ejemplo:
Recursos
Cursos
Talleres
Programas
Sobre Dani


Evitar navegación confusa.

El usuario debe entender rápidamente qué comprar.

---

# 5. Arquitectura de la HOME

La home no debe ser un blog.

Debe funcionar como **página de orientación de producto**.

Estructura:
Meet Dani

¿Qué necesitas?

Reviews

Newsletter

---

# 6. Sección clave: ¿Qué necesitas?

Esta sección funciona como **sistema de orientación del usuario**.

El visitante elige entre:

- Recursos
- Cursos
- Talleres
- Programas

Esto reduce:

- confusión
- abandono

Y mejora:

- conversión.

---

# 7. Funnel de usuario

El flujo ideal es:
Usuario nuevo

↓

Descubre contenido

↓

Compra recurso PDF

↓

Compra curso

↓

Participa en taller

↓

Entra en programa


Este es el modelo de crecimiento del proyecto.

---

# 8. Automatización futura

El sistema debería evolucionar hacia:

- email automation
- recomendaciones de producto
- bundles
- membresía

Herramientas posibles:

MailerLite  
WooCommerce Membership  
WooCommerce Subscriptions

---

# 9. Principios UX del proyecto

1. Claridad
2. Simplicidad
3. Jerarquía visual
4. Conversión

Evitar:

- exceso de información
- navegación compleja
- páginas saturadas.

---

# 10. Objetivo final

Convertir el sitio en una **escuela de recursos psicológicos online**, donde el usuario pueda avanzar progresivamente en su proceso personal.

---

# 11. Documentación operativa (sandbox)
Para evitar perder contexto (por ejemplo, chats que fallan), este repo mantiene:
- `docs/project_status.md` — estado del proyecto y plan (Semana 1/2)
- `docs/ARCHITECTURE_NOTES.md` — decisiones y notas vivas de arquitectura

# 12. Escuela: Tutor LMS + capa editorial con CPT
- **Tutor LMS** se mantiene como motor de cursos (hay cursos activos).
- La navegación/SEO/UX de la escuela se apoya en CPTs (catálogo editorial) para:
  - mejor jerarquía de oferta
  - CTAs consistentes
  - escalabilidad sin depender de páginas sueltas o solo categorías Woo

Regla: definir una fuente de verdad para el acceso (Tutor vs Memberships/Subs) para evitar doble gating.
