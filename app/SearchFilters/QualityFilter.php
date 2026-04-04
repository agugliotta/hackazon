<?php

namespace App\SearchFilters;

use App\Models\Option;

class QualityFilter implements BaseFilter
{
    protected $_value = null;
    protected array $_valueVariants = [];
    const OPTION_NAME = 'Quality';

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

    public function setValue($value): void { $this->_value = $value; }

    public function hasValue(): bool
    {
        return !empty($this->_value) && isset($this->_valueVariants[$this->_value]);
    }

    public function getSql(&$model): string { return ''; }

    public function getFieldName(): string { return 'quality-filter'; }

    public function getVariants(): array { return $this->_valueVariants; }
}
