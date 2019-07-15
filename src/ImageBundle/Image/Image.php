<?php
namespace ImageBundle\Image;

class Image implements \JsonSerializable
{
    private $storagePath;
    private $publicPath;
    private $name;

    public function __construct(string $storagePath = null, string $publicPath = null, string $name = null)
    {
        $this->storagePath = $storagePath;
        $this->publicPath = $publicPath;
        $this->name = $name;
    }


    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getStoragePath(): string
    {
        return $this->storagePath;
    }

    public function setStoragePath(string $storagePath): self
    {
        $this->storagePath = $storagePath;
        return $this;
    }

    public function getPublicPath(): string
    {
        return $this->publicPath;
    }

    public function setPublicPath(string $publicPath): self
    {
        $this->publicPath = $publicPath;
        return $this;
    }

    static public function createFromJson(array $json = null): ?self
    {
        $result = null;
        if($json){
            $result = new self($json['storage_path'], $json['public_path'], $json['name'] ?? '');
        }
        return $result;
    }


    function jsonSerialize()
    {
        return [
            'public_path' => $this->publicPath,
            'storage_path' => $this->storagePath,
            'name' => $this->name
        ];
    }

}