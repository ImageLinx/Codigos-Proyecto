
# Guía de Configuración para la Infraestructura de Red ImageLinx

## Requisitos
- Ubuntu Server (versión estable más reciente)
- Conexión a Internet
- Acceso de administrador (root)

## Paso 1: Configuración Inicial e Instalación

### Actualiza tu sistema
Antes de comenzar, asegúrate de que todos los paquetes estén actualizados para mantener el sistema seguro y estable.

```bash
sudo apt update && sudo apt upgrade
```

## Paso 2: Instalación y Configuración del Servidor DHCP
### Instalar isc-dhcp-server
Instala el servidor DHCP utilizando el siguiente comando:

```bash
sudo apt install isc-dhcp-server
```
### Configurar isc-dhcp-server
Edita el archivo de configuración para especificar en qué interfaz escuchará el servidor DHCP.

```bash
sudo nano /etc/default/isc-dhcp-server
```
Agrega o edita la línea de INTERFACESv4 para especificar tu interfaz de red, como se muestra a continuación:

```plaintext
INTERFACESv4="enp0s3"
```
**Soporte para IPv6**

En este proyecto, se ha configurado el servidor DHCP para operar con direcciones IPv4, que es el protocolo de Internet más utilizado. Sin embargo, si deseas habilitar el soporte para IPv6, que es la versión más reciente del protocolo de Internet, puedes hacerlo configurando también las interfaces para IPv6.

Para agregar soporte IPv6, necesitarás editar la línea INTERFACESv6 en el mismo archivo de configuración:
```plaintext
INTERFACESv6="enp0s3"
```
*Se podría desviar las conexiones IPv6 e IPv4 por interfaces distintas si se desea*

### Configurar dhcpd.conf
Ahora configura el archivo principal de DHCP para definir el dominio, los servidores DNS, el tiempo de concesión de las direcciones IP y las subredes.

```bash
sudo nano /etc/dhcp/dhcpd.conf
```
Agrega o modifica las siguientes líneas según tu configuración de red:

```plaintext
option domain-name "ImageLinx.lan";
option domain-name-servers 192.168.1.199;
default-lease-time 600;
max-lease-time 7200;
ddns-update-style none;

subnet 192.168.1.0 netmask 255.255.255.0 {
  range 192.168.1.179 192.168.1.189;
  option routers 192.168.1.1;
  option broadcast-address 192.168.1.255;
}
```
Configura direcciones IP estáticas para servidores específicos dentro de la misma subred:

```plaintext
host servidor-web-y-ftp-1-2 {
  hardware ethernet 08:00:27:0d:96:89;
  fixed-address 192.168.1.191;
}

host servidor-dns-1-2 {
  hardware ethernet 08:00:27:11:b3:fa;
  fixed-address 192.168.1.199;
}

host control-1-2 {
  hardware ethernet 08:00:27:ff:8d:11;
  fixed-address 192.168.1.193;
}
```
### Iniciar y Habilitar DHCP Server

```bash
sudo systemctl restart isc-dhcp-server
sudo systemctl enable isc-dhcp-server
```
## Paso 3: Instalación y Configuración del Servidor DNS con BIND9
### Instalar BIND9 y dnsutils

```bash
sudo apt install bind9
sudo apt install dnsutils
```
### Crear el directorio para las zonas
Crea un directorio para almacenar los archivos de configuración de las zonas.

```bash
sudo mkdir /etc/bind/zones
```
### Configurar named.conf.options
Configura las opciones principales de BIND:

```bash
sudo nano /etc/bind/named.conf.options
```
Incluye lo siguiente para definir la ACL y las opciones del servidor:

```plaintext
acl internals {
  127.0.0.1;
  192.168.1.0/24;
};

options {
  directory "/var/cache/bind";
  forwarders {
    8.8.8.8;
    8.8.4.4;
  };
  dnssec-validation auto;
  listen-on { 192.168.1.199; };
  allow-query { internals; };
}
```
- **ACL "internals":** Define una lista de control de acceso llamada internals.

- **127.0.0.1:** Incluye la dirección IP de loopback (localhost), lo que permite que el propio servidor DNS realice consultas a sí mismo.

- **192.168.1.0/24:** Especifica una red entera, permitiendo a todas las direcciones IP dentro del rango de la subred 192.168.1.0 a 192.168.1.255 realizar consultas DNS. Esto es comúnmente usado para permitir que todos los dispositivos dentro de una red local hagan uso del servidor DNS

