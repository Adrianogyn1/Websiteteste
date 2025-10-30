<?php include __DIR__.'/includes/header.php'; ?>




    <style>
        body {
            background-color: #f4f7fa;
        }
        .avatar-card {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .controls-card {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        /* Estilo para simular avatar de chat/perfil */
        #avatar-container {
            width: 150px; 
            height: 150px; 
            margin: 0 auto 15px;
        }
        /* Estilização das opções de cor */
        .color-option {
            width: 25px;
            height: 25px;
            border-radius: 50%;
            border: 1px solid #00000030;
            display: inline-block;
            cursor: pointer;
            margin: 3px;
        }
        .color-option:hover, .color-option.active-selection {
            opacity: 0.8;
            transform: scale(1.1);
            border: 2px solid #0d6efd; /* Borda azul para seleção ativa */
        }
        .btn-group-sm {
            flex-wrap: wrap; 
        }
        .btn-option.active-selection {
            background-color: #0d6efd;
            color: white;
            border-color: #0d6efd;
        }
        .sticky-top {
            top: 20px; /* Ajuda a manter o avatar visível durante o scroll */
        }
        .text-json {
            font-family: monospace;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
            word-break: break-all;
        }
    </style>
</head>
<body>

    <div class="container my-5">
        <h1 class="text-center mb-4 text-primary">🎨 Gerador de Avatares</h1>
        
        <div class="row">
            
            <div class="col-md-4 mb-4">
                <div class="avatar-card text-center sticky-top">
                    <h5 class="mb-3">Seu Avatar</h5>
                    <div id="avatar-container">
                        <div class="spinner-border text-primary" role="status">
                          <span class="visually-hidden">Carregando...</span>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 mt-4">
                        <button id="randomize-btn" class="btn btn-warning">
                            <i class="fas fa-dice"></i> Aleatório
                        </button>
                        <button id="save-btn" class="btn btn-success">
                            <i class="fas fa-download"></i> Download
                        </button>
                        
                        <button id="save-to-db-btn" class="btn btn-primary mt-2">
                            <i class="fas fa-database"></i> Salvar
                        </button>
                    </div>
                    
                    <div id="db-feedback" class="mt-3" style="display:none">
                        <small class="text-success fw-bold">JSON Enviado:</small>
                        <p class="text-json"><?=$user->avatar?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="controls-card">
                    <h4 class="mb-3">Opções de Personalização</h4>
                    <div class="accordion" id="accordionControls">
                        </div>
                </div>
            </div>
        </div>
    </div>

     <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

<?php echo json_encode($user);?>

<?php echo json_encode($user);?>

<script>
    // Opções Padrão
    let currentOptions='';

    // --- BLOCO PHP PARA CARREGAR DO BANCO DE DADOS ---
    <?php 
    // 1. Verifica se existe uma configuração de avatar salva para o usuário
    
   if($user->avatar  && $user->avatar != ""): 
        // 2. Cria uma nova variável JavaScript chamada 'savedOptionsJson'
        // IMPORTANTE: use json_encode() para garantir que a string JSON PHP seja válida em JS
        // O $user->avatar DEVE ser uma string JSON válida aqui.
    ?>
    
  //  let savedOptionsJson ='[]';

    try {
      savedOptionsJson=  <?php echo json_encode($user->avatar);?>
        // Faz o parse da string JSON para um objeto JavaScript
       // const savedOptions = JSON.parse(savedOptionsJson);
        
        // Sobrescreve as opções padrão com as opções salvas
       currentOptions = {
            ...currentOptions, // Mantém o style, width e height
            ...savedOptions    // Aplica as configurações salvas (top, skin, eyes, etc.)
       };

        console.log("Avatar carregado do BD!");

    } catch (e) {
        currentOptions='';
        console.error("Erro ao fazer parse da configuração do avatar do banco de dados:", e);
    }

    <?php 
    endif; 
    ?>
    // --------------------------------------------------

    // ... (O restante do seu código JavaScript, incluindo renderAvatar(), saveAvatar(), etc., continua aqui)
</script>


    <script>
        // Opções Iniciais (Válidas)
        if(currentOptions ==''){
         currentOptions = {
            style: 'circle',
            top: 'shortWaved', 
            skin: 'light', 
            clothing: 'hoodie',
            clothingColor: 'blue03',
            eyes: 'happy',
            eyebrows: 'default',
            mouth: 'smile',
            hairColor: 'brown',
            facialHair: null, // null é o valor correto para 'none'
            facialHairColor: 'brown',
            accessories: null, // null é o valor correto para 'none'
            width: "150",
            height: "150"
        };
        }

        
        // --- Função Principal: Renderizar/Atualizar o Avatar ---
        function renderAvatar() {
            try {
                // A função Avataaars.create() retorna a string SVG
                const svg = Avataaars.create(currentOptions);
                $('#avatar-container').html(svg);
            } catch (e) {
                console.error("Erro ao gerar avatar:", e);
                $('#avatar-container').html('<div class="alert alert-danger">Erro ao carregar.</div>');
            }
        }

        // --- NOVO: Função para Salvar a Configuração (Simulação de Envio ao BD) ---
        function sendConfigToDatabase() {
            // Remove as propriedades de tamanho (width, height, style) antes de salvar,
            // pois elas são de renderização e não de configuração do avatar.
            const configToSave = {...currentOptions};
            delete configToSave.width;
            delete configToSave.height;
            delete configToSave.style;
            
            // 1. Converte o objeto JavaScript em uma string JSON para envio
            const configJson = JSON.stringify(configToSave, null, 2); // null, 2 para formatar bonito
            const avatar= configJson;
            // *** ESTE É O PONTO CHAVE DA INTEGRAÇÃO COM O BACKEND ***
            
            // Simulação de envio via AJAX/Fetch
            // Em produção, você usaria $.ajax ou fetch() para enviar isso ao seu servidor
            
            /**/ $.ajax({
                url: '/api/user/avatar.php', 
                type: 'POST',
                contentType: 'application/json',
                data: configJson,//{'avatar':json_encode(avatar)},
                success: function(response) {
                    if(response.sucess)
                    alert('Avatar salvo com sucesso!');
                    else
                    alert('Erro ao salvar avatar!');
                },
                error: function(xhr, status, error) {
                    console.error('Erro no servidor:', error);
                }
            });
            /**/

            // Feedback visual no frontend
          // $('#db-feedback').show();
         //   $('#db-feedback p').text(configJson);
           // alert('Configuração do avatar pronta para envio (JSON exibido abaixo).');
        }


        // --- Função para Salvar o Avatar como Arquivo SVG ---
        function saveAvatar() {
            const svgString = Avataaars.create(currentOptions);
            const blob = new Blob([svgString], { type: 'image/svg+xml' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            
            a.href = url;
            a.download = 'meu_avataaars.svg'; 
            
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        // --- Opções Disponíveis para os Botões ---
        const editableTypes = [
            { id: 'top', label: 'Cabelo/Chapéu', map: Avataaars.paths.top },
            { id: 'hairColor', label: 'Cor do Cabelo', map: Avataaars.colors.hair },
            { id: 'skin', label: 'Cor da Pele', map: Avataaars.colors.skin },
            { id: 'facialHair', label: 'Barba/Bigode', map: Avataaars.paths.facialHair },
            { id: 'facialHairColor', label: 'Cor da Barba', map: Avataaars.colors.hair },
            { id: 'eyebrows', label: 'Sobrancelhas', map: Avataaars.paths.eyebrows },
            { id: 'eyes', label: 'Olhos', map: Avataaars.paths.eyes },
            { id: 'mouth', label: 'Boca', map: Avataaars.paths.mouth },
            { id: 'accessories', label: 'Acessórios', map: Avataaars.paths.accessories },
            { id: 'clothing', label: 'Roupa', map: Avataaars.paths.clothing },
            { id: 'clothingColor', label: 'Cor da Roupa', map: Avataaars.colors.palette },
        ];

        // --- Geração Dinâmica da Sanfona (jQuery) ---
        $(document).ready(function() {
            const $accordion = $('#accordionControls');

            editableTypes.forEach((type, index) => {
                const typeId = type.id;
                const label = type.label;
                const optionsMap = type.map;
                const collapseId = `collapse-${typeId}`;
                
                // 1. Cria a Estrutura do Item do Accordion
                const $item = $(`<div class="accordion-item">
                    <h2 class="accordion-header" id="heading-${typeId}">
                        <button class="accordion-button ${index > 0 ? 'collapsed' : ''}" type="button" data-bs-toggle="collapse" data-bs-target="#${collapseId}" aria-expanded="${index === 0}" aria-controls="${collapseId}">
                            ${label}
                        </button>
                    </h2>
                    <div id="${collapseId}" class="accordion-collapse collapse ${index === 0 ? 'show' : ''}" aria-labelledby="heading-${typeId}" data-bs-parent="#accordionControls">
                        <div class="accordion-body">
                            <div class="d-flex flex-wrap" id="group-${typeId}">
                                </div>
                        </div>
                    </div>
                </div>`);

                const $btnContainer = $item.find(`#group-${typeId}`);

                // 2. Cria os Botões/Opções
                Object.keys(optionsMap).forEach(optionKey => {
                    const optionValue = optionKey;
                    
                    let formattedLabel = optionKey.charAt(0).toUpperCase() + optionKey.slice(1)
                                           .replace(/([A-Z])/g, ' $1')
                                           .replace(/None/, 'Nenhum');
                    
                    // A. Opções de Cores
                    if (typeId.includes('Color') || typeId === 'skin') {
                        const hexCode = optionsMap[optionKey];
                        const isActive = currentOptions[typeId] === optionValue ? 'active-selection' : '';
                        btnHtml = `
                            <span class="color-option ${isActive}" data-type="${typeId}" data-value="${optionValue}" 
                                style="background-color: ${hexCode};" title="${formattedLabel}">
                            </span>
                        `;
                    // B. Opções de Paths/Estilos
                    } else {
                        // Trata o valor "none" como null na lógica do Avataaars
                        const finalValue = (optionKey === 'none' && (typeId === 'facialHair' || typeId === 'accessories')) ? null : optionKey;
                        const isActive = currentOptions[typeId] === optionKey ? 'active-selection' : '';
                        
                        btnHtml = `
                            <button type="button" class="btn btn-sm btn-outline-secondary m-1 btn-option ${isActive}" 
                                data-type="${typeId}" data-value="${finalValue}">
                                ${formattedLabel}
                            </button>
                        `;
                    }

                    $btnContainer.append(btnHtml);
                });

                $accordion.append($item);
            });

            // --- Gerenciamento de Eventos (jQuery) ---

            // Evento de Clique nos Botões de Opção (Texto e Cor)
            $('.btn-option, .color-option').on('click', function() {
                const type = $(this).data('type');
                const value = $(this).data('value');

                // 1. Atualiza as opções do avatar
                currentOptions[type] = value;

                // 2. Renderiza o novo avatar
                renderAvatar();

                // 3. Atualiza o estado visual de seleção 
                const $parentGroup = $(this).closest('.d-flex');
                $parentGroup.find('.active-selection').removeClass('active-selection');
                $(this).addClass('active-selection');
            });
            
            // Evento de Clique no Botão Salvar SVG (Arquivo)
            $('#save-btn').on('click', saveAvatar);

            // NOVO: Evento de Clique no Botão Salvar Configuração (Banco de Dados)
            $('#save-to-db-btn').on('click', sendConfigToDatabase);


            // Randomização completa
            $('#randomize-btn').on('click', function() {
                editableTypes.forEach(type => {
                    const options = Object.keys(type.map);
                    const randomKey = options[Math.floor(Math.random() * options.length)];
                    let randomValue = randomKey;

                    if (randomKey === 'none' && (type.id === 'facialHair' || type.id === 'accessories')) {
                        randomValue = null;
                    }
                    currentOptions[type.id] = randomValue;
                });
                
                renderAvatar();
                $('.active-selection').removeClass('active-selection');
            });


            // Renderiza o avatar inicial ao carregar a página
            renderAvatar();
        });
    </script>
<?php include __DIR__.'/includes/footer.php'; ?>
