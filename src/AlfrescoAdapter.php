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

    public function fileExists(string $path): bool
    {
        return $this->service->existsPath($path);
    }

    public function directoryExists(string $path): bool
    {
        // En Alfresco solemos tratar todo como objeto, 
        // pero puedes validar si es carpeta específicamente si tu provider lo permite
        return $this->service->existsPath($path);
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $info = pathinfo($path);
        $parentPath = $info['dirname'] === '.' ? '' : $info['dirname'];
        
        $this->service->createDocumentByPath($parentPath, $info['basename'], $contents);
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->write($path, stream_get_contents($contents), $config);
    }

    public function read(string $path): string
    {
        return $this->service->getDocumentContent($this->service->getObjectByPath($path)->id);
    }

    public function readStream(string $path)
    {
        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, $this->read($path));
        rewind($stream);
        return $stream;
    }

    public function delete(string $path): void
    {
        $object = $this->service->getObjectByPath($path);
        $this->service->delete($object->id);
    }

    public function deleteDirectory(string $path): void
    {
        $this->delete($path);
    }

    public function createDirectory(string $path, Config $config): void
    {
        $info = pathinfo($path);
        $parentPath = $info['dirname'] === '.' ? '' : $info['dirname'];
        $this->service->createFolder($info['basename'], $this->service->getObjectByPath($parentPath)->id);
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $folder = $this->service->getFolderByPath($path);
        $children = $this->service->getChildren($folder->id);

        foreach ($children as $child) {
            $path = trim($path . '/' . $child->name, '/');
            
            if ($child->isFolder) { // Asumiendo que tus objetos tienen esta propiedad
                yield new DirectoryAttributes($path);
            } else {
                yield new FileAttributes($path, $child->size ?? 0, null, $child->updatedAt ?? null);
            }
        }
    }

    // Implementar el resto de métodos (move, copy, metadata...) usando tus métodos del service
    public function move(string $source, string $destination, Config $config): void {
        $obj = $this->service->getObjectByPath($source);
        $this->service->moveByPath($obj->id, dirname($destination));
        $this->service->rename($obj->id, basename($destination));
    }
}