- **directory "/var/cache/bind":** Establece el directorio donde BIND almacena sus archivos de caché y otros datos relacionados. Este es el directorio predeterminado para la caché en la mayoría de las instalaciones de BIND.

- **forwarders { 8.8.8.8; 8.8.4.4; }:** Define servidores DNS "forwarders" o reenviadores. Cuando el servidor DNS local no puede resolver un nombre de dominio por sí mismo, reenvía la consulta a estos servidores DNS externos. Aquí, 8.8.8.8 y 8.8.4.4 son servidores DNS públicos proporcionados por Google, conocidos por su fiabilidad y velocidad.

- **dnssec-validation auto:** Habilita la validación DNSSEC, que es un conjunto de extensiones de seguridad para asegurar las respuestas a las consultas DNS. La opción auto configura el servidor para que automáticamente gestione y valide las firmas de DNSSEC, mejorando la seguridad contra ataques de envenenamiento de caché y otros tipos de falsificación DNS.

- **listen-on { 192.168.1.199; }:** Configura el servidor DNS para escuchar consultas en la interfaz de red con la dirección IP 192.168.1.199. Esto limita las respuestas del servidor DNS a solicitudes recibidas a través de esta dirección IP específica.

- **allow-query { internals; }:** Restringe quién puede hacer consultas DNS al servidor a los dispositivos definidos en la ACL internals. Esto significa que solo los dispositivos en la red local (192.168.1.0/24) y el propio servidor DNS (127.0.0.1) pueden hacer consultas.

### Configuración de Zonas en BIND
Define las zonas de DNS para tu dominio y crea los archivos de configuración de las zonas.

```bash
sudo nano /etc/bind/named.conf.local
```
Incluye las definiciones de zona y asegúrate de apuntar a los archivos correctos dentro del directorio /etc/bind/zones:

```plaintext
zone "ImageLinx.lan" {
  type master;
  file "/etc/bind/zones/ImageLinx.lan.db";
  allow-transfer { internals; };
};

zone "1.168.192.in-addr.arpa" {
  type master;
  file "/etc/bind/zones/1.168.192.in-addr.arpa.db";
  allow-transfer { internals; };
};
```
### Crear archivos de zona directa e inversa
Crea los archivos para las zonas directa e inversa utilizando la estructura base de db.local.

```bash
sudo touch /etc/bind/zones/ImageLinx.lan.db
sudo touch /etc/bind/zones/1.168.192.in-addr.arpa.db
```
### Edita el archivo de zona directa:

```bash
sudo nano /etc/bind/zones/ImageLinx.lan.db
```
Incluye lo siguiente, asegurándote de incrementar el número de serie en una unidad cada vez que edites el archivo (donde pone Serial):

```plaintext
$TTL    604800
$ORIGIN ImageLinx.lan.
@  	 IN 	 SOA     ImageLinx.lan. ImageLinx.proton.me. (
                     		 3		 ; Serial
                		 604800		 ; Refresh
                 		 86400		 ; Retry
               		 2419200		 ; Expire
                		 604800 )  	 ; Negative Cache TTL
;
@  	 IN 	 NS 	 ImageLinx.lan.
@  	 IN 	 A  	 192.168.1.191
www     IN 	 A  	 192.168.1.191
```
### Edita el archivo de zona inversa:

```bash
sudo nano /etc/bind/zones/1.168.192.in-addr.arpa.db
```
Incluye lo siguiente, recordando actualizar el número de serie como antes:

```plaintext
$TTL    604800
$ORIGIN 1.168.192.in-addr.arpa.
@  	 IN 	 SOA     ImageLinx.lan. ImageLinx.proton.me. (
                     		 2		 ; Serial
                		 604800		 ; Refresh
                 		 86400		 ; Retry
               		 2419200		 ; Expire
                		 604800 )  	 ; Negative Cache TTL
;
@  	 IN 	 NS 	 ImageLinx.lan.

191     IN 	 PTR     www.ImageLinx.lan.
```
### Iniciar y Habilitar BIND9

```bash
sudo systemctl restart bind9
sudo systemctl enable bind9
```

