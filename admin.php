<?php
session_start();
// Redireciona para a nova página de coleções por padrão
header('Location: admin_collections.php');
exit;
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(getSiteTitle(' - Admin')); ?></title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="css/variables.css">
    <link rel="stylesheet" href="css/utilities.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/project-modal.css">
    <link rel="icon" href="logo/favicon2.png" type="image/png">
    <?php include 'includes/logo-font.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
</head>

<body>

    <!-- Header -->
    <header class="admin-header">
        <div class="brand">
            <svg class="logo-svg" viewBox="0 0 440 53" xmlns="http://www.w3.org/2000/svg" style="height: 24px; width: auto; max-width: 200px;">
                <g transform="matrix(1,0,0,1,-9184.726383,-653.363191)">
                    <g id="Prancheta1" transform="matrix(0.714933,0,0,0.37034,2680.065568,427.091861)">
                        <rect x="9098.279" y="610.982" width="614.802" height="140.605" style="fill:none;" />
                        <g transform="matrix(11.766975,0,0,22.715858,-46.903458,-11068.105915)">
                            <text x="794.884px" y="519.326px" style="font-family:'Inter', sans-serif;font-weight:600;font-size:5.672px;fill:currentColor;letter-spacing:0.05em;">W
                                <tspan x="800.153px 801.412px 805.133px 808.355px 809.506px 812.791px 816.029px 819.847px 823.568px 824.827px" y="519.326px 519.326px 519.326px 519.326px 519.326px 519.326px 519.326px 519.326px 519.326px 519.326px">IDE STUDIO</tspan>
                            </text>
                        </g>
                        <g transform="matrix(1.13365,0,0,4.123102,6077.775465,-4484.639623)">
                            <rect x="2674" y="1242.263" width="157" height="22.5" style="fill:currentColor;" />
                        </g>
                    </g>
                </g>
            </svg>
            
            <nav class="admin-nav">
                <a href="index.php" target="_blank" class="admin-nav-link">Ver Site</a>
                <a href="change_password.php" class="admin-nav-link">Alterar Senha</a>
                <a href="logout.php" class="admin-nav-link">Sair</a>
            </nav>
        </div>

        <button class="btn-save">Salvar Alterações</button>
    </header>

    <main class="admin-container">

        <!-- TABS NAVIGATION -->
        <div class="tabs-nav">
            <button class="tab-btn active" onclick="openTab('tab-collections', this)">Coleções</button>
            <button class="tab-btn" onclick="openTab('tab-hero', this)">Texto Principal</button>
            <button class="tab-btn" onclick="openTab('tab-contact', this)">Contato</button>
            <button class="tab-btn" onclick="openTab('tab-menu', this)">Menu</button>
            <button class="tab-btn" onclick="openTab('tab-general', this)">Geral</button>
        </div>

        <!-- TAB 1: COLEÇÕES -->
        <div id="tab-collections" class="tab-pane active">
            <section class="intro-section" style="margin-bottom: 40px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <span class="section-title" style="margin:0;">Gerenciar Coleções</span>
                    <button onclick="addCollection()" class="btn-add-service">
                        <span>+</span> Adicionar Coleção
                    </button>
                </div>
                <p style="font-size: 0.9rem; color: #666; margin-top: 10px; margin-bottom: 30px;">
                    Organize seus produtos em coleções temáticas.
                </p>
                
                <!-- Collections List -->
                <div id="collections-list" class="collections-grid">
                    <!-- Collections will be loaded here via JavaScript -->
                </div>
            </section>
        </div>



        <!-- TAB 3: HERO TEXT -->
        <div id="tab-hero" class="tab-pane">
            <section class="intro-section">
                <span class="section-title">Texto Principal da Home</span>
                <div class="input-group" style="margin-top: 15px;">
                    <textarea id="heroText" class="admin-input" rows="4" 
                        style="width: 100%; border-bottom: 1px solid rgba(0,0,0,0.1); font-size: 1.1rem; line-height: 1.5;" 
                        placeholder="Digite o texto de introdução do site..."></textarea>
                    <p style="font-size: 0.85rem; color: #888; margin-top: 8px;">
                        💡 Use <code style="background: rgba(0,0,0,0.05); padding: 2px 6px; border-radius: 4px; font-family: monospace;">&lt;texto&gt;</code> para criar uma pill. 
                        Exemplo: "Design <code style="background: rgba(0,0,0,0.05); padding: 2px 6px; border-radius: 4px; font-family: monospace;">&lt;profissional&gt;</code> e moderno"
                    </p>
                </div>
                
                <!-- Typography Controls -->
                <div style="margin-top: 40px; display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                    <!-- Font Size -->
                    <div>
                        <label class="input-label" style="display: block; margin-bottom: 12px; font-weight: 500;">Tamanho do Texto</label>
                        <div class="typography-options">
                            <label class="typography-option">
                                <input type="radio" name="heroFontSize" value="xs">
                                <span class="option-label">Extra Small</span>
                            </label>
                            <label class="typography-option">
                                <input type="radio" name="heroFontSize" value="sm">
                                <span class="option-label">Small</span>
                            </label>
                            <label class="typography-option">
                                <input type="radio" name="heroFontSize" value="medium" checked>
                                <span class="option-label">Medium</span>
                            </label>
                            <label class="typography-option">
                                <input type="radio" name="heroFontSize" value="lg">
                                <span class="option-label">Large</span>
                            </label>
                            <label class="typography-option">
                                <input type="radio" name="heroFontSize" value="xl">
                                <span class="option-label">Extra Large</span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Font Weight -->
                    <div>
                        <label class="input-label" style="display: block; margin-bottom: 12px; font-weight: 500;">Espessura do Texto</label>
                        <div class="typography-options">
                            <label class="typography-option">
                                <input type="radio" name="heroFontWeight" value="thin">
                                <span class="option-label">Thin</span>
                            </label>
                            <label class="typography-option">
                                <input type="radio" name="heroFontWeight" value="light" checked>
                                <span class="option-label">Light</span>
                            </label>
                            <label class="typography-option">
                                <input type="radio" name="heroFontWeight" value="regular">
                                <span class="option-label">Regular</span>
                            </label>
                            <label class="typography-option">
                                <input type="radio" name="heroFontWeight" value="medium">
                                <span class="option-label">Medium</span>
                            </label>
                            <label class="typography-option">
                                <input type="radio" name="heroFontWeight" value="bold">
                                <span class="option-label">Bold</span>
                            </label>
                        </div>
                    </div>
                </div>
            </section>
        </div>





        <!-- TAB 4: MENU -->
        <div id="tab-menu" class="tab-pane">
            <section class="intro-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <span class="section-title" style="margin:0;">Gerenciar Menu</span>
                    <button onclick="addMenuItem()" class="btn-add-service">
                        <span>+</span> Adicionar Link
                    </button>
                </div>
                
                <!-- HEADER DA TABELA -->
                <div class="menu-header menu-grid-layout">
                    <div></div>
                    <div>Título do Botão</div>
                    <div>Link de Destino (URL)</div>
                    <div></div>
                </div>

                <div id="menu-list" style="display: flex; flex-direction: column;"></div>
            </section>
        </div>

        <!-- TAB 5: CONTATO -->
        <div id="tab-contact" class="tab-pane">
            <section class="intro-section">
                <span class="section-title">Textos da Página de Contato</span>
                
                <div class="input-group" style="margin-top: 20px;">
                    <label class="input-label">Título Principal</label>
                    <textarea id="contactTitle" class="admin-input" rows="4"
                        style="width: 100%; box-sizing: border-box; border: none; border-bottom: 1px solid rgba(0,0,0,0.1); font-size: 1.1rem; line-height: 1.5; background: transparent;" 
                        placeholder="Ex: Vamos criar algo incrível juntos?"></textarea>
                </div>

                <div class="input-group" style="margin-top: 20px;">
                    <label class="input-label">Subtítulo</label>
                    <textarea id="contactSubtitle" class="admin-input" rows="4"
                        style="width: 100%; box-sizing: border-box; border: none; border-bottom: 1px solid rgba(0,0,0,0.1); font-size: 1.1rem; line-height: 1.5; background: transparent;" 
                        placeholder="Ex: Entre em contato e vamos conversar sobre seu próximo projeto."></textarea>
                </div>
            </section>
        </div>

        <!-- TAB 6: GERAL -->
        <div id="tab-general" class="tab-pane">
            <section class="intro-section">
                <span class="section-title">Configurações Gerais</span>
                
                <div class="input-group" style="margin-top: 20px;">
                    <label class="input-label">Título do Site</label>
                    <input type="text" id="siteTitle" class="admin-input" 
                        style="width: 100%; box-sizing: border-box; border: none; border-bottom: 1px solid rgba(0,0,0,0.1); font-size: 1.1rem; padding: 10px 0; background: transparent;" 
                        placeholder="Ex: Wide Studio - Art Direction">
                    <p style="font-size: 0.85rem; color: #888; margin-top: 8px;">Este título aparece na aba do navegador</p>
                </div>

                <!-- Contact Information -->
                <div class="input-group" style="margin-top: 30px;">
                    <label class="input-label">E-mail de Contato</label>
                    <input type="email" id="contactEmail" class="admin-input" 
                        style="width: 100%; box-sizing: border-box; border: none; border-bottom: 1px solid rgba(0,0,0,0.1); font-size: 1.1rem; padding: 10px 0; background: transparent;" 
                        placeholder="Ex: seu@email.com">
                    <p style="font-size: 0.85rem; color: #888; margin-top: 8px;">E-mail exibido na página de contato</p>
                </div>

                <div class="input-group" style="margin-top: 20px;">
                    <label class="input-label">Telefone/WhatsApp</label>
                    <input type="text" id="contactPhone" class="admin-input" 
                        style="width: 100%; box-sizing: border-box; border: none; border-bottom: 1px solid rgba(0,0,0,0.1); font-size: 1.1rem; padding: 10px 0; background: transparent;" 
                        placeholder="Ex: (47) 9 9999-9999">
                    <p style="font-size: 0.85rem; color: #888; margin-top: 8px;">Número exibido na página de contato</p>
                </div>

                <div class="input-group" style="margin-top: 20px;">
                    <label class="input-label">Instagram</label>
                    <input type="text" id="contactInstagram" class="admin-input" 
                        style="width: 100%; box-sizing: border-box; border: none; border-bottom: 1px solid rgba(0,0,0,0.1); font-size: 1.1rem; padding: 10px 0; background: transparent;" 
                        placeholder="Ex: seuperfil ou @seuperfil">
                    <p style="font-size: 0.85rem; color: #888; margin-top: 8px;">Perfil do Instagram (com ou sem @)</p>
                </div>

                <div class="input-group" style="margin-top: 30px;">
                    <label class="input-label">Texto do Footer</label>
                    <input type="text" id="footerText" class="admin-input" 
                        style="width: 100%; box-sizing: border-box; border: none; border-bottom: 1px solid rgba(0,0,0,0.1); font-size: 1.1rem; padding: 10px 0; background: transparent;" 
                        placeholder="Ex: © Gustavo Starke Fotografia">
                    <p style="font-size: 0.85rem; color: #888; margin-top: 8px;">Texto exibido no rodapé (footer) do site</p>
                </div>
            </section>

            <!-- Logo Customization -->
            <section class="intro-section" style="margin-top: 40px;">
                <span class="section-title">Logo do Header</span>
                
                <div class="input-group" style="margin-top: 20px;">
                    <label class="input-label">Texto da Logo</label>
                    <input type="text" id="logoText" class="admin-input" 
                        style="width: 100%; box-sizing: border-box; border: none; border-bottom: 1px solid rgba(0,0,0,0.1); font-size: 1.1rem; padding: 10px 0; background: transparent;" 
                        placeholder="Ex: Gustavo Starke">
                    <p style="font-size: 0.85rem; color: #888; margin-top: 8px;">Texto exibido como logo no header</p>
                </div>

                <!-- Logo Preview -->
                <div style="margin-top: 30px; padding: 30px; background: rgba(0,0,0,0.02); border-radius: 12px; border: 1px solid rgba(0,0,0,0.08); display: flex; align-items: center; justify-content: center;">
                    <div style="text-align: center;">
                        <p style="font-size: 0.75rem; opacity: 0.5; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 500;">Preview</p>
                        <span id="logoPreview" style="font-family: var(--font-main); font-size: 20px; font-weight: 600; letter-spacing: 0.02em; color: var(--text-color); text-transform: uppercase; transition: all 0.2s ease;">
                            Gustavo Starke
                        </span>
                    </div>
                </div>

                <!-- Logo Font Family -->
                <div class="input-group" style="margin-top: 30px;">
                    <label class="input-label">Fonte da Logo</label>
                    <div style="position: relative;">
                        <select id="logoFontFamily" class="admin-input" style="width: 100%; box-sizing: border-box; border: none; border-bottom: 1px solid rgba(0,0,0,0.1); font-size: 1.1rem; padding: 10px 0; background: transparent; cursor: pointer;">
                            <option value="Inter" style="font-family: 'Inter', sans-serif;">Inter (Padrão)</option>
                            <option value="Montserrat" style="font-family: 'Montserrat', sans-serif;">Montserrat (Moderna)</option>
                            <option value="Playfair Display" style="font-family: 'Playfair Display', serif;">Playfair Display (Serifa Elegante)</option>
                            <option value="Lato" style="font-family: 'Lato', sans-serif;">Lato (Neutara)</option>
                            <option value="Cormorant Garamond" style="font-family: 'Cormorant Garamond', serif;">Cormorant Garamond (Clássica)</option>
                            <option value="Roboto" style="font-family: 'Roboto', sans-serif;">Roboto (Técnica)</option>
                            <option value="Outfit" style="font-family: 'Outfit', sans-serif;">Outfit (Clean)</option>
                        </select>
                        <span style="position: absolute; right: 0; top: 15px; pointer-events: none; opacity: 0.5;">▼</span>
                    </div>
                    <p style="font-size: 0.85rem; color: #888; margin-top: 8px;">Escolha a tipografia principal da logo</p>
                </div>

                <!-- Logo Typography Controls -->
                <div style="margin-top: 30px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px;">
                    <!-- Font Size -->
                    <div>
                        <label class="input-label" style="display: block; margin-bottom: 12px; font-weight: 500;">Tamanho da Fonte</label>
                        <div class="typography-options">
                            <label class="typography-option">
                                <input type="radio" name="logoFontSize" value="16">
                                <span class="option-label">16px</span>
                            </label>
                            <label class="typography-option">
                                <input type="radio" name="logoFontSize" value="18">
                                <span class="option-label">18px</span>
                            </label>
                            <label class="typography-option">
                                <input type="radio" name="logoFontSize" value="20" checked>
                                <span class="option-label">20px</span>
                            </label>
                            <label class="typography-option">
                                <input type="radio" name="logoFontSize" value="22">
                                <span class="option-label">22px</span>
                            </label>
                            <label class="typography-option">
                                <input type="radio" name="logoFontSize" value="24">
                                <span class="option-label">24px</span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Font Weight -->
                    <div>
                        <label class="input-label" style="display: block; margin-bottom: 12px; font-weight: 500;">Espessura</label>
                        <div class="typography-options">
                            <label class="typography-option">
                                <input type="radio" name="logoFontWeight" value="300">
                                <span class="option-label">Light</span>
                            </label>
                            <label class="typography-option">
                                <input type="radio" name="logoFontWeight" value="400">
                                <span class="option-label">Regular</span>
                            </label>
                            <label class="typography-option">
                                <input type="radio" name="logoFontWeight" value="500">
                                <span class="option-label">Medium</span>
                            </label>
                            <label class="typography-option">
                                <input type="radio" name="logoFontWeight" value="600" checked>
                                <span class="option-label">SemiBold</span>
                            </label>
                            <label class="typography-option">
                                <input type="radio" name="logoFontWeight" value="700">
                                <span class="option-label">Bold</span>
                            </label>
                        </div>
                    </div>

                    <!-- Letter Spacing -->
                    <div>
                        <label class="input-label" style="display: block; margin-bottom: 12px; font-weight: 500;">Espaçamento</label>
                        <div class="typography-options">
                            <label class="typography-option">
                                <input type="radio" name="logoLetterSpacing" value="0">
                                <span class="option-label">0</span>
                            </label>
                            <label class="typography-option">
                                <input type="radio" name="logoLetterSpacing" value="0.01">
                                <span class="option-label">0.01em</span>
                            </label>
                            <label class="typography-option">
                                <input type="radio" name="logoLetterSpacing" value="0.02" checked>
                                <span class="option-label">0.02em</span>
                            </label>
                            <label class="typography-option">
                                <input type="radio" name="logoLetterSpacing" value="0.05">
                                <span class="option-label">0.05em</span>
                            </label>
                            <label class="typography-option">
                                <input type="radio" name="logoLetterSpacing" value="0.1">
                                <span class="option-label">0.1em</span>
                            </label>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Gallery Settings (moved from Gallery tab) -->
            <section class="intro-section" style="margin-top: 40px;">
                <span class="section-title">Configurações da Galeria</span>
                
                <div class="setting-row" style="margin-top: 20px;">
                    <div class="setting-info">
                        <span class="setting-label">Modo de Cores</span>
                        <span class="setting-hint">Escolha como as imagens serão exibidas na galeria pública</span>
                    </div>
                    <div class="ios-toggle-wrapper">
                        <span class="toggle-label-left">P&B</span>
                        <label class="ios-switch" id="color-mode-switch">
                            <input type="checkbox" id="color-mode-toggle">
                            <span class="slider"></span>
                        </label>
                        <span class="toggle-label-right">Cores</span>
                    </div>
                </div>
                
                <div class="setting-row" style="margin-top: 20px;">
                    <div class="setting-info">
                        <span class="setting-label">Número de Colunas</span>
                        <span class="setting-hint">Defina quantas colunas a galeria terá no desktop (máximo)</span>
                    </div>
                    <div class="columns-selector">
                        <label class="column-option">
                            <input type="radio" name="galleryColumns" value="3">
                            <span class="column-label">3</span>
                        </label>
                        <label class="column-option">
                            <input type="radio" name="galleryColumns" value="4" checked>
                            <span class="column-label">4</span>
                        </label>
                        <label class="column-option">
                            <input type="radio" name="galleryColumns" value="5">
                            <span class="column-label">5</span>
                        </label>
                        <label class="column-option">
                            <input type="radio" name="galleryColumns" value="6">
                            <span class="column-label">6</span>
                        </label>
                    </div>
                </div>
                
                <div class="setting-row" style="margin-top: 20px;">
                    <div class="setting-info">
                        <span class="setting-label">Espaçamento entre Imagens</span>
                        <span class="setting-hint">Defina o espaço (gap) entre as imagens da galeria</span>
                    </div>
                    <div class="columns-selector">
                        <label class="column-option">
                            <input type="radio" name="galleryGap" value="2">
                            <span class="column-label">2px</span>
                        </label>
                        <label class="column-option">
                            <input type="radio" name="galleryGap" value="5">
                            <span class="column-label">5px</span>
                        </label>
                        <label class="column-option">
                            <input type="radio" name="galleryGap" value="10" checked>
                            <span class="column-label">10px</span>
                        </label>
                        <label class="column-option">
                            <input type="radio" name="galleryGap" value="12">
                            <span class="column-label">12px</span>
                        </label>
                        <label class="column-option">
                            <input type="radio" name="galleryGap" value="15">
                            <span class="column-label">15px</span>
                        </label>
                    </div>
                </div>
                
                <div class="setting-row" style="margin-top: 20px;">
                    <div class="setting-info">
                        <span class="setting-label">Bordas das Imagens</span>
                        <span class="setting-hint">Arredondamento das bordas das imagens</span>
                    </div>
                    <div class="columns-selector">
                        <label class="column-option">
                            <input type="radio" name="galleryBorderRadius" value="0">
                            <span class="column-label">Nenhum</span>
                        </label>
                        <label class="column-option">
                            <input type="radio" name="galleryBorderRadius" value="8" checked>
                            <span class="column-label">Suave</span>
                        </label>
                        <label class="column-option">
                            <input type="radio" name="galleryBorderRadius" value="16">
                            <span class="column-label">Médio</span>
                        </label>
                        <label class="column-option">
                            <input type="radio" name="galleryBorderRadius" value="24">
                            <span class="column-label">Arredondado</span>
                        </label>
                    </div>
                </div>
            </section>
        </div>

    </main>

    <!-- SortableJS (Drag & Drop Library) -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

    <!-- Hidden Input for Replace -->
    <input type="file" id="replaceInput" style="display: none;">

    <!-- Modal de Gerenciamento de Fotos do Projeto -->
    <div id="project-images-modal" class="modal-overlay" style="display: none;">
        <div class="modal-container" style="max-width: 1200px;">
            <div class="modal-header">
                <h2 class="modal-title" id="modal-project-title">Gerenciar Fotos do Projeto</h2>
                <button class="modal-close" onclick="closeProjectImagesModal()">×</button>
            </div>
            
            <div class="modal-body">
                <!-- Upload Area -->
                <div class="upload-zone" id="project-upload-zone" style="margin-bottom: 30px;">
                    <input type="file" id="project-images-input" multiple accept="image/*" style="display: none;">
                    <div class="upload-zone-content" onclick="document.getElementById('project-images-input').click()" style="cursor: pointer; padding: 40px; border: 2px dashed #ddd; border-radius: 12px; text-align: center; transition: all 0.3s;">
                        <span style="font-size: 3rem;">📁</span>
                        <p style="margin: 10px 0 5px; font-weight: 500;">Clique para fazer upload de fotos</p>
                        <p style="font-size: 0.85rem; opacity: 0.7;">Múltiplas seleções permitidas</p>
                    </div>
                </div>
                
                <!-- Images Grid -->
                <div id="project-images-grid" class="project-images-grid">
                    <!-- Fotos inseridas dinamicamente -->
                </div>
                
                <div id="project-images-empty" style="text-align: center; padding: 40px; opacity: 0.5; display: none;">
                    Nenhuma foto neste projeto ainda.
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden file input for collection image upload -->
    <input type="file" id="collectionImageInput" accept="image/*" style="display: none;">

    <!-- Admin Logic (ES6 Module) -->
    <script type="module" src="js/admin.js"></script>
</body>
</html>
