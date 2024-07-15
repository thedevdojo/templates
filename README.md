# DevDojo Templates Package

This is a package that will add templates support in your Laravel application

## Adding Templates

The **templates** package will look inside of the `resources/templates` folder for any folder that has a `.json` file inside of it with the same name. *(You can change the template folder location in the config)*

As an example if you have a folder called **sample-template** and inside that folder you have another file called **sample-template.json** with the following contents:

```
{
    "name": "Sample Template",
    "version": "1.0"
}
```

This package will detect this as a new template. You can also include a sample screenshot of your template, which would be **sample-template.jpg** *(800x500px) for best results*

In fact, you can checkout the sample-template repo here: [https://github.com/thedevdojo/sample-template](https://github.com/thedevdojo/sample-template)

You can activate this template by setting the `active` column to 1 for that specific template. Then use it like:

```
return view('template::welcome')
```

This will then look in the current active template folder for a new view called `welcome.blade.php` :D

## Template Configs

You may choose to publish a config to your project by running:

```
php artisan vendor:publish
```

You will want to publish the templates config, and you will now see a new config located at `config/templates.php`, which will look like the following:

```
<?php

return [

    'templates_folder' => resource_path('views/templates'),
    'publish_assets' => true,
    'create_tables' => true

];
```

Now, you can choose an alternate location for your templates folder. By default this will be put into the `resources/views` folder; however, you can change that to any location you would like.

Additionally, you can set **publish_assets** to *true* or *false*, if it is set to *true* anytime the templates directory is scanned it will publish the `assets` folder in your template to the public folder inside a new `templates` folder. Set this to *false* and this will no longer happen.
