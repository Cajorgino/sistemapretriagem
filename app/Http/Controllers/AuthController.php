<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $crm = strtoupper(preg_replace('/[\s\-]+/u', '', trim((string) $request->input('crm', ''))));
        $request->merge(['crm' => $crm]);

        $credentials = $request->validate([
            'crm' => ['required', 'string', 'min:3', 'max:20'],
            'password' => ['required', 'string'],
        ]);

        try {
            $auth = Auth::attempt([
                'crm' => $crm,
                'password' => $credentials['password'],
            ], $request->boolean('remember'));
        } catch (\Throwable $e) {
            Log::error('Erro ao tentar login: '.$e->getMessage());

            // Se o erro for de algoritmo, tratamos como falha de autenticação comum
            if (str_contains($e->getMessage(), 'Bcrypt algorithm')) {
                return response()->json(['errors' => ['crm' => ['Erro de compatibilidade de senha. Registre-se novamente.']]], 401);
            }

            return response()->json([
                'message' => 'Erro interno no servidor.',
                'error' => $e->getMessage(),
            ], 500);
        }

        if ($auth) {
            $request->session()->regenerate();

            return response()->json(['message' => 'Login realizado com sucesso!']);
        }

        return response()->json([
            'errors' => ['crm' => ['CRM ou senha inválidos']],
        ], 401);
    }

    public function logout()
    {
        Auth::logout();

        return redirect('/');
    }

    public function registerIndex()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        try {
            $crm = strtoupper(preg_replace('/[\s\-]+/u', '', trim((string) $request->input('crm', ''))));
            $request->merge(['crm' => $crm]);

            $validator = Validator::make($request->all(), [
                'nome' => ['required', 'string', 'max:255'],
                'crm' => ['required', 'string', 'min:3', 'max:20', 'unique:users,crm'],
                'telefone' => ['required', 'string', 'max:20'],
                'password' => ['required', 'string', 'min:8'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = \App\Models\User::create([
                'name' => $request->nome,
                'crm' => $request->crm,
                'telefone' => $request->telefone,
                'password' => Hash::make($request->password),
                'role' => 'user',
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro ao registrar usuário: '.$e->getMessage());

            return response()->json([
                'message' => 'Erro ao criar usuário. Verifique se a tabela users possui as colunas name, crm e telefone.',
                'error' => $e->getMessage(),
            ], 500);
        }

        $request->session()->regenerate();

        return response()->json([
            'message' => 'Cadastro realizado com sucesso! Faça login com seu CRM e senha.',
            'redirect' => route('login'),
        ], 201);
    }
}
