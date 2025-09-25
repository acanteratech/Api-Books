# Api Books
## Requisitos Previos

- PHP (versión 7.4 o superior recomendada)
- MySQL (dump incluido en apiBooks.sql) 
- Servidor web

# Tiempo dedicado al proyecto <8 horas
## Mejoras necesarias:

- Test unitarios
- Docker
- Sistema de Autenticación (JWT) 

## Mejoras adicionales

- Cachear las consultas a la API externa para reducir las llamadas realizadas
- Traducción a varios idiomas

# DOCUMENTACIÓN DE LA API

- URL: tu localhost (EN MI CASO: http://apibooks)
## COMANDOS:
  - GET /: Health check
  - GET /books: Listar libros
  - POST /books: Crear libro
    {
      "title": "Test Post",
      "author": "Ale",
      "isbn": "9789506441746",
      "publication_year": 1999
    }
  - GET /books/{id}: Obtener libro
  - PUT /books/{id}": Actualizar libro
  - DELETE /books/{id}": Eliminar libro
  - GET /books/search?q=query: Buscar libros
  
