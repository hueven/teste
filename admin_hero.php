<?php
require_once 'admin_layout_top.php';
$pageScript = 'admin_hero.js';

// Carregar dados via PHP
$heroText = getSetting('hero_text', '');

require_once 'admin_header.php';
?>

<section class="intro-section">
    <h1 class="section-title">Texto Principal da Home</h1>
    <div class="input-group" style="margin-top: 15px;">
        <textarea id="heroText" class="admin-input" rows="8" 
            placeholder="Digite o texto de introdução do site..."><?php echo htmlspecialchars($heroText); ?></textarea>
        <p style="font-size: 0.85rem; color: #888; margin-top: 15px;">
            💡 Use <code>&lt;texto&gt;</code> para criar uma pill (destaque). <br>
            Exemplo: "Design <code>&lt;profissional&gt;</code> e moderno"
        </p>
    </div>
    
    <div style="margin-top: 50px; display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
        <!-- Font Size -->
        <div>
            <label class="input-label" style="display: block; margin-bottom: 15px; font-weight: 600;">Tamanho do Texto</label>
            <div class="typography-options">
                <?php 
                $currentFontSize = getSetting('hero_font_size', 'medium');
                $sizes = ['xs' => 'Extra Small', 'sm' => 'Small', 'medium' => 'Medium', 'lg' => 'Large', 'xl' => 'Extra Large'];
                foreach ($sizes as $val => $label): 
                    $checked = ($currentFontSize == $val) ? 'checked' : '';
                ?>
                <label class="typography-option">
                    <input type="radio" name="heroFontSize" value="<?php echo $val; ?>" <?php echo $checked; ?>>
                    <span class="option-label"><?php echo $label; ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Font Weight -->
        <div>
            <label class="input-label" style="display: block; margin-bottom: 15px; font-weight: 600;">Espessura do Texto</label>
            <div class="typography-options">
                <?php 
                $currentFontWeight = getSetting('hero_font_weight', 'regular');
                $weights = ['thin' => 'Thin', 'light' => 'Light', 'regular' => 'Regular', 'medium' => 'Medium', 'bold' => 'Bold'];
                foreach ($weights as $val => $label): 
                    $checked = ($currentFontWeight == $val) ? 'checked' : '';
                ?>
                <label class="typography-option">
                    <input type="radio" name="heroFontWeight" value="<?php echo $val; ?>" <?php echo $checked; ?>>
                    <span class="option-label"><?php echo $label; ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once 'admin_layout_bottom.php'; ?>
