<?php
/**
 * Enhanced Trip Builder Template
 * Uses external CSS/JS files for modern, clean implementation
 * 
 * @package YourHiddenTrip
 * @version 6.2
 */

if (!defined('ABSPATH')) exit;

// Get current language for multilingual support
$current_lang = isset($current_lang) ? $current_lang : 'it';
$plugin_settings = YHT_Plugin::get_instance()->get_settings();
?>

<div id="yht-builder" class="yht-wrap" aria-live="polite">
  
  <!-- Theme Toggle Button -->
  <button class="yht-theme-toggle" aria-label="<?php _e('Cambia tema', 'your-hidden-trip'); ?>" title="<?php _e('Cambia tema scuro/chiaro', 'your-hidden-trip'); ?>">
    <span class="theme-icon light">🌞</span>
    <span class="theme-icon dark" style="display:none;">🌙</span>
  </button>

  <!-- Enhanced Header -->
  <header class="yht-header" role="banner">
    <div class="yht-badge"><?php printf(__('Versione %s', 'your-hidden-trip'), '6.3'); ?></div>
    <h1 class="yht-title"><?php _e('Your Hidden Trip Builder', 'your-hidden-trip'); ?></h1>
    <div class="yht-subtitle"><?php _e('Scopri itinerari unici in Tuscia & Umbria', 'your-hidden-trip'); ?></div>
  </header>

  <!-- Enhanced Progress Bar -->
  <div class="yht-progressbar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
    <i style="width:0%"></i>
  </div>

  <!-- Enhanced Steps Navigation -->
  <nav class="yht-steps" role="tablist" aria-label="<?php _e('Passi del trip builder', 'your-hidden-trip'); ?>">
    <button class="yht-step" role="tab" tabindex="0" aria-selected="true" data-active="true" data-step="1" data-label="<?php _e('Esperienza', 'your-hidden-trip'); ?>">1</button>
    <div class="yht-line" role="presentation"><i style="width:0%"></i></div>
    <button class="yht-step" role="tab" tabindex="-1" aria-selected="false" data-step="2" data-label="<?php _e('Destinazione', 'your-hidden-trip'); ?>">2</button>
    <div class="yht-line" role="presentation"><i style="width:0%"></i></div>
    <button class="yht-step" role="tab" tabindex="-1" aria-selected="false" data-step="3" data-label="<?php _e('Attività', 'your-hidden-trip'); ?>">3</button>
    <div class="yht-line" role="presentation"><i style="width:0%"></i></div>
    <button class="yht-step" role="tab" tabindex="-1" aria-selected="false" data-step="4" data-label="<?php _e('Alloggio', 'your-hidden-trip'); ?>">4</button>
    <div class="yht-line" role="presentation"><i style="width:0%"></i></div>
    <button class="yht-step" role="tab" tabindex="-1" aria-selected="false" data-step="5" data-label="<?php _e('Durata', 'your-hidden-trip'); ?>">5</button>
    <div class="yht-line" role="presentation"><i style="width:0%"></i></div>
    <button class="yht-step" role="tab" tabindex="-1" aria-selected="false" data-step="6" data-label="<?php _e('Riepilogo', 'your-hidden-trip'); ?>">6</button>
  </nav>

  <!-- Step 1: Tipo Esperienza -->
  <section id="yht-step1" class="yht-stepview" data-show="true" role="tabpanel" aria-labelledby="yht-h2-1">
    <h2 id="yht-h2-1" class="yht-h2"><?php _e('Che tipo di esperienza desideri?', 'your-hidden-trip'); ?></h2>
    <p class="yht-help"><?php _e('Scegli il tema principale del tuo viaggio in Tuscia e Umbria.', 'your-hidden-trip'); ?></p>
    
    <div class="yht-grid" role="radiogroup" aria-labelledby="yht-h2-1">
      <article class="yht-card yht-animate-in" tabindex="0" role="radio" aria-checked="false" data-group="esperienza" data-value="enogastronomica">
        <button class="yht-wishlist-btn" data-item-id="exp-enogastronomica" aria-label="<?php _e('Aggiungi ai preferiti', 'your-hidden-trip'); ?>">🤍</button>
        <div class="yht-ico">🍷</div>
        <div class="yht-t"><?php _e('Enogastronomica', 'your-hidden-trip'); ?></div>
        <div class="yht-d"><?php _e('Degustazioni, cantine, prodotti tipici', 'your-hidden-trip'); ?></div>
        <div class="yht-features">
          <span>✨ <?php _e('Visite esclusive', 'your-hidden-trip'); ?></span>
          <span>🧑‍🍳 <?php _e('Chef locali', 'your-hidden-trip'); ?></span>
        </div>
      </article>
      
      <article class="yht-card yht-animate-in" tabindex="0" role="radio" aria-checked="false" data-group="esperienza" data-value="storico_culturale">
        <button class="yht-wishlist-btn" data-item-id="exp-storico" aria-label="<?php _e('Aggiungi ai preferiti', 'your-hidden-trip'); ?>">🤍</button>
        <div class="yht-ico">🏛️</div>
        <div class="yht-t"><?php _e('Storico-Culturale', 'your-hidden-trip'); ?></div>
        <div class="yht-d"><?php _e('Musei, borghi medievali, siti archeologici', 'your-hidden-trip'); ?></div>
        <div class="yht-features">
          <span>📚 <?php _e('Guide esperte', 'your-hidden-trip'); ?></span>
          <span>🎭 <?php _e('Eventi culturali', 'your-hidden-trip'); ?></span>
        </div>
      </article>
      
      <article class="yht-card yht-animate-in" tabindex="0" role="radio" aria-checked="false" data-group="esperienza" data-value="natura_relax">
        <button class="yht-wishlist-btn" data-item-id="exp-natura" aria-label="<?php _e('Aggiungi ai preferiti', 'your-hidden-trip'); ?>">🤍</button>
        <div class="yht-ico">🌿</div>
        <div class="yht-t"><?php _e('Natura e Relax', 'your-hidden-trip'); ?></div>
        <div class="yht-d"><?php _e('Terme, parchi naturali, wellness', 'your-hidden-trip'); ?></div>
        <div class="yht-features">
          <span>🧘 <?php _e('Spa & Terme', 'your-hidden-trip'); ?></span>
          <span>🥾 <?php _e('Trekking guidato', 'your-hidden-trip'); ?></span>
        </div>
      </article>
      
      <article class="yht-card yht-animate-in" tabindex="0" role="radio" aria-checked="false" data-group="esperienza" data-value="avventura">
        <button class="yht-wishlist-btn" data-item-id="exp-avventura" aria-label="<?php _e('Aggiungi ai preferiti', 'your-hidden-trip'); ?>">🤍</button>
        <div class="yht-ico">⛰️</div>
        <div class="yht-t"><?php _e('Avventura Outdoor', 'your-hidden-trip'); ?></div>
        <div class="yht-d"><?php _e('Trekking, mountain bike, attività sportive', 'your-hidden-trip'); ?></div>
        <div class="yht-features">
          <span>🚴 <?php _e('E-bike incluse', 'your-hidden-trip'); ?></span>
          <span>🧗 <?php _e('Climbing', 'your-hidden-trip'); ?></span>
        </div>
      </article>
      
      <article class="yht-card yht-animate-in" tabindex="0" role="radio" aria-checked="false" data-group="esperienza" data-value="romantica">
        <button class="yht-wishlist-btn" data-item-id="exp-romantica" aria-label="<?php _e('Aggiungi ai preferiti', 'your-hidden-trip'); ?>">🤍</button>
        <div class="yht-ico">💕</div>
        <div class="yht-t"><?php _e('Romantica', 'your-hidden-trip'); ?></div>
        <div class="yht-d"><?php _e('Cene a lume di candela, tramonti, coppia', 'your-hidden-trip'); ?></div>
        <div class="yht-features">
          <span>💐 <?php _e('Sorprese incluse', 'your-hidden-trip'); ?></span>
          <span>🥂 <?php _e('Champagne', 'your-hidden-trip'); ?></span>
        </div>
      </article>
      
      <article class="yht-card yht-animate-in" tabindex="0" role="radio" aria-checked="false" data-group="esperienza" data-value="famiglia">
        <button class="yht-wishlist-btn" data-item-id="exp-famiglia" aria-label="<?php _e('Aggiungi ai preferiti', 'your-hidden-trip'); ?>">🤍</button>
        <div class="yht-ico">👨‍👩‍👧‍👦</div>
        <div class="yht-t"><?php _e('Famiglia', 'your-hidden-trip'); ?></div>
        <div class="yht-d"><?php _e('Attività per bambini, family-friendly', 'your-hidden-trip'); ?></div>
        <div class="yht-features">
          <span>🎪 <?php _e('Attività bimbi', 'your-hidden-trip'); ?></span>
          <span>🏰 <?php _e('Castelli da esplorare', 'your-hidden-trip'); ?></span>
        </div>
      </article>
    </div>
    
    <div class="yht-error" id="yht-err1" role="alert" aria-live="polite">
      <?php _e('Seleziona almeno un tipo di esperienza.', 'your-hidden-trip'); ?>
    </div>
    
    <div class="yht-actions">
      <button class="yht-btn yht-btn-primary" data-next="1" disabled>
        <?php _e('Continua', 'your-hidden-trip'); ?>
        <span class="btn-arrow">→</span>
      </button>
      <button class="yht-btn yht-btn-ghost" data-reset="1">
        <?php _e('Reset selezione', 'your-hidden-trip'); ?>
      </button>
    </div>
  </section>

  <!-- Step 2: Destinazione -->
  <section id="yht-step2" class="yht-stepview" role="tabpanel" aria-labelledby="yht-h2-2">
    <h2 id="yht-h2-2" class="yht-h2"><?php _e('Quale zona ti interessa di più?', 'your-hidden-trip'); ?></h2>
    <p class="yht-help"><?php _e('Esplora le bellezze di Tuscia e Umbria, ogni zona ha le sue gemme nascoste.', 'your-hidden-trip'); ?></p>
    
    <div class="yht-grid" role="radiogroup" aria-labelledby="yht-h2-2">
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="destinazione" data-value="viterbo_tuscia">
        <button class="yht-share-btn" aria-label="<?php _e('Condividi', 'your-hidden-trip'); ?>">📤</button>
        <button class="yht-wishlist-btn" data-item-id="dest-viterbo" aria-label="<?php _e('Aggiungi ai preferiti', 'your-hidden-trip'); ?>">🤍</button>
        <div class="yht-ico">🏰</div>
        <div class="yht-t"><?php _e('Viterbo e Alta Tuscia', 'your-hidden-trip'); ?></div>
        <div class="yht-d"><?php _e('Città dei Papi, terme, borghi etruschi', 'your-hidden-trip'); ?></div>
        <div class="yht-price">€<?php _e('89/pax', 'your-hidden-trip'); ?></div>
      </article>
      
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="destinazione" data-value="lago_bolsena">
        <button class="yht-share-btn" aria-label="<?php _e('Condividi', 'your-hidden-trip'); ?>">📤</button>
        <button class="yht-wishlist-btn" data-item-id="dest-bolsena" aria-label="<?php _e('Aggiungi ai preferiti', 'your-hidden-trip'); ?>">🤍</button>
        <div class="yht-ico">🌊</div>
        <div class="yht-t"><?php _e('Lago di Bolsena', 'your-hidden-trip'); ?></div>
        <div class="yht-d"><?php _e('Il lago più grande del Lazio, isole e tradizioni', 'your-hidden-trip'); ?></div>
        <div class="yht-price">€<?php _e('95/pax', 'your-hidden-trip'); ?></div>
      </article>
      
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="destinazione" data-value="orvieto_umbria">
        <button class="yht-share-btn" aria-label="<?php _e('Condividi', 'your-hidden-trip'); ?>">📤</button>
        <button class="yht-wishlist-btn" data-item-id="dest-orvieto" aria-label="<?php _e('Aggiungi ai preferiti', 'your-hidden-trip'); ?>">🤍</button>
        <div class="yht-ico">⛪</div>
        <div class="yht-t"><?php _e('Orvieto e Umbria Sud', 'your-hidden-trip'); ?></div>
        <div class="yht-d"><?php _e('Duomo gotico, vini pregiati, sotterranei', 'your-hidden-trip'); ?></div>
        <div class="yht-price">€<?php _e('105/pax', 'your-hidden-trip'); ?></div>
      </article>
      
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="destinazione" data-value="todi_spoleto">
        <button class="yht-share-btn" aria-label="<?php _e('Condividi', 'your-hidden-trip'); ?>">📤</button>
        <button class="yht-wishlist-btn" data-item-id="dest-todi" aria-label="<?php _e('Aggiungi ai preferiti', 'your-hidden-trip'); ?>">🤍</button>
        <div class="yht-ico">🏘️</div>
        <div class="yht-t"><?php _e('Todi e Spoleto', 'your-hidden-trip'); ?></div>
        <div class="yht-d"><?php _e('Borghi medievali, festival, arte contemporanea', 'your-hidden-trip'); ?></div>
        <div class="yht-price">€<?php _e('99/pax', 'your-hidden-trip'); ?></div>
      </article>
      
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="destinazione" data-value="assisi_perugia">
        <button class="yht-share-btn" aria-label="<?php _e('Condividi', 'your-hidden-trip'); ?>">📤</button>
        <button class="yht-wishlist-btn" data-item-id="dest-assisi" aria-label="<?php _e('Aggiungi ai preferiti', 'your-hidden-trip'); ?>">🤍</button>
        <div class="yht-ico">🙏</div>
        <div class="yht-t"><?php _e('Assisi e Perugia', 'your-hidden-trip'); ?></div>
        <div class="yht-d"><?php _e('Spiritualità, arte francescana, cioccolato', 'your-hidden-trip'); ?></div>
        <div class="yht-price">€<?php _e('115/pax', 'your-hidden-trip'); ?></div>
      </article>
      
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="destinazione" data-value="mix_personalizzato">
        <button class="yht-share-btn" aria-label="<?php _e('Condividi', 'your-hidden-trip'); ?>">📤</button>
        <button class="yht-wishlist-btn" data-item-id="dest-mix" aria-label="<?php _e('Aggiungi ai preferiti', 'your-hidden-trip'); ?>">🤍</button>
        <div class="yht-ico">🗺️</div>
        <div class="yht-t"><?php _e('Mix Personalizzato', 'your-hidden-trip'); ?></div>
        <div class="yht-d"><?php _e('Un itinerario su misura tra Tuscia e Umbria', 'your-hidden-trip'); ?></div>
        <div class="yht-price"><?php _e('Su richiesta', 'your-hidden-trip'); ?></div>
      </article>
    </div>
    
    <div class="yht-error" id="yht-err2" role="alert" aria-live="polite">
      <?php _e('Seleziona una destinazione.', 'your-hidden-trip'); ?>
    </div>
    
    <div class="yht-actions">
      <button class="yht-btn yht-btn-primary" data-next="2" disabled>
        <?php _e('Continua', 'your-hidden-trip'); ?>
        <span class="btn-arrow">→</span>
      </button>
      <button class="yht-btn yht-btn-secondary" data-prev="2">
        <span class="btn-arrow">←</span>
        <?php _e('Indietro', 'your-hidden-trip'); ?>
      </button>
    </div>
  </section>

  <!-- Notification Container -->
  <div class="yht-notifications" id="yht-notifications" aria-live="polite" aria-atomic="false"></div>
  
  <!-- Loading Overlay -->
  <div class="yht-loading-overlay" id="yht-loading" style="display: none;">
    <div class="yht-spinner"></div>
    <p><?php _e('Creazione del tuo itinerario personalizzato...', 'your-hidden-trip'); ?></p>
  </div>

