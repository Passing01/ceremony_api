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
            --bg: #0f172a;
            --sidebar-bg: #1e293b;
            --text: #f8fafc;
            --accent: #f43f5e;
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

        /* ========== SIDEBAR ========== */
        .sidebar {
            width: 350px;
            background: var(--sidebar-bg);
            border-right: 1px solid rgba(255,255,255,0.1);
            display: flex;
            flex-direction: column;
            padding: 30px;
            z-index: 10;
        }

        .sidebar h2 { margin-bottom: 20px; font-weight: 600; }
        .sidebar p { font-size: 0.9rem; color: #94a3b8; margin-bottom: 30px; }

        .control-group { margin-bottom: 25px; }
        .control-label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 10px; color: #cbd5e1; text-transform: uppercase; letter-spacing: 1px; }

        .btn {
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-family: inherit;
        }

        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-2px); }

        .btn-secondary { background: rgba(255,255,255,0.05); color: white; border: 1px solid rgba(255,255,255,0.1); }
        .btn-secondary:hover { background: rgba(255,255,255,0.1); }

        .footer-btns { margin-top: auto; display: flex; flex-direction: column; gap: 15px; }

        /* ========== MAIN AREA (PHONE) ========== */
        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            background: radial-gradient(circle at center, #1e293b 0%, #0f172a 100%);
        }

        .phone-mockup {
            width: 380px;
            height: 780px;
            background: #000;
            border-radius: 50px;
            padding: 12px;
            box-shadow: 0 50px 100px rgba(0,0,0,0.5), 0 0 0 2px rgba(255,255,255,0.1);
            position: relative;
        }

        .phone-screen {
            width: 100%;
            height: 100%;
            background: #fff;
            border-radius: 40px;
            overflow: hidden;
            position: relative;
        }

        .phone-notch {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 150px;
            height: 30px;
            background: #000;
            border-bottom-left-radius: 20px;
            border-bottom-right-radius: 20px;
            z-index: 20;
        }

        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        /* ========== EDITOR OVERLAYS ========== */
        .editor-hint {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.8);
            padding: 10px 20px;
            border-radius: 50px;
            font-size: 0.85rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
            pointer-events: none;
            transition: opacity 0.3s;
        }

        #mediaModal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 100;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: var(--sidebar-bg);
            padding: 30px;
            border-radius: 20px;
            width: 90%;
            max-width: 400px;
        }

        .modal-content input {
            width: 100%;
            padding: 12px;
            background: rgba(0,0,0,0.2);
            border: 1px solid rgba(255,255,255,0.1);
            color: white;
            border-radius: 8px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Personnalisation</h2>
        <p>Modifiez le texte, les images et gérez les sections de votre invitation.</p>

        <div class="control-group">
            <span class="control-label">Structure</span>
            <button class="btn btn-secondary" onclick="addSection()">＋ Ajouter une section</button>
        </div>

        <div class="control-group">
            <span class="control-label">Musique de fond</span>
            <button class="btn btn-secondary">🎵 Choisir une musique</button>
        </div>

        <div class="footer-btns">
            <button class="btn btn-secondary" onclick="window.location.href='{{ route('builder.index') }}'">Annuler</button>
            <button class="btn btn-primary" onclick="finishEditing()">Suivant : Gérer l'événement →</button>
        </div>
    </div>

    <div class="main-content">
        <div class="phone-mockup">
            <div class="phone-notch"></div>
            <div class="phone-screen">
                <iframe id="templateFrame" srcdoc=""></iframe>
            </div>
        </div>
        <div class="editor-hint">Cliquez sur un élément pour le modifier</div>
    </div>

    <!-- Modal pour les images -->
    <div id="mediaModal">
        <div class="modal-content">
            <h3>Modifier le média</h3>
            <p>Entrez l'URL de l'image ou de la vidéo</p>
            <input type="text" id="mediaUrl" placeholder="https://...">
            <div style="display:flex; gap:10px;">
                <button class="btn btn-secondary" onclick="closeModal()">Annuler</button>
                <button class="btn btn-primary" onclick="applyMedia()">Appliquer</button>
            </div>
        </div>
    </div>

    <script>
        const rawHtml = `{!! addslashes($html) !!}`;
        const templateFrame = document.getElementById('templateFrame');
        let currentEditingElement = null;

        // Injecter le script de l'éditeur dans le template
        const editorScript = `
            <style>
                [contenteditable]:focus { outline: 2px solid #6366f1; border-radius: 4px; padding: 2px; }
                .editable-hover { position: relative; }
                .delete-btn {
                    position: absolute; top: 0; right: 0;
                    background: #f43f5e; color: white; border: none;
                    border-radius: 50%; width: 24px; height: 24px;
                    cursor: pointer; display: none; z-index: 1000;
                    font-size: 12px; align-items: center; justify-content: center;
                }
                .chapter:hover .delete-btn, .chapter-slide:hover .delete-btn, .slide:hover .delete-btn { display: flex; }
            </style>
            <script>
                document.querySelectorAll('h1, h2, h3, p, .text, .story, .chapter-number, .chapter-badge, .date, .chapter-date').forEach(el => {
                    el.contentEditable = true;
                });

                document.querySelectorAll('img, video').forEach(el => {
                    el.style.cursor = 'pointer';
                    el.addEventListener('click', (e) => {
                        window.parent.postMessage({type: 'editMedia', src: el.src}, '*');
                        window.parent.currentEditingElement = el;
                        e.stopPropagation();
                    });
                });

                // Ajouter bouton de suppression aux sections
                document.querySelectorAll('.chapter, .chapter-slide, .slide').forEach(el => {
                    if (el.classList.contains('active') || el.classList.contains('visible') || el.dataset.index !== undefined) {
                        const btn = document.createElement('button');
                        btn.className = 'delete-btn';
                        btn.innerHTML = '✕';
                        btn.onclick = (e) => {
                            if(confirm('Supprimer cette section ?')) el.remove();
                            e.stopPropagation();
                        };
                        el.style.position = 'relative';
                        el.appendChild(btn);
                    }
                });

                // Désactiver les liens
                document.querySelectorAll('a').forEach(a => a.onclick = (e) => e.preventDefault());
                
                // Désactiver le formulaire RSVP (ne pas le laisser envoyer des trucs pendant l'edit)
                const form = document.querySelector('form');
                if(form) form.onsubmit = (e) => e.preventDefault();
            <\/script>
        `;

        templateFrame.srcdoc = rawHtml.replace('</body>', editorScript + '</body>');

        window.addEventListener('message', (e) => {
            if (e.data.type === 'editMedia') {
                document.getElementById('mediaModal').style.display = 'flex';
                document.getElementById('mediaUrl').value = e.data.src;
            }
        });

        function closeModal() {
            document.getElementById('mediaModal').style.display = 'none';
        }

        function applyMedia() {
            const url = document.getElementById('mediaUrl').value;
            if (currentEditingElement) {
                currentEditingElement.src = url;
            }
            closeModal();
        }

        function addSection() {
            // Logique pour ajouter une section (copie d'un chapitre existant par ex)
            const iframeDoc = templateFrame.contentDocument || templateFrame.contentWindow.document;
            const chapters = iframeDoc.querySelectorAll('.chapter, .chapter-slide, .slide');
            if (chapters.length > 0) {
                const newChapter = chapters[0].cloneNode(true);
                // Reset content
                newChapter.querySelectorAll('[contenteditable]').forEach(el => el.innerText = 'Nouveau texte');
                chapters[0].parentNode.appendChild(newChapter);
                alert('Nouvelle section ajoutée à la fin !');
            }
        }

        function finishEditing() {
            const iframeDoc = templateFrame.contentDocument || templateFrame.contentWindow.document;
            
            // Nettoyage avant sauvegarde
            iframeDoc.querySelectorAll('.delete-btn').forEach(btn => btn.remove());
            iframeDoc.querySelectorAll('[contenteditable]').forEach(el => el.contentEditable = false);

            const finalHtml = iframeDoc.documentElement.outerHTML;
            
            // Envoyer au serveur (ou stocker en session)
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('builder.save') }}';
            
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            
            const htmlInput = document.createElement('input');
            htmlInput.type = 'hidden';
            htmlInput.name = 'html';
            htmlInput.value = finalHtml;

            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'template_id';
            idInput.value = '{{ $id }}';
            
            form.appendChild(csrf);
            form.appendChild(htmlInput);
            form.appendChild(idInput);
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>
