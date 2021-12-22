

# Alfresco Client for Laravel
Access client to the Alfresco APIs (Rest and CMIS)

<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

## Instalació
```bash
composer require ajtarragona/alfresco-laravel:"@dev"
``` 

## Configuració
Pots configurar el paquet a través de l'arxiu `.env` de l'aplicació. Aquests son els parámetres disponibles :

Paràmetre |  Descripció  | Valors
--- | --- | --- 
ALFRESCO_URL | Url base de la API  | `http://ip_or_domain:port/alfresco/`
ALFRESCO_API | Api type | `rest` / <ins>`cmis`</ins> 
ALFRESCO_API_VERSION  | Codi de versió | `1.0` (rest) / <ins>`1.1`</ins> (cmis)
ALFRESCO_REPOSITORY_ID  | ID del repositori | `-default-` per defecte
ALFRESCO_BASE_ID  | ID alfresco del directori base |  
ALFRESCO_BASE_PATH  | Path del directori base |  
ALFRESCO_USER  | Usuari |  
ALFRESCO_PASSWORD  | Password | --- 
ALFRESCO_DEBUG  | Mode debug (habilita més logs) | `true` / <ins>`false`</ins>
ALFRESCO_REPEATED_POLICY  | Política a seguir en cas de pujar un arxiu repetit | <ins>`rename`</ins> / `overwrite` / `deny`
ALFRESCO_EXPLORER  | Habilita un [explorador d'arxius](#explorador) | `true` / <ins>`false`</ins> 
ALFRESCO_VERIFY_SSL  | Habilita la verificación del SSL del servidor | `true` / <ins>`false`</ins>  



Alternativament, pots publicar l'arxiu de configuració del paquet amb la comanda:

```bash
php artisan vendor:publish --tag=ajtarragona-alfresco
```

Això copiarà l'arxiu `alfresco.php` a la carpeta `config`.

 

## Ús
Un cop configurat, el paquet està a punt per fer-se servir.
Ho pots fer de les següents maneres:


**A través d'una `Facade`:**
```php
use Alfresco;
...
public  function  test(){
    $file=Alfresco::getDocument("xxx-yyy-zzz");
    ...
}
```

Per Laravel < 5.6, cal registrar l'alias de la Facade a l'arxiu `config/app.php` :
 
```php
'aliases'  =>  [
    ...
    'Alfresco'  =>  Ajtarragona\AlfrescoLaravel\Facades\Alfresco::class
]
```

  

**Vía Injecció de dependències:**
Als teus controlladors, helpers, model:


```php
use Ajtarragona\AlfrescoLaravel\Models\AlfrescoService;
...

public  function  test(AlfrescoService  $client){
    $file=$client->getDocument("xxx-yyy-zzz");
    ...
}
```

**Vía funció `helper`:**
```php
...
public  function  test(){
    $file=alfresco()->getDocument("xxx-yyy-zzz");
    ...
}
```

  
  

## Funcions

Funció | Descripció | Paràmetres | Retorn | Excepcions
--- | --- | --- | --- | ---
**getBasepath** | Retorna el directori arrel des del qual s'executaran els altres mètodes |  | `string` | 
**setBasepath** | Defineix el directori arrel des del qual s'executaran els altres mètodes | `string:$path`|  
**getBaseFolder** | Retorna el BaseFolder (el directori arrel a partir del basepath, si està definit) | | `AlfrescoFolder` |	
**exists** | Retorna si existeix un objecte amb l'ID passat | `string:$objectId` | `boolean` |
**existsPath** | Retorna si existeix un objecte amb el path passat | `string:$objectPath` | `boolean` |
**getObject** | Retorna un objecte amb l'ID passat | `string:$objectId` |  `AlfrescoObject` | 
**getObjectByPath** | Retorna un objecte amb el path passat | `string:$objectPath` | `AlfrescoObject` | 
**downloadObject** | Descarrega el contingut d'un objecte passant el seu ID | `string:$objectId`<br/> `boolean:$stream=false` | Binary Content |  `AlfrescoObjectNotFoundException`
**getFolder** | Retorna una carpeta d'Alfresco passant el seu ID | `string:$folderId` | `AlfrescoFolder` |  `AlfrescoObjectNotFoundException`
**getFolderByPath** | Retorna una carpeta d'Alfresco passant la seva ruta (a partir del basepath) | `string:$folderPath` | `AlfrescoFolder` |  `AlfrescoObjectNotFoundException`
**getParent** | Retorna la carpeta pare de l'objecte amb l'ID passat | `string:$objectId` | `AlfrescoFolder` |  `AlfrescoObjectNotFoundException`
**getChildren** | Retorna els fills d'una carpeta d'Alfresco passant el seu ID | `string:$folderId` | `AlfrescoFolder[]` |  `AlfrescoObjectNotFoundException`
**createFolder** | Crea una carpeta passant el seu nom dins la carpeta amb l'ID passat.<br>Retorna la carpeta creada | `string:$folderName`<br>`string:$parentId=null` | `AlfrescoFolder` |  `AlfrescoObjectNotFoundException`<br>`AlfrescoObjectAlreadyExistsException`
**getDocument** | Retorna un document d'Alfresco passant el seu ID | `string:$documentId` | `AlfrescoDocument` |  `AlfrescoObjectNotFoundException`
**getDocumentByPath** | Retorna un document d'Alfresco passant la seva ruta (a partir del basepath) | `string:$documentPath` | `AlfrescoDocument` |  `AlfrescoObjectNotFoundException`
**getDocumentContent** | Retorna el contingut binari d'un document d'Alfresco passant el seu ID | `string:$documentId` | Binary Content | 
**delete** | Elimina el document o carpeta d'Alfresco amb l'ID passat | `string:$objectId` | `boolean` |  `AlfrescoObjectNotFoundException`
**copy** | Copia el document o carpeta d'Alfresco amb l'ID passat dins de la carpeta amb l'ID passat. Retorna el nou objecte. | `string:$objectId`<br>`string:$folderId` |   `AlfrescoObject` | `AlfrescoObjectNotFoundException`
**copyByPath** | Copia el document o carpeta d'Alfresco amb l'ID passat dins de la carpeta amb la ruta passada (a partir del basepath). Retorna el nou objecte. | `string:$objectId` <br>`string:$folderPath` | `AlfrescoObject` |  `AlfrescoObjectNotFoundException`<br>`AlfrescoObjectAlreadyExistsException`
**move** | Mou el document o carpeta d'Alfresco amb l'ID passat dins de la carpeta amb l'ID passat. Retorna el nou objecte. | `string:$objectId` <br> `string:$folderId` | `AlfrescoObject`   |`AlfrescoObjectNotFoundException`<br>`AlfrescoObjectAlreadyExistsException`
**moveByPath** | Mou el document o carpeta d'Alfresco amb l'ID passat dins de la carpeta amb la ruta passada (a partir del basepath). Retorna el nou objecte. | `string:$objectId`<br>`string:$folderPath` | `AlfrescoObject`  |  `AlfrescoObjectNotFoundException`<br>`AlfrescoObjectAlreadyExistsException`
**rename** | Renombra el document o carpeta d'Alfresco amb l'ID passat amb un nou nom. Retorna el nou objecte. | `string:$objectId`<br>`string:$newName` | `AlfrescoObject`  |  `AlfrescoObjectNotFoundException`<br>`AlfrescoObjectAlreadyExistsException`
**createDocument** | Crea un nou document a Alfresco a partir del contingut binari a la carpeta pare amb l'ID passat | `string:$parentId`<br>`string:$filename`<br>`string:$filecontent` | `AlfrescoObject`  |  `AlfrescoObjectNotFoundException`<br>`AlfrescoObjectAlreadyExistsException`
**createDocumentByPath** | Crea un nou document a Alfresco a partir del contingut binari a la carpeta pare amb la ruta passada (a partir del basepath)| `string:$parentPath`<br>`string:$filename`<br>`string:$filecontent` | `AlfrescoObject`  |  `AlfrescoObjectNotFoundException`<br>`AlfrescoObjectAlreadyExistsException`
**upload** | Carrega un document a Alfresco a partir d'un objecte `UploadedFile` o un array d'aquests. Típicament s'utilitza des d'un controlador Laravel, recollint els arxius de la request que venen d'un formulari multipart | `string:$parentId`<br>`UploadedFile-UploadedFile[]:$documents`  |  `AlfrescoDocument` or `string` in case of error
**getSites** | Retorna todos los Sites de alfresco (como objetos AlfrescoFolder)|   |  `AlfrescoFolder[]`
**search** | Busca documents que continguin el text passat al nom o al contingut a partir de la carpeta amb l'ID passat o l'arrel| `string:$query`<br>`string:$folderId=null`<br>`boolean:$recursive:false`  |  `AlfrescoObject[]` | `AlfrescoObjectNotFoundException`
**searchByPath** | Busca documents que continguin el text passat al nom o al contingut a partir de la carpeta amb la ruta passada (a partir de la carpeta arrel o al basepath si està definit) | `string:$query`<br>`string:$folderPath=null`<br>`boolean:$recursive:false`  |  `AlfrescoObject[]` | `AlfrescoObjectNotFoundException`



<a name="explorador"></a>
## Explorador d'arxius
Si habilitem  el paràmetre: 
```
ALFRESCO_DEBUG = true 
```
a l'arxiu `.env`, podem accedir a un *file-explorer* a la ruta: 
`/ajtarragona/alfresco/explorer`

> Aquesta funcionalitat requereixen el paquet **web-components**: <br><br>[https://github.com/ajtarragona/web-components](https://github.com/ajtarragona/web-components)<br><br>És una ruta securitzada i només s'hi podrà accedir si habilitem l'autenticació a la nostra aplicació Laravel.