</div>

<!-- Enhanced Summary Preview (shown when data is selected) -->
<div class="yht-summary-preview" id="yht-summary-preview" style="display: none;">
  <h3><?php _e('Anteprima Viaggio', 'your-hidden-trip'); ?></h3>
  <div class="summary-content"></div>
  <button class="yht-btn yht-btn-small" onclick="document.getElementById('yht-summary-preview').style.display='none';">
    <?php _e('Chiudi', 'your-hidden-trip'); ?>
  </button>
</div>

<style>
/* Enhanced Summary Preview */
.yht-summary-preview {
  position: fixed;
  top: 20px;
  right: 20px;
  background: var(--card);
  border: 2px solid var(--primary);
  border-radius: var(--radius);
  padding: 16px;
  max-width: 300px;
  box-shadow: var(--shadow-lg);
  z-index: 1000;
  animation: slideInRight 0.3s ease;
}

.yht-summary-preview h3 {
  margin: 0 0 12px;
  color: var(--primary);
  font-size: 1.1rem;
}

.yht-btn-small {
  padding: 6px 12px;
  font-size: 0.85rem;
  margin-top: 8px;
}

/* Enhanced Features List */
.yht-features {
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin-top: 8px;
  font-size: 0.85rem;
}

.yht-features span {
  color: var(--success);
  font-weight: 500;
}

