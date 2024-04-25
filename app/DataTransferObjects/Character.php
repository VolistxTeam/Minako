<?php

namespace App\DataTransferObjects;

class Character extends DataTransferObjectBase
{
    public static function fromModel($character): self
    {
        return new self($character);
    }

    public function GetDTO(): array
    {
        return [
            'id' => $this->entity->uniqueID,
            'names' => $this->formatNames(),
            'description' => $this->entity->description,
            'image' => $this->formatImage(),
            'attributes' => $this->entity->attributes,
            'mappings' => $this->formatMappings(),
            'created_at' => (string) $this->entity->created_at,
            'updated_at' => (string) $this->entity->updated_at,
        ];
    }

    private function formatNames(): array
    {
        return [
            'canonical' => $this->entity->name_canonical,
            'english' => $this->entity->name_english,
            'japanese' => $this->entity->name_japanese,
            'synonyms' => $this->entity->name_synonyms,
        ];
    }

    private function formatImage(): array
    {
        $appUrl = config('app.url', 'http://localhost');

        return [
            'width' => $this->entity->image_width,
            'height' => $this->entity->image_height,
            'format' => 'jpg',
            'link' => "$appUrl/character/{$this->entity->uniqueID}/image",
        ];
    }

    private function formatMappings(): array
    {
        return collect($this->entity->mappings ?? [])->map(function ($mapping) {
            return Mapping::fromModel($mapping)->GetDTO();
        })->prepend([
            'service' => 'notify/anime',
            'service_id' => (string) $this->entity->uniqueID,
        ])->toArray();
    }
}
