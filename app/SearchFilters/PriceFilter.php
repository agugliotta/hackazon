<?php

namespace App\SearchFilters;

class PriceFilter implements BaseFilter
{
    protected $_value;
    protected array $_valueVariants = [];

    public function __construct()
    {
        $this->_valueVariants[1] = [0, 100];
        $this->_valueVariants[2] = [100, 200];
        $this->_valueVariants[3] = [200, 300];
        $this->_valueVariants[4] = [300, 500];
        $this->_valueVariants[5] = [500, 1000];
    }

    public function getValue() { return $this->_value; }

    public function setValue($value): void { $this->_value = $value; }

    public function hasValue(): bool
    {
        return !empty($this->_value) && isset($this->_valueVariants[$this->_value]);
    }

    public function getSql(&$model): void
    {
        $values = $this->_valueVariants[$this->getValue()];
        $model->where('Price', '>=', $values[0])->where('Price', '<=', $values[1]);
    }

    public function getFieldName(): string { return 'price-filter'; }

    public function getVariants(): array { return $this->_valueVariants; }

    public function getLabel($id): string
    {
        return isset($this->_valueVariants[$id])
            ? '$' . $this->_valueVariants[$id][0] . ' &ndash; $' . $this->_valueVariants[$id][1]
            : '--';
    }
}