—------------------------------------------------------------------------------------------
## Paso 4: Instalación y Configuración del Servidor Web Apache2
### Instalación de Apache2
Para instalar Apache2, ejecuta el siguiente comando en la terminal de Ubuntu:
```bash
sudo apt install apache2
```
Después de la instalación, verifica el estado del servicio Apache2 para confirmar que está activo y funcionando correctamente:
```bash
sudo systemctl status apache2
```
### Exploración del Directorio de Configuración de Apache2
Una vez confirmado que Apache2 está operativo, navega al directorio /etc/apache2 para revisar la estructura y los archivos de configuración del servidor web. Este paso es crucial para entender cómo se organiza Apache y dónde se encuentran los archivos de configuración relevantes.

```bash
cd /etc/apache2
ls -l
```

### Instalación de PHP y el Módulo PHP para Apache2
Procede con la instalación de PHP y su módulo para Apache2, que es esencial para ejecutar aplicaciones web dinámicas en tu servidor. Usa el siguiente comando para realizar la instalación:

```bash
sudo apt install php libapache2-mod-php
```

### Creación y Configuración del Directorio del Servidor Web para ImageLinx.lan
### Creación del Directorio principal
Crea el directorio principal para la aplicación web dentro del directorio raíz de Apache2 usando el siguiente comando:

```bash
sudo mkdir -p /var/www/html/ImageLinx.lan
```

### Establecimiento de Permisos
Configura los permisos del directorio para asegurar que el servidor web tenga los permisos adecuados para servir y gestionar los archivos correctamente. Es importante que el servidor tenga permisos de lectura sobre los archivos HTML, CSS y PHP, y permisos de escritura en el directorio uploads para que los usuarios puedan subir archivos:

```bash
sudo chown -R www-data:www-data /var/www/html/ImageLinx.lan
sudo chmod -R 755 /var/www/html/ImageLinx.lan
```
### Creación del Directorio de Subidas (Uploads)

Para permitir que los usuarios suban imágenes a la aplicación, es necesario crear un directorio de subidas dentro del directorio de la aplicación web. Este directorio almacenará las imágenes cargadas por los usuarios.

```bash
sudo mkdir /var/www/html/ImageLinx.lan/uploads
```

Establecimiento de Permisos para el Directorio de Subidas

Es crucial que el servidor web tenga permisos de escritura en el directorio de subidas para permitir la carga de archivos. A continuación, se configuran los permisos adecuados para este directorio:

```bash
sudo chown -R www-data:www-data /var/www/html/ImageLinx.lan/uploads
sudo chmod -R 775 /var/www/html/ImageLinx.lan/uploads
```
Estos comandos aseguran que el usuario bajo el cual se ejecuta el servidor web, www-data, tenga los permisos necesarios para escribir en el directorio de subidas, mientras que otros usuarios del grupo tienen permisos de lectura y ejecución.

#### ¿Por qué usar el usuario www-data?/

En los sistemas basados en Debian, como Ubuntu, www-data es el usuario predeterminado bajo el cual se ejecutan el servidor web Apache y otros servidores web. Usar este usuario tiene varias ventajas de seguridad y practicidad:

**Seguridad Mejorada:** Al operar los servicios web bajo www-data, limitamos los permisos de estos procesos exclusivamente a las operaciones necesarias. Esto significa que si un atacante compromete el servidor web, sus capacidades para realizar cambios en el sistema estarán restringidas a lo que www-data puede hacer. De esta forma, se reduce el riesgo de daños mayores al sistema.

**Separación de Privilegios:** Usar www-data ayuda a mantener una clara separación de privilegios en el sistema. Al no ejecutar el servidor web como root o como un usuario con amplios privilegios administrativos, se asegura que las actividades del servidor web no puedan interferir con otras operaciones críticas del sistema.

**Gestión Simplificada:** Dado que www-data es un estándar en muchos sistemas para la ejecución de servidores web, usar este usuario simplifica la configuración y la administración del sistema, ya que muchos ejemplos y documentación presuponen su uso.

Establecer los permisos adecuadamente no solo ayuda a asegurar la funcionalidad del sitio web, sino que también juega un papel crucial en la protección del servidor contra actividades maliciosas o no autorizadas. Asegúrate de siempre revisar y ajustar los permisos según las necesidades específicas de tu aplicación y entorno de hosting.


### Creación de Archivos de la Aplicación Web
Para configurar la aplicación web en el servidor, necesitarás crear los siguientes archivos dentro del directorio ImageLinx.lan. Estos archivos son esenciales para el funcionamiento de la aplicación:

#### Archivos Necesarios