/* Button Enhancements */
.btn-arrow {
  font-size: 1rem;
  margin-left: 4px;
}

.yht-btn-primary .btn-arrow {
  margin-left: 8px;
}

.yht-btn-secondary .btn-arrow {
  margin-right: 8px;
  margin-left: 0;
}

/* Loading Overlay */
.yht-loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.8);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  color: white;
}

.yht-spinner {
  width: 50px;
  height: 50px;
  border: 4px solid rgba(255, 255, 255, 0.3);
  border-top: 4px solid var(--primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 16px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>

<script>
// Initialize trip builder when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
  // This will be handled by the YHTEnhancer class
  console.log('Trip Builder Enhanced Template Loaded');
  
  // Accessibility improvements
  const cards = document.querySelectorAll('.yht-card[role="radio"]');
  cards.forEach(card => {
    card.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        card.click();
      }
    });
  });
  
  // Initialize wishlist states from localStorage
  const wishlist = JSON.parse(localStorage.getItem('yht-wishlist') || '[]');
  document.querySelectorAll('.yht-wishlist-btn').forEach(btn => {
    const itemId = btn.dataset.itemId;
    if (wishlist.includes(itemId)) {
      btn.innerHTML = '❤️';
      btn.setAttribute('aria-label', '<?php _e("Rimuovi dai preferiti", "your-hidden-trip"); ?>');
    }
  });
});
</script>