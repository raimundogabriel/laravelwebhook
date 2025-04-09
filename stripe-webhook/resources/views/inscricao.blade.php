<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscrição</title>
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-lg mx-auto bg-white p-6 rounded-md shadow-md">
        <h1 class="text-2xl font-semibold text-center mb-4">Formulário de Inscrição</h1>

        @if(session('status'))
            <div class="bg-green-100 text-green-800 p-4 mb-4 rounded-md">
                <p>{{ session('status') }}</p>
            </div>
        @endif

        <form action="{{ route('inscricao') }}" method="POST" class="space-y-4">
            @csrf
            <div class="flex flex-col">
                <label for="nome" class="text-sm font-medium text-gray-700">Nome:</label>
                <input type="text" id="nome" name="nome" required class="p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Digite seu nome">
            </div>
            <div class="flex flex-col">
                <label for="cpf" class="text-sm font-medium text-gray-700">CPF:</label>
                <input type="text" id="cpf" name="cpf" required class="p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" maxlength="14" oninput="maskCPF(this)" placeholder="Digite seu CPF">
            </div>
            <div class="flex flex-col">
                <label for="telefone" class="text-sm font-medium text-gray-700">Telefone:</label>
                <input type="text" id="telefone" name="telefone" required class="p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" maxlength="15" oninput="maskPhone(this)" placeholder="Digite seu telefone">
            </div>

            <button type="submit" class="w-full py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Inscrever</button>
        </form>

        <form action="{{ route('cancelar-inscricao') }}" method="POST" class="mt-4">
            @csrf
            <button type="submit" class="w-full py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Cancelar Inscrição</button>
        </form>
    </div>

    <script>
        function maskCPF(cpf) {
            var value = cpf.value.replace(/\D/g, '');
            if (value.length <= 11) {
                cpf.value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{1})/, '$1.$2.$3-$4');
            }
        }

        function maskPhone(phone) {
            var value = phone.value.replace(/\D/g, '');
            if (value.length <= 11) {
                phone.value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
            }
        }
    </script>
</body>
</html>
