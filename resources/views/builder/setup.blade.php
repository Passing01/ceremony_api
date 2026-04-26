<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration de l'événement - Ceremony</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text: #1e293b;
            --border: #e2e8f0;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            padding: 40px 20px;
        }

        .container { max-width: 800px; margin: 0 auto; }

        header { margin-bottom: 40px; }
        h1 { font-family: 'Playfair Display', serif; font-size: 2.5rem; margin-bottom: 10px; }
        p.subtitle { color: #64748b; }

        .card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            border: 1px solid var(--border);
            margin-bottom: 30px;
        }

        h2 { font-size: 1.25rem; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }

        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 0.9rem; font-weight: 600; margin-bottom: 8px; color: #475569; }
        input, select, textarea {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            border: 1px solid var(--border);
            font-family: inherit;
            font-size: 1rem;
            transition: all 0.3s;
        }
        input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }

        .guest-list { margin-top: 20px; }
        .guest-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: #f1f5f9;
            border-radius: 10px;
            margin-bottom: 10px;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-family: inherit;
        }
        .btn-primary { background: var(--primary); color: white; width: 100%; }
        .btn-secondary { background: #e2e8f0; color: #475569; }

        .preview-box {
            background: #fefce8;
            border: 1px dashed #facc15;
            padding: 15px;
            border-radius: 10px;
            font-size: 0.9rem;
            color: #854d0e;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Finalisez votre événement</h1>
            <p class="subtitle">Votre invitation est prête. Configurez maintenant les détails logistiques.</p>
        </header>

        <form id="setupForm">
            @csrf
            <div class="card">
                <h2><span>📅</span> Informations Générales</h2>
                <div class="form-group">
                    <label>Nom de l'événement</label>
                    <input type="text" name="title" placeholder="Ex: Mariage de Sarah & Tom" required>
                </div>
                <div class="form-group">
                    <label>Date et Heure</label>
                    <input type="datetime-local" name="event_date" required>
                </div>
                <div class="form-group">
                    <label>Lieu</label>
                    <input type="text" name="location" placeholder="Adresse complète ou nom du lieu">
                </div>
            </div>

            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
                    <h2 style="margin-bottom:0;"><span>👥</span> Liste des Invités</h2>
                    <button type="button" id="contactPickerBtn" class="btn btn-secondary" style="font-size:0.8rem; padding:8px 15px;">📱 Importer Contacts</button>
                </div>
                <div id="guestContainer" class="guest-list">
                    <!-- Dynamic guests -->
                </div>
                <div style="display:flex; gap:10px;">
                    <input type="text" id="guestName" placeholder="Nom de l'invité" style="flex:1;">
                    <input type="tel" id="guestPhone" placeholder="Numéro WhatsApp" style="flex:1;">
                    <button type="button" class="btn btn-secondary" onclick="addGuest()">Ajouter</button>
                </div>
            </div>

            <div class="card">
                <h2><span>💬</span> Message d'invitation</h2>
                <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 15px;">Ce texte sera envoyé via WhatsApp avec le lien vers votre invitation personnalisée.</p>
                <div class="form-group">
                    <textarea name="invitation_message" rows="4" placeholder="Bonjour ! Nous sommes heureux de vous inviter..."></textarea>
                </div>
                <div class="preview-box">
                    <strong>Aperçu du lien :</strong><br>
                    https://ceremony.com/e/mariage-sarah-tom
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Lancer les invitations ✨</button>
        </form>
    </div>

    <script>
        const guests = [];
        const contactPickerBtn = document.getElementById('contactPickerBtn');
        const setupForm = document.getElementById('setupForm');

        setupForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (guests.length === 0) {
                alert('Veuillez ajouter au moins un invité.');
                return;
            }

            const formData = new FormData(setupForm);
            const data = Object.fromEntries(formData.entries());
            data.guests = guests;

            try {
                const response = await fetch('{{ route('builder.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                if (response.ok) {
                    alert('Félicitations ! Votre événement a été créé et les invitations sont prêtes à être envoyées.');
                    window.location.href = result.redirect;
                } else {
                    alert('Erreur: ' + result.message);
                }
            } catch (err) {
                console.error(err);
                alert('Une erreur est survenue lors de la sauvegarde.');
            }
        });

        // Feature detection for Contact Picker API
        if (!('contacts' in navigator && 'ContactsManager' in window)) {
            contactPickerBtn.innerHTML = "📱 Contacts (Flutter Bridge)";
            // Note: If running inside Flutter WebView, the developer should inject a JS handler
        }

        contactPickerBtn.addEventListener('click', async () => {
            try {
                if ('contacts' in navigator) {
                    // Modern Browser API
                    const props = ['name', 'tel'];
                    const opts = { multiple: true };
                    const selectedContacts = await navigator.contacts.select(props, opts);
                    
                    selectedContacts.forEach(contact => {
                        if (contact.name && contact.tel && contact.tel.length > 0) {
                            guests.push({ 
                                name: contact.name[0], 
                                phone: contact.tel[0].replace(/\s+/g, '') // Nettoyer le numéro
                            });
                        }
                    });
                    renderGuests();
                } else {
                    // Bridge Flutter: Envoyer un message au container Flutter
                    if (window.FlutterInterface) {
                        window.FlutterInterface.postMessage('openContactPicker');
                    } else {
                        alert("L'importation native n'est pas disponible sur ce navigateur. Veuillez entrer les noms manuellement.");
                    }
                }
            } catch (err) {
                console.error('Erreur contact picker:', err);
            }
        });

        // Ecouter les contacts envoyés par Flutter (via JS injection)
        window.addContactsFromFlutter = function(contactsJson) {
            const newContacts = JSON.parse(contactsJson);
            newContacts.forEach(c => guests.push(c));
            renderGuests();
        };

        function addGuest() {
            const name = document.getElementById('guestName').value;
            const phone = document.getElementById('guestPhone').value;

            if (name && phone) {
                guests.push({ name, phone });
                renderGuests();
                document.getElementById('guestName').value = '';
                document.getElementById('guestPhone').value = '';
            }
        }

        function renderGuests() {
            const container = document.getElementById('guestContainer');
            container.innerHTML = guests.map((g, i) => `
                <div class="guest-item">
                    <span><strong>${g.name}</strong> (${g.phone})</span>
                    <button type="button" style="background:none; border:none; color:#f43f5e; cursor:pointer;" onclick="removeGuest(${i})">✕</button>
                </div>
            `).join('');
        }

        function removeGuest(index) {
            guests.splice(index, 1);
            renderGuests();
        }
    </script>
</body>
</html>
