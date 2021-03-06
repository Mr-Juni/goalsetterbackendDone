<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\ConfirmationLink;
Use App\Activities;
Use App\User;


class RegistrationController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public function store(Request $request, User $user, Activities $activities) {

    	$attribute = $this->validate($request, [
    		'name' => 'required',
    		'email' => 'required|email|unique:users',
    		'phone_number' => 'required|min:10|numeric',
    		'password' => 'required|min:6|confirmed',
    	]);

        $time =  time();
        $created_time = date('h:i A — Y-m-d', $time+3600);

    	//generate a ramdom api token for user recognition
    	$generateRandomString = Str::random(60);

        $token = hash('sha256', $generateRandomString);
        //Generatate a token for the password recvery process
        $generateVerifyToken = Str::random(60);

        $verify_token = hash('adler32', $generateVerifyToken);

        //generate a ramdom api token for user confirmation
        $generateRandomConfirm = Str::random(60);

        $confirm_token = hash('sha256', $generateRandomConfirm);
        
        //insert the details into the user class and into the model class

            $user->name = ucwords($request->input('name'));
            $user->email = $request->input('email');
            $user->phone_number = $request->input('phone_number');
            $user->password = Hash::make($request->input('password'));

            $user->verify_code = $verify_token;
            $user->user_image = "user.jpg";
            $user->api_token = $token;
            $user->confirm_token = $confirm_token;


                 try{
                     Mail::to($user->email)->send(new ConfirmationLink($user)); 
                  } catch (Exception $ex) {

                     return response()->json(['data' =>['error' => false, 'message' => "Try again"]], 500);

                  }

                  $info = $user->save();

                  $splitemail = $user->email;
                  $emai_link = explode("@",$splitemail);

                  $emai_link = "www.".$emai_link[1];

                  $activities->owner_id = $user->id;
                  $activities->narrative = "Registered to GoalSetter App @".$created_time.".";
                  $activities->save();

                   return response()->json(['data' => ['success' => true, 'message' => 'Registrtion Successful, A confirmation link has been sent to '.$user->email.'', 'user' => $user, 'image_link' => 'http://res.cloudinary.com/getfiledata/image/upload/', 'email_link' => $emai_link]], 201);

        
    }
}
