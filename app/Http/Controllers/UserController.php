<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class UserController extends Controller
{
    //

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username_or_email' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => "Gagal",
                "code" => 422,
                "message" => "Validasi gagal",
                "data" => [
                    "errors" => $validator->errors()
                ]
            ], 422);
        }

        $loginField = $request->input('username_or_email');
        $password = $request->input('password');

        // Menentukan apakah input berupa email atau username
        $fieldType = filter_var($loginField, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // Cari user berdasarkan email atau username
        $user = User::where($fieldType, $loginField)->first();

        // Cek apakah user ada dan password cocok
        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json([
                "status" => "Gagal",
                "code" => 400,
                "message" => "Email/Username atau Password salah",
            ], 400);
        }

        $token = $user->createToken('tokenName', ['*'], Carbon::now()->addHour())->plainTextToken;

        // Jika autentikasi berhasil
        return response()->json([
            "status" => "Berhasil",
            "code" => 200,
            "message" => "Login Berhasil",
            "data" => [
                // "id" => $user->id,
                // "name" => $user->name,
                // "username" => $user->username,
                // "email" => $user->email,
                "token" => $token
            ]
        ]);
    }



    public function register(Request $request)
    {
        // $validator = Validator::make($request->all(), [
        //     'name' => 'required|string|max:50',
        //     'username' => 'required|string|max:50|unique:users',
        //     'email' => 'required|string|email|max:50|unique:users',
        //     'password' => [
        //         'required',
        //         'string',
        //         'min:8',
        //         'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!#%*?&])[A-Za-z\d@$!#%*?&]{8,}$/'
        //     ],
        //     'confirm_password' => 'required|string|same:password',
        // ], [
        //         'name.required' => 'Name must be included',
        //         'username.required' => 'Username must be included',
        //         'email.required' => 'Email must be included',
        //         'password.regex' => 'Password must be at least 8 characters, include at least 1 uppercase letter, 1 number, and 1 symbol.',
        //         'confirm_password.same' => 'The confirmation must be the same'
        //     ]);

        // if ($validator->fails()) {
        //     return response()->json([
        //         "status" => "Gagal",
        //         "code" => 422,
        //         "message" => "Validation errors",
        //         "errors" => $validator->errors()
        //     ], 422);
        // }

        // try {
        //     $user = User::create([
        //         'name' => $request->name,
        //         'username' => $request->username,
        //         // Make sure this line is present
        //         'email' => $request->email,
        //         'password' => Hash::make($request->password),
        //     ]);

        //     return response()->json([
        //         "status" => "Berhasil",
        //         "code" => 201,
        //         "message" => "User successfully registered",
        //         "data" => [
        //             "id" => $user->id,
        //             "name" => $user->name,
        //             "username" => $user->username,
        //             "email" => $user->email,
        //         ]
        //     ], 201);
        // } catch (\Exception $e) {
        //     Log::error('Error during user registration: ' . $e->getMessage());
        //     return response()->json([
        //         "status" => "Gagal",
        //         "message" => "Server error: " . $e->getMessage()
        //     ], 500);
        // }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:4',
            'username' => 'required|string|min:4|unique:users',
            'email' => 'required|string|email|max:50|unique:users',
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!#%*?&])[A-Za-z\d@$!#%*?&]{8,}$/'
            ],
            'confirm_password' => 'required|string|same:password',
        ], [
            'name.required' => 'Name must be included',
            'username.required' => 'Username must be included',
            'email.required' => 'Email must be included',
            'password.regex' => 'Password must be at least 8 characters, include at least 1 uppercase letter, 1 number, and 1 symbol.',
            'confirm_password.same' => 'The confirmation must be the same'
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                "status" => "Gagal",
                "code" => 422,
                "message" => "Validation errors",
                "errors" => $validator->errors()
            ], 422);
        }
    
        try {
            // Buat pengguna baru
            $user = User::create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);
    
            // Buat token API untuk pengguna
            $token = $user->createToken('tokenName', ['*'], Carbon::now()->addHour())->plainTextToken;
    
            return response()->json([
                "status" => "Berhasil",
                "code" => 201,
                "message" => "User successfully registered and logged in",
                "data" => [
                    "id" => $user->id,
                    "name" => $user->name,
                    "username" => $user->username,
                    "email" => $user->email,
                    "token" => $token // Kembalikan token
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error during user registration: ' . $e->getMessage());
            return response()->json([
                "status" => "Gagal",
                "message" => "Server error: " . $e->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            "data" => [
                "message" => "Logout Success"
            ]
        ]);
    }

    private function errorResponse($code, $message, $errors = null)
    {
        return response()->json([
            'status' => 'Gagal',
            'code' => $code,
            'message' => $message,
            'errors' => $errors
        ], $code);
    }
    private function successResponse($data, $message, $code = 200)
    {
        return response()->json([
            'status' => 'Berhasil',
            'code' => $code,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    public function uploadImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'avatar_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'avatar_image.image' => 'The file must be an image.',
            'avatar_image.mimes' => 'The avatar must be a file of type: jpeg, png, jpg, gif.',
            'avatar_image.max' => 'The avatar size must not exceed 2MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'Gagal',
                'code' => 422,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();

            // Handle the file upload
            if ($request->hasFile('avatar_image')) {
                $file = $request->file('avatar_image');
                $fileName = time() . '_' . $file->getClientOriginalName();

                // Save file to the 'public/images' directory
                $filePath = $file->storeAs('public/avatar_images', $fileName);

                // Save the file path in the database
                $user->avatar_image = Storage::url($filePath);
                $user->save();

                return response()->json([
                    'status' => 'Berhasil',
                    'code' => 200,
                    'message' => 'Avatar uploaded successfully.',
                    'data' => [
                        'image_url' => $user->avatar_image
                    ]
                ], 200);
            }

            return response()->json([
                'status' => 'Berhasil',
                'code' => 200,
                'message' => 'No avatar uploaded, but operation succeeded.',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error uploading avatar: ' . $e->getMessage());
            return response()->json([
                'status' => 'Gagal',
                'code' => 500,
                'message' => 'Server error: ' . $e->getMessage(),
            ], 500);
        }
    }

    

    public function editProfile(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:50',
            'about_me' => 'nullable|string|max:500',
            'current_password' => 'nullable|required_with:new_password|string',
            'new_password' => [
                'nullable',
                'required_with:current_password',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$#!%*?&])[A-Za-z\d@$#!%*?&]{8,}$/',
                'confirmed' // Pastikan ada field new_password_confirmation
            ]
        ], [
            'name.max' => 'The name must not exceed 50 characters.',
            'about_me.max' => 'The about me field must not exceed 500 characters.',
            'new_password.regex' => 'Password must include at least 8 characters, one uppercase letter, one number, and one special character.',
            'new_password.confirmed' => 'The new password confirmation does not match.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'Gagal',
                'code' => 422,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user(); // Ambil pengguna yang sedang login

            // Update nama jika ada
            if ($request->has('name')) {
                $user->name = $request->name;
            }

            // Update about me jika ada
            if ($request->has('about_me')) {
                $user->about_me = $request->about_me;
            }

            // Ganti password jika current_password dan new_password disediakan
            if ($request->has('current_password') && $request->has('new_password')) {
                // Verifikasi password lama
                if (!Hash::check($request->current_password, $user->password)) {
                    return response()->json([
                        'status' => 'Gagal',
                        'code' => 401,
                        'message' => 'The current password is incorrect.'
                    ], 401);
                }

                // Update password baru
                $user->password = Hash::make($request->new_password);
            }

            $user->save();

            return response()->json([
                'status' => 'Berhasil',
                'code' => 200,
                'message' => 'Profile updated successfully.',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'about_me' => $user->about_me,
                    'avatar_image' => $user->avatar_image,
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating profile: ' . $e->getMessage());
            return response()->json([
                'status' => 'Gagal',
                'code' => 500,
                'message' => 'Server error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getUser(Request $request)
    {
        try {
            // Ambil pengguna yang sedang login
            $user = $request->user();

            // Kembalikan data pengguna
            return response()->json([
                'status' => 'Berhasil',
                'code' => 200,
                'message' => 'User data retrieved successfully.',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'about_me' => $user->about_me,
                    'avatar_image' => $user->avatar_image,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error retrieving user data: ' . $e->getMessage());
            return response()->json([
                'status' => 'Gagal',
                'code' => 500,
                'message' => 'Server error: ' . $e->getMessage(),
            ], 500);
        }
    }

}
