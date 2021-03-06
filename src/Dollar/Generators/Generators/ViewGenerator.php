<?php

namespace Dollar\Generators\Generators;

use Illuminate\Support\Pluralizer;

class ViewGenerator extends Generator
{

    /**
     * Fetch the compiled template for a view
     *
     * @param  string $template Path to template
     * @param  string $name
     * @return string Compiled template
     */
    protected function getTemplate($template, $name)
    {
        $this->template = $this->file->get($template);

        if ($this->needsScaffolding($template)) {
            return $this->getScaffoldedTemplate($name);
        }

        // Otherwise, just set the file
        // contents to the file name
        return $name;
    }

    /**
     * Get the scaffolded template for a view
     *
     * @param  string $name
     * @return string Compiled template
     */
    protected function getScaffoldedTemplate($name)
    {
        $model = $this->cache->getModelName();  // post
        $models = Pluralizer::plural($model);   // posts
        $Models = ucwords($models);             // Posts
        $Model = Pluralizer::singular($Models); // Post

        // Create and Edit views require form elements
        if ($name === 'create.blade' or $name === 'edit.blade') {
            $formElements = $this->makeFormElements();

            $this->template = str_replace('{{formElements}}', $formElements, $this->template);
        }

        // Replace template vars in view
        foreach (array('model', 'models', 'Models', 'Model') as $var) {
            $this->template = str_replace('{{' . $var . '}}', $$var, $this->template);
        }

        // And finally create the table rows
        list($headings, $fields, $editAndDeleteLinks, $showLink) = $this->makeTableRows($model);
        $this->template = str_replace('{{headings}}',
            '<th></th>' . implode(PHP_EOL . "\t\t\t\t", $headings),
            $this->template);
        $this->template = str_replace('{{fields}}',
            PHP_EOL . $showLink . implode(PHP_EOL . "\t\t\t\t\t",
                $fields) . PHP_EOL . $editAndDeleteLinks,
            $this->template);

        return $this->template;
    }

    /**
     * Create the table rows
     *
     * @param  string $model
     * @return Array
     */
    protected function makeTableRows($model)
    {
        $models = Pluralizer::plural($model); // posts

        $fields = $this->cache->getFields();

        // First, we build the table headings
        $headings = array_map(function ($field) {
            /* If field[3]==1*/
            if ($field[3] == 1) {
                return '<th>' . ucwords($field) . '</th>';
            }
            return '';
        },
            array_keys($fields));

        // And then the rows, themselves
        $fields = array_map(function ($field) use ($model) {
            return "<td>{{{ \$$model->$field }}}</td>";
        },
            array_keys($fields));

        // Now, we'll add the edit and delete buttons.
        $editAndDelete = <<<EOT
                    <td>
                        {{ Form::open(array('style' => 'display: inline-block;', 'method' => 'DELETE', 'route' => array('admin.{$models}.destroy', \${$model}->id))) }}
                            {{ Form::submit('Delete', array('class' => 'btn btn-danger')) }}
                        {{ Form::close() }}
                        {{ link_to_route('admin.{$models}.edit', 'Edit', array(\${$model}->id), array('class' => 'btn btn-info')) }}
                    </td>
EOT;

        $showLink = <<<EOT
<td>
{{link_to_route('admin.{$models}.show', \${$model}->id, array(\${$model}->id))}}
</td>
EOT;

        return array($headings, $fields, $editAndDelete, $showLink);
    }

    /**
     * Add Laravel methods, as string,
     * for the fields
     *
     * @return string
     */
    public function makeFormElements()
    {
        $formMethods = array();

        foreach ($this->cache->getFields() as $name => $field) {
            $type = $field[0];
            $label = $field[1];
            $isFillable = $field[2];
            $isTableIndex = $field[3];
            $formalName = ucwords($name);

            // TODO: add remaining types
            switch ($type) {
                case 'integer':
                    $element = "{{ Form::number('$name', Input::old('$name') ? Input::old('$name') : createFaker()->randomNumber(), array('class'=>'form-control')) }}";
                    break;

                case 'text':
                    $element = "{{ Form::textarea('$name', Input::old('$name') ? Input::old('$name') : createFaker()->realText(), array('class'=>'form-control', 'placeholder'=>'$formalName')) }}";
                    break;

                case 'boolean':
                    $element = "{{ Form::checkbox('$name') }}";
                    break;

                default:
                    $element = "{{ Form::text('$name',  Input::old('$name') ? Input::old('$name') : createFaker()->name, array('class'=>'form-control', 'placeholder'=>'$formalName')) }}";
                    break;
            }

            // Now that we have the correct $element,
            // We can build up the HTML fragment
            $frag = <<<EOT
        <div class="form-group">
            {{ Form::label('$name', '$label:', array('class'=>'col-md-2 control-label')) }}
            <div class="col-sm-10">
              $element
            </div>
        </div>

EOT;

            $formMethods[] = $frag;
        }

        return implode(PHP_EOL, $formMethods);
    }

}
