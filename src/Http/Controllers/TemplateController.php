<?php

namespace DevDojo\Templates\Http\Controllers;

use Voyager;
use Illuminate\Http\Request;
use \DevDojo\Templates\Models\Template;
use Illuminate\Support\Facades\File;
use \DevDojo\Templates\Models\TemplateOptions;
use TCG\Voyager\Http\Controllers\Controller;

class TemplatesController extends Controller
{
    private $templates_folder = '';

    public function __construct(){
        $this->templates_folder = config('templates.templates_folder', resource_path('views/templates'));
    }

    public function index(){

        // Anytime the admin visits the template page we will check if we
        // need to add any more templates to the database
        $this->installTemplates();
        $templates = Template::all();

        return view('templates::index', compact('templates'));
    }

    private function getTemplatesFromFolder(){
        $templates = array();

        if(!file_exists($this->templates_folder)){
            mkdir($this->templates_folder);
        }

        $scandirectory = scandir($this->templates_folder);

        if(isset($scandirectory)){

            foreach($scandirectory as $folder){
                //dd($template_folder . '/' . $folder . '/' . $folder . '.json');
                $json_file = $this->templates_folder . '/' . $folder . '/' . $folder . '.json';
                if(file_exists($json_file)){
                    $templates[$folder] = json_decode(file_get_contents($json_file), true);
                    $templates[$folder]['folder'] = $folder;
                    $templates[$folder] = (object)$templates[$folder];
                }
            }

        }

        return (object)$templates;
    }

    private function installTemplates() {

        $templates = $this->getTemplatesFromFolder();

        foreach($templates as $template){
            if(isset($template->folder)){
                $template_exists = Template::where('folder', '=', $template->folder)->first();
                // If the template does not exist in the database, then update it.
                if(!isset($template_exists->id)){
                    $version = isset($template->version) ? $template->version : '';
                    Template::create(['name' => $template->name, 'folder' => $template->folder, 'version' => $version]);
                    if(config('templates.publish_assets', true)){
                        $this->publishAssets($template->folder);
                    }
                } else {
                    // If it does exist, let's make sure it's been updated
                    $template_exists->name = $template->name;
                    $template_exists->version = isset($template->version) ? $template->version : '';
                    $template_exists->save();
                    if(config('templates.publish_assets', true)){
                        $this->publishAssets($template->folder);
                    }
                }
            }
        }
    }

    public function activate($template_folder){

        $template = Template::where('folder', '=', $template_folder)->first();

        if(isset($template->id)){
            $this->deactivateTemplates();
            $template->active = 1;
            $template->save();
            return redirect()
                ->route("voyager.template.index")
                ->with([
                        'message'    => "Successfully activated " . $template->name . " template.",
                        'alert-type' => 'success',
                    ]);
        } else {
            return redirect()
                ->route("voyager.template.index")
                ->with([
                        'message'    => "Could not find template " . $template_folder . ".",
                        'alert-type' => 'error',
                    ]);
        }

    }

    public function delete(Request $request){
        $template = Template::find($request->id);
        if(!isset($template)){
            return redirect()
                ->route("voyager.template.index")
                ->with([
                        'message'    => "Could not find template to delete",
                        'alert-type' => 'error',
                    ]);
        }

        $template_name = $template->name;

        // if the folder exists delete it
        if(file_exists($this->templates_folder.'/'.$template->folder)){
            File::deleteDirectory($this->templates_folder.'/'.$template->folder, false);
        }

        $template->delete();

        return redirect()
                ->back()
                ->with([
                        'message'    => "Successfully deleted template " . $template_name,
                        'alert-type' => 'success',
                    ]);

    }

    public function options($template_folder){

        $template = Template::where('folder', '=', $template_folder)->first();

        if(isset($template->id)){

            $options = [];

            return view('templates::options', compact('options', 'template'));

        } else {
            return redirect()
                ->route("voyager.template.index")
                ->with([
                        'message'    => "Could not find template " . $template_folder . ".",
                        'alert-type' => 'error',
                    ]);
        }
    }

    public function options_save(Request $request, $template_folder){
        $template = Template::where('folder', '=', $template_folder)->first();

        if(!isset($template->id)){
            return redirect()
                ->route("voyager.template.index")
                ->with([
                        'message'    => "Could not find template " . $template_folder . ".",
                        'alert-type' => 'error',
                    ]);
        }

        foreach($request->all() as $key => $content){

            // If we have a type checkbox and it is unchecked we need to set a value to null
            if($content == 'checkbox'){
                $field = str_replace('_type__template_field', '', $key);
                if(!isset($request->{$field})){
                    $request->request->add([$field => null]);
                    $key = $field;
                }
            }


            if(!$this->stringEndsWith($key, '_details__template_field') && !$this->stringEndsWith($key, '_type__template_field') && $key != '_token'){
                
                $type = $request->{$key.'_type__template_field'};
                $details = $request->{$key.'_details__template_field'};
                $row = (object)['field' => $key, 'type' => $type, 'details' => $details];



                $value = $this->getContentBasedOnType($request, 'templates', $row);

                $option = TemplateOptions::where('template_id', '=', $template->id)->where('key', '=', $key)->first();


                // If we already have this key with the Template ID we can update the value
                if(isset($option->id)){
                    $option->value = $value;
                    $option->save();
                } else {
                    TemplateOptions::create(['template_id' => $template->id, 'key' => $key, 'value' => $value]);
                }
            }
        }


        return redirect()
                ->back()
                ->with([
                        'message'    => "Successfully Saved Template Options",
                        'alert-type' => 'success',
                    ]);


    }

    function stringEndsWith($haystack, $needle)
    {
        $length = strlen($needle);

        return $length === 0 ||
        (substr($haystack, -$length) === $needle);
    }

    private function deactivateTemplates(){
        Template::query()->update(['active' => 0]);
    }

    private function publishAssets($template) {
        $template_path = public_path('templates/'.$template);

        if(!file_exists($template_path)){
            if(!file_exists(public_path('templates'))){
                mkdir(public_path('templates'));
            }
            mkdir($template_path);
        }

        File::copyDirectory($this->templates_folder.'/'.$template.'/assets', public_path('templates/'.$template));
        File::copy($this->templates_folder.'/'.$template.'/'.$template.'.jpg', public_path('templates/'.$template.'/'.$template.'.jpg'));
    }
}
