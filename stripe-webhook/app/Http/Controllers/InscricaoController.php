<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InscricaoController extends Controller
{
    // Função de inscrição
    public function inscrever(Request $request)
    {
        // Lógica de inscrição
        // Por exemplo, você pode armazenar dados no banco de dados

        return redirect('/')->with('status', 'Inscrição realizada com sucesso!');
    }

    // Função de cancelamento da inscrição
    public function cancelarInscricao(Request $request)
    {
        // Lógica de cancelamento da inscrição
        // Exemplo: Remover dados do banco de dados

        return redirect('/')->with('status', 'Inscrição cancelada com sucesso!');
    }
}
