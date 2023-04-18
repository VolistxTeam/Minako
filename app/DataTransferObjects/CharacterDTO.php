<?php

namespace App\DataTransferObjects;

class CharacterDTO extends DataTransferObjectBase
{
    public static function fromModel($character): self
    {
        return new self($character);
    }

    public function GetDTO(): array
    {
        $filteredMappingData = [
            [
                'service' => 'notify/character',
                'service_id' => (string)$this->entity->notifyID
            ]
        ];

        foreach ($this->entity->mappings ?? [] as $item) {
            $filteredMappingData[] = MappingDTO::fromModel($item)->GetDTO();
        }

        return [
            'id' => $this->entity->uniqueID,
            'names' => [
                'canonical' => $this->entity->name_canonical,
                'english' => $this->entity->name_english,
                'japanese' => $this->entity->name_japanese,
                'synonyms' => $this->entity->name_synonyms,
            ],
            'description' => $this->entity->description,
            'image' => [
                'width' => $this->entity->image_width,
                'height' => $this->entity->image_height,
                'format' => 'jpg',
                'link' => config('app.url', 'http://localhost') . '/character/' . $this->entity->uniqueID . '/image',
            ],
            'attributes' => $this->entity->attributes,
            'mappings' => $filteredMappingData,
            'created_at' => (string)$this->entity->created_at,
            'updated_at' => (string)$this->entity->updated_at,
        ];
    }


}
