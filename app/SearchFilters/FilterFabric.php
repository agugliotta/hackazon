<?php

namespace App\SearchFilters;

use Illuminate\Http\Request;

class FilterFabric
{
    /** @var BaseFilter[] */
    protected array $_filters = [];

    protected Request $_request;

    private array $_filterConfig = [
        'Price'   => PriceFilter::class,
        'Quality' => QualityFilter::class,
        'Brand'   => BrandFilter::class,
    ];

    public function __construct(Request $request)
    {
        $this->_request = $request;
        $this->init();
    }

    private function init(): void
    {
        foreach ($this->_filterConfig as $name => $className) {
            $this->addFilter($name, $className);
        }
    }

    public function getFilter(string $name): ?BaseFilter
    {
        return $this->_filters[$name] ?? null;
    }

    public function addFilter(string $filterName, string $filterClass, string $filterElementName = ''): void
    {
        if (!isset($this->_filters[$filterName]) && class_exists($filterClass)) {
            $this->_filters[$filterName] = new $filterClass();
            $elementName = empty($filterElementName)
                ? $this->_filters[$filterName]->getFieldName()
                : $filterElementName;
            $value = $this->_request->input($elementName);
            $this->_filters[$filterName]->setValue($value);
        }
    }
}
