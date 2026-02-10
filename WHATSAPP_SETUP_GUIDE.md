# Guide de Configuration WhatsApp API (Meta)

Ce guide vous explique comment obtenir les identifiants nécessaires (`WHATSAPP_TOKEN` et `WHATSAPP_PHONE_NUMBER_ID`) pour faire fonctionner l'envoi d'invitations.

## Étape 1 : Créer un compte Meta Developer

1. Allez sur [developers.facebook.com](https://developers.facebook.com/).
2. Connectez-vous avec votre compte Facebook.
3. Cliquez sur "My Apps" (Mes applications) > "Create App".
4. Sélectionnez le type **"Autre"** (Other) > **"Business"**.
5. Donnez un nom à votre application (ex: `CeremonyApp`) et liez-la à un compte Business (ou laissez vide si c'est pour tester).

## Étape 2 : Ajouter WhatsApp à l'application

1. Dans le tableau de bord de votre nouvelle application, trouvez "WhatsApp" en bas de page.
2. Cliquez sur **"Set up"**.
3. Meta va générer un numéro de test pour vous.

## Étape 3 : Récupérer les identifiants (Mode Test)

Dans le menu de gauche, sous **WhatsApp > API Setup**, vous verrez :

1. **Temporary Access Token** : C'est votre `WHATSAPP_TOKEN` pour le développement (expire en 24h).
   * *Pour la production, il faudra créer un "System User" pour avoir un token permanent.*
2. **Phone Number ID** : C'est votre `WHATSAPP_PHONE_NUMBER_ID`.

Copiez ces valeurs dans votre fichier `.env` :

```env
WHATSAPP_TOKEN=EAAKg...
WHATSAPP_PHONE_NUMBER_ID=10060...
```

## Étape 4 : Créer le Template de Message

Pour envoyer des messages à des utilisateurs qui ne vous ont pas encore écrit (invitations), vous **DEVEZ** utiliser un template approuvé.

1. Allez dans **WhatsApp > Quickstart**.
2. Cliquez sur le lien **"Click here to create templates"** (ou allez dans le "WhatsApp Manager" via votre Business Suite).
3. Cliquez sur **"Create Template"**.
4. Remplissez les infos :
   * **Category** : Utility (ou Marketing)
   * **Name** : `ceremony_invite_v1` (ce nom doit correspondre exactement à celui dans le code `SendWhatsAppInvite.php`)
   * **Language** : French (Français)

5. **Contenu du message** :
   Dans le corps du message (Body), mettez quelque chose comme :
   
   > Bonjour, vous êtes invité à **{{1}}**.
   >
   > Cliquez ici pour voir votre invitation : **{{2}}**
   >
   > Message personnel :
   > **{{3}}**

   * `{{1}}` sera remplacé par le titre de l'événement.
   * `{{2}}` sera remplacé par le lien.
   * `{{3}}` sera remplacé par le `invitation_text`.

6. **Validation** : Soumettez le template. La validation par Meta prend généralement quelques secondes à quelques minutes.

## Étape 5 : Ajouter des destinataires (Mode Test)

En mode test (avec le token temporaire et le numéro de test), vous ne pouvez envoyer des messages qu'à des numéros **vérifiés**.

1. Retournez dans **WhatsApp > API Setup**.
2. Dans la section "To", ajoutez votre propre numéro de téléphone personnel pour tester.
3. Validez le code reçu par SMS.

## Passage en Production

Une fois que vous êtes prêt à lancer l'application :
1. Ajoutez un vrai numéro de téléphone dans la section "Phone numbers" (pas votre numéro perso actuel s'il est déjà sur WhatsApp standard).
2. Configurez un **System User** dans les paramètres Business pour obtenir un token permanent.
3. Ajoutez une méthode de paiement (les 1000 premières conversations de service par mois sont gratuites, puis c'est payant).
