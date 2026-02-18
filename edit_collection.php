<?php
/**
 * PÁGINA DE EDIÇÃO DE COLEÇÃO (Versão Estabilizada V2)
 */
require_once 'config.php';

// Iniciar sessão
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$collectionId = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT) ?: 1;
$pdo = getDbConnection();

// 1. Buscar Dados
try {
    $stmt = $pdo->prepare("SELECT * FROM collections WHERE id = ?");
    $stmt->execute([$collectionId]);
    $collection = $stmt->fetch();
} catch (Exception $e) {
    die("Erro DB: " . $e->getMessage());
}

if (!$collection) {
    die("Coleção ID $collectionId não encontrada no banco de dados.");
}

// 2. Sanitização
$title = $collection['title'];
$folderName = mb_strtolower($title, 'UTF-8');
$folderName = preg_replace('/[áàãâä]/u', 'a', $folderName);
$folderName = preg_replace('/[éèêë]/u', 'e', $folderName);
$folderName = preg_replace('/[íìîï]/u', 'i', $folderName);
$folderName = preg_replace('/[óòõôö]/u', 'o', $folderName);
$folderName = preg_replace('/[úùûü]/u', 'u', $folderName);
$folderName = preg_replace('/[ç]/u', 'c', $folderName);
$folderName = preg_replace('/[^a-z0-9]/', '-', $folderName);
$folderName = preg_replace('/-+/', '-', $folderName);
$folderName = trim($folderName, '-');

