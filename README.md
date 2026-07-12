# FunSQL

**FunSQL** es un **Query Builder ligero para PHP y MySQL** que permite construir consultas SQL utilizando únicamente arrays de PHP, sin depender de frameworks ni librerías externas.

Su objetivo es simplificar la generación dinámica de consultas SQL manteniendo el control total sobre el código generado.

---

# Características

* ✔ SELECT dinámicos
* ✔ INSERT
* ✔ UPDATE
* ✔ DELETE
* ✔ INNER JOIN
* ✔ LEFT JOIN
* ✔ GROUP BY
* ✔ ORDER BY
* ✔ LIMIT
* ✔ Actualizaciones múltiples mediante `CASE WHEN`
* ✔ Obtención del SQL generado sin ejecutar la consulta
* ✔ Inspección automática de la estructura de la base de datos
* ✔ Compatible con MySQL y MariaDB
* ✔ Basado únicamente en `mysqli`

---

# Instalación

Simplemente incluya el archivo dentro de su proyecto.

```php
require_once "fun_sql.php";
```

Crear la conexión:

```php
$db = new db(
    "localhost",
    "usuario",
    "contraseña",
    "basededatos"
);

$db->conectar();
```

---

# Filosofía

La idea principal de FunSQL es representar una consulta SQL como una estructura de datos en PHP.

En lugar de construir cadenas SQL manualmente:

```sql
SELECT users.id, users.name
FROM users
LEFT JOIN roles ON users.role = roles.id
WHERE users.active = 1
ORDER BY users.name ASC
LIMIT 20;
```

la consulta se representa mediante un único array asociativo.

```php
$db->select_nw(false,[
    "tables"=>"users",
    "field"=>[
        "users.id",
        "users.name"
    ],
    "conditions"=>[
        "users.active",
        "=",
        1
    ]
]);
```

Este enfoque permite construir consultas dinámicamente de una forma mucho más sencilla y mantenible.

---

# select_nw()

`select_nw()` es el método principal de FunSQL.

Su objetivo es construir consultas `SELECT` utilizando un único array asociativo, donde cada propiedad representa una cláusula SQL.

La firma del método es:

```php
select_nw(bool $returnQuery, array $data)
```

* **true** → devuelve únicamente el SQL generado.
* **false** → ejecuta la consulta y devuelve el resultado.

---

# Ejemplo completo

```php
$resultado = $db->select_nw(false,[

    "tables"=>"users",

    "field"=>[
        "users.id",
        "users.name",
        "roles.description role"
    ],

    "join"=>[
        [
            "roles",
            [
                "users.role",
                "=",
                "roles.id"
            ],
            "LEFT"
        ]
    ],

    "conditions"=>[
        ["users.active","=",1],
        ["roles.visible","=",1]
    ],

    "group"=>"users.id",

    "order"=>[
        "users.name",
        true
    ],

    "limit"=>20

]);
```

SQL generado:

```sql
SELECT
    users.id,
    users.name,
    roles.description role
FROM users
LEFT JOIN roles
    ON users.role = roles.id
WHERE users.active = 1
AND roles.visible = 1
GROUP BY users.id
ORDER BY users.name ASC
LIMIT 20;
```

---

# Estructura del array

Cada propiedad representa una cláusula del SQL.

| Propiedad  | Equivalente SQL |
| ---------- | --------------- |
| field      | SELECT          |
| tables     | FROM            |
| join       | JOIN            |
| foreign    | ON              |
| conditions | WHERE           |
| group      | GROUP BY        |
| order      | ORDER BY        |
| limit      | LIMIT           |

---

# field

Define los campos que serán seleccionados.

Puede utilizarse un string:

```php
"field"=>"*"
```

o un array:

```php
"field"=>[
    "id",
    "name",
    "email"
]
```

Genera:

```sql
SELECT id, name, email
```

---

# tables

Indica la tabla principal.

```php
"tables"=>"users"
```

También admite múltiples tablas.

```php
"tables"=>[
    "users",
    "roles"
]
```

---

# conditions

Corresponde a la cláusula `WHERE`.

Condición simple:

```php
"conditions"=>[
    "id",
    "=",
    10
]
```

Resultado:

```sql
WHERE id = 10
```

Múltiples condiciones:

```php
"conditions"=>[

    ["active","=",1],

    ["age",">",18],

    ["country","=","Argentina","OR"]

]
```

Resultado:

```sql
WHERE active = 1
AND age > 18
OR country = 'Argentina'
```

---

# join

Permite definir uno o varios JOIN.

Cada JOIN posee la siguiente estructura:

```php
[
    "tabla",
    [
        "campo1",
        "=",
        "campo2"
    ],
    "TIPO"
]
```

Ejemplo:

```php
"join"=>[
    [
        "roles",
        [
            "users.role",
            "=",
            "roles.id"
        ]
    ]
]
```

Genera:

```sql
INNER JOIN roles
ON users.role = roles.id
```

Si se especifica un tercer parámetro, se utiliza como tipo de JOIN.

```php
[
    "roles",
    [
        "users.role",
        "=",
        "roles.id"
    ],
    "LEFT"
]
```

Resultado:

```sql
LEFT JOIN roles
ON users.role = roles.id
```

Puede agregarse cualquier cantidad de JOIN.

---

# foreign

Cuando no se utiliza la propiedad `join`, es posible especificar manualmente la condición `ON`.

```php
"foreign"=>[
    "users.role",
    "=",
    "roles.id"
]
```

Genera:

