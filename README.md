[![Total Downloads](https://poser.pugx.org/girafa/crud/downloads)](https://packagist.org/packages/girafa/crud)

Crud
========
Base module to extend to simplify crud development.

Installation
============
## Composer

The suggested installation method is via [composer](http://getcomposer.org/):

```sh
php composer.phar require girafa/crud:dev-master
```

or

1. Add this project in your composer.json:

    ```json
    "require": {
        "girafa/crud": "dev-master"
    }
    ```

2. Now tell composer to download Girafa\Crud by running the command:

    ```bash
    $ php composer.phar update
    ```

## Git Submodule

 Clone this project into your `./vendor/` directory

    ```sh
    cd vendor
    git clone https://github.com/girafacomunicacao/girafa-crud.git
    ```

Configuration
=============
### Global configuration
Copy `./vendor/girafa/crud/config/module.config.php` to `./config/autoload/crud.global.php`
This configuration parameters applies to all modules that use Crud

### Per module configuration
Add in `module/YourModule/config/config.php` a section like this

```
    'Crud' => array(
        __NAMESPACE__ => array(
            's_indexTitle'      => 'Index page default',
            's_indexTemplate'   => 'crud/index/index',
            's_newTitle'        => 'New page default',
            's_newTemplate'     => 'crud/index/default-form',
            's_editTitle'       => 'Edit page default',
            's_editTemplate'    => 'crud/index/default-form',
            's_detailTitle'     => 'Detail page default',
            's_detailTemplate'  => 'crud/index/detail',
            's_processErrorTitle'       => 'Form errors page default',
            's_processErrorTemplate'    => 'crud/index/default-form',
            's_deleteRouteRedirect'     => 'crud',
            's_processRouteRedirect'     => 'crud',
        )
    )
```
This configuration parameters applies to all controller extending Crud defined in that namespace

### Per controller configuration
Redefine in your controller parameters you want to edit after call Crud constructor like

```
class IndexController extends \Girafa\Crud\Controller\CrudIndexController {
    
    public function __construct($I_service, $I_form) {
        $entityName = 'Dog';
        parent::__construct($entityName, $I_service, $I_form);
        $this->s_indexTitle = 'Title specific for this controller';
    }
}
```