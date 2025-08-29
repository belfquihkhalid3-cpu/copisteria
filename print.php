<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Copisteria - Impression</title>
    
    <!-- Bootstrap 5.3.2 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6.4 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #2c5aa0;
            --primary-dark: #1d4084;
            --primary-light: #4a7bc8;
            --secondary-color: #f8f9fa;
            --accent-color: #17a2b8;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --dark-color: #343a40;
            --light-gray: #6c757d;
            --border-color: #e9ecef;
            --shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            --shadow-lg: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            --border-radius: 0.75rem;
            --border-radius-lg: 1rem;
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        /* Header moderne */
        .header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 1.5rem 0;
            box-shadow: var(--shadow-lg);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .header .nav-info {
            color: rgba(255,255,255,0.9);
            font-size: 0.9rem;
        }

        .header .btn-logout {
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.3);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
            backdrop-filter: blur(10px);
        }

        .header .btn-logout:hover {
            background: rgba(255,255,255,0.3);
            border-color: rgba(255,255,255,0.5);
            color: white;
            transform: translateY(-2px);
        }

        /* Container principal */
        .main-container {
            display: flex;
            min-height: calc(100vh - 120px);
            gap: 2rem;
            padding: 2rem;
            max-width: 1600px;
            margin: 0 auto;
        }

        /* Zone de téléchargement moderne */
        .upload-zone {
            flex: 1;
            background: white;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            margin-right: 1rem;
        }

        .upload-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            color: white;
            padding: 1.5rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .upload-content {
            padding: 2rem;
        }

        .drop-zone {
            border: 3px dashed var(--border-color);
            border-radius: var(--border-radius);
            padding: 4rem 2rem;
            text-align: center;
            background: var(--secondary-color);
            transition: var(--transition);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .drop-zone:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.5s;
        }

        .drop-zone:hover {
            border-color: var(--primary-color);
            background: rgba(44, 90, 160, 0.05);
            transform: translateY(-2px);
        }

        .drop-zone:hover:before {
            left: 100%;
        }

        .drop-zone.dragover {
            border-color: var(--success-color);
            background: rgba(40, 167, 69, 0.1);
            transform: scale(1.02);
        }

        .upload-icon {
            font-size: 4rem !important;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            transition: var(--transition);
        }

        .drop-zone:hover .upload-icon {
            transform: scale(1.1) rotate(5deg);
            color: var(--primary-dark);
        }

        .upload-text {
            font-size: 1.2rem;
            color: var(--dark-color);
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .upload-subtext {
            color: var(--light-gray);
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .btn-browse {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            border: none;
            color: white;
            padding: 0.8rem 2rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            font-size: 1rem;
            transition: var(--transition);
            box-shadow: var(--shadow);
        }

        .btn-browse:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%);
        }

        .file-list {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid var(--border-color);
        }

        .file-item {
            background: var(--secondary-color);
            border-radius: var(--border-radius);
            padding: 1rem 1.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            border-left: 4px solid var(--primary-color);
            transition: var(--transition);
        }

        .file-item:hover {
            background: white;
            transform: translateX(5px);
            box-shadow: var(--shadow);
        }

        .file-icon {
            font-size: 2rem !important;
            color: var(--primary-color);
        }

        /* Sidebar moderne - PLUS LARGE */
        .config-sidebar {
            width: 450px; /* Augmenté de 380px à 450px */
            background: white;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-lg);
            height: fit-content;
            position: sticky;
            top: 2rem;
            overflow: hidden;
        }

        .sidebar-header {
            background: linear-gradient(135deg, var(--dark-color) 0%, #495057 100%);
            color: white;
            padding: 1.5rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .sidebar-content {
            padding: 2rem;
            max-height: calc(100vh - 200px);
            overflow-y: auto;
        }

        /* Sections de configuration */
        .config-section {
            margin-bottom: 2.5rem;
            padding-bottom: 2rem;
            border-bottom: 2px solid var(--border-color);
        }

        .config-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title i {
            font-size: 1.5rem !important; /* Icônes des sections plus grandes */
            color: var(--primary-color);
            background: rgba(44, 90, 160, 0.1);
            padding: 0.5rem;
            border-radius: 50%;
            width: 3rem;
            height: 3rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Options avec grandes icônes */
        .option-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .option-item {
            background: var(--secondary-color);
            border: 2px solid transparent;
            border-radius: var(--border-radius);
            padding: 1.5rem 1rem;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .option-item:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.5s;
        }

        .option-item:hover {
            border-color: var(--primary-color);
            background: white;
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .option-item:hover:before {
            left: 100%;
        }

        .option-item.active {
            border-color: var(--primary-color);
            background: rgba(44, 90, 160, 0.1);
            box-shadow: var(--shadow);
        }

        .option-item i {
            font-size: 2.5rem !important; /* Icônes des options encore plus grandes */
            color: var(--primary-color);
            margin-bottom: 0.75rem;
            transition: var(--transition);
        }

        .option-item:hover i {
            transform: scale(1.15) rotate(5deg);
            color: var(--primary-dark);
        }

        .option-text {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--dark-color);
            line-height: 1.3;
        }

        /* Couleurs spirale */
        .color-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin: 1rem 0;
        }

        .color-item {
            width: 100%;
            height: 60px; /* Plus grand */
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            border: 3px solid transparent;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: 600;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }

        .color-item:hover {
            transform: scale(1.05) rotate(2deg);
            box-shadow: var(--shadow-lg);
        }

        .color-item.active {
            border-color: var(--dark-color);
            transform: scale(1.1);
        }

        /* Boutons d'action modernes */
        .action-buttons {
            padding: 2rem;
            background: var(--secondary-color);
            border-top: 2px solid var(--border-color);
            display: flex;
            gap: 1rem;
        }

        .btn-modern {
            flex: 1;
            padding: 1rem 2rem;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            font-size: 1rem;
            transition: var(--transition);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }

        .btn-modern:before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255,255,255,0.3);
            border-radius: 50%;
            transition: all 0.5s;
            transform: translate(-50%, -50%);
        }

        .btn-modern:hover:before {
            width: 300px;
            height: 300px;
        }

        .btn-calculate {
            background: linear-gradient(135deg, var(--success-color) 0%, #20c997 100%);
            color: white;
            box-shadow: var(--shadow);
        }

        .btn-calculate:hover {
            background: linear-gradient(135deg, #20c997 0%, var(--success-color) 100%);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-order {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: var(--shadow);
        }

        .btn-order:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        /* Prix total moderne */
        .total-price {
            background: linear-gradient(135deg, var(--warning-color) 0%, #fd7e14 100%);
            color: white;
            padding: 1.5rem 2rem;
            border-radius: var(--border-radius-lg);
            text-align: center;
            margin: 1.5rem 0;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }

        .total-price:before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            animation: shine 3s infinite;
        }

        @keyframes shine {
            0% { transform: translateX(-100%) translateY(-100%) rotate(30deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(30deg); }
        }

        .price-label {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 0.5rem;
        }

        .price-amount {
            font-size: 2rem;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .config-sidebar {
                width: 400px;
            }
        }

        @media (max-width: 992px) {
            .main-container {
                flex-direction: column;
                padding: 1rem;
            }
            
            .config-sidebar {
                width: 100%;
                position: static;
            }
            
            .upload-zone {
                margin-right: 0;
                margin-bottom: 1rem;
            }
        }

        @media (max-width: 576px) {
            .option-group {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
            }
            
            .color-options {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .section-title i {
                font-size: 1.3rem !important;
                width: 2.5rem;
                height: 2.5rem;
            }
            
            .option-item i {
                font-size: 2rem !important;
            }
        }

        /* Animations */
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Scrollbar personnalisée */
        .sidebar-content::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar-content::-webkit-scrollbar-track {
            background: var(--secondary-color);
            border-radius: 3px;
        }

        .sidebar-content::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 3px;
        }

        .sidebar-content::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-print me-2"></i>Copisteria Pro</h1>
                    <div class="nav-info">
                        <i class="fas fa-user me-2"></i>Connecté en tant que: <strong>Administrator</strong>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <button class="btn btn-logout" onclick="logout()">
                        <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Container principal -->
    <div class="main-container">
        <!-- Zone de téléchargement -->
        <div class="upload-zone fade-in">
            <div class="upload-header">
                <i class="fas fa-cloud-upload-alt me-2"></i>
                Téléchargement de documents
            </div>
            <div class="upload-content">
                <div class="drop-zone" id="dropZone">
                    <i class="fas fa-cloud-upload-alt upload-icon"></i>
                    <div class="upload-text">Glissez vos fichiers ici</div>
                    <div class="upload-subtext">
                        ou cliquez pour parcourir<br>
                        <small>Formats supportés: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX<br>
                        Taille max: 50 MB par fichier</small>
                    </div>
                    <button class="btn btn-browse mt-3">
                        <i class="fas fa-folder-open me-2"></i>Parcourir les fichiers
                    </button>
                    <input type="file" id="fileInput" multiple hidden accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx">
                </div>
                
                <div class="file-list" id="fileList" style="display: none;">
                    <h6 class="mb-3">
                        <i class="fas fa-file-alt me-2"></i>Fichiers sélectionnés:
                    </h6>
                </div>
            </div>
        </div>

        <!-- Sidebar de configuration - PLUS LARGE -->
        <div class="config-sidebar fade-in">
            <div class="sidebar-header">
                <i class="fas fa-cog"></i>
                Configuration d'impression
            </div>
            <div class="sidebar-content">
                <!-- Format -->
                <div class="config-section">
                    <div class="section-title">
                        <i class="fas fa-file-alt"></i>
                        Format de papier
                    </div>
                    <div class="option-group">
                        <div class="option-item active" data-format="a4">
                            <i class="fas fa-file-alt"></i>
                            <div class="option-text">A4</div>
                        </div>
                        <div class="option-item" data-format="a3">
                            <i class="fas fa-file"></i>
                            <div class="option-text">A3</div>
                        </div>
                        <div class="option-item" data-format="letter">
                            <i class="fas fa-file-text"></i>
                            <div class="option-text">Letter</div>
                        </div>
                        <div class="option-item" data-format="legal">
                            <i class="fas fa-file-contract"></i>
                            <div class="option-text">Legal</div>
                        </div>
                    </div>
                </div>

                <!-- Impression -->
                <div class="config-section">
                    <div class="section-title">
                        <i class="fas fa-print"></i>
                        Type d'impression
                    </div>
                    <div class="option-group">
                        <div class="option-item active" data-print="simple">
                            <i class="fas fa-file-alt"></i>
                            <div class="option-text">Simple</div>
                        </div>
                        <div class="option-item" data-print="recto-verso">
                            <i class="fas fa-copy"></i>
                            <div class="option-text">Recto-Verso</div>
                        </div>
                    </div>
                </div>

                <!-- Couleur -->
                <div class="config-section">
                    <div class="section-title">
                        <i class="fas fa-palette"></i>
                        Couleur d'impression
                    </div>
                    <div class="option-group">
                        <div class="option-item active" data-color="nb">
                            <i class="fas fa-circle" style="color: #6c757d;"></i>
                            <div class="option-text">Noir & Blanc</div>
                        </div>
                        <div class="option-item" data-color="color">
                            <i class="fas fa-circle" style="color: #dc3545;"></i>
                            <div class="option-text">Couleur</div>
                        </div>
                    </div>
                </div>

                <!-- Reliure -->
                <div class="config-section">
                    <div class="section-title">
                        <i class="fas fa-book"></i>
                        Type de reliure
                    </div>
                    <div class="option-group">
                        <div class="option-item active" data-binding="none">
                            <i class="fas fa-file-alt"></i>
                            <div class="option-text">Aucune</div>
                        </div>
                        <div class="option-item" data-binding="agrafage">
                            <i class="fas fa-paperclip"></i>
                            <div class="option-text">Agrafage</div>
                        </div>
                        <div class="option-item" data-binding="spirale">
                            <i class="fas fa-dharmachakra"></i>
                            <div class="option-text">Spirale</div>
                        </div>
                        <div class="option-item" data-binding="thermique">
                            <i class="fas fa-book"></i>
                            <div class="option-text">Thermique</div>
                        </div>
                    </div>
                </div>

                <!-- Couleur spirale -->
                <div class="config-section" id="spiralColorSection" style="display: none;">
                    <div class="section-title">
                        <i class="fas fa-palette"></i>
                        Couleur de la spirale
                    </div>
                    <div class="color-options">
                        <div class="color-item active" data-spiral-color="noir" style="background: #2c3e50; color: white;">Noir</div>
                        <div class="color-item" data-spiral-color="blanc" style="background: #ecf0f1; color: #2c3e50;">Blanc</div>
                        <div class="color-item" data-spiral-color="rouge" style="background: #e74c3c; color: white;">Rouge</div>
                        <div class="color-item" data-spiral-color="bleu" style="background: #3498db; color: white;">Bleu</div>
                        <div class="color-item" data-spiral-color="vert" style="background: #27ae60; color: white;">Vert</div>
                        <div class="color-item" data-spiral-color="jaune" style="background: #f1c40f; color: #2c3e50;">Jaune</div>
                        <div class="color-item" data-spiral-color="orange" style="background: #e67e22; color: white;">Orange</div>
                        <div class="color-item" data-spiral-color="violet" style="background: #9b59b6; color: white;">Violet</div>
                        <div class="color-item" data-spiral-color="gris" style="background: #7f8c8d; color: white;">Gris</div>
                    </div>
                </div>

                <!-- Copies -->
                <div class="config-section">
                    <div class="section-title">
                        <i class="fas fa-copy"></i>
                        Nombre de copies
                    </div>
                    <div class="row g-2">
                        <div class="col-8">
                            <input type="number" class="form-control form-control-lg" id="copies" min="1" max="1000" value="1">
                        </div>
                        <div class="col-4">
                            <div class="btn-group-vertical w-100">
                                <button class="btn btn-outline-primary btn-sm" onclick="incrementCopies()">
                                    <i class="fas fa-plus"></i>
                                </button>
                                <button class="btn btn-outline-primary btn-sm" onclick="decrementCopies()">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Prix total -->
                <div class="total-price">
                    <div class="price-label">Prix total estimé</div>
                    <div class="price-amount" id="totalPrice">0.00 €</div>
                </div>
            </div>

            <!-- Boutons d'action -->
            <div class="action-buttons">
                <button class="btn btn-modern btn-calculate" onclick="calculatePrice()">
                    <i class="fas fa-calculator me-2"></i>Calculer
                </button>
                <button class="btn btn-modern btn-order" onclick="processOrder()">
                    <i class="fas fa-shopping-cart me-2"></i>Commander
                </button>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // État de la configuration
        let printConfig = {
            format: 'a4',
            printType: 'simple',
            color: 'nb',
            binding: 'none',
            spiralColor: 'noir',
            copies: 1
        };

        // Prix de base (en euros)
        const basePrices = {
            simple_nb: { a4: 0.05, a3: 0.10, letter: 0.05, legal: 0.06 },
            simple_color: { a4: 0.15, a3: 0.30, letter: 0.15, legal: 0.18 },
            recto_verso_nb: { a4: 0.08, a3: 0.16, letter: 0.08, legal: 0.10 },
            recto_verso_color: { a4: 0.25, a3: 0.50, letter: 0.25, legal: 0.30 }
        };

        const bindingPrices = {
            none: 0,
            agrafage: 0.50,
            spirale: 2.00,
            thermique: 5.00
        };

        let uploadedFiles = [];

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            initializeEventListeners();
            updatePrice();
        });

        function initializeEventListeners() {
            // Gestion du drag & drop
            const dropZone = document.getElementById('dropZone');
            const fileInput = document.getElementById('fileInput');

            dropZone.addEventListener('click', () => fileInput.click());
            dropZone.addEventListener('dragover', handleDragOver);
            dropZone.addEventListener('dragleave', handleDragLeave);
            dropZone.addEventListener('drop', handleDrop);
            fileInput.addEventListener('change', handleFileSelect);

            // Gestion des options de configuration
            document.querySelectorAll('.option-item').forEach(item => {
                item.addEventListener('click', handleOptionClick);
            });

            document.querySelectorAll('.color-item').forEach(item => {
                item.addEventListener('click', handleColorClick);
            });

            // Gestion du nombre de copies
            document.getElementById('copies').addEventListener('input', function() {
                printConfig.copies = parseInt(this.value) || 1;
                updatePrice();
            });
        }

        function handleDragOver(e) {
            e.preventDefault();
            e.stopPropagation();
            e.currentTarget.classList.add('dragover');
        }

        function handleDragLeave(e) {
            e.preventDefault();
            e.stopPropagation();
            e.currentTarget.classList.remove('dragover');
        }

        function handleDrop(e) {
            e.preventDefault();
            e.stopPropagation();
            e.currentTarget.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            processFiles(files);
        }

        function handleFileSelect(e) {
            const files = e.target.files;
            processFiles(files);
        }

        function processFiles(files) {
            const allowedTypes = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation'
            ];

            Array.from(files).forEach(file => {
                if (allowedTypes.includes(file.type) && file.size <= 50 * 1024 * 1024) {
                    uploadedFiles.push({
                        name: file.name,
                        size: file.size,
                        type: file.type,
                        file: file,
                        pages: Math.ceil(Math.random() * 20) + 1 // Simulation du nombre de pages
                    });
                } else {
                    alert(`Le fichier ${file.name} n'est pas supporté ou dépasse 50MB`);
                }
            });

            displayFiles();
            updatePrice();
        }

        function displayFiles() {
            const fileList = document.getElementById('fileList');
            if (uploadedFiles.length === 0) {
                fileList.style.display = 'none';
                return;
            }

            fileList.style.display = 'block';
            fileList.innerHTML = `
                <h6 class="mb-3">
                    <i class="fas fa-file-alt me-2"></i>Fichiers sélectionnés:
                </h6>
            `;

            uploadedFiles.forEach((file, index) => {
                const fileIcon = getFileIcon(file.type);
                const fileSize = formatFileSize(file.size);
                
                const fileItem = document.createElement('div');
                fileItem.className = 'file-item';
                fileItem.innerHTML = `
                    <i class="${fileIcon} file-icon"></i>
                    <div class="flex-grow-1">
                        <div class="fw-bold">${file.name}</div>
                        <small class="text-muted">${fileSize} • ${file.pages} page(s)</small>
                    </div>
                    <button class="btn btn-sm btn-outline-danger" onclick="removeFile(${index})">
                        <i class="fas fa-trash"></i>
                    </button>
                `;
                fileList.appendChild(fileItem);
            });
        }

        function getFileIcon(type) {
            const icons = {
                'application/pdf': 'fas fa-file-pdf text-danger',
                'application/msword': 'fas fa-file-word text-primary',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document': 'fas fa-file-word text-primary',
                'application/vnd.ms-excel': 'fas fa-file-excel text-success',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': 'fas fa-file-excel text-success',
                'application/vnd.ms-powerpoint': 'fas fa-file-powerpoint text-warning',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation': 'fas fa-file-powerpoint text-warning'
            };
            return icons[type] || 'fas fa-file';
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function removeFile(index) {
            uploadedFiles.splice(index, 1);
            displayFiles();
            updatePrice();
        }

        function handleOptionClick(e) {
            const item = e.currentTarget;
            const section = item.closest('.config-section');
            
            // Retirer active de tous les items de la section
            section.querySelectorAll('.option-item').forEach(opt => opt.classList.remove('active'));
            
            // Ajouter active à l'item cliqué
            item.classList.add('active');

            // Mettre à jour la configuration
            if (item.dataset.format) {
                printConfig.format = item.dataset.format;
            } else if (item.dataset.print) {
                printConfig.printType = item.dataset.print;
            } else if (item.dataset.color) {
                printConfig.color = item.dataset.color;
            } else if (item.dataset.binding) {
                printConfig.binding = item.dataset.binding;
                
                // Afficher/masquer la section couleur spirale
                const spiralSection = document.getElementById('spiralColorSection');
                if (item.dataset.binding === 'spirale') {
                    spiralSection.style.display = 'block';
                } else {
                    spiralSection.style.display = 'none';
                }
            }

            updatePrice();
        }

        function handleColorClick(e) {
            const item = e.currentTarget;
            const section = item.closest('.config-section');
            
            // Retirer active de tous les items de couleur
            section.querySelectorAll('.color-item').forEach(color => color.classList.remove('active'));
            
            // Ajouter active à l'item cliqué
            item.classList.add('active');

            if (item.dataset.spiralColor) {
                printConfig.spiralColor = item.dataset.spiralColor;
            }

            updatePrice();
        }

        function incrementCopies() {
            const copiesInput = document.getElementById('copies');
            const currentValue = parseInt(copiesInput.value) || 1;
            const newValue = Math.min(currentValue + 1, 1000);
            copiesInput.value = newValue;
            printConfig.copies = newValue;
            updatePrice();
        }

        function decrementCopies() {
            const copiesInput = document.getElementById('copies');
            const currentValue = parseInt(copiesInput.value) || 1;
            const newValue = Math.max(currentValue - 1, 1);
            copiesInput.value = newValue;
            printConfig.copies = newValue;
            updatePrice();
        }

        function updatePrice() {
            if (uploadedFiles.length === 0) {
                document.getElementById('totalPrice').textContent = '0.00 €';
                return;
            }

            // Calculer le nombre total de pages
            const totalPages = uploadedFiles.reduce((sum, file) => sum + file.pages, 0);

            // Déterminer la clé de prix
            const priceKey = `${printConfig.printType}_${printConfig.color}`;
            const pricePerPage = basePrices[priceKey][printConfig.format] || 0.05;
            
            // Calculer le coût de base
            let totalCost = totalPages * pricePerPage * printConfig.copies;
            
            // Ajouter le coût de la reliure (par document, pas par page)
            const bindingCost = bindingPrices[printConfig.binding] * uploadedFiles.length * printConfig.copies;
            totalCost += bindingCost;

            document.getElementById('totalPrice').textContent = totalCost.toFixed(2) + ' €';
        }

        function calculatePrice() {
            if (uploadedFiles.length === 0) {
                alert('Veuillez d\'abord sélectionner des fichiers à imprimer.');
                return;
            }

            // Animation du bouton
            const btn = document.querySelector('.btn-calculate');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Calcul...';
            btn.disabled = true;

            // Simuler un calcul côté serveur
            setTimeout(() => {
                updatePrice();
                btn.innerHTML = originalText;
                btn.disabled = false;
                
                // Afficher une notification
                showNotification('Prix calculé avec succès!', 'success');
            }, 1000);
        }

        function processOrder() {
            if (uploadedFiles.length === 0) {
                alert('Veuillez d\'abord sélectionner des fichiers à imprimer.');
                return;
            }

            // Animation du bouton
            const btn = document.querySelector('.btn-order');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Traitement...';
            btn.disabled = true;

            // Simuler le traitement de la commande
            setTimeout(() => {
                const orderData = {
                    files: uploadedFiles.map(f => ({
                        name: f.name,
                        pages: f.pages
                    })),
                    config: printConfig,
                    totalPrice: document.getElementById('totalPrice').textContent
                };

                console.log('Commande traitée:', orderData);
                
                btn.innerHTML = originalText;
                btn.disabled = false;
                
                // Afficher une notification de succès
                showNotification('Commande envoyée avec succès!', 'success');
                
                // Réinitialiser le formulaire
                setTimeout(() => {
                    resetForm();
                }, 2000);
            }, 2000);
        }

        function resetForm() {
            uploadedFiles = [];
            displayFiles();
            
            // Réinitialiser les options par défaut
            document.querySelectorAll('.option-item').forEach(item => item.classList.remove('active'));
            document.querySelectorAll('.color-item').forEach(item => item.classList.remove('active'));
            
            // Réactiver les options par défaut
            document.querySelector('[data-format="a4"]').classList.add('active');
            document.querySelector('[data-print="simple"]').classList.add('active');
            document.querySelector('[data-color="nb"]').classList.add('active');
            document.querySelector('[data-binding="none"]').classList.add('active');
            document.querySelector('[data-spiral-color="noir"]').classList.add('active');
            
            // Masquer la section couleur spirale
            document.getElementById('spiralColorSection').style.display = 'none';
            
            // Réinitialiser la configuration
            printConfig = {
                format: 'a4',
                printType: 'simple',
                color: 'nb',
                binding: 'none',
                spiralColor: 'noir',
                copies: 1
            };
            
            document.getElementById('copies').value = 1;
            updatePrice();
        }

        function showNotification(message, type = 'info') {
            // Créer une notification toast
            const toast = document.createElement('div');
            toast.className = `alert alert-${type === 'success' ? 'success' : 'info'} position-fixed top-0 end-0 m-3`;
            toast.style.zIndex = '9999';
            toast.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            // Supprimer automatiquement après 3 secondes
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, 3000);
        }

        function logout() {
            if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
                // Ici vous pouvez ajouter la logique de déconnexion
                window.location.href = 'auth/logout.php';
            }
        }

        // Animation au scroll
        window.addEventListener('scroll', function() {
            const scrolled = window.pageYOffset;
            const parallax = document.querySelector('.header');
            const speed = scrolled * 0.5;
            parallax.style.transform = `translateY(${speed}px)`;
        });

        // Effet de particules (optionnel)
        function createParticles() {
            const particlesContainer = document.createElement('div');
            particlesContainer.className = 'particles-container';
            particlesContainer.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                pointer-events: none;
                z-index: -1;
            `;
            
            document.body.appendChild(particlesContainer);
            
            for (let i = 0; i < 50; i++) {
                const particle = document.createElement('div');
                particle.style.cssText = `
                    position: absolute;
                    width: 2px;
                    height: 2px;
                    background: rgba(44, 90, 160, 0.1);
                    border-radius: 50%;
                    animation: float ${Math.random() * 20 + 10}s infinite linear;
                `;
                
                particle.style.left = Math.random() * 100 + '%';
                particle.style.top = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 20 + 's';
                
                particlesContainer.appendChild(particle);
            }
        }

        // Style pour l'animation des particules
        const style = document.createElement('style');
        style.textContent = `
            @keyframes float {
                0% {
                    transform: translateY(100vh) rotate(0deg);
                    opacity: 0;
                }
                10% {
                    opacity: 1;
                }
                90% {
                    opacity: 1;
                }
                100% {
                    transform: translateY(-100vh) rotate(360deg);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);

        // Initialiser les particules (optionnel)
        // createParticles();
    </script>
</body>
</html>