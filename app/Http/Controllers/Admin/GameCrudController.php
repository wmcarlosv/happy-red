<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;

// VALIDATION: change the requests to match your own file names if you need form validation
use App\Http\Requests\GameRequest as StoreRequest;
use App\Http\Requests\GameRequest as UpdateRequest;
use Backpack\CRUD\CrudPanel;
use App\Game as GG;

/**
 * Class GameCrudController
 * @package App\Http\Controllers\Admin
 * @property-read CrudPanel $crud
 */
class GameCrudController extends CrudController
{
    public function setup()
    {
        /*
        |--------------------------------------------------------------------------
        | CrudPanel Basic Information
        |--------------------------------------------------------------------------
        */
        $this->crud->setModel('App\Models\Game');
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/game');
        $this->crud->setEntityNameStrings('Pago', 'Pagos');

        /*
        |--------------------------------------------------------------------------
        | CrudPanel Configuration
        |--------------------------------------------------------------------------
        */

        // TODO: remove setFromDb() and manually define Fields and Columns
        //$this->crud->setFromDb();

        $this->crud->addColumn([
            'type' => 'select', 
            'label' => 'Nombre',
            'name' => 'user_id', 
            'entity' => 'user',
            'attribute' => 'name',
        ]);
        $this->crud->addColumn([
            'type' => 'boolean', 
            'label' => 'Jugando',
            'name' => 'status', 
        ]);

        $this->crud->addColumn([
            'type' => 'boolean', 
            'label' => 'Aprobado',
            'name' => 'approved', 
        ]);

        $this->crud->addColumn([
            'type' => 'check', 
            'label' => 'Envió Pago',
            'name' => 'payment', 
        ]);

        $this->crud->addColumn([
            'type' => 'datetime', 
            'label' => 'Fecha',
            'name' => 'updated_at', 
        ]);

        // column that shows the parent's first name
        $this->crud->addField([
            'type' => 'select', 
            'label' => 'Nombre',
            'name' => 'user_id', 
            'entity' => 'user',
            'attribute' => 'name',
            'attributes' => [
                'readonly'=>'readonly',
                'disabled'=>'disabled',
              ],
        ]);

        $this->crud->addField([
            'type' => 'datetime', 
            'label' => 'Fecha',
            'name' => 'updated_at', 
            'attributes' => [
                'readonly'=>'readonly',
                'disabled'=>'disabled',
              ],
        ]);
        


        $this->crud->addField([
            'type' => 'checkbox', 
            'label' => 'Activo',
            'name' => 'status',
        ]);

        $this->crud->addField([
            'type' => 'checkbox', 
            'label' => 'Aprobado',
            'name' => 'approved', 
        ]);




        // add asterisk for fields that are required in GameRequest
        $this->crud->setRequiredFields(StoreRequest::class, 'create');
        $this->crud->setRequiredFields(UpdateRequest::class, 'edit');
        $this->crud->denyAccess('delete');
        $this->crud->denyAccess('create');
    }

    public function store(StoreRequest $request)
    {
        // your additional operations before save here
        $redirect_location = parent::storeCrud($request);
        // your additional operations after save here
        // use $this->data['entry'] or $this->crud->entry
        return $redirect_location;
    }

    public function update(UpdateRequest $request)
    {
        // your additional operations before save here
        $redirect_location = parent::updateCrud($request);
        // your additional operations after save here
        // use $this->data['entry'] or $this->crud->entry
        return $redirect_location;
    }

    public function edit($id)
    {

        $game = GG::find($id);
        $user_id = $game->user_id;
        $fileName = $game->payment;

    $this->crud->addField([ // image
        'label' => "Comprobante de Pago",
        'name' => "payment",
        'type' => 'image',
        //'attributes' => ['disabled' => 'disabled'],
        'upload' => false,
        'crop' => false, // set to true to allow cropping, false to disable
        'aspect_ratio' => 1, // ommit or set to 0 to allow any aspect ratio
        'disk' => 'local', // in case you need to show images from a different disk
        'prefix' => 'payment/'.$user_id.'/' // in case your db value is only the file name (no path), you can use this to prepend your path to the image src (in HTML), before it's shown to the user;
    ]);

    /*if(!empty($fileName)){
        $image ='
            <div class="row">
                <div class="form-group col-xs-12" style="margin-bottom: 20px;">
                    <center><img id="mainImage" src="/storage/payment/'.$user_id.'/'.$fileName.'"></center>
                </div>
            </div>
            ';
    }else{
        $image = '';
    }

    $this->crud->addField([ // image
    'name' => 'separator',
    'type' => 'custom_html',
    'value' => '<hr>'.$image
    ])->afterField('updated_at');*/

    $content = parent::edit($id);

    return $content;
}

}
