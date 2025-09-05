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

// Get plugin settings with error handling
$plugin_settings = array();
try {
    if (class_exists('YHT_Plugin')) {
        $plugin_instance = YHT_Plugin::get_instance();
        if ($plugin_instance) {
            $plugin_settings = $plugin_instance->get_settings();
        }
    }
} catch (Exception $e) {
    // Fallback to empty settings array if plugin instance fails
    $plugin_settings = array();
}
?>

<div id="yht-builder" class="yht-wrap" aria-live="polite">
  
  <!-- Theme Toggle Button -->
  <button class="yht-theme-toggle" aria-label="<?php _e('Cambia tema', 'your-hidden-trip'); ?>" title="<?php _e('Cambia tema scuro/chiaro', 'your-hidden-trip'); ?>">
    <span class="theme-icon light">🌞</span>
    <span class="theme-icon dark" style="display:none;">🌙</span>
  </button>

  <!-- Enhanced Header -->
  <header class="yht-header" role="banner">
    <div class="yht-badge"><?php printf(__('Versione %s', 'your-hidden-trip'), '6.3.0'); ?></div>
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

  <!-- Step 3: Attività -->
  <section id="yht-step3" class="yht-stepview" role="tabpanel" aria-labelledby="yht-h2-3">
    <h2 id="yht-h2-3" class="yht-h2"><?php _e('Che tipo di attività ti interessano?', 'your-hidden-trip'); ?></h2>
    <p class="yht-help"><?php _e('Seleziona le attività che vorresti includere nel tuo viaggio.', 'your-hidden-trip'); ?></p>
    
    <div class="yht-grid" role="group" aria-labelledby="yht-h2-3">
      <article class="yht-card" tabindex="0" role="checkbox" aria-checked="false" data-group="attivita" data-value="enogastronomia">
        <button class="yht-wishlist-btn" data-item-id="act-enogastronomia" aria-label="<?php _e('Aggiungi ai preferiti', 'your-hidden-trip'); ?>">🤍</button>
        <div class="yht-ico">🍷</div>
        <div class="yht-t"><?php _e('Enogastronomia', 'your-hidden-trip'); ?></div>
        <div class="yht-d"><?php _e('Degustazioni vini, cantine, ristoranti tipici', 'your-hidden-trip'); ?></div>
      </article>
      
      <article class="yht-card" tabindex="0" role="checkbox" aria-checked="false" data-group="attivita" data-value="cultura">
        <button class="yht-wishlist-btn" data-item-id="act-cultura" aria-label="<?php _e('Aggiungi ai preferiti', 'your-hidden-trip'); ?>">🤍</button>
        <div class="yht-ico">🏛️</div>
        <div class="yht-t"><?php _e('Cultura', 'your-hidden-trip'); ?></div>
        <div class="yht-d"><?php _e('Musei, monumenti, siti archeologici', 'your-hidden-trip'); ?></div>
      </article>
      
      <article class="yht-card" tabindex="0" role="checkbox" aria-checked="false" data-group="attivita" data-value="natura">
        <button class="yht-wishlist-btn" data-item-id="act-natura" aria-label="<?php _e('Aggiungi ai preferiti', 'your-hidden-trip'); ?>">🤍</button>
        <div class="yht-ico">🌿</div>
        <div class="yht-t"><?php _e('Natura', 'your-hidden-trip'); ?></div>
        <div class="yht-d"><?php _e('Trekking, parchi naturali, escursioni', 'your-hidden-trip'); ?></div>
      </article>
      
      <article class="yht-card" tabindex="0" role="checkbox" aria-checked="false" data-group="attivita" data-value="benessere">
        <button class="yht-wishlist-btn" data-item-id="act-benessere" aria-label="<?php _e('Aggiungi ai preferiti', 'your-hidden-trip'); ?>">🤍</button>
        <div class="yht-ico">🧘</div>
        <div class="yht-t"><?php _e('Benessere', 'your-hidden-trip'); ?></div>
        <div class="yht-d"><?php _e('Terme, spa, relax e meditazione', 'your-hidden-trip'); ?></div>
      </article>
      
      <article class="yht-card" tabindex="0" role="checkbox" aria-checked="false" data-group="attivita" data-value="avventura">
        <button class="yht-wishlist-btn" data-item-id="act-avventura" aria-label="<?php _e('Aggiungi ai preferiti', 'your-hidden-trip'); ?>">🤍</button>
        <div class="yht-ico">⛰️</div>
        <div class="yht-t"><?php _e('Avventura', 'your-hidden-trip'); ?></div>
        <div class="yht-d"><?php _e('Sport outdoor, climbing, mountain bike', 'your-hidden-trip'); ?></div>
      </article>
      
      <article class="yht-card" tabindex="0" role="checkbox" aria-checked="false" data-group="attivita" data-value="shopping">
        <button class="yht-wishlist-btn" data-item-id="act-shopping" aria-label="<?php _e('Aggiungi ai preferiti', 'your-hidden-trip'); ?>">🤍</button>
        <div class="yht-ico">🛍️</div>
        <div class="yht-t"><?php _e('Shopping', 'your-hidden-trip'); ?></div>
        <div class="yht-d"><?php _e('Mercati locali, artigianato, prodotti tipici', 'your-hidden-trip'); ?></div>
      </article>
    </div>
    
    <div class="yht-error" id="yht-err3" role="alert" aria-live="polite">
      <?php _e('Seleziona almeno una attività.', 'your-hidden-trip'); ?>
    </div>
    
    <div class="yht-actions">
      <button class="yht-btn yht-btn-primary" data-next="3" disabled>
        <?php _e('Continua', 'your-hidden-trip'); ?>
        <span class="btn-arrow">→</span>
      </button>
      <button class="yht-btn yht-btn-secondary" data-prev="3">
        <span class="btn-arrow">←</span>
        <?php _e('Indietro', 'your-hidden-trip'); ?>
      </button>
    </div>
  </section>

  <!-- Step 4: Alloggio -->
  <section id="yht-step4" class="yht-stepview" role="tabpanel" aria-labelledby="yht-h2-4">
    <h2 id="yht-h2-4" class="yht-h2"><?php _e('Che tipo di alloggio preferisci?', 'your-hidden-trip'); ?></h2>
    <p class="yht-help"><?php _e('Scegli il tipo di struttura ricettiva per il tuo soggiorno.', 'your-hidden-trip'); ?></p>
    
    <div class="yht-grid" role="radiogroup" aria-labelledby="yht-h2-4">
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="alloggio" data-value="hotel">
        <button class="yht-share-btn" aria-label="<?php _e('Condividi', 'your-hidden-trip'); ?>">📤</button>
        <button class="yht-wishlist-btn" data-item-id="acc-hotel" aria-label="<?php _e('Aggiungi ai preferiti', 'your-hidden-trip'); ?>">🤍</button>
        <div class="yht-ico">🏨</div>
        <div class="yht-t"><?php _e('Hotel', 'your-hidden-trip'); ?></div>
        <div class="yht-d"><?php _e('Hotel 3-4 stelle con tutti i servizi', 'your-hidden-trip'); ?></div>
        <div class="yht-price">€<?php _e('80-150/notte', 'your-hidden-trip'); ?></div>
      </article>
      
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="alloggio" data-value="agriturismo">
        <button class="yht-share-btn" aria-label="<?php _e('Condividi', 'your-hidden-trip'); ?>">📤</button>
        <button class="yht-wishlist-btn" data-item-id="acc-agriturismo" aria-label="<?php _e('Aggiungi ai preferiti', 'your-hidden-trip'); ?>">🤍</button>
        <div class="yht-ico">🚜</div>
        <div class="yht-t"><?php _e('Agriturismo', 'your-hidden-trip'); ?></div>
        <div class="yht-d"><?php _e('Esperienza autentica in campagna', 'your-hidden-trip'); ?></div>
        <div class="yht-price">€<?php _e('60-120/notte', 'your-hidden-trip'); ?></div>
      </article>
      
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="alloggio" data-value="bb">
        <button class="yht-share-btn" aria-label="<?php _e('Condividi', 'your-hidden-trip'); ?>">📤</button>
        <button class="yht-wishlist-btn" data-item-id="acc-bb" aria-label="<?php _e('Aggiungi ai preferiti', 'your-hidden-trip'); ?>">🤍</button>
        <div class="yht-ico">🏠</div>
        <div class="yht-t"><?php _e('B&B', 'your-hidden-trip'); ?></div>
        <div class="yht-d"><?php _e('Accoglienza familiare, colazione inclusa', 'your-hidden-trip'); ?></div>
        <div class="yht-price">€<?php _e('50-90/notte', 'your-hidden-trip'); ?></div>
      </article>
      
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="alloggio" data-value="resort">
        <button class="yht-share-btn" aria-label="<?php _e('Condividi', 'your-hidden-trip'); ?>">📤</button>
        <button class="yht-wishlist-btn" data-item-id="acc-resort" aria-label="<?php _e('Aggiungi ai preferiti', 'your-hidden-trip'); ?>">🤍</button>
        <div class="yht-ico">🏖️</div>
        <div class="yht-t"><?php _e('Resort & Spa', 'your-hidden-trip'); ?></div>
        <div class="yht-d"><?php _e('Lusso e relax con centro benessere', 'your-hidden-trip'); ?></div>
        <div class="yht-price">€<?php _e('150-300/notte', 'your-hidden-trip'); ?></div>
      </article>
      
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="alloggio" data-value="villa">
        <button class="yht-share-btn" aria-label="<?php _e('Condividi', 'your-hidden-trip'); ?>">📤</button>
        <button class="yht-wishlist-btn" data-item-id="acc-villa" aria-label="<?php _e('Aggiungi ai preferiti', 'your-hidden-trip'); ?>">🤍</button>
        <div class="yht-ico">🏰</div>
        <div class="yht-t"><?php _e('Villa Storica', 'your-hidden-trip'); ?></div>
        <div class="yht-d"><?php _e('Dimora storica esclusiva', 'your-hidden-trip'); ?></div>
        <div class="yht-price">€<?php _e('200-500/notte', 'your-hidden-trip'); ?></div>
      </article>
      
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="alloggio" data-value="mix">
        <button class="yht-share-btn" aria-label="<?php _e('Condividi', 'your-hidden-trip'); ?>">📤</button>
        <button class="yht-wishlist-btn" data-item-id="acc-mix" aria-label="<?php _e('Aggiungi ai preferiti', 'your-hidden-trip'); ?>">🤍</button>
        <div class="yht-ico">🗺️</div>
        <div class="yht-t"><?php _e('Mix Personalizzato', 'your-hidden-trip'); ?></div>
        <div class="yht-d"><?php _e('Combinazione di diverse tipologie', 'your-hidden-trip'); ?></div>
        <div class="yht-price"><?php _e('Su misura', 'your-hidden-trip'); ?></div>
      </article>
    </div>
    
    <div class="yht-error" id="yht-err4" role="alert" aria-live="polite">
      <?php _e('Seleziona un tipo di alloggio.', 'your-hidden-trip'); ?>
    </div>
    
    <div class="yht-actions">
      <button class="yht-btn yht-btn-primary" data-next="4" disabled>
        <?php _e('Continua', 'your-hidden-trip'); ?>
        <span class="btn-arrow">→</span>
      </button>
      <button class="yht-btn yht-btn-secondary" data-prev="4">
        <span class="btn-arrow">←</span>
        <?php _e('Indietro', 'your-hidden-trip'); ?>
      </button>
    </div>
  </section>

  <!-- Step 5: Durata -->
  <section id="yht-step5" class="yht-stepview" role="tabpanel" aria-labelledby="yht-h2-5">
    <h2 id="yht-h2-5" class="yht-h2"><?php _e('Quanto durerà il tuo viaggio?', 'your-hidden-trip'); ?></h2>
    <p class="yht-help"><?php _e('Scegli la durata del soggiorno e specifica quando partire.', 'your-hidden-trip'); ?></p>
    
    <div class="yht-grid" role="radiogroup" aria-labelledby="yht-h2-5">
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="durata" data-value="1_notte">
        <div class="yht-ico">🌙</div>
        <div class="yht-t"><?php _e('1 Notte', 'your-hidden-trip'); ?></div>
        <div class="yht-d"><?php _e('Weekend breve e intensivo', 'your-hidden-trip'); ?></div>
        <div class="yht-price">€<?php _e('120-250', 'your-hidden-trip'); ?></div>
      </article>
      
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="durata" data-value="2_notti">
        <div class="yht-ico">🌙🌙</div>
        <div class="yht-t"><?php _e('2 Notti', 'your-hidden-trip'); ?></div>
        <div class="yht-d"><?php _e('Weekend rilassante', 'your-hidden-trip'); ?></div>
        <div class="yht-price">€<?php _e('220-450', 'your-hidden-trip'); ?></div>
      </article>
      
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="durata" data-value="3_notti">
        <div class="yht-ico">🌙🌙🌙</div>
        <div class="yht-t"><?php _e('3 Notti', 'your-hidden-trip'); ?></div>
        <div class="yht-d"><?php _e('Piccola vacanza', 'your-hidden-trip'); ?></div>
        <div class="yht-price">€<?php _e('320-650', 'your-hidden-trip'); ?></div>
      </article>
      
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="durata" data-value="4_notti">
        <div class="yht-ico">🌙🌙🌙🌙</div>
        <div class="yht-t"><?php _e('4 Notti', 'your-hidden-trip'); ?></div>
        <div class="yht-d"><?php _e('Viaggio approfondito', 'your-hidden-trip'); ?></div>
        <div class="yht-price">€<?php _e('420-850', 'your-hidden-trip'); ?></div>
      </article>
      
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="durata" data-value="5_notti">
        <div class="yht-ico">🌙🌙🌙🌙🌙</div>
        <div class="yht-t"><?php _e('5 Notti', 'your-hidden-trip'); ?></div>
        <div class="yht-d"><?php _e('Settimana di relax', 'your-hidden-trip'); ?></div>
        <div class="yht-price">€<?php _e('520-1050', 'your-hidden-trip'); ?></div>
      </article>
      
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="durata" data-value="personalizzata">
        <div class="yht-ico">📅</div>
        <div class="yht-t"><?php _e('Personalizzata', 'your-hidden-trip'); ?></div>
        <div class="yht-d"><?php _e('Durata su misura', 'your-hidden-trip'); ?></div>
        <div class="yht-price"><?php _e('Su richiesta', 'your-hidden-trip'); ?></div>
      </article>
    </div>
    
    <div class="yht-row yht-mt" style="margin-top: 24px;">
      <div class="yht-col">
        <label for="yht-startdate" class="yht-field-label"><?php _e('Data di partenza', 'your-hidden-trip'); ?></label>
        <input id="yht-startdate" class="yht-input" type="date" style="width: 100%; padding: 12px; border: 2px solid var(--line); border-radius: 10px; font-size: 1rem;" />
      </div>
      <div class="yht-col">
        <label for="yht-pax" class="yht-field-label"><?php _e('Numero persone', 'your-hidden-trip'); ?></label>
        <input id="yht-pax" class="yht-input" type="number" min="1" value="2" style="width: 100%; padding: 12px; border: 2px solid var(--line); border-radius: 10px; font-size: 1rem;" />
      </div>
    </div>
    
    <div class="yht-error" id="yht-err5" role="alert" aria-live="polite">
      <?php _e('Seleziona la durata del viaggio e la data di partenza.', 'your-hidden-trip'); ?>
    </div>
    
    <div class="yht-actions">
      <button class="yht-btn yht-btn-primary" data-next="5" disabled>
        <?php _e('Vai al riepilogo', 'your-hidden-trip'); ?>
        <span class="btn-arrow">→</span>
      </button>
      <button class="yht-btn yht-btn-secondary" data-prev="5">
        <span class="btn-arrow">←</span>
        <?php _e('Indietro', 'your-hidden-trip'); ?>
      </button>
    </div>
  </section>

  <!-- Step 6: Riepilogo -->
  <section id="yht-step6" class="yht-stepview" role="tabpanel" aria-labelledby="yht-h2-6">
    <h2 id="yht-h2-6" class="yht-h2"><?php _e('🎯 Il tuo viaggio perfetto', 'your-hidden-trip'); ?></h2>
    <p class="yht-help"><?php _e('Ecco il riepilogo del tuo viaggio personalizzato in Tuscia e Umbria.', 'your-hidden-trip'); ?></p>
    
    <div class="yht-summary" id="yht-summary" style="background: var(--card); border: 2px solid var(--primary); border-radius: 16px; padding: 24px; margin: 20px 0;">
      <div class="summary-content">
        <p><?php _e('Seleziona le tue preferenze per vedere il riepilogo personalizzato...', 'your-hidden-trip'); ?></p>
      </div>
    </div>
    
    <h3 style="margin: 24px 0 16px; color: var(--primary); font-size: 1.3rem;">
      🌟 <?php _e('Proposte di tour personalizzate', 'your-hidden-trip'); ?>
    </h3>
    <div id="yht-tours" style="margin-bottom: 24px;">
      <div style="text-align: center; padding: 40px; color: var(--muted); font-style: italic;">
        <?php _e('Completa i passaggi precedenti per vedere le proposte di tour personalizzate...', 'your-hidden-trip'); ?>
      </div>
    </div>
    
    <div class="yht-actions">
      <button class="yht-btn yht-btn-primary" id="yht-book-now" style="background: var(--gradient-primary); font-size: 1.1rem;" disabled>
        🎉 <?php _e('Prenota ora', 'your-hidden-trip'); ?>
      </button>
      <button class="yht-btn yht-btn-secondary" data-prev="6">
        <span class="btn-arrow">←</span>
        <?php _e('Modifica preferenze', 'your-hidden-trip'); ?>
      </button>
      <button class="yht-btn yht-btn-ghost" id="yht-export">
        📤 <?php _e('Esporta PDF', 'your-hidden-trip'); ?>
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