**index.html - Define la estructura de la página.**
Ver código HTML
[Ver código HTML](https://github.com/ImageLinx/ImageLinxPhotoSwap/blob/main/src/index.html)

**hoja_estilos.css - Contiene los estilos visuales para la aplicación.**
[Ver código CSS](https://github.com/ImageLinx/ImageLinxPhotoSwap/blob/main/src/hoja_estilos.css)

**funcionalidades.php - Encargado del procesamiento del lado del servidor.**
[Ver código PHP](https://github.com/ImageLinx/ImageLinxPhotoSwap/blob/main/src/funcionalidades.php)


**Imagen_de_fondo.webp - Imagen de fondo utilizada en la aplicación (opcional, elige tu propia imagen).**
Asegúrate de colocar el archivo Imagen_de_fondo.webp en el mismo directorio. En este caso se utilizo el formato webp por su eficiencia a la hora de subir y descargar imágenes, pero se puede elegir el formato de imagen preferente.

### Automatización de la Gestión de Archivos
Configura una tarea cron para eliminar automáticamente imágenes que han estado en el directorio de subidas por más de 24 horas:

```bash
sudo crontab -u www-data -e
```

Añade la siguiente línea para ejecutar la tarea cada hora, lo cual es más adecuado para un entorno de producción:

```bash
0 * * * * find /var/www/html/ImageLinx.lan/uploads -type f -mtime +1 -exec rm {} \;
```
**Este comando se ejecuta al comienzo de cada hora del día ( 0 * * * * ) y comprueba qué archivos llevan más de 1 día en ese directorio (-mtime +1) para eliminar aquellos que sobrepasen ese periodo de estancia en el directorio uploads**

*Para un entorno real se debería ajustar el tiempo de ejecución de la tarea, ya que sobrecargaría el servidor innecesariamente si está en funcionamiento contínuo. Por ejemplo poner una revisión cada 24 horas.*

### Adición y Configuración de los Módulos Info y Status en Apache2
Módulo Info
Para habilitar el módulo info y configurar el acceso a la información del servidor web a través de una interfaz web, sigue estos pasos:
Habilitar el Módulo Info:

```bash
sudo a2enmod info
```

### Configuración del Acceso en apache2.conf:
Edita el archivo de configuración principal de Apache para agregar la configuración del módulo info:

```bash
sudo nano /etc/apache2/apache2.conf
```

Añade al final del archivo:

```apacheconf
<Location /info>
	SetHandler server-info
	Require ip 127.0.0.1
	Require ip 192.168.1.192
	Require ip 192.168.1.199
</Location>
```

### Reiniciar Apache:
Para que los cambios tengan efecto, reinicia el servidor Apache:

```bash
sudo systemctl restart apache2
```

### Módulo Status
Para habilitar la monitorización avanzada del estado del servidor Apache usando el módulo status:
Habilitar el Módulo Status:

```bash
sudo a2enmod status
```

### Configuración del Acceso en apache2.conf:
Continúa editando el archivo apache2.conf para configurar el acceso al estado del servidor:

 ```bash
sudo nano /etc/apache2/apache2.conf
```

Añade al final del archivo:

```apacheconf
<Location /estado>
	SetHandler server-status
	Require ip 127.0.0.1
	Require ip 192.168.1.192
	Require ip 192.168.1.199
</Location>
```
### Reiniciar Apache:
Reinicia el servidor Apache para aplicar los cambios:

 ```bash
sudo systemctl restart apache2
```

### Adición del Módulo SSL para Conexiones Seguras
Activación del Módulo SSL
Para comenzar con la configuración de conexiones seguras mediante SSL en Apache, primero debes habilitar el módulo SSL:

```bash
sudo a2enmod ssl
```

### Creación del Directorio para Almacenar Certificados
Crea un directorio específico dentro de /etc/apache2 para almacenar los archivos de certificado y clave privada:

```bash
sudo mkdir /etc/apache2/ssl
```

#### Generación del Certificado y Clave Privada
Genera un certificado SSL autofirmado y una clave privada usando OpenSSL. Este certificado tendrá una validez de un año y empleará una clave RSA de 2048 bits:

```bash
sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout /etc/apache2/ssl/key_privada.key -out /etc/apache2/ssl/cert_apache.crt -subj "/C=(Código del país)/ST=(Estado o Provincia)/L=(Localidad)/O=(Nombre de la Organización)/OU=(Unidad Organizativa)/CN=(Nombre Común o dominio)/emailAddress=(Correo Electrónico)"

```

**En este último comando para generar el certificado y la clave privada se deben completar los campos con los datos propios**

### Copia de Seguridad del Archivo de Configuración SSL
Antes de realizar cambios en el archivo de configuración SSL, es una buena práctica realizar una copia de seguridad:

```bash
sudo cp /etc/apache2/sites-available/default-ssl.conf /etc/apache2/sites-available/default-ssl.conf.bak
```

### Configuración del Archivo default-ssl.conf
Edita el archivo default-ssl.conf para especificar las rutas del certificado y la clave privada recién generados, y para configurar el DocumentRoot para apuntar al directorio de tu aplicación web:

```bash
sudo nano /etc/apache2/sites-available/default-ssl.conf
```

Incluye las siguientes líneas en el archivo, ajustando las rutas y el DocumentRoot:
```apacheconf
<VirtualHost *:443>
	ServerAdmin webmaster@localhost

	DocumentRoot /var/www/html/ImageLinx.lan

	ErrorLog ${APACHE_LOG_DIR}/error.log
	CustomLog ${APACHE_LOG_DIR}/access.log combined

	SSLEngine on
	SSLCertificateFile /etc/apache2/ssl/cert_apache.crt
	SSLCertificateKeyFile /etc/apache2/ssl/key_privada.key

	# Demás configuraciones...
```
</VirtualHost>

### Habilitación del Sitio SSL
Habilita el sitio SSL para incluirlo en la configuración de Apache y prepararlo para servir contenido a través de HTTPS:

```bash
sudo a2ensite default-ssl.conf
```

### Recarga de la Configuración de Apache
Recarga la configuración de Apache para aplicar los cambios realizados:

```bash
sudo systemctl reload apache2
```

### Acceso al Sitio y Verificación
Al acceder al sitio a través de un navegador utilizando HTTPS, se mostrará una advertencia de seguridad debido a que el certificado es autofirmado. En un entorno de prueba, puedes proceder y aceptar el riesgo para continuar al sitio web. En un entorno de producción, se recomienda obtener un certificado emitido por una autoridad de certificación reconocida para evitar estas advertencias y asegurar que la conexión sea confiable para los usuarios.
Este proceso configura Apache para usar SSL, asegurando que las conexiones al servidor sean seguras y cifradas, lo que es esencial para proteger la transmisión de datos sensibles, especialmente en aplicaciones como ImageLinx que manejan intercambio de imágenes. Esta configuración también demuestra la importancia de usar conexiones seguras en cualquier plataforma que intercambie datos confidenciales entre el cliente y el servidor.

## Paso 5: Configuración de una Máquina de Gestión Centralizada con SSH
Instalación de OpenSSH-Server
Como parte de la estrategia de gestión centralizada, es crucial que todas las máquinas servidoras del entorno tengan instalado el servicio OpenSSH para permitir la administración remota y segura:
Instalación en Servidores: Instala openssh-server en cada una de las máquinas servidoras para habilitar el acceso remoto seguro:

```bash
sudo apt install openssh-server
```

Este paso asegura que cada servidor pueda ser administrado remotamente desde la máquina de gestión centralizada.
Verificación en la Máquina de Gestión Centralizada: En la máquina destinada a la gestión centralizada, verifica que el paquete openssh-client esté instalado. Este paquete es esencial para iniciar sesiones SSH hacia los otros servidores y generalmente viene preinstalado en muchas distribuciones de Linux:

```bash
ssh -V
```

Si no está instalado, puedes instalarlo con:

```bash
sudo apt install openssh-client
```

### Configuración de Acceso Remoto Mediante SSH
Una vez que openssh-server esté activo en los servidores y openssh-client listo en la máquina de gestión, procede a configurar el acceso remoto:
Establecimiento de Conexiones SSH: Utiliza SSH para conectar desde la máquina de gestión centralizada a cualquier servidor del entorno. Deberás conocer el nombre de host o la dirección IP de cada servidor:

```bash
ssh usuario@hostname
```

usuario: El nombre de usuario en el servidor al que deseas conectarte.
hostname: El nombre de host o dirección IP del servidor.
Ejemplo:

```bash
ssh admin@192.168.1.100
```

Donde admin es el usuario y 192.168.1.100 es la dirección IP del servidor DNS, DHCP o web.
Gestión Centralizada: Este método permite administrar todos los servidores desde un único punto, simplificando las tareas de administración y mantenimiento. La consolidación de la administración en una única máquina de gestión centralizada reduce la complejidad y mejora la eficiencia operativa.
Seguridad de las Conexiones: Asegúrate de que todas las conexiones SSH estén protegidas mediante claves de cifrado fuertes y, si es posible, configura autenticación basada en claves en lugar de contraseñas para aumentar la seguridad.

# Pruebas

## Objetivo General
Verificar y asegurar que todas las configuraciones y funcionalidades del sistema de intercambio de imágenes funcionen correctamente en términos de rendimiento, seguridad y usabilidad.

### Comprobación de Interfaces de Red
#### Descripción
Validar que las configuraciones de red, tanto de servidores como de clientes, se adhieran a las especificaciones de IPs dinámicas y estáticas del servidor DHCP.

#### Procedimiento
- **Paso 1:** Acceder a cada servidor y máquina cliente.
- **Paso 2:** Ejecutar el siguiente comando para revisar las direcciones IP asignadas:
  ```bash
  ip a
  ```

    Paso 3: Verificar que las direcciones IP estáticas y dinámicas asignadas coincidan con las configuraciones previstas.

#### Resultados Esperados

Cada servidor y cliente debe mostrar las direcciones IP conforme a la configuración de DHCP especificada, sin discrepancias.
## Evaluación de la Resolución DNS
### Descripción

Comprobar la capacidad del servidor DNS para resolver nombres de dominio tanto internamente como hacia el exterior.
#### Procedimiento

    Paso 1: Utilizar el comando dig en una máquina cliente para resolver un dominio interno y uno externo, como se muestra a continuación:

    ```bash

    dig www.example.com
    dig google.com
    ```

    Paso 2: Observar las respuestas del servidor DNS a las consultas.

#### Resultados Esperados

El servidor DNS debe resolver correctamente todas las consultas. Las respuestas deben incluir las direcciones IP correspondientes a cada dominio solicitado sin errores.
## Prueba de Funcionamiento del Servidor Web
### Descripción

Asegurar que el servidor web permita la carga de imágenes y el acceso a estas mediante enlaces generados.
#### Procedimiento

    Paso 1: Desde un cliente (Cliente A), cargar una imagen al servidor utilizando la interfaz web proporcionada.
    Paso 2: Acceder al enlace generado desde una máquina cliente diferente (Cliente B) y intentar visualizar o descargar la imagen.
    Paso 3: Confirmar la accesibilidad y la correcta visualización de la imagen.

#### Resultados Esperados

La imagen debe ser accesible y visualizable desde cualquier cliente que utilice el enlace proporcionado. La carga y descarga deben completarse sin errores.
## Comprobación de la Eliminación Automática de Imágenes
### Descripción

Verificar que el sistema elimine automáticamente las imágenes que excedan las 24 horas de permanencia en el servidor, conforme a la configuración de crontab.
#### Procedimiento

    Paso 1: Subir una imagen al directorio "uploads".
    Paso 2: Esperar 24 horas y revisar el directorio para confirmar la eliminación de la imagen.

    ```bash

    ls /var/www/html/ImageLinx.lan/uploads
    ```

    Paso 3: Documentar la presencia inicial y la ausencia posterior de la imagen en el directorio.

#### Resultados Esperados

Las imágenes que superen las 24 horas de antigüedad en el directorio "uploads" deben ser eliminadas automáticamente. La automatización debe funcionar conforme al crontab establecido, sin intervención manual. 

-*Si el servidor no está encendido a la hora que se debiera ejecutar el comando, este no borrará el contenido que lleve más de 24 horas en el directorio uploads y habrá que esperar al comienzo de la siguiente hora. Esto en un entorno de producción no es un problema ya que habitualmente el servidor estará en marcha contínuamente, en un entorno de pruebas donde se está apagando y encendiendo el servidor, sí que habrá que tener esto en cuenta*


## Recomendaciones Finales
- Mantenimiento Regular: Realiza comprobaciones y actualizaciones regulares en todos los servidores para garantizar que el software esté actualizado y que no haya vulnerabilidades de seguridad.

- Monitoreo Continuo: Implementa soluciones de monitoreo para seguir el estado y rendimiento de todos los servidores centralmente, lo cual te permitirá responder rápidamente a cualquier problema que pueda surgir.




