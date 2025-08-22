<div id="yht-builder" class="yht-wrap" aria-live="polite">
  <style>
    :root{
      --bg:#f8fafc; --text:#111827; --muted:#6b7280; --card:#ffffff; --line:#e5e7eb;
      --primary:#10b981; --primary-600:#059669; --accent:#38bdf8; --danger:#ef4444; --warning:#f59e0b;
      --radius:14px; --shadow:0 10px 25px rgba(0,0,0,.08);
    }
    .yht-wrap{max-width:980px;margin:0 auto;padding:20px;background:var(--bg);color:var(--text);font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial;border-radius:var(--radius);box-shadow:var(--shadow);position:relative;overflow:hidden}
    .yht-header{display:flex;align-items:center;gap:12px;margin-bottom:16px}
    .yht-badge{font-size:.78rem;border:1px solid var(--line);padding:4px 10px;border-radius:999px;color:var(--muted)}
    .yht-title{font-size:1.4rem;font-weight:700}
    .yht-progressbar{height:8px;background:var(--line);border-radius:999px;margin:14px 0 22px;overflow:hidden}
    .yht-progressbar>i{display:block;height:100%;width:0;background:linear-gradient(90deg,var(--primary),#34d399);transition:width .4s ease}
    .yht-steps{display:flex;align-items:center;gap:10px;margin-bottom:20px}
    .yht-step{width:38px;height:38px;border-radius:999px;background:#dde2e7;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;cursor:not-allowed;position:relative;user-select:none}
    .yht-step[data-active="true"]{background:var(--primary);cursor:pointer}
    .yht-step[data-done="true"]{background:#4b5563;cursor:pointer}
    .yht-step::after{content:attr(data-label);position:absolute;top:-22px;white-space:nowrap;font-size:.78rem;color:var(--muted)}
    .yht-line{flex:1;height:4px;background:var(--line);border-radius:4px;position:relative;overflow:hidden}
    .yht-line>i{position:absolute;inset:0;width:0;background:var(--primary);transition:width .4s ease}
    .yht-stepview{display:none;opacity:0;transform:translateY(8px);transition:opacity .25s,transform .25s}
    .yht-stepview[data-show="true"]{display:block;opacity:1;transform:none}
    .yht-h2{font-size:1.25rem;margin:0 0 8px}
    .yht-help{color:var(--muted);font-size:.92rem;margin-bottom:14px}
    .yht-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
    @media (max-width:720px){ .yht-grid{grid-template-columns:1fr 1fr} }
    @media (max-width:500px){ .yht-grid{grid-template-columns:1fr} }
    .yht-card{background:var(--card);border:2px solid var(--line);border-radius:12px;padding:16px;cursor:pointer;position:relative;transition:.2s;outline:none}
    .yht-card:hover{transform:translateY(-2px);border-color:var(--primary);box-shadow:var(--shadow)}
    .yht-card[data-selected="true"]{border-color:var(--primary);box-shadow:var(--shadow)}
    .yht-ico{font-size:1.6rem;line-height:1;margin-bottom:8px}
    .yht-t{font-weight:700}
    .yht-d{color:var(--muted);font-size:.9rem;margin-top:2px}
    .yht-actions{display:flex;gap:10px;margin-top:14px;flex-wrap:wrap}
    .yht-btn{appearance:none;border:0;border-radius:10px;padding:12px 18px;font-weight:700;background:var(--primary);color:#fff;cursor:pointer}
    .yht-btn:hover{background:var(--primary-600)}
    .yht-btn.secondary{background:#e2e8f0;color:#111827}
    .yht-btn.ghost{background:transparent;border:2px solid var(--line);color:#111827}
    .yht-error{display:none;color:var(--danger);font-size:.95rem;margin:8px 0}
    .yht-error[data-show="true"]{display:block}
    .yht-summary{margin:16px 0;padding:16px;background:var(--card);border:1px solid var(--line);border-radius:12px}
    .yht-row{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
    .yht-col{flex:1 1 auto}
    .yht-input{width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:10px;background:#fff;color:#111827}
    .yht-small{font-size:.86rem;color:var(--muted)}
    .yht-mt{margin-top:14px}
    .yht-price{font-weight:bold;color:var(--primary);margin-top:4px;font-size:1.1rem}
    .yht-card[data-selected="true"] .yht-price{color:#fff;background:var(--primary);padding:2px 6px;border-radius:6px;font-size:0.9rem}
    
    /* Enhanced Booking Styles */
    .yht-booking-header{margin-bottom:24px}
    .yht-trust-bar{display:flex;justify-content:center;gap:20px;margin:20px 0;padding:16px;background:linear-gradient(135deg,#f0f9ff,#e0f2fe);border-radius:12px;border:1px solid #bae6fd}
    .trust-item{display:flex;align-items:center;gap:6px;font-size:0.9rem;color:#0369a1}
    .trust-icon{font-size:1.1rem}
    .trust-text{font-weight:600}
    
    .yht-social-proof{background:#f8f9fa;border-radius:12px;padding:20px;margin:16px 0;border:1px solid #e9ecef}
    .social-stats{display:flex;justify-content:center;gap:30px;margin-bottom:16px}
    .stat-item{text-align:center}
    .stat-number{display:block;font-size:1.3rem;font-weight:700;color:var(--primary)}
    .stat-label{font-size:0.85rem;color:var(--muted);margin-top:2px}
    
    .recent-bookings{margin:12px 0;text-align:center}
    .recent-booking{display:inline-flex;align-items:center;gap:8px;background:#fff;padding:8px 12px;border-radius:20px;font-size:0.9rem;color:#374151;border:1px solid #d1d5db}
    .booking-time{color:var(--muted);font-size:0.8rem}
    
    .urgency-message{display:flex;align-items:center;justify-content:center;gap:8px;background:#fef3c7;color:#92400e;padding:12px;border-radius:8px;margin-top:12px;border:1px solid #fcd34d}
    .urgency-icon{font-size:1.1rem}
    .urgency-text{font-size:0.95rem;font-weight:500}
    
    .enhanced-form{background:#fff;border-radius:16px;padding:24px;box-shadow:0 4px 20px rgba(0,0,0,0.08);border:1px solid #e5e7eb}
    .form-section{margin-bottom:24px}
    .section-title{font-size:1.1rem;font-weight:600;color:#111827;margin-bottom:12px;display:flex;align-items:center;gap:8px}
    .section-icon{font-size:1.2rem}
    
    .security-badge{display:inline-flex;align-items:center;gap:6px;background:#dcfce7;color:#166534;padding:6px 12px;border-radius:20px;font-size:0.85rem;font-weight:500;border:1px solid #bbf7d0}
    .guarantee-text{background:#f0fdf4;color:#15803d;padding:12px;border-radius:8px;font-size:0.9rem;text-align:center;margin:12px 0;border:1px solid #bbf7d0}
    
    .enhanced-button{background:linear-gradient(135deg,var(--primary),#34d399);border:none;color:#fff;padding:16px 32px;border-radius:12px;font-size:1.1rem;font-weight:700;cursor:pointer;position:relative;overflow:hidden;box-shadow:0 4px 15px rgba(16,185,129,0.4);transition:all 0.3s ease}
    .enhanced-button:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(16,185,129,0.6)}
    .enhanced-button:disabled{opacity:0.7;cursor:not-allowed}
    
    .testimonial{background:#fff;border-radius:12px;padding:16px;margin:12px 0;border-left:4px solid var(--primary);box-shadow:0 2px 8px rgba(0,0,0,0.05)}
    .testimonial-text{font-style:italic;color:#374151;margin-bottom:8px}
    .testimonial-author{font-weight:600;color:#111827;font-size:0.9rem}
    .testimonial-rating{color:#fbbf24;font-size:0.9rem}
    
    @media (max-width: 768px){
      .yht-trust-bar{flex-wrap:wrap;gap:12px}
      .social-stats{flex-wrap:wrap;gap:16px}
      .recent-booking{font-size:0.85rem}
    }
    
    /* Enhanced Package Cards */
    .enhanced-package{position:relative;overflow:visible}
    .package-badge{position:absolute;top:-8px;right:-8px;background:#ff6b35;color:#fff;padding:4px 8px;border-radius:12px;font-size:0.75rem;font-weight:600;z-index:1}
    .package-badge.premium{background:#8b5cf6}
    .package-badge.luxury{background:#f59e0b}
    .package-features{margin:12px 0;display:flex;flex-direction:column;gap:4px}
    .package-features span{font-size:0.85rem;color:#059669;font-weight:500}
    .enhanced-input{border:2px solid #e5e7eb;transition:border-color 0.2s ease}
    .enhanced-input:focus{border-color:var(--primary);outline:none}
    .privacy-notice{background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:12px;margin-top:16px}
    .privacy-notice p{margin:0;font-size:0.9rem;color:#374151;text-align:center}
    
    /* Enhanced Pricing Section */
    .enhanced-pricing{background:linear-gradient(135deg,#f0f9ff,#e0f2fe);border:2px solid var(--primary)}
    .pricing-header{display:flex;justify-content:between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:8px}
    .pricing-header h3{margin:0;color:var(--primary);font-size:1.2rem}
    .savings-badge{background:#fef3c7;color:#92400e;padding:4px 12px;border-radius:20px;font-size:0.8rem;font-weight:600}
    .total-price-section{text-align:center;margin:16px 0}
    .payment-info{display:block;font-size:0.9rem;color:#059669;margin-top:8px;font-weight:500}
    .value-props{margin-top:16px;display:grid;gap:6px}
    .value-item{font-size:0.9rem;color:#374151;display:flex;align-items:center;gap:8px}
    
    /* Enhanced Actions */
    .enhanced-actions{display:flex;flex-direction:column;gap:12px;align-items:center;margin-top:24px}
    .enhanced-button{position:relative;display:flex;flex-direction:column;align-items:center;min-width:250px}
    .enhanced-button.primary{background:linear-gradient(135deg,#dc2626,#ef4444);animation:pulse-glow 2s infinite}
    .button-subtext{font-size:0.8rem;font-weight:400;margin-top:4px;opacity:0.9}
    
    @keyframes pulse-glow {
      0%, 100% { box-shadow: 0 4px 15px rgba(220,38,38,0.4); }
      50% { box-shadow: 0 6px 20px rgba(220,38,38,0.6); }
    }
    
    /* Flexibility Options */
    .flexibility-options{display:grid;gap:12px;margin-top:12px}
    .flex-option{background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:12px}
    .flex-checkbox{display:flex;align-items:flex-start;gap:12px;cursor:pointer;position:relative}
    .checkmark{width:20px;height:20px;border:2px solid #d1d5db;border-radius:4px;display:flex;align-items:center;justify-content:center;transition:all 0.2s ease}
    .flex-checkbox input[type="checkbox"]{display:none}
    .flex-checkbox input[type="checkbox"]:checked + .checkmark{background:var(--primary);border-color:var(--primary)}
    .flex-checkbox input[type="checkbox"]:checked + .checkmark::after{content:"✓";color:#fff;font-size:14px;font-weight:bold}
    .flex-text{flex:1}
    .flex-text strong{display:block;color:#111827;margin-bottom:4px}
    .flex-benefit{display:block;font-size:0.85rem;color:var(--primary);font-weight:600}
    .flex-checkbox:hover .checkmark{border-color:var(--primary)}
  </style>

  <div class="yht-header">
    <span class="yht-badge">Your Hidden Trip</span>
    <div class="yht-title"><?php _e('Crea il tuo viaggio su misura', 'your-hidden-trip'); ?></div>
  </div>

  <div class="yht-progressbar" aria-hidden="true"><i id="yht-progress"></i></div>

  <div class="yht-steps" role="navigation" aria-label="<?php esc_attr_e('Step di compilazione', 'your-hidden-trip'); ?>">
    <div id="yht-s1" class="yht-step" data-label="<?php esc_attr_e('Viaggiatore', 'your-hidden-trip'); ?>" data-active="true" tabindex="0">1</div>
    <div class="yht-line"><i id="yht-l1"></i></div>
    <div id="yht-s2" class="yht-step" data-label="<?php esc_attr_e('Esperienze', 'your-hidden-trip'); ?>" tabindex="-1">2</div>
    <div class="yht-line"><i id="yht-l2"></i></div>
    <div id="yht-s3" class="yht-step" data-label="<?php esc_attr_e('Luogo', 'your-hidden-trip'); ?>" tabindex="-1">3</div>
    <div class="yht-line"><i id="yht-l3"></i></div>
    <div id="yht-s4" class="yht-step" data-label="<?php esc_attr_e('Trasporto', 'your-hidden-trip'); ?>" tabindex="-1">4</div>
    <div class="yht-line"><i id="yht-l4"></i></div>
    <div id="yht-s5" class="yht-step" data-label="<?php esc_attr_e('Durata', 'your-hidden-trip'); ?>" tabindex="-1">5</div>
    <div class="yht-line"><i id="yht-l5"></i></div>
    <div id="yht-s6" class="yht-step" data-label="<?php esc_attr_e('Riepilogo', 'your-hidden-trip'); ?>" tabindex="-1">6</div>
    <div class="yht-line"><i id="yht-l6"></i></div>
    <div id="yht-s7" class="yht-step" data-label="<?php esc_attr_e('Prenota', 'your-hidden-trip'); ?>" tabindex="-1">7</div>
  </div>

  <!-- STEP 1: Traveler Type -->
  <section id="yht-step1" class="yht-stepview" data-show="true" role="region" aria-labelledby="yht-h2-1">
    <h2 id="yht-h2-1" class="yht-h2"><?php _e('Che tipo di viaggiatore sei?', 'your-hidden-trip'); ?></h2>
    <p class="yht-help"><?php _e('Scegli lo stile: incide su tappe al giorno e budget.', 'your-hidden-trip'); ?></p>
    <div class="yht-grid" role="radiogroup" aria-label="<?php esc_attr_e('Tipo viaggiatore', 'your-hidden-trip'); ?>">
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="travelerType" data-value="active">
        <div class="yht-ico">⚡</div><div class="yht-t"><?php _e('Ami fare tante cose', 'your-hidden-trip'); ?></div><div class="yht-d"><?php _e('Ritmo alto (3–4 tappe/giorno)', 'your-hidden-trip'); ?></div>
      </article>
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="travelerType" data-value="relaxed">
        <div class="yht-ico">☕</div><div class="yht-t"><?php _e('Giornata rilassata', 'your-hidden-trip'); ?></div><div class="yht-d"><?php _e('Ritmo lento (1–2 tappe/giorno)', 'your-hidden-trip'); ?></div>
      </article>
    </div>
    <p id="yht-err1" class="yht-error" aria-live="polite"><?php _e('Seleziona un tipo di viaggiatore.', 'your-hidden-trip'); ?></p>
    <div class="yht-actions">
      <button class="yht-btn" data-next="1"><?php _e('Prosegui', 'your-hidden-trip'); ?></button>
      <button class="yht-btn ghost" data-reset="1"><?php _e('Reset', 'your-hidden-trip'); ?></button>
    </div>
  </section>

  <!-- STEP 2: Experiences -->
  <section id="yht-step2" class="yht-stepview" role="region" aria-labelledby="yht-h2-2">
    <h2 id="yht-h2-2" class="yht-h2"><?php _e('Che tipo di esperienza cerchi?', 'your-hidden-trip'); ?></h2>
    <p class="yht-help"><?php _e('Puoi selezionare più opzioni.', 'your-hidden-trip'); ?></p>
    <div class="yht-grid" role="group" aria-label="<?php esc_attr_e('Esperienze', 'your-hidden-trip'); ?>">
      <article class="yht-card" tabindex="0" role="checkbox" aria-checked="false" data-group="esperienze" data-value="trekking"><div class="yht-ico">🥾</div><div class="yht-t"><?php _e('Trekking', 'your-hidden-trip'); ?></div><div class="yht-d"><?php _e('Sentieri e natura', 'your-hidden-trip'); ?></div></article>
      <article class="yht-card" tabindex="0" role="checkbox" aria-checked="false" data-group="esperienze" data-value="passeggiata"><div class="yht-ico">🚶</div><div class="yht-t"><?php _e('Passeggiata', 'your-hidden-trip'); ?></div><div class="yht-d"><?php _e('Percorsi facili', 'your-hidden-trip'); ?></div></article>
      <article class="yht-card" tabindex="0" role="checkbox" aria-checked="false" data-group="esperienze" data-value="cultura"><div class="yht-ico">🏛️</div><div class="yht-t"><?php _e('Cultura', 'your-hidden-trip'); ?></div><div class="yht-d"><?php _e('Borghi, musei, siti', 'your-hidden-trip'); ?></div></article>
      <article class="yht-card" tabindex="0" role="checkbox" aria-checked="false" data-group="esperienze" data-value="benessere"><div class="yht-ico">🧖</div><div class="yht-t"><?php _e('Benessere', 'your-hidden-trip'); ?></div><div class="yht-d"><?php _e('Terme e spa', 'your-hidden-trip'); ?></div></article>
      <article class="yht-card" tabindex="0" role="checkbox" aria-checked="false" data-group="esperienze" data-value="enogastronomia"><div class="yht-ico">🍷</div><div class="yht-t"><?php _e('Enogastronomia', 'your-hidden-trip'); ?></div><div class="yht-d"><?php _e('Cantine e sapori', 'your-hidden-trip'); ?></div></article>
    </div>
    <p id="yht-err2" class="yht-error" aria-live="polite"><?php _e('Seleziona almeno un\'esperienza.', 'your-hidden-trip'); ?></p>
    <div class="yht-actions">
      <button class="yht-btn" data-next="2"><?php _e('Prosegui', 'your-hidden-trip'); ?></button>
      <button class="yht-btn ghost" data-reset="2"><?php _e('Reset', 'your-hidden-trip'); ?></button>
    </div>
  </section>

  <!-- STEP 3: Areas -->
  <section id="yht-step3" class="yht-stepview" role="region" aria-labelledby="yht-h2-3">
    <h2 id="yht-h2-3" class="yht-h2"><?php _e('Dove preferisci?', 'your-hidden-trip'); ?></h2>
    <p class="yht-help"><?php _e('Seleziona aree d\'interesse.', 'your-hidden-trip'); ?></p>
    <div class="yht-grid" role="group" aria-label="<?php esc_attr_e('Luoghi', 'your-hidden-trip'); ?>">
      <article class="yht-card" tabindex="0" role="checkbox" aria-checked="false" data-group="luogo" data-value="natura"><div class="yht-ico">🌳</div><div class="yht-t"><?php _e('Natura', 'your-hidden-trip'); ?></div></article>
      <article class="yht-card" tabindex="0" role="checkbox" aria-checked="false" data-group="luogo" data-value="citta"><div class="yht-ico">🏙️</div><div class="yht-t"><?php _e('Città', 'your-hidden-trip'); ?></div></article>
      <article class="yht-card" tabindex="0" role="checkbox" aria-checked="false" data-group="luogo" data-value="collina"><div class="yht-ico">⛰️</div><div class="yht-t"><?php _e('Collina', 'your-hidden-trip'); ?></div></article>
      <article class="yht-card" tabindex="0" role="checkbox" aria-checked="false" data-group="luogo" data-value="lago"><div class="yht-ico">🌊</div><div class="yht-t"><?php _e('Lago', 'your-hidden-trip'); ?></div></article>
      <article class="yht-card" tabindex="0" role="checkbox" aria-checked="false" data-group="luogo" data-value="centro_storico"><div class="yht-ico">🏰</div><div class="yht-t"><?php _e('Centro storico', 'your-hidden-trip'); ?></div></article>
    </div>
    <p id="yht-err3" class="yht-error" aria-live="polite"><?php _e('Seleziona almeno un luogo.', 'your-hidden-trip'); ?></p>
    <div class="yht-actions">
      <button class="yht-btn" data-next="3"><?php _e('Prosegui', 'your-hidden-trip'); ?></button>
      <button class="yht-btn ghost" data-reset="3"><?php _e('Reset', 'your-hidden-trip'); ?></button>
    </div>
  </section>

  <!-- STEP 4: Transportation (NEW) -->
  <section id="yht-step4" class="yht-stepview" role="region" aria-labelledby="yht-h2-4">
    <h2 id="yht-h2-4" class="yht-h2"><?php _e('Come preferisci muoverti?', 'your-hidden-trip'); ?></h2>
    <p class="yht-help"><?php _e('Seleziona la tua preferenza di trasporto per ottimizzare i suggerimenti.', 'your-hidden-trip'); ?></p>
    <div class="yht-grid" role="radiogroup" aria-label="<?php esc_attr_e('Trasporto', 'your-hidden-trip'); ?>">
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="trasporto" data-value="auto_propria">
        <div class="yht-ico">🚗</div><div class="yht-t"><?php _e('Auto propria', 'your-hidden-trip'); ?></div><div class="yht-d"><?php _e('Hai la tua auto', 'your-hidden-trip'); ?></div>
      </article>
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="trasporto" data-value="noleggio_auto">
        <div class="yht-ico">🚙</div><div class="yht-t"><?php _e('Noleggio auto', 'your-hidden-trip'); ?></div><div class="yht-d"><?php _e('Serve auto a noleggio', 'your-hidden-trip'); ?></div>
      </article>
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="trasporto" data-value="autista">
        <div class="yht-ico">🚖</div><div class="yht-t"><?php _e('Con autista', 'your-hidden-trip'); ?></div><div class="yht-d"><?php _e('Prefer essere guidato', 'your-hidden-trip'); ?></div>
      </article>
    </div>
    <p id="yht-err4" class="yht-error" aria-live="polite"><?php _e('Seleziona una preferenza di trasporto.', 'your-hidden-trip'); ?></p>
    <div class="yht-actions">
      <button class="yht-btn" data-next="4"><?php _e('Prosegui', 'your-hidden-trip'); ?></button>
      <button class="yht-btn ghost" data-reset="4"><?php _e('Reset', 'your-hidden-trip'); ?></button>
    </div>
  </section>

  <!-- STEP 5: Duration & Date -->
  <section id="yht-step5" class="yht-stepview" role="region" aria-labelledby="yht-h2-5">
    <h2 id="yht-h2-5" class="yht-h2"><?php _e('Quanto tempo hai?', 'your-hidden-trip'); ?></h2>
    <p class="yht-help"><?php _e('Scegli durata e data di partenza.', 'your-hidden-trip'); ?></p>
    <div class="yht-grid" role="radiogroup" aria-label="<?php esc_attr_e('Durata', 'your-hidden-trip'); ?>">
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="durata" data-value="1_notte"><div class="yht-ico">🌙</div><div class="yht-t"><?php _e('1 notte', 'your-hidden-trip'); ?></div></article>
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="durata" data-value="2_notti"><div class="yht-ico">🌙🌙</div><div class="yht-t"><?php _e('2 notti', 'your-hidden-trip'); ?></div></article>
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="durata" data-value="3_notti"><div class="yht-ico">🌙🌙🌙</div><div class="yht-t"><?php _e('3 notti', 'your-hidden-trip'); ?></div></article>
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="durata" data-value="4_notti"><div class="yht-ico">🌙🌙🌙🌙</div><div class="yht-t"><?php _e('4 notti', 'your-hidden-trip'); ?></div></article>
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="durata" data-value="5+_notti"><div class="yht-ico">🌙+</div><div class="yht-t"><?php _e('5+ notti', 'your-hidden-trip'); ?></div></article>
    </div>
    <div class="yht-row yht-mt">
      <div class="yht-col">
        <label for="yht-startdate" class="yht-small"><?php _e('Data di partenza', 'your-hidden-trip'); ?></label>
        <input id="yht-startdate" class="yht-input" type="date" />
      </div>
      <div class="yht-col">
        <label for="yht-pax" class="yht-small"><?php _e('Persone', 'your-hidden-trip'); ?></label>
        <input id="yht-pax" class="yht-input" type="number" min="1" value="2" />
      </div>
    </div>
    <p id="yht-err5" class="yht-error" aria-live="polite"><?php _e('Seleziona una durata e la data di partenza.', 'your-hidden-trip'); ?></p>
    <div class="yht-actions">
      <button class="yht-btn" data-next="5"><?php _e('Vai al riepilogo', 'your-hidden-trip'); ?></button>
      <button class="yht-btn ghost" data-reset="5"><?php _e('Reset', 'your-hidden-trip'); ?></button>
    </div>
  </section>

  <!-- STEP 6: Summary -->
  <section id="yht-step6" class="yht-stepview" role="region" aria-labelledby="yht-h2-6">
    <h2 id="yht-h2-6" class="yht-h2">Riepilogo</h2>
    <div class="yht-summary" id="yht-summary"></div>
    <h3 style="margin:12px 0 6px">Proposte di tour</h3>
    <div id="yht-tours"></div>
    <div class="yht-actions">
      <button class="yht-btn secondary" id="yht-print">Stampa</button>
      <button class="yht-btn ghost" id="yht-export">Esporta JSON</button>
      <button class="yht-btn ghost" id="yht-pdf">Scarica PDF</button>
    </div>
  </section>

  <!-- STEP 7: Booking -->
  <section id="yht-step7" class="yht-stepview" role="region" aria-labelledby="yht-h2-7">
    <div class="yht-booking-header">
      <h2 id="yht-h2-7" class="yht-h2">🏆 Completa la tua prenotazione</h2>
      <p class="yht-help">Finalizza il tuo pacchetto all-inclusive con tutti i servizi inclusi.</p>
      
      <!-- Trust & Security Section -->
      <div class="yht-trust-bar">
        <div class="trust-item">
          <span class="trust-icon">🔒</span>
          <span class="trust-text">SSL Sicuro</span>
        </div>
        <div class="trust-item">
          <span class="trust-icon">✅</span>
          <span class="trust-text">Garanzia 100%</span>
        </div>
        <div class="trust-item">
          <span class="trust-icon">⚡</span>
          <span class="trust-text">Conferma Immediata</span>
        </div>
        <div class="trust-item">
          <span class="trust-icon">📞</span>
          <span class="trust-text">Support 24/7</span>
        </div>
      </div>

      <!-- Social Proof Section -->
      <div class="yht-social-proof">
        <div class="social-stats">
          <div class="stat-item">
            <span class="stat-number" id="total-bookings">1,247</span>
            <span class="stat-label">Viaggi organizzati</span>
          </div>
          <div class="stat-item">
            <span class="stat-number">4.9★</span>
            <span class="stat-label">Valutazione media</span>
          </div>
          <div class="stat-item">
            <span class="stat-number">98%</span>
            <span class="stat-label">Clienti soddisfatti</span>
          </div>
        </div>
        
        <div class="recent-bookings">
          <div class="recent-booking">
            <span class="booking-text"><?php printf(__('🟢 Marco da Roma ha appena prenotato un tour %s', 'your-hidden-trip'), __('Premium', 'your-hidden-trip')); ?></span>
            <span class="booking-time"><?php printf(__('%s min fa', 'your-hidden-trip'), '2'); ?></span>
          </div>
        </div>
        
        <div class="urgency-message">
          <span class="urgency-icon">⏰</span>
          <span class="urgency-text">Solo <strong>3 posti</strong> rimasti per le tue date!</span>
        </div>
      </div>
    </div>
    
    <div id="yht-booking-form" style="display:none;" class="enhanced-form">
      <div class="yht-grid">
        <!-- Package Selection with Enhanced Design -->
        <div style="grid-column:1/3;">
          <div class="section-title">
            <span class="section-icon">🎁</span>
            <span>Seleziona il tuo pacchetto esclusivo</span>
          </div>
          <div class="yht-grid" role="radiogroup" aria-label="Tipo pacchetto">
            <article class="yht-card enhanced-package" tabindex="0" role="radio" aria-checked="false" data-group="packageType" data-value="standard">
              <div class="package-badge"><?php _e('Più popolare', 'your-hidden-trip'); ?></div>
              <div class="yht-ico">⭐</div>
              <div class="yht-t"><?php _e('Standard', 'your-hidden-trip'); ?></div>
              <div class="yht-d"><?php _e('Comfort essenziale', 'your-hidden-trip'); ?></div>
              <div class="package-features">
                <span><?php _e('✓ Alloggio 3★', 'your-hidden-trip'); ?></span>
                <span><?php _e('✓ Colazione inclusa', 'your-hidden-trip'); ?></span>
              </div>
              <div class="yht-price" id="price-standard">€0</div>
            </article>
            <article class="yht-card enhanced-package" tabindex="0" role="radio" aria-checked="false" data-group="packageType" data-value="premium">
              <div class="package-badge premium"><?php _e('Consigliato', 'your-hidden-trip'); ?></div>
              <div class="yht-ico">⭐⭐</div>
              <div class="yht-t"><?php _e('Premium', 'your-hidden-trip'); ?></div>
              <div class="yht-d"><?php _e('Esperienza superiore', 'your-hidden-trip'); ?></div>
              <div class="package-features">
                <span><?php _e('✓ Alloggio 4★', 'your-hidden-trip'); ?></span>
                <span><?php _e('✓ Mezza pensione', 'your-hidden-trip'); ?></span>
                <span><?php _e('✓ Guide esperte', 'your-hidden-trip'); ?></span>
              </div>
              <div class="yht-price" id="price-premium">€0</div>
            </article>
            <article class="yht-card enhanced-package" tabindex="0" role="radio" aria-checked="false" data-group="packageType" data-value="luxury">
              <div class="package-badge luxury"><?php _e('Esclusivo', 'your-hidden-trip'); ?></div>
              <div class="yht-ico">⭐⭐⭐</div>
              <div class="yht-t"><?php _e('Luxury', 'your-hidden-trip'); ?></div>
              <div class="yht-d"><?php _e('Massimo lusso', 'your-hidden-trip'); ?></div>
              <div class="package-features">
                <span><?php _e('✓ Alloggio 5★', 'your-hidden-trip'); ?></span>
                <span><?php _e('✓ Pensione completa', 'your-hidden-trip'); ?></span>
                <span><?php _e('✓ Servizio concierge', 'your-hidden-trip'); ?></span>
                <span><?php _e('✓ Transfer privato', 'your-hidden-trip'); ?></span>
              </div>
              <div class="yht-price" id="price-luxury">€0</div>
            </article>
          </div>
        </div>

        <!-- Booking Flexibility Options -->
        <div style="grid-column:1/3;" class="form-section">
          <div class="section-title">
            <span class="section-icon">⚙️</span>
            <span>Personalizza la tua esperienza</span>
          </div>
          <div class="flexibility-options">
            <div class="flex-option">
              <label class="flex-checkbox">
                <input type="checkbox" id="flexible-dates" />
                <span class="checkmark"></span>
                <span class="flex-text">
                  <strong>Date flessibili</strong> - Posso partire ±3 giorni
                  <span class="flex-benefit">💰 Risparmia fino al 15%</span>
                </span>
              </label>
            </div>
            <div class="flex-option">
              <label class="flex-checkbox">
                <input type="checkbox" id="add-insurance" />
                <span class="checkmark"></span>
                <span class="flex-text">
                  <strong>Assicurazione viaggio</strong> - Protezione completa
                  <span class="flex-benefit">🛡️ Solo €19/persona</span>
                </span>
              </label>
            </div>
            <div class="flex-option">
              <label class="flex-checkbox">
                <input type="checkbox" id="early-checkin" />
                <span class="checkmark"></span>
                <span class="flex-text">
                  <strong>Check-in anticipato</strong> - Dalle ore 12:00
                  <span class="flex-benefit">⏰ +€25/notte</span>
                </span>
              </label>
            </div>
            <div class="flex-option">
              <label class="flex-checkbox">
                <input type="checkbox" id="late-checkout" />
                <span class="checkmark"></span>
                <span class="flex-text">
                  <strong>Check-out posticipato</strong> - Fino alle 16:00
                  <span class="flex-benefit">🧳 +€25/notte</span>
                </span>
              </label>
            </div>
          </div>
        </div>

        <!-- Customer Details with Enhanced Security -->
        <div style="grid-column:1/3;" class="form-section">
          <div class="section-title">
            <span class="section-icon">👤</span>
            <span>I tuoi dati (protetti e sicuri)</span>
            <span class="security-badge">🔒 Dati criptati SSL</span>
          </div>
        </div>
        <div><label>Nome completo *</label><input type="text" id="customer-name" class="yht-input enhanced-input" required placeholder="Mario Rossi" /></div>
        <div><label>Email *</label><input type="email" id="customer-email" class="yht-input enhanced-input" required placeholder="mario.rossi@email.com" /></div>
        <div><label>Telefono</label><input type="tel" id="customer-phone" class="yht-input enhanced-input" placeholder="+39 123 456 7890" /></div>
        <div><label>Numero viaggiatori</label><input type="number" id="num-pax" class="yht-input enhanced-input" min="1" max="10" value="2" /></div>
        <div style="grid-column:1/3;"><label>Richieste speciali</label><textarea id="special-requests" class="yht-input enhanced-input" rows="3" placeholder="Allergie, esigenze particolari, preferenze, occasioni speciali..."></textarea></div>
        
        <!-- Add Privacy Notice -->
        <div style="grid-column:1/3;" class="privacy-notice">
          <p>🔒 I tuoi dati personali sono protetti secondo il GDPR. Non condividiamo mai le tue informazioni con terzi.</p>
        </div>
      </div>

      <!-- Enhanced Pricing Summary with Benefits -->
      <div id="pricing-summary" class="yht-summary enhanced-pricing" style="margin-top:24px;">
        <div class="pricing-header">
          <h3>💎 Il tuo pacchetto all-inclusive</h3>
          <div class="savings-badge">Risparmia fino al 30% vs prenotazioni separate</div>
        </div>
        <div id="price-breakdown"></div>
        <div class="total-price-section">
          <div style="border-top:1px solid var(--line);margin-top:10px;padding-top:10px;font-weight:bold;font-size:1.2rem;color:var(--primary);" id="total-price"></div>
          <div class="payment-info">
            <span>💳 Acconto solo 20% • Resto alla partenza</span>
          </div>
        </div>
        
        <!-- Value proposition -->
        <div class="value-props">
          <div class="value-item">✅ Cancellazione gratuita fino a 48h prima</div>
          <div class="value-item">✅ Assistenza 24/7 durante il viaggio</div>
          <div class="value-item">✅ Garanzia soddisfatti o rimborsati</div>
        </div>
      </div>

      <!-- Testimonial -->
      <div class="testimonial">
        <div class="testimonial-text">"Esperienza fantastica! Tutto organizzato perfettamente, non ho dovuto pensare a nulla. Consigliatissimo!"</div>
        <div class="testimonial-author">⭐⭐⭐⭐⭐ - Sarah M., Roma</div>
      </div>

      <div class="yht-actions enhanced-actions">
        <button class="yht-btn enhanced-button" id="check-availability">
          🔍 Verifica disponibilità
          <span class="button-subtext">Controllo in tempo reale</span>
        </button>
        <button class="yht-btn enhanced-button primary" id="complete-booking" style="display:none;">
          🎉 PRENOTA ORA - POSTO SICURO!
          <span class="button-subtext">Conferma immediata • Pagamento sicuro</span>
        </button>
        <button class="yht-btn ghost" onclick="goToStep(6)">← Torna ai tour</button>
      </div>

      <div class="guarantee-text">
        🛡️ <strong>Garanzia 100%:</strong> Se non sei completamente soddisfatto, ti rimborsiamo l'intero importo senza domande.
      </div>
    </div>

    <div id="yht-availability-check" style="display:none;">
      <div class="yht-summary">
        <h3>Verifica disponibilità</h3>
        <p>Stiamo controllando la disponibilità per le tue date...</p>
      </div>
    </div>
  </section>

  <script>
    // Enhanced trip builder with transportation options
    const REST = '<?php echo rest_url('yht/v1'); ?>';
    
    // Localized strings for JavaScript
    const yht_i18n = {
      recent_booking_text: "<?php echo esc_js(__('🟢 %s ha appena prenotato un tour %s', 'your-hidden-trip')); ?>",
      time_ago: "<?php echo esc_js(__('%s min fa', 'your-hidden-trip')); ?>",
      spots_left: "<?php echo esc_js(__('Solo <strong>%d posti</strong> rimasti per le tue date!', 'your-hidden-trip')); ?>",
      system_error: "<?php echo esc_js(__('Errore di sistema', 'your-hidden-trip')); ?>",
      availability_error: "<?php echo esc_js(__('Non è possibile verificare la disponibilità al momento. Riprova più tardi.', 'your-hidden-trip')); ?>",
      package_standard: "<?php echo esc_js(__('Standard', 'your-hidden-trip')); ?>",
      package_premium: "<?php echo esc_js(__('Premium', 'your-hidden-trip')); ?>",
      package_luxury: "<?php echo esc_js(__('Luxury', 'your-hidden-trip')); ?>"
    };
    
    const state = {
      travelerType: '',
      esperienze: [],
      luogo: [],
      trasporto: '',
      durata: '',
      startdate: '',
      pax: 2,
      selectedTour: null,
      packageType: '',
      customerDetails: {}
    };

    let currentStep = 1;

    function initBuilder() {
      setupCardInteractions();
      setupNavigation();
      setupFormSubmission();
      initBookingEnhancements();
    }

    function setupCardInteractions() {
      document.querySelectorAll('.yht-card').forEach(card => {
        card.addEventListener('click', handleCardClick);
        card.addEventListener('keydown', (e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            handleCardClick.call(card);
          }
        });
      });
    }

    function handleCardClick() {
      const group = this.dataset.group;
      const value = this.dataset.value;
      const isCheckbox = this.getAttribute('role') === 'checkbox';

      if (isCheckbox) {
        const isSelected = this.dataset.selected === 'true';
        this.dataset.selected = !isSelected;
        this.setAttribute('aria-checked', !isSelected);
        
        if (!isSelected) {
          state[group].push(value);
        } else {
          state[group] = state[group].filter(v => v !== value);
        }
      } else {
        // Radio button
        document.querySelectorAll(`[data-group="${group}"]`).forEach(el => {
          el.dataset.selected = 'false';
          el.setAttribute('aria-checked', 'false');
        });
        this.dataset.selected = 'true';
        this.setAttribute('aria-checked', 'true');
        state[group] = value;
      }
    }

    function setupNavigation() {
      document.querySelectorAll('[data-next]').forEach(btn => {
        btn.addEventListener('click', () => {
          const step = parseInt(btn.dataset.next);
          if (validateStep(step)) {
            goToStep(step + 1);
          }
        });
      });

      document.querySelectorAll('[data-reset]').forEach(btn => {
        btn.addEventListener('click', () => {
          const step = parseInt(btn.dataset.reset);
          resetStep(step);
        });
      });

      document.getElementById('yht-startdate').addEventListener('change', (e) => {
        state.startdate = e.target.value;
      });

      document.getElementById('yht-pax').addEventListener('change', (e) => {
        state.pax = parseInt(e.target.value) || 2;
      });
    }

    function validateStep(step) {
      const errorEl = document.getElementById(`yht-err${step}`);
      let isValid = true;

      switch (step) {
        case 1:
          isValid = !!state.travelerType;
          break;
        case 2:
          isValid = state.esperienze.length > 0;
          break;
        case 3:
          isValid = state.luogo.length > 0;
          break;
        case 4:
          isValid = !!state.trasporto;
          break;
        case 5:
          isValid = !!state.durata && !!state.startdate;
          break;
      }

      errorEl.setAttribute('data-show', !isValid);
      return isValid;
    }

    function goToStep(step) {
      // Hide current step
      document.querySelectorAll('.yht-stepview').forEach(view => {
        view.setAttribute('data-show', 'false');
      });

      // Show new step
      document.getElementById(`yht-step${step}`).setAttribute('data-show', 'true');

      // Update step indicators
      updateStepIndicators(step);
      currentStep = step;

      // Generate tours if final step
      if (step === 6) {
        generateTours();
      }
    }

    function updateStepIndicators(step) {
      for (let i = 1; i <= 6; i++) {
        const stepEl = document.getElementById(`yht-s${i}`);
        const lineEl = document.getElementById(`yht-l${i}`);

        if (i < step) {
          stepEl.setAttribute('data-done', 'true');
          stepEl.setAttribute('data-active', 'false');
          if (lineEl) lineEl.style.width = '100%';
        } else if (i === step) {
          stepEl.setAttribute('data-active', 'true');
          stepEl.setAttribute('data-done', 'false');
          if (lineEl) lineEl.style.width = '0%';
        } else {
          stepEl.setAttribute('data-active', 'false');
          stepEl.setAttribute('data-done', 'false');
          if (lineEl) lineEl.style.width = '0%';
        }
      }

      // Update progress bar
      const progress = ((step - 1) / 5) * 100;
      document.getElementById('yht-progress').style.width = progress + '%';
    }

    function resetStep(step) {
      const group = ['', 'travelerType', 'esperienze', 'luogo', 'trasporto', 'durata'][step];
      if (Array.isArray(state[group])) {
        state[group] = [];
      } else {
        state[group] = '';
      }

      // Reset UI
      document.querySelectorAll(`[data-group="${group}"]`).forEach(el => {
        el.dataset.selected = 'false';
        el.setAttribute('aria-checked', 'false');
      });
    }

    async function generateTours() {
      try {
        const response = await fetch(REST + '/generate', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(state)
        });

        const result = await response.json();
        displayTours(result.tours);
        displaySummary();
      } catch (error) {
        console.error('Error generating tours:', error);
      }
    }

    function displaySummary() {
      const summary = document.getElementById('yht-summary');
      const trasportoText = {
        'auto_propria': 'Auto propria',
        'noleggio_auto': 'Noleggio auto',
        'autista': 'Con autista'
      };

      summary.innerHTML = `
        <strong>Le tue preferenze:</strong><br>
        Stile: ${state.travelerType === 'active' ? 'Attivo' : 'Rilassato'}<br>
        Esperienze: ${state.esperienze.join(', ')}<br>
        Aree: ${state.luogo.join(', ')}<br>
        Trasporto: ${trasportoText[state.trasporto] || state.trasporto}<br>
        Durata: ${state.durata.replace('_', ' ')}<br>
        Data: ${state.startdate}<br>
        Persone: ${state.pax}
      `;
    }

    function displayTours(tours) {
      const container = document.getElementById('yht-tours');
      container.innerHTML = tours.map(tour => `
        <div class="yht-tour" style="background:#fff;border:1px solid var(--line);border-radius:12px;padding:12px;margin-bottom:12px;">
          <h4 style="margin:.2rem 0;">${tour.name}</h4>
          <div class="meta" style="color:var(--muted);font-size:.9rem;margin:6px 0;">
            ${tour.stops} tappe • €${tour.totalEntryCost || 0} ingressi
          </div>
          
          ${tour.accommodations && tour.accommodations.length > 0 ? `
            <div style="margin-top:8px;">
              <strong style="font-size:.9rem;">🏨 Alloggi suggeriti:</strong>
              <ul style="margin:4px 0 0 16px;font-size:.85rem;">
                ${tour.accommodations.map(acc => `<li>${acc.title}</li>`).join('')}
              </ul>
            </div>
          ` : ''}
          
          ${tour.services && tour.services.length > 0 ? `
            <div style="margin-top:8px;">
              <strong style="font-size:.9rem;">🍽️ Servizi consigliati:</strong>
              <ul style="margin:4px 0 0 16px;font-size:.85rem;">
                ${tour.services.map(service => {
                  const serviceType = service.service_type && service.service_type.length > 0 ? 
                    (service.service_type.includes('ristorante') ? '🍽️' : 
                     service.service_type.includes('noleggio_auto') ? '🚙' :
                     service.service_type.includes('autista') ? '🚖' : '⚙️') : '⚙️';
                  return `<li>${serviceType} ${service.title}</li>`;
                }).join('')}
              </ul>
            </div>
          ` : ''}
          
          <div class="pick" style="margin-top:8px;">
            <button class="yht-btn" onclick="selectTour('${tour.name}', ${JSON.stringify(tour).replace(/'/g, '&apos;')})">Seleziona</button>
          </div>
        </div>
      `).join('');
    }

    function selectTour(tourName, tourData) {
      console.log('Tour selected:', tourName, tourData);
      state.selectedTour = tourData;
      
      // Show booking form
      document.getElementById('yht-booking-form').style.display = 'block';
      
      // Calculate initial prices for all package types
      updatePackagePricing();
      
      // Go to booking step
      goToStep(7);
    }

    async function updatePackagePricing() {
      if (!state.selectedTour) return;
      
      const numPax = parseInt(document.getElementById('num-pax')?.value || 2);
      const travelDate = state.startdate;
      
      const packageTypes = ['standard', 'premium', 'luxury'];
      
      for (const packageType of packageTypes) {
        try {
          const response = await fetch(REST + '/calculate_price', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              tour: state.selectedTour,
              package_type: packageType,
              num_pax: numPax,
              travel_date: travelDate
            })
          });
          
          const result = await response.json();
          if (result.ok) {
            document.getElementById(`price-${packageType}`).textContent = `€${result.total}`;
          }
        } catch (error) {
          console.error('Error calculating price:', error);
          document.getElementById(`price-${packageType}`).textContent = '€--';
        }
      }
    }

    function goToStep(step) {
      // Hide all steps
      document.querySelectorAll('.yht-stepview').forEach(s => s.setAttribute('data-show', 'false'));
      
      // Show target step
      document.getElementById(`yht-step${step}`).setAttribute('data-show', 'true');
      
      // Update progress
      currentStep = step;
      updateProgress();
    }

    function updateProgress() {
      const progress = (currentStep / 7) * 100;
      document.getElementById('yht-progress').style.width = progress + '%';
      
      // Update step indicators
      for (let i = 1; i <= 7; i++) {
        const stepEl = document.getElementById(`yht-s${i}`);
        if (i < currentStep) {
          stepEl.setAttribute('data-done', 'true');
          stepEl.setAttribute('data-active', 'false');
        } else if (i === currentStep) {
          stepEl.setAttribute('data-active', 'true');
          stepEl.setAttribute('data-done', 'false');
        } else {
          stepEl.setAttribute('data-active', 'false');
          stepEl.setAttribute('data-done', 'false');
        }
        
        // Update progress lines
        if (i < 7) {
          const lineEl = document.getElementById(`yht-l${i}`);
          lineEl.style.width = i < currentStep ? '100%' : '0%';
        }
      }
    }

    function setupFormSubmission() {
      // Existing export functions
      document.getElementById('yht-print')?.addEventListener('click', () => {
        window.print();
      });

      document.getElementById('yht-export')?.addEventListener('click', () => {
        const dataStr = JSON.stringify(state, null, 2);
        const dataBlob = new Blob([dataStr], {type: 'application/json'});
        const url = URL.createObjectURL(dataBlob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'trip-preferences.json';
        link.click();
      });

      document.getElementById('yht-pdf')?.addEventListener('click', async () => {
        try {
          const response = await fetch(REST + '/pdf', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(state)
          });
          const result = await response.json();
          if (result.pdf_url) {
            window.open(result.pdf_url, '_blank');
          }
        } catch (error) {
          console.error('Error generating PDF:', error);
        }
      });

      // New booking event handlers
      document.getElementById('num-pax')?.addEventListener('change', updatePackagePricing);
      
      // Package type selection
      document.querySelectorAll('[data-group="packageType"]').forEach(card => {
        card.addEventListener('click', function() {
          // Update UI selection
          document.querySelectorAll('[data-group="packageType"]').forEach(c => 
            c.setAttribute('data-selected', 'false'));
          this.setAttribute('data-selected', 'true');
          
          state.packageType = this.dataset.value;
          updatePricingSummary();
        });
      });

      // Availability check
      document.getElementById('check-availability')?.addEventListener('click', async () => {
        await checkAvailability();
      });

      // Complete booking
      document.getElementById('complete-booking')?.addEventListener('click', async () => {
        await completeBooking();
      });
    }

    async function updatePricingSummary() {
      if (!state.selectedTour || !state.packageType) return;
      
      const numPax = parseInt(document.getElementById('num-pax')?.value || 2);
      
      try {
        const response = await fetch(REST + '/calculate_price', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            tour: state.selectedTour,
            package_type: state.packageType,
            num_pax: numPax,
            travel_date: state.startdate
          })
        });
        
        const result = await response.json();
        if (result.ok) {
          const breakdown = document.getElementById('price-breakdown');
          breakdown.innerHTML = `
            <div style="display:flex;justify-content:space-between;margin:4px 0;">
              <span>Alloggio (${result.package_type}):</span>
              <span>€${result.breakdown.accommodation || 0}</span>
            </div>
            <div style="display:flex;justify-content:space-between;margin:4px 0;">
              <span>Attività ed escursioni:</span>
              <span>€${result.breakdown.activities || 0}</span>
            </div>
            <div style="display:flex;justify-content:space-between;margin:4px 0;">
              <span>Pasti inclusi:</span>
              <span>€${result.breakdown.meals || 0}</span>
            </div>
            <div style="display:flex;justify-content:space-between;margin:4px 0;">
              <span>Trasporti:</span>
              <span>€${result.breakdown.transport || 0}</span>
            </div>
            <div style="display:flex;justify-content:space-between;margin:4px 0;">
              <span>Servizi e assistenza:</span>
              <span>€${result.breakdown.service_fee || 0}</span>
            </div>
          `;
          
          document.getElementById('total-price').innerHTML = `
            Totale: €${result.total} | Acconto: €${result.deposit}
          `;
        }
      } catch (error) {
        console.error('Error updating pricing summary:', error);
      }
    }

    async function checkAvailability() {
      if (!state.selectedTour) return;
      
      const numPax = parseInt(document.getElementById('num-pax').value);
      
      document.getElementById('yht-availability-check').style.display = 'block';
      
      try {
        const response = await fetch(REST + '/check_availability', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            tour: state.selectedTour,
            travel_date: state.startdate,
            num_pax: numPax
          })
        });
        
        const result = await response.json();
        const checkDiv = document.getElementById('yht-availability-check');
        
        if (result.available) {
          checkDiv.innerHTML = `
            <div class="yht-summary" style="background:#d1fae5;border-color:#10b981;">
              <h3 style="color:#065f46;">✅ Disponibilità confermata!</h3>
              <p>Il tuo pacchetto è disponibile per le date selezionate.</p>
            </div>
          `;
          document.getElementById('complete-booking').style.display = 'inline-block';
        } else {
          checkDiv.innerHTML = `
            <div class="yht-summary" style="background:#fed7d7;border-color:#f56565;">
              <h3 style="color:#c53030;">❌ Disponibilità limitata</h3>
              <p>Alcuni servizi potrebbero non essere disponibili:</p>
              <ul>${result.messages.map(msg => `<li>${msg}</li>`).join('')}</ul>
              <p>Ti contatteremo per trovare alternative.</p>
            </div>
          `;
          document.getElementById('complete-booking').style.display = 'inline-block';
        }
      } catch (error) {
        console.error('Error checking availability:', error);
        document.getElementById('yht-availability-check').innerHTML = `
          <div class="yht-summary" style="background:#fed7d7;border-color:#f56565;">
            <h3 style="color:#c53030;">${yht_i18n.system_error}</h3>
            <p>${yht_i18n.availability_error}</p>
          </div>
        `;
      }
    }

    // Enhanced booking features
    function initBookingEnhancements() {
      // Load real booking stats
      loadBookingStats();
      
      // Animate recent bookings
      animateRecentBookings();
      
      // Update urgency message
      updateUrgencyMessage();
      
      // Real-time form validation
      setupRealTimeValidation();
    }

    async function loadBookingStats() {
      try {
        const response = await fetch(REST + '/booking_stats');
        const stats = await response.json();
        
        if (stats && stats.total_bookings) {
          // Update total bookings with animation
          animateCounterTo('total-bookings', stats.total_bookings);
          
          // Update recent bookings with real data
          if (stats.recent_bookings && stats.recent_bookings.length > 0) {
            animateRecentBookingsWithData(stats.recent_bookings);
          }
        }
      } catch (error) {
        console.log('Using fallback booking stats');
        animateRecentBookings(); // Fallback to static data
      }
    }

    function animateCounterTo(elementId, targetNumber) {
      const element = document.getElementById(elementId);
      if (!element) return;
      
      const startNum = parseInt(element.textContent.replace(/,/g, '')) || 1000;
      const duration = 2000;
      const increment = (targetNumber - startNum) / (duration / 50);
      let currentNum = startNum;
      
      const counter = setInterval(() => {
        currentNum += increment;
        if (currentNum >= targetNumber) {
          currentNum = targetNumber;
          clearInterval(counter);
        }
        element.textContent = Math.floor(currentNum).toLocaleString();
      }, 50);
    }

    function animateRecentBookingsWithData(bookingsData) {
      const container = document.querySelector('.recent-booking');
      if (!container || !bookingsData.length) return;
      
      let currentIndex = 0;
      const updateBooking = () => {
        const booking = bookingsData[currentIndex % bookingsData.length];
        container.innerHTML = `
          <span class="booking-text">${yht_i18n.recent_booking_text.replace('%s', booking.name).replace('%s', booking.package)}</span>
          <span class="booking-time">${yht_i18n.time_ago.replace('%s', booking.time_minutes || booking.time.replace(' min fa', ''))}</span>
        `;
        currentIndex++;
      };
      
      updateBooking(); // Show first one immediately
      setInterval(updateBooking, 5000); // Update every 5 seconds
    }

    function animateRecentBookings() {
      const bookings = [
        { name: 'Marco da Roma', package: yht_i18n.package_premium, time: '2 min fa' },
        { name: 'Laura da Milano', package: yht_i18n.package_luxury, time: '5 min fa' },
        { name: 'Giuseppe da Napoli', package: yht_i18n.package_standard, time: '8 min fa' },
        { name: 'Francesca da Firenze', package: yht_i18n.package_premium, time: '12 min fa' }
      ];
      
      const container = document.querySelector('.recent-booking');
      if (!container) return;
      
      let currentIndex = 0;
      setInterval(() => {
        const booking = bookings[currentIndex % bookings.length];
        container.innerHTML = `
          <span class="booking-text">${yht_i18n.recent_booking_text.replace('%s', booking.name).replace('%s', booking.package)}</span>
          <span class="booking-time">${yht_i18n.time_ago.replace('%s', booking.time.replace(' min fa', ''))}</span>
        `;
        currentIndex++;
      }, 4000);
    }

    function updateUrgencyMessage() {
      const urgencyEl = document.querySelector('.urgency-text');
      if (!urgencyEl) return;
      
      const spots = Math.floor(Math.random() * 5) + 2; // 2-6 spots
      urgencyEl.innerHTML = `Solo <strong>${spots} posti</strong> rimasti per le tue date!`;
      
      // Update every 30 seconds
      setInterval(() => {
        const newSpots = Math.max(1, spots - Math.floor(Math.random() * 2));
        urgencyEl.innerHTML = `Solo <strong>${newSpots} posti</strong> rimasti per le tue date!`;
      }, 30000);
    }

    function animateStats() {
      const totalBookingsEl = document.getElementById('total-bookings');
      if (!totalBookingsEl) return;
      
      let startNum = 1247;
      const incrementer = () => {
        startNum += Math.floor(Math.random() * 3);
        totalBookingsEl.textContent = startNum.toLocaleString();
      };
      
      // Update every 2 minutes
      setInterval(incrementer, 120000);
    }

    function setupRealTimeValidation() {
      const nameInput = document.getElementById('customer-name');
      const emailInput = document.getElementById('customer-email');
      
      if (nameInput) {
        nameInput.addEventListener('input', (e) => {
          const value = e.target.value.trim();
          if (value.length > 2) {
            e.target.style.borderColor = '#10b981';
            e.target.style.background = '#f0fdf4';
          } else {
            e.target.style.borderColor = '#e5e7eb';
            e.target.style.background = '#fff';
          }
        });
      }
      
      if (emailInput) {
        emailInput.addEventListener('input', (e) => {
          const value = e.target.value.trim();
          const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
          if (emailRegex.test(value)) {
            e.target.style.borderColor = '#10b981';
            e.target.style.background = '#f0fdf4';
          } else {
            e.target.style.borderColor = '#e5e7eb';
            e.target.style.background = '#fff';
          }
        });
      }
    }

    async function completeBooking() {
      // Validate form
      const customerName = document.getElementById('customer-name').value.trim();
      const customerEmail = document.getElementById('customer-email').value.trim();
      const numPax = parseInt(document.getElementById('num-pax').value);
      
      if (!customerName || !customerEmail || !state.packageType) {
        alert('Compila tutti i campi obbligatori.');
        return;
      }
      
      const bookingData = {
        customer_name: customerName,
        customer_email: customerEmail,
        customer_phone: document.getElementById('customer-phone').value.trim(),
        tour: state.selectedTour,
        travel_date: state.startdate,
        num_pax: numPax,
        package_type: state.packageType,
        special_requests: document.getElementById('special-requests').value.trim(),
        // Flexibility options
        flexible_dates: document.getElementById('flexible-dates')?.checked || false,
        add_insurance: document.getElementById('add-insurance')?.checked || false,
        early_checkin: document.getElementById('early-checkin')?.checked || false,
        late_checkout: document.getElementById('late-checkout')?.checked || false
      };
      
      try {
        const button = document.getElementById('complete-booking');
        button.disabled = true;
        button.innerHTML = `
          <span>🚀 Creazione prenotazione...</span>
          <span class="button-subtext">Elaborazione sicura in corso</span>
        `;
        
        const response = await fetch(REST + '/book_package', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(bookingData)
        });
        
        const result = await response.json();
        
        if (result.ok) {
          // Show enhanced success message
          showSuccessMessage(result);
          
          // Delayed redirect to payment with countdown
          let countdown = 5;
          const countdownInterval = setInterval(() => {
            button.innerHTML = `
              <span>✅ Prenotazione confermata!</span>
              <span class="button-subtext">Reindirizzamento al pagamento tra ${countdown}s...</span>
            `;
            countdown--;
            if (countdown <= 0) {
              clearInterval(countdownInterval);
              window.location.href = result.wc_checkout_url;
            }
          }, 1000);
        } else {
          showErrorMessage(result.message);
          button.disabled = false;
          button.innerHTML = `
            <span>🎉 PRENOTA ORA - POSTO SICURO!</span>
            <span class="button-subtext">Conferma immediata • Pagamento sicuro</span>
          `;
        }
      } catch (error) {
        console.error('Error completing booking:', error);
        showErrorMessage('Errore di sistema. Riprova più tardi.');
        const button = document.getElementById('complete-booking');
        button.disabled = false;
        button.innerHTML = `
          <span>🎉 PRENOTA ORA - POSTO SICURO!</span>
          <span class="button-subtext">Conferma immediata • Pagamento sicuro</span>
        `;
      }
    }

    function showSuccessMessage(result) {
      // Escape HTML to prevent XSS
      const escapeHtml = (text) => {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
      };
      
      const messageHtml = `
        <div class="success-overlay" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:10000;display:flex;align-items:center;justify-content:center;">
          <div class="success-modal" style="background:#fff;border-radius:16px;padding:32px;max-width:500px;text-align:center;animation:modalFadeIn 0.3s ease;">
            <div style="font-size:4rem;margin-bottom:16px;">🎉</div>
            <h2 style="color:#10b981;margin-bottom:16px;">Prenotazione Confermata!</h2>
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:16px;margin:16px 0;">
              <p><strong>Riferimento:</strong> ${escapeHtml(result.booking_reference || '')}</p>
              <p><strong>Totale:</strong> €${escapeHtml(result.total_price || '0')}</p>
              <p><strong>Acconto:</strong> €${escapeHtml(result.deposit_amount || '0')} (20%)</p>
            </div>
            <p style="color:#374151;margin:16px 0;">✅ La tua prenotazione è stata confermata con successo!</p>
            <p style="color:#374151;margin:16px 0;">💳 Procederai ora al pagamento sicuro dell'acconto</p>
            <p style="font-size:0.9rem;color:#6b7280;">🔒 Pagamento protetto SSL • Garanzia soddisfatti o rimborsati</p>
          </div>
        </div>
        <style>
        @keyframes modalFadeIn {
          from { opacity: 0; transform: scale(0.9); }
          to { opacity: 1; transform: scale(1); }
        }
        </style>
      `;
      
      document.body.insertAdjacentHTML('beforeend', messageHtml);
    }

    function showErrorMessage(message) {
      // Escape HTML to prevent XSS
      const escapeHtml = (text) => {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
      };
      
      const errorHtml = `
        <div class="error-overlay" style="position:fixed;top:20px;right:20px;background:#fef2f2;border:2px solid #fca5a5;border-radius:12px;padding:20px;max-width:400px;z-index:10000;animation:slideInRight 0.3s ease;">
          <div style="display:flex;align-items:center;gap:12px;">
            <span style="font-size:1.5rem;">❌</span>
            <div>
              <h4 style="color:#dc2626;margin:0 0 8px;">Ops! Qualcosa è andato storto</h4>
              <p style="color:#991b1b;margin:0;font-size:0.9rem;">${escapeHtml(message)}</p>
            </div>
          </div>
        </div>
        <style>
        @keyframes slideInRight {
          from { opacity: 0; transform: translateX(100%); }
          to { opacity: 1; transform: translateX(0); }
        }
        </style>
      `;
      
      document.body.insertAdjacentHTML('beforeend', errorHtml);
      
      // Auto remove after 5 seconds
      setTimeout(() => {
        const overlay = document.querySelector('.error-overlay');
        if (overlay) overlay.remove();
      }, 5000);
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initBuilder);
    } else {
      initBuilder();
    }
  </script>
</div>