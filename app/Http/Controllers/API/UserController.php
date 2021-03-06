<?php

namespace App\Http\Controllers\API;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;  
use App\Http\Controllers\Controller;

class UserController extends Controller  
{

     /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api');    
        //$this->authorize('isAdmin'); // admin can access all
    }

    /**
     * Display a listing of the resource. 
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //$this->authorize('isAdmin');
        if (\Gate::allows('isAdmin') || \Gate::allows('isAuthor')) {
            return User::latest()->paginate(2);    
        } 
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|max:191',
            'email' => 'required|string|email|max:191|unique:users',
            'password' => 'required|string|min:6', 
        ]);

        return User::create([
            'name' => $request['name'],
            'email' => $request['email'],
            'type' => $request['type'],
            'bio' => $request['bio'],
            'photo' => $request['photo'],
            'password' => Hash::make($request['password']),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Display the specified resource.
     * @return \Illuminate\Http\Response
     */
    public function profile()
    {
        return auth('api')->user();  
    }

    public function updateProfile(Request $request) {
        $user = auth('api')->user(); 

        $this->validate($request, [
            'name' => 'required|string|max:191',
            'email' => 'required|string|email|max:191|unique:users,email,'.$user->id,
            'password' => 'sometimes|required|string|min:6',   
        ]);

        $currentPhoto = $user->photo;

        if($request->photo != $currentPhoto) {
            // image upload 
            $name = time().'.'.explode('/', explode(':', substr($request->photo,0, strpos($request->photo, ';')))[1])[1];  
            // unique string ,., extension
            \Image::make($request->photo)->save(public_path('/img/profile/').$name);

            // $request->photo = $name; 
            $request->merge(['photo' => $name]);

            // old img delete
            $userPhoto = public_path('img/profile/').$currentPhoto; 
            if (file_exists($userPhoto)) {
                @unlink($userPhoto); 
            }
        }

        if (!empty($request->password)) {
            $request->merge( ['password' => Hash::make($request['password']) ]); 
        }

        $user->update($request->all());

        return ['message' => 'Success'];
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $this->validate($request, [
            'name' => 'required|string|max:191',
            'email' => 'required|string|email|max:191|unique:users,email,'.$user->id,
            'password' => 'sometimes|string|min:6',  
        ]);
        $user->update($request->all());

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // if current is admin
        $this->authorize('isAdmin');  
        
        $user = User::findOrFail($id); 

        // delete the user
        $user->delete();  

        // redirect 
        return ['message' => 'User has been Deleted'];  
    }

    // Searching
    public function search(){
         if ($search = \Request::get('q')) {
            $users = User::where(function($query) use ($search){
                $query->where('name','LIKE',"%$search%")
                        ->orWhere('email','LIKE',"%$search%");
            })->paginate(20);
        } else {
            $users = User::latest()->paginate(5); 
        }
         return $users;
     }
}
