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
<script>
    (function($){
        const $wrap = $('#yht-chiusure-wrap');
        let data = [];
        try{ data = JSON.parse($wrap.data('json')||'[]'); }catch(e){ data=[]; }
        function render(){
            const $body = $('#yht-chiusure-body'); $body.empty();
            data.forEach((row,i)=>{
                const tr = $(`
                    <tr>
                        <td><input type="date" value="${row.start||''}" class="yht-c-start"/></td>
                        <td><input type="date" value="${row.end||''}" class="yht-c-end"/></td>
                        <td><input type="text" value="${row.note||''}" class="yht-c-note"/></td>
                        <td><a href="#" data-i="${i}" class="yht-c-del">Rimuovi</a></td>
                    </tr>`);
                $body.append(tr);
            });
            $('#yht_chiusure_json').val(JSON.stringify(data));
        }
        render();
        $('#yht-add-closure').on('click', function(){
            data.push({start:'',end:'',note:''}); render();
        });
        $(document).on('input','.yht-c-start', function(){ data[$(this).closest('tr').index()].start = this.value; $('#yht_chiusure_json').val(JSON.stringify(data)); });
        $(document).on('input','.yht-c-end', function(){ data[$(this).closest('tr').index()].end = this.value; $('#yht_chiusure_json').val(JSON.stringify(data)); });
        $(document).on('input','.yht-c-note', function(){ data[$(this).closest('tr').index()].note = this.value; $('#yht_chiusure_json').val(JSON.stringify(data)); });
        $(document).on('click','.yht-c-del', function(e){ e.preventDefault(); data.splice($(this).data('i'),1); render(); });
    })(jQuery);
</script>