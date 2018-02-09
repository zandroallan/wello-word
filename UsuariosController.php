<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ContraseniaRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Http\Models\Usuario;
use App\Http\Models\catalogos\rol;
use App\Http\Models\Configuracion\RoleUser;
use App\Http\Models\catalogos\Area;
use App\Http\Requests\UsuarioRequest;

class UsuariosController extends Controller
{
    private $route= 'usuarios';
    public function __construct()
    {
        $this->middleware('auth');
        view()->share('titulo', 'Usuarios');
        view()->share('current_route', $this->route);
    }

    //muestra el listado de usuarios.
    public function index()
    {
        //listo los usuarios registrados en la base de datos
        $resultados = Usuario::listar_usuarios()->get();
        return view('mantenimiento.usuario.index',['resultados'=>$resultados]);
    }

    //metodo para crear un nuevo usuario
    public function nuevo()
    {
      $roles= rol::lists_rol();
      $areas= Area::combo_area();
      $isEdit=0;
      return view('mantenimiento.usuario.create',['roles'=>$roles,'isEdit'=>$isEdit, 'areas'=>$areas]);
    }

    //guardo loa datos del nuevo usuario
    public function nuevo_store(UsuarioRequest $request)
    {
       try {


          //busco si existe un usuario duplicado
          $existe=Usuario::buscar_usuario_en_area($request->input('id_area'))->first();

          //comparo si hubo alguna coincidencia con el nickname
          if(count($existe)==1 )
            {
              return redirect()->back()->with(['existe'=>'Un usuario ya fue agregado al area seleccionada'])->withInput();
            }

            //asigno los objetos a las variables
            $usuario['nombre']=$request->input('nombre');
            $usuario['id_area']=$request->input('id_area');
            $usuario['nickname']=$request->input('nickname');
            $usuario['curp']=$request->input('curp');
            $usuario['password']=Hash::make(trim($request->input('password')));
                $r_usuario = Usuario::create($usuario);

                //busco el id insertado para proceder a guardar la imagen
                $r_foto=Usuario::findOrFail($r_usuario->id);
                //la ruta donde se guardaran fisicamente los archivos
                $ruta= public_path().'/img/avatar/';
                //valido que exista un archivo
                if($request->hasFile('file2')){
                  //asigno el archivo a una variable
                  $archivo = $request->file('file2');
                  //creo el nombre del archivo
                  $fileName = "usuario".$r_foto->id.'.'.$archivo->getClientOriginalExtension();
                  //obtengo la extension del archivo
                      $tipo_archivo=$archivo->getClientOriginalExtension();
                      //guardo el archivo en la carpeta del servidor
                      $archivo->move($ruta, $fileName);
                }
                else
                {
                  $fileName='default.png';
                }
                $foto['img']=$fileName;
                $r_foto->fill((array)$foto)->save();

                //guardo los datos del rol
                $rol['user_id']=$r_foto->id;
                $rol['role_id']=$request->input('id_rol');
                $r_rol= RoleUser::create($rol);

                return redirect()->route('usuarios.index')->with('success', "El usuario ha sido <b>creado satisfactoriamente</b>.");

        } catch (Exception $ex) {

        }
    }

    //metodo para editar un nuevo usuario
    public function editar($id_usuario)
    {
      $resultado=Usuario::edit($id_usuario)->first();
      $roles= rol::lists_rol();
      $areas= Area::combo_area();
      $isEdit=1;
      return view('mantenimiento.usuario.edit',['resultado'=>$resultado,'id_usuario'=>$id_usuario, 'roles'=>$roles, 'isEdit'=>$isEdit,'areas'=>$areas]);
    }

    //actualizo los datos del usuario
    public function update(UsuarioRequest $request)
    {
      try {

          //obtengo el id del usuario a editar
          $id_usuario=$request->input('id_usuario');

          //busco los datos del usuario a editar
          $r_usuario=usuario::findOrFail($id_usuario);
          //asigno los objetos a las variables
          $usuario['nombre']=$request->input('nombre');
          $usuario['id_area']=$request->input('id_area');
          $usuario['nickname']=$request->input('nickname');
          $usuario['curp']=$request->input('curp');
          $usuario['password']=Hash::make(trim($request->input('password')));

          //la ruta donde se guardaran fisicamente los archivos
              $ruta= public_path().'/img/avatar/';
              //valido que exista un archivo
              if($request->hasFile('file2')){
                //asigno el archivo a una variable
                $archivo = $request->file('file2');

                //creo el nombre del archivo
                $fileName = "usuario".$r_usuario->id.'.'.$archivo->getClientOriginalExtension();
                //obtengo la extension del archivo
                    $tipo_archivo=$archivo->getClientOriginalExtension();
                    //guardo el archivo en la carpeta del servidor
                    $archivo->move($ruta, $fileName);
                    $usuario['img']=$fileName;

              }

                $r_usuario->fill((array)$usuario)->save();

                //guardo los datos del rol
                $r_rol=RoleUser::edit($r_usuario->id);
                $rol['role_id']=$request->input('id_rol');
                $r_rol->fill((array)$rol)->save();

              return redirect()->route('usuarios.index')->with('success', "El usuario ha sido <b>editado satisfactoriamente</b>.");

        } catch (Exception $ex) {

        }
    }

    public function  contrasenia($id_usuario)
    {
       return view('mantenimiento.usuario.cambiarContrasenia',['id_usuario'=>$id_usuario]);
    }

    public function update_contrasenia($id_usuario, ContraseniaRequest $request)
    {
        try {

              $contra=trim($request->input('password'));
              $confirm=trim($request->input('password_confirmation'));

              if($contra!=$confirm)
              {
                return redirect()->back()->withErrors(['duplicado'=>'Las contraseñas ingresadas no coinciden.'])->withInput();
              }
              else
              {
                $r_contrasenia=usuario::findOrFail($id_usuario);
                $contrasenia['password']=Hash::make(trim($contra));
                $r_contrasenia->fill((array)$contrasenia)->save();

                return redirect()->route('dashboard.index')->with('success', "La contraseña ha sido <b>cambiada satisfactoriamente</b>.");
              }

          } catch (Exception $ex) {

          }
    }
}
