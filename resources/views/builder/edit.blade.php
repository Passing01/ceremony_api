<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personnalisation - Ceremony</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --bg: #f8fafc;
            --sidebar-bg: #ffffff;
            --text: #1e293b;
            --border: #e2e8f0;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            height: 100vh;
            display: flex;
            overflow: hidden;
        }

        /* ========== FORM SIDEBAR ========== */
        .form-sidebar {
            width: 450px;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            padding: 25px;
            overflow-y: auto;
            z-index: 10;
            box-shadow: 10px 0 30px rgba(0,0,0,0.02);
        }

        .form-sidebar h2 { margin-bottom: 5px; font-weight: 600; font-size: 1.5rem; }
        .form-sidebar p { font-size: 0.9rem; color: #64748b; margin-bottom: 25px; }

        .section-card {
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            background: #fff;
            transition: all 0.3s;
        }

        .section-card:hover { border-color: var(--primary); box-shadow: 0 4px 12px rgba(99, 102, 241, 0.05); }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f1f5f9;
        }

        .section-header h3 { font-size: 1rem; color: var(--primary); font-weight: 600; }

        .field-group { margin-bottom: 15px; }
        .field-group label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; color: #475569; }
        
        input, textarea {
            width: 100%;
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid var(--border);
            font-family: inherit;
            font-size: 0.95rem;
            transition: all 0.3s;
        }
        input:focus, textarea:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }

        .btn {
            padding: 12px 20px;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: inherit;
            font-size: 0.95rem;
        }
        .btn-primary { background: var(--primary); color: white; width: 100%; }
        .btn-danger { background: #fee2e2; color: #ef4444; padding: 6px 12px; font-size: 0.8rem; }
        .btn-secondary { background: #f1f5f9; color: #475569; width: 100%; }

        .footer-actions {
            margin-top: auto;
            padding-top: 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        /* ========== PREVIEW AREA ========== */
        .preview-area {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1f5f9;
            position: relative;
        }

        .phone-container {
            width: 340px;
            height: 680px;
            background: #000;
            border-radius: 40px;
            padding: 10px;
            box-shadow: 0 40px 80px rgba(0,0,0,0.15);
            position: relative;
        }

        .phone-screen {
            width: 100%;
            height: 100%;
            background: #fff;
            border-radius: 32px;
            overflow: hidden;
            position: relative;
        }

        iframe { width: 100%; height: 100%; border: none; }

        .preview-label {
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #fff;
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            z-index: 100;
        }
    </style>
</head>
<body>
    <div class="form-sidebar">
        <h2>Personnalisation</h2>
        <p>Remplissez le formulaire ci-dessous. Vos modifications apparaissent en direct sur l'aperçu.</p>

        <div id="formContent">
            <!-- Les sections seront générées ici -->
        </div>

        <button class="btn btn-secondary" onclick="addSection()" style="margin-bottom: 20px;">＋ Ajouter une section</button>

        <div class="footer-actions">
            <button class="btn btn-primary" onclick="finishEditing()">Terminer la personnalisation →</button>
            <button class="btn btn-secondary" onclick="window.location.href='{{ route('builder.index') }}'">Annuler</button>
        </div>
    </div>

    <div class="preview-area">
        <div class="preview-label">Aperçu en temps réel</div>
        <div class="phone-container">
            <div class="phone-screen">
                <iframe id="templateFrame" srcdoc=""></iframe>
            </div>
        </div>
    </div>

    <script>
        const templateId = {{ $id }};
        const rawHtml = `{!! addslashes($html) !!}`;
        const templateFrame = document.getElementById('templateFrame');
        const formContent = document.getElementById('formContent');

        // Charger l'iframe
        templateFrame.srcdoc = rawHtml;

        // Fonction pour mettre à jour l'aperçu (debounced)
        let timeout;
        function updatePreview() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                const iframeDoc = templateFrame.contentDocument || templateFrame.contentWindow.document;
                
                // Ici on injecte les données du formulaire dans l'iframe
                // Cette partie dépend de la structure de chaque template.
                // Pour simplifier, on va faire des sélecteurs génériques.
                
                const data = getFormData();
                
                // Template 1 logic
                if (templateId == 4 || templateId == 1) {
                    const chapters = iframeDoc.querySelectorAll('.chapter');
                    data.sections.forEach((sec, i) => {
                        if (chapters[i]) {
                            if (sec.title) chapters[i].querySelector('h2').innerText = sec.title;
                            if (sec.text) chapters[i].querySelector('.text').innerText = sec.text;
                            if (sec.date) chapters[i].querySelector('.date').innerText = sec.date;
                            if (sec.media) {
                                const img = chapters[i].querySelector('img');
                                if(img) img.src = sec.media;
                                const video = chapters[i].querySelector('video');
                                if(video) video.src = sec.media;
                            }
                        }
                    });
                }
                
                // Pour les templates 2 & 3 qui sont data-driven, on peut injecter dans l'array global
                if (templateFrame.contentWindow.chaptersData) {
                    templateFrame.contentWindow.chaptersData = data.sections;
                    if (templateFrame.contentWindow.showChapter) templateFrame.contentWindow.showChapter(0);
                }
            }, 300);
        }

        function getFormData() {
            const sections = [];
            document.querySelectorAll('.section-card').forEach(card => {
                const sec = {};
                card.querySelectorAll('input, textarea').forEach(input => {
                    sec[input.dataset.key] = input.value;
                });
                sections.push(sec);
            });
            return { sections };
        }

        // Générer le formulaire initial (à adapter selon le Template)
        const initialSections = [
            { label: 'Chapitre 1', title: 'Le premier regard', text: "C'était un soir d'automne...", date: 'Paris, 12 Octobre 2020', media: 'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?w=600&h=400&fit=crop' },
            { label: 'Chapitre 2', title: 'La promenade', text: "Nos mains se sont effleurées...", date: 'Printemps 2021', media: 'https://assets.mixkit.co/videos/preview/mixkit-tree-with-yellow-flowers-1173-large.mp4' },
            { label: 'L\'invitation', title: 'Nous nous marions', text: "Nous serions honorés...", date: '14 Juin 2026', media: '' },
        ];

        function createSectionForm(data, index) {
            const card = document.createElement('div');
            card.className = 'section-card';
            card.innerHTML = `
                <div class="section-header">
                    <h3>${data.label || 'Nouvelle Section'}</h3>
                    <button class="btn btn-danger" onclick="this.closest('.section-card').remove(); updatePreview();">Supprimer</button>
                </div>
                <div class="field-group">
                    <label>Titre</label>
                    <input type="text" data-key="title" value="${data.title || ''}" oninput="updatePreview()">
                </div>
                <div class="field-group">
                    <label>Histoire / Message</label>
                    <textarea data-key="text" rows="3" oninput="updatePreview()">${data.text || ''}</textarea>
                </div>
                <div class="field-group">
                    <label>Date / Lieu</label>
                    <input type="text" data-key="date" value="${data.date || ''}" oninput="updatePreview()">
                </div>
                <div class="field-group">
                    <label>URL Image ou Vidéo</label>
                    <input type="text" data-key="media" value="${data.media || ''}" oninput="updatePreview()">
                </div>
            `;
            formContent.appendChild(card);
        }

        initialSections.forEach((s, i) => createSectionForm(s, i));

        function addSection() {
            createSectionForm({ label: 'Nouveau Chapitre' }, document.querySelectorAll('.section-card').length);
            updatePreview();
        }

        function finishEditing() {
            const iframeDoc = templateFrame.contentDocument || templateFrame.contentWindow.document;
            const finalHtml = iframeDoc.documentElement.outerHTML;
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('builder.save') }}';
            
            const csrf = document.createElement('input');
            csrf.type = 'hidden'; name = '_token'; csrf.name = '_token'; csrf.value = '{{ csrf_token() }}';
            
            const htmlInput = document.createElement('input');
            htmlInput.type = 'hidden'; name = 'html'; htmlInput.name = 'html'; htmlInput.value = finalHtml;

            const idInput = document.createElement('input');
            idInput.type = 'hidden'; name = 'template_id'; idInput.name = 'template_id'; idInput.value = '{{ $id }}';
            
            form.appendChild(csrf); form.appendChild(htmlInput); form.appendChild(idInput);
            document.body.appendChild(form);
            form.submit();
        }

        // Initial preview update after load
        templateFrame.onload = updatePreview;
    </script>
</body>
</html>
