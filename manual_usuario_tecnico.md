# Manual de Usuario y Técnico - Vivero El Prado

## Manual de Usuario

### Introducción
Vivero El Prado es una plataforma web diseñada para facilitar la compra de plantas, macetas, abonos y tierra. Este manual te guiará en el uso de las principales funcionalidades del sitio.

### Acceso al Sitio
1. Abre tu navegador web.
2. Ingresa la URL: `http://localhost/vivero_el_prado/`.
3. Navega por las diferentes secciones disponibles en el menú lateral.

### Funcionalidades Principales
- **Catálogo:** Explora los productos disponibles.
- **Carrito:** Agrega productos al carrito para realizar compras.
- **Registro e Inicio de Sesión:** Crea una cuenta o inicia sesión para acceder a tus pedidos.
- **Mis Pedidos:** Consulta el historial de tus compras.
- **Gráficos:** Accede a los reportes de Power BI desde la página principal.

### Pasos para Comprar
1. Navega al catálogo y selecciona los productos deseados.
2. Haz clic en "Agregar al carrito".
3. Ve al carrito y procede al checkout.
4. Completa la información de pago y confirma tu pedido.

---

## Manual Técnico

### Requisitos
- **Servidor Local:** XAMPP o similar.
- **Base de Datos:** MySQL.
- **Lenguaje:** PHP 8.2.

### Instalación
1. Descarga e instala XAMPP.
2. Coloca el proyecto en la carpeta `htdocs`.
3. Inicia Apache y MySQL desde el Panel de Control de XAMPP.
4. Importa la base de datos desde el archivo SQL proporcionado.

### Estructura del Proyecto
- **`public/`:** Contiene las páginas accesibles para los usuarios.
- **`admin/`:** Archivos administrativos como reportes.
- **`auth/`:** Manejo de registro e inicio de sesión.
- **`config/`:** Configuración de la base de datos.
- **`includes/`:** Componentes reutilizables como encabezados y pies de página.
- **`vendor/`:** Dependencias instaladas con Composer.

### Configuración
1. Edita el archivo `config/config.php` para ajustar las credenciales de la base de datos.
2. Asegúrate de que las rutas en el archivo `index.php` sean correctas.

### Solución de Problemas
- **Error de conexión a la base de datos:** Verifica las credenciales en `config/db.php`.
- **Página en blanco:** Revisa los logs de Apache para identificar errores.
- **Problemas con gráficos:** Asegúrate de que el enlace de Power BI en `reporte_powerbi.php` sea válido.

### Mantenimiento
- Actualiza las dependencias con Composer usando:
  ```
  composer update
  ```
- Realiza respaldos periódicos de la base de datos.

---

Este manual proporciona una guía básica para usuarios y técnicos. Para más detalles, consulta la documentación completa del proyecto.