<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choisissez votre Template - Ceremony</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --accent: #f43f5e;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text: #1e293b;
            --text-light: #64748b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        header {
            text-align: center;
            margin-bottom: 60px;
        }

        h1 {
            font-family: 'Playfair Display', serif;
            font-size: 3.5rem;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #1e293b 0%, #4f46e5 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .subtitle {
            font-size: 1.2rem;
            color: var(--text-light);
            max-width: 600px;
            margin: 0 auto;
        }

        .template-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .template-card {
            background: var(--card-bg);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
            position: relative;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .template-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border-color: var(--primary);
        }

        .preview-container {
            height: 400px;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .preview-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .template-card:hover .preview-overlay {
            opacity: 1;
        }

        .btn-select {
            background: white;
            color: var(--primary);
            padding: 12px 24px;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            transform: translateY(20px);
        }

        .template-card:hover .btn-select {
            transform: translateY(0);
        }

        .info {
            padding: 25px;
            text-align: center;
        }

        .info h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            margin-bottom: 8px;
        }

        .info p {
            color: var(--text-light);
            font-size: 0.95rem;
        }

        .tag {
            position: absolute;
            top: 20px;
            right: 20px;
            background: var(--primary);
            color: white;
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            z-index: 2;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Prêt à éblouir ?</h1>
            <p class="subtitle">Choisissez une maquette et personnalisez-la en quelques secondes pour créer une invitation mémorable.</p>
        </header>

        <div class="template-grid">
            @foreach($templates as $template)
            <div class="template-card" onclick="window.location.href='{{ route('builder.edit', $template->id) }}'">
                <span class="tag">{{ $template->category }}</span>
                <div class="preview-container">
                    @if($template->preview_image)
                        <img src="{{ $template->preview_image }}" alt="{{ $template->name }}" style="width:100%; height:100%; object-fit:cover;">
                    @else
                        <div style="font-size: 5rem;">💌</div>
                    @endif
                    <div class="preview-overlay">
                        <span class="btn-select">Personnaliser</span>
                    </div>
                </div>
                <div class="info">
                    <h3>{{ $template->name }}</h3>
                    <p>Interactif & Élégant - {{ number_format($template->price_per_pack, 2) }}€</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</body>
</html>
