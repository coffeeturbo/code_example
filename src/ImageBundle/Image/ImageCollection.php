<?php
namespace ImageBundle\Image;

class ImageCollection implements \JsonSerializable
{
    private $images;

    static public function createFromJson(array $json = null ): self
    {
        $collection = new ImageCollection();

        if((!is_null($json)) && (count($json) > 0)){
            foreach($json as $item => $value)
            {
                $collection->addImage(new Image($value['storage_path'], $value['public_path'], $item ));
            }
        }
        return $collection;
    }

    public function addImage(Image $image)
    {
        $this->images[$image->getName() ?? null] = $image;

        return $this;
    }

    public function getImages(): ?array
    {
        return $this->images;
    }

    function jsonSerialize()
    {
        $result = null;

        if($this->images){
            $result =  array_map(function(Image $image){
                return $image->jsonSerialize();
            }, $this->images);
        }

        return $result;
    }

}