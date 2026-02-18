<?php
require_once 'admin_layout_top.php';
$pageScript = 'admin_settings.js';

// Carregar dados via PHP para máxima segurança
$siteTitle = getSetting('site_title', '');
$logoText = getSetting('logo_text', '');
$logoFontFamily = getSetting('logo_font_family', 'Inter');
$logoFontSize = getSetting('logo_font_size', '20');
$galleryColorMode = getSetting('gallery_color_mode', 'color');
$galleryColumns = getSetting('gallery_columns', '4');

require_once 'admin_header.php';
?>

<section class="intro-section">
    <h1 class="section-title">Configurações Gerais</h1>
    
    <div class="input-group" style="margin-top: 30px; max-width: 600px;">
        <label class="input-label">Título do Site</label>
        <input type="text" id="siteTitle" class="admin-input" 
            value="<?php echo htmlspecialchars($siteTitle); ?>"
            placeholder="Ex: Wide Studio - Art Direction">
        <p class="input-hint" style="font-size: 0.85rem; color: #888; margin-top: 8px;">Aparece na aba do navegador e metadados SEO</p>
    </div>
</section>

<!-- Logo Customization -->
<section class="intro-section" style="margin-top: 60px;">
    <h2 class="section-title">Logo do Header</h2>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 30px;">
        <div class="input-group">
            <label class="input-label">Texto da Logo</label>
            <input type="text" id="logoText" class="admin-input" 
                value="<?php echo htmlspecialchars($logoText); ?>"
                placeholder="Ex: Gustavo Starke">
            <p class="input-hint" style="font-size: 0.85rem; color: #888; margin-top: 8px;">O texto que aparece no canto superior esquerdo</p>
        </div>

        <!-- Font Family -->
        <div class="input-group">
            <label class="input-label">Fonte da Logo</label>
            <div style="position: relative;">
                <select id="logoFontFamily" class="admin-input" style="width: 100%; border: none; border-bottom: 1px solid rgba(0,0,0,0.1); font-size: 1.1rem; padding: 12px 0; background: transparent; cursor: pointer; -webkit-appearance: none;">
                    <?php 
                    $fonts = ['Inter' => 'Inter (Padrão)', 'Montserrat' => 'Montserrat', 'Playfair Display' => 'Playfair Display', 'Lato' => 'Lato', 'Cormorant Garamond' => 'Cormorant Garamond', 'Roboto' => 'Roboto', 'Outfit' => 'Outfit'];
                    foreach ($fonts as $val => $label): 
                        $selected = ($logoFontFamily == $val) ? 'selected' : '';
                    ?>
                        <option value="<?php echo $val; ?>" <?php echo $selected; ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
                <span style="position: absolute; right: 0; top: 15px; pointer-events: none; opacity: 0.3;">▼</span>
            </div>
            <p class="input-hint" style="font-size: 0.85rem; color: #888; margin-top: 8px;">Escolha a tipografia que melhor define sua marca</p>
        </div>
    </div>

    <!-- Logo Preview -->
    <div style="margin-top: 40px; padding: 50px; background: rgba(0,0,0,0.02); border-radius: 16px; border: 1px solid rgba(0,0,0,0.06); display: flex; align-items: center; justify-content: center; min-height: 140px;">
        <div style="text-align: center;">
            <p style="font-size: 0.7rem; opacity: 0.4; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 0.1em; font-weight: 600;">Visualização em tempo real</p>
            <span id="logoPreview" style="font-family: var(--font-main); font-size: <?php echo $logoFontSize; ?>px; font-weight: 600; letter-spacing: 0.02em; color: #000; text-transform: uppercase;">
                <?php echo $logoText ? htmlspecialchars($logoText) : 'Logo Preview'; ?>
            </span>
        </div>
    </div>

    <div style="margin-top: 40px; max-width: 400px;">
        <label class="input-label" style="display: block; margin-bottom: 15px; font-weight: 500;">Tamanho da Logo</label>
        <div class="typography-options" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <?php foreach(['16','18','20','22','24'] as $size): 
                $checked = ($logoFontSize == $size) ? 'checked' : '';
            ?>
                <label class="typography-option">
                    <input type="radio" name="logoFontSize" value="<?php echo $size; ?>" <?php echo $checked; ?>>
                    <span class="option-label" style="padding: 8px 16px; border: 1px solid rgba(0,0,0,0.1); border-radius: 50px; cursor: pointer; font-size: 0.9rem;"><?php echo $size; ?>px</span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Gallery Settings -->
<section class="intro-section" style="margin-top: 60px;">
    <h2 class="section-title">Comportamento da Galeria</h2>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px; margin-top: 30px;">
        <div class="setting-row" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(0,0,0,0.05); padding-bottom: 25px;">
            <div class="setting-info">
                <span class="setting-label" style="display: block; font-weight: 500; font-size: 1.1rem;">Modo de Cores</span>
                <span class="setting-hint" style="font-size: 0.85rem; color: #888; margin-top: 4px;">Exibição inicial das fotos</span>
            </div>
            <div class="ios-toggle-wrapper" style="display: flex; align-items: center; gap: 12px;">
                <span class="toggle-label-left" style="font-size: 0.8rem; opacity: 0.5;">P&B</span>
                <label class="ios-switch">
                    <input type="checkbox" id="color-mode-toggle" <?php echo ($galleryColorMode === 'color') ? 'checked' : ''; ?>>
                    <span class="slider"></span>
                </label>
                <span class="toggle-label-right" style="font-size: 0.8rem; font-weight: 500;">COLOR</span>
            </div>
        </div>
        
        <div class="setting-row" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(0,0,0,0.05); padding-bottom: 25px;">
            <div class="setting-info">
                <span class="setting-label" style="display: block; font-weight: 500; font-size: 1.1rem;">Layout de Colunas</span>
                <span class="setting-hint" style="font-size: 0.85rem; color: #888; margin-top: 4px;">Colunas máximas no Desktop</span>
            </div>
            <div class="columns-selector" style="display: flex; gap: 8px;">
                <?php foreach([3,4,5,6] as $col): 
                    $checked = ($galleryColumns == $col) ? 'checked' : '';
                ?>
                    <label class="column-option">
                        <input type="radio" name="galleryColumns" value="<?php echo $col; ?>" <?php echo $checked; ?>>
                        <span class="column-label" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(0,0,0,0.1); border-radius: 50%; cursor: pointer; font-size: 0.9rem;"><?php echo $col; ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once 'admin_layout_bottom.php'; ?>
