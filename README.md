# AA-25---Desafio-Halloween
# AA-28 LA WEB DEL TERROR ACCESIBLE
Auditoría de Calidad (ISO 25000), Accesibilidad (WCAG 2.1) y Testing de Software
Evolucion del proyecto web del Desafío de Halloween maquetado previamente para convertirlo en un producto de software accesible, seguro y normado.  aplicación de pautas WCAG 2.1 (Nivel AA), verificación de la navegación por teclado y auditar el sistema mediante la Matriz de Calidad ISO/IEC 25010.
¿Qué cambios clave se hicieron para solucionar los errores de Lighthouse? (index)
Se agregó la etiqueta <main>: Envuelve todo el cuerpo principal de la página, solucionando el error de "Document does not have a main landmark".

Jerarquía estricta de títulos (<h1> y <h2>): El título principal ahora es un único <h1> arriba del todo, y las secciones del Top y del Listado usan <h2>, solucionando el error de orden descendente en los headings.

Contraste de colores: Se ajustaron los tonos de los textos anaranjados (#ff7518 y #ff9933) sobre el fondo oscuro (#121212) para que superen el ratio de contraste exigido por las pautas WCAG 2.1.

Foco visible (:focus-visible): Se añadió el contorno para los botones y enlaces al navegar exclusivamente mediante la tecla Tab.

¿Qué se mejoró con login.html para cumplir con la rúbrica?
Atributos de etiqueta (<label for="...">): Vinculan de manera formal el texto descriptivo con cada campo de entrada, requisito indispensable para la accesibilidad semántica.

Navegación por teclado y Foco (:focus-visible): Se añadió un contorno claro y accesible al presionar la tecla Tab, permitiendo operar todo el formulario sin mouse.

Comunicación Asíncrona (aria-live="polite"): Se incorporó un contenedor oculto visualmente pero activo para lectores de pantalla (sr-only), el cual anuncia de manera dinámica los mensajes devueltos por el sistema tras el fetch.
