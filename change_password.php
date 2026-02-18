<?php
session_start();
require 'config.php';
// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(getSiteTitle(' - Alterar Senha')); ?></title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="css/variables.css">
    <link rel="stylesheet" href="css/utilities.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="icon" href="logo/favicon2.png" type="image/png">
</head>
<body class="login-page">

    <div class="login-container">
        
        <div class="login-logo">
            <h2 style="font-family: 'Inter'; font-weight: 500; font-size: 1.2rem;">Alterar Senha</h2>
        </div>

        <!-- Form -->
        <form class="login-form">
            
            <!-- Senha Atual -->
            <div class="input-block">
                <input type="password" id="currPass" placeholder="Senha Atual" class="login-input" required>
                <button type="button" class="password-toggle" onclick="togglePass('currPass', this)" title="Ver senha">
                    <svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                </button>
            </div>
            
            <!-- Nova Senha -->
            <div class="input-block">
                <input type="password" id="newPass" placeholder="Nova Senha (min 8 chars)" class="login-input" required>
                <button type="button" class="password-toggle" onclick="togglePass('newPass', this)" title="Ver senha">
                    <svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                </button>
                <div style="font-size: 0.7rem; color: #888; text-align: left; margin-top: 5px;">
                    Requisitos: 8 caracteres e 1 caractere especial (ex: @, #, !)
                </div>
            </div>

            <!-- Confirmar -->
            <div class="input-block">
                <input type="password" id="confPass" placeholder="Confirmar Nova Senha" class="login-input" required>
                <button type="button" class="password-toggle" onclick="togglePass('confPass', this)" title="Ver senha">
                    <svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                </button>
            </div>

            <button type="submit" class="btn-login">Atualizar Senha</button>
        </form>

        <a href="admin.php" class="back-link">Cancelar e Voltar</a>

    </div>

    <script>
        /**
         * Toggle password visibility
         * @param {string} inputId - ID do campo de input
         * @param {HTMLElement} btn - Botão clicado
         */
        function togglePass(inputId, btn) {
            const input = document.getElementById(inputId);
            const svg = btn.querySelector('svg');
            
            if (input.type === 'password') {
                input.type = 'text';
                svg.innerHTML = '<path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/>';
                btn.style.opacity = '0.8';
            } else {
                input.type = 'password';
                svg.innerHTML = '<path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>';
                btn.style.opacity = '0.3';
            }
        }

        // Form submission logic
        const form = document.querySelector('.login-form');
        const btn = document.querySelector('.btn-login');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const currentPass = document.getElementById('currPass').value;
            const newPass = document.getElementById('newPass').value;
            const confirmPass = document.getElementById('confPass').value;

            if (newPass !== confirmPass) {
                alert("A nova senha e a confirmação não batem.");
                return;
            }

            const originalText = btn.textContent;
            btn.textContent = 'Atualizando...';
            btn.style.opacity = '0.7';

            try {
                const response = await fetch('change_password_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        current_password: currentPass, 
                        new_password: newPass 
                    })
                });

                const data = await response.json();

                if (data.success) {
                    btn.textContent = 'Sucesso!';
                    btn.style.backgroundColor = '#4CAF50';
                    btn.style.color = '#fff';
                    alert('Senha atualizada com sucesso!');
                    setTimeout(() => window.location.href = 'admin.php', 1000);
                } else {
                    alert(data.error || 'Erro ao atualizar');
                    btn.textContent = originalText;
                    btn.style.opacity = '1';
                }
            } catch (error) {
                console.error(error);
                alert('Erro de conexão.');
                btn.textContent = originalText;
                btn.style.opacity = '1';
            }
        });
    </script>

    <!-- Cursor Script -->
    <script type="module">
        import { initCustomCursor } from './js/common.js';
        initCustomCursor();
    </script>

</body>
</html>
