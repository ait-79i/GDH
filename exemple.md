# Email Template Example (GDH)

## Subject

Nouvelle demande de rendez-vous – {{date_rdv}} – {{nom_lead}}

## Body (HTML)

<div class="container">
  <h1>Nouvelle demande de rendez-vous</h1>

  <p>Bonjour <strong>{{nom_destinataire}}</strong>,</p>
  <p>Vous avez reçu une nouvelle demande de rendez-vous. Veuillez trouver ci‑dessous les informations du lead et son créneau prioritaire.</p>

  <h2>Résumé</h2>
  <table class="details">
    <tr>
      <th>Destinataire</th>
      <td>{{nom_destinataire}}</td>
    </tr>
    <tr>
      <th>Créneau prioritaire</th>
      <td>{{date_rdv}}</td>
    </tr>
  </table>

  <h2>Disponibilités proposées</h2>
  {{creneaux_rdv}}

  <h2>Coordonnées du lead</h2>
  <table class="details">
    <tr>
      <th>Nom complet</th>
      <td>{{nom_lead}}</td>
    </tr>
    <tr>
      <th>Email</th>
      <td><a href="mailto:{{email_lead}}">{{email_lead}}</a></td>
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
    <a class="btn" href="tel:{{phone}}">Appeler le lead</a>
    <a class="btn" href="mailto:{{email_lead}}">Répondre par e‑mail</a>
  </div>

  <p class="note">Astuce : les autres disponibilités proposées par le client sont visibles dans l'administration du site.</p>

  <hr>
  <p class="footer">Cet e‑mail a été généré automatiquement par {{nom_destinataire}}.</p>
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

Bonjour {{nom_destinataire}},

Nouvelle demande de rendez-vous
Destinataire: {{nom_destinataire}}
Créneau prioritaire: {{date_rdv}}

Disponibilités proposées:
{{creneaux_rdv}}

Coordonnées du lead:
- Nom complet: {{nom_lead}}
- Email: {{email_lead}}
- Téléphone: {{phone}}
- Adresse d'intervention: {{address}}, {{postal_code}} {{city}}

Actions:
- Appeler: {{phone}}
- Répondre par e‑mail: {{email_lead}}

Cet e‑mail a été généré automatiquement par {{nom_destinataire}}.

## Confirmation Email – Subject

Confirmation de votre demande de rendez-vous – {{date_rdv}}

## Confirmation Email – Body (HTML)

<div class="container">
  <h1>Confirmation de votre demande</h1>

  <p>Bonjour <strong>{{nom_lead}}</strong>,</p>
  <p>Nous accusons réception de votre demande de rendez-vous. Voici votre récapitulatif.</p>

  <h2>Détail de votre demande</h2>
  <table class="details">
    <tr>
      <th>Date prioritaire</th>
      <td>{{date_rdv}}</td>
    </tr>
    <tr>
      <th>Adresse d'intervention</th>
      <td>{{address}}, {{postal_code}} {{city}}</td>
    </tr>
  </table>

  <h2>Autres disponibilités proposées</h2>
  {{creneaux_rdv}}

  <p><strong>{{nom_destinataire}}</strong> vous contactera prochainement afin de confirmer un créneau.</p>

  <p class="footer">Cet e‑mail a été envoyé automatiquement. Si ces informations ne sont pas correctes, répondez directement à ce message.</p>
</div>

## Plain text fallback (confirmation)

Bonjour {{nom_lead}},

Nous accusons réception de votre demande de rendez-vous.

- Date prioritaire: {{date_rdv}}
- Adresse d'intervention: {{address}}, {{postal_code}} {{city}}
- Autres disponibilités:
{{creneaux_rdv}}

{{nom_destinataire}} vous contactera pour confirmer un créneau.
