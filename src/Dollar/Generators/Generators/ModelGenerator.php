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
        if (!$ilabels = $this->cache->getFields()) {
            return str_replace('{{labels}}', '', $this->template);
        }

        $labels = [];
        foreach ($ilabels as $name => $fields) {
            $labels[] = "'$name'=>'$fields[1]'";
        }

        $labels_ = str_replace('{{labels}}',
            PHP_EOL . "\t\t" . implode(',' . PHP_EOL . "\t\t", $labels) . PHP_EOL . "\t",
            $fillables_);


        /* Add relationships */
        if (!$irelationships = $this->cache->getFields()) {
            return str_replace('{{relationships}}', '', $this->template);
        }

        $relationships = [];
        foreach ($irelationships as $name => $fields) {
            /* Start from the relationships */
            for ($i = 3; $i < count($fields); $i++) {
                foreach (['hm', 'ho', 'btm', 'bt'] as $query) {
                    if (substr($fields[$i], 0, strlen($query)) === $query) {
                        /* Check what type of relationship and prepare function string*/
                        $relModel = explode(' ', $fields[$i]);
                        $relModelType = $relModel[0]; /* hm|ho|btm */
                        $relModelClass = $relModel[1]; /* Relationship Model */
                        $lcRelModelClass = lcfirst($relModelClass);
                        switch ($query) {
                            case 'hm':
                                $functionString = <<<EOT
    public function {$lcRelModelClass}()
    {
        return \$this->hasMany('{$relModelClass}');
    }
EOT;
                                break;
                            case 'ho':
                                $functionString = <<<EOT
    public function {$lcRelModelClass}()
    {
        return \$this->hasOne('{$relModelClass}');
    }
EOT;
                                break;
                            case 'btm':
                                $functionString = <<<EOT
    public function {$lcRelModelClass}()
    {
        return \$this->belongsToMany('{$relModelClass}');
    }
EOT;
                                break;
                            case 'bt':
                                $functionString = <<<EOT
    public function {$lcRelModelClass}()
    {
        return \$this->belongsTo('{$relModelClass}');
    }
EOT;
                                break;
                        }
                        $relationships[] = $functionString;
                    }
                }
            }
        }

        $relationships_ = str_replace('{{relationships}}',
            '' . implode(PHP_EOL . PHP_EOL, $relationships) . PHP_EOL,
            $labels_);

        return $relationships_;
    }

}