/* Step View Enhancements - Fix Overlapping Issues */
.yht-stepview {
  display: none !important;
  opacity: 0;
  transform: translateY(20px);
  transition: opacity 0.4s ease, transform 0.4s ease;
  position: relative;
  z-index: 1;
}

.yht-stepview[data-show="true"] {
  display: block !important;
  opacity: 1;
  transform: translateY(0);
  animation: slideInUp 0.6s ease;
}

/* Form Field Styles */
.yht-field-label {
  display: block;
  margin-bottom: 6px;
  font-weight: 600;
  color: var(--text);
  font-size: 0.9rem;
}

.yht-input {
  width: 100%;
  padding: 12px;
  border: 2px solid var(--line);
  border-radius: 10px;
  font-size: 1rem;
  background: var(--card);
  color: var(--text);
  transition: var(--transition);
}

.yht-input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.yht-row {
  display: flex;
  gap: 16px;
  align-items: end;
  flex-wrap: wrap;
}

.yht-col {
  flex: 1;
  min-width: 200px;
}

/* Enhanced Grid for Better Responsiveness */
.yht-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 16px;
  margin: 20px 0;
}

@media (max-width: 768px) {
  .yht-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  
  .yht-row {
    flex-direction: column;
    gap: 12px;
  }
  
  .yht-col {
    min-width: auto;
  }
}

