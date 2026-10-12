<?php namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller {

    /**
     * Авторизация пользователя
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request) {
        // В users.email кириллические домены хранятся в Punycode — приводим так же, как веб-вход
        $email = $request->input('email');
        if (is_string($email)) {
            $request->merge(['email' => encodeEmail(trim($email))]);
        }

        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Неверный логин или пароль',
            ], 401);
        }

        // Удаляем старые токены
        $user->tokens()->delete();

        // Создаем новый токен
        $token = $user->createToken('mobile-app')->plainTextToken;

        // Получаем все права пользователя через Spatie
        $permissions = $user->getAllPermissions()->pluck('name')->toArray();

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'full_name' => $user->full_name,
                'staff_id' => $user->staff_id,
                'permissions' => $permissions,
            ],
        ]);
    }

    /**
     * Выход пользователя (удаление текущего токена)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request) {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }

    /**
     * Получить данные текущего пользователя
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request) {
        $user = $request->user();

        // Получаем все права пользователя через Spatie
        $permissions = $user->getAllPermissions()->pluck('name')->toArray();

        return response()->json([
            'id' => $user->id,
            'email' => $user->email,
            'full_name' => $user->full_name,
            'staff_id' => $user->staff_id,
            'department_id' => $user->department_id,
            'permissions' => $permissions,
        ]);
    }
}
