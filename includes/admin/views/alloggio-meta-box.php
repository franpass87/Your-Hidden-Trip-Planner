<?php
/**
 * Admin view for accommodation pricing management
 * 
 * @package YourHiddenTrip
 */

if (!defined('ABSPATH')) exit;

// Variables passed from meta box callback: $lat, $lng, etc.
?>
<style>
    .yht-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .yht-grid input, .yht-grid select{width:100%}
    .pricing-section{margin-top:20px;padding:15px;background:#f9f9f9;border-radius:8px;}
    .pricing-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:15px;margin-top:10px;}
    .pricing-card{padding:12px;background:white;border-radius:6px;border:1px solid #ddd;}
    .pricing-card h4{margin:0 0 8px;color:#333;}
    .price-input{font-size:14px;font-weight:bold;}
    .inclusions{margin-top:15px;padding:12px;background:#e8f5e8;border-radius:6px;}
</style>

<div class="yht-grid">
    <div><label>Latitudine</label><input type="text" name="yht_lat" value="<?php echo $lat; ?>" /></div>
    <div><label>Longitudine</label><input type="text" name="yht_lng" value="<?php echo $lng; ?>" /></div>
    <div><label>Fascia prezzo legacy</label><input type="text" name="yht_fascia_prezzo" value="<?php echo $fascia; ?>" /></div>
    <div><label>Capienza (persone)</label><input type="number" name="yht_capienza" value="<?php echo $capienza; ?>" /></div>
</div>

<div class="pricing-section">
    <h3>🏨 Prezzi All-Inclusive per notte (per persona)</h3>
    <p class="description">Imposta i prezzi che includono alloggio + tutti i servizi del pacchetto scelto.</p>
    
    <div class="pricing-grid">
        <div class="pricing-card">
            <h4>⭐ Standard</h4>
            <p style="font-size:12px;color:#666;margin:0 0 8px;">Comfort essenziale + colazione + cena</p>
            <input type="number" step="0.01" name="yht_prezzo_notte_standard" value="<?php echo $prezzo_standard; ?>" 
                   class="price-input" placeholder="es. 80.00" />
            <span style="font-size:12px;color:#888;">€/notte per persona</span>
        </div>
        
        <div class="pricing-card">
            <h4>⭐⭐ Premium</h4>
            <p style="font-size:12px;color:#666;margin:0 0 8px;">Esperienza superiore + tutti i pasti</p>
            <input type="number" step="0.01" name="yht_prezzo_notte_premium" value="<?php echo $prezzo_premium; ?>" 
                   class="price-input" placeholder="es. 120.00" />
            <span style="font-size:12px;color:#888;">€/notte per persona</span>
        </div>
        
        <div class="pricing-card">
            <h4>⭐⭐⭐ Luxury</h4>
            <p style="font-size:12px;color:#666;margin:0 0 8px;">Massimo lusso + servizi esclusivi</p>
            <input type="number" step="0.01" name="yht_prezzo_notte_luxury" value="<?php echo $prezzo_luxury; ?>" 
                   class="price-input" placeholder="es. 200.00" />
            <span style="font-size:12px;color:#888;">€/notte per persona</span>
        </div>
    </div>
</div>

<div class="inclusions">
    <h3>🍽️ Servizi di ristorazione inclusi nei prezzi</h3>
    <p class="description">Seleziona quali pasti sono automaticamente inclusi nei prezzi sopra indicati.</p>
    
    <div class="yht-grid">
        <div>
            <label style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" name="yht_incluso_colazione" value="1" <?php checked($colazione,'1'); ?> />
                🥐 Colazione inclusa nel prezzo
            </label>
        </div>
        <div>
            <label style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" name="yht_incluso_pranzo" value="1" <?php checked($pranzo,'1'); ?> />
                🍝 Pranzo incluso nel prezzo
            </label>
        </div>
        <div>
            <label style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" name="yht_incluso_cena" value="1" <?php checked($cena,'1'); ?> />
                🍷 Cena inclusa nel prezzo
            </label>
        </div>
    </div>
</div>

<div style="margin-top:20px;padding:12px;background:#fff3cd;border:1px solid #ffc107;border-radius:6px;">
    <strong>💡 Suggerimento:</strong> I prezzi dovrebbero includere tutti i costi per il livello del pacchetto. 
    Il sistema calcolerà automaticamente i costi aggiuntivi per attività, trasporti e servizi extra.
</div>