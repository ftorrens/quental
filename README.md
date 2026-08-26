# Backend Laravel — Rick and Morty API

## 📖 Acerca del proyecto

Backend desarrollado en **Laravel**, con soporte para **Laravel Sail (Docker)** e integración con la API externa de **Rick and Morty**.

El proyecto está diseñado siguiendo una arquitectura organizada y mantenible, utilizando diferentes patrones y herramientas para facilitar la gestión y transformación de los datos.

### Características principales

* Integración con Rick and Morty API:** Sincronización paginada y optimizada con transacciones por lote (`DB::transaction`).
* Autenticación con Laravel Sanctum:** Registro, inicio de sesión y gestión de tokens Bearer.
* Validación Estricta y Seguridad (FormRequests):** Filtrado de datos, sanitización de parámetros Query (`FilterCharacterRequest`) y prevención de vulnerabilidades DoS/SQLi.
* Gestión de Favoritos:** Relaciones muchos a muchos para marcar y listar personajes favoritos por usuario.
* Persistencia mediante Eloquent ORM:** Modelado de personajes, ubicaciones, episodios y relaciones entre sí.
* Entorno Dockerizado (Laravel Sail):** Configuración rápida y aislada para desarrollo local.
* Documentación interactiva OpenAPI/Swagger:** Integración con L5-Swagger para probar la API gráficamente.
* Suite de Pruebas Automatizadas:** Cobertura con PHPUnit / Pest (`sail test`).

---

## 🚀 Guía de inicio rápido

### Requisitos previos

Antes de comenzar, asegúrate de tener instaladas las siguientes herramientas:

* **Docker**
* **Docker Compose**
* **Git**
* **Composer**

### 1. Instalar las dependencias de PHP

Instala las dependencias del proyecto utilizando Composer:

```bash
composer install
```

### 2. Configurar el archivo de entorno

Copia el archivo de configuración `.env.example`:

```bash
cp .env.example .env
```

A continuación, revisa y configura las variables de entorno necesarias en el archivo `.env`, y coloca preferentemente la clave entregada de la base de datos en la variable DB_PASSWORD:.

### 3. Levantar los contenedores con Laravel Sail

Inicia los contenedores Docker utilizando Laravel Sail:

```bash
./vendor/bin/sail up -d
```

Una vez ejecutado el comando, los servicios definidos por el proyecto estarán disponibles dentro de los contenedores Docker.

### 4. Generar la clave de la aplicación

Genera la clave de cifrado de Laravel:

```bash
sail artisan key:generate
```

### 5. Ejecutar las migraciones

Crea las tablas necesarias en la base de datos:

```bash
sail artisan migrate
```

---

## 🧬 Sincronización de datos de Rick and Morty

El proyecto dispone de un comando Artisan personalizado para obtener los datos de la API de **Rick and Morty** y almacenarlos en la base de datos local.

La sincronización se realiza de forma **paginada**, permitiendo procesar los datos de la API de manera eficiente.

Para iniciar la sincronización:

```bash
sail artisan rickandmorty:sync
```

Este comando se encarga de consultar la API externa y almacenar la información correspondiente en la base de datos.

---

## 📚 Documentación de la API

La API está documentada mediante **L5-Swagger**, utilizando el estándar **OpenAPI**.

La documentación proporciona una interfaz interactiva desde la que es posible consultar los endpoints disponibles y realizar peticiones directamente contra la API.

### Swagger UI

Con el proyecto levantado, accede a:

**http://localhost/api/documentation**

---

## 🧪 Ejecución de pruebas

Para ejecutar toda la suite de pruebas automatizadas:

```bash
sail test
```

Esto ejecutará las pruebas configuradas en el proyecto y permitirá comprobar que las diferentes funcionalidades continúan funcionando correctamente.

---

## 🏗️ Arquitectura del proyecto

El proyecto utiliza diferentes patrones y conceptos de diseño para mantener una separación clara de responsabilidades.

### DTOs

Los **Data Transfer Objects (DTOs)** se utilizan para transportar información entre las diferentes capas de la aplicación de forma estructurada y controlada.

### Mappers

Los **Mappers** se encargan de transformar los datos entre diferentes representaciones, evitando acoplar directamente las diferentes capas de la aplicación.

### Eloquent ORM

Laravel **Eloquent** se utiliza como ORM para gestionar la persistencia de los datos y las relaciones entre las diferentes entidades.

### Comandos Artisan

Los comandos personalizados de **Artisan** permiten encapsular procesos específicos de la aplicación.

En este proyecto se utiliza, entre otros, el comando:

```bash
sail artisan rickandmorty:sync
```

para realizar la sincronización de datos con la API externa.

### API Resources

Los **API Resources** permiten controlar y estructurar las respuestas que devuelve la API, manteniendo un formato consistente.

### Swagger / OpenAPI

La API está documentada mediante **Swagger / OpenAPI**, facilitando la consulta de los endpoints y la realización de pruebas desde una interfaz gráfica.

---

## 🐳 Laravel Sail

El proyecto utiliza **Laravel Sail** como entorno de desarrollo basado en Docker.

Esto permite ejecutar la aplicación y sus servicios asociados sin necesidad de instalar y configurar manualmente todas las dependencias en el sistema local.

Para iniciar el entorno:

```bash
./vendor/bin/sail up -d
```

Para detener los contenedores:

```bash
sail down
```

Para consultar los contenedores en ejecución:

```bash
sail ps
```

---
