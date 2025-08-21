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
  </style>

  <div class="yht-header">
    <span class="yht-badge">Your Hidden Trip</span>
    <div class="yht-title">Crea il tuo viaggio su misura</div>
  </div>

  <div class="yht-progressbar" aria-hidden="true"><i id="yht-progress"></i></div>

  <div class="yht-steps" role="navigation" aria-label="Step di compilazione">
    <div id="yht-s1" class="yht-step" data-label="Viaggiatore" data-active="true" tabindex="0">1</div>
    <div class="yht-line"><i id="yht-l1"></i></div>
    <div id="yht-s2" class="yht-step" data-label="Esperienze" tabindex="-1">2</div>
    <div class="yht-line"><i id="yht-l2"></i></div>
    <div id="yht-s3" class="yht-step" data-label="Luogo" tabindex="-1">3</div>
    <div class="yht-line"><i id="yht-l3"></i></div>
    <div id="yht-s4" class="yht-step" data-label="Trasporto" tabindex="-1">4</div>
    <div class="yht-line"><i id="yht-l4"></i></div>
    <div id="yht-s5" class="yht-step" data-label="Durata" tabindex="-1">5</div>
    <div class="yht-line"><i id="yht-l5"></i></div>
    <div id="yht-s6" class="yht-step" data-label="Riepilogo" tabindex="-1">6</div>
  </div>

  <!-- STEP 1: Traveler Type -->
  <section id="yht-step1" class="yht-stepview" data-show="true" role="region" aria-labelledby="yht-h2-1">
    <h2 id="yht-h2-1" class="yht-h2">Che tipo di viaggiatore sei?</h2>
    <p class="yht-help">Scegli lo stile: incide su tappe al giorno e budget.</p>
    <div class="yht-grid" role="radiogroup" aria-label="Tipo viaggiatore">
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="travelerType" data-value="active">
        <div class="yht-ico">⚡</div><div class="yht-t">Ami fare tante cose</div><div class="yht-d">Ritmo alto (3–4 tappe/giorno)</div>
      </article>
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="travelerType" data-value="relaxed">
        <div class="yht-ico">☕</div><div class="yht-t">Giornata rilassata</div><div class="yht-d">Ritmo lento (1–2 tappe/giorno)</div>
      </article>
    </div>
    <p id="yht-err1" class="yht-error" aria-live="polite">Seleziona un tipo di viaggiatore.</p>
    <div class="yht-actions">
      <button class="yht-btn" data-next="1">Prosegui</button>
      <button class="yht-btn ghost" data-reset="1">Reset</button>
    </div>
  </section>

  <!-- STEP 2: Experiences -->
  <section id="yht-step2" class="yht-stepview" role="region" aria-labelledby="yht-h2-2">
    <h2 id="yht-h2-2" class="yht-h2">Che tipo di esperienza cerchi?</h2>
    <p class="yht-help">Puoi selezionare più opzioni.</p>
    <div class="yht-grid" role="group" aria-label="Esperienze">
      <article class="yht-card" tabindex="0" role="checkbox" aria-checked="false" data-group="esperienze" data-value="trekking"><div class="yht-ico">🥾</div><div class="yht-t">Trekking</div><div class="yht-d">Sentieri e natura</div></article>
      <article class="yht-card" tabindex="0" role="checkbox" aria-checked="false" data-group="esperienze" data-value="passeggiata"><div class="yht-ico">🚶</div><div class="yht-t">Passeggiata</div><div class="yht-d">Percorsi facili</div></article>
      <article class="yht-card" tabindex="0" role="checkbox" aria-checked="false" data-group="esperienze" data-value="cultura"><div class="yht-ico">🏛️</div><div class="yht-t">Cultura</div><div class="yht-d">Borghi, musei, siti</div></article>
      <article class="yht-card" tabindex="0" role="checkbox" aria-checked="false" data-group="esperienze" data-value="benessere"><div class="yht-ico">🧖</div><div class="yht-t">Benessere</div><div class="yht-d">Terme e spa</div></article>
      <article class="yht-card" tabindex="0" role="checkbox" aria-checked="false" data-group="esperienze" data-value="enogastronomia"><div class="yht-ico">🍷</div><div class="yht-t">Enogastronomia</div><div class="yht-d">Cantine e sapori</div></article>
    </div>
    <p id="yht-err2" class="yht-error" aria-live="polite">Seleziona almeno un'esperienza.</p>
    <div class="yht-actions">
      <button class="yht-btn" data-next="2">Prosegui</button>
      <button class="yht-btn ghost" data-reset="2">Reset</button>
    </div>
  </section>

  <!-- STEP 3: Areas -->
  <section id="yht-step3" class="yht-stepview" role="region" aria-labelledby="yht-h2-3">
    <h2 id="yht-h2-3" class="yht-h2">Dove preferisci?</h2>
    <p class="yht-help">Seleziona aree d'interesse.</p>
    <div class="yht-grid" role="group" aria-label="Luoghi">
      <article class="yht-card" tabindex="0" role="checkbox" aria-checked="false" data-group="luogo" data-value="natura"><div class="yht-ico">🌳</div><div class="yht-t">Natura</div></article>
      <article class="yht-card" tabindex="0" role="checkbox" aria-checked="false" data-group="luogo" data-value="citta"><div class="yht-ico">🏙️</div><div class="yht-t">Città</div></article>
      <article class="yht-card" tabindex="0" role="checkbox" aria-checked="false" data-group="luogo" data-value="collina"><div class="yht-ico">⛰️</div><div class="yht-t">Collina</div></article>
      <article class="yht-card" tabindex="0" role="checkbox" aria-checked="false" data-group="luogo" data-value="lago"><div class="yht-ico">🌊</div><div class="yht-t">Lago</div></article>
      <article class="yht-card" tabindex="0" role="checkbox" aria-checked="false" data-group="luogo" data-value="centro_storico"><div class="yht-ico">🏰</div><div class="yht-t">Centro storico</div></article>
    </div>
    <p id="yht-err3" class="yht-error" aria-live="polite">Seleziona almeno un luogo.</p>
    <div class="yht-actions">
      <button class="yht-btn" data-next="3">Prosegui</button>
      <button class="yht-btn ghost" data-reset="3">Reset</button>
    </div>
  </section>

  <!-- STEP 4: Transportation (NEW) -->
  <section id="yht-step4" class="yht-stepview" role="region" aria-labelledby="yht-h2-4">
    <h2 id="yht-h2-4" class="yht-h2">Come preferisci muoverti?</h2>
    <p class="yht-help">Seleziona la tua preferenza di trasporto per ottimizzare i suggerimenti.</p>
    <div class="yht-grid" role="radiogroup" aria-label="Trasporto">
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="trasporto" data-value="auto_propria">
        <div class="yht-ico">🚗</div><div class="yht-t">Auto propria</div><div class="yht-d">Hai la tua auto</div>
      </article>
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="trasporto" data-value="noleggio_auto">
        <div class="yht-ico">🚙</div><div class="yht-t">Noleggio auto</div><div class="yht-d">Serve auto a noleggio</div>
      </article>
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="trasporto" data-value="autista">
        <div class="yht-ico">🚖</div><div class="yht-t">Con autista</div><div class="yht-d">Prefer essere guidato</div>
      </article>
    </div>
    <p id="yht-err4" class="yht-error" aria-live="polite">Seleziona una preferenza di trasporto.</p>
    <div class="yht-actions">
      <button class="yht-btn" data-next="4">Prosegui</button>
      <button class="yht-btn ghost" data-reset="4">Reset</button>
    </div>
  </section>

  <!-- STEP 5: Duration & Date -->
  <section id="yht-step5" class="yht-stepview" role="region" aria-labelledby="yht-h2-5">
    <h2 id="yht-h2-5" class="yht-h2">Quanto tempo hai?</h2>
    <p class="yht-help">Scegli durata e data di partenza.</p>
    <div class="yht-grid" role="radiogroup" aria-label="Durata">
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="durata" data-value="1_notte"><div class="yht-ico">🌙</div><div class="yht-t">1 notte</div></article>
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="durata" data-value="2_notti"><div class="yht-ico">🌙🌙</div><div class="yht-t">2 notti</div></article>
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="durata" data-value="3_notti"><div class="yht-ico">🌙🌙🌙</div><div class="yht-t">3 notti</div></article>
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="durata" data-value="4_notti"><div class="yht-ico">🌙🌙🌙🌙</div><div class="yht-t">4 notti</div></article>
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="durata" data-value="5+_notti"><div class="yht-ico">🌙+</div><div class="yht-t">5+ notti</div></article>
    </div>
    <div class="yht-row yht-mt">
      <div class="yht-col">
        <label for="yht-startdate" class="yht-small">Data di partenza</label>
        <input id="yht-startdate" class="yht-input" type="date" />
      </div>
      <div class="yht-col">
        <label for="yht-pax" class="yht-small">Persone</label>
        <input id="yht-pax" class="yht-input" type="number" min="1" value="2" />
      </div>
    </div>
    <p id="yht-err5" class="yht-error" aria-live="polite">Seleziona una durata e la data di partenza.</p>
    <div class="yht-actions">
      <button class="yht-btn" data-next="5">Vai al riepilogo</button>
      <button class="yht-btn ghost" data-reset="5">Reset</button>
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

  <script>
    // Enhanced trip builder with transportation options
    const REST = '<?php echo rest_url('yht/v1'); ?>';
    const state = {
      travelerType: '',
      esperienze: [],
      luogo: [],
      trasporto: '',
      durata: '',
      startdate: '',
      pax: 2
    };

    let currentStep = 1;

    function initBuilder() {
      setupCardInteractions();
      setupNavigation();
      setupFormSubmission();
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
      alert(`Tour "${tourName}" selezionato! 
${tourData.accommodations && tourData.accommodations.length > 0 ? `Include ${tourData.accommodations.length} alloggi suggeriti e ` : ''}${tourData.services && tourData.services.length > 0 ? tourData.services.length + ' servizi consigliati.' : 'Nessun servizio aggiuntivo.'}`);
    }

    function setupFormSubmission() {
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
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initBuilder);
    } else {
      initBuilder();
    }
  </script>
</div>