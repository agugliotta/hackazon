<?php
/**
 * Created by IntelliJ IDEA.
 * User: Nikolay Chervyakov 
 * Date: 08.12.2014
 * Time: 16:46
 */


namespace VulnModule\Storage;


use VulnModule\Config\ConditionalVulnerableElement;
use VulnModule\Config\Context;
use VulnModule\Config\Field;
use VulnModule\Config\ICondition;
use VulnModule\Config\VulnerableElement;
use VulnModule\Vulnerability;
use VulnModule\VulnerabilityFactory;

/**
 * Renders the context in HTML format
 * @package VulnModule\Storage
 */
class HTMLWriter implements IWriter
{
    protected string $viewPath = '';

    function __construct()
    {
        $this->viewPath = resource_path('views/admin/context/partials');
    }

    /**
     * Renders a PHP partial template with variables via output buffering.
     */
    protected function renderTemplate(string $name, array $vars = []): string
    {
        $templatePath = $this->viewPath . '/' . $name . '.php';
        if (!file_exists($templatePath)) {
            return '';
        }
        extract($vars, EXTR_SKIP);
        ob_start();
        include $templatePath;
        return ob_get_clean();
    }

    public function write(Context $context)
    {
        return $this->renderTemplate('index', ['result' => $this->renderContext($context)]);
    }

    public function renderContext(Context $context)
    {
        $children = '';
        $fields = '';

        $vulnerabilities = $this->renderVulnerabilityTree($context->getVulnerabilityElement());

        if ($context->hasFields()) {
            $fieldsHtml = [];
            foreach ($context->getFields() as $field) {
                $fieldsHtml[] = $this->renderField($field);
            }
            $fields = implode('', $fieldsHtml);
        }

        if ($context->hasChildren()) {
            $childrenHtml = [];

            foreach ($context->getChildrenArray() as $child) {
                $childrenHtml[] = $this->renderContext($child);
            }

            $children = implode('', $childrenHtml);
        }


        return $this->renderTemplate('context', [
            'vulnerabilities' => $vulnerabilities,
            'children'        => $children,
            'fields'          => $fields,
            'contextName'     => $context->getName(),
            'type'            => $context->getType(),
        ]);
    }

    /**
     * @param VulnerableElement $element
     * @return string
     */
    public function renderVulnerabilityTree(VulnerableElement $element)
    {
        $vulnerabilities = [];
        $childrenVulns = '';
        $conditions = [];

        if ($element->hasChildren()) {
            $childrenHtml = [];

            foreach ($element->getChildrenArray() as $child) {
                $childrenHtml[] = $this->renderVulnerabilityTree($child);
            }

            $childrenVulns = implode('', $childrenHtml);
        }

        if ($element instanceof ConditionalVulnerableElement) {
            /** @var ICondition $condition */
            foreach ($element->getConditions()->getConditions() as $condition) {
                $conditions[$condition->getName()] = $condition->toArray();
            }
        }

        /** @var Vulnerability $vuln */
        foreach ($element->getVulnerabilitySet()->getVulnerabilities() as $vuln) {
            $vulnerabilities[$vuln->getName()] = $vuln->asArray();
        }
        sort($vulnerabilities);

        $vulnNames = VulnerabilityFactory::instance()->getAllVulnerabilityNames();
        $computedVulnerabilities = [];

        /** @var Vulnerability $vuln */
        foreach ($vulnNames as $vulnName) {
            $computedVulnerabilities[] = $element->getComputedVulnerability($vulnName)->asArray();
        }

        return $this->renderTemplate('vuln_element', [
            'vulnerabilities'        => $vulnerabilities,
            'computedVulnerabilities' => $computedVulnerabilities,
            'childrenVulns'          => $childrenVulns,
            'conditionList'          => $conditions,
            'isRoot'                 => false,
        ]);
    }

    private function renderField(Field $field)
    {
        return $this->renderTemplate('field', [
            'fieldName'       => $field->getName(),
            'source'          => $field->getSource(),
            'vulnerabilities' => $this->renderVulnerabilityTree($field->getVulnerabilityElement()),
        ]);
    }
}