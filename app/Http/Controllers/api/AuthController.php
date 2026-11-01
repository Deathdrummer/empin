<?php namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\StaffPushToken;
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
            'installation_id' => 'nullable|string|max:100|regex:/^[a-zA-Z0-9_-]+$/',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Неверный логин или пароль',
            ], 401);
        }

        // Повторный вход заменяет только сессию этого устройства. Глобальное удаление ломало
        // звонки и PushKit другого телефона того же сотрудника. Старые клиенты без installation_id
        // остаются совместимыми; их сессии снимаются обычным logout.
        $installationId = $credentials['installation_id'] ?? null;
        $tokenName = $installationId ? 'mobile-app:' . $installationId : 'mobile-app';
        $token = \DB::transaction(function () use ($user, $installationId, $tokenName) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($installationId) {
                $user->tokens()->where('name', $tokenName)->delete();
            }
            return $user->createToken($tokenName)->plainTextToken;
        });

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
        $user = $request->user();
        $accessToken = $user->currentAccessToken();
        $name = $accessToken->name;
        if ($user->staff_id && str_starts_with($name, 'mobile-app:')) {
            $tokens = StaffPushToken::where('staff_id', $user->staff_id)
                ->where('installation_id', substr($name, strlen('mobile-app:')));
            $removed = (clone $tokens)->pluck('token');
            $tokens->delete();
            Staff::whereKey($user->staff_id)->whereIn('expo_push_token', $removed)
                ->update(['expo_push_token' => null]);
        }
        $accessToken->delete();

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