```sql
ON users.role = roles.id
```

---

# group

Permite agregar un `GROUP BY`.

```php
"group"=>"category"
```

Resultado:

```sql
GROUP BY category
```

---

# order

Define el orden del resultado.

```php
"order"=>[
    "name",
    true
]
```

Resultado:

```sql
ORDER BY name ASC
```

Mientras que:

```php
"order"=>[
    "name",
    false
]
```

produce:

```sql
ORDER BY name DESC
```

---

# limit

Limita la cantidad de registros.

```php
"limit"=>100
```

Resultado:

```sql
LIMIT 100
```

---

# INSERT

Los registros se crean mediante `create()`.

```php
$db->create(

    false,

    "users",

    [

        "name"=>"John",

        "email"=>"john@mail.com",

        "active"=>1

    ]

);
```

La función devuelve automáticamente el último ID insertado.

---

# UPDATE

La versión moderna del método es `nw_update()`.

```php
$db->nw_update(false,[

    "tables"=>"users",

    "field_val"=>[
        "name"=>"Peter",
        "active"=>1
    ],

    "conditions"=>[
        "id",
        "=",
        15
    ]

]);
```

Genera:

```sql
UPDATE users
SET
    name='Peter',
    active=1
WHERE id = 15;
```

---

# Actualizaciones múltiples

En ocasiones es necesario actualizar una misma columna con valores diferentes para múltiples registros. Normalmente esto implicaría ejecutar una consulta `UPDATE` por cada registro, lo que aumenta el tiempo de ejecución y la cantidad de viajes al servidor.

`multiUpdate()` resuelve este problema generando una única consulta SQL utilizando la estructura `CASE WHEN`.

Por ejemplo, supongamos que deseamos actualizar el estado de varios usuarios:

| ID | Nuevo estado |
|---:|:-------------|
| 1 | Activo |
| 2 | Suspendido |
| 3 | Eliminado |

En lugar de ejecutar tres consultas independientes:

```sql
UPDATE users SET status = 'Activo' WHERE id = 1;

UPDATE users SET status = 'Suspendido' WHERE id = 2;

UPDATE users SET status = 'Eliminado' WHERE id = 3;
```

FunSQL genera automáticamente una única consulta:

```sql
UPDATE users
SET status =
CASE id
    WHEN 1 THEN 'Activo'
    WHEN 2 THEN 'Suspendido'
    WHEN 3 THEN 'Eliminado'
END
WHERE id IN (1,2,3);
```

Esto reduce considerablemente el tiempo de ejecución y la carga sobre el servidor de base de datos.

## Uso

El método recibe una estructura especial en el parámetro `field_val`:

```php
$db->multiUpdate(false,

    "users",

    [

        "multi" => true,

        "attr" => [

            "status",   // Campo que será actualizado
            "id"        // Campo utilizado para comparar cada registro

        ],

        "values" => [

            1 => "'Activo'",
            2 => "'Suspendido'",
            3 => "'Eliminado'"

        ]

    ],

    [

        "id",
        "IN",
        "(1,2,3)"

    ]

);
```

La propiedad `attr` define:

- **Primer valor:** la columna que será modificada.
- **Segundo valor:** la columna utilizada en cada sentencia `WHEN`.

Mientras que `values` contiene un diccionario donde:

- La **clave** representa el valor evaluado en cada `WHEN`.
- El **valor** representa el dato que será asignado mediante `THEN`.

En el ejemplo anterior se genera internamente:

```sql
status = CASE id
    WHEN 1 THEN 'Activo'
    WHEN 2 THEN 'Suspendido'
    WHEN 3 THEN 'Eliminado'
END
```

Posteriormente, dicha expresión se incorpora automáticamente al `UPDATE`.

## ¿Cuándo utilizarlo?

`multiUpdate()` resulta especialmente útil para operaciones masivas como:

- Actualizar prioridades de una lista.
- Reordenar elementos (drag & drop).
- Cambiar estados de múltiples registros.
- Asignar categorías diferentes a varios elementos.
- Sincronizar información importada desde archivos CSV o Excel.
- Procesar grandes cantidades de datos minimizando el número de consultas enviadas al servidor.

Para conjuntos de datos medianos o grandes, este enfoque suele ofrecer un rendimiento significativamente mejor que ejecutar un `UPDATE` por cada registro.
---

# Obtener únicamente el SQL

Todos los métodos reciben como primer parámetro un valor booleano.

```php
true
```

Devuelve únicamente la consulta SQL.

```php
$sql = $db->select_nw(true,[

    "tables"=>"users",

    "field"=>"*"

]);
```

Resultado:

```sql
SELECT *
FROM users;
```

Esto resulta especialmente útil para depuración o para registrar consultas antes de ejecutarlas.

---

# Inspección automática de la Base de Datos

FunSQL incorpora el método:

```php
$db->getDataBaseFields();
```

El método consulta automáticamente `INFORMATION_SCHEMA` y devuelve un array con:

* Todas las tablas.
* Todos los campos.
* Tipo de dato.
* Claves primarias.
* Claves foráneas.
* Relaciones entre tablas.

Resulta especialmente útil para construir generadores automáticos de formularios, CRUDs o sistemas de administración.

---

# Compatibilidad

* PHP 7+
* PHP 8+
* MySQL
* MariaDB

---

# Dependencias

FunSQL únicamente utiliza la extensión nativa de PHP:

* `mysqli`

No requiere Composer ni dependencias externas.

---

# Licencia

MIT