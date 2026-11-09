# AA-25 - Desafío Halloween: La Web del Terror Accesible

Auditoría de Calidad (ISO/IEC 25010), Accesibilidad (WCAG 2.1) y Testing de Software.

## 🎯 Descripción del Proyecto
Evolución del proyecto web del Desafío de Halloween para convertirlo en un producto de software accesible, seguro y normado, aplicando pautas WCAG 2.1 (Nivel AA), verificación estricta de navegación por teclado y una arquitectura robusta basada en PHP/MySQL (PDO).

---

## 🛠️ Mejoras de Accesibilidad (Lighthouse 100/100)
* **Estructura Semántica:** Se incorporó la etiqueta principal `<main>` para resolver los indicadores de puntos de referencia (*landmarks*).
* **Jerarquía de Títulos:** Se ordenaron los encabezados de forma estrictamente descendente (`<h1>` y `<h2>`) para lectores de pantalla.
* **Contraste de Colores:** Se ajustaron los tonos en la hoja de estilos para cumplir con los ratios mínimos exigidos por las pautas WCAG 2.1.
* **Navegación por Teclado:** Se aseguró la operabilidad completa mediante la tecla `Tab` utilizando la regla CSS `:focus-visible`.
* **Comunicación Asíncrona:** Integración de atributos `aria-live="polite"` para notificar cambios dinámicos en los formularios de autenticación.

---

## 📋 Matriz de Autoevaluación ISO/IEC 25010

| Característica | Evidencia Técnica | Estado |
| :--- | :--- | :--- |
| **1. Adecuación Funcional** | Backend en PHP con persistencia de datos en MySQL vía PDO. | Cumple |
| **2. Eficiencia de Desempeño** | Respuestas asíncronas optimizadas y cargas ligeras en entorno local. | Cumple |
| **3. Compatibilidad** | Arquitectura modular separando vistas estáticas y lógica de API. | Cumple |
| **4. Fiabilidad** | Manejo de excepciones PDO y validación estricta de campos vacíos. | Cumple |
| **5. Seguridad (OWASP)** | Prevención de Inyección SQL mediante *Prepared Statements*. | Cumple |
| **6. Usabilidad / A11y** | 100/100 en Lighthouse de accesibilidad y navegación por teclado. | Cumple |
| **7. Mantenibilidad** | Código estructurado en capas limpias y versionado en GitHub. | Cumple |
