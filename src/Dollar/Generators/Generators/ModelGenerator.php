<?php

namespace Dollar\Generators\Generators;

/**
 * Class ModelGenerator
 * @package Dollar\Generators\Generators
 */
class ModelGenerator extends Generator
{

    /**
     * Fetch the compiled template for a model
     *
     * @param  string $template Path to template
     * @param  string $className
     * @return string Compiled template
     */
    protected function getTemplate($template, $className)
    {
        $this->template = $this->file->get($template);

        if ($this->needsScaffolding($template)) {
            $this->template = $this->getScaffoldedModel($className);
        }

        return str_replace('{{className}}', $className, $this->template);
    }

    /**
     * Get template for a scaffold
     *
     * @param $className
     * @return string
     * @internal param string $template Path to template
     * @internal param string $name
     */
    protected function getScaffoldedModel($className)
    {
        /* Add rules */
        if (!$fields = $this->cache->getFields()) {
            return str_replace('{{rules}}', '', $this->template);
        }

        $rules = array_map(function ($field) {
            return "'$field' => 'required'";
        },
            array_keys($fields));

        $rules_ = str_replace('{{rules}}',
            PHP_EOL . "\t\t" . implode(',' . PHP_EOL . "\t\t", $rules) . PHP_EOL . "\t",
            $this->template);

        //        return $rules_;

        /* Add fillables */
        if (!$ifillables = $this->cache->getFields()) {
            return str_replace('{{fillables}}', '', $this->template);
        }

        $fillables = [];
        foreach ($ifillables as $name => $fields) {
            if ($fields[2] == 1 /* $isFillable */) {
                $fillables[] = "'$name'";
            }
        }

        $fillables_ = str_replace('{{fillables}}',
            PHP_EOL . "\t\t" . implode(',' . PHP_EOL . "\t\t", $fillables) . PHP_EOL . "\t",
            $rules_);

        /* Add labels */

        return $fillables_;
    }

}
