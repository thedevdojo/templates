<?php

namespace DevDojo\Templates\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateOptions extends Model
{
	protected $table = 'template_options';
    protected $fillable = ['template_id', 'key', 'value'];
}
