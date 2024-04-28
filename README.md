# ImageLinx: Plataforma de Intercambio de Imágenes

## Acerca de ImageLinx

ImageLinx es una plataforma creada para facilitar el intercambio seguro de imágenes. Con servidores dedicados para DHCP, DNS y servicios web, este proyecto nace de la necesidad de compartir imágenes sin comprometer la privacidad, utilizando un sistema que genera enlaces directos para visualizar las imágenes subidas.

## Empezando

Esta sección te guiará a través de la configuración básica para que puedas empezar a utilizar la plataforma ImageLinx para tus propios fines de intercambio de imágenes.

### Requisitos

Antes de comenzar, necesitarás:

- Una instalación de Ubuntu Server, preferiblemente la versión 20.04 LTS.
- VirtualBox instalado si planeas usar entornos virtuales.
- Conexión a internet para la instalación y configuración.
- Privilegios de administrador en tu servidor.

### Instalación

Para instalar y configurar tu entorno ImageLinx, sigue la [Guía de Configuración Detallada](https://github.com/ImageLinx/ImageLinxPhotoSwap/blob/main/Setup_Guide.md), que te proporcionará todos los pasos necesarios.

*Todas las indicaciones respecto a configuraciones de red muestran como se creo para en este proyecto. Las direcciones IP de los servidores, los rangos de IP dinámicas si se desea para una red interna, las distintas directivas que definen los servidores y todos los valores de configuración de red cada uno podrá adaptarlo a su caso particular.*

**La única configuración que difiere del proyecto real es a la hora de generar el certificado SSL y la clave privada, por temas de privacidad:**

sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout /etc/apache2/ssl/key_privada.key -out /etc/apache2/ssl/cert_apache.crt -subj "/C=(Código del país)/ST=(Estado o Provincia)/L=(Localidad)/O=(Nombre de la Organización)/OU=(Unidad Organizativa)/CN=(Nombre Común o dominio)/emailAddress=(Correo Electrónico)"

**Los campos:"/C=(Código del país)/ST=(Estado o Provincia)/L=(Localidad)/O=(Nombre de la Organización)/OU=(Unidad Organizativa)/CN=(Nombre Común o dominio)/emailAddress=(Correo Electrónico)"  deberán rellenarse con los datos propios que se quieran incluir**

## Uso de la Plataforma

Una vez instalado, podrás subir imágenes a través de una interfaz web intuitiva y obtener enlaces para su distribución. Para más detalles sobre cómo cargar y administrar tus imágenes, visita la sección de documentación.

## Licencia

Este proyecto está licenciado bajo la GNU General Public License v3.0 - vea el archivo [LICENSE](LICENSE) para más detalles.



## Contacto

Para preguntas generales o soporte, por favor contacta a ImageLinx@proton.me




