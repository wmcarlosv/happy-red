<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;

// VALIDATION: change the requests to match your own file names if you need form validation
use App\Http\Requests\UserRequest as StoreRequest;
use App\Http\Requests\UserRequest as UpdateRequest;
use Backpack\CRUD\CrudPanel;
use App\Invitation;
use App\User;

/**
 * Class UserCrudController
 * @package App\Http\Controllers\Admin
 * @property-read CrudPanel $crud
 */
class UserCrudController extends CrudController
{
    public function setup()
    {
        /*
        |--------------------------------------------------------------------------
        | CrudPanel Basic Information
        |--------------------------------------------------------------------------
        */
        $this->crud->setModel('App\Models\User');
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/user');
        $this->crud->setEntityNameStrings('Usuario', 'Usuarios');

        /*
        |--------------------------------------------------------------------------
        | CrudPanel Configuration
        |--------------------------------------------------------------------------
        */

        // TODO: remove setFromDb() and manually define Fields and Columns
        //$this->crud->setFromDb();

        // Columns
        $this->crud->addColumn(['name' => 'name', 'type' => 'text', 'label' => 'Nombre']);
        $this->crud->addColumn(['name' => 'last_name', 'type' => 'text', 'label' => 'Apellido']);
        $this->crud->addColumn(['name' => 'email', 'type' => 'email', 'label' => 'Email']);
        $this->crud->addColumn(['name' => 'nickname', 'type' => 'text', 'label' => 'Nick']);

        // Fields
        //$this->crud->addField(['name' => 'photo', 'type' => 'image', 'label' => '','upload' => false]);
        $this->crud->addField(['name' => 'name', 'type' => 'text', 'label' => 'Nombre']);
        $this->crud->addField(['name' => 'last_name', 'type' => 'text', 'label' => 'Apellido']);
        $this->crud->addField(['name' => 'dni', 'type' => 'text', 'label' => 'Documento']);
        $this->crud->addField(['name' => 'phone', 'type' => 'text', 'label' => 'Teléfono']);
        $this->crud->addField(['name' => 'email', 'type' => 'email', 'label' => 'Email']);
        $this->crud->addField(['name' => 'nickname', 'type' => 'text', 'label' => 'Nick']);
                
        //$this->crud->addField(['name' => 'code', 'type' => 'text', 'label' => 'Code']);
        //$this->crud->addField(['name' => 'admin', 'type' => 'checkbox', 'label' => 'Admin']);

        // add asterisk for fields that are required in UserRequest
        $this->crud->setRequiredFields(StoreRequest::class, 'create');
        $this->crud->setRequiredFields(UpdateRequest::class, 'edit');
        $this->crud->allowAccess('show');
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

        $user = User::find($id);
        $photo = $user->photo;
        
        $image ='
        <div class="row">
            <div class="form-group col-xs-12" style="margin-bottom: 20px;">
            <center><img id="mainImage" src="/storage/'.$photo.'"></center>
            </div>
        </div>
        ';

        $this->crud->addField([ // image
        'name' => 'separator',
        'type' => 'custom_html',
        'value' => $image
        ])->beforeField('name');

        $content = parent::edit($id);

        return $content;
    }

    
    public function show($id)
    {
        $content = parent::show($id);

        $this->crud->setColumns([
            ['name' => 'name', // the db column name (attribute name)
            'label' => 'Nombre', // the human-readable label for it
            'type' => 'text']
        ,[
            'name' => 'last_name', // the db column name (attribute name)
            'label' => "Apellido", // the human-readable label for it
            'type' => 'text' 
        ]]);

        $this->crud->addColumn(['name' => 'code', 'type' => 'text', 'label' => 'Code']);

        $this->crud->addColumn([
            'name' => '',
            'type' => 'custom_html',
            'value' => $this->_getUsersGame($id),
            
        ]);

        return $content;
    }

    public function _getUsersGame($user_id){
        $html = '';
        
        $invitations = Invitation::select('invitations.*','games.approved')
        ->leftJoin('games', 'games.id', '=', 'invitations.invited_user_game')
        ->where('invitations.invited_user_id','=',$user_id)
        ->where('games.status','>',0)
        ->where('games.approved','=',1)
        ->orderBy('invitations.created_at', 'desc')
        ->get();

        //$html .= $this->_getRow($invitations,0);
        $html .= $this->_getUsersGameLevel($user_id,1);
        $html .= $this->_getUsersGameLevel($user_id,2);
        return $html;
    }

    public function _getUsersGameLevel($user_id,$level){
        $html = '';
        $invitations = Invitation::select('invitations.*','games.approved','users.nickname')
        ->leftJoin('games', 'games.id', '=', 'invitations.invited_user_game')
        ->leftJoin('users', 'users.id', '=', 'invitations.invited_user_id')
        ->where('invitations.status','>',0)
        ->where('invitations.status','<',2)
        //->where('games.approved','=',1)
        ->where('invitations.level_'.$level,'=',$user_id)
        ->orderBy('invitations.created_at', 'desc')
        ->get();
        if(count($invitations) > 0){
            $html = $this->_getRow($invitations,$level,count($invitations));
        }
        return $html;
    }

    public function _getRow($invitations,$level,$total = 0){
        $row = '<div class=""><div style="background:grey" class="text-center"><strong>Level '.$level.'</strong> ('.$total.')</div></div>';
        foreach ($invitations as $key => $invitation) {
            $data = "'Invitación (".$invitation->id.") - user_id: ".$invitation->user_id." | invited_user_id: ".$invitation->invited_user_id." | game: ".$invitation->invited_user_game." | level_1: ".$invitation->level_1." | level_2: ".$invitation->level_2." | level_3: ".$invitation->level_3." | level_4: ".$invitation->level_4." | level_5: ".$invitation->level_5." | status: ".$invitation->status." | description: ".$invitation->description." | created_at: ".$invitation->created_at." | updated_at: ".$invitation->updated_at." | approved: ".$invitation->approved."'";        
            $color = $invitation->approved ? 'green' : 'red'; 
            $row .= '<span style="margin: 1px 3px ;color:    '.$color.'" onclick="alert('.$data.')">[ '.$invitation->nickname.' ]</span>';
        }      
        return '<div class=""><div class="text-center">'.$row.'</div></div>';  
    }

}