// 3. Caminhos
$targetDir = __DIR__ . '/images/collections/' . $folderName;
$webPath = 'images/collections/' . $folderName . '/';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Coleção: <?php echo htmlspecialchars($title); ?></title>
    <style>
        body { font-family: sans-serif; padding: 40px; background: #fafafa; color: #333; line-height: 1.6; }
        .header { margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .collection-info { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .info-item label { display: block; font-size: 11px; color: #888; text-transform: uppercase; margin-bottom: 5px; }
        .info-item .value { font-size: 15px; font-weight: 500; }
        .upload-section { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px; border: 2px dashed #ddd; text-align: center; }
        .upload-section.dragover { border-color: #000; background: #f9f9f9; }
        .upload-btn { display: inline-block; padding: 12px 24px; background: #000; color: #fff; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; margin-top: 20px; }
        .item { background: #fff; padding: 10px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); position: relative; }
        .item img { width: 100%; height: 180px; object-fit: cover; border-radius: 2px; }
        .item-actions { position: absolute; top: 15px; right: 15px; opacity: 0; transition: 0.2s; }
        .item:hover .item-actions { opacity: 1; }
        .btn-delete { background: #ff4d4d; color: #fff; border: none; padding: 5px 8px; border-radius: 3px; cursor: pointer; font-size: 11px; }
        .btn { display: inline-block; padding: 10px 20px; background: #000; color: #fff; text-decoration: none; border-radius: 4px; font-size: 14px; border: none; cursor: pointer; }
        .btn-api { background: #007bff; }
        #upload-status { margin-top: 15px; font-weight: bold; }
    </style>
    <script>
        // Definir funções no head para garantir disponibilidade baseada em hoisting
        const COLLECTION_ID = <?php echo $collectionId; ?>;

        async function testApi() {
            console.log('Testing API Connection...');
            try {
                const res = await fetch('collections_api.php?action=test');
                const text = await res.text();
                console.log('Server response:', text);
                try {
                    const data = JSON.parse(text);
                    if (data.success) alert('✅ API Conectada: ' + (data.message || 'OK'));
                    else alert('❌ Erro na API: ' + data.error);
                } catch(e) {
                    alert('❌ Resposta inválida do servidor (veja console)');
                    console.error('JSON Parse Error:', e, 'Raw Text:', text);
                }
            } catch (e) {
                alert('❌ Falha na conexão: ' + e.message);
            }
        }

        async function handleUpload(files) {
            if (!files || files.length === 0) return;
            const statusEl = document.getElementById('upload-status');
            statusEl.innerHTML = 'Processando...';
            
            let count = 0;
            const fileArray = Array.from(files);
            
            for (const file of fileArray) {
                const fd = new FormData();
                fd.append('action', 'upload_collection_image');
                fd.append('collection_id', COLLECTION_ID);
                fd.append('image', file);

                try {
                    const res = await fetch('collections_api.php', { method: 'POST', body: fd });
                    const text = await res.text();
                    const data = JSON.parse(text);
                    if (data.success) {
                        count++;
                        console.log('✅ Upload Success:', data.image_path);
                        console.log('📂 Physical Path:', data.physical_path);
                        statusEl.innerHTML = `Enviando: ${count} de ${fileArray.length}`;
                    } else {
                        alert('Erro no arquivo ' + file.name + ': ' + data.error);
                    }
                } catch (e) {
                    alert('Erro de rede ao enviar ' + file.name);
                }
            }

            if (count > 0) {
                statusEl.innerHTML = `<span style="color: green">✓ ${count} imagens enviadas!</span>`;
                setTimeout(() => location.reload(), 1000);
            }
        }

        async function deleteImage(name) {
            if (!confirm('Excluir esta imagem permanentemente?')) return;
            try {
                const res = await fetch('collections_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete_collection_image', collection_id: COLLECTION_ID, image_name: name })
                });
                const data = await res.json();
                if (data.success) location.reload();
                else alert('Erro: ' + data.error);
            } catch (e) { alert('Erro na conexão'); }
        }
    </script>
</head>
<body>

    <div class="header">
        <h1>Editar: <?php echo htmlspecialchars($title); ?></h1>
        <button onclick="testApi()" class="btn btn-api">Testar Conexão</button>
    </div>

    <div class="collection-info">
        <div class="info-item" style="margin-bottom: 20px;">
            <label>Descrição</label>
            <div class="value"><?php echo nl2br(htmlspecialchars($collection['description'] ?: 'Sem descrição')); ?></div>
        </div>
        <div class="info-grid">
            <div class="info-item"><label>Produzido por</label><div class="value"><?php echo htmlspecialchars($collection['produced_by'] ?: '-'); ?></div></div>
            <div class="info-item"><label>Ano</label><div class="value"><?php echo htmlspecialchars($collection['year'] ?: '-'); ?></div></div>
        </div>
    </div>

    <div class="upload-section" id="drop-zone">
        <h3>Fotos da Coleção</h3>
        <p style="color: #888;">Arraste fotos ou clique no botão</p>
        <label for="file-input" class="upload-btn">Escolher Fotos</label>
        <input type="file" id="file-input" multiple accept="image/*" style="display: none;" onchange="handleUpload(this.files)">
        <div id="upload-status"></div>
    </div>

    <?php if (is_dir($targetDir)): ?>
        <?php 
            $isWritable = is_writable($targetDir);
            if ($isWritable) echo '<span class="success-badge">✓ Pasta Conectada e Gravável</span>';
            else echo '<span class="success-badge" style="background: #fffbdd; color: #735c0f;">⚠ Pasta sem permissão de escrita</span>';
        ?>
        <div class="gallery">
            <?php
            $files = scandir($targetDir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                    echo '<div class="item">';
                    echo '<img src="' . $webPath . $file . '" alt="' . $file . '">';
                    echo '<div class="item-actions"><button class="btn-delete" onclick="deleteImage(\'' . $file . '\')">Excluir</button></div>';
                    echo '</div>';
                }
            }
            ?>
        </div>
    <?php else: ?>
        <p style="color: red;">Pasta de imagens não encontrada.</p>
    <?php endif; ?>

    <div style="margin-top: 40px;">
        <a href="admin_collections.php" class="btn">Voltar</a>
    </div>

    <script>
        // Setup Drag & Drop
        const dz = document.getElementById('drop-zone');
        dz.addEventListener('dragover', e => { e.preventDefault(); dz.style.borderColor = '#000'; });
        dz.addEventListener('dragleave', () => dz.style.borderColor = '#ddd');
        dz.addEventListener('drop', e => {
            e.preventDefault();
            dz.style.borderColor = '#ddd';
            handleUpload(e.dataTransfer.files);
        });
    </script>
</body>
</html>
