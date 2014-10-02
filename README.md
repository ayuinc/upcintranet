# Ayu Consulting S.A.C.

Ayu Consulting Expression Engine Boilerplate

Descarga el boilerplate con el siguiente comando: 

	git clone https://github.com/ayuinc/bp_exp_ayu.git nombre_del_nuevo_proyecto

## Etapa de Desarrollo

### Frontend

Desde la consola navega hasta el directorio "stylesheets" y ejecuta: 

  	sass --watch main.scss:main.css

__RECUERDA__: El nombre de tus vistas, branches, clases y id's deberán contener la siguiente nomenclatura: 

###### Vistas

Las vistas son los templates principales. Generalmente representan una vista para el modelo MVC (index, show, new, edit).

	tipoDeUsuario_vista_index/show/new/edit.html

###### Includes

Los _includes_ son aquellos elementos que se repiten a lo largo de vistas principales

	include_nombre.html

Un nuevo proyecto ya incluye dos _include files_ principales dentro de `templates/default_site/includes.group/` que son el `footer.html` y el `header.html`. 

En el `footer.html` únicamente deberás descomentar los JavaScripts que el proyecto requiera y añade nuevos debajo de `<!-- APP CUSTOM JavaScripts -->`. 

###### GIT Branches

	fe_vista_funcionalidad

###### CSS Classes y ID's

Determina si el `selector` que estás por crear pertenece a una vista o a un include. Si el selector __pertenece a una vista__, crea un archivo `_*.scss` dentro de `/stylesheets/app/` que sea igual al nombre de la vista `.html`.

Por el contrario, si el selector pertenece a un include, crea un archivo `_*.scss` que tenga el mismo nombre que el include al que está relacionado dentro de `/stylesheets/app/includes`

### Backend

__IMPORTANTE__: Por ningún motivo deberás de sobreescribir los `config/` files que contiene una instalación vacía de Expression Engine.

Para evitar sobreescribir este repositorio ejecuta el siguiente comando: 

	git remote rm origin

Después ejecuta lo siguiente: 

	git remote add origin *URL al nuevo repositorio del proyecto

#### Nomenclatura backend

###### GIT Branches

	be_vista_funcionalidad

## Etapa de Producción

### Frontend

Desde la consola navega hasta el directorio "stylesheets" y ejecuta: 

  	sass --watch main.min.scss:main.min.css -t compressed

## Consideraciones

Optimiza tu editor de texto así: 

		{
			"caret_style": "phase",
			"font_size": 14,
			"highlight_line": true,
			"line_padding_bottom": 1,
			"line_padding_top": 1,
			"margin": 2,
			"tab_size": 2
		}

__Al nombrar tus clases y id's piensa:__

> Si alguien más tomara este proyecto, sabría a qué me refiero?