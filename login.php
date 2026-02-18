<?php
session_start();
require 'config.php';
// Se já estiver logado, manda direto para o Admin
if (isset($_SESSION['user_id'])) {
    header("Location: admin.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(getSiteTitle(' - Login')); ?></title>
    
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
        
        <!-- Logo -->
        <div class="login-logo">
            <svg class="logo-svg" viewBox="0 0 440 53" xmlns="http://www.w3.org/2000/svg" 
                 style="height: 32px; width: auto; color: #111;">
                <g transform="matrix(1,0,0,1,-9184.726383,-653.363191)">
                    <g id="Prancheta1" transform="matrix(0.714933,0,0,0.37034,2680.065568,427.091861)">
                        <rect x="9098.279" y="610.982" width="614.802" height="140.605" style="fill:none;" />
                        <g transform="matrix(11.766975,0,0,22.715858,-46.903458,-11068.105915)">
                            <text x="794.884px" y="519.326px" style="font-family:'Inter', sans-serif;font-weight:600;font-size:5.672px;fill:currentColor;letter-spacing:0.05em;">W
                                <tspan x="800.153px 801.412px 805.133px 808.355px 809.506px 812.791px 816.029px 819.847px 823.568px 824.827px " y="519.326px 519.326px 519.326px 519.326px 519.326px 519.326px 519.326px 519.326px 519.326px 519.326px ">IDE STUDIO</tspan>
                            </text>
                        </g>
                        <g transform="matrix(1.13365,0,0,4.123102,6077.775465,-4484.639623)">
                            <rect x="2674" y="1242.263" width="157" height="22.5" style="fill:currentColor;" />
                        </g>
                    </g>
            </svg>
        </div>

        <!-- Form -->
        <form class="login-form" method="POST" action="login_api.php">
            <div class="input-block">
                <input type="text" name="username" placeholder="Usuário" class="login-input" required>
            </div>
            
            <div class="input-block">
                <input type="password" name="password" placeholder="Senha" class="login-input" required>
            </div>

            <button type="submit" class="btn-login">Entrar</button>
        </form>

        <a href="index" class="back-link">← Voltar ao Site</a>

    </div>

    <script>
        console.log('Login script loaded successfully');
        
        const loginForm = document.querySelector('.login-form');
        const btnLogin = document.querySelector('.btn-login');
        const loginInput = document.querySelectorAll('.login-input');

        if (!loginForm || !btnLogin || !loginInput.length) {
            console.error('Login elements not found!');
        } else {
            console.log('Login elements found, attaching event listener');
            
            loginForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                console.log('Form submitted, preventing default');
                
                const originalText = btnLogin.textContent;
                btnLogin.textContent = 'Verificando...';
                btnLogin.style.opacity = '0.7';
                
                const username = loginInput[0].value;
                const password = loginInput[1].value;

                console.log('Attempting login for user:', username);

                try {
                    console.log('Sending POST request to login_api');
                    
                    // Usa 'login_api' sem .php porque o servidor tem URL rewriting
                    const currentPath = window.location.pathname;
                    const basePath = currentPath.substring(0, currentPath.lastIndexOf('/') + 1);
                    const apiUrl = window.location.origin + basePath + 'login_api';
                    console.log('API URL:', apiUrl);
                    
                    const response = await fetch(apiUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ username, password })
                    });

                    console.log('Response received:', response.status, response.statusText);
                    console.log('Response URL:', response.url);
                    console.log('Was redirected:', response.redirected);
                    
                    // Captura o texto bruto ANTES de tentar parsear como JSON
                    const rawText = await response.text();
                    console.log('Raw response (first 500 chars):', rawText.substring(0, 500));
                    
                    // Tenta parsear como JSON
                    let data;
                    try {
                        data = JSON.parse(rawText);
                        console.log('Response data:', data);
                    } catch (jsonError) {
                        console.error('JSON Parse Error:', jsonError);
                        console.error('Full raw response:', rawText);
                        alert('Erro: O servidor retornou HTML em vez de JSON. Verifique o console para detalhes.');
                        btnLogin.textContent = originalText;
                        btnLogin.style.opacity = '1';
                        return;
                    }

                    if (data.success) {
                        btnLogin.textContent = 'Sucesso';
                        btnLogin.style.backgroundColor = '#4CAF50';
                        btnLogin.style.color = '#fff';
                        
                        setTimeout(() => {
                            window.location.href = 'admin.php';
                        }, 500);
                    } else {
                        alert(data.error || 'Login falhou');
                        btnLogin.textContent = originalText;
                        btnLogin.style.opacity = '1';
                    }
                } catch (error) {
                    console.error('Error during login:', error);
                    alert('Erro de conexão: ' + error.message);
                    btnLogin.textContent = originalText;
                    btnLogin.style.opacity = '1';
                }
            });
        }
    </script>

</body>
</html>
