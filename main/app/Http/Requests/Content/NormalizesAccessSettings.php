<?php

namespace App\Http\Requests\Content;

trait NormalizesAccessSettings
{
    protected function normalizeAccessSettings(): void
    {
        if (is_array($this->input('prerequisite_modules')) && array_is_list($this->input('prerequisite_modules'))) {
            $this->merge(['prerequisite_modules' => array_values(array_filter($this->input('prerequisite_modules'), fn ($id) => $id !== null && $id !== ''))]);
        }
    }
}