/* Card Selection States */
.yht-card[data-selected="true"] {
  border-color: var(--primary);
  background: rgba(16, 185, 129, 0.05);
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
}

.yht-card[data-selected="true"] .yht-ico {
  transform: scale(1.1);
}

/* Button States */
.yht-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none !important;
}

.yht-btn:disabled:hover {
  transform: none;
  background: inherit;
}

/* Error Messages */
.yht-error[data-show="true"] {
  display: block;
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.3);
  border-radius: 8px;
  padding: 12px;
  margin: 12px 0;
  animation: slideInDown 0.3s ease;
}

@keyframes slideInDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
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
  console.log('Trip Builder Enhanced Template Loaded');
  
  // Wait for YHTEnhancer to be available
  if (typeof window.yhtEnhancer !== 'undefined') {
    console.log('YHTEnhancer already available');
  } else {
    // Wait for enhancer to be loaded
    const checkEnhancer = setInterval(() => {
      if (typeof window.yhtEnhancer !== 'undefined') {
        clearInterval(checkEnhancer);
        console.log('YHTEnhancer loaded and ready');
      }
    }, 100);
  }
  
  // Accessibility improvements
  const cards = document.querySelectorAll('.yht-card[role="radio"], .yht-card[role="checkbox"]');
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
  
  // Prevent wishlist and share buttons from triggering card selection
  document.querySelectorAll('.yht-wishlist-btn, .yht-share-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.stopPropagation();
    });
  });
});
</script>