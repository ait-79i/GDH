# Email Template Example (GDH)

## Subject

Confirmation de votre demande de rendez-vous [appointment_id] – [site_name]

## Body (HTML)

<div class="container">
  <h1>Merci, [client_name] !</h1>
  <p>Nous avons bien reçu votre demande de rendez-vous.</p>

  <h2>Détails de votre demande</h2>
  <table class="details">
    <tr>
      <th>Référence</th>
      <td>[appointment_id]</td>
    </tr>
    <tr>
      <th>Date et heure</th>
      <td>[appointment_date]</td>
    </tr>
    <tr>
      <th>Service</th>
      <td>[service_name]</td>
    </tr>
    <tr>
      <th>Coordonnées</th>
      <td>
        <div>Email: [client_email]</div>
        <div>Téléphone: [phone]</div>
      </td>
    </tr>
    <tr>
      <th>Adresse d'intervention</th>
      <td>[address], [postal_code] [city]</td>
    </tr>
  </table>

  <p>Un e-mail de confirmation vous sera envoyé à l'adresse <strong>[client_email]</strong>. Vous pouvez répondre directement à ce message si besoin.</p>

  <div class="cta">
    <a class="btn" href="mailto:[admin_email]">Contacter l'équipe [site_name]</a>
  </div>

  <hr>
  <p class="footer">Cet e-mail a été envoyé par [site_name]. Pour toute question, écrivez à <a href="mailto:[admin_email]">[admin_email]</a>.</p>
</div>

## Style (CSS)

body { background:#f6f7f7; color:#1d2327; font-family: Arial, Helvetica, sans-serif; }
.container { max-width:640px; margin:24px auto; background:#ffffff; border:1px solid #dcdcde; border-radius:8px; padding:24px; }
h1 { color:#1d2327; font-size:22px; margin:0 0 12px; }
h2 { color:#1d2327; font-size:18px; margin:24px 0 12px; }
.details { width:100%; border-collapse:collapse; }
.details th, .details td { text-align:left; padding:10px; border-bottom:1px solid #e2e4e7; vertical-align:top; }
.details th { width:35%; color:#50575e; }
.cta { margin:24px 0; }
.btn { display:inline-block; background:#006847; color:#ffffff !important; text-decoration:none; padding:12px 16px; border-radius:6px; font-weight:bold; }
.footer { color:#6c7781; font-size:12px; }

## Plain text fallback

Merci, [client_name] !

Nous avons bien reçu votre demande de rendez-vous.

Référence: [appointment_id]
Date et heure: [appointment_date]
Service: [service_name]
Email: [client_email]
Téléphone: [phone]
Adresse d'intervention: [address], [postal_code] [city]

Vous recevrez un e-mail de confirmation à [client_email].
Pour toute question, contactez [admin_email].

— [site_name]
