<?php

namespace Ajtarragona\AlfrescoLaravel;

use Ajtarragona\AlfrescoLaravel\Models\AlfrescoService;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\StorageAttributes;

class AlfrescoAdapter implements FilesystemAdapter
{
    protected $service;

    public function __construct(AlfrescoService $service)
    {
        $this->service = $service;
    }

    public function fileExists(string $path): bool {
        return $this->service->existsPath($path);
    }

    public function directoryExists(string $path): bool {
        // Implementar lógica para comprobar si la ruta es carpeta
        return $this->service->existsPath($path); 
    }

    // En V3, write devuelve void. Si falla, lanza excepción.
    public function write(string $path, string $contents, Config $config): void {
        $info = pathinfo($path);
        $parent = $info['dirname'] === '.' ? '' : $info['dirname'];
        $this->service->createDocumentByPath($parent, $info['basename'], $contents);
    }

    public function writeStream(string $path, $contents, Config $config): void {
        $this->write($path, stream_get_contents($contents), $config);
    }

    public function read(string $path): string {
        $doc = $this->service->getObjectByPath($path);
        return $this->service->getDocumentContent($doc->id);
    }

    // listContents ahora debe devolver un iterable (yield) de objetos StorageAttributes
    public function listContents(string $path, bool $deep): iterable {
        $folder = $this->service->getFolderByPath($path);
        $children = $this->service->getChildren($folder->id);
        // dd('listContents',$path, $children);

        foreach ($children as $child) {
            $childPath = ltrim($path . '/' . $child->name, '/');
            
            if ($child->isFolder()) {
                yield new DirectoryAttributes($childPath);
            } else {
                yield new FileAttributes(
                    $childPath, 
                    $child->size ?? 0, 
                    null, 
                    $child->updatedAt ?? null
                );
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function readStream(string $path)
    {
        $contents = $this->read($path);
        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, $contents);
        rewind($stream);
        return $stream;
    }

    /**
     * @inheritDoc
     */
    public function delete(string $path): void
    {
        try {
            $object = $this->service->getObjectByPath($path);
            $this->service->delete($object->id);
        } catch (\Exception $e) {
            throw \League\Flysystem\UnableToDeleteFile::atLocation($path, $e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function deleteDirectory(string $path): void
    {
        try {
            $this->delete($path);
        } catch (\Exception $e) {
            throw \League\Flysystem\UnableToDeleteDirectory::atLocation($path, $e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function createDirectory(string $path, Config $config): void
    {
        try {
            $info = pathinfo($path);
            $parentPath = $info['dirname'] === '.' ? '' : $info['dirname'];
            
            // Obtenemos el ID del padre para crear la carpeta
            $parent = $this->service->getObjectByPath($parentPath);
            $this->service->createFolder($info['basename'], $parent->id);
        } catch (\Exception $e) {
            throw \League\Flysystem\UnableToCreateDirectory::atLocation($path, $e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function setVisibility(string $path, string $visibility): void
    {
        // Alfresco maneja permisos vía ACLs, Flysystem usa public/private.
        // Si no necesitas mapearlo, puedes dejarlo vacío o lanzar excepción si es obligatorio.
    }

    /**
     * @inheritDoc
     */
    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            $obj = $this->service->getObjectByPath($source);
            $destDir = dirname($destination);
            $newName = basename($destination);

            $this->service->copyByPath($obj->id, $destDir === '.' ? '' : $destDir);
            
            // Si el nombre en destino es diferente, renombramos la copia
            if ($newName !== $obj->name) {
                // Nota: copyByPath debería devolver el nuevo objeto para obtener su ID
                // Si no lo devuelve, habría que buscarlo en el destino para renombrar.
            }
        } catch (\Exception $e) {
            throw \League\Flysystem\UnableToCopyFile::fromLocationTo($source, $destination, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $obj = $this->service->getObjectByPath($source);
            $destDir = dirname($destination);
            $newName = basename($destination);

            // Mover a la nueva carpeta
            $this->service->moveByPath($obj->id, $destDir === '.' ? '' : $destDir);
            
            // Si el nombre cambia, renombrar
            if ($newName !== $obj->name) {
                $this->service->rename($obj->id, $newName);
            }
        } catch (\Exception $e) {
            throw \League\Flysystem\UnableToMoveFile::fromLocationTo($source, $destination, $e);
        }
    }


    // Métodos de metadatos (ahora obligatorios en la interfaz)
    public function visibility(string $path): FileAttributes {
        return new FileAttributes($path, null, 'public');
    }

    public function mimeType(string $path): FileAttributes {
        return new FileAttributes($path, null, null, null, 'application/octet-stream');
    }

    public function lastModified(string $path): FileAttributes {
        $doc = $this->service->getObjectByPath($path);
        return new FileAttributes($path, null, null, $doc->updatedAt ?? time());
    }

    public function fileSize(string $path): FileAttributes {
        $doc = $this->service->getObjectByPath($path);
        return new FileAttributes($path, $doc->size ?? 0);
    }
}