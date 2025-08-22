# Come utilizzare lo shortcode Your Hidden Trip Planner

Il problema "nonostante metto lo shortcode nella pagina non vedo niente" è stato risolto!

## Il problema identificato
Il plugin aveva un errore nelle chiamate a `YHT_Plugin::get_instance()` nei template che causava errori PHP silenziosi, impedendo al shortcode di visualizzare qualsiasi contenuto.

## La soluzione implementata
È stata aggiunta una gestione degli errori robusta che garantisce che il shortcode funzioni anche se la classe del plugin ha problemi di inizializzazione.

## Come usare il shortcode

### Utilizzo base
```
[yourhiddentrip_builder]
```

### Utilizzo con parametri
```
[yourhiddentrip_builder template="enhanced" theme="auto"]
```

### Parametri disponibili:
- `template`: 
  - `enhanced` (predefinito) - Template moderno con interfaccia migliorata
  - `regular` - Template originale
- `theme`:
  - `auto` (predefinito) - Tema automatico
  - `light` - Tema chiaro  
  - `dark` - Tema scuro

### Dove inserire lo shortcode
1. Vai nella sezione **Pagine** o **Articoli** del tuo WordPress
2. Modifica la pagina dove vuoi mostrare il trip builder
3. Inserisci il codice shortcode: `[yourhiddentrip_builder]`
4. Salva e visualizza la pagina

### Verifica che funzioni
Dopo aver inserito lo shortcode, dovresti vedere:
- Un'interfaccia del trip builder con tema moderno
- Pulsanti e opzioni interattive
- Un form step-by-step per configurare il viaggio

### Risoluzione problemi

Se lo shortcode ancora non appare:

1. **Verifica che il plugin sia attivo**
   - Vai in **Plugin** > **Plugin installati**
   - Assicurati che "Your Hidden Trip Builder" sia attivato

2. **Controlla errori PHP**
   - Attiva il debug WordPress nel `wp-config.php`:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

3. **Verifica i file del plugin**
   - Assicurati che tutti i file del plugin siano presenti
   - In particolare controlla che esistano:
     - `includes/frontend/class-yht-shortcode.php`
     - `includes/frontend/views/trip-builder-enhanced.php`
     - `includes/class-yht-plugin.php`

4. **Test di compatibilità**
   - Prova a disattivare temporaneamente altri plugin per verificare conflitti
   - Prova con un tema WordPress predefinito

### Supporto tecnico
Se il problema persiste, controlla:
- Log degli errori del server
- Console del browser per errori JavaScript
- Che non ci siano conflitti con altri plugin o temi

Il fix implementato dovrebbe risolvere il problema principale che impediva la visualizzazione del shortcode.