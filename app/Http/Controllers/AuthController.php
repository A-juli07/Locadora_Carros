<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Models\User;

/**
 * @OA\Info(
 *     title="API Locadora de Carros",
 *     version="1.0.0",
 *     description="Documentação da API da Locadora de Carros com autenticação JWT"
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Servidor API"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Insira o token JWT no formato: Bearer {seu_token}"
 * )
 */

class AuthController extends Controller
{
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @OA\Post(
     *   path="/api/cadastro",
     *   summary="Cadastro de usuário",
     *   tags={"Auth"},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"name","email","password"},
     *       @OA\Property(property="name", type="string", example="João da Silva"),
     *       @OA\Property(property="email", type="string", format="email", example="joao@example.com"),
     *       @OA\Property(property="password", type="string", format="password", example="123456")
     *     )
     *   ),
     *   @OA\Response(response=201, description="Usuário criado com sucesso"),
     *   @OA\Response(response=422, description="Dados inválidos")
     * )
     */

    public function cadastro(Request $request)
    {
        $request->validate($this->user->rules());

        $user = User::create(
            [
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]
        );

        try {
            $token = JWTAuth::fromUser($user);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Impossivel criar o token, tente novamente'], 500);
        }

        return response()->json([
            'user' => $user,
            'token' => $token
        ], 201);
    }

    /**
     * @OA\Post(
     *   path="/api/login",
     *   summary="Login do usuário",
     *   tags={"Auth"},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"email","password"},
     *       @OA\Property(property="email", type="string", format="email", example="joao@example.com"),
     *       @OA\Property(property="password", type="string", format="password", example="123456")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Login bem-sucedido"),
     *   @OA\Response(response=401, description="Usuário ou senha inválidos")
     * )
     */

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'Usuario ou Senha Invalidas!'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'Impossibilitado de criar o Token'], 500);
        }

        return response()->json([
            'token' => $token,
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ]);
    }

    /**
     * @OA\Post(
     *   path="/api/v1/me",
     *   summary="Usuário autenticado",
     *   tags={"Auth"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Retorna os dados do usuário logado"),
     *   @OA\Response(response=401, description="Não autenticado")
     * )
     */

    public function me()
    {
        try {
            if (!auth()->user()) {
                return response()->json(['error' => 'Nenhum usuario logado!'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'Ocorreu um erro, tente novamente'], 500);
        }

        return response()->json(auth()->user());
    }

    /**
     * @OA\Post(
     *   path="/api/v1/logout",
     *   summary="Logout do usuário",
     *   tags={"Auth"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Logout realizado com sucesso"),
     *   @OA\Response(response=401, description="Não autenticado")
     * )
     */

    public function logout()
    {
        try {
            auth('api')->logout();
        } catch (JWTException $e) {
            return response()->json(['error' => 'Falha, tente de novo'], 500);
        }

        return response()->json(['message' => 'Logout realizado com sucesso!']);
    }

    public function refresh()
    {
        $token = auth('api')->refresh();
        return response()->json(['token' => $token]);
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }
}
