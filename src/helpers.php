<?php


if (!function_exists('template_field')){

	function template_field($type, $key, $title, $content = '', $details = '', $placeholder = '', $required = 0){

		$template = \DevDojo\Templates\Models\Template::where('folder', '=', ACTIVE_TEMPLATE_FOLDER)->first();

		$option_exists = $template->options->where('key', '=', $key)->first();

		if(isset($option_exists->value)){
			$content = $option_exists->value;
		}

		$row = new class{ public function getTranslatedAttribute(){} };
		$row->required = $required;
		$row->field = $key;
		$row->type = $type;
		$row->details = $details;
		$row->display_name = $placeholder;

		$dataTypeContent = new class{ public function getKey(){} };
		$dataTypeContent->{$key} = $content;

		$label = '<label for="'. $key . '">' . $title . '<span class="how_to">You can reference this value with <code>template(\'' . $key . '\')</code></span></label>';
		$details = '<input type="hidden" value="' . $details . '" name="' . $key . '_details__template_field">';
		$type = '<input type="hidden" value="' . $type . '" name="' . $key . '_type__template_field">';
		return $label . app('voyager')->formField($row, '', $dataTypeContent) . $details . $type . '<hr>';
	}

}

if (!function_exists('template')){

	function template($key, $default = ''){
		$template = \DevDojo\Templates\Models\Template::where('active', '=', 1)->first();

		if(Cookie::get('template')){
            $template_cookied = \DevDojo\Templates\Models\Template::where('folder', '=', Cookie::get('template'))->first();
            if(isset($template_cookied->id)){
                $template = $template_cookied;
            }
        }

		$value = $template->options->where('key', '=', $key)->first();

		if(isset($value)) {
			return $value->value;
		}

		return $default;
	}

}

if(!function_exists('template_folder')){
	function template_folder($folder_file = ''){

		if(defined('TEMPLATE_FOLDER') && TEMPLATE_FOLDER){
			return 'templates/' . TEMPLATE_FOLDER . $folder_file;
		}

		$template = \DevDojo\Templates\Models\Template::where('active', '=', 1)->first();

		if(Cookie::get('template')){
            $template_cookied = \DevDojo\Templates\Models\Template::where('folder', '=', Cookie::get('template'))->first();
            if(isset($template_cookied->id)){
                $template = $template_cookied;
            }
        }

		define('TEMPLATE_FOLDER', $template->folder);
		return 'templates/' . $template->folder . $folder_file;
	}
}

if(!function_exists('template_folder_url')){
	function template_folder_url($folder_file = ''){

		if(defined('TEMPLATE_FOLDER') && TEMPLATE_FOLDER){
			return url('templates/' . TEMPLATE_FOLDER . $folder_file);
		}

		$template = \DevDojo\Templates\Models\Template::where('active', '=', 1)->first();

		if(Cookie::get('template')){
            $template_cookied = \DevDojo\Templates\Models\Template::where('folder', '=', Cookie::get('template'))->first();
            if(isset($template_cookied->id)){
                $template = $template_cookied;
            }
        }

		define('TEMPLATE_FOLDER', $template->folder);
		return url('templates/' . $template->folder . $folder_file);
	}
}
