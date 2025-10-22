# GDH Rendez-vous WordPress Plugin

Un plugin WordPress pour la prise de rendez-vous en ligne via un formulaire multi-étapes dans une popup.

## Description

GDH Rendez-vous est un plugin WordPress qui permet d'intégrer facilement un système de prise de rendez-vous sur votre site. Le plugin offre un formulaire multi-étapes dans une popup élégante et personnalisable, permettant aux visiteurs de sélectionner des créneaux horaires et de fournir leurs informations de contact.

## Fonctionnalités

- **Formulaire multi-étapes** : Processus de réservation en 3 étapes (sélection de créneaux, adresse, informations personnelles)
- **Interface responsive** : S'adapte à tous les appareils (desktop, tablette, mobile)
- **Personnalisation avancée** : Couleurs, polices, textes et styles entièrement personnalisables
- **Gestion des destinataires** : Mode statique (email fixe) ou dynamique (email basé sur le contenu)
- **Notifications par email** : Envoi automatique d'emails au destinataire et confirmation au client
- **Validation des formulaires** : Vérification des champs obligatoires et formats d'entrée
- **Intégration CGV** : Option pour ajouter l'acceptation des conditions générales de vente

## Installation

1. Téléchargez le plugin et décompressez-le
2. Uploadez le dossier `GDH Plugin` dans le répertoire `/wp-content/plugins/` de votre installation WordPress
3. Activez le plugin via le menu 'Extensions' dans WordPress
4. Configurez les paramètres du plugin via le menu 'Rendez-vous' dans l'administration WordPress

## Utilisation du Shortcode

Le plugin s'intègre facilement sur n'importe quelle page de votre site grâce au shortcode `[gdh_rdv]`.

### Shortcode de base

```
[gdh_rdv]
```

### Shortcode avec options

```
[gdh_rdv button_label="Réserver maintenant" class="my-custom-button" style="background-color: #ff0000; color: white;"]
```

### Options du shortcode

| Option | Description | Valeur par défaut |
|--------|-------------|-------------------|
| `button_label` | Texte affiché sur le bouton | "Prendre rendez-vous" |
| `class` | Classes CSS à ajouter au bouton | "" |
| `style` | Style CSS inline à appliquer au bouton | "" |

## Configuration

### Paramètres d'apparence

Accédez à **Rendez-vous > Apparence de la popup** pour personnaliser :

- Couleurs (primaire, accent, texte des boutons)
- Police de caractères
- Titre de la popup et son alignement
- Page CGV (conditions générales)
- Opacité de l'overlay

### Paramètres des emails

Accédez à **Rendez-vous > Paramètres des e-mails** pour configurer :

1. **Email de notification à l'artisan/destinataire** :
   - Sujet et corps du message
   - Variables disponibles pour personnaliser le contenu

2. **Email de confirmation au client** (optionnel) :
   - Activation/désactivation
   - Sujet et corps du message

3. **Configuration du destinataire** :
   - **Mode statique** : Email fixe pour toutes les notifications
   - **Mode dynamique** : Email récupéré depuis les métadonnées du contenu où le shortcode est placé

## Mode Dynamique

Le mode dynamique permet d'envoyer les notifications à différents destinataires en fonction du contenu où le shortcode est placé.

### Configuration du mode dynamique

1. Sélectionnez "Mode dynamique" dans les paramètres des emails
2. Choisissez le type de contenu (post type) concerné
3. Spécifiez les champs meta pour l'email et le nom du destinataire

### Validation automatique

Le plugin effectue plusieurs validations pour garantir que la configuration est correcte :

- Vérification que le shortcode est placé dans le bon type de contenu
- Vérification que les champs meta sont correctement configurés
- Affichage d'avertissements si des problèmes sont détectés

## Variables disponibles dans les emails

| Variable | Description |
|----------|-------------|
| `{{nom_lead}}` | Nom complet du client |
| `{{date_rdv}}` | Date et heure du rendez-vous prioritaire |
| `{{email_lead}}` | Adresse email du client |
| `{{phone}}` | Numéro de téléphone du client |
| `{{address}}` | Adresse d'intervention |
| `{{city}}` | Ville d'intervention |
| `{{postal_code}}` | Code postal d'intervention |
| `{{nom_destinataire}}` | Nom du destinataire/artisan |
| `{{creneaux_rdv}}` | Liste formatée de tous les créneaux proposés |

## Suivi des emails

Le plugin enregistre automatiquement les informations suivantes pour chaque rendez-vous :

- `_gdh_email_sent` : Statut d'envoi de l'email à l'artisan (0/1)
- `_gdh_destinataire_email` : Email du destinataire/artisan
- `_gdh_destinataire_name` : Nom du destinataire/artisan

## Développement

### Structure du plugin

```
GDH Plugin/
├── assets/                  # Ressources CSS et JavaScript
│   ├── css/
│   └── js/
├── src/                     # Code source PHP
│   ├── Admin/               # Classes pour l'administration
│   ├── Ajax/                # Gestionnaires de requêtes AJAX
│   ├── Core/                # Classes principales du plugin
│   ├── Frontend/            # Contrôleurs frontend
│   ├── PostTypes/           # Définitions des types de contenu personnalisés
│   ├── Services/            # Services (email, logger, etc.)
│   └── Shortcodes/          # Gestionnaire de shortcodes
├── templates/               # Templates Twig
│   ├── admin/               # Templates d'administration
│   └── frontend/            # Templates frontend
├── vendor/                  # Dépendances (généré par Composer)
├── gdh-rdv-plugin.php       # Point d'entrée du plugin
└── composer.json            # Configuration Composer
```

### Dépendances

- Twig : Moteur de templates
- WordPress : 5.0 ou supérieur
- PHP : 7.2 ou supérieur

## Support

Pour toute question ou assistance, veuillez contacter le développeur du plugin.

## Licence

Ce plugin est distribué sous licence propriétaire. Tous droits réservés.

---

Développé par Abdelkarim AIT HQI
