<style>
    .yht-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .yht-grid input[type=text], .yht-grid textarea{width:100%}
    .yht-chiusure-list li{margin-bottom:6px}
</style>
<div class="yht-grid">
    <div><label>Latitudine</label><input type="text" name="yht_lat" value="<?php echo $lat; ?>" /></div>
    <div><label>Longitudine</label><input type="text" name="yht_lng" value="<?php echo $lng; ?>" /></div>
    <div><label>Costo ingresso (€)</label><input type="text" name="yht_cost_ingresso" value="<?php echo $cst; ?>" /></div>
    <div><label>Durata media visita (min)</label><input type="text" name="yht_durata_min" value="<?php echo $dur; ?>" /></div>
    <div><label><input type="checkbox" name="yht_accesso_family" value="1" <?php checked($fam,'1'); ?> /> Family-friendly</label></div>
    <div><label><input type="checkbox" name="yht_accesso_pet" value="1" <?php checked($pet,'1'); ?> /> Pet-friendly</label></div>
    <div><label><input type="checkbox" name="yht_accesso_mobility" value="1" <?php checked($mob,'1'); ?> /> Accessibilità ridotta</label></div>
    <div style="grid-column:1/3"><label>Orari / Note</label><textarea name="yht_orari_note" rows="3"><?php echo $ora; ?></textarea></div>
</div>
<hr/>
<h4>Chiusure/Disponibilità</h4>
<p class="description">Aggiungi periodi non prenotabili (es. manutenzione). Verranno esclusi dal generatore.</p>
<div id="yht-chiusure-wrap" data-json="<?php echo esc_attr($chi); ?>">
    <table class="widefat">
        <thead><tr><th>Dal</th><th>Al</th><th>Nota</th><th></th></tr></thead>
        <tbody id="yht-chiusure-body"></tbody>
    </table>
    <p><button class="button" type="button" id="yht-add-closure">+ Aggiungi chiusura</button></p>
    <input type="hidden" name="yht_chiusure_json" id="yht_chiusure_json" value="<?php echo esc_attr($chi); ?>"/>
</div>
