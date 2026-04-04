<?php

namespace App\SearchFilters;

use App\Models\Option;

class BrandFilter implements BaseFilter
{
    protected array $_value = [];
    protected array $_valueVariants = [];
    const OPTION_NAME = 'Brand';

    public function __construct()
    {
        $this->init();
    }

    private function init(): void
    {
        $option = Option::where('name', self::OPTION_NAME)->first();
        if ($option) {
            foreach ($option->variants as $var) {
                $this->_valueVariants[$var->variantID] = $var->name;
            }
        }
    }

    public function getValue() { return $this->_value; }

    public function setValue($value): void
    {
        $this->_value = is_array($value) ? $value : (!empty($value) ? [$value] : []);
    }

    public function hasValue(): bool
    {
        return count($this->_value) > 0;
    }

    public function getSql(&$model): string { return ''; }

    public function getFieldName(): string { return 'brand-filter'; }

    public function getVariants(): array { return $this->_valueVariants; }
}
