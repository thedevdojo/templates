<?php

namespace DevDojo\Templates\Models;

use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    //
    protected $table = 'templates';
    protected $fillable = ['name', 'folder', 'version'];

    public function options(){
    	return $this->hasMany('\DevDojo\Templates\Models\TemplateOptions', 'template_id');
    }
}
