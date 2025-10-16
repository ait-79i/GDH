# Email Template Example (GDH)

## Subject

Nouvelle demande de rendez-vous – {{artisan_name}}

## Body (HTML)

<div class="container">
  <h1>Nouvelle demande de rendez-vous</h1>

  <p>Bonjour <strong>{{artisan_name}}</strong>,</p>
  <p>Vous avez reçu une nouvelle demande de rendez-vous. Voici les informations du client et son créneau prioritaire.</p>

  <h2>Résumé</h2>
  <table class="details">
    <tr>
      <th>Artisan</th>
      <td>{{artisan_name}}</td>
    </tr>
    <tr>
      <th>Créneau prioritaire</th>
      <td>{{appointment_date}}</td>
    </tr>
  </table>

  <h2>Disponibilités proposées</h2>
  {{appointment_slots}}

  <h2>Coordonnées du client</h2>
  <table class="details">
    <tr>
      <th>Nom complet</th>
      <td>{{client_name}}</td>
    </tr>
    <tr>
      <th>Email</th>
      <td><a href="mailto:{{client_email}}">{{client_email}}</a></td>
    </tr>
    <tr>
      <th>Téléphone</th>
      <td><a href="tel:{{phone}}">{{phone}}</a></td>
    </tr>
    <tr>
      <th>Adresse d'intervention</th>
      <td>{{address}}, {{postal_code}} {{city}}</td>
    </tr>
  </table>

  <div class="cta">
    <a class="btn" href="tel:{{phone}}">Appeler le client</a>
    <a class="btn" href="mailto:{{client_email}}">Répondre par e‑mail</a>
  </div>

  <p class="note">Astuce : les autres disponibilités proposées par le client sont visibles dans l'administration du site.</p>

  <hr>
  <p class="footer">Cet e-mail est généré automatiquement par {{artisan_name}}.</p>
</div>

## Style (CSS)

body { background:#f6f7f7; color:#1d2327; font-family: Arial, Helvetica, sans-serif; }
.container { max-width:640px; margin:24px auto; background:#ffffff; border:1px solid #dcdcde; border-radius:8px; padding:24px; }
h1 { color:#1d2327; font-size:22px; margin:0 0 12px; }
h2 { color:#1d2327; font-size:18px; margin:24px 0 12px; }
.details { width:100%; border-collapse:collapse; }
.details th, .details td { text-align:left; padding:10px; border-bottom:1px solid #e2e4e7; vertical-align:top; }
.details th { width:35%; color:#50575e; }
.gdh-slots { margin: 0 0 12px 18px; }
.gdh-slots li { line-height: 1.5; }
.gdh-slot { margin: 0 0 12px; }
.cta { margin:24px 0; }
.btn { display:inline-block; background:#006847; color:#ffffff !important; text-decoration:none; padding:12px 16px; border-radius:6px; font-weight:bold; }
.footer { color:#6c7781; font-size:12px; }

## Plain text fallback

Bonjour {{artisan_name}},

Nouvelle demande de rendez-vous
Artisan: {{artisan_name}}
Créneau prioritaire: {{appointment_date}}

Disponibilités proposées:
{{appointment_slots}}

Coordonnées du client:
- Nom complet: {{client_name}}
- Email: {{client_email}}
- Téléphone: {{phone}}
- Adresse d'intervention: {{address}}, {{postal_code}} {{city}}

Actions:
- Appeler: {{phone}}
- Répondre par e-mail: {{client_email}}

Cet e-mail est généré automatiquement par {{artisan_name}}.
